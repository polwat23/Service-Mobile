<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DocumentRequestTrack')){
		$arrayGrpForm = array();
		$arrayGrp = array();
		$arrayDocGrp = array();
		
		$getReqDocument = $conmysql->prepare("SELECT rd.reqdoc_no, rd.member_no, rd.documenttype_code, rd.form_value, rd.document_url, rd.req_status, rd.request_date, rd.update_date,dt.documenttype_desc
										FROM gcreqdoconline rd 
										LEFT JOIN gcreqdoctype dt ON dt.documenttype_code = rd.documenttype_code 
										WHERE rd.member_no = :member_no ORDER BY rd.request_date DESC");
		$getReqDocument->execute([
			':member_no' => $payload["member_no"],
		]);
		while($rowReqDocument = $getReqDocument->fetch(PDO::FETCH_ASSOC)){
			$arrayDoc = array();
			$arrayDoc["REQDOC_NO"] = $rowReqDocument["reqdoc_no"];
			$arrayDoc["MEMBER_NO"] = $rowReqDocument["member_no"];
			$arrayDoc["DOCUMENTTYPE_CODE"] = $rowReqDocument["documenttype_code"];
			$arrayDoc["DOCUMENTTYPE_DESC"] =  $rowReqDocument["documenttype_desc"];
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
			$arrayDoc["REQUEST_DATE_RAW"] = $rowReqDocument["request_date"];
			$arrayDocGrp[] = $arrayDoc;
		}
		
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