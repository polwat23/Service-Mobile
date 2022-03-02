<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','documentrequest')){
		$arrGrp = array();
		$arrayDocGrp = array();
		
		$getReqDocument = $conmysql->prepare("SELECT rd.reqdoc_no, rd.member_no, rd.documenttype_code, rd.form_value, rd.document_url, rd.req_status, rd.request_date, rd.update_date,dt.documenttype_desc
										FROM gcreqdoconline rd 
										LEFT JOIN gcreqdoctype dt ON dt.documenttype_code = rd.documenttype_code 
										ORDER BY rd.request_date DESC");
		$getReqDocument->execute();
		while($rowReqDocument = $getReqDocument->fetch(PDO::FETCH_ASSOC)){
			$arrayDoc = array();
			$arrayDoc["REQDOC_NO"] = $rowReqDocument["reqdoc_no"];
			$arrayDoc["MEMBER_NO"] = $rowReqDocument["member_no"];
			$arrayDoc["DOCUMENTTYPE_CODE"] = $rowReqDocument["documenttype_code"];
			$arrayDoc["DOCUMENTTYPE_DESC"] = $rowReqDocument["documenttype_desc"];
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
			if($rowReqDocument["documenttype_code"] == "RRSN"){
				$arrayDoc["IS_PAYSLIP"] = true;
			}
			$arrayDocGrp[] = $arrayDoc;
		}
		
		$arrayResult['REQ_DOCUMENT'] = $arrayDocGrp;
		$arrayResult['REQ_MESSAGE'] = $dataComing["is_filtered"] ? null : null;
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