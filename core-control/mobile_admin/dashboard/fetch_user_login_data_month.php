<?php
require_once('../../autoload.php');
if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin',null)){
		$arrayGroup = array();
		$arrayGroupWeb = array();
		$arrayGroupMobile = array();
		$arrGroupMonth = array();
		$fetchUserlogin = $conmysql->prepare("SELECT COUNT(MEMBER_NO) as C_NAME,DATE_FORMAT(login_date,'%m') as MONTH
			FROM gcuserlogin
				WHERE login_date <= DATE_SUB(login_date,INTERVAL -6 MONTH)
				GROUP BY DATE_FORMAT(login_date,'%m')");
		$fetchUserlogin->execute();
		while($rowUserlogin = $fetchUserlogin->fetch(PDO::FETCH_ASSOC)){
			$arrGroupRootUserlogin = array();
			$arrGroupRootUserlogin["MONTH"] = $rowUserlogin["MONTH"];;
			$arrGroupRootUserlogin["AMT"] = $rowUserlogin["C_NAME"];
			$arrayGroup[] = $arrGroupRootUserlogin;
		}
		
		$fetchUserloginWeb = $conmysql->prepare("SELECT COUNT(MEMBER_NO) as C_NAME,DATE_FORMAT(login_date,'%m') as MONTH
			FROM gcuserlogin
				WHERE login_date <= DATE_SUB(login_date,INTERVAL -6 MONTH) AND channel = 'web'
				GROUP BY DATE_FORMAT(login_date,'%m')");
		$fetchUserloginWeb->execute();
		while($rowUserlogin = $fetchUserloginWeb->fetch(PDO::FETCH_ASSOC)){
			$arrGroupRootUserlogin = array();
			$arrGroupRootUserlogin["MONTH"] = $rowUserlogin["MONTH"];;
			$arrGroupRootUserlogin["AMT"] = $rowUserlogin["C_NAME"];
			$arrayGroupWeb[] = $arrGroupRootUserlogin;
		}
		
		$fetchUserloginMobile = $conmysql->prepare("SELECT COUNT(MEMBER_NO) as C_NAME,DATE_FORMAT(login_date,'%m') as MONTH
			FROM gcuserlogin
				WHERE login_date <= DATE_SUB(login_date,INTERVAL -6 MONTH) AND channel = 'mobile_app'
				GROUP BY DATE_FORMAT(login_date,'%m')");
		$fetchUserloginMobile->execute();
		while($rowUserlogin = $fetchUserloginMobile->fetch(PDO::FETCH_ASSOC)){
			$arrGroupRootUserlogin = array();
			$arrGroupRootUserlogin["MONTH"] = $rowUserlogin["MONTH"];;
			$arrGroupRootUserlogin["AMT"] = $rowUserlogin["C_NAME"];
			$arrayGroupMobile[] = $arrGroupRootUserlogin;
		}
					
		$arrayResult["USER_LOGIN_DATA"] = $arrayGroup;
		$arrayResult["USER_LOGIN_DATA_WEB"] = $arrayGroupWeb;
		$arrayResult["USER_LOGIN_DATA_MOBILE"] = $arrayGroupMobile;
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