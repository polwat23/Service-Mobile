<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['username','password','device_name','ip_address','unique_id'],$dataComing)){
	$checkPassword = $conmysql->prepare("SELECT cs.section_system,cs.system_assign,cu.password
										FROM coreuser cu LEFT JOIN coresectionsystem cs ON cu.id_section_system = cs.id_section_system
										WHERE cu.username = :username");
	$checkPassword->execute([
		':username' => $dataComing["username"]
	]);
	if($checkPassword->rowCount() > 0){
		$rowPassword = $checkPassword->fetch();
		if(password_verify($dataComing["password"], $rowPassword['password'])){
			$arrPayload = array();
			$arrPayload['section_system'] = $rowPassword['section_system'];
			$arrPayload['username'] = $dataComing["username"];
			$arrPayload['exp'] = time() + 21600;
			$access_token = $jwt_token->customPayload($arrPayload, $config["SECRET_KEY_CORE"]);
			$arrayResult["SECTION_ASSIGN"] = $rowPassword["system_assign"];
			$arrayResult["USERNAME"] = $dataComing["username"];
			$arrayResult["ACCESS_TOKEN"] = $access_token;
			$arrayResult["RESULT"] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE_CODE'] = "4003";
			$arrayResult['RESPONSE_AWARE'] = "password";
			$arrayResult['RESPONSE'] = "Invalid password";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		http_response_code(204);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "4004";
	$arrayResult['RESPONSE_AWARE'] = "argument";
	$arrayResult['RESPONSE'] = "Not complete argument";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>