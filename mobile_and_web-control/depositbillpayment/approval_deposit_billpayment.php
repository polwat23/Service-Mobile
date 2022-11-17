<?php
ini_set('default_socket_timeout', 300);
require_once('../autoload.php');

if($lib->checkCompleteArgument(['amt_transfer','tran_id'],$dataComing)){
	$lang_locale = 'en';
	$checkBillAvailable = $conmysql->prepare("SELECT transfer_status,expire_date,member_no,qrtransfer_amt FROM gcqrcodegenmaster 
											WHERE qrgenerate = :tran_id and member_no = :member_no");
	$checkBillAvailable->execute([
		':tran_id' => $dataComing["tran_id"],
		':member_no' => $dataComing["member_no"]
	]);
	if($checkBillAvailable->rowCount() > 0){
		$rowCheckBill = $checkBillAvailable->fetch(PDO::FETCH_ASSOC);
		if($rowCheckBill["member_no"] == $dataComing["member_no"]){
			if($rowCheckBill["qrtransfer_amt"] == $dataComing["amt_transfer"]){
				if($rowCheckBill["transfer_status"] == '0'){
					if(date('YmdHis',strtotime($rowCheckBill["expire_date"])) > date('YmdHis')){
						
						$getPayAccFee = $conmysql->prepare("SELECT gba.account_payfee,cs.fee_deposit FROM gcbindaccount gba 
															LEFT JOIN csbankdisplay cs ON gba.bank_code = cs.bank_code
															WHERE gba.member_no = :member_no 
															and gba.bindaccount_status = '1' and gba.bank_code = '999'");
						$getPayAccFee->execute([':member_no' => $dataComing["member_no"]]);
						$rowPayFee = $getPayAccFee->fetch(PDO::FETCH_ASSOC);
						$balanceMin = $cal_dep->getWithdrawable($rowPayFee["account_payfee"]);
						if($balanceMin < $rowPayFee["fee_deposit"]){
							$arrayResult['RESPONSE_CODE'] = "WS0100";
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
							$arrayResult['RESULT'] = FALSE;
							ob_flush();
							echo json_encode($arrayResult);
							exit();
						}
						
						$getDetailTran = $conmysql->prepare("SELECT trans_code_qr,ref_account,qrtransferdt_amt FROM gcqrcodegendetail 
															WHERE qrgenerate = :tran_id");
						$getDetailTran->execute([':tran_id' => $dataComing["tran_id"]]);
						while($rowDetail = $getDetailTran->fetch(PDO::FETCH_ASSOC)){
							if($rowDetail["trans_code_qr"] == '01'){ //ฝากเงิน
								$deptaccount_no = preg_replace('/-/','',$rowDetail["ref_account"]);
								$arrRightDep = $cal_dep->depositCheckDepositRights($deptaccount_no,$rowDetail["qrtransferdt_amt"],"TransactionDeposit","999",false);
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
							}else if($rowDetail["trans_code_qr"] == '02'){ //ชำระหนี้
								$fetchLoanRepay = $conoracle->prepare("SELECT lnm.LCONT_AMOUNT_SAL as principal_balance, lnm.LCONT_PROFIT as int_balance
																	FROM LOAN_M_CONTACT lnm LEFT JOIN LOAN_M_TYPE_NAME lnt ON lnm.L_TYPE_CODE = lnt.L_TYPE_CODE  
																	WHERE lnm.LCONT_ID = :loancontract_no
																	and lnm.LCONT_STATUS_CONT IN('H','A','A1')");
								$fetchLoanRepay->execute([':loancontract_no' => $rowDetail["ref_account"]]);
								$rowLoan = $fetchLoanRepay->fetch(PDO::FETCH_ASSOC);
								$interest = $cal_loan->calculateIntAPI($rowDetail["ref_account"],$rowDetail["qrtransferdt_amt"]);
								if($rowDetail["ref_account"] > ($rowLoan["PRINCIPAL_BALANCE"]) + $rowLoan["INT_BALANCE"]){
									$arrayResult['RESPONSE_CODE'] = "WS0098";
									$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
									$arrayResult['RESULT'] = FALSE;
									ob_flush();
									echo json_encode($arrayResult);
									exit();
								}
							}else if($rowDetail["trans_code_qr"] == '03'){ //ซื้อหุ้น
								$getCurrShare = $conoracle->prepare("SELECT SHR_SUM_BTH FROM SHR_MEM WHERE account_id = :member_no");
								$getCurrShare->execute([':member_no' => $rowDetail["ref_account"]]);
								$rowCurrShare = $getCurrShare->fetch(PDO::FETCH_ASSOC);
								$sharereq_value = $rowCurrShare["SHARESTK_AMT"] + $rowDetail["qrtransferdt_amt"];
								$shareround_factor = 10;
								if($sharereq_value < $shareround_factor){
									$arrayResult['RESPONSE_CODE'] = "WS0075";
									if(isset($configError["BUY_SHARES_ERR"][0]["0003"][0][$lang_locale])){
										$arrayResult['RESPONSE_MESSAGE'] = str_replace('${SHAREROUND_FACTOR}',number_format($shareround_factor,2),$configError["BUY_SHARES_ERR"][0]["0003"][0][$lang_locale]);
									}else{
										$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
									}
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
						$updateQRExpire = $conmysql->prepare("UPDATE gcqrcodegenmaster SET transfer_status = '-9' WHERE qrgenerate = :tran_id");
						$updateQRExpire->execute([':tran_id' => $dataComing["tran_id"]]);
						$arrayResult['RESPONSE_CODE'] = "WS0109";
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						ob_flush();
						echo json_encode($arrayResult);
						exit();
					}
				}else{
					if($rowCheckBill["transfer_status"] == '-9'){
						$arrayResult['RESPONSE_CODE'] = "WS0109";
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						ob_flush();
						echo json_encode($arrayResult);
						exit();
					}else{
						$arrayResult['RESPONSE_CODE'] = "WS0108";
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						ob_flush();
						echo json_encode($arrayResult);
						exit();
					}
				}
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0112";
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
		$arrayResult['RESPONSE_CODE'] = "WS0025";
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
