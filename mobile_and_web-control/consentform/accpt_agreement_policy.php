<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','terms_id'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ConsentAgreement')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$insertAcceptPolicy = $conmysql->prepare("INSERT INTO gcacceptpolicy(member_no,url_policy,policy_id)
													VALUES(:member_no,:url_policy,:policy_id)");
		$insertAcceptPolicy->execute([
			':member_no' => $payload["member_no"],
			':url_policy' => $config["URL_POLICY"],
			':policy_id' => $dataComing["terms_id"]
		]);
		
		$getStatusAccept = $conoracle->prepare("SELECT PRIVACY_POLICY FROM mbmembmaster WHERE member_no = :member_no");
		$getStatusAccept->execute([':member_no' => $member_no]);
		$rowStatus = $getStatusAccept->fetch(PDO::FETCH_ASSOC);
		if(isset($rowStatus["PRIVACY_POLICY"]) && $rowStatus["PRIVACY_POLICY"] != '0' && $rowStatus["PRIVACY_POLICY"] != '9'){
			if($rowStatus["PRIVACY_POLICY"] == '1'){
				$updatePrivacy = $conoracle->prepare("UPDATE mbmembmaster SET privacy_policy = '3' WHERE member_no = :member_no");
				$updatePrivacy->execute([':member_no' => $member_no]);
			}
		}else{
			$updatePrivacy = $conoracle->prepare("UPDATE mbmembmaster SET privacy_policy = '2' WHERE member_no = :member_no");
			$updatePrivacy->execute([':member_no' => $member_no]);
		}
		require_once('../../include/exit_footer.php');
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
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
