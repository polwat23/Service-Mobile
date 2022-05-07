<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SettingMemberInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrConstInfo = array();
		$conoracle->beginTransaction();
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
			if($arrConstInfo["tel"] == '0'){
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
					$updatePhone = $conoracle->prepare("UPDATE mbmembmaster SET addr_mobilephone = :phone_number WHERE member_no = :member_no");
					if($updatePhone->execute([
						':member_no' => $member_no,
						':phone_number' => $dataComing["tel"] ?? "-"
					])){
						$log->writeLog('editinfo',$logStruc);
					}else{
						$filename = basename(__FILE__, '.php');
						$logStruc = [
							":error_menu" => $filename,
							":error_code" => "WS1003",
							":error_desc" => "แก้ไขเบอร์โทรไม่ได้เพราะ update ลงตาราง mbmembmaster ไม่ได้"."\n"."Query => ".$updateTel->queryString."\n"."Param => ". json_encode([
								':phone_number' => $dataComing["tel"],
								':member_no' => $payload["member_no"]
							]),
							":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
						];
						
						$log->writeLog('errorusage',$logStruc);
						$message_error = "แก้ไขเบอร์โทรไม่ได้เพราะ update ลง mbmembmaster ไม่ได้"."\n"."Query => ".$updateTel->queryString."\n"."Param => ". json_encode([
							':phone_number' => $dataComing["tel"],
							':member_no' => $payload["member_no"]
						]);
						$lib->sendLineNotify($message_error);
						$arrayResult["RESULT"] = FALSE;
						$conoracle->rollback();
					}			
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
				$getAddr = $conoracle->prepare("SELECT mb.CURRADDR_NO as ADDR_NO,
														mb.CURRADDR_MOO as ADDR_MOO,
														mb.CURRADDR_SOI as ADDR_SOI,
														mb.CURRADDR_VILLAGE as ADDR_VILLAGE,
														mb.CURRADDR_ROAD as ADDR_ROAD,
														MBT.TAMBOL_DESC AS TAMBOL_DESC,
														MBD.DISTRICT_DESC AS DISTRICT_DESC,
														MB.CURRPROVINCE_CODE AS PROVINCE_CODE,
														MBP.PROVINCE_DESC AS PROVINCE_DESC,
														MB.CURRADDR_POSTCODE AS ADDR_POSTCODE,
														mb.CURRAMPHUR_CODE AS DISTRICT_CODE,
														mb.CURRTAMBOL_CODE AS TAMBOL_CODE
														FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
														LEFT JOIN MBUCFMEMBGROUP mg ON mb.MEMBGROUP_CODE = mg.MEMBGROUP_CODE
														LEFT JOIN MBUCFMEMBTYPE mt ON mb.MEMBTYPE_CODE = mt.MEMBTYPE_CODE
														LEFT JOIN MBUCFTAMBOL MBT ON mb.CURRTAMBOL_CODE = MBT.TAMBOL_CODE
														LEFT JOIN MBUCFDISTRICT MBD ON mb.CURRAMPHUR_CODE = MBD.DISTRICT_CODE
														LEFT JOIN MBUCFPROVINCE MBP ON mb.CURRPROVINCE_CODE = MBP.PROVINCE_CODE
														WHERE mb.member_no = :member_no");
				$getAddr->execute([':member_no' => $member_no]);
				$rowAddr = $getAddr->fetch(PDO::FETCH_ASSOC);
				$updateDataAddress = $conoracle->prepare("UPDATE mbmembmaster SET 
															CURRADDR_NO = :addr_no,
															CURRADDR_MOO = :addr_moo,
															CURRADDR_SOI = :addr_soi,
															CURRADDR_VILLAGE = :addr_village,
															CURRADDR_ROAD = :addr_road,
															CURRPROVINCE_CODE = :province_code,
															CURRADDR_POSTCODE = :post_code,
															CURRAMPHUR_CODE = :district_code,
															CURRTAMBOL_CODE = :tambol_code
															WHERE member_no = :member_no");
				if($updateDataAddress->execute([
					':addr_no' => $dataComing["address"]["addr_no"] ?? $rowAddr["ADDR_NO"],
					':addr_moo' => $dataComing["address"]["addr_moo"] ?? $rowAddr["ADDR_MOO"],
					':addr_village' => $dataComing["address"]["addr_village"] ?? $rowAddr["ADDR_VILLAGE"],
					':addr_soi' => $dataComing["address"]["addr_soi"] ?? $rowAddr["ADDR_SOI"],
					':addr_road' => $dataComing["address"]["addr_road"] ?? $rowAddr["ADDR_ROAD"],
					':tambol_code' => $dataComing["address"]["tambol_code"] ?? $rowAddr["TAMBOL_CODE"],
					':district_code' => $dataComing["address"]["district_code"] ?? $rowAddr["DISTRICT_CODE"],
					':province_code' => $dataComing["address"]["province_code"] ?? $rowAddr["PROVINCE_CODE"],
					':post_code' => $dataComing["address"]["addr_postcode"] ?? $rowAddr["ADDR_POSTCODE"],
					':member_no' => $member_no
				])){
					$arrayResult["RESULT_ADDRESS"] = TRUE;
				}else{
					$filename = basename(__FILE__, '.php');
					$logStruc = [
						":error_menu" => $filename,
						":error_code" => "WS1003",
						":error_desc" => "แก้ไขเบอร์โทรไม่ได้เพราะ update ลงตาราง gcmemberaccount ไม่ได้"."\n"."Query => ".$updateDataAddress->queryString."\n"."Param => ". json_encode([
							':addr_no' => $dataComing["address"]["addr_no"] ?? $rowAddr["ADDR_NO"],
							':addr_moo' => $dataComing["address"]["addr_moo"] ?? $rowAddr["ADDR_MOO"],
							':addr_village' => $dataComing["address"]["addr_village"] ?? $rowAddr["ADDR_VILLAGE"],
							':addr_soi' => $dataComing["address"]["addr_soi"] ?? $rowAddr["ADDR_SOI"],
							':addr_road' => $dataComing["address"]["addr_road"] ?? $rowAddr["ADDR_ROAD"],
							':tambol_code' => $dataComing["address"]["tambol_code"] ?? $rowAddr["TAMBOL_CODE"],
							':district_code' => $dataComing["address"]["district_code"] ?? $rowAddr["DISTRICT_CODE"],
							':province_code' => $dataComing["address"]["province_code"] ?? $rowAddr["PROVINCE_CODE"],
							':post_code' => $dataComing["address"]["addr_postcode"] ?? $rowAddr["ADDR_POSTCODE"],
							':member_no' => $member_no
						]),
						":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
					];
					$log->writeLog('errorusage',$logStruc);
					$message_error = "แก้ไขเบอร์โทรไม่ได้เพราะ update ลง gcmemberaccount ไม่ได้"."\n"."Query => ".$updateDataAddress->queryString."\n"."Param => ". json_encode([
						':addr_no' => $dataComing["address"]["addr_no"] ?? $rowAddr["ADDR_NO"],
						':addr_moo' => $dataComing["address"]["addr_moo"] ?? $rowAddr["ADDR_MOO"],
						':addr_village' => $dataComing["address"]["addr_village"] ?? $rowAddr["ADDR_VILLAGE"],
						':addr_soi' => $dataComing["address"]["addr_soi"] ?? $rowAddr["ADDR_SOI"],
						':addr_road' => $dataComing["address"]["addr_road"] ?? $rowAddr["ADDR_ROAD"],
						':tambol_code' => $dataComing["address"]["tambol_code"] ?? $rowAddr["TAMBOL_CODE"],
						':district_code' => $dataComing["address"]["district_code"] ?? $rowAddr["DISTRICT_CODE"],
						':province_code' => $dataComing["address"]["province_code"] ?? $rowAddr["PROVINCE_CODE"],
						':post_code' => $dataComing["address"]["addr_postcode"] ?? $rowAddr["ADDR_POSTCODE"],
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
		$conoracle->commit();
		$arrayResult["RESULT"] = TRUE;
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