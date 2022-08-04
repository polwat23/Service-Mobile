<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ManagementAccount')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrDeptAllowed = array();
		$arrAccAllowed = array();
		$arrAllowAccGroup = array();
		
		$getDeptTypeAllow = $conmysql->prepare("SELECT dept_type_code FROM gcconstantaccountdept
												WHERE allow_withdraw_outside = '1' OR allow_withdraw_inside = '1' OR allow_deposit_outside = '1' OR allow_deposit_inside = '1'");
		$getDeptTypeAllow->execute();
		while($rowDeptAllow = $getDeptTypeAllow->fetch(PDO::FETCH_ASSOC)){
			$arrDeptAllowed[] = "'".$rowDeptAllow["dept_type_code"]."'";
		}
		$InitDeptAccountAllowed = $conmysql->prepare("SELECT deptaccount_no FROM gcuserallowacctransaction WHERE member_no = :member_no and is_use <> '-9'");
		$InitDeptAccountAllowed->execute([':member_no' => $payload["member_no"]]);
		while($rowAccountAllowed = $InitDeptAccountAllowed->fetch(PDO::FETCH_ASSOC)){
			$arrAccAllowed[] = $rowAccountAllowed["deptaccount_no"];
		}
		if(sizeof($arrAccAllowed) > 0){
			$getAccountAllinCoop = $conoracle->prepare("SELECT dpm.account_no as deptaccount_no,TRIM(dpm.account_name) as deptaccount_name,dpt.ACC_DESC as depttype_desc,dpm.ACC_TYPE as depttype_code
														FROM BK_H_SAVINGACCOUNT dpm LEFT JOIN BK_M_ACC_TYPE dpt ON dpm.ACC_TYPE = dpt.ACC_TYPE
														WHERE dpm.ACC_TYPE IN(".implode(',',$arrDeptAllowed).")
														and dpm.account_no NOT IN(".implode(',',$arrAccAllowed).")
														and dpm.account_id = :member_no and dpm.ACC_STATUS = 'O' and dpm.JOIN_FLAG='N' ORDER BY dpm.account_no");
		}else{
			$getAccountAllinCoop = $conoracle->prepare("SELECT dpm.account_no as deptaccount_no,TRIM(dpm.account_name) as deptaccount_name,dpt.ACC_DESC as depttype_desc,dpm.ACC_TYPE as depttype_code
														FROM BK_H_SAVINGACCOUNT dpm LEFT JOIN BK_M_ACC_TYPE dpt ON dpm.ACC_TYPE = dpt.ACC_TYPE
														WHERE dpm.ACC_TYPE IN(".implode(',',$arrDeptAllowed).")
														and dpm.account_id = :member_no and dpm.ACC_STATUS = 'O' and dpm.JOIN_FLAG='N' ORDER BY dpm.account_no");

		}
		$getAccountAllinCoop->execute([':member_no' => $member_no]);
		while($rowAccIncoop = $getAccountAllinCoop->fetch(PDO::FETCH_ASSOC)){
			$arrAccInCoop["DEPTACCOUNT_NO"] = $rowAccIncoop["DEPTACCOUNT_NO"];
			$arrAccInCoop["DEPTACCOUNT_NO_FORMAT"] = $lib->formataccount($rowAccIncoop["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
			$arrAccInCoop["DEPTACCOUNT_NO_FORMAT_HIDE"] = $lib->formataccount_hidden($rowAccIncoop["DEPTACCOUNT_NO"],$func->getConstant('hidden_dep'));
			$arrAccInCoop["DEPTACCOUNT_NAME"] = preg_replace('/\"/','',trim($rowAccIncoop["DEPTACCOUNT_NAME"]));
			$arrAccInCoop["DEPT_TYPE"] = $rowAccIncoop["DEPTTYPE_DESC"];
			$getIDDeptTypeAllow = $conmysql->prepare("SELECT id_accountconstant FROM gcconstantaccountdept
													WHERE dept_type_code = :depttype_code");
			$getIDDeptTypeAllow->execute([
				':depttype_code' => $rowAccIncoop["DEPTTYPE_CODE"]
			]);
			$rowIDDeptTypeAllow = $getIDDeptTypeAllow->fetch(PDO::FETCH_ASSOC);
			$arrAccInCoop["ID_ACCOUNTCONSTANT"] = $rowIDDeptTypeAllow["id_accountconstant"];
			$getDeptTypeAllow = $conmysql->prepare("SELECT allow_withdraw_outside,allow_withdraw_inside,allow_deposit_outside
																	FROM gcconstantaccountdept
																	WHERE dept_type_code = :depttype_code");
			$getDeptTypeAllow->execute([
				':depttype_code' => $rowAccIncoop["DEPTTYPE_CODE"]
			]);
			$rowDeptTypeAllow = $getDeptTypeAllow->fetch(PDO::FETCH_ASSOC);
			if(($rowDeptTypeAllow["allow_withdraw_outside"] == '0' && $rowDeptTypeAllow["allow_deposit_outside"] == '0') && 
			$rowDeptTypeAllow["allow_withdraw_inside"] == '1'){
				$arrAccInCoop["ALLOW_DESC"] = $configError['ALLOW_TRANS_INSIDE_FLAG_ON'][0][$lang_locale];
			}else if($rowDeptTypeAllow["allow_withdraw_outside"] == '1' || $rowDeptTypeAllow["allow_deposit_outside"] == '1'){
				$arrAccInCoop["ALLOW_DESC"] = $configError['ALLOW_TRANS_ALL_MENU'][0][$lang_locale];
			}else{
				$arrAccInCoop["FLAG_NAME"] = $configError['ACC_TRANS_FLAG_OFF'][0][$lang_locale];
			}
			if($rowDeptTypeAllow["allow_withdraw_inside"] == '0'){
				$arrAccInCoop["FLAG_NAME"] = $configError['ACC_TRANS_FLAG_OFF'][0][$lang_locale];
			}
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