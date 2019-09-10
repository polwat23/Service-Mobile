<?php
require_once('../../autoload.php');

if(isset($dataComing["api_key"]) && isset($dataComing["unique_id"]) && isset($dataComing["member_no"]) && isset($dataComing["email"])
&& isset($dataComing["device_name"])){
	$conmysql_nottest = $con->connecttomysql();
	if($api->check_apikey($dataComing["api_key"],$dataComing["unique_id"],$conmysql_nottest)){
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
			$template = $func->getTemplate('send_mail_forget_password',$conmysql);
			$arrayDataTemplate = array();
			$temp_pass = $lib->randomText('number',6);
			$arrayDataTemplate["FULL_NAME"] = (isset($rowName["MEMB_NAME"]) ? $rowName["MEMB_NAME"].' '.$rowName["MEMB_SURNAME"] : $member_no);
			$arrayDataTemplate["TEMP_PASSWORD"] = $temp_pass;
			$arrayDataTemplate["DEVICE_NAME"] = $dataComing["device_name"];
			$arrayDataTemplate["REQUEST_DATE"] = $lib->convertdate(date('Y-m-d H:i'),'D m Y',true);
			$conmysql->beginTransaction();
			$updateTemppass = $conmysql->prepare("UPDATE mdbmemberaccount SET temppass = :temp_pass,account_status = '-9',update_date = NOW() 
												WHERE member_no = :member_no");
			if($updateTemppass->execute([
				':temp_pass' => $temp_pass,
				':member_no' => $member_no
			])){
				$arrResponse = $lib->mergeTemplate($template["SUBJECT"],$template["BODY"],$arrayDataTemplate);
				if($lib->sendMail($dataComing["email"],$arrResponse["SUBJECT"],$arrResponse["BODY"],$mailFunction)){
					$conmysql->commit();
					if($func->logoutAll(null,$member_no,'-9',$conmysql)){
						$arrayResult['RESULT'] = TRUE;
						echo json_encode($arrayResult);
					}else{
						$arrayResult['RESPONSE_CODE'] = "SQL500";
						$arrayResult['RESPONSE'] = "Cannot update Temppass";
						$arrayResult['RESULT'] = FALSE;
						http_response_code(203);
						echo json_encode($arrayResult);
						exit();
					}
				}else{
					$conmysql->rollback();
					$arrayResult['RESPONSE_CODE'] = "MAIL500";
					$arrayResult['RESPONSE'] = "Cannot send mail";
					$arrayResult['RESULT'] = FALSE;
					http_response_code(203);
					echo json_encode($arrayResult);
					exit();
				}
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE_CODE'] = "SQL500";
				$arrayResult['RESPONSE'] = "Cannot update Temppass";
				$arrayResult['RESULT'] = FALSE;
				http_response_code(203);
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$arrayResult = array();
			$arrayResult['RESPONSE_CODE'] = "SQL400";
			$arrayResult['RESPONSE'] = "Not found a membership";
			$arrayResult['RESULT'] = FALSE;
			http_response_code(203);
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult = array();
		$arrayResult['RESPONSE_CODE'] = "PARAM500";
		$arrayResult['RESPONSE'] = "Invalid API KEY";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(203);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "PARAM400";
	$arrayResult['RESPONSE'] = "Not complete parameter";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(203);
	echo json_encode($arrayResult);
	exit();
}
?>