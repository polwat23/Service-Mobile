<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constanttransactionmenu')){
		$arrayGroup = array();
		$fetchConstant = $conmysql->prepare("SELECT id_menu,menu_name,menu_icon_path FROM gcmenu mc
										WHERE id_menu in (18,56,57) AND menu_status<> -9");
		$fetchConstant->execute();
		while($rowAccount = $fetchConstant->fetch(PDO::FETCH_ASSOC)){
			$arrConstans = array();
			$arrConstans["ID_PARENT"] = $rowAccount["id_menu"];
			$arrConstans["PARENT_NAME"] = $rowAccount["menu_name"];
			$arrConstans["PARENT_ICON_PATH"] = $rowAccount["menu_icon_path"];
			$arrConstans["TRANSMENU"] = [];
			
			$fetchMenuTransaction = $conmysql->prepare("SELECT id_menu,menu_name,menu_icon_path,menu_component FROM gcmenu mc
										WHERE menu_parent = :id_parent AND menu_status<> -9");
			$fetchMenuTransaction->execute([
				':id_parent' => $rowAccount["id_menu"]
			]);
			while($rowMenu = $fetchMenuTransaction->fetch(PDO::FETCH_ASSOC)){
				$arrMenu = array();
				$arrMenu["ID_MENU"] = $rowMenu["id_menu"];
				$arrMenu["MENU_NAME"] = $rowMenu["menu_name"];
				$arrMenu["MENU_COMPONENT"] = $rowMenu["menu_component"];
				$arrMenu["MENU_ICON_PATH"] = $rowMenu["menu_icon_path"];
				
				$arrMenu["BANK_CONSTANT"] = array();
				$fetchBankMapping = $conmysql->prepare("SELECT bc.id_bankconstant,
												bc.transaction_cycle,
												bc.max_numof_deposit,
												bc.max_numof_withdraw,
												bc.min_deposit,
												bc.max_deposit,
												bc.min_withdraw,
												bc.max_withdraw,
												bc.each_bank,
												bcp.id_constantmapping 
												FROM gcbankconstant bc
												LEFT JOIN gcmenuconstantmapping bcp ON bc.id_bankconstant = bcp.id_bankconstant
												WHERE bcp.menu_component = :menu_component AND bcp.is_use = '1'");
				$fetchBankMapping->execute([
					':menu_component' => $rowMenu["menu_component"]
				]);
				while($rowBankMapping = $fetchBankMapping->fetch(PDO::FETCH_ASSOC)){
					$arrMapping = [];
					$arrMapping["ID_BANKCONSTANT"] = $rowBankMapping["id_bankconstant"];
					if($rowBankMapping["transaction_cycle"] == "day"){
						$arrMapping["TRANSACTION_CYCLE"] = "รายวัน";
					}else if($rowBankMapping["transaction_cycle"] == "time"){
						$arrMapping["TRANSACTION_CYCLE"] = "รายครั้ง";
					}else if($rowBankMapping["transaction_cycle"] == "month"){
						$arrMapping["TRANSACTION_CYCLE"] = "รายเดือน";
					}else if($rowBankMapping["transaction_cycle"] == "year"){
						$arrMapping["TRANSACTION_CYCLE"] = "รายปี";
					}else{
						$arrMapping["TRANSACTION_CYCLE"] = $rowBankMapping["transaction_cycle"];
					}
					$arrMapping["MAX_NUMOF_DEPOSIT"] = $rowBankMapping["max_numof_deposit"] == "-1" ? "ไม่จำกัด" : number_format($rowBankMapping["max_numof_deposit"],0)." ครั้ง";
					$arrMapping["MAX_NUMOF_WITHDRAW"] = $rowBankMapping["max_numof_withdraw"] == "-1" ? "ไม่จำกัด" : number_format($rowBankMapping["max_numof_withdraw"],0)." ครั้ง";
					$arrMapping["MIN_DEPOSIT"] = $rowBankMapping["min_deposit"] == "-1" ? "ไม่จำกัด" :  number_format($rowBankMapping["min_deposit"],2)." บาท";
					$arrMapping["MAX_DEPOSIT"] = $rowBankMapping["max_deposit"] == "-1" ? "ไม่จำกัด" :  number_format($rowBankMapping["max_deposit"],2)." บาท";
					$arrMapping["MIN_WITHDRAW"] = $rowBankMapping["min_withdraw"] == "-1" ? "ไม่จำกัด" :  number_format($rowBankMapping["min_withdraw"],2)." บาท";
					$arrMapping["MAX_WITHDRAW"] = $rowBankMapping["max_withdraw"] == "-1" ? "ไม่จำกัด" :  number_format($rowBankMapping["max_withdraw"],2)." บาท";
					$arrMapping["EACH_BANK"] = $rowBankMapping["each_bank"];
					$arrMapping["ID_CONSTANTMAPPING"] = $rowBankMapping["id_constantmapping"];
					$arrMenu["BANK_CONSTANT"][] = $arrMapping;
				}
				
				$arrConstans["TRANSMENU"][] = $arrMenu;
			}
			$arrayGroup[] = $arrConstans;
		}
		$arrayResult["TRANSACTION_MENU"] = $arrayGroup;
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