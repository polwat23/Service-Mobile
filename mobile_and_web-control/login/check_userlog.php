<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['pin'],$dataComing)){
	$checkPinNull = $conmysql->prepare("SELECT pin FROM gcmemberaccount WHERE member_no = :member_no and account_status NOT IN('-6','-7','-8')");
	$checkPinNull->execute([':member_no' => $payload["member_no"]]);
	$rowPinNull = $checkPinNull->fetch();
	if(isset($rowPinNull["pin"])){
		$checkPin = $conmysql->prepare("SELECT account_status FROM gcmemberaccount WHERE member_no = :member_no and pin = :pin");
		$checkPin->execute([
			':member_no' => $payload["member_no"],
			':pin' => $dataComing["pin"]
		]);
		if($checkPin->rowCount() > 0){
			$rowaccount = $checkPin->fetch();
			if($rowaccount["account_status"] == '-9'){
				$arrayResult['TEMP_PASSWORD'] = TRUE;
			}else{
				$arrayResult['TEMP_PASSWORD'] = FALSE;
			}
			$arrayResult['RESULT'] = TRUE;
			if(isset($new_token)){
				$arrayResult['NEW_TOKEN'] = $new_token;
			}
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0011";
			if($lang_locale == 'th'){
				$arrayResult['RESPONSE_MESSAGE'] = "Pin ไม่ถูกต้อง";
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = "Pin is invalid";
			}
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$updatePin = $conmysql->prepare("UPDATE gcmemberaccount SET pin = :pin WHERE member_no = :member_no");
		if($updatePin->execute([
			':pin' => $dataComing["pin"],
			':member_no' => $payload["member_no"]
		])){
			$fetchAcc = $conmysql->prepare("SELECT account_status FROM gcmemberaccount WHERE member_no = :member_no");
			$fetchAcc->execute([
				':member_no' => $payload["member_no"]
			]);
			$rowaccount = $fetchAcc->fetch();
			if($rowaccount["account_status"] == '-9'){
				$arrayResult['TEMP_PASSWORD'] = TRUE;
			}else{
				$arrayResult['TEMP_PASSWORD'] = FALSE;
			}
			$arrayResult['RESULT'] = TRUE;
			if(isset($new_token)){
				$arrayResult['NEW_TOKEN'] = $new_token;
			}
			echo json_encode($arrayResult);
		}else{
			$arrExecute = [
				':pin' => $dataComing["pin"],
				':member_no' => $payload["member_no"]
			];
			$arrError = array();
			$arrError["EXECUTE"] = $arrExecute;
			$arrError["QUERY"] = $updatePin;
			$arrError["ERROR_CODE"] = 'WS1009';
			$lib->addLogtoTxt($arrError,'pin_error');
			$arrayResult['RESPONSE_CODE'] = "WS1009";
			if($lang_locale == 'th'){
				$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถตั้ง Pin ได้กรุณาติดต่อสหกรณ์ #WS1009";
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = "Cannot set Pin please contact cooperative #WS1009";
			}
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
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