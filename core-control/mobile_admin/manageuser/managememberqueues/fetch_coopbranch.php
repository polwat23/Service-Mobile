<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managememberqueues')){
		$arrayGroup = array();
		$fetchBranch = $conmssql->prepare("SELECT PREFIX_COOP, COOP_ID FROM cmcoopmaster");
		$fetchBranch->execute();
		while($rowBranch = $fetchBranch->fetch(PDO::FETCH_ASSOC)){
			$arrGroupUserAcount = array();
			$arrGroupUserAcount["COOP_BRANCH_ID"] = $rowBranch["COOP_ID"];
			$arrGroupUserAcount["COOP_BRANCH_DESC"] = $rowBranch["PREFIX_COOP"] ?? "สอ. คูโบต้า";
			
			$arrayGroup[] = $arrGroupUserAcount;
		}
		$arrayResult["COOP_BRANCH"] = $arrayGroup;
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