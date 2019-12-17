<?php
$anonymous = '';
require_once('../autoload.php');

if(!$anonymous){
	if($payload["member_no"] == 'dev@mode'){
		$member_no = $config["MEMBER_NO_DEV_DEPOSIT"];
	}else if($payload["member_no"] == 'salemode'){
		$member_no = $config["MEMBER_NO_SALE_DEPOSIT"];
	}else{
		$member_no = $payload["member_no"];
	}
	$user_type = $payload["user_type"];
	$permission = array();
	$arrayResult = array();
	$arrayAllMenu = array();
	$arrayMenuSetting = array();
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
		default : $permission[] = '0';
			break;
	}
	if(isset($dataComing["menu_parent"])){
		if($user_type == '5' || $user_type == '9'){
			$fetch_menu = $conmysql->prepare("SELECT id_menu,menu_name,menu_icon_path,menu_component,menu_status,menu_version FROM gcmenu 
											WHERE menu_permission IN (".implode(',',$permission).") and menu_parent = :menu_parent and (menu_channel = :channel OR menu_channel = 'both')
											ORDER BY menu_order ASC");
		}else{
			$fetch_menu = $conmysql->prepare("SELECT id_menu,menu_name,menu_icon_path,menu_component,menu_status,menu_version FROM gcmenu 
											WHERE menu_permission IN (".implode(',',$permission).") and menu_parent = :menu_parent and menu_status = '1' 
											and (menu_channel = :channel OR menu_channel = 'both')
											ORDER BY menu_order ASC");
		}
		$fetch_menu->execute([
			':menu_parent' => $dataComing["menu_parent"],
			':channel' => $dataComing["channel"]
		]);
		while($rowMenu = $fetch_menu->fetch()){
			if($dataComing["channel"] == 'mobile_app'){
				if(preg_replace('/\./','',$dataComing["app_version"]) >= preg_replace('/\./','',$rowMenu["menu_version"]) || $user_type == '5' || $user_type == '9'){
					$arrMenu = array();
					$arrMenu["ID_MENU"] = (int) $rowMenu["id_menu"];
					$arrMenu["MENU_NAME"] = $rowMenu["menu_name"];
					$arrMenu["MENU_ICON_PATH"] = $rowMenu["menu_icon_path"];
					$arrMenu["MENU_COMPONENT"] = $rowMenu["menu_component"];
					$arrMenu["MENU_STATUS"] = $rowMenu["menu_status"];
					$arrMenu["MENU_VERSION"] = $rowMenu["menu_version"];
					$arrayAllMenu[] = $arrMenu;
				}
			}else{
				$arrMenu = array();
				$arrMenu["ID_MENU"] = (int) $rowMenu["id_menu"];
				$arrMenu["MENU_NAME"] = $rowMenu["menu_name"];
				$arrMenu["MENU_ICON_PATH"] = $rowMenu["menu_icon_path"];
				$arrMenu["MENU_COMPONENT"] = $rowMenu["menu_component"];
				$arrMenu["MENU_STATUS"] = $rowMenu["menu_status"];
				$arrMenu["MENU_VERSION"] = $rowMenu["menu_version"];
				$arrayAllMenu[] = $arrMenu;
			}
		}
		if(isset($new_token)){
			$arrayResult['NEW_TOKEN'] = $new_token;
		}
		$arrayResult['MENU'] = $arrayAllMenu;
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrMenuDep = array();
		$arrMenuLoan = array();
		$arrayGroupMenu = array();
		$arrayMenuTransaction = array();
		if($user_type == '5' || $user_type == '9'){
			$fetch_menu = $conmysql->prepare("SELECT id_menu,menu_name,menu_icon_path,menu_component,menu_parent,menu_status,menu_version FROM gcmenu 
											WHERE menu_permission IN (".implode(',',$permission).") and menu_parent IN('0','24','18') and (menu_channel = :channel OR menu_channel = 'both')
											ORDER BY menu_order ASC");
		}else{
			$fetch_menu = $conmysql->prepare("SELECT id_menu,menu_name,menu_icon_path,menu_component,menu_parent,menu_status,menu_version FROM gcmenu
											WHERE menu_permission IN (".implode(',',$permission).") and menu_parent IN('0','24','18') and menu_status = '1'
											and (menu_channel = :channel OR menu_channel = 'both') ORDER BY menu_order ASC");
		}
		$fetch_menu->execute([
			':channel' => $dataComing["channel"]
		]);
		while($rowMenu = $fetch_menu->fetch()){
			if($dataComing["channel"] == 'mobile_app'){
				if(preg_replace('/\./','',$dataComing["app_version"]) >= preg_replace('/\./','',$rowMenu["menu_version"]) || $user_type == '5' || $user_type == '9'){
					$arrMenu = array();
					$arrMenu["ID_MENU"] = (int) $rowMenu["id_menu"];
					$arrMenu["MENU_NAME"] = $rowMenu["menu_name"];
					$arrMenu["MENU_ICON_PATH"] = $rowMenu["menu_icon_path"];
					$arrMenu["MENU_COMPONENT"] = $rowMenu["menu_component"];
					$arrMenu["MENU_STATUS"] = $rowMenu["menu_status"];
					$arrMenu["MENU_VERSION"] = $rowMenu["menu_version"];
					if($rowMenu["menu_parent"] == '0'){
						$arrayGroupMenu["ID_PARENT"] = $rowMenu["menu_parent"];
						$arrayGroupMenu["MENU"][] = $arrMenu;
					}else if($rowMenu["menu_parent"] == '24'){
						$arrayMenuSetting[] = $arrMenu;
					}else if($rowMenu["menu_parent"] == '18'){
						$arrayMenuTransaction["ID_PARENT"] = $rowMenu["menu_parent"];
						$arrayMenuTransaction["MENU"][] = $arrMenu;
					}
					if($rowMenu["id_menu"] == 1){
						$fetchMenuDep = $conoracle->prepare("SELECT SUM(prncbal) as BALANCE,COUNT(deptaccount_no) as C_ACCOUNT FROM dpdeptmaster WHERE member_no = :member_no and deptclose_status = 0");
						$fetchMenuDep->execute([':member_no' => $member_no]);
						$rowMenuDep = $fetchMenuDep->fetch();
						$arrMenuDep["BALANCE"] = number_format($rowMenuDep["BALANCE"],2);
						$arrMenuDep["AMT_ACCOUNT"] = $rowMenuDep["C_ACCOUNT"] ?? 0;
					}else if($rowMenu["id_menu"] == 2){
						$fetchMenuLoan = $conoracle->prepare("SELECT SUM(PRINCIPAL_BALANCE) as BALANCE,COUNT(loancontract_no) as C_CONTRACT FROM lncontmaster WHERE member_no = :member_no and contract_status = 1");
						$fetchMenuLoan->execute([':member_no' => $member_no]);
						$rowMenuLoan = $fetchMenuLoan->fetch();
						$arrMenuLoan["BALANCE"] = number_format($rowMenuLoan["BALANCE"],2);
						$arrMenuLoan["AMT_CONTRACT"] = $rowMenuLoan["C_CONTRACT"] ?? 0;
					}					
				}
			}else{
				$arrMenu = array();
				$arrMenu["ID_MENU"] = (int) $rowMenu["id_menu"];
				$arrMenu["MENU_NAME"] = $rowMenu["menu_name"];
				$arrMenu["MENU_ICON_PATH"] = $rowMenu["menu_icon_path"];
				$arrMenu["MENU_COMPONENT"] = $rowMenu["menu_component"];
				$arrMenu["MENU_STATUS"] = $rowMenu["menu_status"];
				$arrMenu["MENU_VERSION"] = $rowMenu["menu_version"];
				if($rowMenu["menu_parent"] == '0'){
					$arrayAllMenu[] = $arrMenu;
				}else if($rowMenu["menu_parent"] == '24'){
					$arrayMenuSetting[] = $arrMenu;
				}else if($rowMenu["menu_parent"] == '18'){
					$arrayMenuTransaction[] = $arrMenu;
				}
			}
		}
		if($dataComing["channel"] == 'mobile_app'){
			$arrayGroupMenu["TEXT_HEADER"] = "ทั่วไป";
			$arrayMenuTransaction["TEXT_HEADER"] = "ธุรกรรม";
			$arrayGroupAllMenu[] = $arrayGroupMenu;
			$arrayGroupAllMenu[] = $arrayMenuTransaction;
			$arrayAllMenu = $arrayGroupAllMenu;
		}
		$arrFavMenuGroup = array();
		$fetchMenuFav = $conmysql->prepare("SELECT gfm.id_fav_menu,gfl.name_fav,gpc.color_text,gpc.color_main,gpc.color_secon,gpc.color_deg,gpc.type_palette
											FROM gcfavoritemenu gfm LEFT JOIN gcfavoritelist gfl ON gfm.fav_refno = gfl.fav_refno
											LEFT JOIN gcpalettecolor gpc ON gfm.id_palette = gpc.id_palette
											WHERE gfl.member_no = :member_no and gfl.is_use = '1' and gfm.is_show = '1' ORDER BY gfm.seq_no ASC");
		$fetchMenuFav->execute([':member_no' => $member_no]);
		while($rowMenuFav = $fetchMenuFav->fetch()){
			$arrFavMenu = array();
			if(isset($rowMenuFav["type_palette"])){
				if($rowMenuFav["type_palette"] == '2'){
					$arrFavMenu["ACCOUNT_COOP_COLOR"] = $rowMenuFav["color_deg"]."|".$rowMenuFav["color_main"].",".$rowMenuFav["color_secon"];
				}else{
					$arrFavMenu["ACCOUNT_COOP_COLOR"] = "90|".$rowMenuFav["color_main"].",".$rowMenuFav["color_main"];
				}
				$arrFavMenu["ACCOUNT_COOP_TEXT_COLOR"] = $rowMenuFav["color_text"];
			}else{
				$arrFavMenu["ACCOUNT_COOP_COLOR"] = $config["DEFAULT_BANNER_COLOR_DEG"]."|".$config["DEFAULT_BANNER_COLOR_MAIN"].",".$config["DEFAULT_BANNER_COLOR_SECON"];
				$arrFavMenu["ACCOUNT_COOP_TEXT_COLOR"] = $config["DEFAULT_BANNER_COLOR_TEXT"];
			}
			$arrFavMenu["NAME_FAV"] = $rowMenuFav["name_fav"];
			$arrFavMenu["ID_FAV_MENU"] = $rowMenuFav["id_fav_menu"];
			$arrFavMenuGroup[] = $arrFavMenu;
		}
		if(sizeof($arrayAllMenu) > 0 || sizeof($arrayMenuSetting) > 0){
			if(isset($new_token)){
				$arrayResult['NEW_TOKEN'] = $new_token;
			}
			if($dataComing["channel"] == 'mobile_app'){
				$arrayResult['MENU_HOME'] = $arrayAllMenu;
				$arrayResult['MENU_SETTING'] = $arrayMenuSetting;
				$arrayResult['MENU_FAVORITE'] = $arrFavMenuGroup;
				$arrayResult['MENU_DEPOSIT'] = $arrMenuDep;
				$arrayResult['MENU_LOAN'] = $arrMenuLoan;
			}else{
				$arrayResult['MENU_HOME'] = $arrayAllMenu;
				$arrayResult['MENU_SETTING'] = $arrayMenuSetting;
				$arrayResult['MENU_TRANSACTION'] = $arrayMenuTransaction;
				$arrayResult['MENU_FAVORITE'] = $arrFavMenuGroup;
				$arrayResult['MENU_DEPOSIT'] = $arrMenuDep;
				$arrayResult['MENU_LOAN'] = $arrMenuLoan;

			}
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			http_response_code(204);
			exit();
		}
	}
}else{
	if($lib->checkCompleteArgument(['api_token'],$dataComing)){
		$arrPayload = $auth->check_apitoken($dataComing["api_token"],$config["SECRET_KEY_JWT"]);
		if(!$arrPayload["VALIDATE"]){
			$arrayResult['RESPONSE_CODE'] = "WS0001";
			$arrayResult['RESPONSE_MESSAGE'] = $arrPayload["ERROR_MESSAGE"];
			$arrayResult['RESULT'] = FALSE;
			http_response_code(401);
			echo json_encode($arrayResult);
			exit();
		}
		$arrayAllMenu = array();
		$fetch_menu = $conmysql->prepare("SELECT id_menu,menu_name,menu_icon_path,menu_component,menu_status,menu_version FROM gcmenu 
											WHERE menu_parent IN ('-1','-2')");
		$fetch_menu->execute();
		while($rowMenu = $fetch_menu->fetch()){
			if($arrPayload["PAYLOAD"]["channel"] == 'mobile_app'){
				if(preg_replace('/\./','',$dataComing["app_version"]) >= preg_replace('/\./','',$rowMenu["menu_version"])){
					$arrMenu = array();
					$arrMenu["ID_MENU"] = (int) $rowMenu["id_menu"];
					$arrMenu["MENU_NAME"] = $rowMenu["menu_name"];
					$arrMenu["MENU_ICON_PATH"] = $rowMenu["menu_icon_path"];
					$arrMenu["MENU_COMPONENT"] = $rowMenu["menu_component"];
					$arrMenu["MENU_STATUS"] = $rowMenu["menu_status"];
					$arrMenu["MENU_VERSION"] = $rowMenu["menu_version"];
					$arrayAllMenu[] = $arrMenu;
				}
			}else{
				$arrMenu = array();
				$arrMenu["ID_MENU"] = (int) $rowMenu["id_menu"];
				$arrMenu["MENU_NAME"] = $rowMenu["menu_name"];
				$arrMenu["MENU_ICON_PATH"] = $rowMenu["menu_icon_path"];
				$arrMenu["MENU_COMPONENT"] = $rowMenu["menu_component"];
				$arrMenu["MENU_STATUS"] = $rowMenu["menu_status"];
				$arrMenu["MENU_VERSION"] = $rowMenu["menu_version"];
				$arrayAllMenu[] = $arrMenu;
			}
		}
		if(isset($arrayAllMenu)){
			$arrayResult['MENU'] = $arrayAllMenu;
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			http_response_code(204);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS4004";
		$arrayResult['RESPONSE_MESSAGE'] = "Not complete argument";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(400);
		echo json_encode($arrayResult);
		exit();
	}
}
?>