<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','documentrequest')){
		$arrGrp = array();
		$arrayDocGrp = array();
		
		$getReqDocument = $conoracle->prepare("select * from mem_t_candidate order by can_no desc");
		$getReqDocument->execute();
		while($rowReqDocument = $getReqDocument->fetch(PDO::FETCH_ASSOC)){
			$arrayDoc = array();
			/*$arrayDoc["REQDOC_NO"] = $rowReqDocument["reqdoc_no"];
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
			}*/
			$formValue = array();
			$formValue["MEMBER_CARDID"]["VALUE"] = $rowReqDocument["CAN_ID_CARD"];
			$formValue["MEMBER_MOBILEPHONE"]["VALUE"] = $rowReqDocument["MOBILE_TEL"];
			$formValue["ADDRESS_ADDR_NO"]["VALUE"] = $rowReqDocument["ADDRESS"];
			$formValue["ADDRESS_ROAD"]["VALUE"] = $rowReqDocument["TANON"];
			$formValue["ADDRESS_SOI"]["VALUE"] = $rowReqDocument["SOI"];
			$formValue["ADDRESS_VILLAGE_NO"]["VALUE"] = $rowReqDocument["MOO_ADDR"];
			$formValue["ADDRESS_DISTRICT_CODE"]["VALUE"]["DISTRICT_CODE"] = $rowReqDocument["DISTRICT_ID"];
			$formValue["ADDRESS_DISTRICT_CODE"]["VALUE"]["DISTRICT_DESC"] = $rowReqDocument["DISTRICT_NAME"];
			$formValue["ADDRESS_DISTRICT_CODE"]["VALUE"]["PROVINCE_CODE"] = $rowReqDocument["PROVINCE_ID"];
			$formValue["ADDRESS_DISTRICT_CODE"]["VALUE"]["POSTCODE"] = $rowReqDocument["ZIP_CODE"];
			$formValue["ADDRESS_TAMBOL_CODE"]["VALUE"]["TAMBOL_CODE"] = $rowReqDocument[""];
			$formValue["ADDRESS_TAMBOL_CODE"]["VALUE"]["TAMBOL_DESC"] = $rowReqDocument["TUMBOL"];
			$formValue["ADDRESS_TAMBOL_CODE"]["VALUE"]["DISTRICT_CODE"] = $rowReqDocument["DISTRICT_ID"];
			$formValue["ADDRESS_PROVINE_CODE"]["VALUE"]["PROVINCE_CODE"] = $rowReqDocument["PROVINCE_ID"];
			$formValue["ADDRESS_PROVINE_CODE"]["VALUE"]["PROVINCE_DESC"] = $rowReqDocument["PROVINCE_NAME"];
			$formValue["ADDRESS_POSTCODE"]["VALUE"] = $rowReqDocument["ZIP_CODE"];
			
			$formValue["SHARE_PERIOD_PAYMENT"]["VALUE"] = $rowReqDocument["SHR_INCV_BTH"];
			
			$arrayDoc["FORM_VALUE"] = json_encode($formValue);
			$arrayDoc["DOCUMENTTYPE_CODE"] = "RRGT";
			$arrayDoc["DOCUMENTTYPE_DESC"] = "ใบคำขอสมัครสมาชิก";
			$arrayDoc["REQ_STATUS"] = '8';
			$arrayDoc["REQ_STATUS_DESC"] = "รอลงรับ";
			$arrayDoc["REQDOC_NO"] = $rowReqDocument["CAN_NO"];
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