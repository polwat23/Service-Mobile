<?php
require_once('../../autoload.php');
if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin',null)){
		$arrayGroup = array();
		$arrGroupMonth = array();
		$fetchUserlogin = $conmysql->prepare("SELECT COUNT(member_no) AS c_user_login FROM gcuserlogin WHERE is_login = '1'");
		$fetchUserlogin->execute();
	    $rowUserlogin = $fetchUserlogin->fetch(PDO::FETCH_ASSOC);
		
		$fetchUserNotRegis = $conoracle->prepare("SELECT COUNT(member_no) AS C_USERNOTREGIS FROM mbmembmaster WHERE resign_status = '0' ");
		$fetchUserNotRegis->execute();
		$rowUserNotRegis = $fetchUserNotRegis->fetch(PDO::FETCH_ASSOC);
		
		$fetchUserRegis = $conmysql->prepare("SELECT COUNT(member_no) AS c_userregit FROM gcmemberaccount WHERE member_no NOT IN('dev@mode','etnmode1','etnmode2','etnmode3','etnmode4','salemode')");									
		$fetchUserRegis->execute();
		$rowUserRegis = $fetchUserRegis->fetch(PDO::FETCH_ASSOC);
		
		$arrGroupRootUserlogin = array();
		$arrGroupRootUserlogin["USER_LOGIN_TODAY"] = number_format($rowUserlogin["c_user_login"],0);
		$arrGroupRootUserlogin["USER_NOT_REGISTER"] = number_format($rowUserNotRegis["C_USERNOTREGIS"] - $rowUserRegis["c_userregit"],0);
		$arrGroupRootUserlogin["USER_REGISTER"] = number_format($rowUserRegis["c_userregit"],0);


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