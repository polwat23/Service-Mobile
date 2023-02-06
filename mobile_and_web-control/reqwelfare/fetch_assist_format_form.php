<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','id_const_welfare'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'AssistRequest')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrayGrpForm = array();
		$year = date("Y");
		$fetchAssist = $conmysql->prepare("SELECT member_no from assreqmasteronline  WHERE req_status  = <> '1' AND member_no =:member_no");
		$fetchAssist->execute([':member_no'=> $member_no]);
		$rowAssist = $fetchAssist->fetch(PDO::FETCH_ASSOC);
		if(isset($rowAssist["member_no"])){
			$arrayResult['RESPONSE_CODE'] = "WS4005";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			http_response_code(403);
			require_once('../../include/exit_footer.php');
		}else{
			$getFormatForm = $conmysql->prepare("SELECT INPUT_TYPE,INPUT_LENGTH,INPUT_NAME,LABEL_TEXT,PLACEHOLDER,DEFAULT_VALUE,IS_REQUIRED,INPUT_FORMAT
											FROM gcformatreqwelfare 
											WHERE id_const_welfare = :id_const_welfare and is_use = '1'");
			$getFormatForm->execute([
				':id_const_welfare' => $dataComing["id_const_welfare"]
			]);
			while($rowForm = $getFormatForm->fetch(PDO::FETCH_ASSOC)){
				$arrayForm = array();
				$arrayForm["INPUT_TYPE"] = $rowForm["INPUT_TYPE"];
				$arrayForm["INPUT_LENGTH"] = $rowForm["INPUT_LENGTH"];
				$arrayForm["INPUT_NAME"] = $rowForm["INPUT_NAME"];
				$arrayForm["LABEL_TEXT"] = $rowForm["LABEL_TEXT"];
				$arrayForm["PLACEHOLDER"] = $rowForm["PLACEHOLDER"];
				$arrayForm["DEFAULT_VALUE"] = $rowForm["DEFAULT_VALUE"];
				$arrayForm["INPUT_FORMAT"] = $rowForm["INPUT_FORMAT"];
				$arrayForm["IS_REQUIRED"] = $rowForm["IS_REQUIRED"];
				$arrayGrpForm[] = $arrayForm;
			}
			$arrayResult['FORM_ASSIST'] = $arrayGrpForm;
			$arrayResult['RESULT'] = TRUE;
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