<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','menu_component'],$dataComing)){
	if($dataComing["menu_component"] == 'reportsmssuccess'){
		$getMessageLog = $conmysql->prepare("SELECT sms_message,member_no,tel_mobile,send_date,send_by,id_smstemplate
											FROM smslogwassent WHERE id_logsent = :id_logsent");
		$getMessageLog->execute([':id_logsent' => $dataComing["id_logsent"]]);
		$rowMessage = $getMessageLog->fetch(PDO::FETCH_ASSOC);
		$arrGRPAll = array();
		$destination = array();
		$arrDestGRP = array();
		$arrayTel = array();
		if(isset($rowMessage["member_no"]) && $rowMessage["member_no"] != ""){
			$arrayTel = $func->getSMSPerson('person',$rowMessage["member_no"],false,true);
		}else{
			$destination_temp["MEMBER_NO"] = null;
			$destination_temp["TEL"] = $rowMessage["tel_mobile"];
			$arrDestGRP[] = $destination_temp;
		}
		
		if(isset($arrDestGRP)){
			$arrayMerge = array_merge($arrayTel,$arrDestGRP);
		}else{
			$arrayMerge = $arrayTel;
		}
		foreach($arrayMerge as $dest){
			if(isset($dest["TEL"]) && $dest["TEL"] != ""){
				$message_body = $rowMessage["sms_message"];
				$arrayDest["cmd_sms"] = "CMD=".$config["CMD_SMS"]."&FROM=".$config["FROM_SERVICES_SMS"]."&TO=66".(substr($dest["TEL"],1,9))."&REPORT=Y&CHARGE=".$config["CHARGE_SMS"]."&CODE=".$config["CODE_SMS"]."&CTYPE=UNICODE&CONTENT=".$lib->unicodeMessageEncode($message_body);
				$arraySendSMS = $lib->sendSMS($arrayDest);
				if($arraySendSMS["RESULT"]){
					$arrGRPAll[$dest["MEMBER_NO"]] = $rowMessage["sms_message"];
				}else{
					$bulkInsert[] = "(null,'".$message_body."','".$dest["MEMBER_NO"]."',
							'sms','".$dest["TEL"]."',null,'".$arraySendSMS["MESSAGE"]."','".$payload["username"]."'".(isset($rowMessage["id_smstemplate"]) ? ",".$rowMessage["id_smstemplate"] : ",null").")";
					if(sizeof($bulkInsert) == 1000){
						$func->logSMSWasNotSent($bulkInsert);
						unset($bulkInsert);
					}
				}
			}
		}
		if(sizeof($arrGRPAll) > 0){
			$func->logSMSWasSent($rowMessage["id_smstemplate"],$arrGRPAll,$arrayMerge,$payload["username"],true);
		}
		if(sizeof($bulkInsert) > 0){
			$func->logSMSWasNotSent($bulkInsert);
			unset($bulkInsert);
			$bulkInsert = array();
		}
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else if($dataComing["menu_component"] == 'reportsmsnotsuccess'){
		$getMessageLog = $conmysql->prepare("SELECT topic,message,member_no,send_platform,tel_mobile,send_date,send_by,id_smstemplate
											FROM smswasnotsent WHERE id_smsnotsent = :id_smsnotsent");
		$getMessageLog->execute([':id_smsnotsent' => $dataComing["id_smsnotsent"]]);
		$rowMessage = $getMessageLog->fetch(PDO::FETCH_ASSOC);
		if($rowMessage["send_platform"] == 'sms'){
			$arrGRPAll = array();
			$destination = array();
			$arrDestGRP = array();
			$arrayTel = array();
			if(isset($rowMessage["member_no"]) && $rowMessage["member_no"] != ""){
				$arrayTel = $func->getSMSPerson('person',$rowMessage["member_no"],false,true);
			}else{
				$destination_temp["MEMBER_NO"] = null;
				$destination_temp["TEL"] = $rowMessage["tel_mobile"];
				$arrDestGRP[] = $destination_temp;
			}
			
			if(isset($arrDestGRP)){
				$arrayMerge = array_merge($arrayTel,$arrDestGRP);
			}else{
				$arrayMerge = $arrayTel;
			}
			foreach($arrayMerge as $dest){
				if(isset($dest["TEL"]) && $dest["TEL"] != ""){
					$message_body = $rowMessage["message"];
					$arrayDest["cmd_sms"] = "CMD=".$config["CMD_SMS"]."&FROM=".$config["FROM_SERVICES_SMS"]."&TO=66".(substr($dest["TEL"],1,9))."&REPORT=Y&CHARGE=".$config["CHARGE_SMS"]."&CODE=".$config["CODE_SMS"]."&CTYPE=UNICODE&CONTENT=".$lib->unicodeMessageEncode($message_body);
					$arraySendSMS = $lib->sendSMS($arrayDest);
					if($arraySendSMS["RESULT"]){
						$arrGRPAll[$dest["MEMBER_NO"]] = $rowMessage["message"];
					}else{
						$bulkInsert[] = "(null,'".$message_body."','".$dest["MEMBER_NO"]."',
								'sms','".$dest["TEL"]."',null,'".$arraySendSMS["MESSAGE"]."','".$payload["username"]."'".(isset($rowMessage["id_smstemplate"]) ? ",".$rowMessage["id_smstemplate"] : ",null").")";
						if(sizeof($bulkInsert) == 1000){
							$func->logSMSWasNotSent($bulkInsert);
							unset($bulkInsert);
						}
					}
				}
			}
			if(sizeof($arrGRPAll) > 0){
				$func->logSMSWasSent($rowMessage["id_smstemplate"],$arrGRPAll,$arrayMerge,$payload["username"],true);
			}
			if(sizeof($bulkInsert) > 0){
				$func->logSMSWasNotSent($bulkInsert);
				unset($bulkInsert);
				$bulkInsert = array();
			}
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$blukInsert = array();
			$blukInsertNot = array();
			$destination = array();
			$arrToken = $func->getFCMToken('person',$rowMessage["member_no"]);
			foreach($arrToken["LIST_SEND"] as $dest){
				if(isset($dest["TOKEN"]) && $dest["TOKEN"] != ""){
					$arrPayloadNotify["TO"] = array($dest["TOKEN"]);
					$arrPayloadNotify["MEMBER_NO"] = $dest["MEMBER_NO"];
					$arrMessage["SUBJECT"] = $rowMessage["topic"];
					$message = ($rowMessage["message"] ?? "-");
					$arrMessage["BODY"] = $message;
					$arrMessage["PATH_IMAGE"] = $pathImg ?? null;
					$arrPayloadNotify["PAYLOAD"] = $arrMessage;
					$arrPayloadNotify["SEND_BY"] = $payload["username"];
					$arrPayloadNotify["ID_TEMPLATE"] = $rowMessage["id_smstemplate"];
					if($lib->sendNotify($arrPayloadNotify,'person')){
						$blukInsert[] = "('1','".$rowMessage["topic"]."','".$message."','".($pathImg ?? null)."','".$dest["MEMBER_NO"]."','".$payload["username"]."'".(isset($rowMessage["id_smstemplate"]) ? ",".$rowMessage["id_smstemplate"] : ",null").")";
						if(sizeof($blukInsert) == 1000){
							$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
							$arrPayloadHistory["bulkInsert"] = $blukInsert;
							$func->insertHistory($arrPayloadHistory);
							unset($blukInsert);
							$blukInsert = array();
						}
					}else{
						$blukInsertNot[] = "('".$rowMessage["topic"]."','".$message."','".$dest["MEMBER_NO"]."','mobile_app',null,'".$dest["TOKEN"]."','ไม่สามารถส่งได้ให้ดู LOG','".$payload["username"]."'".(isset($rowMessage["id_smstemplate"]) ? ",".$rowMessage["id_smstemplate"] : ",null").",'".($pathImg ?? null)."')";
						if(sizeof($blukInsertNot) == 1000){
							$func->logSMSWasNotSent($blukInsertNot,false,'0',true);
							unset($blukInsertNot);
							$blukInsertNot = array();
						}
					}
				}
			}
			if(sizeof($blukInsert) > 0){
				$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
				$arrPayloadHistory["bulkInsert"] = $blukInsert;
				$func->insertHistory($arrPayloadHistory);
				unset($blukInsert);
				$blukInsert = array();
			}
			if(sizeof($blukInsertNot) > 0){
				$func->logSMSWasNotSent($blukInsertNot,false,'0',true);
				unset($blukInsertNot);
				$blukInsertNot = array();
			}
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}
	}else if($dataComing["menu_component"] == 'reportnotifysuccess'){
		$getMessageLog = $conmysql->prepare("SELECT his_title,his_detail,member_no,his_path_image,id_smstemplate,send_by,his_type
											FROM gchistory WHERE id_history = :id_history");
		$getMessageLog->execute([':id_history' => $dataComing["id_history"]]);
		$rowMessage = $getMessageLog->fetch(PDO::FETCH_ASSOC);
		$blukInsert = array();
		$blukInsertNot = array();
		$destination = array();
		$arrToken = $func->getFCMToken('person',$rowMessage["member_no"]);
		foreach($arrToken["LIST_SEND"] as $dest){
			if(isset($dest["TOKEN"]) && $dest["TOKEN"] != ""){
				$arrPayloadNotify["TO"] = array($dest["TOKEN"]);
				$arrPayloadNotify["MEMBER_NO"] = $dest["MEMBER_NO"];
				$arrMessage["SUBJECT"] = $rowMessage["his_title"];
				$message = ($rowMessage["his_detail"] ?? "-");
				$arrMessage["BODY"] = $message;
				$arrMessage["PATH_IMAGE"] = $rowMessage["his_path_image"] ?? null;
				$arrPayloadNotify["PAYLOAD"] = $arrMessage;
				$arrPayloadNotify["SEND_BY"] = $rowMessage["send_by"];
				$arrPayloadNotify["ID_TEMPLATE"] = $rowMessage["id_smstemplate"];
				if($lib->sendNotify($arrPayloadNotify,'person')){
					$blukInsert[] = "('1','".$rowMessage["his_title"]."','".$message."','".($rowMessage["his_path_image"] ?? null)."','".$dest["MEMBER_NO"]."','".$payload["username"]."'".(isset($rowMessage["id_smstemplate"]) ? ",".$rowMessage["id_smstemplate"] : ",null").")";
					if(sizeof($blukInsert) == 1000){
						$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
						$arrPayloadHistory["bulkInsert"] = $blukInsert;
						$func->insertHistory($arrPayloadHistory,$rowMessage["his_type"]);
						unset($blukInsert);
						$blukInsert = array();
					}
				}else{
					$blukInsertNot[] = "('".$rowMessage["his_title"]."','".$message."','".$dest["MEMBER_NO"]."','mobile_app',null,'".$dest["TOKEN"]."','ไม่สามารถส่งได้ให้ดู LOG','".$payload["username"]."'".(isset($rowMessage["id_smstemplate"]) ? ",".$rowMessage["id_smstemplate"] : ",null").",'".($rowMessage["his_path_image"] ?? null)."')";
					if(sizeof($blukInsertNot) == 1000){
						$func->logSMSWasNotSent($blukInsertNot,false,'0',true);
						unset($blukInsertNot);
						$blukInsertNot = array();
					}
				}
			}
		}
		if(sizeof($blukInsert) > 0){
			$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
			$arrPayloadHistory["bulkInsert"] = $blukInsert;
			$func->insertHistory($arrPayloadHistory,$rowMessage["his_type"]);
			unset($blukInsert);
			$blukInsert = array();
		}
		if(sizeof($blukInsertNot) > 0){
			$func->logSMSWasNotSent($blukInsertNot,false,'0',true);
			unset($blukInsertNot);
			$blukInsertNot = array();
		}
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else if($dataComing["menu_component"] == 'reportsmstranwassent'){
		$getMessageLog = $conmysql->prepare("SELECT sms_message,member_no,tel_mobile,send_date,send_by,id_smstemplate
											FROM smstranwassent WHERE id_smssent = :id_smssent");
		$getMessageLog->execute([':id_smssent' => $dataComing["id_smssent"]]);
		$rowMessage = $getMessageLog->fetch(PDO::FETCH_ASSOC);
		$arrGRPAll = array();
		$destination = array();
		$arrDestGRP = array();
		$arrayTel = array();
		if(isset($rowMessage["member_no"]) && $rowMessage["member_no"] != ""){
			$arrayTel = $func->getSMSPerson('person',$rowMessage["member_no"],false,true);
		}else{
			$destination_temp["MEMBER_NO"] = null;
			$destination_temp["TEL"] = $rowMessage["tel_mobile"];
			$arrDestGRP[] = $destination_temp;
		}
		
		if(isset($arrDestGRP)){
			$arrayMerge = array_merge($arrayTel,$arrDestGRP);
		}else{
			$arrayMerge = $arrayTel;
		}
		foreach($arrayMerge as $dest){
			if(isset($dest["TEL"]) && $dest["TEL"] != ""){
				$message_body = $rowMessage["sms_message"];
				$arrayDest["cmd_sms"] = "CMD=".$config["CMD_SMS"]."&FROM=".$config["FROM_SERVICES_SMS"]."&TO=66".(substr($dest["TEL"],1,9))."&REPORT=Y&CHARGE=".$config["CHARGE_SMS"]."&CODE=".$config["CODE_SMS"]."&CTYPE=UNICODE&CONTENT=".$lib->unicodeMessageEncode($message_body);
				$arraySendSMS = $lib->sendSMS($arrayDest);
				if($arraySendSMS["RESULT"]){
					$arrGRPAll[$dest["MEMBER_NO"]] = $rowMessage["sms_message"];
				}else{
					$bulkInsert[] = "(null,'".$message_body."','".$dest["MEMBER_NO"]."',
							'sms','".$dest["TEL"]."',null,'".$arraySendSMS["MESSAGE"]."','".$payload["username"]."'".(isset($rowMessage["id_smstemplate"]) ? ",".$rowMessage["id_smstemplate"] : ",null").")";
					if(sizeof($bulkInsert) == 1000){
						$func->logSMSWasNotSent($bulkInsert);
						unset($bulkInsert);
					}
				}
			}
		}
		if(sizeof($arrGRPAll) > 0){
			$func->logSMSWasSent($rowMessage["id_smstemplate"],$arrGRPAll,$arrayMerge,$payload["username"],true);
		}
		if(sizeof($bulkInsert) > 0){
			$func->logSMSWasNotSent($bulkInsert);
			unset($bulkInsert);
			$bulkInsert = array();
		}
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>
