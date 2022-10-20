<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','documentrequest')){
		
		$arrAssistGrp = array();
		$arrayGrpForm = array();
		
		$getDocumentType = $conmysql->prepare("SELECT documenttype_code, documenttype_desc FROM gcreqdoctype
										WHERE is_use = '1'");
		$getDocumentType->execute();
		
		while($rowType = $getDocumentType->fetch(PDO::FETCH_ASSOC)){
			$arrType = array();
			$arrType["DOCUMENTTYPE_CODE"] = $rowType["documenttype_code"];
			$arrType["DOCUMENTTYPE_DESC"] = $rowType["documenttype_desc"];
			$arrAssistGrp[] = $arrType;
		}
		
		$getFormatForm = $conmysql->prepare("SELECT id_format_req_doc, documenttype_code, form_label, form_key, group_id, max_value, min_value,
										form_type, colspan, fullwidth, required, placeholder, default_value, form_option, maxwidth ,is_use
										FROM gcformatreqdocument");
		$getFormatForm->execute();
		
		while($rowForm = $getFormatForm->fetch(PDO::FETCH_ASSOC)){
			$arrayForm = array();
			$arrayForm["FORM_LABEL"] = $rowForm["form_label"];
			$arrayForm["FORM_KEY"] = $rowForm["form_key"];
			$arrayForm["FORM_TYPE"] = $rowForm["form_type"];
			$arrayForm["FORM_OPTION"] = $rowForm["form_option"];
			$arrayForm["IS_USE"] = $rowForm["is_use"];
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
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>