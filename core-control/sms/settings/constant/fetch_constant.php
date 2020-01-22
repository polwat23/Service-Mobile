<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'sms','manageconstant')){
		$arrayGroup = array();
		$fetchConstant = $conmysql->prepare("SELECT id_smscssystem as id_constant,smscs_name as constant_name,smscs_desc as constant_desc,
												smscs_value as constant_value
												FROM smsconstantsystem WHERE is_use = '1'");
		$fetchConstant->execute();
		while($rowMenuMobile = $fetchConstant->fetch()){
			$arrConstans = array();
			$arrConstans["CONSTANT_ID"] = $rowMenuMobile["id_constant"];
			$arrConstans["CONSTANT_NAME"] = $rowMenuMobile["constant_name"];
			$arrConstans["CONSTANT_DESC"] = $rowMenuMobile["constant_desc"];
			$arrConstans["CONSTANT_VALUE"] = $rowMenuMobile["constant_value"];
			$arrayGroup[] = $arrConstans;
		}
		$arrayResult["CONSTANT_DATA"] = $arrayGroup;
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