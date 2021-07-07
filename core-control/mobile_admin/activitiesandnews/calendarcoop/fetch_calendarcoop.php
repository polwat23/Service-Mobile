<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','calendarcoop')){
		$arrayGroup = array();
		
		$fetchCalendar= $conoracle->prepare("SELECT id_task,task_topic,task_detail,TO_CHAR(start_date,'YYYY-MM-DD') as start_date,
												TO_CHAR(end_date,'YYYY-MM-DD') as end_date, event_start_time,event_end_time,is_settime,is_notify,is_notify_before,event_html
												FROM gctaskevent");
		$fetchCalendar->execute();
		while($rowCalendar = $fetchCalendar->fetch(PDO::FETCH_ASSOC)){
			$arrConstans = array();
			$arrConstans["ID_TASK"] = $rowCalendar["ID_TASK"];
			$arrConstans["TASK_TOPIC"] = $rowCalendar["TASK_TOPIC"];
			$arrConstans["TASK_DETAIL"] = $rowCalendar["TASK_DETAIL"] ?? "";
			$arrConstans["EVENT_HTML"] = stream_get_contents($rowCalendar["EVENT_HTML"]);
			$arrConstans["START_DATE"] = $rowCalendar["START_DATE"];
			$arrConstans["END_DATE"] = $rowCalendar["END_DATE"];
			$arrConstans["START_TIME"] = $rowCalendar["EVENT_START_TIME"];
			$arrConstans["END_TIME"] = $rowCalendar["EVENT_END_TIME"];
			$arrConstans["IS_SETTIME"] = $rowCalendar["IS_SETTIME"] == 1 ? true : false;
			$arrConstans["IS_NOTIFY"] = $rowCalendar["IS_NOTIFY"] == 1 ? true : false;
			$arrConstans["IS_NOTIFY_BEFORE"] = $rowCalendar["IS_NOTIFY_BEFORE"] == 1 ? true : false;
			$arrayGroup[] = $arrConstans;
		}
		
		$arrayResult["EVENT_DATA"] = $arrayGroup;
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