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
			if($arrConstInfo["email"] == '1'){
				$arrayResult['RESULT_EMAIL'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
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
					$arrayResult['RESULT_EMAIL'] = TRUE;
				}else{
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
		}
		if(isset($dataComing["tel"]) && $dataComing["tel"] != ""){
			if($arrConstInfo["tel"] == '1'){
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
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
					$arrayResult["RESULT_TEL"] = TRUE;
				}else{
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
		}
		if(isset($dataComing["address"]) && $dataComing["address"] != ""){
			if($arrConstInfo["address"] == '1'){
				$getAddr = $conoracle->prepare("SELECT 
													MB.CONTACT_ADDRESS||' '||(select p.district_desc from mbucfdistrict p where p.district_code = mb.other_ampher_code)||' '||(select p.province_desc from mbucfprovince p where p.province_code = mb.other_province_code)||' '||MB.other_postcode as OTHER_CONTACT_ADDRESS
												FROM WCDEPTMASTER MB
													WHERE  trim(MB.DEPTACCOUNT_NO) = :member_no");
				$getAddr->execute([':member_no' => $member_no]);
				$rowAddr = $getAddr->fetch(PDO::FETCH_ASSOC);
				$getTambol = $conoracle->prepare("select p.TAMBOL_DESC from mbucftambol p where p.tambol_code = :amphur");
				$getTambol->execute([':amphur' => $dataComing["address"]["tambol_code"]]);
				$rowTambol = $getTambol->fetch(PDO::FETCH_ASSOC);
				$arrd_no = $dataComing["address"]["addr_no"]." หมู่ ".($dataComing["address"]["addr_moo"] ?? '-')." หมู่บ้าน ".($dataComing["address"]["addr_village"] ?? '-')." ซอย ".
					($dataComing["address"]["addr_soi"] ?? '-')." ถนน ".($dataComing["address"]["addr_road"] ?? '-')." ตำบล/แขวง ".($rowTambol["TAMBOL_DESC"] ?? '-');
				$updateDataAddress = $conoracle->prepare("UPDATE WCDEPTMASTER SET 
															CONTACT_ADDRESS = :addr_no,
															other_ampher_code = :district_code,
															other_province_code = :province_code,
															postcode = :postcode
															WHERE trim(DEPTACCOUNT_NO) = :member_no");
				if($updateDataAddress->execute([
					':addr_no' => $arrd_no,
					':district_code' => $dataComing["address"]["district_code"],
					':province_code' => $dataComing["address"]["province_code"],
					':postcode' => $dataComing["address"]["addr_postcode"],
					':member_no' => $member_no
				])){
					$arrayResult["RESULT_ADDRESS"] = TRUE;
				}else{
					$filename = basename(__FILE__, '.php');
					$logStruc = [
						":error_menu" => $filename,
						":error_code" => "WS1003",
						":error_desc" => "แก้ไขเบอร์โทรไม่ได้เพราะ update ลงตาราง gcmemberaccount ไม่ได้"."\n"."Query => ".$updateDataAddress->queryString."\n"."Param => ". json_encode([
							':addr_no' => $arrd_no,
							':district_code' => $dataComing["address"]["district_code"],
							':province_code' => $dataComing["address"]["province_code"],
							':member_no' => $member_no
						]),
						":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
					];
					$log->writeLog('errorusage',$logStruc);
					$message_error = "แก้ไขเบอร์โทรไม่ได้เพราะ update ลง gcmemberaccount ไม่ได้"."\n"."Query => ".$updateDataAddress->queryString."\n"."Param => ".json_encode([
						':addr_no' => $arrd_no,
						':district_code' => $dataComing["address"]["district_code"],
						':province_code' => $dataComing["address"]["province_code"],
						':member_no' => $member_no
					]);
					$lib->sendLineNotify($message_error);
					$arrayResult["RESULT_ADDRESS"] = FALSE;
					$arrayResult["RESULT"] = FALSE;
					$conoracle->rollback();
				}
			}else{
			}
		}
		if(isset($arrayResult["RESULT_ADDRESS"]) && !$arrayResult["RESULT_ADDRESS"]){
			$arrayResult['RESPONSE_CODE'] = "WS1039";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
		
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