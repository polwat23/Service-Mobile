<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantapprwithdrawal')){
		$arrayGroup = array();
		$fetchUser = $conoracle->prepare("SELECT user_name,full_name,description FROM amsecusers WHERE user_status = '1'");
		$fetchUser->execute();
		while($rowUser = $fetchUser->fetch(PDO::FETCH_ASSOC)){
			$arrUsers = array();
			$arrUsers["USER_NAME"] = $rowUser["USER_NAME"];
			$arrUsers["FULL_NAME"] = $rowUser["FULL_NAME"];
			$arrUsers["DESCRIPTION"] = $rowUser["DESCRIPTION"];
			$arrayGroup[] = $arrUsers;
		}
		$arrayResult["USER_SYSTEM"] = $arrayGroup;
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