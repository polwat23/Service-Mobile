<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','manageuseraccount')){
		$arrayGroup = array();
		$fetchUserAccount = $conoracle->prepare("SELECT member_no, phone_number, email, register_date, register_channel, account_status, user_type
												FROM gcmemberaccount WHERE user_type IN('0','1') ORDER BY register_date DESC");
		$fetchUserAccount->execute();
		while($rowUserlogin = $fetchUserAccount->fetch(PDO::FETCH_ASSOC)){
			$arrGroupUserAcount = array();
			$arrGroupUserAcount["MEMBER_NO"] = $rowUserlogin["MEMBER_NO"];
			$arrGroupUserAcount["TEL"] = $lib->formatphone($rowUserlogin["PHONE_NUMBER"]);
			$arrGroupUserAcount["EMAIL"] = $rowUserlogin["EMAIL"];
			$arrGroupUserAcount["REGISTERDATE"] = $lib->convertdate($rowUserlogin["REGISTER_DATE"],'d m Y',true);
			$arrGroupUserAcount["REGISTERCHANNEL"] = $rowUserlogin["REGISTER_CHANNEL"];
			$arrGroupUserAcount["ACCOUTSTATUS"] = $rowUserlogin["ACCOUNT_STATUS"];
			$arrGroupUserAcount["ACCOUTTYPE"] = $rowUserlogin["USER_TYPE"];
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