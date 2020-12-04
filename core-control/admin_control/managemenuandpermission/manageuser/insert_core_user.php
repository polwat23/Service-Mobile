<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','username','password','id_section_system'],$dataComing)){
	if($func->check_permission_core($payload,'admincontrol','managecoreusers')){
		$password = password_hash($dataComing["password"], PASSWORD_DEFAULT);
		$checkPk = $conmysql->prepare("SELECT username FROM coreuser WHERE username = :username");
		$checkPk->execute([':username' => $dataComing["username"]]);
		if($checkPk->rowCount() > 0){
			$rowUser = $checkPk->fetch(PDO::FETCH_ASOC);
			$updateUser = $conmysql->prepare("UPDATE coreuser SET password = :password,id_section_system = :id_section_system WHERE username = :username and is_use = '-9'");
			if($updateUser->execute([
				':password' => $password,
				':id_section_system' => $dataComing["id_section_system"],
				':username' => $dataComing["username"]
			])){
				$arrayStruc = [
					':menu_name' => "manageuser",
					':username' => $payload["username"],
					':use_list' => "insert core user",
					':details' => 'add username : '.$dataComing["username"]
				];
				$log->writeLog('editadmincontrol',$arrayStruc);
				$arrayResult["RESULT"] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มผู้ใช้งานได้กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
		}else{
			$updatemenu = $conmysql->prepare("INSERT INTO coreuser (username, password, id_section_system) 
											 VALUES(:username,:password,:id_section_system)");
			if($updatemenu->execute([
				':username' => $dataComing["username"],
				':password' => $password,
				':id_section_system' => $dataComing["id_section_system"]
			])){
				$arrayStruc = [
					':menu_name' => "manageuser",
					':username' => $payload["username"],
					':use_list' => "insert core user",
					':details' => 'add username : '.$dataComing["username"]
				];
				$log->writeLog('editadmincontrol',$arrayStruc);
				$arrayResult["RESULT"] = TRUE;
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มผู้ใช้งานได้กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
			require_once('../../../../include/exit_footer.php');
		}
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../../include/exit_footer.php');
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../../include/exit_footer.php');
}
?>