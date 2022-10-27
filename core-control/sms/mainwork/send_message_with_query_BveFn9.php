<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','message_emoji_','type_send','channel_send'],$dataComing)){
	if($func->check_permission_core($payload,'sms','sendmessageall',$conoracle) 
		|| $func->check_permission_core($payload,'sms','sendmessageperson',$conoracle)){
		$id_template = isset($dataComing["id_smstemplate"]) && $dataComing["id_smstemplate"] != "" ? $dataComing["id_smstemplate"] : null;
		$member_destination = array();
		if($dataComing["type_send"] == "person"){
			if(isset($dataComing["destination"]) && $dataComing["destination"] != null){
				foreach($dataComing["destination"] as $desMemberNo){
					$member_destination[] = strtolower($lib->mb_str_pad($desMemberNo));
				}
			}
		}
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
			$getNormCont = $conoracle->prepare("SELECT sld.ITEM_PAYAMT,sl.MEMBER_NO,sld.SLIPITEM_DESC,TO_CHAR(sl.operate_date,'YYYY-MM-DD HH24:MI') as ENTRY_DATE,
												NVL(sld.LOANCONTRACT_NO,sl.MEMBER_NO) as LOANCONTRACT_NO,sld.ITEM_BALANCE,sld.SLIPITEMTYPE_CODE
												FROM slslippayin sl LEFT JOIN slslippayindet sld ON sl.payinslip_no = sld.payinslip_no
												where TRUNC(TO_CHAR(sl.slip_date,'YYYYMMDD')) = '".$dataComing["date_send"]."'".
												(($dataComing["type_send"] == "person") ? (" and sl.MEMBER_NO in('".implode("','",$member_destination)."')") : "").
												" and sl.ref_system = 'BIL' and sl.slip_status = '1'");
			$getNormCont->execute();
			while($rowTarget = $getNormCont->fetch(PDO::FETCH_ASSOC)){
				$arrGroupMessage = array();
				$arrMemberNoDestination = array();
				$arrTarget = array();
				$prefixText = '';
				if($rowTarget["SLIPITEMTYPE_CODE"] == 'I01'){
					$prefixText = 'ชำระ ';
					$arrTarget["LOANCONTRACT_NO"] = '';
				}else if($rowTarget["SLIPITEMTYPE_CODE"] == 'LON'){
					$arrTarget["LOANCONTRACT_NO"] = $rowTarget["LOANCONTRACT_NO"];
				}else{
					$arrTarget["LOANCONTRACT_NO"] = '';
				}
				$arrTarget["SLIPITEM_DESC"] = $prefixText.$rowTarget["SLIPITEM_DESC"];
				$arrTarget["ITEM_BALANCE"] = number_format($rowTarget["ITEM_BALANCE"],2);
				$arrTarget["ITEM_PAYAMT"] = number_format($rowTarget["ITEM_PAYAMT"],2);
				$arrTarget["SLIP_DATE"] = $lib->convertdate($rowTarget["ENTRY_DATE"],'d m Y',true);
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
			$getNormContDept = $conoracle->prepare("SELECT dp.deptaccount_no,
												 dpm.member_no,dp.deptslip_amt,dpc.recppaytype_desc,dp.prncbal,TO_CHAR(dp.deptslip_date,'YYYY-MM-DD') as deptslip_date
												FROM dpdeptslip dp LEFT JOIN dpdeptmaster dpm ON dp.deptaccount_no = dpm.deptaccount_no
												LEFT JOIN dpucfrecppaytype dpc ON dp.recppaytype_code = dpc.recppaytype_code
												WHERE dp.recppaytype_code = 'DTB' 
												and TRUNC(TO_CHAR(dp.entry_date,'YYYYMMDD')) = '".$dataComing["date_send"]."'".
												(($dataComing["type_send"] == "person") ? (" and dpm.MEMBER_NO in('".implode("','",$member_destination)."')") : ""));
			$getNormContDept->execute();
			while($rowTarget = $getNormContDept->fetch(PDO::FETCH_ASSOC)){
				$arrGroupMessage = array();
				$arrMemberNoDestination = array();
				$arrTarget = array();
				$prefixText = '';
				$arrTarget["LOANCONTRACT_NO"] = $rowTarget["DEPTACCOUNT_NO"];
				$arrTarget["SLIPITEM_DESC"] = 'ฝากเงินเข้าบัญชี';
				$arrTarget["ITEM_BALANCE"] = number_format($rowTarget["PRNCBAL"],2);
				$arrTarget["ITEM_PAYAMT"] = number_format($rowTarget["DEPTSLIP_AMT"],2);
				$arrTarget["SLIP_DATE"] = $lib->convertdate($rowTarget["DEPTSLIP_DATE"],'d m Y');
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
			$getNormCont = $conoracle->prepare("SELECT sld.ITEM_PAYAMT,sl.MEMBER_NO,sld.SLIPITEM_DESC,TO_CHAR(sl.operate_date,'YYYY-MM-DD HH24:MI') as ENTRY_DATE,
												NVL(sld.LOANCONTRACT_NO,sl.MEMBER_NO) as LOANCONTRACT_NO,sld.ITEM_BALANCE,sld.SLIPITEMTYPE_CODE
												FROM slslippayin sl LEFT JOIN slslippayindet sld ON sl.payinslip_no = sld.payinslip_no
												where TRUNC(TO_CHAR(sl.slip_date,'YYYYMMDD')) = '".$dataComing["date_send"]."'".
												(($dataComing["type_send"] == "person") ? (" and sl.MEMBER_NO in('".implode("','",$member_destination)."')") : "").
												" and sl.ref_system = 'BIL' and sl.slip_status = '1'");
			$getNormCont->execute();
			while($rowTarget = $getNormCont->fetch(PDO::FETCH_ASSOC)){
				$arrGroupCheckSend = array();
				$arrGroupMessage = array();
				$arrTarget = array();
				$prefixText = '';
				if($rowTarget["SLIPITEMTYPE_CODE"] == 'I01'){
					$prefixText = 'ชำระ ';
					$arrTarget["LOANCONTRACT_NO"] = '';
				}else if($rowTarget["SLIPITEMTYPE_CODE"] == 'LON'){
					$arrTarget["LOANCONTRACT_NO"] = $rowTarget["LOANCONTRACT_NO"];
				}else{
					$arrTarget["LOANCONTRACT_NO"] = '';
				}
				$arrTarget["SLIPITEM_DESC"] = $prefixText.$rowTarget["SLIPITEM_DESC"];
				$arrTarget["ITEM_BALANCE"] = number_format($rowTarget["ITEM_BALANCE"],2);
				$arrTarget["ITEM_PAYAMT"] = number_format($rowTarget["ITEM_PAYAMT"],2);
				$arrTarget["SLIP_DATE"] = $lib->convertdate($rowTarget["ENTRY_DATE"],'d m Y',true);
				$arrMessage = $lib->mergeTemplate(null,$dataComing["message_emoji_"],$arrTarget);
				if(!in_array($rowTarget["MEMBER_NO"].'_'.$arrMessage["BODY"],$dataComing["destination_revoke"])){
					$arrayTel = $func->getSMSPerson('person',$rowTarget["MEMBER_NO"],$conoracle);
					if(isset($arrayTel[0]["TEL"]) && $arrayTel[0]["TEL"] != ""){
						$arrayDest["cmd_sms"] = "CMD=".$config["CMD_SMS"]."&FROM=".$config["FROM_SERVICES_SMS"]."&TO=66".(substr($arrayTel[0]["TEL"],1,9))."&REPORT=Y&CHARGE=".$config["CHARGE_SMS"]."&CODE=".$config["CODE_SMS"]."&CTYPE=UNICODE&CONTENT=".$lib->unicodeMessageEncode($arrMessage["BODY"]);
						$arraySendSMS = $lib->sendSMS($arrayDest);
						if($arraySendSMS["RESULT"]){
							$func->logSMSWasSentPerson($id_template,$arrMessage["BODY"],$rowTarget["MEMBER_NO"],$arrayTel[0]["TEL"],$payload["username"],$conoracle);
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
			$getNormContDept = $conoracle->prepare("SELECT dp.deptaccount_no,
												 dpm.member_no,dp.deptslip_amt,dpc.recppaytype_desc,dp.prncbal,TO_CHAR(dp.deptslip_date,'YYYY-MM-DD') as deptslip_date
												FROM dpdeptslip dp LEFT JOIN dpdeptmaster dpm ON dp.deptaccount_no = dpm.deptaccount_no
												LEFT JOIN dpucfrecppaytype dpc ON dp.recppaytype_code = dpc.recppaytype_code
												WHERE dp.recppaytype_code = 'DTB' 
												and TRUNC(TO_CHAR(dp.entry_date,'YYYYMMDD')) = '".$dataComing["date_send"]."'".
												(($dataComing["type_send"] == "person") ? (" and dpm.MEMBER_NO in('".implode("','",$member_destination)."')") : ""));
			$getNormContDept->execute();
			while($rowTarget = $getNormContDept->fetch(PDO::FETCH_ASSOC)){
				$arrGroupCheckSend = array();
				$arrGroupMessage = array();
				$arrTarget = array();
				$prefixText = '';
				$arrTarget["LOANCONTRACT_NO"] = $rowTarget["DEPTACCOUNT_NO"];
				$arrTarget["SLIPITEM_DESC"] = 'ฝากเงินเข้าบัญชี';
				$arrTarget["ITEM_BALANCE"] = number_format($rowTarget["PRNCBAL"],2);
				$arrTarget["ITEM_PAYAMT"] = number_format($rowTarget["DEPTSLIP_AMT"],2);
				$arrTarget["SLIP_DATE"] = $lib->convertdate($rowTarget["DEPTSLIP_DATE"],'d m Y');
				$arrMessage = $lib->mergeTemplate(null,$dataComing["message_emoji_"],$arrTarget);
				if(!in_array($rowTarget["MEMBER_NO"].'_'.$arrMessage["BODY"],$dataComing["destination_revoke"])){
					$arrayTel = $func->getSMSPerson('person',$rowTarget["MEMBER_NO"],$conoracle);
					if(isset($arrayTel[0]["TEL"]) && $arrayTel[0]["TEL"] != ""){
						$arrayDest["cmd_sms"] = "CMD=".$config["CMD_SMS"]."&FROM=".$config["FROM_SERVICES_SMS"]."&TO=66".(substr($arrayTel[0]["TEL"],1,9))."&REPORT=Y&CHARGE=".$config["CHARGE_SMS"]."&CODE=".$config["CODE_SMS"]."&CTYPE=UNICODE&CONTENT=".$lib->unicodeMessageEncode($arrMessage["BODY"]);
						$arraySendSMS = $lib->sendSMS($arrayDest);
						if($arraySendSMS["RESULT"]){
							$func->logSMSWasSentPerson($id_template,$arrMessage["BODY"],$rowTarget["MEMBER_NO"],$arrayTel[0]["TEL"],$payload["username"],$conoracle);
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
			/*if(sizeof($arrGRPAll) > 0){
				$arrayLogSMS = $func->logSMSWasSent($id_template,$arrGRPAll,$arrayMerge,$payload["username"],$conoracle,true);
				$arrayResult['RESULT'] = $arrayLogSMS;
			}else{
				$arrayResult['RESULT'] = TRUE;
			}*/
			$arrayResult['RESULT'] = TRUE;
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