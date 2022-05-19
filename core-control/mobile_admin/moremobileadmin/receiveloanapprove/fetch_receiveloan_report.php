<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','repayloanapprove')){
		$fetchRepayLoan = $conmysql->prepare("SELECT id_receiveloan, member_no, loancontract_no,deptaccount_no,principal_balance, amount_receive, withdrawable_amt,calint_from,receive_date, 
											 receive_stauts ,payload,is_bank
											 FROM gcreceiveloanod WHERE receive_stauts= '1'
											 ORDER BY receive_date DESC ");
		$fetchRepayLoan->execute();
		$arrayRepayLoan = array();
		while($rowRepayloan = $fetchRepayLoan->fetch(PDO::FETCH_ASSOC)){
			$arrayRepay = array();
			$getMember = $conoracle->prepare("SELECT mp.PRENAME_DESC,mb.MEMB_NAME,mb.MEMB_SURNAME 
											 FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code  
											 WHERE mb.member_no  = :member_no");
			$getMember->execute([':member_no' => $rowRepayloan["member_no"]]);
			$rowMember = $getMember->fetch(PDO::FETCH_ASSOC);
			$arrayRepay["ID_RECEIVELOAN"] = $rowRepayloan["id_receiveloan"] ?? null;
			$arrayRepay["MEMBER_NO"] = $rowRepayloan["member_no"] ?? null;
			$arrayRepay["FULLNAME"] = $rowMember["PRENAME_DESC"].$rowMember["MEMB_NAME"].' '.$rowMember["MEMB_SURNAME"];
			$arrayRepay["LOANCONTRACT_NO"] = $rowRepayloan["loancontract_no"] ?? null;			
			$arrayRepay["DEPTACCOUNT_NO"] = $rowRepayloan["deptaccount_no"] ?? null;		
			$arrayRepay["AMOUNT_RECEIVE"] = number_format($rowRepayloan["amount_receive"],2)?? null;
			$arrayRepay["AMOUNT_RECEIVE_FORMAT"] = number_format($rowRepayloan["amount_receive"],2)?? null;
			$arrayRepay["AMOUNT_RECEIVE"] = $rowRepayloan["amount_receive"];
			$arrayRepay["WITHDRAWABLE_AMT"] = number_format($rowRepayloan["withdrawable_amt"],2)?? null;
			$arrayRepay["PRINCIPAL_BALANCE"] = number_format($rowRepayloan["principal_balance"],2)?? null;
			$arrayRepay["RECEIVE_STAUTS"] = $rowRepayloan["receive_stauts"];
			$arrayRepay["IS_BANK"] = $rowRepayloan["is_bank"]=="1"?"ธนาคารกรุงไทย":"ภายใน";
			$arrayRepay["RECEIVE_DATE"] = $lib->convertdate($rowRepayloan["receive_date"],'d m Y',true);
			$arrayRepay["CALINT_FROM"] = $lib->convertdate($rowRepayloan["calint_from"],'d m Y',true);
			$arrayRepayLoan[] = $arrayRepay;
		}
		
		$arrayResult["RECEIVE_LOAN_REPORT"] = $arrayRepayLoan;
		$arrayResult["RESULT"] = TRUE;
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