<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'Event')){
		$arrayGroupNews = array();
		$fetchEvent = $conmysql->prepare("SELECT id_task,task_topic,task_detail,start_date,end_date,
										date_format(event_start_time,'%H:%i') as event_start_time,
										date_format(event_end_time,'%H:%i') as event_end_time,
										is_settime,create_date,update_date,is_notify,is_notify_before,create_by
										FROM gctaskevent
										WHERE start_date >= CURDATE() or end_date >= CURDATE() ORDER BY start_date DESC");
		$fetchEvent->execute();
		while($rowEvent = $fetchEvent->fetch(PDO::FETCH_ASSOC)){
			$arrayEvent = array();
			$arrayEvent["ID_TASK"] = $lib->text_limit($rowEvent["id_task"]);
			$arrayEvent["TASK_TOPIC"] = $lib->text_limit($rowEvent["task_topic"]);
			$arrayEvent["TASK_DETAIL"] = $lib->text_limit($rowEvent["task_detail"],100);
			$arrayEvent["START_DATE"] = $lib->convertdate($rowEvent["start_date"],'D m Y');
			$arrayEvent["START_DATE_RAW"] = $lib->convertdate($rowEvent["start_date"],'D-n-y');
			$arrayEvent["END_DATE"] = $lib->convertdate($rowEvent["end_date"],'D m Y');
			$arrayEvent["END_DATE_RAW"] = $lib->convertdate($rowEvent["end_date"],'D-n-y');
			$arrayEvent["START_TIME"] = $rowEvent["event_start_time"];
			$arrayEvent["END_TIME"] = $rowEvent["event_end_time"];
			$arrayEvent["IS_SETTIME"] = $rowEvent["is_settime"];
			$arrayEvent["CREATE_DATE"] = $lib->convertdate($rowEvent["create_date"],'D m Y',true);
			$arrayEvent["UPDATE_DATE"] = $lib->convertdate($rowEvent["update_date"],'D m Y',true);
			$arrayEvent["IS_NOTIFY"] = $rowEvent["is_notify"];
			$arrayEvent["IS_NOTIFY_BEFORE"] = $rowEvent["is_notify_before"];
			$arrayEvent["CREATE_BY"] = $rowEvent["create_by"];
			$arrayGroupNews[] = $arrayEvent;
		}
		$arrayResult['EVENT'] = $arrayGroupNews;
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>