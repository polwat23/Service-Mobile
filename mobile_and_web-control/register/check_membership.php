<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['user_type'],$payload) && $lib->checkCompleteArgument(['member_no','id_card','api_key','unique_id','menu_component'],$dataComing)){
	$conmysql_nottest = $con->connecttomysql();
	if($auth->check_apikey($dataComing["api_key"],$dataComing["unique_id"],$conmysql_nottest)){
		if($func->check_permission($payload["user_type"],$dataComing["menu_component"],$conmysql,'AppRegister')){
			$member_no = str_pad($dataComing["member_no"],8,0,STR_PAD_LEFT);
			$checkMember = $conmysql->prepare("SELECT id_account FROM gcmemberaccount WHERE member_no = :member_no");
			$checkMember->execute([':member_no' => $member_no]);
			if($checkMember->rowCount() > 0){
				$arrayResult = array();
				$arrayResult['RESPONSE_CODE'] = "2002";
				$arrayResult['RESPONSE'] = "You have a membership";
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
					$arrayResult = array();
					$arrayResult['MEMBER_NO'] = $member_no;
					$arrayResult['CARD_PERSON'] = $dataComing["id_card"];
					$arrayResult['MEMBER_FULLNAME'] = $rowMember["MEMB_NAME"].' '.$rowMember["MEMB_SURNAME"];
					$arrayResult['RESULT'] = TRUE;
					echo json_encode($arrayResult);
				}else{
					http_response_code(404);
					exit();
				}
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "4003";
			$arrayResult['RESPONSE_AWARE'] = "permission";
			$arrayResult['RESPONSE'] = "Not permission this menu";
			$arrayResult['RESULT'] = FALSE;
			http_response_code(403);
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult = array();
		$arrayResult['RESPONSE_CODE'] = "4007";
		$arrayResult['RESPONSE_AWARE'] = "api";
		$arrayResult['RESPONSE'] = "Invalid API KEY";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(407);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "4004";
	$arrayResult['RESPONSE_AWARE'] = "argument";
	$arrayResult['RESPONSE'] = "Not complete argument";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>