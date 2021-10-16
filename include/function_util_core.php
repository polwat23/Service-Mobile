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
				$revokeAllToken = $conora->prepare("UPDATE gctoken SET at_is_revoke = :type_revoke,at_expire_date = NOW(),
											rt_is_revoke = :type_revoke,rt_expire_date = NOW()
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
				$revokeAllToken = $conora->prepare("UPDATE gctoken SET at_is_revoke = :type_revoke,at_expire_date = NOW(),
												rt_is_revoke = :type_revoke,rt_expire_date = NOW()
												WHERE id_token = :id_token");
				$forceLogout = $conora->prepare("UPDATE gcuserlogin SET is_login = :type_login,logout_date = NOW()
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
			$conora->beginTransaction();
			$id_history = $this->getMaxTable('id_history' , 'gchistory',$conora);
			if($payload["TYPE_SEND_HISTORY"] == "onemessage"){
				if($is_sendahead == '1'){
					$bulkInsert = array();
					foreach($payload["MEMBER_NO"] as $member_no){
						$bulkInsert[] = "(".$id_history.",'".$type_history."','".$payload["PAYLOAD"]["SUBJECT"]."','".$payload["PAYLOAD"]["BODY"]."','".$payload["PAYLOAD"]["PATH_IMAGE"]."','".$member_no."','".$payload["SEND_BY"]."'".(isset($payload["ID_TEMPLATE"]) ? ",".$payload["ID_TEMPLATE"] : ",null").",'".$is_sendahead."')";
						if(sizeof($bulkInsert) == 1000){
							$insertHis = $conora->prepare("INSERT INTO gchistory(id_history,his_type,his_title,his_detail,his_path_image,member_no,send_by,id_smstemplate,is_sendahead) 
													VALUES".implode(',',$bulkInsert));
							if($insertHis->execute()){
								unset($bulkInsert);
								$bulkInsert = array();
							}else{
								$conora->rollback();
								return false;
							}
						}
						$id_history++;
					}
					if(sizeof($bulkInsert) > 0){
						$insertHis = $conora->prepare("INSERT INTO gchistory(id_history,his_type,his_title,his_detail,his_path_image,member_no,send_by,id_smstemplate,is_sendahead) 
													VALUES".implode(',',$bulkInsert));
						if($insertHis->execute()){
							$conora->commit();
							return true;
						}else{
							$conora->rollback();
							return false;
						}
					}else{
						$conora->commit();
						return true;
					}
				}else{
					$bulkInsert = array();
					foreach($payload["MEMBER_NO"] as $member_no){
						$bulkInsert[] = "(".$id_history.",'".$type_history."','".$payload["PAYLOAD"]["SUBJECT"]."','".$payload["PAYLOAD"]["BODY"]."','".$payload["PAYLOAD"]["PATH_IMAGE"]."','".$member_no."','".$payload["SEND_BY"]."'".(isset($payload["ID_TEMPLATE"]) ? ",".$payload["ID_TEMPLATE"] : ",null").")";
						if(sizeof($bulkInsert) == 1000){
							$insertHis = $conora->prepare("INSERT INTO gchistory(id_history,his_type,his_title,his_detail,his_path_image,member_no,send_by,id_smstemplate) 
													VALUES".implode(',',$bulkInsert));
							if($insertHis->execute()){
								unset($bulkInsert);
								$bulkInsert = array();
							}else{
								$conora->rollback();
								return false;
							}
						}
						$id_history++;
					}
					if(sizeof($bulkInsert) > 0){
						$insertHis = $conora->prepare("INSERT INTO gchistory(id_history,his_type,his_title,his_detail,his_path_image,member_no,send_by,id_smstemplate) 
													VALUES".implode(',',$bulkInsert));
						if($insertHis->execute()){
							$conora->commit();
							return true;
						}else{
							$conora->rollback();
							return false;
						}
					}else{
						$conora->commit();
						return true;
					}

				}
			}else if($payload["TYPE_SEND_HISTORY"] == "manymessage"){
				if($is_sendahead == '1'){
					$insertHis = $conora->prepare("INSERT INTO gchistory(id_history,his_type,his_title,his_detail,his_path_image,member_no,send_by,id_smstemplate,is_sendahead) 
													VALUES".implode(',',$payload["bulkInsert"]));
					if($insertHis->execute()){
						$conora->commit();
						return true;
					}else{
						$conora->rollback();
						return false;
					}
				}else{
					$insertHis = $conora->prepare("INSERT INTO gchistory(id_history,his_type,his_title,his_detail,his_path_image,member_no,send_by,id_smstemplate) 
													VALUES".implode(',',$payload["bulkInsert"]));
					if($insertHis->execute()){
						$conora->commit();
						return true;
					}else{
						$conora->rollback();
						return false;
					}
				}
			}else{
				return true;
			}
		}
		public function check_permission_core($payload,$root_menu,$page_name=null,$conora){
			if(isset($payload["section_system"]) && isset($payload["username"])){
				if($payload["section_system"] == "root" || $payload["section_system"] == "root_test"){
					return true;
				}else{
					if(isset($page_name)){
						$getConstructorMenu = $conora->prepare("SELECT cm.id_coremenu FROM corepermissionmenu cpm LEFT JOIN coremenu cm ON cpm.id_coremenu = cm.id_coremenu
															WHERE cpm.is_use = '1' and cm.coremenu_status = '1' and cpm.username = :username and cm.root_path = :root_menu");
						$getConstructorMenu->execute([
							':username' => $payload["username"],
							':root_menu' => $root_menu
						]);
						$rowrootMenu = $getConstructorMenu->fetch(\PDO::FETCH_ASSOC);
						if(isset($rowrootMenu["ID_COREMENU"])){
							$checkMenuinRoot = $conora->prepare("SELECT csm.id_submenu FROM coresubmenu csm LEFT JOIN corepermissionsubmenu cpsm ON csm.id_submenu = cpsm.id_submenu
																WHERE cpsm.is_use = '1' and csm.id_coremenu = :id_coremenu and csm.menu_status = '1' and csm.page_name = :page_name");
							$checkMenuinRoot->execute([
								':id_coremenu' => $rowrootMenu["ID_COREMENU"],
								':page_name' => $page_name
							]);
							$rowrootMenuinRoot = $checkMenuinRoot->fetch(\PDO::FETCH_ASSOC);
							if(isset($rowrootMenuinRoot["ID_SUBMENU"])){
								return true;
							}else{
								return false;
							}
						}else{
							return false;
						}
					}else{
						$checkPermit = $conora->prepare("SELECT cm.id_coremenu FROM corepermissionmenu cpm LEFT JOIN coremenu cm ON cpm.id_coremenu = cm.id_coremenu
														WHERE cpm.is_use = '1' and cm.coremenu_status = '1' and cpm.username = :username and cm.root_path = :root_menu");
						$checkPermit->execute([
							':username' => $payload["username"],
							':root_menu' => $root_menu
						]);
						$rowCheckPermit = $checkPermit->fetch(\PDO::FETCH_ASSOC);
						if(isset($rowCheckPermit["ID_COREMENU"])){
							return true;
						}else{
							return false;
						}
					}
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
						$fetchFCMToken = $conora->prepare("SELECT hms_token,fcm_token,receive_notify_news,receive_notify_transaction,member_no FROM gcmemberaccount WHERE member_no IN('".implode("','",$member_no)."')");
						$fetchFCMToken->execute();
					}else{
						$fetchFCMToken = $conora->prepare("SELECT hms_token,fcm_token,receive_notify_news,receive_notify_transaction,member_no FROM gcmemberaccount WHERE member_no = :member_no");
						$fetchFCMToken->execute([':member_no' => $member_no]);
					}
					while($rowFCMToken = $fetchFCMToken->fetch(\PDO::FETCH_ASSOC)){
						if(!in_array($rowFCMToken["MEMBER_NO"],$arrayMember)){
							$arrayMT = array();
							if(isset($rowFCMToken["FCM_TOKEN"]) && $rowFCMToken["FCM_TOKEN"] != ""){
								$arrayMT["TOKEN"] = $rowFCMToken["FCM_TOKEN"];
								$arrayMT["MEMBER_NO"] = $rowFCMToken["MEMBER_NO"];
								$arrayMT["RECEIVE_NOTIFY_NEWS"] = $rowFCMToken["RECEIVE_NOTIFY_NEWS"];
								$arrayMT["RECEIVE_NOTIFY_TRANSACTION"] = $rowFCMToken["RECEIVE_NOTIFY_TRANSACTION"];
								$arrayMember[] = $rowFCMToken["MEMBER_NO"];
								$arrayMemberGRP[] = $arrayMT;
							}else{
								$arrayMT["TOKEN"] = $rowFCMToken["HMS_TOKEN"];
								$arrayMT["MEMBER_NO"] = $rowFCMToken["MEMBER_NO"];
								$arrayMT["RECEIVE_NOTIFY_NEWS"] = $rowFCMToken["RECEIVE_NOTIFY_NEWS"];
								$arrayMT["RECEIVE_NOTIFY_TRANSACTION"] = $rowFCMToken["RECEIVE_NOTIFY_TRANSACTION"];
								$arrayMember[] = $rowFCMToken["MEMBER_NO"];
								$arrayMemberGRPHW[] = $arrayMT;
							}
						}
					}
				}
			}else{
				$fetchFCMToken = $conora->prepare("SELECT hms_token,fcm_token,receive_notify_news,member_no FROM gcmemberaccount");
				$fetchFCMToken->execute();
				while($rowFCMToken = $fetchFCMToken->fetch(\PDO::FETCH_ASSOC)){
					if(!in_array($rowFCMToken["MEMBER_NO"],$arrayMember)){
						$arrayMT = array();
						if(isset($rowFCMToken["FCM_TOKEN"]) && $rowFCMToken["FCM_TOKEN"] != ""){
							$arrayMT["TOKEN"] = $rowFCMToken["FCM_TOKEN"];
							$arrayMT["MEMBER_NO"] = $rowFCMToken["MEMBER_NO"];
							$arrayMT["RECEIVE_NOTIFY_NEWS"] = $rowFCMToken["RECEIVE_NOTIFY_NEWS"];
							$arrayMember[] = $rowFCMToken["MEMBER_NO"];
							$arrayMemberGRP[] = $arrayMT;
						}else{
							$arrayMT["TOKEN"] = $rowFCMToken["HMS_TOKEN"];
							$arrayMT["MEMBER_NO"] = $rowFCMToken["MEMBER_NO"];
							$arrayMT["RECEIVE_NOTIFY_NEWS"] = $rowFCMToken["RECEIVE_NOTIFY_NEWS"];
							$arrayMember[] = $rowFCMToken["MEMBER_NO"];
							$arrayMemberGRPHW[] = $arrayMT;
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
						$fetchDataOra = $conora->prepare("SELECT MEM_TELMOBILE,MEMBER_NO FROM mbmembmaster WHERE member_no IN(".implode(',',$arrayMemberTemp).") and
																resign_status = 0 and MEM_TELMOBILE IS NOT NULL");
						$fetchDataOra->execute();
						while($rowDataOra = $fetchDataOra->fetch(\PDO::FETCH_ASSOC)){
							if(isset($rowDataOra["MEM_TELMOBILE"])){
								$arrayMT = array();
								$arrayMT["TEL"] = '0875514386';//$rowDataOra["MEM_TELMOBILE"];
								$arrayMT["MEMBER_NO"] = $rowDataOra["MEMBER_NO"];
								$arrayMemberGRP[] = $arrayMT;
							}
						}
					}
				}else{
					if(is_array($member_no) && sizeof($member_no) > 0){
						$fetchDataOra = $conora->prepare("SELECT MEM_TELMOBILE,MEMBER_NO FROM mbmembmaster WHERE member_no IN('".implode("','",$member_no)."')");
						$fetchDataOra->execute();
					}else{
						$fetchDataOra = $conora->prepare("SELECT MEM_TELMOBILE,MEMBER_NO FROM mbmembmaster WHERE member_no = :member_no");
						$fetchDataOra->execute([':member_no' => $member_no]);
					}
					while($rowDataOra = $fetchDataOra->fetch(\PDO::FETCH_ASSOC)){
						if($check_tel){
							if(isset($rowDataOra["MEM_TELMOBILE"])){
								$arrayMT = array();
								$arrayMT["TEL"] = '0875514386';//$rowDataOra["MEM_TELMOBILE"];
								$arrayMT["MEMBER_NO"] = $rowDataOra["MEMBER_NO"];
								$arrayMemberGRP[] = $arrayMT;
							}
						}else{
							$arrayMT = array();
							$arrayMT["TEL"] = '0875514386';//$rowDataOra["MEM_TELMOBILE"];
							$arrayMT["MEMBER_NO"] = $rowDataOra["MEMBER_NO"];
							$arrayMemberGRP[] = $arrayMT;
						}
					}
				}
			}else{
				$fetchDataOra = $conora->prepare("SELECT MEM_TELMOBILE,MEMBER_NO FROM mbmembmaster WHERE resign_status = '0'");
				$fetchDataOra->execute();
				while($rowDataOra = $fetchDataOra->fetch(\PDO::FETCH_ASSOC)){
						$arrayMT = array();
						$arrayMT["TEL"] = '0875514386';//$rowDataOra["MEM_TELMOBILE"];
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
			$conora->beginTransaction();
			$id_logsent = $this->getMaxTable('id_logsent' , 'smslogwassent',$conora);
			$textcombine = array();
			$textcombinenotsent = array();
			if($is_sendahead){
				if($trans_flag){
				}else{
					if($multi_message){
						foreach($destination as $dest){
							$textcombine[] = "('".$id_logsent."','".$message[$dest["MEMBER_NO"]]."','".($dest["MEMBER_NO"] ?? null)."','".($dest["TEL"] ?? null)."','".$send_by."'".(isset($id_smstemplate) ? ",".$id_smstemplate : ",null").",'".$is_sendahead."')";
							if(sizeof($textcombine) == 1000){
								$insertToLogSMS = $conora->prepare("INSERT INTO smslogwassent(id_logsent,sms_message,member_no,tel_mobile,send_by,id_smstemplate,is_sendahead)
																	VALUES".implode(',',$textcombine));
								if($insertToLogSMS->execute()){
									unset($textcombine);
									$textcombine = array();
								}else{
									$conora->rollback();
									break;
								}
							}
							$id_logsent++;
						}
						if(sizeof($textcombine) > 0){
							$insertToLogSMS = $conora->prepare("INSERT INTO smslogwassent(id_logsent,sms_message,member_no,tel_mobile,send_by,id_smstemplate,is_sendahead)
																	VALUES".implode(',',$textcombine));
							if($insertToLogSMS->execute()){
								$conora->commit();
								return true;
							}else{
								$conora->rollback();
								return false;
							}
						}else{
							$conora->commit();
							return true;
						}
					}else{
						foreach($destination as $dest){
							if(isset($dest["TEL"]) && $dest["TEL"] != ""){
								$textcombine[] = "('".$id_logsent."','".$message."','".$dest["MEMBER_NO"]."','".$dest["TEL"]."','".$send_by."'".(isset($id_smstemplate) ? ",".$id_smstemplate : ",null").",'".$is_sendahead."')";
							}else{
								$textcombinenotsent[] = "('".$id_logsent."','".$message."','".$dest["MEMBER_NO"]."','sms','ไม่พบเบอร์โทรศัพท์','".$send_by."'".(isset($id_smstemplate) ? ",".$id_smstemplate : ",null").",'".$is_sendahead."')";
							}
							if(sizeof($textcombine) == 1000){
								$insertToLogSMS = $conora->prepare("INSERT INTO smslogwassent(id_logsent,sms_message,member_no,tel_mobile,send_by,id_smstemplate,is_sendahead)
																	VALUES".implode(',',$textcombine));
								if($insertToLogSMS->execute()){
									unset($textcombine);
									$textcombine = array();
								}else{
									$conora->rollback();
									return false;
								}
							}
							if(sizeof($textcombinenotsent) == 1000){
								$insertToLogNotSentSMS = $conora->prepare("INSERT INTO smswasnotsent(id_logsent,message,member_no,send_platform,cause_notsent,send_by,id_smstemplate,is_sendahead)
																		VALUES".implode(',',$textcombinenotsent));
								if($insertToLogNotSentSMS->execute()){
									unset($textcombinenotsent);
									$textcombinenotsent = array();
								}else{
									$conora->rollback();
									return false;
								}
							}
							$id_logsent++;
						}
						if(sizeof($textcombine) > 0){
							$insertToLogSMS = $conora->prepare("INSERT INTO smslogwassent(id_logsent,sms_message,member_no,tel_mobile,send_by,id_smstemplate,is_sendahead)
																	VALUES".implode(',',$textcombine));
							if($insertToLogSMS->execute()){
								if(sizeof($textcombinenotsent) > 0){
									$insertToLogNotSentSMS = $conora->prepare("INSERT INTO smswasnotsent(id_logsent,message,member_no,send_platform,cause_notsent,send_by,id_smstemplate,is_sendahead)
																			VALUES".implode(',',$textcombinenotsent));
									if($insertToLogNotSentSMS->execute()){
										$conora->commit();
										return true;
									}else{
										$conora->rollback();
										return false;
									}
								}else{
									$conora->commit();
									return true;
								}
							}else{
								$conora->rollback();
								return false;
							}
						}else{
							if(sizeof($textcombinenotsent) > 0){
								$insertToLogNotSentSMS = $conora->prepare("INSERT INTO smswasnotsent(id_logsent,message,member_no,send_platform,cause_notsent,send_by,id_smstemplate,is_sendahead)
																			VALUES".implode(',',$textcombinenotsent));
								if($insertToLogNotSentSMS->execute()){
									$conora->commit();
										return true;
								}else{
									$conora->rollback();
									return false;
								}
							}else{
								$conora->commit();
								return true;
							}
						}
					}
				}
			}else{
				if($trans_flag){
					
				}else{
					if($multi_message){
						foreach($destination as $dest){
							$textcombine[] = "('".$id_logsent."','".$message[$dest["MEMBER_NO"]]."','".($dest["MEMBER_NO"] ?? null)."','".($dest["TEL"] ?? null)."','".$send_by."'".(isset($id_smstemplate) ? ",".$id_smstemplate : ",null").")";
							if(sizeof($textcombine) == 1000){
								$insertToLogSMS = $conora->prepare("INSERT INTO smslogwassent(id_logsent,sms_message,member_no,tel_mobile,send_by,id_smstemplate)
																	VALUES".implode(',',$textcombine));
								if($insertToLogSMS->execute()){
									unset($textcombine);
									$textcombine = array();
								}else{
									$conora->rollback();
									break;
								}
							}
							$id_logsent++;
						}
						if(sizeof($textcombine) > 0){
							$insertToLogSMS = $conora->prepare("INSERT INTO smslogwassent(id_logsent,sms_message,member_no,tel_mobile,send_by,id_smstemplate)
																	VALUES".implode(',',$textcombine));
							if($insertToLogSMS->execute()){
								$conora->commit();
								return true;
							}else{
								$conora->rollback();
								return false;
							}
						}else{
							$conora->commit();
							return true;
						}
					}else{
						foreach($destination as $dest){
							if(isset($dest["TEL"]) && $dest["TEL"] != ""){
								$textcombine[] = "('".$id_logsent."','".$message."','".$dest["MEMBER_NO"]."','".$dest["TEL"]."','".$send_by."'".(isset($id_smstemplate) ? ",".$id_smstemplate : ",null").")";
							}else{
								$textcombinenotsent[] = "('".$id_logsent."','".$message."','".$dest["MEMBER_NO"]."','sms','ไม่พบเบอร์โทรศัพท์','".$send_by."'".(isset($id_smstemplate) ? ",".$id_smstemplate : ",null").")";
							}
							if(sizeof($textcombine) == 1000){
								$insertToLogSMS = $conora->prepare("INSERT INTO smslogwassent(id_logsent,sms_message,member_no,tel_mobile,send_by,id_smstemplate)
																	VALUES".implode(',',$textcombine));
								if($insertToLogSMS->execute()){
									unset($textcombine);
									$textcombine = array();
								}else{
									$conora->rollback();
									return false;
								}
							}
							if(sizeof($textcombinenotsent) == 1000){
								$insertToLogNotSentSMS = $conora->prepare("INSERT INTO smswasnotsent(id_logsent,message,member_no,send_platform,cause_notsent,send_by,id_smstemplate)
																		VALUES".implode(',',$textcombinenotsent));
								if($insertToLogNotSentSMS->execute()){
									unset($textcombinenotsent);
									$textcombinenotsent = array();
								}else{
									$conora->rollback();
									return false;
								}
							}
							$id_logsent++;
						}
						if(sizeof($textcombine) > 0){
							$insertToLogSMS = $conora->prepare("INSERT INTO smslogwassent(id_logsent,sms_message,member_no,tel_mobile,send_by,id_smstemplate)
																	VALUES".implode(',',$textcombine));
							if($insertToLogSMS->execute()){
								if(sizeof($textcombinenotsent) > 0){
									$insertToLogNotSentSMS = $conora->prepare("INSERT INTO smswasnotsent(id_logsent,message,member_no,send_platform,cause_notsent,send_by,id_smstemplate)
																			VALUES".implode(',',$textcombinenotsent));
									if($insertToLogNotSentSMS->execute()){
										$conora->commit();
										return true;
									}else{
										$conora->rollback();
										return false;
									}
								}else{
									$conora->commit();
									return true;
								}
							}else{
								$conora->rollback();
								return false;
							}
						}else{
							if(sizeof($textcombinenotsent) > 0){
								$insertToLogNotSentSMS = $conora->prepare("INSERT INTO smswasnotsent(id_logsent,message,member_no,send_platform,cause_notsent,send_by,id_smstemplate)
																			VALUES".implode(',',$textcombinenotsent));
								if($insertToLogNotSentSMS->execute()){
									$conora->commit();
										return true;
								}else{
									$conora->rollback();
									return false;
								}
							}else{
								$conora->commit();
								return true;
							}
						}
					}
				}
			}
		}
		public function logSMSWasNotSent($bulkInsert,$conora,$multi_message=false,$is_sendahead = '0',$his_img=false) {
			$conora->beginTransaction();
			if($is_sendahead == '1'){
				if($multi_message){
					return true;
				}else{
					if($his_img){
						$insertToLogSMS = $conora->prepare("INSERT INTO smswasnotsent(id_smsnotsent,topic,message,member_no,send_platform,tel_mobile,fcm_token,cause_notsent,send_by,id_smstemplate,is_sendahead,his_path_image)
															VALUES".implode(',',$bulkInsert));
					}else{
						$insertToLogSMS = $conora->prepare("INSERT INTO smswasnotsent(id_smsnotsent,topic,message,member_no,send_platform,tel_mobile,fcm_token,cause_notsent,send_by,id_smstemplate,is_sendahead)
															VALUES".implode(',',$bulkInsert));
					}
					if($insertToLogSMS->execute()){
						$conora->commit();
						return true;
					}else{
						$conora->rollback();
						return false;
					}
				}
			}else{
				if($multi_message){
					return true;
				}else{
					if($his_img){
						$insertToLogSMS = $conora->prepare("INSERT INTO smswasnotsent(id_smsnotsent,topic,message,member_no,send_platform,tel_mobile,fcm_token,cause_notsent,send_by,id_smstemplate,his_path_image)
																VALUES".implode(',',$bulkInsert));
					}else{
						$insertToLogSMS = $conora->prepare("INSERT INTO smswasnotsent(id_smsnotsent,topic,message,member_no,send_platform,tel_mobile,fcm_token,cause_notsent,send_by,id_smstemplate)
																VALUES".implode(',',$bulkInsert));
					}
					if($insertToLogSMS->execute()){
						$conora->commit();
						return true;
					}else{
						$conora->rollback();
						return false;
					}
				}
			}
		}
		public function logSendMail($blukInsert,$conora){
			$insertMail = $conora->prepare("INSERT INTO smslogmailsend(id_mailsend,mail_title, mail_text,mail_address,send_status,send_by,id_smstemplate,is_sendahead,error_message,member_no)
												VALUES".implode(",",$blukInsert));
			if($insertMail->execute()){
				return true;
			}else{
				return false;
			}
		}
		
		public function getMaxTable($column , $table,$conora){
			$queryMax = $conora->prepare("SELECT MAX(".$column.") as MAX_TABLE FROM ".$table);
			$queryMax->execute();
			$rowQueryMax = $queryMax->fetch(\PDO::FETCH_ASSOC);
			return $rowQueryMax["MAX_TABLE"] + 1;
		}
		
}
?>