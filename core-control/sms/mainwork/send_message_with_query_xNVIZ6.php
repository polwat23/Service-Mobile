<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','message_emoji_','type_send','channel_send'],$dataComing)){
	if($func->check_permission_core($payload,'sms','sendmessageall',$conoracle) 
		|| $func->check_permission_core($payload,'sms','sendmessageperson',$conoracle)){
		$id_template = isset($dataComing["id_smstemplate"]) && $dataComing["id_smstemplate"] != "" ? $dataComing["id_smstemplate"] : null;
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
					require_once('../../../include/exit_footer.php');
				}else{
					if($createImage){
						$pathImg = $config["URL_SERVICE"]."resource/image_wait_to_be_sent/".$createImage["normal_path"];
					}else{
						$arrayResult['RESPONSE_MESSAGE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
						$arrayResult['RESULT'] = FALSE;
						require_once('../../../include/exit_footer.php');
					}
				}
			}
			$id_smsnotsent = $func->getMaxTable('id_smsnotsent' , 'smswasnotsent',$conoracle);
			$id_history = $func->getMaxTable('id_history' , 'gchistory',$conoracle);
			$getNormCont = $conoracle->prepare("select atm.REGIS_DOCNO,TO_CHAR(atm.APPROVE_DATE, 'dd MON yyyy', 'NLS_CALENDAR=''THAI BUDDHA'' NLS_DATE_LANGUAGE=THAI') as APPROVE_DATE,
												mp.PRENAME_DESC || mb.memb_name || ' ' || mb.memb_surname as FULL_NAME,atm.MEMBER_NO 
												from atmregister atm LEFT JOIN mbmembmaster mb ON atm.MEMBER_NO = mb.MEMBER_NO 
												LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code where atm.expense_bank = '006' 
												and atm.appl_status = '1'  and 
												TRUNC(TO_CHAR(atm.APPROVE_DATE,'YYYYMMDD')) = '".$dataComing["date_send"]."'
												and atm.sync_notify_flag = '0'");
			$getNormCont->execute();
			while($rowTarget = $getNormCont->fetch(PDO::FETCH_ASSOC)){
				$arrGroupMessage = array();
				$arrMemberNoDestination = array();
				$arrTarget = array();
				$arrTarget["FULL_NAME"] = $rowTarget["FULL_NAME"];
				$arrMessageMerge = $lib->mergeTemplate($dataComing["topic_emoji_"],$dataComing["message_emoji_"],$arrTarget);
				if(!in_array($rowTarget["MEMBER_NO"].'_'.$arrMessageMerge["BODY"],$dataComing["destination_revoke"])){
					$arrToken = $func->getFCMToken('person',$rowTarget["MEMBER_NO"],$conoracle);
					if(sizeof($arrToken["MEMBER_NO"]) > 0){
						if(isset($arrToken["LIST_SEND"][0]["TOKEN"]) && $arrToken["LIST_SEND"][0]["TOKEN"] != ""){
							if($arrToken["LIST_SEND"][0]["RECEIVE_NOTIFY_TRANSACTION"] == "1"){
								$arrPayloadNotify["TO"] = array($arrToken["LIST_SEND"][0]["TOKEN"]);
								$arrPayloadNotify["MEMBER_NO"] = $arrToken["LIST_SEND"][0]["MEMBER_NO"];
								$arrMessage["SUBJECT"] = $arrMessageMerge["SUBJECT"];
								$arrMessage["BODY"] = $arrMessageMerge["BODY"];
								$arrMessage["PATH_IMAGE"] = $pathImg ?? null;
								$arrPayloadNotify["PAYLOAD"] = $arrMessage;
								$arrPayloadNotify["SEND_BY"] = $payload["username"];
								$arrPayloadNotify["ID_TEMPLATE"] = $id_template;
								$arrPayloadNotify["TYPE_NOTIFY"] = "2";										
								if($lib->sendNotify($arrPayloadNotify,$dataComing["type_send"])){
									$blukInsert[] = "('".$id_history."','1','".$arrMessageMerge["SUBJECT"]."','".$arrMessageMerge["BODY"]."','".($pathImg ?? null)."','".$arrToken["LIST_SEND"][0]["MEMBER_NO"]."','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
									if(sizeof($blukInsert) == 1000){
										$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
										$arrPayloadHistory["bulkInsert"] = $blukInsert;												
										$func->insertHistory($arrPayloadHistory,'2','0',$conoracle);
										unset($blukInsert);
										$blukInsert = array();
									}
								}else{
									$blukInsertNot[] = "('".$id_smsnotsent."','".$arrMessageMerge["SUBJECT"]."','".$arrMessageMerge["BODY"]."','".$arrToken["LIST_SEND"][0]["MEMBER_NO"]."','".$dataComing["channel_send"]."',null,'".$arrToken["LIST_SEND"][0]["TOKEN"]."','ไม่สามารถส่งได้ให้ดู LOG','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
									if(sizeof($blukInsertNot) == 1000){
										$func->logSMSWasNotSent($blukInsertNot,$conoracle);
										unset($blukInsertNot);
										$blukInsertNot = array();
									}
								}
							}else{
								$blukInsertNot[] = "('".$id_smsnotsent."','".$arrMessageMerge["SUBJECT"]."','".$arrMessageMerge["BODY"]."','".$arrToken["LIST_SEND"][0]["MEMBER_NO"]."','".$dataComing["channel_send"]."',null,'".$arrToken["LIST_SEND"][0]["TOKEN"]."','บัญชีปลายทางไม่ประสงค์เปิดรับการแจ้งเตือน','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
								if(sizeof($blukInsertNot) == 1000){
									$func->logSMSWasNotSent($blukInsertNot,$conoracle);
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
									$arrPayloadNotify["SEND_BY"] = $payload["username"];
									$arrPayloadNotify["ID_TEMPLATE"] = $id_template;
									$arrPayloadNotify["TYPE_NOTIFY"] = "2";
									if($lib->sendNotifyHW($arrPayloadNotify,$dataComing["type_send"])){
										$blukInsert[] = "('".$id_history."','1','".$arrMessageMerge["SUBJECT"]."','".$arrMessageMerge["BODY"]."','".($pathImg ?? null)."','".$arrToken["LIST_SEND_HW"][0]["MEMBER_NO"]."','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
										if(sizeof($blukInsert) == 1000){
											$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
											$arrPayloadHistory["bulkInsert"] = $blukInsert;
											$func->insertHistory($arrPayloadHistory,'2','0',$conoracle);
											unset($blukInsert);
											$blukInsert = array();
										}
									}else{
										$blukInsertNot[] = "('".$id_smsnotsent."','".$arrMessageMerge["SUBJECT"]."','".$arrMessageMerge["BODY"]."','".$arrToken["LIST_SEND_HW"][0]["MEMBER_NO"]."','".$dataComing["channel_send"]."',null,'".$arrToken["LIST_SEND_HW"][0]["TOKEN"]."','ไม่สามารถส่งได้ให้ดู LOG','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
										if(sizeof($blukInsertNot) == 1000){
											$func->logSMSWasNotSent($blukInsertNot,$conoracle);
											unset($blukInsertNot);
											$blukInsertNot = array();
										}
									}
								}else{
									$blukInsertNot[] = "('".$id_smsnotsent."','".$arrMessageMerge["SUBJECT"]."','".$arrMessageMerge["BODY"]."','".$arrToken["LIST_SEND_HW"][0]["MEMBER_NO"]."','".$dataComing["channel_send"]."',null,'".$arrToken["LIST_SEND_HW"][0]["TOKEN"]."','บัญชีปลายทางไม่ประสงค์เปิดรับการแจ้งเตือน','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
									if(sizeof($blukInsertNot) == 1000){
										$func->logSMSWasNotSent($blukInsertNot,$conoracle);
										unset($blukInsertNot);
										$blukInsertNot = array();
									}
								}
							}else{
								$blukInsertNot[] = "('".$id_smsnotsent."','".$arrMessageMerge["SUBJECT"]."','".$arrMessageMerge["BODY"]."','".$target."','".$dataComing["channel_send"]."',null,null,'หา Token ในการส่งไม่เจออาจจะเพราะไม่อนุญาตให้ส่งแจ้งเตือนเข้าเครื่อง','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
								if(sizeof($blukInsertNot) == 1000){
									$func->logSMSWasNotSent($blukInsertNot,$conoracle);
									unset($blukInsertNot);
									$blukInsertNot = array();
								}
							}
						}
					}else{
						$blukInsertNot[] = "('".$id_smsnotsent."','".$arrMessageMerge["SUBJECT"]."','".$arrMessageMerge["BODY"]."','".$target."','".$dataComing["channel_send"]."',null,null,'สมาชิกยังไม่ได้ใช้งานแอปพลิเคชั่น','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
						if(sizeof($blukInsertNot) == 1000){
							$func->logSMSWasNotSent($blukInsertNot,$conoracle);
							unset($blukInsertNot);
							$blukInsertNot = array();
						}
					}
				}
				$id_smsnotsent++;
				$id_history++;
			}
			if(sizeof($blukInsertNot) > 0){
				$func->logSMSWasNotSent($blukInsertNot,$conoracle);
				unset($blukInsertNot);
				$blukInsertNot = array();
			}
			
			if(sizeof($blukInsert) > 0){
				$arrPayloadHistory["bulkInsert"] = $blukInsert;
				$arrPayloadHistory["TYPE_SEND_HISTORY"] = "manymessage";
				$func->insertHistory($arrPayloadHistory,'2','0',$conoracle);
				unset($blukInsert);
				$blukInsert = array();
			}
			$arrayResult["RESULT"] = TRUE;	
			require_once('../../../include/exit_footer.php');
		}else{
			$arrGRPAll = array();
			$arrayMerge = array();
			$bulkInsert = array();
			$id_smsnotsent = $func->getMaxTable('id_smsnotsent' , 'smswasnotsent',$conoracle);
			$getNormCont = $conoracle->prepare("select atm.REGIS_DOCNO,TO_CHAR(atm.APPROVE_DATE, 'dd MON yyyy', 'NLS_CALENDAR=''THAI BUDDHA'' NLS_DATE_LANGUAGE=THAI') as APPROVE_DATE,
												mp.PRENAME_DESC || mb.memb_name || ' ' || mb.memb_surname as FULL_NAME,atm.MEMBER_NO 
												from atmregister atm LEFT JOIN mbmembmaster mb ON atm.MEMBER_NO = mb.MEMBER_NO 
												LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code where atm.expense_bank = '006' 
												and atm.appl_status = '1'  and 
												TRUNC(TO_CHAR(atm.APPROVE_DATE,'YYYYMMDD')) = '".$dataComing["date_send"]."'
												and atm.sync_notify_flag = '0'");
			$getNormCont->execute();
			while($rowTarget = $getNormCont->fetch(PDO::FETCH_ASSOC)){
				$arrGroupCheckSend = array();
				$arrGroupMessage = array();
				$arrTarget = array();
				$arrTarget["FULL_NAME"] = $rowTarget["FULL_NAME"];
				$arrMessage = $lib->mergeTemplate(null,$dataComing["message_emoji_"],$arrTarget);
				if(!in_array($rowTarget["MEMBER_NO"].'_'.$arrMessage["BODY"],$dataComing["destination_revoke"])){
					$arrayTel = $func->getSMSPerson('person',$rowTarget["MEMBER_NO"],$conoracle);
					if(isset($arrayTel[0]["TEL"]) && $arrayTel[0]["TEL"] != ""){
						$arrayDest["cmd_sms"] = "CMD=".$config["CMD_SMS"]."&FROM=".$config["FROM_SERVICES_SMS"]."&TO=66".(substr($arrayTel[0]["TEL"],1,9))."&REPORT=Y&CHARGE=".$config["CHARGE_SMS"]."&CODE=".$config["CODE_SMS"]."&CTYPE=UNICODE&CONTENT=".$lib->unicodeMessageEncode($arrMessage["BODY"]);
						$arraySendSMS = $lib->sendSMS($arrayDest);
						if($arraySendSMS["RESULT"]){
							$arrayMerge[] = $arrayTel[0];
							$arrGRPAll[$arrayTel[0]["MEMBER_NO"]] = $arrMessage["BODY"];
						}else{
							$bulkInsert[] = "('".$id_smsnotsent."','".$arrMessageMerge["SUBJECT"]."','".$arrMessage["BODY"]."','".$arrayTel[0]["MEMBER_NO"]."',
									'sms','".$arrayTel[0]["TEL"]."',null,'".$arraySendSMS["MESSAGE"]."','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
							if(sizeof($bulkInsert) == 1000){
								$func->logSMSWasNotSent($bulkInsert,$conoracle);
								unset($bulkInsert);
								$bulkInsert = array();
							}
						}
					}else{
						$bulkInsert[] = "('".$id_smsnotsent."','".$arrMessageMerge["SUBJECT"]."','".$arrMessage["BODY"]."','".$arrayTel[0]["MEMBER_NO"]."',
						'sms',null,null,'ไม่พบเบอร์โทรศัพท์','".$payload["username"]."'".(isset($id_template) ? ",".$id_template : ",null").")";
						if(sizeof($bulkInsert) == 1000){
							$func->logSMSWasNotSent($bulkInsert,$conoracle);
							unset($bulkInsert);
							$bulkInsert = array();
						}
					}
					}
				$id_smsnotsent++;
			}
			if(sizeof($bulkInsert) > 0){
				$func->logSMSWasNotSent($bulkInsert,$conoracle);
				unset($bulkInsert);
				$bulkInsert = array();
			}
			if(sizeof($arrGRPAll) > 0){
				$arrayLogSMS = $func->logSMSWasSent($id_template,$arrGRPAll,$arrayMerge,$payload["username"],$conoracle,true);
				$arrayResult['RESULT'] = $arrayLogSMS;
			}else{
				$arrayResult['RESULT'] = TRUE;
			}
			require_once('../../../include/exit_footer.php');
		}
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../include/exit_footer.php');
	}
}else{
	$arrayResult['RESULT'] = FALSE;	
	http_response_code(400);
	require_once('../../../include/exit_footer.php');
}
?>