<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','reqopenaccountlist')){
		$arrGrp = array();
		$arrayGroup = array();
		$arrayExecute = array();
		$fetchDepttype = $conmssql->prepare("SELECT DEPTTYPE_CODE,DEPTTYPE_DESC FROM DPDEPTTYPE ");
		$fetchDepttype->execute();
		while($rowDepttype = $fetchDepttype->fetch(PDO::FETCH_ASSOC)){
			$arrayGroup[$rowDepttype["DEPTTYPE_CODE"]] = $rowDepttype["DEPTTYPE_DESC"];
		}
		
		$getAllReqDocno = $conmysql->prepare("SELECT reqopendoc_no, member_no, depttype_code, req_date, req_status, amount_open, slip_url, doc_url
														FROM gcreqopenaccount WHERE req_status = :req_status");
		$getAllReqDocno->execute([':req_status' => $dataComing["req_status"]]);
		while($rowDocno = $getAllReqDocno->fetch(PDO::FETCH_ASSOC)){
			$arrDocno = array();
			$arrDocno["REQOPENDOC_NO"] = $rowDocno["reqopendoc_no"];
			$arrDocno["MEMBER_NO"] = $rowDocno["member_no"];
			$arrDocno["DEPTTYPE_CODE"] = $arrayGroup[$rowDocno["depttype_code"]];
			$arrDocno["REQ_DATE"] = $lib->convertdate($rowDocno["req_date"],'D m Y',true);
			$arrDocno["AMOUNT_OPEN"] = $rowDocno["amount_open"];
			$arrDocno["SLIP_URL"] = $rowDocno["slip_url"];
			$arrDocno["DOC_URL"] = $rowDocno["doc_url"];
			$arrDocno["REQ_STATUS"]  = $rowDocno["req_status"];
			if($rowDocno["req_status"] == '8'){
				$arrDocno["REQ_STATUS_DESC"] = "รอดำเนินการ";
			}else if($rowDocno["req_status"] == '1'){
				$arrDocno["REQ_STATUS_DESC"] = "อนุมัติ";
			}else {
				$arrDocno["REQ_STATUS_DESC"] = "ยกเลิก";
			}
			$arrGrp[] = $arrDocno;
		}
			
		$arrayResult['REQ_LIST'] = $arrGrp;
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