<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SettingHideAccount')){
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
		$arrHeaderAPI[] = 'Req-trans : '.date('YmdHis');
		$arrDataAPI["MemberID"] = substr($member_no,-6);
		$arrResponseAPI = $lib->posting_dataAPI($config["URL_SERVICE_EGAT"]."Account/InquiryAccount",$arrDataAPI,$arrHeaderAPI);
		$arrResponseAPI = json_decode($arrResponseAPI);
		$formatDept = $func->getConstant('dep_format');
		$formatDeptHidden = $func->getConstant('hidden_dep');
		if($arrResponseAPI->responseCode == "200"){
			foreach($arrResponseAPI->accountDetail as $accData){
				if (in_array($accData->coopAccountNo, $arrTypeAllow) && $accData->accountStatus == "0"){
					$arrAccount = array();
					$arrAccount["ACCOUNT_NO"] = $lib->formataccount($accData->coopAccountNo,$formatDept);
					$arrAccount["ACCOUNT_NO_HIDDEN"] = $lib->formataccount_hidden($accData->coopAccountNo,$formatDeptHidden);
					$arrAccount["ACCOUNT_NAME"] = preg_replace('/\"/','',$accData->coopAccountName);
					$arrAccount["ACCOUNT_DESC"] = $accData->accountDesc;
					$arrAllAccount[] = $arrAccount;
				}
			}
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