<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','loanrequestqueues')){
		
		$arrayBranch = array();
		$fetchBranch = $conoracle->prepare("SELECT PREFIX_COOP, COOP_ID FROM cmcoopmaster");
		$fetchBranch->execute();
		while($rowBranch = $fetchBranch->fetch(PDO::FETCH_ASSOC)){
			$arrayBranch[$rowBranch["COOP_ID"]] = $rowBranch["PREFIX_COOP"];
		}
		
		$arrayGroup = array();
		$fetchQueue = $conmysql->prepare("SELECT queue_date,coop_branch_id,max_queue,remain_queue,queue_starttime,queue_endtime,queue_id,queue_type FROM gcloanreqqueuemaster WHERE queue_status = '1' 
		AND queue_date BETWEEN CURDATE() - INTERVAL 2 MONTH AND CURDATE() + INTERVAL 2 MONTH");
		$fetchQueue->execute();
		while($rowQueue = $fetchQueue->fetch(PDO::FETCH_ASSOC)){
			$arrGroupUserAcount = array();
			$arrGroupUserAcount["QUEUE_DATE"] = $rowQueue["queue_date"];
			$arrGroupUserAcount["COOP_BRANCH_ID"] = $rowQueue["coop_branch_id"];
			$arrGroupUserAcount["PREFIX_COOP"] = $arrayBranch[$rowQueue["coop_branch_id"]];
			$arrGroupUserAcount["MAX_QUEUE"] = $rowQueue["max_queue"];
			$arrGroupUserAcount["REMAIN_QUEUE"] = $rowQueue["remain_queue"];
			$arrGroupUserAcount["QUEUE_STARTTIME"] = $rowQueue["queue_starttime"];
			$arrGroupUserAcount["QUEUE_ENDTIME"] = $rowQueue["queue_endtime"];
			$arrGroupUserAcount["QUEUE_ID"] = $rowQueue["queue_id"];
			$arrGroupUserAcount["QUEUE_TYPE"] = $rowQueue["queue_type"];
			
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