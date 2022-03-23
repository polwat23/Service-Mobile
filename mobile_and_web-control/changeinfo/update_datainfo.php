<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SettingMemberInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrConstInfo = array();
		$getConstInfo = $conmysql->prepare("SELECT const_code,save_tablecore FROM gcconstantchangeinfo");
		$getConstInfo->execute();
		while($rowConst = $getConstInfo->fetch(PDO::FETCH_ASSOC)){
			$arrConstInfo[$rowConst["const_code"]] = $rowConst["save_tablecore"];
		}
		if(isset($dataComing["email"]) && $dataComing["email"] != ""){
			$conmysql->beginTransaction();
			$getOldEmail = $conmysql->prepare("SELECT email FROM gcmemberaccount WHERE member_no = :member_no");
			$getOldEmail->execute([':member_no' => $payload["member_no"]]);
			$rowEmail = $getOldEmail->fetch(PDO::FETCH_ASSOC);
			$updateEmail = $conmysql->prepare("UPDATE gcmemberaccount SET email = :email WHERE member_no = :member_no");
			if($updateEmail->execute([
				':email' => $dataComing["email"],
				':member_no' => $payload["member_no"]
			])){
				$logStruc = [
					":member_no" => $payload["member_no"],
					":old_data" => $rowEmail["email"] ?? "-",
					":new_data" => $dataComing["email"] ?? "-",
					":data_type" => "email",
					":id_userlogin" => $payload["id_userlogin"]
				];
				$log->writeLog('editinfo',$logStruc);
				if($arrConstInfo["email"] == '1'){
					$updateEmailOracle = $conoracle->prepare("UPDATE mbmembmaster SET addr_email = :email WHERE member_no = :member_no");
					if($updateEmailOracle->execute([
						':email' => $dataComing["email"],
						':member_no' => $member_no
					])){
						$conmysql->commit();
						$arrayResult['RESULT_EMAIL'] = TRUE;
					}else{
						$conmysql->rollback();
						$arrayResult['RESULT_EMAIL'] = FALSE;
					}
				}else{
					$conmysql->commit();
					$arrayResult['RESULT_EMAIL'] = TRUE;
				}
			}else{
				$conmysql->rollback();
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS1010",
					":error_desc" => "แก้ไขอีเมลไม่ได้เพราะ update ลงตาราง gcmemberaccount ไม่ได้"."\n"."Query => ".$updateEmail->queryString."\n"."Param => ". json_encode([
						':email' => $dataComing["email"],
						':member_no' => $payload["member_no"]
					]),
					":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$message_error = "แก้ไขอีเมลไม่ได้เพราะ update ลง gcmemberaccount ไม่ได้"."\n"."Query => ".$updateEmail->queryString."\n"."Param => ". json_encode([
					':email' => $dataComing["email"],
					':member_no' => $payload["member_no"]
				]);
				$lib->sendLineNotify($message_error);
				$arrayResult['RESULT_EMAIL'] = FALSE;
			}
		}
		if(isset($dataComing["tel"]) && $dataComing["tel"] != ""){
			$conmysql->beginTransaction();
			$getOldTel = $conmysql->prepare("SELECT phone_number FROM gcmemberaccount WHERE member_no = :member_no");
			$getOldTel->execute([':member_no' => $payload["member_no"]]);
			$rowTel = $getOldTel->fetch(PDO::FETCH_ASSOC);
			$updateTel = $conmysql->prepare("UPDATE gcmemberaccount SET phone_number = :phone_number WHERE member_no = :member_no");
			if($updateTel->execute([
				':phone_number' => $dataComing["tel"],
				':member_no' => $payload["member_no"]
			])){
				$logStruc = [
					":member_no" => $payload["member_no"],
					":old_data" => $rowTel["phone_number"] ?? "-",
					":new_data" => $dataComing["tel"] ?? "-",
					":data_type" => "tel",
					":id_userlogin" => $payload["id_userlogin"]
				];
				$log->writeLog('editinfo',$logStruc);
				if($arrConstInfo["tel"] == '1'){
					$updateTelOracle = $conoracle->prepare("UPDATE mbmembmaster SET addr_mobilephone = :phone_number WHERE member_no = :member_no");
					if($updateTelOracle->execute([
						':phone_number' => $dataComing["tel"],
						':member_no' => $member_no
					])){
						$conmysql->commit();
						$arrayResult['RESULT_TEL'] = TRUE;
					}else{
						$conmysql->rollback();
						$arrayResult['RESULT_TEL'] = FALSE;
					}
				}else{
					$conmysql->commit();
					$arrayResult["RESULT_TEL"] = TRUE;
				}
			}else{
				$conmysql->rollback();
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS1003",
					":error_desc" => "แก้ไขเบอร์โทรไม่ได้เพราะ update ลงตาราง gcmemberaccount ไม่ได้"."\n"."Query => ".$updateTel->queryString."\n"."Param => ". json_encode([
						':phone_number' => $dataComing["tel"],
						':member_no' => $payload["member_no"]
					]),
					":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$message_error = "แก้ไขเบอร์โทรไม่ได้เพราะ update ลง gcmemberaccount ไม่ได้"."\n"."Query => ".$updateTel->queryString."\n"."Param => ". json_encode([
					':phone_number' => $dataComing["tel"],
					':member_no' => $payload["member_no"]
				]);
				$lib->sendLineNotify($message_error);
				$arrayResult["RESULT_TEL"] = FALSE;
			}
		}
		if(isset($dataComing["address"]) && $dataComing["address"] != ""){
			if($arrConstInfo["address"] == '1'){
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$memberInfo = $conoracle->prepare("SELECT 
													mb.ADDR_NO as ADDR_NO,
													mb.ADDR_MOO as ADDR_MOO,
													mb.ADDR_SOI as ADDR_SOI,
													mb.ADDR_VILLAGE as ADDR_VILLAGE,
													mb.ADDR_ROAD as ADDR_ROAD,
													MBTR.TAMBOL_DESC AS TAMBOL_REG_DESC,
													MBDR.DISTRICT_DESC AS DISTRICT_REG_DESC,
													MB.PROVINCE_CODE AS PROVINCE_CODE,
													MBPR.PROVINCE_DESC AS PROVINCE_REG_DESC,
													MB.ADDR_POSTCODE AS ADDR_POSTCODE,
													MBTR.TAMBOL_CODE,
													MBDR.DISTRICT_CODE
													FROM mbmembmaster mb
													LEFT JOIN MBUCFTAMBOL MBTR ON mb.TAMBOL_CODE = MBTR.TAMBOL_CODE
													LEFT JOIN MBUCFDISTRICT MBDR ON mb.AMPHUR_CODE = MBDR.DISTRICT_CODE
													LEFT JOIN MBUCFPROVINCE MBPR ON mb.PROVINCE_CODE = MBPR.PROVINCE_CODE
													WHERE mb.member_no = :member_no");
				$memberInfo->execute([':member_no' => $member_no]);
				$rowMember = $memberInfo->fetch(PDO::FETCH_ASSOC);
				$arrOldAddress["addr_no"] = $rowMember["ADDR_NO"];
				$arrOldAddress["addr_moo"] = $rowMember["ADDR_MOO"];
				$arrOldAddress["addr_soi"] = $rowMember["ADDR_SOI"];
				$arrOldAddress["addr_village"] = $rowMember["ADDR_VILLAGE"];
				$arrOldAddress["addr_road"] = $rowMember["ADDR_ROAD"];
				$arrOldAddress["district_code"] = $rowMember["DISTRICT_CODE"];
				$arrOldAddress["addr_postcode"] = $rowMember["ADDR_POSTCODE"];
				$arrOldAddress["tambol_code"] = $rowMember["TAMBOL_CODE"];
				$arrOldAddress["province_code"] = $rowMember["PROVINCE_CODE"];
				$insertChangeData = $conmysql->prepare("INSERT INTO gcmembereditdata(member_no,old_data,incoming_data,inputgroup_type)
														VALUES(:member_no,:old_address,:address,'address')");
				if($insertChangeData->execute([
					':member_no' => $payload["member_no"],
					':old_address' => json_encode($arrOldAddress),
					':address' => json_encode($dataComing["address"])
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
					$message_error = "แก้ไขเบอร์โทรไม่ได้เพราะ insert ลง gcmembereditdata ไม่ได้"."\n"."Query => ".$insertChangeData->queryString."\n"."Param => ". json_encode([
						':member_no' => $payload["member_no"],
						':old_address' => json_encode($arrOldAddress),
						':address' => json_encode($dataComing["address"])
					]);
					$lib->sendLineNotify($message_error);
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
