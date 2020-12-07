<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'AssistRequest')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrAssistGrp = array();
		$year = date("Y");
		$getTypeMember = $conoracle->prepare("SELECT MEMBCAT_CODE FROM MBMEMBMASTER WHERE member_no = :member_no");
		$getTypeMember->execute([':member_no' => $member_no]);
		$rowTypeMember = $getTypeMember->fetch(PDO::FETCH_ASSOC);
		$getNameWelfare = $conoracle->prepare("SELECT DISTINCT aud.ASSISTTYPE_CODE,aut.ASSISTTYPE_DESC from assucfassisttype aut 
												LEFT JOIN assucfassisttypedet aud ON aut.ASSISTTYPE_CODE = aud.ASSISTTYPE_CODE
												WHERE aud.assist_year = :year and (aud.membcat_code = :membcat_code OR aud.membcat_code = 'AL')");
		$getNameWelfare->execute([
			':year' => $year,
			':membcat_code' => $rowTypeMember["MEMBCAT_CODE"]
		]);
		while($rowNameWelfare = $getNameWelfare->fetch(PDO::FETCH_ASSOC)){
			$arrayWef[$rowNameWelfare["ASSISTTYPE_CODE"]] = $rowNameWelfare["ASSISTTYPE_DESC"];
		}
		$fetchAssistType = $conmysql->prepare("SELECT id_const_welfare,welfare_type_code,member_cate_code FROM gcconstantwelfare 
											WHERE is_use = '1' and (member_cate_code = :cate_code OR member_cate_code = 'AL')");
		$fetchAssistType->execute([':cate_code' => $rowTypeMember["MEMBCAT_CODE"]]);
		while($rowAssistType = $fetchAssistType->fetch(PDO::FETCH_ASSOC)){
			$arrAssist = array();
			$arrAssist["ID_CONST_WELFARE"] = $rowAssistType["id_const_welfare"];
			$arrAssist["WELFARE_DESC"] = $arrayWef[$rowAssistType["welfare_type_code"]];
			$arrAssist["ASSISTTYPE_CODE"] = $rowAssistType["welfare_type_code"];
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