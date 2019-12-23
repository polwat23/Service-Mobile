<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['api_token','unique_id','member_no','email','device_name'],$dataComing)){
	$arrPayload = $auth->check_apitoken($dataComing["api_token"],$config["SECRET_KEY_JWT"]);
	if(!$arrPayload["VALIDATE"]){
		$arrayResult['RESPONSE_CODE'] = "WS0001";
		if($lang_locale == 'th'){
			$arrayResult['RESPONSE_MESSAGE'] = "มีบางอย่างผิดพลาดกรุณาติดต่อสหกรณ์ #WS0001";
		}else{
			$arrayResult['RESPONSE_MESSAGE'] = "Something wrong please contact cooperative #WS0001";
		}
		$arrayResult['RESULT'] = FALSE;
		http_response_code(401);
		echo json_encode($arrayResult);
		exit();
	}
	$member_no = str_pad($dataComing["member_no"],8,0,STR_PAD_LEFT);
	$checkMember = $conmysql->prepare("SELECT id_account FROM mdbmemberaccount 
										WHERE member_no = :member_no and email = :email");
	$checkMember->execute([
		':member_no' => $member_no,
		':email' => $dataComing["email"]
	]);
	if($checkMember->rowCount() > 0){
		$getNameMember = $conoracle->prepare("SELECT memb_name,memb_surname FROM mbmembmaster WHERE member_no = :member_no");
		$getNameMember->execute([':member_no' => $member_no]);
		$rowName = $getNameMember->fetch();
		$template = $func->getTemplate('send_mail_forget_password');
		$arrayDataTemplate = array();
		$temp_pass = $lib->randomText('number',6);
		$arrayDataTemplate["FULL_NAME"] = (isset($rowName["MEMB_NAME"]) ? $rowName["MEMB_NAME"].' '.$rowName["MEMB_SURNAME"] : $member_no);
		$arrayDataTemplate["TEMP_PASSWORD"] = $temp_pass;
		$arrayDataTemplate["DEVICE_NAME"] = $arrPayload["PAYLOAD"]["device_name"];
		$arrayDataTemplate["REQUEST_DATE"] = $lib->convertdate(date('Y-m-d H:i'),'D m Y',true);
		$conmysql->beginTransaction();
		$updateTemppass = $conmysql->prepare("UPDATE gcmemberaccount SET temppass = :temp_pass,account_status = '-9' 
											WHERE member_no = :member_no");
		if($updateTemppass->execute([
			':temp_pass' => $temp_pass,
			':member_no' => $member_no
		])){
			$arrResponse = $lib->mergeTemplate($template["SUBJECT"],$template["BODY"],$arrayDataTemplate);
			if($lib->sendMail($dataComing["email"],$arrResponse["SUBJECT"],$arrResponse["BODY"],$mailFunction)){
				$conmysql->commit();
				if($func->logoutAll(null,$member_no,'-9')){
					$arrayResult['RESULT'] = TRUE;
					echo json_encode($arrayResult);
				}else{
					$arrayResult['RESPONSE_CODE'] = "WS1013";
					if($lang_locale == 'th'){
						$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถเปลี่ยนรหัสผ่านได้กรุณาติดต่อสหกรณ์ #WS1013";
					}else{
						$arrayResult['RESPONSE_MESSAGE'] = "Cannot change password please contact cooperative #WS1013";
					}
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE_CODE'] = "WS0019";
				if($lang_locale == 'th'){
					$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถส่งอีเมลได้ กรุณาติดต่อสหกรณ์";
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = "Cannot send email please contact cooperative";
				}
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$conmysql->rollback();
			$arrExecute = [
				':temp_pass' => $temp_pass,
				':member_no' => $member_no
			];
			$arrError = array();
			$arrError["EXECUTE"] = $arrExecute;
			$arrError["QUERY"] = $updateTemppass;
			$arrError["ERROR_CODE"] = 'WS1014';
			$lib->addLogtoTxt($arrError,'forget_error');
			$arrayResult['RESPONSE_CODE'] = "WS1014";
			if($lang_locale == 'th'){
				$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถขอรหัสผ่านชั่วคราวได้กรุณาติดต่อสหกรณ์ #WS1014";
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = "Cannot request temp password please contact cooperative #WS1014";
			}
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0003";
		if($lang_locale == 'th'){
			$arrayResult['RESPONSE_MESSAGE'] = "ไม่พบข้อมูลผู้ใช้";
		}else{
			$arrayResult['RESPONSE_MESSAGE'] = "Not found membership";
		}
		$arrayResult['RESULT'] = FALSE;
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