<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['username'],$dataComing)){
	$checkPassword = $conoracle->prepare("SELECT USER_NAME as USERNAME
										FROM amsecusers
										WHERE USER_NAME = :username");
	$checkPassword->execute([
		':username' => $dataComing["username"]
	]);
	$rowPassword = $checkPassword->fetch(PDO::FETCH_ASSOC);
	if(isset($rowPassword["USERNAME"])){	
		$arrPayload = array();
		$arrPayload['section_system'] = "root";
		$arrPayload['username'] = $dataComing["username"];
		$arrPayload['exp'] = time() + 21600;
		$access_token = $jwt_token->customPayload($arrPayload, $config["SECRET_KEY_CORE"]);
		if($dataComing["username"] != 'dev@mode'){
			$updateOldUser = $conoracle->prepare("UPDATE coreuserlogin SET is_login = '0' WHERE username = :username");
			$updateOldUser->execute([':username' => $dataComing["username"]]);
		}
		$queryMax = $conoracle->prepare("SELECT MAX(id_userlogin) as MAX_TABLE FROM coreuserlogin");
		$queryMax->execute();
		$rowQueryMax = $queryMax->fetch(PDO::FETCH_ASSOC);
		$id_userlogin = $rowQueryMax["MAX_TABLE"] + 1;
		$insertLog = $conoracle->prepare("INSERT INTO coreuserlogin(id_userlogin,username,unique_id,device_name,auth_token,logout_date ,login_date , update_date)
										VALUES(:id_userlogin,:username,:unique_id,:device_name,:token,TO_DATE(:logout_date,'yyyy/mm/dd hh24:mi:ss'), TO_DATE(:login_date,'yyyy/mm/dd hh24:mi:ss') ,TO_DATE(:update_date,'yyyy/mm/dd hh24:mi:ss'))");
		if($insertLog->execute([
			':id_userlogin' => $id_userlogin,
			':username' => $dataComing["username"],
			':unique_id' => $dataComing["unique_id"],
			':device_name' => $dataComing["device_name"],
			':token' => $access_token,
			':logout_date' => date('Y-m-d H:i:s', strtotime('+1 hour')),
			':login_date' => date('Y-m-d H:i:s'),
			':update_date' => date('Y-m-d H:i:s')
		])){
			$arrayResult["USERNAME"] = $dataComing["username"];
			$arrayResult["ACCESS_TOKEN"] = $access_token;
			$arrayResult["RESULT"] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถเข้าสู่ระบบได้ กรุณาลองใหม่อีกครั้ง";		
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
		}
	}else{
		$arrayResult['RESPONSE'] = "ไม่พบข้อมูลผู้ใช้งานกรุณาตรวจสอบ ชื่อผู้ใช้ / รหัสผ่าน หรือฐานข้อมูล อีกครั้ง";
		$arrayResult['RESULT'] = FALSE;
		require_once('../../include/exit_footer.php');
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
}
?>