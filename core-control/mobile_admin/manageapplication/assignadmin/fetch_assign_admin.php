<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','assignadmin')){
		$arrayAdmin = array();
		$fetchAdmin = $conmysql->prepare("SELECT member_no FROM gcmemberaccount WHERE user_type = '1'");
		$fetchAdmin->execute();
		while($rowAdmin = $fetchAdmin->fetch()){
			$arrayAdmin[] = $rowAdmin["member_no"];
		}
		$arrayResult['ADMIN'] = $arrayAdmin;
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
