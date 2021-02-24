<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managememberqueues')){
			
			foreach($dataComing["branch_arr"] as $branch){
				foreach($dataComing["date_arr"] as $date_value){
					$insertConst = $conmysql->prepare("SELECT * FROM gcqueuemaster
																		WHERE queue_status = '1' AND coop_branch_id = :coop_branch_id
																		AND queue_date = :queue_date
																		AND CAST(: AS time) BETWEEN queue_starttime AND queue_endtime");
			
					$fetchQueue->execute([
						':coop_branch_id' => $branch,
						':queue_date' => $date_value,
						':start_time' => $dataComing["start_time"],
						':start_time' => $dataComing["start_time"]
					]);
					while($rowQueue = $fetchQueue->fetch(PDO::FETCH_ASSOC)){
						$arrGroupUserAcount = array();
						$arrGroupUserAcount["QUEUEDT_ID"] = $rowQueue["queuedt_id"];
						$arrGroupUserAcount["QUEUE_ID"] = $rowQueue["queue_id"];
						$arrGroupUserAcount["COOP_BRANCH_ID"] = $rowQueue["coop_branch_id"];
						$arrGroupUserAcount["MAX_QUEUE"] = $rowQueue["max_queue"];
						$arrGroupUserAcount["QUEUE_DATE"] = $rowQueue["queue_date"];
						$arrGroupUserAcount["QUEUE_STARTTIME"] = $rowQueue["queue_starttime"];
						$arrGroupUserAcount["QUEUE_ENDTIME"] = $rowQueue["queue_endtime"];
						$arrGroupUserAcount["QUEUE_STATUS"] = $rowQueue["queue_status"];
						$arrGroupUserAcount["COOP_BRANCH_DESC"] = null;
						
						$fetchBranch = $conoracle->prepare("SELECT PREFIX_COOP, COOP_ID FROM cmcoopmaster WHERE COOP_ID = :coop_id");
						$fetchBranch->execute([
							':coop_id' => $rowQueue["coop_branch_id"]
						]);
					
						while($rowBranch = $fetchBranch->fetch(PDO::FETCH_ASSOC)){
							$arrGroupUserAcount["COOP_BRANCH_DESC"] = $rowBranch["PREFIX_COOP"];
						}
						$arrayGroup[] = $arrGroupUserAcount;
					}
				}
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