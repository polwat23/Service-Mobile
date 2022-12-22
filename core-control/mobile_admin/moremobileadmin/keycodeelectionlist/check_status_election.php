<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','member_no'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','keycodeelectionlist')){		
		$getAllReqDocno = $conmysql->prepare("SELECT id_election FROM gcelection WHERE member_no = :member_no and year_election = YEAR(NOW()) + 543");
		$getAllReqDocno->execute([':member_no' => $dataComing["member_no"]]);
		if($getAllReqDocno->rowCount() > 0){
			$arrayResult["STATUS_DESC"] = "เลือกตั้งแล้ว";
		}else{
			$arrayResult["STATUS_DESC"] = "ยังไม่ได้เลือกตั้ง";
		}
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