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
		$getLastDoc = substr($rowLastDocno["REQLOAN_DOC"],8);
		$reqloan_doc = date("Ymd").($getLastDoc + 1);
		if(isset($dataComing["upload_slip_salary"]) && $dataComing["upload_slip_salary"] != ""){
			$subpath = 'salary';
			$destination = __DIR__.'/../../resource/reqloan_doc/'.$reqloan_doc.'/'.$subpath;
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
				echo json_encode($arrayResult);
				exit();
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
			$destination = __DIR__.'/../../resource/reqloan_doc/'.$reqloan_doc.'/'.$subpath;
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
				unlink($fullPathSalary);
				rmdir($directory);
				$arrayResult['RESPONSE_CODE'] = "WS0008";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}else{
				if($createImage){
					$directory = __DIR__.'/../../resource/reqloan_doc/'.$reqloan_doc;
					$fullPathCitizen = __DIR__.'/../../resource/reqloan_doc/'.$reqloan_doc.'/'.$createImage["normal_path"];
					$citizenCopy = $config["URL_SERVICE"]."resource/reqloan_doc/".$reqloan_doc."/".$createImage["normal_path"];
				}
			}
		}
		$getSalaryId = $conoracle->prepare("SELECT salary_amount FROM mbmembmaster WHERE member_no = :member_no");
		$getSalaryId->execute([':member_no' => $member_no]);
		$rowSalary = $getSalaryId->fetch(PDO::FETCH_ASSOC);
		$InsertFormOnline = $conmysql->prepare("INSERT INTO gcreqloan(reqloan_doc,member_no,loantype_code,request_amt,period_payment,period,loanpermit_amt,receive_net,
																int_rate_at_req,salary_at_req,salary_img,citizen_img,id_userlogin)
																VALUES(:member_no,:loantype_code,:request_amt,:period_payment,:period,:loanpermit_amt,:request_amt,:int_rate,:salary,:salary_img,:citizen_img,:id_userlogin)");
		if($InsertFormOnline->execute([
			':reqloan_doc' => $reqloan_doc,
			':member_no' => $payload["member_no"],
			':loantype_code' => $dataComing["loantype_code"],
			':request_amt' => $dataComing["request_amt"],
			':period_payment' => $dataComing["period_payment"],
			':period' => $dataComing["period"],
			':loanpermit_amt' => $dataComing["loanpermit_amt"],
			':int_rate' => $dataComing["int_rate"],
			':salary' => $rowSalary["salary_amount"],
			':salary_img' => $slipSalary,
			':citizen_img' => $citizenCopy,
			':id_userlogin' => $payload["id_userlogin"]
		])){
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
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
					':int_rate' => $dataComing["int_rate"],
					':salary' => $rowSalary["salary_amount"],
					':salary_img' => $slipSalary,
					':citizen_img' => $citizenCopy,
					':id_userlogin' => $payload["id_userlogin"]
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
				':int_rate' => $dataComing["int_rate"],
				':salary' => $rowSalary["salary_amount"],
				':salary_img' => $slipSalary,
				':citizen_img' => $citizenCopy,
				':id_userlogin' => $payload["id_userlogin"]
			]);
			$lib->sendLineNotify($message_error);
			$arrayResult['RESPONSE_CODE'] = "WS1036";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
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
	echo json_encode($arrayResult);
	exit();
}
?>