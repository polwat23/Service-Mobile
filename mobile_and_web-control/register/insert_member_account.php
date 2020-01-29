<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['member_no','email','phone','password','api_token','unique_id','menu_component'],$dataComing)){
	$arrPayload = $auth->check_apitoken($dataComing["api_token"],$config["SECRET_KEY_JWT"]);
	if(!$arrPayload["VALIDATE"]){
		$arrayResult['RESPONSE_CODE'] = "WS0001";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(401);
		echo json_encode($arrayResult);
		exit();
	}
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'AppRegister')){
		$email = $dataComing["email"];
		$phone = $dataComing["phone"];
		$password = password_hash($dataComing["password"], PASSWORD_DEFAULT);
		$insertAccount = $conmysql->prepare("INSERT INTO gcmemberaccount(member_no,password,phone_number,email,limit_transaction_amt) 
											VALUES(:member_no,:password,:phone,:email,:limit_amt)");
		if($insertAccount->execute([
			':member_no' => $dataComing["member_no"],
			':password' => $password,
			':phone' => $phone,
			':email' => $email,
			':limit_amt' => $func->getConstant('limit_withdraw')
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
				':email' => $email,
			':limit_amt' => $func->getConstant('limit_withdraw')
			];
			$arrError = array();
			$arrError["EXECUTE"] = $arrExecute;
			$arrError["QUERY"] = $insertAccount;
			$arrError["ERROR_CODE"] = 'WS1018';
			$lib->addLogtoTxt($arrError,'register_error');
			$arrayResult['RESPONSE_CODE'] = "WS1018";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
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