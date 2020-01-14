<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','settingpalette',$conmysql)){
		$arrayGroup = array();
		$fetchPalette = $conmysql->prepare("SELECT id_palette,type_palette,color_main,color_secon,color_deg,color_text
											 FROM gcpalettecolor WHERE is_use = '1'");
		$fetchPalette->execute();
		while($rowPalette = $fetchPalette->fetch()){
			$arrPalette = array();
			$arrPalette["ID_PALETTE"] = $rowPalette["id_palette"];
			$arrPalette["TYPE_PALETTE"] = $rowPalette["type_palette"];
			$arrPalette["COLOR_MAIN"] = $rowPalette["color_main"];
			$arrPalette["COLOR_SECON"] = $rowPalette["color_secon"];
			$arrPalette["COLOR_DEG"] = $rowPalette["color_deg"];
			$arrPalette["COLOR_TEXT"] = $rowPalette["color_text"];
			
			$arrayGroup[] = $arrPalette;
		}
		$arrayResult["PALETTE_DATA"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE_CODE'] = "4003";
		$arrayResult['RESPONSE_AWARE'] = "permission";
		$arrayResult['RESPONSE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "4004";
	$arrayResult['RESPONSE_AWARE'] = "argument";
	$arrayResult['RESPONSE'] = "Not complete argument";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>