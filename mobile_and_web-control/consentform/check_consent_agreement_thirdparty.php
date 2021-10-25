<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ConsentAgreement')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		if($dataComing["consent_step"] == '0'){
			$getRefresh = $conmssql->prepare("SELECT refresh_token,access_token,CONVERT(varchar,token_expire,20) as token_expire,CONVERT(varchar,refresh_expire,20) as refresh_expire 
											FROM gcauthconsent WHERE is_revoke = '0'");
			$getRefresh->execute();
			$rowRefreshToken = $getRefresh->fetch(PDO::FETCH_ASSOC);
			if(isset($rowRefreshToken["refresh_token"]) && $rowRefreshToken["refresh_token"] != ""){
				if($rowRefreshToken["token_expire"] <= date('Y-m-d H:i:s')){
					$arrPrepare = array();
					$arrPrepare["refreshToken"] = $rowRefreshToken["refresh_token"];
					$arrAuth["HEADER"] = (object)[];
					$arrAuth["BODY"] = $arrPrepare;
					$arrAuth["URL"] = $config["CONSENT_API"].'/TokenAuth/RefreshToken';
					$arrayResult["CONSENT_STEP"] = '1';
					$arrayResult["DATA_CONSENT"] = $arrAuth;
					$arrayResult['RESULT'] = TRUE;
					require_once('../../include/exit_footer.php');
				}else{
					$arrayResult["CONSENT_STEP"] = '1';
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
				$arrayResult["CONSENT_STEP"] = '1';
				$arrayResult["PREVIOUS_CALL"] = FALSE;
				$arrayResult["DATA_CONSENT"] = $arrAuth;
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}
		}else if($dataComing["consent_step"] == '1'){
			$getRefresh = $conmssql->prepare("SELECT refresh_token,access_token,CONVERT(varchar,token_expire,20) as token_expire,CONVERT(varchar,refresh_expire,20) as refresh_expire 
											FROM gcauthconsent WHERE is_revoke = '0'");
			$getRefresh->execute();
			$rowRefreshToken = $getRefresh->fetch(PDO::FETCH_ASSOC);
			if(isset($rowRefreshToken["refresh_token"]) && $rowRefreshToken["refresh_token"] != ""){
				if($rowRefreshToken["token_expire"] <= date('Y-m-d H:i:s')){
					$updateToken = $conmssql->prepare("UPDATE gcauthconsent SET access_token = :access_token,token_expire = :token_expire WHERE is_revoke = '0'");
					$updateToken->execute([
						':access_token' => $dataComing["data_api"]["result"]["accessToken"],
						':token_expire' => date("Y-m-d H:i:s", strtotime("+".(ceil($dataComing["data_api"]["result"]["expireInSeconds"] / 1.1))." sec"))
					]);
				}
				$accessToken = $dataComing["data_api"]["result"]["accessToken"] ?? $rowRefreshToken["access_token"];
				$arrPrepare = array();
				$arrPrepare["Filter"] = "MBKCOP_CoMarketing_TH_V1";
				$arrAuth["HEADER"] = (object)["Authorization" => "Bearer ".$accessToken];
				$arrAuth["BODY"] = $arrPrepare;
				$arrAuth["METHOD"] = "GET";
				$arrAuth["URL"] = $config["CONSENT_API"].'/services/app/ConsentPoints/GetAll';
				$arrayResult["CONSENT_STEP"] = '2';
				$arrayResult["PREVIOUS_CALL"] = FALSE;
				$arrayResult["DATA_CONSENT"] = $arrAuth;
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				if(isset($dataComing["data_api"]["result"]) && $dataComing["data_api"]["result"] != ""){
					$insertToken = $conmssql->prepare("INSERT INTO gcauthconsent(refresh_token,access_token,token_expire,id_userlogin,member_no,refresh_expire)
													VALUES(:refresh_token,:access_token,:token_expire,:id_userlogin,:member_no,:refresh_expire)");
					$insertToken->execute([
						':refresh_token' => $dataComing["data_api"]["result"]["refreshToken"],
						':access_token' => $dataComing["data_api"]["result"]["accessToken"],
						':token_expire' => date("Y-m-d H:i:s", strtotime("+".(ceil($dataComing["data_api"]["result"]["expireInSeconds"] / 1.1))." sec")),
						':refresh_expire' =>  date("Y-m-d H:i:s", strtotime("+".(ceil($dataComing["data_api"]["result"]["refreshTokenExpireInSeconds"] / 1.1))." sec"))
					]);
					$arrayResult['DEBUG'] = $insertToken->queryString.' / '.json_encode([
						':refresh_token' => $dataComing["data_api"]["result"]["refreshToken"],
						':access_token' => $dataComing["data_api"]["result"]["accessToken"],
						':token_expire' => date("Y-m-d H:i:s", strtotime("+".(ceil($dataComing["data_api"]["result"]["expireInSeconds"] / 1.1))." sec")),
						':refresh_expire' =>  date("Y-m-d H:i:s", strtotime("+".(ceil($dataComing["data_api"]["result"]["refreshTokenExpireInSeconds"] / 1.1))." sec"))
					]);
					$accessToken = $dataComing["data_api"]["result"]["accessToken"];
					$arrPrepare = array();
					$arrPrepare["Filter"] = "MBKCOP_CoMarketing_TH_V1";
					$arrAuth["HEADER"] = (object)["Authorization" => "Bearer ".$accessToken];
					$arrAuth["METHOD"] = "GET";
					$arrAuth["BODY"] = $arrPrepare;
					$arrAuth["URL"] = $config["CONSENT_API"].'/services/app/ConsentPoints/GetAll';
					$arrayResult["CONSENT_STEP"] = '2';
					$arrayResult["PREVIOUS_CALL"] = FALSE;
					$arrayResult["DATA_CONSENT"] = $arrAuth;
					$arrayResult['RESULT'] = TRUE;
					require_once('../../include/exit_footer.php');
				}
			}
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