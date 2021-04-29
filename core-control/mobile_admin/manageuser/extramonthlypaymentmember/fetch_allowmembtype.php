<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','extramonthlypaymentmembers')){
		$arrayGroup = array();
		$fetchUserGroup = $conmysql->prepare("SELECT id_extrapayment, membertype_code, membertype_desc, is_use FROM gcextrapaymentmembertype");
		$fetchUserGroup->execute();
		while($rowUserGroup = $fetchUserGroup->fetch(PDO::FETCH_ASSOC)){
			$arrGroupUserAcount = array();
			$arrGroupUserAcount["ID_EXTRAPAYMENT"] = $rowUserGroup["id_extrapayment"];
			$arrGroupUserAcount["MEMBERTYPE_CODE"] = $rowUserGroup["membertype_code"];
			$arrGroupUserAcount["MEMBERTYPE_DESC"] = $rowUserGroup["membertype_desc"];
			$arrGroupUserAcount["IS_USE"] = $rowUserGroup["is_use"] == '1';
			
			$arrayGroup[] = $arrGroupUserAcount;
		}
		$arrayResult["ALLOW_MEMBTYPE"] = $arrayGroup;
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