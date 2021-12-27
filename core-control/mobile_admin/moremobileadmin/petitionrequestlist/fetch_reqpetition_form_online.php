<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','petitionrequestlist')){
		$arrGrp = array();
		$arrayExecute = array();
		
		if(isset($dataComing["req_status"]) && $dataComing["req_status"] != ""){
			$arrayExecute[':req_status'] = $dataComing["req_status"];
		}
		if(isset($dataComing["filter_member_no"]) && $dataComing["filter_member_no"] != ""){
			$arrayExecute[':filter_member_no'] = strtolower($lib->mb_str_pad($dataComing["filter_member_no"]));
		}
		if(isset($dataComing["filter_req_docno"]) && $dataComing["filter_req_docno"] != ""){
			$arrayExecute[':filter_req_docno'] = $dataComing["filter_req_docno"];
		}
		if(isset($dataComing["start_date"]) && $dataComing["start_date"] != ""){
			$arrayExecute[':start_date'] = $dataComing["start_date"];
		}
		if(isset($dataComing["end_date"]) && $dataComing["end_date"] != ""){
			$arrayExecute[':end_date'] = $dataComing["end_date"];
		}
		if(isset($dataComing["req_status"]) && $dataComing["req_status"] != ""){
			$getAllReqDocno = $conmysql->prepare("SELECT reqdoc_no, member_no, petitionform_code, petitionform_desc, document_url, req_status, remark, req_desc, effect_date, request_date, update_date
															FROM gcpetitionformreq WHERE req_status = :req_status". 
															($dataComing["is_filtered"] ? (
															(isset($dataComing["filter_member_no"]) && $dataComing["filter_member_no"] != '' ? " and member_no = :filter_member_no" : null).
															(isset($dataComing["filter_req_docno"]) && $dataComing["filter_req_docno"] != '' ? " and reqdoc_no = :filter_req_docno" : null).
															(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? " and date_format(request_date,'%Y-%m-%d') >= :start_date" : null).
															(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? " and date_format(request_date,'%Y-%m-%d') <= :end_date" : null)
															) 
															: " AND DATE_FORMAT(request_date, '%Y-%m-%d') >= now()-interval 3 month"));
			$getAllReqDocno->execute($dataComing["is_filtered"] ? $arrayExecute : [':req_status' => $dataComing["req_status"]]);
			while($rowDocno = $getAllReqDocno->fetch(PDO::FETCH_ASSOC)){
				$arrDocno = array();
				$arrDocno["REQDOC_NO"] = $rowDocno["reqdoc_no"];
				$arrDocno["MEMBER_NO"] = $rowDocno["member_no"];
				$arrDocno["PETITIONFORM_CODE"] = $rowDocno["petitionform_code"];
				$arrDocno["PETITIONFORM_DESC"] = $rowDocno["petitionform_desc"];
				$arrDocno["DOCUMENT_URL"] = $rowDocno["document_url"];
				$arrDocno["REMARK"] = $rowDocno["remark"];
				$arrDocno["REQ_DESC"] = $rowDocno["req_desc"];
				$arrDocno["REQUEST_DATE"] = $lib->convertdate($rowDocno["request_date"],'d m Y',true);
				$arrDocno["EFFECT_DATE"] = $lib->convertdate($rowDocno["effect_date"],'d m Y');
				$arrDocno["REQ_STATUS"]  = $rowDocno["req_status"];
				if($rowDocno["req_status"] == '8'){
					$arrDocno["REQ_STATUS_DESC"] = "รอลงรับ";
				}else if($rowDocno["req_status"] == '1'){
					$arrDocno["REQ_STATUS_DESC"] = "อนุมัติ";
				}else if($rowDocno["req_status"] == '-9'){
					$arrDocno["REQ_STATUS_DESC"] = "ไม่อนุมัติ";
				}else if($rowDocno["req_status"] == '7'){
					$arrDocno["REQ_STATUS_DESC"] = "ลงรับรอตรวจสิทธิ์เพิ่มเติม";
				}else{
					$arrDocno["REQ_STATUS_DESC"] = "ยกเลิก";
				}
				$arrGrp[] = $arrDocno;
			}
		}else{
			$getAllReqDocno = $conmysql->prepare("reqdoc_no, member_no, petitionform_code, petitionform_desc, document_url, req_status, remark, req_desc, effect_date, request_date, update_date
															FROM gcpetitionformreq WHERE 1=1". 
															($dataComing["is_filtered"] ? (
															(isset($dataComing["filter_member_no"]) && $dataComing["filter_member_no"] != '' ? " and member_no = :filter_member_no" : null).
															(isset($dataComing["filter_req_docno"]) && $dataComing["filter_req_docno"] != '' ? " and reqdoc_no = :filter_req_docno" : null).
															(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? " and date_format(request_date,'%Y-%m-%d') >= :start_date" : null).
															(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? " and date_format(request_date,'%Y-%m-%d') <= :end_date" : null)
															) 
															: " AND DATE_FORMAT(request_date, '%Y-%m-%d') >= now()-interval 3 month"));
			$getAllReqDocno->execute($dataComing["is_filtered"] ? $arrayExecute : []);
			while($rowDocno = $getAllReqDocno->fetch(PDO::FETCH_ASSOC)){
				$arrDocno = array();
				$arrDocno["REQDOC_NO"] = $rowDocno["reqdoc_no"];
				$arrDocno["MEMBER_NO"] = $rowDocno["member_no"];
				$arrDocno["PETITIONFORM_CODE"] = $rowDocno["petitionform_code"];
				$arrDocno["PETITIONFORM_DESC"] = $rowDocno["petitionform_desc"];
				$arrDocno["DOCUMENT_URL"] = $rowDocno["document_url"];
				$arrDocno["REMARK"] = $rowDocno["remark"];
				$arrDocno["REQ_DESC"] = $rowDocno["req_desc"];
				$arrDocno["EFFECT_DATE"] = $rowDocno["effect_date"];
				$arrDocno["REQUEST_DATE"] = $lib->convertdate($rowDocno["request_date"],'d m Y',true);
				$arrDocno["EFFECT_DATE"] = $lib->convertdate($rowDocno["effect_date"],'d m Y');
				$arrDocno["REQ_STATUS"]  = $rowDocno["req_status"];
				if($rowDocno["req_status"] == '8'){
					$arrDocno["REQ_STATUS_DESC"] = "รอลงรับ";
				}else if($rowDocno["req_status"] == '1'){
					$arrDocno["REQ_STATUS_DESC"] = "อนุมัติ";
				}else if($rowDocno["req_status"] == '-9'){
					$arrDocno["REQ_STATUS_DESC"] = "ไม่อนุมัติ";
				}else if($rowDocno["req_status"] == '7'){
					$arrDocno["REQ_STATUS_DESC"] = "ลงรับรอตรวจสิทธิ์เพิ่มเติม";
				}else{
					$arrDocno["REQ_STATUS_DESC"] = "ยกเลิก";
				}
				$arrGrp[] = $arrDocno;
			}
		}
		$arrayResult['REQ_LIST'] = $arrGrp;
		$arrayResult['REQ_MESSAGE'] = $dataComing["is_filtered"] ? null : "รายการใบคำร้อง 3 เดือนล่าสุด";
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