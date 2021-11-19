<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['username','password','device_name','unique_id'],$dataComing)){
	if($dataComing["username"] == 'dev@mode'){
		$checkPassword = $conmssql->prepare("SELECT cu.password
											FROM coreuser cu
											WHERE cu.username = :username and cu.user_status = '1'");
		$checkPassword->execute([
			':username' => $dataComing["username"]
		]);
		$rowPassword = $checkPassword->fetch(PDO::FETCH_ASSOC);
		if(password_verify($dataComing["password"], $rowPassword['password'])){
			$arrPayload = array();
			$arrPayload['section_system'] = 'root';
			$arrPayload['username'] = $dataComing["username"];
			$arrPayload['exp'] = time() + 21600;
			$access_token = $jwt_token->customPayload($arrPayload, $config["SECRET_KEY_CORE"]);
			$insertLog = $conmssql->prepare("INSERT INTO coreuserlogin(username,unique_id,device_name,auth_token,logout_date)
											VALUES(:username,:unique_id,:device_name,:token,:logout_date)");
			if($insertLog->execute([
				':username' => $dataComing["username"],
				':unique_id' => $dataComing["unique_id"],
				':device_name' => $dataComing["device_name"],
				':token' => $access_token,
				':logout_date' => date('Y-m-d H:i:s', strtotime('+1 hour'))
			])){
				$arrayResult["SECTION_ASSIGN"] = 'Super Admin';
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
			$arrayResult['RESPONSE'] = "รหัสผ่านไม่ถูกต้อง";
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
		}
	}else{
		$adConnect = ldap_connect($config["AD_HOST"]);
		$bindUnPw = ldap_bind($adConnect,$dataComing["username"]."@mbk-center.mbk",$dataComing["password"]);
		if($bindUnPw){
			$getUn = $conmssql->prepare("SELECT password FROM coreuser WHERE username = :un");
			$getUn->execute([':un' => $dataComing["username"]]);
			$rowUn = $getUn->fetch(PDO::FETCH_ASSOC);
			if(isset($rowUn["password"]) && $rowUn["password"] != ""){
				
			}else{
				$pw = password_hash($dataComing["password"], PASSWORD_DEFAULT);
				$insertUn = $conmssql->prepare("INSERT INTO coreuser(username,password,id_section_system)
												VALUES(:un,:pw,10)");
				$insertUn->execute([
					':un' => $dataComing["username"],
					':pw' => $pw
				]);
			}
			$arrPayload = array();
			$arrPayload['section_system'] = 'mb';
			$arrPayload['username'] = $dataComing["username"];
			$arrPayload['exp'] = time() + 21600;
			$access_token = $jwt_token->customPayload($arrPayload, $config["SECRET_KEY_CORE"]);
			if($dataComing["username"] != 'dev@mode'){
				$updateOldUser = $conmssql->prepare("UPDATE coreuserlogin SET is_login = '0' WHERE username = :username");
				$updateOldUser->execute([':username' => $dataComing["username"]]);
			}
			$insertLog = $conmssql->prepare("INSERT INTO coreuserlogin(username,unique_id,device_name,auth_token,logout_date)
											VALUES(:username,:unique_id,:device_name,:token,:logout_date)");
			if($insertLog->execute([
				':username' => $dataComing["username"],
				':unique_id' => $dataComing["unique_id"],
				':device_name' => $dataComing["device_name"],
				':token' => $access_token,
				':logout_date' => date('Y-m-d H:i:s', strtotime('+1 hour'))
			])){
				$arrayResult["SECTION_ASSIGN"] = "ระบบสมาชิก";
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
			$arrayResult['RESPONSE'] = "รหัสผ่านไม่ถูกต้อง";
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
		}
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
}
?>