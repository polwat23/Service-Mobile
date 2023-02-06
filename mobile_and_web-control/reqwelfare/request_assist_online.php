<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','id_const_welfare','assisttype_code'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'AssistRequest')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$insertBulkColumn = array();
		$insertBulkData = array();
		$getDocSystemPrefix = $conmysql->prepare("SELECT prefix_docno FROM docsystemprefix WHERE menu_component = :menu_component and is_use = '1'");
		$getDocSystemPrefix->execute([':menu_component' => $dataComing["menu_component"]]);
		if($getDocSystemPrefix->rowCount() > 0){
			$rowDocPrefix = $getDocSystemPrefix->fetch(PDO::FETCH_ASSOC);
			$reqloan_doc = null;
			$arrPrefixRaw = $func->PrefixGenerate($rowDocPrefix["prefix_docno"]);
			$arrPrefixSort = explode(',',$rowDocPrefix["prefix_docno"]);
			foreach($arrPrefixSort as $prefix){
				$reqloan_doc .= $arrPrefixRaw[$prefix];
			}
			
			$getColumnFormat = $conmysql->prepare("SELECT INPUT_NAME , INPUT_TYPE
												FROM gcformatreqwelfare
												WHERE id_const_welfare = :id_const_welfare and is_use = '1'");
			$getColumnFormat->execute([':id_const_welfare' => $dataComing["id_const_welfare"]]);
			while($rowColumn = $getColumnFormat->fetch(PDO::FETCH_ASSOC)){
				$insertBulkColumn[] = $rowColumn["INPUT_NAME"];
				if($rowColumn["INPUT_TYPE"] == 'date'){	
					$insertBulkData[] = "DATE_FORMAT(".$rowColumn["INPUT_NAME"].",'%Y%d%M')";
				}else {
					$insertBulkData[] = ':'.$rowColumn["INPUT_NAME"];
				}
			}
			
			
			$assist_docno = $reqloan_doc;
			$pathFile = $config["URL_SERVICE"].'/resource/pdf/request_assist/'.$assist_docno.'.pdf?v='.time();
			$insertBulkColumn[] = "member_no";
			$insertBulkColumn[] = "assisttype_code";
			$insertBulkColumn[] = "req_status";
			$insertBulkColumn[] = "assist_docno";
			$insertBulkColumn[] = "assist_year";
			$insertBulkColumn[] = "contractdoc_url";
			$insertBulkData[] = ":member_no";
			$insertBulkData[] = ":assisttype_code";
			$insertBulkData[] = ":req_status";
			$insertBulkData[] = ":assist_docno";
			$insertBulkData[] = ":assist_year";
			$insertBulkData[] = ":contractdoc_url";
			$textColumnInsert = "(".implode(",",$insertBulkColumn).")";
			$textDataInsert = "(".implode(",",$insertBulkData).")";
			$arrayExecute = array();
			foreach($insertBulkColumn as $keyExe){
				if($keyExe == 'member_no'){
					$arrayExecute[':'.$keyExe] = $member_no;
				}else if($keyExe == 'req_status'){
					$arrayExecute[':'.$keyExe] = "8";
				}else if($keyExe == 'assist_docno'){
					$arrayExecute[':'.$keyExe] = $assist_docno;
				}else if($keyExe == 'assist_year'){
					$arrayExecute[':'.$keyExe] = date('Y');
				}else if($keyExe == 'req_date'){
					$arrayExecute[':'.$keyExe] = date('Y-m-d');
				}else if($keyExe == 'contractdoc_url'){
					$arrayExecute[':'.$keyExe] = $pathFile;
				}else{
					$arrayExecute[':'.$keyExe] = $dataComing[$keyExe];
				}
			}
			$insertToAssistMast = $conmysql->prepare("INSERT INTO ASSREQMASTERONLINE".$textColumnInsert." VALUES".$textDataInsert);
			if($insertToAssistMast->execute($arrayExecute)){	
				
				$getRequestDocno = $conmysql->prepare("SELECT  assist_docno, assisttype_code, member_no, assist_name, assist_lastname, age, father_name, mother_name, academy_name, education_level, assist_amt, assist_year, req_date, req_status
														FROM assreqmasteronline WHERE   assist_docno = :assist_docno ");
				$getRequestDocno->execute([':assist_docno' => $assist_docno]);
				$rowRequestDocno = $getRequestDocno->fetch(PDO::FETCH_ASSOC);
				$arrData = array();
				$getFullName  = $conmssql->prepare("SELECT MP.PRENAME_DESC , MB.MEMB_NAME ,MB.MEMB_SURNAME  , MP.PRENAME_DESC , MG.MEMBGROUP_DESC , mb.MEM_TEL
													FROM MBMEMBMASTER MB LEFT JOIN MBUCFPRENAME MP ON MB.PRENAME_CODE = MP.PRENAME_CODE
													LEFT JOIN MBUCFMEMBGROUP MG ON MB.MEMBGROUP_CODE = MG.MEMBGROUP_CODE
													WHERE MB.MEMBER_NO = :member_no ");
				$getFullName->execute([':member_no' => $member_no]);
				$rowFullName = $getFullName->fetch(PDO::FETCH_ASSOC);
				$arrData["assist_docno"] = $assist_docno;
				$arrData["member_no"] = $rowRequestDocno["member_no"];
				$arrData["memb_name"] = $rowFullName["MEMB_NAME"];
				$arrData["memb_surname"] = $rowFullName["MEMB_SURNAME"];
				$arrData["prename_desc"] = $rowFullName["PRENAME_DESC"];
				$arrData["fulname"] = $rowFullName["PRENAME_DESC"] + $rowFullName["MEMB_NAME"]+' '+$rowFullName["MEMB_SURNAME"];
				$arrData["membgroup_desc"] = $rowFullName["MEMBGROUP_DESC"];
				$arrData["memb_tel"] = $rowFullName["MEMB_TEL"];
				$arrData["assist_name"] = $rowRequestDocno["assist_name"];
				$arrData["assist_lastname"] = $rowRequestDocno["assist_lastname"];
				$arrData["age"] = $rowRequestDocno["age"];
				$arrData["mother_name"] = $rowRequestDocno["mother_name"];
				$arrData["academy_name"] = $rowRequestDocno["academy_name"];
				$arrData["father_name"] = $rowRequestDocno["father_name"];
				$arrData["education_level"] = $rowRequestDocno["education_level"];
				
				if(file_exists('from_request_welfare.php')){
					include('from_request_welfare.php');
					$arrayPDF = GeneratePDFWelfare($arrData,$lib);
				}else{
					$arrayPDF["RESULT"] = FALSE;
				}
				if($arrayPDF["RESULT"]){
					$arrayResult['RESULT'] = TRUE;
					require_once('../../include/exit_footer.php');
				}	
			}else{
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS0055",
					":error_desc" => "ไม่สามารถขอทุนสวัสดิการได้"."\n".json_encode($dataComing),
					":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				/*$lib->sendLineNotify(json_encode($textColumnInsert));
				$lib->sendLineNotify(json_encode($textDataInsert));
				$lib->sendLineNotify(json_encode($arrayExecute));*/
				
				$log->writeLog('errorusage',$logStruc);
				$message_error = "ไฟล์ ".$filename." ไม่สามารถขอทุนสวัสดิการได้"."\n"."Query => ".$insertToAssistMast->queryString."\n"."DATA => ".json_encode($arrayExecute);
				$arrayResult['RESPONSE']  = json_encode($insertToAssistMast);
				$arrayResult['arrayExecute']  = json_encode($arrayExecute);
				$arrayResult['RESPONSE_CODE'] = "WS0055";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
		}else{
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS0063",
				":error_desc" => "ไม่พบเลขเอกสารของระบบขอกู้ออนไลน์ กรุณาสร้างชุด Format เลขเอกสาร",
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
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
	
}
?>