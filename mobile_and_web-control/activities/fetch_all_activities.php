<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'Event')){
		$arrayGroupNews = array();
		$fetchEvent = $conoracle->prepare("SELECT id_task,task_topic,task_detail,start_date,end_date,
										TO_DATE(event_start_time,'HH24:MI') as event_start_time,
										TO_DATE(event_end_time,'HH24:MI') as event_end_time,
										is_settime,create_date,update_date,is_notify,is_notify_before,create_by,event_html
										FROM gctaskevent WHERE is_use = '1' ORDER BY start_date DESC");
		$fetchEvent->execute();
		while($rowEvent = $fetchEvent->fetch(PDO::FETCH_ASSOC)){
			$arrayEvent = array();
			$arrayEvent["ID_TASK"] = $lib->text_limit($rowEvent["ID_TASK"]);
			$arrayEvent["TASK_TOPIC"] = $lib->text_limit($rowEvent["TASK_TOPIC"]);
			$arrayEvent["TASK_DETAIL"] = $lib->text_limit($rowEvent["TASK_DETAIL"],100);
			$arrayEvent["START_DATE"] = $lib->convertdate($rowEvent["START_DATE"],'D m Y');
			$arrayEvent["START_DATE_RAW"] = $lib->convertdate($rowEvent["START_DATE"],'D-n-y');
			$arrayEvent["END_DATE"] = $lib->convertdate($rowEvent["END_DATE"],'D m Y');
			$arrayEvent["END_DATE_RAW"] = $lib->convertdate($rowEvent["END_DATE"],'D-n-y');
			$arrayEvent["START_TIME"] = $rowEvent["EVENT_START_TIME"];
			$arrayEvent["END_TIME"] = $rowEvent["EVENT_END_TIME"];
			$arrayEvent["IS_SETTIME"] = $rowEvent["IS_SETTIME"];
			$arrayEvent["CREATE_DATE"] = $lib->convertdate($rowEvent["CREATE_DATE"],'D m Y',true);
			$arrayEvent["UPDATE_DATE"] = $lib->convertdate($rowEvent["UPDATE_DATE"],'D m Y',true);
			$arrayEvent["IS_NOTIFY"] = $rowEvent["IS_NOTIFY"];
			$arrayEvent["IS_NOTIFY_BEFORE"] = $rowEvent["IS_NOTIFY_BEFORE"];
			$arrayEvent["CREATE_BY"] = $rowEvent["CREATE_BY"];
			$arrayEvent["EVENT_HTML"] = $rowEvent["EVENT_HTML"];
			$arrayGroupNews[] = $arrayEvent;
		}
		$arrayResult['EVENT'] = $arrayGroupNews;
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