<?php
require_once('../../autoload.php');
if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
		$arrayGroup = array();
		$arrGroupMonth = array();
		$arrGroupRootUserlogin = array();	
		//web
		/*$fetchUserloginWeb = $conoracle->prepare("SELECT COUNT(member_no) AS c_user_login_web FROM gcuserlogin WHERE is_login = '1' AND channel = 'web'");
		$fetchUserloginWeb->execute();
	    $rowUserloginWeb = $fetchUserloginWeb->fetch(PDO::FETCH_ASSOC);
		
		//mobile_app
		$fetchUserloginMobile = $conoracle->prepare("SELECT COUNT(member_no) AS c_user_login_mobile FROM gcuserlogin WHERE is_login = '1' AND channel = 'mobile_app'");
		$fetchUserloginMobile->execute();
	    $rowUserloginMobile = $fetchUserloginMobile->fetch(PDO::FETCH_ASSOC);
		
		$fetchUserNotRegis = $conoracle->prepare("SELECT COUNT(member_no) AS C_USERNOTREGIS FROM mbmembmaster WHERE resign_status = '0' ");
		$fetchUserNotRegis->execute();
		$rowUserNotRegis = $fetchUserNotRegis->fetch(PDO::FETCH_ASSOC);
		
		$fetchUserRegis = $conoracle->prepare("SELECT COUNT(member_no) AS c_userregit FROM gcmemberaccount WHERE member_no NOT IN('dev@mode','etnmode1','etnmode2','etnmode3','etnmode4','salemode')");									
		$fetchUserRegis->execute();
		$rowUserRegis = $fetchUserRegis->fetch(PDO::FETCH_ASSOC);
		
		$arrGroupRootUserlogin["USER_LOGIN_TODAY"] = number_format($rowUserloginWeb["C_USER_LOGIN_WEB"] + $rowUserloginMobile["C_USER_LOGIN_MOBILE"],0);
		$arrGroupRootUserlogin["USER_LOGIN_TODAY_WEB"] = number_format($rowUserloginWeb["C_USER_LOGIN_WEB"],0);
		$arrGroupRootUserlogin["USER_LOGIN_TODAY_MOBILE"] = number_format($rowUserloginMobile["C_USER_LOGIN_MOBILE"],0);
		$arrGroupRootUserlogin["USER_NOT_REGISTER"] = number_format($rowUserNotRegis["C_USERNOTREGIS"] - $rowUserRegis["C_USERREGIT"],0);
		$arrGroupRootUserlogin["USER_REGISTER"] = number_format($rowUserRegis["C_USERREGIT"],0);*/


		$arrayGroup[] = $arrGroupRootUserlogin;	
		$arrayResult["USER_LOGIN_LOGOUT_DATA"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		require_once('../../../include/exit_footer.php');
	
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../include/exit_footer.php');
	
}
?>
