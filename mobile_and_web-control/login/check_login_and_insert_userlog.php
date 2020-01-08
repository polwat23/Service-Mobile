<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['member_no','api_token','password','unique_id'],$dataComing)){
	$arrPayload = $auth->check_apitoken($dataComing["api_token"],$config["SECRET_KEY_JWT"]);
	if(!$arrPayload["VALIDATE"]){
		$arrayResult['RESPONSE_CODE'] = "WS0001";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(401);
		echo json_encode($arrayResult);
		exit();
	}
	$member_no = strtolower(str_pad($dataComing["member_no"],8,0,STR_PAD_LEFT));
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
			try{
				$conmysql->beginTransaction();
				$updateOldToken = $conmysql->prepare("UPDATE gctoken SET at_is_revoke = '-9',rt_is_revoke = '-9',
														rt_expire_date = NOW(),at_expire_date = NOW() 
														WHERE unique_id = :unique_id and (at_is_revoke = '0' OR rt_is_revoke = '0')");
				$updateOldToken->execute([
					':unique_id' => $dataComing["unique_id"]
				]);
				if($member_no != 'dev@mode' && $member_no != 'salemode' && $arrPayload["PAYLOAD"]["channel"] == 'mobile_app'){
					$getMemberLogged = $conmysql->prepare("SELECT id_token FROM gcuserlogin WHERE member_no = :member_no and channel = 'mobile_app' and is_login = '1'");
					$getMemberLogged->execute([':member_no' => $member_no]);
					if($getMemberLogged->rowCount() > 0){
						$arrayIdToken = array();
						$rowIdToken = $getMemberLogged->fetch();
						$arrayIdToken[] = $rowIdToken["id_token"];
						$updateLoggedOneDevice = $conmysql->prepare("UPDATE gctoken gt,gcuserlogin gu SET gt.rt_is_revoke = '-7',
																	gt.at_is_revoke = '-7',gt.rt_expire_date = NOW(),gt.at_expire_date = NOW(),
																	gu.is_login = '-7',gu.logout_date = NOW()
																	WHERE gt.id_token IN(".implode(',',$arrayIdToken).") and gu.id_token IN(".implode(',',$arrayIdToken).")");
						$updateLoggedOneDevice->execute();
					}
				}
				$insertToken = $conmysql->prepare("INSERT INTO gctoken(refresh_token,unique_id,channel,device_name,ip_address,fcm_token) 
													VALUES(:refresh_token,:unique_id,:channel,:device_name,:ip_address,:fcm_token)");
				if($insertToken->execute([
					':refresh_token' => $refresh_token,
					':unique_id' => $dataComing["unique_id"],
					':channel' => $arrPayload["PAYLOAD"]["channel"],
					':device_name' => $arrPayload["PAYLOAD"]["device_name"],
					':ip_address' => $arrPayload["PAYLOAD"]["ip_address"],
					':fcm_token' => $dataComing["fcm_token"] ?? null
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
						':device_name' => $arrPayload["PAYLOAD"]["device_name"],
						':channel' => $arrPayload["PAYLOAD"]["channel"],
						':unique_id' => $dataComing["unique_id"],
						':firstapp' => $firstapp,
						':id_token' => $id_token
					])){
						$arrPayloadNew = array();
						$arrPayloadNew['id_userlogin'] = $conmysql->lastInsertId();
						$arrPayloadNew['user_type'] = $rowPassword['user_type'];
						$arrPayloadNew['id_token'] = $id_token;
						$arrPayloadNew['member_no'] = $member_no;
						if($arrPayload["PAYLOAD"]["channel"] == 'mobile_app'){
							$arrPayloadNew['exp'] = time() + 86400;
						}else {
							$arrPayloadNew['exp'] = time() + 900;
						}
						$arrPayloadNew['refresh_amount'] = 0;
						$access_token = $jwt_token->customPayload($arrPayloadNew, $config["SECRET_KEY_JWT"]);
						$updateAccessToken = $conmysql->prepare("UPDATE gctoken SET access_token = :access_token WHERE id_token = :id_token");
						if($updateAccessToken->execute([
							':access_token' => $access_token,
							':id_token' => $id_token
						])){
							$conmysql->commit();
							$arrayResult['REFRESH_TOKEN'] = $refresh_token;
							$arrayResult['ACCESS_TOKEN'] = $access_token;
							// Pin Status : 9 => DEV, 1 => TRUE, 0 => FALSE
							if($arrPayload["PAYLOAD"]["channel"] == 'mobile_app'){
								if($rowPassword['user_type'] == '9'){
									$arrayResult['PIN'] = (isset($rowPassword["pin"]) ? 9 : 0);
								}else{
									$arrayResult['PIN'] = (isset($rowPassword["pin"]) ? 1 : 0);
								}
							}
							$arrayResult['RESULT'] = TRUE;
							echo json_encode($arrayResult);
						}else{
							$conmysql->rollback();
							$arrExecute = [
								':access_token' => $access_token,
								':id_token' => $id_token
							];
							$arrError = array();
							$arrError["EXECUTE"] = $arrExecute;
							$arrError["QUERY"] = $updateAccessToken;
							$arrError["ERROR_CODE"] = 'WS1001';
							$lib->addLogtoTxt($arrError,'login_error');
							$arrayResult['RESPONSE_CODE'] = "WS1001";
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
							$arrayResult['RESULT'] = FALSE;
							echo json_encode($arrayResult);
							exit();
						}
					}else{
						$conmysql->rollback();
						$arrExecute = [
							':member_no' => $member_no,
							':device_name' => $arrPayload["PAYLOAD"]["device_name"],
							':channel' => $arrPayload["PAYLOAD"]["channel"],
							':unique_id' => $dataComing["unique_id"],
							':firstapp' => $firstapp,
							':id_token' => $id_token
						];
						$arrError = array();
						$arrError["EXECUTE"] = $arrExecute;
						$arrError["QUERY"] = $insertLogin;
						$arrError["ERROR_CODE"] = 'WS1002';
						$lib->addLogtoTxt($arrError,'login_error');
						$arrayResult['RESPONSE_CODE'] = "WS1002";
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						echo json_encode($arrayResult);
						exit();
					}
				}else{
					$conmysql->rollback();
					$arrExecute = [
						':refresh_token' => $refresh_token,
						':unique_id' => $dataComing["unique_id"],
						':channel' => $arrPayload["PAYLOAD"]["channel"],
						':device_name' => $arrPayload["PAYLOAD"]["device_name"],
						':ip_address' => $arrPayload["PAYLOAD"]["ip_address"],
						':fcm_token' => $dataComing["fcm_token"] ?? null
					];
					$arrError = array();
					$arrError["EXECUTE"] = $arrExecute;
					$arrError["QUERY"] = $insertToken;
					$arrError["ERROR_CODE"] = 'WS1003';
					$lib->addLogtoTxt($arrError,'login_error');
					$arrayResult['RESPONSE_CODE'] = "WS1003";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}catch (PDOExecption $e) {
				$conmysql->rollback();
				$arrError = array();
				$arrError["MESSAGE"] = $e->getMessage();
				$arrError["ERROR_CODE"] = 'WS9999';
				$lib->addLogtoTxt($arrError,'exception_error');
				$arrayResult['RESPONSE_CODE'] = "WS9999";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0002";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0003";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>