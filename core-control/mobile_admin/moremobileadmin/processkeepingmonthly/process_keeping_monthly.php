<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','processmonthlybilling')){
		$arrBodyInq = array();
		$arrPayloadNew = array();
		$arrPayloadNew["process_by"] = $payload["username"]; 
		$arrPayloadNew['exp'] = time() + intval($func->getConstant("limit_session_timeout"));
		$access_token = $jwt_token->customPayload($arrPayloadNew, $config["SECRET_KEY_JWT"]);
		$headerInq[] = "Authorization: Bearer ".$access_token;
		$headerInq[] = "transaction_scheduler: 1";
		$responseAPI = $lib->posting_data($config["URL_SERVICE"].'mobile_and_web-control/repaymonthlyfull/process_keeping_monthly',$arrBodyInq,$headerInq);
		$arrResponseAPIInq = json_decode($responseAPI);
		if($arrResponseAPIInq->RESULT){
			$arrayResult['RESULT'] = TRUE;
			require_once('../../../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE'] = "ประมวลไม่สำเร็จกรุณาลองอีกครั้ง";
			$arrayResult['RESULT'] = FALSE;
			require_once('../../../../include/exit_footer.php');
		}
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