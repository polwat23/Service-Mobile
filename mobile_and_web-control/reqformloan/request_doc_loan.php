<?php
require_once('../autoload.php');


if($lib->checkCompleteArgument(['menu_component','loantype_code','request_amt','period_payment','period','loanpermit_amt'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanRequestForm')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$slipSalary = null;
		$citizenCopy = null;
		$fullPathSalary = null;
		$fullPathCitizen = null;
		$directory = null;
		$getLastDocno = $conmysql->prepare("SELECT MAX(reqloan_doc) as REQLOAN_DOC FROM gcreqloan");
		$getLastDocno->execute();
		$rowLastDocno = $getLastDocno->fetch(PDO::FETCH_ASSOC);
		$getLastDoc = isset($rowLastDocno["REQLOAN_DOC"]) && $rowLastDocno["REQLOAN_DOC"] != "" ? substr($rowLastDocno["REQLOAN_DOC"],11) : 0;
		$reqloan_doc = 'D'.$dataComing["loantype_code"].date("Ymd").str_pad(intval($getLastDoc) + 1,4,0,STR_PAD_LEFT);
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
				}
			}
		}
		$memberInfoMobile = $conmysql->prepare("SELECT phone_number,email,path_avatar,member_no FROM gcmemberaccount WHERE member_no = :member_no");
		$memberInfoMobile->execute([':member_no' => $payload["member_no"]]);
		$rowInfoMobile = $memberInfoMobile->fetch(PDO::FETCH_ASSOC);
		$fetchData = $conoracle->prepare("SELECT mb.memb_name,mb.memb_surname,mp.prename_desc,mb.position_desc,mg.membgroup_desc,mb.salary_amount,mb.birth_date,
												md.district_desc,(sh.sharestk_amt * 10) as SHARE_AMT,(sh.SHAREBEGIN_AMT * 10) AS SHAREBEGIN_AMT,
												(periodshare_amt * 10) as PERIOD_SHARE_AMT
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
		$conmysql->beginTransaction();
		$InsertFormOnline = $conmysql->prepare("INSERT INTO gcreqloan(reqloan_doc,member_no,loantype_code,request_amt,period_payment,period,loanpermit_amt,receive_net,
																int_rate_at_req,salary_at_req,salary_img,citizen_img,id_userlogin,contractdoc_url,objective)
																VALUES(:reqloan_doc,:member_no,:loantype_code,:request_amt,:period_payment,:period,:loanpermit_amt,:request_amt,:int_rate
																,:salary,:salary_img,:citizen_img,:id_userlogin,:contractdoc_url,:objective)");
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
			':objective' => $dataComing["objective"] ?? null
		])){
			$getUcollwho = $conoracle->prepare("SELECT
												LCC.LOANCONTRACT_NO AS LOANCONTRACT_NO,
												LNTYPE.loantype_desc as TYPE_DESC,LNTYPE.loangroup_code,
												PRE.PRENAME_DESC,MEMB.MEMB_NAME,MEMB.MEMB_SURNAME,
												LCM.MEMBER_NO AS MEMBER_NO,
												NVL(LCM.principal_balance,0) as LOAN_BALANCE,
												LCM.LAST_PERIODPAY as LAST_PERIOD,
												LCM.period_payamt as PERIOD
												FROM
												LNCONTCOLL LCC LEFT JOIN LNCONTMASTER LCM ON  LCC.LOANCONTRACT_NO = LCM.LOANCONTRACT_NO
												LEFT JOIN MBMEMBMASTER MEMB ON LCM.MEMBER_NO = MEMB.MEMBER_NO
												LEFT JOIN MBUCFPRENAME PRE ON MEMB.PRENAME_CODE = PRE.PRENAME_CODE
												LEFT JOIN lnloantype LNTYPE  ON LCM.loantype_code = LNTYPE.loantype_code
												WHERE
												LCM.CONTRACT_STATUS > 0 AND LCM.CONTRACT_STATUS <> 8
												AND LCC.LOANCOLLTYPE_CODE = '01'
												AND LCC.REF_COLLNO = :member_no");
			$getUcollwho->execute([':member_no' => $member_no]);
			$coll_01 = 0;
			$coll_02 = 0;
			while($rowUcollwho = $getUcollwho->fetch(PDO::FETCH_ASSOC)){
				if($rowUcollwho["LOANGROUP_CODE"] == '01'){
					$coll_01 += $rowUcollwho["LOAN_BALANCE"];
				}else if($rowUcollwho["LOANGROUP_CODE"] == '02'){
					$coll_02 += $rowUcollwho["LOAN_BALANCE"];
				}
			}
			$arrData = array();
			$arrData["requestdoc_no"] = $reqloan_doc;
			$arrData["full_name"] = $rowData["PRENAME_DESC"].$rowData["MEMB_NAME"].' '.$rowData["MEMB_SURNAME"];
			$arrData["name"] = $rowData["MEMB_NAME"].' '.$rowData["MEMB_SURNAME"];
			$arrData["member_no"] = $payload["member_no"];
			$arrData["birth_date"] = $rowData["BIRTH_DATE"];
			$arrData["position"] = $rowData["POSITION_DESC"];
			$arrData["pos_group"] = $rowData["MEMBGROUP_DESC"];
			$arrData["district_desc"] = $rowData["DISTRICT_DESC"];
			$arrData["salary_amount"] = number_format($rowData["SALARY_AMOUNT"],2);
			$arrData["sum_amount"] = number_format($rowData["SALARY_AMOUNT"],2);
			$arrData["share_bf"] = number_format($rowData["SHAREBEGIN_AMT"],2);
			$arrData["share_amt"] = $rowData["SHARE_AMT"];
			$arrData["period_share_amt"] = number_format($rowData["PERIOD_SHARE_AMT"],2);
			$arrData["request_amt"] = $dataComing["request_amt"] ?? 0;
			$arrData["objective"] = $dataComing["objective"] ?? "";
			$arrData["phone"] = $lib->formatphone($rowInfoMobile["phone_number"]);
			$arrData["coll_01"] = number_format($coll_01,2);
			$arrData["coll_02"] = number_format($coll_02,2);
			$arrData["period_payment"] = number_format($dataComing["period_payment"],2);
			$arrData["period"] = number_format($dataComing["period"]);
			$arrData["int_rate"] = $dataComing["int_rate"];
			$arrData["type_desc"] = $dataComing["int_rate"];
			if(file_exists('form_request_loan_'.$dataComing["loantype_code"].'.php')){
				include('form_request_loan_'.$dataComing["loantype_code"].'.php');
				$arrayPDF = GeneratePDFContract($arrData,$lib);
			}else{
				$arrayPDF["RESULT"] = FALSE;
			}
			
			if($arrayPDF["RESULT"]){
				$conmysql->commit();
				//$arrayResult['REPORT_URL'] = $pathFile;
				$arrayResult['APV_DOCNO'] = $reqloan_doc;
				$arrayResult['SHOW_SLIP'] = TRUE;
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
					':contractdoc_url' => $pathFile
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
				':contractdoc_url' => $pathFile
			]);
			$lib->sendLineNotify($message_error);
			$arrayResult['RESPONSE_CODE'] = "WS1036";
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