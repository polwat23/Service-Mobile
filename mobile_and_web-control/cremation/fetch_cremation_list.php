<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'CremationInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrayDataWcGrp = array();
		$getDataWc = $conmssql->prepare("SELECT WCDEPTMASTER.DEPTACCOUNT_NO,WCDEPTMASTER.WFACCOUNT_NAME,WCUCFCREMATION.CS_NAME,
									WCDEPTMASTER.WFTYPE_CODE,WCUCFCREMATION.AMT,WCUCFCREMATION.RATE
									FROM WCDEPTMASTER LEFT JOIN WCUCFCREMATION 
									ON WCDEPTMASTER.BRANCH_ID = WCUCFCREMATION.CS_BRANCH  
									WHERE WCDEPTMASTER.DEPTCLOSE_STATUS = 0  AND WCDEPTMASTER.DEPTTYPE_CODE NOT IN('03')
									AND TRIM(WCDEPTMASTER.MEMBER_NO) = :member_no");
		$getDataWc->execute([':member_no' => $member_no]);
		while($rowDataWc = $getDataWc->fetch(PDO::FETCH_ASSOC)){
			if(isset($rowDataWc["CS_NAME"]) && $rowDataWc["CS_NAME"] != ""){
				$arrayDataWc = array();
				$arrayDataWc["DEPTACCOUNT_NO"] = $lib->formataccount($rowDataWc["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
				$arrayDataWc["ACCOUNT_NAME"] = $rowDataWc["WFACCOUNT_NAME"];
				$arrayDataWc["CREMATION_TYPE"] = $rowDataWc["CS_NAME"];
				$arrayDataWc["CREMATION_CODE"] = $rowDataWc["WFTYPE_CODE"];
				$arrayDataWc["AMOUNT_WC"] = number_format($rowDataWc["AMT"],2);
				$getPersonAccountWC = $conmssql->prepare("SELECT WCCODEPOSIT.NAME,WCCODEPOSIT.SEQ_NO
													FROM WCDEPTMASTER LEFT JOIN WCCODEPOSIT 
													ON WCDEPTMASTER.DEPTACCOUNT_NO= WCCODEPOSIT.DEPTACCOUNT_NO 
													WHERE WCDEPTMASTER.DEPTCLOSE_STATUS = 0 AND TRIM(WCDEPTMASTER.DEPTACCOUNT_NO) = :account_no
													ORDER BY WCCODEPOSIT.SEQ_NO ASC");
				$getPersonAccountWC->execute([':account_no' => TRIM($rowDataWc["DEPTACCOUNT_NO"])]);
				while($rowPerson = $getPersonAccountWC->fetch(PDO::FETCH_ASSOC)){
					if(isset($rowPerson["NAME"]) && $rowPerson["NAME"] != ""){
						$arrPerson = array();
						$arrPerson["NAME"] = $rowPerson["NAME"];
						$arrPerson["SEQ_NO"] = $rowPerson["SEQ_NO"];
						$arrayDataWc["PERSON"][] = $arrPerson;
					}
				}
				$arrayDataWcGrp[] = $arrayDataWc;
			}
		}
		$arrayResult['CREMATION'] = $arrayDataWcGrp;
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
