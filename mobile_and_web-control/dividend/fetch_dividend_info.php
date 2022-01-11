<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DividendInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrDivmaster = array();
		$limit_year = $func->getConstant('limit_dividend');
		$getYeardividend = $conmysql->prepare("SELECT year AS DIV_YEAR FROM yrimport WHERE MEMBER_NO = :member_no
												GROUP BY year ORDER BY year DESC LIMIT ".$limit_year);
		$getYeardividend->execute([
			':member_no' => $member_no
		]);
		while($rowYear = $getYeardividend->fetch(PDO::FETCH_ASSOC)){
			$arrDividend = array();
			$getDivMaster = $conmysql->prepare("SELECT DIV_AMT,AVG_AMT FROM yrimport WHERE member_no = :member_no and year = :div_year");
			$getDivMaster->execute([
				':member_no' => $member_no,
				':div_year' => $rowYear["DIV_YEAR"]
			]);
			$rowDiv = $getDivMaster->fetch(PDO::FETCH_ASSOC);
			$arrDividend["YEAR"] = $rowYear["DIV_YEAR"];
			$arrDividend["DIV_AMT"] = number_format($rowDiv["DIV_AMT"],2);
			$arrDividend["AVG_AMT"] = number_format($rowDiv["AVG_AMT"],2);
			$arrDividend["SUM_AMT"] = number_format($rowDiv["DIV_AMT"] + $rowDiv["AVG_AMT"],2);
			$getMethpay = $conmysql->prepare("SELECT receive_desc,receive_acc,remark1,remark2,balance
											FROM yrimport WHERE member_no = :member_no and year = :div_year");
			$getMethpay->execute([
				':member_no' => $member_no,
				':div_year' => $rowYear["DIV_YEAR"]
			]);
			while($rowMethpay = $getMethpay->fetch(PDO::FETCH_ASSOC)){
				$arrayRecv = array();
				if(isset($rowMethpay["receive_acc"]) && $rowMethpay["receive_acc"] != ""){
					$arrayRecv["ACCOUNT_RECEIVE"] = $lib->formataccount($rowMethpay["receive_acc"],'xxx-xxxxxx-x');
				}
				$arrayRecv["RECEIVE_DESC"] = $rowMethpay["receive_desc"];
				$arrayRecv["BANK"] = $rowMethpay["remark1"].' '.$rowMethpay["remark2"];
				$arrayRecv["RECEIVE_AMT"] = number_format($rowMethpay["balance"],2);
				$arrDividend["RECEIVE_ACCOUNT"][] = $arrayRecv;
			}
			
			$getPaydiv = $conmysql->prepare("SELECT execution, revenue, loan_kp, loan_waitpay, share_waitpay, loan_coop, insure_loan, forward_cremation, grj_deposit,
											ssk_waitpay, sks_waitpay, shareandloan_waitpay, cre_forward_ssak, cremation_ssak, cre_forward_fscct, 
											cre_forward_s_ch_a_n, cre_forward_ss_st
											FROM yrimport WHERE member_no = :member_no and year = :div_year");
			$getPaydiv->execute([
				':member_no' => $member_no,
				':div_year' => $rowYear["DIV_YEAR"]
			]);
			$arrayPayGroup = array();
			$sumPay = 0;
			while($rowPay = $getPaydiv->fetch(PDO::FETCH_ASSOC)){
				$arrPay = array();
				if(isset($rowPay["execution"]) && $rowPay["execution"] > 0){
					$arrPay["TYPE_DESC"] = 'อายัด ส่งสำนักงานบังคับคดี';
					$arrPay["PAY_AMT"] = number_format($rowPay["execution"],2);
					$sumPay += $rowPay["execution"];
					$arrayPayGroup[] = $arrPay;
				}
				if(isset($rowPay["revenue"]) && $rowPay["revenue"] > 0){
					$arrPay["TYPE_DESC"] = 'อายัด ส่งสรรพากร';
					$arrPay["PAY_AMT"] = number_format($rowPay["revenue"],2);
					$sumPay += $rowPay["revenue"];
					$arrayPayGroup[] = $arrPay;
				}
				if(isset($rowPay["loan_kp"]) && $rowPay["loan_kp"] > 0){
					$arrPay["TYPE_DESC"] = 'เงินกู้ คป.';
					$arrPay["PAY_AMT"] = number_format($rowPay["loan_kp"],2);
					$sumPay += $rowPay["loan_kp"];
					$arrayPayGroup[] = $arrPay;
				}
				if(isset($rowPay["loan_waitpay"]) && $rowPay["loan_waitpay"] > 0){
					$arrPay["TYPE_DESC"] = 'เงินกู้ค้างชำระ';
					$arrPay["PAY_AMT"] = number_format($rowPay["loan_waitpay"],2);
					$sumPay += $rowPay["loan_waitpay"];
					$arrayPayGroup[] = $arrPay;
				}
				if(isset($rowPay["share_waitpay"]) && $rowPay["share_waitpay"] > 0){
					$arrPay["TYPE_DESC"] = 'เงินค่าหุ้นค้างชำระ';
					$arrPay["PAY_AMT"] = number_format($rowPay["share_waitpay"],2);
					$sumPay += $rowPay["share_waitpay"];
					$arrayPayGroup[] = $arrPay;
				}
				if(isset($rowPay["loan_coop"]) && $rowPay["loan_coop"] > 0){
					$arrPay["TYPE_DESC"] = 'เงินยืมสหกรณ์ตามมติที่ประชุม ';
					$arrPay["PAY_AMT"] = number_format($rowPay["loan_coop"],2);
					$sumPay += $rowPay["loan_coop"];
					$arrayPayGroup[] = $arrPay;
				}
				if(isset($rowPay["insure_loan"]) && $rowPay["insure_loan"] > 0){
					$arrPay["TYPE_DESC"] = 'เงินเบี้ยประกันเงินกู้';
					$arrPay["PAY_AMT"] = number_format($rowPay["insure_loan"],2);
					$sumPay += $rowPay["insure_loan"];
					$arrayPayGroup[] = $arrPay;
				}
				if(isset($rowPay["forward_cremation"]) && $rowPay["forward_cremation"] > 0){
					$arrPay["TYPE_DESC"] = 'เงินฌาปนกิจล่วงหน้า';
					$arrPay["PAY_AMT"] = number_format($rowPay["forward_cremation"],2);
					$sumPay += $rowPay["forward_cremation"];
					$arrayPayGroup[] = $arrPay;
				}
				if(isset($rowPay["grj_deposit"]) && $rowPay["grj_deposit"] > 0){
					$arrPay["TYPE_DESC"] = 'เงิน ก.ร.จ.';
					$arrPay["PAY_AMT"] = number_format($rowPay["grj_deposit"],2);
					$sumPay += $rowPay["grj_deposit"];
					$arrayPayGroup[] = $arrPay;
				}
				if(isset($rowPay["ssk_waitpay"]) && $rowPay["ssk_waitpay"] > 0){
					$arrPay["TYPE_DESC"] = 'เงินค้างจ่าย สสค.';
					$arrPay["PAY_AMT"] = number_format($rowPay["ssk_waitpay"],2);
					$sumPay += $rowPay["ssk_waitpay"];
					$arrayPayGroup[] = $arrPay;
				}
				if(isset($rowPay["sks_waitpay"]) && $rowPay["sks_waitpay"] > 0){
					$arrPay["TYPE_DESC"] = 'เงินค้างจ่าย ส.ค.ส.';
					$arrPay["PAY_AMT"] = number_format($rowPay["sks_waitpay"],2);
					$sumPay += $rowPay["sks_waitpay"];
					$arrayPayGroup[] = $arrPay;
				}
				if(isset($rowPay["shareandloan_waitpay"]) && $rowPay["shareandloan_waitpay"] > 0){
					$arrPay["TYPE_DESC"] = 'เงินค่าหุ้นและเงินกู้ค้างชำระ';
					$arrPay["PAY_AMT"] = number_format($rowPay["shareandloan_waitpay"],2);
					$sumPay += $rowPay["shareandloan_waitpay"];
					$arrayPayGroup[] = $arrPay;
				}
				if(isset($rowPay["cre_forward_ssak"]) && $rowPay["cre_forward_ssak"] > 0){
					$arrPay["TYPE_DESC"] = 'เงินฌาปนกิจล่วงหน้า สสอค.';
					$arrPay["PAY_AMT"] = number_format($rowPay["cre_forward_ssak"],2);
					$sumPay += $rowPay["cre_forward_ssak"];
					$arrayPayGroup[] = $arrPay;
				}
				if(isset($rowPay["cremation_ssak"]) && $rowPay["cremation_ssak"] > 0){
					$arrPay["TYPE_DESC"] = 'เงินฌาปนกิจ สสอค.';
					$arrPay["PAY_AMT"] = number_format($rowPay["cremation_ssak"],2);
					$sumPay += $rowPay["cremation_ssak"];
					$arrayPayGroup[] = $arrPay;
				}
				if(isset($rowPay["cre_forward_fscct"]) && $rowPay["cre_forward_fscct"] > 0){
					$arrPay["TYPE_DESC"] = 'เงินฌาปนกิจล่วงหน้า สส.ชสอ.';
					$arrPay["PAY_AMT"] = number_format($rowPay["cre_forward_fscct"],2);
					$sumPay += $rowPay["cre_forward_fscct"];
					$arrayPayGroup[] = $arrPay;
				}
				if(isset($rowPay["cre_forward_s_ch_a_n"]) && $rowPay["cre_forward_s_ch_a_n"] > 0){
					$arrPay["TYPE_DESC"] = 'เงินฌาปนกิจล่วงหน้า สฌอน.';
					$arrPay["PAY_AMT"] = number_format($rowPay["cre_forward_s_ch_a_n"],2);
					$sumPay += $rowPay["cre_forward_s_ch_a_n"];
					$arrayPayGroup[] = $arrPay;
				}
				if(isset($rowPay["cre_forward_ss_st"]) && $rowPay["cre_forward_ss_st"] > 0){
					$arrPay["TYPE_DESC"] = 'เงินฌาปนกิจล่วงหน้า สส.สท. ';
					$arrPay["PAY_AMT"] = number_format($rowPay["cre_forward_ss_st"],2);
					$sumPay += $rowPay["cre_forward_ss_st"];
					$arrayPayGroup[] = $arrPay;
				}
			}
			$arrDividend["PAY"] = $arrayPayGroup;
			$arrDividend["SUMPAY"] = number_format($sumPay,2);
			$arrDivmaster[] = $arrDividend;
		}
		$arrayResult["DIVIDEND"] = $arrDivmaster;
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