<?php
require_once('../autoloadConnection.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/function_util.php');

use Utility\Library;
use Component\functions;

$lib = new library();
$func = new functions();

$jsonConfig = file_get_contents(__DIR__.'/../config/config_constructor.json');
$config = json_decode($jsonConfig,true);

$dateNow = date("YmdHi");

$getNotifyWaitforSend = $conmysql->prepare("SELECT is_import,create_by,id_sendahead,send_topic,send_message,destination,id_smsquery,id_smstemplate,send_platform,send_image
										FROM smssendahead WHERE is_use = '1' and :datenow >= DATE_FORMAT(send_date,'%Y%m%d%H%i')");
$getNotifyWaitforSend->execute([':datenow' => $dateNow]);
while($rowNoti = $getNotifyWaitforSend->fetch(PDO::FETCH_ASSOC)){
	if($rowNoti["send_platform"] == '1'){
		if($rowNoti["destination"] != 'all'){
			$arrGRPAll = array();
			$arrayMerge = array();
			$bulkInsert = array();
			if(isset($rowNoti["id_smsquery"])){
				$getQuery = $conmysql->prepare("SELECT sms_query,column_selected,is_bind_param,target_field,condition_target FROM smsquery WHERE id_smsquery = :id_query");
				$getQuery->execute([':id_query' => $rowNoti["id_smsquery"]]);
				if($getQuery->rowCount() > 0){
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
							$arrMessage = $lib->mergeTemplate(null,$rowNoti["send_message"],$arrTarget);
							$arrayTel = $func->getSMSPerson('person',array($rowTarget[$rowQuery["target_field"]]));
							if(isset($arrayTel[0]["TEL"]) && $arrayTel[0]["TEL"] != ""){
								$arrayDest["cmd_sms"] = "CMD=".$config["CMD_SMS"]."&FROM=".$config["FROM_SERVICES_SMS"]."&TO=66".(substr($arrayTel[0]["TEL"],1,9))."&REPORT=Y&CHARGE=".$config["CHARGE_SMS"]."&CODE=".$config["CODE_SMS"]."&CTYPE=UNICODE&CONTENT=".$lib->unicodeMessageEncode($arrMessage["BODY"]);
								$arraySendSMS = $lib->sendSMS($arrayDest);
								if($arraySendSMS["RESULT"]){
									$arrayMerge[] = $arrayTel[0];
									$arrGRPAll[$arrayTel[0]["MEMBER_NO"]] = $arrMessage["BODY"];
								}else{
									$bulkInsert[] = "('".$arrMessage["BODY"]."','".$arrayTel[0]["MEMBER_NO"]."',
											'sms','".$arrayTel[0]["TEL"]."',null,'".$arraySendSMS["MESSAGE"]."','".$rowNoti["create_by"]."'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").")";
									if(sizeof($bulkInsert) == 1000){
										$func->logSMSWasNotSent($bulkInsert);
										unset($bulkInsert);
										$bulkInsert = array();
									}
								}
							}else{
								$bulkInsert[] = "('".$arrMessage["BODY"]."','".$arrayTel[0]["MEMBER_NO"]."',
								'sms',null,null,'ไม่พบเบอร์โทรศัพท์','".$rowNoti["create_by"]."'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").")";
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
							$updateFlagSent = $conmysql->prepare("UPDATE smssendahead SET is_use = '-9' WHERE id_sendahead = :id_sendahead");
							$updateFlagSent->execute([':id_sendahead' => $rowNoti["id_sendahead"]]);
							$arrayLogSMS = $func->logSMSWasSent($rowNoti["id_smstemplate"],$arrGRPAll,$arrayMerge,$rowNoti["create_by"],true);
						}
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
						$condition = explode(':',$rowQuery["condition_target"]);
						$arrDest = explode(',',$rowNoti["destination"]);
						foreach($arrDest as $target){
							if($condition[1] == $rowQuery["target_field"]){
								if(strlen($target) <= 8){
									$destination = strtolower($lib->mb_str_pad($target));
								}else{
									$destination = $target;
								}
							}else{
								$destination = $target;
							}
							$queryTarget = $conoracle->prepare($query);
							$queryTarget->execute([':'.$condition[1] => $destination]);
							$rowTarget = $queryTarget->fetch(PDO::FETCH_ASSOC);
							if(isset($rowTarget[$rowQuery["target_field"]])){
								$arrGroupCheckSend = array();
								$arrGroupMessage = array();
								$arrTarget = array();
								foreach($arrColumn as $column){
									$arrTarget[$column] = $rowTarget[strtoupper($column)] ?? null;
								}
								$arrMessage = $lib->mergeTemplate(null,$rowNoti["send_message"],$arrTarget);
								if($condition[1] == $rowQuery["target_field"]){
									$arrayTel = $func->getSMSPerson('person',array($destination));
								}else{
									$arrayTel = $func->getSMSPerson('person',array($rowTarget[$rowQuery["target_field"]]));
								}
								if(isset($arrayTel[0]["TEL"]) && $arrayTel[0]["TEL"] != ""){
									$arrayDest["cmd_sms"] = "CMD=".$config["CMD_SMS"]."&FROM=".$config["FROM_SERVICES_SMS"]."&TO=66".(substr($arrayTel[0]["TEL"],1,9))."&REPORT=Y&CHARGE=".$config["CHARGE_SMS"]."&CODE=".$config["CODE_SMS"]."&CTYPE=UNICODE&CONTENT=".$lib->unicodeMessageEncode($arrMessage["BODY"]);
									$arraySendSMS = $lib->sendSMS($arrayDest);
									if($arraySendSMS["RESULT"]){
										$arrayMerge[] = $arrayTel[0];
										$arrGRPAll[$arrayTel[0]["MEMBER_NO"]] = $arrMessage["BODY"];
									}else{
										$bulkInsert[] = "('".$arrMessage["BODY"]."','".$arrayTel[0]["MEMBER_NO"]."',
												'sms','".$arrayTel[0]["TEL"]."',null,'".$arraySendSMS["MESSAGE"]."','".$rowNoti["create_by"]."'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").")";
										if(sizeof($bulkInsert) == 1000){
											$func->logSMSWasNotSent($bulkInsert);
											unset($bulkInsert);
											$bulkInsert = array();
										}
									}
								}else{
									$bulkInsert[] = "('".$arrMessage["BODY"]."','".$arrayTel[0]["MEMBER_NO"]."',
									'sms',null,null,'ไม่พบเบอร์โทรศัพท์','".$rowNoti["create_by"]."'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").")";
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
							$updateFlagSent = $conmysql->prepare("UPDATE smssendahead SET is_use = '-9' WHERE id_sendahead = :id_sendahead");
							$updateFlagSent->execute([':id_sendahead' => $rowNoti["id_sendahead"]]);
							$arrayLogSMS = $func->logSMSWasSent($rowNoti["id_smstemplate"],$arrGRPAll,$arrayMerge,$rowNoti["create_by"],true);
						}
					}
				}
			}else{
				$arrDestGRP = array();
				$destination = array();
				if($rowNoti["is_import"] == '1'){
					$destinationJson = json_decode($rowNoti["destination"],true);
					foreach($destinationJson as $key => $message_body){
						$arrayTel = array();
						$destination_temp = array();
						if(mb_strlen($key) <= 8){
							$destination[] = strtolower($lib->mb_str_pad($key));
						}else if(mb_strlen($key) == 10){
							$destination_temp["MEMBER_NO"] = null;
							$destination_temp["TEL"] = $key;
							$arrDestGRP[] = $destination_temp;
						}
						$arrayTel = $func->getSMSPerson('person',$destination,false,true);
						if(isset($arrDestGRP)){
							$arrayMerge = array_merge($arrayTel,$arrDestGRP);
						}else{
							$arrayMerge = $arrayTel;
						}
						foreach($arrayMerge as $dest){
							$arrGroupCheckSend = array();
							if(isset($dest["TEL"]) && $dest["TEL"] != ""){
								$arrayDest["cmd_sms"] = "CMD=".$config["CMD_SMS"]."&FROM=".$config["FROM_SERVICES_SMS"]."&TO=66".(substr($dest["TEL"],1,9))."&REPORT=Y&CHARGE=".$config["CHARGE_SMS"]."&CODE=".$config["CODE_SMS"]."&CTYPE=UNICODE&CONTENT=".$lib->unicodeMessageEncode($message_body);
								$arraySendSMS = $lib->sendSMS($arrayDest);
								if($arraySendSMS["RESULT"]){
									$arrGRPAll[$dest["MEMBER_NO"]] = $message_body;
								}else{
									$bulkInsert[] = "('".$message_body."','".$dest["MEMBER_NO"]."',
											'sms','".$dest["TEL"]."',null,'".$arraySendSMS["MESSAGE"]."','".$rowNoti["create_by"]."'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").")";
									if(sizeof($bulkInsert) == 1000){
										$func->logSMSWasNotSent($bulkInsert);
										unset($bulkInsert);
									}
								}
							}
						}
					}
				}else{
					$arrDest = explode(',',$rowNoti["destination"]);
					foreach($arrDest as $dest){
						$destination_temp = array();
						if(mb_strlen($dest) <= 8){
							$destination[] = strtolower($lib->mb_str_pad($dest));
						}else if(mb_strlen($dest) == 10){
							$destination_temp["MEMBER_NO"] = null;
							$destination_temp["TEL"] = $dest;
							$arrDestGRP[] = $destination_temp;
						}
					}
					$arrayTel = $func->getSMSPerson('person',$destination,false,true);
					if(isset($arrDestGRP)){
						$arrayMerge = array_merge($arrayTel,$arrDestGRP);
					}else{
						$arrayMerge = $arrayTel;
					}
					foreach($arrayMerge as $dest){
						$arrGroupCheckSend = array();
						if(isset($dest["TEL"]) && $dest["TEL"] != ""){
							$message_body = $rowNoti["send_message"];
							$arrayDest["cmd_sms"] = "CMD=".$config["CMD_SMS"]."&FROM=".$config["FROM_SERVICES_SMS"]."&TO=66".(substr($dest["TEL"],1,9))."&REPORT=Y&CHARGE=".$config["CHARGE_SMS"]."&CODE=".$config["CODE_SMS"]."&CTYPE=UNICODE&CONTENT=".$lib->unicodeMessageEncode($message_body);
							$arraySendSMS = $lib->sendSMS($arrayDest);
							if($arraySendSMS["RESULT"]){
								$arrGRPAll[$dest["MEMBER_NO"]] = $rowNoti["send_message"];
							}else{
								$bulkInsert[] = "('".$message_body."','".$dest["MEMBER_NO"]."',
										'sms','".$dest["TEL"]."',null,'".$arraySendSMS["MESSAGE"]."','".$rowNoti["create_by"]."'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").")";
								if(sizeof($bulkInsert) == 1000){
									$func->logSMSWasNotSent($bulkInsert);
									unset($bulkInsert);
								}
							}
						}
					}
				}
				if(sizeof($arrGRPAll) > 0){
					$updateFlagSent = $conmysql->prepare("UPDATE smssendahead SET is_use = '-9' WHERE id_sendahead = :id_sendahead");
					$updateFlagSent->execute([':id_sendahead' => $rowNoti["id_sendahead"]]);
					$func->logSMSWasSent($rowNoti["id_smstemplate"],$arrGRPAll,$arrayMerge,$rowNoti["create_by"],true);
				}
				if(sizeof($bulkInsert) > 0){
					$func->logSMSWasNotSent($bulkInsert);
					unset($bulkInsert);
					$bulkInsert = array();
				}
			}
		}
	}else{
		if($rowNoti["destination"] == 'all'){
			$bulkInsert = array();
			$arrToken = $func->getFCMToken('all');
			$arrAllToken = array();
			$arrAllMember_no = array();
			foreach($arrToken["LIST_SEND"] as $dest){
				if(isset($dest["TOKEN"]) && $dest["TOKEN"] != ""){
					if($dest["RECEIVE_NOTIFY_NEWS"] == "1"){
						$arrAllMember_no[] = $dest["MEMBER_NO"];
						$arrAllToken[] = $dest["TOKEN"];
					}else{
						$bulkInsert[] = "('".$rowNoti["send_message"]."','".$dest["MEMBER_NO"]."',
						'mobile_app',null,'".$dest["TOKEN"]."','บัญชีปลายทางไม่ประสงค์เปิดรับการแจ้งเตือน','".$rowNoti["create_by"]."'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").")";
					}
					if(sizeof($bulkInsert) == 1000){
						$func->logSMSWasNotSent($bulkInsert);
						unset($bulkInsert);
						$bulkInsert = array();
					}
				}else{
					$bulkInsert[] = "('".$rowNoti["send_message"]."','".$dest["MEMBER_NO"]."',
					'mobile_app',null,null,'หา Token ในการส่งไม่เจออาจจะเพราะไม่อนุญาตให้ส่งแจ้งเตือนเข้าเครื่อง','".$rowNoti["create_by"]."'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").")";
					if(sizeof($bulkInsert) == 1000){
						$func->logSMSWasNotSent($bulkInsert);
						unset($bulkInsert);
						$bulkInsert = array();
					}
				}
			}
			if(sizeof($arrAllToken) > 0){
				if(sizeof($bulkInsert) > 0){
					$func->logSMSWasNotSent($bulkInsert);
					unset($bulkInsert);
					$bulkInsert = array();
				}
				$arrPayloadNotify["TO"] = '/topics/member';
				$arrPayloadNotify["MEMBER_NO"] = $arrAllMember_no;
				$arrMessage["SUBJECT"] = $rowNoti["send_topic"];
				$arrMessage["BODY"] = $rowNoti["send_message"];
				$arrMessage["PATH_IMAGE"] = $rowNoti["send_image"] ?? null;
				$arrPayloadNotify["PAYLOAD"] = $arrMessage;
				$arrPayloadNotify["TYPE_SEND_HISTORY"] = "onemessage";
				if($func->insertHistory($arrPayloadNotify,'1')){
					if($lib->sendNotify($arrPayloadNotify,'all')){
						$updateFlagSent = $conmysql->prepare("UPDATE smssendahead SET is_use = '-9' WHERE id_sendahead = :id_sendahead");
						$updateFlagSent->execute([':id_sendahead' => $rowNoti["id_sendahead"]]);
					}
				}
			}else{
				if(sizeof($bulkInsert) > 0){
					$func->logSMSWasNotSent($bulkInsert);
					unset($bulkInsert);
					$bulkInsert = array();
				}
			}
		}else{
			if(isset($rowNoti["id_smsquery"])){
				$getQuery = $conmysql->prepare("SELECT sms_query,column_selected,is_bind_param,target_field,condition_target FROM smsquery WHERE id_smsquery = :id_query");
				$getQuery->execute([':id_query' => $rowNoti["id_smsquery"]]);
				if($getQuery->rowCount() > 0){
					$blukInsert = array();
					$blukInsertNot = array();
					$rowQuery = $getQuery->fetch(PDO::FETCH_ASSOC);
					$arrColumn = explode(',',$rowQuery["column_selected"]);
					if($rowQuery["is_bind_param"] == '0'){
						$sendPass = false;
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
							$arrMessageMerge = $lib->mergeTemplate($rowNoti["send_topic"],$rowNoti["send_message"],$arrTarget);
							$arrToken = $func->getFCMToken('person',array($rowTarget[$rowQuery["target_field"]]));
							if(isset($arrToken["LIST_SEND"][0]["TOKEN"]) && $arrToken["LIST_SEND"][0]["TOKEN"] != ""){
								if($arrToken["LIST_SEND"][0]["RECEIVE_NOTIFY_NEWS"] == "1"){
									$arrPayloadNotify["TO"] = array($arrToken["LIST_SEND"][0]["TOKEN"]);
									$arrPayloadNotify["MEMBER_NO"] = $arrToken["LIST_SEND"][0]["MEMBER_NO"];
									$arrMessage["SUBJECT"] = $arrMessageMerge["SUBJECT"];
									$arrMessage["BODY"] = $arrMessageMerge["BODY"];
									$arrMessage["PATH_IMAGE"] = $rowNoti["send_image"] ?? null;
									$arrPayloadNotify["PAYLOAD"] = $arrMessage;
									if($lib->sendNotify($arrPayloadNotify,'person')){
										$sendPass = true;
										$blukInsert[] = "('1','".$arrMessageMerge["SUBJECT"]."','".$arrMessageMerge["BODY"]."','".($rowNoti["send_image"] ?? null)."','".$arrToken["LIST_SEND"][0]["MEMBER_NO"]."')";
										if(sizeof($blukInsert) == 1000){
											$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
											$arrPayloadHistory["bulkInsert"] = $blukInsert;
											$func->insertHistory($arrPayloadHistory);
											unset($blukInsert);
											$blukInsert = array();
										}
									}else{
										$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$arrToken["LIST_SEND"][0]["MEMBER_NO"]."','mobile_app',null,'".$arrToken["LIST_SEND"][0]["TOKEN"]."','ไม่สามารถส่งได้ให้ดู LOG','".$rowNoti["create_by"]."'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").")";
										if(sizeof($blukInsertNot) == 1000){
											$func->logSMSWasNotSent($blukInsertNot);
											unset($blukInsertNot);
											$blukInsertNot = array();
										}
									}
								}else{
									$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$arrToken["LIST_SEND"][0]["MEMBER_NO"]."','mobile_app',null,'".$arrToken["LIST_SEND"][0]["TOKEN"]."','บัญชีปลายทางไม่ประสงค์เปิดรับการแจ้งเตือน','".$rowNoti["create_by"]."'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").")";
									if(sizeof($blukInsertNot) == 1000){
										$func->logSMSWasNotSent($blukInsertNot);
										unset($blukInsertNot);
										$blukInsertNot = array();
									}
								}
							}else{
								$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$rowTarget[$rowQuery["target_field"]]."','mobile_app',null,null,'หา Token ในการส่งไม่เจออาจจะเพราะไม่อนุญาตให้ส่งแจ้งเตือนเข้าเครื่อง','".$rowNoti["create_by"]."'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").")";
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
						if($sendPass === true){
							$updateFlagSent = $conmysql->prepare("UPDATE smssendahead SET is_use = '-9' WHERE id_sendahead = :id_sendahead");
							$updateFlagSent->execute([':id_sendahead' => $rowNoti["id_sendahead"]]);
						}
					}else{
						$sendPass = false;
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
						$condition = explode(':',$rowQuery["condition_target"]);
						$arrDest = explode(',',$rowNoti["destination"]);
						foreach($arrDest as $target){
							if($condition[1] == $rowQuery["target_field"]){
								if(strlen($target) <= 8){
									$target = strtolower($lib->mb_str_pad($target));
								}else{
									$target = $target;
								}
							}else{
								$target = $target;
							}
							$target = strtolower($lib->mb_str_pad($target));
							$queryTarget = $conoracle->prepare($query);
							$queryTarget->execute([':'.$condition[1] => $target]);
							$rowTarget = $queryTarget->fetch(PDO::FETCH_ASSOC);
							if(isset($rowTarget[$rowQuery["target_field"]])){
								$arrGroupMessage = array();
								$arrDestination = array();
								$arrMemberNoDestination = array();
								$arrTarget = array();
								foreach($arrColumn as $column){
									$arrTarget[$column] = $rowTarget[strtoupper($column)] ?? null;
								}
								$arrMessageMerge = $lib->mergeTemplate($rowNoti["send_topic"],$rowNoti["send_message"],$arrTarget);
								if($condition[1] == $rowQuery["target_field"]){
									$arrToken = $func->getFCMToken('person',array($target));
								}else{
									$arrToken = $func->getFCMToken('person',array($rowTarget[$rowQuery["target_field"]]));
								}
								if(sizeof($arrToken["MEMBER_NO"]) > 0){
									if(isset($arrToken["LIST_SEND"][0]["TOKEN"]) && $arrToken["LIST_SEND"][0]["TOKEN"] != ""){
										if($arrToken["LIST_SEND"][0]["RECEIVE_NOTIFY_NEWS"] == "1"){
											$arrPayloadNotify["TO"] = array($arrToken["LIST_SEND"][0]["TOKEN"]);
											$arrPayloadNotify["MEMBER_NO"] = $arrToken["LIST_SEND"][0]["MEMBER_NO"];
											$arrMessage["SUBJECT"] = $arrMessageMerge["SUBJECT"];
											$arrMessage["BODY"] = $arrMessageMerge["BODY"];
											$arrMessage["PATH_IMAGE"] = $rowNoti["send_image"] ?? null;
											$arrPayloadNotify["PAYLOAD"] = $arrMessage;
											if($lib->sendNotify($arrPayloadNotify,'person')){
												$sendPass = true;
												$blukInsert[] = "('1','".$arrMessageMerge["SUBJECT"]."','".$arrMessageMerge["BODY"]."','".($rowNoti["send_image"] ?? null)."','".$arrToken["LIST_SEND"][0]["MEMBER_NO"]."')";
												if(sizeof($blukInsert) == 1000){
													$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
													$arrPayloadHistory["bulkInsert"] = $blukInsert;
													$func->insertHistory($arrPayloadHistory);
													unset($blukInsert);
													$blukInsert = array();
												}
											}else{
												$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$arrToken["LIST_SEND"][0]["MEMBER_NO"]."','mobile_app',null,'".$arrToken["LIST_SEND"][0]["TOKEN"]."','ไม่สามารถส่งได้ให้ดู LOG','".$rowNoti["create_by"]."'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").")";
												if(sizeof($blukInsertNot) == 1000){
													$func->logSMSWasNotSent($blukInsertNot);
													unset($blukInsertNot);
													$blukInsertNot = array();
												}
											}
										}else{
											$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$arrToken["LIST_SEND"][0]["MEMBER_NO"]."','mobile_app',null,'".$arrToken["LIST_SEND"][0]["TOKEN"]."','บัญชีปลายทางไม่ประสงค์เปิดรับการแจ้งเตือน','".$rowNoti["create_by"]."'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").")";
											if(sizeof($blukInsertNot) == 1000){
												$func->logSMSWasNotSent($blukInsertNot);
												unset($blukInsertNot);
												$blukInsertNot = array();
											}
										}
									}else{
										$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$target."','mobile_app',null,null,'หา Token ในการส่งไม่เจออาจจะเพราะไม่อนุญาตให้ส่งแจ้งเตือนเข้าเครื่อง','".$rowNoti["create_by"]."'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").")";
										if(sizeof($blukInsertNot) == 1000){
											$func->logSMSWasNotSent($blukInsertNot);
											unset($blukInsertNot);
											$blukInsertNot = array();
										}
									}
								}else{
									$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$target."','mobile_app',null,null,'สมาชิกยังไม่ได้ใช้งานแอปพลิเคชั่น','".$rowNoti["create_by"]."'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").")";
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
						if($sendPass === true){
							$updateFlagSent = $conmysql->prepare("UPDATE smssendahead SET is_use = '-9' WHERE id_sendahead = :id_sendahead");
							$updateFlagSent->execute([':id_sendahead' => $rowNoti["id_sendahead"]]);
						}
					}
				}
			}else{
				$sendPass = false;
				$blukInsert = array();
				$blukInsertNot = array();
				if($rowNoti["is_import"] == '1'){
					$destinationJson = json_decode($rowNoti["destination"],true);
					foreach($destinationJson as $key => $message_body){
						$destination = array();
						$destination[] = strtolower($lib->mb_str_pad($key));
						$arrToken = $func->getFCMToken('person',$destination);
						foreach($arrToken["LIST_SEND"] as $dest){
							if(isset($dest["TOKEN"]) && $dest["TOKEN"] != ""){
								$arrPayloadNotify["TO"] = array($dest["TOKEN"]);
								$arrPayloadNotify["MEMBER_NO"] = $dest["MEMBER_NO"];
								$arrMessage["SUBJECT"] = $rowNoti["send_topic"];
								$message = $message_body;
								$arrMessage["BODY"] = $message;
								$arrMessage["PATH_IMAGE"] = $rowNoti["send_image"] ?? null;
								$arrPayloadNotify["PAYLOAD"] = $arrMessage;
								if($lib->sendNotify($arrPayloadNotify,'person')){
									$sendPass = true;
									$blukInsert[] = "('1','".$rowNoti["send_topic"]."','".$message."','".($rowNoti["send_image"] ?? null)."','".$dest["MEMBER_NO"]."')";
									if(sizeof($blukInsert) == 1000){
										$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
										$arrPayloadHistory["bulkInsert"] = $blukInsert;
										$func->insertHistory($arrPayloadHistory);
										unset($blukInsert);
										$blukInsert = array();
									}
								}else{
									$blukInsertNot[] = "('".$message."','".$dest["MEMBER_NO"]."','mobile_app',null,'".$dest["TOKEN"]."','ไม่สามารถส่งได้ให้ดู LOG','".$rowNoti["create_by"]."'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").")";
									if(sizeof($blukInsertNot) == 1000){
										$func->logSMSWasNotSent($blukInsertNot);
										unset($blukInsertNot);
										$blukInsertNot = array();
									}
								}
							}
						}
					}
				}else{
					$destination = array();
					$arrDest = explode(',',$rowNoti["destination"]);
					foreach($arrDest as $target){
						$destination[] = strtolower($lib->mb_str_pad($target));
					}
					$arrToken = $func->getFCMToken('person',$destination);
					foreach($arrToken["LIST_SEND"] as $dest){
						if(isset($dest["TOKEN"]) && $dest["TOKEN"] != ""){
							$arrPayloadNotify["TO"] = array($dest["TOKEN"]);
							$arrPayloadNotify["MEMBER_NO"] = $dest["MEMBER_NO"];
							$arrMessage["SUBJECT"] = $rowNoti["send_topic"];
							$message = $rowNoti["send_message"];
							$arrMessage["BODY"] = $message;
							$arrMessage["PATH_IMAGE"] = $rowNoti["send_image"] ?? null;
							$arrPayloadNotify["PAYLOAD"] = $arrMessage;
							if($lib->sendNotify($arrPayloadNotify,'person')){
								$sendPass = true;
								$blukInsert[] = "('1','".$rowNoti["send_topic"]."','".$message."','".($rowNoti["send_image"] ?? null)."','".$dest["MEMBER_NO"]."')";
								if(sizeof($blukInsert) == 1000){
									$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
									$arrPayloadHistory["bulkInsert"] = $blukInsert;
									$func->insertHistory($arrPayloadHistory);
									unset($blukInsert);
									$blukInsert = array();
								}
							}else{
								$blukInsertNot[] = "('".$message."','".$dest["MEMBER_NO"]."','mobile_app',null,'".$dest["TOKEN"]."','ไม่สามารถส่งได้ให้ดู LOG','".$rowNoti["create_by"]."'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").")";
								if(sizeof($blukInsertNot) == 1000){
									$func->logSMSWasNotSent($blukInsertNot);
									unset($blukInsertNot);
									$blukInsertNot = array();
								}
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
					$func->logSMSWasNotSent($blukInsertNot);
					unset($blukInsertNot);
					$blukInsertNot = array();
				}
				if($sendPass === true){
					$updateFlagSent = $conmysql->prepare("UPDATE smssendahead SET is_use = '-9' WHERE id_sendahead = :id_sendahead");
					$updateFlagSent->execute([':id_sendahead' => $rowNoti["id_sendahead"]]);
				}
			}
		}
	}
}
?>