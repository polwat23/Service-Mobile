<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['member_no','id_card','api_token','unique_id','deptaccount_no'],$dataComing)){
	$arrPayload = $auth->check_apitoken($dataComing["api_token"],$config["SECRET_KEY_JWT"]);
	if(!$arrPayload["VALIDATE"]){
		$arrayResult['RESPONSE_CODE'] = "WS0001";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(401);
		echo json_encode($arrayResult);
		exit();
	}
	$member_no = strtolower($lib->mb_str_pad($dataComing["member_no"]));
	$checkMember = $conmysql->prepare("SELECT member_no FROM gcmemberaccount WHERE member_no = :member_no");
	$checkMember->execute([':member_no' => $member_no]);
	if($checkMember->rowCount() > 0){
		$arrayResult['RESPONSE_CODE'] = "WS0020";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		echo json_encode($arrayResult);
		exit();
	}else{
		if($member_no == "00035319" || $member_no == "00166294" || $member_no == "00373222" || $member_no == "00124796"
		|| $member_no == "00340499" || $member_no == "00862320" || $member_no == "00862819" || $member_no == "00862207"
		|| $member_no == "00862533" || $member_no == "00862487" || $member_no == "00863076" || $member_no == "00863173"
		|| $member_no == "00863203" || $member_no == "00863254" || $member_no == "00862878" || $member_no == "00863290"
		|| $member_no == "00862681" || $member_no == "00862908" || $member_no == "00862746" || $member_no == "00862762"
		|| $member_no == "00862827" || $member_no == "00862991" || $member_no == "00863299" || $member_no == "00863294"
		|| $member_no == "00508780" || $member_no == "00862355" || $member_no == "00863106" || $member_no == "00863149"){
			$arrHeaderAPI[] = 'Req-trans : '.date('YmdHis');
			$arrDataAPI["MemberID"] = substr($member_no,-6);
			$arrDataAPI["CitizenID"] = $dataComing["id_card"];
			$arrDataAPI["CoopAccountNo"] = preg_replace('/-/','',$dataComing["deptaccount_no"]);
			$arrResponseAPI = $lib->posting_data($config["URL_SERVICE_EGAT"]."MemberProfile/VerifyMember",$arrDataAPI,$arrHeaderAPI);
			if(!$arrResponseAPI["RESULT"]){
				$arrayResult['RESPONSE_CODE'] = "WS9999";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
			$arrResponseAPI = json_decode($arrResponseAPI);
			if($arrResponseAPI->responseCode == "200"){
				$arrayResult['MEMBER_NO'] = $member_no;
				$arrayResult['CARD_PERSON'] = $dataComing["id_card"];
				$arrayResult['MEMBER_FULLNAME'] = $arrResponseAPI->firstName.' '.$arrResponseAPI->lastName;
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0003";
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