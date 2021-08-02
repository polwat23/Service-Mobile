<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','coop_branch_id','queue_type'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanRequestQueue')){
		$arrayGroup = array();
		$fetchBranch = $conmysql->prepare("SELECT queue_date,coop_branch_id,SUM(max_queue) as max_queue,SUM(remain_queue) as remain_queue FROM gcloanreqqueuemaster WHERE queue_status = '1' AND coop_branch_id = :coop_branch_id AND queue_type = :queue_type AND
		queue_date BETWEEN CURDATE() AND CURDATE() + INTERVAL 2 MONTH
		GROUP BY queue_date");
		$fetchBranch->execute([
			':coop_branch_id' => $dataComing["coop_branch_id"],
			':queue_type' => $dataComing["queue_type"]
		]);
		while($rowBranch = $fetchBranch->fetch(PDO::FETCH_ASSOC)){
			$arrGroupUserAcount = array();
			$arrGroupUserAcount["QUEUE_DATE"] = $rowBranch["queue_date"];
			$arrGroupUserAcount["COOP_BRANCH_ID"] = $rowBranch["coop_branch_id"];
			$arrGroupUserAcount["MAX_QUEUE"] = $rowBranch["max_queue"];
			$arrGroupUserAcount["REMAIN_QUEUE"] = $rowBranch["remain_queue"];
			
			$arrayGroup[] = $arrGroupUserAcount;
		}
		
		$arrayResult['QUEUES_DATE'] = $arrayGroup;
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