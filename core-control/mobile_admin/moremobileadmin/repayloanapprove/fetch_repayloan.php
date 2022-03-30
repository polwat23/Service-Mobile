<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','repayloanapprove')){
		$fetchRepayLoan = $conmysql->prepare("SELECT id_repayloan, loancontract_no, amount, operate_date, repayloan_status, member_no 
											  FROM gcrepayloan WHERE repayloan_status= '8'");
		$fetchRepayLoan->execute();
		$arrayRepayLoan = array();
		while($rowRepayloan = $fetchRepayLoan->fetch(PDO::FETCH_ASSOC)){
			$arrayRepay = array();
			$getMember = $conoracle->prepare("SELECT mp.PRENAME_DESC,mb.MEMB_NAME,mb.MEMB_SURNAME 
											 FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code  
											 WHERE mb.member_no  = :member_no");
			$getMember->execute([':member_no' => $rowRepayloan["member_no"]]);
			$rowMember = $getMember->fetch(PDO::FETCH_ASSOC);
			$arrayRepay["ID_REPAYLOAN"] = $rowRepayloan["id_repayloan"] ?? null;
			$arrayRepay["MEMBER_NO"] = $rowRepayloan["member_no"] ?? null;
			$arrayRepay["FULLNAME"] = $rowMember["PRENAME_DESC"].$rowMember["MEMB_NAME"].' '.$rowMember["MEMB_SURNAME"];
			$arrayRepay["LOANCONTRACT_NO"] = $rowRepayloan["loancontract_no"] ?? null;
			$arrayRepay["AMOUNT"] = number_format($rowRepayloan["amount"],2)?? null;
			$arrayRepay["REPAYLOAN_STATUS"] = $rowRepayloan["repayloan_status"];
			$arrayRepay["OPERATE_DATE"] = $lib->convertdate($rowRepayloan["operate_date"],'d m Y',true);
			$arrayRepayLoan[] = $arrayRepay;
		}
		
		$arrayResult["REPAY_LOAN"] = $arrayRepayLoan;
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