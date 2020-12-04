<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','loanrequestform')){
		$arrGrp = null;
		$arrayType = array();
		
		$getAllReqDocno = $conmysql->prepare("SELECT COUNT(reqloan_doc) AS COUNT_WAITING,
															(SELECT COUNT(reqloan_doc) AS COUNT_PROCESSING FROM gcreqloan WHERE req_status = '7') AS COUNT_PROCESSING,
															(SELECT COUNT(reqloan_doc) AS COUNT_CANCEL FROM gcreqloan WHERE req_status = '-9') AS COUNT_CANCEL,
															(SELECT COUNT(reqloan_doc) AS COUNT_APPROVE FROM gcreqloan WHERE req_status = '1') AS COUNT_APPROVE
															FROM gcreqloan WHERE req_status = '8'");
		$getAllReqDocno->execute();
		while($rowDocno = $getAllReqDocno->fetch(PDO::FETCH_ASSOC)){
				$arrGrp = $rowDocno;
		}
		
		$arrayResult['COUNT_REQ'] = $arrGrp;
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