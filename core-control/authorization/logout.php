<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['unique_id','token'],$dataComing)){
	$updateLogLogout = $conmysql->prepare("UPDATE coreuserlogin SET is_login = '0',logout_date = NOW() WHERE token = :token");
	$updateLogLogout->execute([
		':token' => $dataComing["token"]
	]);
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>