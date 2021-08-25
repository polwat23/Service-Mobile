<?php
$arrPostData = array();
$arrPostData['replyToken'] = $reply_token;
$TextTemplate = $conmysql->prepare("SELECT ltm.id_ref,ltm.type_message,lic.menu_component,lic.file_service
													FROM lbincoming lic LEFT JOIN lbtextmaptype ltm ON lic.id_textincome = ltm.id_textincome
													WHERE lic.text_income = :txt_income and lic.is_use = '1' and lic.menu_component IS NOT NULL");
$TextTemplate->execute([':txt_income' => $message]);
if($TextTemplate->rowCount() > 0){
	$rowTemplate = $TextTemplate->fetch(PDO::FETCH_ASSOC);
	$getTableName = $conmysql->prepare("SELECT table_name,condition_key FROM lbmaptypetablename WHERE type_message = :type_message");
	$getTableName->execute([':type_message' => $rowTemplate["type_message"]]);
	$rowTableName = $getTableName->fetch(PDO::FETCH_ASSOC);
	if($rowTemplate["type_message"] == 'image'){
		$getDataTemplate = $conmysql->prepare("SELECT image_url FROM ".$rowTableName["table_name"]." WHERE ".$rowTableName["condition_key"]." = :id_ref and is_use = '1'");
		$getDataTemplate->execute([':id_ref' => $rowTemplate["id_ref"]]);
		$rowDataTemplate = $getDataTemplate->fetch(PDO::FETCH_ASSOC);
		$dataTemplate = $lineLib->mergeImageMessage($rowDataTemplate["image_url"]);
		$arrPostData['messages'][0] = $dataTemplate;
	}else if($rowTemplate["type_message"] == 'location'){
		$getDataTemplate = $conmysql->prepare("SELECT title, address, latitude,longtitude FROM ".$rowTableName["table_name"]." WHERE ".$rowTableName["condition_key"]." = :id_ref and is_use = '1'");
		$getDataTemplate->execute([':id_ref' => $rowTemplate["id_ref"]]);
		$rowDataTemplate = $getDataTemplate->fetch(PDO::FETCH_ASSOC);
		$dataTemplate = $lineLib->mergeLocationMessage($rowDataTemplate["title"],$rowDataTemplate["address"],$rowDataTemplate["latitude"],$rowDataTemplate["longtitude"]);
		$arrPostData['messages'][0] = $dataTemplate;
	}else if($rowTemplate["type_message"] == 'text'){
		$getDataTemplate = $conmysql->prepare("SELECT text_message  FROM ".$rowTableName["table_name"]." WHERE ".$rowTableName["condition_key"]." = :id_ref and is_use = '1'");
		$getDataTemplate->execute([':id_ref' => $rowTemplate["id_ref"]]);
		$rowDataTemplate = $getDataTemplate->fetch(PDO::FETCH_ASSOC);
		$dataTemplate = $lineLib->mergeTextMessage($rowDataTemplate["text_message"]);
		$arrPostData['messages'][0] = $dataTemplate;
	}else if($rowTemplate["type_message"] == 'action'){
		$getDataTemplate = $conmysql->prepare("SELECT 
																			ac.type,ac.text,ac.url,ac.area_x,ac.area_y,ac.width,ac.height,ac.label,ac.data,ac.mode,ac.initial,ac.max,ac.min
																		FROM lbquickmessage qc
																		LEFT JOIN lbaction ac ON qc.id_action = ac.id_action 
																		WHERE qc.id_quickmsg = :id_ref and qc.is_use = '1'");
		$getDataTemplate->execute([':id_ref' => $rowTemplate["id_ref"]]);
		$rowDataTemplate = $getDataTemplate->fetch(PDO::FETCH_ASSOC);
		if($rowDataTemplate["type"]=='message'){
			$dataTemplate = $lineLib->mergeMessageAction($rowDataTemplate["text"],$rowDataTemplate["label"]);
			$arrPostData['messages'][0] = $dataTemplate;
		}else if($rowDataTemplate["type"]=='uri'){
			$dataTemplate = $lineLib->mergeUrlAction($rowDataTemplate["text"],$rowDataTemplate["url"],$rowDataTemplate["label"]);
			$arrPostData['messages'][0] = $dataTemplate;
		}else if($rowDataTemplate["type"]=='datetime_picker'){
			$dataTemplate = $lineLib->mergeDetetimePickerAction($rowDataTemplate["label"],$rowDataTemplate["data"],$rowDataTemplate["mode"],$rowDataTemplate["initial"],$rowDataTemplate["max"],$rowDataTemplate["min"]);
			$arrPostData['messages'][0] = $dataTemplate;
		}else if($rowDataTemplate["type"]=='camera'){
			$dataTemplate = $lineLib->mergeCameraAction($rowDataTemplate["text"],$rowDataTemplate["label"]);
			$arrPostData['messages'][0] = $dataTemplate;
		}else if($rowDataTemplate["type"]=='camera_roll'){
			$dataTemplate = $lineLib->mergeCameraRollAction($rowDataTemplate["text"],$rowDataTemplate["label"]);
			$arrPostData['messages'][0] = $dataTemplate;
		}else if($rowDataTemplate["type"]=='postback'){
			$dataTemplate = $lineLib->mergePostbackAction($rowDataTemplate["text"],$rowDataTemplate["label"],$rowDataTemplate["data"]);
			$arrPostData['messages'][0] = $dataTemplate;
		}else if($rowDataTemplate["type"]=='location'){
			$dataTemplate = $lineLib->mergeLocationAction($rowDataTemplate["text"],$rowDataTemplate["label"]);
			$arrPostData['messages'][0] = $dataTemplate;
		}else {
			file_put_contents(__DIR__.'/../log/response.txt', json_encode($rowDataTemplate) . PHP_EOL, FILE_APPEND);
			$dataTemplate = $lineLib->mergeTextMessage("ลง else".$rowTemplate["id_ref"].$rowDataTemplate["type"]);
			$arrPostData['messages'][0] = $dataTemplate;
		}
	}
}else{
	$messageErr = $func->getMsgLine('1');
	$dataTemplate = $lineLib->mergeTextMessage($messageErr);
	$arrPostData['messages'][0] = $dataTemplate;
	file_put_contents(__DIR__.'/../log/response.txt', json_encode($message) . PHP_EOL, FILE_APPEND);
}
require_once(__DIR__.'./replyresponse.php');
?>