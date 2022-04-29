<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SettingWithdrawConsent')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		if($dataComing["consent_step"] == '0'){
			$getRefresh = $conmssql->prepare("SELECT refresh_token,access_token,CONVERT(varchar,token_expire,20) as token_expire,CONVERT(varchar,refresh_expire,20) as refresh_expire 
											FROM gcauthconsent WHERE is_revoke = '0'");
			$getRefresh->execute();
			$rowRefreshToken = $getRefresh->fetch(PDO::FETCH_ASSOC);
			$getTelMobile = $conmssql->prepare("SELECT phone_number FROM gcmemberaccount WHERE member_no = :member_no");
			$getTelMobile->execute([':member_no' => $payload["member_no"]]);
			$rowTelMobile = $getTelMobile->fetch(PDO::FETCH_ASSOC);
			if(isset($rowRefreshToken["refresh_token"]) && $rowRefreshToken["refresh_token"] != ""){
				if($rowRefreshToken["token_expire"] <= date('Y-m-d H:i:s')){
					if(isset($dataComing["data_api"]["result"]["expireInSeconds"])){
						$updateToken = $conmssql->prepare("UPDATE gcauthconsent SET access_token = :access_token,token_expire = :token_expire WHERE is_revoke = '0'");
						$updateToken->execute([
							':access_token' => $dataComing["data_api"]["result"]["accessToken"],
							':token_expire' => date("Y-m-d H:i:s", strtotime("+".(ceil($dataComing["data_api"]["result"]["expireInSeconds"] / 1.1))." sec"))
						]);
					}else{
						$arrPrepare["refreshToken"] = $rowRefreshToken["refresh_token"];
						$arrAuth["HEADER"] = (object)[];
						$arrAuth["BODY"] = $arrPrepare;
						$arrAuth["URL"] = $config["CONSENT_API"].'/TokenAuth/RefreshToken';
						$arrayResult["CONSENT_STEP"] = $dataComing["consent_step"];
						$arrayResult["PREVIOUS_CALL"] = FALSE;
						$arrayResult["DATA_CONSENT"] = $arrAuth;
						$arrayResult['RESULT'] = TRUE;
						require_once('../../include/exit_footer.php');
					}
				}
				$accessToken = $dataComing["data_api"]["result"]["accessToken"] ?? $rowRefreshToken["access_token"];
				$arrPrepare = array();
				if($lang_locale == 'th'){
					$arrPrepare["consentPointId"] = $config["CONSENT_POINTID_TH"];
				}else{
					$arrPrepare["consentPointId"] = $config["CONSENT_POINTID_EN"];
				}
				if($payload["member_no"] == 'etnmode1' || $payload["member_no"] == 'etnmode2'){
					$arrAuth["data_loopback"]["tel_mobile"] = '0883995571';
				}else{
					$arrAuth["data_loopback"]["tel_mobile"] = preg_replace('/-/','',$rowTelMobile["phone_number"]);
				}
				$arrPrepare["identifier"] = $arrAuth["data_loopback"]["tel_mobile"];
				$arrPrepare["langCode"] = $lang_locale;
				$arrAuth["HEADER"] = (object)["Authorization" => "Bearer ".$accessToken];
				$arrAuth["BODY"] = $arrPrepare;
				$arrAuth["METHOD"] = "GET";
				$arrAuth["URL"] = $config["CONSENT_API"].'/services/app/ConsentPoints/GetApprovedPurposeByIdentifier';
				$arrayResult["CONSENT_STEP"] = '1';
				$arrayResult["PREVIOUS_CALL"] = FALSE;
				$arrayResult["DATA_CONSENT"] = $arrAuth;
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$insertToken = $conmssql->prepare("INSERT INTO gcauthconsent(refresh_token,access_token,token_expire,refresh_expire)
													VALUES(:refresh_token,:access_token,:token_expire,:refresh_expire)");
				$insertToken->execute([
					':refresh_token' => $dataComing["data_api"]["result"]["refreshToken"],
					':access_token' => $dataComing["data_api"]["result"]["accessToken"],
					':token_expire' => date("Y-m-d H:i:s", strtotime("+".(ceil($dataComing["data_api"]["result"]["expireInSeconds"] / 1.1))." sec")),
					':refresh_expire' => date("Y-m-d H:i:s", strtotime("+".(ceil($dataComing["data_api"]["result"]["refreshTokenExpireInSeconds"] / 1.1))." sec"))
				]);
				$accessToken = $dataComing["data_api"]["result"]["accessToken"];
				$arrPrepare = array();
				if($lang_locale == 'th'){
					$arrPrepare["consentPointId"] = $config["CONSENT_POINTID_TH"];
				}else{
					$arrPrepare["consentPointId"] = $config["CONSENT_POINTID_EN"];
				}
				if($payload["member_no"] == 'etnmode1' || $payload["member_no"] == 'etnmode2'){
					$arrAuth["data_loopback"]["tel_mobile"] = '0883995571';
				}else{
					$arrAuth["data_loopback"]["tel_mobile"] = preg_replace('/-/','',$rowTelMobile["phone_number"]);
				}
				$arrPrepare["identifier"] = $arrAuth["data_loopback"]["tel_mobile"];
				$arrPrepare["langCode"] = $lang_locale;
				$arrAuth["HEADER"] = (object)["Authorization" => "Bearer ".$accessToken];
				$arrAuth["BODY"] = $arrPrepare;
				$arrAuth["METHOD"] = "GET";
				$arrAuth["URL"] = $config["CONSENT_API"].'/services/app/ConsentPoints/GetApprovedPurposeByIdentifier';
				$arrayResult["CONSENT_STEP"] = '1';
				$arrayResult["PREVIOUS_CALL"] = FALSE;
				$arrayResult["DATA_CONSENT"] = $arrAuth;
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}
		} else if($dataComing["consent_step"] == '1') {
			if($dataComing["data_api"]["success"]){
				$arrPurposeGrp = array();
				if(sizeof($dataComing["data_api"]["result"]["purposes"]) > 0){
					foreach($dataComing["data_api"]["result"]["purposes"] as $purpose){
						$arrPurpose = array();
						$arrPurpose["ID"] = $purpose["id"];
						$arrPurpose["DETAIL"] = $purpose["name"];
						$arrPurposeGrp[] = $arrPurpose;
					}
					$arrConsentGrp["DATA"] = $arrPurposeGrp;
					$arrConsentGrp["IS_ACTIVE"] = TRUE;
					if($lang_locale == 'th'){
						$arrConsentGrp["SUBTITLE"] = "ยินยอมให้บริษัทฯ และบริษัทในกลุ่มเอ็มบีเค และพันธมิตรทางธุรกิจประมวลผลข้อมูลส่วนบุคคลของข้าพเจ้า เพื่อวัตถุประสงค์ดังต่อไปนี้";
						$arrConsentGrp["REMARK"] = "หมายเหตุ: ท่านสามารถเลือกให้ความยินยอมทั้งหมด บางส่วน หรือไม่ให้ความยินยอมได้ หากท่านปฏิเสธไม่ให้ความยินยอมหรือถอนความยินยอมที่ท่านได้ให้ไว้ บริษัทฯ อาจไม่สามารถดำเนินการให้บริการให้ตรงกับความต้องการของท่านได้อย่างมีประสิทธิภาพ";
						$arrConsentGrp["TITLE"] = 'การถอนความยินยอม';
					}else{
						$arrConsentGrp["SUBTITLE"] = 'consent to MBK Public Company Limited ("Company"), Companies in the MBK Group 
						and Business Alliances to collect, use and disclose ("Processing") my personal data for';
						$arrConsentGrp["REMARK"] = 'Notes: You can choose to give your consent wholly, partly or without consent. If you refuse to 
						consent or withdraw the consent you have given, the Company may not be able to operate the service to meet your needs efficiently';
						$arrConsentGrp["TITLE"] = 'Consent';
					}
					
					$arrAuth["tel_mobile"] = $dataComing["data_api"]["data_loopback"]["tel_mobile"];
					$arrConsentGrp["data_loopback"] = $arrAuth;
					$arrayResult["CONSENT"] = $arrConsentGrp;
					$arrayResult["CONSENT_STEP"] = '2';
					$arrayResult["PREVIOUS_CALL"] = FALSE;
					$arrayResult['RESULT'] = TRUE;
					require_once('../../include/exit_footer.php');
				}else{
					$arrayResult["CONSENT_STEP"] = '2';
					$arrayResult['RESULT'] = TRUE;
					require_once('../../include/exit_footer.php');
				}
			}
		} else if ($dataComing["consent_step"] == '2') {
			$getRefresh = $conmssql->prepare("SELECT refresh_token,access_token,CONVERT(varchar,token_expire,20) as token_expire,CONVERT(varchar,refresh_expire,20) as refresh_expire 
											FROM gcauthconsent WHERE is_revoke = '0'");
			$getRefresh->execute();
			$rowRefreshToken = $getRefresh->fetch(PDO::FETCH_ASSOC);
			if(isset($rowRefreshToken["refresh_token"]) && $rowRefreshToken["refresh_token"] != ""){
				if($rowRefreshToken["token_expire"] <= date('Y-m-d H:i:s')){
					if(isset($dataComing["data_api"]["result"]["expireInSeconds"])){
						$updateToken = $conmssql->prepare("UPDATE gcauthconsent SET access_token = :access_token,token_expire = :token_expire WHERE is_revoke = '0'");
						$updateToken->execute([
							':access_token' => $dataComing["data_api"]["result"]["accessToken"],
							':token_expire' => date("Y-m-d H:i:s", strtotime("+".(ceil($dataComing["data_api"]["result"]["expireInSeconds"] / 1.1))." sec"))
						]);
					}else{
						$arrPrepare["refreshToken"] = $rowRefreshToken["refresh_token"];
						$arrAuth["HEADER"] = (object)[];
						$arrAuth["BODY"] = $arrPrepare;
						$arrAuth["data_loopback"] = $dataComing["data_api"];
						$arrAuth["URL"] = $config["CONSENT_API"].'/TokenAuth/RefreshToken';
						$arrayResult["CONSENT_STEP"] = $dataComing["consent_step"];
						$arrayResult["PREVIOUS_CALL"] = FALSE;
						$arrayResult["DATA_CONSENT"] = $arrAuth;
						$arrayResult['RESULT'] = TRUE;
						require_once('../../include/exit_footer.php');
					}
				}
				$accessToken = $dataComing["data_api"]["result"]["accessToken"] ?? $rowRefreshToken["access_token"];
				if($dataComing["resend"]){
					$dataConsentIncome = $dataComing["data_api"]["data_loopback"];
					$arrPrepare = array();
					$arrPrepare["identifier"] = $dataConsentIncome["tel_mobile"];
					$arrPrepare["mobileNo"] = $dataConsentIncome["tel_mobile"];
					$arrPrepare["langCode"] = $lang_locale;
					$arrPrepare["otpType"] = '2';
					if($lang_locale == 'th'){
						$arrPrepare["consentPoint"] = $config["CONSENT_POINTID_TH"];
					}else{
						$arrPrepare["consentPoint"] = $config["CONSENT_POINTID_EN"];
					}
					$arrPrepare["purposeIds"] = $dataConsentIncome["consent"]["purposeIds"];
					$arrAuth["HEADER"] = (object)["Authorization" => "Bearer ".$accessToken];
					$arrAuth["BODY"] = $arrPrepare;
					$arrAuth["data_loopback"] = $dataConsentIncome;
				}else{
					if(isset($dataComing["data_api"]["data_loopback"]) && $dataComing["data_api"]["data_loopback"] != ""){
						$dataConsentIncome = $dataComing["data_api"]["data_loopback"];
					}else{
						$dataConsentIncome = $dataComing["data_api"];
					}
					$arrPrepare = array();
					$arrPrepare["identifier"] = $dataConsentIncome["tel_mobile"];
					$arrPrepare["mobileNo"] = $dataConsentIncome["tel_mobile"];
					$arrPrepare["langCode"] = $lang_locale;
					$arrPrepare["otpType"] = '2';
					if($lang_locale == 'th'){
						$arrPrepare["consentPoint"] = $config["CONSENT_POINTID_TH"];
					}else{
						$arrPrepare["consentPoint"] = $config["CONSENT_POINTID_EN"];
					}
					$arrListPurpose = array();
					foreach($dataComing["data_api"]["purposes"] as $purposes){
						if($purposes["ACTIVE"] == 0){
							$arrListPurpose[] = $purposes["ID"];
						}
					}
					$arrPrepare["purposeIds"] = $arrListPurpose;
					$arrAuth["HEADER"] = (object)["Authorization" => "Bearer ".$accessToken];
					$arrAuth["BODY"] = $arrPrepare;
					$arrAuth["data_loopback"] = $dataComing["data_api"]["data_loopback"];
					$arrAuth["data_loopback"]["consent"] = $arrPrepare;
				}
				
				$arrAuth["URL"] = $config["CONSENT_API"].'/services/app/DataSubjects/RequestWithdrawnConsent';
				$arrayResult["CONSENT_STEP"] = '3';
				$arrayResult["PREVIOUS_CALL"] = FALSE;
				$arrayResult["DATA_CONSENT"] = $arrAuth;
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}
		}else if($dataComing["consent_step"] == '3'){
			$getRefresh = $conmssql->prepare("SELECT refresh_token,access_token,CONVERT(varchar,token_expire,20) as token_expire,CONVERT(varchar,refresh_expire,20) as refresh_expire 
											FROM gcauthconsent WHERE is_revoke = '0'");
			$getRefresh->execute();
			$rowRefreshToken = $getRefresh->fetch(PDO::FETCH_ASSOC);
			if(isset($rowRefreshToken["refresh_token"]) && $rowRefreshToken["refresh_token"] != ""){
				if($rowRefreshToken["token_expire"] <= date('Y-m-d H:i:s')){
					if(isset($dataComing["data_api"]["result"]["expireInSeconds"])){
						$updateToken = $conmssql->prepare("UPDATE gcauthconsent SET access_token = :access_token,token_expire = :token_expire WHERE is_revoke = '0'");
						$updateToken->execute([
							':access_token' => $dataComing["data_api"]["result"]["accessToken"],
							':token_expire' => date("Y-m-d H:i:s", strtotime("+".(ceil($dataComing["data_api"]["result"]["expireInSeconds"] / 1.1))." sec"))
						]);
					}else{
						$arrPrepare["refreshToken"] = $rowRefreshToken["refresh_token"];
						$arrAuth["HEADER"] = (object)[];
						$arrAuth["BODY"] = $arrPrepare;
						$arrAuth["data_loopback"] = $dataComing["data_api"];
						$arrAuth["URL"] = $config["CONSENT_API"].'/TokenAuth/RefreshToken';
						$arrayResult["CONSENT_STEP"] = $dataComing["consent_step"];
						$arrayResult["PREVIOUS_CALL"] = FALSE;
						$arrayResult["DATA_CONSENT"] = $arrAuth;
						$arrayResult["IS_OTP"] = false;
						$arrayResult['RESULT'] = TRUE;
						require_once('../../include/exit_footer.php');
					}
				}
				$accessToken = $dataComing["data_api"]["result"]["accessToken"] ?? $rowRefreshToken["access_token"];
				$arrPrepare = array();
				$arrPrepare["Key"] = $dataComing["data_api"]["result"]["otpRefCode"];
				$arrPrepare["KeyDisplay"] = $dataComing["data_api"]["result"]["otpRefCode"];
				$arrPrepare["Code"] = "";
				$arrAuth["HEADER"] = (object)["Authorization" => "Bearer ".$accessToken];
				$arrAuth["BODY"] = $arrPrepare;
				$arrAuth["data_loopback"] = $dataComing["data_api"]["data_loopback"];
				$arrAuth["URL"] = $config["CONSENT_API"].'/services/app/DataSubjects/WithdrawnVerifyOtp';
				$arrayResult["CONSENT_STEP"] = '4';
				$arrayResult["PREVIOUS_CALL"] = FALSE;
				$arrayResult["IS_OTP"] = true;
				$arrayResult["DATA_CONSENT"] = $arrAuth;
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}
		}else if($dataComing["consent_step"] == '4'){
			/*$insertChangeData = $conmssql->prepare("INSERT INTO gcmembereditdata(member_no,old_data,incoming_data,inputgroup_type)
													VALUES(:member_no,:old_data,:new_data,'tel')");
			$insertChangeData->execute([
				':member_no' => $payload["member_no"],
				':old_data' => null,
				':new_data' => $dataComing["data_api"]["data_loopback"]["tel_mobile"]
			]);*/
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}
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