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
	if($member_no == "00007688" || $member_no == "00007797" || $member_no == "00015546" || $member_no == "00017748" || $member_no == "00018703" || 
	   $member_no == "00020089" || $member_no == "00020137" || $member_no == "00021596" || $member_no == "00021598" || $member_no == "00021599" ||
	   $member_no == "00022039" || $member_no == "00022853" || $member_no == "00022854" || $member_no == "00022855" || $member_no == "00024001" ||
	   $member_no == "00024398" || $member_no == "00027507" || $member_no == "00027865" || $member_no == "00029566" || $member_no == "00029990" ||
	   $member_no == "00031290" || $member_no == "00031291" || $member_no == "00031700" || $member_no == "00033681" || $member_no == "00033682" ||
	   $member_no == "00033683" || $member_no == "00034912" || $member_no == "00036851" || $member_no == "00036923" || $member_no == "00040422" ||
	   $member_no == "00041427" || $member_no == "00041428" || $member_no == "00041429" || $member_no == "00041430" || $member_no == "00041431" ||
	   $member_no == "00041432" || $member_no == "00041433" || $member_no == "00041434" || $member_no == "00041435" || $member_no == "00044196" ||
	   $member_no == "00046149" || $member_no == "00046150" || $member_no == "00050396" || $member_no == "00050397" || $member_no == "00050398" ||
	   $member_no == "00050399" || $member_no == "00050400" || $member_no == "00050401" || $member_no == "00050402" || $member_no == "00051146" ||
	   $member_no == "00051376" || $member_no == "00056739" || $member_no == "00012427" || $member_no == "00002041" || $member_no == "00006930" ||
	   $member_no == "00018505" || $member_no == "00010350" || $member_no == "00017448" || $member_no == "00006231" || $member_no == "00009431" ||
	   $member_no == "00012067" || $member_no == "00011090" || $member_no == "00014782" || $member_no == "00007560" || $member_no == "00008647" ||
	   $member_no == "00015818" || $member_no == "00029856" || $member_no == "00519022" || $member_no == "00518529" || $member_no = "00022856" ||
	   $member_nk == "00043755"){
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