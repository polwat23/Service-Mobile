<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['api_token','unique_id','member_no','card_person'],$dataComing)){
	$arrPayload = $auth->check_apitoken($dataComing["api_token"],$config["SECRET_KEY_JWT"]);
	if(!$arrPayload["VALIDATE"]){
		$filename = basename(__FILE__, '.php');
		$logStruc = [
			":error_menu" => $filename,
			":error_code" => "WS0001",
			":error_desc" => "ไม่สามารถยืนยันข้อมูลได้"."\n".json_encode($dataComing),
			":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
		];
		$log->writeLog('errorusage',$logStruc);
		$arrayResult['RESPONSE_CODE'] = "WS0001";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(401);
		require_once('../../include/exit_footer.php');
		
	}
	$member_no = strtolower($lib->mb_str_pad($dataComing["member_no"]));
	$checkMember = $conoracle->prepare("SELECT account_status,user_type FROM gcmemberaccount 
										WHERE member_no = :member_no");
	$checkMember->execute([
		':member_no' => $member_no
	]);
	$rowChkMemb = $checkMember->fetch(PDO::FETCH_ASSOC);
	if(isset($rowChkMemb["ACCOUNT_STATUS"])){
		if($rowChkMemb["ACCOUNT_STATUS"] == '-8'){
			$arrayResult['RESPONSE_CODE'] = "WS0048";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
		$member_no_RAW = $configAS[$member_no] ?? $member_no;
		$getCardPerson = $conoracle->prepare("SELECT CARD_PERSON,MEM_TELMOBILE FROM mbmembmaster WHERE member_no = :member_no");
		$getCardPerson->execute([':member_no' => $member_no_RAW]);
		$rowMemb = $getCardPerson->fetch(PDO::FETCH_ASSOC);
		if($rowMemb["CARD_PERSON"] != $dataComing["card_person"]){
			$arrayResult['RESPONSE_CODE'] = "WS0060";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
		if($rowChkMemb['USER_TYPE'] == '0' || $rowChkMemb['USER_TYPE'] == '1'){
			$arrayResult['IS_OTP'] = TRUE;
		}
		$arrayResult['TEL'] = $rowMemb["MEM_TELMOBILE"];
		$arrayResult['TEL_FORMAT'] = $lib->formatphone($rowMemb["MEM_TELMOBILE"]);
		$arrayResult['TEMP_PASSWORD'] = TRUE;
		$arrayResult['RESULT'] = TRUE;
		require_once('../../include/exit_footer.php');
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0003";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		require_once('../../include/exit_footer.php');
		
	}
}else{
	$filename = basename(__FILE__, '.php');
	$logStruc = [
		":error_menu" => $filename,
		":error_code" => "WS4004",
		":error_desc" => "ส่ง Argument มาไม่ครบ "."\n".json_encode($dataComing),
		":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
	];
	$log->writeLog('errorusage',$logStruc);
	$message_error = "ไฟล์ ".$filename." ส่ง Argument มาไม่ครบมาแค่ "."\n".json_encode($dataComing);
	$lib->sendLineNotify($message_error);
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
	
}
?>