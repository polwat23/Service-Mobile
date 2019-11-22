<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['section_system','username'],$payload) && $lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload["section_system"],'mobileadmin',$conmysql)){
		$arrayGroup = array();
		$fetchUserlogin = $conmysql->prepare("SELECT member_no, device_name, login_date, channel 
												FROM  gcuserlogin WHERE is_login = '1' ");
		$fetchUserlogin->execute();
		while($rowUserlogin = $fetchUserlogin->fetch()){
			$arrGroupRootUseronline = array();
			$arrGroupRootUseronline["MEMBER_NO"] = $rowUserlogin["member_no"];
			$arrGroupRootUseronline["DEVICE"] = $rowUserlogin["device_name"];
			$arrGroupRootUseronline["LOGIN_DATE"] = $lib->convertdate($rowUserlogin["login_date"],'d m Y',true);
			$arrGroupRootUseronline["CHANNEL"] = $rowUserlogin["channel"];
			$arrayGroup[] = $arrGroupRootUseronline;
		}
		$arrayResult["USER_ONLINE"] = $arrayGroup;
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