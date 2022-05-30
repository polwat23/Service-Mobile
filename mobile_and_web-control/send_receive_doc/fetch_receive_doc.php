<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SendReceiveDocuments')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGroupHis = array();
		
		$file_doc = __DIR__.'/../../resource/member_document';	
		$files = scandir($file_doc);
		
		foreach($files as $file_member){
			$arrFile = array();
			$name_file = $file_member;
			$file_member = substr($file_member,0,8);
			$name = substr($name_file,8);
			if($file_member == $member_no){
				$arrFile["DOC_NAME"] = $name;
				$arrFile["DOC_URL"] = $config["URL_SERVICE"]."resource/member_document/".$name_file;
				$arrGroupHis[] = $arrFile;
			}
		}
		//$lib->sendLineNotify(json_encode($file_doc));
		$getHistory = $conmysql->prepare("SELECT doc_no, doc_filename,create_date,doc_address  FROM gcdocuploadfile WHERE doc_status = '1'");
		$getHistory->execute([':member_no' => $member_no]);
		while($rowHistory = $getHistory->fetch(PDO::FETCH_ASSOC)){
			$arrHistory = array();
			$arrHistory["DOC_NAME"] = $rowHistory["doc_filename"];
			$arrHistory["DOC_DATE"] = $lib->convertdate($rowHistory["create_date"],"D M Y");
			$arrHistory["DOC_URL"] = $rowHistory["doc_address"];
			$arrGroupHis[] = $arrHistory;
		}
		$arrayResult['DOC'] = $arrGroupHis;
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
