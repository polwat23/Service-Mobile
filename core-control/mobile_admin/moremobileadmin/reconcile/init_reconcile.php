<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','reconcile')){
		$arrayBank = array();
		
		$fetchBankList = $conoracle->prepare("SELECT bank_code,bank_short_name,bank_logo_path FROM csbankdisplay");
		$fetchBankList->execute();
		while($rowBank = $fetchBankList->fetch(PDO::FETCH_ASSOC)){
			$itemBank = array();
			$itemBank["BANK_CODE"] = $rowBank["BANK_CODE"];
			$itemBank["BANK_NAME"] = $rowBank["BANK_SHORT_NAME"];
			$itemBank["BANK_LOGO_PATH"] = $rowBank["BANK_LOGO_PATH"];
			$arrayBank[] = $itemBank;
		}
		
		$arrayResult['BANK_LIST'] = $arrayBank;
		$arrayResult['RESULT'] = TRUE;
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