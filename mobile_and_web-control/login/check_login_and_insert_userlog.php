<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['member_no','api_token','password','unique_id'],$dataComing)){
	$arrPayload = $auth->check_apitoken($dataComing["api_token"],$config["SECRET_KEY_JWT"]);
	if(!$arrPayload["VALIDATE"]){
		$arrayResult['RESPONSE_CODE'] = "WS0001";
		$arrayResult['RESPONSE_MESSAGE'] = $arrPayload["ERROR_MESSAGE"];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(401);
		echo json_encode($arrayResult);
		exit();
	}
	$member_no = strtolower(str_pad($dataComing["member_no"],8,0,STR_PAD_LEFT));
	$checkMemberResign = $conoracle->prepare("SELECT resign_status FROM mbmembmaster WHERE member_no = :member_no");
	$checkMemberResign->execute([
		':member_no' => $member_no
	]);
	$rowMemberResign = $checkMemberResign->fetch();
	if(isset($rowMemberResign["RESIGN_STATUS"]) || $member_no === 'dev@mode' || $member_no === 'salemode'){
		if($rowMemberResign["RESIGN_STATUS"] == 0 || $member_no === 'dev@mode' || $member_no === 'salemode'){
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
					$fcm_token = (isset($dataComing["fcm_token"]) && $dataComing["fcm_token"] != '' ? $dataComing["fcm_token"] : null);
					try{
						$conmysql->beginTransaction();
						$updateOldToken = $conmysql->prepare("UPDATE gctoken SET at_is_revoke = '-9',rt_is_revoke = '-9',
																rt_expire_date = NOW(),at_expire_date = NOW() 
																WHERE unique_id = :unique_id and (at_is_revoke = '0' OR rt_is_revoke = '0')");
						$updateOldToken->execute([
							':unique_id' => $dataComing["unique_id"]
						]);
						$insertToken = $conmysql->prepare("INSERT INTO gctoken(refresh_token,unique_id,channel,device_name,ip_address,fcm_token) 
															VALUES(:refresh_token,:unique_id,:channel,:device_name,:ip_address,:fcm_token)");
						if($insertToken->execute([
							':refresh_token' => $refresh_token,
							':unique_id' => $dataComing["unique_id"],
							':channel' => $arrPayload["PAYLOAD"]["channel"],
							':device_name' => $arrPayload["PAYLOAD"]["device_name"],
							':ip_address' => $arrPayload["PAYLOAD"]["ip_address"],
							':fcm_token' => $fcm_token ?? null
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
									$arrayResult['RESPONSE_CODE'] = "WS0002";
									$arrayResult['RESPONSE_MESSAGE'] = "Cannot update Access Token";
									$arrayResult['RESULT'] = FALSE;
									echo json_encode($arrayResult);
									exit();
								}
							}else{
								$conmysql->rollback();
								$arrayResult['RESPONSE_CODE'] = "WS0002";
								$arrayResult['RESPONSE_MESSAGE'] = "Error! Cannot Insert User Log";
								$arrayResult['RESULT'] = FALSE;
								echo json_encode($arrayResult);
								exit();
							}
						}else{
							$conmysql->rollback();
							$arrayResult['RESPONSE_CODE'] = "WS0003";
							$arrayResult['RESPONSE_MESSAGE'] = "Error! Cannot Insert Token";
							$arrayResult['RESULT'] = FALSE;
							echo json_encode($arrayResult);
							exit();
						}
					}catch (PDOExecption $e) {
						$conmysql->rollback();
						$arrayResult['RESPONSE_CODE'] = "WS9999";
						$arrayResult['RESPONSE_MESSAGE'] = $e->getMessage();
						$arrayResult['RESULT'] = FALSE;
						echo json_encode($arrayResult);
						exit();
					}
				}else{
					$arrayResult['RESPONSE_CODE'] = "WS0004";
					$arrayResult['RESPONSE_MESSAGE'] = "รหัสผ่านผิด";
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}else{
				$checkMember = $conoracle->prepare("SELECT card_person,mem_telmobile FROM mbmembmaster WHERE member_no = :member_no and card_person = :password");
				$checkMember->execute([
					':member_no' => $member_no,
					':password' => $dataComing["password"]
				]);
				$rowMemberChecked = $checkMember->fetch();
				if(isset($rowMemberChecked["CARD_PERSON"])){
					$arrayResult['TEL'] = $rowMemberChecked["MEM_TELMOBILE"];
					$arrayResult['TEL_FORMAT'] = $lib->formatphone($rowMemberChecked["MEM_TELMOBILE"],' ');
					$arrayResult['VERIFY'] = TRUE;
					$arrayResult['RESULT'] = TRUE;
					echo json_encode($arrayResult);
					exit();
				}else{
					$arrayResult['RESPONSE_CODE'] = "WS0030";
					$arrayResult['RESPONSE_MESSAGE'] = "รหัสบัตรประชาชนไม่ตรงกับฐานข้อมูล";
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0021";
			$arrayResult['RESPONSE_MESSAGE'] = "ระบบไม่รองรับสมาชิกเกษียณ";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0005";
		$arrayResult['RESPONSE_MESSAGE'] = "ไม่พบข้อมูลการเป็นสมาชิกของท่านกรุณาตรวจสอบอีกครั้ง";
		$arrayResult['RESULT'] = FALSE;
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = "Not complete argument";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>