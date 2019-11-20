<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['member_no','id_token','user_type'],$payload) && $lib->checkCompleteArgument(['menu_component','password'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],$conmysql,'SettingChangePassword')){
		$password = password_hash($dataComing["password"], PASSWORD_DEFAULT);
		$conmysql->beginTransaction();
		$changePassword = $conmysql->prepare("UPDATE gcmemberaccount SET password = :password,temppass = null,account_status = '1'
												WHERE member_no = :member_no");
		if($changePassword->execute([
			':password' => $password,
			':member_no' => $payload["member_no"]
		])){
			if($func->logoutAll($payload["id_token"],$payload["member_no"],'-9',$conmysql)){
				$conmysql->commit();
				$arrayResult['RESULT'] = TRUE;
				if(isset($new_token)){
					$arrayResult['NEW_TOKEN'] = $new_token;
				}
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS1012";
				$arrayResult['RESPONSE_MESSAGE'] = "Cannot change password because cannot logout";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS1011";
			$arrayResult['RESPONSE_MESSAGE'] = "Cannot change password";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = "Not complete argument";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>