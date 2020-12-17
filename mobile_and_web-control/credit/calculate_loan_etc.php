<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$oldBal = 0;
$request_amt = 0;
$fetchCredit = $conoracle->prepare("SELECT	
									MAX(l.maxloan_amt) as MAXLOAN_AMT ,
									sum((s.sharestk_amt*sh.unitshare_value*l.multiple_share )) AS SHARE_AMT,
									sum((NVL(m.salary_amount,15000)*l.multiple_salary )) AS SALARY_AMOUNT
									FROM lnloantypecustom l LEFT JOIN lnloantype lt ON l.loantype_code = lt.loantype_code,
									shsharemaster s LEFT JOIN mbmembmaster m ON s.member_no=m.member_no
									LEFT JOIN lnloantypembtype lm ON lm.membtype_code = m.membtype_code,shsharetype sh
									WHERE m.member_no = :member_no
									AND s.SHAREMASTER_STATUS = '1' 
									AND LT.LOANGROUP_CODE IN ( '01','02','03' )
									AND LT.LOANTYPE_CODE = :loantype_code
									AND s.sharestk_amt*sh.unitshare_value between l.startshare_amt AND l.endshare_amt 
									AND s.last_period between l.startage_amt AND l.endage_amt 
									AND NVL(m.salary_amount,15000) between l.startsalary_amt AND l.endsalary_amt 
									AND TRUNC(MONTHS_BETWEEN (SYSDATE,m.member_date ) /12 *12) between
									l.startmember_time and l.endmember_time");
$fetchCredit->execute([
	':member_no' => $member_no,
	':loantype_code' => $loantype_code
]);
$rowCredit = $fetchCredit->fetch(PDO::FETCH_ASSOC);
$arrCheckLowest = array();
$arrCheckLowest[] = $rowCredit["MAXLOAN_AMT"];
if($rowCredit["SALARY_AMOUNT"] > 0){
	$arrCheckLowest[] = $rowCredit["SALARY_AMOUNT"];
}
if($rowCredit["SHARE_AMT"] > 0){
	$arrCheckLowest[] = $rowCredit["SHARE_AMT"];
}
$maxloan_amt = MIN($arrCheckLowest);
$arrSubOtherInfo["LABEL"] = "กู้ได้ไม่เกิน";

if($loantype_code == '55'){
	if($maxloan_amt > 3000000){
		$maxloan_amt = 3000000;
	}
	$arrSubOtherInfo["VALUE"] = "180 งวด";
}else if($loantype_code == '10'){
	$arrSubOtherInfo["VALUE"] = "10 งวด";
} else if($loantype_code == '21'){
	$arrSubOtherInfo["VALUE"] = "10 งวด";
}else if($loantype_code == '22'){
	$arrSubOtherInfo["VALUE"] = "10 งวด";
}else if($loantype_code == '31'){
	$arrSubOtherInfo["VALUE"] = "250 งวด";
}else if($loantype_code == '38'){
	$arrSubOtherInfo["VALUE"] = "360 งวด";
}else if($loantype_code == '41'){
	$arrSubOtherInfo["VALUE"] = "200 งวด";
}else if($loantype_code == '42'){
	$arrSubOtherInfo["VALUE"] = "360 งวด";
}else if($loantype_code == '12'){
	$arrSubOtherInfo["VALUE"] = "180 งวด";
}else if($loantype_code == '52'){
	$arrSubOtherInfo["VALUE"] = "180 งวด";
}else if($loantype_code == '53'){
	$arrSubOtherInfo["VALUE"] = "48 งวด";
}else if($loantype_code == '13'){
	$arrSubOtherInfo["VALUE"] = "12 งวด";
}else if($loantype_code == '14'){
	$arrSubOtherInfo["VALUE"] = "36 งวด";
}else if($loantype_code == '15'){
	$arrSubOtherInfo["VALUE"] = "24 งวด";
}else if($loantype_code == '17'){
	$arrSubOtherInfo["VALUE"] = "36 งวด";
}else if($loantype_code == '57'){
	$arrSubOtherInfo["VALUE"] = "12 งวด";
}else if($loantype_code == '58'){
	$arrSubOtherInfo["VALUE"] = "24 งวด";
}else{
	$arrSubOtherInfo["VALUE"] = "180 งวด";
}
$arrOtherInfo[] = $arrSubOtherInfo;
?>