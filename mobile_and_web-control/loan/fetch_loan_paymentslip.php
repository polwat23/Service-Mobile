<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','loancontract_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrAllLoan = array();
		
		$getContract = $conmssql->prepare("SELECT lt.LOANTYPE_DESC AS LOAN_TYPE,RTRIM(ln.LOANCONTRACT_NO) as LOANCONTRACT_NO,ln.principal_balance as LOAN_BALANCE,ln.LOANTYPE_CODE,lt.LOANGROUP_CODE, 
											ln.loanapprove_amt as APPROVE_AMT,ln.STARTCONT_DATE,ln.PERIOD_PAYMENT,ln.period_payamt as PERIOD,
											ln.LAST_PERIODPAY as LAST_PERIOD,
											(SELECT max(operate_date) FROM lncontstatement WHERE loancontract_no = ln.loancontract_no) as LAST_OPERATE_DATE
											FROM lncontmaster ln LEFT JOIN LNLOANTYPE lt ON ln.LOANTYPE_CODE = lt.LOANTYPE_CODE 
											WHERE ln.member_no = :member_no and ln.contract_status > 0 and ln.contract_status <> 8
											AND  ln.loancontract_no = :loancontract_no");
		$getContract->execute([
			':member_no' => $member_no,
			':loancontract_no' => $dataComing["loancontract_no"]
		]);
		$rowContract = $getContract->fetch(PDO::FETCH_ASSOC);
		$arrGroupContract = array();
		$contract_no = preg_replace('/\//','',$rowContract["LOANCONTRACT_NO"]);
		$arrContract = array();
		
		$arrayPaymentGrp = array();
		$getPaymentSlip = $conmysql->prepare("SELECT id_slip_paydept, member_no, loancontract_no, principle, interest, req_status, remark, 
											payment_amt, slip_url, create_date FROM gcslippaydept 
											WHERE member_no = :member_no and loancontract_no = :loancontract_no ORDER BY create_date DESC limit 2");
		$getPaymentSlip->execute([
			':member_no' => $payload["member_no"],
			':loancontract_no' => $contract_no
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
	
		$arrContract["PAYMENT_SLIP"] = $arrayPaymentGrp;
		
		$interest = $cal_loan->calculateInterestRevert($rowContract["LOANCONTRACT_NO"]);
		$arrContract["CONTRACT_NO"] = $contract_no;
		$arrContract["LOAN_BALANCE"] = number_format($rowContract["LOAN_BALANCE"]+$cal_loan->calculateInterestRevert($rowContract["LOANCONTRACT_NO"]),2);
		$arrContract["APPROVE_AMT"] = number_format($rowContract["APPROVE_AMT"],2);
		$arrContract["LAST_OPERATE_DATE"] = $lib->convertdate($rowContract["LAST_OPERATE_DATE"],'y-n-d');
		$arrContract["LAST_OPERATE_DATE_FORMAT"] = $lib->convertdate($rowContract["LAST_OPERATE_DATE"],'D m Y');
		$arrContract["STARTCONT_DATE"] = $lib->convertdate($rowContract["STARTCONT_DATE"],'D m Y');
		$arrContract["PERIOD_PAYMENT"] = number_format($rowContract["PERIOD_PAYMENT"],2);
		$arrContract["PERIOD"] = $rowContract["LAST_PERIOD"].' / '.$rowContract["PERIOD"];
		$arrContract["INTEREST"] = number_format($interest,2);
		$arrContract["PRINCIPLE"] = number_format($rowContract["LOAN_BALANCE"],2);
		$arrContract['TYPE_LOAN'] = $rowContract["LOAN_TYPE"];
		$arrContract['LOANGROUP_CODE'] = $rowContract["LOANGROUP_CODE"];
		$arrContract['IS_DISABLE_PAYMENT'] = false;
		$arrContract['IS_CANUPLOAD'] = true;
		$arrContract['PAYMENT_VALUE'] =  $rowContract["LOAN_BALANCE"] + $interest;
		
		$start_paydate = 24;
		if(date('w', strtotime(date("Y-m").'-'.$start_paydate)) == 0 || date('w', strtotime(date("Y-m").'-'.$start_paydate)) == 6){
			$startPayDate = date('d', strtotime(date("Y-m").'-'.$start_paydate." friday this week"));
		}else{
			$startPayDate = $start_paydate;
		}
		
		if(!(date("d") >= $startPayDate || date("d") <= 7)){
			$arrContract['IS_CANUPLOAD'] = false;
			$arrContract['REMARK'] = 'เปิดให้ชำหนี้เฉพาะวันที่ '.$start_paydate.' - 7 กรุณาทำรายการใหม่อีกครั้งในวันเวลาดังกล่าว';
		}else if($rowContract["LOANGROUP_CODE"] == '01'){
			$getActiveSlip = $conmysql->prepare("SELECT id_slip_paydept, member_no, loancontract_no, principle, interest, req_status, remark, 
											payment_amt, slip_url, create_date FROM gcslippaydept 
											WHERE member_no = :member_no and loancontract_no = :loancontract_no and req_status not in ('9','-9')");
			$getActiveSlip->execute([
				':member_no' => $payload["member_no"],
				':loancontract_no' => $contract_no
			]);
			$rowActiveSlip = $getActiveSlip->fetch(PDO::FETCH_ASSOC);
			$arrContract['IS_DISABLE_PAYMENT'] = true;
			$arrContract['PAYMENT_VALUE'] = $rowContract["LOAN_BALANCE"] + $interest;
			$arrContract['REMARK'] = 'เงื่อนไขการชำระหนี้ เงินกู้ฉุกเฉินต้องชำระเงินต้นทั้งหมดที่เป็นหนี้คงเหลือ+ดอกเบี้ย';
			if(isset($rowActiveSlip["id_slip_paydept"])){
				$arrContract['IS_DISABLE_PAYMENT'] = true;
				$arrContract['IS_CANUPLOAD'] = false;
				$arrContract['PAYMENT_VALUE'] = $rowContract["LOAN_BALANCE"] + $interest;
				$arrContract['REMARK'] = 'ไม่สามารถทำรายรายเพิ่มได้ เนื่องจากมีรายการชำระหนี้เดิมรอดำเนินการอยู่';
			}
		}else{
			if(date("d") != $startPayDate){
				$arrContract['IS_DISABLE_PAYMENT'] = true;
			}
		}
		$arrContract['MAX_PAYMENT'] = $rowContract["LOAN_BALANCE"] + $interest;
		$arrContract['MIN_PAYMENT'] = 0;
		
		$arrayResult['DETAIL_LOAN'] = $arrContract;
		$arrayResult['startPayDate'] = $startPayDate;
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