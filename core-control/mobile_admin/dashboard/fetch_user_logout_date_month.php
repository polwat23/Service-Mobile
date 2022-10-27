<?php
require_once('../../autoload.php');
if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
		$arrayGroup = array();
		

		$arrGroupMonth = array();
		/*$fetchUserlogin = $conoracle->prepare("SELECT
												TO_CHAR(login_date, 'MM') AS  MONTH,
												TO_CHAR(login_date, 'YYYY') AS YEAR,
												NVL((
													SELECT COUNT(member_no) as C_MEM_LOGIN 
													FROM gcuserlogin 
													WHERE
														TO_CHAR(login_date, 'MM') = TO_CHAR(sysdate, 'MM')  
														and is_login = '1'  AND channel = 'web'),0) as C_MEM_LOGIN_WEB,
												NVL((
													SELECT COUNT(member_no) as C_MEM_LOGIN 
													FROM gcuserlogin 
													WHERE
														TO_CHAR(login_date, 'MM') = TO_CHAR(sysdate, 'MM')  
														and is_login = '1'  AND channel = 'mobile_app'),0) as C_MEM_LOGIN_MOBILE,
												NVL((
													SELECT COUNT(member_no) as C_MEM_LOGIN 
													FROM gcuserlogin 
													WHERE
														TO_CHAR(login_date, 'MM') = TO_CHAR(sysdate, 'MM')  
														and is_login = '1' ),0) as C_MEM_LOGIN,
												NVL((
													SELECT COUNT(member_no) as C_MEM_LOGOUT FROM gcuserlogin 
													WHERE
														TO_CHAR(login_date, 'MM') = TO_CHAR(sysdate, 'MM')  and is_login <> '1' ),0) as C_MEM_LOGOUT,
												NVL((
													SELECT COUNT(member_no) as C_MEM_LOGOUT FROM gcuserlogin 
													WHERE
														TO_CHAR(login_date, 'MM') = TO_CHAR(sysdate, 'MM')  and is_login <> '1'  AND channel = 'web'),0) as C_MEM_LOGOUT_WEB,
												NVL((
													SELECT COUNT(member_no) as C_MEM_LOGOUT FROM gcuserlogin 
													WHERE
														TO_CHAR(login_date, 'MM') = TO_CHAR(sysdate, 'MM')  and is_login <> '1'  AND channel = 'mobile_app'),0) as C_MEM_LOGOUT_MOBILE
											FROM
												gcuserlogin
											WHERE
												TO_CHAR(login_date,'YYYY-MM-DD')  BETWEEN TO_CHAR(ADD_MONTHS(sysdate, -6),'YYYY-MM-DD') and  TO_CHAR(sysdate,'YYYY-MM-DD')
											GROUP BY login_date 
											ORDER BY login_date ASC");
		$fetchUserlogin->execute();
		while($rowUserlogin = $fetchUserlogin->fetch(PDO::FETCH_ASSOC)){
			$arrGroupRootUserlogin = array();
			$arrGroupRootUserlogin["MONTH"] = $rowUserlogin["MONTH"];
			$arrGroupRootUserlogin["YEAR"] = $rowUserlogin["YEAR"]+543;
			$arrGroupRootUserlogin["C_MEM_LOGIN"] = $rowUserlogin["C_MEM_LOGIN"];
			$arrGroupRootUserlogin["C_MEM_LOGIN_WEB"] = $rowUserlogin["C_MEM_LOGIN_WEB"];
			$arrGroupRootUserlogin["C_MEM_LOGIN_MOBILE"] = $rowUserlogin["C_MEM_LOGIN_MOBILE"];
			$arrGroupRootUserlogin["C_MEM_LOGOUT"] = $rowUserlogin["C_MEM_LOGOUT"];
			$arrGroupRootUserlogin["C_MEM_LOGOUT_WEB"] = $rowUserlogin["C_MEM_LOGOUT_WEB"];
			$arrGroupRootUserlogin["C_MEM_LOGOUT_MOBILE"] = $rowUserlogin["C_MEM_LOGOUT_MOBILE"];
			$arrayGroup[] = $arrGroupRootUserlogin;
		}
		*/		
		$arrayResult["USER_LOGIN_LOGOUT_DATA"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		require_once('../../../include/exit_footer.php');

}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../include/exit_footer.php');
}
?>