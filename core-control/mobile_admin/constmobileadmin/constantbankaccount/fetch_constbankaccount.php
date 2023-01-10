<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantdeptaccount')){
		$arrayGroup = array();
		$fetchConstant = $conmysql->prepare("SELECT
										bank.bank_code,
										bank.bank_name,
										bank.bank_short_name,
										bank.bank_logo_path,
										bank.bank_format_account,
										bank.bank_format_account_hide,
										bank.id_palette,
										color.type_palette,
										color.color_main,
										color.color_secon,
										color.color_deg,
										color.color_text
									FROM
										csbankdisplay bank
									INNER JOIN gcpalettecolor color ON
										bank.id_palette = color.id_palette");
		$fetchConstant->execute();
		while($rowAccount = $fetchConstant->fetch(PDO::FETCH_ASSOC)){
			$arrConstans = array();
			$arrConstans["ID_PALETTE"] = $rowAccount["id_palette"];
			$arrConstans["BANK_CODE"] = $rowAccount["bank_code"];
			$arrConstans["BANK_NAME"] = $rowAccount["bank_name"];
			$arrConstans["BANK_SHORT_NAME"] = $rowAccount["bank_short_name"];
			$arrConstans["BANK_LOGO_PATH"] = $rowAccount["bank_logo_path"];
			$arrConstans["BANK_FORMAT_ACCOUNT"] = $rowAccount["bank_format_account"];
			$arrConstans["BANK_FORMAT_ACCOUNT_HIDE"] = $rowAccount["bank_format_account_hide"];
			$arrConstans["TYPE_PALETTE"] = $rowAccount["type_palette"];
			$arrConstans["COLOR_MAIN"] = $rowAccount["color_main"];
			$arrConstans["COLOR_SECON"] = $rowAccount["color_secon"];
			$arrConstans["COLOR_DEG"] = $rowAccount["color_deg"];
			$arrConstans["COLOR_TEXT"] = $rowAccount["color_text"];
			$arrayGroup[] = $arrConstans;
		}
		$arrayResult["BANKACCOUNT_DATA"] = $arrayGroup;
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