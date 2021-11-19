<?php
require_once('../../autoload.php');
if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin',null)){
		$arrayGroup = array();
		$arrayGroupWeb = array();
		$arrayGroupMobile = array();
		$arrGroupMonth = array();
		$fetchUserlogin = $conmssql->prepare("SELECT COUNT(MEMBER_NO) as C_NAME,MONTH(login_date) as MONTH,YEAR(login_date) as YEAR
				   FROM gcuserlogin
				WHERE  CONVERT( VARCHAR, DATEADD(month,-6,login_date),111) <=  CONVERT(VARCHAR , login_date, 111)
				GROUP BY login_date ORDER BY login_date ASC");
		$fetchUserlogin->execute();
		while($rowUserlogin = $fetchUserlogin->fetch(PDO::FETCH_ASSOC)){
			$arrGroupRootUserlogin = array();
			$arrGroupRootUserlogin["MONTH"] = $rowUserlogin["MONTH"];
			$arrGroupRootUserlogin["YEAR"] = $rowUserlogin["YEAR"] + 543;
			$arrGroupRootUserlogin["AMT"] = $rowUserlogin["C_NAME"];
			$arrayGroup[] = $arrGroupRootUserlogin;
		}
		
		$fetchUserloginWeb = $conmssql->prepare("SELECT COUNT(MEMBER_NO) as C_NAME,MONTH(login_date) as MONTH,YEAR(login_date) as YEAR 
				FROM gcuserlogin
				WHERE  CONVERT( VARCHAR, DATEADD(month,-6,login_date),111) <=  CONVERT(VARCHAR , login_date, 111)
				AND channel = 'web'
				GROUP BY MEMBER_NO ,login_date
				 ORDER BY login_date ASC");
		$fetchUserloginWeb->execute();
		while($rowUserlogin = $fetchUserloginWeb->fetch(PDO::FETCH_ASSOC)){
			$arrGroupRootUserlogin = array();
			$arrGroupRootUserlogin["MONTH"] = $rowUserlogin["MONTH"];
			$arrGroupRootUserlogin["YEAR"] = $rowUserlogin["YEAR"] + 543;
			$arrGroupRootUserlogin["AMT"] = $rowUserlogin["C_NAME"];
			$arrayGroupWeb[] = $arrGroupRootUserlogin;
		}
		
		$fetchUserloginMobile = $conmssql->prepare("SELECT COUNT(MEMBER_NO) as C_NAME,MONTH(login_date) as MONTH,YEAR(login_date) as YEAR 
				FROM gcuserlogin
				WHERE  CONVERT( VARCHAR, DATEADD(month,-6,login_date),111) <=  CONVERT(VARCHAR , login_date, 111) AND channel = 'mobile_app'
				GROUP BY MEMBER_NO ,login_date
				ORDER BY login_date ASC");
		$fetchUserloginMobile->execute();
		while($rowUserlogin = $fetchUserloginMobile->fetch(PDO::FETCH_ASSOC)){
			$arrGroupRootUserlogin = array();
			$arrGroupRootUserlogin["MONTH"] = $rowUserlogin["MONTH"];
			$arrGroupRootUserlogin["YEAR"] = $rowUserlogin["YEAR"] + 543;
			$arrGroupRootUserlogin["AMT"] = $rowUserlogin["C_NAME"];
			$arrayGroupMobile[] = $arrGroupRootUserlogin;
		}
					
		$arrayResult["USER_LOGIN_DATA"] = $arrayGroup;
		$arrayResult["USER_LOGIN_DATA_WEB"] = $arrayGroupWeb;
		$arrayResult["USER_LOGIN_DATA_MOBILE"] = $arrayGroupMobile;
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