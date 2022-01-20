<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','reqdocumentformlist')){
		$arrGrp = null;
		$arrayType = array();
		
		$getAllReqDocno = $conmysql->prepare("SELECT COUNT(reqdoc_no) AS COUNT_WAITING,
															(SELECT COUNT(reqdoc_no) AS COUNT_PROCESSING FROM gcreqdocformonline WHERE req_status = '7') AS COUNT_PROCESSING,
															(SELECT COUNT(reqdoc_no) AS COUNT_CANCEL FROM gcreqdocformonline WHERE req_status = '9') AS COUNT_CANCEL,
															(SELECT COUNT(reqdoc_no) AS COUNT_DISAPPROVAL FROM gcreqdocformonline WHERE req_status = '-9') AS COUNT_DISAPPROVAL,
															(SELECT COUNT(reqdoc_no) AS COUNT_APPROVE FROM gcreqdocformonline WHERE req_status = '1') AS COUNT_APPROVE
															FROM gcreqdocformonline WHERE req_status = '8'");
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