<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','documenttype_code'],$dataComing)){
	if($dataComing["documenttype_code"] == 'CBNF'){
		if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DocumentBeneciaryRequest')){
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0006";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			http_response_code(403);
			require_once('../../include/exit_footer.php');
		}
	}else if($dataComing["documenttype_code"] == 'CSHR'){
		if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DocumentShrPaymentRequest')){
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0006";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			http_response_code(403);
			require_once('../../include/exit_footer.php');
		}
	}else if($dataComing["documenttype_code"] == 'RRSN'){
		if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DocumentResignRequest')){
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0006";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			http_response_code(403);
			require_once('../../include/exit_footer.php');
		}
	}else if($dataComing["documenttype_code"] == 'RCER'){
		if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DocumentCertRequest')){
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0006";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			http_response_code(403);
			require_once('../../include/exit_footer.php');
		}
	}else if($dataComing["documenttype_code"] == 'PAYD'){
		if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DocumentPayDeptRequest')){
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0006";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			http_response_code(403);
			require_once('../../include/exit_footer.php');
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../include/exit_footer.php');
	}

		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrayGrpForm = array();
		$arrayGrp = array();
		$arrayDocGrp = array();
		
		$getReqDocument = $conmysql->prepare("SELECT ro.reqdoc_no, ro.member_no, ro.documenttype_code, ro.form_value, ro.document_url, ro.req_status, ro.request_date, ro.update_date, rt.documenttype_desc
										FROM gcreqdoconline ro
										JOIN gcreqdoctype rt ON ro.documenttype_code = rt.documenttype_code
										WHERE ro.req_status NOT IN('9','-9','1') and ro.documenttype_code = :documenttype_code and ro.member_no = :member_no");
		$getReqDocument->execute([
			':documenttype_code' => $dataComing["documenttype_code"],
			':member_no' => $payload["member_no"],
		]);
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
			$arrayDocGrp[] = $arrayDoc;
		}
		
		if(count($arrayDocGrp) > 0){
			$arrayResult['CAN_REQ'] = FALSE;
		}else{
			$arrayResult['CAN_REQ'] = TRUE;
		}
		
		$getFormatForm = $conmysql->prepare("SELECT id_format_req_doc, documenttype_code, form_label, form_key, group_id, max_value, min_value,
										form_type, colspan, fullwidth, required, placeholder, default_value, form_option, maxwidth, mask
										FROM gcformatreqdocument
										WHERE is_use = '1' AND documenttype_code = :documenttype_code");
		$getFormatForm->execute([
			':documenttype_code' => $dataComing["documenttype_code"]
		]);
		
		if($dataComing["documenttype_code"] == 'CBNF'){
			$arrayForm = array();
			$arrayForm["GROUP_ID"] = "1";
			$arrayForm["GROUP_DESC"] = null;
			$arrayGrpData[] = $arrayForm;
			$arrayForm = array();
			$arrayForm["GROUP_ID"] = "2";
			$arrayForm["GROUP_DESC"] = "รายชื่อผู้รับผลประโยชน์";
			$arrayGrpData[] = $arrayForm;
		}else if($dataComing["documenttype_code"] == 'CSHR'){
			$arrayForm = array();
			$arrayForm["GROUP_ID"] = "1";
			$arrayForm["GROUP_DESC"] = 'ความประสงค์การส่งหุ้นรายเดือน';
			$arrayGrpData[] = $arrayForm;
			$arrayForm = array();
			$arrayForm["GROUP_ID"] = "2";
			$arrayForm["GROUP_DESC"] = "การส่งหุ้นรายเดือน";
			$arrayGrpData[] = $arrayForm;
		}else if($dataComing["documenttype_code"] == 'RRSN'){
			$arrayForm = array();
			$arrayForm["GROUP_ID"] = "1";
			$arrayForm["GROUP_DESC"] = "ข้อมูลหุ้นและหนี้";
			$arrayGrpData[] = $arrayForm;
			$arrayForm = array();
			$arrayForm["GROUP_ID"] = "2";
			$arrayForm["GROUP_DESC"] = "กรอกข้อมูลใบคำขอลาออก";
			$arrayGrpData[] = $arrayForm;
		}else if($dataComing["documenttype_code"] == 'PAYD'){
			$arrayForm = array();
			$arrayForm["GROUP_ID"] = "1";
			$arrayForm["GROUP_DESC"] = "ชำระหนี้ (วันที่ 28 ของเดือน)";
			$arrayGrpData[] = $arrayForm;
			$arrayForm = array();
			$arrayForm["GROUP_ID"] = "3";
			$arrayForm["GROUP_DESC"] = "บัญชีสหกรณ์ฯ";
			$arrayGrpData[] = $arrayForm;
		}
		//ชำระหนี้บางส่วน
		if($dataComing["documenttype_code"] == "PAYD"){;
			$arrOldContract = array();
			$getOldContract = $conmssql->prepare("SELECT LM.PRINCIPAL_BALANCE,LT.LOANTYPE_DESC,LM.LOANCONTRACT_NO,LM.LAST_PERIODPAY, lt.LOANGROUP_CODE, lm.LOANTYPE_CODE, lm.PERIOD_PAYMENT
										FROM lncontmaster lm LEFT JOIN lnloantype lt ON lm.loantype_code = lt.loantype_code 
										WHERE lm.member_no = :member_no and lm.contract_status > 0 and lm.contract_status <> 8 and lt.loangroup_code = '02'");
			$getOldContract->execute([
				':member_no' => $member_no
			]);
			while($rowOldContract = $getOldContract->fetch(PDO::FETCH_ASSOC)){
				$arrContract = array();
				$contract_no = preg_replace('/\//','',$rowOldContract["LOANCONTRACT_NO"]);
				$arrContract['LOANTYPE_DESC'] = $rowOldContract["LOANTYPE_DESC"];
				$arrContract["CONTRACT_NO"] = $contract_no;
				$arrContract['BALANCE'] = $rowOldContract["PRINCIPAL_BALANCE"];
				$arrContract['BALANCE_AND_INTEREST'] = $rowOldContract["PRINCIPAL_BALANCE"] + round($cal_loan->calculateInterest($rowOldContract["LOANCONTRACT_NO"]), 0, PHP_ROUND_HALF_DOWN);
				$arrContract['INTEREST'] = round($cal_loan->calculateInterest($rowOldContract["LOANCONTRACT_NO"]), 0, PHP_ROUND_HALF_DOWN);
				
				$arrOldContract[] = $arrContract;
			}
			$arrayForm = array();
			$arrayForm["ID_FORMAT_REQ_DOC"] = null;
			$arrayForm["DOCUMENTTYPE_CODE"] = "RRSN";
			$arrayForm["FORM_LABEL"] = "รายละเอียดเงินกู้สามัญ";
			$arrayForm["FORM_KEY"] = "CONTRACT";
			$arrayForm["FORM_TYPE"] = "contract";
			$arrayForm["COLSPAN"] = "24";
			$arrayForm["FULLWIDTH"] = false;
			$arrayForm["REQUIRED"] = true;
			$arrayForm["PLACEHOLDER"] = null;
			$arrayForm["DEFAULT_VALUE"] = null;
			$arrayForm["FORM_OPTION"] = json_encode($arrOldContract);
			$arrayForm["MAXWIDTH"] = null;
			$arrayForm["GROUP_ID"] = "1";
			$arrayForm["MAX_VALUE"] = null;
			$arrayForm["MIN_VALUE"] = null;
			$arrayGrpForm[] = $arrayForm;
			
			$arrayForm = array();
			$arrayForm["ID_FORMAT_REQ_DOC"] = null;
			$arrayForm["DOCUMENTTYPE_CODE"] = "RRSN";
			$arrayForm["FORM_LABEL"] = null;
			$arrayForm["FORM_KEY"] = "SHARE_STK";
			$arrayForm["FORM_TYPE"] = "remark";
			$arrayForm["COLSPAN"] = "24";
			$arrayForm["FULLWIDTH"] = false;
			$arrayForm["REQUIRED"] = false;
			$arrayForm["PLACEHOLDER"] = "ชื่อบัญชี “สหกรณ์ออมทรัพย์พนักงานสยามคูโบต้า จำกัด”";
			$arrayForm["DEFAULT_VALUE"] = null;
			$arrayForm["FORM_OPTION"] = null;
			$arrayForm["MAXWIDTH"] = null;
			$arrayForm["GROUP_ID"] = "3";
			$arrayForm["MAX_VALUE"] = '#3f51b5';
			$arrayForm["MIN_VALUE"] = '#3f51b522';
			$arrayGrpForm[] = $arrayForm;
		}
		
		//ข้อมูลใบรับรอง
		if($dataComing["documenttype_code"] == "RCER"){
			$getSharemasterinfo = $conmssql->prepare("SELECT (sharestk_amt * 10) as SHARE_AMT, (periodshare_amt * 10) as PERIOD_SHARE_AMT
												FROM shsharemaster WHERE member_no = :member_no");
			$getSharemasterinfo->execute([':member_no' => $member_no]);
			$rowMastershare = $getSharemasterinfo->fetch(PDO::FETCH_ASSOC);
			$arrayForm = array();
			$arrayForm["ID_FORMAT_REQ_DOC"] = null;
			$arrayForm["DOCUMENTTYPE_CODE"] = "RRSN";
			$arrayForm["FORM_LABEL"] = "ทุนเรือนหุ้นรายเดือน";
			$arrayForm["FORM_KEY"] = "PERIOD_SHARE_AMT";
			$arrayForm["FORM_TYPE"] = "remark";
			$arrayForm["COLSPAN"] = "24";
			$arrayForm["FULLWIDTH"] = false;
			$arrayForm["REQUIRED"] = false;
			$arrayForm["PLACEHOLDER"] = "ทุนเรือนหุ้นรายเดือน : ".number_format($rowMastershare["PERIOD_SHARE_AMT"],2)." บาท\t\t".
						"ทุนเรือนหุ้นสะสม : ".number_format($rowMastershare["SHARE_AMT"],2)." บาท";
			$arrayForm["DEFAULT_VALUE"] = $rowMastershare["PERIOD_SHARE_AMT"];
			$arrayForm["FORM_OPTION"] = null;
			$arrayForm["MAXWIDTH"] = null;
			$arrayForm["GROUP_ID"] = null;
			$arrayForm["MAX_VALUE"] = '#3f51b5';
			$arrayForm["MIN_VALUE"] = '#3f51b522';
			$arrayGrpForm[] = $arrayForm;
			$arrayForm = array();
			$arrayForm["ID_FORMAT_REQ_DOC"] = null;
			$arrayForm["DOCUMENTTYPE_CODE"] = "RRSN";
			$arrayForm["FORM_LABEL"] = "ทุนเรือนหุ้นสะสม";
			$arrayForm["FORM_KEY"] = "SHARE_STK";
			$arrayForm["FORM_TYPE"] = "remark";
			$arrayForm["COLSPAN"] = "24";
			$arrayForm["FULLWIDTH"] = false;
			$arrayForm["REQUIRED"] = false;
			$arrayForm["PLACEHOLDER"] = null;
			$arrayForm["DEFAULT_VALUE"] = $rowMastershare["SHARE_AMT"];
			$arrayForm["FORM_OPTION"] = null;
			$arrayForm["MAXWIDTH"] = null;
			$arrayForm["GROUP_ID"] = null;
			$arrayForm["MAX_VALUE"] = '#3f51b5';
			$arrayForm["MIN_VALUE"] = '#3f51b522';
			$arrayGrpForm[] = $arrayForm;
			
			//หนี้เดิม
			$loan_prn_1 = 0;
			$loan_pay_1 = 0;
			$loan_int_1 = 0;
			$loan_prn_2 = 0;
			$loan_pay_2 = 0;
			$loan_int_2 = 0;
			$getOldContract = $conmssql->prepare("SELECT LM.PRINCIPAL_BALANCE,LT.LOANTYPE_DESC,LM.LOANCONTRACT_NO,LM.LAST_PERIODPAY, lt.LOANGROUP_CODE, lm.LOANTYPE_CODE, lm.PERIOD_PAYMENT
							FROM lncontmaster lm LEFT JOIN lnloantype lt ON lm.loantype_code = lt.loantype_code 
							WHERE lm.member_no = :member_no and lm.contract_status > 0 and lm.contract_status <> 8");
			$getOldContract->execute([
				':member_no' => $member_no
			]);

			while($rowOldContract = $getOldContract->fetch(PDO::FETCH_ASSOC)){
				if($rowOldContract["LOANGROUP_CODE"] == '01'){
					$loan_prn_1 += $rowOldContract["PRINCIPAL_BALANCE"];
					$loan_pay_1 += $rowOldContract["PERIOD_PAYMENT"];
					$loan_int_1 += $cal_loan->calculateInterestRevert($rowOldContract["LOANCONTRACT_NO"]);
				}else if($rowOldContract["LOANGROUP_CODE"] == '02'){
					$loan_prn_2 += $rowOldContract["PRINCIPAL_BALANCE"];
					$loan_pay_2 += $rowOldContract["PERIOD_PAYMENT"];
					$loan_int_2 += $cal_loan->calculateInterestRevert($rowOldContract["LOANCONTRACT_NO"]);
				}
			}
			$arrayForm = array();
			$arrayForm["ID_FORMAT_REQ_DOC"] = null;
			$arrayForm["DOCUMENTTYPE_CODE"] = "RRSN";
			$arrayForm["FORM_LABEL"] = "เงินงวดฉุกเฉิน";
			$arrayForm["FORM_KEY"] = "PERIOD_PAYMENT_01";
			$arrayForm["FORM_TYPE"] = "remark";
			$arrayForm["COLSPAN"] = "24";
			$arrayForm["FULLWIDTH"] = false;
			$arrayForm["REQUIRED"] = false;
			$arrayForm["PLACEHOLDER"] = "เงินงวดฉุกเฉิน : ".number_format($loan_pay_1,2)." บาท\t\t".
						"เงินกู้ฉุกเฉินคงเหลือ : ".number_format($loan_prn_1,2)." บาท";
			$arrayForm["DEFAULT_VALUE"] = $loan_pay_1;
			$arrayForm["FORM_OPTION"] = null;
			$arrayForm["MAXWIDTH"] = null;
			$arrayForm["GROUP_ID"] = null;
			$arrayForm["MAX_VALUE"] = '#3f51b5';
			$arrayForm["MIN_VALUE"] = '#3f51b522';
			$arrayGrpForm[] = $arrayForm;
			$arrayForm = array();
			$arrayForm["ID_FORMAT_REQ_DOC"] = null;
			$arrayForm["DOCUMENTTYPE_CODE"] = "RRSN";
			$arrayForm["FORM_LABEL"] = "เงินกู้ฉุกเฉินคงเหลือ";
			$arrayForm["FORM_KEY"] = "LOAN_BALANCE_01";
			$arrayForm["FORM_TYPE"] = "remark";
			$arrayForm["COLSPAN"] = "24";
			$arrayForm["FULLWIDTH"] = false;
			$arrayForm["REQUIRED"] = false;
			$arrayForm["PLACEHOLDER"] = null;
			$arrayForm["DEFAULT_VALUE"] = $loan_prn_1;
			$arrayForm["FORM_OPTION"] = null;
			$arrayForm["MAXWIDTH"] = null;
			$arrayForm["GROUP_ID"] = null;
			$arrayForm["MAX_VALUE"] = '#3f51b5';
			$arrayForm["MIN_VALUE"] = '#3f51b522';
			$arrayGrpForm[] = $arrayForm;
			$arrayForm = array();
			$arrayForm["ID_FORMAT_REQ_DOC"] = null;
			$arrayForm["DOCUMENTTYPE_CODE"] = "RRSN";
			$arrayForm["FORM_LABEL"] = "เงินงวดสามัญ";
			$arrayForm["FORM_KEY"] = "PERIOD_PAYMENT_02";
			$arrayForm["FORM_TYPE"] = "remark";
			$arrayForm["COLSPAN"] = "24";
			$arrayForm["FULLWIDTH"] = false;
			$arrayForm["REQUIRED"] = false;
			$arrayForm["PLACEHOLDER"] = "เงินงวดสามัญ : ".number_format($loan_pay_2,2)." บาท\t\t".
						"เงินกู้สามัญคงเหลือ : ".number_format($loan_prn_2,2)." บาท";
			$arrayForm["DEFAULT_VALUE"] = $loan_pay_2;
			$arrayForm["FORM_OPTION"] = null;
			$arrayForm["MAXWIDTH"] = null;
			$arrayForm["GROUP_ID"] = null;
			$arrayForm["MAX_VALUE"] = '#3f51b5';
			$arrayForm["MIN_VALUE"] = '#3f51b522';
			$arrayGrpForm[] = $arrayForm;
			$arrayForm = array();
			$arrayForm["ID_FORMAT_REQ_DOC"] = null;
			$arrayForm["DOCUMENTTYPE_CODE"] = "RRSN";
			$arrayForm["FORM_LABEL"] = "เงินกู้สามัญคงเหลือ";
			$arrayForm["FORM_KEY"] = "LOAN_BALANCE_02";
			$arrayForm["FORM_TYPE"] = "remark";
			$arrayForm["COLSPAN"] = "24";
			$arrayForm["FULLWIDTH"] = false;
			$arrayForm["REQUIRED"] = false;
			$arrayForm["PLACEHOLDER"] = null;
			$arrayForm["DEFAULT_VALUE"] = $loan_prn_2;
			$arrayForm["FORM_OPTION"] = null;
			$arrayForm["MAXWIDTH"] = null;
			$arrayForm["GROUP_ID"] = null;
			$arrayForm["MAX_VALUE"] = '#3f51b5';
			$arrayForm["MIN_VALUE"] = '#3f51b522';
			$arrayGrpForm[] = $arrayForm;
		}
		//เงื่อนไขลาออก
		if($dataComing["documenttype_code"] == "RRSN"){
			$getSharemasterinfo = $conmssql->prepare("SELECT (sharestk_amt * 10) as SHARE_AMT, (periodshare_amt * 10) as PERIOD_SHARE_AMT
												FROM shsharemaster WHERE member_no = :member_no");
			$getSharemasterinfo->execute([':member_no' => $member_no]);
			$rowMastershare = $getSharemasterinfo->fetch(PDO::FETCH_ASSOC);
			$arrayForm = array();
			$arrayForm["ID_FORMAT_REQ_DOC"] = null;
			$arrayForm["DOCUMENTTYPE_CODE"] = "RRSN";
			$arrayForm["FORM_LABEL"] = "ทุนเรือนหุ้นทั้งหมด";
			$arrayForm["FORM_KEY"] = "SHARE_STK";
			$arrayForm["FORM_TYPE"] = "remark";
			$arrayForm["COLSPAN"] = "24";
			$arrayForm["FULLWIDTH"] = false;
			$arrayForm["REQUIRED"] = false;
			$arrayForm["PLACEHOLDER"] = "ทุนเรือนหุ้นทั้งหมด ".number_format($rowMastershare["SHARE_AMT"],2)." บาท";
			$arrayForm["DEFAULT_VALUE"] = $rowMastershare["SHARE_AMT"];
			$arrayForm["FORM_OPTION"] = null;
			$arrayForm["MAXWIDTH"] = null;
			$arrayForm["GROUP_ID"] = "1";
			$arrayForm["MAX_VALUE"] = '#3f51b5';
			$arrayForm["MIN_VALUE"] = '#3f51b522';
			$arrayGrpForm[] = $arrayForm;
			
			//หนี้เดิม
			$loan_prn_1 = 0;
			$loan_int_1 = 0;
			$loan_prn_2 = 0;
			$loan_int_2 = 0;
			$getOldContract = $conmssql->prepare("SELECT LM.PRINCIPAL_BALANCE,LT.LOANTYPE_DESC,LM.LOANCONTRACT_NO,LM.LAST_PERIODPAY, lt.LOANGROUP_CODE, lm.LOANTYPE_CODE, lm.PERIOD_PAYMENT
							FROM lncontmaster lm LEFT JOIN lnloantype lt ON lm.loantype_code = lt.loantype_code 
							WHERE lm.member_no = :member_no and lm.contract_status > 0 and lm.contract_status <> 8");
			$getOldContract->execute([
				':member_no' => $member_no
			]);

			while($rowOldContract = $getOldContract->fetch(PDO::FETCH_ASSOC)){
				if($rowOldContract["LOANGROUP_CODE"] == '01'){
					$loan_prn_1 += $rowOldContract["PRINCIPAL_BALANCE"];
					$loan_int_1 += $cal_loan->calculateInterestAddMonth($rowOldContract["LOANCONTRACT_NO"]);
				}else if($rowOldContract["LOANGROUP_CODE"] == '02'){
					$loan_prn_2 += $rowOldContract["PRINCIPAL_BALANCE"];
					$loan_int_2 += $cal_loan->calculateInterestAddMonth($rowOldContract["LOANCONTRACT_NO"]);
				}
			}
			$receive_net = $rowMastershare["SHARE_AMT"]-($loan_prn_2 + $loan_int_2+$loan_prn_1 + $loan_int_1);
			$arrayForm = array();
			$arrayForm["ID_FORMAT_REQ_DOC"] = null;
			$arrayForm["DOCUMENTTYPE_CODE"] = "RRSN";
			$arrayForm["FORM_LABEL"] = "เงินสามัญคงเหลือ";
			$arrayForm["FORM_KEY"] = "LOAN_GROUP_BAL_2";
			$arrayForm["FORM_TYPE"] = "remark";
			$arrayForm["COLSPAN"] = "24";
			$arrayForm["FULLWIDTH"] = false;
			$arrayForm["REQUIRED"] = false;
			$arrayForm["PLACEHOLDER"] = "- เงินกู้สามัญคงเหลือ : ".number_format($loan_prn_2,2)." บาท  \nดอกเบี้ย : ".number_format($loan_int_2,2)." บาท  \nรวมยอด : ".number_format($loan_prn_2 + $loan_int_2,2)." บาท\n\n".
			"- เงินกู้ฉุกเฉินคงเหลือ : ".number_format($loan_prn_1,2)." บาท  \nดอกเบี้ย : ".number_format($loan_int_1,2)." บาท   \nรวมยอด : ".number_format($loan_prn_1 + $loan_int_1,2)." บาท ";
			$arrayForm["DEFAULT_VALUE"] = $loan_prn_2;
			$arrayForm["FORM_OPTION"] = null;
			$arrayForm["MAXWIDTH"] = null;
			$arrayForm["GROUP_ID"] = "1";
			$arrayForm["MAX_VALUE"] = '#5d4943';
			$arrayForm["MIN_VALUE"] = '#5d494322';
			$arrayGrpForm[] = $arrayForm;
			$arrayForm = array();
			$arrayForm["FORM_LABEL"] = "ดอกเบี้ยเงินกู้สามัญ";
			$arrayForm["GROUP_ID"] = "1";
			$arrayForm["FORM_KEY"] = "LOAN_GROUP_INT_2";
			$arrayForm["FORM_TYPE"] = "remark";
			$arrayForm["DEFAULT_VALUE"] = $loan_int_2;
			$arrayGrpForm[] = $arrayForm;
			$arrayForm = array();
			$arrayForm["ID_FORMAT_REQ_DOC"] = null;
			$arrayForm["DOCUMENTTYPE_CODE"] = "RRSN";
			$arrayForm["FORM_LABEL"] = "เงินกู้ฉุกเฉินคงเหลือ";
			$arrayForm["FORM_KEY"] = "LOAN_GROUP_BAL_1";
			$arrayForm["FORM_TYPE"] = "remark";
			$arrayForm["COLSPAN"] = "24";
			$arrayForm["FULLWIDTH"] = false;
			$arrayForm["REQUIRED"] = false;
			$arrayForm["PLACEHOLDER"] = null;
			$arrayForm["DEFAULT_VALUE"] = $loan_prn_1;
			$arrayForm["FORM_OPTION"] = null;
			$arrayForm["MAXWIDTH"] = null;
			$arrayForm["GROUP_ID"] = "1";
			$arrayForm["MAX_VALUE"] = '#5d4943';
			$arrayForm["MIN_VALUE"] = '#5d494322';
			$arrayGrpForm[] = $arrayForm;
			$arrayForm = array();
			$arrayForm["FORM_LABEL"] = "ดอกเบี้ยเงินกู้ฉุกเฉิน";
			$arrayForm["GROUP_ID"] = "1";
			$arrayForm["FORM_KEY"] = "LOAN_GROUP_INT_1";
			$arrayForm["FORM_TYPE"] = "remark";
			$arrayForm["DEFAULT_VALUE"] = $loan_int_1;
			$arrayGrpForm[] = $arrayForm;
			$arrayForm = array();
			$arrayForm["ID_FORMAT_REQ_DOC"] = null;
			$arrayForm["DOCUMENTTYPE_CODE"] = "RRSN";
			$arrayForm["FORM_LABEL"] = "จำนวนเงินที่จะได้รับ";
			$arrayForm["FORM_KEY"] = "RECEIVE_NET";
			$arrayForm["FORM_TYPE"] = "remark";
			$arrayForm["COLSPAN"] = "24";
			$arrayForm["FULLWIDTH"] = false;
			$arrayForm["REQUIRED"] = false;
			$arrayForm["PLACEHOLDER"] = "จำนวนเงินที่จะได้รับ : ".number_format($receive_net,2)." บาท";
			$arrayForm["DEFAULT_VALUE"] = $receive_net;
			$arrayForm["FORM_OPTION"] = null;
			$arrayForm["MAXWIDTH"] = null;
			$arrayForm["GROUP_ID"] = "1";
			$arrayForm["MAX_VALUE"] = '#5d4943';
			$arrayForm["MIN_VALUE"] = '#5d494322';
			$arrayGrpForm[] = $arrayForm;
		}
		
			while($rowForm = $getFormatForm->fetch(PDO::FETCH_ASSOC)){
				if($dataComing["documenttype_code"] == "CSHR" && $rowForm["form_key"] == "SHARE_PERIOD_PAYMENT"){
					$getSharemasterinfo = $conmssql->prepare("SELECT (periodshare_amt * 10) as PERIOD_SHARE_AMT
														FROM shsharemaster WHERE member_no = :member_no");
					$getSharemasterinfo->execute([':member_no' => $member_no]);
					$rowMastershare = $getSharemasterinfo->fetch(PDO::FETCH_ASSOC);
					$arrayForm = array();
					$arrayForm["ID_FORMAT_REQ_DOC"] = null;
					$arrayForm["DOCUMENTTYPE_CODE"] = "CSHR";
					$arrayForm["FORM_LABEL"] = "ค่าส่งหุ้นรายเดือนเดิม";
					$arrayForm["FORM_KEY"] = "OLD_SHR_PAYMENT";
					$arrayForm["FORM_TYPE"] = "remark";
					$arrayForm["COLSPAN"] = "24";
					$arrayForm["FULLWIDTH"] = false;
					$arrayForm["REQUIRED"] = false;
					$arrayForm["PLACEHOLDER"] = "ปัจจุบันส่งค่าหุ้นรายเดือน เดือนละ ".number_format($rowMastershare["PERIOD_SHARE_AMT"], 2)." บาท";
					$arrayForm["DEFAULT_VALUE"] = $rowMastershare["PERIOD_SHARE_AMT"];
					$arrayForm["FORM_OPTION"] = null;
					$arrayForm["MAXWIDTH"] = null;
					$arrayForm["GROUP_ID"] = '2';
					$arrayForm["MAX_VALUE"] = 'white';
					$arrayForm["MIN_VALUE"] = 'cornflowerblue';
					$arrayGrpForm[] = $arrayForm;
					
					//คำนวณเงินเดือนคงเหลือ
					$getMemberIno = $conmssql->prepare("SELECT SALARY_AMOUNT,SALARY_ID FROM mbmembmaster WHERE member_no = :member_no");
					$getMemberIno->execute([':member_no' => $member_no]);
					$rowMember = $getMemberIno->fetch(PDO::FETCH_ASSOC);
					$salary = $rowMember["SALARY_AMOUNT"] ?? 0;
					$getOldContract = $conmssql->prepare("SELECT LM.PRINCIPAL_BALANCE,LT.LOANTYPE_DESC,LM.LOANCONTRACT_NO,LM.LAST_PERIODPAY, lt.LOANGROUP_CODE, lm.LOANTYPE_CODE, lm.PERIOD_PAYMENT
						FROM lncontmaster lm LEFT JOIN lnloantype lt ON lm.loantype_code = lt.loantype_code 
						WHERE lm.member_no = :member_no and lm.contract_status > 0 and lm.contract_status <> 8");
					$getOldContract->execute([
						':member_no' => $member_no
					]);

					while($rowOldContract = $getOldContract->fetch(PDO::FETCH_ASSOC)){
						$sum_old_payment += $rowOldContract["PERIOD_PAYMENT"];
					}
					$getMthOther = $conmssql->prepare("SELECT SUM(mthother_amt) as MTHOTHER_AMT FROM mbmembmthother WHERE member_no = :member_no and sign_flag = '-1'");
					$getMthOther->execute([':member_no' => $member_no]);
					$rowOther = $getMthOther->fetch(PDO::FETCH_ASSOC);
					$mthother_amt = $rowOther["MTHOTHER_AMT"] ?? 0;
					$getSettlement = $conmysql->prepare("SELECT settlement_amt, salary FROM gcmembsettlement WHERE is_use = '1' AND emp_no = :emp_no AND MONTH(month_period) = MONTH(:month_period) AND YEAR(month_period) = YEAR(:month_period)");
					$getSettlement->execute([
						':emp_no' => $rowMember["SALARY_ID"],
						':month_period' => $dataComing["form_value_root_"]["EFFECT_MONTH"]["VALUE"],
					]);
					$rowSettlement = $getSettlement->fetch(PDO::FETCH_ASSOC);
					$other_amt = $rowSettlement["settlement_amt"] ?? $mthother_amt;
					$sum_old_payment += $other_amt;
					$salary_balance = $salary - $sum_old_payment;
					$getSharemasterinfo = $conmssql->prepare("SELECT (periodshare_amt * 10) as PERIOD_SHARE_AMT
														FROM shsharemaster WHERE member_no = :member_no");
					$getSharemasterinfo->execute([':member_no' => $member_no]);
					$rowMastershare = $getSharemasterinfo->fetch(PDO::FETCH_ASSOC);
					$arrayForm = array();
					$arrayForm["ID_FORMAT_REQ_DOC"] = null;
					$arrayForm["DOCUMENTTYPE_CODE"] = "CSHR";
					$arrayForm["FORM_LABEL"] = "จำนวนที่สามารถเพิ่มได้สูงสุด";
					$arrayForm["FORM_KEY"] = "MAX_SHR_PAYMENT";
					$arrayForm["FORM_TYPE"] = "remark";
					$arrayForm["COLSPAN"] = "24";
					$arrayForm["FULLWIDTH"] = false;
					$arrayForm["REQUIRED"] = false;
					$arrayForm["PLACEHOLDER"] = "จำนวนที่สามารถเพิ่มได้สูงสุด : ".number_format($salary_balance > 0 ? $salary_balance : 0, 2)." บาท";
					$arrayForm["DEFAULT_VALUE"] = $salary_balance;
					$arrayForm["FORM_OPTION"] = null;
					$arrayForm["MAXWIDTH"] = null;
					$arrayForm["GROUP_ID"] = '2';
					$arrayForm["MAX_VALUE"] = '#f44336';
					$arrayForm["MIN_VALUE"] = '#fff0f0';
					$arrayGrpForm[] = $arrayForm;
				}
				
				//ลาออกหากหนี้มากกว่าทุนเรือนหุ้นให้เลือกว่าลาออกจากสหกรณ์หรือบริษัท
				$arrayForm = array();
				if($dataComing["documenttype_code"] == "RRSN" && $rowForm["form_key"] == "RESIGN_FROM_OPTION"){
					if($dataComing["documenttype_code"] == "RRSN" && $receive_net < 0){
						$arrayForm = array();
						$arrayForm["ID_FORMAT_REQ_DOC"] = null;
						$arrayForm["DOCUMENTTYPE_CODE"] = "RRSN";
						$arrayForm["FORM_LABEL"] = "";
						$arrayForm["FORM_KEY"] = "RESIGN_REMARK";
						$arrayForm["FORM_TYPE"] = "remark";
						$arrayForm["COLSPAN"] = "24";
						$arrayForm["FULLWIDTH"] = false;
						$arrayForm["REQUIRED"] = false;
						$arrayForm["PLACEHOLDER"] = "กรณีหนี้มากกว่าทุนเรือนหุ้น ไม่สามารถลาออกจากสหกรณ์ฯได้ กรุณาชำระหนี้หรือติดต่อเจ้าหน้าที่ !";
						$arrayForm["DEFAULT_VALUE"] = null;
						$arrayForm["FORM_OPTION"] = null;
						$arrayForm["MAXWIDTH"] = null;
						$arrayForm["GROUP_ID"] = "2";
						$arrayForm["MAX_VALUE"] = '#e91e1e';
						$arrayForm["MIN_VALUE"] = '#e91e1e33';
						$arrayGrpForm[] = $arrayForm;
					}
					
					//อัปโหลดสลิปชำระหนี้ตอนลาออก
					$arrayFormLoan = array();
					$arrayFormLoan["ID_FORMAT_REQ_DOC"] = null;
					$arrayFormLoan["DOCUMENTTYPE_CODE"] = "RRSN";
					$arrayFormLoan["FORM_LABEL"] = "ชำระหนี้";
					$arrayFormLoan["FORM_KEY"] = "LOAN_PAYMENT";
					$arrayFormLoan["FORM_TYPE"] = "loanpayment";
					$arrayFormLoan["COLSPAN"] = "24";
					$arrayFormLoan["FULLWIDTH"] = false;
					$arrayFormLoan["REQUIRED"] = false;
					$arrayFormLoan["PLACEHOLDER"] = null;
					$arrayFormLoan["DEFAULT_VALUE"] = null;
					$arrayFormLoan["FORM_OPTION"] =  null;
					$arrayFormLoan["MAXWIDTH"] = null;
					$arrayFormLoan["GROUP_ID"] = "2";
					$arrayFormLoan["MAX_VALUE"] = null;
					$arrayFormLoan["MIN_VALUE"] = null;
					$arrayGrpForm[] = $arrayFormLoan;
					
					if($receive_net < 0){
						$arrayForm["ID_FORMAT_REQ_DOC"] = $rowForm["id_format_req_doc"];
						$arrayForm["DOCUMENTTYPE_CODE"] = $rowForm["documenttype_code"];
						$arrayForm["FORM_LABEL"] = $rowForm["form_label"];
						$arrayForm["FORM_KEY"] = $rowForm["form_key"];
						$arrayForm["FORM_TYPE"] = $rowForm["form_type"];
						$arrayForm["COLSPAN"] = $rowForm["colspan"];
						$arrayForm["FULLWIDTH"] = $rowForm["fullwidth"] == 1 ? true : false;
						$arrayForm["REQUIRED"] = $rowForm["required"] == 1 ? true : false;
						$arrayForm["PLACEHOLDER"] = $rowForm["placeholder"];
						$arrayForm["DEFAULT_VALUE"] = $rowForm["default_value"];
						$arrayForm["FORM_OPTION"] = $rowForm["form_option"];
						$arrayForm["MAXWIDTH"] = $rowForm["maxwidth"];
						$arrayForm["GROUP_ID"] = $rowForm["group_id"];
						$arrayForm["MAX_VALUE"] = $rowForm["max_value"];
						$arrayForm["MIN_VALUE"] = $rowForm["min_value"];
						$arrayForm["MASK"] = $rowForm["mask"];
					}
				}else{
					$arrayForm["ID_FORMAT_REQ_DOC"] = $rowForm["id_format_req_doc"];
					$arrayForm["DOCUMENTTYPE_CODE"] = $rowForm["documenttype_code"];
					$arrayForm["FORM_LABEL"] = $rowForm["form_label"];
					$arrayForm["FORM_KEY"] = $rowForm["form_key"];
					$arrayForm["FORM_TYPE"] = $rowForm["form_type"];
					$arrayForm["COLSPAN"] = $rowForm["colspan"];
					$arrayForm["FULLWIDTH"] = $rowForm["fullwidth"] == 1 ? true : false;
					$arrayForm["REQUIRED"] = $rowForm["required"] == 1 ? true : false;
					$arrayForm["PLACEHOLDER"] = $rowForm["placeholder"];
					$arrayForm["DEFAULT_VALUE"] = $rowForm["default_value"];
					$arrayForm["FORM_OPTION"] = $rowForm["form_option"];
					$arrayForm["MAXWIDTH"] = $rowForm["maxwidth"];
					$arrayForm["GROUP_ID"] = $rowForm["group_id"];
					$arrayForm["MAX_VALUE"] = $rowForm["max_value"];
					$arrayForm["MIN_VALUE"] = $rowForm["min_value"];
					$arrayForm["MASK"] = $rowForm["mask"];
				}
				
				//lock effect month
				if($dataComing["documenttype_code"] == "CSHR" && $rowForm["form_key"] == "EFFECT_MONTH"){
					$day = date('d');
					if($day > 7){
						$dateNow = new DateTime('first day of this month');
						$dateNow->modify('+1 month');
						$dateNow = $dateNow->format('Y-m-d');
						$arrayForm["DEFAULT_VALUE"] = $dateNow;
					}else{
						$dateNow = new DateTime('first day of this month');
						$dateNow = $dateNow->format('Y-m-d');
						$arrayForm["DEFAULT_VALUE"] = $dateNow;
					}
					$arrayForm["FORM_TYPE"] = "remark";
					$date_arr = explode(" ",$lib->convertdate($dateNow,"D M Y"));
					$arrayForm["PLACEHOLDER"] = $rowForm["form_label"].' '.$date_arr[1].' '.$date_arr[2];
					$arrayForm["MAX_VALUE"] = '#ffffff';
					$arrayForm["MIN_VALUE"] = '#dc5349';
				}
				if($dataComing["documenttype_code"] == "RRSN" && $rowForm["form_key"] == "EFFECT_DATE"){
					$day = date('d');
					if($day > 7){
						$dateNow = new DateTime('first day of this month');
						$dateNow->modify('+1 month');
						$dateNow = $dateNow->format('Y-m-d');
						$arrayForm["DEFAULT_VALUE"] = $dateNow;
					}else{
						$dateNow = new DateTime('first day of this month');
						$dateNow = $dateNow->format('Y-m-d');
						$arrayForm["DEFAULT_VALUE"] = $dateNow;
					}
					$arrayForm["FORM_TYPE"] = "remark";
					$date_arr = explode(" ",$lib->convertdate($dateNow,"D M Y"));
					$arrayForm["PLACEHOLDER"] = $rowForm["form_label"].' '.$date_arr[1].' '.$date_arr[2];
					$arrayForm["MAX_VALUE"] = '#ffffff';
					$arrayForm["MIN_VALUE"] = '#dc5349';
				}
				$arrayGrpForm[] = $arrayForm;
				
				//ลาออก
				if($dataComing["documenttype_code"] == "RRSN" && $rowForm["form_key"] == "RESIGN_OPTION"){
					$arrayForm = array();
					$arrayForm["ID_FORMAT_REQ_DOC"] = null;
					$arrayForm["DOCUMENTTYPE_CODE"] = "RRSN";
					$arrayForm["FORM_LABEL"] = "แจ้งเตือนออกก่อน";
					$arrayForm["FORM_KEY"] = "RESIGN_REMARK";
					$arrayForm["FORM_TYPE"] = "remark";
					$arrayForm["COLSPAN"] = "24";
					$arrayForm["FULLWIDTH"] = false;
					$arrayForm["REQUIRED"] = false;
					$arrayForm["PLACEHOLDER"] = "(กรณีต้องการลาออกก่อนครบกำหนดระยะเวลา 5 ปี ให้แจ้งความจำนงกับทางสหกรณ์ฯ ล่วงหน้า 6 เดือน)";
					$arrayForm["DEFAULT_VALUE"] = null;
					$arrayForm["FORM_OPTION"] = null;
					$arrayForm["MAXWIDTH"] = null;
					$arrayForm["GROUP_ID"] = "2";
					$arrayForm["MAX_VALUE"] = '#e91e1e';
					$arrayForm["MIN_VALUE"] = '#e91e1e33';
					$arrayGrpForm[] = $arrayForm;

				}
			}
		
		$arrayResult['FORM_REQDOCUMENT'] = $arrayGrpForm;
		$arrayResult['FORM_GROUP'] = $arrayGrpData;
		$arrayResult['REQ_DOCUMENT'] = $arrayDocGrp;
		
		$arrayResult['RESULT'] = TRUE;
		require_once('../../include/exit_footer.php');
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