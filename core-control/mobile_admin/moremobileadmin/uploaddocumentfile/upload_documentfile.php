<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','file_name','file_doc'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','uploaddocuments')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$file_name = "ADMIN".$lib->randomText('number',8);
		if(isset($file_name) && $file_name != ""){
			$destination = __DIR__.'/../../../resource/document/';
			$data_Img = explode(',',$dataComing["file_doc"]);
			$info_img = explode('/',$data_Img[0]);
			$ext_img = str_replace('base64','',$info_img[1]);
			if(!file_exists($destination)){
				mkdir($destination, 0777, true);
			}
			
			if($ext_img == 'pdf'){
				$createFile = $lib->base64_to_pdf($dataComing["file_doc"],$file_name,$destination);
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS4005";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
			if($createFile == 'oversize'){
				$arrayResult['RESPONSE_CODE'] = "WS0008";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
				
			}else{
				if($createFile){
					$doc_address = $config["URL_SERVICE"]."core-control/resource/document/".$createFile["normal_path"];
					$insertDocMaster = $conmysql->prepare("INSERT INTO gcdocuploadfile(doc_no, doc_filename, doc_address)
															VALUES(:doc_no,:doc_filename,:doc_address)");
					$insertDocMaster->execute([
						':doc_no' => $file_name,
						':doc_filename' => $dataComing["file_name"],
						':doc_address' => $doc_address,
					]);					
				}
			}
			$arrayResult['RESULT'] = TRUE;
			require_once('../../../../include/exit_footer.php');
			
		}else{
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS0063",
				":error_desc" => "เลขเอกสารเป็นค่าว่าง ไม่สามารถสร้างเลขเอกสารเป็นค่าว่างได้",
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "เลขเอกสารเป็นค่าว่าง ไม่สามารถสร้างเลขเอกสารเป็นค่าว่างได้";
			$lib->sendLineNotify($message_error);
			$arrayResult['RESPONSE_CODE'] = "WS0063";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../../../include/exit_footer.php');
		}
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../../include/exit_footer.php');
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../../include/exit_footer.php');
}
?>