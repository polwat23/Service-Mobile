<?php
set_time_limit(150);
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','k_mobile_no','citizen_id','coop_account_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'BindAccountConsent')){
		try {
			$coop_account_no = preg_replace('/-/','',$dataComing["coop_account_no"]);
			$mobile_no = preg_replace('/-/','',$dataComing["k_mobile_no"]);
			$arrPayloadverify = array();
			$arrPayloadverify['member_no'] = $payload["member_no"];
			$arrPayloadverify['coop_account_no'] = $coop_account_no.$lib->randomText('all',2);
			$arrPayloadverify['user_mobile_no'] = $mobile_no;
			$arrPayloadverify['citizen_id'] = $dataComing["citizen_id"];
			$arrPayloadverify["coop_key"] = $config["COOP_KEY"];
			$arrPayloadverify['exp'] = time() + 60;
			$sigma_key = $lib->generate_token();
			$arrPayloadverify['sigma_key'] = $sigma_key;
			$verify_token = $jwt_token->customPayload($arrPayloadverify, $config["SIGNATURE_KEY_VERIFY_API"]);
			$arrSendData = array();
			$arrSendData["verify_token"] = $verify_token;
			$arrSendData["app_id"] = $config["APP_ID"];
			$checkAccBankBeenbind = $conmysql->prepare("SELECT id_bindaccount FROM gcbindaccount WHERE member_no = :member_no and bindaccount_status IN('0','1')");
			$checkAccBankBeenbind->execute([':member_no' => $payload["member_no"]]);
			if($checkAccBankBeenbind->rowCount() > 0){
				$arrayResult['RESPONSE_CODE'] = "WS0036";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
			$checkBeenBindForPending = $conmysql->prepare("SELECT id_bindaccount FROM gcbindaccount WHERE member_no = :member_no and bindaccount_status = '8'");
			$checkBeenBindForPending->execute([
				':member_no' => $payload["member_no"]
			]);
			if($checkBeenBindForPending->rowCount() > 0){
				$arrayAccPending = array();
				while($rowAccPending = $checkBeenBindForPending->fetch()){
					$arrayAccPending[] = $rowAccPending["id_bindaccount"];
				}
				$deleteAccForPending = $conmysql->prepare("DELETE FROM gcbindaccount WHERE id_bindaccount IN(".implode(',',$arrayAccPending).")");
				$deleteAccForPending->execute();
			}
			if($payload["member_no"] == "dev@mode" || $payload["member_no"] == "salemode"){
				$member_no = $configAS[$payload["member_no"]];
			}else{
				$member_no = $payload["member_no"];
			}
			$fetchMemberName = $conoracle->prepare("SELECT MP.PRENAME_DESC,MB.MEMB_NAME,MB.MEMB_SURNAME 
													FROM MBMEMBMASTER MB LEFT JOIN MBUCFPRENAME MP ON MB.PRENAME_CODE = MP.PRENAME_CODE
													WHERE MB.member_no = :member_no");
			$fetchMemberName->execute([
				':member_no' => $member_no
			]);
			$rowMember = $fetchMemberName->fetch();
			$account_name_th = $rowMember["PRENAME_DESC"].$rowMember["MEMB_NAME"].' '.$rowMember["MEMB_SURNAME"];
			//$account_name_en = $arrResponseVerify->ACCOUNT_NAME_EN;
			$conmysql->beginTransaction();
			$insertPendingBindAccount = $conmysql->prepare("INSERT INTO gcbindaccount(sigma_key,member_no,deptaccount_no_coop,citizen_id,mobile_no,bank_account_name,bank_account_name_en,bank_code,id_token) 
															VALUES(:sigma_key,:member_no,:coop_account_no,:citizen_id,:mobile_no,:bank_account_name,:bank_account_name_en,'004',:id_token)");
			if($insertPendingBindAccount->execute([
				':sigma_key' => $sigma_key,
				':member_no' => $payload["member_no"],
				':coop_account_no' => $coop_account_no,
				':citizen_id' => $dataComing["citizen_id"],
				':mobile_no' => $mobile_no,
				':bank_account_name' => $account_name_th,
				':bank_account_name_en' => $account_name_th,
				':id_token' => $payload["id_token"]
			])){
				$responseAPI = $lib->posting_data($config["URL_API_COOPDIRECT"].'/request_reg_id_for_consent',$arrSendData);
				if(!$responseAPI){
					$arrayResult['RESPONSE_CODE'] = "WS0022";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
				$arrResponse = json_decode($responseAPI);
				if($arrResponse->RESULT){
					$conmysql->commit();
					$arrayResult["URL_CONSENT"] = $arrResponse->URL_CONSENT;
					$arrayResult['RESULT'] = TRUE;
					echo json_encode($arrayResult);
				}else{
					$conmysql->rollback();
					$text = '#Bind #WS0039 : '.date("Y-m-d H:i:s").' > '.json_encode($arrResponse).' | '.json_encode($arrPayloadverify);
					file_put_contents(__DIR__.'/../../log/consentbind_error.txt', $text . PHP_EOL, FILE_APPEND);
					$arrayResult['RESPONSE_CODE'] = "WS0039";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}else{
				$conmysql->rollback();
				$arrExecute = [
					':sigma_key' => $sigma_key,
					':member_no' => $payload["member_no"],
					':coop_account_no' => $coop_account_no,
					':citizen_id' => $dataComing["citizen_id"],
					':mobile_no' => $mobile_no,
					':bank_account_name' => $account_name_th,
					':bank_account_name_en' => $account_name_en,
					':limit_amt' => $func->getConstant('limit_withdraw'),
					':id_token' => $payload["id_token"]
				];
				$arrError = array();
				$arrError["EXECUTE"] = $arrExecute;
				$arrError["QUERY"] = $insertPendingBindAccount;
				$arrError["ERROR_CODE"] = 'WS1022';
				$lib->addLogtoTxt($arrError,'bind_error');
				$arrayResult['RESPONSE_CODE'] = "WS1022";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}catch(Throwable $e) {
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