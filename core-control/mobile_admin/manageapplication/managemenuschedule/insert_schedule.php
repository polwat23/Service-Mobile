<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_menu','schedule_type','schedule_start','start_menu_status','start_menu_channel'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managemenuschedule')){
			
			$schedule_end = null;
			if($dataComing["schedule_type"] == "onetime"){
				if($dataComing["schedule_end"] != ""){
					$schedule_end = $dataComing["schedule_end"];
				}else{
					$schedule_end = null;
				}	
			}else{
				if($dataComing["end_schedule_timedate"] != "" ){
					$schedule_end = $dataComing["end_schedule_timedate"];
				}else{
					$schedule_end = null;
				}
			}
			
			$time_end = null;
			if($dataComing["schedule_type"] == "everyday" && $dataComing["schedule_end"] != ""){
				$time_end = $dataComing["schedule_end"];
			}
			
			$insert_schedule = $conmysql->prepare("INSERT INTO gcmenuschedule(id_menu, schedule_type, schedule_start, start_menu_status, start_menu_channel, schedule_end, end_menu_status, end_menu_channel, create_by, update_by, time_end) 
																VALUES (:id_menu, :schedule_type, :schedule_start, :start_menu_status, :start_menu_channel, :schedule_end, :end_menu_status, :end_menu_channel, :create_by, :update_by, :time_end)");
			if($insert_schedule->execute([
					':id_menu' => $dataComing["id_menu"],
					':schedule_type' => $dataComing["schedule_type"],
					':schedule_start' => $dataComing["schedule_start"],
					':start_menu_status' => $dataComing["start_menu_status"],
					':start_menu_channel' => $dataComing["start_menu_channel"],
					':schedule_end' => $schedule_end,
					':end_menu_status' => $dataComing["end_menu_status"] != "" ? $dataComing["end_menu_status"]  : null,
					':end_menu_channel' => $dataComing["end_menu_channel"] != "" ? $dataComing["end_menu_channel"]  : null,
					':create_by' => $payload["username"],
					':update_by' => $payload["username"],
					':time_end' => $time_end
			])){
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = "เพิ่มกำหนดการไม่สำเร็จ";
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