<?php
require_once('../autoload.php');


if($lib->checkCompleteArgument(['menu_component','loantype_code','request_amt','period_payment','period','loanpermit_amt','option_paytype'],$dataComing)){
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
				$cal_start_pay_date = $func->getConstant('cal_start_pay_date');
				$slipSalary = null;
				$citizenCopy = null;
				$houseRegisCopy = null;
				$fullPathSalary = null;
				$fullPathCitizen = null;
				$fullPathHouseRegis = null;
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
						$createImage = $lib->base64_to_img($dataComing["upload_slip_salary"],$reqloan_doc.$subpath,$destination,null);
					}else if($ext_img == 'pdf'){
						$createImage = $lib->base64_to_pdf($dataComing["upload_slip_salary"],$reqloan_doc.$subpath,$destination);
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
						$createImage = $lib->base64_to_img($dataComing["upload_citizen_copy"],$reqloan_doc.$subpath,$destination,null);
					}else if($ext_img == 'pdf'){
						$createImage = $lib->base64_to_pdf($dataComing["upload_citizen_copy"],$reqloan_doc.$subpath,$destination);
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
				if(isset($dataComing["upload_house_regis_copy"]) && $dataComing["upload_house_regis_copy"] != ""){
					$subpath = 'house_regis';
					$destination = __DIR__.'/../../resource/reqloan_doc/'.$reqloan_doc;
					$data_Img = explode(',',$dataComing["upload_house_regis_copy"]);
					$info_img = explode('/',$data_Img[0]);
					$ext_img = str_replace('base64','',$info_img[1]);
					if(!file_exists($destination)){
						mkdir($destination, 0777, true);
					}
					if($ext_img == 'png' || $ext_img == 'jpg' || $ext_img == 'jpeg'){
						$createImage = $lib->base64_to_img($dataComing["upload_house_regis_copy"],$reqloan_doc.$subpath,$destination,null);
					}else if($ext_img == 'pdf'){
						$createImage = $lib->base64_to_pdf($dataComing["upload_house_regis_copy"],$reqloan_doc.$subpath,$destination);
					}
					if($createImage == 'oversize'){
						unlink($fullPathSalary);
						unlink($fullPathCitizen);
						rmdir($directory);
						$arrayResult['RESPONSE_CODE'] = "WS0008";
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
						
					}else{
						if($createImage){
							$directory = __DIR__.'/../../resource/reqloan_doc/'.$reqloan_doc;
							$fullPathHouseRegis = __DIR__.'/../../resource/reqloan_doc/'.$reqloan_doc.'/'.$createImage["normal_path"];
							$houseRegisCopy = $config["URL_SERVICE"]."resource/reqloan_doc/".$reqloan_doc."/".$createImage["normal_path"];
							$getControlFolderHouseRegis = $conmysql->prepare("SELECT docgrp_no FROM docgroupcontrol WHERE is_use = '1' and menu_component = :menu_component");
							$getControlFolderHouseRegis->execute([':menu_component' => $subpath]);
							$rowControlHouseRegis = $getControlFolderHouseRegis->fetch(PDO::FETCH_ASSOC);
							$insertDocMaster = $conmysql->prepare("INSERT INTO doclistmaster(doc_no,docgrp_no,doc_filename,doc_type,doc_address,member_no)
																	VALUES(:doc_no,:docgrp_no,:doc_filename,:doc_type,:doc_address,:member_no)");
							$insertDocMaster->execute([
								':doc_no' => $reqloan_doc.$subpath,
								':docgrp_no' => $rowControlHouseRegis["docgrp_no"],
								':doc_filename' => $reqloan_doc.$subpath,
								':doc_type' => $ext_img,
								':doc_address' => $houseRegisCopy,
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
				$fetchData = $conoracle->prepare("SELECT mb.memb_name,mb.memb_surname,mp.prename_desc,mb.position_desc,mg.membgroup_desc,mc.MEMBGROUP_DESC as GROUPWORK,mb.salary_amount,mb.card_person,
														(sh.SHAREBEGIN_AMT * 10) AS SHAREBEGIN_AMT
														FROM mbmembmaster mb LEFT JOIN 
														mbucfprename mp ON mb.prename_code = mp.prename_code
														LEFT JOIN mbucfmembgroup mg ON mb.MEMBGROUP_CODE = mg.MEMBGROUP_CODE 
														LEFT JOIN mbucfmembgroup mc ON mg.MEMBGROUP_CONTROL = mc.MEMBGROUP_CODE
														LEFT JOIN shsharemaster sh ON mb.member_no = sh.member_no
														WHERE mb.member_no = :member_no");
				$fetchData->execute([
					':member_no' => $member_no
				]);
				$rowData = $fetchData->fetch(PDO::FETCH_ASSOC);
				$pathFile = $config["URL_SERVICE"].'/resource/pdf/request_loan/'.$reqloan_doc.'.pdf?v='.time();
				$conmysql->beginTransaction();
				$getDeptATM = $conoracle->prepare("SELECT DEPTACCOUNT_NO FROM dpdeptmaster WHERE depttype_code = '88' and deptclose_status = 0 and member_no = :member_no");
				$getDeptATM->execute([':member_no' => $member_no]);
				$rowDept = $getDeptATM->fetch(PDO::FETCH_ASSOC);
				
				$oldbal = 0;
				if($dataComing["loantype_code"] == '25'){
					$oldContract = $conoracle->prepare("SELECT lm.PRINCIPAL_BALANCE,lt.loantype_desc,lm.LOANCONTRACT_NO
									FROM lncontmaster lm LEFT JOIN lnloantype lt ON lm.loantype_code = lt.loantype_code 
									WHERE lm.member_no = :member_no and lm.contract_status > 0 and lm.contract_status <> 8 and lm.loantype_code IN('25')");
					$oldContract->execute([
						':member_no' => $member_no
					]);
					while($rowOldContract = $oldContract->fetch(PDO::FETCH_ASSOC)){
						$oldbal += $rowOldContract["PRINCIPAL_BALANCE"];
					}
				}
				$InsertFormOnline = $conmysql->prepare("INSERT INTO gcreqloan(reqloan_doc,member_no,loantype_code,request_amt,period_payment,period,loanpermit_amt,diff_old_contract,receive_net,
																		int_rate_at_req,salary_at_req,salary_img,citizen_img,house_regis_img,id_userlogin,contractdoc_url,deptaccount_no_coop,objective,option_pay,pay_date,channel)
																		VALUES(:reqloan_doc,:member_no,:loantype_code,:request_amt,:period_payment,:period,:loanpermit_amt,:diff_old,:receive_net,:int_rate
																		,:salary,:salary_img,:citizen_img,:house_regis_img,:id_userlogin,:contractdoc_url,:deptaccount_no_coop,:objective,:option_pay,:pay_date,:channel)");
				if($InsertFormOnline->execute([
					':reqloan_doc' => $reqloan_doc,
					':member_no' => $payload["member_no"],
					':loantype_code' => $dataComing["loantype_code"],
					':request_amt' => $dataComing["request_amt"],
					':period_payment' => $dataComing["period_payment"],
					':period' => $dataComing["period"],
					':loanpermit_amt' => $dataComing["loanpermit_amt"],
					':diff_old' => $oldbal,
					':receive_net' => $dataComing["request_amt"] - $oldbal,
					':int_rate' => $dataComing["int_rate"] / 100,
					':salary' => $rowData["SALARY_AMOUNT"],
					':salary_img' => $slipSalary,
					':citizen_img' => $citizenCopy,
					':house_regis_img' => $houseRegisCopy,
					':id_userlogin' => $payload["id_userlogin"],
					':contractdoc_url' => $pathFile,
					':deptaccount_no_coop' => $rowDept["DEPTACCOUNT_NO"],
					':objective' => $dataComing["objective"],
					':option_pay' => $dataComing["option_paytype"],
					':pay_date' => date("Y-m-t", strtotime('last day of '.$cal_start_pay_date.' month',strtotime(date('Y-m-d')))),
					':channel' => $dataComing["channel"]
				])){
					$getTel = $conmysql->prepare("SELECT phone_number FROM gcmemberaccount WHERE member_no = :member_no");
					$getTel->execute([':member_no' => $payload["member_no"]]);
					$rowTel = $getTel->fetch(PDO::FETCH_ASSOC);
					$arrData = array();
					$arrData["requestdoc_no"] = $reqloan_doc;
					$arrData["request_date"] = $lib->convertdate(date('d/m/Y'),'d M Y');
					$arrData["full_name"] = $rowData["PRENAME_DESC"].$rowData["MEMB_NAME"].' '.$rowData["MEMB_SURNAME"];
					$arrData["name"] = $rowData["MEMB_NAME"].' '.$rowData["MEMB_SURNAME"];
					$arrData["member_no"] = $payload["member_no"];
					$arrData["position"] = $rowData["POSITION_DESC"];
					$arrData["pos_group"] = $rowData["MEMBGROUP_DESC"];
					$arrData["district_desc"] = $rowData["GROUPWORK"];
					$arrData["salary_amount"] = number_format($rowData["SALARY_AMOUNT"],2);
					$arrData["share_bf"] = number_format($rowData["SHAREBEGIN_AMT"],2);
					$arrData["request_amt"] = $dataComing["request_amt"];
					$arrData["period_payment"] = $dataComing["period_payment"];
					$arrData["period"] = $dataComing["period"];
					$arrData["pay_date"] = $lib->convertdate(date("Y-m-t", strtotime('last day of '.$cal_start_pay_date.' month',strtotime(date('Y-m-d')))),'d M Y');
					$arrData["objective"] = $dataComing["objective"];
					$arrData["tel"] = $rowTel["phone_number"];
					$arrData["int_rate"] = $dataComing["int_rate"];
					$arrData["card_person"] = $rowData["CARD_PERSON"];
					if($dataComing["option_paytype"] == '0'){
						$arrData["option_pay"] = "คงต้น";
					}else if($dataComing["option_paytype"] == '2'){
						$arrData["option_pay"] = "ชำระแค่ดอกเบี้ย";
					}else{
						$arrData["option_pay"] = "คงยอด";
					}
					$arrData["dept_no"] = $lib->formataccount($rowDept["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
					if(file_exists('form_request_loan_'.$dataComing["loantype_code"].'.php')){
						include('form_request_loan_'.$dataComing["loantype_code"].'.php');
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
						$arrayResult['REPORT_URL'] = $pathFile;
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
					unlink($fullPathHouseRegis);
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
							':receive_net' => $dataComing["request_amt"] - $oldbal,
							':int_rate' => $dataComing["int_rate"] / 100,
							':salary' => $rowData["SALARY_AMOUNT"],
							':salary_img' => $slipSalary,
							':citizen_img' => $citizenCopy,
							':id_userlogin' => $payload["id_userlogin"],
							':contractdoc_url' => $pathFile,
							':deptaccount_no_coop' => $rowDept["DEPTACCOUNT_NO"],
							':objective' => $dataComing["objective"],
							':option_pay' => $dataComing["option_paytype"],
							':pay_date' => date("Y-m-t", strtotime('last day of '.$cal_start_pay_date.' month',strtotime(date('Y-m-d')))),
							':channel' => $dataComing["channel"]
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
						':receive_net' => $dataComing["request_amt"] - $oldbal,
						':int_rate' => $dataComing["int_rate"] / 100,
						':salary' => $rowData["SALARY_AMOUNT"],
						':salary_img' => $slipSalary,
						':citizen_img' => $citizenCopy,
						':id_userlogin' => $payload["id_userlogin"],
						':contractdoc_url' => $pathFile,
						':deptaccount_no_coop' => $rowDept["DEPTACCOUNT_NO"],
						':objective' => $dataComing["objective"],
						':option_pay' => $dataComing["option_paytype"],
						':pay_date' => date("Y-m-t", strtotime('last day of '.$cal_start_pay_date.' month',strtotime(date('Y-m-d')))),
						':channel' => $dataComing["channel"]
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