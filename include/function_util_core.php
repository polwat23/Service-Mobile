<?php

namespace Component;

class functions {
		
		public function getConstant($constant,$conora) {
			$getLimit = $conora->prepare("SELECT constant_value FROM gcconstant WHERE constant_name = :constant and is_use = '1'");
			$getLimit->execute([':constant' => $constant]);
			$rowLimit = $getLimit->fetch(\PDO::FETCH_ASSOC);
			if(isset($rowLimit["CONSTANT_VALUE"])){
				return $rowLimit["CONSTANT_VALUE"];
			}else{
				return false;
			}
		}
		public function getTemplateSystem($component_system,$seq_no='1',$conora){
			$getTemplatedata = $conora->prepare("SELECT subject,body ,id_systemplate
													FROM smssystemtemplate WHERE component_system = :component_system and is_use = '1' and seq_no = :seq_no");
			$getTemplatedata->execute([
				':component_system' => $component_system,
				':seq_no' => $seq_no
			]);
			$rowTemplate = $getTemplatedata->fetch(\PDO::FETCH_ASSOC);
			if(isset($rowTemplate["ID_SYSTEMPLATE"])){
				$arrayResult = array();
				$arrayResult["SUBJECT"] = $rowTemplate["SUBJECT"];
				$arrayResult["BODY"] =  $rowTemplate["BODY"];
			return $arrayResult;
			}else{
				return null;
			}
		}
		public function logout($id_token,$type_login,$conora) {
			$logout = $conora->prepare("UPDATE gcuserlogin SET is_login = :type_login,logout_date = sysdate WHERE id_token = :id_token");
			if($logout->execute([
				':type_login' => $type_login,
				':id_token' => $id_token
			])){
				$this->revoke_alltoken($id_token,'-9',true,$conora);
				return true;
			}else{
				return false;
			}
		}
		public function revoke_alltoken($id_token,$type_revoke,$is_notlogout=false,$conora){
			if($is_notlogout){
				$revokeAllToken = $conora->prepare("UPDATE gctoken SET at_is_revoke = :type_revoke,at_expire_date = SYSDATE,
											rt_is_revoke = :type_revoke,rt_expire_date = SYSDATE
											WHERE id_token = :id_token");
				if($revokeAllToken->execute([
					':type_revoke' => $type_revoke,
					':id_token' => $id_token
				])){
					return true;
				}else{
					return false;
				}
			}else{
				$type_login = null;
				switch($type_revoke) {
					case '-9' : $type_login = '0';
						break;
					case '-8' : $type_login = '-99';
						break;
					case '-7' : $type_login = '-7';
						break;
					case '-88' : $type_login = '-88';
						break;
					case '-99' : $type_login = '-6';
						break;
					case '-6' : $type_login = '-5';
						break;
				}
				$revokeAllToken = $conora->prepare("UPDATE gctoken SET at_is_revoke = :type_revoke,at_expire_date = SYSDATE,
												rt_is_revoke = :type_revoke,rt_expire_date = SYSDATE
												WHERE id_token = :id_token");
				$forceLogout = $conora->prepare("UPDATE gcuserlogin SET is_login = :type_login,logout_date = SYSDATE
												WHERE id_token = :id_token");
				if($revokeAllToken->execute([
					':type_revoke' => $type_revoke,
					':id_token' => $id_token
				]) && $forceLogout->execute([
					':type_login' => $type_login,
					':id_token' => $id_token
				])){
					return true;
				}else{
					return false;
				}
			}
		}
		public function insertHistory($payload,$type_history='1',$is_sendahead = '0',$conora) {
			
			if($payload["TYPE_SEND_HISTORY"] == "onemessage"){
				if($is_sendahead == '1'){
					foreach($payload["MEMBER_NO"] as $member_no){
						$id_history = $this->getMaxTable('id_history' , 'gchistory',$conora);
						$bulkInsert = "(".$id_history.",'".$type_history."','".$payload["PAYLOAD"]["SUBJECT"]."','".$payload["PAYLOAD"]["BODY"]."','".$payload["PAYLOAD"]["PATH_IMAGE"]."','".$member_no."','".$payload["SEND_BY"]."'".(isset($payload["ID_TEMPLATE"]) ? ",".$payload["ID_TEMPLATE"] : ",null").",'".$is_sendahead."')";
						$insertHis = $conora->prepare("INSERT INTO gchistory(id_history,his_type,his_title,his_detail,his_path_image,member_no,send_by,id_smstemplate,is_sendahead) 
												VALUES".$bulkInsert);
						$insertHis->execute();
					}
					return true;
				}else{
					foreach($payload["MEMBER_NO"] as $member_no){
						$id_history = $this->getMaxTable('id_history' , 'gchistory',$conora);
						$bulkInsert = "(".$id_history.",'".$type_history."','".$payload["PAYLOAD"]["SUBJECT"]."','".$payload["PAYLOAD"]["BODY"]."','".$payload["PAYLOAD"]["PATH_IMAGE"]."','".$member_no."','".$payload["SEND_BY"]."'".(isset($payload["ID_TEMPLATE"]) ? ",".$payload["ID_TEMPLATE"] : ",null").")";
						$insertHis = $conora->prepare("INSERT INTO gchistory(id_history,his_type,his_title,his_detail,his_path_image,member_no,send_by,id_smstemplate) 
													VALUES".$bulkInsert);
					}
					return true;

				}
			}else if($payload["TYPE_SEND_HISTORY"] == "manymessage"){
				if($is_sendahead == '1'){
					foreach($payload["bulkInsert"] as $value){
						$insertHis = $conora->prepare("INSERT INTO gchistory(id_history,his_type,his_title,his_detail,his_path_image,member_no,send_by,id_smstemplate,is_sendahead) 
														VALUES".$value);
						$insertHis->execute();
					}
					return true;
				}else{
					foreach($payload["bulkInsert"] as $value){
						$insertHis = $conora->prepare("INSERT INTO gchistory(id_history,his_type,his_title,his_detail,his_path_image,member_no,send_by,id_smstemplate) 
													VALUES".$value);
					}
					return true;
				}
			}else{
				return true;
			}
		}
		public function check_permission_core($payload,$root_menu,$page_name=null,$conora){
			if(isset($payload["username"])){
				$getConstructorMenu = $conora->prepare("SELECT amp.WINDOW_ID FROM amsecwins amw LEFT JOIN amsecpermiss amp ON amw.window_id = amp.window_id
													WHERE amp.application = 'user' and amp.check_flag = '1' and amp.user_name = :username and amw.win_object = :page_name");
				$getConstructorMenu->execute([
					':username' => $payload["username"],
					':page_name' => $page_name
				]);
				$rowrootMenu = $getConstructorMenu->fetch(\PDO::FETCH_ASSOC);
				if(isset($rowrootMenu["WINDOW_ID"])){
					return true;
				}else{
					return false;
				}
			}else{
				return false;
			}
		}
		
		public function getFCMToken($type_target,$member_no=null,$conora){
			$arrayMemberGRP = array();
			$arrayMember = array();
			$arrayAll = array();
			$arrayMemberGRPHW = array();
			if($type_target == 'person'){
				if(isset($member_no) && $member_no != ""){
					if(is_array($member_no) && sizeof($member_no) > 0){
						$fetchFCMToken = $conora->prepare("SELECT HMS_TOKEN,FCM_TOKEN,RECEIVE_NOTIFY_NEWS,RECEIVE_NOTIFY_TRANSACTION,MEMBER_NO FROM gcmemberaccount WHERE member_no IN('".implode("','",$member_no)."')");
						$fetchFCMToken->execute();
					}else{
						$fetchFCMToken = $conora->prepare("SELECT HMS_TOKEN,FCM_TOKEN,RECEIVE_NOTIFY_NEWS,RECEIVE_NOTIFY_TRANSACTION,MEMBER_NO FROM gcmemberaccount WHERE member_no = :member_no");
						$fetchFCMToken->execute([':member_no' => $member_no]);
					}
					while($rowFCMToken = $fetchFCMToken->fetch(\PDO::FETCH_ASSOC)){
						if(!in_array($rowFCMToken["MEMBER_NO"],$arrayMember)){
							$arrayMT = array();
							$arrayHW = array();
							if(isset($rowFCMToken["FCM_TOKEN"]) && $rowFCMToken["FCM_TOKEN"] != ""){
								$arrayMT["TOKEN"] = $rowFCMToken["FCM_TOKEN"];
								$arrayMT["MEMBER_NO"] = $rowFCMToken["MEMBER_NO"];
								$arrayMT["RECEIVE_NOTIFY_NEWS"] = $rowFCMToken["RECEIVE_NOTIFY_NEWS"];
								$arrayMT["RECEIVE_NOTIFY_TRANSACTION"] = $rowFCMToken["RECEIVE_NOTIFY_TRANSACTION"];
								$arrayMember[] = $rowFCMToken["MEMBER_NO"];
								$arrayMemberGRP[] = $arrayMT;
								$arrayHW["TOKEN"] = null;
								$arrayHW["MEMBER_NO"] = null;
								$arrayHW["RECEIVE_NOTIFY_NEWS"] = $rowFCMToken["RECEIVE_NOTIFY_NEWS"];
								$arrayHW["RECEIVE_NOTIFY_TRANSACTION"] = $rowFCMToken["RECEIVE_NOTIFY_TRANSACTION"];
								$arrayMemberGRPHW[] = $arrayHW;
							}else{
								$arrayMT["TOKEN"] = null;
								$arrayMT["MEMBER_NO"] = null;
								$arrayMT["RECEIVE_NOTIFY_NEWS"] = $rowFCMToken["RECEIVE_NOTIFY_NEWS"];
								$arrayMT["RECEIVE_NOTIFY_TRANSACTION"] = $rowFCMToken["RECEIVE_NOTIFY_TRANSACTION"];
								$arrayMemberGRP[] = $arrayMT;
								$arrayHW["TOKEN"] = $rowFCMToken["HMS_TOKEN"];
								$arrayHW["MEMBER_NO"] = $rowFCMToken["MEMBER_NO"];
								$arrayHW["RECEIVE_NOTIFY_NEWS"] = $rowFCMToken["RECEIVE_NOTIFY_NEWS"];
								$arrayHW["RECEIVE_NOTIFY_TRANSACTION"] = $rowFCMToken["RECEIVE_NOTIFY_TRANSACTION"];
								$arrayMember[] = $rowFCMToken["MEMBER_NO"];
								$arrayMemberGRPHW[] = $arrayHW;
							}
						}
					}
				}
			}else{
				$fetchFCMToken = $conora->prepare("SELECT HMS_TOKEN,FCM_TOKEN,RECEIVE_NOTIFY_NEWS,MEMBER_NO FROM gcmemberaccount");
				$fetchFCMToken->execute();
				while($rowFCMToken = $fetchFCMToken->fetch(\PDO::FETCH_ASSOC)){
					if(!in_array($rowFCMToken["MEMBER_NO"],$arrayMember)){
						$arrayMT = array();
						$arrayHW = array();
						if(isset($rowFCMToken["FCM_TOKEN"]) && $rowFCMToken["FCM_TOKEN"] != ""){
							$arrayMT["TOKEN"] = $rowFCMToken["FCM_TOKEN"];
							$arrayMT["MEMBER_NO"] = $rowFCMToken["MEMBER_NO"];
							$arrayMT["RECEIVE_NOTIFY_NEWS"] = $rowFCMToken["RECEIVE_NOTIFY_NEWS"];
							$arrayMember[] = $rowFCMToken["MEMBER_NO"];
							$arrayMemberGRP[] = $arrayMT;
							$arrayHW["TOKEN"] = null;
							$arrayHW["MEMBER_NO"] = null;
							$arrayHW["RECEIVE_NOTIFY_NEWS"] = $rowFCMToken["RECEIVE_NOTIFY_NEWS"];
							$arrayMemberGRPHW[] = $arrayHW;
						}else{
							$arrayMT["TOKEN"] = null;
							$arrayMT["MEMBER_NO"] = null;
							$arrayMT["RECEIVE_NOTIFY_NEWS"] = $rowFCMToken["RECEIVE_NOTIFY_NEWS"];
							$arrayMemberGRP[] = $arrayMT;
							$arrayHW["TOKEN"] = $rowFCMToken["HMS_TOKEN"];
							$arrayHW["MEMBER_NO"] = $rowFCMToken["MEMBER_NO"];
							$arrayHW["RECEIVE_NOTIFY_NEWS"] = $rowFCMToken["RECEIVE_NOTIFY_NEWS"];
							$arrayMember[] = $rowFCMToken["MEMBER_NO"];
							$arrayMemberGRPHW[] = $arrayHW;
						}
					}
				}
			}
			$arrayAll["MEMBER_NO"] = $arrayMember;
			$arrayAll["LIST_SEND_HW"] = $arrayMemberGRPHW;
			$arrayAll["LIST_SEND"] = $arrayMemberGRP;
			return $arrayAll;
		}
		
		public function getSMSPerson($type_target,$member_no=null,$conora,$trans_flag=false,$check_tel=false){
			$arrayMemberGRP = array();
			if($type_target == 'person'){
				if($trans_flag){
					$fetchMemberAllow = $conora->prepare("SELECT smscsp_member FROM smsconstantperson WHERE is_use = '1' and smscsp_member IN('".implode("','",$member_no)."') ");
					$fetchMemberAllow->execute();
					while($rowMember = $fetchMemberAllow->fetch(\PDO::FETCH_ASSOC)){
						$arrayMemberTemp[] = "'".$rowMember["smscsp_member"]."'";
					}
					if(sizeof($arrayMemberTemp) > 0){
						$fetchDataOra = $conora->prepare("SELECT mb.MEM_TELMOBILE,mb.MEMBER_NO ,mp.prename_desc || mb.memb_name ||' '||mb.memb_surname as FULLNAME
																FROM mbmembmaster mb LEFT JOIN mbucfprename mp  ON mb.prename_code  = mp.prename_code 
																 WHERE mb.member_no IN(".implode(',',$arrayMemberTemp).") and
																 mb.resign_status = 0 and mb.MEM_TELMOBILE IS NOT NULL");
						$fetchDataOra->execute();
						while($rowDataOra = $fetchDataOra->fetch(\PDO::FETCH_ASSOC)){
							if(isset($rowDataOra["MEM_TELMOBILE"])){
								$arrayMT = array();
								$arrayMT["TEL"] = $rowDataOra["MEM_TELMOBILE"];
								$arrayMT["MEMBER_NO"] = $rowDataOra["MEMBER_NO"];
								$arrayMT["FULLNAME"] = $rowDataOra["FULLNAME"];
								$arrayMemberGRP[] = $arrayMT;
							}
						}
					}
				}else{
					if(is_array($member_no) && sizeof($member_no) > 0){
						if(sizeof($member_no) > 1000){
							$arrayMemerLoop = array();
							foreach($member_no as $memb_loop){
								$arrayMemerLoop[] = $memb_loop;
								if(sizeof($arrayMemerLoop) == 1000){
									$fetchDataOra = $conora->prepare("SELECT mb.MEM_TELMOBILE,mb.MEMBER_NO ,mp.prename_desc || mb.memb_name ||' '||mb.memb_surname as FULLNAME 
																			FROM mbmembmaster mb LEFT JOIN mbucfprename mp  ON mb.prename_code  = mp.prename_code 
																			WHERE mb.member_no IN('".implode("','",$arrayMemerLoop)."')");
									$fetchDataOra->execute();
									while($rowDataOra = $fetchDataOra->fetch(\PDO::FETCH_ASSOC)){
										if($check_tel){
											if(isset($rowDataOra["MEM_TELMOBILE"])){
												$arrayMT = array();
												$arrayMT["TEL"] = $rowDataOra["MEM_TELMOBILE"];
												$arrayMT["MEMBER_NO"] = $rowDataOra["MEMBER_NO"];
												$arrayMT["FULLNAME"] = $rowDataOra["FULLNAME"];
												$arrayMemberGRP[] = $arrayMT;
											}
										}else{
											$arrayMT = array();
											$arrayMT["TEL"] = $rowDataOra["MEM_TELMOBILE"];
											$arrayMT["MEMBER_NO"] = $rowDataOra["MEMBER_NO"];
											$arrayMT["FULLNAME"] = $rowDataOra["FULLNAME"];
											$arrayMemberGRP[] = $arrayMT;
										}
									}
									unset($arrayMemerLoop);
									$arrayMemerLoop = array();
								}
							}
							if(sizeof($arrayMemerLoop) > 0){
								$fetchDataOra = $conora->prepare("SELECT mb.MEM_TELMOBILE,mb.MEMBER_NO ,mp.prename_desc || mb.memb_name ||' '||mb.memb_surname as FULLNAME 
																		FROM mbmembmaster mb LEFT JOIN mbucfprename mp  ON mb.prename_code  = mp.prename_code 
																		WHERE mb.member_no IN('".implode("','",$arrayMemerLoop)."')");
								$fetchDataOra->execute();
								while($rowDataOra = $fetchDataOra->fetch(\PDO::FETCH_ASSOC)){
									if($check_tel){
										if(isset($rowDataOra["MEM_TELMOBILE"])){
											$arrayMT = array();
											$arrayMT["TEL"] = $rowDataOra["MEM_TELMOBILE"];
											$arrayMT["MEMBER_NO"] = $rowDataOra["MEMBER_NO"];
											$arrayMT["FULLNAME"] = $rowDataOra["FULLNAME"];
											$arrayMemberGRP[] = $arrayMT;
										}
									}else{
										$arrayMT = array();
										$arrayMT["TEL"] = $rowDataOra["MEM_TELMOBILE"];
										$arrayMT["MEMBER_NO"] = $rowDataOra["MEMBER_NO"];
										$arrayMT["FULLNAME"] = $rowDataOra["FULLNAME"];
										$arrayMemberGRP[] = $arrayMT;
									}
								}
								unset($arrayMemerLoop);
								$arrayMemerLoop = array();
							}
						}else{
							$fetchDataOra = $conora->prepare("SELECT mb.MEM_TELMOBILE,mb.MEMBER_NO ,mp.prename_desc || mb.memb_name ||' '||mb.memb_surname as FULLNAME 
																	FROM mbmembmaster mb LEFT JOIN mbucfprename mp  ON mb.prename_code  = mp.prename_code 
																	WHERE mb.member_no IN('".implode("','",$member_no)."')");
							$fetchDataOra->execute();
							while($rowDataOra = $fetchDataOra->fetch(\PDO::FETCH_ASSOC)){
								if($check_tel){
									if(isset($rowDataOra["MEM_TELMOBILE"])){
										$arrayMT = array();
										$arrayMT["TEL"] = $rowDataOra["MEM_TELMOBILE"];
										$arrayMT["MEMBER_NO"] = $rowDataOra["MEMBER_NO"];
										$arrayMT["FULLNAME"] = $rowDataOra["FULLNAME"];
										$arrayMemberGRP[] = $arrayMT;
									}
								}else{
									$arrayMT = array();
									$arrayMT["TEL"] = $rowDataOra["MEM_TELMOBILE"];
									$arrayMT["MEMBER_NO"] = $rowDataOra["MEMBER_NO"];
									$arrayMT["FULLNAME"] = $rowDataOra["FULLNAME"];
									$arrayMemberGRP[] = $arrayMT;
								}
							}
						}
					}else{
						$fetchDataOra = $conora->prepare("SELECT mb.MEM_TELMOBILE,mb.MEMBER_NO ,mp.prename_desc || mb.memb_name ||' '||mb.memb_surname as FULLNAME 
																FROM mbmembmaster mb LEFT JOIN mbucfprename mp  ON mb.prename_code  = mp.prename_code 
																WHERE  mb.member_no = :member_no");
						$fetchDataOra->execute([':member_no' => $member_no]);
						while($rowDataOra = $fetchDataOra->fetch(\PDO::FETCH_ASSOC)){
							if($check_tel){
								if(isset($rowDataOra["MEM_TELMOBILE"])){
									$arrayMT = array();
									$arrayMT["TEL"] = $rowDataOra["MEM_TELMOBILE"];
									$arrayMT["MEMBER_NO"] = $rowDataOra["MEMBER_NO"];
									$arrayMT["FULLNAME"] = $rowDataOra["FULLNAME"];
									$arrayMemberGRP[] = $arrayMT;
								}
							}else{
								$arrayMT = array();
								$arrayMT["TEL"] = $rowDataOra["MEM_TELMOBILE"];
								$arrayMT["MEMBER_NO"] = $rowDataOra["MEMBER_NO"];
								$arrayMT["FULLNAME"] = $rowDataOra["FULLNAME"];
								$arrayMemberGRP[] = $arrayMT;
							}
						}
					}
					
				}
			}else{
				$fetchDataOra = $conora->prepare("SELECT  mb.MEM_TELMOBILE,mb.MEMBER_NO ,mp.prename_desc || mb.memb_name ||' '||mb.memb_surname as fullname 
												FROM mbmembmaster mb LEFT JOIN mbucfprename mp  ON mb.prename_code  = mp.prename_code 
												WHERE mb.resign_status = '0'");
				$fetchDataOra->execute();
				while($rowDataOra = $fetchDataOra->fetch(\PDO::FETCH_ASSOC)){
						$arrayMT = array();
						$arrayMT["TEL"] = $rowDataOra["MEM_TELMOBILE"];
						$arrayMT["MEMBER_NO"] = $rowDataOra["MEMBER_NO"];
						$arrayMemberGRP[] = $arrayMT;
				}
			}
			return $arrayMemberGRP;
		}
		public function getMailAddress($member_no=null,$conora){
			$arrayMemberGRP = array();
			if(is_array($member_no) && sizeof($member_no) > 0){
				$fetchDataOra = $conora->prepare("SELECT EMAIL,MEMBER_NO FROM mbmembmaster WHERE member_no IN('".implode("','",$member_no)."')");
				$fetchDataOra->execute();
			}else{
				$fetchDataOra = $conora->prepare("SELECT EMAIL,MEMBER_NO FROM mbmembmaster WHERE member_no = :member_no");
				$fetchDataOra->execute([':member_no' => $member_no]);
			}
			while($rowDataOra = $fetchDataOra->fetch(\PDO::FETCH_ASSOC)){
				$arrayMT = array();
				$arrayMT["EMAIL"] = $rowDataOra["EMAIL"];
				$arrayMT["MEMBER_NO"] = $rowDataOra["MEMBER_NO"];
				$arrayMemberGRP[] = $arrayMT;
			}
			return $arrayMemberGRP;
		}
		public function logSMSWasSent($id_smstemplate=null,$message,$destination,$send_by,$conora,
		$multi_message=false,$trans_flag=false,$is_sendahead = '0') {
			if($is_sendahead){
				if($trans_flag){
				}else{
					if($multi_message){
						foreach($destination as $dest){
							$id_logsent = $this->getMaxTable('id_logsent' , 'smslogwassent',$conora);
							$insertToLogSMS = $conora->prepare("INSERT INTO smslogwassent(id_logsent,sms_message,member_no,tel_mobile,send_by,id_smstemplate,is_sendahead)
																VALUES('".$id_logsent."','".$message[$dest["MEMBER_NO"]]."','".($dest["MEMBER_NO"] ?? null)."','".($dest["TEL"] ?? null)."','".$send_by."'".(isset($id_smstemplate) ? ",".$id_smstemplate : ",null").",'".$is_sendahead."')");
							$insertToLogSMS->execute();
						}
						return true;
					}else{
						foreach($destination as $dest){
							$id_logsent = $this->getMaxTable('id_logsent' , 'smslogwassent',$conora);
							if(isset($dest["TEL"]) && $dest["TEL"] != ""){
								$textcombine = "('".$id_logsent."','".$message."','".$dest["MEMBER_NO"]."','".$dest["TEL"]."','".$send_by."'".(isset($id_smstemplate) ? ",".$id_smstemplate : ",null").",'".$is_sendahead."')";
								$insertToLogSMS = $conora->prepare("INSERT INTO smslogwassent(id_logsent,sms_message,member_no,tel_mobile,send_by,id_smstemplate,is_sendahead)
																	VALUES".$textcombine);
								$insertToLogSMS->execute();
							}else{
								$textcombinenotsent = "('".$id_logsent."','".$message."','".$dest["MEMBER_NO"]."','sms','ไม่พบเบอร์โทรศัพท์','".$send_by."'".(isset($id_smstemplate) ? ",".$id_smstemplate : ",null").",'".$is_sendahead."')";
								$insertToLogNotSentSMS = $conora->prepare("INSERT INTO smswasnotsent(id_logsent,message,member_no,send_platform,cause_notsent,send_by,id_smstemplate,is_sendahead)
																		VALUES".$textcombinenotsent);
								$insertToLogNotSentSMS->execute();
							}
						}
						return true;
					}
				}
			}else{
				if($trans_flag){
				}else{
					if($multi_message){
						$id_logsent = $this->getMaxTable('id_logsent' , 'smslogwassent',$conora);
						foreach($destination as $dest){
							$textcombine = "('".$id_logsent."','".$message[$dest["MEMBER_NO"]]."','".($dest["MEMBER_NO"] ?? null)."','".($dest["TEL"] ?? null)."','".$send_by."'".(isset($id_smstemplate) ? ",".$id_smstemplate : ",null").")";
							$insertToLogSMS = $conora->prepare("INSERT INTO smslogwassent(id_logsent,sms_message,member_no,tel_mobile,send_by,id_smstemplate)
																VALUES".$textcombine);
							$insertToLogSMS->execute();
						}
						return true;
					}else{
						foreach($destination as $dest){
							$id_logsent = $this->getMaxTable('id_logsent' , 'smslogwassent',$conora);
							if(isset($dest["TEL"]) && $dest["TEL"] != ""){
								$textcombine = "('".$id_logsent."','".$message."','".$dest["MEMBER_NO"]."','".$dest["TEL"]."','".$send_by."'".(isset($id_smstemplate) ? ",".$id_smstemplate : ",null").")";
								$insertToLogSMS = $conora->prepare("INSERT INTO smslogwassent(id_logsent,sms_message,member_no,tel_mobile,send_by,id_smstemplate)
																	VALUES".$textcombine);
								$insertToLogSMS->execute();
							}else{
								$textcombinenotsent = "('".$id_logsent."','".$message."','".$dest["MEMBER_NO"]."','sms','ไม่พบเบอร์โทรศัพท์','".$send_by."'".(isset($id_smstemplate) ? ",".$id_smstemplate : ",null").")";
								$insertToLogNotSentSMS = $conora->prepare("INSERT INTO smswasnotsent(id_logsent,message,member_no,send_platform,cause_notsent,send_by,id_smstemplate)
																		VALUES".$textcombinenotsent);
								$insertToLogNotSentSMS->execute();
							}
						}
						return true;
					}
				}
			}
		}
		public function logSMSWasSentPerson($id_smstemplate=null,$message,$destination,$tel,$send_by,$conora) {
			$id_logsent = $this->getMaxTable('id_logsent' , 'smslogwassent',$conora);
			$textcombine = "('".$id_logsent."','".$message."','".$destination."','".$tel."','".$send_by."'".(isset($id_smstemplate) ? ",".$id_smstemplate : ",null").")";
			$insertToLogSMS = $conora->prepare("INSERT INTO smslogwassent(id_logsent,sms_message,member_no,tel_mobile,send_by,id_smstemplate)
												VALUES".$textcombine);
			$insertToLogSMS->execute();
		}
		public function logTempSMSWasSentPerson($id_smstemplate=null,$message,$destination,$tel,$send_by,$conora) {
			//file_put_contents('Msgresponse.txt', json_encode($destination,JSON_UNESCAPED_UNICODE ) . PHP_EOL, FILE_APPEND);
			$id_logsent = $this->getMaxTable('id_logsent' , 'smstemplogwassent',$conora);
			$textcombine = "('".$id_logsent."','".$message."','".$destination."','".$tel."','".$send_by."'".(isset($id_smstemplate) ? ",".$id_smstemplate : ",null").")";
			$insertToLogSMS = $conora->prepare("INSERT INTO smstemplogwassent(id_logsent,sms_message,member_no,tel_mobile,send_by,id_smstemplate)
												VALUES".$textcombine);
			$insertToLogSMS->execute();
		}
		public function logTempSMSBirthdateWasSent($id_smstemplate=null,$message,$destination,$tel,$send_by,$conora) {
			//file_put_contents('Msgresponse.txt', json_encode($destination,JSON_UNESCAPED_UNICODE ) . PHP_EOL, FILE_APPEND);
			$id_logsent = $this->getMaxTable('id_logsent' , 'SMSTEMPBIRTHDATEWASSENT',$conora);
			$textcombine = "('".$id_logsent."','".$message."','".$destination."','".$tel."','".$send_by."'".(isset($id_smstemplate) ? ",".$id_smstemplate : ",null").")";
			$insertToLogSMS = $conora->prepare("INSERT INTO SMSTEMPBIRTHDATEWASSENT(id_logsent,sms_message,member_no,tel_mobile,send_by,id_smstemplate)
												VALUES".$textcombine);
			$insertToLogSMS->execute();
		}
		
		
				
		public function logSMSWasNotSent($bulkInsert,$conora,$multi_message=false,$is_sendahead = '0',$his_img=false) {
			if($is_sendahead == '1'){
				if($multi_message){
					return true;
				}else{
					if($his_img){
						foreach($bulkInsert as $value) {
							$insertToLogSMS = $conora->prepare("INSERT INTO smswasnotsent(id_smsnotsent,topic,message,member_no,send_platform,tel_mobile,fcm_token,cause_notsent,send_by,id_smstemplate,is_sendahead,his_path_image)
															VALUES".$value);
							$insertToLogSMS->execute();
						}
					}else{
						foreach($bulkInsert as $value) {
							$insertToLogSMS = $conora->prepare("INSERT INTO smswasnotsent(id_smsnotsent,topic,message,member_no,send_platform,tel_mobile,fcm_token,cause_notsent,send_by,id_smstemplate,is_sendahead)
															VALUES".$value);
							$insertToLogSMS->execute();
						}
					}
					return true;
				}
			}else{
				if($multi_message){
					return true;
				}else{
					if($his_img){
						foreach($bulkInsert as $value) {
							$insertToLogSMS = $conora->prepare("INSERT INTO smswasnotsent(id_smsnotsent,topic,message,member_no,send_platform,tel_mobile,fcm_token,cause_notsent,send_by,id_smstemplate,his_path_image)
																VALUES".$value);
							$insertToLogSMS->execute();
						}
						
					}else{
						$insertToLogSMS = $conora->prepare("INSERT INTO smswasnotsent(id_smsnotsent,topic,message,member_no,send_platform,tel_mobile,fcm_token,cause_notsent,send_by,id_smstemplate)
																VALUES".$value);
						$insertToLogSMS->execute();
					}
					return true;
				}
			}
		}
		public function logSendMail($blukInsert,$conora){
			foreach($blukInsert as $value){
				$insertMail = $conora->prepare("INSERT INTO smslogmailsend(id_mailsend,mail_title, mail_text,mail_address,send_status,send_by,id_smstemplate,is_sendahead,error_message,member_no)
												VALUES".$value);
				$insertMail->execute();
			}
			return true;
		}
		
		public function getMaxTable($column , $table,$conora){
			$queryMax = $conora->prepare("SELECT MAX(".$column.") as MAX_TABLE FROM ".$table);
			$queryMax->execute();
			$rowQueryMax = $queryMax->fetch(\PDO::FETCH_ASSOC);
			return $rowQueryMax["MAX_TABLE"] + 1;
		}
		
}
?>