<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managememberqueues')){
		$arrayGroup = array();
		$fetchBranch = $conmysql->prepare("SELECT qmt.queue_id,qmt.coop_branch_id,qmt.max_queue,qmt.queue_date,qmt.queue_starttime,qmt.queue_endtime,qmt.remain_queue,qmt.queue_status FROM gcqueuemaster qmt
														WHERE qmt.coop_branch_id = :coop_branch_id AND MONTH(qmt.queue_date) = MONTH(:queuedate)
														ORDER BY qmt.queue_date,qmt.queue_starttime");
		$fetchBranch->execute([
			':coop_branch_id' => $dataComing["coop_branch_id"],
			':queuedate' => $dataComing["queuedate"],
		]);
		while($rowBranch = $fetchBranch->fetch(PDO::FETCH_ASSOC)){
			$arrGroupUserAcount = array();
			$arrGroupUserAcount["QUEUE_ID"] = $rowBranch["queue_id"];
			$arrGroupUserAcount["COOP_BRANCH_ID"] = $rowBranch["coop_branch_id"];
			$arrGroupUserAcount["MAX_QUEUE"] = $rowBranch["max_queue"];
			$arrGroupUserAcount["QUEUE_DATE"] = $rowBranch["queue_date"];
			$arrGroupUserAcount["QUEUE_STARTTIME"] = $rowBranch["queue_starttime"];
			$arrGroupUserAcount["QUEUE_ENDTIME"] = $rowBranch["queue_endtime"];
			$arrGroupUserAcount["REMAIN_QUEUE"] = $rowBranch["remain_queue"];
			$arrGroupUserAcount["QUEUE_STATUS"] = $rowBranch["queue_status"];
			
			$arrayGroup[] = $arrGroupUserAcount;
		}
		$arrayResult["QUEUES_DATA"] = $arrayGroup;
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