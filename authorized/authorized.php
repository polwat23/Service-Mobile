<?php

namespace Authorized;

class API {
	
	public function check_apikey($api_key,$unique_id,$con){
		$updateAllUniqueID = $con->prepare("UPDATE gcapikey SET is_revoke = '-9',expire_date = NOW() WHERE unique_id = :unique_id
											api_key <> :api_key and is_revoke = '0'");
		$updateAllUniqueID->execute([':unique_id' => $unique_id,':api_key' => $api_key]);
		$checkAPIKey = $con->prepare("SELECT id_api,is_revoke FROM gcapikey WHERE api_key = :api_key");
		$checkAPIKey->execute([
			':api_key' => $api_key
		]);
		if($checkAPIKey->rowCount() > 0){
			$rowAPI = $checkAPIKey->fetch();
			if($rowAPI["is_revoke"] == '0'){
				return true;
			}else{
				$revokeAPI = $con->prepare("UPDATE gcapikey SET is_revoke = '-99',expire_date = NOW() WHERE id_api = :id_api");
				$revokeAPI->execute([':id_api' => $rowAPI["id_api"]]);
				return false;
			}
		}else{
			return false;
		}
	}
	
	public function refresh_accesstoken($refresh_token,$unique_id,$con,$channel,$payload,$jwt_token,$secret_key){
		$checkRT = $con->prepare("SELECT id_token,DATE_FORMAT(rt_expire_date,'%Y-%m-%d') as rt_expire_date,rt_is_revoke FROM gctoken
								WHERE refresh_token = :refresh_token and unique_id = :unique_id and id_api = :id_api");
		$checkRT->execute([
			':refresh_token' => $refresh_token,
			':unique_id' => $unique_id,
			':id_api' => $payload["id_api"]
		]);
		if($checkRT->rowCount() > 0){
			$Token = $checkRT->fetch();
			if($Token["rt_is_revoke"] == '0' && (empty($Token["rt_expire_date"]) || $Token["rt_expire_date"] > date('Y-m-d'))){
				if($channel == 'mobile_app'){
					$payload["exp"] = time() + 86400;
				}else{
					$payload["exp"] = time() + 900;
				}
				$payload["refresh_amount"] = $payload["refresh_amount"] + 1;
				$new_access_token = $jwt_token->customPayload($payload, $secret_key);
				$updateNewAT = $con->prepare("UPDATE gctoken SET access_token = :new_access_token,at_update_date = NOW()
												WHERE id_token = :id_token");
				if($updateNewAT->execute([
					':new_access_token' => $new_access_token,
					':id_token' => $Token["id_token"]
				])){
					$arrReturn = array();
					$arrReturn["ACCESS_TOKEN"] = $new_access_token;
					return $arrReturn;
				}else{
					return false;
				}
			}else{
				$revokeRefreshToken = $con->prepare("UPDATE gctoken SET rt_is_revoke = '-99',rt_expire_date = NOW()
													,at_is_revoke = '-99',at_expire_date = NOW()
													WHERE id_token = :id_token");
				$revokeRefreshToken->execute([':id_token' => $Token["id_token"]]);
				return false;
			}
		}else{
			return false;
		}
	}
}

?>