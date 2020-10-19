<?php
require_once('../autoload.php');

use Endroid\QrCode\QrCode;

if($lib->checkCompleteArgument(['menu_component','trans_code','account_no','amt_transfer'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'GenerateQR')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$stringQRGenerate = "|".$config["CROSSBANK_TAX_SUFFIX"]."\r\n".$member_no."\r\n".$dataComing["trans_code"].$dataComing["account_no"]."\r\n".str_replace('.','',$dataComing["amt_transfer"]);
		$qrCode = new QrCode($stringQRGenerate);
		header('Content-Type: '.$qrCode->getContentType());
		$qrCode->writeString();
		$qrCode->writeFile(__DIR__.'/../../resource/qrcode/'.$payload["member_no"].$dataComing["trans_code"].$dataComing["account_no"].date('YmdHis').'.png');
		$fullPath = $config["URL_SERVICE"].'/resource/qrcode/'.$payload["member_no"].$dataComing["trans_code"].$dataComing["account_no"].date('YmdHis').'.png';
		header('Content-Type: application/json;charset=utf-8');
		$arrayResult["QRCODE_PATH"] = $fullPath;
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
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
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>