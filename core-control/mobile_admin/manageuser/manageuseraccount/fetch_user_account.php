<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','manageuseraccount')){
		$arrayGroup = array();
		$fetchUserAccount = $conmysql->prepare("SELECT gc.member_no, gc.ref_memno, gc.phone_number, gc.email, gc.register_date, gc.register_channel, 
												gc.account_status, gc.user_type ,gs.prename_desc , gs.memb_name
												FROM gcmembonlineregis gs  LEFT JOIN gcmemberaccount gc ON  gs.member_no = gc.ref_memno
												WHERE gc.user_type IN('0','1') 
												ORDER BY gc.register_date DESC");
		$fetchUserAccount->execute();
		while($rowUserlogin = $fetchUserAccount->fetch(PDO::FETCH_ASSOC)){
			$arrGroupUserAcount = array();
			$memberInfo = $conoracle->prepare("SELECT addr_email FROM mbmembmaster 
												WHERE  member_no = :member_no");
			$memberInfo->execute([':member_no' => $rowUserlogin["member_no"]]);
			$rowMember = $memberInfo->fetch(PDO::FETCH_ASSOC);		
			$arrGroupUserAcount["MEMBER_NO"] = $rowUserlogin["member_no"];
			$arrGroupUserAcount["REF_MEMNO"] = $rowUserlogin["ref_memno"];
			$arrGroupUserAcount["COOP_NAME"] = $rowUserlogin["prename_desc"].$rowUserlogin["memb_name"];
			$arrGroupUserAcount["TEL"] = $lib->formatphone($rowUserlogin["phone_number"]);
			$arrGroupUserAcount["EMAIL"] = $rowMember["ADDR_EMAIL"];
			$arrGroupUserAcount["REGISTERDATE"] = $lib->convertdate($rowUserlogin["register_date"],'d m Y',true);
			$arrGroupUserAcount["REGISTERCHANNEL"] = $rowUserlogin["register_channel"];
			$arrGroupUserAcount["ACCOUTSTATUS"] = $rowUserlogin["account_status"];
			$arrGroupUserAcount["ACCOUTTYPE"] = $rowUserlogin["user_type"];
			$arrayGroup[] = $arrGroupUserAcount;
		}
		$arrayResult["USER_ACCOUNT"] = $arrayGroup;
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