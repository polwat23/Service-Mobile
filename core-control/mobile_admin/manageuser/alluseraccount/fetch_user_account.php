<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','allusers',$conoracle)){
		$arrayGroup = array();
		$fetchUserAccount = $conoracle->prepare("SELECT member_no, phone_number, email, register_date, register_channel, account_status 
												FROM gcmemberaccount");
		$fetchUserAccount->execute();
		while($rowUserlogin = $fetchUserAccount->fetch()){
			$arrGroupUserAcount = array();
			$arrGroupUserAcount["MEMBER_NO"] = $rowUserlogin["MEMBER_NO"];
			$arrGroupUserAcount["TEL"] = $lib->formatphone($rowUserlogin["PHONE_NUMBER"]);
			$arrGroupUserAcount["EMAIL"] = $rowUserlogin["EMAIL"];
			$arrGroupUserAcount["REGISTERDATE"] = $lib->convertdate($rowUserlogin["REGISTER_DATE"],'d m Y',true);
			$arrGroupUserAcount["REGISTERCHANNEL"] = $rowUserlogin["REGISTER_CHANNEL"];
			$arrGroupUserAcount["ACCOUTSTATUS"] = $rowUserlogin["ACCOUNT_STATUS"];
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