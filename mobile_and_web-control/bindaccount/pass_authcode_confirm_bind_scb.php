<?php
set_time_limit(150);
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'BindAccountConsent')){
		$coop_account_no = $payload["member_no"];
		if($dataComing["statusCode"] == '0000'){
			$conmysql->beginTransaction();
			$updateBindAcc = $conmysql->prepare("UPDATE gcbindaccount SET bindaccount_status = '1',bind_date = NOW() WHERE reg_ref = :regRef");
			if($updateBindAcc->execute([':regRef' => $dataComing["regRef"]])){
				$arrPayloadverify = array();
				$getCitizenId = $conmysql->prepare("SELECT citizen_id FROM gcbindaccount WHERE reg_ref = :regRef");
				$getCitizenId->execute([':regRef' => $dataComing["regRef"]]);
				$rowCitizen = $getCitizenId->fetch(PDO::FETCH_ASSOC);
				$arrPayloadverify['regRef'] = $dataComing["regRef"];
				$arrPayloadverify['citizenId'] = $rowCitizen["citizen_id"];
				$arrPayloadverify["coop_key"] = $config["COOP_KEY"];
				$arrPayloadverify['exp'] = time() + 300;
				$verify_token = $jwt_token->customPayload($arrPayloadverify, $config["SIGNATURE_KEY_VERIFY_API"]);
				$arrSendData = array();
				$arrSendData["verify_token"] = $verify_token;
				$arrSendData["app_id"] = $config["APP_ID"];
				$responseAPI = $lib->posting_data($config["URL_API_COOPDIRECT"].'/scb/payment/receiveResponseRegister',$arrSendData);
				if(!$responseAPI["RESULT"]){
					$arrayResult['RESPONSE_CODE'] = "WS0022";
					$arrayStruc = [
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':bind_status' => '-9',
						':response_code' => $arrayResult['RESPONSE_CODE'],
						':response_message' => $responseAPI["RESPONSE_MESSAGE"],
						':coop_account_no' => $coop_account_no,
						':query_flag' => '1'
					];
					$log->writeLog('bindaccount',$arrayStruc);
					$message_error = "ผูกบัญชีไม่ได้เพราะต่อ Service ไปที่ ".$config["URL_API_COOPDIRECT"]."/scb/payment/receiveResponseRegister ไม่ได้ ตอนเวลา ".date('Y-m-d H:i:s');
					$lib->sendLineNotify($message_error);
					$lib->sendLineNotify($message_error,$config["LINE_NOTIFY_DEPOSIT"]);
					$func->MaintenanceMenu($dataComing["menu_component"]);
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}
				$arrResponse = json_decode($responseAPI);
				if($arrResponse->RESULT){
					$updateBankAcc = $conmysql->prepare("UPDATE gcbindaccount SET deptaccount_no_bank = :bank_acc WHERE reg_ref = :regRef");
					$updateBankAcc->execute([
						':bank_acc' => $arrResponse->BANK_ACCOUNT,
						':regRef' => $arrResponse->REG_REF
					]);
					$conmysql->commit();
					$arrayResult['RESULT'] = TRUE;
					require_once('../../include/exit_footer.php');
				}else{
					$conmysql->rollback();
					$arrayResult['RESPONSE_CODE'] = "WS0039";
					$arrayStruc = [
						':member_no' => $payload["member_no"],
						':id_userlogin' => $payload["id_userlogin"],
						':bind_status' => '-9',
						':response_code' => $arrayResult['RESPONSE_CODE'],
						':response_message' => $arrResponse->RESPONSE_MESSAGE,
						':coop_account_no' => $coop_account_no,
						':query_flag' => '1'
					];
					$log->writeLog('bindaccount',$arrayStruc);
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE_CODE'] = "WS0039";
				$arrayStruc = [
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':bind_status' => '-9',
					':response_code' => $arrayResult['RESPONSE_CODE'],
					':response_message' => $updateBindAcc->queryString.' / '.json_encode([':regRef' => $dataComing["regRef"]]),
					':coop_account_no' => $coop_account_no,
					':query_flag' => '1'
				];
				$log->writeLog('bindaccount',$arrayStruc);
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0039";
			$arrayStruc = [
				':member_no' => $payload["member_no"],
				':id_userlogin' => $payload["id_userlogin"],
				':bind_status' => '-9',
				':response_code' => $arrayResult['RESPONSE_CODE'],
				':response_message' => $dataComing["errorCode"].'|'.$dataComing["statusCode"].'/'.$dataComing["statusDesc"],
				':coop_account_no' => $coop_account_no,
				':query_flag' => '1'
			];
			$log->writeLog('bindaccount',$arrayStruc);
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