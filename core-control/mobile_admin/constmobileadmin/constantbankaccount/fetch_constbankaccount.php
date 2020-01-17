<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantdeptaccount')){
		$arrayGroup = array();
		$fetchConstant = $conmysql->prepare("SELECT cbpc.id_bankpalette, cbpc.id_palette, cbpc.bank_code,
											bank.bank_short_name,bank.bank_logo_path,
											pc.type_palette, pc.color_main,pc.color_secon,pc.color_deg,pc.color_text
											FROM gcconstantbankpalette cbpc 
											JOIN csbankdisplay bank ON cbpc.bank_code = bank.bank_code
											JOIN gcpalettecolor pc ON pc.id_palette = cbpc.id_palette
											WHERE cbpc.is_use = '1'");
		$fetchConstant->execute();
		while($rowAccount = $fetchConstant->fetch()){
			$arrConstans = array();
			$arrConstans["ID_BANKPALETTE"] = $rowAccount["id_bankpalette"];
			$arrConstans["ID_PALETTE"] = $rowAccount["id_palette"];
			$arrConstans["BANK_CODE"] = $rowAccount["bank_code"];
			$arrConstans["BANK_SHORT_NAME"] = $rowAccount["bank_short_name"];
			$arrConstans["BANK_LOGO_PATH"] = $rowAccount["bank_logo_path"];
			$arrConstans["TYPE_PALETTE"] = $rowAccount["type_palette"];
			$arrConstans["COLOR_MAIN"] = $rowAccount["color_main"];
			$arrConstans["COLOR_SECON"] = $rowAccount["color_secon"];
			$arrConstans["COLOR_DEG"] = $rowAccount["color_deg"];
			$arrConstans["COLOR_TEXT"] = $rowAccount["color_text"];
			$arrayGroup[] = $arrConstans;
		}
		$arrayResult["BANKACCOUNT_DATA"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>