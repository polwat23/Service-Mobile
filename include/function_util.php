<?php

namespace Component;

class functions {
		
		public function logout($id_token,$type_login,$con) {
			$logout = $con->prepare("UPDATE gcuserlogin SET is_login = :type_login,logout_date = NOW() WHERE id_token = :id_token");
			if($logout->execute([
				':type_login' => $type_login,
				':id_token' => $id_token
			])){
				$this->revoke_accesstoken($id_token,'-9',$con);
				$this->revoke_refreshtoken($id_token,'-9',$con);
				return true;
			}else{
				return false;
			}
		}
		public function logoutAll($id_token,$member_no,$type_login,$con) {
			$arrMember = array();
			if(isset($id_token)){
				$getMemberlogin = $con->prepare("SELECT id_token FROM gcuserlogin WHERE member_no = :member_no and id_token <> :id_token and is_login = '1'");
				$getMemberlogin->execute([
					':member_no' => $member_no,
					':id_token' => $id_token
				]);
			}else{
				$getMemberlogin = $con->prepare("SELECT id_token FROM gcuserlogin WHERE member_no = :member_no and is_login = '1'");
				$getMemberlogin->execute([
					':member_no' => $member_no
				]);
			}
			while($rowMember = $getMemberlogin->fetch()){
				$arrMember[] = $rowMember["id_token"];
			}
			$logout = $con->prepare("UPDATE gcuserlogin SET is_login = :type_login,logout_date = NOW() 
									WHERE member_no = :member_no and id_token <> :id_token and is_login = '1'");
			if($logout->execute([
				':type_login' => $type_login,
				':member_no' => $member_no,
				':id_token' => $id_token
			])){
				foreach($arrMember as $token_value){
					$this->revoke_accesstoken($token_value,'-9',$con);
					$this->revoke_refreshtoken($token_value,'-9',$con);
				}
				return true;
			}else{
				return false;
			}
		}
		public function revoke_accesstoken($id_token,$type_revoke,$con){
			$revokeAT = $con->prepare("UPDATE gctoken SET at_is_revoke = :type_revoke,at_expire_date = NOW() WHERE id_token = :id_token");
			$revokeAT->execute([
				':type_revoke' => $type_revoke,
				':id_token' => $id_token
			]);
			if($revokeAT->rowCount() > 0){
				return true;
			}else{
				return false;
			}
		}
		public function revoke_refreshtoken($id_token,$type_revoke,$con){
			$revokeRT = $con->prepare("UPDATE gctoken SET rt_is_revoke = :type_revoke,rt_expire_date = NOW() WHERE id_token = :id_token");
			$revokeRT->execute([
				':type_revoke' => $type_revoke,
				':id_token' => $id_token
			]);
			if($revokeRT->rowCount() > 0){
				return true;
			}else{
				return false;
			}
		}
		public function check_permission($user_type,$menu_component,$con,$service_component=null){
			$permission = array();
			switch($user_type){ 
				case '0' : 
					$permission[] = '0';
					break;
				case '1' : 
					$permission[] = '0';
					$permission[] = '1';
					break;
				case '5' : 
					$permission[] = '0';
					$permission[] = '1';
					$permission[] = '2';
					break;
				case '9' : 
					$permission[] = '0';
					$permission[] = '1';
					$permission[] = '2';
					$permission[] = '3';
					break;
				default : $permission[] = '0';
					break;
			}
			if($user_type == '5' || $user_type == '9'){
				$checkPermission = $con->prepare("SELECT id_menu FROM gcmenu WHERE menu_component = :menu_component 
										 and menu_permission IN (".implode(',',$permission).")");
			}else{
				$checkPermission = $con->prepare("SELECT id_menu FROM gcmenu WHERE menu_component = :menu_component 
										and menu_status = 1 and menu_permission IN (".implode(',',$permission).")");
			}
			$checkPermission->execute([':menu_component' => $menu_component]);
			if($checkPermission->rowCount() > 0 && $menu_component == $service_component){
				return true;
			}else{
				return false;
			}
		}
		public function getConstant($component,$con) {
			$getLimit = $con->prepare("SELECT constant_value FROM gcconstant WHERE constant_name = :component and is_use = '1'");
			$getLimit->execute([':component' => $component]);
			if($getLimit->rowCount() > 0){
				$rowLimit = $getLimit->fetch();
				return $rowLimit["constant_value"];
			}else{
				return false;
			}
		}
		public function getPathpic($member_no,$con){
			$getAvatar = $con->prepare("SELECT path_avatar FROM gcmemberaccount WHERE member_no = :member_no");
			$getAvatar->execute([':member_no' => $member_no]);
			$rowPathpic = $getAvatar->fetch();
			return $rowPathpic["path_avatar"];
		}
		public function getTemplate($template_name,$con){
			$getTemplatedata = $con->prepare("SELECT template_subject,template_body 
										FROM gctemplate WHERE template_name = :template_name and is_use = '1'");
			$getTemplatedata->execute([':template_name' => $template_name]);
			$rowTemplate = $getTemplatedata->fetch();
			$arrayResult = array();
			$arrayResult["SUBJECT"] = $rowTemplate["template_subject"];
			$arrayResult["BODY"] = $rowTemplate["template_body"];
			return $arrayResult;
		}
		public function insertHistory($payload,$type_history,$con) {
			$insertHis = $con->prepare("INSERT INTO gchistory(his_type,his_title,his_detail,his_path_image,member_no) 
										VALUES(:his_type,:title,:detail,:path_image,:member_no)");
			if($insertHis->execute([
				':his_type' => $type_history,
				':title' => $payload["title"],
				':detail' => $payload["detail"],
				':path_image' => $payload["path_image"],
				':member_no' => $payload["member_no"]
			])){
				return true;
			}else{
				return false;
			}
		}
		public function check_permission_core($section_system,$root_menu,$con){
			if($section_system == "root" || $section_system == "root_test"){
				return true;
			}else{
				return false;
			}
		}
}
?>