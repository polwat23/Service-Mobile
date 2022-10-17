<?php
require_once('../autoload.php');


if($lib->checkCompleteArgument(['menu_component','temp_img'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanRequestForm')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
	
		if(isset($dataComing["temp_img"]) && $dataComing["temp_img"] != ""){
			$subpath = $member_no.time().$lib->randomText("" ,8);
			$destination = __DIR__.'/../../resource/reqloan_doc_temp';
			$data_Img = explode(',',$dataComing["temp_img"]);
			$info_img = explode('/',$data_Img[0]);
			$ext_img = str_replace('base64','',$info_img[1]);
			if(!file_exists($destination)){
				mkdir($destination, 0777, true);
			}
			if($ext_img == 'png' || $ext_img == 'jpg' || $ext_img == 'jpeg'){
				$createImage = $lib->base64_to_img($dataComing["temp_img"],$subpath,$destination,null);
			}else if($ext_img == 'pdf'){
				$createImage = $lib->base64_to_pdf($dataComing["temp_img"],$subpath,$destination);
			}
			if($createImage == 'oversize'){
				$arrayResult['RESPONSE_CODE'] = "WS0008";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}else{
				if($createImage){
					$directory = __DIR__.'/../../resource/reqloan_doc_temp';
					$fullPathSalary = __DIR__.'/../../resource/reqloan_doc_temp/'.$createImage["normal_path"];
					$slipSalary = $config["URL_SERVICE"]."resource/reqloan_doc_temp/".$createImage["normal_path"];
					$arrayResult['PATH'] = $createImage["normal_path"];
					$arrayResult['RESULT'] = TRUE;
					require_once('../../include/exit_footer.php');
				}else{
					$arrayResult['RESPONSE_CODE'] = "WS0132";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
				}
			}
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