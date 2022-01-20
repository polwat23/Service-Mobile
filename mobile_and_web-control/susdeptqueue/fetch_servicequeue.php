<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SuspendingDebtQueue')){
		$arrayGroup = array();
		$currentTime = date("H.i");
		$fetchBranch = $conmysql->prepare("SELECT qmt.queue_id, qmt.coop_branch_id, qmt.max_queue, qmt.queue_date, qmt.queue_starttime, qmt.queue_endtime, qmt.queue_status,qmt.remain_queue
														FROM gcsusdebtqueuemaster qmt
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
			if((int)date("Ymd") >= (int)date_create($rowBranch["queue_date"])->format("Ymd")){
					$arrGroupUserAcount["IS_DISABLE"] = true;
					$arrGroupUserAcount["IS_HIDE"] = false;
			}else if((int)date("Ymd",strtotime("+1 day")) == (int)date_create($rowBranch["queue_date"])->format("Ymd")){
					
				if($rowBranch["queue_status"] == '1' && $rowBranch["remain_queue"] > 0){
					$arrGroupUserAcount["ERROR_MSG"] = "ปิดรับคิว เวลา 16.00 น.";
				}
			
				if((float)$currentTime > 16.00 && $rowBranch["queue_status"] == '1' && $rowBranch["remain_queue"] > 0){
					$arrGroupUserAcount["IS_DISABLE"] = true;
					$arrGroupUserAcount["IS_HIDE"] = false;
				}
			}
			
			$arrGroupUserAcount["MEMBERS"] = array();
			
			$fetchMember= $conmysql->prepare("SELECT member_no FROM gcsusdebtqueuedetail WHERE queue_id = :queue_id");
			$fetchMember->execute([
				':queue_id' => $rowBranch["queue_id"]
			]);
			
			while($rowMember = $fetchMember->fetch(PDO::FETCH_ASSOC)){
				$arrMember = array();
				$arrMember["MEMBER_NO"] = $rowMember["member_no"];
			
				$arrGroupUserAcount["MEMBERS"][] = $arrMember;
			}
			
			$arrayGroup[] = $arrGroupUserAcount;
		}
		$arrayResult["QUEUES_DATA"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		require_once('../../include/exit_footer.php');
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../include/exit_footer.php');
		
	}
}else{
	$filename = basename(__FILE__, '.php');
	$logStruc = [
		":error_menu" => $filename,
		":error_code" => "WS4004",
		":error_desc" => "ส่ง Argument มาไม่ครบ "."\n".json_encode($dataComing),
		":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
	];
	$log->writeLog('errorusage',$logStruc);
	$message_error = "ไฟล์ ".$filename." ส่ง Argument มาไม่ครบมาแค่ "."\n".json_encode($dataComing);
	$lib->sendLineNotify($message_error);
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
	
}
?>