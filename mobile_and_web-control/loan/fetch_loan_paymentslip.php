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
		$getPaymentSlip = $conmysql->prepare("SELECT id_slip_paydept, member_no, loancontract_no, principle, interest, req_status, remark, payment_acc,
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
			$arrayDoc["PAYMENT_ACC"] = $rowPaymentSlip["payment_acc"];
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
		
		
		if(date('w', strtotime(date("Y-m").'-28')) == 0 || date('w', strtotime(date("Y-m").'-28')) == 6){
			$startPayDate = date('d', strtotime(date("Y-m").'-28'." friday this week"));
		}else{
			$startPayDate = 28;
		}
		
		if(!(date("d") >= $startPayDate || date("d") <= 7)){
			$arrContract['IS_CANUPLOAD'] = false;
			$arrContract['REMARK'] = 'เปิดให้ชำระหนี้เฉพาะวันที่ 28 - 7 กรุณาทำรายการใหม่อีกครั้งในวันเวลาดังกล่าว';
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
			$day = date('d');
			if($day <= 7){
				$start_date = new DateTime('first day of this month');
				$start_date->modify('-2 month');
				$start_date = $start_date->format('Y-m-08');
				$end_date = new DateTime('first day of this month');
				$end_date->modify('-1 month');
				$end_date = $end_date->format('Y-m-07');
			}else{
				$start_date = new DateTime('first day of this month');
				$start_date->modify('-1 month');
				$start_date = $start_date->format('Y-m-08');
				$end_date = new DateTime('first day of this month');
				$end_date = $end_date->format('Y-m-07');
			}
			$getActiveSlip = $conmysql->prepare("SELECT * FROM gcreqdoconline
							WHERE member_no = :member_no AND documenttype_code = 'PAYD' AND req_status = '1' 
							AND DATE_FORMAT(request_date,'%Y-%m-%d') >= :start_date 
							AND DATE_FORMAT(request_date,'%Y-%m-%d') <= :end_date");
			$getActiveSlip->execute([
				':member_no' => $payload["member_no"],
				':start_date' => $start_date,
				':end_date' => $end_date
			]);
			$rowActiveSlip = $getActiveSlip->fetch(PDO::FETCH_ASSOC);
			if($getActiveSlip->rowCount() > 0){
				$arrContract['IS_DISABLE_PAYMENT'] =  false;
			}else{
				$arrContract['IS_DISABLE_PAYMENT'] = true;
				$arrContract['REMARK'] = 'กรณีต้องการชำระหนี้บางส่วน กรุณากรอกข้อมูลใบคำขอชำระหนี้บางส่วนในหน้าจอ "ใบคำขอชำระหนี้บางส่วน" หลังจากใบคำขออนุมัติเเล้วจึงจะทำรายการชำระหนี้บางส่วนได้';
				$arrContract['REMARK_BG_COLOR'] = '#f2a365';
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