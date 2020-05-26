<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['member_no','id_card','api_token','unique_id'],$dataComing)){
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
		$checkValid = $conoracle->prepare("SELECT mb.memb_name,mb.memb_surname,mb.resign_status,mp.prename_desc 
											FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
											WHERE mb.member_no = :member_no and mb.card_person = :card_person");
		$checkValid->execute([
			':member_no' => $member_no,
			':card_person' => $dataComing["id_card"]
		]);
		$rowMember = $checkValid->fetch(PDO::FETCH_ASSOC);
		if(isset($rowMember["MEMB_NAME"])){
			if($rowMember["RESIGN_STATUS"] == '1'){
				$arrayResult['RESPONSE_CODE'] = "WS0051";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
			$arrayResult['MEMBER_NO'] = $member_no;
			$arrayResult['CARD_PERSON'] = $dataComing["id_card"];
			$arrayResult['MEMBER_FULLNAME'] = $rowMember["PRENAME_DESC"].$rowMember["MEMB_NAME"].' '.$rowMember["MEMB_SURNAME"];
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0003";
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