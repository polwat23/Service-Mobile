<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantdeptaccount')){
		$arrayGroup = array();
		$fetchConstant = $conoracle->prepare("SELECT
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
										color.color_text,
										bank.fee_deposit,
										bank.fee_withdraw
									FROM
										csbankdisplay bank
									INNER JOIN gcpalettecolor color ON
										bank.id_palette = color.id_palette");
		$fetchConstant->execute();
		while($rowAccount = $fetchConstant->fetch(PDO::FETCH_ASSOC)){
			$arrConstans = array();
			$arrConstans["ID_PALETTE"] = $rowAccount["ID_PALETTE"];
			$arrConstans["BANK_CODE"] = $rowAccount["BANK_CODE"];
			$arrConstans["BANK_NAME"] = $rowAccount["BANK_NAME"];
			$arrConstans["BANK_SHORT_NAME"] = $rowAccount["BANK_SHORT_NAME"];
			$arrConstans["BANK_LOGO_PATH"] = $rowAccount["BANK_LOGO_PATH"];
			$arrConstans["BANK_FORMAT_ACCOUNT"] = $rowAccount["BANK_FORMAT_ACCOUNT"];
			$arrConstans["BANK_FORMAT_ACCOUNT_HIDE"] = $rowAccount["BANK_FORMAT_ACCOUNT_HIDE"];
			$arrConstans["TYPE_PALETTE"] = $rowAccount["TYPE_PALETTE"];
			$arrConstans["COLOR_MAIN"] = $rowAccount["COLOR_MAIN"];
			$arrConstans["COLOR_SECON"] = $rowAccount["COLOR_SECON"];
			$arrConstans["COLOR_DEG"] = $rowAccount["COLOR_DEG"];
			$arrConstans["COLOR_TEXT"] = $rowAccount["COLOR_TEXT"];
			$arrConstans["FEE_DEPOSIT"] = $rowAccount["FEE_DEPOSIT"];
			$arrConstans["FEE_WITHDRAW"] = $rowAccount["FEE_WITHDRAW"];
			
			$arrConstans["BANK_CONSTANT"] = [];
			$fetchBankMapping = $conoracle->prepare("SELECT bc.id_bankconstant,
											bc.transaction_name,
											bc.transaction_cycle,
											bc.max_numof_deposit,
											bc.max_numof_withdraw,
											bc.min_deposit,
											bc.max_deposit,
											bc.min_withdraw,
											bc.max_withdraw,
											bc.each_bank,
											bcp.id_bankconstantmapping 
											FROM gcbankconstant bc
											LEFT JOIN gcbankconstantmapping bcp ON bc.id_bankconstant = bcp.id_bankconstant
											WHERE bcp.bank_code = :bank_code AND bcp.is_use = '1'");
			$fetchBankMapping->execute([
				':bank_code' => $rowAccount["BANK_CODE"]
			]);
			while($rowBankMapping = $fetchBankMapping->fetch(PDO::FETCH_ASSOC)){
				$arrMapping = [];
				$arrMapping["ID_BANKCONSTANT"] = $rowBankMapping["id_bankconstant"];
				if($rowBankMapping["TRANSACTION_CYCLE"] == "day"){
					$arrMapping["TRANSACTION_CYCLE"] = "รายวัน";
				}else if($rowBankMapping["TRANSACTION_CYCLE"] == "time"){
					$arrMapping["TRANSACTION_CYCLE"] = "รายครั้ง";
				}else if($rowBankMapping["TRANSACTION_CYCLE"] == "month"){
					$arrMapping["TRANSACTION_CYCLE"] = "รายเดือน";
				}else if($rowBankMapping["TRANSACTION_CYCLE"] == "year"){
					$arrMapping["TRANSACTION_CYCLE"] = "รายปี";
				}else{
					$arrMapping["TRANSACTION_CYCLE"] = $rowBankMapping["TRANSACTION_CYCLE"];
				}
				$arrMapping["TRANSACTION_NAME"] = $rowBankMapping["TRANSACTION_NAME"];
				$arrMapping["MAX_NUMOF_DEPOSIT"] = $rowBankMapping["MAX_NUMOF_DEPOSIT"] == "-1" ? "ไม่จำกัด" : number_format($rowBankMapping["MAX_NUMOF_DEPOSIT"],0)." ครั้ง";
				$arrMapping["MAX_NUMOF_WITHDRAW"] = $rowBankMapping["MAX_NUMOF_WITHDRAW"] == "-1" ? "ไม่จำกัด" : number_format($rowBankMapping["MAX_NUMOF_WITHDRAW"],0)." ครั้ง";
				$arrMapping["MIN_DEPOSIT"] = $rowBankMapping["MIN_DEPOSIT"] == "-1" ? "ไม่จำกัด" :  number_format($rowBankMapping["MIN_DEPOSIT"],2)." บาท";
				$arrMapping["MAX_DEPOSIT"] = $rowBankMapping["MAX_DEPOSIT"] == "-1" ? "ไม่จำกัด" :  number_format($rowBankMapping["MAX_DEPOSIT"],2)." บาท";
				$arrMapping["MIN_WITHDRAW"] = $rowBankMapping["MIN_WITHDRAW"] == "-1" ? "ไม่จำกัด" :  number_format($rowBankMapping["MIN_WITHDRAW"],2)." บาท";
				$arrMapping["MAX_WITHDRAW"] = $rowBankMapping["MAX_WITHDRAW"] == "-1" ? "ไม่จำกัด" :  number_format($rowBankMapping["MAX_WITHDRAW"],2)." บาท";
				$arrMapping["EACH_BANK"] = $rowBankMapping["EACH_BANK"];
				$arrMapping["ID_BANKCONSTANTMAPPING"] = $rowBankMapping["ID_BANKCONSTANTMAPPING"];
				$arrConstans["BANK_CONSTANT"][] = $arrMapping;
			}
			
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