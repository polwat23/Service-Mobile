<?php
set_time_limit(150);
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','k_mobile_no','citizen_id','kb_account_no','coop_account_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'BindAccountConsent')){
		if($payload["member_no"] == 'dev@mode'){
			$member_no = $config["MEMBER_NO_DEV_TRANSACTION"];
		}else if($payload["member_no"] == 'salemode'){
			$member_no = $config["MEMBER_NO_SALE_TRANSACTION"];
		}else{
			$member_no = $payload["member_no"];
		}
		try {
			$kb_account_no = preg_replace('/-/','',$dataComing["kb_account_no"]);
			$coop_account_no = preg_replace('/-/','',$dataComing["coop_account_no"]);
			$mobile_no = preg_replace('/-/','',$dataComing["k_mobile_no"]);
			$arrPayloadverify = array();
			$arrPayloadverify['member_no'] = $member_no;
			$arrPayloadverify['coop_account_no'] = $coop_account_no;
			$arrPayloadverify['user_mobile_no'] = $mobile_no;
			$arrPayloadverify['citizen_id'] = $dataComing["citizen_id"];
			$arrPayloadverify['kb_account_no'] = $kb_account_no;
			$arrPayloadverify["coop_key"] = $config["COOP_KEY"];
			$arrPayloadverify['exp'] = time() + 60;
			$sigma_key = $lib->generate_token();
			$arrPayloadverify['sigma_key'] = $sigma_key;
			$verify_token = $jwt_token->customPayload($arrPayloadverify, $config["SIGNATURE_KEY_VERIFY_API"]);
			$arrSendData = array();
			$arrSendData["verify_token"] = $verify_token;
			$arrSendData["app_id"] = $config["APP_ID"];
			$checkAccBankBeenbind = $conmysql-prepare("SELECT id_bindaccount FROM gcbindaccount WHERE deptaccount_no_bank = :kb_account_no and bindaccount_status IN('0','1')");
			$checkAccBankBeenbind->execute([':kb_account_no' => $kb_account_no]);
			if($checkAccBankBeenbind->rowCount() > 0){
				$arrayResult['RESPONSE_CODE'] = "WS0036";
				if($lang_locale == 'th'){
					$arrayResult['RESPONSE_MESSAGE'] = "บัญชีปลายทางของท่านได้มีการผูกบัญชีไว้อยู่แล้ว หากท่านต้องการผูกบัญชีใหม่ กรุณายกเลิกผูกบัญชีกับบัญชีเดิมก่อน หรือสามารถเปลี่ยนบัญชีที่ผูกได้";
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = "Destination account has been bind if you want to rebind account please unbind from old account or change to bind account";
				}
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
			$checkBeenBindForPending = $conmysql->prepare("SELECT id_bindaccount FROM gcbindaccount WHERE member_no = :member_no and bindaccount_status = '8'");
			$checkBeenBindForPending->execute([
				':member_no' => $member_no
			]);
			if($checkBeenBindForPending->rowCount() > 0){
				$arrayAccPending = array();
				while($rowAccPending = $checkBeenBindForPending->fetch()){
					$arrayAccPending[] = $rowAccPending["id_bindaccount"];
				}
				$deleteAccForPending = $conmysql->prepare("DELETE FROM gcbindaccount WHERE id_bindaccount IN(".implode(',',$arrayAccPending).")");
				$deleteAccForPending->execute();
			}
			$conmysql->beginTransaction();
			$insertPendingBindAccount = $conmysql->prepare("INSERT INTO gcbindaccount(sigma_key,member_no,deptaccount_no_coop,deptaccount_no_bank,mobile_no,bank_code,id_bankpalette,id_token) 
															VALUES(:sigma_key,:member_no,:coop_account_no,:kb_account_no,:mobile_no,'004',2,:id_token)");
			if($insertPendingBindAccount->execute([
				':sigma_key' => $sigma_key,
				':member_no' => $member_no,
				':coop_account_no' => $coop_account_no,
				':kb_account_no' => $kb_account_no,
				':mobile_no' => $mobile_no,
				':id_token' => $payload["id_token"]
			])){
				$responseAPI = $lib->posting_data($config["URL_API_GENSOFT"].'/bindaccount/pending_bind_account',$arrSendData);
				if(!$responseAPI){
					$arrayResult['RESPONSE_CODE'] = "WS0022";
					if($lang_locale == 'th'){
						$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถผูกบัญชีได้ กรุณาติดต่อสหกรณ์ #WS0022";
					}else{
						$arrayResult['RESPONSE_MESSAGE'] = "Cannot bind account please contact cooperative #WS0022";
					}
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
				$arrResponse = json_decode($responseAPI);
				if($arrResponse->RESULT){
					$conmysql->commit();
					$arrayResult["URL_CONSENT"] = $arrResponse->URL_CONSENT;
					if(isset($new_token)){
						$arrayResult['NEW_TOKEN'] = $new_token;
					}
					$arrayResult['RESULT'] = TRUE;
					echo json_encode($arrayResult);
				}else{
					$conmysql->rollback();
					$text = '#Bind #WS0039 : '.date("Y-m-d H:i:s").' > '.json_encode($arrResponse).' | '.json_encode($arrPayloadverify);
					file_put_contents(__DIR__.'/../../log/consentbind_error.txt', $text . PHP_EOL, FILE_APPEND);
					$arrayResult['RESPONSE_CODE'] = "WS0039";
					if($lang_locale == 'th'){
						$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถผูกบัญชีได้ กรุณาติดต่อสหกรณ์ #WS0039";
					}else{
						$arrayResult['RESPONSE_MESSAGE'] = "Cannot bind account please contact cooperative #WS0039";
					}
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}else{
				$conmysql->rollback();
				$arrExecute = [
					':sigma_key' => $sigma_key,
					':member_no' => $member_no,
					':coop_account_no' => $coop_account_no,
					':kb_account_no' => $kb_account_no,
					':mobile_no' => $mobile_no,
					':id_token' => $payload["id_token"]
				];
				$arrError = array();
				$arrError["EXECUTE"] = $arrExecute;
				$arrError["QUERY"] = $insertPendingBindAccount;
				$arrError["ERROR_CODE"] = 'WS1022';
				$lib->addLogtoTxt($arrError,'bind_error');
				$arrayResult['RESPONSE_CODE'] = "WS1022";
				if($lang_locale == 'th'){
					$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถผูกบัญชีได้ กรุณาติดต่อสหกรณ์ #WS1022";
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = "Cannot bind account please contact cooperative #WS1022";
				}
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
			if($lang_locale == 'th'){
					$arrayResult['RESPONSE_MESSAGE'] = "เกิดข้อผิดพลาดบางประการกรุณาติดต่อสหกรณ์ #WS9999";
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = "Something wrong please contact cooperative #WS9999";
			}
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		if($lang_locale == 'th'){
			$arrayResult['RESPONSE_MESSAGE'] = "ท่านไม่มีสิทธิ์ใช้งานเมนูนี้";
		}else{
			$arrayResult['RESPONSE_MESSAGE'] = "You not have permission for this menu";
		}
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	if($lang_locale == 'th'){
		$arrayResult['RESPONSE_MESSAGE'] = "มีบางอย่างผิดพลาดกรุณาติดต่อสหกรณ์ #WS4004";
	}else{
		$arrayResult['RESPONSE_MESSAGE'] = "Something wrong please contact cooperative #WS4004";
	}
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>