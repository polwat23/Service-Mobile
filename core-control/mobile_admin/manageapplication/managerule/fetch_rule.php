<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managerule')){
		$arrayGrpRule = array();
		$fetchRule = $conmssql->prepare("SELECT rule_name,rule_url,id_rule FROM gcrulecooperative WHERE is_use = '1' ORDER BY CREATE_DATE ASC");
		$fetchRule->execute();
		while($rowRule = $fetchRule->fetch(PDO::FETCH_ASSOC)){
			$arrayRule = array();
			$arrayRule["RULE_NAME"] = $rowRule["rule_name"];
			$arrayRule["RULE_URL"] = $rowRule["rule_url"];
			$arrayRule["ID_RULE"] = $rowRule["id_rule"];
			$arrayGrpRule[] = $arrayRule;
		}
		$arrayResult['RULE'] = $arrayGrpRule;
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
