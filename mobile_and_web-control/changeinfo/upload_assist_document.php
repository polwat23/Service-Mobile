<?php
require_once('../autoload.php');


if($lib->checkCompleteArgument(['menu_component','docgrp_no','document_data','file_title'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'CoopInfo')){
		$member_no = $payload["ref_memno"];
		$getDocSystemPrefix = $conmysql->prepare("SELECT prefix_docno FROM docsystemprefix WHERE menu_component = :menu_component and is_use = '1'");
		$getDocSystemPrefix->execute([':menu_component' => $dataComing["menu_component"].$dataComing["docgrp_no"]]);
		if($getDocSystemPrefix->rowCount() > 0){
			$rowDocPrefix = $getDocSystemPrefix->fetch(PDO::FETCH_ASSOC);
			$reqloan_doc = null;
			$arrPrefixRaw = $func->PrefixGenerate($rowDocPrefix["prefix_docno"]);
			$arrPrefixSort = explode(',',$rowDocPrefix["prefix_docno"]);
			foreach($arrPrefixSort as $prefix){
				$reqloan_doc .= $arrPrefixRaw[$prefix];
			}
			
			if(isset($reqloan_doc) && $reqloan_doc != ""){
				$destination = __DIR__.'/../../resource/coopdocument/'.$member_no;
				$data_Img = explode(',',$dataComing["document_data"]);
				$info_img = explode('/',$data_Img[0]);
				$ext_img = str_replace('base64','',$info_img[1]);
				if(!file_exists($destination)){
					mkdir($destination, 0777, true);
				}
				if($ext_img == 'png' || $ext_img == 'jpg' || $ext_img == 'jpeg'){
					$createImage = $lib->base64_to_img($dataComing["document_data"],$reqloan_doc,$destination,null);
				}else if($ext_img == 'pdf'){
					$createImage = $lib->base64_to_pdf($dataComing["document_data"],$reqloan_doc,$destination);
				}
				if($createImage == 'oversize'){
					$arrayResult['RESPONSE_CODE'] = "WS0008";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}else{
					if($createImage){
						$directory = __DIR__.'/../../resource/coopdocument/'.$member_no;
						$fullPathSalary = __DIR__.'/../../resource/coopdocument/'.$member_no.'/'.$createImage["normal_path"];
						$slipSalary = $config["URL_SERVICE"]."resource/coopdocument/".$member_no."/".$createImage["normal_path"];
						$insertDocMaster = $conmysql->prepare("INSERT INTO doclistmaster(doc_no,docgrp_no,doc_filename,doc_aliasname,doc_type,doc_address,member_no,username)
																VALUES(:doc_no,:docgrp_no,:doc_filename,:doc_aliasname,:doc_type,:doc_address,:member_no,:username)");
						$insertDocMaster->execute([
							':doc_no' => $reqloan_doc,
							':docgrp_no' => $dataComing["docgrp_no"],
							':doc_filename' => $reqloan_doc,
							':doc_aliasname' => $dataComing["file_title"],
							':doc_type' => $ext_img,
							':doc_address' => $slipSalary,
							':member_no' => $member_no,
							':username' => $payload["member_no"]
						]);
						$insertDocList = $conmysql->prepare("INSERT INTO doclistdetail(doc_no,member_no,new_filename,id_userlogin,username)
																VALUES(:doc_no,:member_no,:file_name,:id_userlogin,:username)");
						$insertDocList->execute([
							':doc_no' => $reqloan_doc,
							':member_no' => $member_no,
							':file_name' => $createImage["normal_path"],
							':id_userlogin' => $payload["id_userlogin"],
							':username' => $payload["member_no"]
						]);
					}
				}
				
				$arrayResult['REPORT_URL'] = $slipSalary;
				$arrayResult['OPERATE_DATE'] = $lib->convertdate(date('Y-m-d H:i:s'),'D m Y',true);
				$arrayResult['DOC_NO'] = $reqloan_doc;
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
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS0063",
				":error_desc" => "ไม่พบเลขเอกสารของระบบ กรุณาสร้างชุด Format เลขเอกสาร",
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
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