<?php
require_once('../../autoload.php');

if(isset($dataComing["member_no"]) && isset($dataComing["id_card"]) && isset($dataComing["api_key"]) && isset($dataComing["unique_id"])){
	$conmysql_nottest = $con->connecttomysql();
	if($api->check_apikey($dataComing["api_key"],$dataComing["unique_id"],$conmysql_nottest)){
		$member_no = str_pad($dataComing["member_no"],8,0,STR_PAD_LEFT);
		$checkMember = $conmysql->prepare("SELECT id_account FROM mdbmemberaccount WHERE member_no = :member_no");
		$checkMember->execute([':member_no' => $member_no]);
		if($checkMember->rowCount() > 0){
			$arrayResult = array();
			$arrayResult['RESPONSE_CODE'] = "SQL300";
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
				$arrayResult = array();
				$arrayResult['RESPONSE_CODE'] = "SQL400";
				$arrayResult['RESPONSE'] = "Don't have a membership or you resigned";
				$arrayResult['RESULT'] = FALSE;
				http_response_code(203);
				echo json_encode($arrayResult);
				exit();
			}
		}
	}else{
		$arrayResult = array();
		$arrayResult['RESPONSE_CODE'] = "PARAM500";
		$arrayResult['RESPONSE'] = "Invalid API KEY";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(203);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult = array();
	$arrayResult['RESPONSE_CODE'] = "PARAM400";
	$arrayResult['RESPONSE'] = "Not complete parameter";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(203);
	echo json_encode($arrayResult);
	exit();
}
?>