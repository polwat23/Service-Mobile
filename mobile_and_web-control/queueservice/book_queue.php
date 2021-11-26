<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','queue_id'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'QueueService')){
		$conmysql->beginTransaction();
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		
		$fetchBranch = $conmysql->prepare("SELECT qmt.queue_id, qmt.coop_branch_id, qmt.max_queue, qmt.queue_date, qmt.queue_starttime, qmt.queue_endtime, qmt.queue_status,qmt.remain_queue
														FROM gcqueuemaster qmt
														WHERE qmt.queue_id = :queue_id and queue_status = '1' and remain_queue > 0");
		$fetchBranch->execute([
			':queue_id' => $dataComing["queue_id"]
		]);
		if($fetchBranch->rowCount() > 0){
			$rowBranch = $fetchBranch->fetch(PDO::FETCH_ASSOC);
			
			$insertConst = $conmysql->prepare("INSERT INTO gcqueuedetail (queue_id, member_no) VALUES (:queue_id, :member_no)");
			if($insertConst->execute([
				':queue_id' => $dataComing["queue_id"],
				':member_no' => $member_no
			])){
				// update remain
				$updatemaster = $conmysql->prepare("UPDATE gcqueuemaster SET remain_queue = remain_queue-1 WHERE queue_id = :queue_id");
				if($updatemaster->execute([
					':queue_id' => $dataComing["queue_id"]
				])){
					$conmysql->commit();
					$arrayResult['RESULT'] = TRUE;
					require_once('../../include/exit_footer.php');
				}else{
					$conmysql->rollback();
					$filename = basename(__FILE__, '.php');
					$logStruc = [
						":error_menu" => $filename,
						":error_code" => "WS1038",
						":error_desc" => "ไม่สามารถ updatemaster ลง gcqueuemaster ได้ "."\n".$updatemaster->queryString."\n".json_encode([
							':queue_id' => $dataComing["queue_id"]
						]),
						":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
					];
					$log->writeLog('errorusage',$logStruc);
					$message_error = "ไม่สามารถ updatemaster ลง gcqueuemaster ได้ "."\n".$updatemaster->queryString."\n".json_encode([
						':queue_id' => $dataComing["queue_id"]
					]);
					$lib->sendLineNotify($message_error);
					$arrayResult['RESPONSE_CODE'] = "WS1038";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
				}
				
			}else{
				$conmysql->rollback();
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS1038",
					":error_desc" => "ไม่สามารถ Insert ลง gcqueuedetail ได้ "."\n".$insertConst->queryString."\n".json_encode([
						':queue_id' => $dataComing["queue_id"],
						':member_no' => $dataComing["member_no"]
					]),
					":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$message_error = "ไม่สามารถ Insert ลง gcqueuedetail ได้ "."\n".$insertConst->queryString."\n".json_encode([
					':queue_id' => $dataComing["queue_id"],
					':member_no' => $dataComing["member_no"]
				]);
				$lib->sendLineNotify($message_error);
				$arrayResult['RESPONSE_CODE'] = "WS1038";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		}else{
			$conmysql->rollback();
			$arrayResult['RESPONSE_CODE'] = "WS0116";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
		}
		
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