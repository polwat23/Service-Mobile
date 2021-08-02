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
	}
}else{
	$messageErr = $func->getMsgLine('1');
	$dataTemplate = $lineLib->mergeTextMessage($messageErr);
	$arrPostData['messages'][0] = $dataTemplate;
}
require_once(__DIR__.'./replyresponse.php');
?>