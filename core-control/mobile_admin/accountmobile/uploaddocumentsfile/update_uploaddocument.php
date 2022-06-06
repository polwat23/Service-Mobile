<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','doc_no'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','uploaddocumentsfile')){
		$updateDoc = $conmysql->prepare("UPDATE doclistmaster SET open_status = '1' WHERE doc_no = :doc_no");
		if($updateDoc->execute([
			':doc_no' => $dataComing["doc_no"]
		])){
			$arrayResult["RESULT"] = TRUE;
		}else{
			$arrayResult['RESPONSE'] = "กรุณาติดต่อผู้พัฒนา";
			$arrayResult['RESULT'] = FALSE;
			require_once('../../../../include/exit_footer.php');
		}
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