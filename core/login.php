<?php
require_once('autoload.php');

if(isset($dataComing["username"]) && isset($dataComing["password"])){
	$checkPassword = $conmysql->prepare("SELECT password,section_system FROM coreuser 
										WHERE username = :username and user_status = '1'");
	$checkPassword->execute([
		':username' => $dataComing["username"]
	]);
	if($checkPassword->rowCount() > 0){
		$rowPassword = $checkPassword->fetch();
		if(password_verify($dataComing["password"], $rowPassword['password'])){
			$arrayResult["SECTION_SYSTEM"] = $rowPassword["section_system"];
			$arrayResult["RESULT"] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult["RESULT"] = FALSE;
			$arrayResult["RESPONSE_CODE"] = "SQL403";
			$arrayResult["RESPONSE"] = "Password is invalid";
			http_response_code(203);
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult["RESULT"] = FALSE;
		$arrayResult["RESPONSE_CODE"] = "SQL400";
		$arrayResult["RESPONSE"] = "Not found user";
		http_response_code(203);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult["RESULT"] = FALSE;
	$arrayResult["RESPONSE_CODE"] = "PARAM400";
	$arrayResult["RESPONSE"] = "Not complete parameter";
	http_response_code(203);
	echo json_encode($arrayResult);
	exit();
}
?>