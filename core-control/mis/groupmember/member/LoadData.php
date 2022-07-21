<?php
require_once('../../../autoload.php');

$arrayResult["STATUS"] = "Hellow";
$arrayResult["MESSAGE"] = "MESSAGE";
$arrayResult["RESULT"] = TRUE;
echo json_encode($arrayResult);
exit();


if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','manageuseraccount')){
		$arrayGroup = array();
		$fetchUserAccount = $conoracle->prepare("SELECT member_no, phone_number, email, register_date, register_channel, account_status, user_type
												FROM gcmemberaccount WHERE user_type IN('0','1')");
		$fetchUserAccount->execute();
		while($rowUserlogin = $fetchUserAccount->fetch()){
			$arrGroupUserAcount = array();
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
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>