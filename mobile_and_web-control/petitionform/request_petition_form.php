<?php
require_once('../autoload.php');

use Dompdf\Dompdf;

$dompdf = new DOMPDF();

if($lib->checkCompleteArgument(['menu_component','petitionform_code','petitionform_desc','req_desc', 'effect_date'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'PetitionForm')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		
			$getDocSystemPrefix = $conmysql->prepare("SELECT prefix_docno FROM docsystemprefix WHERE menu_component = :menu_component and is_use = '1'");
			$getDocSystemPrefix->execute([':menu_component' => $dataComing["menu_component"]]);
			
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
				$getControlDoc->execute([':menu_component' => $dataComing["menu_component"]]);
				$rowConDoc = $getControlDoc->fetch(PDO::FETCH_ASSOC);
				
				$memberInfo = $conoracle->prepare("SELECT mp.prename_short,mb.memb_name,mb.memb_surname,mb.birth_date,mb.card_person,
										mb.member_date,mb.position_desc,mg.membgroup_desc,mt.membtype_desc,
										mb.MEMB_ADDR as ADDR_NO,
										mb.ADDR_GROUP as ADDR_MOO,
										mb.SOI as ADDR_SOI,
										mb.MOOBAN as ADDR_VILLAGE,
										mb.ROAD as ADDR_ROAD,
										mb.TAMBOL AS TAMBOL_DESC,
										MBD.DISTRICT_DESC AS DISTRICT_DESC,
										MB.PROVINCE_CODE AS PROVINCE_CODE,
										MBP.PROVINCE_DESC AS PROVINCE_DESC,
										MB.POSTCODE AS ADDR_POSTCODE
										FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
										LEFT JOIN MBUCFMEMBGROUP mg ON mb.MEMBGROUP_CODE = mg.MEMBGROUP_CODE
										LEFT JOIN MBUCFMEMBTYPE mt ON mb.MEMBTYPE_CODE = mt.MEMBTYPE_CODE
										LEFT JOIN MBUCFDISTRICT MBD ON mb.DISTRICT_CODE = MBD.DISTRICT_CODE
										and mb.PROVINCE_CODE = MBD.PROVINCE_CODE
										LEFT JOIN MBUCFPROVINCE MBP ON mb.PROVINCE_CODE = MBP.PROVINCE_CODE
										WHERE mb.member_no = :member_no and mb.member_status = '1' and branch_id = :branch_id");
				$memberInfo->execute([
					':member_no' => $member_no,
					':branch_id' => $payload["branch_id"]
				]);
				$rowData = $memberInfo->fetch(PDO::FETCH_ASSOC);
				
				$memberInfoMobile = $conmysql->prepare("SELECT phone_number,email,path_avatar,member_no FROM gcmemberaccount WHERE member_no = :member_no");
				$memberInfoMobile->execute([':member_no' => $member_no]);
				$rowInfoMobile = $memberInfoMobile->fetch(PDO::FETCH_ASSOC);
				
				$pathFile = $config["URL_SERVICE"].'/resource/pdf/req_document/'.$reqdoc_no.'.pdf?v='.time();
				$conmysql->beginTransaction();
				$InsertFormOnline = $conmysql->prepare("INSERT INTO gcpetitionformreq(reqdoc_no, member_no, petitionform_code, petitionform_desc, document_url, req_desc, effect_date) 
													VALUES (:reqdoc_no, :member_no, :petitionform_code, :petitionform_desc, :document_url, :req_desc, :effect_date)");
				
				if($InsertFormOnline->execute([
					':reqdoc_no' => $reqdoc_no,
					':member_no' => $payload["member_no"],
					':petitionform_code' => $dataComing["petitionform_code"],
					':petitionform_desc' => $dataComing["petitionform_desc"],
					':document_url' => $pathFile,
					':req_desc' => $dataComing["req_desc"],
					':effect_date' => $dataComing["effect_date"]
				])){
					$arrGroupDetail = array();
					$arrGroupDetail["MEMBER_NO"] =  $member_no;
					$arrGroupDetail["FULLNAME"] =  $rowData["PRENAME_SHORT"].$rowData["MEMB_NAME"].'  '.$rowData["MEMB_SURNAME"];
					$arrGroupDetail["TEL"] =  $rowInfoMobile["phone_number"];
					$arrGroupDetail["MEMBGROUP_DESC"] =  $rowData["MEMBGROUP_DESC"];
					$arrGroupDetail["PETITIONFORM_CODE"] =  $dataComing["petitionform_code"];
					$arrGroupDetail["PETITIONFORM_DESC"] =  $dataComing["petitionform_desc"];
					$arrGroupDetail["REQ_DESC"] =  $dataComing["req_desc"];
					
					$effect_date = $lib->convertdate($dataComing["effect_date"],"D m Y");
					$arr_effect_date = explode(" ",$effect_date);
					
					$arrGroupDetail["EFFECT_DATE_D"] =  $arr_effect_date[0];
					$arrGroupDetail["EFFECT_DATE_M"] =  $arr_effect_date[1];
					$arrGroupDetail["EFFECT_DATE_Y"] =  $arr_effect_date[2];
					
					$arrGroupDetail["REQDOC_NO"] = $reqdoc_no;
					include('form_request_document.php');
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
						":error_code" => "WS1042",
						":error_desc" => "เพิ่มคำร้องไม่ได้เพราะ Insert ลงตาราง gcpetitionformreq ไม่ได้"."\n"."Query => ".$InsertFormOnline->queryString."\n"."Param => ". json_encode([
							':reqdoc_no' => $reqdoc_no,
							':member_no' => $payload["member_no"],
							':petitionform_code' => $dataComing["petitionform_code"],
							':petitionform_desc' => $dataComing["petitionform_desc"],
							':document_url' => $pathFile,
							':req_desc' => $dataComing["req_desc"],
							':effect_date' => $dataComing["effect_date"]
						]),
						":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
					];
					$log->writeLog('errorusage',$logStruc);
					$message_error = "เพิ่มคำร้องไม่ได้เพราะ Insert ลง gcpetitionformreq ไม่ได้"."\n"."Query => ".$InsertFormOnline->queryString."\n"."Param => ". json_encode([
						':reqdoc_no' => $reqdoc_no,
						':member_no' => $payload["member_no"],
						':petitionform_code' => $dataComing["petitionform_code"],
						':petitionform_desc' => $dataComing["petitionform_desc"],
						':document_url' => $pathFile,
						':req_desc' => $dataComing["req_desc"],
						':effect_date' => $dataComing["effect_date"]
					]);
					$lib->sendLineNotify($message_error);
					$arrayResult['RESPONSE_CODE'] = "WS1042";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
				}
			}else{
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS1042",
					":error_desc" => "เลขเอกสารเป็นค่าว่าง ไม่สามารถสร้างเลขเอกสารเป็นค่าว่างได้",
					":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$message_error = "เลขเอกสารเป็นค่าว่าง ไม่สามารถสร้างเลขเอกสารเป็นค่าว่างได้";
				$lib->sendLineNotify($message_error);
				$arrayResult['RESPONSE_CODE'] = "WS1042";
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
