<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','usernotregistered')){
		$arrayUserRegister = array();
		$fetchUserAccount = $conmysql->prepare("SELECT member_no FROM gcmemberaccount");
		$fetchUserAccount->execute();
		while($rowUserRegis = $fetchUserAccount->fetch()){
			$arrayUserRegister[] = $rowUserRegis["member_no"];
		}
		$arrayGroup = array();
		if(sizeof($arrayUserRegister) > 0){
			$fetchUserNotRegis = $conoracle->prepare("SELECT mb.member_no,mp.prename_desc,mb.memb_name,mb.memb_surname,mb.member_date
													,mb.mem_telmobile,mb.email FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
													WHERE mb.resign_status = '0' and mb.member_no NOT IN('".implode("','",$arrayUserRegister)."')");
		}else{
			$fetchUserNotRegis = $conoracle->prepare("SELECT mb.member_no,mp.prename_desc,mb.memb_name,mb.memb_surname,mb.member_date
													,mb.mem_telmobile,mb.email FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
													WHERE mb.resign_status = '0'");
		}
		$fetchUserNotRegis->execute();
		while($rowUserNotRegis = $fetchUserNotRegis->fetch()){
			$arrayUserNotRegister = array();
			$arrayUserNotRegister["MEMBER_NO"] = $rowUserNotRegis["MEMBER_NO"];
			$arrayUserNotRegister["NAME"] = $rowUserNotRegis["PRENAME_DESC"].$rowUserNotRegis["MEMB_NAME"]." ".$rowUserNotRegis["MEMB_SURNAME"];
			$arrayUserNotRegister["MEMBER_DATE"] = $lib->convertdate($rowUserNotRegis["MEMBER_DATE"],'D m Y');
			$arrayUserNotRegister["TEL"] = $lib->formatphone($rowUserNotRegis["MEM_TELMOBILE"],'-');
			$arrayUserNotRegister["EMAIL"] = $rowUserNotRegis["EMAIL"];
			$arrayGroup[] = $arrayUserNotRegister;
		}
		$arrayResult["USER_NOT_REGISTER"] = $arrayGroup;
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