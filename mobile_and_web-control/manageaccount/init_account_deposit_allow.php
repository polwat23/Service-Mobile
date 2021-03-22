<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ManagementAccount')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrDeptAllowed = array();
		$arrAccAllowed = array();
		$arrAllowAccGroup = array();
		$getDeptTypeAllow = $conmysql->prepare("SELECT dept_type_code FROM gcconstantaccountdept WHERE 
												allow_withdraw_outside = '1' OR allow_withdraw_inside = '1' OR allow_deposit_outside = '1' OR allow_deposit_inside = '1'
												OR allow_buy_share = '1' OR allow_pay_loan = '1' OR allow_showdetail = '1'");
		$getDeptTypeAllow->execute();
		if($getDeptTypeAllow->rowCount() > 0 ){
			while($rowDeptAllow = $getDeptTypeAllow->fetch(PDO::FETCH_ASSOC)){
				$arrayDepttype = array();
				$arrDeptAllowed[] = $rowDeptAllow["dept_type_code"];
			}
			$InitDeptAccountAllowed = $conmysql->prepare("SELECT deptaccount_no FROM gcuserallowacctransaction WHERE member_no = :member_no and is_use <> '-9'");
			$InitDeptAccountAllowed->execute([':member_no' => $payload["member_no"]]);
			while($rowAccountAllowed = $InitDeptAccountAllowed->fetch(PDO::FETCH_ASSOC)){
				$arrAccAllowed[] = $rowAccountAllowed["deptaccount_no"];
			}
			$arrHeaderAPI[] = 'Req-trans : '.date('YmdHis');
			$arrDataAPI["MemberID"] = substr($member_no,-6);
			$arrResponseAPI = $lib->posting_dataAPI($config["URL_SERVICE_EGAT"]."Account/InquiryAccount",$arrDataAPI,$arrHeaderAPI);
			if(!$arrResponseAPI["RESULT"]){
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS9999",
					":error_desc" => "ติดต่อ Server เงินฝาก Egat ไม่ได้ ".$config["URL_SERVICE_EGAT"]."Account/InquiryAccount",
					":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$message_error = "ไฟล์ ".$filename." ติดต่อ Server เงินฝาก Egat ไม่ได้ ".$config["URL_SERVICE_EGAT"]."Account/InquiryAccount";
				$lib->sendLineNotify($message_error);
				$func->MaintenanceMenu($dataComing["menu_component"]);
				$arrayResult['RESPONSE_CODE'] = "WS9999";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
			$arrResponseAPI = json_decode($arrResponseAPI);
			if($arrResponseAPI->responseCode == "200"){
				$limit_trans = $func->getConstant("limit_withdraw");
				foreach($arrResponseAPI->accountDetail as $accData){
					if (in_array($accData->accountType, $arrDeptAllowed) && !in_array($accData->coopAccountNo, $arrAccAllowed) && $accData->accountStatus == "0"){
						$arrAccInCoop["DEPTACCOUNT_NO"] = $accData->coopAccountNo;
						$arrAccInCoop["DEPTACCOUNT_NO_FORMAT"] = $lib->formataccount($accData->coopAccountNo,$func->getConstant('dep_format'));
						$arrAccInCoop["DEPTACCOUNT_NO_FORMAT_HIDE"] = $lib->formataccount_hidden($accData->coopAccountNo,$func->getConstant('hidden_dep'));
						$arrAccInCoop["DEPTACCOUNT_NAME"] = preg_replace('/\"/','',$accData->coopAccountName);
						$arrAccInCoop["DEPT_TYPE"] = $accData->accountDesc;
						$getIDDeptTypeAllow = $conmysql->prepare("SELECT allow_withdraw_outside,allow_withdraw_inside,allow_deposit_outside,allow_deposit_inside,
																allow_buy_share,allow_pay_loan,id_accountconstant,allow_showdetail FROM gcconstantaccountdept
																WHERE dept_type_code = :depttype_code and 
																(allow_withdraw_outside = '1' OR allow_withdraw_inside = '1' OR allow_deposit_outside = '1' OR allow_deposit_inside = '1'
																OR allow_buy_share = '1' OR allow_pay_loan = '1' OR allow_showdetail = '1')");
						$getIDDeptTypeAllow->execute([
							':depttype_code' => $accData->accountType
						]);
						$rowDeptTypeAllow = $getIDDeptTypeAllow->fetch(PDO::FETCH_ASSOC);
						$arrAccInCoop["ID_ACCOUNTCONSTANT"] = $rowDeptTypeAllow["id_accountconstant"];
						if(($rowDeptTypeAllow["allow_withdraw_outside"] == '0' && $rowDeptTypeAllow["allow_deposit_outside"] == '0') 
						&& ($rowDeptTypeAllow["allow_withdraw_inside"] == '1' || $rowDeptTypeAllow["allow_deposit_inside"] == '1')){
							if($rowDeptTypeAllow["allow_buy_share"] == '1' && $rowDeptTypeAllow["allow_pay_loan"] == '1'){
								$arrAccInCoop["ALLOW_DESC"] = $configError['ALLOW_TRANS_INSIDE_ALL_FLAG_ON'][0][$lang_locale];
							}else if($rowDeptTypeAllow["allow_buy_share"] == '1' && $rowDeptTypeAllow["allow_pay_loan"] == '0'){
								$arrAccInCoop["ALLOW_DESC"] = $configError['ALLOW_TRANS_BUY_SHARE_FLAG_ON'][0][$lang_locale];
							}else if($rowDeptTypeAllow["allow_buy_share"] == '0' && $rowDeptTypeAllow["allow_pay_loan"] == '1'){
								$arrAccInCoop["ALLOW_DESC"] = $configError['ALLOW_TRANS_PAY_LOAN_FLAG_ON'][0][$lang_locale];
							}else{
								$arrAccInCoop["ALLOW_DESC"] = $configError['ALLOW_TRANS_INSIDE_FLAG_ON'][0][$lang_locale];
							}
						}else if(($rowDeptTypeAllow["allow_withdraw_outside"] == '1' || $rowDeptTypeAllow["allow_deposit_outside"] == '1')){
							if($rowDeptTypeAllow["allow_deposit_inside"] == '0' && $rowDeptTypeAllow["allow_withdraw_inside"] == '0'){
								$arrAccInCoop["ALLOW_DESC"] = $configError['ALLOW_TRANS_OUTSIDE_FLAG_ON'][0][$lang_locale];
							}else{
								$arrAccInCoop["ALLOW_DESC"] = $configError['ALLOW_TRANS_ALL_FLAG_ON'][0][$lang_locale];
							}
						}
						if($rowDeptTypeAllow["allow_showdetail"] == '1'){
							$arrAccInCoop["FLAG_NAME"] = $configError['ALLOW_ACC_SHOW_FLAG_ON'][0][$lang_locale];
						}else{
							$arrAccInCoop["FLAG_NAME"] = $configError['ALLOW_ACC_SHOW_FLAG_OFF'][0][$lang_locale];
						}
						$arrAccInCoop["LIMIT_COOP_TRANS_AMT"] = $limit_trans;
						$arrAccInCoop["LIMIT_TRANSACTION_AMT"] = $limit_trans;
						$arrAllowAccGroup[] = $arrAccInCoop;
					}
				}
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS9001";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
			$arrayResult['ACCOUNT_ALLOW'] = $arrAllowAccGroup;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
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