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

$getNotifyWaitforSend = $conmysql->prepare("SELECT is_import,create_by,id_sendahead,send_topic,send_message,destination,CASE destination_revoke WHEN '' THEN NULL ELSE destination_revoke END as destination_revoke
										,id_smsquery,id_smstemplate,send_platform,send_image
										FROM smssendahead WHERE is_use = '1' and :datenow >= DATE_FORMAT(send_date,'%Y%m%d%H%i')");
$getNotifyWaitforSend->execute([':datenow' => $dateNow]);
while($rowNoti = $getNotifyWaitforSend->fetch(PDO::FETCH_ASSOC)){
	$pathImg = isset($rowNoti["send_image"]) && $rowNoti["send_image"] != "" ? $config["URL_SERVICE"].$rowNoti["send_image"] : null;
	if($rowNoti["send_platform"] == '2'){
		if($rowNoti["is_import"] == '1'){
			$blukInsert = array();
			$blukInsertNot = array();
			$destination = array();
			$destinationFull = array();
			$message_importData = json_decode($rowNoti["destination"]);
			if(isset($rowNoti["destination_revoke"]) && $rowNoti["destination_revoke"] != ""){
				foreach($message_importData as $key => $target){
					if(!in_array($key,explode(',',$rowNoti["destination_revoke"]))){
						$destination[] = strtolower($lib->mb_str_pad($target->DESTINATION));
						$destinationFull[] = $target;
					}
				}
			}else{
				foreach($message_importData as $key => $target){
					$destination[] = strtolower($lib->mb_str_pad($target->DESTINATION));
					$destinationFull[] = $target;
				}
			}
			$arrToken = $func->getFCMToken('person',$destination);
			foreach($destinationFull as $dest){
				$indexFound = array_search($dest->DESTINATION, $arrToken["MEMBER_NO"]);
				if($indexFound !== false){
					if(isset($arrToken["LIST_SEND"][$indexFound]["MEMBER_NO"]) && $arrToken["LIST_SEND"][$indexFound]["MEMBER_NO"] != ""){
						$member_no = $arrToken["LIST_SEND"][$indexFound]["MEMBER_NO"];
						$token = $arrToken["LIST_SEND"][$indexFound]["TOKEN"];
						$recv_noti_news = $arrToken["LIST_SEND"][$indexFound]["RECEIVE_NOTIFY_NEWS"] ?? null;
						$recv_noti_trans = $arrToken["LIST_SEND"][$indexFound]["RECEIVE_NOTIFY_TRANSACTION"] ?? null;
						if(isset($token) && $token != ""){
							if($recv_noti_news == "1"){
								$arrPayloadNotify["TO"] = array($token);
								$arrPayloadNotify["MEMBER_NO"] = $member_no;
								$arrMessage["SUBJECT"] = $rowNoti["send_topic"];
								$arrMessage["BODY"] = $dest->MESSAGE ?? "-";
								$arrMessage["PATH_IMAGE"] = $pathImg ?? null;
								$arrPayloadNotify["PAYLOAD"] = $arrMessage;
								if($lib->sendNotify($arrPayloadNotify,'person')){
									$blukInsert[] = "('1','".$rowNoti["send_topic"]."','".$dest->MESSAGE."','".($pathImg ?? null)."','".$member_no."','".$rowNoti["create_by"]."'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").",'1')";
									if(sizeof($blukInsert) == 1000){
										$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
										$arrPayloadHistory["bulkInsert"] = $blukInsert;
										$func->insertHistory($arrPayloadHistory,'1','1');
										unset($blukInsert);
										$blukInsert = array();
									}
								}else{
									$blukInsertNot[] = "('".$dest->MESSAGE."','".$member_no."','mobile_app',null,'".$token."','ไม่สามารถส่งได้ให้ดู LOG','system'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").",'1')";
									if(sizeof($blukInsertNot) == 1000){
										$func->logSMSWasNotSent($blukInsertNot,false,'1');
										unset($blukInsertNot);
										$blukInsertNot = array();
									}
								}
							}
						}
					}else{
						$member_no = $arrToken["LIST_SEND_HW"][$indexFound]["MEMBER_NO"];
						$token = $arrToken["LIST_SEND_HW"][$indexFound]["TOKEN"];
						$recv_noti_news = $arrToken["LIST_SEND_HW"][$indexFound]["RECEIVE_NOTIFY_NEWS"] ?? null;
						$recv_noti_trans = $arrToken["LIST_SEND_HW"][$indexFound]["RECEIVE_NOTIFY_TRANSACTION"] ?? null;
						if(isset($token) && $token != ""){
							if($recv_noti_news == "1"){
								$arrPayloadNotify["TO"] = array($token);
								$arrPayloadNotify["MEMBER_NO"] = $member_no;
								$arrMessage["SUBJECT"] = $rowNoti["send_topic"];
								$arrMessage["BODY"] = $dest->MESSAGE ?? "-";
								$arrMessage["PATH_IMAGE"] = $pathImg ?? null;
								$arrPayloadNotify["PAYLOAD"] = $arrMessage;
								if($lib->sendNotifyHW($arrPayloadNotify,'person')){
									$blukInsert[] = "('1','".$rowNoti["send_topic"]."','".$dest->MESSAGE."','".($pathImg ?? null)."','".$member_no."','".$rowNoti["create_by"]."'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").",'1')";
									if(sizeof($blukInsert) == 1000){
										$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
										$arrPayloadHistory["bulkInsert"] = $blukInsert;
										$func->insertHistory($arrPayloadHistory,'1','1');
										unset($blukInsert);
										$blukInsert = array();
									}
								}else{
									$blukInsertNot[] = "('".$dest->MESSAGE."','".$member_no."','mobile_app',null,'".$token."','ไม่สามารถส่งได้ให้ดู LOG','system'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").",'1')";
									if(sizeof($blukInsertNot) == 1000){
										$func->logSMSWasNotSent($blukInsertNot,false,'1');
										unset($blukInsertNot);
										$blukInsertNot = array();
									}
								}
							}
						}
					}
				}
			}
			if(sizeof($blukInsert) > 0){
				$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
				$arrPayloadHistory["bulkInsert"] = $blukInsert;
				$func->insertHistory($arrPayloadHistory,'1','1');
				unset($blukInsert);
				$blukInsert = array();
			}
			if(sizeof($blukInsertNot) > 0){
				$func->logSMSWasNotSent($blukInsertNot,false,'1');
				unset($blukInsertNot);
				$blukInsertNot = array();
			}
		}else{
			if(isset($rowNoti["id_smsquery"])){
				if($rowNoti["destination"] != 'all'){
					$getQuery = $conmysql->prepare("SELECT sms_query,column_selected,is_bind_param,target_field,condition_target,is_stampflag,stamp_table,where_stamp,set_column
													FROM smsquery WHERE id_smsquery = :id_query");
					$getQuery->execute([':id_query' => $rowNoti["id_smsquery"]]);
					if($getQuery->rowCount() > 0){
						$blukInsert = array();
						$blukInsertNot = array();
						$rowQuery = $getQuery->fetch(PDO::FETCH_ASSOC);
						$arrColumn = explode(',',$rowQuery["column_selected"]);
						if($rowQuery["is_bind_param"] == '0'){
							$queryTarget = $conmssql->prepare($rowQuery['sms_query']);
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
								if(!in_array($rowTarget[$rowQuery["target_field"]]."_".$arrMessageMerge["BODY"],json_decode($rowNoti["destination_revoke"]))){
									$arrToken = $func->getFCMToken('person',$rowTarget[$rowQuery["target_field"]]);
									if(isset($arrToken["LIST_SEND"][0]["TOKEN"]) && $arrToken["LIST_SEND"][0]["TOKEN"] != ""){
										if($arrToken["LIST_SEND"][0]["RECEIVE_NOTIFY_TRANSACTION"] == "1"){
											$arrPayloadNotify["TO"] = array($arrToken["LIST_SEND"][0]["TOKEN"]);
											$arrPayloadNotify["MEMBER_NO"] = $arrToken["LIST_SEND"][0]["MEMBER_NO"];
											$arrMessage["SUBJECT"] = $arrMessageMerge["SUBJECT"];
											$arrMessage["BODY"] = $arrMessageMerge["BODY"];
											$arrMessage["PATH_IMAGE"] = $pathImg ?? null;
											$arrPayloadNotify["PAYLOAD"] = $arrMessage;
											$arrPayloadNotify["TYPE_NOTIFY"] = "2";
											if($lib->sendNotify($arrPayloadNotify,'person')){
												if($rowQuery["is_stampflag"] == '1'){
													$arrayExecute = array();
													preg_match_all('/\\:(.*?)\\s/',$rowQuery["where_stamp"],$arrayRawExecute);
													foreach($arrayRawExecute[1] as $execute){
														$arrayExecute[$execute] = $rowTarget[$execute];
													}
													$updateFlagStamp = $conmssql->prepare("UPDATE ".$rowQuery["stamp_table"]." SET ".$rowQuery["set_column"]." WHERE ".$rowQuery["where_stamp"]);
													$updateFlagStamp->execute($arrayExecute);
												}
												$blukInsert[] = "('1','".$arrMessageMerge["SUBJECT"]."','".$arrMessageMerge["BODY"]."','".($pathImg ?? null)."','".$arrToken["LIST_SEND"][0]["MEMBER_NO"]."','".$rowNoti["create_by"]."'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").",'1')";
												if(sizeof($blukInsert) == 1000){
													$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
													$arrPayloadHistory["bulkInsert"] = $blukInsert;
													$func->insertHistory($arrPayloadHistory,'2','1');
													unset($blukInsert);
													$blukInsert = array();
												}
											}else{
												$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$arrToken["LIST_SEND"][0]["MEMBER_NO"]."','mobile_app',null,'".$arrToken["LIST_SEND"][0]["TOKEN"]."','ไม่สามารถส่งได้ให้ดู LOG','system'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").",'1')";
												if(sizeof($blukInsertNot) == 1000){
													$func->logSMSWasNotSent($blukInsertNot,false,'1');
													unset($blukInsertNot);
													$blukInsertNot = array();
												}
											}
										}else{
											$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$arrToken["LIST_SEND"][0]["MEMBER_NO"]."','mobile_app',null,'".$arrToken["LIST_SEND"][0]["TOKEN"]."','บัญชีปลายทางไม่ประสงค์เปิดรับการแจ้งเตือน','system'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").",'1')";
											if(sizeof($blukInsertNot) == 1000){
												$func->logSMSWasNotSent($blukInsertNot,false,'1');
												unset($blukInsertNot);
												$blukInsertNot = array();
											}
										}
									}else{
										if(isset($arrToken["LIST_SEND_HW"][0]["TOKEN"]) && $arrToken["LIST_SEND_HW"][0]["TOKEN"] != ""){
											if($arrToken["LIST_SEND_HW"][0]["RECEIVE_NOTIFY_TRANSACTION"] == "1"){
												$arrPayloadNotify["TO"] = array($arrToken["LIST_SEND_HW"][0]["TOKEN"]);
												$arrPayloadNotify["MEMBER_NO"] = $arrToken["LIST_SEND_HW"][0]["MEMBER_NO"];
												$arrMessage["SUBJECT"] = $arrMessageMerge["SUBJECT"];
												$arrMessage["BODY"] = $arrMessageMerge["BODY"];
												$arrMessage["PATH_IMAGE"] = $pathImg ?? null;
												$arrPayloadNotify["PAYLOAD"] = $arrMessage;
												$arrPayloadNotify["TYPE_NOTIFY"] = "2";
												if($lib->sendNotifyHW($arrPayloadNotify,'person')){
													if($rowQuery["is_stampflag"] == '1'){
														$arrayExecute = array();
														preg_match_all('/\\:(.*?)\\s/',$rowQuery["where_stamp"],$arrayRawExecute);
														foreach($arrayRawExecute[1] as $execute){
															$arrayExecute[$execute] = $rowTarget[$execute];
														}
														$updateFlagStamp = $conmssql->prepare("UPDATE ".$rowQuery["stamp_table"]." SET ".$rowQuery["set_column"]." WHERE ".$rowQuery["where_stamp"]);
														$updateFlagStamp->execute($arrayExecute);
													}
													$blukInsert[] = "('1','".$arrMessageMerge["SUBJECT"]."','".$arrMessageMerge["BODY"]."','".($pathImg ?? null)."','".$arrToken["LIST_SEND_HW"][0]["MEMBER_NO"]."','".$rowNoti["create_by"]."'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").",'1')";
													if(sizeof($blukInsert) == 1000){
														$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
														$arrPayloadHistory["bulkInsert"] = $blukInsert;
														$func->insertHistory($arrPayloadHistory,'2','1');
														unset($blukInsert);
														$blukInsert = array();
													}
												}else{
													$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$arrToken["LIST_SEND_HW"][0]["MEMBER_NO"]."','mobile_app',null,'".$arrToken["LIST_SEND_HW"][0]["TOKEN"]."','ไม่สามารถส่งได้ให้ดู LOG','system'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").",'1')";
													if(sizeof($blukInsertNot) == 1000){
														$func->logSMSWasNotSent($blukInsertNot,false,'1');
														unset($blukInsertNot);
														$blukInsertNot = array();
													}
												}
											}else{
												$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$arrToken["LIST_SEND_HW"][0]["MEMBER_NO"]."','mobile_app',null,'".$arrToken["LIST_SEND_HW"][0]["TOKEN"]."','บัญชีปลายทางไม่ประสงค์เปิดรับการแจ้งเตือน','system'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").",'1')";
												if(sizeof($blukInsertNot) == 1000){
													$func->logSMSWasNotSent($blukInsertNot,false,'1');
													unset($blukInsertNot);
													$blukInsertNot = array();
												}
											}
										}else{
											$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$rowTarget[$rowQuery["target_field"]]."','mobile_app',null,null,'หา Token ในการส่งไม่เจออาจจะเพราะไม่อนุญาตให้ส่งแจ้งเตือนเข้าเครื่อง','system'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").",'1')";
											if(sizeof($blukInsertNot) == 1000){
												$func->logSMSWasNotSent($blukInsertNot,false,'1');
												unset($blukInsertNot);
												$blukInsertNot = array();
											}
										}
									}
								}
							}
							if(sizeof($blukInsertNot) > 0){
								$func->logSMSWasNotSent($blukInsertNot,false,'1');
								unset($blukInsertNot);
								$blukInsertNot = array();
							}
							if(sizeof($blukInsert) > 0){
								$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
								$arrPayloadHistory["bulkInsert"] = $blukInsert;
								$func->insertHistory($arrPayloadHistory,'2','1');
								unset($blukInsert);
								$blukInsert = array();
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
							$message_importData = explode(',',$rowNoti["destination"]);
							foreach($message_importData as $target){
								if($condition[1] == $rowQuery["target_field"]){
									if(strlen($target) <= 8){
										$target = strtolower($lib->mb_str_pad($target));
									}else{
										$target = $target;
									}
								}else{
									$target = $target;
								}
								$queryTarget = $conmssql->prepare($query);
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
									if(!in_array($target.'_'.$arrMessageMerge["BODY"],json_decode($rowNoti["destination_revoke"]))){
										if($condition[1] == $rowQuery["target_field"]){
											$arrToken = $func->getFCMToken('person',$target);
										}else{
											$arrToken = $func->getFCMToken('person',$rowTarget[$rowQuery["target_field"]]);
										}
										if(sizeof($arrToken["MEMBER_NO"]) > 0){
											if(isset($arrToken["LIST_SEND"][0]["TOKEN"]) && $arrToken["LIST_SEND"][0]["TOKEN"] != ""){
												if($arrToken["LIST_SEND"][0]["RECEIVE_NOTIFY_TRANSACTION"] == "1"){
													$arrPayloadNotify["TO"] = array($arrToken["LIST_SEND"][0]["TOKEN"]);
													$arrPayloadNotify["MEMBER_NO"] = $arrToken["LIST_SEND"][0]["MEMBER_NO"];
													$arrMessage["SUBJECT"] = $arrMessageMerge["SUBJECT"];
													$arrMessage["BODY"] = $arrMessageMerge["BODY"];
													$arrMessage["PATH_IMAGE"] = $pathImg ?? null;
													$arrPayloadNotify["PAYLOAD"] = $arrMessage;
													$arrPayloadNotify["TYPE_NOTIFY"] = "2";
													if($lib->sendNotify($arrPayloadNotify,'person')){
														if($rowQuery["is_stampflag"] == '1'){
															$arrayExecute = array();
															preg_match_all('/\\:(.*?)\\s/',$rowQuery["where_stamp"],$arrayRawExecute);
															foreach($arrayRawExecute[1] as $execute){
																$arrayExecute[$execute] = $rowTarget[$execute];
															}
															$updateFlagStamp = $conmssql->prepare("UPDATE ".$rowQuery["stamp_table"]." SET ".$rowQuery["set_column"]." WHERE ".$rowQuery["where_stamp"]);
															$updateFlagStamp->execute($arrayExecute);
														}
														$blukInsert[] = "('1','".$arrMessageMerge["SUBJECT"]."','".$arrMessageMerge["BODY"]."','".($pathImg ?? null)."','".$arrToken["LIST_SEND"][0]["MEMBER_NO"]."','".$rowNoti["create_by"]."'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").",'1')";
														if(sizeof($blukInsert) == 1000){
															$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
															$arrPayloadHistory["bulkInsert"] = $blukInsert;
															$func->insertHistory($arrPayloadHistory,'2','1');
															unset($blukInsert);
															$blukInsert = array();
														}
													}else{
														$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$arrToken["LIST_SEND"][0]["MEMBER_NO"]."','mobile_app',null,'".$arrToken["LIST_SEND"][0]["TOKEN"]."','ไม่สามารถส่งได้ให้ดู LOG','system'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").",'1')";
														if(sizeof($blukInsertNot) == 1000){
															$func->logSMSWasNotSent($blukInsertNot,false,'1');
															unset($blukInsertNot);
															$blukInsertNot = array();
														}
													}
												}else{
													$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$arrToken["LIST_SEND"][0]["MEMBER_NO"]."','mobile_app',null,'".$arrToken["LIST_SEND"][0]["TOKEN"]."','บัญชีปลายทางไม่ประสงค์เปิดรับการแจ้งเตือน','system'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").",'1')";
													if(sizeof($blukInsertNot) == 1000){
														$func->logSMSWasNotSent($blukInsertNot,false,'1');
														unset($blukInsertNot);
														$blukInsertNot = array();
													}
												}
											}else{
												if(isset($arrToken["LIST_SEND_HW"][0]["TOKEN"]) && $arrToken["LIST_SEND_HW"][0]["TOKEN"] != ""){
													if($arrToken["LIST_SEND_HW"][0]["RECEIVE_NOTIFY_TRANSACTION"] == "1"){
														$arrPayloadNotify["TO"] = array($arrToken["LIST_SEND_HW"][0]["TOKEN"]);
														$arrPayloadNotify["MEMBER_NO"] = $arrToken["LIST_SEND_HW"][0]["MEMBER_NO"];
														$arrMessage["SUBJECT"] = $arrMessageMerge["SUBJECT"];
														$arrMessage["BODY"] = $arrMessageMerge["BODY"];
														$arrMessage["PATH_IMAGE"] = $pathImg ?? null;
														$arrPayloadNotify["PAYLOAD"] = $arrMessage;
														$arrPayloadNotify["TYPE_NOTIFY"] = "2";
														if($lib->sendNotifyHW($arrPayloadNotify,'person')){
															if($rowQuery["is_stampflag"] == '1'){
																$arrayExecute = array();
																preg_match_all('/\\:(.*?)\\s/',$rowQuery["where_stamp"],$arrayRawExecute);
																foreach($arrayRawExecute[1] as $execute){
																	$arrayExecute[$execute] = $rowTarget[$execute];
																}
																$updateFlagStamp = $conmssql->prepare("UPDATE ".$rowQuery["stamp_table"]." SET ".$rowQuery["set_column"]." WHERE ".$rowQuery["where_stamp"]);
																$updateFlagStamp->execute($arrayExecute);
															}
															$blukInsert[] = "('1','".$arrMessageMerge["SUBJECT"]."','".$arrMessageMerge["BODY"]."','".($pathImg ?? null)."','".$arrToken["LIST_SEND_HW"][0]["MEMBER_NO"]."','".$rowNoti["create_by"]."'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").",'1')";
															if(sizeof($blukInsert) == 1000){
																$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
																$arrPayloadHistory["bulkInsert"] = $blukInsert;
																$func->insertHistory($arrPayloadHistory,'2','1');
																unset($blukInsert);
																$blukInsert = array();
															}
														}else{
															$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$arrToken["LIST_SEND_HW"][0]["MEMBER_NO"]."','mobile_app',null,'".$arrToken["LIST_SEND_HW"][0]["TOKEN"]."','ไม่สามารถส่งได้ให้ดู LOG','system'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").",'1')";
															if(sizeof($blukInsertNot) == 1000){
																$func->logSMSWasNotSent($blukInsertNot,false,'1');
																unset($blukInsertNot);
																$blukInsertNot = array();
															}
														}
													}else{
														$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$arrToken["LIST_SEND_HW"][0]["MEMBER_NO"]."','mobile_app',null,'".$arrToken["LIST_SEND_HW"][0]["TOKEN"]."','บัญชีปลายทางไม่ประสงค์เปิดรับการแจ้งเตือน','system'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").",'1')";
														if(sizeof($blukInsertNot) == 1000){
															$func->logSMSWasNotSent($blukInsertNot,false,'1');
															unset($blukInsertNot);
															$blukInsertNot = array();
														}
													}
												}else{
													$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$target."','mobile_app',null,null,'หา Token ในการส่งไม่เจออาจจะเพราะไม่อนุญาตให้ส่งแจ้งเตือนเข้าเครื่อง','system'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").",'1')";
													if(sizeof($blukInsertNot) == 1000){
														$func->logSMSWasNotSent($blukInsertNot,false,'1');
														unset($blukInsertNot);
														$blukInsertNot = array();
													}
												}
											}
										}else{
											$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$target."','mobile_app',null,null,'สมาชิกยังไม่ได้ใช้งานแอปพลิเคชั่น','system'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").",'1')";
											if(sizeof($blukInsertNot) == 1000){
												$func->logSMSWasNotSent($blukInsertNot,false,'1');
												unset($blukInsertNot);
												$blukInsertNot = array();
											}
										}
									}
								}
							}
							if(sizeof($blukInsertNot) > 0){
								$func->logSMSWasNotSent($blukInsertNot,false,'1');
								unset($blukInsertNot);
								$blukInsertNot = array();
							}
							if(sizeof($blukInsert) > 0){
								$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
								$arrPayloadHistory["bulkInsert"] = $blukInsert;
								$func->insertHistory($arrPayloadHistory,'2','1');
								unset($blukInsert);
								$blukInsert = array();
							}
						}
					}
				}else{
					$getQuery = $conmysql->prepare("SELECT sms_query,column_selected,is_bind_param,target_field,condition_target FROM smsquery WHERE id_smsquery = :id_query");
					$getQuery->execute([':id_query' => $rowNoti["id_smsquery"]]);
					if($getQuery->rowCount() > 0){
						$blukInsert = array();
						$blukInsertNot = array();
						$rowQuery = $getQuery->fetch(PDO::FETCH_ASSOC);
						$arrColumn = explode(',',$rowQuery["column_selected"]);
						if($rowQuery["is_bind_param"] == '0'){
							$queryTarget = $conmssql->prepare($rowQuery['sms_query']);
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
								if(!in_array($rowTarget[$rowQuery["target_field"]]."_".$arrMessageMerge["BODY"],json_decode($rowNoti["destination_revoke"]))){
									$arrToken = $func->getFCMToken('person',$rowTarget[$rowQuery["target_field"]]);
									if(isset($arrToken["LIST_SEND"][0]["TOKEN"]) && $arrToken["LIST_SEND"][0]["TOKEN"] != ""){
										if($arrToken["LIST_SEND"][0]["RECEIVE_NOTIFY_TRANSACTION"] == "1"){
											$arrPayloadNotify["TO"] = array($arrToken["LIST_SEND"][0]["TOKEN"]);
											$arrPayloadNotify["MEMBER_NO"] = $arrToken["LIST_SEND"][0]["MEMBER_NO"];
											$arrMessage["SUBJECT"] = $arrMessageMerge["SUBJECT"];
											$arrMessage["BODY"] = $arrMessageMerge["BODY"];
											$arrMessage["PATH_IMAGE"] = $pathImg ?? null;
											$arrPayloadNotify["PAYLOAD"] = $arrMessage;
											$arrPayloadNotify["TYPE_NOTIFY"] = "2";
											if($lib->sendNotify($arrPayloadNotify,'person')){
												if($rowQuery["is_stampflag"] == '1'){
													$arrayExecute = array();
													preg_match_all('/\\:(.*?)\\s/',$rowQuery["where_stamp"],$arrayRawExecute);
													foreach($arrayRawExecute[1] as $execute){
														$arrayExecute[$execute] = $rowTarget[$execute];
													}
													$updateFlagStamp = $conmssql->prepare("UPDATE ".$rowQuery["stamp_table"]." SET ".$rowQuery["set_column"]." WHERE ".$rowQuery["where_stamp"]);
													$updateFlagStamp->execute($arrayExecute);
												}
												$blukInsert[] = "('1','".$arrMessageMerge["SUBJECT"]."','".$arrMessageMerge["BODY"]."','".($pathImg ?? null)."','".$arrToken["LIST_SEND"][0]["MEMBER_NO"]."','".$rowNoti["create_by"]."'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").",'1')";
												if(sizeof($blukInsert) == 1000){
													$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
													$arrPayloadHistory["bulkInsert"] = $blukInsert;
													$func->insertHistory($arrPayloadHistory,'2','1');
													unset($blukInsert);
													$blukInsert = array();
												}
											}else{
												$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$arrToken["LIST_SEND"][0]["MEMBER_NO"]."','mobile_app',null,'".$arrToken["LIST_SEND"][0]["TOKEN"]."','ไม่สามารถส่งได้ให้ดู LOG','system'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").".'1')";
												if(sizeof($blukInsertNot) == 1000){
													$func->logSMSWasNotSent($blukInsertNot,false,'1');
													unset($blukInsertNot);
													$blukInsertNot = array();
												}
											}
										}else{
											$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$arrToken["LIST_SEND"][0]["MEMBER_NO"]."','mobile_app',null,'".$arrToken["LIST_SEND"][0]["TOKEN"]."','บัญชีปลายทางไม่ประสงค์เปิดรับการแจ้งเตือน','system'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").",'1')";
											if(sizeof($blukInsertNot) == 1000){
												$func->logSMSWasNotSent($blukInsertNot,false,'1');
												unset($blukInsertNot);
												$blukInsertNot = array();
											}
										}
									}else{
										if(isset($arrToken["LIST_SEND_HW"][0]["TOKEN"]) && $arrToken["LIST_SEND_HW"][0]["TOKEN"] != ""){
											if($arrToken["LIST_SEND_HW"][0]["RECEIVE_NOTIFY_TRANSACTION"] == "1"){
												$arrPayloadNotify["TO"] = array($arrToken["LIST_SEND_HW"][0]["TOKEN"]);
												$arrPayloadNotify["MEMBER_NO"] = $arrToken["LIST_SEND_HW"][0]["MEMBER_NO"];
												$arrMessage["SUBJECT"] = $arrMessageMerge["SUBJECT"];
												$arrMessage["BODY"] = $arrMessageMerge["BODY"];
												$arrMessage["PATH_IMAGE"] = $pathImg ?? null;
												$arrPayloadNotify["PAYLOAD"] = $arrMessage;
												$arrPayloadNotify["TYPE_NOTIFY"] = "2";
												if($lib->sendNotifyHW($arrPayloadNotify,'person')){
													if($rowQuery["is_stampflag"] == '1'){
														$arrayExecute = array();
														preg_match_all('/\\:(.*?)\\s/',$rowQuery["where_stamp"],$arrayRawExecute);
														foreach($arrayRawExecute[1] as $execute){
															$arrayExecute[$execute] = $rowTarget[$execute];
														}
														$updateFlagStamp = $conmssql->prepare("UPDATE ".$rowQuery["stamp_table"]." SET ".$rowQuery["set_column"]." WHERE ".$rowQuery["where_stamp"]);
														$updateFlagStamp->execute($arrayExecute);
													}
													$blukInsert[] = "('1','".$arrMessageMerge["SUBJECT"]."','".$arrMessageMerge["BODY"]."','".($pathImg ?? null)."','".$arrToken["LIST_SEND_HW"][0]["MEMBER_NO"]."','".$rowNoti["create_by"]."'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").",'1')";
													if(sizeof($blukInsert) == 1000){
														$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
														$arrPayloadHistory["bulkInsert"] = $blukInsert;
														$func->insertHistory($arrPayloadHistory,'2','1');
														unset($blukInsert);
														$blukInsert = array();
													}
												}else{
													$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$arrToken["LIST_SEND_HW"][0]["MEMBER_NO"]."','mobile_app',null,'".$arrToken["LIST_SEND_HW"][0]["TOKEN"]."','ไม่สามารถส่งได้ให้ดู LOG','system'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").".'1')";
													if(sizeof($blukInsertNot) == 1000){
														$func->logSMSWasNotSent($blukInsertNot,false,'1');
														unset($blukInsertNot);
														$blukInsertNot = array();
													}
												}
											}else{
												$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$arrToken["LIST_SEND_HW"][0]["MEMBER_NO"]."','mobile_app',null,'".$arrToken["LIST_SEND_HW"][0]["TOKEN"]."','บัญชีปลายทางไม่ประสงค์เปิดรับการแจ้งเตือน','system'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").",'1')";
												if(sizeof($blukInsertNot) == 1000){
													$func->logSMSWasNotSent($blukInsertNot,false,'1');
													unset($blukInsertNot);
													$blukInsertNot = array();
												}
											}
										}else{
											$blukInsertNot[] = "('".$arrMessageMerge["BODY"]."','".$rowTarget[$rowQuery["target_field"]]."','mobile_app',null,null,'หา Token ในการส่งไม่เจออาจจะเพราะไม่อนุญาตให้ส่งแจ้งเตือนเข้าเครื่อง','system'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").",'1')";
											if(sizeof($blukInsertNot) == 1000){
												$func->logSMSWasNotSent($blukInsertNot,false,'1');
												unset($blukInsertNot);
												$blukInsertNot = array();
											}
										}
									}
								}
							}
							if(sizeof($blukInsertNot) > 0){
								$func->logSMSWasNotSent($blukInsertNot,false,'1');
								unset($blukInsertNot);
								$blukInsertNot = array();
							}
							if(sizeof($blukInsert) > 0){
								$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
								$arrPayloadHistory["bulkInsert"] = $blukInsert;
								$func->insertHistory($arrPayloadHistory,'2','1');
								unset($blukInsert);
								$blukInsert = array();
							}
						}
					}
				}
			}else{
				if($rowNoti["destination"] != 'all'){
					$blukInsert = array();
					$blukInsertNot = array();
					$destination = array();
					$message_importData = explode(',',$rowNoti["destination"]);
					if(isset($rowNoti["destination_revoke"]) && $rowNoti["destination_revoke"] != ""){
						foreach($message_importData as $key => $target){
							if(!in_array($key,explode(',',$rowNoti["destination_revoke"]))){
								$destination[] = strtolower($lib->mb_str_pad($target));
							}
						}
					}else{
						foreach($message_importData as $key => $target){
							$destination[] = strtolower($lib->mb_str_pad($target));
						}
					}
					$arrToken = $func->getFCMToken('person',$destination);
					foreach($arrToken["LIST_SEND"] as $dest){
						if(isset($dest["TOKEN"]) && $dest["TOKEN"] != ""){
							$arrPayloadNotify["TO"] = array($dest["TOKEN"]);
							$arrPayloadNotify["MEMBER_NO"] = $dest["MEMBER_NO"];
							$arrMessage["SUBJECT"] = $rowNoti["send_topic"];
							$message = ($rowNoti["send_message"] ?? "-");
							$arrMessage["BODY"] = $message;
							$arrMessage["PATH_IMAGE"] = $pathImg ?? null;
							$arrPayloadNotify["PAYLOAD"] = $arrMessage;
							if($lib->sendNotify($arrPayloadNotify,'person')){
								$blukInsert[] = "('1','".$rowNoti["send_topic"]."','".$message."','".($pathImg ?? null)."','".$dest["MEMBER_NO"]."','".$rowNoti["create_by"]."'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").",'1')";
								if(sizeof($blukInsert) == 1000){
									$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
									$arrPayloadHistory["bulkInsert"] = $blukInsert;
									$func->insertHistory($arrPayloadHistory,'1','1');
									unset($blukInsert);
									$blukInsert = array();
								}
							}else{
								$blukInsertNot[] = "('".$message."','".$dest["MEMBER_NO"]."','mobile_app',null,'".$dest["TOKEN"]."','ไม่สามารถส่งได้ให้ดู LOG','system'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").",'1')";
								if(sizeof($blukInsertNot) == 1000){
									$func->logSMSWasNotSent($blukInsertNot,false,'1');
									unset($blukInsertNot);
									$blukInsertNot = array();
								}
							}
						}
					}
					foreach($arrToken["LIST_SEND_HW"] as $dest){
						if(isset($dest["TOKEN"]) && $dest["TOKEN"] != ""){
							$arrPayloadNotify["TO"] = array($dest["TOKEN"]);
							$arrPayloadNotify["MEMBER_NO"] = $dest["MEMBER_NO"];
							$arrMessage["SUBJECT"] = $rowNoti["send_topic"];
							$message = ($rowNoti["send_message"] ?? "-");
							$arrMessage["BODY"] = $message;
							$arrMessage["PATH_IMAGE"] = $pathImg ?? null;
							$arrPayloadNotify["PAYLOAD"] = $arrMessage;
							if($lib->sendNotifyHW($arrPayloadNotify,'person')){
								$blukInsert[] = "('1','".$rowNoti["send_topic"]."','".$message."','".($pathImg ?? null)."','".$dest["MEMBER_NO"]."','".$rowNoti["create_by"]."'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").",'1')";
								if(sizeof($blukInsert) == 1000){
									$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
									$arrPayloadHistory["bulkInsert"] = $blukInsert;
									$func->insertHistory($arrPayloadHistory,'1','1');
									unset($blukInsert);
									$blukInsert = array();
								}
							}else{
								$blukInsertNot[] = "('".$message."','".$dest["MEMBER_NO"]."','mobile_app',null,'".$dest["TOKEN"]."','ไม่สามารถส่งได้ให้ดู LOG','system'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").",'1')";
								if(sizeof($blukInsertNot) == 1000){
									$func->logSMSWasNotSent($blukInsertNot,false,'1');
									unset($blukInsertNot);
									$blukInsertNot = array();
								}
							}
						}
					}
					if(sizeof($blukInsert) > 0){
						$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
						$arrPayloadHistory["bulkInsert"] = $blukInsert;
						$func->insertHistory($arrPayloadHistory,'1','1');
						unset($blukInsert);
						$blukInsert = array();
					}
					if(sizeof($blukInsertNot) > 0){
						$func->logSMSWasNotSent($blukInsertNot,false,'1');
						unset($blukInsertNot);
						$blukInsertNot = array();
					}
				}else{
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
								'mobile_app',null,'".$dest["TOKEN"]."','บัญชีปลายทางไม่ประสงค์เปิดรับการแจ้งเตือน','system'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").",'1')";
							}
							if(sizeof($bulkInsert) == 1000){
								$func->logSMSWasNotSent($bulkInsert,false,'1');
								unset($bulkInsert);
								$bulkInsert = array();
							}
						}else{
							$bulkInsert[] = "('".$rowNoti["send_message"]."','".$dest["MEMBER_NO"]."',
							'mobile_app',null,null,'หา Token ในการส่งไม่เจออาจจะเพราะไม่อนุญาตให้ส่งแจ้งเตือนเข้าเครื่อง','system'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").",'1')";
							if(sizeof($bulkInsert) == 1000){
								$func->logSMSWasNotSent($bulkInsert,false,'1');
								unset($bulkInsert);
								$bulkInsert = array();
							}
						}
					}
					foreach($arrToken["LIST_SEND_HW"] as $dest){
						if(isset($dest["TOKEN"]) && $dest["TOKEN"] != ""){
							if($dest["RECEIVE_NOTIFY_NEWS"] == "1"){
								$arrAllMember_no[] = $dest["MEMBER_NO"];
								$arrAllToken[] = $dest["TOKEN"];
							}else{
								$bulkInsert[] = "('".$rowNoti["send_message"]."','".$dest["MEMBER_NO"]."',
								'mobile_app',null,'".$dest["TOKEN"]."','บัญชีปลายทางไม่ประสงค์เปิดรับการแจ้งเตือน','system'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").",'1')";
							}
							if(sizeof($bulkInsert) == 1000){
								$func->logSMSWasNotSent($bulkInsert,false,'1');
								unset($bulkInsert);
								$bulkInsert = array();
							}
						}else{
							$bulkInsert[] = "('".$rowNoti["send_message"]."','".$dest["MEMBER_NO"]."',
							'mobile_app',null,null,'หา Token ในการส่งไม่เจออาจจะเพราะไม่อนุญาตให้ส่งแจ้งเตือนเข้าเครื่อง','system'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").",'1')";
							if(sizeof($bulkInsert) == 1000){
								$func->logSMSWasNotSent($bulkInsert,false,'1');
								unset($bulkInsert);
								$bulkInsert = array();
							}
						}
					}
					if(sizeof($arrAllToken) > 0){
						if(sizeof($bulkInsert) > 0){
							$func->logSMSWasNotSent($bulkInsert,false,'1');
							unset($bulkInsert);
							$bulkInsert = array();
						}
						$arrPayloadNotify["TO"] = '/topics/member';
						$arrPayloadNotify["MEMBER_NO"] = $arrAllMember_no;
						$arrMessage["SUBJECT"] = $rowNoti["send_topic"];
						$arrMessage["BODY"] = $rowNoti["send_message"];
						$arrMessage["PATH_IMAGE"] = $pathImg ?? null;
						$arrPayloadNotify["PAYLOAD"] = $arrMessage;
						$arrPayloadNotify["TYPE_SEND_HISTORY"] = "onemessage";
						$arrPayloadNotify["SEND_BY"] = $rowNoti["create_by"];
						$arrPayloadNotify["ID_TEMPLATE"] = $rowNoti["id_smstemplate"];
						if($lib->sendNotify($arrPayloadNotify,'all') || $lib->sendNotifyHW($arrPayloadNotify,'all')){
							$func->insertHistory($arrPayloadNotify,'1','1');
						}
					}else{
						if(sizeof($bulkInsert) > 0){
							$func->logSMSWasNotSent($bulkInsert,false,'1');
							unset($bulkInsert);
							$bulkInsert = array();
						}
					}
				}
			}
		}
	}else{
		if($rowNoti["is_import"] == '1'){
			$bulkInsert = array();
			$arrGRPAll = array();
			$destination = array();
			$arrDestGRP = array();
			$arrDestSend = array();
			$message_importData = json_decode($rowNoti["destination"]);
			if(isset($rowNoti["destination_revoke"]) && $rowNoti["destination_revoke"] != ""){
				foreach($message_importData as $key => $target){
					$destination_temp = array();
					if(mb_strlen($target->DESTINATION) <= 8){
						if(!in_array($key,explode(',',$rowNoti["destination_revoke"]))){
							$destination[] = strtolower($lib->mb_str_pad($target->DESTINATION));
							$arrDestSend[] = $target;
						}
					}else if(mb_strlen($target->DESTINATION) == 10){
						if(!in_array($key,explode(',',$rowNoti["destination_revoke"]))){
							$destination_temp["MEMBER_NO"] = null;
							$destination_temp["TEL"] = $target->DESTINATION;
							$arrDestGRP[] = $destination_temp;
							$arrDestSend[] = $target;
						}
					}
				}
			}else{
				foreach($message_importData as $key => $target){
					$destination_temp = array();
					if(mb_strlen($target->DESTINATION) <= 8){
						$destination[] = strtolower($lib->mb_str_pad($target->DESTINATION));
						$arrDestSend[] = $target;
					}else if(mb_strlen($target->DESTINATION) == 10){
						$destination_temp["MEMBER_NO"] = null;
						$destination_temp["TEL"] = $target->DESTINATION;
						$arrDestGRP[] = $destination_temp;
						$arrDestSend[] = $target;
					}
				}
			}
			$arrayTel = $func->getSMSPerson('person',$destination,false,true);
			if(isset($arrDestGRP)){
				$arrayMerge = array_merge($arrayTel,$arrDestGRP);
			}else{
				$arrayMerge = $arrayTel;
			}
			$arrSend = array();
			foreach($arrDestSend as $dest){
				$indexFound = array_search($dest->DESTINATION, array_column($arrayMerge, 'MEMBER_NO')) !== false ? 
				array_search($dest->DESTINATION, array_column($arrayMerge, 'MEMBER_NO')) : array_search($dest->DESTINATION, array_column($arrayMerge, 'TEL'));
				if($indexFound !== false){
					$member_no = $arrayMerge[$indexFound]["MEMBER_NO"];
					$telMember = $arrayMerge[$indexFound]["TEL"];
					if(isset($telMember) && $telMember != ""){
						$message_body = $dest->MESSAGE;
						$arrayDest["cmd_sms"] = "CMD=".$config["CMD_SMS"]."&FROM=".$config["FROM_SERVICES_SMS"]."&TO=66".(substr($telMember,1,9))."&REPORT=Y&CHARGE=".$config["CHARGE_SMS"]."&CODE=".$config["CODE_SMS"]."&CTYPE=UNICODE&CONTENT=".$lib->unicodeMessageEncode($message_body);
						$arraySendSMS = $lib->sendSMS($arrayDest);
						if($arraySendSMS["RESULT"]){
							$arrSendTemp = array();
							$arrGRPAll[$member_no] = $message_body;
							$arrSendTemp["TEL"] = $telMember;
							$arrSendTemp["MEMBER_NO"] = $member_no;
							$arrSend[] = $arrSendTemp;
						}else{
							$bulkInsert[] = "('".$message_body."','".$member_no."',
									'sms','".$telMember."',null,'".$arraySendSMS["MESSAGE"]."','system'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").",'1')";
							if(sizeof($bulkInsert) == 1000){
								$func->logSMSWasNotSent($bulkInsert,false,'1');
								unset($bulkInsert);
							}
						}
					}
				}
			}
			if(sizeof($arrGRPAll) > 0){
				$func->logSMSWasSent($rowNoti["id_smstemplate"],$arrGRPAll,$arrSend,'system',true,false,'1');
			}
			if(sizeof($bulkInsert) > 0){
				$func->logSMSWasNotSent($bulkInsert,false,'1');
				unset($bulkInsert);
				$bulkInsert = array();
			}
		}else{
			if(isset($rowNoti["id_smsquery"])){
				if($rowNoti["destination"] != 'all'){
					$getQuery = $conmysql->prepare("SELECT sms_query,column_selected,is_bind_param,target_field,condition_target FROM smsquery WHERE id_smsquery = :id_query");
					$getQuery->execute([':id_query' => $rowNoti["id_smsquery"]]);
					if($getQuery->rowCount() > 0){
						$arrGRPAll = array();
						$arrayMerge = array();
						$bulkInsert = array();
						$rowQuery = $getQuery->fetch(PDO::FETCH_ASSOC);
						$arrColumn = explode(',',$rowQuery["column_selected"]);
						if($rowQuery["is_bind_param"] == '0'){
							$queryTarget = $conmssql->prepare($rowQuery['sms_query']);
							$queryTarget->execute();
							while($rowTarget = $queryTarget->fetch(PDO::FETCH_ASSOC)){
								$arrTarget = array();
								foreach($arrColumn as $column){
									$arrTarget[$column] = $rowTarget[strtoupper($column)] ?? null;
								}
								$arrMessage = $lib->mergeTemplate(null,$rowNoti["send_message"],$arrTarget);
								if(!in_array($rowTarget[$rowQuery["target_field"]]."_".$arrMessage["BODY"],json_decode($rowNoti["destination_revoke"]))){
									$arrayTel = $func->getSMSPerson('person',$rowTarget[$rowQuery["target_field"]]);
									if(isset($arrayTel[0]["TEL"]) && $arrayTel[0]["TEL"] != ""){
										$arrayDest["cmd_sms"] = "CMD=".$config["CMD_SMS"]."&FROM=".$config["FROM_SERVICES_SMS"]."&TO=66".(substr($arrayTel[0]["TEL"],1,9))."&REPORT=Y&CHARGE=".$config["CHARGE_SMS"]."&CODE=".$config["CODE_SMS"]."&CTYPE=UNICODE&CONTENT=".$lib->unicodeMessageEncode($arrMessage["BODY"]);
										$arraySendSMS = $lib->sendSMS($arrayDest);
										if($arraySendSMS["RESULT"]){
											if($rowQuery["is_stampflag"] == '1'){
												$arrayExecute = array();
												preg_match_all('/\\:(.*?)\\s/',$rowQuery["where_stamp"],$arrayRawExecute);
												foreach($arrayRawExecute[1] as $execute){
													$arrayExecute[$execute] = $rowTarget[$execute];
												}
												$updateFlagStamp = $conmssql->prepare("UPDATE ".$rowQuery["stamp_table"]." SET ".$rowQuery["set_column"]." WHERE ".$rowQuery["where_stamp"]);
												$updateFlagStamp->execute($arrayExecute);
											}
											$arrayMerge[] = $arrayTel[0];
											$arrGRPAll[$arrayTel[0]["MEMBER_NO"]] = $arrMessage["BODY"];
										}else{
											$bulkInsert[] = "('".$arrMessage["BODY"]."','".$arrayTel[0]["MEMBER_NO"]."',
													'sms','".$arrayTel[0]["TEL"]."',null,'".$arraySendSMS["MESSAGE"]."','system'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").",'1')";
											if(sizeof($bulkInsert) == 1000){
												$func->logSMSWasNotSent($bulkInsert,false,'1');
												unset($bulkInsert);
												$bulkInsert = array();
											}
										}
									}else{
										$bulkInsert[] = "('".$arrMessage["BODY"]."','".$arrayTel[0]["MEMBER_NO"]."',
										'sms',null,null,'ไม่พบเบอร์โทรศัพท์','system'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").",'1')";
										if(sizeof($bulkInsert) == 1000){
											$func->logSMSWasNotSent($bulkInsert,false,'1');
											unset($bulkInsert);
											$bulkInsert = array();
										}
									}
								}
							}
							if(sizeof($bulkInsert) > 0){
								$func->logSMSWasNotSent($bulkInsert,false,'1');
								unset($bulkInsert);
								$bulkInsert = array();
							}
							if(sizeof($arrGRPAll) > 0){
								$func->logSMSWasSent($rowNoti["id_smstemplate"],$arrGRPAll,$arrayMerge,'system',true,false,'1');
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
							$message_importData = explode(',',$rowNoti["destination"]);
							foreach($message_importData as $target){
								if($condition[1] == $rowQuery["target_field"]){
									if(strlen($target) <= 8){
										$destination = strtolower($lib->mb_str_pad($target));
									}else{
										$destination = $target;
									}
								}else{
									$destination = $target;
								}
								
								$queryTarget = $conmssql->prepare($query);
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
									if(!in_array($destination.'_'.$arrMessage["BODY"],json_decode($rowNoti["destination_revoke"]))){
										if($condition[1] == $rowQuery["target_field"]){
											$arrayTel = $func->getSMSPerson('person',$destination);
										}else{
											$arrayTel = $func->getSMSPerson('person',$rowTarget[$rowQuery["target_field"]]);
										}
										if(isset($arrayTel[0]["TEL"]) && $arrayTel[0]["TEL"] != ""){
											$arrayDest["cmd_sms"] = "CMD=".$config["CMD_SMS"]."&FROM=".$config["FROM_SERVICES_SMS"]."&TO=66".(substr($arrayTel[0]["TEL"],1,9))."&REPORT=Y&CHARGE=".$config["CHARGE_SMS"]."&CODE=".$config["CODE_SMS"]."&CTYPE=UNICODE&CONTENT=".$lib->unicodeMessageEncode($arrMessage["BODY"]);
											$arraySendSMS = $lib->sendSMS($arrayDest);
											if($arraySendSMS["RESULT"]){
												if($rowQuery["is_stampflag"] == '1'){
													$arrayExecute = array();
													preg_match_all('/\\:(.*?)\\s/',$rowQuery["where_stamp"],$arrayRawExecute);
													foreach($arrayRawExecute[1] as $execute){
														$arrayExecute[$execute] = $rowTarget[$execute];
													}
													$updateFlagStamp = $conmssql->prepare("UPDATE ".$rowQuery["stamp_table"]." SET ".$rowQuery["set_column"]." WHERE ".$rowQuery["where_stamp"]);
													$updateFlagStamp->execute($arrayExecute);
												}
												$arrayMerge[] = $arrayTel[0];
												$arrGRPAll[$arrayTel[0]["MEMBER_NO"]] = $arrMessage["BODY"];
											}else{
												$bulkInsert[] = "('".$arrMessage["BODY"]."','".$arrayTel[0]["MEMBER_NO"]."',
														'sms','".$arrayTel[0]["TEL"]."',null,'".$arraySendSMS["MESSAGE"]."','system'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").",'1')";
												if(sizeof($bulkInsert) == 1000){
													$func->logSMSWasNotSent($bulkInsert,false,'1');
													unset($bulkInsert);
													$bulkInsert = array();
												}
											}
										}else{
											$bulkInsert[] = "('".$arrMessage["BODY"]."','".$arrayTel[0]["MEMBER_NO"]."',
											'sms',null,null,'ไม่พบเบอร์โทรศัพท์','system'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").",'1')";
											if(sizeof($bulkInsert) == 1000){
												$func->logSMSWasNotSent($bulkInsert,false,'1');
												unset($bulkInsert);
												$bulkInsert = array();
											}
										}
									}
								}
							}
							if(sizeof($bulkInsert) > 0){
								$func->logSMSWasNotSent($bulkInsert,false,'1');
								unset($bulkInsert);
								$bulkInsert = array();
							}
							if(sizeof($arrGRPAll) > 0){
								$func->logSMSWasSent($rowNoti["id_smstemplate"],$arrGRPAll,$arrayMerge,'system',true,false,'1');
							}
						}
					}
				}else{
					$getQuery = $conmysql->prepare("SELECT sms_query,column_selected,is_bind_param,target_field,condition_target FROM smsquery WHERE id_smsquery = :id_query");
					$getQuery->execute([':id_query' => $rowNoti["id_smsquery"]]);
					if($getQuery->rowCount() > 0){
						$arrGRPAll = array();
						$arrayMerge = array();
						$bulkInsert = array();
						$rowQuery = $getQuery->fetch(PDO::FETCH_ASSOC);
						$arrColumn = explode(',',$rowQuery["column_selected"]);
						if($rowQuery["is_bind_param"] == '0'){
							$queryTarget = $conmssql->prepare($rowQuery['sms_query']);
							$queryTarget->execute();
							while($rowTarget = $queryTarget->fetch(PDO::FETCH_ASSOC)){
								$arrTarget = array();
								foreach($arrColumn as $column){
									$arrTarget[$column] = $rowTarget[strtoupper($column)] ?? null;
								}
								$arrMessage = $lib->mergeTemplate(null,$rowNoti["send_message"],$arrTarget);
								if(!in_array($rowTarget[$rowQuery["target_field"]]."_".$arrMessage["BODY"],json_decode($rowNoti["destination_revoke"]))){
									$arrayTel = $func->getSMSPerson('person',$rowTarget[$rowQuery["target_field"]]);
									if(isset($arrayTel[0]["TEL"]) && $arrayTel[0]["TEL"] != ""){
										$arrayDest["cmd_sms"] = "CMD=".$config["CMD_SMS"]."&FROM=".$config["FROM_SERVICES_SMS"]."&TO=66".(substr($arrayTel[0]["TEL"],1,9))."&REPORT=Y&CHARGE=".$config["CHARGE_SMS"]."&CODE=".$config["CODE_SMS"]."&CTYPE=UNICODE&CONTENT=".$lib->unicodeMessageEncode($arrMessage["BODY"]);
										$arraySendSMS = $lib->sendSMS($arrayDest);
										if($arraySendSMS["RESULT"]){
											if($rowQuery["is_stampflag"] == '1'){
												$arrayExecute = array();
												preg_match_all('/\\:(.*?)\\s/',$rowQuery["where_stamp"],$arrayRawExecute);
												foreach($arrayRawExecute[1] as $execute){
													$arrayExecute[$execute] = $rowTarget[$execute];
												}
												$updateFlagStamp = $conmssql->prepare("UPDATE ".$rowQuery["stamp_table"]." SET ".$rowQuery["set_column"]." WHERE ".$rowQuery["where_stamp"]);
												$updateFlagStamp->execute($arrayExecute);
											}
											$arrayMerge[] = $arrayTel[0];
											$arrGRPAll[$arrayTel[0]["MEMBER_NO"]] = $arrMessage["BODY"];
										}else{
											$bulkInsert[] = "('".$arrMessage["BODY"]."','".$arrayTel[0]["MEMBER_NO"]."',
													'sms','".$arrayTel[0]["TEL"]."',null,'".$arraySendSMS["MESSAGE"]."','system'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").",'1')";
											if(sizeof($bulkInsert) == 1000){
												$func->logSMSWasNotSent($bulkInsert,false,'1');
												unset($bulkInsert);
												$bulkInsert = array();
											}
										}
									}else{
										$bulkInsert[] = "('".$arrMessage["BODY"]."','".$arrayTel[0]["MEMBER_NO"]."',
										'sms',null,null,'ไม่พบเบอร์โทรศัพท์','system'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").",'1')";
										if(sizeof($bulkInsert) == 1000){
											$func->logSMSWasNotSent($bulkInsert,false,'1');
											unset($bulkInsert);
											$bulkInsert = array();
										}
									}
								}
							}
							if(sizeof($bulkInsert) > 0){
								$func->logSMSWasNotSent($bulkInsert,false,'1');
								unset($bulkInsert);
								$bulkInsert = array();
							}
							if(sizeof($arrGRPAll) > 0){
								$func->logSMSWasSent($rowNoti["id_smstemplate"],$arrGRPAll,$arrayMerge,'system',true,false,'1');
							}
						}
					}
				}
			}else{
				if($rowNoti["destination"] != 'all'){
					$arrGRPAll = array();
					$destination = array();
					$arrDestGRP = array();
					$bulkInsert = array();
					$message_importData = explode(',',$rowNoti["destination"]);
					if(isset($rowNoti["destination_revoke"]) && $rowNoti["destination_revoke"] != ""){
						foreach($message_importData as $key => $target){
							$destination_temp = array();
							if(mb_strlen($target) <= 8){
								if(!in_array($key,explode(',',$rowNoti["destination_revoke"]))){
									$destination[] = strtolower($lib->mb_str_pad($target));
								}
							}else if(mb_strlen($target) == 10){
								if(!in_array($target,explode(',',$rowNoti["destination_revoke"]))){
									$destination_temp["MEMBER_NO"] = null;
									$destination_temp["TEL"] = $target;
									$arrDestGRP[] = $destination_temp;
								}
							}
						}
					}else{
						foreach($message_importData as $key => $target){
							$destination_temp = array();
							if(mb_strlen($target) <= 8){
								$destination[] = strtolower($lib->mb_str_pad($target));
							}else if(mb_strlen($target) == 10){
								$destination_temp["MEMBER_NO"] = null;
								$destination_temp["TEL"] = $target;
								$arrDestGRP[] = $destination_temp;
							}
						}
					}
					$arrayTel = $func->getSMSPerson('person',$destination,false,true);
					if(isset($arrDestGRP)){
						$arrayMerge = array_merge($arrayTel,$arrDestGRP);
					}else{
						$arrayMerge = $arrayTel;
					}
					foreach($arrayMerge as $dest){
						if(isset($dest["TEL"]) && $dest["TEL"] != ""){
							$message_body = $rowNoti["send_message"];
							$arrayDest["cmd_sms"] = "CMD=".$config["CMD_SMS"]."&FROM=".$config["FROM_SERVICES_SMS"]."&TO=66".(substr($dest["TEL"],1,9))."&REPORT=Y&CHARGE=".$config["CHARGE_SMS"]."&CODE=".$config["CODE_SMS"]."&CTYPE=UNICODE&CONTENT=".$lib->unicodeMessageEncode($message_body);
							$arraySendSMS = $lib->sendSMS($arrayDest);
							if($arraySendSMS["RESULT"]){
								$arrGRPAll[$dest["MEMBER_NO"]] = $rowNoti["send_message"];
							}else{
								$bulkInsert[] = "('".$message_body."','".$dest["MEMBER_NO"]."',
										'sms','".$dest["TEL"]."',null,'".$arraySendSMS["MESSAGE"]."','system'".(isset($rowNoti["id_smstemplate"]) ? ",".$rowNoti["id_smstemplate"] : ",null").",'1')";
								if(sizeof($bulkInsert) == 1000){
									$func->logSMSWasNotSent($bulkInsert,false,'1');
									unset($bulkInsert);
								}
							}
						}
					}
					if(sizeof($arrGRPAll) > 0){
						$func->logSMSWasSent($rowNoti["id_smstemplate"],$arrGRPAll,$arrayMerge,'system',true,false,'1');
					}
					if(sizeof($bulkInsert) > 0){
						$func->logSMSWasNotSent($bulkInsert,false,'1');
						unset($bulkInsert);
						$bulkInsert = array();
					}
				}
			}
		}
	}
	$updateSentNoti = $conmysql->prepare("UPDATE smssendahead SET is_use = '-9' WHERE id_sendahead = :id_sendahead");
	$updateSentNoti->execute([':id_sendahead' => $rowNoti["id_sendahead"]]);
}
?>