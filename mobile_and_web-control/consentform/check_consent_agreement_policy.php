<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ConsentAgreement')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$getAccept = $conmysql->prepare("SELECT consent_id FROM gcconsentpdpa WHERE member_no = :member_no");
		$getAccept->execute([':member_no' => $payload["member_no"]]);
		if($getAccept->rowCount() == 0){
			$consentArr = array();
			$fetchConsent = $conmysql->prepare("SELECT type,text,consent_id,is_object FROM gcconsentform");
			$fetchConsent->execute();
			while($rowConsent = $fetchConsent->fetch(PDO::FETCH_ASSOC)){
				if($rowConsent["type"] != "DATA"){
					$consentArr[$rowConsent["type"]] = $rowConsent["text"];
				}else{
					$consentArr["DATA"][] = json_decode($rowConsent["text"],true);
				}
			}
			$arrayResult["CONSENT"] = $consentArr;
		}else{
			$rowAccept = $getAccept->fetch(PDO::FETCH_ASSOC);
			$consentArr = array();
			$fetchConsent = $conmysql->prepare("SELECT type,text,consent_id,is_object FROM gcconsentform where consent_id <> = :consent_id");
			$fetchConsent->execute([':consent_id' => $rowAccept["consent_id"]]);
			while($rowConsent = $fetchConsent->fetch(PDO::FETCH_ASSOC)){
				if($rowConsent["type"] != "DATA"){
					$consentArr[$rowConsent["type"]] = $rowConsent["text"];
				}else{
					$consentArr["DATA"][] = json_decode($rowConsent["text"],true);
				}
			}
			if(sizeof($consentArr) > 0){
				$arrayResult["CONSENT"] = $consentArr;
			}
		}
		
		$arrayResult['RESULT'] = TRUE;
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
