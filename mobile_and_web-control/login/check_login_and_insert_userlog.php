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
	$member_no = strtolower($lib->mb_str_pad($dataComing["member_no"]));
	$checkLogin = $conmysql->prepare("SELECT password,user_type,pin,account_status,temppass FROM gcmemberaccount 
										WHERE member_no = :member_no");
	$checkLogin->execute([':member_no' => $member_no]);
	if($checkLogin->rowCount() > 0){
		$rowPassword = $checkLogin->fetch(PDO::FETCH_ASSOC);
		$checkResign = $conoracle->prepare("SELECT resign_status FROM mbmembmaster WHERE member_no = :member_no");
		$checkResign->execute([':member_no' => $member_no]);
		$rowResign = $checkResign->fetch(PDO::FETCH_ASSOC);
		if($rowResign["RESIGN_STATUS"] == '1'){
			$arrayResult['RESPONSE_CODE'] = "WS0051";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
		if($rowPassword['account_status'] == '-8'){
			$arrayResult['RESPONSE_CODE'] = "WS0048";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}else if($rowPassword['account_status'] == '-9'){
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
				$getMemberLogged = $conmysql->prepare("SELECT id_token FROM gcuserlogin WHERE member_no = :member_no and channel = :channel and is_login = '1'");
				$getMemberLogged->execute([
					':member_no' => $member_no,
					':channel' => $dataComing["channel"]
				]);
				if($getMemberLogged->rowCount() > 0){
					$arrayIdToken = array();
					while($rowIdToken = $getMemberLogged->fetch(PDO::FETCH_ASSOC)){
						$arrayIdToken[] = $rowIdToken["id_token"];
					}
					$updateLoggedOneDevice = $conmysql->prepare("UPDATE gctoken gt,gcuserlogin gu SET gt.rt_is_revoke = '-6',
																gt.at_is_revoke = '-6',gt.rt_expire_date = NOW(),gt.at_expire_date = NOW(),
																gu.is_login = '-5',gu.logout_date = NOW()
																WHERE gt.id_token IN(".implode(',',$arrayIdToken).") and gu.id_token IN(".implode(',',$arrayIdToken).")");
					$updateLoggedOneDevice->execute();
				}
				$insertToken = $conmysql->prepare("INSERT INTO gctoken(refresh_token,unique_id,channel,device_name,ip_address) 
													VALUES(:refresh_token,:unique_id,:channel,:device_name,:ip_address)");
				if($insertToken->execute([
					':refresh_token' => $refresh_token,
					':unique_id' => $dataComing["unique_id"],
					':channel' => $arrPayload["PAYLOAD"]["channel"],
					':device_name' => $arrPayload["PAYLOAD"]["device_name"],
					':ip_address' => $arrPayload["PAYLOAD"]["ip_address"]
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
						$arrPayloadNew['exp'] = time() + 900;
						$arrPayloadNew['refresh_amount'] = 0;
						$access_token = $jwt_token->customPayload($arrPayloadNew, $config["SECRET_KEY_JWT"]);
						if($dataComing["channel"] == 'mobile_app'){
							$updateFCMToken = $conmysql->prepare("UPDATE gcmemberaccount SET fcm_token = :fcm_token WHERE member_no = :member_no");
							$updateFCMToken->execute([
								':fcm_token' => $dataComing["fcm_token"] ?? null,
								':member_no' => $member_no
							]);
						}
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