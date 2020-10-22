<?php
require_once('../../autoload.php');
if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin',null)){
		$arrayGroup = array();
		$arrGroupMonth = array();
		
		//web
		$fetchUserloginWeb = $conmysql->prepare("SELECT COUNT(member_no) as SUM_LOGIN_WEB FROM gcmemberaccount where pin IS NULL and member_no NOT IN('dev@mode','salemode','etnmode1','etnmode2','etnmode3','etnmode4') and account_status IN('1','-8','-9')");
		$fetchUserloginWeb->execute();
	    $rowUserloginWeb = $fetchUserloginWeb->fetch(PDO::FETCH_ASSOC);
		
		//mobile_app
		$fetchUserloginMobile = $conmysql->prepare("SELECT COUNT(member_no) as SUM_LOGIN_MOBILE FROM gcmemberaccount where pin IS NOT NULL and member_no NOT IN('dev@mode','salemode','etnmode1','etnmode2','etnmode3','etnmode4') and account_status IN('1','-8','-9')");
		$fetchUserloginMobile->execute();
	    $rowUserloginMobile = $fetchUserloginMobile->fetch(PDO::FETCH_ASSOC);
		
		$fetchUserNotRegis = $conoracle->prepare("SELECT COUNT(member_no) AS C_USERNOTREGIS FROM shsharemaster WHERE sharestk_amt > 0 ");
		$fetchUserNotRegis->execute();
		$rowUserNotRegis = $fetchUserNotRegis->fetch(PDO::FETCH_ASSOC);
		
		$fetchUserRegis = $conmysql->prepare("SELECT COUNT(member_no) AS c_userregit FROM gcmemberaccount WHERE 
																member_no NOT IN('dev@mode','etnmode1','etnmode2','etnmode3','etnmode4','salemode') and account_status IN('1','-8','-9')");									
		$fetchUserRegis->execute();
		$rowUserRegis = $fetchUserRegis->fetch(PDO::FETCH_ASSOC);
		
		$arrGroupRootUserlogin = array();
		$arrGroupRootUserlogin["USER_LOGIN_TODAY"] = number_format($rowUserloginWeb["SUM_LOGIN_WEB"] + $rowUserloginMobile["SUM_LOGIN_MOBILE"],0);
		$arrGroupRootUserlogin["USER_LOGIN_TODAY_WEB"] = number_format($rowUserloginWeb["SUM_LOGIN_WEB"],0);
		$arrGroupRootUserlogin["USER_LOGIN_TODAY_MOBILE"] = number_format($rowUserloginMobile["SUM_LOGIN_MOBILE"],0);
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