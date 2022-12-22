<?php
require_once(__DIR__.'/../../autoloadConnection.php');
require_once(__DIR__.'/../../include/validate_input.php');

$member_no = $member_no ?? $dataComing["member_no"];
$loantype_code = $rowCanCal["loantype_code"] ?? $dataComing["loantype_code"];
$maxloan_amt = 0;
$oldBal = 0;
$receive_net = 0;
$getMemb = $conoracle->prepare("SELECT mb.MEMBTYPE_CODE,mg.MEMBGROUP_CONTROL,(sh.sharestk_amt*10) as SHARE_AMT,mb.SALARY_AMOUNT
											FROM mbmembmaster mb LEFT JOIN shsharemaster sh ON mb.member_no = sh.member_no 
											LEFT JOIN mbucfmembgroup mg ON mb.membgroup_code = mg.membgroup_code
											WHERE mb.member_no = :member_no");
$getMemb->execute([':member_no' => $member_no]);
$rowMemb = $getMemb->fetch(PDO::FETCH_ASSOC);
if($rowMemb["MEMBTYPE_CODE"] != "05" && $rowMemb["MEMBTYPE_CODE"] != "10" && $rowMemb["MEMBGROUP_CONTROL"] < '82500000'){
	$canRequest = TRUE;
	$maxloan_amt = $rowMemb["SHARE_AMT"] * 0.90;
}else{
	$canRequest = TRUE;
	$maxloan_amt = $rowMemb["SHARE_AMT"] * 0.90;
	$rights_desc = "สมาชิกท่านนี้ได้อยู่ประเภทสมาชิกเป็นลูกจ้างชั่วคราวหรือมูลนิธิ สมาคม";
}
if($maxloan_amt > $rowMemb["SHARE_AMT"]){
	$maxloan_amt = $rowMemb["SHARE_AMT"];
}
if($maxloan_amt > 0){
	$arrOldContract = array();
	$getOldContract = $conoracle->prepare("SELECT lm.principal_balance,lt.loantype_desc,lm.loancontract_no FROM lncontmaster lm 
														LEFT JOIN lnloantype lt ON lm.loantype_code = lt.loantype_code 
														WHERE lm.member_no = :member_no and lm.contract_status > 0 and lm.loantype_code IN('23','20','33')");
	$getOldContract->execute([
		':member_no' => $member_no
	]);
	while($rowOldContract = $getOldContract->fetch(PDO::FETCH_ASSOC)){
		$arrContract = array();
		$arrContract['LOANTYPE_DESC'] = $rowOldContract["LOANTYPE_DESC"];
		$contract_no = $rowOldContract["LOANCONTRACT_NO"];
		if(mb_stripos($contract_no,'.') === FALSE){
			$loan_format = mb_substr($contract_no,0,2).'.'.mb_substr($contract_no,2,6).'/'.mb_substr($contract_no,8,2);
			if(mb_strlen($contract_no) == 10){
				$arrContract["CONTRACT_NO"] = $loan_format;
			}else if(mb_strlen($contract_no) == 11){
				$arrContract["CONTRACT_NO"] = $loan_format.'-'.mb_substr($contract_no,10);
			}
		}else{
			$arrContract["CONTRACT_NO"] = $contract_no;
		}
		$arrContract['BALANCE'] = $rowOldContract["PRINCIPAL_BALANCE"];
		$oldBal += $rowOldContract["PRINCIPAL_BALANCE"];
		$arrOldContract[] = $arrContract;
	}
	$arrCredit["OLD_CONTRACT"] = $arrOldContract;
}
$arrCredit["FLAG_SHOW_RECV_NET"] = FALSE;
if($loanRequest === TRUE){
	$receive_net -= $oldBal;
}
?>