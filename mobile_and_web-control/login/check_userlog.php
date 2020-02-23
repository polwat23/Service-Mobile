<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['pin'],$dataComing)){
	$checkPinNull = $conmysql->prepare("SELECT pin,account_status FROM gcmemberaccount WHERE member_no = :member_no and account_status IN('1','-9')");
	$checkPinNull->execute([':member_no' => $payload["member_no"]]);
	$rowPinNull = $checkPinNull->fetch(PDO::FETCH_ASSOC);
	if(isset($rowPinNull["pin"])){
		if($rowPinNull["pin"] == $dataComing["pin"]){
			if($rowPinNull["account_status"] == '-9'){
				$arrayResult['TEMP_PASSWORD'] = TRUE;
			}else{
				$arrayResult['TEMP_PASSWORD'] = FALSE;
			}
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0011";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
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
			if($rowPinNull["account_status"] == '-9'){
				$arrayResult['TEMP_PASSWORD'] = TRUE;
			}else{
				$arrayResult['TEMP_PASSWORD'] = FALSE;
			}
			$arrayResult['RESULT'] = TRUE;
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
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>