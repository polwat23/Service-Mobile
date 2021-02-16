<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ManagementAccount')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGroupAccAllow = array();
		$arrAccAllow = array();
		$arrAccAllowDep = array();
		$fetchAccountBeenAllow = $conmysql->prepare("SELECT gat.deptaccount_no,gat.is_use,gat.limit_transaction_amt,gct.allow_showdetail,
														gct.allow_withdraw_outside,gct.allow_withdraw_inside,gct.allow_deposit_outside,gct.allow_deposit_inside,
														gct.allow_buy_share,gct.allow_pay_loan
														FROM gcuserallowacctransaction gat LEFT JOIN gcconstantaccountdept gct ON gat.id_accountconstant = gct.id_accountconstant
														WHERE gat.member_no = :member_no and gat.is_use <> '-9'");
		$fetchAccountBeenAllow->execute([':member_no' => $payload["member_no"]]);
		if($fetchAccountBeenAllow->rowCount() > 0){
			while($rowAccBeenAllow = $fetchAccountBeenAllow->fetch(PDO::FETCH_ASSOC)){
				$arrAccAllowDep[] = $rowAccBeenAllow["deptaccount_no"]; 
				$arrAccAllow[$rowAccBeenAllow["deptaccount_no"]]["deptaccount_no"] = $rowAccBeenAllow["deptaccount_no"];
				$arrAccAllow[$rowAccBeenAllow["deptaccount_no"]]["is_use"] = $rowAccBeenAllow["is_use"];
				$arrAccAllow[$rowAccBeenAllow["deptaccount_no"]]["limit_transaction_amt"] = $rowAccBeenAllow["limit_transaction_amt"];
				$arrAccAllow[$rowAccBeenAllow["deptaccount_no"]]["allow_showdetail"] = $rowAccBeenAllow["allow_showdetail"];
				$arrAccAllow[$rowAccBeenAllow["deptaccount_no"]]["allow_withdraw_outside"] = $rowAccBeenAllow["allow_withdraw_outside"];
				$arrAccAllow[$rowAccBeenAllow["deptaccount_no"]]["allow_withdraw_inside"] = $rowAccBeenAllow["allow_withdraw_inside"];
				$arrAccAllow[$rowAccBeenAllow["deptaccount_no"]]["allow_deposit_outside"] = $rowAccBeenAllow["allow_deposit_outside"];
				$arrAccAllow[$rowAccBeenAllow["deptaccount_no"]]["allow_deposit_inside"] = $rowAccBeenAllow["allow_deposit_inside"];
				$arrAccAllow[$rowAccBeenAllow["deptaccount_no"]]["allow_buy_share"] = $rowAccBeenAllow["allow_buy_share"];
				$arrAccAllow[$rowAccBeenAllow["deptaccount_no"]]["allow_pay_loan"] = $rowAccBeenAllow["allow_pay_loan"];
			}
			$arrAccBeenAllow = array();
			$arrHeaderAPI[] = 'Req-trans : '.date('YmdHis');
			$arrDataAPI["MemberID"] = substr($member_no,-6);
			$arrResponseAPI = $lib->posting_dataAPI($config["URL_SERVICE_EGAT"]."Account/InquiryAccount",$arrDataAPI,$arrHeaderAPI);
			if(!$arrResponseAPI["RESULT"]){
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS1031",
					":error_desc" => "ติดต่อ Server เงินฝาก Egat ไม่ได้ ".$config["URL_SERVICE_EGAT"]."Account/InquiryAccount",
					":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$message_error = "ไฟล์ ".$filename."ติดต่อ Server เงินฝาก Egat ไม่ได้ ".$config["URL_SERVICE_EGAT"]."Account/InquiryAccount";
				$lib->sendLineNotify($message_error);
				$func->MaintenanceMenu($dataComing["menu_component"]);
				$arrayResult['RESPONSE_CODE'] = "WS1031";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
			$arrResponseAPI = json_decode($arrResponseAPI);
			if($arrResponseAPI->responseCode == "200"){
				foreach($arrResponseAPI->accountDetail as $accData){
					if(in_array($accData->coopAccountNo, $arrAccAllowDep) && $accData->accountStatus == "0"){
						if(($arrAccAllow[$accData->coopAccountNo]["allow_withdraw_outside"] == '0' && $arrAccAllow[$accData->coopAccountNo]["allow_deposit_outside"] == '0') 
						&& ($arrAccAllow[$accData->coopAccountNo]["allow_withdraw_inside"] == '1' || $arrAccAllow[$accData->coopAccountNo]["allow_deposit_inside"] == '1')){
							if($arrAccAllow[$accData->coopAccountNo]["allow_buy_share"] == '1' && $arrAccAllow[$accData->coopAccountNo]["allow_pay_loan"] == '1'){
								$arrAccBeenAllow["ALLOW_DESC"] = $configError['ALLOW_TRANS_INSIDE_ALL_FLAG_ON'][0][$lang_locale];
							}else if($arrAccAllow[$accData->coopAccountNo]["allow_buy_share"] == '1' && $arrAccAllow[$accData->coopAccountNo]["allow_pay_loan"] == '0'){
								$arrAccBeenAllow["ALLOW_DESC"] = $configError['ALLOW_TRANS_BUY_SHARE_FLAG_ON'][0][$lang_locale];
							}else if($arrAccAllow[$accData->coopAccountNo]["allow_buy_share"] == '0' && $arrAccAllow[$accData->coopAccountNo]["allow_pay_loan"] == '1'){
								$arrAccBeenAllow["ALLOW_DESC"] = $configError['ALLOW_TRANS_PAY_LOAN_FLAG_ON'][0][$lang_locale];
							}else{
								$arrAccBeenAllow["ALLOW_DESC"] = $configError['ALLOW_TRANS_INSIDE_FLAG_ON'][0][$lang_locale];
							}
						}else if(($arrAccAllow[$accData->coopAccountNo]["allow_withdraw_outside"] == '1' || $arrAccAllow[$accData->coopAccountNo]["allow_deposit_outside"] == '1')){
							if($arrAccAllow[$accData->coopAccountNo]["allow_deposit_inside"] == '0' && $arrAccAllow[$accData->coopAccountNo]["allow_withdraw_inside"] == '0'){
								$arrAccBeenAllow["ALLOW_DESC"] = $configError['ALLOW_TRANS_OUTSIDE_FLAG_ON'][0][$lang_locale];
							}else{
								$arrAccBeenAllow["ALLOW_DESC"] = $configError['ALLOW_TRANS_ALL_FLAG_ON'][0][$lang_locale];
							}
						}
						if($arrAccAllow[$accData->coopAccountNo]["allow_showdetail"] == '1'){
							$arrAccBeenAllow["FLAG_NAME"] = $configError['ALLOW_ACC_SHOW_FLAG_ON'][0][$lang_locale];
						}else{
							$arrAccBeenAllow["FLAG_NAME"] = $configError['ALLOW_ACC_SHOW_FLAG_OFF'][0][$lang_locale];
						}
						$arrAccBeenAllow["DEPTACCOUNT_NAME"] = preg_replace('/\"/','',$accData->coopAccountName);
						$arrAccBeenAllow["DEPT_TYPE"] = $accData->accountDesc;
						$limit_coop = $func->getConstant("limit_withdraw");
						if($arrAccAllow[$accData->coopAccountNo]["limit_transaction_amt"] > $limit_coop){
							$arrAccBeenAllow["LIMIT_TRANSACTION_AMT"] = $limit_coop;
						}else{
							$arrAccBeenAllow["LIMIT_TRANSACTION_AMT"] = $arrAccAllow[$accData->coopAccountNo]["limit_transaction_amt"];
						}
						$arrAccBeenAllow["LIMIT_COOP_TRANS_AMT"] = $limit_coop;
						$arrAccBeenAllow["DEPTACCOUNT_NO"] = $accData->coopAccountNo;
						$arrAccBeenAllow["DEPTACCOUNT_NO_FORMAT"] = $lib->formataccount($accData->coopAccountNo,$func->getConstant('dep_format'));
						$arrAccBeenAllow["DEPTACCOUNT_NO_FORMAT_HIDE"] = $lib->formataccount_hidden($arrAccBeenAllow["DEPTACCOUNT_NO_FORMAT"],$func->getConstant('hidden_dep'));
						$arrAccBeenAllow["STATUS_ALLOW"] = $arrAccAllow[$accData->coopAccountNo]["is_use"];
						$arrGroupAccAllow[] = $arrAccBeenAllow;
					}
				}
			}else{
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS1031",
					":error_desc" => "Error ขาติดต่อ Server เงินฝาก Egat"."\n".json_encode($arrResponseAPI),
					":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$arrayResult['RESPONSE_CODE'] = "WS1031";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
			$arrayResult['ACCOUNT_ALLOW'] = $arrGroupAccAllow;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			http_response_code(204);
			
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