<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','approveusermobile')){
		$arrayAccount = array();
		$fetchAccount = $conmysql->prepare("SELECT gc.member_no, gc.ref_memno, gc.acc_name, gc.acc_surname, gc.account_status, 
											gm.prename_desc ,gm.memb_name ,gc.email
											FROM gcmemberaccount gc LEFT JOIN gcmembonlineregis gm ON gc.ref_memno = gm.member_no 
											where  gc.account_status = '8' ");
		$fetchAccount->execute();
		while($rowUser = $fetchAccount->fetch(PDO::FETCH_ASSOC)){
			$arrUserAcount = array();
			$arrUserAcount["REF_MEMNO"] = $rowUser["ref_memno"];
			$arrUserAcount["MEMBER_NO"] = $rowUser["member_no"];
			$arrUserAcount["NAME"] = $rowUser["acc_name"].' '.$rowUser["acc_surname"];
			$arrUserAcount["COOP_NAME"] = $rowUser["prename_desc"].''.$rowUser["memb_name"];
			$arrUserAcount["ACCOUNT_STATUS"] = $rowUser["account_status"];
			$arrUserAcount["EMAIL"] = $rowUser["email"];
			//$arrUserAcount["REGISTER_DATE"] = $lib->convertdate($rowUser["register_date"],'d m Y',true);
			$arrayAccount[] = $arrUserAcount;
		}
		$arrayResult["USER_APV"] = $arrayAccount;		
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