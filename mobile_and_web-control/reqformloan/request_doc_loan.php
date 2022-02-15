<?php
require_once('../autoload.php');


if($lib->checkCompleteArgument(['menu_component','loantype_code','request_amt','period_payment','period','loanpermit_amt'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanRequestForm')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
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
			if(isset($reqloan_doc) && $reqloan_doc != ""){
				$getControlDoc = $conmysql->prepare("SELECT docgrp_no FROM docgroupcontrol WHERE is_use = '1' and menu_component = :menu_component");
				$getControlDoc->execute([':menu_component' => $dataComing["menu_component"]]);
				$rowConDoc = $getControlDoc->fetch(PDO::FETCH_ASSOC);
				$checkGroupControl = $conmysql->prepare("SELECT docgrp_no FROM docgroupcontrol WHERE docgrp_ref = :docgrp_ref 
														and is_use = '1'");
				$checkGroupControl->execute([':docgrp_ref' => $rowConDoc["docgrp_no"]]);
				$rowGrpControl = $checkGroupControl->fetch(PDO::FETCH_ASSOC);
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
							$getControlFolderSalary = $conmysql->prepare("SELECT docgrp_no FROM docgroupcontrol WHERE is_use = '1' and menu_component = :menu_component");
							$getControlFolderSalary->execute([':menu_component' => $subpath]);
							$rowControlSalary = $getControlFolderSalary->fetch(PDO::FETCH_ASSOC);
							$insertDocMaster = $conmysql->prepare("INSERT INTO doclistmaster(doc_no,docgrp_no,doc_filename,doc_type,doc_address,member_no)
																	VALUES(:doc_no,:docgrp_no,:doc_filename,:doc_type,:doc_address,:member_no)");
							$insertDocMaster->execute([
								':doc_no' => $reqloan_doc.$subpath,
								':docgrp_no' => $rowControlSalary["docgrp_no"],
								':doc_filename' => $reqloan_doc.$subpath,
								':doc_type' => $ext_img,
								':doc_address' => $slipSalary,
								':member_no' => $payload["member_no"]
							]);
							$insertDocList = $conmysql->prepare("INSERT INTO doclistdetail(doc_no,member_no,new_filename,id_userlogin)
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
							$getControlFolderCitizen = $conmysql->prepare("SELECT docgrp_no FROM docgroupcontrol WHERE is_use = '1' and menu_component = :menu_component");
							$getControlFolderCitizen->execute([':menu_component' => $subpath]);
							$rowControlCitizen = $getControlFolderCitizen->fetch(PDO::FETCH_ASSOC);
							$insertDocMaster = $conmysql->prepare("INSERT INTO doclistmaster(doc_no,docgrp_no,doc_filename,doc_type,doc_address,member_no)
																	VALUES(:doc_no,:docgrp_no,:doc_filename,:doc_type,:doc_address,:member_no)");
							$insertDocMaster->execute([
								':doc_no' => $reqloan_doc.$subpath,
								':docgrp_no' => $rowControlCitizen["docgrp_no"],
								':doc_filename' => $reqloan_doc.$subpath,
								':doc_type' => $ext_img,
								':doc_address' => $citizenCopy,
								':member_no' => $payload["member_no"]
							]);
							$insertDocList = $conmysql->prepare("INSERT INTO doclistdetail(doc_no,member_no,new_filename,id_userlogin)
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
				
				$fetchPrefix = $conoracle->prepare("SELECT prefix, loantype_desc FROM lnloantype where loantype_code = :loantype_code");
				$fetchPrefix->execute([
					':loantype_code' => $dataComing["loantype_code"]
				]);
				$rowPrefix = $fetchPrefix->fetch(PDO::FETCH_ASSOC);
				$fetchData = $conoracle->prepare("SELECT mp.prename_desc,mb.memb_name,mb.memb_surname,mb.birth_date,mb.card_person,mb.salary_amount, 
													TRUNC(MONTHS_BETWEEN(sysdate,mb.birth_date) /12)  as birth_date_raw,
													mb.member_date,mb.work_date,mb.retry_date,mb.position_desc,mg.membgroup_desc,mt.membtype_desc,mb.membgroup_code,
													mb.ADDR_NO as ADDR_NO,
													mb.ADDR_MOO as ADDR_MOO,
													mb.ADDR_SOI as ADDR_SOI,
													mb.ADDR_VILLAGE as ADDR_VILLAGE,
													mb.ADDR_ROAD as ADDR_ROAD,
													MBT.TAMBOL_DESC AS TAMBOL_DESC,
													MBD.DISTRICT_DESC AS DISTRICT_DESC,
													MB.PROVINCE_CODE AS PROVINCE_CODE,
													MBP.PROVINCE_DESC AS PROVINCE_DESC,
													MB.ADDR_POSTCODE AS ADDR_POSTCODE,(sh.sharestk_amt * 10) AS SHAREBEGIN_AMT,sh.sharestk_amt,(sh.periodshare_amt * 10) as periodshare_amt,
													mb.addr_email as email,mb.addr_mobilephone as MEM_TELMOBILE, mariage_status
													FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
													LEFT JOIN MBUCFMEMBGROUP mg ON mb.MEMBGROUP_CODE = mg.MEMBGROUP_CODE
													LEFT JOIN MBUCFMEMBTYPE mt ON mb.MEMBTYPE_CODE = mt.MEMBTYPE_CODE
													LEFT JOIN MBUCFTAMBOL MBT ON mb.TAMBOL_CODE = MBT.TAMBOL_CODE
													LEFT JOIN MBUCFDISTRICT MBD ON mb.AMPHUR_CODE = MBD.DISTRICT_CODE
													LEFT JOIN MBUCFPROVINCE MBP ON mb.PROVINCE_CODE = MBP.PROVINCE_CODE
													LEFT JOIN shsharemaster sh ON mb.member_no = sh.member_no
													WHERE mb.member_no = :member_no");
				$fetchData->execute([
					':member_no' => $member_no
				]);
				$rowData = $fetchData->fetch(PDO::FETCH_ASSOC);
				
				$memberInfoMobile = $conmysql->prepare("SELECT phone_number,email FROM gcmemberaccount WHERE member_no = :member_no");
				$memberInfoMobile->execute([':member_no' => $payload["member_no"]]);
				$rowInfoMobile = $memberInfoMobile->fetch(PDO::FETCH_ASSOC);
		
				$rowGroupAddr = [];
				if(isset($rowData["MEMBGROUP_CODE"]) && $rowData["MEMBGROUP_CODE"] != ""){
					$fetchGroupAddr = $conoracle->prepare("select 
													mb.ADDR_PHONE,
													MBT.TAMBOL_DESC AS TAMBOL_DESC,
													MBD.DISTRICT_DESC AS DISTRICT_DESC,
													MBP.PROVINCE_DESC AS PROVINCE_DESC from MBUCFMEMBGROUP mb
													LEFT JOIN MBUCFTAMBOL MBT ON mb.ADDR_TAMBOL = MBT.TAMBOL_CODE
													LEFT JOIN MBUCFDISTRICT MBD ON mb.ADDR_AMPHUR = MBD.DISTRICT_CODE
													LEFT JOIN MBUCFPROVINCE MBP ON mb.ADDR_PROVINCE = MBP.PROVINCE_CODE
													where MEMBGROUP_CODE = :membgroup_code");
					$fetchGroupAddr->execute([
						':membgroup_code' => $rowData["MEMBGROUP_CODE"]
					]);
					$rowGroupAddr = $fetchGroupAddr->fetch(PDO::FETCH_ASSOC);
				}
				
				$rowMate = [];
				if(isset($rowData["MARIAGE_STATUS"]) && $rowData["MARIAGE_STATUS"] == "1"){
					$fetchMate = $conoracle->prepare("SELECT 
													mb.mateaddr_no as ADDR_NO,
													mb.mateaddr_moo as ADDR_MOO,
													mb.mateaddr_soi as ADDR_SOI,
													mb.mateaddr_village as ADDR_VILLAGE,
													mb.mateaddr_road as ADDR_ROAD,
													MBT.TAMBOL_DESC AS TAMBOL_DESC,
													MBD.DISTRICT_DESC AS DISTRICT_DESC,
													MB.MATEPROVINCE_CODE AS PROVINCE_CODE,
													MBP.PROVINCE_DESC AS PROVINCE_DESC,
													MB.MATEADDR_POSTCODE AS ADDR_POSTCODE,
													mate_name,mate_cardperson,mateaddr_phone
													FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
													LEFT JOIN MBUCFTAMBOL MBT ON mb.MATETAMBOL_CODE = MBT.TAMBOL_CODE
													LEFT JOIN MBUCFDISTRICT MBD ON mb.MATEAMPHUR_CODE = MBD.DISTRICT_CODE
													LEFT JOIN MBUCFPROVINCE MBP ON mb.MATEPROVINCE_CODE = MBP.PROVINCE_CODE
													WHERE mb.member_no = :member_no");
					$fetchMate->execute([
						':member_no' => $member_no
					]);
					$rowMate = $fetchMate->fetch(PDO::FETCH_ASSOC);
				}
				
				$pathFile = $config["URL_SERVICE"].'/resource/pdf/request_loan/'.$reqloan_doc.'.pdf?v='.time();
				$conmysql->beginTransaction();
				$getDeptacc = $conmysql->prepare("SELECT bank_account_no FROM gcallowmemberreqloan WHERE member_no = :member_no and is_allow = '1'");
				$getDeptacc->execute([':member_no' => $payload["member_no"]]);
				$rowDeptAcc = $getDeptacc->fetch(PDO::FETCH_ASSOC);
				$deptaccount_no_bank = $rowDeptAcc["bank_account_no"];
				$InsertFormOnline = $conmysql->prepare("INSERT INTO gcreqloan(reqloan_doc,member_no,loantype_code,request_amt,period_payment,period,loanpermit_amt,receive_net,
																		int_rate_at_req,salary_at_req,salary_img,citizen_img,id_userlogin,contractdoc_url,deptaccount_no_bank)
																		VALUES(:reqloan_doc,:member_no,:loantype_code,:request_amt,:period_payment,:period,:loanpermit_amt,:request_amt,:int_rate
																		,:salary,:salary_img,:citizen_img,:id_userlogin,:contractdoc_url,:deptaccount_no_bank)");
				if($InsertFormOnline->execute([
					':reqloan_doc' => $reqloan_doc,
					':member_no' => $payload["member_no"],
					':loantype_code' => $dataComing["loantype_code"],
					':request_amt' => $dataComing["request_amt"],
					':period_payment' => $dataComing["period_payment"],
					':period' => $dataComing["period"],
					':loanpermit_amt' => $dataComing["loanpermit_amt"],
					':int_rate' => $dataComing["int_rate"] / 100,
					':salary' => $rowData["SALARY_AMOUNT"],
					':salary_img' => $slipSalary,
					':citizen_img' => $citizenCopy,
					':id_userlogin' => $payload["id_userlogin"],
					':contractdoc_url' => $pathFile,
					':deptaccount_no_bank' => $deptaccount_no_bank ?? null,
				])){
				//if(true){
					$arrData = array();
					$arrData["birth_date"] = $lib->convertdate($rowData["BIRTH_DATE"],"D M Y");	
					$arrData["member_date"] = $lib->convertdate($rowData["MEMBER_DATE"],"D M Y");
					$arrData["retry_date"] = explode(" ",$lib->convertdate($rowData["RETRY_DATE"],"D M Y"))[2];
					$arrData["work_date"] = $lib->convertdate($rowData["WORK_DATE"],"D M Y");
					$arrData["mem_telmobile"] = $rowData["MEM_TELMOBILE"];
					$arrData["work_date_raw"] = $rowData["WORK_DATE"];
					$arrData["member_date_raw"] = $rowData["MEMBER_DATE"];
					$arrData["retry_date_raw"] = $rowData["RETRY_DATE"];
					$arrData["birth_date_raw"] = $rowData["BIRTH_DATE_RAW"];
					$arrData["requestdoc_no"] = $reqloan_doc;
					$arrData["loan_prefix"] = $rowPrefix["PREFIX"];
					$arrData["loantype_desc"] = $rowPrefix["LOANTYPE_DESC"];
					$arrData["full_name"] = $rowData["PRENAME_DESC"].$rowData["MEMB_NAME"].' '.$rowData["MEMB_SURNAME"];
					$arrData["name"] = $rowData["MEMB_NAME"].' '.$rowData["MEMB_SURNAME"];
					$arrData["member_no"] = $payload["member_no"];
					$arrData["position"] = $rowData["POSITION_DESC"];
					$arrData["pos_group"] = $rowData["MEMBGROUP_DESC"];
					$arrData["pos_group_code"] = $rowData["MEMBGROUP_CODE"];
					
					$arrData["group_district_desc"] = $rowGroupAddr["DISTRICT_DESC"];
					$arrData["group_tambol_desc"] = $rowGroupAddr["TAMBOL_DESC"];
					$arrData["group_province_desc"] = $rowGroupAddr["PROVINCE_DESC"];
					$arrData["group_phone"] = $rowGroupAddr["ADDR_PHONE"];
					
					$arrData["district_desc"] = $rowData["DISTRICT_DESC"];
					$arrData["tambol_desc"] = $rowData["TAMBOL_DESC"];
					$arrData["province_desc"] = $rowData["PROVINCE_DESC"];
					$arrData["addr_moo"] = $rowData["ADDR_MOO"];
					$arrData["addr_road"] = $rowData["ADDR_ROAD"];
					$arrData["addr_no"] = $rowData["ADDR_NO"];
					$arrData["addr_soi"] = $rowMate["ADDR_SOI"];
					$arrData["addr_postcode"] = $rowData["ADDR_POSTCODE"];
					$arrData["card_person"] = $rowData["CARD_PERSON"];
					
					$arrData["mate_name"] = $rowMate["MATE_NAME"];
					$arrData["mateaddr_no"] = $rowMate["ADDR_NO"];
					$arrData["mateaddr_moo"] = $rowMate["ADDR_MOO"];
					$arrData["mateaddr_village"] = $rowMate["ADDR_VILLAGE"];
					$arrData["mateaddr_road"] = $rowMate["ADDR_ROAD"];
					$arrData["matetambol_desc"] = $rowMate["TAMBOL_DESC"];
					$arrData["matedistrict_desc"] = $rowMate["DISTRICT_DESC"];
					$arrData["mateprovince_desc"] = $rowMate["PROVINCE_DESC"];
					$arrData["mate_cardperson"] = $rowMate["MATE_CARDPERSON"];
					$arrData["mateaddr_phone"] = $rowMate["MATEADDR_PHONE"];
					
					$arrData["loantype_code"] = $dataComing["loantype_code"];
					$arrData["objective"] = $dataComing["objective"];
					$arrData["salary_amount"] = number_format($rowData["SALARY_AMOUNT"],2);
					$arrData["share_bf"] = number_format($rowData["SHAREBEGIN_AMT"],2);
					$arrData["sharestk_amt"] = number_format($rowData["SHAREBEGIN_AMT"],2);
					$arrData["periodshare_amt"] = number_format($rowData["PERIODSHARE_AMT"],2);
					
					$fetchLoanGroup = $conoracle->prepare("SELECT LOANGROUP_CODE FROM LNLOANTYPE WHERE LOANTYPE_CODE = :loantype_code ");
					$fetchLoanGroup->execute([
						':loantype_code' => $dataComing["loantype_code"]
					]);
					$rowLoanGroup = $fetchLoanGroup->fetch(PDO::FETCH_ASSOC);
					$arrData["loangroup_code"] = $rowLoanGroup["LOANGROUP_CODE"];
					
					
					$fetchEmerLoan = $conoracle->prepare("SELECT LM.STARTCONT_DATE,LM.LOANCONTRACT_NO,LM.PRINCIPAL_BALANCE,LT.LOANTYPE_CODE,LT.LOANTYPE_DESC,LT.LOANGROUP_CODE
													FROM LNCONTMASTER LM 
													JOIN LNLOANTYPE LT ON LT.LOANTYPE_CODE = LM.LOANTYPE_CODE 
													WHERE LM.MEMBER_NO = :member_no 
													AND LM.CONTRACT_STATUS > 0 AND LM.CONTRACT_STATUS <> 8");
					$fetchEmerLoan->execute([
						':member_no' => $member_no
					]);
					$arrData["emer_contract"] = array();
					$arrData["common_contract"] = array();
					while($rowEmerLoan = $fetchEmerLoan->fetch(PDO::FETCH_ASSOC)){
						if($rowEmerLoan["LOANGROUP_CODE"] == '01'){
							$tempArr = array();
							$tempArr["STARTCONT_DATE"] = $lib->convertdate($rowEmerLoan["STARTCONT_DATE"],"D m Y");
							$tempArr["LOANCONTRACT_NO"] = $rowEmerLoan["LOANCONTRACT_NO"];
							$tempArr["PRINCIPAL_BALANCE"] = number_format($rowEmerLoan["PRINCIPAL_BALANCE"],2);
							$arrData["emer_contract"][] = $tempArr;
						}else{
							$tempArr = array();
							$tempArr["STARTCONT_DATE"] = $lib->convertdate($rowEmerLoan["STARTCONT_DATE"],"D m Y");
							$tempArr["LOANCONTRACT_NO"] = $rowEmerLoan["LOANCONTRACT_NO"];
							$tempArr["PRINCIPAL_BALANCE"] = number_format($rowEmerLoan["PRINCIPAL_BALANCE"],2);
							$arrData["common_contract"][] = $tempArr;
						}
					}
					
					$arrData["request_amt"] = $dataComing["request_amt"];
					$arrData["period_payment"] = $dataComing["period_payment"];
					$arrData["period"] = $dataComing["period"];
					$arrData["recv_account"] = $deptaccount_no_bank;
					if(file_exists('form_request_loan.php')){
						include('form_request_loan.php');
						$arrayPDF = GeneratePDFContract($arrData,$lib);
					}else{
						$arrayPDF["RESULT"] = FALSE;
					}
					
					if($arrayPDF["RESULT"]){
						/*$insertDocMaster = $conmysql->prepare("INSERT INTO doclistmaster(doc_no,docgrp_no,doc_filename,doc_type,doc_address,member_no)
																VALUES(:doc_no,:docgrp_no,:doc_filename,'pdf',:doc_address,:member_no)");
						$insertDocMaster->execute([
							':doc_no' => $reqloan_doc,
							':docgrp_no' => $rowGrpControl["docgrp_no"],
							':doc_filename' => $reqloan_doc,
							':doc_address' => $pathFile,
							':member_no' => $payload["member_no"]
						]);*/
						$insertDocList = $conmysql->prepare("INSERT INTO doclistdetail(doc_no,member_no,new_filename,id_userlogin)
																VALUES(:doc_no,:member_no,:file_name,:id_userlogin)");
						$insertDocList->execute([
							':doc_no' => $reqloan_doc,
							':member_no' => $payload["member_no"],
							':file_name' => $reqloan_doc.'.pdf',
							':id_userlogin' => $payload["id_userlogin"]
						]);
						$conmysql->commit();
						$arrayResult['SHOW_SLIP'] = TRUE;
						$arrayResult['APV_DOCNO'] = $reqloan_doc;
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
