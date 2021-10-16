<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantchangeinfo',$conoracle)){
		$arrayGroup = array();
		$fetchConstChangeInfo = $conoracle->prepare("SELECT const_code,const_desc,is_change,save_tablecore FROM gcconstantchangeinfo");
		$fetchConstChangeInfo->execute();
		while($rowConst = $fetchConstChangeInfo->fetch(PDO::FETCH_ASSOC)){
			$arrConst = array();
			$arrConst["CONST_CODE"] = $rowConst["CONST_CODE"];
			$arrConst["CONST_DESC"] = $rowConst["CONST_DESC"];
			$arrConst["IS_CHANGE"] = $rowConst["IS_CHANGE"];
			$arrConst["SAVE_TABLECORE"] = $rowConst["SAVE_TABLECORE"];
			$arrayGroup[] = $arrConst;
		}
		$arrayResult["CONST_CHANGE"] = $arrayGroup;
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