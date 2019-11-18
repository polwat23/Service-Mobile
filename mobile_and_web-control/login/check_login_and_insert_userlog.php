<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['member_no','api_key','password','channel','id_api','device_name','unique_id'],$dataComing)){
	$conmysql_nottest = $con->connecttomysql();
	if($auth->check_apikey($dataComing["api_key"],$dataComing["unique_id"],$conmysql_nottest)){
		$arrayResult = array();
		$member_no = str_pad($dataComing["member_no"],8,0,STR_PAD_LEFT);
		$checkLogin = $conmysql->prepare("SELECT password,user_type,pin,account_status,temppass FROM gcmemberaccount 
											WHERE member_no = :member_no and account_status NOT IN('-8','-7','-6')");
		$checkLogin->execute([':member_no' => $member_no]);
		if($checkLogin->rowCount() > 0){
			$rowPassword = $checkLogin->fetch();
			if($rowPassword['account_status'] == '-9'){
				if($dataComing["password"] == $rowPassword['temppass']){
					$valid_pass = true;
				}else{
					$valid_pass = false;
				}
			}else{
				$valid_pass = password_verify($dataComing["password"], $rowPassword['password']);
			}
			if ($valid_pass) {
				$refresh_token = $lib->generate_token();
				$dateAfter1day = date('Y-m-d H:i:s',strtotime("+1 day"));
				if($dataComing["channel"] == 'mobile_app'){
					try{
						$conmysql->beginTransaction();
						$updateOldToken = $conmysql->prepare("UPDATE gctoken SET at_is_revoke = '-9',rt_is_revoke = '-9',
																rt_expire_date = NOW(),at_expire_date = NOW() WHERE unique_id = :unique_id and id_api = :id_api");
						$updateOldToken->execute([
							':unique_id' => $dataComing["unique_id"],
							':id_api' => $dataComing["id_api"]
						]);
						$insertToken = $conmysql->prepare("INSERT INTO gctoken(refresh_token,unique_id,channel,id_api) VALUES(:refresh_token,:unique_id,:channel,:id_api)");
						if($insertToken->execute([
							':refresh_token' => $refresh_token,
							':unique_id' => $dataComing["unique_id"],
							':channel' => $dataComing["channel"],
							':id_api' => $dataComing["id_api"]
						])){
							$id_token = $conmysql->lastInsertId();
							if(isset($dataComing["firsttime"])){
								$firstapp = 0;
							}else{
								$firstapp = 1;
							}
							$insertLogin = $conmysql->prepare("INSERT INTO gcuserlogin(member_no,device_name,channel,unique_id,status_firstapp,id_token) 
														VALUES(:member_no,:device_name,:channel,:unique_id,:firstapp,:id_token)");
							if($insertLogin->execute([
								':member_no' => $member_no,
								':device_name' => $dataComing["device_name"],
								':channel' => $dataComing["channel"],
								':unique_id' => $dataComing["unique_id"],
								':firstapp' => $firstapp,
								':id_token' => $id_token
							])){
								$updateFCMtoken = $conmysql_nottest->prepare("UPDATE gcapikey SET fcm_token = :fcm_token,member_no = :member_no 
																				WHERE id_api = :id_api");
								if($updateFCMtoken->execute([
									':fcm_token' => $dataComing["fcm_token"],
									':member_no' => $member_no,
									':id_api' => $dataComing["id_api"]
								])){
									$arrPayload = array();
									$arrPayload['id_userlogin'] = $conmysql->lastInsertId();
									$arrPayload['user_type'] = $rowPassword['user_type'];
									$arrPayload['id_token'] = $id_token;
									$arrPayload['id_api'] = $dataComing["id_api"];
									$arrPayload['member_no'] = $member_no;
									$arrPayload['exp'] = time() + 86400;
									$arrPayload['refresh_amount'] = 0;
									$access_token = $jwt_token->customPayload($arrPayload, $config["SECRET_KEY_JWT"]);
									$updateAccessToken = $conmysql->prepare("UPDATE gctoken SET access_token = :access_token WHERE id_token = :id_token");
									if($updateAccessToken->execute([
										':access_token' => $access_token,
										':id_token' => $id_token
									])){
										$conmysql->commit();
										$arrayResult['REFRESH_TOKEN'] = $refresh_token;
										$arrayResult['ACCESS_TOKEN'] = $access_token;
										// Pin Status : 9 => DEV, 1 => TRUE, 0 => FALSE
										if($rowPassword['user_type'] == '9'){
											$arrayResult['PIN'] = (isset($rowPassword["pin"]) ? 9 : 0);
										}else{
											$arrayResult['PIN'] = (isset($rowPassword["pin"]) ? 1 : 0);
										}
										$arrayResult['RESULT'] = TRUE;
										echo json_encode($arrayResult);
									}else{
										$conmysql->rollback();
										$arrayResult['RESPONSE_CODE'] = "5005";
										$arrayResult['RESPONSE_AWARE'] = "update";
										$arrayResult['RESPONSE'] = "Cannot update Access Token";
										$arrayResult['RESULT'] = FALSE;
										echo json_encode($arrayResult);
										exit();
									}
								}else{
									$conmysql->rollback();
									$arrayResult['RESPONSE_CODE'] = "5005";
									$arrayResult['RESPONSE_AWARE'] = "update";
									$arrayResult['RESPONSE'] = "Update FCM Token Failed";
									$arrayResult['RESULT'] = FALSE;
									echo json_encode($arrayResult);
									exit();
								}
							}else{
								$conmysql->rollback();
								$arrayResult['RESPONSE_CODE'] = "5005";
								$arrayResult['RESPONSE_AWARE'] = "insert";
								$arrayResult['RESPONSE'] = "Error! Cannot Insert User Log";
								$arrayResult['RESULT'] = FALSE;
								echo json_encode($arrayResult);
								exit();
							}
						}else{
							$conmysql->rollback();
							$arrayResult['RESPONSE_CODE'] = "5005";
							$arrayResult['RESPONSE_AWARE'] = "insert";
							$arrayResult['RESPONSE'] = "Error! Cannot Insert Token";
							$arrayResult['RESULT'] = FALSE;
							echo json_encode($arrayResult);
							exit();
						}
					}catch (PDOExecption $e) {
						$conmysql->rollback();
						$arrayResult['RESPONSE_CODE'] = "5005";
						$arrayResult['RESPONSE_AWARE'] = "anything";
						$arrayResult['RESPONSE'] = $e->getMessage();
						$arrayResult['RESULT'] = FALSE;
						echo json_encode($arrayResult);
						exit();
					}
				}else if($dataComing["channel"] == 'web'){
					try{
						$conmysql->beginTransaction();
						$updateOldToken = $conmysql->prepare("UPDATE gctoken SET at_is_revoke = '-9',rt_is_revoke = '-9',
																rt_expire_date = NOW(),at_expire_date = NOW() WHERE unique_id = :unique_id and id_api = :id_api");
						$updateOldToken->execute([
							':unique_id' => $dataComing["unique_id"],
							':id_api' => $dataComing["id_api"]
						]);
						$insertToken = $conmysql->prepare("INSERT INTO gctoken(refresh_token,rt_expire_date,unique_id,channel,id_api) 
													VALUES(:refresh_token,:expire_refresh_token,:unique_id,:channel,:id_api)");
						if($insertToken->execute([
							':refresh_token' => $refresh_token,
							':expire_refresh_token' => $dateAfter1day,
							':unique_id' => $dataComing["unique_id"],
							':channel' => $dataComing["channel"],
							':id_api' => $dataComing["id_api"]
						])){
							$id_token = $conmysql->lastInsertId();
							$insertLogin = $conmysql->prepare("INSERT INTO gcuserlogin(member_no,device_name,channel,unique_id,id_token) 
																VALUES(:member_no,:device_name,:channel,:unique_id,:id_token)");
							if($insertLogin->execute([
								':member_no' => $member_no,
								':device_name' => $dataComing["device_name"],
								':channel' => $dataComing["channel"],
								':unique_id' => $dataComing["unique_id"],
								':id_token' => $id_token
							])){
								$updateFCMtoken = $conmysql_nottest->prepare("UPDATE gcapikey SET member_no = :member_no 
																				WHERE id_api = :id_api");
								if($updateFCMtoken->execute([
									':member_no' => $member_no,
									':id_api' => $dataComing["id_api"]
								])){
									$arrPayload = array();
									$arrPayload['id_userlogin'] = $conmysql->lastInsertId();
									$arrPayload['user_type'] = $rowPassword['user_type'];
									$arrPayload['id_token'] = $id_token;
									$arrPayload['id_api'] = $dataComing["id_api"];
									$arrPayload['member_no'] = $member_no;
									$arrPayload['exp'] = time() + 900;
									$arrPayload['refresh_amount'] = 0;
									$access_token = $jwt_token->customPayload($arrPayload, $config["SECRET_KEY_JWT"]);
									$updateAccessToken = $conmysql->prepare("UPDATE gctoken SET access_token = :access_token WHERE id_token = :id_token");
									if($updateAccessToken->execute([
										':access_token' => $access_token,
										':id_token' => $id_token
									])){
										$conmysql->commit();
										$arrayResult['REFRESH_TOKEN'] = $refresh_token;
										$arrayResult['ACCESS_TOKEN'] = $access_token;
										$arrayResult['PIN'] = (isset($rowPassword["pin"]) ? TRUE : FALSE);
										$arrayResult['RESULT'] = TRUE;
										echo json_encode($arrayResult);
									}else{
										$conmysql->rollback();
										$arrayResult['RESPONSE_CODE'] = "5005";
										$arrayResult['RESPONSE_AWARE'] = "update";
										$arrayResult['RESPONSE'] = "Cannot update Access Token";
										$arrayResult['RESULT'] = FALSE;
										echo json_encode($arrayResult);
										exit();
									}
								}else{
									$conmysql->rollback();
									$arrayResult['RESPONSE_CODE'] = "5005";
									$arrayResult['RESPONSE_AWARE'] = "update";
									$arrayResult['RESPONSE'] = "Update Member no Failed";
									$arrayResult['RESULT'] = FALSE;
									echo json_encode($arrayResult);
									exit();
								}
							}else{
								$conmysql->rollback();
								$arrayResult['RESPONSE_CODE'] = "5005";
								$arrayResult['RESPONSE_AWARE'] = "insert";
								$arrayResult['RESPONSE'] = "Error! Cannot Insert User Log";
								$arrayResult['RESULT'] = FALSE;
								echo json_encode($arrayResult);
								exit();
							}
						}else{
							$conmysql->rollback();
							$arrayResult['RESPONSE_CODE'] = "5005";
							$arrayResult['RESPONSE_AWARE'] = "insert";
							$arrayResult['RESPONSE'] = "Error! Cannot Insert Token";
							$arrayResult['RESULT'] = FALSE;
							echo json_encode($arrayResult);
							exit();
						}
					}catch (PDOExecption $e) {
						$conmysql->rollback();
						$arrayResult['RESPONSE_CODE'] = "5005";
						$arrayResult['RESPONSE_AWARE'] = "anything";
						$arrayResult['RESPONSE'] = $e->getMessage();
						$arrayResult['RESULT'] = FALSE;
						echo json_encode($arrayResult);
						exit();
					}
				}else{
					$arrayResult['RESPONSE_CODE'] = "4005";
					$arrayResult['RESPONSE_AWARE'] = "channel";
					$arrayResult['RESPONSE'] = "Not available this channel";
					$arrayResult['RESULT'] = FALSE;
					http_response_code(403);
					echo json_encode($arrayResult);
					exit();
				}
			}else{
				$arrayResult['RESPONSE_CODE'] = "4003";
				$arrayResult['RESPONSE_AWARE'] = "password";
				$arrayResult['RESPONSE'] = "Invalid password";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			http_response_code(404);
			exit();
		}
	}else{
		$arrayResult = array();
		$arrayResult['RESPONSE_CODE'] = "4007";
		$arrayResult['RESPONSE_AWARE'] = "api";
		$arrayResult['RESPONSE'] = "Invalid API KEY";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(407);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "4004";
	$arrayResult['RESPONSE_AWARE'] = "argument";
	$arrayResult['RESPONSE'] = "Not complete argument";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>