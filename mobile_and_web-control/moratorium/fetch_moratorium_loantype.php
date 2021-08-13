<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'Moratorium')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGrp = array();
		
		$checkLoanMoratorium = $conoracle->prepare("select a.loancontract_no, b.loantype_desc, a.coop_id,a.LOANTYPE_CODE,
																 (case when a.loantype_code like '1%' then 1 
																 when a.loantype_code in ('20','21','22','27') then 2
																 when a.loantype_code = '30' then 3
																 when a.loantype_code in ('23','24','25','26') then 4
																 else 5 end) as set_sort
																from lncontmaster a, lnloantype b
																where a.loantype_code = b.loantype_code and
																 a.contract_status = 1 and
																 a.member_no = :member_no
																order by set_sort");
		$checkLoanMoratorium->execute([':member_no' => $member_no]);
		while($rowLoanType = $checkLoanMoratorium->fetch(PDO::FETCH_ASSOC)){
			$arrLoanType = array();
			$arrLoanType["SET_SORT"] = $rowLoanType["SET_SORT"];
			$arrLoanType["COOP_ID"] = $rowLoanType["COOP_ID"];
			$arrLoanType["LOANTYPE_DESC"] = $rowLoanType["LOANTYPE_DESC"];
			$contract_no = preg_replace('/\//','',$rowLoanType["LOANCONTRACT_NO"]);
			$arrLoanType["LOANCONTRACT_NO"] = $rowLoanType["LOANCONTRACT_NO"];
			if(mb_stripos($contract_no,'.') === FALSE){
				$loan_format = mb_substr($contract_no,0,2).'.'.mb_substr($contract_no,2,6).'/'.mb_substr($contract_no,8,2);
				if(mb_strlen($contract_no) == 10){
					$arrLoanType["LOANCONTRACT_REFNO"] = $loan_format;
				}else if(mb_strlen($contract_no) == 11){
					$arrLoanType["LOANCONTRACT_REFNO"] = $loan_format.'-'.mb_substr($contract_no,10);
				}
			}else{
				$arrLoanType["LOANCONTRACT_REFNO"] = $contract_no;
			}
			
			if($rowLoanType["LOANTYPE_CODE"] == '28'){
				$arrLoanType["IS_CANMORATORIUM"] = false;
				$arrLoanType["MORATORIUM_REMARK"] = "ไม่สามารถทำรายการนี้ได้";
			}else if($rowLoanType["SET_SORT"] == '4'){
				$arrLoanType["IS_CANMORATORIUM"] = false;
				$arrLoanType["MORATORIUM_REMARK"] = "ไม่สามารถทำรายการได้ เนื่องจากเป็นสัญญาประเภทสามัญใช้คนค้ำประกัน กรุณาจองคิวพักชำระเงินต้นเพื่อทำรายการที่สหกรณ์";
			}else if($rowLoanType["SET_SORT"] == '5'){
				$arrLoanType["IS_CANMORATORIUM"] = false;
				$arrLoanType["MORATORIUM_REMARK"] = "ไม่สามารถทำรายการได้ เนื่องจากเป็นสัญญาประเภทเงินกู้พิเศษหลักทรัพย์ค้ำประกัน กรุณาจองคิวพักชำระเงินต้นเพื่อทำรายการที่สหกรณ์";
			}else{
				$arrLoanType["IS_CANMORATORIUM"] = true;
			}
			$checkCanMora = $conoracle->prepare("SELECT LOANCONTRACT_NO FROM LNREQMORATORIUM WHERE COOP_ID = '000000' AND REQUEST_STATUS = 1 AND TO_CHAR(REQUEST_DATE, 'YYYYMM') BETWEEN '202101' AND '202105' AND LOANCONTRACT_NO = :loancontract_no");
			$checkCanMora->execute([':loancontract_no' =>  $rowLoanType["LOANCONTRACT_NO"]]);
			$rowCheck = $checkCanMora->fetch(PDO::FETCH_ASSOC);
			if(isset($rowCheck["LOANCONTRACT_NO"]) && $rowCheck["LOANCONTRACT_NO"] != ""){
				$arrLoanType["IS_CANMORATORIUM"] = false;
				$arrLoanType["MORATORIUM_REMARK"] = "อยู่ในระหว่างการพักชำระเงินต้น";
			}
			$fetchMoratorium = $conoracle->prepare("select rm.MORATORIUM_DOCNO, rm.LOANCONTRACT_NO, rm.REQUEST_DATE, rm.REQUEST_STATUS, 
																	rm.ENTRY_ID, rm.ENTRY_DATE, rm.CANCEL_ID, rm.CANCEL_DATE, rm.CANCEL_PRINCIPAL_BALANCE,rm.CANCEL_COOP,rm.CANCEL_DOCNO,cm.LOANTYPE_CODE,TO_CHAR(rm.ENTRY_DATE,'YYYY-MM-DD') as ENTRY_DATE_FORMAT
																	from LNREQMORATORIUM rm
																	join LNCONTMASTER cm ON cm.LOANCONTRACT_NO = rm.LOANCONTRACT_NO
																	where rm.member_no = :member_no AND rm.LOANCONTRACT_NO = :loancontract_no AND rm.REQUEST_STATUS = '1'
																	AND rm.ENTRY_DATE >= to_date('2021-06-01','YYYY-MM-DD')");
			$fetchMoratorium->execute([
				':member_no' => $member_no,
				':loancontract_no' => $rowLoanType["LOANCONTRACT_NO"]
			]);
			while($rowMoratorium = $fetchMoratorium->fetch(PDO::FETCH_ASSOC)){
				$arrMoratorium = array();
				$arrMoratorium["IS_SHOWREQ"] = true;
				if((int)date_create($rowQueue["ENTRY_DATE_FORMAT"])->format("Ymd") <= (int)date_create("2021-03-26")->format("Ymd")){
					$arrMoratorium["IS_CANCANCELREQ"] = false;
				}else if($rowMoratorium["LOANTYPE_CODE"] == '23' || $rowMoratorium["LOANTYPE_CODE"] == '24' || $rowMoratorium["LOANTYPE_CODE"] == '25' || $rowMoratorium["LOANTYPE_CODE"] == '26'){
					$arrMoratorium["IS_CANCANCELREQ"] = false;
				}else{
					$arrMoratorium["IS_CANCANCELREQ"] = true;
				}
				$arrMoratorium["MORATORIUM_DOCNO"] = $rowMoratorium["MORATORIUM_DOCNO"];
				$arrMoratorium["REQUEST_DATE"] = $lib->convertdate($rowMoratorium["ENTRY_DATE"] , 'D m Y', true);
				$arrLoanType["REQ_MORATORIUM"] = $arrMoratorium;
			}
			
			$arrGrp[] = $arrLoanType;
		}
		$arrayResult['LOAN_MORATORIUM'] = $arrGrp;
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