<?php
require_once('../autoload.php');


if($lib->checkCompleteArgument(['menu_component','upload_doc','doc_name'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SendReceiveDocuments')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$req_doc = time().$member_no;
		if(isset($req_doc) && $req_doc != ""){
			$destination = __DIR__.'/../../resource/document/'.$member_no;
			$data_Img = explode(',',$dataComing["upload_doc"]);
			$info_img = explode('/',$data_Img[0]);
			$ext_img = str_replace('base64','',$info_img[1]);
			if(!file_exists($destination)){
				mkdir($destination, 0777, true);
			}
			
			if($ext_img == 'pdf'){
				$createFile = $lib->base64_to_pdf($dataComing["upload_doc"],$req_doc,$destination);
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS4005";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
			if($createFile == 'oversize'){
				$arrayResult['RESPONSE_CODE'] = "WS0008";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}else{
				if($createFile){
					$directory = __DIR__.'/../../resource/document/'.$member_no;
					$fullPathSalary = __DIR__.'/../../resource/document/'.$member_no.'/'.$createFile["normal_path"];
					$doc_address = $config["URL_SERVICE"]."resource/document/".$member_no."/".$createFile["normal_path"];
					$insertDocMaster = $conmysql->prepare("INSERT INTO doclistmaster(doc_no,docgrp_no,doc_filename,doc_type,doc_address,member_no)
															VALUES(:doc_no,:docgrp_no,:doc_filename,:doc_type,:doc_address,:member_no)");
					$insertDocMaster->execute([
						':doc_no' => $req_doc,
						':docgrp_no' => $req_doc,
						':doc_filename' => $dataComing["doc_name"],
						':doc_type' => $ext_img,
						':doc_address' => $doc_address,
						':member_no' => $member_no
					]);					
				}
			}
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
			
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
			require_once('../../include/exit_footer.php');
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