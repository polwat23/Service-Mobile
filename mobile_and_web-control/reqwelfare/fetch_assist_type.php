<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'AssistRequest')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrAssistGrp = array();
		$getTypeMember = $conmssql->prepare("SELECT '01' as MEMBCAT_CODE FROM MBMEMBMASTER WHERE member_no = :member_no");
		$getTypeMember->execute([':member_no' => $member_no]);
		$rowTypeMember = $getTypeMember->fetch(PDO::FETCH_ASSOC);
		$getNameWelfare = $conmssql->prepare("SELECT DISTINCT aut.ASSISTTYPE_CODE,aut.ASSISTTYPE_DESC,aud.MEMBTYPE_CODE from assucfassisttype aut 
												LEFT JOIN assucfassisttypedet aud ON aut.ASSISTTYPE_CODE = aud.ASSISTTYPE_CODE");
		$getNameWelfare->execute();
		while($rowNameWelfare = $getNameWelfare->fetch(PDO::FETCH_ASSOC)){
			$arrayWef[$rowNameWelfare["ASSISTTYPE_CODE"]] = $rowNameWelfare["ASSISTTYPE_DESC"];
		}
		$fetchAssistType = $conmysql->prepare("SELECT ID_CONST_WELFARE,WELFARE_TYPE_CODE,MEMBER_CATE_CODE FROM gcconstantwelfare 
											WHERE is_use = '1'");
		$fetchAssistType->execute([':cate_code' => $rowTypeMember["MEMBCAT_CODE"]]);
		while($rowAssistType = $fetchAssistType->fetch(PDO::FETCH_ASSOC)){
			$arrAssist = array();
			$arrAssist["ID_CONST_WELFARE"] = $rowAssistType["ID_CONST_WELFARE"];
			$arrAssist["WELFARE_DESC"] = $arrayWef[$rowAssistType["WELFARE_TYPE_CODE"]];
			$arrAssist["ASSISTTYPE_CODE"] = $rowAssistType["WELFARE_TYPE_CODE"];
			$arrAssistGrp[] = $arrAssist;
		}
		$arrayResult['WELFARE_TYPE'] = $arrAssistGrp;
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