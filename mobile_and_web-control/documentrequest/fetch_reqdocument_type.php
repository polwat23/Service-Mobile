<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','document_code'],$dataComing)){
	if($dataComing["document_code"] == 'CBNF'){
		if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DocumentBeneciaryRequest')){
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0006";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			http_response_code(403);
			require_once('../../include/exit_footer.php');
		}
	}else if($dataComing["document_code"] == 'CSHR'){
		if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DocumentShrPaymentRequest')){
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0006";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			http_response_code(403);
			require_once('../../include/exit_footer.php');
		}
	}else if($dataComing["document_code"] == 'RRSN'){
		if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DocumentResignRequest')){
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0006";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			http_response_code(403);
			require_once('../../include/exit_footer.php');
		}
	}else if($dataComing["document_code"] == 'RCER'){
		if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DocumentCertRequest')){
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0006";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			http_response_code(403);
			require_once('../../include/exit_footer.php');
		}
	}else if($dataComing["document_code"] == 'PAYD'){
		if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DocumentPayDeptRequest')){
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0006";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			http_response_code(403);
			require_once('../../include/exit_footer.php');
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../include/exit_footer.php');
	}
	$is_disabled = false;
	$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
	$arrAssistGrp = array();
	
	$getDocumentType = $conmysql->prepare("SELECT documenttype_code, documenttype_desc FROM gcreqdoctype
									WHERE is_use = '1' and documenttype_code = :documenttype_code");
	$getDocumentType->execute([
		':documenttype_code' => $dataComing["document_code"]
	]);
	
	while($rowType = $getDocumentType->fetch(PDO::FETCH_ASSOC)){
		$arrType = array();
		$arrType["DOCUMENTTYPE_CODE"] = $rowType["documenttype_code"];
		$arrType["DOCUMENTTYPE_DESC"] = $rowType["documenttype_desc"];
		$arrAssistGrp[] = $arrType;
	}
	if(($dataComing["document_code"] == 'CSHR' || $dataComing["document_code"] == 'RRSN' || $dataComing["document_code"] == 'PAYD') && date("d") == 7){
		$is_disabled = false;
		$arrayResult['REQDOC_REMARK_TITLE'] = "หมายเหตุ";
		$arrayResult['REQDOC_REMARK_DETAIL'] = "ปิดรับเอกสารใบคำขอ เพิ่มลดหุ้น ชำระหนี้บางส่วนและลาออก ทุกวันที่ 7 ของเดือน";
	}
	if(($dataComing["document_code"] == 'RRSN') && date("d") >= 8 && date("d") <= 19){
		$is_disabled = true;
		$arrayResult['REQDOC_REMARK_TITLE'] = "ปิดรับเอกสารใบคำขอลาออก";
		$arrayResult['REQDOC_REMARK_DETAIL'] = "อยู่ระหว่างประมวลผลวันที่ 8-19 กรุณาทำรายการใหม่อีกครั้งหลังวันเวลาดังกล่าว";
	}
	
	$arrayResult['IS_LOANPAY'] = true;
	$arrayResult['DISABLED_REQ'] = $is_disabled;
	$arrayResult['DOCUMENTTYPE_LIST'] = $arrAssistGrp;
	$arrayResult['RESULT'] = TRUE;
	require_once('../../include/exit_footer.php');
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