<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','message_emoji_','type_send','channel_send'],$dataComing)){
	if($func->check_permission_core($payload,'sms','sendmessage')){
		if($dataComing["channel_send"] == "mobile_app"){
			if(isset($dataComing["send_image"]) && $dataComing["send_image"] != null){
				$destination = __DIR__.'/../../../resource/image_wait_to_be_sent';
				$file_name = $lib->randomText('all',6);
				if(!file_exists($destination)){
					mkdir($destination, 0777, true);
				}
				$createImage = $lib->base64_to_img($dataComing["send_image"],$file_name,$destination,null);
				if($createImage == 'oversize'){
					$arrayResult['RESPONSE_MESSAGE'] = "รูปภาพที่ต้องการส่งมีขนาดใหญ่เกินไป";
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}else{
					if($createImage){
						$pathImg = $config["URL_SERVICE"]."resource/image_wait_to_be_sent/".$createImage["normal_path"];
					}else{
						$arrayResult['RESPONSE_MESSAGE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
						$arrayResult['RESULT'] = FALSE;
						echo json_encode($arrayResult);
						exit();
					}
				}
			}
			if($dataComing["type_send"] == "person"){
				$arrPayloadNotify = array();
				$arrMessage = array();
				if(isset($dataComing["message_importData"]) && $dataComing["message_importData"] != ""){
					$blukInsert = array();
					$blukInsertNot = array();
					foreach($dataComing["destination"] as $target){
						$destination = strtolower(str_pad($target,8,0,STR_PAD_LEFT));
						$arrToken = $func->getFCMToken('person',array($destination));
						if(isset($arrToken["TOKEN"])){
							$arrPayloadNotify["TO"] = $arrToken["TOKEN"];
							$arrPayloadNotify["MEMBER_NO"] = $arrToken["MEMBER_NO"];
							$arrMessage["SUBJECT"] = $dataComing["topic_emoji_"];
							if(isset($dataComing["message_importData"]) && $dataComing["message_importData"] != ""){
								$message = $dataComing["message_importData"][$destination];
							}else{
								$message = $dataComing["message_emoji_"];
							}
							$arrMessage["BODY"] = $message;
							$arrMessage["PATH_IMAGE"] = $pathImg ?? null;
							$arrPayloadNotify["PAYLOAD"] = $arrMessage;
							if($lib->sendNotify($arrPayloadNotify,$dataComing["type_send"])){
								$blukInsert[] = "('1','".$dataComing["topic_emoji_"]."','".$message."','".($pathImg ?? null)."','".$destination."')";
								if(sizeof($blukInsert) == 1000){
									$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
									$arrPayloadHistory["bulkInsert"] = $blukInsert;
									$func->insertHistory($arrPayloadHistory);
									unset($blukInsert);
									$blukInsert = array();
								}
							}else{
								foreach($arrToken["TOKEN"] as $token){
									$blukInsertNot[] = "('".$message."','".$destination."','".$token."','".$dataComing["channel_send"]."','".$payload["username"]."')";
									if(sizeof($blukInsertNot) == 1000){
										$func->logSMSWasNotSent($blukInsertNot);
										unset($blukInsertNot);
										$blukInsertNot = array();
									}
								}
							}
						}
					}
					if(sizeof($blukInsertNot) > 0){
						$func->logSMSWasNotSent($blukInsertNot);
						unset($blukInsertNot);
						$blukInsertNot = array();
					}
					if(sizeof($blukInsert) > 0){
						$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
						$arrPayloadHistory["bulkInsert"] = $blukInsert;
						$func->insertHistory($arrPayloadHistory);
						unset($blukInsert);
						$blukInsert = array();
					}
					$arrayResult['RESULT'] = TRUE;
					echo json_encode($arrayResult);
				}else{
					$destination = array();
					foreach($dataComing["destination"] as $target){
						$destination[] = strtolower(str_pad($target,8,0,STR_PAD_LEFT));
					}
					$arrToken = $func->getFCMToken('person',$destination);
					$arrPayloadNotify["TO"] = $arrToken["TOKEN"];
					$arrPayloadNotify["MEMBER_NO"] = $arrToken["MEMBER_NO"];
					$arrMessage["SUBJECT"] = $dataComing["topic_emoji_"];
					$arrMessage["BODY"] = $dataComing["message_emoji_"];
					$arrMessage["PATH_IMAGE"] = $pathImg ?? null;
					$arrPayloadNotify["PAYLOAD"] = $arrMessage;
					$arrPayloadNotify["TYPE_SEND_HISTORY"] = "onemessage";
					if($func->insertHistory($arrPayloadNotify,'1')){
						if($lib->sendNotify($arrPayloadNotify,$dataComing["type_send"])){
							$arrayResult['RESULT'] = TRUE;
							echo json_encode($arrayResult);
						}else{
							$arrayResult['RESPONSE'] = "ส่งข้อความล้มเหลว กรุณาติดต่อผู้พัฒนา";
							$arrayResult['RESULT'] = FALSE;
							echo json_encode($arrayResult);
							exit();
						}
					}else{
						$arrayResult['RESPONSE'] = "ไม่สามารถส่งข้อความได้เนื่องจากไม่สามารถบันทึกประวัติการส่งแจ้งเตือนได้";
						$arrayResult['RESULT'] = FALSE;
						echo json_encode($arrayResult);
						exit();
					}
				}
			}else{
				$arrToken = $func->getFCMToken('all');
				$arrPayloadNotify["TO"] = $arrToken["TOKEN"];
				$arrPayloadNotify["MEMBER_NO"] = $arrToken["MEMBER_NO"];
				$arrMessage["SUBJECT"] = $dataComing["topic_emoji_"];
				$arrMessage["BODY"] = $dataComing["message_emoji_"];
				$arrMessage["PATH_IMAGE"] = $pathImg ?? null;
				$arrPayloadNotify["PAYLOAD"] = $arrMessage;
				$arrPayloadNotify["TYPE_SEND_HISTORY"] = "onemessage";
				if($func->insertHistory($arrPayloadNotify,'1')){
					if($lib->sendNotify($arrPayloadNotify,'person')){
					//if($lib->sendNotify($arrPayloadNotify,$dataComing["type_send"])){ //รอแก้ไขส่งทุกคน Subscribe ตามห้อง
						$arrayResult['RESULT'] = TRUE;
						echo json_encode($arrayResult);
					}else{
						$arrayResult['RESPONSE'] = "ส่งข้อความล้มเหลว กรุณาติดต่อผู้พัฒนา";
						$arrayResult['RESULT'] = FALSE;
						echo json_encode($arrayResult);
						exit();
					}
				}else{
					$arrayResult['RESPONSE'] = "ไม่สามารถส่งข้อความได้เนื่องจากไม่สามารถบันทึกประวัติการส่งแจ้งเตือนได้";
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}
		}else if($dataComing["channel_send"] == "sms"){
			if($dataComing["type_send"] == "person"){
				$destination = array();
				$arrDestGRP = array();
				foreach($dataComing["destination"] as $target){
					$destination_temp = array();
					if(mb_strlen($target) <= 8){
						$destination[] = strtolower(str_pad($target,8,0,STR_PAD_LEFT));
					}else if(mb_strlen($target) == 10){
						$destination_temp["MEMBER_NO"] = null;
						$destination_temp["TEL"] = $target;
						$arrDestGRP[] = $destination_temp;
					}
				}
				$arrayTel = $func->getSMSPerson('person',$destination);
				if(isset($arrDestGRP)){
					$arrayMerge = array_merge($arrayTel,$arrDestGRP);
				}else{
					$arrayMerge = $arrayTel;
				}
				if(isset($dataComing["message_importData"]) && $dataComing["message_importData"] != ""){
					$message = $dataComing["message_importData"];
					$arrayLogSMS = $func->logSMSWasSent($message,$arrayMerge,$payload["username"],true);
				}else{
					$arrayLogSMS = $func->logSMSWasSent($dataComing["message_emoji_"],$arrayMerge,$payload["username"]);
				}
				$arrayResult['RESULT'] = $arrayLogSMS;
				echo json_encode($arrayResult);
			}else{
				$arrayTel = $func->getSMSPerson('all');
				$arrayLogSMS = $func->logSMSWasSent($dataComing["message_emoji_"],$arrayTel,$payload["username"]);
				$arrayResult['RESULT'] = $arrayLogSMS;
				echo json_encode($arrayResult);
			}
		}else{
			$arrayResult['RESPONSE'] = "ยังไม่รองรับรูปแบบการส่งนี้";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>