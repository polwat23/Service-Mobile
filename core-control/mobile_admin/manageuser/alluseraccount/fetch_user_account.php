<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','allusers')){
		$arrayGroup = array();
		$fetchUserAccount = $conmysql->prepare("SELECT member_no, phone_number, email, register_date, register_channel, account_status 
												FROM gcmemberaccount");
		$fetchUserAccount->execute();
		while($rowUserlogin = $fetchUserAccount->fetch()){
			$arrGroupUserAcount = array();
			$arrGroupUserAcount["MEMBER_NO"] = $rowUserlogin["member_no"];
			$arrGroupUserAcount["TEL"] = $lib->formatphone($rowUserlogin["phone_number"]);
			$arrGroupUserAcount["EMAIL"] = $rowUserlogin["email"];
			$arrGroupUserAcount["REGISTERDATE"] = $lib->convertdate($rowUserlogin["register_date"],'d m Y',true);
			$arrGroupUserAcount["REGISTERCHANNEL"] = $rowUserlogin["register_channel"];
			$arrGroupUserAcount["ACCOUTSTATUS"] = $rowUserlogin["account_status"];
			$arrayGroup[] = $arrGroupUserAcount;
		}
		$arrayResult["USER_ACCOUNT"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE_CODE'] = "4003";
		$arrayResult['RESPONSE_AWARE'] = "permission";
		$arrayResult['RESPONSE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "4004";
	$arrayResult['RESPONSE_AWARE'] = "argument";
	$arrayResult['RESPONSE'] = "Not complete argument";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>