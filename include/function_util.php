<?php

namespace Component;

use Connection\connection;

class functions {
		private $con;
		private $conora;
		
		function __construct() {
			$connection = new connection();
			$this->con = $connection->connecttomysql();
			$this->conora = $connection->connecttooracle();
		}
		
		public function checkLogin($id_token) {
			$checkLogin = $this->con->prepare("SELECT id_userlogin,is_login FROM gcuserlogin 
												WHERE id_token = :id_token");
			$checkLogin->execute([
				':id_token' => $id_token
			]);
			$rowLogin = $checkLogin->fetch(\PDO::FETCH_ASSOC);
			$arrayLogin = array();
			if($rowLogin["is_login"] == '1'){
				$arrayLogin["RETURN"] = TRUE;
				return $arrayLogin;
			}else{
				$arrayLogin["IS_LOGIN"] = $rowLogin["is_login"] ?? '-99';
				$arrayLogin["RETURN"] = FALSE;
				return $arrayLogin;
			}
		}
		public function checkAccStatus($member_no) {
			$checkStatus = $this->con->prepare("SELECT account_status FROM gcmemberaccount 
												WHERE member_no = :member_no");
			$checkStatus->execute([
				':member_no' => $member_no
			]);
			$rowStatus = $checkStatus->fetch(\PDO::FETCH_ASSOC);
			$arrayStatus = array();
			if($rowStatus["account_status"] == '1' || $rowStatus["account_status"] == '-9'){
				return TRUE;
			}else{
				return FALSE;
			}
		}
		public function logout($id_token,$type_login) {
			$logout = $this->con->prepare("UPDATE gcuserlogin SET is_login = :type_login,logout_date = NOW() WHERE id_token = :id_token");
			if($logout->execute([
				':type_login' => $type_login,
				':id_token' => $id_token
			])){
				$this->revoke_alltoken($id_token,'-9',true);
				return true;
			}else{
				$arrExecute = [
					':type_login' => $type_login,
					':id_token' => $id_token
				];
				$arrError = array();
				$arrError["EXECUTE"] = $arrExecute;
				$arrError["QUERY"] = $logout;
				$arrError["COMPONENT"] = "Logout";
				file_put_contents(__DIR__.'/../log/logout_error.txt', json_encode($arrError) . PHP_EOL, FILE_APPEND);
				return false;
			}
		}
		public function logoutAll($id_token,$member_no,$type_login) {
			$arrMember = array();
			if(isset($id_token)){
				$getMemberlogin = $this->con->prepare("SELECT id_token FROM gcuserlogin WHERE member_no = :member_no and id_token <> :id_token and is_login = '1'");
				$getMemberlogin->execute([
					':member_no' => $member_no,
					':id_token' => $id_token
				]);
			}else{
				$getMemberlogin = $this->con->prepare("SELECT id_token FROM gcuserlogin WHERE member_no = :member_no and is_login = '1'");
				$getMemberlogin->execute([
					':member_no' => $member_no
				]);
			}
			while($rowMember = $getMemberlogin->fetch(\PDO::FETCH_ASSOC)){
				$arrMember[] = $rowMember["id_token"];
			}
			$logout = $this->con->prepare("UPDATE gcuserlogin SET is_login = :type_login,logout_date = NOW() 
									WHERE member_no = :member_no and id_token <> :id_token and is_login = '1'");
			if($logout->execute([
				':type_login' => $type_login,
				':member_no' => $member_no,
				':id_token' => $id_token
			])){
				foreach($arrMember as $token_value){
					$this->revoke_alltoken($token_value,'-9',$this->con,true);
				}
				return true;
			}else{
				$arrExecute = [
					':type_login' => $type_login,
					':member_no' => $member_no,
					':id_token' => $id_token
				];
				$arrError = array();
				$arrError["EXECUTE"] = $arrExecute;
				$arrError["QUERY"] = $logout;
				$arrError["COMPONENT"] = "LogoutAll";
				file_put_contents(__DIR__.'/../log/logout_error.txt', json_encode($arrError) . PHP_EOL, FILE_APPEND);
				return false;
			}
		}
		public function revoke_alltoken($id_token,$type_revoke,$is_notlogout=false){
			if($is_notlogout){
				$revokeAllToken = $this->con->prepare("UPDATE gctoken SET at_is_revoke = :type_revoke,at_expire_date = NOW(),
											rt_is_revoke = :type_revoke,rt_expire_date = NOW()
											WHERE id_token = :id_token");
				if($revokeAllToken->execute([
					':type_revoke' => $type_revoke,
					':id_token' => $id_token
				])){
					return true;
				}else{
					$arrExecute = [
						':type_revoke' => $type_revoke,
						':id_token' => $id_token
					];
					$arrError = array();
					$arrError["EXECUTE"] = $arrExecute;
					$arrError["QUERY"] = $revokeAllToken;
					$arrError["COMPONENT"] = "Revoke all token";
					file_put_contents(__DIR__.'/../log/logout_error.txt', json_encode($arrError) . PHP_EOL, FILE_APPEND);
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
				$revokeAllToken = $this->con->prepare("UPDATE gctoken SET at_is_revoke = :type_revoke,at_expire_date = NOW(),
												rt_is_revoke = :type_revoke,rt_expire_date = NOW()
												WHERE id_token = :id_token");
				$forceLogout = $this->con->prepare("UPDATE gcuserlogin SET is_login = :type_login,logout_date = NOW()
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
					$arrExecute = [
						':type_revoke' => $type_revoke,
						':id_token' => $id_token,
						':type_login' => $type_login
					];
					$arrError = array();
					$arrError["EXECUTE"] = $arrExecute;
					$arrError["QUERY"] = $revokeAllToken;
					$arrError["QUERY_LOGOUT"] = $forceLogout;
					$arrError["COMPONENT"] = "Revoke all token";
					file_put_contents(__DIR__.'/../log/logout_error.txt', json_encode($arrError) . PHP_EOL, FILE_APPEND);
					return false;
				}
			}
		}
		public function revoke_accesstoken($id_token,$type_revoke){
			$revokeAT = $this->con->prepare("UPDATE gctoken SET at_is_revoke = :type_revoke,at_expire_date = NOW() WHERE id_token = :id_token");
			if($revokeAT->execute([
				':type_revoke' => $type_revoke,
				':id_token' => $id_token
			])){
				return true;
			}else{
				$arrExecute = [
					':type_revoke' => $type_revoke,
					':id_token' => $id_token
				];
				$arrError = array();
				$arrError["EXECUTE"] = $arrExecute;
				$arrError["QUERY"] = $revokeAT;
				$arrError["COMPONENT"] = "Revoke access token";
				file_put_contents(__DIR__.'/../log/logout_error.txt', json_encode($arrError) . PHP_EOL, FILE_APPEND);
				return false;
			}
		}
		public function revoke_refreshtoken($id_token,$type_revoke){
			$revokeRT = $this->con->prepare("UPDATE gctoken SET rt_is_revoke = :type_revoke,rt_expire_date = NOW() WHERE id_token = :id_token");
			if($revokeRT->execute([
				':type_revoke' => $type_revoke,
				':id_token' => $id_token
			])){
				return true;
			}else{
				$arrExecute = [
					':type_revoke' => $type_revoke,
					':id_token' => $id_token
				];
				$arrError = array();
				$arrError["EXECUTE"] = $arrExecute;
				$arrError["QUERY"] = $revokeRT;
				$arrError["COMPONENT"] = "Revoke refresh token";
				file_put_contents(__DIR__.'/../log/logout_error.txt', json_encode($arrError) . PHP_EOL, FILE_APPEND);
				return false;
			}
		}
		public function check_permission($user_type,$menu_component,$service_component=null){
			require('validate_input.php');
			$permission = array();
			switch($user_type){
				case '0' : 
					$permission[] = "'0'";
					break;
				case '1' : 
					$permission[] = "'0'";
					$permission[] = "'1'";
					break;
				case '5' : 
					$permission[] = "'0'";
					$permission[] = "'1'";
					$permission[] = "'2'";
					break;
				case '9' : 
					$permission[] = "'0'";
					$permission[] = "'1'";
					$permission[] = "'2'";
					$permission[] = "'3'";
					break;
				default : $permission[] = "'0'";
					break;
			}
			if($user_type == '5' || $user_type == '9'){
				$checkPermission = $this->con->prepare("SELECT id_menu FROM gcmenu WHERE menu_component = :menu_component 
										 and menu_permission IN (".implode(',',$permission).") ");
				$checkPermission->execute([
					':menu_component' => $menu_component
				]);
			}else if($user_type == '1'){
				$checkPermission = $this->con->prepare("SELECT gm.id_menu FROM gcmenu gm LEFT JOIN gcmenu gm2 ON gm.menu_parent = gm2.id_menu 
										WHERE gm.menu_component = :menu_component and (gm2.menu_status IN('0','1') OR gm.menu_parent IN('0','-1','-2','-8','-9'))
										 and gm.menu_status IN('0','1') and gm.menu_permission IN (".implode(',',$permission).")");
				$checkPermission->execute([
					':menu_component' => $menu_component
				]);
			}else{
				$checkPermission = $this->con->prepare("SELECT gm.id_menu FROM gcmenu gm LEFT JOIN gcmenu gm2 ON gm.menu_parent = gm2.id_menu 
										WHERE gm.menu_component = :menu_component and (gm2.menu_status = '1' OR gm.menu_parent IN('0','-1','-2','-8','-9'))
										 and gm.menu_status = '1' and gm.menu_permission IN (".implode(',',$permission).") and (gm.menu_channel = :channel OR gm.menu_channel = 'both')");
				$checkPermission->execute([
					':menu_component' => $menu_component,
					':channel' => $dataComing["channel"]
				]);
			}
			if($checkPermission->rowCount() > 0 && $menu_component == $service_component){
				return true;
			}else{
				return false;
			}
		}
		public function getConstant($constant) {
			$getLimit = $this->con->prepare("SELECT constant_value FROM gcconstant WHERE constant_name = :constant and is_use = '1'");
			$getLimit->execute([':constant' => $constant]);
			if($getLimit->rowCount() > 0){
				$rowLimit = $getLimit->fetch(\PDO::FETCH_ASSOC);
				return $rowLimit["constant_value"];
			}else{
				return false;
			}
		}
		public function getPathpic($member_no){
			$getAvatar = $this->con->prepare("SELECT path_avatar FROM gcmemberaccount WHERE member_no = :member_no and path_avatar IS NOT NULL");
			$getAvatar->execute([':member_no' => $member_no]);
			if($getAvatar->rowCount() > 0){
				$rowPathpic = $getAvatar->fetch(\PDO::FETCH_ASSOC);
				$returnResult["AVATAR_PATH"] = $rowPathpic["path_avatar"];
				$explodePathAvatar = explode('.',$rowPathpic["path_avatar"]);
				$returnResult["AVATAR_PATH_WEBP"] = $explodePathAvatar[0].'.webp';
			}else{
				$returnResult["AVATAR_PATH"] = null;
				$returnResult["AVATAR_PATH_WEBP"] = null;
			}
			return $returnResult;
		}
		public function getTemplateSystem($component_system,$seq_no='1'){
			$getTemplatedata = $this->con->prepare("SELECT subject,body 
													FROM smssystemtemplate WHERE component_system = :component_system and is_use = '1' and seq_no = :seq_no");
			$getTemplatedata->execute([
				':component_system' => $component_system,
				':seq_no' => $seq_no
			]);
			$rowTemplate = $getTemplatedata->fetch(\PDO::FETCH_ASSOC);
			$arrayResult = array();
			$arrayResult["SUBJECT"] = $rowTemplate["subject"];
			$arrayResult["BODY"] = $rowTemplate["body"];
			return $arrayResult;
		}
		public function insertHistory($payload,$type_history='1') {
			$this->con->beginTransaction();
			if($payload["TYPE_SEND_HISTORY"] == "onemessage"){
				$bulkInsert = array();
				foreach($payload["MEMBER_NO"] as $member_no){
					$bulkInsert[] = "('".$type_history."','".$payload["PAYLOAD"]["SUBJECT"]."','".$payload["PAYLOAD"]["BODY"]."','".$payload["PAYLOAD"]["PATH_IMAGE"]."','".$member_no."')";
					if(sizeof($bulkInsert) == 1000){
						$insertHis = $this->con->prepare("INSERT INTO gchistory(his_type,his_title,his_detail,his_path_image,member_no) 
												VALUES".implode(',',$bulkInsert));
						if($insertHis->execute()){
							unset($bulkInsert);
							$bulkInsert = array();
							continue;
						}else{
							$this->con->rollback();
							return false;
						}
					}
				}
				if(sizeof($bulkInsert) > 0){
					$insertHis = $this->con->prepare("INSERT INTO gchistory(his_type,his_title,his_detail,his_path_image,member_no) 
												VALUES".implode(',',$bulkInsert));
					if($insertHis->execute()){
						$this->con->commit();
						return true;
					}else{
						$this->con->rollback();
						return false;
					}
				}else{
					$this->con->commit();
					return true;
				}
			}else if($payload["TYPE_SEND_HISTORY"] == "manymessage"){
				$insertHis = $this->con->prepare("INSERT INTO gchistory(his_type,his_title,his_detail,his_path_image,member_no) 
												VALUES".implode(',',$payload["bulkInsert"]));
				if($insertHis->execute()){
					$this->con->commit();
					return true;
				}else{
					$this->con->rollback();
					return false;
				}
			}else{
				return true;
			}
		}
		public function check_permission_core($payload,$root_menu,$page_name=null){
			if(isset($payload["section_system"]) && isset($payload["username"])){
				if($payload["section_system"] == "root" || $payload["section_system"] == "root_test"){
					return true;
				}else{
					if(isset($page_name)){
						$getConstructorMenu = $this->con->prepare("SELECT cm.id_coremenu FROM corepermissionmenu cpm LEFT JOIN coremenu cm ON cpm.id_coremenu = cm.id_coremenu
															WHERE cpm.is_use = '1' and cm.coremenu_status = '1' and cpm.username = :username and cm.root_path = :root_menu");
						$getConstructorMenu->execute([
							':username' => $payload["username"],
							':root_menu' => $root_menu
						]);
						if($getConstructorMenu->rowCount() > 0){
							$rowrootMenu = $getConstructorMenu->fetch(\PDO::FETCH_ASSOC);
							$checkMenuinRoot = $this->con->prepare("SELECT csm.id_submenu FROM coresubmenu csm LEFT JOIN corepermissionsubmenu cpsm ON csm.id_submenu = cpsm.id_submenu
																WHERE cpsm.is_use = '1' and csm.id_coremenu = :id_coremenu and csm.menu_status = '1' and csm.page_name = :page_name");
							$checkMenuinRoot->execute([
								':id_coremenu' => $rowrootMenu["id_coremenu"],
								':page_name' => $page_name
							]);
							if($checkMenuinRoot->rowCount() > 0){
								return true;
							}else{
								return false;
							}
						}else{
							return false;
						}
					}else{
						$checkPermit = $this->con->prepare("SELECT cm.id_coremenu FROM corepermissionmenu cpm LEFT JOIN coremenu cm ON cpm.id_coremenu = cm.id_coremenu
														WHERE cpm.is_use = '1' and cm.coremenu_status = '1' and cpm.username = :username and cm.root_path = :root_menu");
						$checkPermit->execute([
							':username' => $payload["username"],
							':root_menu' => $root_menu
						]);
						if($checkPermit->rowCount() > 0){
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
		
		public function getFCMToken($type_target,$member_no=null){
			$arrayMemberGRP = array();
			$arrayMember = array();
			$arrayAll = array();
			if($type_target == 'person'){
				if(isset($member_no) && $member_no != ""){
					$fetchFCMToken = $this->con->prepare("SELECT fcm_token,receive_notify_news,receive_notify_transaction,member_no FROM gcmemberaccount WHERE member_no IN('".implode("','",$member_no)."')");
					$fetchFCMToken->execute();
					while($rowFCMToken = $fetchFCMToken->fetch(\PDO::FETCH_ASSOC)){
						if(!in_array($rowFCMToken["member_no"],$arrayMember)){
							$arrayMT = array();
							$arrayMT["TOKEN"] = $rowFCMToken["fcm_token"];
							$arrayMT["MEMBER_NO"] = $rowFCMToken["member_no"];
							$arrayMT["RECEIVE_NOTIFY_NEWS"] = $rowFCMToken["receive_notify_news"];
							$arrayMT["RECEIVE_NOTIFY_TRANSACTION"] = $rowFCMToken["receive_notify_transaction"];
							$arrayMember[] = $rowFCMToken["member_no"];
							$arrayMemberGRP[] = $arrayMT;
						}
					}
				}
			}else{
				$fetchFCMToken = $this->con->prepare("SELECT fcm_token,receive_notify_news,member_no FROM gcmemberaccount");
				$fetchFCMToken->execute();
				while($rowFCMToken = $fetchFCMToken->fetch(\PDO::FETCH_ASSOC)){
					if(!in_array($rowFCMToken["member_no"],$arrayMember)){
						$arrayMT = array();
						$arrayMT["TOKEN"] = $rowFCMToken["fcm_token"];
						$arrayMT["MEMBER_NO"] = $rowFCMToken["member_no"];
						$arrayMT["RECEIVE_NOTIFY_NEWS"] = $rowFCMToken["receive_notify_news"];
						$arrayMember[] = $rowFCMToken["member_no"];
						$arrayMemberGRP[] = $arrayMT;
					}
				}
			}
			$arrayAll["MEMBER_NO"] = $arrayMember;
			$arrayAll["LIST_SEND"] = $arrayMemberGRP;
			return $arrayAll;
		}
		
		public function getSMSPerson($type_target,$member_no=null,$trans_flag=false,$check_tel=false){
			$arrayMember = array();
			$arrayMemberGRP = array();
			if($type_target == 'person'){
				if($trans_flag){
					$fetchMemberAllow = $this->con->prepare("SELECT smscsp_member FROM smsconstantperson WHERE is_use = '1' and smscsp_member IN('".implode("','",$member_no)."') ");
					$fetchMemberAllow->execute();
					while($rowMember = $fetchMemberAllow->fetch(\PDO::FETCH_ASSOC)){
						$arrayMemberTemp[] = "'".$rowMember["smscsp_member"]."'";
					}
					if(sizeof($arrayMemberTemp) > 0){
						$fetchDataOra = $this->conora->prepare("SELECT MEM_TELMOBILE,MEMBER_NO FROM mbmembmaster WHERE member_no IN(".implode(',',$arrayMemberTemp).") and
																resign_status = 0 and MEM_TELMOBILE IS NOT NULL");
						$fetchDataOra->execute();
						while($rowDataOra = $fetchDataOra->fetch(\PDO::FETCH_ASSOC)){
							if(isset($rowDataOra["MEM_TELMOBILE"])){
									$arrayMT = array();
									$arrayMT["TEL"] = $rowDataOra["MEM_TELMOBILE"];
									$arrayMT["MEMBER_NO"] = $rowDataOra["MEMBER_NO"];
									$arrayMember[] = $rowDataOra["MEMBER_NO"];
									$arrayMemberGRP[] = $arrayMT;
							}
						}
					}
				}else{
					$fetchDataOra = $this->conora->prepare("SELECT MEM_TELMOBILE,MEMBER_NO FROM mbmembmaster WHERE member_no IN(".implode(',',$member_no).")");
					$fetchDataOra->execute();
					while($rowDataOra = $fetchDataOra->fetch(\PDO::FETCH_ASSOC)){
						if($check_tel){
							if(isset($rowDataOra["MEM_TELMOBILE"])){
									$arrayMT = array();
									$arrayMT["TEL"] = $rowDataOra["MEM_TELMOBILE"];
									$arrayMT["MEMBER_NO"] = $rowDataOra["MEMBER_NO"];
									$arrayMember[] = $rowDataOra["MEMBER_NO"];
									$arrayMemberGRP[] = $arrayMT;
							}
						}else{
								$arrayMT = array();
								$arrayMT["TEL"] = $rowDataOra["MEM_TELMOBILE"];
								$arrayMT["MEMBER_NO"] = $rowDataOra["MEMBER_NO"];
								$arrayMember[] = $rowDataOra["MEMBER_NO"];
								$arrayMemberGRP[] = $arrayMT;
						}
					}
				}
			}else{
				$fetchDataOra = $this->conora->prepare("SELECT MEM_TELMOBILE,MEMBER_NO FROM mbmembmaster WHERE resign_status = '0' and rownum <= 10");
				$fetchDataOra->execute();
				while($rowDataOra = $fetchDataOra->fetch(\PDO::FETCH_ASSOC)){
						$arrayMT = array();
						$arrayMT["TEL"] = $rowDataOra["MEM_TELMOBILE"];
						$arrayMT["MEMBER_NO"] = $rowDataOra["MEMBER_NO"];
						$arrayMember[] = $rowDataOra["MEMBER_NO"];
						$arrayMemberGRP[] = $arrayMT;
				}
			}
			return $arrayMemberGRP;
		}
		public function logSMSWasSent($id_smstemplate=null,$message,$destination,$send_by,$multi_message=false,$trans_flag=false,$payment_keep=false) {
			$this->con->beginTransaction();
			$textcombine = array();
			$textcombinenotsent = array();
			if($trans_flag){
				if($payment_keep){
					
				}
			}else{
				if($multi_message){
					foreach($destination as $dest){
						$textcombine[] = "('".$message[$dest["MEMBER_NO"]]."','".($dest["MEMBER_NO"] ?? null)."','".($dest["TEL"] ?? null)."','".$send_by."'".(isset($id_smstemplate) ? ",".$id_smstemplate : ",null").")";
						if(sizeof($textcombine) == 1000){
							$insertToLogSMS = $this->con->prepare("INSERT INTO smslogwassent(sms_message,member_no,tel_mobile,send_by,id_smstemplate)
																VALUES".implode(',',$textcombine));
							if($insertToLogSMS->execute()){
								unset($textcombine);
								$textcombine = array();
								continue;
							}else{
								$this->con->rollback();
								break;
							}
						}
					}
					if(sizeof($textcombine) > 0){
						$insertToLogSMS = $this->con->prepare("INSERT INTO smslogwassent(sms_message,member_no,tel_mobile,send_by,id_smstemplate)
																VALUES".implode(',',$textcombine));
						if($insertToLogSMS->execute()){
							$this->con->commit();
							return true;
						}else{
							$this->con->rollback();
							return false;
						}
					}else{
						$this->con->commit();
						return true;
					}
				}else{
					foreach($destination as $dest){
						if(isset($dest["TEL"]) && $dest["TEL"] != ""){
							$textcombine[] = "('".$message."','".$dest["MEMBER_NO"]."','".$dest["TEL"]."','".$send_by."'".(isset($id_smstemplate) ? ",".$id_smstemplate : ",null").")";
						}else{
							$textcombinenotsent[] = "('".$message."','".$dest["MEMBER_NO"]."','sms','ไม่พบเบอร์โทรศัพท์','".$send_by."'".(isset($id_smstemplate) ? ",".$id_smstemplate : ",null").")";
						}
						if(sizeof($textcombine) == 1000){
							$insertToLogSMS = $this->con->prepare("INSERT INTO smslogwassent(sms_message,member_no,tel_mobile,send_by,id_smstemplate)
																VALUES".implode(',',$textcombine));
							if($insertToLogSMS->execute()){
								unset($textcombine);
								$textcombine = array();
								continue;
							}else{
								$this->con->rollback();
								return false;
							}
						}
						if(sizeof($textcombinenotsent) == 1000){
							$insertToLogNotSentSMS = $this->con->prepare("INSERT INTO smswasnotsent(message,member_no,send_platform,cause_notsent,send_by,id_smstemplate)
																	VALUES".implode(',',$textcombinenotsent));
							if($insertToLogNotSentSMS->execute()){
								unset($textcombinenotsent);
								$textcombinenotsent = array();
								continue;
							}else{
								$this->con->rollback();
								return false;
							}
						}
					}
					if(sizeof($textcombine) > 0){
						$insertToLogSMS = $this->con->prepare("INSERT INTO smslogwassent(sms_message,member_no,tel_mobile,send_by,id_smstemplate)
																VALUES".implode(',',$textcombine));
						if($insertToLogSMS->execute()){
							if(sizeof($textcombinenotsent) > 0){
								$insertToLogNotSentSMS = $this->con->prepare("INSERT INTO smswasnotsent(message,member_no,send_platform,cause_notsent,send_by,id_smstemplate)
																		VALUES".implode(',',$textcombinenotsent));
								if($insertToLogNotSentSMS->execute()){
									$this->con->commit();
									return true;
								}else{
									$this->con->rollback();
									return false;
								}
							}else{
								$this->con->commit();
								return true;
							}
						}else{
							$this->con->rollback();
							return false;
						}
					}else{
						if(sizeof($textcombinenotsent) > 0){
							$insertToLogNotSentSMS = $this->con->prepare("INSERT INTO smswasnotsent(message,member_no,send_platform,cause_notsent,send_by,id_smstemplate)
																		VALUES".implode(',',$textcombinenotsent));
							if($insertToLogNotSentSMS->execute()){
								$this->con->commit();
									return true;
							}else{
								$this->con->rollback();
								return false;
							}
						}else{
							$this->con->commit();
							return true;
						}
					}
				}
			}
		}
		public function logSMSWasNotSent($bulkInsert,$multi_message=false) {
			$this->con->beginTransaction();
			if($multi_message){
				return true;
			}else{
				$insertToLogSMS = $this->con->prepare("INSERT INTO smswasnotsent(message,member_no,send_platform,tel_mobile,fcm_token,cause_notsent,send_by,id_smstemplate)
														VALUES".implode(',',$bulkInsert));
				if($insertToLogSMS->execute()){
					$this->con->commit();
					return true;
				}else{
					$this->con->rollback();
					return false;
				}
			}
		}
}
?>