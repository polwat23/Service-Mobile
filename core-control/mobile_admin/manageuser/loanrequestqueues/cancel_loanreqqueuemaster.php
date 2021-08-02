<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','loanrequestqueues')){
		
			$insertConst = $conmysql->prepare("UPDATE gcloanreqqueuemaster SET queue_status = '0',update_by = :username WHERE queue_id = :queue_id");
			if($insertConst->execute([
				':queue_id' => $dataComing["queue_id"],
				':username' => $payload["username"]
			])){
					$arrayResult['RESULT'] = TRUE;
					require_once('../../../../include/exit_footer.php');
			}else{
					$conmysql->rollback();
					$arrayResult['RESPONSE'] = "ไม่สามารถยกเลิกรอบบริการได้";
					$arrayResult['insertConst'] = $insertConst;
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