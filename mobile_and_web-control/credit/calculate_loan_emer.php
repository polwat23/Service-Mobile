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
if($rowMemb["MEMBGROUP_CONTROL"] != "82500000" && $rowMemb["MEMBTYPE_CODE"] <= '10'){
	$getCollGrp = $conoracle->prepare("SELECT mg.MEMBGROUP_CONTROL,mg.MEMBGROUP_CODE,mb.RESIGN_STATUS FROM lncontmaster lm 
													LEFT JOIN lncontcoll lcc ON lm.loancontract_no = lcc.loancontract_no 
													LEFT JOIN mbmembmaster mb ON lcc.ref_collno = mb.member_no
													LEFT JOIN mbucfmembgroup mg ON mb.membgroup_code = mg.membgroup_code
													WHERE lm.member_no = :member_no and lm.contract_status > 0 and lcc.loancolltype_code = '01'");
	$getCollGrp->execute([':member_no' => $member_no]);
	$rowCollGrp = $getCollGrp->fetch(PDO::FETCH_ASSOC);
	if((($rowCollGrp["MEMBGROUP_CONTROL"] == '82500000' && ($rowCollGrp["MEMBGROUP_CODE"] == '82500600' || $rowCollGrp["MEMBGROUP_CODE"] == '82500700' || $rowCollGrp["MEMBGROUP_CODE"] == '82500800')) 
		|| $rowCollGrp["MEMBGROUP_CONTROL"] != '82500000') && $rowCollGrp["RESIGN_STATUS"] == 0){
		$getCollMemb = $conoracle->prepare("SELECT mg.membgroup_control,mg.MEMBGROUP_CODE FROM lncontcoll lcc LEFT JOIN lncontmaster lm ON lcc.loancontract_no = lm.loancontract_no 
													LEFT JOIN mbmembmaster mb ON lm.member_no = mb.member_no
													LEFT JOIN mbucfmembgroup mg ON mb.membgroup_code = mg.membgroup_code
													WHERE lcc.ref_collno = :member_no and lcc.loancolltype_code = '01' and lm.contract_status > 0");
		$getCollMemb->execute([':member_no' => $member_no]);
		$rowCollMemb = $getCollMemb->fetch(PDO::FETCH_ASSOC);
		if((($rowCollMemb["MEMBGROUP_CONTROL"] == '82500000' && ($rowCollMemb["MEMBGROUP_CODE"] == '82500600' || $rowCollMemb["MEMBGROUP_CODE"] == '82500700' || $rowCollMemb["MEMBGROUP_CODE"] == '82500800')) 
		|| $rowCollMemb["MEMBGROUP_CONTROL"] != '82500000')){
			$canRequest = TRUE;
			if($rowMemb["MEMBTYPE_CODE"] == '05' || $rowMemb["MEMBTYPE_CODE"] == '10'){
				$percentShare = 0.90;
				$maxloan_amt = $rowMemb["SHARE_AMT"] * $percentShare;
				$rights_desc = "ได้ ".$maxloan_amt." บาท คิดเป็น".($percentShare * 100)."% ของค่าหุ้นเนื่องจาก ประเภทสมาชิกเป็น ".$rowMemb["MEMBTYPE_CODE"];
			}else{
				$checkCountContract = $conoracle->prepare("SELECT * FROM (SELECT COUNT(lm.loancontract_no) as C_CON_NORMAL FROM lncontmaster lm 
																				LEFT JOIN lnloantype lt ON lm.loantype_code = lt.loantype_code
																				WHERE lt.loangroup_code = '02' and lm.member_no = :member_no and lm.contract_status > 0),
																				(SELECT COUNT(lm.loancontract_no) as C_CON_SPECIAL FROM lncontmaster lm 
																				LEFT JOIN lnloantype lt ON lm.loantype_code = lt.loantype_code
																				WHERE lt.loangroup_code = '03' and lm.member_no = :member_no and lm.contract_status > 0),
																				(SELECT COUNT(loancontract_no) as C_CON_ADJ FROM lncontmaster lm
																				WHERE loantype_code = '25' and member_no = :member_no and contract_status > 0)");
				$checkCountContract->execute([':member_no' => $member_no]);
				$rowCount = $checkCountContract->fetch(PDO::FETCH_ASSOC);
				if(($rowCount["C_CON_NORMAL"] > 0 && $rowCount["C_CON_SPECIAL"] > 0) || $rowCount["C_CON_ADJ"] > 0){
					$maxloan_amt = $rowMemb["SALARY_AMOUNT"] * 2;
					$rights_desc = "ได้ ".$maxloan_amt." บาท คิดเป็น 2 เท่าของเงินเดือนเนื่องจากมีเงินกู้สามัญ เงินกู้พิเศษหรือเงินกู้ปรับปรุงโครงสร้างหนี้";
				}else{
					$getCollSelfLoan = $conoracle->prepare("SELECT COUNT(*) as C_COLL_OWN,own_contract.last_periodpay as OWN_LAST_PERIODPAY,ref_coll.last_periodpay as COLL_LAST_PERIODPAY
																			FROM(
																			SELECT lc.ref_collno,lm.last_periodpay
																			FROM lncontmaster lm LEFT JOIN lncontcoll lc ON lm.loancontract_no = lc.loancontract_no
																			WHERE lm.member_no = :member_no and lm.contract_status > 0 and lc.loancolltype_code = '01'
																			) own_contract,(SELECT lm.member_no,lm.last_periodpay
																			FROM lncontcoll lc LEFT JOIN lncontmaster lm ON lc.loancontract_no = lm.loancontract_no
																			WHERE lc.ref_collno = :member_no and lm.contract_status > 0 and lc.loancolltype_code = '01'
																			) ref_coll
																			WHERE own_contract.ref_collno = ref_coll.member_no and rownum <= 1
																			GROUP BY own_contract.last_periodpay,ref_coll.last_periodpay");
					$getCollSelfLoan->execute([':member_no' => $member_no]);
					$rowSelfLoan = $getCollSelfLoan->fetch(PDO::FETCH_ASSOC);
					if($rowSelfLoan["C_COLL_OWN"] > 0){
						$getLoanSpecial = $conoracle->prepare("SELECT COUNT(lm.loancontract_no) as C_CONT FROM lncontmaster lm 
																				LEFT JOIN lnloantype lt ON lm.LOANTYPE_CODE = lt.LOANTYPE_CODE
																				WHERE lm.member_no = :member_no and lm.contract_status > 0 and lt.LOANGROUP_CODE = '03'");
						$getLoanSpecial->execute([':member_no' => $member_no]);
						$rowLoanSpecial = $getLoanSpecial->fetch(PDO::FETCH_ASSOC);
						if($rowLoanSpecial["C_CONT"] > 0){
							$maxloan_amt = $rowMemb["SALARY_AMOUNT"] * 1;
							$rights_desc = "ได้ ".$maxloan_amt." บาท คิดเป็น 1 เท่าของเงินเดือนเนื่องจากมีการแลกกู้แลกค้ำและมีเงินกู้พิเศษมากกว่า 1 สัญญา";
						}else{
							if($rowSelfLoan["OWN_LAST_PERIODPAY"] >= 4 && $rowSelfLoan["COLL_LAST_PERIODPAY"] >= 1){
								$maxloan_amt = $rowMemb["SALARY_AMOUNT"] * 3;
								$rights_desc = "ได้ ".$maxloan_amt." บาท คิดเป็น 3 เท่าของเงินเดือนเนื่องจากท่านส่งงวดกู้สามัญมาแล้ว 4 งวดและผู้ค้ำของท่านส่งสัญญาค้ำมาแล้ว 1 งวด";
							}else{
								$maxloan_amt = $rowMemb["SALARY_AMOUNT"] * 2;
								$rights_desc = "ได้ ".$maxloan_amt." บาท คิดเป็น 2 เท่าของเงินเดือนเนื่องจากมีการแลกกู้แลกค้ำ";
							}
						}
					}else{
						$getLoanSpecial = $conoracle->prepare("SELECT COUNT(lm.loancontract_no) as C_CONT FROM lncontmaster lm 
																				LEFT JOIN lnloantype lt ON lm.LOANTYPE_CODE = lt.LOANTYPE_CODE
																				WHERE lm.member_no = :member_no and lm.contract_status > 0 and lt.LOANGROUP_CODE = '03'");
						$getLoanSpecial->execute([':member_no' => $member_no]);
						$rowLoanSpecial = $getLoanSpecial->fetch(PDO::FETCH_ASSOC);
						$checkCollAmt = $conoracle->prepare("SELECT COUNT(lm.loancontract_no) as C_CONT
																			FROM lncontmaster lm LEFT JOIN lncontcoll lc ON lm.LOANCONTRACT_NO = lc.LOANCONTRACT_NO
																			WHERE lc.ref_collno = :member_no and lm.contract_status > 0");
						$checkCollAmt->execute([':member_no' => $member_no]);
						$rowCollAmt = $checkCollAmt->fetch(PDO::FETCH_ASSOC);
						if($rowLoanSpecial["C_CONT"] > 0 && $rowCollAmt["C_CONT"] >= 2){
							$maxloan_amt = $rowMemb["SALARY_AMOUNT"] * 3;
							$rights_desc = "ได้ ".$maxloan_amt." บาท คิดเป็น 3 เท่าของเงินเดือนเนื่องจากสมาชิกมีเงินกู้พิเศษและค้ำประกันอย่างน้อย 2 สัญญา";
						}else{
							$getContactTransfer = $conoracle->prepare("SELECT PRINCIPAL_BALANCE FROM lncontmaster WHERE contract_status = 11 and member_no = :member_no");
							$getContactTransfer->execute([':member_no' => $member_no]);
							$rowContactTransfer = $getContactTransfer->fetch(PDO::FETCH_ASSOC);
							if(isset($rowContactTransfer["PRINCIPAL_BALANCE"]) && $rowContactTransfer["PRINCIPAL_BALANCE"]  != ""){
								$percentShare = 1.25;
								if($rowContactTransfer["PRINCIPAL_BALANCE"] < ($rowMemb["SHARE_AMT"] * $percentShare)){
									$maxloan_amt = $rowMemb["SALARY_AMOUNT"] * 1;
									$rights_desc = "ได้ ".$maxloan_amt." บาท คิดเป็น 1 เท่าของเงินเดือนเนื่องจากมีสัญญารับโอนมาและหนี้คงเหลือน้อยกว่า ".($percentShare * 100)."% ของหุ้น";
								}else{
									$canRequest = FALSE;
									$maxloan_amt = 0;
								}
							}else{
								$maxloan_amt = $rowMemb["SALARY_AMOUNT"] * 3;
								$rights_desc = "ได้ ".$maxloan_amt." บาท คิดเป็น 3 เท่าของเงินเดือน";
							}
						}
					}
				}
			}
			if($maxloan_amt > $rowMemb["SHARE_AMT"]){
				$maxloan_amt = $rowMemb["SHARE_AMT"];
			}
			if($maxloan_amt > 0){
				$arrOldContract = array();
				$getOldContract = $conoracle->prepare("SELECT lm.principal_balance,lt.loantype_desc,lm.loancontract_no FROM lncontmaster lm 
																	LEFT JOIN lnloantype lt ON lm.loantype_code = lt.loantype_code 
																	WHERE lm.member_no = :member_no and lm.contract_status > 0 and lm.loantype_code IN('10','11','12','13')");
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
		}else{
			$rights_desc = "สมาชิกท่านนี้มีการค้ำประกันให้หน่วยเก็บเอง ไม่สามารถขอกู้ฉุกเฉินได้";
		}
	}else{
		if($rowCollGrp["MEMBGROUP_CONTROL"] == '82500000'){
			$rights_desc = "สมาชิกท่านนี้มีหน่วยเก็บเองค้ำประกันอยู่ ไม่สามารถขอกู้ฉุกเฉินได้";
		}
		if($rowCollGrp["RESIGN_STATUS"] > 0){
			$rights_desc = "สมาชิกท่านนี้ได้ไปค้ำประกันให้สมาชิกลาออก ไม่สามารถขอกู้ฉุกเฉินได้";
		}
	}
}else{
	if($rowMemb["MEMBGROUP_CONTROL"] == "82500000"){
		$rights_desc = "หน่วยเก็บเอง ไม่สามารถขอกู้ฉุกเฉินได้";
	}
	if($rowMemb["MEMBTYPE_CODE"] > '10'){
		$rights_desc = "ประเภทสมาชิก ไม่สามารถขอกู้ฉุกเฉินได้";
	}
}
if($loanRequest === TRUE){
	$receive_net -= $oldBal;
}
?>