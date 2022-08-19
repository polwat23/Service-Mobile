<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'CremationInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrayDataWcGrp = array();
		$arrayDataWcAcc = array();
		$conmssqlcmt = $con->connecttosqlservercmt();
		$getDataWc = $conmssqlcmt->prepare("SELECT WCM.DEPTACCOUNT_NO,CONCAT(MP.PRENAME_DESC,WCM.DEPTACCOUNT_NAME,' ',WCM.DEPTACCOUNT_SNAME) AS CS_NAME,WCC.WC_ID,WCC.COOP_NAME,WCM.PRNCBAL
																	FROM WCDEPTMASTER WCM LEFT JOIN WCCONTCOOP WCC
																	ON WCM.WC_ID = WCC.WC_ID  AND WCC.COOP_ID = '051001'
																	LEFT JOIN MBUCFPRENAME MP ON WCM.PRENAME_CODE = MP.PRENAME_CODE
																	WHERE  WCM.MEMBER_NO = :member_no AND WCM.COOP_ID = '051001' ");
		$getDataWc->execute([':member_no' => $member_no]);
		while($rowDataWc = $getDataWc->fetch(PDO::FETCH_ASSOC)){
			$arrayDataWc = array();
			$arrayDataWc["DEPTACCOUNT_NO"] = $lib->formataccount($rowDataWc["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
			$arrayDataWc["ACCOUNT_NAME"] = $rowDataWc["CS_NAME"];
			$arrayDataWc["CREMATION_CODE"] = $rowDataWc["WC_ID"];
			
			if(preg_replace('/\./','',$dataComing["app_version"]) >= '331' || $dataComing["channel"] == 'web'){
				$arrayDataWc['ALLOW_DECEASED_LIST'] = TRUE;
			}
			
			$arrayDataWc["CREMATION_TYPE"] = $rowDataWc["COOP_NAME"];
			$arrayDataWc["AMOUNT_WC"] = number_format($rowDataWc["PRNCBAL"],2);
			if(in_array($rowDataWc["DEPTACCOUNT_NO"],$arrayDataWcAcc) === FALSE){
				$arrayDataWcGrp[] = $arrayDataWc;
				$arrayDataWcAcc[] = $rowDataWc["DEPTACCOUNT_NO"];
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
