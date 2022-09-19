<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','manageuseraccount')){
		$arrayGroup = array();
		$arrayMembName = array();
		$fetchUser= $conoracle->prepare("SELECT  MB.MEMBER_NO ,MP.PRENAME_DESC,MB.MEMB_NAME,MB.MEMB_SURNAME 
										FROM MBMEMBMASTER MB LEFT JOIN MBUCFPRENAME MP ON MB.PRENAME_CODE = MP.PRENAME_CODE");
		$fetchUser->execute();
		while($rowUser = $fetchUser->fetch(PDO::FETCH_ASSOC)){
			$arrayMembName[$rowUser["MEMBER_NO"]] = $rowUser["PRENAME_DESC"].''.$rowUser["MEMB_NAME"].' '.$rowUser["MEMB_SURNAME"];
		}

		$fetchUserAccount = $conmysql->prepare("SELECT member_no, phone_number, email, register_date, register_channel, account_status, user_type
												FROM gcmemberaccount WHERE user_type IN('0','1') ORDER BY register_date DESC");
		$fetchUserAccount->execute();
		while($rowUserlogin = $fetchUserAccount->fetch(PDO::FETCH_ASSOC)){
			$arrGroupUserAcount = array();
			$arrGroupUserAcount["FULL_NAME"] = $arrayMembName[$rowUserlogin["member_no"]];
			$arrGroupUserAcount["MEMBER_NO"] = $rowUserlogin["member_no"];
			$arrGroupUserAcount["TEL"] = $lib->formatphone($rowUserlogin["phone_number"]);
			$arrGroupUserAcount["EMAIL"] = $rowUserlogin["email"];
			$arrGroupUserAcount["REGISTERDATE"] = $lib->convertdate($rowUserlogin["register_date"],'d m Y',true);
			$arrGroupUserAcount["REGISTERCHANNEL"] = $rowUserlogin["register_channel"];
			$arrGroupUserAcount["ACCOUTSTATUS"] = $rowUserlogin["account_status"];
			$arrGroupUserAcount["ACCOUTTYPE"] = $rowUserlogin["user_type"];
			$arrayGroup[] = $arrGroupUserAcount;
		}
		$arrayResult["USER_ACCOUNT"] = $arrayGroup;
		$arrayResult["fetchUser"] = $fetchUser->fetch(PDO::FETCH_ASSOC);
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