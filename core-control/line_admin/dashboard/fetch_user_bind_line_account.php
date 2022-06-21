<?php
require_once('../../autoload.php');
if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin',null)){
		$arrayGroup = array();
		
		$fetchUserBindAccount = $conmysql->prepare("SELECT title, count(*)AS c_title
											  FROM lbrespondmessage
											  GROUP BY title
											  ORDER BY c_title DESC
											  LIMIT 7
											  ");
		$fetchUserBindAccount->execute();
		while($rowUserlogin = $fetchUserBindAccount->fetch(PDO::FETCH_ASSOC)){
			$arrUserBindAccont = array();
			$arrUserBindAccont["TITLE"] = $rowUserlogin["title"];
			$arrUserBindAccont["C_TITLE"] = $rowUserlogin["c_title"];
			$arrayGroup[] = $arrUserBindAccont;
		}
					
		$arrayResult["USER_BIND_ACCOUNT_LINE_DATA"] = $arrayGroup;
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