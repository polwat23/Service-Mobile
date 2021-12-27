<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'PetitionForm')){
		$arrayGrpForm = array();
		$year = date("Y");
		$getFormatForm = $conmysql->prepare("SELECT petitionform_code, petitionform_desc
											FROM gcpetitionformtype 
											WHERE is_use = '1' order by petitionform_code desc");
		$getFormatForm->execute([
			':id_const_welfare' => $dataComing["id_const_welfare"]
		]);
		while($rowForm = $getFormatForm->fetch(PDO::FETCH_ASSOC)){
			$arrayForm = array();
			$arrayForm["PETITIONFORM_CODE"] = $rowForm["petitionform_code"];
			$arrayForm["PETITIONFORM_DESC"] = $rowForm["petitionform_desc"];
			$arrayGrpForm[] = $arrayForm;
		}
		$arrayResult['PETITION_TYPE'] = $arrayGrpForm;
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