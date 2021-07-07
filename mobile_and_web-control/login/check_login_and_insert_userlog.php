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
		require_once('../../include/exit_footer.php');
		
	}
	$member_no = strtolower($lib->mb_str_pad($dataComing["member_no"]));
	if($arrPayload["PAYLOAD"]["channel"] == "mobile_app"){
		$checkBlackList = $conoracle->prepare("SELECT type_blacklist FROM gcdeviceblacklist WHERE unique_id = :unique_id and is_blacklist = '1'");
		$checkBlackList->execute([':unique_id' => $dataComing["unique_id"]]);
		$rowBlackList = $checkBlackList->fetch(PDO::FETCH_ASSOC);
		if(isset($rowBlackList["TYPE_BLACKLIST"])){
			if($rowBlackList["TYPE_BLACKLIST"] == '0'){
				$arrayResult['RESPONSE_CODE'] = "WS0068";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}else{
				if(isset($dataComing["is_root"]) && $dataComing["is_root"] == "0"){
					$updateBlacklist = $conoracle->prepare("UPDATE gcdeviceblacklist SET is_blacklist = '0' WHERE unique_id = :unique_id and type_blacklist = '1'");
					$updateBlacklist->execute([':unique_id' => $dataComing["unique_id"]]);
				}else{
					$arrayResult['RESPONSE_CODE'] = "WS0069";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}
			}
		}
	}
	$checkLogin = $conoracle->prepare("SELECT password,user_type,pin,account_status,temppass FROM gcmemberaccount 
										WHERE member_no = :member_no");
	$checkLogin->execute([':member_no' => $member_no]);
	$rowcheckLogin = $checkLogin->fetch(PDO::FETCH_ASSOC);
	if(isset($rowcheckLogin["USER_TYPE"])){
		if($arrPayload["PAYLOAD"]["channel"] == "mobile_app" && isset($dataComing["is_root"])){
			$checkBlackList = $conoracle->prepare("SELECT type_blacklist FROM gcdeviceblacklist WHERE unique_id = :unique_id and is_blacklist = '1' and type_blacklist = '1'");
			$checkBlackList->execute([':unique_id' => $dataComing["unique_id"]]);
			$rowBlackList = $checkBlackList->fetch(PDO::FETCH_ASSOC);
			if(isset($rowBlackList["TYPE_BLACKLIST"])){
				$arrayResult['RESPONSE_CODE'] = "WS0069";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
			if($dataComing["is_root"] == "1"){				
				$max_id  = $func->getMaxTable('id_blacklist' , 'gcdeviceblacklist');			
				$insertBlackList = $conoracle->prepare("INSERT INTO gcdeviceblacklist(id_blacklist, unique_id,member_no,type_blacklist)
													VALUES(:id_blacklist,:unique_id,:member_no,'1')");
				if($insertBlackList->execute([
					':id_blacklist' => $max_id,
					':unique_id' => $dataComing["unique_id"],
					':member_no' => $member_no
				])){
					$arrayResult['RESPONSE_CODE'] = "WS0069";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}
			}
		}
		$rowPassword = $rowcheckLogin;
		$checkResign = $conoracle->prepare("SELECT resign_status FROM mbmembmaster WHERE member_no = :member_no");
		$checkResign->execute([':member_no' => $member_no]);
		$rowResign = $checkResign->fetch(PDO::FETCH_ASSOC);
		if($rowResign["RESIGN_STATUS"] == '1'){
			$arrayResult['RESPONSE_CODE'] = "WS0051";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');	
		}
		if($rowPassword['ACCOUNT_STATUS'] == '-8'){
			$arrayResult['RESPONSE_CODE'] = "WS0048";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}else if($rowPassword['ACCOUNT_STATUS'] == '-9'){
			$valid_pass = password_verify($dataComing["password"], $rowPassword['TEMPPASS']);
		}else{
			$valid_pass = password_verify($dataComing["password"], $rowPassword['PASSWORD']);
		}
		if ($valid_pass) {
			$conoracle->beginTransaction();
			$refresh_token = $lib->generate_token();
			$max_id_token  = $func->getMaxTable('id_token','gctoken');
			
			$insertToken = $conoracle->prepare("INSERT INTO gctoken(id_token,refresh_token,unique_id,channel,device_name,ip_address) 
												VALUES(:id_token, :refresh_token,:unique_id,:channel,:device_name,:ip_address)");
			if($insertToken->execute([
				':id_token' => $max_id_token,
				':refresh_token' => $refresh_token,
				':unique_id' => $dataComing["unique_id"],
				':channel' => $arrPayload["PAYLOAD"]["channel"],
				':device_name' => $arrPayload["PAYLOAD"]["device_name"],
				':ip_address' => $arrPayload["PAYLOAD"]["ip_address"]
			])){
				$getMemberToken = $conoracle->prepare("SELECT id_token FROM gctoken WHERE  id_token =(select max(id_token) from gctoken)");
				$getMemberToken->execute();
				$rowMemberToken = $getMemberToken->fetch(PDO::FETCH_ASSOC);
				$id_token = $rowMemberToken["ID_TOKEN"];
				
				$getMemberLogged = $conoracle->prepare("SELECT id_token,unique_id FROM gcuserlogin WHERE member_no = :member_no and channel = :channel and is_login = '1'");
				$getMemberLogged->execute([
					':member_no' => $member_no,
					':channel' => $arrPayload["PAYLOAD"]["channel"]
				]);
				$arrayIdToken = array();
				$prev_unique_id = null;
				while($rowIdToken = $getMemberLogged->fetch(PDO::FETCH_ASSOC)){
					$arrayIdToken[] = $rowIdToken["ID_TOKEN"];	
					if($arrPayload["PAYLOAD"]["channel"] == 'mobile_app' && $dataComing["unique_id"] != $rowIdToken["UNIQUE_ID"] && 
					$prev_unique_id != $rowIdToken["UNIQUE_ID"]){
						$prev_unique_id = $rowIdToken["UNIQUE_ID"];
						
						$id_blacklist  = $func->getMaxTable('id_blacklist' , 'gcdeviceblacklist');	
						
						$insertBacklist = $conoracle->prepare("INSERT INTO gcdeviceblacklist(id_blacklist,unique_id,type_blacklist,member_no,new_id_token,old_id_token)
															VALUES(:id_blacklist,:unique_id,:is_root,:member_no,:new_id_token,:old_id_token)");
						if($insertBacklist->execute([
							':id_blacklist' => $id_blacklist ,
							':unique_id' => $rowIdToken["UNIQUE_ID"],
							':is_root' => $dataComing["is_root"] ?? 0,
							':member_no' => $dataComing["member_no"],
							':new_id_token' => $id_token,
							':old_id_token' => $rowIdToken["ID_TOKEN"]
						])){
						}else{
							$conoracle->rollback();
							$arrayResult['RESPONSE_CODE'] = "WS1002";
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
							$arrayResult['RESULT'] = FALSE;
							require_once('../../include/exit_footer.php');
							
						}
					}
				}
				if(sizeof($arrayIdToken) > 0){
					$updateLoggedOneDevice = $conoracle->prepare("UPDATE gctoken gt,gcuserlogin gu SET gt.rt_is_revoke = '-6',
																gt.at_is_revoke = '-6',gt.rt_expire_date = SYSDATE ,gt.at_expire_date = SYSDATE,
																gu.is_login = '-5',gu.logout_date = SYSDATE
																WHERE gt.id_token IN(".implode(',',$arrayIdToken).") and gu.id_token IN(".implode(',',$arrayIdToken).")");
					$updateLoggedOneDevice->execute();
				}
				if(isset($dataComing["firsttime"])){
					$firstapp = 0;
				}else{
					$firstapp = 1;
				}
				
				$max_id_userlogin  = $func->getMaxTable('id_userlogin' , 'gcuserlogin');
				
				$insertLogin = $conoracle->prepare("INSERT INTO gcuserlogin(id_userlogin ,member_no,device_name,channel,unique_id,status_firstapp,id_token) 
											VALUES(:max_id_userlogin, :member_no,:device_name,:channel,:unique_id,:firstapp,:id_token)");
				if($insertLogin->execute([
					':max_id_userlogin' => $max_id_userlogin,
					':member_no' => $member_no,
					':device_name' => $arrPayload["PAYLOAD"]["device_name"],
					':channel' => $arrPayload["PAYLOAD"]["channel"],
					':unique_id' => $dataComing["unique_id"],
					':firstapp' => $firstapp,
					':id_token' => $id_token
				])){
					$arrPayloadNew = array();
					$arrPayloadNew['id_userlogin'] = $max_id_userlogin;
					$arrPayloadNew['user_type'] = $rowPassword['USER_TYPE'];
					$arrPayloadNew['id_token'] = $id_token;
					$arrPayloadNew['member_no'] = $member_no;
					$arrPayloadNew['exp'] = time() + intval($func->getConstant("limit_session_timeout"));
					$arrPayloadNew['refresh_amount'] = 0;
					$access_token = $jwt_token->customPayload($arrPayloadNew, $config["SECRET_KEY_JWT"]);
					if($arrPayload["PAYLOAD"]["channel"] == 'mobile_app'){
						if(isset($dataComing["fcm_token"]) && $dataComing["fcm_token"] != ""){
							$updateFCMToken = $conoracle->prepare("UPDATE gcmemberaccount SET fcm_token = :fcm_token  WHERE member_no = :member_no");
							$updateFCMToken->execute([
								':fcm_token' => $dataComing["fcm_token"] ?? null,
								':member_no' => $member_no
							]);
						}
						if(isset($dataComing["hms_token"]) && $dataComing["hms_token"] != ""){
							$updateFCMToken = $conoracle->prepare("UPDATE gcmemberaccount SET hms_token = :hms_token  WHERE member_no = :member_no");
							$updateFCMToken->execute([
								':hms_token' => $dataComing["hms_token"] ?? null,
								':member_no' => $member_no
							]);
						}
					}
					$updateAccessToken = $conoracle->prepare("UPDATE gctoken SET access_token = :access_token WHERE id_token = :id_token");
					if($updateAccessToken->execute([
						':access_token' => $access_token,
						':id_token' => $id_token
					])){
						$conoracle->commit();
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
							if($rowPassword['USER_TYPE'] == '9'){
								$arrayResult['PIN'] = (isset($rowPassword["PIN"]) ? 9 : 0);
							}else{
								$arrayResult['PIN'] = (isset($rowPassword["PIN"]) ? 1 : 0);
							}
						}else{
							if($rowPassword['ACCOUNT_STATUS'] == '-9'){
								$arrayResult['TEMP_PASSWORD'] = TRUE;
							}else{
								$arrayResult['TEMP_PASSWORD'] = FALSE;
							}
						}
						$arrayResult['MEMBER_NO'] = $member_no;
						if($arrPayload["PAYLOAD"]["channel"] == 'mobile_app' && ($rowPassword['USER_TYPE'] == '0' || 
						$rowPassword['USER_TYPE'] == '1') && $member_no != "etnmode1" && $member_no != "etnmode2" && $member_no != "dev@mode" && $member_no != "etnmode3" && $member_no != "etnmode4" 
						&& $member_no != "etnmode5" && $member_no != "etnmode6" && $member_no != "etnmode7" && $member_no != "etnmode8" && 
						$member_no != "etnmode9" && (empty($dataComing["auto_login"]) || $dataComing["auto_login"] === FALSE)){
							$arrayResult['IS_OTP'] = TRUE;
						}
						$updateWrongPassCount = $conoracle->prepare("UPDATE gcmemberaccount SET counter_wrongpass = 0  WHERE member_no = :member_no");
						$updateWrongPassCount->execute([
							':member_no' => $member_no
						]);
						$arrayResult['RESULT'] = TRUE;
						require_once('../../include/exit_footer.php');
					}else{
						$conoracle->rollback();
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
						require_once('../../include/exit_footer.php');
						
					}
				}else{
					$conoracle->rollback();
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
					require_once('../../include/exit_footer.php');
					
				}
			}else{
				$conoracle->rollback();
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS1001",
					":error_desc" => "ไม่สามารถเข้าสู่ระบบได้ "."\n".json_encode($dataComing),
					":error_device" => $arrPayload["PAYLOAD"]["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$message_error = "ไม่สามารถเข้าสู่ระบบได้เพราะไม่สามารถ Insert ลง gctoken"."\n"."Query => ".$insertToken->queryString."\n"."Data => ".json_encode([
					':id_token' => $max_id_token,
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
				require_once('../../include/exit_footer.php');
				
			}
		}else{
			$updateCounter = $conoracle->prepare("UPDATE gcmemberaccount SET counter_wrongpass = counter_wrongpass + 1 WHERE member_no = :member_no");
			$updateCounter->execute([':member_no' => $member_no]);
			$getCounter = $conoracle->prepare("SELECT counter_wrongpass FROM gcmemberaccount WHERE member_no = :member_no");
			$getCounter->execute([':member_no' => $member_no]);
			$rowCounter = $getCounter->fetch(PDO::FETCH_ASSOC);
			if($rowCounter["counter_wrongpass"] >= 5){
				$updateAccountStatus = $conoracle->prepare("UPDATE gcmemberaccount SET account_status = '-8',counter_wrongpass = 0 WHERE member_no = :member_no");
				$updateAccountStatus->execute([':member_no' => $member_no]);
				$struc = [
					':member_no' =>  $member_no,
					':device_name' =>  $arrPayload["PAYLOAD"]["device_name"],
					':unique_id' =>  $dataComing["UNIQUE_ID"]
				];
				$log->writeLog("lockaccount",$struc);
				$arrayResult['RESPONSE_CODE'] = "WS0048";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
			$arrayResult['COUNTER_CAUTION'] = 5 - $rowCounter["counter_wrongpass"];
			$arrayResult['RESPONSE_CODE'] = "WS0002";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
	}else{
		$checkMember = $conoracle->prepare("SELECT resign_status,mem_telmobile,email FROM mbmembmaster WHERE member_no = :member_no and TRIM(card_person) = :card_person");
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
				require_once('../../include/exit_footer.php');
				
			}else{
				$arrayResult['MEMBER_NO'] = $member_no;
				$arrayResult['TEL'] = $rowMember["MEM_TELMOBILE"];
				$arrayResult['TEL_FORMAT'] = $lib->formatphone($rowMember["MEM_TELMOBILE"]);
				$arrayResult['EMAIL'] = $rowMember["EMAIL"];
				$arrayResult['VERIFY'] = TRUE;
				$arrayResult['USE_EMAIL_FROM_COOP'] = TRUE;
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0003";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
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
	require_once('../../include/exit_footer.php');
	
}
?>