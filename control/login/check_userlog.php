<?php
require_once('../../autoload.php');

$arrayResult = array();
if(isset($dataComing["access_token"]) && isset($dataComing["unique_id"]) && isset($dataComing["channel"]) && 
isset($dataComing["pin"]) && isset($dataComing["member_no"]) && isset($dataComing["id_userlogin"]) && isset($dataComing["refresh_token"])){
	$is_accessToken = $api->check_accesstoken($dataComing["access_token"],$conmysql);
	$id_token = null;
	$new_token = null;
	if(!$is_accessToken){
		$is_refreshToken_arr = $api->refresh_accesstoken($dataComing["refresh_token"],$dataComing["unique_id"],$conmysql,$lib,$dataComing["channel"]);
		if(!$is_refreshToken_arr){
			$arrayResult['RESPONSE_CODE'] = "SQL409";
			$arrayResult['RESPONSE'] = "Invalid Access Maybe AccessToken and RefreshToken is not correct";
			$arrayResult['RESULT'] = FALSE;
			http_response_code(203);
			echo json_encode($arrayResult);
			exit();
		}else{
			$id_token = $is_refreshToken_arr["ID_TOKEN"];
			$new_token = $is_refreshToken_arr["ACCESS_TOKEN"];
		}
	}else{
		$id_token = $is_accessToken;
	}
	$checkPinNull = $conmysql->prepare("SELECT pin FROM mdbmemberaccount WHERE member_no = :member_no");
	$checkPinNull->execute([':member_no' => $dataComing["member_no"]]);
	$rowPinNull = $checkPinNull->fetch();
	if(isset($rowPinNull["pin"])){
		$checkPin = $conmysql->prepare("SELECT id_account FROM mdbmemberaccount WHERE member_no = :member_no and pin = :pin");
		$checkPin->execute([
			':member_no' => $dataComing["member_no"],
			':pin' => $dataComing["pin"]
		]);
		if($checkPin->rowCount() > 0){
			$insertToLogAccess = $conmysql->prepare("INSERT INTO mdbuseraccessafterlogin(access_date,id_userlogin) 
													VALUES(NOW(),:id_userlogin)");
			if($insertToLogAccess->execute([':id_userlogin' => $dataComing["id_userlogin"]])){
				$arrayResult['RESULT'] = TRUE;
				if(isset($new_token)){
					$arrayResult['NEW_TOKEN'] = $new_token;
				}
				echo json_encode($arrayResult);
			}else{
				$func->logout($id_token,'-9',$conmysql);
				$arrayResult['RESPONSE_CODE'] = "SQL500";
				$arrayResult['RESPONSE'] = "Cannot Insert User Access";
				$arrayResult['RESULT'] = FALSE;
				http_response_code(203);
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "SQL403";
			$arrayResult['RESPONSE'] = "Invalid Pin";
			$arrayResult['RESULT'] = FALSE;
			http_response_code(203);
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$conmysql->beginTransaction();
		$updatePin = $conmysql->prepare("UPDATE mdbmemberaccount SET pin = :pin WHERE member_no = :member_no");
		if($updatePin->execute([
			':pin' => $dataComing["pin"],
			':member_no' => $dataComing["member_no"]
		])){
			$insertToLogAccess = $conmysql->prepare("INSERT INTO mdbuseraccessafterlogin(access_date,id_userlogin) 
													VALUES(NOW(),:id_userlogin)");
			if($insertToLogAccess->execute([':id_userlogin' => $dataComing["id_userlogin"]])){
				$conmysql->commit();
				$arrayResult['RESULT'] = TRUE;
				if(isset($new_token)){
					$arrayResult['NEW_TOKEN'] = $new_token;
				}
				echo json_encode($arrayResult);
			}else{
				$conmysql->rollback();
				$func->logout($id_token,'-9',$conmysql);
				$arrayResult['RESPONSE_CODE'] = "SQL500";
				$arrayResult['RESPONSE'] = "Cannot Insert User Access";
				$arrayResult['RESULT'] = FALSE;
				http_response_code(203);
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$conmysql->rollback();
			$arrayResult['RESPONSE_CODE'] = "SQL500";
			$arrayResult['RESPONSE'] = "Update Pin Failed";
			$arrayResult['RESULT'] = FALSE;
			http_response_code(203);
			echo json_encode($arrayResult);
			exit();
		}
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "PARAM400";
	$arrayResult['RESPONSE'] = "Not complete parameter";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(203);
	echo json_encode($arrayResult);
	exit();
}
?>