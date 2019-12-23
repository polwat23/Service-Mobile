<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','id_token'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SettingManageDevice')){
		if($func->logout($dataComing["id_token"],'-7')){
			$arrayResult['RESULT'] = TRUE;
			if(isset($new_token)){
				$arrayResult['NEW_TOKEN'] = $new_token;
			}
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS1019";
			if($lang_locale == 'th'){
				$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถบังคับให้อุปกรณ์นี้ออกจากระบบได้ กรุณาติดต่อสหกรณ์ #WS1019";
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = "Cannot force logout device please contact cooperative #WS1019";
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