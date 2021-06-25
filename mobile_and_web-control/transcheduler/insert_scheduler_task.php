<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component', 'transaction_type', 'from_account', 'scheduler_type', 'start_date', 'amt_transfer'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ScheduleList')){
		$dateOper = date('c');
		$scheduler_date = null;
		$start_date = null;
		$end_date = null;
		$scheduler_status = '0';
		
		//หาวันที่ทำรายการ
		if($dataComing["scheduler_type"] == '2') {
			$start_date = $dataComing["start_date"];
			$every = intval($dataComing["every_date"]);
			if(date('j', strtotime($start_date)) <= $every){
				//วันที่เริ่มต้น <= every_date
				if($every <= date('t', strtotime($start_date))) { 
					//วันที่ของเดือนมีจริง
					$scheduler_date = date('Y-m-d', strtotime(date('Y-m',strtotime($start_date)).'-'.$every));
				}else {
					//วันที่ไม่มีจริง ปัดเป็นวันสุดท้ายของเดือน
					$scheduler_date = date('Y-m-t', strtotime($start_date));
				}
			}else {
				//วันที่เริ่มต้น > every_date รายการจะเริ่มเดือนถัดไป
				$last_day_of_next_month = date('Y-m-d', strtotime('last day of next month', strtotime($start_date)));
				if($every <= date('t', strtotime($last_day_of_next_month))) {
					//วันที่ของเดือนมีจริง
					$scheduler_date = date('Y-m-d', strtotime(date('Y-m', strtotime($last_day_of_next_month)).'-'.$every));
				}else {
					//วันที่ไม่มีจริง ปัดเป็นวันสุดท้ายของเดือน
					$scheduler_date = $last_day_of_next_month;
				}
			}
			
			$end_date = $dataComing["end_date"] ?? null;
			
			if(!empty($end_date) && (strtotime($end_date) < strtotime($scheduler_date))){
				//ถ้าวันที่สินสุดน้อยกว่าวันที่รอทำรายการให้ยกเลิกรายการ
				$scheduler_status = '-9';
			}
		}else {
			$scheduler_date = $dataComing["start_date"];
		}
		
		$insertSchedule = $conmysql->prepare("INSERT INTO gctransactionschedule(transaction_type,from_account,destination,scheduler_type,scheduler_date,start_date,end_date,
											every_date,amt_transfer,bank_code,member_no,id_userlogin,app_version,scheduler_status)
											VALUES(:transaction_type,:from_account,:destination,:scheduler_type,:scheduler_date,:start_date,:end_date,:every_date,:amt_transfer,
											:bank_code,:member_no,:id_userlogin,:app_version,:scheduler_status)");
		if($insertSchedule->execute([
			':transaction_type' => $dataComing["transaction_type"],
			':from_account' => $dataComing["from_account"],
			':destination' => $dataComing["destination"] ?? null,
			':scheduler_type' => $dataComing["scheduler_type"],
			':start_date' => $start_date,
			':end_date' => $end_date,
			':every_date' => $dataComing["every_date"] ?? null,
			':scheduler_date' => $scheduler_date,
			':amt_transfer' => $dataComing["amt_transfer"],
			':bank_code' => $dataComing["bank_code"] ?? null,
			':member_no' => $payload["member_no"],
			':id_userlogin' => $payload["id_userlogin"],
			':app_version' => $dataComing["app_version"],
			':scheduler_status' => $scheduler_status
		])){
			$arrayResult["TRANSACTION_DATE"] = $lib->convertdate($dateOper,'D m Y',true);
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS1029",
				":error_desc" => "สร้างรายการล่วงหน้าไม่ได้ไม่สามารถ Insert gctransactionschedule ได้"."\n"."Query => ".$insertSchedule->queryString."\n".json_encode([
					':transaction_type' => $dataComing["transaction_type"],
					':from_account' => $dataComing["from_account"],
					':destination' => $dataComing["destination"] ?? null,
					':scheduler_type' => $dataComing["scheduler_type"],
					':start_date' => $start_date,
					':end_date' => $end_date,
					':every_date' => $dataComing["every_date"] ?? null,
					':scheduler_date' => $scheduler_date,
					':amt_transfer' => $dataComing["amt_transfer"],
					':bank_code' => $dataComing["bank_code"] ?? null,
					':member_no' => $payload["member_no"],
					':id_userlogin' => $payload["id_userlogin"],
					':app_version' => $dataComing["app_version"],
					':scheduler_status' => $scheduler_status
				]),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "สร้างรายการล่วงหน้าไม่ได้ไม่สามารถ Insert gctransactionschedule ได้"."\n"."Query => ".$insertSchedule->queryString."\n".json_encode([
				':transaction_type' => $dataComing["transaction_type"],
				':from_account' => $dataComing["from_account"],
				':destination' => $dataComing["destination"] ?? null,
				':scheduler_type' => $dataComing["scheduler_type"],
				':start_date' => $start_date,
				':end_date' => $end_date,
				':every_date' => $dataComing["every_date"] ?? null,
				':scheduler_date' => $scheduler_date,
				':amt_transfer' => $dataComing["amt_transfer"],
				':bank_code' => $dataComing["bank_code"] ?? null,
				':member_no' => $payload["member_no"],
				':id_userlogin' => $payload["id_userlogin"],
				':app_version' => $dataComing["app_version"],
				':scheduler_status' => $scheduler_status
			]);
			$lib->sendLineNotify($message_error);
			$arrayResult['RESPONSE_CODE'] = "WS1029";
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