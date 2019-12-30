<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','sigma_key','limit_amt'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ManagementAccount')){
		if($payload["member_no"] == 'dev@mode'){
			$member_no = $config["MEMBER_NO_DEV_TRANSACTION"];
		}else if($payload["member_no"] == 'salemode'){
			$member_no = $config["MEMBER_NO_SALE_TRANSACTION"];
		}else{
			$member_no = $payload["member_no"];
		}
		$updateLimitTrans = $conmysql->prepare("UPDATE gcbindaccount SET limit_amt = :limit_amt WHERE sigma_key = :sigma_key");
		if($updateLimitTrans->execute([
			':limit_amt' => $dataComing["limit_amt"],
			':sigma_key' => $dataComing["sigma_key"]
		])){
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrExecute = [
				':limit_amt' => $dataComing["limit_amt"],
				':sigma_key' => $dataComing["sigma_key"]
			];
			$arrError = array();
			$arrError["EXECUTE"] = $arrExecute;
			$arrError["QUERY"] = $updateLimitTrans;
			$arrError["ERROR_CODE"] = 'WS1026';
			$lib->addLogtoTxt($arrError,'changelimit_error');
			$arrayResult['RESPONSE_CODE'] = "WS1026";
			if($lang_locale == 'th'){
				$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถเปลี่ยนแปลงวงเงินได้ กรุณาติดต่อสหกรณ์ #WS1026";
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = "Cannot change limit please contact cooperative #WS1026";
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