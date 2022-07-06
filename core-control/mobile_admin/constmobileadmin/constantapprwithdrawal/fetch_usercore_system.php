<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantapprwithdrawal',$conoracle)){
		$arrayGroup = array();
		$fetchConstant = $conoracle->prepare("SELECT  USER_NAME , FULL_NAME,DESCRIPTION FROM amsecusers");
		$fetchConstant->execute();
		while($rowMenuMobile = $fetchConstant->fetch(PDO::FETCH_ASSOC)){
			$arrConstans = array();
			$arrConstans["ID_SECTION_SYSTEM"] = $rowMenuMobile["USER_NAME"];
			$arrConstans["USER_NAME"] = $rowMenuMobile["USER_NAME"];
			$arrConstans["FULL_NAME"] = $rowMenuMobile["FULL_NAME"];
			$arrConstans["DESCRIPTION"] = $rowMenuMobile["DESCRIPTION"];
			$arrayGroup[] = $arrConstans;
		}
		$arrayResult["USER_SYSTEM"] = $arrayGroup;
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