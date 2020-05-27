<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','userlogin')){
		$arrayGroup = array();
		$fetchUserlogin = $conmysql->prepare("SELECT member_no, device_name, login_date, id_token 
												FROM  gcuserlogin WHERE is_login = '1' ORDER BY id_userlogin DESC");
		$fetchUserlogin->execute();
		while($rowUserlogin = $fetchUserlogin->fetch(PDO::FETCH_ASSOC)){
			$arrGroupRootUserlogin = array();
			$arrGroupRootUserlogin["MEMBER_NO"] = $rowUserlogin["member_no"];
			$arrGroupRootUserlogin["DEVICE"] = $rowUserlogin["device_name"];
			$arrGroupRootUserlogin["LOGIN_DATE"] = $lib->convertdate($rowUserlogin["login_date"],'d m Y',true);
			$arrGroupRootUserlogin["ID_TOKEN"] = $rowUserlogin["id_token"];
			$arrayGroup[] = $arrGroupRootUserlogin;
		}
		$arrayResult["USER_LOGIN"] = $arrayGroup;
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