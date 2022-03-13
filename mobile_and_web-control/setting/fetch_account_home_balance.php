<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SettingHideAccount')
		|| $func->check_permission($payload["user_type"],$dataComing["menu_component"],'SettingEnableViewBalance')){
		$member_no = $configAS[$payload["member_no"]] ?? TRIM($payload["member_no"]);
		$arrAllAccount = array();
		$arrAllLoan = array();
		
		$arrTypeAllow = array();
		$getTypeAllowShow = $conmysql->prepare("SELECT gat.deptaccount_no 
												FROM gcuserallowacctransaction gat LEFT JOIN gcconstantaccountdept gct ON gat.id_accountconstant = gct.id_accountconstant
												WHERE gct.allow_showdetail = '1' and gat.member_no = :member_no and gat.is_use = '1'");
		$getTypeAllowShow->execute([':member_no' => $payload["member_no"]]);
		while($rowTypeAllow = $getTypeAllowShow->fetch(PDO::FETCH_ASSOC)){
			$arrTypeAllow[] = $rowTypeAllow["deptaccount_no"];
		}
		$getAccount = $conoracle->prepare("SELECT dp.depttype_code,dt.depttype_desc,dp.deptaccount_no,dp.deptaccount_name,dp.prncbal as BALANCE,
											(SELECT max(OPERATE_DATE) FROM dpdeptstatement WHERE deptaccount_no = dp.deptaccount_no) as LAST_OPERATE_DATE
											FROM dpdeptmaster dp LEFT JOIN DPDEPTTYPE dt ON dp.depttype_code = dt.depttype_code and dp.membcat_code = dt.membcat_code
											WHERE dp.deptaccount_no IN(".implode(',',$arrTypeAllow).") and dp.deptclose_status <> 1 ORDER BY dp.deptaccount_no ASC");
		$getAccount->execute();
		$formatDept = $func->getConstant('dep_format');
		$formatDeptHidden = $func->getConstant('hidden_dep');
		while($rowAccount = $getAccount->fetch(PDO::FETCH_ASSOC)){
			$arrAccount = array();
			$arrAccount["ACCOUNT_NO"] = $lib->formataccount($rowAccount["DEPTACCOUNT_NO"],$formatDept);
			$arrAccount["ACCOUNT_NO_HIDDEN"] = $lib->formataccount_hidden($rowAccount["DEPTACCOUNT_NO"],$formatDeptHidden);
			$arrAccount["ACCOUNT_NAME"] = preg_replace('/\"/','',$rowAccount["DEPTACCOUNT_NAME"]);
			$arrAccount["ACCOUNT_DESC"] = $rowAccount["DEPTTYPE_DESC"];
			$arrAllAccount[] = $arrAccount;
		}
		$arrayResult['DEPOSIT'] = $arrAllAccount;
		
		$getContract = $conoracle->prepare("SELECT lt.LOANTYPE_DESC AS LOAN_TYPE,ln.loancontract_no
											FROM lncontmaster ln LEFT JOIN LNLOANTYPE lt ON ln.LOANTYPE_CODE = lt.LOANTYPE_CODE 
											WHERE ln.member_no = :member_no and ln.contract_status > 0 and ln.contract_status <> 8");
		$getContract->execute([
			':member_no' => $member_no
		]);
		while($rowContract = $getContract->fetch(PDO::FETCH_ASSOC)){
			$contract_no = preg_replace('/\//','',$rowContract["LOANCONTRACT_NO"]);
			$arrContract = array();
			$arrContract["ACCOUNT_NO"] = $contract_no;
			$arrContract["ACCOUNT_DESC"] = $rowContract["LOAN_TYPE"];
			$arrAllLoan[] = $arrContract;
		}
		$arrayResult['LOAN'] = $arrAllLoan;
		
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