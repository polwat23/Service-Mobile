<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'sms','calendarcoop')){
		$arrayGroup = array();
		
		$fetchCalendar= $conmysql->prepare("SELECT id_task,task_topic,task_detail,start_date,end_date
												FROM gctaskevent");
		$fetchCalendar->execute();
		while($rowCalendar = $fetchCalendar->fetch()){
			$arrConstans = array();
			$arrConstans["ID_TASK"] = $rowCalendar["id_task"];
			$arrConstans["TASK_TOPIC"] = $rowCalendar["task_topic"];
			$arrConstans["TASK_DETAIL"] = $rowCalendar["task_detail"];
			$arrConstans["START_DATE"] = $rowCalendar["start_date"];
			$arrConstans["END_DATE"] = $rowCalendar["end_date"];
			$arrayGroup[] = $arrConstans;
		}
		
		$arrayResult["EVENT_DATA"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>