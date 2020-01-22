<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','task_topic','start_date','end_date','create_by'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','calendarcoop')){
		$insertTaskEvent = $conmysql->prepare("INSERT INTO `gctaskevent`(`task_topic`, `task_detail`, `start_date`, `end_date`,`event_start_time`,`event_end_time`,`is_settime`,`is_notify`,`is_notify_before`,`create_by`) 
							VALUES (:task_topic,:task_detail,:start_date,:end_date,:event_start_time,:event_end_time,:is_settime,:create_by)");
		if($insertTaskEvent->execute([
			':task_topic' => $dataComing["task_topic"],
			':task_detail' => $dataComing["task_detail"],
			':start_date' => $dataComing["start_date"],
			':end_date'=> $dataComing["end_date"],
			':event_start_time'=> $dataComing["start_time"] == '' ? null : $dataComing["start_time"],
			':event_end_time'=> $dataComing["end_time"] == '' ? null : $dataComing["end_time"],
			':is_settime'=> $dataComing["is_settime"],
			':is_notify'=> $dataComing["is_notify"],
			':is_notify_before'=> $dataComing["is_notify_before"],
			':create_by'=> $dataComing["create_by"]
		])){
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มกิจกรรมได้ กรุณาติดต่อผู้พัฒนา";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
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