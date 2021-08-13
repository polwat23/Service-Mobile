<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','loanrequestqueues')){
		$conmysql->beginTransaction();
		
			$insertConst = $conmysql->prepare("UPDATE gcloanreqqueuemaster SET remain_queue = remain_queue+1 WHERE queue_id = :queue_id");
			if($insertConst->execute([
				':queue_id' => $dataComing["queue_id"]
			])){
					
					$updateDt = $conmysql->prepare("UPDATE gcloanreqqueuedetail SET is_use = '0', update_by = :user_name WHERE queuedt_id = :queuedt_id");
					if($updateDt->execute([
						':queuedt_id' => $dataComing["queuedt_id"],
						':user_name' => $payload["username"]
					])){
							$conmysql->commit();
							$arrayResult['RESULT'] = TRUE;
							require_once('../../../../include/exit_footer.php');
					}else{
							$conmysql->rollback();
							$arrayResult['RESPONSE'] = "ไม่สามารถยกเลิกคิวได้";
							$arrayResult['RESULT'] = FALSE;
							require_once('../../../../include/exit_footer.php');
					}
					
			}else{
					$conmysql->rollback();
					$arrayResult['RESPONSE'] = "ไม่สามารถยกเลิกคิวได้";
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