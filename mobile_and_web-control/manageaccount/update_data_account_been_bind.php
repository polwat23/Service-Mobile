<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','id_bindaccount','bind_status'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ManagementAccount')){
		$updateAccountBeenbind = $conmysql->prepare("UPDATE gcbindaccount SET bindaccount_status = :bind_status WHERE id_bindaccount = :id_bindaccount");
		if($updateAccountBeenbind->execute([
			':bind_status' => $dataComing["bind_status"],
			':id_bindaccount' => $dataComing["id_bindaccount"]
		])){
			if(isset($new_token)){
				$arrayResult['NEW_TOKEN'] = $new_token;
			}
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrExecute = [
				':bind_status' => $dataComing["bind_status"],
				':id_bindaccount' => $dataComing["id_bindaccount"]
			];
			$arrError = array();
			$arrError["EXECUTE"] = $arrExecute;
			$arrError["QUERY"] = $updateAccountBeenbind;
			$arrError["ERROR_CODE"] = 'WS1025';
			$lib->addLogtoTxt($arrError,'manageaccount_error');
			$arrayResult['RESPONSE_CODE'] = "WS1025";
			if($lang_locale == 'th'){
				$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถเปลี่ยนสถานะบัญชีที่ผูกสำหรับการทำธุรกรรมได้ กรุณาติดต่อสหกรณ์ #WS1025";
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = "Cannot change account status bind for transaction please contact cooperative #WS1025";
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