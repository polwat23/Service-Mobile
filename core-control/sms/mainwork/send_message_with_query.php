<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','message_emoji_','type_send','channel_send','id_query'],$dataComing)){
	if($func->check_permission_core($payload,'sms','sendmessageall') || $func->check_permission_core($payload,'sms','sendmessageperson')){
		$id_template = isset($dataComing["id_smstemplate"]) && $dataComing["id_smstemplate"] != "" ? $dataComing["id_smstemplate"] : null;
		if($dataComing["channel_send"] == "mobile_app"){
			$getQuery = $conmysql->prepare("SELECT sms_query,column_selected,is_bind_param,target_field,condition_target FROM smsquery WHERE id_smsquery = :id_query");
			$getQuery->execute([':id_query' => $dataComing["id_query"]]);
			if($getQuery->rowCount() > 0){
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
				$blukInsert = array();
				$blukInsertNot = array();
				$rowQuery = $getQuery->fetch(PDO::FETCH_ASSOC);
				$arrColumn = explode(',',$rowQuery["column_selected"]);
				if($rowQuery["is_bind_param"] == '0'){
					$queryTarget = $conoracle->prepare($rowQuery['sms_query']);
					$queryTarget->execute();
					while($rowTarget = $queryTarget->fetch(PDO::FETCH_ASSOC)){
						$arrGroupMessage = array();
						$arrDestination = array();
						$arrMemberNoDestination = array();
						$arrTarget = array();
						foreach($arrColumn as $column){
							$arrTarget[$column] = $rowTarget[strtoupper($column)] ?? null;
						}
						$arrMessageMerge = $lib->mergeTemplate($dataComing["topic_emoji_"],$dataComing["message_emoji_"],$arrTarget);
						$arrToken = $func->getFCMToken('person',array($rowTarget[$rowQuery["target_field"]]));
						if(isset($arrToken["LIST_SEND"][0]["TOKEN"]) && $arrToken["LIST_SEND"][0]["TOKEN"] != ""){
							if($arrToken["LIST_SEND"][0]["RECEIVE_NOTIFY_NEWS"] == "1"){
								$arrPayloadNotify["TO"] = array($arrToken["LIST_SEND"][0]["TOKEN"]);
								$arrPayloadNotify["MEMBER_NO"] = $arrToken["LIST_SEND"][0]["MEMBER_NO"];
								$arrMessage["SUBJECT"] = $arrMessageMerge["SUBJECT"];
								$arrMessage["BODY"] = $arrMessageMerge["BODY"];
								$arrMessage["PATH_IMAGE"] = $pathImg ?? null;
								$arrPayloadNotify["PAYLOAD"] = $arrMessage;
								if($lib->sendNotify($arrPayloadNotify,$dataComing["type_send"])){
									$blukInsert[] = "('1','".$arrMessageMerge["SUBJECT"]."','".$arrMessageMerge["BODY"]."','".($pathImg ?? null)."','".$arrToken["LIST_SEND"][0]["MEMBER_NO"]."')";
									if(sizeof($blukInsert) == 1000){
										$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
										$arrPayloadHistory["bulkInsert"] = $blukInsert;
										$func->insertHistory($arrPayloadHistory);
										unset($blukInsert);
										$blukInsert = array();
									}
								}else{
									$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$arrToken["LIST_SEND"][0]["MEMBER_NO"]."','".$dataComing["channel_send"]."',null,'".$arrToken["LIST_SEND"][0]["TOKEN"]."','ไม่สามารถส่งได้ให้ดู LOG','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
									if(sizeof($blukInsertNot) == 1000){
										$func->logSMSWasNotSent($blukInsertNot);
										unset($blukInsertNot);
										$blukInsertNot = array();
									}
								}
							}else{
								$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$arrToken["LIST_SEND"][0]["MEMBER_NO"]."','".$dataComing["channel_send"]."',null,'".$arrToken["LIST_SEND"][0]["TOKEN"]."','บัญชีปลายทางไม่ประสงค์เปิดรับการแจ้งเตือน','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
								if(sizeof($blukInsertNot) == 1000){
									$func->logSMSWasNotSent($blukInsertNot);
									unset($blukInsertNot);
									$blukInsertNot = array();
								}
							}
						}else{
							$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$rowTarget[$rowQuery["target_field"]]."','".$dataComing["channel_send"]."',null,null,'หา Token ในการส่งไม่เจออาจจะเพราะไม่อนุญาตให้ส่งแจ้งเตือนเข้าเครื่อง','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
							if(sizeof($blukInsertNot) == 1000){
								$func->logSMSWasNotSent($blukInsertNot);
								unset($blukInsertNot);
								$blukInsertNot = array();
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
					$arrayResult["RESULT"] = TRUE;
					echo json_encode($arrayResult);
				}else{
					$query = $rowQuery['sms_query'];
					if(stripos($query,'WHERE') === FALSE){
						if(stripos($query,'GROUP BY') !== FALSE){
							$arrQuery = explode('GROUP BY',$query);
							$query = $arrQuery[0]." WHERE ".$rowQuery["condition_target"]." GROUP BY ".$arrQuery[1];
						}else{
							$query .= " WHERE ".$rowQuery["condition_target"];
						}
					}else{
						if(stripos($query,'GROUP BY') !== FALSE){
							$arrQuery = explode('GROUP BY',$query);
							$query = $arrQuery[0]." and ".$rowQuery["condition_target"]." GROUP BY ".$arrQuery[1];
						}else{
							$query .= " and ".$rowQuery["condition_target"];
						}
					}
					foreach($dataComing["destination"] as $target){
						$target = strtolower($lib->mb_str_pad($target));
						$queryTarget = $conoracle->prepare($query);
						$queryTarget->execute([':'.$rowQuery["target_field"] => $target]);
						$rowTarget = $queryTarget->fetch(PDO::FETCH_ASSOC);
						if(isset($rowTarget[$rowQuery["target_field"]])){
							$arrGroupMessage = array();
							$arrDestination = array();
							$arrMemberNoDestination = array();
							$arrTarget = array();
							foreach($arrColumn as $column){
								$arrTarget[$column] = $rowTarget[strtoupper($column)] ?? null;
							}
							$arrMessageMerge = $lib->mergeTemplate($dataComing["topic_emoji_"],$dataComing["message_emoji_"],$arrTarget);
							$arrToken = $func->getFCMToken('person',array($target));
							if(sizeof($arrToken["MEMBER_NO"]) > 0){
								if(isset($arrToken["LIST_SEND"][0]["TOKEN"]) && $arrToken["LIST_SEND"][0]["TOKEN"] != ""){
									if($arrToken["LIST_SEND"][0]["RECEIVE_NOTIFY_NEWS"] == "1"){
										$arrPayloadNotify["TO"] = array($arrToken["LIST_SEND"][0]["TOKEN"]);
										$arrPayloadNotify["MEMBER_NO"] = $arrToken["LIST_SEND"][0]["MEMBER_NO"];
										$arrMessage["SUBJECT"] = $arrMessageMerge["SUBJECT"];
										$arrMessage["BODY"] = $arrMessageMerge["BODY"];
										$arrMessage["PATH_IMAGE"] = $pathImg ?? null;
										$arrPayloadNotify["PAYLOAD"] = $arrMessage;
										if($lib->sendNotify($arrPayloadNotify,$dataComing["type_send"])){
											$blukInsert[] = "('1','".$arrMessageMerge["SUBJECT"]."','".$arrMessageMerge["BODY"]."','".($pathImg ?? null)."','".$arrToken["LIST_SEND"][0]["MEMBER_NO"]."')";
											if(sizeof($blukInsert) == 1000){
												$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
												$arrPayloadHistory["bulkInsert"] = $blukInsert;
												$func->insertHistory($arrPayloadHistory);
												unset($blukInsert);
												$blukInsert = array();
											}
										}else{
											$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$arrToken["LIST_SEND"][0]["MEMBER_NO"]."','".$dataComing["channel_send"]."',null,'".$arrToken["LIST_SEND"][0]["TOKEN"]."','ไม่สามารถส่งได้ให้ดู LOG','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
											if(sizeof($blukInsertNot) == 1000){
												$func->logSMSWasNotSent($blukInsertNot);
												unset($blukInsertNot);
												$blukInsertNot = array();
											}
										}
									}else{
										$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$arrToken["LIST_SEND"][0]["MEMBER_NO"]."','".$dataComing["channel_send"]."',null,'".$arrToken["LIST_SEND"][0]["TOKEN"]."','บัญชีปลายทางไม่ประสงค์เปิดรับการแจ้งเตือน','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
										if(sizeof($blukInsertNot) == 1000){
											$func->logSMSWasNotSent($blukInsertNot);
											unset($blukInsertNot);
											$blukInsertNot = array();
										}
									}
								}else{
									$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$target."','".$dataComing["channel_send"]."',null,null,'หา Token ในการส่งไม่เจออาจจะเพราะไม่อนุญาตให้ส่งแจ้งเตือนเข้าเครื่อง','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
									if(sizeof($blukInsertNot) == 1000){
										$func->logSMSWasNotSent($blukInsertNot);
										unset($blukInsertNot);
										$blukInsertNot = array();
									}
								}
							}else{
								$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$target."','".$dataComing["channel_send"]."',null,null,'สมาชิกยังไม่ได้ใช้งานแอปพลิเคชั่น','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
								if(sizeof($blukInsertNot) == 1000){
									$func->logSMSWasNotSent($blukInsertNot);
									unset($blukInsertNot);
									$blukInsertNot = array();
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
					$arrayResult["RESULT"] = TRUE;
					echo json_encode($arrayResult);
				}
			}else{
				$arrayResult['RESPONSE'] = "ไม่พบชุดคิวรี่ข้อมูล กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$getQuery = $conmysql->prepare("SELECT sms_query,column_selected,is_bind_param,target_field,condition_target FROM smsquery WHERE id_smsquery = :id_query");
			$getQuery->execute([':id_query' => $dataComing["id_query"]]);
			if($getQuery->rowCount() > 0){
				$arrGRPAll = array();
				$arrayMerge = array();
				$bulkInsert = array();
				$rowQuery = $getQuery->fetch(PDO::FETCH_ASSOC);
				$arrColumn = explode(',',$rowQuery["column_selected"]);
				if($rowQuery["is_bind_param"] == '0'){
					$queryTarget = $conoracle->prepare($rowQuery['sms_query']);
					$queryTarget->execute();
					while($rowTarget = $queryTarget->fetch(PDO::FETCH_ASSOC)){
						$arrTarget = array();
						foreach($arrColumn as $column){
							$arrTarget[$column] = $rowTarget[strtoupper($column)] ?? null;
						}
						$arrMessage = $lib->mergeTemplate(null,$dataComing["message_emoji_"],$arrTarget);
						$arrayTel = $func->getSMSPerson('person',array($rowTarget[$rowQuery["target_field"]]));
						if(isset($arrayTel[0]["TEL"]) && $arrayTel[0]["TEL"] != ""){
							$arrayDest["cmd_sms"] = "CMD=".$config["CMD_SMS"]."&FROM=".$config["FROM_SERVICES_SMS"]."&TO=66".(substr($arrayTel[0]["TEL"],1,9))."&REPORT=Y&CHARGE=".$config["CHARGE_SMS"]."&CODE=".$config["CODE_SMS"]."&CTYPE=UNICODE&CONTENT=".$lib->unicodeMessageEncode($arrMessage["BODY"]);
							$arraySendSMS = $lib->sendSMS($arrayDest);
							if($arraySendSMS["RESULT"]){
								$arrayMerge[] = $arrayTel[0];
								$arrGRPAll[$arrayTel[0]["MEMBER_NO"]] = $arrMessage["BODY"];
							}else{
								$bulkInsert[] = "('".$arrMessage["BODY"]."','".$arrayTel[0]["MEMBER_NO"]."',
										'sms','".$arrayTel[0]["TEL"]."',null,'".$arraySendSMS["MESSAGE"]."','".$payload["username"]."','".$id_template."')";
								if(sizeof($bulkInsert) == 1000){
									$func->logSMSWasNotSent($bulkInsert);
									unset($bulkInsert);
									$bulkInsert = array();
								}
							}
						}else{
							$bulkInsert[] = "('".$arrMessage["BODY"]."','".$arrayTel[0]["MEMBER_NO"]."',
							'sms',null,null,'ไม่พบเบอร์โทรศัพท์','".$payload["username"]."','".$id_template."')";
							if(sizeof($bulkInsert) == 1000){
								$func->logSMSWasNotSent($bulkInsert);
								unset($bulkInsert);
								$bulkInsert = array();
							}
						}
					}
					if(sizeof($bulkInsert) > 0){
						$func->logSMSWasNotSent($bulkInsert);
						unset($bulkInsert);
						$bulkInsert = array();
					}
					if(sizeof($arrGRPAll) > 0){
						$arrayLogSMS = $func->logSMSWasSent($id_template,$arrGRPAll,$arrayMerge,$payload["username"],true);
						$arrayResult['RESULT'] = $arrayLogSMS;
					}else{
						$arrayResult['RESULT'] = TRUE;
					}
					echo json_encode($arrayResult);
				}else{
					$query = $rowQuery['sms_query'];
					if(stripos($query,'WHERE') === FALSE){
						if(stripos($query,'GROUP BY') !== FALSE){
							$arrQuery = explode('GROUP BY',$query);
							$query = $arrQuery[0]." WHERE ".$rowQuery["condition_target"]." GROUP BY ".$arrQuery[1];
						}else{
							$query .= " WHERE ".$rowQuery["condition_target"];
						}
					}else{
						if(stripos($query,'GROUP BY') !== FALSE){
							$arrQuery = explode('GROUP BY',$query);
							$query = $arrQuery[0]." and ".$rowQuery["condition_target"]." GROUP BY ".$arrQuery[1];
						}else{
							$query .= " and ".$rowQuery["condition_target"];
						}
					}
					foreach($dataComing["destination"] as $target){
						if(mb_strlen($target) <= 8){
							$destination = strtolower($lib->mb_str_pad($target));
						}else{
							$destination = $target;
						}
						$queryTarget = $conoracle->prepare($query);
						$queryTarget->execute([':'.$rowQuery["target_field"] => $destination]);
						$rowTarget = $queryTarget->fetch(PDO::FETCH_ASSOC);
						if(isset($rowTarget[$rowQuery["target_field"]])){
							$arrGroupCheckSend = array();
							$arrGroupMessage = array();
							$arrTarget = array();
							foreach($arrColumn as $column){
								$arrTarget[$column] = $rowTarget[strtoupper($column)] ?? null;
							}
							$arrMessage = $lib->mergeTemplate(null,$dataComing["message_emoji_"],$arrTarget);
							$arrayTel = $func->getSMSPerson('person',array($destination));
							if(isset($arrayTel[0]["TEL"]) && $arrayTel[0]["TEL"] != ""){
								$arrayDest["cmd_sms"] = "CMD=".$config["CMD_SMS"]."&FROM=".$config["FROM_SERVICES_SMS"]."&TO=66".(substr($arrayTel[0]["TEL"],1,9))."&REPORT=Y&CHARGE=".$config["CHARGE_SMS"]."&CODE=".$config["CODE_SMS"]."&CTYPE=UNICODE&CONTENT=".$lib->unicodeMessageEncode($arrMessage["BODY"]);
								$arraySendSMS = $lib->sendSMS($arrayDest);
								if($arraySendSMS["RESULT"]){
									$arrayMerge[] = $arrayTel[0];
									$arrGRPAll[$arrayTel[0]["MEMBER_NO"]] = $arrMessage["BODY"];
								}else{
									$bulkInsert[] = "('".$arrMessage["BODY"]."','".$arrayTel[0]["MEMBER_NO"]."',
											'sms','".$arrayTel[0]["TEL"]."',null,'".$arraySendSMS["MESSAGE"]."','".$payload["username"]."','".$id_template."')";
									if(sizeof($bulkInsert) == 1000){
										$func->logSMSWasNotSent($bulkInsert);
										unset($bulkInsert);
										$bulkInsert = array();
									}
								}
							}else{
								$bulkInsert[] = "('".$arrMessage["BODY"]."','".$arrayTel[0]["MEMBER_NO"]."',
								'sms',null,null,'ไม่พบเบอร์โทรศัพท์','".$payload["username"]."','".$id_template."')";
								if(sizeof($bulkInsert) == 1000){
									$func->logSMSWasNotSent($bulkInsert);
									unset($bulkInsert);
									$bulkInsert = array();
								}
							}
						}
					}
					if(sizeof($bulkInsert) > 0){
						$func->logSMSWasNotSent($bulkInsert);
						unset($bulkInsert);
						$bulkInsert = array();
					}
					if(sizeof($arrGRPAll) > 0){
						$arrayLogSMS = $func->logSMSWasSent($id_template,$arrGRPAll,$arrayMerge,$payload["username"],true);
						$arrayResult['RESULT'] = $arrayLogSMS;
					}else{
						$arrayResult['RESULT'] = TRUE;
					}
					echo json_encode($arrayResult);
				}
			}else{
				$arrayResult['RESPONSE'] = "ไม่พบชุดคิวรี่ข้อมูล กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
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