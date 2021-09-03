<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','documenttype_code'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DocumentRequest')){
		$arrayGrpForm = array();
		$arrayGrp = array();
		$arrayDocGrp = array();
		
		$getReqDocument = $conmysql->prepare("SELECT reqdoc_no, member_no, documenttype_code, form_value, document_url, req_status, request_date, update_date 
										FROM gcreqdoconline WHERE req_status NOT IN('9','-9') and documenttype_code = :documenttype_code and member_no = :member_no");
		$getReqDocument->execute([
			':documenttype_code' => $dataComing["documenttype_code"],
			':member_no' => $payload["member_no"],
		]);
		while($rowReqDocument = $getReqDocument->fetch(PDO::FETCH_ASSOC)){
			$arrayDoc = array();
			$arrayDoc["REQDOC_NO"] = $rowReqDocument["reqdoc_no"];
			$arrayDoc["MEMBER_NO"] = $rowReqDocument["member_no"];
			$arrayDoc["DOCUMENTTYPE_CODE"] = $rowReqDocument["documenttype_code"];
			$arrayDoc["DOCUMENTTYPE_DESC"] = "เปลี่ยนแปลงผู้รับผลประโยชน์";
			$arrayDoc["FORM_VALUE"] = $rowReqDocument["form_value"];
			$arrayDoc["DOCUMENT_URL"] = $rowReqDocument["document_url"];
			$arrayDoc["REQ_STATUS"] = $rowReqDocument["req_status"];
			if($rowReqDocument["req_status"] == '1'){
				$arrayDoc["REQ_STATUS_DESC"] = "อนุมัติ";
			}else if($rowReqDocument["req_status"] == '8'){
				$arrayDoc["REQ_STATUS_DESC"] = "รอลงรับ";
				$arrayDoc["ALLOW_CANCEL"] = true;
			}else if($rowReqDocument["req_status"] == '9'){
				$arrayDoc["REQ_STATUS_DESC"] = "ยกเลิก";
				$arrayDoc["REQ_STATUS_COLOR"] = "#f5222d";
			}else if($rowReqDocument["req_status"] == '-9'){
				$arrayDoc["REQ_STATUS_DESC"] = "ไม่อนุมัติ";
				$arrayDoc["REQ_STATUS_COLOR"] = "#f5222d";
			}else if($rowReqDocument["req_status"] == '7'){
				$arrayDoc["REQ_STATUS_DESC"] = "ลงรับรอตรวจสิทธิ์เพิ่มเติม";
			}
			$arrayDoc["REQUEST_DATE"] = $lib->convertdate($rowReqDocument["request_date"],"D m Y", true);
			$arrayDoc["UPDATE_DATE"] = $lib->convertdate($rowReqDocument["update_date"],"D m Y", true);
			$arrayDocGrp[] = $arrayDoc;
		}
		
		if(count($arrayDocGrp) > 0){
			$arrayResult['CAN_REQ'] = FALSE;
		}else{
			$arrayResult['CAN_REQ'] = TRUE;
		}
		
		$getFormatForm = $conmysql->prepare("SELECT id_format_req_doc, documenttype_code, form_label, form_key, group_id, max_value, min_value,
										form_type, colspan, fullwidth, required, placeholder, default_value, form_option, maxwidth 
										FROM gcformatreqdocument
										WHERE is_use = '1' AND documenttype_code = :documenttype_code");
		$getFormatForm->execute([
			':documenttype_code' => $dataComing["documenttype_code"]
		]);
		
		$arrayForm = array();
		$arrayForm["GROUP_ID"] = "1";
		$arrayForm["GROUP_DESC"] = null;
		$arrayGrpData[] = $arrayForm;
		$arrayForm = array();
		$arrayForm["GROUP_ID"] = "2";
		$arrayForm["GROUP_DESC"] = "รายชื่อผู้รับผลประโยชน์";
		$arrayGrpData[] = $arrayForm;
		
		while($rowForm = $getFormatForm->fetch(PDO::FETCH_ASSOC)){
			$arrayForm = array();
			$arrayForm["ID_FORMAT_REQ_DOC"] = $rowForm["id_format_req_doc"];
			$arrayForm["DOCUMENTTYPE_CODE"] = $rowForm["documenttype_code"];
			$arrayForm["FORM_LABEL"] = $rowForm["form_label"];
			$arrayForm["FORM_KEY"] = $rowForm["form_key"];
			$arrayForm["FORM_TYPE"] = $rowForm["form_type"];
			$arrayForm["COLSPAN"] = $rowForm["colspan"];
			$arrayForm["FULLWIDTH"] = $rowForm["fullwidth"] == 1 ? true : false;
			$arrayForm["REQUIRED"] = $rowForm["required"] == 1 ? true : false;
			$arrayForm["PLACEHOLDER"] = $rowForm["placeholder"];
			$arrayForm["DEFAULT_VALUE"] = $rowForm["default_value"];
			$arrayForm["FORM_OPTION"] = $rowForm["form_option"];
			$arrayForm["MAXWIDTH"] = $rowForm["maxwidth"];
			$arrayForm["GROUP_ID"] = $rowForm["group_id"];
			$arrayForm["MAX_VALUE"] = $rowForm["max_value"];
			$arrayForm["MIN_VALUE"] = $rowForm["min_value"];
			$arrayGrpForm[] = $arrayForm;
		}
		
		$arrayResult['FORM_REQDOCUMENT'] = $arrayGrpForm;
		$arrayResult['FORM_GROUP'] = $arrayGrpData;
		$arrayResult['REQ_DOCUMENT'] = $arrayDocGrp;
		
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