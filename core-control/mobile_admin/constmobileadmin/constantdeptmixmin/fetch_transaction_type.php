<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantdeptmixmin',$conoracle)){
		$arrayDept = array();
		$fetchDepttype = $conoracle->prepare("SELECT MENU_COMPONENT ,DESCRIPTION FROM gctransactiontype ORDER BY ID ASC");
		$fetchDepttype->execute();
		while($dataDepttype = $fetchDepttype->fetch(PDO::FETCH_ASSOC)){
			$depttype = array();
			$depttype["MENU_COMPONENT"] = $dataDepttype["MENU_COMPONENT"];
			$depttype["DESCRIPTION"] = $dataDepttype["DESCRIPTION"];
			$arrayDept[] = $depttype;
		}
		$arrayResult['TRANTYPE_DATA'] = $arrayDept;
		$arrayResult['RESULT'] = TRUE;
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

