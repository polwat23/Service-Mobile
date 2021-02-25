<?php
set_time_limit(150);
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','id_bindaccount','sigma_key'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'BindAccountConsent')){
		$arrPayloadverify = array();
		$arrPayloadverify['member_no'] = $payload["member_no"];
		$check_account = $conmysql->prepare("SELECT id_bindaccount,deptaccount_no_bank,bank_code  
											FROM gcbindaccount WHERE sigma_key = :sigma_key and id_bindaccount = :id_bindaccount and member_no = :member_no
											and bindaccount_status IN('0','1')");
		$check_account->execute([
			':sigma_key' => $dataComing["sigma_key"],
			':id_bindaccount' => $dataComing["id_bindaccount"],
			':member_no' => $payload["member_no"]
		]);
		if($check_account->rowCount() > 0){
			$rowAcc = $check_account->fetch(PDO::FETCH_ASSOC);
			$arrPayloadverify["coop_key"] = $config["COOP_KEY"];
			$arrPayloadverify['exp'] = time() + 300;
			$arrPayloadverify['sigma_key'] = $dataComing["sigma_key"];
			$verify_token = $jwt_token->customPayload($arrPayloadverify, $config["SIGNATURE_KEY_VERIFY_API"]);
			$arrSendData = array();
			$arrSendData["verify_token"] = $verify_token;
			$arrSendData["app_id"] = $config["APP_ID"];
			$conmysql->beginTransaction();
			$updateUnBindAccount = $conmysql->prepare("UPDATE gcbindaccount SET bindaccount_status = '-9',unbind_date = NOW() 
														WHERE deptaccount_no_bank = :deptaccount_no_bank and bank_code = :bank_code and bindaccount_status IN('0','1')");
			if($updateUnBindAccount->execute([
				':deptaccount_no_bank' => $rowAcc["deptaccount_no_bank"],
				':bank_code' => $rowAcc["bank_code"]
			])){
				$responseAPI = $lib->posting_data($config["URL_API_COOPDIRECT"].'/kbank/request_unbind_espa_id',$arrSendData);
				if(!$responseAPI["RESULT"]){
					$conmysql->rollback();
					$arrayResult['RESPONSE_CODE'] = "WS0029";
					$arrayStruc = [
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':unbind_status' => '-9',
						':response_code' => $arrayResult['RESPONSE_CODE'],
						':response_message' => $responseAPI["RESPONSE_MESSAGE"],
						':id_bindaccount' => $dataComing["id_bindaccount"],
						':query_flag' => '1'
					];
					$log->writeLog('unbindaccount',$arrayStruc);
					$message_error = "ปิดเมนูผูกบัญชี เนื่องจากยกเลิกผูกบัญชีไม่ได้เพราะต่อ Service ไปที่ ".$config["URL_API_COOPDIRECT"]."/kbank/request_reg_id_for_consent ไม่ได้ ตอนเวลา ".date('Y-m-d H:i:s');
					$lib->sendLineNotify($message_error);
					$lib->sendLineNotify($message_error,$config["LINE_NOTIFY_DEPOSIT"]);
					$func->MaintenanceMenu($dataComing["menu_component"]);
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}
				$arrResponse = json_decode($responseAPI);
				if($arrResponse->RESULT){
					$conmysql->commit();
					$arrayStruc = [
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':id_bindaccount' => $dataComing["id_bindaccount"],
						':unbind_status' => '1'
					];
					$log->writeLog('unbindaccount',$arrayStruc);
					$arrayResult['RESULT'] = TRUE;
					require_once('../../include/exit_footer.php');
				}else{
					$conmysql->rollback();
					$arrayResult['RESPONSE_CODE'] = "WS0040";
					$arrayStruc = [
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':unbind_status' => '-9',
						':response_code' => $arrResponse->RESPONSE_CODE,
						':response_message' => $arrResponse->RESPONSE_MESSAGE,
						':id_bindaccount' => $dataComing["id_bindaccount"],
						':query_flag' => '1'
					];
					$log->writeLog('unbindaccount',$arrayStruc);
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}			
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE_CODE'] = "WS1021";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayStruc = [
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':unbind_status' => '-9',
					':response_code' => $arrayResult['RESPONSE_CODE'],
					':response_message' => $arrayResult['RESPONSE_MESSAGE'],
					':id_bindaccount' => $dataComing["id_bindaccount"],
					':data_bind_error' => json_encode([
						':deptaccount_no_bank' => $rowAcc["deptaccount_no_bank"],
						':bank_code' => $rowAcc["bank_code"]
					]),
					':query_error' => $updateUnBindAccount->queryString,
					':query_flag' => '-9'
				];
				$log->writeLog('unbindaccount',$arrayStruc);
				$message_error = "ปิดเมนูผูกบัญชี เนื่องจากยกเลิกผูกบัญชี Update ลง gcbindaccount ไม่ได้ "."\n"."Query => ".$updateUnBindAccount->queryString."\n"."Param => ". json_encode([
					':deptaccount_no_bank' => $rowAcc["deptaccount_no_bank"],
					':bank_code' => $rowAcc["bank_code"]
				]);
				$lib->sendLineNotify($message_error);
				$lib->sendLineNotify($message_error,$config["LINE_NOTIFY_DEPOSIT"]);
				$func->MaintenanceMenu($dataComing["menu_component"]);
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0021";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
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