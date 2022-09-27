<?php
$header = array();
$fetchName = $conoracle->prepare("SELECT MB.MEMB_NAME,MB.MEMB_SURNAME,MP.PRENAME_DESC,MBG.MEMBGROUP_DESC,MBG.MEMBGROUP_CODE
										FROM MBMEMBMASTER MB LEFT JOIN 
										MBUCFPRENAME MP ON MB.PRENAME_CODE = MP.PRENAME_CODE
										LEFT JOIN MBUCFMEMBGROUP MBG ON MB.MEMBGROUP_CODE = MBG.MEMBGROUP_CODE
										WHERE mb.member_no = :member_no");
$fetchName->execute([
	':member_no' => $member_no
]);
$rowName = $fetchName->fetch(PDO::FETCH_ASSOC);
$header["fullname"] = $rowName["PRENAME_DESC"].$rowName["MEMB_NAME"].' '.$rowName["MEMB_SURNAME"];
$header["member_group"] = $rowName["MEMBGROUP_CODE"].' '.$rowName["MEMBGROUP_DESC"];

$perid_no = $arrayGroupPeriod[0]["PERIOD"]??NULL;

if(isset($seq_no) && $seq_no !=''){
	$getPaymentDetail = $conoracle->prepare("SELECT 
											CASE kut.system_code 
											WHEN 'LON' THEN ISNULL(LT.LOANTYPE_DESC,KUT.KEEPITEMTYPE_DESC)
											WHEN 'DEP' THEN ISNULL(DP.DEPTTYPE_DESC,KUT.KEEPITEMTYPE_DESC)
											ELSE kut.keepitemtype_desc
											END as TYPE_DESC,
											kut.keepitemtype_grp as TYPE_GROUP,
											kpd.MONEY_RETURN_STATUS,
											kpd.ADJUST_ITEMAMT,
											kpd.ADJUST_PRNAMT,
											kpd.ADJUST_INTAMT,
											case kut.keepitemtype_grp 
												WHEN 'DEP' THEN kpd.description
												WHEN 'LON' THEN kpd.loancontract_no
											ELSE kpd.description END as PAY_ACCOUNT,
											kpd.PERIOD,
											ISNULL(kpd.ITEM_PAYMENT * kut.SIGN_FLAG,0) AS ITEM_PAYMENT,
											ISNULL(kpd.ITEM_BALANCE,0) AS ITEM_BALANCE,
											ISNULL(kpd.principal_payment,0) AS PRN_BALANCE,
											ISNULL(kpd.interest_payment,0) AS INT_BALANCE
											FROM kpmastreceivedet kpd LEFT JOIN KPUCFKEEPITEMTYPE kut ON 
											kpd.keepitemtype_code = kut.keepitemtype_code
											LEFT JOIN lnloantype lt ON kpd.shrlontype_code = lt.loantype_code
											LEFT JOIN dpdepttype dp ON kpd.shrlontype_code = dp.depttype_code
											WHERE kpd.member_no = :member_no and kpd.recv_period = :recv_period
											and kpd.seq_no = :seq_no
											ORDER BY kut.SORT_IN_RECEIVE ASC");
	$getPaymentDetail->execute([
		':member_no' => $member_no,
		':recv_period' => $perid_no,
		':seq_no' => $seq_no
	]);
}else{
	$getPaymentDetail = $conoracle->prepare("SELECT 
												CASE kut.keepitemtype_code 
												WHEN 'LON' THEN NVL(lt.LOANTYPE_DESC,kut.keepitemtype_desc) 
												WHEN 'DEP' THEN NVL(dp.DEPTTYPE_DESC,kut.keepitemtype_desc) 
												ELSE kut.keepitemtype_desc
												END as TYPE_DESC,
												kut.keepitemtype_grp as TYPE_GROUP,
												'1' as MONEY_RETURN_STATUS,
												kpd.ADJUST_ITEMAMT,
												kpd.ADJUST_PRNAMT,
												kpd.ADJUST_INTAMT,
												case kut.keepitemtype_grp 
													WHEN 'DEP' THEN kpd.description
													WHEN 'LON' THEN kpd.loancontract_no
												ELSE kpd.description END as PAY_ACCOUNT,
												kpd.period,
												NVL(kpd.ITEM_PAYMENT * kut.SIGN_FLAG,0) AS ITEM_PAYMENT,
												NVL(kpd.PRINCIPAL_BALANCE,0) AS ITEM_BALANCE,
												NVL(kpd.principal_payment,0) AS PRN_BALANCE,
												NVL(kpd.interest_payment,0) AS INT_BALANCE
												FROM kpmastreceivedet kpd LEFT JOIN KPUCFKEEPITEMTYPE kut ON 
												kpd.keepitemtype_code = kut.keepitemtype_code
												LEFT JOIN mbmembmaster mb ON kpd.member_no = mb.member_no
												LEFT JOIN lnloantype lt ON kpd.shrlontype_code = lt.loantype_code
												LEFT JOIN dpdepttype dp ON kpd.shrlontype_code = dp.depttype_code
												and dp.membcat_code = mb.membcat_code
											WHERE kpd.member_no = :member_no and kpd.recv_period = :recv_period
											ORDER BY kut.SORT_IN_RECEIVE ASC");
	$getPaymentDetail->execute([
		':member_no' => $member_no,
		':recv_period' => $perid_no
	]);	
}

$arrGroupDetail = array();
while($rowDetail = $getPaymentDetail->fetch(PDO::FETCH_ASSOC)){
	$arrDetail = array();
	$arrDetail["TYPE_DESC"] = $rowDetail["TYPE_DESC"];
	if($rowDetail["TYPE_GROUP"] == 'SHR'){
		$arrDetail["PERIOD"] = $rowDetail["PERIOD"];
	}else if($rowDetail["TYPE_GROUP"] == 'LON'){
		$arrDetail["PAY_ACCOUNT"] = $rowDetail["PAY_ACCOUNT"];
		$arrDetail["PAY_ACCOUNT_LABEL"] = 'เลขสัญญา';
		$arrDetail["PERIOD"] = $rowDetail["PERIOD"];
		if($rowDetail["MONEY_RETURN_STATUS"] == '-99' || $rowDetail["ADJUST_ITEMAMT"] > 0){
			$arrDetail["PRN_BALANCE"] = number_format($rowDetail["ADJUST_PRNAMT"],2);
			$arrDetail["INT_BALANCE"] = number_format($rowDetail["ADJUST_INTAMT"],2);
		}else{
			$arrDetail["PRN_BALANCE"] = number_format($rowDetail["PRN_BALANCE"],2);
			$arrDetail["INT_BALANCE"] = number_format($rowDetail["INT_BALANCE"],2);
		}
	}else if($rowDetail["TYPE_GROUP"] == 'DEP'){
		$arrDetail["PAY_ACCOUNT"] = $lib->formataccount($rowDetail["PAY_ACCOUNT"],$func->getConstant('dep_format'));
		$arrDetail["PAY_ACCOUNT_LABEL"] = 'เลขบัญชี';
	}else if($rowDetail["TYPE_GROUP"] == "OTH"){
		$arrDetail["PAY_ACCOUNT"] = $rowDetail["PAY_ACCOUNT"];
		$arrDetail["PAY_ACCOUNT_LABEL"] = 'จ่าย';
	}
	if($rowDetail["MONEY_RETURN_STATUS"] == '-99' || $rowDetail["ADJUST_ITEMAMT"] > 0){
		$arrDetail["ITEM_PAYMENT"] = number_format($rowDetail["ADJUST_ITEMAMT"],2);
		$arrDetail["ITEM_PAYMENT_NOTFORMAT"] = $rowDetail["ADJUST_ITEMAMT"];
	}else{
		$arrDetail["ITEM_PAYMENT"] = number_format($rowDetail["ITEM_PAYMENT"],2);
		$arrDetail["ITEM_PAYMENT_NOTFORMAT"] = $rowDetail["ITEM_PAYMENT"];
	}
	if($rowDetail["ITEM_BALANCE"] > 0){
		$arrDetail["ITEM_BALANCE"] = number_format($rowDetail["ITEM_BALANCE"],2);
	}
	$arrGroupDetail[] = $arrDetail;
}
$getDetailKPHeader = $conoracle->prepare("SELECT 
											kpd.RECEIPT_NO,
											kpd.RECEIPT_DATE as OPERATE_DATE,
											kpd.KEEPING_STATUS
											FROM kpmastreceive kpd
										WHERE kpd.member_no = :member_no and kpd.recv_period = :recv_period");
$getDetailKPHeader->execute([
	':member_no' => $member_no,
	':recv_period' => $perid_no
]);
$rowKPHeader = $getDetailKPHeader->fetch(PDO::FETCH_ASSOC);
$header["keeping_status"] = $rowKPHeader["KEEPING_STATUS"];
$header["recv_period"] = $lib->convertperiodkp(TRIM($perid_no));
$header["member_no"] = $data;
$header["receipt_no"] = TRIM($rowKPHeader["RECEIPT_NO"]);
$header["operate_date"] = $lib->convertdate($rowKPHeader["OPERATE_DATE"],'D m Y');
?>