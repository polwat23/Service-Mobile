<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['api_token','unique_id','member_no','tel','device_name'],$dataComing)){
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
	$checkMember = $conmysql->prepare("SELECT account_status,phone_number FROM gcmemberaccount 
										WHERE member_no = :member_no");
	$checkMember->execute([
		':member_no' => $member_no
	]);
	if($checkMember->rowCount() > 0){
		$rowChkMemb = $checkMember->fetch(PDO::FETCH_ASSOC);
		$getTelMemb = $conmssql->prepare("SELECT MEM_TELMOBILE FROM mbmembmaster WHERE member_no = :member_no");
		$getTelMemb->execute([':member_no' => $dataComing["member_no"]]);
		$rowTel = $getTelMemb->fetch(PDO::FETCH_ASSOC);
		if($rowChkMemb["account_status"] == '-8'){
			$arrayResult['RESPONSE_CODE'] = "WS0048";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
		if(empty($rowTel["MEM_TELMOBILE"])){
			$arrayResult['RESPONSE_CODE'] = "WS0094";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
		if(strtolower($dataComing["tel"]) != strtolower($rowTel["MEM_TELMOBILE"])){
			$arrayResult['RESPONSE_CODE'] = "WS0095";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
		$template = $func->getTemplateSystem('ForgetPasswordSMS');
		$arrayDataTemplate = array();
		$temp_pass = $lib->randomText('number',6);
		$arrayDataTemplate["TEMP_PASSWORD"] = $temp_pass;
		$arrayDataTemplate["MEMBER_NO"] = $member_no;
		$arrayDataTemplate["REQUEST_DATE"] = $lib->convertdate(date('Y-m-d H:i'),'D m Y',true);
		$conmysql->beginTransaction();
		$updateTemppass = $conmysql->prepare("UPDATE gcmemberaccount SET prev_acc_status = account_status,temppass = :temp_pass,account_status = '-9',counter_wrongpass = 0,temppass_is_md5 = '0'
											WHERE member_no = :member_no");
		if($updateTemppass->execute([
			':temp_pass' => password_hash($temp_pass,PASSWORD_DEFAULT),
			':member_no' => $member_no
		])){
			$arrMessage = $lib->mergeTemplate($template["SUBJECT"],$template["BODY"],$arrayDataTemplate);
			$arrVerifyToken['exp'] = time() + 300;
			$arrVerifyToken['action'] = "sendmsg";
			$arrVerifyToken["mode"] = "eachmsg";
			$arrVerifyToken['typeMsg'] = 'OTP';
			$verify_token =  $jwt_token->customPayload($arrVerifyToken, $config["KEYCODE"]);
			$arrMsg[0]["msg"] = $arrMessage["BODY"];
			$arrMsg[0]["to"] = $dataComing["tel"];
			$arrSendData["dataMsg"] = $arrMsg;
			$arrSendData["custId"] = 'nhp';
			$arrHeader[] = "version: v1";
			$arrHeader[] = "OAuth: Bearer ".$verify_token;
			$arraySendSMS = $lib->posting_data($config["URL_SMS"].'/navigator',$arrSendData,$arrHeader);

			if($arraySendSMS["RESULT"]){
				$arrayLogSMS = $func->logSMSWasSent(null,$arrMessage["BODY"],$arrayTel,'system');
				if($func->logoutAll(null,$member_no,'-9')){
					$conmysql->commit();
					$arrayResult['RESULT'] = TRUE;
					require_once('../../include/exit_footer.php');
				}else{
					$filename = basename(__FILE__, '.php');
					$logStruc = [
						":error_menu" => $filename,
						":error_code" => "WS1014",
						":error_desc" => "ลืมรหัสผ่านไม่ได้เพราะไม่สามารถบังคับอุปกรณ์อื่นออกจากระบบได้ "."\n".json_encode($dataComing),
						":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
					];
					$log->writeLog('errorusage',$logStruc);
					$message_error = "ไม่สามารถลืมรหัสผ่านได้เพราะบังคับคนออกจากระบบไม่ได้"."\n"."Data => ".json_encode($dataComing)."\n"."Payload => ".json_encode($payload);
					$lib->sendLineNotify($message_error);
					$arrayResult['RESPONSE_CODE'] = "WS1014";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}
			}else{
				$bulkInsert[] = "('".$arrMessage["BODY"]."','".$member_no."',
						'mobile_app',null,null,'ส่ง SMS ไม่ได้เนื่องจาก ".$arraySendSMS["RESPONSE_MESSAGE"]."','system',null)";
				$func->logSMSWasNotSent($bulkInsert);
				unset($bulkInsert);
				$bulkInsert = array();
				$conmysql->rollback();
				$arrayResult['RESPONSE_CODE'] = "WS0018";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
		}else{
			$conmysql->rollback();
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS1014",
				":error_desc" => "บันทึกรหัสผ่านชั่วคราวไม่ได้ "."\n".json_encode($dataComing),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ไม่สามารถลืมรหัสผ่านได้เพราะ Update ลง gcmemberaccount ไม่ได้"."\n"."Query => ".$updateTemppass->queryString."\n"."Param => ". json_encode([
				':temp_pass' => password_hash($temp_pass,PASSWORD_DEFAULT),
				':member_no' => $member_no
			]);
			$lib->sendLineNotify($message_error);
			$arrayResult['RESPONSE_CODE'] = "WS1014";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0003";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		require_once('../../include/exit_footer.php');
		
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
