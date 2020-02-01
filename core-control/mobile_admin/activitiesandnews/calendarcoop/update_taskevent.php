<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','task_topic','start_date','end_date','create_by','id_task'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','calendarcoop')){
		
		$UpdateTaskEvent = $conmysql->prepare("UPDATE gctaskevent SET task_topic = :task_topic, task_detail = :task_detail, start_date = :start_date, end_date = :end_date,
											event_start_time = :event_start_time,event_end_time = :event_end_time ,is_settime = :is_settime,create_by = :create_by,
											is_notify = :is_notify,is_notify_before = :is_notify_before
											WHERE id_task = :id_task");
		if($UpdateTaskEvent->execute([
			':task_topic' => $dataComing["task_topic"],
			':task_detail' => $dataComing["task_detail"] == '' ? null : $dataComing["task_detail"],
			':start_date' => $dataComing["start_date"],
			':end_date'=> $dataComing["end_date"],
			':event_start_time'=> $dataComing["start_time"] == '' ? null : $dataComing["start_time"],
			':event_end_time'=> $dataComing["end_time"] == '' ? null : $dataComing["end_time"],
			':is_settime'=> $dataComing["is_settime"],
			':create_by'=> $dataComing["create_by"],
			':is_notify'=> $dataComing["is_notify"],
			':is_notify_before'=> $dataComing["is_notify_before"],
			':id_task'=> $dataComing["id_task"]
		])){
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขกิจกรรมได้ กรุณาติดต่อผู้พัฒนา";
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