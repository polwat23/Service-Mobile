<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','id_const_welfare','assisttype_code','assist_year'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'AssistRequest')){
		$insertBulkColumn = array();
		$insertBulkData = array();
		$getColumnFormat = $conmysql->prepare("SELECT input_name
												FROM gcformatreqwelfare
												WHERE id_const_welfare = :id_const_welfare and is_use = '1'");
		$getColumnFormat->execute([':id_const_welfare' => $dataComing["id_const_welfare"]]);
		while($rowColumn = $getColumnFormat->fetch(PDO::FETCH_ASSOC)){
			$insertBulkColumn[] = $rowColumn["input_name"];
			$insertBulkData[] = ':'.$rowColumn["input_name"];
		}
		$insertBulkColumn[] = "member_no";
		$insertBulkColumn[] = "assisttype_code";
		$insertBulkColumn[] = "coop_id";
		$insertBulkColumn[] = "req_status";
		$insertBulkData[] = ":member_no";
		$insertBulkData[] = ":assisttype_code";
		$insertBulkData[] = ":coop_id";
		$insertBulkData[] = ":req_status";
		$textColumnInsert = "(".implode(",",$insertBulkColumn).")";
		$textDataInsert = "(".implode(",",$insertBulkData).")";
		$arrayExecute = array();
		foreach($insertBulkColumn as $keyExe){
			if($keyExe == 'coop_id'){
				$arrayExecute[':'.$keyExe] = "050001";
			}else if($keyExe == 'member_no'){
				$arrayExecute[':'.$keyExe] = $payload[$keyExe];
			}else if($keyExe == 'req_status'){
				$arrayExecute[':'.$keyExe] = "8";
			}else{
				$arrayExecute[':'.$keyExe] = $dataComing[$keyExe];
			}
		}
		$insertToAssistMast = $conoracle->prepare("INSERT INTO ASSREQMASTERONLINE".$textColumnInsert." VALUES".$textDataInsert);
		if($insertToAssistMast->execute($arrayExecute)){
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS0055",
				":error_desc" => "ไม่สามารถขอทุนสวัสดิการได้"."\n".json_encode($dataComing),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ไฟล์ ".$filename." ไม่สามารถขอทุนสวัสดิการได้"."\n"."Query => ".$insertToAssistMast->queryString."\n"."DATA => ".json_encode($arrayExecute);
			$lib->sendLineNotify($message_error);
			$arrayResult['RESPONSE_CODE'] = "WS0055";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>