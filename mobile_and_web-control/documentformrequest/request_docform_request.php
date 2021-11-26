<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','upload_document','reqdocformtype_id'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DocumentFormRequest')){
			$reqdoc_no = null;
			$conmysql->beginTransaction();
			$getPrefix = $conmysql->prepare("SELECT COUNT(*) as c_reqloan FROM gcreqdocformonline WHERE SUBSTR(reqdoc_no,1,10) = :cur_date");
			$getPrefix->execute([':cur_date' => 'RD'.date("Ymd")]);
			$rowPrefix = $getPrefix->fetch(PDO::FETCH_ASSOC);
			$reqdoc_no = 'RD'.date("Ymd").str_pad($rowPrefix["c_reqloan"]+1,8,"0",STR_PAD_LEFT);
			if(isset($reqdoc_no) && $reqdoc_no != ""){
				$subpath =  $reqdoc_no;
				$folder_name = $payload["member_no"];
				$destination = __DIR__.'/../../resource/upload_document/'.$folder_name;
				$data_Img = explode(',',$dataComing["upload_document"]);
				$info_img = explode('/',$data_Img[0]);
				$ext_img = str_replace('base64','',$info_img[1]);
				if(!file_exists($destination)){
					mkdir($destination, 0777, true);
				}
				if($ext_img == 'png' || $ext_img == 'jpg' || $ext_img == 'jpeg'){
					$createImage = $lib->base64_to_img($dataComing["upload_document"],$subpath,$destination,null);
				}else if($ext_img == 'pdf'){
					$createImage = $lib->base64_to_pdf($dataComing["upload_document"],$subpath,$destination);
				}
				if($createImage == 'oversize'){
					$conmysql->rollback();
					$arrayResult['RESPONSE_CODE'] = "WS0008";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
				}else{
					if($createImage){
						$directory = __DIR__.'/../../resource/upload_document/'.$folder_name;
						$fullPathSalary = __DIR__.'/../../resource/upload_document/'.$folder_name.'/'.$createImage["normal_path"];
						$slipSalary = $config["URL_SERVICE"]."resource/upload_document/".$folder_name."/".$createImage["normal_path"];
						$getControlFolderSalary = $conmysql->prepare("SELECT docgrp_no FROM docgroupcontrol WHERE is_use = '1' and menu_component = :menu_component");
						$getControlFolderSalary->execute([':menu_component' => $dataComing["menu_component"]]);
						$rowControlSalary = $getControlFolderSalary->fetch(PDO::FETCH_ASSOC);
						$insertDocMaster = $conmysql->prepare("INSERT INTO doclistmaster(doc_no,docgrp_no,doc_filename,doc_type,doc_address,member_no)
																VALUES(:doc_no,:docgrp_no,:doc_filename,:doc_type,:doc_address,:member_no)");
						$insertDocMaster->execute([
							':doc_no' => $reqdoc_no,
							':docgrp_no' => $rowControlSalary["docgrp_no"],
							':doc_filename' => $subpath,
							':doc_type' => $ext_img,
							':doc_address' => $slipSalary,
							':member_no' => $payload["member_no"]
						]);
						$insertDocList = $conmysql->prepare("INSERT INTO doclistdetail(doc_no,member_no,new_filename,id_userlogin)
																VALUES(:doc_no,:member_no,:file_name,:id_userlogin)");
						$insertDocList->execute([
							':doc_no' => $reqdoc_no,
							':member_no' => $payload["member_no"],
							':file_name' => $createImage["normal_path"],
							':id_userlogin' => $payload["id_userlogin"]
						]);
					}
				}
				
				$InsertFormOnline = $conmysql->prepare("INSERT INTO gcreqdocformonline(reqdoc_no, member_no, reqdocformtype_id, document_url) 
																	VALUES (:reqdoc_no, :member_no, :reqdocformtype_id, :document_url)");
				if($InsertFormOnline->execute([
					':reqdoc_no' => $reqdoc_no,
					':member_no' => $payload["member_no"],
					':reqdocformtype_id' => $dataComing["reqdocformtype_id"],
					':document_url' => $slipSalary,
				])){
						$conmysql->commit();
						$arrayResult['REPORT_URL'] = $slipSalary;
						$arrayResult['REQ_DATE'] = $lib->convertdate(date("Y-m-d H:i:s"),'D m Y',true);
						$arrayResult['APV_DOCNO'] = $reqdoc_no;
						$arrayResult['RESULT'] = TRUE;
						require_once('../../include/exit_footer.php');
				}else{
					$conmysql->rollback();
					unlink($fullPathSalary);
					rmdir($directory);
					$filename = basename(__FILE__, '.php');
					$logStruc = [
						":error_menu" => $filename,
						":error_code" => "WS1036",
						":error_desc" => "ทำรายการไม่ได้เพราะ Insert ลงตาราง gcreqdocformonline ไม่ได้"."\n"."Query => ".$InsertFormOnline->queryString."\n"."Param => ". json_encode([
							':reqdoc_no' => $reqdoc_no,
							':member_no' => $payload["member_no"],
							':reqdocformtype_id' => $dataComing["reqdocformtype_id"],
							':document_url' => $slipSalary,
						]),
						":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
					];
					$log->writeLog('errorusage',$logStruc);
					$message_error = "ทำรายการไม่ได้เพราะ Insert ลง gcreqdocformonline ไม่ได้"."\n"."Query => ".$InsertFormOnline->queryString."\n"."Param => ". json_encode([
							':reqdoc_no' => $reqdoc_no,
							':member_no' => $payload["member_no"],
							':reqdocformtype_id' => $dataComing["reqdocformtype_id"],
							':document_url' => $slipSalary,
						]);
					$lib->sendLineNotify($message_error);
					$arrayResult['RESPONSE_CODE'] = "WS1036";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
				}
			}else{
				$conmysql->rollback();
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