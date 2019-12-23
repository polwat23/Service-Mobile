<?php
require_once('../autoload.php');

$updateResetPin = $conmysql->prepare("UPDATE gcmemberaccount SET pin = null WHERE member_no = :member_no");
if($updateResetPin->execute([
	':member_no' => $payload["member_no"]
])){
	if($func->logoutAll(null,$payload["member_no"],'-10')){
		$arrayResult['RESULT'] = TRUE;
		if(isset($new_token)){
			$arrayResult['NEW_TOKEN'] = $new_token;
		}
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS1017";
		if($lang_locale == 'th'){
			$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถเปลี่ยน Pin ได้กรุณาติดต่อสหกรณ์ #WS1017";
		}else{
			$arrayResult['RESPONSE_MESSAGE'] = "Cannot change Pin please contact cooperative #WS1017";
		}
		$arrayResult['RESULT'] = FALSE;
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrExecute = [
		':member_no' => $payload["member_no"]
	];
	$arrError = array();
	$arrError["EXECUTE"] = $arrExecute;
	$arrError["QUERY"] = $updateResetPin;
	$arrError["ERROR_CODE"] = 'WS1016';
	$lib->addLogtoTxt($arrError,'pin_error');
	$arrayResult['RESPONSE_CODE'] = "WS1016";
	if($lang_locale == 'th'){
		$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถรีเซ็ต Pin ได้กรุณาติดต่อสหกรณ์ #WS1016";
	}else{
		$arrayResult['RESPONSE_MESSAGE'] = "Cannot reset Pin please contact cooperative #WS1016";
	}
	$arrayResult['RESULT'] = FALSE;
	echo json_encode($arrayResult);
	exit();
}
?>