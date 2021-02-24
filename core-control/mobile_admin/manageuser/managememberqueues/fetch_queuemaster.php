<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managememberqueues')){
		$arrayGroup = array();
		$fetchBranch = $conmysql->prepare("SELECT qmt.queue_id, qmt.coop_branch_id, qmt.max_queue, qmt.queue_date, qmt.queue_starttime, qmt.queue_endtime, qmt.queue_status, qmt.remain_queue
														FROM gcqueuemaster qmt
														WHERE qmt.queue_date = :queue_date AND qmt.coop_branch_id = :coop_branch_id");
		$fetchBranch->execute([
			':queue_date' => $dataComing["queue_date"],
			':coop_branch_id' => $dataComing["coop_branch_id"]
		]);
		while($rowBranch = $fetchBranch->fetch(PDO::FETCH_ASSOC)){
			$arrGroupUserAcount = array();
			$arrGroupUserAcount["QUEUE_ID"] = $rowBranch["queue_id"];
			$arrGroupUserAcount["COOP_BRANCH_ID"] = $rowBranch["coop_branch_id"];
			$arrGroupUserAcount["MAX_QUEUE"] = $rowBranch["max_queue"];
			$arrGroupUserAcount["QUEUE_DATE"] = $rowBranch["queue_date"];
			$arrGroupUserAcount["QUEUE_STARTTIME"] = $rowBranch["queue_starttime"];
			$arrGroupUserAcount["QUEUE_ENDTIME"] = $rowBranch["queue_endtime"];
			$arrGroupUserAcount["QUEUE_STATUS"] = $rowBranch["queue_status"];
			$arrGroupUserAcount["REMAIN_QUEUE"] = $rowBranch["remain_queue"];
			$arrGroupUserAcount["MEMBERS"] = array();
			
			$fetchMember= $conmysql->prepare("SELECT member_no,is_use,queuedt_id FROM gcqueuedetail WHERE queue_id = :queue_id");
			$fetchMember->execute([
				':queue_id' => $rowBranch["queue_id"]
			]);
			
			while($rowMember = $fetchMember->fetch(PDO::FETCH_ASSOC)){
				$arrMember = array();
				$arrMember["MEMBER_NO"] = $rowMember["member_no"];
				$arrMember["IS_USE"] = $rowMember["is_use"];
				$arrMember["QUEUEDT_ID"] = $rowMember["queuedt_id"];
				
				$fetchMemberInfo = $conoracle->prepare("SELECT mp.prename_short,mb.memb_name,mb.memb_surname,mb.prename_code
											FROM mbmembmaster mb 
											LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
											WHERE mb.member_no = :member_no");
				$fetchMemberInfo->execute([
					':member_no' => $rowMember["member_no"]
				]);
				
				while($rowMemberInfo = $fetchMemberInfo->fetch(PDO::FETCH_ASSOC)){
					$arrMember["PRENAME_SHORT"] =  $rowMemberInfo["PRENAME_SHORT"] ;
					$arrMember["MEMB_NAME"] =  $rowMemberInfo["MEMB_NAME"] ;
					$arrMember["MEMB_SURNAME"] =  $rowMemberInfo["MEMB_SURNAME"] ;
				}
			
				$arrGroupUserAcount["MEMBERS"][] = $arrMember;
			}
			
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