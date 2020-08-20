<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SlipInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$limit_period = $func->getConstant('limit_kpmonth');
		$arrayGroupPeriod = array();
		$getPeriodKP = $conmssql->prepare("SELECT * from ((
															SELECT recv_period from kpmastreceive where member_no = :member_no
														) ORDER BY recv_period DESC) where rownum <= :limit_period");
		$getPeriodKP->execute([
				':member_no' => $member_no,
				':limit_period' => $limit_period
		]);
		while($rowPeriod = $getPeriodKP->fetch(PDO::FETCH_ASSOC)){
			$arrKpmonth = array();
			$arrKpmonth["PERIOD"] = $rowPeriod["RECV_PERIOD"];
			$arrKpmonth["MONTH_RECEIVE"] = $lib->convertperiodkp($rowPeriod["RECV_PERIOD"]);
			$getKPDetail = $conmssql->prepare("SELECT kpr.RECEIPT_DATE,kpr.RECEIPT_NO,NVL(sum_item.ITEM_PAYMENT,kpr.RECEIVE_AMT) as RECEIVE_AMT from kpmastreceive kpr,
													(SELECT NVL(SUM(kpd.ITEM_PAYMENT * kut.sign_flag),0) as ITEM_PAYMENT 
													FROM kpmastreceivedet kpd
													LEFT JOIN KPUCFKEEPITEMTYPE kut ON 
													kpd.keepitemtype_code = kut.keepitemtype_code
													where kpd.member_no = :member_no and kpd.recv_period = :recv_period) sum_item
													where kpr.member_no = :member_no and kpr.recv_period = :recv_period and kpr.KEEPING_STATUS = 1");
			$getKPDetail->execute([
				':member_no' => $member_no,
				':recv_period' => $rowPeriod["RECV_PERIOD"]
			]);
			$rowKPDetali = $getKPDetail->fetch(PDO::FETCH_ASSOC);
			$arrKpmonth["SLIP_NO"] = $rowKPDetali["RECEIPT_NO"];
			$arrKpmonth["SLIP_DATE"] = $lib->convertdate($rowKPDetali["RECEIPT_DATE"],'d m Y');
			$arrKpmonth["RECEIVE_AMT"] = number_format($rowKPDetali["RECEIVE_AMT"],2);
			$arrayGroupPeriod[] = $arrKpmonth;
		}
		$arrayResult['KEEPING_LIST'] = $arrayGroupPeriod;
		$arrayResult['RESULT'] = TRUE;
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
