<?php
require_once('../../autoload.php');
if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin',null)){
		$arrayGroup = array();
		$arrGroupMonth = array();
		$arrayRegis = array();
		$arrayNotRegis = array();
		
		//web
		$fetchUserloginWeb = $conmysql->prepare("SELECT COUNT(member_no) AS c_user_login_web FROM gcuserlogin WHERE is_login = '1' AND channel = 'web'");
		$fetchUserloginWeb->execute();
	    $rowUserloginWeb = $fetchUserloginWeb->fetch(PDO::FETCH_ASSOC);
		
		//mobile_app
		$fetchUserloginMobile = $conmysql->prepare("SELECT COUNT(member_no) AS c_user_login_mobile FROM gcuserlogin WHERE is_login = '1' AND channel = 'mobile_app'");
		$fetchUserloginMobile->execute();
	    $rowUserloginMobile = $fetchUserloginMobile->fetch(PDO::FETCH_ASSOC);
		
		$fetchUserNotRegis = $conmssql->prepare("SELECT member_no FROM mbmembmaster WHERE resign_status = '0' ");
		$fetchUserNotRegis->execute();
		$rowUserNotRegis = $fetchUserNotRegis->fetch(PDO::FETCH_ASSOC);
		while($rowUserNotRegis = $fetchUserNotRegis->fetch(PDO::FETCH_ASSOC)){
			$arrayNotRegis[] = $rowUserNotRegis["member_no"];
		}
		$countRegis = count($arrayRegis);
		
		$fetchUserRegis = $conmysql->prepare("SELECT member_no FROM gcmemberaccount WHERE member_no NOT IN('dev@mode','etnmode1','etnmode2','etnmode3','etnmode4','salemode')");									
		$fetchUserRegis->execute();
		while($rowUserRegis = $fetchUserRegis->fetch(PDO::FETCH_ASSOC)){
			if(array_search($rowUserRegis["member_no"],$arrayNotRegis) > -1){
				$arrayRegis[] = $rowUserRegis["member_no"];
			}
		}
		$countRegis = count($arrayRegis);
		$countNotRegis = count($arrayNotRegis);
		
		$arrGroupRootUserlogin = array();
		$arrGroupRootUserlogin["USER_LOGIN_TODAY"] = number_format($rowUserloginWeb["c_user_login_web"] + $rowUserloginMobile["c_user_login_mobile"],0);
		$arrGroupRootUserlogin["USER_LOGIN_TODAY_WEB"] = number_format($rowUserloginWeb["c_user_login_web"],0);
		$arrGroupRootUserlogin["USER_LOGIN_TODAY_MOBILE"] = number_format($rowUserloginMobile["c_user_login_mobile"],0);
		$arrGroupRootUserlogin["USER_NOT_REGISTER"] = number_format($countNotRegis - $countRegis,0);
		$arrGroupRootUserlogin["USER_REGISTER"] = number_format($countRegis,0);


		$arrayGroup[] = $arrGroupRootUserlogin;	
		$arrayResult["USER_LOGIN_LOGOUT_DATA"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		require_once('../../../include/exit_footer.php');
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../include/exit_footer.php');
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../include/exit_footer.php');
}
?>
