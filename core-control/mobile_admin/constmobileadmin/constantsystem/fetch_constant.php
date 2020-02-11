<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantsystem')){
		$arrayGroup = array();
		$fetchConstant = $conmysql->prepare("SELECT id_constant,constant_name,constant_desc,constant_value
											 FROM gcconstant WHERE is_use = '1'");
		$fetchConstant->execute();
		while($rowMenuMobile = $fetchConstant->fetch(PDO::FETCH_ASSOC)){
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