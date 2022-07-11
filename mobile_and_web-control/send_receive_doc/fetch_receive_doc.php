<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SendReceiveDocuments')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGroupHis = array();
		$file_doc = glob(__DIR__.'/../../resource/member_document/*/*/'.$member_no.'/*.pdf');

		foreach($file_doc as $file_member){
			$arrFile = array();
			
			$base_url = str_replace("\\", "/", str_replace(realpath(__DIR__."/../../resource/member_document"), "", realpath($file_member)));
			$path_explode = explode("/", $base_url);
			
			$arrFile["DOC_NAME"] = basename(end($path_explode), ".pdf");
			$file_path = "/resource/member_document/".$base_url;
			
			$file_created_date = date("Y-m-d H:i:s", filectime(realpath($file_member)));
			$arrFile["file_created_date"] = $file_created_date;
			$arrFile["DOC_DATE"] = $lib->convertdate($file_created_date,"D M Y");
			
			if ($forceNewSecurity == true) {
				$arrFile['DOC_URL'] = $config["URL_SERVICE"]."/resource/get_resource?id=".hash("sha256", $file_path);
				$arrFile["DOC_URL_TOKEN"] = $lib->generate_token_access_resource($file_path, $jwt_token, $config["SECRET_KEY_JWT"]);
			} else {
				$arrFile["DOC_URL"] = $config["URL_SERVICE"].$file_path;
			}
			$arrGroupHis[] = $arrFile;
		}
		
		//sort by date
		usort($arrGroupHis, function($a, $b) {
			return strtotime($b['file_created_date']) - strtotime($a['file_created_date']);
		});
		
		//$lib->sendLineNotify(json_encode($file_doc));
		
		/*
		$getHistory = $conmysql->prepare("SELECT doc_no, doc_filename,create_date,doc_address  FROM gcdocuploadfile WHERE doc_status = '1'");
		$getHistory->execute([':member_no' => $member_no]);
		while($rowHistory = $getHistory->fetch(PDO::FETCH_ASSOC)){
			$arrHistory = array();
			$arrHistory["DOC_NAME"] = $rowHistory["doc_filename"];
			$arrHistory["DOC_DATE"] = $lib->convertdate($rowHistory["create_date"],"D M Y");
			$arrHistory["DOC_URL"] = $rowHistory["doc_address"];
			$arrGroupHis[] = $arrHistory;
		}
		*/
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
