<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','deptaccount_no','allow_status'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ManagementAccount')){
		$updateAccountBeenAllow = $conmysql->prepare("UPDATE gcuserallowacctransaction SET is_use = :allow_status WHERE deptaccount_no = :deptaccount_no");
		if($updateAccountBeenAllow->execute([
			':allow_status' => $dataComing["allow_status"],
			':deptaccount_no' => $dataComing["deptaccount_no"]
		])){
			if(isset($new_token)){
				$arrayResult['NEW_TOKEN'] = $new_token;
			}
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrExecute = [
				':allow_status' => $dataComing["allow_status"],
				':deptaccount_no' => $dataComing["deptaccount_no"]
			];
			$arrError = array();
			$arrError["EXECUTE"] = $arrExecute;
			$arrError["QUERY"] = $updateAccountBeenAllow;
			$arrError["ERROR_CODE"] = 'WS1024';
			$lib->addLogtoTxt($arrError,'manageaccount_error');
			$arrayResult['RESPONSE_CODE'] = "WS1024";
			if($lang_locale == 'th'){
				$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถเปลี่ยนสถานะบัญชีที่อนุญาติสำหรับการทำธุรกรรมได้ กรุณาติดต่อสหกรณ์ #WS1024";
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = "Cannot change account status allow for transaction please contact cooperative #WS1024";
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