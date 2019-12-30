<?php
set_time_limit(150);
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','id_bindaccount','sigma_key'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'BindAccountConsent')){
		if($payload["member_no"] == 'dev@mode'){
			$member_no = $config["MEMBER_NO_DEV_TRANSACTION"];
		}else if($payload["member_no"] == 'salemode'){
			$member_no = $config["MEMBER_NO_SALE_TRANSACTION"];
		}else{
			$member_no = $payload["member_no"];
		}
		$arrPayloadverify = array();
		$arrPayloadverify['member_no'] = $member_no;
		$check_account = $conmysql->prepare("SELECT id_bindaccount FROM gcbindaccount WHERE sigma_key = :sigma_key and id_bindaccount = :id_bindaccount and member_no = :member_no
											and bindaccount_status IN('0','1')");
		$check_account->execute([
			':sigma_key' => $dataComing["sigma_key"],
			':id_bindaccount' => $dataComing["id_bindaccount"],
			':member_no' => $member_no
		]);
		if($check_account->rowCount() > 0){
			$arrPayloadverify["coop_key"] = $config["COOP_KEY"];
			$arrPayloadverify['exp'] = time() + 60;
			$arrPayloadverify['sigma_key'] = $dataComing["sigma_key"];
			$verify_token = $jwt_token->customPayload($arrPayloadverify, $config["SIGNATURE_KEY_VERIFY_API"]);
			$arrSendData = array();
			$arrSendData["verify_token"] = $verify_token;
			$arrSendData["app_id"] = $config["APP_ID"];
			$conmysql->beginTransaction();
			$updateUnBindAccount = $conmysql->prepare("UPDATE gcbindaccount SET bindaccount_status = '-9',unbind_date = NOW() WHERE sigma_key = :sigma_key and id_bindaccount = :id_bindaccount");
			if($updateUnBindAccount->execute([
				':sigma_key' => $dataComing["sigma_key"],
				':id_bindaccount' => $dataComing["id_bindaccount"]
			])){
				$responseAPI = $lib->posting_data($config["URL_API_GENSOFT"].'/bindaccount/unbind_account',$arrSendData);
				if(!$responseAPI){
					$conmysql->rollback();
					$arrayResult['RESPONSE_CODE'] = "WS0029";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
				$arrResponse = json_decode($responseAPI);
				if($arrResponse->RESULT){
					$conmysql->commit();
					if(isset($new_token)){
						$arrayResult['NEW_TOKEN'] = $new_token;
					}
					$arrayResult['RESULT'] = TRUE;
					echo json_encode($arrayResult);
				}else{
					$conmysql->rollback();
					$text = '#Unbind #WS0040: '.date("Y-m-d H:i:s").' > '.json_encode($arrResponse).' | '.json_encode($arrPayloadverify);
					file_put_contents(__DIR__.'/../../log/unbind_error.txt', $text . PHP_EOL, FILE_APPEND);
					$arrayResult['RESPONSE_CODE'] = "WS0040";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}			
			}else{
				$conmysql->rollback();
				$arrExecute = [
					':sigma_key' => $dataComing["sigma_key"],
					':id_bindaccount' => $dataComing["id_bindaccount"]
				];
				$arrError = array();
				$arrError["EXECUTE"] = $arrExecute;
				$arrError["QUERY"] = $updateUnBindAccount;
				$arrError["ERROR_CODE"] = 'WS1021';
				$lib->addLogtoTxt($arrError,'bind_error');
				$arrayResult['RESPONSE_CODE'] = "WS1021";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0021";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
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