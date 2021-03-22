<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','deptaccount_no','amt_transfer'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepInsideCoop') ||
	$func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferSelfDepInsideCoop')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$to_deptaccount_no = preg_replace('/-/','',$dataComing["to_deptaccount_no"]);
		$from_deptaccount_no = preg_replace('/-/','',$dataComing["deptaccount_no"]);
		$checkWithdraw = $cal_dep->depositCheckWithdrawRights($from_deptaccount_no,$dataComing["amt_transfer"],$dataComing["menu_component"]);
		if($checkWithdraw["RESULT"]){
			$checkDeposit = $cal_dep->depositCheckDepositRights($to_deptaccount_no,$dataComing["amt_transfer"],$dataComing["menu_component"]);
			if($checkDeposit["RESULT"]){
				$getMemberNo = $conmysql->prepare("SELECT member_no FROM gcuserallowacctransaction WHERE deptaccount_no = :deptaccount_no and is_use = '1'");
				$getMemberNo->execute([':deptaccount_no' => $to_deptaccount_no]);
				$rowMember_noDest = $getMemberNo->fetch(PDO::FETCH_ASSOC);
				$member_no_dest = $configAS[$rowMember_noDest["member_no"]] ?? $rowMember_noDest["member_no"];
				$arrHeaderAPI[] = 'Req-trans : '.date('YmdHis');
				$arrDataAPI["MemberID"] = substr($member_no,-6);
				$arrDataAPI["FromCoopAccountNo"] = $from_deptaccount_no;
				$arrDataAPI["ToMemberID"] = substr($member_no_dest,-6);
				$arrDataAPI["ToCoopAccountNo"] = $to_deptaccount_no;
				$arrDataAPI["TransferAmount"] = $dataComing["amt_transfer"];
				$arrDataAPI["UserRequestDate"] = date('c');
				$arrResponseAPI = $lib->posting_dataAPI($config["URL_SERVICE_EGAT"]."Account/CheckTransferFee",$arrDataAPI,$arrHeaderAPI);
				if(!$arrResponseAPI["RESULT"]){
					$filename = basename(__FILE__, '.php');
					$logStruc = [
						":error_menu" => $filename,
						":error_code" => "WS9999",
						":error_desc" => "Cannot connect server Deposit API ".$config["URL_SERVICE_EGAT"]."Account/CheckTransferFee",
						":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
					];
					$log->writeLog('errorusage',$logStruc);
					$message_error = "ไฟล์ ".$filename." Cannot connect server Deposit API ".$config["URL_SERVICE_EGAT"]."Account/CheckTransferFee";
					$lib->sendLineNotify($message_error);
					$func->MaintenanceMenu($dataComing["menu_component"]);
					$arrayResult['RESPONSE_CODE'] = "WS9999";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}
				$arrResponseAPI = json_decode($arrResponseAPI);
				if($arrResponseAPI->responseCode == "200"){
					$arrayResult['PENALTY_AMT'] = preg_replace('/,/', '', $arrResponseAPI->coopFee);
					$arrayResult['PENALTY_AMT_FORMAT'] = $arrResponseAPI->coopFee;
					$arrayResult['TRANS_REF_CODE'] = $arrResponseAPI->transferRefCode;
					if((int)$arrayResult['PENALTY_AMT'] > 0){
						$arrayCaution['RESPONSE_MESSAGE'] = $configError["CAUTION_WITHDRAW"][0][$lang_locale];
						$arrayCaution['CANCEL_TEXT'] = $configError["BUTTON_TEXT"][0]["CANCEL_TEXT"][0][$lang_locale];
						$arrayCaution['CONFIRM_TEXT'] = $configError["BUTTON_TEXT"][0]["CONFIRM_TEXT"][0][$lang_locale];
						$arrayResult['CAUTION'] = $arrayCaution;
					}
					$arrayResult['RESULT'] = TRUE;
					require_once('../../include/exit_footer.php');
				}else{
					$arrayResult['RESPONSE_CODE'] = "WS9001";
					if($arrResponseAPI->responseCode == '415'){
						$type_account = substr($from_deptaccount_no,3,2);
						if($type_account == '10'){
							$accountDesc = "NORMAL";
						}else{
							$accountDesc = "SPECIAL";
						}
						$arrayResult['RESPONSE_MESSAGE'] = $configError["SAVING_EGAT_ERR"][0][$arrResponseAPI->responseCode][0][$accountDesc][0][$lang_locale];
					}else{
						if(isset($configError["SAVING_EGAT_ERR"][0][$arrResponseAPI->responseCode][0][$lang_locale])){
							$arrayResult['RESPONSE_MESSAGE'] = $configError["SAVING_EGAT_ERR"][0][$arrResponseAPI->responseCode][0][$lang_locale];
						}else{
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						}
					}
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}
			}else{
				$arrayResult['RESPONSE_CODE'] = $checkDeposit["RESPONSE_CODE"];
				if($checkDeposit["RESPONSE_CODE"] == 'WS0056'){
					$arrayResult['RESPONSE_MESSAGE'] = str_replace('${min_amount_deposit}',number_format($checkDeposit["MINWITD_AMT"],2),$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				}
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = $checkWithdraw["RESPONSE_CODE"];
			if($checkWithdraw["RESPONSE_CODE"] == 'WS0056'){
				$arrayResult['RESPONSE_MESSAGE'] = str_replace('${min_amount_deposit}',number_format($checkWithdraw["MINWITD_AMT"],2),$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			}
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../include/exit_footer.php');
		
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
	require_once('../../include/exit_footer.php');
	
}
?>