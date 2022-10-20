<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','loanpaymentslip')){
		$arrGrp = array();
		$arrayDocGrp = array();
		
		$getReqDocument = $conmysql->prepare("SELECT id_slip_paydept, member_no, loancontract_no, principle, interest, req_status, remark, reqdoc_no, payment_acc,
											payment_amt, slip_url, create_date FROM gcslippaydept 
											ORDER BY create_date DESC");
		$getReqDocument->execute();
		while($rowPaymentSlip = $getReqDocument->fetch(PDO::FETCH_ASSOC)){
			$arrayDoc = array();
			$arrayDoc["ID_SLIP_PAYDEPT"] = $rowPaymentSlip["id_slip_paydept"];
			$arrayDoc["MEMBER_NO"] = $rowPaymentSlip["member_no"];
			$arrayDoc["LOANCONTRACT_NO"] = $rowPaymentSlip["loancontract_no"];
			$arrayDoc["PRINCIPLE"] =  number_format($rowPaymentSlip["principle"],2);
			$arrayDoc["INTEREST"] = number_format($rowPaymentSlip["interest"],2);
			$arrayDoc["PAYMENT_AMT"] = number_format($rowPaymentSlip["payment_amt"],2);
			$arrayDoc["SLIP_URL"] = $rowPaymentSlip["slip_url"];
			$arrayDoc["PAYMENT_ACC"] = $rowPaymentSlip["payment_acc"];
			$arrayDoc["REQUEST_DATE"] = $lib->convertdate($rowPaymentSlip["create_date"],"D m Y", true);
			$arrayDoc["REQUEST_DATE_RAW"] = $rowPaymentSlip["create_date"];
			$arrayDoc["REMARK"] = $rowPaymentSlip["remark"];
			$arrayDoc["REQ_STATUS"] = $rowPaymentSlip["req_status"];
			$arrayDoc["REQDOC_NO"] = $rowPaymentSlip["reqdoc_no"];
			if($rowPaymentSlip["req_status"] == '1'){
				$arrayDoc["REQ_STATUS_DESC"] = "อนุมัติ";
			}else if($rowPaymentSlip["req_status"] == '8'){
				$arrayDoc["REQ_STATUS_DESC"] = "รอลงรับ";
				if(isset($rowPaymentSlip["reqdoc_no"])){
					$arrayDoc["ALLOW_CANCEL"] = false;
				}else{
					$arrayDoc["ALLOW_CANCEL"] = true;
				}
			}else if($rowPaymentSlip["req_status"] == '9'){
				$arrayDoc["REQ_STATUS_DESC"] = "ยกเลิก";
				$arrayDoc["REQ_STATUS_COLOR"] = "#f5222d";
			}else if($rowPaymentSlip["req_status"] == '-9'){
				$arrayDoc["REQ_STATUS_DESC"] = "ไม่อนุมัติ";
				$arrayDoc["REQ_STATUS_COLOR"] = "#f5222d";
			}else if($rowPaymentSlip["req_status"] == '7'){
				$arrayDoc["REQ_STATUS_DESC"] = "ลงรับรอตรวจสิทธิ์เพิ่มเติม";
				if(isset($rowPaymentSlip["reqdoc_no"])){
					$arrayDoc["ALLOW_CANCEL"] = false;
				}else{
					$arrayDoc["ALLOW_CANCEL"] = true;
				}
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