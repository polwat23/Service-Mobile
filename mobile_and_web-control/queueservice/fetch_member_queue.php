<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'QueueService')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrayGroup = array();
		$currentTime = date("H.i");
		$fetchQueue = $conmysql->prepare("SELECT qdt.member_no,qdt.queuedt_id,qmt.queue_id,qmt.coop_branch_id,qmt.max_queue,qmt.queue_date,qmt.queue_starttime,qmt.queue_endtime,qmt.queue_status,qmt.remain_queue
														FROM gcqueuedetail qdt
														LEFT JOIN gcqueuemaster qmt ON qdt.queue_id = qmt.queue_id
														WHERE qdt.member_no = :member_no AND qdt.is_use = '1' AND qmt.queue_date >= CURDATE()");
		$fetchQueue->execute([
			':member_no' => $member_no
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
			$arrGroupUserAcount["REMAIN_QUEUE"] = $rowQueue["remain_queue"];
			$arrGroupUserAcount["COOP_BRANCH_DESC"] = null;
			if(date("Y-m-d") == $rowQueue["queue_date"]){
				$arrayResult['IS_CAN_CANCEL'] = false;
			}else if((int)date("Ymd",strtotime("+1 day")) == (int)date_create($rowQueue["queue_date"])->format("Ymd")){
				if((float)$currentTime > 16.00){
					$arrayResult['IS_CAN_CANCEL'] = false;
				}else{
					$arrayResult['IS_CAN_CANCEL'] = TRUE;
				}
			}else{
				$arrayResult['IS_CAN_CANCEL'] = TRUE;
			}
			
			$fetchBranch = $conoracle->prepare("SELECT PREFIX_COOP, COOP_ID FROM cmcoopmaster WHERE COOP_ID = :coop_id");
			$fetchBranch->execute([
				':coop_id' => $rowQueue["coop_branch_id"]
			]);
		
			while($rowBranch = $fetchBranch->fetch(PDO::FETCH_ASSOC)){
				$arrGroupUserAcount["COOP_BRANCH_DESC"] = $rowBranch["PREFIX_COOP"];
			}
			$arrayGroup[] = $arrGroupUserAcount;
		}
		
		$arrayResult['MEMBER_QUEUE'] = $arrayGroup;
		$arrayResult['RESULT'] = TRUE;
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