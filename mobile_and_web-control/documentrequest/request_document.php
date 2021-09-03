<?php
require_once('../autoload.php');

use Dompdf\Dompdf;

$dompdf = new DOMPDF();

if($lib->checkCompleteArgument(['menu_component','form_value_root_','documenttype_code'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DocumentRequest')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		if($dataComing["documenttype_code"] == "CBNF"){
		
			$getDocSystemPrefix = $conmysql->prepare("SELECT prefix_docno FROM docsystemprefix WHERE menu_component = :menu_component and is_use = '1'");
			$getDocSystemPrefix->execute([':menu_component' => $dataComing["documenttype_code"]]);
			
			$reqdoc_no = null;
			if($getDocSystemPrefix->rowCount() > 0){
				$rowDocPrefix = $getDocSystemPrefix->fetch(PDO::FETCH_ASSOC);
				$arrPrefixRaw = $func->PrefixGenerate($rowDocPrefix["prefix_docno"]);
				$arrPrefixSort = explode(',',$rowDocPrefix["prefix_docno"]);
				foreach($arrPrefixSort as $prefix){
					$reqdoc_no .= $arrPrefixRaw[$prefix];
				}
			}
			
			if(isset($reqdoc_no) && $reqdoc_no != ""){
				$getControlDoc = $conmysql->prepare("SELECT docgrp_no FROM docgroupcontrol WHERE is_use = '1' and menu_component = :menu_component");
				$getControlDoc->execute([':menu_component' => $dataComing["documenttype_code"]]);
				$rowConDoc = $getControlDoc->fetch(PDO::FETCH_ASSOC);
				
				$fetchData = $conmssql->prepare("SELECT MB.MEMB_NAME,MB.MEMB_SURNAME,MP.PRENAME_DESC,MB.SALARY_ID
														FROM MBMEMBMASTER MB LEFT JOIN 
														MBUCFPRENAME MP ON MB.PRENAME_CODE = MP.PRENAME_CODE
														WHERE MB.MEMBER_NO = :member_no");
				$fetchData->execute([
					':member_no' => $member_no
				]);
				$rowData = $fetchData->fetch(PDO::FETCH_ASSOC);
				
				$pathFile = $config["URL_SERVICE"].'/resource/pdf/req_document/req_cbnf/'.$reqdoc_no.'.pdf?v='.time();
				$conmysql->beginTransaction();
				$InsertFormOnline = $conmysql->prepare("INSERT INTO gcreqdoconline(reqdoc_no, member_no, documenttype_code, form_value, document_url) 
													VALUES (:reqdoc_no, :member_no, :documenttype_code, :form_value,:document_url)");
				if($InsertFormOnline->execute([
					':reqdoc_no' => $reqdoc_no,
					':member_no' => $payload["member_no"],
					':documenttype_code' => $dataComing["documenttype_code"],
					':form_value' => json_encode($dataComing["form_value_root_"]),
					':document_url' => $pathFile,
				])){
					$arrGroupDetail = array();
					$arrGroupDetail["MEMBER_NO"] =  $member_no;
					$arrGroupDetail["EMP_NO"] = $rowData["SALARY_ID"];
					$arrGroupDetail["REQDOC_NO"] = $reqdoc_no;
					$arrGroupDetail["MEMBER_FULLNAME"] =  $rowData["PRENAME_DESC"].$rowData["MEMB_NAME"]." ".$rowData["MEMB_SURNAME"];
					$arrGroupDetail["EFFECT_DATE"] =  $dataComing["form_value_root_"]["EFFECT_DATE"]["VALUE"] ? $lib->convertdate($dataComing["form_value_root_"]["EFFECT_DATE"]["VALUE"],"D m Y") : "";
					$arrGroupDetail["BENEF_NAME_1"] =  $dataComing["form_value_root_"]["BENEF_NAME_1"]["VALUE"] ?? "";
					$arrGroupDetail["BENEF_NAME_2"] =  $dataComing["form_value_root_"]["BENEF_NAME_2"]["VALUE"] ?? "";
					$arrGroupDetail["BENEF_NAME_3"] =  $dataComing["form_value_root_"]["BENEF_NAME_3"]["VALUE"] ?? "";
					$arrGroupDetail["BENEF_NAME_4"] =  $dataComing["form_value_root_"]["BENEF_NAME_4"]["VALUE"] ?? "";
					$arrGroupDetail["BENEF_OPTION"] =  $dataComing["form_value_root_"]["BENEF_OPTION"]["VALUE"] ?? "";
					$arrGroupDetail["OPTION_VALUE"] =  $dataComing["form_value_root_"]["BENEF_OPTION"]["OPTION_VALUE"][$dataComing["form_value_root_"]["BENEF_OPTION"]["VALUE"]];
					include('form_request_document_'.$dataComing["documenttype_code"].'.php');
					$arrayPDF = GenerateReport($arrGroupDetail,$lib);
					if($arrayPDF["RESULT"]){
						$arrayResult['REPORT_URL'] = $config["URL_SERVICE"].$arrayPDF["PATH"];
						
						$insertDocMaster = $conmysql->prepare("INSERT INTO doclistmaster(doc_no,docgrp_no,doc_filename,doc_type,doc_address,member_no)
																VALUES(:doc_no,:docgrp_no,:doc_filename,'pdf',:doc_address,:member_no)");
						$insertDocMaster->execute([
							':doc_no' => $reqdoc_no,
							':docgrp_no' => $rowConDoc["docgrp_no"],
							':doc_filename' => $reqdoc_no,
							':doc_address' => $pathFile,
							':member_no' => $payload["member_no"]
						]);
						$insertDocList = $conmysql->prepare("INSERT INTO doclistdetail(doc_no,member_no,new_filename,id_userlogin)
																VALUES(:doc_no,:member_no,:file_name,:id_userlogin)");
						$insertDocList->execute([
							':doc_no' => $reqdoc_no,
							':member_no' => $payload["member_no"],
							':file_name' => $reqdoc_no.'.pdf',
							':id_userlogin' => $payload["id_userlogin"]
						]);
						$conmysql->commit();
						$arrayResult['RESULT'] = TRUE;
						require_once('../../include/exit_footer.php');
					}else{
						$conmysql->rollback();
						$filename = basename(__FILE__, '.php');
						$logStruc = [
							":error_menu" => $filename,
							":error_code" => "WS0044",
							":error_desc" => "สร้าง PDF ไม่ได้ "."\n".json_encode($dataComing),
							":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
						];
						$log->writeLog('errorusage',$logStruc);
						$message_error = "สร้างไฟล์ PDF ไม่ได้ ".$filename."\n"."DATA => ".json_encode($dataComing);
						$lib->sendLineNotify($message_error);
						$arrayResult['RESPONSE_CODE'] = "WS0044";
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
						
					}
				}else{
					$conmysql->rollback();
					$filename = basename(__FILE__, '.php');
					$logStruc = [
						":error_menu" => $filename,
						":error_code" => "WS1036",
						":error_desc" => "ขอกู้ไม่ได้เพราะ Insert ลงตาราง gcreqdoconline ไม่ได้"."\n"."Query => ".$InsertFormOnline->queryString."\n"."Param => ". json_encode([
							':reqdoc_no' => $reqdoc_no,
							':member_no' => $payload["member_no"],
							':document_url' => $pathFile,
						]),
						":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
					];
					$log->writeLog('errorusage',$logStruc);
					$message_error = "ขอกู้ไม่ได้เพราะ Insert ลง gcreqdoconline ไม่ได้"."\n"."Query => ".$InsertFormOnline->queryString."\n"."Param => ". json_encode([
						':reqdoc_no' => $reqdoc_no,
						':member_no' => $payload["member_no"],
						':document_url' => $pathFile,
					]);
					$lib->sendLineNotify($message_error);
					$arrayResult['RESPONSE_CODE'] = "WS1036";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
				}
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
			$arrayResult['RESPONSE_CODE'] = "WS0124";
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