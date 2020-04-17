<?php
require_once('../../autoload.php');
if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin',null)){
		$arrayGroup = array();
		

		$arrGroupMonth = array();
		$fetchUserlogin = $conmysql->prepare("SELECT
												DATE_FORMAT(login_date, '%m') AS MONTH,
												IFNULL((
													SELECT COUNT(member_no) as C_MEM_LOGIN 
													FROM gcuserlogin 
													WHERE
														DATE_FORMAT(login_date, '%m') = MONTH 
														and is_login = '1' 
													GROUP BY DATE_FORMAT(login_date, '%m')),0) as C_MEM_LOGIN,
												IFNULL((
													SELECT COUNT(member_no) as C_MEM_LOGOUT FROM gcuserlogin 
													WHERE
														DATE_FORMAT(login_date, '%m') = MONTH and is_login <> '1' 
													GROUP BY DATE_FORMAT(login_date, '%m')),0) as C_MEM_LOGOUT
											FROM
												gcuserlogin
											WHERE
												login_date <= DATE_SUB(login_date, INTERVAL -6 MONTH)
											GROUP BY
												DATE_FORMAT(login_date, '%m')");
		$fetchUserlogin->execute();
		while($rowUserlogin = $fetchUserlogin->fetch(PDO::FETCH_ASSOC)){
			$arrGroupRootUserlogin = array();
			$arrGroupRootUserlogin["MONTH"] = $rowUserlogin["MONTH"];
			$arrGroupRootUserlogin["C_MEM_LOGIN"] = $rowUserlogin["C_MEM_LOGIN"];
			$arrGroupRootUserlogin["C_MEM_LOGOUT"] = $rowUserlogin["C_MEM_LOGOUT"];
			$arrayGroup[] = $arrGroupRootUserlogin;
		}
					
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