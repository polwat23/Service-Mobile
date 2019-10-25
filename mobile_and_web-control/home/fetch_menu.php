<?php
require_once('../autoload.php');

if(isset($dataComing["channel"])){
	$status_token = $api->validate_jwttoken($author_token,$payload["exp"],$jwt_token,$config["SECRET_KEY_JWT"]);
	if($status_token){
		$user_type = $payload["user_type"];
		$permission = array();
		$arrayResult = array();
		$arrayAllMenu = array();
		$arrayMenuSetting = array();
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
		if(isset($dataComing["menu_parent"])){
			if($user_type == '5' || $user_type == '9'){
				$fetch_menu = $conmysql->prepare("SELECT id_menu,menu_name,menu_icon_path,menu_component,menu_status,menu_version FROM gcmenu 
												WHERE menu_permission IN (".implode(',',$permission).") and menu_parent = :menu_parent ORDER BY menu_order ASC");
			}else{
				$fetch_menu = $conmysql->prepare("SELECT id_menu,menu_name,menu_icon_path,menu_component,menu_status,menu_version FROM gcmenu 
												WHERE menu_permission IN (".implode(',',$permission).") and menu_parent = :menu_parent and menu_status = '1' 
												ORDER BY menu_order ASC");
			}
			$fetch_menu->execute([
				':menu_parent' => $dataComing["menu_parent"]
			]);
			while($rowMenu = $fetch_menu->fetch()){
				if($dataComing["channel"] == 'mobile_app'){
					if(preg_replace('/\./','',$dataComing["app_version"]) >= preg_replace('/\./','',$rowMenu["menu_version"]) || $user_type == '5' || $user_type == '9'){
						$arrMenu = array();
						$arrMenu["ID_MENU"] = $rowMenu["id_menu"];
						$arrMenu["MENU_NAME"] = $rowMenu["menu_name"];
						$arrMenu["MENU_ICON_PATH"] = $rowMenu["menu_icon_path"];
						$arrMenu["MENU_COMPONENT"] = $rowMenu["menu_component"];
						$arrMenu["MENU_STATUS"] = $rowMenu["menu_status"];
						$arrMenu["MENU_VERSION"] = $rowMenu["menu_version"];
						$arrayAllMenu[] = $arrMenu;
					}
				}else{
					$arrMenu = array();
					$arrMenu["ID_MENU"] = $rowMenu["id_menu"];
					$arrMenu["MENU_NAME"] = $rowMenu["menu_name"];
					$arrMenu["MENU_ICON_PATH"] = $rowMenu["menu_icon_path"];
					$arrMenu["MENU_COMPONENT"] = $rowMenu["menu_component"];
					$arrMenu["MENU_STATUS"] = $rowMenu["menu_status"];
					$arrMenu["MENU_VERSION"] = $rowMenu["menu_version"];
					$arrayAllMenu[] = $arrMenu;
				}
			}
			$arrayResult['MENU'] = $arrayAllMenu;
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			if($user_type == '5' || $user_type == '9'){
				$fetch_menu = $conmysql->prepare("SELECT id_menu,menu_name,menu_icon_path,menu_component,menu_parent,menu_status,menu_version FROM gcmenu 
												WHERE menu_permission IN (".implode(',',$permission).") and menu_parent IN('0','24') ORDER BY menu_order ASC");
			}else{
				$fetch_menu = $conmysql->prepare("SELECT id_menu,menu_name,menu_icon_path,menu_component,menu_parent,menu_status,menu_version FROM gcmenu 
												WHERE menu_permission IN (".implode(',',$permission).") and menu_parent IN('0','24') and menu_status = '1' ORDER BY menu_order ASC");
			}
			$fetch_menu->execute();
			while($rowMenu = $fetch_menu->fetch()){
				if($dataComing["channel"] == 'mobile_app'){
					if(preg_replace('/\./','',$dataComing["app_version"]) >= preg_replace('/\./','',$rowMenu["menu_version"]) || $user_type == '5' || $user_type == '9'){
						$arrMenu = array();
						$arrMenu["ID_MENU"] = $rowMenu["id_menu"];
						$arrMenu["MENU_NAME"] = $rowMenu["menu_name"];
						$arrMenu["MENU_ICON_PATH"] = $rowMenu["menu_icon_path"];
						$arrMenu["MENU_COMPONENT"] = $rowMenu["menu_component"];
						$arrMenu["MENU_STATUS"] = $rowMenu["menu_status"];
						$arrMenu["MENU_VERSION"] = $rowMenu["menu_version"];
						if($rowMenu["menu_parent"] == '0'){
							$arrayAllMenu[] = $arrMenu;
						}else if($rowMenu["menu_parent"] == '24'){
							$arrayMenuSetting[] = $arrMenu;
						}
					}
				}else{
					$arrMenu = array();
					$arrMenu["ID_MENU"] = $rowMenu["id_menu"];
					$arrMenu["MENU_NAME"] = $rowMenu["menu_name"];
					$arrMenu["MENU_ICON_PATH"] = $rowMenu["menu_icon_path"];
					$arrMenu["MENU_COMPONENT"] = $rowMenu["menu_component"];
					$arrMenu["MENU_STATUS"] = $rowMenu["menu_status"];
					$arrMenu["MENU_VERSION"] = $rowMenu["menu_version"];
					if($rowMenu["menu_parent"] == '0'){
						$arrayAllMenu[] = $arrMenu;
					}else if($rowMenu["menu_parent"] == '24'){
						$arrayMenuSetting[] = $arrMenu;
					}
				}
			}
			$arrayResult['MENU_HOME'] = $arrayAllMenu;
			$arrayResult['MENU_SETTING'] = $arrayMenuSetting;
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}
	}else{
		$arrayAllMenu = array();
		$fetch_menu = $conmysql->prepare("SELECT id_menu,menu_name,menu_icon_path,menu_component,menu_status,menu_version FROM gcmenu 
											WHERE menu_parent IN ('-1','-2')");
		$fetch_menu->execute();
		while($rowMenu = $fetch_menu->fetch()){
			if($dataComing["channel"] == 'mobile_app'){
				if(preg_replace('/\./','',$dataComing["app_version"]) >= preg_replace('/\./','',$rowMenu["menu_version"])){
					$arrMenu = array();
					$arrMenu["ID_MENU"] = $rowMenu["id_menu"];
					$arrMenu["MENU_NAME"] = $rowMenu["menu_name"];
					$arrMenu["MENU_ICON_PATH"] = $rowMenu["menu_icon_path"];
					$arrMenu["MENU_COMPONENT"] = $rowMenu["menu_component"];
					$arrMenu["MENU_STATUS"] = $rowMenu["menu_status"];
					$arrMenu["MENU_VERSION"] = $rowMenu["menu_version"];
					$arrayAllMenu[] = $arrMenu;
				}
			}else{
				$arrMenu = array();
				$arrMenu["ID_MENU"] = $rowMenu["id_menu"];
				$arrMenu["MENU_NAME"] = $rowMenu["menu_name"];
				$arrMenu["MENU_ICON_PATH"] = $rowMenu["menu_icon_path"];
				$arrMenu["MENU_COMPONENT"] = $rowMenu["menu_component"];
				$arrMenu["MENU_STATUS"] = $rowMenu["menu_status"];
				$arrMenu["MENU_VERSION"] = $rowMenu["menu_version"];
				$arrayAllMenu[] = $arrMenu;
			}
		}
		$arrayResult['MENU'] = $arrayAllMenu;
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}
}
?>