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
		while($rowDeptAllow = $getDeptTypeAllow->fetch(PDO::FETCH_ASSOC)){
			$arrayDepttype = array();
			$arrDeptAllowed[] = "'".$rowDeptAllow["dept_type_code"]."'";
		}
		$InitDeptAccountAllowed = $conmysql->prepare("SELECT deptaccount_no FROM gcuserallowacctransaction WHERE member_no = :member_no and is_use <> '-9'");
		$InitDeptAccountAllowed->execute([':member_no' => $payload["member_no"]]);
		while($rowAccountAllowed = $InitDeptAccountAllowed->fetch(PDO::FETCH_ASSOC)){
			$arrAccAllowed[] = $rowAccountAllowed["deptaccount_no"];
		}
		if(sizeof($arrAccAllowed) > 0){
			$getAccountAllinCoop = $conoracle->prepare("SELECT dpm.deptaccount_no,TRIM(dpm.deptaccount_name) as deptaccount_name,dpt.depttype_desc,dpm.DEPTTYPE_CODE
														FROM dpdeptmaster dpm LEFT JOIN dpdepttype dpt ON dpm.depttype_code = dpt.depttype_code and dpm.membcat_code = dpt.membcat_code
														WHERE dpm.depttype_code IN(".implode(',',$arrDeptAllowed).")
														and dpm.deptaccount_no NOT IN(".implode(',',$arrAccAllowed).")
														and dpm.member_no = :member_no and dpm.deptclose_status = 0 ORDER BY dpm.deptaccount_no");
		}else{
			$getAccountAllinCoop = $conoracle->prepare("SELECT dpm.deptaccount_no,TRIM(dpm.deptaccount_name) as deptaccount_name,dpt.depttype_desc,dpm.depttype_code
														FROM dpdeptmaster dpm LEFT JOIN dpdepttype dpt ON dpm.depttype_code = dpt.depttype_code and dpm.membcat_code = dpt.membcat_code
														WHERE dpm.depttype_code IN(".implode(',',$arrDeptAllowed).")
														and dpm.member_no = :member_no and dpm.deptclose_status = 0 ORDER BY dpm.deptaccount_no");
		}
		$getAccountAllinCoop->execute([':member_no' => $member_no]);
		while($rowAccIncoop = $getAccountAllinCoop->fetch(PDO::FETCH_ASSOC)){
			$limit_trans = $func->getConstant("limit_withdraw");
			$arrAccInCoop["DEPTACCOUNT_NO"] = $rowAccIncoop["DEPTACCOUNT_NO"];
			$arrAccInCoop["DEPTACCOUNT_NO_FORMAT"] = $lib->formataccount($rowAccIncoop["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
			$arrAccInCoop["DEPTACCOUNT_NO_FORMAT_HIDE"] = $lib->formataccount_hidden($rowAccIncoop["DEPTACCOUNT_NO"],$func->getConstant('hidden_dep'));
			$arrAccInCoop["DEPTACCOUNT_NAME"] = preg_replace('/\"/','',trim($rowAccIncoop["DEPTACCOUNT_NAME"]));
			$arrAccInCoop["DEPT_TYPE"] = $rowAccIncoop["DEPTTYPE_DESC"];
			$getIDDeptTypeAllow = $conmysql->prepare("SELECT allow_withdraw_outside,allow_withdraw_inside,allow_deposit_outside,allow_deposit_inside,
													allow_buy_share,allow_pay_loan,id_accountconstant,allow_showdetail FROM gcconstantaccountdept
													WHERE dept_type_code = :depttype_code and 
													(allow_withdraw_outside = '1' OR allow_withdraw_inside = '1' OR allow_deposit_outside = '1' OR allow_deposit_inside = '1'
													OR allow_buy_share = '1' OR allow_pay_loan = '1' OR allow_showdetail = '1')");
			$getIDDeptTypeAllow->execute([
				':depttype_code' => $rowAccIncoop["DEPTTYPE_CODE"]
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
		$arrayResult['ACCOUNT_ALLOW'] = $arrAllowAccGroup;
		$arrayResult['RESULT'] = TRUE;
		require_once('../../include/exit_footer.php');

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