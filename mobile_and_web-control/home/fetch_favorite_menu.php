<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['user_type','member_no'],$payload) && $lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],$conmysql,'FavoriteAccount')){
		$arrGroupFavmenu = array();
		$fetchFavMenu = $conmysql->prepare("SELECT gfl.fav_name,gpc.type_palette,gpc.color_deg,gpc.color_main,gpc.color_secon
											FROM gcfavoritemenu gfm LEFT JOIN gcpalettecolor gpc ON gfm.id_palette = gpc.id_palette and gpc.is_use = '1'
											LEFT JOIN gcfavoritelist gfl ON gfm.fav_refno = gfl.fav_refno and gfl.is_use = '1'
											WHERE gfl.member_no = :member_no ORDER BY gfm.seq_no ASC");
		$fetchFavMenu->execute([':member_no' => $payload["member_no"]]);
		while($rowFavMenu = $fetchFavMenu->fetch()){
			$arrayFavMenu = array();
			$arrayFavMenu["FAV_NAME_MENU"] = $rowFavMenu["fav_name"];
			$arrayFavMenu["FAV_ICON_MENU"] = mb_substr($rowFavMenu["fav_name"],0,1);
			if($rowFavMenu["type_palette"] == '2'){
				$arrayFavMenu["FAV_COLOR_MENU"] = $rowFavMenu["color_deg"]."|".$rowFavMenu["color_main"].",".$rowFavMenu["color_secon"];
			}else{
				$arrayFavMenu["FAV_COLOR_MENU"] = $rowFavMenu["color_main"];
			}
			$arrGroupFavmenu[] = $arrayFavMenu;
		}
		$arrayResult['FAV_MENU'] = $arrGroupFavmenu;
		if(isset($new_token)){
			$arrayResult['NEW_TOKEN'] = $new_token;
		}
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
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
?>