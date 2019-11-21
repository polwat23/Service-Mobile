<?php
declare(strict_types=1);

namespace Authorized;

use ReallySimpleJWT\{Parse,Encode,Validate,Jwt};
use ReallySimpleJWT\Exception\ValidateException;
use Component\functions;

class Authorization {
	
	public function check_apitoken($api_token, string $secret_key) : array {
		if(isset($api_token)){
			$jwt = new Jwt($api_token, $secret_key);

			$parse_token = new Parse($jwt, new Validate(), new Encode());
			$arrayReturn = array();
			try{
				$parsed_token = $parse_token->validate()
					->validateExpiration()
					->parse();
				$payload = $parsed_token->getPayload();
				$arrayReturn["PAYLOAD"] = $payload;
				$arrayReturn["VALIDATE"] = true;
				return $arrayReturn;
			}catch (ValidateException $e) {
				$arrayReturn["ERROR_MESSAGE"] = $e->getMessage();
				$arrayReturn["VALIDATE"] = false;
				return $arrayReturn;
			}
		}else{
			$arrayReturn["ERROR_MESSAGE"] = "Cannot access";
			$arrayReturn["VALIDATE"] = false;
			return $arrayReturn;
		}
	}
	
	public function refresh_accesstoken($refresh_token,$unique_id,$con,$channel,$payload,$jwt_token,$secret_key){
		$checkRT = $con->prepare("SELECT DATE_FORMAT(rt_expire_date,'%Y-%m-%d') as rt_expire_date,rt_is_revoke FROM gctoken
								WHERE refresh_token = :refresh_token and unique_id = :unique_id and id_token = :id_token
								and rt_is_revoke = '0'");
		$checkRT->execute([
			':refresh_token' => $refresh_token,
			':unique_id' => $unique_id,
			':id_token' => $payload["id_token"]
		]);
		if($checkRT->rowCount() > 0){
			$Token = $checkRT->fetch();
			if(empty($Token["rt_expire_date"]) || ($Token["rt_expire_date"] > date('Y-m-d'))){
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
					':id_token' => $payload["id_token"]
				])){
					$arrReturn = array();
					$arrReturn["ACCESS_TOKEN"] = $new_access_token;
					return $arrReturn;
				}else{
					return false;
				}
			}else{
				$func = new functions();
				$func->revoke_accesstoken($payload["id_token"],'-99',$con);
				$func->revoke_refreshtoken($payload["id_token"],'-99',$con);
				return false;
			}
		}else{
			return false;
		}
	}
}

?>