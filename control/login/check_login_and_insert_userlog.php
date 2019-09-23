<?php

require_once('../../autoload.php');

if(isset($dataComing["member_no"]) && isset($dataComing["api_key"]) && isset($dataComing["password"]) && isset($dataComing["unique_id"]) && 
isset($dataComing["channel"]) && isset($dataComing["id_api"]) && isset($dataComing["device_name"]) && isset($dataComing["platform"]) && isset($dataComing["fcm_token"])){
	$conmysql_nottest = $con->connecttomysql();
	if($api->check_apikey($dataComing["api_key"],$dataComing["unique_id"],$conmysql_nottest)){
		$arrayResult = array();
		$member_no = str_pad($dataComing["member_no"],8,0,STR_PAD_LEFT);
		$checkLogin = $conmysql->prepare("SELECT password,user_type,pin,account_status,temppass FROM mdbmemberaccount 
											WHERE member_no = :member_no and account_status <> '-8'");
		$checkLogin->execute([':member_no' => $member_no]);
		if($checkLogin->rowCount() > 0){
			$rowPassword = $checkLogin->fetch();
			if($rowPassword['account_status'] == '-9'){
				if($dataComing["password"] == $rowPassword['temppass']){
					$valid_pass = true;
				}else{
					$arrayResult['RESPONSE_CODE'] = "SQL403";
					$arrayResult['RESPONSE'] = "Temp password is invalid";
					$arrayResult['RESULT'] = FALSE;
					http_response_code(203);
					echo json_encode($arrayResult);
					exit();
				}
			}else{
				$valid_pass = password_verify($dataComing["password"], $rowPassword['password']);
			}
			if ($valid_pass) {
				$refresh_token = $lib->generate_token();
				$access_token = $lib->generate_token();
				$dateAfter1day = date('Y-m-d H:i:s',strtotime("+1 day"));
				$dateAfter1hours = date('Y-m-d H:i:s',strtotime("+1 hour"));
				if($dataComing["channel"] == 'mobile_app'){
					try{
						$conmysql->beginTransaction();
						$updateOldToken = $conmysql->prepare("UPDATE mdbtoken SET at_is_revoke = '-9',rt_is_revoke = '-9',
																rt_expire_date = NOW(),at_expire_date = NOW() WHERE unique_id = :unique_id and id_api = :id_api");
						$updateOldToken->execute([
							':unique_id' => $dataComing["unique_id"],
							':id_api' => $dataComing["id_api"]
						]);
						$insertToken = $conmysql->prepare("INSERT INTO mdbtoken(refresh_token,access_token,at_expire_date,unique_id,id_api) 
													VALUES(:refresh_token,:access_token,:expire_access_token,:unique_id,:id_api)");
						if($insertToken->execute([
							':refresh_token' => $refresh_token,
							':access_token' => $access_token,
							':expire_access_token' => $dateAfter1day,
							':unique_id' => $dataComing["unique_id"],
							':id_api' => $dataComing["id_api"]
						])){
							$id_token = $conmysql->lastInsertId();
							if(isset($dataComing["firsttime"])){
								$firstapp = 0;
							}else{
								$firstapp = 1;
							}
							$insertLogin = $conmysql->prepare("INSERT INTO mdbuserlogin(member_no,device_name,os_platform,channel,login_date,unique_id,status_firstapp,id_token) 
														VALUES(:member_no,:device_name,:platform,:channel,NOW(),:unique_id,:firstapp,:id_token)");
							if($insertLogin->execute([
								':member_no' => $member_no,
								':device_name' => $dataComing["device_name"],
								':platform' => $dataComing["platform"],
								':channel' => $dataComing["channel"],
								':unique_id' => $dataComing["unique_id"],
								':firstapp' => $firstapp,
								':id_token' => $id_token
							])){
								$updateFCMtoken = $conmysql_nottest->prepare("UPDATE mdbapikey SET fcm_token = :fcm_token WHERE id_api = :id_api");
								if($updateFCMtoken->execute([
									':fcm_token' => $dataComing["fcm_token"],
									':id_api' => $dataComing["id_api"]
								])){
									$arrayResult['ID_USERLOGIN'] = $conmysql->lastInsertId();
									$conmysql->commit();
									$arrayResult['USER_TYPE'] = $rowPassword['user_type'];
									$arrayResult['REFRESH_TOKEN'] = $refresh_token;
									$arrayResult['MEMBER_NO'] = $member_no;
									$arrayResult['ACCESS_TOKEN'] = $access_token;
									$arrayResult['PIN'] = (isset($rowPassword["pin"]) ? TRUE : FALSE);
									$arrayResult['RESULT'] = TRUE;
									echo json_encode($arrayResult);
								}else{
									$conmysql->rollback();
									$arrayResult['RESPONSE_CODE'] = "SQL500";
									$arrayResult['RESPONSE'] = "Update FCM Token Failed";
									$arrayResult['RESULT'] = FALSE;
									http_response_code(203);
									echo json_encode($arrayResult);
									exit();
								}
							}else{
								$conmysql->rollback();
								$arrayResult['RESPONSE_CODE'] = "SQL500";
								$arrayResult['RESPONSE'] = "Error! Cannot Insert User Log";
								$arrayResult['RESULT'] = FALSE;
								http_response_code(203);
								echo json_encode($arrayResult);
								exit();
							}
						}else{
							$conmysql->rollback();
							$arrayResult['RESPONSE_CODE'] = "SQL500";
							$arrayResult['RESPONSE'] = "Error! Cannot Insert Token";
							$arrayResult['RESULT'] = FALSE;
							http_response_code(203);
							echo json_encode($arrayResult);
							exit();
						}
					}catch (PDOExecption $e) {
						$conmysql->rollback();
						$arrayResult['RESPONSE_CODE'] = "SQL500";
						$arrayResult['RESPONSE'] = $e->getMessage();
						$arrayResult['RESULT'] = FALSE;
						http_response_code(203);
						echo json_encode($arrayResult);
						exit();
					}
				}else if($dataComing["channel"] == 'web'){
					try{
						$conmysql->beginTransaction();
						$updateOldToken = $conmysql->prepare("UPDATE mdbtoken SET at_is_revoke = '-9',rt_is_revoke = '-9',
																rt_expire_date = NOW(),at_expire_date = NOW() WHERE unique_id = :unique_id and id_api = :id_api");
						$updateOldToken->execute([
							':unique_id' => $dataComing["unique_id"],
							':id_api' => $dataComing["id_api"]
						]);
						$insertToken = $conmysql->prepare("INSERT INTO mdbtoken(refresh_token,access_token,rt_expire_date,at_expire_date,unique_id,id_api) 
													VALUES(:refresh_token,:access_token,:expire_refresh_token,:expire_access_token,:unique_id,:id_api)");
						if($insertToken->execute([
							':refresh_token' => $refresh_token,
							':access_token' => $access_token,
							':expire_refresh_token' => $dateAfter1day,
							':expire_access_token' => $dateAfter1hours,
							':unique_id' => $dataComing["unique_id"],
							':id_api' => $dataComing["id_api"]
						])){
							$id_token = $conmysql->lastInsertId();
							$insertLogin = $conmysql->prepare("INSERT INTO mdbuserlogin(member_no,device_name,os_platform,channel,login_date,unique_id,status_firstapp,id_token) 
														VALUES(:member_no,:device_name,:platform,:channel,NOW(),:unique_id,'1',:id_token)");
							if($insertLogin->execute([
								':member_no' => $member_no,
								':device_name' => $dataComing["device_name"],
								':platform' => $dataComing["platform"],
								':channel' => $dataComing["channel"],
								':unique_id' => $dataComing["unique_id"],
								':id_token' => $id_token
							])){
								$arrayResult['ID_USERLOGIN'] = $conmysql->lastInsertId();
								$conmysql->commit();
								$arrayResult['USER_TYPE'] = $rowPassword['user_type'];
								$arrayResult['REFRESH_TOKEN'] = $refresh_token;
								$arrayResult['ACCESS_TOKEN'] = $access_token;
								$arrayResult['MEMBER_NO'] = $member_no;
								$arrayResult['RESULT'] = TRUE;
								echo json_encode($arrayResult);
							}else{
								$conmysql->rollback();
								$arrayResult['RESPONSE_CODE'] = "SQL500";
								$arrayResult['RESPONSE'] = "Error! Cannot Insert User Log";
								$arrayResult['RESULT'] = FALSE;
								http_response_code(203);
								echo json_encode($arrayResult);
								exit();
							}
						}else{
							$conmysql->rollback();
							$arrayResult['RESPONSE_CODE'] = "SQL500";
							$arrayResult['RESPONSE'] = "Error! Cannot Insert Token";
							$arrayResult['RESULT'] = FALSE;
							http_response_code(203);
							echo json_encode($arrayResult);
							exit();
						}
					}catch (PDOExecption $e) {
						$conmysql->rollback();
						$arrayResult['RESPONSE_CODE'] = "SQL500";
						$arrayResult['RESPONSE'] = $e->getMessage();
						$arrayResult['RESULT'] = FALSE;
						http_response_code(203);
						echo json_encode($arrayResult);
						exit();
					}
				}else{
					$arrayResult['RESPONSE_CODE'] = "PARAM500";
					$arrayResult['RESPONSE'] = "Not available this channel";
					$arrayResult['RESULT'] = FALSE;
					http_response_code(203);
					echo json_encode($arrayResult);
					exit();
				}
			}else{
				$arrayResult['RESPONSE_CODE'] = "SQL403";
				$arrayResult['RESPONSE'] = "Invalid password";
				$arrayResult['RESULT'] = FALSE;
				http_response_code(203);
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "SQL400";
			$arrayResult['RESPONSE'] = "Don't have a member";
			$arrayResult['RESULT'] = FALSE;
			http_response_code(203);
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult = array();
		$arrayResult['RESPONSE_CODE'] = "PARAM500";
		$arrayResult['RESPONSE'] = "Invalid API KEY";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(203);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult = array();
	$arrayResult['RESPONSE_CODE'] = "PARAM400";
	$arrayResult['RESPONSE'] = "Not complete parameter";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(203);
	echo json_encode($arrayResult);
	exit();
}
?>