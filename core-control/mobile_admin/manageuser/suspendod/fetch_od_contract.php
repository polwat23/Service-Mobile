<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','suspendod')){
		$arrayGroupAll = array();
		$fetchContract = $conmysql->prepare("SELECT suspendod_id, member_no, loancontract_no FROM gcsuspendod WHERE is_use = '1'");
		$fetchContract->execute($arrayExecute);
		while($rowContract = $fetchContract->fetch(PDO::FETCH_ASSOC)){
			$arrayGroup = array();
			$arrayGroup["LOANCONTRACT_NO"] = $rowContract["loancontract_no"];
			$arrayGroup["MEMBER_NO"] = $rowContract["member_no"];
			$arrayGroup["SUSPENDOD_ID"] = $rowContract["suspendod_id"];
			$arrayGroupAll[$rowContract["member_no"]][] = $arrayGroup;
		}
		$arrayResult["SUSPEND_OD_LIST"] = $arrayGroupAll;
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