<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SettingMemberInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrConstInfo = array();
		$getConstInfo = $conmssql->prepare("SELECT const_code,save_tablecore FROM gcconstantchangeinfo");
		$getConstInfo->execute();
		while($rowConst = $getConstInfo->fetch(PDO::FETCH_ASSOC)){
			$arrConstInfo[$rowConst["const_code"]] = $rowConst["save_tablecore"];
		}
		if(isset($dataComing["email"]) && $dataComing["email"] != ""){
			if($arrConstInfo["email"] == '1'){
				$arrayResult['RESULT_EMAIL'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$memberInfo = $conmssqlcoop->prepare("SELECT EMAIL FROM COCOOPTATION WHERE member_id = :member_no");
				$memberInfo->execute([':member_no' => $member_no]);
				$rowMember = $memberInfo->fetch(PDO::FETCH_ASSOC);
				$insertChangeData = $conmssql->prepare("INSERT INTO gcmembereditdata(member_no,old_data,incoming_data,inputgroup_type)
														VALUES(:member_no,:old_email,:email,'email')");
				if($insertChangeData->execute([
					':member_no' => $payload["member_no"],
					':old_email' => $rowMember["EMAIL"],
					':email' => $dataComing["email"]
				])){
					$arrayResult["RESULT_ADDRESS"] = TRUE;
				}else{
					$filename = basename(__FILE__, '.php');
					$logStruc = [
						":error_menu" => $filename,
						":error_code" => "WS1003",
						":error_desc" => "แก้ไขที่อยู่ไม่ได้เพราะ insert ลงตาราง gcmembereditdata ไม่ได้"."\n"."Query => ".$insertChangeData->queryString."\n"."Param => ". json_encode([
							':member_no' => $payload["member_no"],
							':old_email' => $rowMember["EMAIL"],
							':email' => $dataComing["email"]
						]),
						":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
					];
					$log->writeLog('errorusage',$logStruc);
					$arrayResult["RESULT_ADDRESS"] = FALSE;
				}
			}
		}
		if(isset($dataComing["tel"]) && $dataComing["tel"] != ""){
			if($arrConstInfo["tel"] == '1'){
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$memberInfo = $conmssqlcoop->prepare("SELECT TELEPHONE FROM COCOOPTATION WHERE member_id = :member_no");
				$memberInfo->execute([':member_no' => $member_no]);
				$rowMember = $memberInfo->fetch(PDO::FETCH_ASSOC);
				$insertChangeData = $conmssql->prepare("INSERT INTO gcmembereditdata(member_no,old_data,incoming_data,inputgroup_type)
														VALUES(:member_no,:old_tel,:tel,'tel')");
				if($insertChangeData->execute([
					':member_no' => $payload["member_no"],
					':old_tel' => $rowMember["TELEPHONE"],
					':tel' => $dataComing["tel"]
				])){
					$arrayResult["RESULT_ADDRESS"] = TRUE;
				}else{
					$filename = basename(__FILE__, '.php');
					$logStruc = [
						":error_menu" => $filename,
						":error_code" => "WS1003",
						":error_desc" => "แก้ไขที่อยู่ไม่ได้เพราะ insert ลงตาราง gcmembereditdata ไม่ได้"."\n"."Query => ".$insertChangeData->queryString."\n"."Param => ". json_encode([
							':member_no' => $payload["member_no"],
							':old_tel' => $rowMember["TELEPHONE"],
							':tel' => $dataComing["tel"]
						]),
						":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
					];
					$log->writeLog('errorusage',$logStruc);
					$arrayResult["RESULT_ADDRESS"] = FALSE;
				}
			}
		}
		if(isset($dataComing["address"]) && $dataComing["address"] != ""){
			if($arrConstInfo["address"] == '1'){
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$memberInfo = $conmssqlcoop->prepare("SELECT ADDRESS1 FROM COCOOPTATION WHERE member_id = :member_no");
				$memberInfo->execute([':member_no' => $member_no]);
				$rowMember = $memberInfo->fetch(PDO::FETCH_ASSOC);
				$arressArr = $dataComing["address"];
				$insertChangeData = $conmssql->prepare("INSERT INTO gcmembereditdata(member_no,old_data,incoming_data,inputgroup_type)
														VALUES(:member_no,:old_address,:address,'address')");
				if($insertChangeData->execute([
					':member_no' => $payload["member_no"],
					':old_address' => $rowMember["ADDRESS1"],
					':address' => json_encode($arressArr,JSON_UNESCAPED_UNICODE )
				])){
					$arrayResult["RESULT_ADDRESS"] = TRUE;
				}else{
					$filename = basename(__FILE__, '.php');
					$logStruc = [
						":error_menu" => $filename,
						":error_code" => "WS1003",
						":error_desc" => "แก้ไขที่อยู่ไม่ได้เพราะ insert ลงตาราง gcmembereditdata ไม่ได้"."\n"."Query => ".$insertChangeData->queryString."\n"."Param => ". json_encode([
							':member_no' => $payload["member_no"],
							':old_address' => json_encode($arrOldAddress),
							':address' => json_encode($dataComing["address"])
						]),
						":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
					];
					$log->writeLog('errorusage',$logStruc);
					$arrayResult["RESULT_ADDRESS"] = FALSE;
				}
			}
		}

		if(isset($arrayResult["RESULT_EMAIL"]) && !$arrayResult["RESULT_EMAIL"]){
			$arrayResult['RESPONSE_CODE'] = "WS1010";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
		if(isset($arrayResult["RESULT_TEL"]) && !$arrayResult["RESULT_TEL"]){
			$arrayResult['RESPONSE_CODE'] = "WS1003";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
		if(isset($arrayResult["RESULT_ADDRESS"]) && !$arrayResult["RESULT_ADDRESS"]){
			$arrayResult['RESPONSE_CODE'] = "WS1039";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}

		$arrayResult['RESULT'] = TRUE;
		require_once('../../include/exit_footer.php');
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