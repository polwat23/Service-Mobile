<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','extracreditproject')){
		$arrayGroup = array();
		$fetchConstant = $conmysql->prepare("SELECT id_extra_credit, extra_credit_name, extra_credit_desc FROM gcconstantextracreditproject WHERE is_use = '1' ORDER BY create_date DESC");
		$fetchConstant->execute();
		while($rowMenuMobile = $fetchConstant->fetch(PDO::FETCH_ASSOC)){
			$arrConstans = array();
			$arrConstans["ID_EXTRA_CREDIT"] = $rowMenuMobile["id_extra_credit"];
			$arrConstans["EXTRA_CREDIT_NAME"] = $rowMenuMobile["extra_credit_name"];
			$arrConstans["EXTRA_CREDIT_DESC"] = $rowMenuMobile["extra_credit_desc"];
			$arrayGroup[] = $arrConstans;
		}
		$arrayResult["CONSTANT_DATA"] = $arrayGroup;
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