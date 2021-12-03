<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanPaymentSlip')){
		$arrayGrpForm = array();
		$arrayPaymentGrp = array();
		
		$getPaymentSlip = $conmysql->prepare("SELECT id_slip_paydept, member_no, loancontract_no, principle, interest, req_status, remark, 
											payment_amt, slip_url, create_date FROM gcslippaydept 
											WHERE member_no = :member_no ORDER BY create_date DESC");
		$getPaymentSlip->execute([
			':member_no' => $payload["member_no"]
		]);
		while($rowPaymentSlip = $getPaymentSlip->fetch(PDO::FETCH_ASSOC)){
			$arrayDoc = array();
			$arrayDoc["ID_SLIP_PAYDEPT"] = $rowPaymentSlip["id_slip_paydept"];
			$arrayDoc["MEMBER_NO"] = $rowPaymentSlip["member_no"];
			$arrayDoc["LOANCONTRACT_NO"] = $rowPaymentSlip["loancontract_no"];
			$arrayDoc["PRINCIPLE"] =  number_format($rowPaymentSlip["principle"],2);
			$arrayDoc["INTEREST"] = number_format($rowPaymentSlip["interest"],2);
			$arrayDoc["PAYMENT_AMT"] = number_format($rowPaymentSlip["payment_amt"],2);
			$arrayDoc["SLIP_URL"] = $rowPaymentSlip["slip_url"];
			$arrayDoc["REQUEST_DATE"] = $lib->convertdate($rowPaymentSlip["create_date"],"D m Y", true);
			$arrayDoc["REQUEST_DATE_RAW"] = $rowPaymentSlip["create_date"];
			$arrayDoc["REMARK"] = $rowPaymentSlip["remark"];
			$arrayDoc["REQ_STATUS"] = $rowPaymentSlip["req_status"];
			if($rowPaymentSlip["req_status"] == '1'){
				$arrayDoc["REQ_STATUS_DESC"] = "อนุมัติ";
			}else if($rowPaymentSlip["req_status"] == '8'){
				$arrayDoc["REQ_STATUS_DESC"] = "รอลงรับ";
				$arrayDoc["ALLOW_CANCEL"] = true;
			}else if($rowPaymentSlip["req_status"] == '9'){
				$arrayDoc["REQ_STATUS_DESC"] = "ยกเลิก";
				$arrayDoc["REQ_STATUS_COLOR"] = "#f5222d";
			}else if($rowPaymentSlip["req_status"] == '-9'){
				$arrayDoc["REQ_STATUS_DESC"] = "ไม่อนุมัติ";
				$arrayDoc["REQ_STATUS_COLOR"] = "#f5222d";
			}else if($rowPaymentSlip["req_status"] == '7'){
				$arrayDoc["REQ_STATUS_DESC"] = "ลงรับรอตรวจสิทธิ์เพิ่มเติม";
			}
			$arrayPaymentGrp[] = $arrayDoc;
		}
		
		$arrayResult['PAYMENT_SLIP'] = $arrayPaymentGrp;
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