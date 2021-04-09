<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','transactionschedulelist')){
		
		$getSchedule = $conmysql->prepare("SELECT ts.id_transchedule, ts.member_no, ts.transaction_type, ts.from_account, ts.destination,
										ts.create_date, ts.update_date, ts.start_date, ts.end_date, ts.scheduler_type, ts.every_date, 
										ts.scheduler_date, ts.transaction_date, ts.amt_transfer, ts.bank_code, ts.id_userlogin, 
										ts.app_version, ts.scheduler_status , cs.bank_short_name
										FROM gctransactionschedule ts
                                        LEFT JOIN csbankdisplay cs ON ts.bank_code = cs.bank_code
										ORDER BY ts.id_transchedule desc");
										
		$getSchedule->execute();
		while($rowSchedule = $getSchedule->fetch(PDO::FETCH_ASSOC)){
			$arrRePay = array();
			$arrRePay["ID_TRANSCHEDULE"] = $rowSchedule["id_transchedule"];
			$arrRePay["MEMBER_NO"] = $rowSchedule["member_no"];
			
			if($rowSchedule["transaction_type"] == "1"){
				$arrRePay["TRANSACTION_TYPE"] = "โอนเงินภายใน";
			}else if($rowSchedule["transaction_type"] == "2"){
				$arrRePay["TRANSACTION_TYPE"] = "ชำระหนี้";
			}else if($rowSchedule["transaction_type"] == "3"){
				$arrRePay["TRANSACTION_TYPE"] = "ซื้อหุ้น";
			}else if($rowSchedule["transaction_type"] == "4"){
				$arrRePay["TRANSACTION_TYPE"] = "ฝากเงิน";
			}else if($rowSchedule["transaction_type"] == "5"){
				$arrRePay["TRANSACTION_TYPE"] = "ถอนเงิน";
			}
			
			$arrRePay["FROM_ACCOUNT"] = $rowSchedule["from_account"];
			$arrRePay["DESTINATION"] = $rowSchedule["destination"];
			$arrRePay["CREATE_DATE"] = $rowSchedule["create_date"];
			$arrRePay["UPDATE_DATE"] = $rowSchedule["update_date"];
			$arrRePay["START_DATE"] = $rowSchedule["start_date"];
			$arrRePay["END_DATE"] = $rowSchedule["end_date"];
			if($rowSchedule["scheduler_type"] == "1"){
				$arrRePay["SCHEDULER_TYPE"] = "ตั้งครั้งเดียว";
			}else if($rowSchedule["scheduler_type"] == "2"){
				$arrRePay["SCHEDULER_TYPE"] = "ตั้งเป็นช่วงเวลา";
			}
			$arrRePay["EVERY_DATE"] = $rowSchedule["every_date"];
			$arrRePay["SCHEDULER_DATE"] = $rowSchedule["scheduler_date"];
			$arrRePay["TRANSACTION_DATE"] = $rowSchedule["transaction_date"];
			$arrRePay["AMT_TRANSFER"] = $rowSchedule["amt_transfer"];
			$arrRePay["BANK_CODE"] = $rowSchedule["bank_short_name"];
			$arrRePay["ID_USERLOGIN"] = $rowSchedule["id_userlogin"];
			$arrRePay["APP_VERSION"] = $rowSchedule["app_version"];
			
			if($rowSchedule["scheduler_status"] == "0"){
				$arrRePay["SCHEDULER_STATUS"] = "รอทำรายการ";
				$arrRePay["STATUS_COLOR"] = null;
			}else if($rowSchedule["scheduler_status"] == "1"){
				$arrRePay["SCHEDULER_STATUS"] = "ทำรายการเสร็จแล้ว";
				$arrRePay["STATUS_COLOR"] = '#00917c';
			}else if($rowSchedule["scheduler_status"] == "-9"){
				$arrRePay["SCHEDULER_STATUS"] = "ยกเลิกการตั้งเวลา";
				$arrRePay["STATUS_COLOR"] = '#c64756';
			}else if($rowSchedule["scheduler_status"] == "-99"){
				$arrRePay["SCHEDULER_STATUS"] = "รายการล้มเหลว";
				$arrRePay["STATUS_COLOR"] = '#c64756';
			}
			
			$arrGrp[] = $arrRePay;
		}
			
		$arrayResult['TRANSACTIONSCHEDULE_LIST'] = $arrGrp;
		$arrayResult['RESULT'] = TRUE;
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