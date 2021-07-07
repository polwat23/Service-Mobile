<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','manageconstant')){
		$arrayGroup = array();
		$fetchConstant = $conoracle->prepare("SELECT id_constant,constant_name,constant_desc,constant_value
											 FROM gcconstant WHERE is_use = '1'");
		$fetchConstant->execute();
		while($rowMenuMobile = $fetchConstant->fetch()){
			$arrConstans = array();
			$arrConstans["CONSTANT_ID"] = $rowMenuMobile["ID_CONSTANT"];
			$arrConstans["CONSTANT_NAME"] = $rowMenuMobile["CONSTANT_NAME"];
			$arrConstans["CONSTANT_DESC"] = $rowMenuMobile["CONSTANT_DESC"];
			$arrConstans["CONSTANT_VALUE"] = $rowMenuMobile["CONSTANT_VALUE"];
			$arrayGroup[] = $arrConstans;
		}
		$arrayResult["CONSTANT_DATA"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		require_once('../../../../include/exit_footer.php');
	}else{
		$arrayResult['RESPONSE_CODE'] = "4003";
		$arrayResult['RESPONSE_AWARE'] = "permission";
		$arrayResult['RESPONSE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../../include/exit_footer.php');
		
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "4004";
	$arrayResult['RESPONSE_AWARE'] = "argument";
	$arrayResult['RESPONSE'] = "Not complete argument";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../../include/exit_footer.php');
	
}
?>