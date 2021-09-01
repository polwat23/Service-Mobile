<?php
ini_set('default_socket_timeout', 300);
require_once('../autoload.php');

if($lib->checkCompleteArgument(['amt_transfer','tran_id'],$dataComing)){
	$lang_locale = 'en';
	$member_no = $configAS[$dataComing["member_no"]] ?? $dataComing["member_no"];
	$checkBillAvailable = $conmysql->prepare("SELECT transfer_status,expire_date,member_no,qrtransfer_amt FROM gcqrcodegenmaster 
											WHERE qrgenerate = :tran_id");
	$checkBillAvailable->execute([
		':tran_id' => $dataComing["tran_id"]
	]);
	if($checkBillAvailable->rowCount() > 0){
		$rowCheckBill = $checkBillAvailable->fetch(PDO::FETCH_ASSOC);
		if($rowCheckBill["member_no"] == $dataComing["member_no"]){
			if($rowCheckBill["qrtransfer_amt"] == $dataComing["amt_transfer"]){
				if($rowCheckBill["transfer_status"] == '0'){
					if(date('YmdHis',strtotime($rowCheckBill["expire_date"])) > date('YmdHis')){
						$getDetailTran = $conmysql->prepare("SELECT trans_code_qr,ref_account,qrtransferdt_amt FROM gcqrcodegendetail 
															WHERE qrgenerate = :tran_id");
						$getDetailTran->execute([':tran_id' => $dataComing["tran_id"]]);
						while($rowDetail = $getDetailTran->fetch(PDO::FETCH_ASSOC)){
							if($rowDetail["trans_code_qr"] == '001'){ //ฝากเงิน
								$deptaccount_no = preg_replace('/-/','',$rowDetail["ref_account"]);
								$arrRightDep = $cal_dep->depositCheckDepositRights($deptaccount_no,$rowDetail["qrtransferdt_amt"],"TransactionDeposit","006");
								if($arrRightDep["RESULT"]){
									$arrHeaderAPI[] = 'Req-trans : '.date('YmdHis');
									$arrDataAPI["MemberID"] = substr($member_no,-6);
									$arrDataAPI["ToCoopAccountNo"] = $deptaccount_no;
									$arrDataAPI["FromBankCode"] = $lib->mb_str_pad($dataComing["bank_code"],'3');
									$arrDataAPI["FromBankAccountNo"] = $lib->mb_str_pad($dataComing["member_no"],'10');
									$arrDataAPI["TransferFee"] = 0;
									$arrDataAPI["DepositAmount"] = $rowDetail["qrtransferdt_amt"];
									$arrDataAPI["UserRequestDate"] = date('c');
									$arrDataAPI["Note"] = "Check Fee of VerifyData in Deposit";
									$arrResponseAPI = $lib->posting_dataAPI($config["URL_SERVICE_EGAT"]."Account/CheckDepositFee",$arrDataAPI,$arrHeaderAPI);
									if(!$arrResponseAPI["RESULT"]){
										$filename = basename(__FILE__, '.php');
										$logStruc = [
											":error_menu" => $filename,
											":error_code" => "WS9999",
											":error_desc" => "Cannot connect server Deposit API ".$config["URL_SERVICE_EGAT"]."Account/CheckDepositFee",
											":error_device" => $dataComing["member_no"].' ref with Bank App'
										];
										$log->writeLog('errorusage',$logStruc);
										$message_error = "ไฟล์ ".$filename." Cannot connect server Deposit API ".$config["URL_SERVICE_EGAT"]."Account/CheckDepositFee";
										$lib->sendLineNotify($message_error);
										$func->MaintenanceMenu($dataComing["menu_component"]);
										$arrayResult['RESPONSE_CODE'] = "WS9999";
										$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
										$arrayResult['RESULT'] = FALSE;
										ob_flush();
										echo json_encode($arrayResult);
										exit();
										
									}
									$arrResponseAPI = json_decode($arrResponseAPI);
									if($arrResponseAPI->responseCode == "200"){
									}else{
										$arrayResult['RESPONSE_CODE'] = "WS0028";
										if(isset($configError["SAVING_EGAT_ERR"][0][$arrResponseAPI->responseCode][0][$lang_locale])){
											$arrayResult['RESPONSE_MESSAGE'] = $configError["SAVING_EGAT_ERR"][0][$arrResponseAPI->responseCode][0][$lang_locale];
										}else{
											$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
										}
										$arrayResult['RESULT'] = FALSE;
										ob_flush();
										echo json_encode($arrayResult);
										exit();
									}
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
							}else if($rowDetail["trans_code_qr"] == '002'){ //ชำระหนี้
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