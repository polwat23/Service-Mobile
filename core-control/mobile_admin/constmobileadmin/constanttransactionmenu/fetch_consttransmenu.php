<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constanttransactionmenu')){
		$arrayGroup = array();
		$fetchConstant = $conoracle->prepare("SELECT id_menu,menu_name,menu_icon_path FROM gcmenu mc
										WHERE id_menu in (56) AND menu_status<> -9");
		$fetchConstant->execute();
		while($rowAccount = $fetchConstant->fetch(PDO::FETCH_ASSOC)){
			$arrConstans = array();
			$arrConstans["ID_PARENT"] = $rowAccount["ID_MENU"];
			$arrConstans["PARENT_NAME"] = $rowAccount["MENU_NAME"];
			$arrConstans["PARENT_ICON_PATH"] = $rowAccount["MENU_ICON_PATH"];
			$arrConstans["TRANSMENU"] = [];
			
			$fetchMenuTransaction = $conoracle->prepare("SELECT id_menu,menu_name,menu_icon_path,menu_component FROM gcmenu mc
										WHERE menu_parent = :id_parent AND menu_status<> -9");
			$fetchMenuTransaction->execute([
				':id_parent' => $rowAccount["ID_MENU"]
			]);
			while($rowMenu = $fetchMenuTransaction->fetch(PDO::FETCH_ASSOC)){
				$arrMenu = array();
				$arrMenu["ID_MENU"] = $rowMenu["ID_MENU"];
				$arrMenu["MENU_NAME"] = $rowMenu["MENU_NAME"];
				$arrMenu["MENU_COMPONENT"] = $rowMenu["MENU_COMPONENT"];
				$arrMenu["MENU_ICON_PATH"] = $rowMenu["MENU_ICON_PATH"];
				
				$arrMenu["BANK_CONSTANT"] = array();
				$fetchBankMapping = $conoracle->prepare("SELECT bc.id_bankconstant,
												bc.transaction_cycle,
												bc.max_numof_deposit,
												bc.max_numof_withdraw,
												bc.min_deposit,
												bc.max_deposit,
												bc.min_withdraw,
												bc.max_withdraw,
												bcp.id_constantmapping 
												FROM gcbankconstant bc
												LEFT JOIN gcmenuconstantmapping bcp ON bc.id_bankconstant = bcp.id_bankconstant
												WHERE bcp.menu_component = :menu_component AND bcp.is_use = '1'");
				$fetchBankMapping->execute([
					':menu_component' => $rowMenu["MENU_COMPONENT"]
				]);
				while($rowBankMapping = $fetchBankMapping->fetch(PDO::FETCH_ASSOC)){
					$arrMapping = [];
					$arrMapping["ID_BANKCONSTANT"] = $rowBankMapping["ID_BANKCONSTANT"];
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
					$arrMapping["MAX_NUMOF_DEPOSIT"] = $rowBankMapping["MAX_NUMOF_DEPOSIT"] == "-1" ? "ไม่จำกัด" : number_format($rowBankMapping["MAX_NUMOF_DEPOSIT"],0)." ครั้ง";
					$arrMapping["MAX_NUMOF_WITHDRAW"] = $rowBankMapping["MAX_NUMOF_WITHDRAW"] == "-1" ? "ไม่จำกัด" : number_format($rowBankMapping["MAX_NUMOF_WITHDRAW"],0)." ครั้ง";
					$arrMapping["MIN_DEPOSIT"] = $rowBankMapping["MIN_DEPOSIT"] == "-1" ? "ไม่จำกัด" :  number_format($rowBankMapping["MIN_DEPOSIT"],2)." บาท";
					$arrMapping["MAX_DEPOSIT"] = $rowBankMapping["MAX_DEPOSIT"] == "-1" ? "ไม่จำกัด" :  number_format($rowBankMapping["MAX_DEPOSIT"],2)." บาท";
					$arrMapping["MIN_WITHDRAW"] = $rowBankMapping["MIN_WITHDRAW"] == "-1" ? "ไม่จำกัด" :  number_format($rowBankMapping["MIN_WITHDRAW"],2)." บาท";
					$arrMapping["MAX_WITHDRAW"] = $rowBankMapping["MAX_WITHDRAW"] == "-1" ? "ไม่จำกัด" :  number_format($rowBankMapping["MAX_WITHDRAW"],2)." บาท";
					$arrMapping["ID_CONSTANTMAPPING"] = $rowBankMapping["ID_CONSTANTMAPPING"];
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