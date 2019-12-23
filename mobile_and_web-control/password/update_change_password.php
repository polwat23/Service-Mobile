<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','password'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SettingChangePassword')){
		$password = password_hash($dataComing["password"], PASSWORD_DEFAULT);
		$conmysql->beginTransaction();
		$changePassword = $conmysql->prepare("UPDATE gcmemberaccount SET password = :password,temppass = null,account_status = '1'
												WHERE member_no = :member_no");
		if($changePassword->execute([
			':password' => $password,
			':member_no' => $payload["member_no"]
		])){
			if($func->logoutAll($payload["id_token"],$payload["member_no"],'-9')){
				$conmysql->commit();
				$arrayResult['RESULT'] = TRUE;
				if(isset($new_token)){
					$arrayResult['NEW_TOKEN'] = $new_token;
				}
				echo json_encode($arrayResult);
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE_CODE'] = "WS1013";
				if($lang_locale == 'th'){
					$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถเปลี่ยนรหัสผ่านได้กรุณาติดต่อสหกรณ์ #WS1013";
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = "Cannot change password please contact cooperative #WS1013";
				}
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$conmysql->rollback();
			$arrExecute = [
				':password' => $password,
				':member_no' => $payload["member_no"]
			];
			$arrError = array();
			$arrError["EXECUTE"] = $arrExecute;
			$arrError["QUERY"] = $changePassword;
			$arrError["ERROR_CODE"] = 'WS1012';
			$lib->addLogtoTxt($arrError,'password_error');
			$arrayResult['RESPONSE_CODE'] = "WS1012";
			if($lang_locale == 'th'){
				$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถเปลี่ยนรหัสผ่านได้กรุณาติดต่อสหกรณ์ #WS1012";
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = "Cannot change password please contact cooperative #WS1012";
			}
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		if($lang_locale == 'th'){
			$arrayResult['RESPONSE_MESSAGE'] = "ท่านไม่มีสิทธิ์ใช้งานเมนูนี้";
		}else{
			$arrayResult['RESPONSE_MESSAGE'] = "You not have permission for this menu";
		}
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	if($lang_locale == 'th'){
		$arrayResult['RESPONSE_MESSAGE'] = "มีบางอย่างผิดพลาดกรุณาติดต่อสหกรณ์ #WS4004";
	}else{
		$arrayResult['RESPONSE_MESSAGE'] = "Something wrong please contact cooperative #WS4004";
	}
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>