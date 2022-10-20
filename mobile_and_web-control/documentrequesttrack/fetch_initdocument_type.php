<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DocumentRequestTrack')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrAssistGrp = array();
		$arrayGrpForm = array();
		
		
		$getDocumentType = $conmysql->prepare("SELECT documenttype_code, documenttype_desc FROM gcreqdoctype
										WHERE is_use = '1' and documenttype_code != 'RRGT'");
		$getDocumentType->execute();
		
		while($rowType = $getDocumentType->fetch(PDO::FETCH_ASSOC)){
			$arrType = array();
			$arrType["DOCUMENTTYPE_CODE"] = $rowType["documenttype_code"];
			$arrType["DOCUMENTTYPE_DESC"] = $rowType["documenttype_desc"];
			$arrAssistGrp[] = $arrType;
		}
		
		$getFormatForm = $conmysql->prepare("SELECT id_format_req_doc, documenttype_code, form_label, form_key, group_id, max_value, min_value,
										form_type, colspan, fullwidth, required, placeholder, default_value, form_option, maxwidth 
										FROM gcformatreqdocument order by form_order");
		$getFormatForm->execute();
		
		while($rowForm = $getFormatForm->fetch(PDO::FETCH_ASSOC)){
			$arrayForm = array();
			$arrayForm["FORM_LABEL"] = $rowForm["form_label"];
			$arrayForm["FORM_KEY"] = $rowForm["form_key"];
			$arrayForm["FORM_TYPE"] = $rowForm["form_type"];
			$arrayForm["FORM_OPTION"] = $rowForm["form_option"];
			$arrayGrpForm[$rowForm["documenttype_code"]][] = $arrayForm;
		}
		$arrayForm = array();
		$arrayForm["FORM_LABEL"] = "รายละเอียดเงินกู้สามัญ";
		$arrayForm["FORM_KEY"] = "CONTRACT";
		$arrayForm["FORM_TYPE"] = "contract";
		$arrayForm["FORM_OPTION"] = null;
		$arrayGrpForm["PAYD"][] = $arrayForm;
		
		$arrayResult['DOCUMENTTYPE_LIST'] = $arrAssistGrp;
		$arrayResult['FORMINPUT_LIST'] = $arrayGrpForm;
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