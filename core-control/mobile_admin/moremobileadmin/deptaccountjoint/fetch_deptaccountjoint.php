<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','deptaccountjoint')){
		$arrayGroupAll = array();

		$fetchDeptJoint = $conmysql->prepare("SELECT deptaccount_no, update_date FROM gcdeptaccountjoint WHERE is_joint = '1'");
		$fetchDeptJoint->execute([]);
		while($rowDeptJoint = $fetchDeptJoint->fetch(PDO::FETCH_ASSOC)){
			$arrayData = array();
			$arrayData["DEPTACCOUNT_NO"] = $rowDeptJoint["deptaccount_no"];
			$arrayData["UPDATE_DATE"] = $rowDeptJoint["update_date"];
			$arrayGroupAll[] = $arrayData;
		}
		$arrayResult["DEPTACCOUNT"] = $arrayGroupAll;
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