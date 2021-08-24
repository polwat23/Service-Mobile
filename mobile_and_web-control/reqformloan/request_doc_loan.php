<?php
require_once('../autoload.php');


if($lib->checkCompleteArgument(['menu_component','loantype_code','request_amt','period_payment','period','loanpermit_amt'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanRequestForm')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		//get loangroup_code
		$getLoanGroup = $conmssql->prepare("SELECT LOANGROUP_CODE FROM lnloantype WHERE loantype_code = :loantype_code");
		$getLoanGroup->execute([
			':loantype_code' => $dataComing["loantype_code"]
		]);
		$rowLoanGroup = $getLoanGroup->fetch(PDO::FETCH_ASSOC);
		
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
				$cal_start_pay_date = $func->getConstant('cal_start_pay_date');
				$slipSalary = null;
				$citizenCopy = null;
				$fullPathSalary = null;
				$fullPathCitizen = null;
				$fullPathBookbank = null;
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
				if(isset($dataComing["upload_bookbank"]) && $dataComing["upload_bookbank"] != ""){
					$subpath = 'bookbank';
					$destination = __DIR__.'/../../resource/reqloan_doc/'.$reqloan_doc;
					$data_Img = explode(',',$dataComing["upload_bookbank"]);
					$info_img = explode('/',$data_Img[0]);
					$ext_img = str_replace('base64','',$info_img[1]);
					if(!file_exists($destination)){
						mkdir($destination, 0777, true);
					}
					if($ext_img == 'png' || $ext_img == 'jpg' || $ext_img == 'jpeg'){
						$createImage = $lib->base64_to_img($dataComing["upload_bookbank"],$subpath,$destination,null);
					}else if($ext_img == 'pdf'){
						$createImage = $lib->base64_to_pdf($dataComing["upload_bookbank"],$subpath,$destination);
					}
					if($createImage == 'oversize'){
						$arrayResult['RESPONSE_CODE'] = "WS0008";
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
						
					}else{
						if($createImage){
							$directory = __DIR__.'/../../resource/reqloan_doc/'.$reqloan_doc;
							$fullPathBookbank = __DIR__.'/../../resource/reqloan_doc/'.$reqloan_doc.'/'.$createImage["normal_path"];
							$bookbankCopy = $config["URL_SERVICE"]."resource/reqloan_doc/".$reqloan_doc."/".$createImage["normal_path"];
							$getControlFolderBookBank = $conmysql->prepare("SELECT docgrp_no FROM docgroupcontrol WHERE is_use = '1' and menu_component = :menu_component");
							$getControlFolderBookBank->execute([':menu_component' => $subpath]);
							$rowControlBookBank = $getControlFolderBookBank->fetch(PDO::FETCH_ASSOC);
							$insertDocMaster = $conmysql->prepare("INSERT INTO doclistmaster(doc_no,docgrp_no,doc_filename,doc_type,doc_address,member_no)
																	VALUES(:doc_no,:docgrp_no,:doc_filename,:doc_type,:doc_address,:member_no)");
							$insertDocMaster->execute([
								':doc_no' => $reqloan_doc.$subpath,
								':docgrp_no' => $rowControlBookBank["docgrp_no"],
								':doc_filename' => $reqloan_doc.$subpath,
								':doc_type' => $ext_img,
								':doc_address' => $bookbankCopy,
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
				if(isset($dataComing["upload_bookcoop"]) && $dataComing["upload_bookcoop"] != ""){
					$subpath = 'bookcoop';
					$destination = __DIR__.'/../../resource/reqloan_doc/'.$reqloan_doc;
					$data_Img = explode(',',$dataComing["upload_bookcoop"]);
					$info_img = explode('/',$data_Img[0]);
					$ext_img = str_replace('base64','',$info_img[1]);
					if(!file_exists($destination)){
						mkdir($destination, 0777, true);
					}
					if($ext_img == 'png' || $ext_img == 'jpg' || $ext_img == 'jpeg'){
						$createImage = $lib->base64_to_img($dataComing["upload_bookcoop"],$subpath,$destination,null);
					}else if($ext_img == 'pdf'){
						$createImage = $lib->base64_to_pdf($dataComing["upload_bookcoop"],$subpath,$destination);
					}
					if($createImage == 'oversize'){
						$arrayResult['RESPONSE_CODE'] = "WS0008";
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
						
					}else{
						if($createImage){
							$directory = __DIR__.'/../../resource/reqloan_doc/'.$reqloan_doc;
							$fullPathBookcoop = __DIR__.'/../../resource/reqloan_doc/'.$reqloan_doc.'/'.$createImage["normal_path"];
							$bookcoopCopy = $config["URL_SERVICE"]."resource/reqloan_doc/".$reqloan_doc."/".$createImage["normal_path"];
							$getControlFolderBookCoop = $conmysql->prepare("SELECT docgrp_no FROM docgroupcontrol WHERE is_use = '1' and menu_component = :menu_component");
							$getControlFolderBookCoop->execute([':menu_component' => $subpath]);
							$rowControlBookCoop = $getControlFolderBookCoop->fetch(PDO::FETCH_ASSOC);
							$insertDocMaster = $conmysql->prepare("INSERT INTO doclistmaster(doc_no,docgrp_no,doc_filename,doc_type,doc_address,member_no)
																	VALUES(:doc_no,:docgrp_no,:doc_filename,:doc_type,:doc_address,:member_no)");
							$insertDocMaster->execute([
								':doc_no' => $reqloan_doc.$subpath,
								':docgrp_no' => $rowControlBookCoop["docgrp_no"],
								':doc_filename' => $reqloan_doc.$subpath,
								':doc_type' => $ext_img,
								':doc_address' => $bookcoopCopy,
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
				$fetchData = $conmssql->prepare("SELECT MB.MEMB_NAME,MB.MEMB_SURNAME,MP.PRENAME_DESC,MUP.POSITION_DESC,MG.MEMBGROUP_DESC,MB.SALARY_AMOUNT,
														MD.DISTRICT_DESC,MBP.PROVINCE_DESC,(SH.SHAREBEGIN_AMT * 10) AS SHAREBEGIN_AMT
														FROM MBMEMBMASTER MB LEFT JOIN 
														MBUCFPRENAME MP ON MB.PRENAME_CODE = MP.PRENAME_CODE
														LEFT JOIN MBUCFMEMBGROUP MG ON MB.MEMBGROUP_CODE = MG.MEMBGROUP_CODE
														LEFT JOIN MBUCFPOSITION MUP ON MB.POSITION_CODE = MUP.POSITION_CODE
														LEFT JOIN MBUCFDISTRICT MD ON MG.DISTRICT_CODE = MD.DISTRICT_CODE
														LEFT JOIN MBUCFPROVINCE MBP ON MB.PROVINCE_CODE = MBP.PROVINCE_CODE
														LEFT JOIN SHSHAREMASTER SH ON MB.MEMBER_NO = SH.MEMBER_NO
														WHERE MB.MEMBER_NO = :member_no");
				$fetchData->execute([
					':member_no' => $member_no
				]);
				$rowData = $fetchData->fetch(PDO::FETCH_ASSOC);
				$dataMobile = $conmysql->prepare("SELECT phone_number,email FROM gcmemberaccount WHERE member_no = :member_no");
				$dataMobile->execute([':member_no' => $member_no]);
				$rowDataM = $dataMobile->fetch(PDO::FETCH_ASSOC);
				$getFundColl = $conmssql->prepare("SELECT SUM(EST_PRICE) as FUND_AMT FROM LNCOLLMASTER WHERE COLLMAST_TYPE = '05' AND MEMBER_NO = :member_no");
				$getFundColl->execute([':member_no' => $member_no]);
				$rowFund = $getFundColl->fetch(PDO::FETCH_ASSOC);
				//ดึงข้อมูลสัญญาเดิม
				$getOldContract = $conmssql->prepare("SELECT LM.PRINCIPAL_BALANCE,LT.LOANTYPE_DESC,LM.LOANCONTRACT_NO,LM.LAST_PERIODPAY, lt.LOANGROUP_CODE, lm.LOANTYPE_CODE
													FROM lncontmaster lm LEFT JOIN lnloantype lt ON lm.loantype_code = lt.loantype_code 
													WHERE lm.member_no = :member_no and lm.contract_status > 0 and lm.contract_status <> 8");
				$getOldContract->execute([
					':member_no' => $member_no
				]);
				$oldGroupBal01 = 0;
				$oldGroupBal02 = 0;
				$oldGroupBal02LastPeriod = 0;
				while($rowOldContract = $getOldContract->fetch(PDO::FETCH_ASSOC)){
					if($rowOldContract["LOANGROUP_CODE"] == "01"){
						$oldGroupBal01 += $rowOldContract["PRINCIPAL_BALANCE"];
					}else if($rowOldContract["LOANGROUP_CODE"] == "02"){
						$oldGroupBal02LastPeriod = $rowOldContract["LAST_PERIODPAY"];
						$oldGroupBal02 += $rowOldContract["PRINCIPAL_BALANCE"];
					}
				}

				$pathFile = $config["URL_SERVICE"].'/resource/pdf/request_loan/'.$reqloan_doc.'.pdf?v='.time();
				$conmysql->beginTransaction();
				$InsertFormOnline = $conmysql->prepare("INSERT INTO gcreqloan(reqloan_doc,member_no,loantype_code,request_amt,period_payment,period,loanpermit_amt,receive_net,
																		int_rate_at_req,salary_at_req,salary_img,bookbank_img,bookcoop_img,id_userlogin,contractdoc_url,
																		deptaccount_no_bank,bank_desc,deptaccount_no_coop,objective,extra_credit_project,diff_old_contract,old_contract)
																		VALUES(:reqloan_doc,:member_no,:loantype_code,:request_amt,:period_payment,:period,:loanpermit_amt,:receive_net,:int_rate
																		,:salary,:salary_img,:bookbank_img,:bookcoop_img,:id_userlogin,:contractdoc_url,:deptaccount_no_bank,:bank_desc,:deptaccount_no_coop,:objective,
																		:extra_credit_project,:diff_old,:old_contract)");
				if($InsertFormOnline->execute([
					':reqloan_doc' => $reqloan_doc,
					':member_no' => $payload["member_no"],
					':loantype_code' => $dataComing["loantype_code"],
					':request_amt' => $dataComing["request_amt"],
					':period_payment' => $dataComing["period_payment"],
					':period' => $dataComing["period"],
					':loanpermit_amt' => $dataComing["loanpermit_amt"],
					':receive_net' => $dataComing["request_amt"] - ($dataComing["old_contract_balance"] ?? 0),
					':int_rate' => $dataComing["int_rate"] / 100,
					':salary' => $rowData["SALARY_AMOUNT"],
					':salary_img' => $slipSalary ?? null ,
					':bookbank_img' => $bookbankCopy ?? null,
					':bookcoop_img' => $bookcoopCopy ?? null,
					':id_userlogin' => $payload["id_userlogin"],
					':contractdoc_url' => $pathFile,
					':deptaccount_no_bank' => $dataComing["expense_accid"] ?? null,
					':bank_desc' => $dataComing["expense_bank_desc"] ?? null,
					':deptaccount_no_coop' => $dataComing["deptaccount_no_coop"] ?? null,
					':objective' => $dataComing["objective"],
					':extra_credit_project' => $dataComing["extra_credit_project"] ?? null,
					':diff_old' => $dataComing["old_contract_balance"] ?? null,
					':old_contract' => $dataComing["old_contract_selected"] ?? null,
				])){
					$arrData = array();
					$arrData["requestdoc_no"] = $reqloan_doc;
					$arrData["full_name"] = $rowData["PRENAME_DESC"].$rowData["MEMB_NAME"].' '.$rowData["MEMB_SURNAME"];
					$arrData["name"] = $rowData["MEMB_NAME"].' '.$rowData["MEMB_SURNAME"];
					$arrData["member_no"] = $payload["member_no"];
					$arrData["position"] = $rowData["POSITION_DESC"];
					$arrData["pos_group"] = $rowData["MEMBGROUP_DESC"];
					$arrData["district_desc"] = $rowData["DISTRICT_DESC"];
					$arrData["province_desc"] = $rowData["PROVINCE_DESC"];
					$arrData["salary_amount"] = number_format($rowData["SALARY_AMOUNT"],2);
					$arrData["share_bf"] = number_format($rowData["SHAREBEGIN_AMT"],2);
					$arrData["fund_amt"] = number_format($rowData["rowFund"],2);
					$arrData["share_fund_amt"] = number_format($rowData["SHAREBEGIN_AMT"] + $rowFund["rowFund"],2);
					$arrData["request_amt"] = $dataComing["request_amt"];
					$arrData["objective"] = $dataComing["objective"];
					$arrData["period"] = $dataComing["period"];
					$arrData["tel"] = $rowDataM["phone_number"];
					$arrData["email"] = $rowDataM["email"];
					$arrData["deptaccount_no_bank"] = $dataComing["expense_accid"] ?? null;
					$arrData["bank_code"] = $dataComing["expense_bank"];
					$arrData["deptaccount_no_coop"] = $dataComing["deptaccount_no_coop"] ?? null;
					$arrData["period_payment"] = $dataComing["period_payment"];
					$arrData["extra_credit_project"] = isset($dataComing["extra_credit_project"]) && ($dataComing["extra_credit_project"] != "");
					$arrData["memb_prename"] = $rowData["PRENAME_DESC"];
					$arrData["memb_name"] = $rowData["MEMB_NAME"];
					$arrData["memb_surname"] = $rowData["MEMB_SURNAME"];
					$arrData["old_groupbal_01"] = $oldGroupBal01;
					$arrData["old_groupbal_02"] = $oldGroupBal02;
					$arrData["old_groupbal_02_period"] = $oldGroupBal02LastPeriod;
					$arrData["int_rate"] = $dataComing["int_rate"];
					$arrData["old_contract_selected"] = $dataComing["old_contract_selected"];
					
					if(file_exists('form_request_loan_'.$rowLoanGroup["LOANGROUP_CODE"].'.php')){
						include('form_request_loan_'.$rowLoanGroup["LOANGROUP_CODE"].'.php');
						$arrayPDF = GeneratePDFContract($arrData,$lib);
					}else{
						$arrayPDF["RESULT"] = FALSE;
					}
					if($arrayPDF["RESULT"]){
						$insertDocMaster = $conmysql->prepare("INSERT INTO doclistmaster(doc_no,docgrp_no,doc_filename,doc_type,doc_address,member_no)
																VALUES(:doc_no,:docgrp_no,:doc_filename,'pdf',:doc_address,:member_no)");
						$insertDocMaster->execute([
							':doc_no' => $reqloan_doc,
							':docgrp_no' => $rowConDoc["docgrp_no"],
							':doc_filename' => $reqloan_doc,
							':doc_address' => $pathFile,
							':member_no' => $payload["member_no"]
						]);
						$insertDocList = $conmysql->prepare("INSERT INTO doclistdetail(doc_no,member_no,new_filename,id_userlogin)
																VALUES(:doc_no,:member_no,:file_name,:id_userlogin)");
						$insertDocList->execute([
							':doc_no' => $reqloan_doc,
							':member_no' => $payload["member_no"],
							':file_name' => $reqloan_doc.'.pdf',
							':id_userlogin' => $payload["id_userlogin"]
						]);
						$conmysql->commit();
						$arrayResult['arrData'] = $arrData;
						//$arrayResult['REPORT_URL'] = $pathFile;
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
							':receive_net' => $dataComing["request_amt"] - ($dataComing["old_contract_balance"] ?? 0),
							':int_rate' => $dataComing["int_rate"] / 100,
							':salary' => $rowData["SALARY_AMOUNT"],
							':salary_img' => $slipSalary,
							':citizen_img' => $citizenCopy,
							':id_userlogin' => $payload["id_userlogin"],
							':contractdoc_url' => $pathFile,
							':deptaccount_no_bank' => $dataComing["deptaccount_no_bank"] ?? null,
							':bank_desc' => $dataComing["bank"] ?? null,
							':deptaccount_no_coop' => $dataComing["deptaccount_no_coop"] ?? null,
							':objective' => $dataComing["objective"],
							':extra_credit_project' => $dataComing["extra_credit_project"],
							':diff_old' => $dataComing["old_contract_balance"] ?? null,
							':old_contract' => $dataComing["old_contract_selected"] ?? null,
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
						':receive_net' => $dataComing["request_amt"] - ($dataComing["old_contract_balance"] ?? 0),
						':int_rate' => $dataComing["int_rate"] / 100,
						':salary' => $rowData["SALARY_AMOUNT"],
						':salary_img' => $slipSalary,
						':citizen_img' => $citizenCopy,
						':id_userlogin' => $payload["id_userlogin"],
						':contractdoc_url' => $pathFile,
						':deptaccount_no_bank' => $dataComing["deptaccount_no_bank"] ?? null,
						':bank_desc' => $dataComing["bank"] ?? null,
						':deptaccount_no_coop' => $dataComing["deptaccount_no_coop"] ?? null,
						':objective' => $dataComing["objective"],
						':expense_bank' => $dataComing["expense_bank"],
						':expense_code' => $dataComing["expense_code"],
						':expense_accid' => $dataComing["expense_accid"],
						':diff_old' => $dataComing["old_contract_balance"] ?? null,
						':old_contract' => $dataComing["old_contract_selected"] ?? null,
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
