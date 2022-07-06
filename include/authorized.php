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
			$arrayReturn["ERROR_MESSAGE"] = "Not found token";
			$arrayReturn["VALIDATE"] = false;
			return $arrayReturn;
		}
	}
	public function CheckPeriodRefreshToken($refresh_token,$unique_id,$id_token,$con){
		$checkRT = $con->prepare("SELECT TO_DATE(rt_expire_date,'YYYY-MM-DD') as rt_expire_date,rt_is_revoke FROM gctoken
								WHERE refresh_token = :refresh_token and unique_id = :unique_id and id_token = :id_token");
		$checkRT->execute([
			':refresh_token' => $refresh_token,
			':unique_id' => $unique_id,
			':id_token' => $id_token
		]);
		$rowToken = $checkRT->fetch(\PDO::FETCH_ASSOC);
		if(isset($rowToken["RT_IS_REVOKE"])){
			if($rowToken["RT_IS_REVOKE"] === '0'){
				if(empty($rowToken["RT_EXPIRE_DATE"]) || ($rowToken["RT_EXPIRE_DATE"] > date('Y-m-d'))){
					return true;
				}else{
					$func = new functions();
					$func->revoke_alltoken($payload["id_token"],'-99',$con);
					return false;
				}
			}else{
				$func = new functions();
				$func->revoke_alltoken($payload["id_token"],$rowToken["RT_IS_REVOKE"],$con);
				return false;
			}
		}else{
			return false;
		}
	}
	public function refresh_accesstoken($refresh_token,$unique_id,$con,$payload,$jwt_token,$secret_key){
		
		try{
		$checkRT = $con->prepare("SELECT TO_DATE(rt_expire_date,'YYYY-MM-DD') as rt_expire_date,rt_is_revoke FROM gctoken
								WHERE refresh_token = :refresh_token and unique_id = :unique_id and id_token = :id_token");
		$checkRT->execute([
			':refresh_token' => $refresh_token,
			':unique_id' => $unique_id,
			':id_token' => $payload["id_token"]
		]);
		$rowToken = $checkRT->fetch(\PDO::FETCH_ASSOC);
		if(isset($rowToken["RT_IS_REVOKE"])){
			$getLimit = $con->prepare("SELECT constant_value FROM gcconstant WHERE constant_name = 'limit_session_timeout' and is_use = '1'");
			$getLimit->execute();
			$rowLimit = $getLimit->fetch(\PDO::FETCH_ASSOC);
			if($rowToken["RT_IS_REVOKE"] === '0'){
				if(empty($rowToken["RT_EXPIRE_DATE"]) || ($rowToken["RT_EXPIRE_DATE"] > date('Y-m-d'))){
					$payload["exp"] = time() + intval($rowLimit['CONSTANT_VALUE']);
					$payload["refresh_amount"] = $payload["refresh_amount"] + 1;
					$new_access_token = $jwt_token->customPayload($payload, $secret_key);
					$updateNewAT = $con->prepare("UPDATE gctoken SET access_token = :new_access_token,at_update_date = SYSDATE
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
					$func->revoke_alltoken($payload["id_token"],'-99',$con);
					return false;
				}
			}else{
				$func = new functions();
				$func->revoke_alltoken($payload["id_token"],$rowToken["RT_IS_REVOKE"],$con);
				return false;
			}
		}else{
			return false;
		}
		}catch(\Throwable $e){
			return true;
		}
	}
}

?>