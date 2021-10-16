<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantdeptaccount',$conoracle)){
		$arrayGroup = array();
		$fetchConstant = $conoracle->prepare("SELECT id_bankconstant, transaction_name, transaction_cycle, max_numof_deposit, max_numof_withdraw, min_deposit, max_deposit, min_withdraw, max_withdraw, each_bank FROM gcbankconstant");
		$fetchConstant->execute();
		while($rowAccount = $fetchConstant->fetch(PDO::FETCH_ASSOC)){
			$arrConstans = array();
			$arrConstans["ID_BANKCONSTANT"] = $rowAccount["ID_BANKCONSTANT"];
			$arrConstans["TRANSACTION_CYCLE"] = $rowAccount["TRANSACTION_CYCLE"];
			$arrConstans["MAX_NUMOF_DEPOSIT"] = $rowAccount["MAX_NUMOF_DEPOSIT"];
			$arrConstans["MAX_NUMOF_WITHDRAW"] = $rowAccount["MAX_NUMOF_WITHDRAW"];
			$arrConstans["MIN_DEPOSIT"] = $rowAccount["MIN_DEPOSIT"];
			$arrConstans["MAX_DEPOSIT"] = $rowAccount["MAX_DEPOSIT"];
			$arrConstans["MIN_WITHDRAW"] = $rowAccount["MIN_WITHDRAW"];
			$arrConstans["MAX_WITHDRAW"] = $rowAccount["MAX_WITHDRAW"];
			$arrConstans["EACH_BANK"] = $rowAccount["EACH_BANK"];
			$arrConstans["TRANSACTION_NAME"] = $rowAccount["TRANSACTION_NAME"];
			$arrayGroup[] = $arrConstans;
		}
		$arrayResult["BANK_CONSTANT"] = $arrayGroup;
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