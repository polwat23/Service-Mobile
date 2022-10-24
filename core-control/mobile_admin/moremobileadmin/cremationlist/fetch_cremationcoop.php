<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','cremationlist')){
	
		$conmssqlcmt = $con->connecttosqlservercmt();
		$fetchCremationList = $conmssqlcmt->prepare("SELECT WC_ID,COOP_SHORTNAME,COOP_NAME,COOP_CONTROL FROM WCCONTCOOP WHERE  coop_control = '051001'");
		$fetchCremationList->execute();
		
		$arrayCremation = array();
		while($rowCremation = $fetchCremationList->fetch(PDO::FETCH_ASSOC)){
			$arrayGroup = array();
			$arrayGroup["WC_ID"] = $rowCremation["WC_ID"];
			$arrayGroup["COOP_SHORTNAME"] = $rowCremation["COOP_SHORTNAME"];
			$arrayGroup["COOP_NAME"] = $rowCremation["COOP_NAME"];
			$arrayCremation[$rowCremation["WC_ID"]] = $arrayGroup;
		}
		
		$arrayResult["CREMATION_COOP"] = $arrayCremation;
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