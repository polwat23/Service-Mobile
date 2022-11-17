<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','qrgeneratelist')){
		$arrayGrpAll = array();
		$arrayLoanData = array();
		$arrayExtendData = array();
		$arrayExecute = array();
		if(isset($dataComing["auto_adj"]) && $dataComing["auto_adj"] != ""){
			$arrayExecute["auto_adj"] = $dataComing["auto_adj"];
		}
		if(isset($dataComing["start_date"]) && $dataComing["start_date"] != ""){
			$arrayExecute["start_date"] = $dataComing["start_date"];
		}
		if(isset($dataComing["end_date"]) && $dataComing["end_date"] != ""){
			$arrayExecute["end_date"] = $dataComing["end_date"];
		}
		$fetchQrgenerateList = $conmysql->prepare("SELECT qrm.qrcodegen_id,qrm.qrgenerate,qrm.member_no,qrm.generate_date,qrm.qrtransfer_amt,qrm.qrtransfer_fee,qrd.auto_adj,
										qrm.expire_date,qrm.transfer_status,qrm.update_date,
										qrd.trans_code_qr,qrd.ref_account,qrd.qrtransferdt_amt,qrd.qrtransferdt_fee,
                                        qrc.trans_desc_qr, qrd.trans_status
										FROM gcqrcodegenmaster qrm
										LEFT JOIN gcqrcodegendetail qrd ON qrd.qrgenerate = qrm.qrgenerate
										LEFT JOIN gcconttypetransqrcode qrc ON qrd.trans_code_qr = qrc.trans_code_qr
										WHERE 1=1".(isset($dataComing["auto_adj"]) && $dataComing["auto_adj"] != "" ? 
										" and auto_adj = :auto_adj" : null)."
										".(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? 
											"and date_format(qrm.generate_date,'%Y-%m-%d') >= :start_date" : null)."
										".(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? 
											"and date_format(qrm.generate_date,'%Y-%m-%d') <= :end_date" : null). "
										and qrm.transfer_status in ('".implode("','",$dataComing["report_status"])."')"."
										 ORDER BY qrm.generate_date DESC");
			
		$fetchQrgenerateList->execute($arrayExecute);
		$loan_bal_01 = 0;
		$loan_prin_01 = 0;
		$loan_int_01 = 0;
		$loan_bal_02 = 0;
		$loan_prin_02 = 0;
		$loan_int_02 = 0;
		
		//เงินฝาก
		$deptTypeAllow = array();
		$deptTypeArr = array();
		$newDeptTypeAcc = array();
		$newDeptTypeAcc["ACC_TYPE_CODE"] = "none";
		$newDeptTypeAcc["ACC_TYPE_DESC"] = "ฯลฯ";
		$newDeptTypeAcc["ACC_TYPE_AMT"] = 0;
		$newDeptTypeAcc["ACC_TYPE_COUNT"] = 0;
		$deptTypeArr["none"] = $newDeptTypeAcc;
		$deptTypeAllow[] = "none";
		
		//เงินกู้
		$loanTypeAllow = array();
		$loanTypeArr = array();
		$getLoanType = $conoracle->prepare("SELECT LOANTYPE_CODE,LOANTYPE_DESC FROM LNLOANTYPE ORDER BY LOANTYPE_CODE");
		$getLoanType->execute();
		while($rowLoanType = $getLoanType->fetch(PDO::FETCH_ASSOC)){
			$newLoanTypeAcc = array();
			$newLoanTypeAcc["LOAN_TYPE_CODE"] = $rowLoanType["LOANTYPE_CODE"];
			$newLoanTypeAcc["LOAN_TYPE_DESC"] = $rowLoanType["LOANTYPE_DESC"];
			$newLoanTypeAcc["LOAN_TYPE_AMT"] = 0;
			$newLoanTypeAcc["LOAN_TYPE_PRN"] = 0;
			$newLoanTypeAcc["LOAN_TYPE_INT"] = 0;
			$newLoanTypeAcc["LOAN_TYPE_FEE"] = 0;
			$newLoanTypeAcc["LOAN_TYPE_COUNT"] = 0;
			$loanTypeArr[$rowLoanType["LOANTYPE_CODE"]] = $newLoanTypeAcc;
			$loanTypeAllow[] = $rowLoanType["LOANTYPE_CODE"];
		}
		$newLoanTypeAcc = array();
		$newLoanTypeAcc["LOAN_TYPE_CODE"] = "none";
		$newLoanTypeAcc["LOAN_TYPE_DESC"] = "ฯลฯ";
		$newLoanTypeAcc["LOAN_TYPE_AMT"] = 0;
		$newLoanTypeAcc["LOAN_TYPE_PRN"] = 0;
		$newLoanTypeAcc["LOAN_TYPE_INT"] = 0;
		$newLoanTypeAcc["LOAN_TYPE_FEE"] = 0;
		$newLoanTypeAcc["LOAN_TYPE_COUNT"] = 0;
		$loanTypeArr["none"] = $newLoanTypeAcc;
		$loanTypeAllow[] = "none";
			
		//รายการอื่นๆ
		$otherTypeAllow = array();
		$otherTypeArr = array();
		$getOther = $conmysql->prepare("SELECT trans_code_qr, trans_desc_qr FROM gcconttypetransqrcode where is_use = '1' AND trans_code_qr not in('001','002')");
		$getOther->execute();
		while($rowOther = $getOther->fetch(PDO::FETCH_ASSOC)){
			$newDeptTypeAcc = array();
			$newDeptTypeAcc["ACC_TYPE_CODE"] = $rowOther["trans_code_qr"];
			$newDeptTypeAcc["ACC_TYPE_DESC"] = $rowOther["trans_desc_qr"];
			$newDeptTypeAcc["ACC_TYPE_AMT"] = 0;
			$newDeptTypeAcc["ACC_TYPE_COUNT"] = 0;
			$otherTypeArr[$rowOther["trans_code_qr"]] = $newDeptTypeAcc;
			$otherTypeAllow[] = $rowOther["trans_code_qr"];
		}
		$newDeptTypeAcc = array();
		$newDeptTypeAcc["ACC_TYPE_CODE"] = "none";
		$newDeptTypeAcc["ACC_TYPE_DESC"] = "ฯลฯ";
		$newDeptTypeAcc["ACC_TYPE_AMT"] = 0;
		$newDeptTypeAcc["ACC_TYPE_COUNT"] = 0;
		$otherTypeArr["none"] = $newDeptTypeAcc;
		$otherTypeAllow[] = "none";
			
		while($rowQr = $fetchQrgenerateList->fetch(PDO::FETCH_ASSOC)){
			$arrayQr = array();
			$arrayQr["QRCODEGEN_ID"] = $rowQr["qrcodegen_id"];
			$arrayQr["QRGENERATE"] = $rowQr["qrgenerate"];
			$arrayQr["MEMBER_NO"] = $rowQr["member_no"];
			if($rowQr["auto_adj"] == "0"){
				$memberInfo = $conoracle->prepare("SELECT mp.prename_short,mb.memb_name,mb.memb_surname
												FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
												WHERE mb.member_no = :member_no");
				$memberInfo->execute([':member_no' => $rowQr["member_no"]]);
				$rowMember = $memberInfo->fetch(PDO::FETCH_ASSOC);
				$arrayQr["FULLNAME"] = $rowMember["PRENAME_SHORT"].$rowMember["MEMB_NAME"]." ".$rowMember["MEMB_SURNAME"];
			}
			$arrayQr["GENERATE_DATE"] = $lib->convertdate($rowQr["generate_date"],'d m Y',true);
			$arrayQr["QRTRANSFER_AMT"] = $rowQr["qrtransfer_amt"] == "0" ? "-" : number_format($rowQr["qrtransfer_amt"],2);
			$arrayQr["QRTRANSFER_FEE"] = $rowQr["qrtransfer_fee"] == "0" ? "-" : number_format($rowQr["qrtransfer_fee"],2);
			$arrayQr["EXPIRE_DATE"] = $lib->convertdate($rowQr["expire_date"],'d m Y',true);
			$arrayQr["TRANSFER_STATUS"] = $rowQr["transfer_status"];
			$arrayQr["UPDATE_DATE"] = $lib->convertdate($rowQr["update_date"],'d m Y',true);
			$arrayQr["TRANS_CODE_QR"] = $rowQr["trans_code_qr"];
			$arrayQr["REF_ACCOUNT"] = $rowQr["ref_account"];
			$arrayQr["QRTRANSFERDT_AMT"] = $rowQr["qrtransferdt_amt"] == "0" ? "-" : number_format($rowQr["qrtransferdt_amt"],2);
			$arrayQr["QRTRANSFERDT_FEE"] = $rowQr["qrtransferdt_fee"] == "0" ? "-" : number_format($rowQr["qrtransferdt_fee"],2);
			$arrayQr["TRANS_DESC_QR"] = $rowQr["trans_desc_qr"];
			$arrayQr["TRANS_STATUS"] = $rowQr["trans_status"];
			$arrayQr["AUTO_ADJ"] = $rowQr["auto_adj"];
			$arrayQr["TYPE_CODE"] = $rowQr["type_code"];
			$arrayQr["QRTRANSFERDT_PRINBAL"] = $rowQr["qrtransferdt_prinbal"];
			$arrayQr["QRTRANSFERDT_INT"] = $rowQr["qrtransferdt_int"];
			
			if($rowQr["trans_code_qr"] == "001"){
				if(isset($deptTypeArr[$rowQr["type_code"]]) && $deptTypeArr[$rowQr["type_code"]] != ""){
					
				}else {
					$deptTypeArr["none"]["ACC_TYPE_AMT"] += $rowQr["qrtransferdt_amt"];
					$deptTypeArr["none"]["ACC_TYPE_COUNT"] += 1;
				}
			}else if($rowQr["trans_code_qr"] == "002"){
				if(isset($loanTypeArr[$rowQr["type_code"]]) && $loanTypeArr[$rowQr["type_code"]] != ""){
					$loanTypeArr[$rowQr["type_code"]]["LOAN_TYPE_AMT"] += $rowQr["qrtransferdt_amt"];
					$loanTypeArr[$rowQr["type_code"]]["LOAN_TYPE_COUNT"] += 1;
				}else {
					$loanTypeArr["none"]["LOAN_TYPE_AMT"] += $rowQr["qrtransferdt_amt"];
					$loanTypeArr["none"]["LOAN_TYPE_COUNT"] += 1;
				}
				
				if($rowQr["type_code"] == '01'){
					$loan_bal_01 += $rowQr["qrtransferdt_amt"];
					$loan_prin_01 += $rowQr["qrtransferdt_prinbal"];
					$loan_int_01 += $rowQr["qrtransferdt_int"];
				}else if($rowQr["type_code"] == '02'){
					$loan_bal_02 += $rowQr["qrtransferdt_amt"];
					$loan_prin_02 += $rowQr["qrtransferdt_prinbal"];
					$loan_int_02 += $rowQr["qrtransferdt_int"];
				}
			}else {
				if(isset($otherTypeArr[$rowQr["trans_code_qr"]]) && $otherTypeArr[$rowQr["trans_code_qr"]] != ""){
					$otherTypeArr[$rowQr["trans_code_qr"]]["ACC_TYPE_AMT"] += $rowQr["qrtransferdt_amt"];
					$otherTypeArr[$rowQr["trans_code_qr"]]["ACC_TYPE_COUNT"] += 1;
				}else {
					$otherTypeArr["none"]["ACC_TYPE_AMT"] += $rowQr["qrtransferdt_amt"];
					$otherTypeArr["none"]["ACC_TYPE_COUNT"] += 1;
				}
			}
			$arrayGrpAll[] = $arrayQr;
		}
		$loanArr = array();
		$loanArr["LOANTYPE_CODE"] = "01";
		$loanArr["LOANTYPE_DESC"] = "เงินกู้ฉุกเฉิน";
		$loanArr["LOANTYPE_BALANCE"] = $loan_bal_01;
		$loanArr["LOANTYPE_PRINBAL"] = $loan_prin_01;
		$loanArr["LOANTYPE_INT"] = $loan_int_01;
		$arrayLoanData[] = $loanArr;
		$loanArr = array();
		$loanArr["LOANTYPE_CODE"] = "02";
		$loanArr["LOANTYPE_DESC"] = "เงินกู้สามัญ";
		$loanArr["LOANTYPE_BALANCE"] = $loan_bal_02;
		$loanArr["LOANTYPE_PRINBAL"] = $loan_prin_02;
		$loanArr["LOANTYPE_INT"] = $loan_int_02;
		$arrayLoanData[] = $loanArr;
		
		//เงินฝาก
		$sum_dept_amt = 0;
		$sum_dept_count = 0;
		$arrayExtendDataDept = array();
		if($dataComing["auto_adj"] != '0'){
			$extendArr = array();
			$extendArr["GENERATE_DATE"] = "";
			$arrayExtendData[] = $extendArr;
			$extendArr["GENERATE_DATE"] = "สรุปยอดรายการเงินฝาก(อัตโนมัติ)";
			$arrayExtendData[] = $extendArr;
			$arrayExtendDataDept[] = $extendArr;
			$extendArr = array();
			$extendArr["GENERATE_DATE"] = "ประเภทรายการ";
			$extendArr["EXPIRE_DATE"] = "จำนวนเงิน";
			$extendArr["TRANS_STATUS"] = "จำนวนรายการ";
			$arrayExtendData[] = $extendArr;
			$arrayExtendDataDept[] = $extendArr;
			foreach ($deptTypeAllow as $dept) {
				$extendArr = array();
				if($deptTypeArr[$dept]["ACC_TYPE_COUNT"] > 0){
					$extendArr["GENERATE_DATE"] = $deptTypeArr[$dept]["ACC_TYPE_DESC"];
					$extendArr["EXPIRE_DATE"] = $deptTypeArr[$dept]["ACC_TYPE_AMT"] == "0" ? "-" : number_format($deptTypeArr[$dept]["ACC_TYPE_AMT"],2);
					$extendArr["TRANS_STATUS"] = $deptTypeArr[$dept]["ACC_TYPE_COUNT"] == "0" ? "-" : number_format($deptTypeArr[$dept]["ACC_TYPE_COUNT"],0);
					$sum_dept_amt += $deptTypeArr[$dept]["ACC_TYPE_AMT"];
					$sum_dept_count += $deptTypeArr[$dept]["ACC_TYPE_COUNT"];
					$arrayExtendData[] = $extendArr;
					$arrayExtendDataDept[] = $extendArr;
				}
			}
			$extendArr = array();
			$extendArr["EXPIRE_DATE"] = $sum_dept_amt == "0" ? "-" : number_format($sum_dept_amt,2);
			$extendArr["TRANS_STATUS"] = $sum_dept_count == "0" ? "-" : number_format($sum_dept_count,0);
			$arrayExtendData[] = $extendArr;
			$arrayExtendDataDept[] = $extendArr;
		}
		
		//เงินกู้
		$sum_loan_amt = 0;
		$sum_loan_prn = 0;
		$sum_loan_int = 0;
		$sum_loan_fee = 0;
		$sum_loan_count = 0;
		$arrayExtendDataLoan = array();
		if($dataComing["auto_adj"] != '0'){
			$extendArr = array();
			$extendArr["GENERATE_DATE"] = "";
			$arrayExtendData[] = $extendArr;
			$extendArr["GENERATE_DATE"] = "สรุปยอดรายการเงินกู้(อัตโนมัติ)";
			$arrayExtendData[] = $extendArr;
			$arrayExtendDataLoan[] = $extendArr;
			$extendArr = array();
			$extendArr["GENERATE_DATE"] = "ประเภทรายการ";
			$extendArr["EXPIRE_DATE"] = "จำนวนเงิน";
			$extendArr["TRANS_STATUS"] = "เงินต้น";
			$extendArr["QRTRANSFERDT_AMT"] = "ดอกเบี้ย";
			$extendArr["QRTRANSFERDT_FEE"] = "ค่าปรับ(ถ้ามี)";
			$extendArr["TRANS_DESC_QR"] = "จำนวนรายการ";
			$arrayExtendData[] = $extendArr;
			$arrayExtendDataLoan[] = $extendArr;
			foreach ($loanTypeAllow as $loan) {
				$extendArr = array();
				$l_amt = $loanTypeArr[$loan]["LOAN_TYPE_AMT"];
				$l_prn = $loanTypeArr[$loan]["LOAN_TYPE_PRN"];
				$l_int = $loanTypeArr[$loan]["LOAN_TYPE_INT"];
				$l_fee = $loanTypeArr[$loan]["LOAN_TYPE_INT"];
				$l_count = $loanTypeArr[$loan]["LOAN_TYPE_COUNT"];
				if($l_count > 0){
					$extendArr["GENERATE_DATE"] = $loanTypeArr[$loan]["LOAN_TYPE_DESC"];
					$extendArr["EXPIRE_DATE"] = $l_amt == "0" ? "-" : number_format($l_amt,2);
					$extendArr["TRANS_STATUS"] =  $l_prn == "0" ? "-" : number_format($l_prn,2);
					$extendArr["QRTRANSFERDT_AMT"] = $l_int == "0" ? "-" :  number_format($l_int,2);
					$extendArr["QRTRANSFERDT_FEE"] =  $l_fee == "0" ? "-" : number_format($l_fee,2);
					$extendArr["TRANS_DESC_QR"] = $l_count == "0" ? "-" : number_format($l_count,0);
					$sum_loan_amt += $loanTypeArr[$loan]["LOAN_TYPE_AMT"];
					$sum_loan_prn += $loanTypeArr[$loan]["LOAN_TYPE_PRN"];
					$sum_loan_int += $loanTypeArr[$loan]["LOAN_TYPE_INT"];
					$sum_loan_fee += $loanTypeArr[$loan]["LOAN_TYPE_FEE"];
					$sum_loan_count += $loanTypeArr[$loan]["LOAN_TYPE_COUNT"];
					$arrayExtendData[] = $extendArr;
					$arrayExtendDataLoan[] = $extendArr;
				}
			}
			$extendArr = array();
			$extendArr["EXPIRE_DATE"] = $sum_loan_amt == "0" ? "-" : number_format($sum_loan_amt,2);
			$extendArr["TRANS_STATUS"] = $sum_loan_prn == "0" ? "-" : number_format($sum_loan_prn,2);
			$extendArr["QRTRANSFERDT_AMT"] = $sum_loan_int == "0" ? "-" : number_format($sum_loan_int,2);
			$extendArr["QRTRANSFERDT_FEE"] = $sum_loan_fee == "0" ? "-" : number_format($sum_loan_fee,2);
			$extendArr["TRANS_DESC_QR"] = $sum_loan_count == "0" ? "-" : number_format($sum_loan_count,0);
			$arrayExtendData[] = $extendArr;
			$arrayExtendDataLoan[] = $extendArr;
		}
		
		//รายการอื่นๆ
		$sum_other_amt = 0;
		$sum_other_count = 0;
		$arrayExtendDataExt = array();
		if($dataComing["auto_adj"] != '1'){
			$extendArr = array();
			$extendArr["GENERATE_DATE"] = "";
			$arrayExtendData[] = $extendArr;
			$extendArr["GENERATE_DATE"] = "สรุปยอดรายการอื่นๆ(ไม่อัตโนมัติ)";
			$arrayExtendData[] = $extendArr;
			$arrayExtendDataExt[] = $extendArr;
			$extendArr = array();
			$extendArr["GENERATE_DATE"] = "ประเภทรายการ";
			$extendArr["EXPIRE_DATE"] = "จำนวนเงิน";
			$extendArr["TRANS_STATUS"] = "จำนวนรายการ";
			$arrayExtendData[] = $extendArr;
			$arrayExtendDataExt[] = $extendArr;
			foreach ($otherTypeAllow as $other) {
				$extendArr = array();
				$o_amt = $otherTypeArr[$other]["ACC_TYPE_AMT"];
				$o_count = $otherTypeArr[$other]["ACC_TYPE_COUNT"];
				if($o_count > 0){
					$extendArr["GENERATE_DATE"] = $otherTypeArr[$other]["ACC_TYPE_DESC"];
					$extendArr["EXPIRE_DATE"] = $o_amt == "0" ? "-" : number_format($o_amt,2);
					$extendArr["TRANS_STATUS"] = $o_count == "0" ? "-" : number_format($o_count,0);
					$sum_other_amt += $otherTypeArr[$other]["ACC_TYPE_AMT"];
					$sum_other_count += $otherTypeArr[$other]["ACC_TYPE_COUNT"];
					$arrayExtendData[] = $extendArr;
					$arrayExtendDataExt[] = $extendArr;
				}
			}
			$extendArr = array();
			$extendArr["EXPIRE_DATE"] = $sum_other_amt == "0" ? "-" : number_format($sum_other_amt,2);
			$extendArr["TRANS_STATUS"] = $sum_other_count == "0" ? "-" : number_format($sum_other_count,0);
			$arrayExtendData[] = $extendArr;
			$arrayExtendDataExt[] = $extendArr;
		}
		//สรุป
		$arrayExtendDataSum = array();
		$extendArr = array();
		$extendArr["GENERATE_DATE"] = "";
		$arrayExtendDataSum[] = $extendArr;
		$extendArr["GENERATE_DATE"] = "สรุปยอดรายการทั้งหมด";
		$arrayExtendData[] = $extendArr;
		$arrayExtendDataSum[] = $extendArr;
		$extendArr = array();
		$extendArr["GENERATE_DATE"] = "ประเภทรายการ";
		$extendArr["EXPIRE_DATE"] = "จำนวนเงิน";
		$extendArr["TRANS_STATUS"] = "จำนวนรายการ";
		$arrayExtendData[] = $extendArr;
		$arrayExtendDataSum[] = $extendArr;
		$extendArr = array();
		$extendArr["GENERATE_DATE"] = "สรุปรวม";
		$sum_all_amt = $sum_dept_amt + $sum_loan_amt + $sum_other_amt;
		$sum_all_count = $sum_dept_count + $sum_loan_count + $sum_other_count;
		$extendArr["EXPIRE_DATE"] = $sum_all_amt == "0" ? "-" : number_format($sum_all_amt,2);
		$extendArr["TRANS_STATUS"] = $sum_all_count == "0" ? "-" : number_format($sum_all_count,0);
		$arrayExtendData[] = $extendArr;
		$arrayExtendDataSum[] = $extendArr;
		
		$arrayResult['QRGENERATELIST'] = $arrayGrpAll;
		$arrayResult['EXTEND'] = $arrayExtendData;
		$arrayResult['EXTEND_DEPT'] = $arrayExtendDataDept;
		$arrayResult['EXTEND_LOAN'] = $arrayExtendDataLoan;
		$arrayResult['EXTEND_EXT'] = $arrayExtendDataExt;
		$arrayResult['EXTEND_SUM'] = $arrayExtendDataSum;
		$arrayResult['LOAN_DATA'] = $arrayLoanData;
		$arrayResult['loanTypeAllow'] = $loanTypeAllow;
		$arrayResult['RESULT'] = TRUE;
		require_once('../../../../include/exit_footer.php');
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../../include/exit_footer.php');
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../../include/exit_footer.php');
}
?>