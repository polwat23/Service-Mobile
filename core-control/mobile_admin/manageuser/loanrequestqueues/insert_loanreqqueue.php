<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','max_queue','start_time','end_time','max_queue','queue_type'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','loanrequestqueues')){
		
			$conmysql->beginTransaction();
			
			foreach($dataComing["branch_arr"] as $branch){
				$insertdateArr = array();
				foreach($dataComing["date_arr"] as $date_value){
					$insertdate = "('".$branch."','".$date_value."','".$dataComing["max_queue"]."','".$dataComing["start_time"]."','".$dataComing["end_time"]."','".$dataComing["max_queue"]."','".$payload["username"]."','".$dataComing["queue_type"]."')";
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