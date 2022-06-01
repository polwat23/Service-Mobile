<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component', 'reqdoc_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DocumentRequestTrack')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		
		$conmysql->beginTransaction();
		$updateReqDoc = $conmysql->prepare("UPDATE gcreqdoconline SET req_status = '9' WHERE reqdoc_no  = :reqdoc_no");
		if($updateReqDoc->execute([
			':reqdoc_no' => $dataComing["reqdoc_no"]
		])){
			//Start ยกเลิกสลิปชำระหนี้
			$cancelReq = $conmysql->prepare("UPDATE gcslippaydept SET req_status = '9' WHERE reqdoc_no  = :reqdoc_no");
			if($cancelReq->execute([':reqdoc_no' => $dataComing["reqdoc_no"]])){
			
			}else{
				$conmysql->rollback();
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS1037",
					":error_desc" => "ยกเลิกรายการอัปโหลดสลิปชำระหนี้ไม่ได้เพราะไม่สามารถ Update ลง gcslippaydept ได้"."\n"."Query => ".$cancelReq->queryString."\n"."Param => ". json_encode([':reqdoc_no' => $dataComing["reqdoc_no"]]),
					":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$message_error = "ยกเลิกรายการอัปโหลดสลิปชำระหนี้ไม่ได้เพราะไม่สามารถ Update ลง gcslippaydept ได้"."\n"."Query => ".$cancelReq->queryString."\n"."Param => ". json_encode([':reqdoc_no' => $dataComing["reqdoc_no"]]);
				$lib->sendLineNotify($message_error);
				$arrayResult['RESPONSE_CODE'] = "WS1037";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
			//End ยกเลิกสลิปชำระหนี้
			
			$conmysql->commit();
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$conmysql->rollback();
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS0125",
				":error_desc" => "ยกเลิกใบคำขอออนไลน์ไม่ได้เพราะไม่สามารถ Update ลง gcreqdoconline ได้"."\n".json_encode($dataComing),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ไฟล์ ".$filename." ยกเลิกใบคำขอออนไลน์ไม่ได้เพราะไม่สามารถ Update ลง gcreqdoconline ได้"."\n"."Query => ".$updateReqDoc->queryString."\n"."DATA => ".json_encode([
				':reqdoc_no' => $dataComing["reqdoc_no"]
			]);
			$lib->sendLineNotify($message_error);
			$arrayResult['RESPONSE_CODE'] = "WS0125";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
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