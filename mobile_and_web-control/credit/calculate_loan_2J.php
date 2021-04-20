<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$oldBal = 0;
$request_amt = 0;
$cal_remark = null;
$getMemb = $conoracle->prepare("SELECT SALARY_AMOUNT,MEMBER_DATE,trunc(MONTHS_BETWEEN(ADD_MONTHS(birth_date, 720),sysdate),0) as REMAIN_PERIOD, TRIM(EMP_TYPE) as EMP_TYPE
								FROM mbmembmaster WHERE member_no = :member_no");
$getMemb->execute([':member_no' => $member_no]);
$rowMemb = $getMemb->fetch(PDO::FETCH_ASSOC);
$month_member = $lib->count_duration($rowMemb["MEMBER_DATE"],'m');
$age_member = intval($month_member);
if($age_member >= 6){
	if($rowMemb["EMP_TYPE"] == '01' || $rowMemb["EMP_TYPE"] == '10' || $rowMemb["EMP_TYPE"] == '02' || $rowMemb["EMP_TYPE"] == '05' || $rowMemb["EMP_TYPE"] == '03' || $rowMemb["EMP_TYPE"] == '04' || $rowMemb["EMP_TYPE"] == '09'){
		//เช็คสิทธิ์กู้  98 % ของค่าหุ้นสะสมที่ปลอดภาระผูกพัน ไม่เกิน 150,000 บาท
		$getShareStk = $conoracle->prepare("SELECT (SHARESTK_AMT * 10) AS SHARESTK_AMT FROM SHSHAREMASTER WHERE MEMBER_NO = :member_no");
		$getShareStk->execute([':member_no' => $member_no]);
		$rowShareStk = $getShareStk->fetch(PDO::FETCH_ASSOC);
		$getContColl = $conoracle->prepare("select sum(lc.coll_balance) as sumcoll_balance
										from lncontcoll lc
										join lncontmaster lm on lc.loancontract_no = lm.loancontract_no
										where lc.loancolltype_code = '02' and lc.coll_status = '1' and lm.contract_status > 0 and lc.ref_collno = :member_no");
		$getContColl->execute([':member_no' => $member_no]);
		$rowContColl = $getContColl->fetch(PDO::FETCH_ASSOC);
		$share_available = ($rowShareStk["SHARESTK_AMT"]) - ($rowContColl["SUMCOLL_BALANCE"] ?? 0);
		
		if($share_available > 150000){
			$share_available = 150000;
			$maxloan_amt = $share_available * 0.98;
		} else {
			$maxloan_amt = $share_available * 0.98;
		}
		
		$getOldContract = $conoracle->prepare("SELECT ln.LOANCONTRACT_NO,ln.PRINCIPAL_BALANCE,lt.LOANTYPE_DESC,lt.LOANPAYMENT_TYPE
											FROM lncontmaster ln LEFT JOIN lnloantype lt ON ln.loantype_code = lt.loantype_code
											WHERE ln.member_no = :member_no and ln.contract_status > 0 and ln.contract_status <> 8
											and lt.loangroup_code = '02' and ln.loantype_code IN('16','10','11','12','13','14','15')");
		$getOldContract->execute([':member_no' => $member_no]);
		while($rowContDetail = $getOldContract->fetch(PDO::FETCH_ASSOC)){
			$arrContract = array();
			$arrContract['LOANTYPE_DESC'] = $rowContDetail["LOANTYPE_DESC"];
			$arrContract['CONTRACT_NO'] = $rowContDetail["LOANCONTRACT_NO"];
			$arrContract['BALANCE'] = $rowContDetail["PRINCIPAL_BALANCE"];
			$oldBal += $rowContDetail["PRINCIPAL_BALANCE"];
			$arrOldContract[] = $arrContract;
		}
		$getPeriod = $conoracle->prepare("SELECT MAX_PERIOD FROM LNLOANTYPEPERIOD 
										WHERE LOANTYPE_CODE = :loantype_code");
		$getPeriod->execute([':loantype_code' => $loantype_code]);
		$rowPeriod = $getPeriod->fetch(PDO::FETCH_ASSOC);
		$arrSubOther["LABEL"] = "งวดสูงสุด";
		$max_period = $rowMemb["REMAIN_PERIOD"];
		if($max_period > $rowPeriod["MAX_PERIOD"]){
			$max_period = $rowPeriod["MAX_PERIOD"];
		}
		if($max_period < 0){
			$max_period = 0;
		}
		$arrSubOther["VALUE"] = $max_period." งวด";
		$arrOtherInfo[] = $arrSubOther;
	}else{
		$maxloan_amt = 0;
		$arrSubOther["LABEL"] = "ประเภทสมาชิกของท่าน ไม่สามารถขอกู้ประเภทนี้ได้";
		$cal_remark = "ประเภทสมาชิกของท่าน ไม่สามารถขอกู้ประเภทนี้ได้";
		$arrCredit["FLAG_SHOW_RECV_NET"] = FALSE;
		$arrCollShould[] = $arrSubOther;
	}
}else{
	$maxloan_amt = 0;
	$arrSubOther["LABEL"] = "ต้องเป็นสมาชิกอย่างน้อย 6 เดือนขึ้นไป";
	$cal_remark = "ต้องเป็นสมาชิกอย่างน้อย 6 เดือนขึ้นไป";
	$arrCredit["FLAG_SHOW_RECV_NET"] = FALSE;
	$arrCollShould[] = $arrSubOther;
}
?>