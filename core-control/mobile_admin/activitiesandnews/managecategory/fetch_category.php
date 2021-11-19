<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managecategory')){
		$getCategory = $conmssql->prepare("SELECT category_code,category_desc,is_use FROM gccategory ");
		$getCategory->execute();
		while($rowCate = $getCategory->fetch(PDO::FETCH_ASSOC)){
			$arrGrpCategory = array();
			$arrCategory["CATEGORY_CODE"] = $rowCate["category_code"];
			$arrCategory["CATEGORY_DESC"] = $rowCate["category_desc"];
			$arrCategory["IS_USE"] = $rowCate["is_use"];
			$arrGrpCategory[] = $arrCategory;
		}
		$arrayResult["CATEGORY_LIST"] = $arrGrpCategory;
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

