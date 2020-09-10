<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['member_no','api_token','password','unique_id'],$dataComing)){
	$arrPayload = $auth->check_apitoken($dataComing["api_token"],$config["SECRET_KEY_JWT"]);
	if(!$arrPayload["VALIDATE"]){
		$filename = basename(__FILE__, '.php');
		$logStruc = [
			":error_menu" => $filename,
			":error_code" => "WS0001",
			":error_desc" => "ไม่สามารถยืนยันข้อมูลได้"."\n".json_encode($dataComing),
			":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
		];
		$log->writeLog('errorusage',$logStruc);
		$arrayResult['RESPONSE_CODE'] = "WS0001";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(401);
		echo json_encode($arrayResult);
		exit();
	}
	$member_no = strtolower($lib->mb_str_pad($dataComing["member_no"]));
	if($arrPayload["PAYLOAD"]["channel"] == "mobile_app"){
		$checkBlackList = $conmysql->prepare("SELECT type_blacklist FROM gcdeviceblacklist WHERE unique_id = :unique_id and is_blacklist = '1'");
		$checkBlackList->execute([':unique_id' => $dataComing["unique_id"]]);
		if($checkBlackList->rowCount() > 0){
			$rowBlackList = $checkBlackList->fetch(PDO::FETCH_ASSOC);
			if($rowBlackList["type_blacklist"] == '0'){
				$arrayResult['RESPONSE_CODE'] = "WS0068";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}else{
				if(isset($dataComing["is_root"]) && $dataComing["is_root"] == "0"){
					$updateBlacklist = $conmysql->prepare("UPDATE gcdeviceblacklist SET is_blacklist = '0' WHERE unique_id = :unique_id and type_blacklist = '1'");
					$updateBlacklist->execute([':unique_id' => $dataComing["unique_id"]]);
				}else{
					$arrayResult['RESPONSE_CODE'] = "WS0069";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}
		}
	}
	$checkLogin = $conmysql->prepare("SELECT password,user_type,pin,account_status,temppass FROM gcmemberaccount 
										WHERE member_no = :member_no");
	$checkLogin->execute([':member_no' => $member_no]);
	if($checkLogin->rowCount() > 0){
		if($arrPayload["PAYLOAD"]["channel"] == "mobile_app" && isset($dataComing["is_root"])){
			$checkBlackList = $conmysql->prepare("SELECT type_blacklist FROM gcdeviceblacklist WHERE unique_id = :unique_id and is_blacklist = '1' and type_blacklist = '1'");
			$checkBlackList->execute([':unique_id' => $dataComing["unique_id"]]);
			if($checkBlackList->rowCount() > 0){
				$arrayResult['RESPONSE_CODE'] = "WS0069";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
			if($dataComing["is_root"] == "1"){
				$insertBlackList = $conmysql->prepare("INSERT INTO gcdeviceblacklist(unique_id,member_no,type_blacklist)
													VALUES(:unique_id,:member_no,'1')");
				if($insertBlackList->execute([
					':unique_id' => $dataComing["unique_id"],
					':member_no' => $member_no
				])){
					$arrayResult['RESPONSE_CODE'] = "WS0069";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}
		}
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
			$valid_pass = password_verify($dataComing["password"], $rowPassword['temppass']);
		}else{
			$valid_pass = password_verify($dataComing["password"], $rowPassword['password']);
		}
		if ($valid_pass) {
			$conmysql->beginTransaction();
			$refresh_token = $lib->generate_token();
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
				$getMemberLogged = $conmysql->prepare("SELECT id_token,unique_id FROM gcuserlogin WHERE member_no = :member_no and channel = :channel and is_login = '1'");
				$getMemberLogged->execute([
					':member_no' => $member_no,
					':channel' => $arrPayload["PAYLOAD"]["channel"]
				]);
				if($getMemberLogged->rowCount() > 0){
					$arrayIdToken = array();
					$prev_unique_id = null;
					while($rowIdToken = $getMemberLogged->fetch(PDO::FETCH_ASSOC)){
						$arrayIdToken[] = $rowIdToken["id_token"];
						if($arrPayload["PAYLOAD"]["channel"] == 'mobile_app' && $dataComing["unique_id"] != $rowIdToken["unique_id"] && 
						$prev_unique_id != $rowIdToken["unique_id"]){
							$prev_unique_id = $rowIdToken["unique_id"];
							$insertBacklist = $conmysql->prepare("INSERT INTO gcdeviceblacklist(unique_id,type_blacklist,member_no,new_id_token,old_id_token)
																VALUES(:unique_id,:is_root,:member_no,:new_id_token,:old_id_token)");
							if($insertBacklist->execute([
								':unique_id' => $rowIdToken["unique_id"],
								':is_root' => $dataComing["is_root"] ?? 0,
								':member_no' => $dataComing["member_no"],
								':new_id_token' => $id_token,
								':old_id_token' => $rowIdToken["id_token"]
							])){
							}else{
								$conmysql->rollback();
								$arrayResult['RESPONSE_CODE'] = "WS1002";
								$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
								$arrayResult['RESULT'] = FALSE;
								echo json_encode($arrayResult);
								exit();
							}
						}
					}
					$updateLoggedOneDevice = $conmysql->prepare("UPDATE gctoken gt,gcuserlogin gu SET gt.rt_is_revoke = '-6',
																gt.at_is_revoke = '-6',gt.rt_expire_date = NOW(),gt.at_expire_date = NOW(),
																gu.is_login = '-5',gu.logout_date = NOW()
																WHERE gt.id_token IN(".implode(',',$arrayIdToken).") and gu.id_token IN(".implode(',',$arrayIdToken).")");
					$updateLoggedOneDevice->execute();
				}
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
					if($arrPayload["PAYLOAD"]["channel"] == 'mobile_app'){
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
							$member_no_alias = $configAS[$member_no] ?? $member_no;
							$fetchTelephone = $conoracle->prepare("SELECT mem_telmobile FROM mbmembmaster WHERE member_no = :member_no");
							$fetchTelephone->execute([
								':member_no' => $member_no_alias
							]);
							$rowTele = $fetchTelephone->fetch(PDO::FETCH_ASSOC);
							$arrayResult['TEL'] = $rowTele["MEM_TELMOBILE"];
							$arrayResult['TEL_FORMAT'] = $lib->formatphone($rowTele["MEM_TELMOBILE"]);
							if($rowPassword['user_type'] == '9'){
								$arrayResult['PIN'] = (isset($rowPassword["pin"]) ? 9 : 0);
							}else{
								$arrayResult['PIN'] = (isset($rowPassword["pin"]) ? 1 : 0);
							}
						}else{
							if($rowPassword['account_status'] == '-9'){
								$arrayResult['TEMP_PASSWORD'] = TRUE;
							}else{
								$arrayResult['TEMP_PASSWORD'] = FALSE;
							}
						}
						$arrayResult['MEMBER_NO'] = $member_no;
						if($arrPayload["PAYLOAD"]["channel"] == 'mobile_app' && ($rowPassword['user_type'] == '0' || 
						$rowPassword['user_type'] == '1') && $member_no != "etnmode1" && $member_no != "etnmode2" && $member_no != "dev@mode" && $member_no != "etnmode3" && $member_no != "etnmode4" 
						&& $member_no != "etnmode5" && $member_no != "etnmode6" && $member_no != "etnmode7" && $member_no != "etnmode8" && $member_no != "etnmode9"){
							$arrayResult['IS_OTP'] = TRUE;
						}
						$updateWrongPassCount = $conmysql->prepare("UPDATE gcmemberaccount SET counter_wrongpass = 0  WHERE member_no = :member_no");
						$updateWrongPassCount->execute([
							':member_no' => $member_no
						]);
						$arrayResult['RESULT'] = TRUE;
						echo json_encode($arrayResult);
					}else{
						$conmysql->rollback();
						$filename = basename(__FILE__, '.php');
						$logStruc = [
							":error_menu" => $filename,
							":error_code" => "WS1001",
							":error_desc" => "ไม่สามารถเข้าสู่ระบบได้ "."\n".json_encode($dataComing),
							":error_device" => $arrPayload["PAYLOAD"]["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
						];
						$log->writeLog('errorusage',$logStruc);
						$message_error = "ไม่สามารถเข้าสู่ระบบได้เพราะไม่สามารถ Update ลง gctoken"."\n"."Query => ".$updateAccessToken->queryString."\n"."Data => ".json_encode([
							':access_token' => $access_token,
							':id_token' => $id_token
						]);
						$lib->sendLineNotify($message_error);
						$arrayResult['RESPONSE_CODE'] = "WS1001";
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						echo json_encode($arrayResult);
						exit();
					}
				}else{
					$conmysql->rollback();
					$filename = basename(__FILE__, '.php');
					$logStruc = [
						":error_menu" => $filename,
						":error_code" => "WS1001",
						":error_desc" => "ไม่สามารถเข้าสู่ระบบได้ "."\n".json_encode($dataComing),
						":error_device" => $arrPayload["PAYLOAD"]["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
					];
					$log->writeLog('errorusage',$logStruc);
					$message_error = "ไม่สามารถเข้าสู่ระบบได้เพราะไม่สามารถ Insert ลง gcuserlogin"."\n"."Query => ".$insertLogin->queryString."\n"."Data => ".json_encode([
						':member_no' => $member_no,
						':device_name' => $arrPayload["PAYLOAD"]["device_name"],
						':channel' => $arrPayload["PAYLOAD"]["channel"],
						':unique_id' => $dataComing["unique_id"],
						':firstapp' => $firstapp,
						':id_token' => $id_token
					]);
					$lib->sendLineNotify($message_error);
					$arrayResult['RESPONSE_CODE'] = "WS1001";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}else{
				$conmysql->rollback();
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS1001",
					":error_desc" => "ไม่สามารถเข้าสู่ระบบได้ "."\n".json_encode($dataComing),
					":error_device" => $arrPayload["PAYLOAD"]["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$message_error = "ไม่สามารถเข้าสู่ระบบได้เพราะไม่สามารถ Insert ลง gctoken"."\n"."Query => ".$insertToken->queryString."\n"."Data => ".json_encode([
					':refresh_token' => $refresh_token,
					':unique_id' => $dataComing["unique_id"],
					':channel' => $arrPayload["PAYLOAD"]["channel"],
					':device_name' => $arrPayload["PAYLOAD"]["device_name"],
					':ip_address' => $arrPayload["PAYLOAD"]["ip_address"]
				]);
				$lib->sendLineNotify($message_error);
				$arrayResult['RESPONSE_CODE'] = "WS1001";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$updateCounter = $conmysql->prepare("UPDATE gcmemberaccount SET counter_wrongpass = counter_wrongpass + 1 WHERE member_no = :member_no");
			$updateCounter->execute([':member_no' => $member_no]);
			$getCounter = $conmysql->prepare("SELECT counter_wrongpass FROM gcmemberaccount WHERE member_no = :member_no");
			$getCounter->execute([':member_no' => $member_no]);
			$rowCounter = $getCounter->fetch(PDO::FETCH_ASSOC);
			if($rowCounter["counter_wrongpass"] >= 5){
				$updateAccountStatus = $conmysql->prepare("UPDATE gcmemberaccount SET account_status = '-8',counter_wrongpass = 0 WHERE member_no = :member_no");
				$updateAccountStatus->execute([':member_no' => $member_no]);
				$struc = [
					':member_no' =>  $member_no,
					':device_name' =>  $arrPayload["PAYLOAD"]["device_name"],
					':unique_id' =>  $dataComing["unique_id"]
				];
				$log->writeLog("lockaccount",$struc);
				$arrayResult['RESPONSE_CODE'] = "WS0048";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
			$arrayResult['COUNTER_CAUTION'] = 5 - $rowCounter["counter_wrongpass"];
			$arrayResult['RESPONSE_CODE'] = "WS0002";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$checkMember = $conoracle->prepare("SELECT resign_status,mem_telmobile FROM mbmembmaster WHERE member_no = :member_no and TRIM(card_person) = :card_person");
		$checkMember->execute([
			':member_no' => $member_no,
			':card_person' => $dataComing["password"]
		]);
		$rowMember = $checkMember->fetch(PDO::FETCH_ASSOC);
		if(isset($rowMember["RESIGN_STATUS"])){
			if($rowMember["RESIGN_STATUS"] == '1'){
				$arrayResult['RESPONSE_CODE'] = "WS0051";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}else{
				$arrayResult['MEMBER_NO'] = $member_no;
				$arrayResult['TEL'] = $rowMember["MEM_TELMOBILE"];
				$arrayResult['TEL_FORMAT'] = $lib->formatphone($rowMember["MEM_TELMOBILE"]);
				$arrayResult['VERIFY'] = TRUE;
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0003";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}
}else{
	$filename = basename(__FILE__, '.php');
	$logStruc = [
		":error_menu" => $filename,
		":error_code" => "WS4004",
		":error_desc" => "ส่ง Argument มาไม่ครบ "."\n".json_encode($dataComing),
		":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
	];
	$log->writeLog('errorusage',$logStruc);
	$message_error = "ไฟล์ ".$filename." ส่ง Argument มาไม่ครบมาแค่ "."\n".json_encode($dataComing);
	$lib->sendLineNotify($message_error);
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>