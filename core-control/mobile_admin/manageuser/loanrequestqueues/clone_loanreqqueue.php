<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','loanrequestqueues')){
		
			$conmysql->beginTransaction();
			
				$insertdateArr = array();
				foreach($dataComing["date_arr"] as $date_value){
					$insertdate = "('".$date_value["COOP_BRANCH_ID"]."','".$date_value["QUEUE_DATE"]."','".$date_value["MAX_QUEUE"]."','".$date_value["QUEUE_STARTTIME"]."','".$date_value["QUEUE_ENDTIME"]."','".$date_value["MAX_QUEUE"]."','".$payload["username"]."','".$date_value["QUEUE_TYPE"]."')";
					$insertdateArr[] =  $insertdate;
				}
				$insertConst = $conmysql->prepare("INSERT gcloanreqqueuemaster(coop_branch_id,queue_date,max_queue,queue_starttime,queue_endtime,remain_queue,create_by,queue_type)
															VALUES".implode(',',$insertdateArr));
				if($insertConst->execute()){
					
				}else{
						$conmysql->rollback();
						$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มรอบบริการได้";
						$arrayResult['RESULT'] = FALSE;
						require_once('../../../../include/exit_footer.php');
				}
			
			$conmysql->commit();
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