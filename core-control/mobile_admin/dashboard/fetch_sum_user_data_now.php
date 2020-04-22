<?php
require_once('../../autoload.php');
if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin',null)){
		$arrayGroup = array();
		
		
		$arrGroupMonth = array();
		$fetchUserlogin = $conmysql->prepare("SELECT COUNT(member_no) AS c_user_login FROM gcuserlogin WHERE is_login = '1'");
		$fetchUserlogin->execute();
	    $rowUserlogin = $fetchUserlogin->fetch(PDO::FETCH_ASSOC);
		
		$fetchUserNotRegis = $conoracle->prepare("SELECT COUNT(mb.member_no) AS c_usernotregis , mb.member_no,mp.prename_desc,mb.memb_name,mb.memb_surname,mb.member_date
													,mb.mem_telmobile,mb.email FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
													WHERE mb.resign_status = '0'");
													
		$fetchUserRegis = $conmysql->prepare("SELECT COUNT(member_no) AS c_userregit FROM gcmemberaccount");									
		$fetchUserRegis->execute();
		$rowUserRegis = $fetchUserRegis->fetch(PDO::FETCH_ASSOC);
		
		$fetchUserNotRegis->execute();
		$rowUserNotRegis = $fetchUserNotRegis->fetch(PDO::FETCH_ASSOC);
		$arrGroupRootUserlogin = array();
		
		$arrGroupRootUserlogin["USER_LOGIN_TODAY"] = $rowUserlogin["c_user_login"]??0;
		$arrGroupRootUserlogin["USER_NOT_REGISTER"] = $rowUserNotRegis["c_usernotregis"]??0;
	$arrGroupRootUserlogin["USER_REGISTER"] = $rowUserRegis["c_userregit"]??0;


		$arrayGroup[] = $arrGroupRootUserlogin;	
		$arrayResult["USER_LOGIN_LOGOUT_DATA"] = $arrayGroup;
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