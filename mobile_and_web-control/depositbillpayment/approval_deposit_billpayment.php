<?php
ini_set('default_socket_timeout', 300);
require_once('../autoload.php');

if($lib->checkCompleteArgument(['amt_transfer','tran_id'],$dataComing)){
	$lang_locale = 'en';
	$checkBillAvailable = $conmysql->prepare("SELECT transfer_status,expire_date FROM gcqrcodegenmaster 
											WHERE qrgenerate = :tran_id and member_no = :member_no");
	$checkBillAvailable->execute([
		':tran_id' => $dataComing["tran_id"],
		':member_no' => $dataComing["member_no"]
	]);
	if($checkBillAvailable->rowCount() > 0){
		$rowCheckBill = $checkBillAvailable->fetch(PDO::FETCH_ASSOC);
		if($rowCheckBill["transfer_status"] == '0'){
			if(date('YmdHis',strtotime($rowCheckBill["expire_date"])) > date('YmdHis')){
				$getDetailTran = $conmysql->prepare("SELECT trans_code_qr,ref_account,qrtransferdt_amt FROM gcqrcodegendetail 
													WHERE qrgenerate = :tran_id");
				$getDetailTran->execute([':tran_id' => $dataComing["tran_id"]]);
				while($rowDetail = $getDetailTran->fetch(PDO::FETCH_ASSOC)){
					if($rowDetail["trans_code_qr"] == '01'){ //ฝากเงิน
						$deptaccount_no = preg_replace('/-/','',$rowDetail["ref_account"]);
						$checkSeqAmt = $cal_dep->getSequestAmt($deptaccount_no,'DTE');
						if($checkSeqAmt["CAN_DEPOSIT"]){
							$arrRightDep = $cal_dep->depositCheckDepositRights($deptaccount_no,$rowDetail["qrtransferdt_amt"],"TransactionDeposit","006");
							if($arrRightDep["RESULT"]){
							}else{
								$arrayResult['RESPONSE_CODE'] = $arrRightDep["RESPONSE_CODE"];
								if($arrRightDep["RESPONSE_CODE"] == 'WS0056'){
									$arrayResult['RESPONSE_MESSAGE'] = str_replace('${min_amount_deposit}',number_format($arrRightDep["MINDEPT_AMT"],2),$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
								}else{
									$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
								}
								$arrayResult['RESULT'] = FALSE;
								ob_flush();
								echo json_encode($arrayResult);
								exit();
							}
						}else{
							$arrayResult['RESPONSE_CODE'] = "WS0104";
							$arrayResult['RESPONSE_MESSAGE'] = $checkSeqAmt["SEQUEST_DESC"];
							$arrayResult['RESULT'] = FALSE;
							ob_flush();
							echo json_encode($arrayResult);
							exit();
						}
					}else if($rowDetail["trans_code_qr"] == '02'){ //ชำระหนี้
						$fetchLoanRepay = $conoracle->prepare("SELECT PRINCIPAL_BALANCE,INTEREST_RETURN,RKEEP_PRINCIPAL
																FROM lncontmaster
																WHERE loancontract_no = :loancontract_no");
						$fetchLoanRepay->execute([':loancontract_no' => $rowDetail["ref_account"]]);
						$rowLoan = $fetchLoanRepay->fetch(PDO::FETCH_ASSOC);
						$interest = $cal_loan->calculateInterest($rowDetail["ref_account"],$rowDetail["qrtransferdt_amt"]);
						if($rowDetail["qrtransferdt_amt"] > ($rowLoan["PRINCIPAL_BALANCE"] - $rowLoan["RKEEP_PRINCIPAL"]) + $interest){
							$arrayResult['RESPONSE_CODE'] = "WS0098";
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
							$arrayResult['RESULT'] = FALSE;
							ob_flush();
							echo json_encode($arrayResult);
							exit();
						}
					}
				}
				$arrayResult['RESULT'] = TRUE;
				ob_flush();
				echo json_encode($arrayResult);
				exit();
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0109";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				ob_flush();
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0108";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			ob_flush();
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0107";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		ob_flush();
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$filename = basename(__FILE__, '.php');
	$logStruc = [
		":error_menu" => $filename,
		":error_code" => "WS4004",
		":error_desc" => "ส่ง Argument มาไม่ครบ "."\n".json_encode($dataComing),
		":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
	];
	$log->writeLog('errorusage',$logStruc);
	$message_error = "ไฟล์ ".$filename." ส่ง Argument มาไม่ครบมาแค่ "."\n".json_encode($dataComing);
	$lib->sendLineNotify($message_error);
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	ob_flush();
	echo json_encode($arrayResult);
	exit();
}
?>