<?php

namespace Authorized;

class API {
	
	public function check_apikey($api_key,$unique_id,$con){
		$checkAPIKey = $con->prepare("SELECT id_api FROM mdbapikey WHERE api_key = :api_key and unique_id = :unique_id and is_revoke = 0");
		$checkAPIKey->execute([
			':api_key' => $api_key,
			':unique_id' => $unique_id
		]);
		if($checkAPIKey->rowCount() > 0){
			return true;
		}else{
			return false;
		}
	}
	
	public function check_accesstoken($access_token,$con){
		$checkAT = $con->prepare("SELECT id_token FROM mdbtoken
								WHERE access_token = :access_token and at_is_revoke = 0 and at_expire_date > NOW()");
		$checkAT->execute([
			':access_token' => $access_token
		]);
		if($checkAT->rowCount() > 0){
			$Token = $checkAT->fetch();
			return $Token["id_token"];
		}else{
			return false;
		}
	}
	
	public function refresh_accesstoken($refresh_token,$unique_id,$con,$lib=null,$channel){
		$checkRT = $con->prepare("SELECT id_token FROM mdbtoken
								WHERE refresh_token = :refresh_token and rt_is_revoke = 0 and (rt_expire_date IS NULL || rt_expire_date > NOW())
								and unique_id = :unique_id");
		$checkRT->execute([
			':refresh_token' => $refresh_token,
			':unique_id' => $unique_id
		]);
		if($checkRT->rowCount() > 0){
			$Token = $checkRT->fetch();
			$date_expire;
			if($channel == 'mobile'){
				$date_expire = date('Y-m-d H:i:s',strtotime("+1 day"));
			}else{
				$date_expire = date('Y-m-d H:i:s',strtotime("+1 hour"));
			}
			$new_access_token = $lib->generate_token();
			$updateNewAT = $con->prepare("UPDATE mdbtoken SET access_token = :new_access_token,at_expire_date = :date_expire
											WHERE id_token = :id_token");
			if($updateNewAT->execute([
				':new_access_token' => $new_access_token,
				':date_expire' => $date_expire,
				':id_token' => $Token["id_token"]
			])){
				$arrReturn = array();
				$arrReturn["ACCESS_TOKEN"] = $new_access_token;
				$arrReturn["ID_TOKEN"] = $Token["id_token"];
				return $arrReturn;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}
}

?>