<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	$fetchUserControl = $conmysql->prepare("SELECT username FROM coreuser");
	$fetchUserControl->execute();
	$arrayGroupAll = array();
	while($rowUserControl = $fetchUserControl->fetch()){
		$arrayGroupAll[] = $rowUserControl["username"];
	}
	$arrayResult['USERNAME'] = $arrayGroupAll;
	$arrayResult['RESULT'] = TRUE;
	echo json_encode($arrayResult);
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>
