<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','assignadmin')){
		$arrayNotAdmin = array();
		$fetchNotAdmin = $conmysql->prepare("SELECT member_no FROM gcmemberaccount WHERE user_type = '0'");
		$fetchNotAdmin->execute();
		while($rowAdmin = $fetchNotAdmin->fetch()){
			$arrayNotAdmin[] = $rowAdmin["member_no"];
		}
		$arrayResult['NOT_ADMIN'] = $arrayNotAdmin;
		$arrayResult['RESULT'] = TRUE;
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
