<?php
require_once('../../autoload.php');
if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin',null)){
		$arrayGroup = array();
		$arrGroupMonth = array();
		
		if(isset($dataComing["register_month"]) && $dataComing["register_month"] != ""){
			$register_month = date('Y-m-d',strtotime('first day of +1 month', strtotime($dataComing["register_month"])));
		}else{
			$register_month = date('Y-m-d',strtotime('first day of +1 month'));
		}
		
		//alluser
		$fetchUserRegis = $conmysql->prepare("SELECT COUNT(member_no) as SUM_REGIS FROM gcmemberaccount where member_no NOT IN('dev@mode','salemode','etnmode1','etnmode2','etnmode3','etnmode4') and account_status IN('1','-8','-9') and register_date < :register_month");
		$fetchUserRegis->execute([
			":register_month" => $register_month
		]);
	    $rowUserRegis = $fetchUserRegis->fetch(PDO::FETCH_ASSOC);
		
		//mobile
		$fetchUserloginMobile = $conmysql->prepare("select COUNT(DISTINCT gu.member_no) as SUM_LOGIN_MOBILE 
															FROM gcuserlogin gu 
															JOIN gcmemberaccount gm ON gm.member_no = gu.member_no 
															WHERE gu.channel = 'mobile_app' AND gu.login_date < :register_month AND gu.member_no NOT IN('dev@mode','salemode','etnmode1','etnmode2','etnmode3','etnmode4')
															AND gm.account_status IN('1','-8','-9') AND pin != ''");
		$fetchUserloginMobile->execute([
			":register_month" => $register_month
		]);
	    $rowUserloginMobile = $fetchUserloginMobile->fetch(PDO::FETCH_ASSOC);
		
		$fetchUserNotRegis = $conoracle->prepare("SELECT COUNT(member_no) AS C_USERNOTREGIS FROM shsharemaster WHERE sharestk_amt > 0 ");
		$fetchUserNotRegis->execute();
		$rowUserNotRegis = $fetchUserNotRegis->fetch(PDO::FETCH_ASSOC);
		
		$arrGroupRootUserlogin = array();
		$arrGroupRootUserlogin["USER_LOGIN_TODAY"] = number_format($rowUserRegis["SUM_REGIS"],0);
		$arrGroupRootUserlogin["USER_LOGIN_TODAY_WEB"] = number_format($rowUserRegis["SUM_REGIS"] - $rowUserloginMobile["SUM_LOGIN_MOBILE"],0);
		$arrGroupRootUserlogin["USER_LOGIN_TODAY_MOBILE"] = number_format($rowUserloginMobile["SUM_LOGIN_MOBILE"],0);
		$arrGroupRootUserlogin["USER_NOT_REGISTER"] = number_format($rowUserNotRegis["C_USERNOTREGIS"] - $rowUserRegis["SUM_REGIS"],0);
		$arrGroupRootUserlogin["USER_REGISTER"] = number_format($rowUserRegis["SUM_REGIS"],0);

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