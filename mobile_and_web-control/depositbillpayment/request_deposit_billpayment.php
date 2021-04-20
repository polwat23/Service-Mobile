<?php
ini_set('default_socket_timeout', 300);
require_once('../autoload.php');

if($lib->checkCompleteArgument(['tran_id'],$dataComing)){
	$lang_locale = 'en';
	$rowCheckBill = $checkBillAvailable->fetch(PDO::FETCH_ASSOC);
	$fee_amt = 0;
	$dateOperC = date('c');
	$dateOper = date('Y-m-d H:i:s',strtotime($dateOperC));
	$updateStampMaster = $conmysql->prepare("UPDATE gcqrcodegenmaster SET transfer_status = '1' WHERE qrgenerate = :tran_id");
	if($updateStampMaster->execute([':tran_id' => $dataComing["tran_id"]])){
		$arrayResult['RESULT'] = TRUE;
		ob_flush();
		echo json_encode($arrayResult);
		exit();
	}else{
		$message_error = "ไม่สามารถ UPDATE ในตาราง gcqrcodegenmaster ".$updateStampMaster->queryString."\n"."Data => ".
		json_encode([':tran_id' => $dataComing["tran_id"]]);
		$lib->sendLineNotify($message_error);
		$message_error = "มีรายการฝากมาจาก Billpayment ตัดเงินเรียบร้อยแต่ไม่สามารถ Update สถานะรายการ QR ได้ เลขรหัสรายการ ".$dataComing["tran_id"].
		" ตรวจสอบได้ที่หน้าจอรายการ QR";
		$lib->sendLineNotify($message_error,$config["LINE_NOTIFY_DEPOSIT"]);
		$arrayResult['RESULT'] = TRUE;
		ob_flush();
		echo json_encode($arrayResult);
		exit();
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
	$arrayResult['RESPONSE_MESSAGE_SOURCE'] = $arrayResult['RESPONSE_MESSAGE'];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	ob_flush();
	echo json_encode($arrayResult);
	exit();
}
?>