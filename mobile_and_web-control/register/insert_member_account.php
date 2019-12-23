<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['member_no','email','phone','password','api_token','unique_id','menu_component'],$dataComing)){
	$arrPayload = $auth->check_apitoken($dataComing["api_token"],$config["SECRET_KEY_JWT"]);
	if(!$arrPayload["VALIDATE"]){
		$arrayResult['RESPONSE_CODE'] = "WS0001";
		if($lang_locale == 'th'){
			$arrayResult['RESPONSE_MESSAGE'] = "มีบางอย่างผิดพลาดกรุณาติดต่อสหกรณ์ #WS0001";
		}else{
			$arrayResult['RESPONSE_MESSAGE'] = "Something wrong please contact cooperative #WS0001";
		}
		$arrayResult['RESULT'] = FALSE;
		http_response_code(401);
		echo json_encode($arrayResult);
		exit();
	}
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'AppRegister')){
		$email = $dataComing["email"];
		$phone = $dataComing["phone"];
		$password = password_hash($dataComing["password"], PASSWORD_DEFAULT);
		$insertAccount = $conmysql->prepare("INSERT INTO gcmemberaccount(member_no,password,phone_number,email) 
											VALUES(:member_no,:password,:phone,:email)");
		if($insertAccount->execute([
			':member_no' => $dataComing["member_no"],
			':password' => $password,
			':phone' => $phone,
			':email' => $email
		])){
			$arrayResult = array();
			$arrayResult['MEMBER_NO'] = $dataComing["member_no"];
			$arrayResult['PASSWORD'] = $dataComing["password"];
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrExecute = [
				':member_no' => $dataComing["member_no"],
				':password' => $password,
				':phone' => $phone,
				':email' => $email
			];
			$arrError = array();
			$arrError["EXECUTE"] = $arrExecute;
			$arrError["QUERY"] = $insertAccount;
			$arrError["ERROR_CODE"] = 'WS1018';
			$lib->addLogtoTxt($arrError,'register_error');
			$arrayResult['RESPONSE_CODE'] = "WS1018";
			if($lang_locale == 'th'){
				$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถสมัครได้ในขณะนี้ กรุณาติดต่อสหกรณ์ #WS1018";
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = "Cannot register this moment please contact cooperative #WS1018";
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