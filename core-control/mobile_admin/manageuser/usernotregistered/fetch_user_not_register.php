<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','usernotregistered')){
		$arrayUserRegister = array();
		$fetchUserAccount = $conmysql->prepare("SELECT member_no FROM gcmemberaccount");
		$fetchUserAccount->execute();
		while($rowUserRegis = $fetchUserAccount->fetch(PDO::FETCH_ASSOC)){
			$arrayUserRegister[] = $rowUserRegis["member_no"];
		}
		$arrayGroup = array();
		if(sizeof($arrayUserRegister) > 0){
			$fetchUserNotRegis = $conoracle->prepare("SELECT mb.member_no,mp.prename_desc,mb.memb_name,mb.memb_surname,mb.member_date
													,mb.sms_mobilephone,mb.addr_email FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
													WHERE mb.resign_status = '0' and mb.member_no NOT IN('".implode("','",$arrayUserRegister)."')");
		}else{
			$fetchUserNotRegis = $conoracle->prepare("SELECT mb.member_no,mp.prename_desc,mb.memb_name,mb.memb_surname,mb.member_date
													,mb.sms_mobilephone,mb.addr_email FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
													WHERE mb.resign_status = '0'");
		}
		$fetchUserNotRegis->execute();
		while($rowUserNotRegis = $fetchUserNotRegis->fetch(PDO::FETCH_ASSOC)){
			$arrayUserNotRegister = array();
			$arrayUserNotRegister["MEMBER_NO"] = $rowUserNotRegis["MEMBER_NO"];
			$arrayUserNotRegister["NAME"] = $rowUserNotRegis["PRENAME_DESC"].$rowUserNotRegis["MEMB_NAME"]." ".$rowUserNotRegis["MEMB_SURNAME"];
			$arrayUserNotRegister["MEMBER_DATE"] = $lib->convertdate($rowUserNotRegis["MEMBER_DATE"],'D m Y');
			$arrayUserNotRegister["TEL"] = $lib->formatphone($rowUserNotRegis["SMS_MOBILEPHONE"],'-');
			$arrayUserNotRegister["EMAIL"] = $rowUserNotRegis["ADDR_EMAIL"] ?? "-";
			$arrayGroup[] = $arrayUserNotRegister;
		}
		$arrayResult["USER_NOT_REGISTER"] = $arrayGroup;
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