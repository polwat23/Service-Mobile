<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','paydeptlist')){
		$arrGrp = array();
		$arrayType = array();
		$arrayExecute = array();
		if(isset($dataComing["start_date"]) && $dataComing["start_date"] != ""){
			$arrayExecute["start_date"] = $dataComing["start_date"];
		}
		if(isset($dataComing["end_date"]) && $dataComing["end_date"] != ""){
			$arrayExecute["end_date"] = $dataComing["end_date"];
		}
		if(isset($dataComing["ref_no"]) && $dataComing["ref_no"] != ""){
			$arrayExecute["ref_no"] = $dataComing["ref_no"];
		}
		if(isset($dataComing["member_no"]) && $dataComing["member_no"] != ""){
			$arrayExecute["member_no"] = strtolower($lib->mb_str_pad($dataComing["member_no"]));
		}
		if($dataComing["date_type"] == 'year'){
			$getRePayLoan = $conmysql->prepare("SELECT pl.ref_no, pl.from_account, pl.loancontract_no, pl.source_type, pl.amount, pl.fee_amt, 
											pl.penalty_amt, pl.principal, pl.interest, pl.interest_return, pl.interest_arrear, pl.bfinterest_return, 
											pl.bfinterest_arrear, pl.bank_code, pl.operate_date, pl.update_date, pl.result_transaction, pl.cancel_date, 
											pl.member_no, pl.id_userlogin, pl.app_version, pl.is_offset, pl.bfkeeping, cb.bank_short_name 
											FROM gcrepayloan pl
											LEFT JOIN csbankdisplay cb ON pl.bank_code = cb.bank_code
										WHERE 1=1 ".(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? 
										"and date_format(operate_date,'%Y') >= date_format(:start_date,'%Y')" : null)."
										".(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? 
										"and date_format(operate_date,'%Y') <= date_format(:end_date,'%Y')" : null)." 
										".(isset($dataComing["ref_no"]) && $dataComing["ref_no"] != "" ? 
										"and ref_no <= :ref_no" : null)." 
										".(isset($dataComing["member_no"]) && $dataComing["member_no"] != "" ? 
										"and member_no = :member_no" : null)."");
		}else if($dataComing["date_type"] == 'month'){
			$getRePayLoan = $conmysql->prepare("SELECT pl.ref_no, pl.from_account, pl.loancontract_no, pl.source_type, pl.amount, pl.fee_amt, 
											pl.penalty_amt, pl.principal, pl.interest, pl.interest_return, pl.interest_arrear, pl.bfinterest_return, 
											pl.bfinterest_arrear, pl.bank_code, pl.operate_date, pl.update_date, pl.result_transaction, pl.cancel_date, 
											pl.member_no, pl.id_userlogin, pl.app_version, pl.is_offset, pl.bfkeeping, cb.bank_short_name 
											FROM gcrepayloan pl
											LEFT JOIN csbankdisplay cb ON pl.bank_code = cb.bank_code
										WHERE 1=1 ".(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? 
										"and date_format(operate_date,'%Y-%m') >= date_format(:start_date,'%Y-%m')" : null)."
										".(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? 
										"and date_format(operate_date,'%Y-%m') <= date_format(:end_date,'%Y-%m')" : null)." 
										".(isset($dataComing["ref_no"]) && $dataComing["ref_no"] != "" ? 
										"and ref_no <= :ref_no" : null)." 
										".(isset($dataComing["member_no"]) && $dataComing["member_no"] != "" ? 
										"and member_no = :member_no" : null)."");
		}else if($dataComing["date_type"] == 'day'){
			$getRePayLoan = $conmysql->prepare("SELECT pl.ref_no, pl.from_account, pl.loancontract_no, pl.source_type, pl.amount, pl.fee_amt, 
											pl.penalty_amt, pl.principal, pl.interest, pl.interest_return, pl.interest_arrear, pl.bfinterest_return, 
											pl.bfinterest_arrear, pl.bank_code, pl.operate_date, pl.update_date, pl.result_transaction, pl.cancel_date, 
											pl.member_no, pl.id_userlogin, pl.app_version, pl.is_offset, pl.bfkeeping, cb.bank_short_name 
											FROM gcrepayloan pl
											LEFT JOIN csbankdisplay cb ON pl.bank_code = cb.bank_code
										WHERE 1=1 ".(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? 
										"and date_format(operate_date,'%Y-%m-%d') >= :start_date" : null)."
										".(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? 
										"and date_format(operate_date,'%Y-%m-%d') <= :end_date" : null)." 
										".(isset($dataComing["ref_no"]) && $dataComing["ref_no"] != "" ? 
										"and ref_no <= :ref_no" : null)." 
										".(isset($dataComing["member_no"]) && $dataComing["member_no"] != "" ? 
										"and member_no = :member_no" : null)."
										".($dataComing["int_return_flag"] == "1" ? 
										"and interest_return > 0" : ($dataComing["int_return_flag"] == "0" ? "and interest_return = 0" : null))."");
		}
		$getRePayLoan->execute($arrayExecute);
		while($rowRePayLoan = $getRePayLoan->fetch(PDO::FETCH_ASSOC)){
			$arrRePay = array();
			$arrRePay["REF_NO"] = $rowRePayLoan["ref_no"];
			$arrRePay["FROM_ACCOUNT"] = $rowRePayLoan["from_account"];
			$arrRePay["LOANCONTRACT_NO"] = $rowRePayLoan["loancontract_no"];
			$arrRePay["SOURCE_TYPE"] = $rowRePayLoan["source_type"] == '1' ? "บัญชีภายในสหกรณ์" : 'บัญชีธนาคาร';
			$arrRePay["AMOUNT"] = $rowRePayLoan["amount"];
			$arrRePay["FEE_AMT"] = $rowRePayLoan["fee_amt"];
			$arrRePay["PENALTY_AMT"] = $rowRePayLoan["penalty_amt"];
			$arrRePay["PRINCIPAL"] = $rowRePayLoan["principal"];
			$arrRePay["INTEREST"] = $rowRePayLoan["interest"];
			$arrRePay["INTEREST_RETURN"] = $rowRePayLoan["interest_return"];
			if($rowRePayLoan["interest_return"]){
				$arrRePay["INTEREST_RETURN_PDF"] = true;
			}else{
				$arrRePay["INTEREST_RETURN_PDF"] = false;
			}
			$arrRePay["INTEREST_ARREAR"] = $rowRePayLoan["interest_arrear"];
			$arrRePay["BFINTEREST_RETURN"] = $rowRePayLoan["bfinterest_return"];
			$arrRePay["BFINTEREST_ARREAR"] = $rowRePayLoan["bfinterest_arrear"];
			$arrRePay["BANK_CODE"] = $rowRePayLoan["bank_short_name"];
			$arrRePay["OPERATE_DATE"] = $rowRePayLoan["operate_date"];
			$arrRePay["UPDATE_DATE"] = $rowRePayLoan["update_date"];
			if($rowRePayLoan["result_transaction"] == '1'){
				$arrRePay["RESULT_TRANSACTION"] = "ปกติ";
			}else if($rowRePayLoan["result_transaction"] == '-9'){
				$arrRePay["RESULT_TRANSACTION"] = "ทำรายการไม่สำเร็จ";
			}else if($rowRePayLoan["result_transaction"] == '-8'){
				$arrRePay["RESULT_TRANSACTION"] = "ปฏิเสธรายการเพื่อทำรายการใหม่";
			}else {
				$arrRePay["RESULT_TRANSACTION"] = $rowRePayLoan["result_transaction"];
			}
			$arrRePay["CANCEL_DATE"] = $rowRePayLoan["cancel_date"];
			$arrRePay["MEMBER_NO"] = $rowRePayLoan["member_no"];
			$arrRePay["ID_USERLOGIN"] = $rowRePayLoan["id_userlogin"];
			$arrRePay["APP_VERSION"] = $rowRePayLoan["app_version"];
			$arrRePay["IS_OFFSET"] = $rowRePayLoan["is_offset"] == '1' ? " ไม่หักกลบ" : "หักกลบสัญญา";
			$arrRePay["BFKEEPING"] = $rowRePayLoan["bfkeeping"];
			
			$arrGrp[] = $arrRePay;
		}
			
		$arrayResult['PAYDEPT_LIST'] = $arrGrp;
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