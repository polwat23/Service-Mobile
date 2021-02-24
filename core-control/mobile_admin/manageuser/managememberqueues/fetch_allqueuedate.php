<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managememberqueues')){
		$arrayGroup = array();
		$fetchBranch = $conmysql->prepare("SELECT queue_date,coop_branch_id,SUM(max_queue) as max_queue,SUM(remain_queue) as remain_queue FROM gcqueuemaster WHERE queue_status = '1' AND coop_branch_id = :coop_branch_id 
		AND queue_date BETWEEN CURDATE() - INTERVAL 2 MONTH AND CURDATE() + INTERVAL 2 MONTH
        GROUP By queue_date");
		$fetchBranch->execute([
			':coop_branch_id' => $dataComing["coop_branch_id"]
		]);
		while($rowBranch = $fetchBranch->fetch(PDO::FETCH_ASSOC)){
			$arrGroupUserAcount = array();
			$arrGroupUserAcount["QUEUE_DATE"] = $rowBranch["queue_date"];
			$arrGroupUserAcount["COOP_BRANCH_ID"] = $rowBranch["coop_branch_id"];
			$arrGroupUserAcount["MAX_QUEUE"] = $rowBranch["max_queue"];
			$arrGroupUserAcount["REMAIN_QUEUE"] = $rowBranch["remain_queue"];
			
			$arrayGroup[] = $arrGroupUserAcount;
		}
		$arrayResult["QUEUES_DATE"] = $arrayGroup;
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