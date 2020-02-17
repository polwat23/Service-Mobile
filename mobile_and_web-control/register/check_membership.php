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
		$member_no = str_pad($dataComing["member_no"],8,0,STR_PAD_LEFT);
		$checkMember = $conmysql->prepare("SELECT id_account FROM gcmemberaccount WHERE member_no = :member_no");
		$checkMember->execute([':member_no' => $member_no]);
		if($checkMember->rowCount() > 0){
			$arrayResult['RESPONSE_CODE'] = "WS0020";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}else{
			$checkValid = $conoracle->prepare("SELECT memb_name,memb_surname FROM mbmembmaster 
												WHERE member_no = :member_no and card_person = :card_person and resign_status = 0");
			$checkValid->execute([
				':member_no' => $member_no,
				':card_person' => $dataComing["id_card"]
			]);
			$rowMember = $checkValid->fetch();
			if($rowMember){
				$arrayResult['MEMBER_NO'] = $member_no;
				$arrayResult['CARD_PERSON'] = $dataComing["id_card"];
				$arrayResult['MEMBER_FULLNAME'] = $rowMember["MEMB_NAME"].' '.$rowMember["MEMB_SURNAME"];
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
	$text = '#CheckData Register : '.date("Y-m-d H:i:s").' > '.json_encode($dataComing);
	file_put_contents(__DIR__.'/../../log/register.txt', $text . PHP_EOL, FILE_APPEND);
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>