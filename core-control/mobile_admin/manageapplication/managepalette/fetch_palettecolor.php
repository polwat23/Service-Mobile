<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managepalette',$conoracle)){
		$arrayGroup = array();
		$fetchPalette = $conoracle->prepare("SELECT id_palette,type_palette,color_main,color_secon,color_deg,color_text,
										type_palette_prev,color_main_prev,color_secon_prev,color_text_prev,color_deg_prev,update_date
										FROM gcpalettecolor WHERE is_use = '1'");
		$fetchPalette->execute();
		while($rowPalette = $fetchPalette->fetch(PDO::FETCH_ASSOC)){
			$arrPalette = array();
			$arrPalette["ID_PALETTE"] = $rowPalette["ID_PALETTE"];
			$arrPalette["TYPE_PALETTE"] = $rowPalette["TYPE_PALETTE"];
			$arrPalette["COLOR_MAIN"] = $rowPalette["COLOR_MAIN"];
			$arrPalette["COLOR_SECON"] = $rowPalette["COLOR_SECON"];
			$arrPalette["COLOR_DEG"] = $rowPalette["COLOR_DEG"];
			$arrPalette["COLOR_TEXT"] = $rowPalette["COLOR_TEXT"];
			$arrPalette["TYPE_PALETTE_PREV"] = $rowPalette["TYPE_PALETTE_PREV"];
			$arrPalette["COLOR_MAIN_PREV"] = $rowPalette["COLOR_MAIN_PREV"];
			$arrPalette["COLOR_SECON_PREV"] = $rowPalette["COLOR_SECON_PREV"];
			$arrPalette["COLOR_DEG_PREV"] = $rowPalette["COLOR_DEG_PREV"];
			$arrPalette["COLOR_TEXT_PREV"] = $rowPalette["COLOR_TEXT_PREV"];
			$arrPalette["UPDATE_DATE"] = $rowPalette["UPDATE_DATE"];
			
			$arrayGroup[] = $arrPalette;
		}
		$arrayResult["PALETTE_DATA"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		require_once('../../../../include/exit_footer.php');
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../../include/exit_footer.php');
		
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../../include/exit_footer.php');
	
}
?>