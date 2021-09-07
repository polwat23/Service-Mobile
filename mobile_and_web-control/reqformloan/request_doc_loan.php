<?php
require_once('../autoload.php');


if($lib->checkCompleteArgument(['menu_component','loantype_code','request_amt','period_payment','period','loanpermit_amt'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanRequestForm')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$getDocSystemPrefix = $conmssql->prepare("SELECT prefix_docno FROM docsystemprefix WHERE menu_component = :menu_component and is_use = '1'");
		$getDocSystemPrefix->execute([':menu_component' => $dataComing["menu_component"]]);
		if($getDocSystemPrefix->rowCount() > 0){
			$rowDocPrefix = $getDocSystemPrefix->fetch(PDO::FETCH_ASSOC);
			$reqloan_doc = null;
			$arrPrefixRaw = $func->PrefixGenerate($rowDocPrefix["prefix_docno"]);
			$arrPrefixSort = explode(',',$rowDocPrefix["prefix_docno"]);
			foreach($arrPrefixSort as $prefix){
				$reqloan_doc .= $arrPrefixRaw[$prefix];
			}
			if(isset($reqloan_doc) && $reqloan_doc != ""){
				$getControlDoc = $conmssql->prepare("SELECT docgrp_no FROM docgroupcontrol WHERE is_use = '1' and menu_component = :menu_component");
				$getControlDoc->execute([':menu_component' => $dataComing["menu_component"]]);
				$rowConDoc = $getControlDoc->fetch(PDO::FETCH_ASSOC);
				$cal_start_pay_date = $func->getConstant('cal_start_pay_date');
				$slipSalary = null;
				$citizenCopy = null;
				$fullPathSalary = null;
				$fullPathCitizen = null;
				$directory = null;
				if(isset($dataComing["upload_slip_salary"]) && $dataComing["upload_slip_salary"] != ""){
					$subpath = 'salary';
					$destination = __DIR__.'/../../resource/reqloan_doc/'.$reqloan_doc;
					$data_Img = explode(',',$dataComing["upload_slip_salary"]);
					$info_img = explode('/',$data_Img[0]);
					$ext_img = str_replace('base64','',$info_img[1]);
					if(!file_exists($destination)){
						mkdir($destination, 0777, true);
					}
					if($ext_img == 'png' || $ext_img == 'jpg' || $ext_img == 'jpeg'){
						$createImage = $lib->base64_to_img($dataComing["upload_slip_salary"],$subpath,$destination,null);
					}else if($ext_img == 'pdf'){
						$createImage = $lib->base64_to_pdf($dataComing["upload_slip_salary"],$subpath,$destination);
					}
					if($createImage == 'oversize'){
						$arrayResult['RESPONSE_CODE'] = "WS0008";
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
						
					}else{
						if($createImage){
							$directory = __DIR__.'/../../resource/reqloan_doc/'.$reqloan_doc;
							$fullPathSalary = __DIR__.'/../../resource/reqloan_doc/'.$reqloan_doc.'/'.$createImage["normal_path"];
							$slipSalary = $config["URL_SERVICE"]."resource/reqloan_doc/".$reqloan_doc."/".$createImage["normal_path"];
							$getControlFolderSalary = $conmssql->prepare("SELECT docgrp_no FROM docgroupcontrol WHERE is_use = '1' and menu_component = :menu_component");
							$getControlFolderSalary->execute([':menu_component' => $subpath]);
							$rowControlSalary = $getControlFolderSalary->fetch(PDO::FETCH_ASSOC);
							$insertDocMaster = $conmssql->prepare("INSERT INTO doclistmaster(doc_no,docgrp_no,doc_filename,doc_type,doc_address,member_no)
																	VALUES(:doc_no,:docgrp_no,:doc_filename,:doc_type,:doc_address,:member_no)");
							$insertDocMaster->execute([
								':doc_no' => $reqloan_doc.$subpath,
								':docgrp_no' => $rowControlSalary["docgrp_no"],
								':doc_filename' => $reqloan_doc.$subpath,
								':doc_type' => $ext_img,
								':doc_address' => $slipSalary,
								':member_no' => $payload["member_no"]
							]);
							$insertDocList = $conmssql->prepare("INSERT INTO doclistdetail(doc_no,member_no,new_filename,id_userlogin)
																	VALUES(:doc_no,:member_no,:file_name,:id_userlogin)");
							$insertDocList->execute([
								':doc_no' => $reqloan_doc.$subpath,
								':member_no' => $payload["member_no"],
								':file_name' => $createImage["normal_path"],
								':id_userlogin' => $payload["id_userlogin"]
							]);
						}
					}
				}
				if(isset($dataComing["upload_citizen_copy"]) && $dataComing["upload_citizen_copy"] != ""){
					$subpath = 'citizen';
					$destination = __DIR__.'/../../resource/reqloan_doc/'.$reqloan_doc;
					$data_Img = explode(',',$dataComing["upload_citizen_copy"]);
					$info_img = explode('/',$data_Img[0]);
					$ext_img = str_replace('base64','',$info_img[1]);
					if(!file_exists($destination)){
						mkdir($destination, 0777, true);
					}
					if($ext_img == 'png' || $ext_img == 'jpg' || $ext_img == 'jpeg'){
						$createImage = $lib->base64_to_img($dataComing["upload_citizen_copy"],$subpath,$destination,null);
					}else if($ext_img == 'pdf'){
						$createImage = $lib->base64_to_pdf($dataComing["upload_citizen_copy"],$subpath,$destination);
					}
					if($createImage == 'oversize'){
						unlink($fullPathSalary);
						rmdir($directory);
						$arrayResult['RESPONSE_CODE'] = "WS0008";
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
						
					}else{
						if($createImage){
							$directory = __DIR__.'/../../resource/reqloan_doc/'.$reqloan_doc;
							$fullPathCitizen = __DIR__.'/../../resource/reqloan_doc/'.$reqloan_doc.'/'.$createImage["normal_path"];
							$citizenCopy = $config["URL_SERVICE"]."resource/reqloan_doc/".$reqloan_doc."/".$createImage["normal_path"];
							$getControlFolderCitizen = $conmssql->prepare("SELECT docgrp_no FROM docgroupcontrol WHERE is_use = '1' and menu_component = :menu_component");
							$getControlFolderCitizen->execute([':menu_component' => $subpath]);
							$rowControlCitizen = $getControlFolderCitizen->fetch(PDO::FETCH_ASSOC);
							$insertDocMaster = $conmssql->prepare("INSERT INTO doclistmaster(doc_no,docgrp_no,doc_filename,doc_type,doc_address,member_no)
																	VALUES(:doc_no,:docgrp_no,:doc_filename,:doc_type,:doc_address,:member_no)");
							$insertDocMaster->execute([
								':doc_no' => $reqloan_doc.$subpath,
								':docgrp_no' => $rowControlCitizen["docgrp_no"],
								':doc_filename' => $reqloan_doc.$subpath,
								':doc_type' => $ext_img,
								':doc_address' => $citizenCopy,
								':member_no' => $payload["member_no"]
							]);
							$insertDocList = $conmssql->prepare("INSERT INTO doclistdetail(doc_no,member_no,new_filename,id_userlogin)
																	VALUES(:doc_no,:member_no,:file_name,:id_userlogin)");
							$insertDocList->execute([
								':doc_no' => $reqloan_doc.$subpath,
								':member_no' => $payload["member_no"],
								':file_name' => $createImage["normal_path"],
								':id_userlogin' => $payload["id_userlogin"]
							]);
						}
					}
				}
				
				$fetchPrefix = $conmssql->prepare("SELECT prefix FROM lnloantype where loantype_code = :loantype_code");
				$fetchPrefix->execute([
					':loantype_code' => $dataComing["loantype_code"]
				]);
				$rowPrefix = $fetchPrefix->fetch(PDO::FETCH_ASSOC);
				
				$fetchData = $conmssql->prepare("SELECT MB.MEMB_NAME,MB.MEMB_SURNAME,MP.PRENAME_DESC,MB.POSITION_DESC,MG.MEMBGROUP_DESC,MB.SALARY_AMOUNT,
														MD.DISTRICT_DESC,(SH.SHAREBEGIN_AMT * 10) AS SHAREBEGIN_AMT,MB.MEMBGROUP_CODE,
														SH.SHARESTK_AMT as SHARE_AMT,(SH.SHARESTK_AMT * 10) as SHARESTK_AMT
														FROM mbmembmaster mb LEFT JOIN
														mbucfprename mp ON mb.prename_code = mp.prename_code
														LEFT JOIN mbucfmembgroup mg ON mb.membgroup_code = mg.membgroup_code
														LEFT JOIN mbucfdistrict md ON mg.ADDR_AMPHUR = md.DISTRICT_CODE
														LEFT JOIN shsharemaster sh ON mb.member_no = sh.member_no
														WHERE mb.member_no = :member_no");
				$fetchData->execute([
					':member_no' => $member_no
				]);
				$rowData = $fetchData->fetch(PDO::FETCH_ASSOC);
				$pathFile = $config["URL_SERVICE"].'/resource/pdf/request_loan/'.$reqloan_doc.'.pdf?v='.time();
				$arrOldGrp = array();
				$oldbal = 0;
				$oldContract = $conmssql->prepare("SELECT lt.loantype_desc,ln.loancontract_no,ln.principal_balance,ln.loanapprove_date,
													ISNULL(lo.loanobjective_desc,'อื่น ๆ ') as loanobjective_desc
													FROM lncontmaster ln LEFT JOIN lnloantype lt ON ln.loantype_code = lt.loantype_code
													LEFT JOIN lnucfloanobjective lo ON ln.loanobjective_code = lo.loanobjective_code and ln.loantype_code = lo.loantype_code
													WHERE ln.member_no = :member_no and ln.contract_status > 0 and 
													ln.contract_status <> 8 and ln.loantype_code = :loantype_code");
				$oldContract->execute([
					':member_no' => $member_no,
					':loantype_code' => $dataComing["loantype_code"]
				]);
				while($rowOldContract = $oldContract->fetch(PDO::FETCH_ASSOC)){
					$arrOld = array();
					$arrOld["loantype_desc"] = $rowOldContract["loantype_desc"];
					$arrOld["loancontract_no"] = $rowOldContract["loancontract_no"];
					$arrOld["principal_balance"] = number_format($rowOldContract["principal_balance"],2);
					$arrOld["loanapprove_date"] = $lib->convertdate($rowOldContract["loanapprove_date"],'d m Y');
					$arrOld["loanobjective_desc"] = $rowOldContract["loanobjective_desc"];
					$oldbal += $rowOldContract["principal_balance"] + $cal_loan->calculateInterest($rowOldContract["loancontract_no"]);
					$arrOldGrp[] = $arrOld;
				}
				$arrData["old_contract"] = $arrOldGrp;
				$conmssql->beginTransaction();
				$InsertFormOnline = $conmssql->prepare("INSERT INTO gcreqloan(reqloan_doc,member_no,loantype_code,request_amt,period_payment,period,loanpermit_amt,diff_old_contract,receive_net,
																		int_rate_at_req,salary_at_req,salary_img,citizen_img,id_userlogin,contractdoc_url,deptaccount_no_bank)
																		VALUES(:reqloan_doc,:member_no,:loantype_code,:request_amt,:period_payment,:period,:loanpermit_amt,:diff_old,:request_amt,:int_rate
																		,:salary,:salary_img,:citizen_img,:id_userlogin,:contractdoc_url,:deptaccount_no_bank)");
				if($InsertFormOnline->execute([
					':reqloan_doc' => $reqloan_doc,
					':member_no' => $payload["member_no"],
					':loantype_code' => $dataComing["loantype_code"],
					':request_amt' => $dataComing["request_amt"],
					':period_payment' => $dataComing["period_payment"],
					':period' => $dataComing["period"],
					':loanpermit_amt' => $dataComing["loanpermit_amt"],
					':diff_old' => $oldbal,
					':int_rate' => $dataComing["int_rate"] / 100,
					':salary' => $rowData["SALARY_AMOUNT"],
					':salary_img' => $slipSalary,
					':citizen_img' => $citizenCopy,
					':id_userlogin' => $payload["id_userlogin"],
					':contractdoc_url' => $pathFile,
					':deptaccount_no_bank' => $dataComing["deptaccount_no_bank"] ?? null,
				])){
					$arrData = array();
					$arrData["requestdoc_no"] = $reqloan_doc;
					$arrData["loan_prefix"] = $rowPrefix["PREFIX"];
					$arrData["full_name"] = $rowData["PRENAME_DESC"].$rowData["MEMB_NAME"].' '.$rowData["MEMB_SURNAME"];
					$arrData["name"] = $rowData["MEMB_NAME"].' '.$rowData["MEMB_SURNAME"];
					$arrData["member_no"] = $payload["member_no"];
					$arrData["position"] = $rowData["POSITION_DESC"];
					$arrData["pos_group"] = $rowData["MEMBGROUP_DESC"];
					$arrData["pos_group_code"] = $rowData["MEMBGROUP_CODE"];
					$arrData["district_desc"] = $rowData["DISTRICT_DESC"];
					$arrData["salary_amount"] = number_format($rowData["SALARY_AMOUNT"],2);
					$arrData["share_bf"] = number_format($rowData["SHAREBEGIN_AMT"],2);
					$arrData["share_amt"] = number_format($rowData["SHARE_AMT"],2);
					$arrData["sharestk_amt"] = number_format($rowData["SHARESTK_AMT"],2);
					$arrData["request_amt"] = $dataComing["request_amt"];
					$arrData["objective"] = $dataComing["objective"];
					$arrData["period_payment"] = $dataComing["period_payment"];
					$arrData["period"] = $dataComing["period"];
					$arrData["recv_account"] = $dataComing["deptaccount_no_bank"];
					if(file_exists('form_request_loan_'.$dataComing["loantype_code"].'.php')){
						include('form_request_loan_'.$dataComing["loantype_code"].'.php');
						$arrayPDF = GeneratePDFContract($arrData,$lib);
					}else{
						$arrayPDF["RESULT"] = FALSE;
					}
					if($arrayPDF["RESULT"]){
						$insertDocMaster = $conmssql->prepare("INSERT INTO doclistmaster(doc_no,docgrp_no,doc_filename,doc_type,doc_address,member_no)
																VALUES(:doc_no,:docgrp_no,:doc_filename,'pdf',:doc_address,:member_no)");
						$insertDocMaster->execute([
							':doc_no' => $reqloan_doc,
							':docgrp_no' => $rowConDoc["docgrp_no"],
							':doc_filename' => $reqloan_doc,
							':doc_address' => $pathFile,
							':member_no' => $payload["member_no"]
						]);
						$insertDocList = $conmssql->prepare("INSERT INTO doclistdetail(doc_no,member_no,new_filename,id_userlogin)
																VALUES(:doc_no,:member_no,:file_name,:id_userlogin)");
						$insertDocList->execute([
							':doc_no' => $reqloan_doc,
							':member_no' => $payload["member_no"],
							':file_name' => $reqloan_doc.'.pdf',
							':id_userlogin' => $payload["id_userlogin"]
						]);
						$conmssql->commit();
						$arrayResult['REPORT_URL'] = $pathFile;
						$arrayResult['APV_DOCNO'] = $reqloan_doc;
						$arrayResult['RESULT'] = TRUE;
						require_once('../../include/exit_footer.php');
						
					}else{
						$conmssql->rollback();
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
					$conmssql->rollback();
					unlink($fullPathSalary);
					unlink($fullPathCitizen);
					rmdir($directory);
					$filename = basename(__FILE__, '.php');
					$logStruc = [
						":error_menu" => $filename,
						":error_code" => "WS1036",
						":error_desc" => "ขอกู้ไม่ได้เพราะ Insert ลงตาราง gcreqloan ไม่ได้"."\n"."Query => ".$InsertFormOnline->queryString."\n"."Param => ". json_encode([
							':reqloan_doc' => $reqloan_doc,
							':member_no' => $payload["member_no"],
							':loantype_code' => $dataComing["loantype_code"],
							':request_amt' => $dataComing["request_amt"],
							':period_payment' => $dataComing["period_payment"],
							':period' => $dataComing["period"],
							':loanpermit_amt' => $dataComing["loanpermit_amt"],
							':diff_old' => $oldbal,
							':int_rate' => $dataComing["int_rate"] / 100,
							':salary' => $rowData["SALARY_AMOUNT"],
							':salary_img' => $slipSalary,
							':citizen_img' => $citizenCopy,
							':id_userlogin' => $payload["id_userlogin"],
							':contractdoc_url' => $pathFile,
							':deptaccount_no_bank' => $dataComing["deptaccount_no_bank"] ?? "-",
						]),
						":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
					];
					$log->writeLog('errorusage',$logStruc);
					$message_error = "ขอกู้ไม่ได้เพราะ Insert ลง gcreqloan ไม่ได้"."\n"."Query => ".$InsertFormOnline->queryString."\n"."Param => ". json_encode([
						':reqloan_doc' => $reqloan_doc,
						':member_no' => $payload["member_no"],
						':loantype_code' => $dataComing["loantype_code"],
						':request_amt' => $dataComing["request_amt"],
						':period_payment' => $dataComing["period_payment"],
						':period' => $dataComing["period"],
						':loanpermit_amt' => $dataComing["loanpermit_amt"],
						':diff_old' => $oldbal,
						':int_rate' => $dataComing["int_rate"] / 100,
						':salary' => $rowData["SALARY_AMOUNT"],
						':salary_img' => $slipSalary,
						':citizen_img' => $citizenCopy,
						':id_userlogin' => $payload["id_userlogin"],
						':contractdoc_url' => $pathFile,
						':deptaccount_no_bank' => $dataComing["deptaccount_no_bank"] ?? "-",
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