<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','member_no'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','repayloanapprove')){
		$insertIntoInfo = $conmysql->prepare("UPDATE gcrepayloan SET repayloan_status =:is_status WHERE member_no = :member_no AND id_repayloan =:id_repayloan");
		if($insertIntoInfo->execute([
			':is_status' => $dataComing["is_status"],
			':member_no' => $dataComing["member_no"],
			':id_repayloan' => $dataComing["id_repayloan"]
		])){
			$arrayResult['RESULT'] = TRUE;
			require_once('../../../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE_MESSAGE'] = "ไม่สำเร็จ";
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