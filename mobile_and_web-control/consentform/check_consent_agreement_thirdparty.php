<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ConsentAgreement')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		if($dataComing["consent_step"] == '0'){
			$arrayResult["CONSENT_STEP"] = '1';
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else if($dataComing["consent_step"] == '1'){
			$getRefresh = $conmssql->prepare("SELECT refresh_token,access_token,CONVERT(varchar,token_expire,20) as token_expire,
											CONVERT(varchar,refresh_expire,20) as refresh_expire 
											FROM gcauthconsent WHERE is_revoke = '0'");
			$getRefresh->execute();
			$rowRefreshToken = $getRefresh->fetch(PDO::FETCH_ASSOC);
			$checkOldFormTel = $conmssql->prepare("SELECT COUNT(id_editdata) as C_FORMNOTAPPROVE FROM gcmembereditdata 
												WHERE member_no = :member_no and inputgroup_type = 'tel' and is_updateoncore = '0'");
			$checkOldFormTel->execute([':member_no' => $payload["member_no"]]);
			$rowOldFormTel = $checkOldFormTel->fetch(PDO::FETCH_ASSOC);
			if($rowOldFormTel["C_FORMNOTAPPROVE"] > 0){
				$getTelMobile = $conmssql->prepare("SELECT incoming_data as phone_number FROM gcmembereditdata WHERE member_no = :member_no
													and inputgroup_type = 'tel' and is_updateoncore = '0'");
				$getTelMobile->execute([':member_no' => $payload["member_no"]]);
				$rowTelMobile = $getTelMobile->fetch(PDO::FETCH_ASSOC);
			}else{
				$getTelMobile = $conmssqlcoop->prepare("SELECT telephone as phone_number FROM COCOOPTATION WHERE member_id = :member_no");
				$getTelMobile->execute([':member_no' => $member_no]);
				$rowTelMobile = $getTelMobile->fetch(PDO::FETCH_ASSOC);
			}
			if(isset($rowRefreshToken["refresh_token"]) && $rowRefreshToken["refresh_token"] != ""){
				if($rowRefreshToken["token_expire"] <= date('Y-m-d H:i:s')){
					$arrPrepare = array();
					$arrPrepare["refreshToken"] = $rowRefreshToken["refresh_token"];
					$arrAuth["HEADER"] = (object)[];
					$arrAuth["BODY"] = $arrPrepare;
					$arrAuth["URL"] = $config["CONSENT_API"].'/TokenAuth/RefreshToken';
					$arrayResult["CONSENT_STEP"] = '2';
					$arrAuth["data_loopback"]["tel_mobile"] = $rowTelMobile["phone_number"];
					$arrayResult["DATA_CONSENT"] = $arrAuth;
					$arrayResult['RESULT'] = TRUE;
					require_once('../../include/exit_footer.php');
				}else{
					$arrAuth["data_loopback"]["tel_mobile"] = $rowTelMobile["phone_number"];
					$arrayResult["DATA_CONSENT"] = $arrAuth;
					$arrayResult["CONSENT_STEP"] = '2';
					$arrayResult['RESULT'] = TRUE;
					require_once('../../include/exit_footer.php');
				}
			}else{
				$arrPrepare = array();
				$arrPrepare["userNameOrEmailAddress"] = $config["CONSENT_UN"];
				$arrPrepare["password"] = $config["CONSENT_PW"];
				$arrAuth["HEADER"] = (object)[];
				$arrAuth["BODY"] = $arrPrepare;
				$arrAuth["URL"] = $config["CONSENT_API"].'/TokenAuth/Authenticate';
				$arrAuth["data_loopback"]["tel_mobile"] = $rowTelMobile["phone_number"];
				$arrayResult["CONSENT_STEP"] = '2';
				$arrayResult["PREVIOUS_CALL"] = FALSE;
				$arrayResult["DATA_CONSENT"] = $arrAuth;
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}
		}else if($dataComing["consent_step"] == '2'){
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
						$arrAuth["URL"] = $config["CONSENT_API"].'/TokenAuth/RefreshToken';
						$arrAuth["data_loopback"]["tel_mobile"] = $dataComing["data_api"]["tel_mobile"];
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
				if(isset($dataComing["data_api"]["data_loopback"]) && $dataComing["data_api"]["data_loopback"] != ""){
					$arrAuth["data_loopback"]["tel_mobile"] = $dataComing["data_api"]["data_loopback"]["tel_mobile"];
				}else{
					$arrAuth["data_loopback"]["tel_mobile"] = $dataComing["data_api"]["tel_mobile"];
				}
				$arrPrepare["identifier"] = $arrAuth["data_loopback"]["tel_mobile"];
				$arrPrepare["langCode"] = $lang_locale;
				$arrAuth["HEADER"] = (object)["Authorization" => "Bearer ".$accessToken];
				$arrAuth["BODY"] = $arrPrepare;
				$arrAuth["METHOD"] = "GET";
				$arrAuth["URL"] = $config["CONSENT_API"].'/services/app/ConsentPoints/GetActivePurposes';
				$arrayResult["CONSENT_STEP"] = '3';
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
				if(isset($dataComing["data_api"]["data_loopback"]) && $dataComing["data_api"]["data_loopback"] != ""){
					$arrAuth["data_loopback"]["tel_mobile"] = $dataComing["data_api"]["data_loopback"]["tel_mobile"];
				}else{
					$arrAuth["data_loopback"]["tel_mobile"] = $dataComing["data_api"]["tel_mobile"];
				}
				$arrPrepare["identifier"] = $arrAuth["data_loopback"]["tel_mobile"];
				$arrPrepare["langCode"] = $lang_locale;
				$arrAuth["HEADER"] = (object)["Authorization" => "Bearer ".$accessToken];
				$arrAuth["BODY"] = $arrPrepare;
				$arrAuth["METHOD"] = "GET";
				$arrAuth["URL"] = $config["CONSENT_API"].'/services/app/ConsentPoints/GetActivePurposes';
				$arrayResult["CONSENT_STEP"] = '3';
				$arrayResult["PREVIOUS_CALL"] = FALSE;
				$arrayResult["DATA_CONSENT"] = $arrAuth;
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}
		}else if($dataComing["consent_step"] == '3'){
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
					if($lang_locale == 'th'){
						$arrConsentGrp["SUBTITLE"] = "ยินยอมให้บริษัทฯ และบริษัทในกลุ่มเอ็มบีเค และพันธมิตรทางธุรกิจประมวลผลข้อมูลส่วนบุคคลของข้าพเจ้า เพื่อวัตถุประสงค์ดังต่อไปนี้";
						$arrConsentGrp["REMARK"] = "หมายเหตุ: ท่านสามารถเลือกให้ความยินยอมทั้งหมด บางส่วน หรือไม่ให้ความยินยอมได้ หากท่านปฏิเสธไม่ให้ความยินยอมหรือถอนความยินยอมที่ท่านได้ให้ไว้ บริษัทฯ อาจไม่สามารถดำเนินการให้บริการให้ตรงกับความต้องการของท่านได้อย่างมีประสิทธิภาพ";
						$arrConsentGrp["TITLE"] = 'การขอความยินยอม';
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
					$arrayResult["CONSENT_STEP"] = '4';
					$arrayResult["PREVIOUS_CALL"] = FALSE;
					$arrayResult['RESULT'] = TRUE;
					require_once('../../include/exit_footer.php');
				}else{
					$arrayResult['RESULT'] = TRUE;
					require_once('../../include/exit_footer.php');
				}
			}	
		}else if($dataComing["consent_step"] == '4'){
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
					$arrPrepare["otpType"] = '1';
					if($lang_locale == 'th'){
						$arrPrepare["consentPoint"] = $config["CONSENT_POINTID_TH"];
					}else{
						$arrPrepare["consentPoint"] = $config["CONSENT_POINTID_EN"];
					}
					$arrPrepare["purposeConsent"] = $dataConsentIncome["consent"]["purposeConsent"];
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
					$arrPrepare["otpType"] = '1';
					if($lang_locale == 'th'){
						$arrPrepare["consentPoint"] = $config["CONSENT_POINTID_TH"];
					}else{
						$arrPrepare["consentPoint"] = $config["CONSENT_POINTID_EN"];
					}
					$arrListPurpose = array();
					foreach($dataComing["data_api"]["purposes"] as $purposes){
						$arrPurposes = array();
						$arrPurposes['id'] = $purposes["ID"];
						$arrPurposes['consentStatus'] = $purposes["ACTIVE"] == 0 ? 'false' : 'true';
						$arrListPurpose[] = $arrPurposes;
					}
					$arrPrepare["purposeConsent"] = $arrListPurpose;
					$arrAuth["HEADER"] = (object)["Authorization" => "Bearer ".$accessToken];
					$arrAuth["BODY"] = $arrPrepare;
					$arrAuth["data_loopback"] = $dataComing["data_api"]["data_loopback"];
					$arrAuth["data_loopback"]["consent"] = $arrPrepare;
				}
				
				$arrAuth["URL"] = $config["CONSENT_API"].'/services/app/ConsentTrans/SubmitConsent';
				$arrayResult["CONSENT_STEP"] = '5';
				$arrayResult["PREVIOUS_CALL"] = FALSE;
				$arrayResult["DATA_CONSENT"] = $arrAuth;
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}
		}else if($dataComing["consent_step"] == '5'){
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
				$arrPrepare["KeyDisplay"] = $dataComing["data_api"]["result"]["otpRefCodeDisplay"];
				$arrPrepare["Code"] = "";
				$arrAuth["HEADER"] = (object)["Authorization" => "Bearer ".$accessToken];
				$arrAuth["BODY"] = $arrPrepare;
				$arrAuth["data_loopback"] = $dataComing["data_api"]["data_loopback"];
				$arrAuth["URL"] = $config["CONSENT_API"].'/services/app/OptTrans/VerifyOtp';
				$arrayResult["CONSENT_STEP"] = '6';
				$arrayResult["PREVIOUS_CALL"] = FALSE;
				$arrayResult["IS_OTP"] = true;
				$arrayResult["DATA_CONSENT"] = $arrAuth;
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}
		}else if($dataComing["consent_step"] == '6'){
			$template = $func->getTemplateSystem('NotifyStaffUpdateData');
			$arrayDataTemplate = array();
			$arrayDataTemplate["MEMBER_NO"] = $payload["member_no"];
			$arrayDataTemplate["DEVICE_NAME"] = $dataComing["device_name"].' / On app version => '.$dataComing["app_version"];
			$arrayDataTemplate["REQUEST_DATE"] = $lib->convertdate(date('Y-m-d H:i'),'D m Y',true);
			
			$arrResponse = $lib->mergeTemplate($template["SUBJECT"],$template["BODY"],$arrayDataTemplate);
			$arrMailStatus = $lib->sendMail($config["MAIL_FOR_NOTI"],$arrResponse["SUBJECT"],$arrResponse["BODY"],$mailFunction);
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