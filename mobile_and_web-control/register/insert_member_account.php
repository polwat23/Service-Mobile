<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['member_no','phone','password','api_token','unique_id'],$dataComing)){
	$arrPayload = $auth->check_apitoken($dataComing["api_token"],$config["SECRET_KEY_JWT"]);
	if(!$arrPayload["VALIDATE"]){
		$arrayResult['RESPONSE_CODE'] = "WS0001";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(401);
		echo json_encode($arrayResult);
		exit();
	}
	$email = isset($dataComing["email"]) ? preg_replace('/\s+/', '', $dataComing["email"]) : null;
	$phone = $dataComing["phone"];
	$password = password_hash($dataComing["password"], PASSWORD_DEFAULT);
	$conmysql->beginTransaction();
	$insertAccount = $conmysql->prepare("INSERT INTO gcmemberaccount(member_no,password,phone_number,email,register_channel,deptaccount_no_regis) 
										VALUES(:member_no,:password,:phone,:email,:channel,:deptaccount_no)");
	if($insertAccount->execute([
		':member_no' => $dataComing["member_no"],
		':password' => $password,
		':phone' => $phone,
		':email' => $email,
		':channel' => $dataComing["channel"],
		':deptaccount_no' => $dataComing["deptaccount_no"]
	])){
		$arrHeaderAPI[] = 'Req-trans : '.date('YmdHis');
		$arrDataAPI["MemberID"] = substr($dataComing["member_no"],-6);
		$arrDataAPI["CitizenID"] = $dataComing["card_person"];
		$arrDataAPI["CoopAccountNo"] = $dataComing["deptaccount_no"];
		$arrDataAPI["UserRequestDate"] = date('c');
		$arrResponseAPI = $lib->posting_data($config["URL_SERVICE_EGAT"]."MemberProfile/SaveMember",$arrDataAPI,$arrHeaderAPI);
		if(!$arrResponseAPI["RESULT"]){
			$arrayResult['RESPONSE_CODE'] = "WS9999";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
		$arrResponseAPI = json_decode($arrResponseAPI);
		if($arrResponseAPI->responseCode == "200"){
			$conmysql->commit();
			$arrayResult['MEMBER_NO'] = $dataComing["member_no"];
			$arrayResult['PASSWORD'] = $dataComing["password"];
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$conmysql->rollback();
			$arrExecute = [
				':member_no' => $dataComing["member_no"],
				':password' => $password,
				':phone' => $phone,
				':email' => $email,
				'error_message' => $arrResponseAPI->responseMessage,
				'card_person' => $dataComing["card_person"],
				'deptaccount_no' => $dataComing["deptaccount_no"]
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
		$conmysql->rollback();
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
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
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