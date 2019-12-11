<?php

namespace Component;

use Connection\connection;

class functions {
		private $con;
		
		function __construct() {
			$connection = new connection();
			$this->con = $connection->connecttomysql();
		}
		
		public function checkLogin($id_token) {
			$checkLogin = $this->con->prepare("SELECT id_userlogin FROM gcuserlogin 
										WHERE id_token = :id_token and is_login = '1'");
			$checkLogin->execute([
				':id_token' => $id_token
			]);
			if($checkLogin->rowCount() > 0){
				return true;
			}else{
				return false;
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
			while($rowMember = $getMemberlogin->fetch()){
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
				return false;
			}
		}
		public function revoke_alltoken($id_token,$type_revoke,$is_logout=false){
			if($is_logout){
				$revokeAllToken = $this->con->prepare("UPDATE gctoken SET at_is_revoke = :type_revoke,at_expire_date = NOW(),
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
					case '-99' : $type_login = '-6';
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
				return false;
			}
		}
		public function check_permission($user_type,$menu_component,$service_component=null){
			if(isset($user_type)){
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
											 and menu_permission IN (".implode(',',$permission).")");
				}else{
					$checkPermission = $this->con->prepare("SELECT id_menu FROM gcmenu WHERE menu_component = :menu_component 
											and menu_status = '1' and menu_permission IN (".implode(',',$permission).")");
				}
				$checkPermission->execute([':menu_component' => $menu_component]);
				if($checkPermission->rowCount() > 0 && $menu_component == $service_component){
					return true;
				}else{
					return false;
				}
			}else{
				$checkPermission = $this->con->prepare("SELECT id_menu FROM gcmenu WHERE menu_component = :menu_component 
											and menu_status = '1' and menu_parent = '-2'");
				$checkPermission->execute([':menu_component' => $menu_component]);
				if($checkPermission->rowCount() > 0 && $menu_component == $service_component){
					return true;
				}else{
					return false;
				}
			}
		}
		public function getConstant($constant) {
			$getLimit = $this->con->prepare("SELECT constant_value FROM gcconstant WHERE constant_name = :constant and is_use = '1'");
			$getLimit->execute([':constant' => $constant]);
			if($getLimit->rowCount() > 0){
				$rowLimit = $getLimit->fetch();
				return $rowLimit["constant_value"];
			}else{
				return false;
			}
		}
		public function getPathpic($member_no){
			$getAvatar = $this->con->prepare("SELECT path_avatar FROM gcmemberaccount WHERE member_no = :member_no");
			$getAvatar->execute([':member_no' => $member_no]);
			if($getAvatar->rowCount() > 0){
				$rowPathpic = $getAvatar->fetch();
				$returnResult["AVATAR_PATH"] = $rowPathpic["path_avatar"];
				$explodePathAvatar = explode('.',$rowPathpic["path_avatar"]);
				$returnResult["AVATAR_PATH_WEBP"] = $explodePathAvatar[0].'.webp';
			}else{
				$returnResult["AVATAR_PATH"] = null;
				$returnResult["AVATAR_PATH_WEBP"] = null;
			}
			return $returnResult;
		}
		public function getTemplate($template_name){
			$getTemplatedata = $this->con->prepare("SELECT template_subject,template_body 
										FROM gctemplate WHERE template_name = :template_name and is_use = '1'");
			$getTemplatedata->execute([':template_name' => $template_name]);
			$rowTemplate = $getTemplatedata->fetch();
			$arrayResult = array();
			$arrayResult["SUBJECT"] = $rowTemplate["template_subject"];
			$arrayResult["BODY"] = $rowTemplate["template_body"];
			return $arrayResult;
		}
		public function insertHistory($payload,$type_history) {
			$this->con->beginTransaction();
			if($payload["TYPE_SEND_HISTORY"] == "onemessage"){
				$bulkInsert = array();
				foreach($payload["MEMBER_NO"] as $member_no){
					$bulkInsert[] = "('".$type_history."','".$payload["PAYLOAD"]["SUBJECT"]."','".$payload["PAYLOAD"]["BODY"]."','".$payload["PAYLOAD"]["PATH_IMAGE"]."','".$member_no."')";
					if(sizeof($bulkInsert) == "1000"){
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
													VALUES(:his_type,:hit_title,:his_detail,:his_path_image,:member_no)");
				if($insertHis->execute([
					':his_type' => $type_history,
					':hit_title' => $payload["PAYLOAD"]["SUBJECT"],
					':his_detail' => $payload["PAYLOAD"]["BODY"],
					':his_path_image' => $payload["PAYLOAD"]["PATH_IMAGE"] ?? null,
					':member_no' => $payload["MEMBER_NO"]
				])){
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
		public function check_permission_core($payload,$root_menu,$page_name){
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
							$rowrootMenu = $getConstructorMenu->fetch();
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
}
?>