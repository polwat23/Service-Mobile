<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload["section_system"],'mobileadmin',$conmysql)){
		$arrayGroup = array();
		$fetchConstant = $conmysql->prepare("SELECT  id_constant, constant_name, constant_desc, constant_value ,is_use 
											 FROM gcconstant ");
		$fetchConstant->execute();
		while($rowMenuMobile = $fetchConstant->fetch()){
			$arrConstans = array();
			$arrConstans["CONSTANT_ID"] = $rowMenuMobile["id_constant"];
			$arrConstans["CONSTANT_NAME"] = $rowMenuMobile["constant_name"];
			$arrConstans["CONSTANT_DESC"] = $rowMenuMobile["constant_desc"];
			$arrConstans["CONSTANT_VALUe"] = $rowMenuMobile["constant_value"];
			$arrConstans["IS_USE"] = $rowMenuMobile["is_use"];
			
			$arrayGroup[] = $arrConstans;
		}
		$arrayResult["CONSTANT_DATA"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE_CODE'] = "4003";
		$arrayResult['RESPONSE_AWARE'] = "permission";
		$arrayResult['RESPONSE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "4004";
	$arrayResult['RESPONSE_AWARE'] = "argument";
	$arrayResult['RESPONSE'] = "Not complete argument";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>