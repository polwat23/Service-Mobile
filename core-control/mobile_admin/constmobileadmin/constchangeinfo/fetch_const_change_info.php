<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantchangeinfo')){
		$arrayGroup = array();
		$fetchConstChangeInfo = $conmssql->prepare("SELECT const_code,const_desc,is_change,save_tablecore FROM gcconstantchangeinfo");
		$fetchConstChangeInfo->execute();
		while($rowConst = $fetchConstChangeInfo->fetch(PDO::FETCH_ASSOC)){
			$arrConst = array();
			$arrConst["CONST_CODE"] = $rowConst["const_code"];
			$arrConst["CONST_DESC"] = $rowConst["const_desc"];
			$arrConst["IS_CHANGE"] = $rowConst["is_change"];
			$arrConst["SAVE_TABLECORE"] = $rowConst["save_tablecore"];
			$arrayGroup[] = $arrConst;
		}
		$arrayResult["CONST_CHANGE"] = $arrayGroup;
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