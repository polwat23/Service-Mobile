<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','loanrequestform')){
		$arrGrp = array();
		$arrayType = array();
		$getLoanTypeDesc = $conoracle->prepare("SELECT loantype_desc,loantype_code FROM lnloantype");
		$getLoanTypeDesc->execute();
		while($rowType = $getLoanTypeDesc->fetch(PDO::FETCH_ASSOC)){
			$arrayType[$rowType["LOANTYPE_CODE"]] = $rowType["LOANTYPE_DESC"];
		}
		if(isset($dataComing["req_status"]) && $dataComing["req_status"] != ""){
			$getAllReqDocno = $conmysql->prepare("SELECT reqloan_doc,member_no,loantype_code,request_amt,period_payment,period,loanpermit_amt,salary_img,citizen_img,req_status,request_date,approve_date
															FROM gcreqloan WHERE req_status = :req_status");
			$getAllReqDocno->execute([
				':req_status' => $dataComing["req_status"]
			]);
			while($rowDocno = $getAllReqDocno->fetch(PDO::FETCH_ASSOC)){
				$arrDocno = array();
				$arrDocno["REQDOC_NO"] = $rowDocno["reqloan_doc"];
				$arrDocno["MEMBER_NO"] = $rowDocno["member_no"];
				$arrDocno["LOANTYPE_CODE"] = $rowDocno["loantype_code"];
				$arrDocno["LOANTYPE_DESC"] = $arrayType[$rowDocno["loantype_code"]];
				$arrDocno["REQUEST_AMT"] = number_format($rowDocno["request_amt"],2);
				$arrDocno["PERIOD_PAYMENT"] = number_format($rowDocno["period_payment"],2);
				$arrDocno["REQUEST_DATE"] = $lib->convertdate($rowDocno["request_date"],'d m Y',true);
				if($rowDocno["req_status"] == '1'){
					$arrDocno["APPROVE_DATE"] = $lib->convertdate($rowDocno["approve_date"],'d m Y',true);
				}
				$arrDocno["PERIOD"] = $rowDocno["period"];
				$arrDocno["LOANPERMIT_AMT"] = number_format($rowDocno["loanpermit_amt"],2);
				$arrDocno["SALARY_IMG"] = $rowDocno["salary_img"];
				$arrDocno["CITIZEN_IMG"] = $rowDocno["citizen_img"];
				$arrDocno["REQ_STATUS"]  = $rowDocno["req_status"];
				if($rowDocno["req_status"] == '8'){
					$arrDocno["REQ_STATUS_DESC"] = "รอลงรับ";
				}else if($rowDocno["req_status"] == '1'){
					$arrDocno["REQ_STATUS_DESC"] = "อนุมัติ";
				}else if($rowDocno["req_status"] == '-9'){
					$arrDocno["REQ_STATUS_DESC"] = "ไม่อนุมัติ";
				}else if($rowDocno["req_status"] == '7'){
					$arrDocno["REQ_STATUS_DESC"] = "ลงรับรอตรวจสิทธิ์เพิ่มเติม";
				}
				$arrGrp[] = $arrDocno;
			}
		}else{
			$getAllReqDocno = $conmysql->prepare("SELECT reqloan_doc,member_no,loantype_code,request_amt,period_payment,period,loanpermit_amt,salary_img,citizen_img,request_date 
															FROM gcreqloan WHERE req_status = '8'");
			$getAllReqDocno->execute();
			while($rowDocno = $getAllReqDocno->fetch(PDO::FETCH_ASSOC)){
				$arrDocno = array();
				$arrDocno["REQDOC_NO"] = $rowDocno["reqloan_doc"];
				$arrDocno["MEMBER_NO"] = $rowDocno["member_no"];
				$arrDocno["LOANTYPE_CODE"] = $rowDocno["loantype_code"];
				$arrDocno["LOANTYPE_DESC"] = $arrayType[$rowDocno["loantype_code"]];
				$arrDocno["REQUEST_AMT"] = number_format($rowDocno["request_amt"],2);
				$arrDocno["PERIOD_PAYMENT"] = number_format($rowDocno["period_payment"],2);
				$arrDocno["PERIOD"] = $rowDocno["period"];
				$arrDocno["REQUEST_DATE"] = $lib->convertdate($rowDocno["request_date"],'d m Y',true);
				$arrDocno["LOANPERMIT_AMT"] = number_format($rowDocno["loanpermit_amt"],2);
				$arrDocno["SALARY_IMG"] = $rowDocno["salary_img"];
				$arrDocno["CITIZEN_IMG"] = $rowDocno["citizen_img"];
				$arrDocno["REQ_STATUS"] = "8";
				$arrDocno["REQ_STATUS_DESC"] = "รอลงรับ";
				$arrGrp[] = $arrDocno;
			}
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