<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component', 'cremation_code'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DeceasedInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		
		$date_now = date('Y-m-d');
		$date_before = date('Y-m-d',strtotime('-12 months'));
	
		$arrayDataWcGrp = array();
		$arrayDataWcAcc = array();
		$arrayNameGrp = array();

		$getDataWc = $conmysql->prepare("SELECT full_name, cremation_amt, data_date FROM gccremationlist WHERE is_use = '1' AND data_date BETWEEN :datebefore AND :datenow AND cremation_coop = :cremation_coop  ORDER BY data_date DESC");
		$getDataWc->execute([
			':datebefore' => $date_before,
			':datenow' => $date_now,
			':cremation_coop' => $dataComing["cremation_code"]
		]);
		
		while($rowDataWc = $getDataWc->fetch(PDO::FETCH_ASSOC)){
			$arrayName = array();
			$arrayName["FULL_NAME"] = $rowDataWc["full_name"];
			$arrayName["CREMATION_AMT_NO_FORMAT"] = $rowDataWc["cremation_amt"];
			$arrayName["CREMATION_AMT"] = number_format($rowDataWc["cremation_amt"],2);
			$arrayNameGrp[$rowDataWc["data_date"]][] = $arrayName;
		}
		
		foreach($arrayNameGrp as $key=>$value) {
			if(in_array($key,$arrayDataWcAcc) === FALSE){
				$arrayDataWc = array();
				$arrayDataWc["LIST"] = $arrayNameGrp[$key];
				$arrayDataWc["MONTH"] = $lib->convertperiodkp(date("Ym", strtotime($key)), true);
				$arrayDataWc["TOTAL_AMT"] = number_format(array_sum(array_column($arrayNameGrp[$key], 'CREMATION_AMT_NO_FORMAT') ?? 0),2);
				$arrayDataWcGrp[] = $arrayDataWc;
				$arrayDataWcAcc[] = $key;
			}
		}

		$arrayResult['DECEASED'] = $arrayDataWcGrp;
		$arrayResult['RESULT'] = TRUE;
	
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
