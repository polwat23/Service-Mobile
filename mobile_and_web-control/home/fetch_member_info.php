<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'MemberInfo')){
		
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$memberInfoMobile = $conmysql->prepare("SELECT phone_number,email,path_avatar,member_no FROM gcmemberaccount WHERE member_no = :member_no");
		$memberInfoMobile->execute([':member_no' => $payload["member_no"]]);
		if($memberInfoMobile->rowCount() > 0){
			$rowInfoMobile = $memberInfoMobile->fetch(PDO::FETCH_ASSOC);
			$arrayResult["PHONE"] = $lib->formatphone($rowInfoMobile["phone_number"]);
			$arrayResult["EMAIL"] = $rowInfoMobile["email"];
			if(isset($rowInfoMobile["path_avatar"])){
				if ($forceNewSecurity == true) {
					$arrayResult['AVATAR_PATH'] = $config["URL_SERVICE"]."/resource/get_resource?id=".hash("sha256", $rowInfoMobile["path_avatar"]);
					$arrayResult["AVATAR_PATH_TOKEN"] = $lib->generate_token_access_resource($rowInfoMobile["path_avatar"], $jwt_token, $config["SECRET_KEY_JWT"]);
					
					$explodePathAvatar = explode('.',$rowInfoMobile["path_avatar"]);
					$arrayResult["AVATAR_PATH_WEBP"] = $config["URL_SERVICE"]."/resource/get_resource?id=".hash("sha256", $explodePathAvatar[0].'.webp');
					$arrayResult["AVATAR_PATH_WEBP_TOKEN"] = $lib->generate_token_access_resource($explodePathAvatar[0].'.webp', $jwt_token, $config["SECRET_KEY_JWT"]);
				} else {
					$arrayResult["AVATAR_PATH"] = $config["URL_SERVICE"].$rowInfoMobile["path_avatar"];
					$explodePathAvatar = explode('.',$rowInfoMobile["path_avatar"]);
					$arrayResult["AVATAR_PATH_WEBP"] = $config["URL_SERVICE"].$explodePathAvatar[0].'.webp';
				}
			}else{
				$arrayResult["AVATAR_PATH"] = null;
				$arrayResult["AVATAR_PATH_WEBP"] = null;
			}
			$memberInfo = $conoracle->prepare("SELECT mp.prename_short,mb.memb_name,mb.memb_surname,mb.birth_date,mb.card_person,
													mb.member_date,mpos.position_desc,mg.membgroup_desc,mt.membtype_desc,TRIM(mg.membgroup_code) as membgroup_code,
													mb.ADDRESS_NO as ADDR_NO, mb.ADDRESS_SOI as ADDR_SOI,mb.ADDRESS_MOO as ADDR_MOO,mb.ADDRESS_ROAD AS ADDR_ROAD,
													MB.ADDRESS_VILLAGE as ADDR_VILLAGE,
													mb.PROVINCE_CODE,
													MBT.TAMBOL_DESC AS TAMBOL_DESC,
													MBD.DISTRICT_DESC AS DISTRICT_DESC,
													MBP.PROVINCE_DESC AS PROVINCE_DESC,
													MBD.POSTCODE AS ADDR_POSTCODE
													FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
													LEFT JOIN MBUCFMEMBGROUP mg ON mb.MEMBGROUP_CODE = mg.MEMBGROUP_CODE
													LEFT JOIN MBUCFMEMBTYPE mt ON mb.MEMBTYPE_CODE = mt.MEMBTYPE_CODE
													LEFT JOIN MBUCFTAMBOL MBT ON mb.TAMBOL_CODE = MBT.TAMBOL_CODE
													LEFT JOIN MBUCFPOSITION MPOS ON mb.POSITION_CODE = MPOS.POSITION_CODE
													LEFT JOIN MBUCFDISTRICT MBD ON mb.DISTRICT_CODE = MBD.DISTRICT_CODE
													LEFT JOIN MBUCFPROVINCE MBP ON mb.PROVINCE_CODE = MBP.PROVINCE_CODE
													WHERE mb.member_no = :member_no");
			$memberInfo->execute([':member_no' => $member_no]);
			$rowMember = $memberInfo->fetch(PDO::FETCH_ASSOC);
			if(isset($rowMember["MEMB_NAME"]) && $rowMember["MEMB_NAME"] != ""){
				$address = (isset($rowMember["ADDR_NO"]) ? $rowMember["ADDR_NO"] : null);
				if(isset($rowMember["PROVINCE_CODE"]) && $rowMember["PROVINCE_CODE"] == '10'){
					$address .= (isset($rowMember["ADDR_MOO"]) ? ' ม.'.$rowMember["ADDR_MOO"] : null);
					$address .= (isset($rowMember["ADDR_SOI"]) ? ' ซอย'.$rowMember["ADDR_SOI"] : null);
					$address .= (isset($rowMember["ADDR_VILLAGE"]) ? ' หมู่บ้าน'.$rowMember["ADDR_VILLAGE"] : null);
					$address .= (isset($rowMember["ADDR_ROAD"]) ? ' ถนน'.$rowMember["ADDR_ROAD"] : null);
					$address .= (isset($rowMember["TAMBOL_DESC"]) ? ' แขวง'.$rowMember["TAMBOL_DESC"] : null);
					$address .= (isset($rowMember["DISTRICT_DESC"]) ? ' เขต'.$rowMember["DISTRICT_DESC"] : null);
					$address .= (isset($rowMember["PROVINCE_DESC"]) ? ' '.$rowMember["PROVINCE_DESC"] : null);
					$address .= (isset($rowMember["ADDR_POSTCODE"]) ? ' '.$rowMember["ADDR_POSTCODE"] : null);
				}else{
					$address .= (isset($rowMember["ADDR_MOO"]) ? ' ม.'.$rowMember["ADDR_MOO"] : null);
					$address .= (isset($rowMember["ADDR_SOI"]) ? ' ซอย'.$rowMember["ADDR_SOI"] : null);
					$address .= (isset($rowMember["ADDR_VILLAGE"]) ? ' หมู่บ้าน'.$rowMember["ADDR_VILLAGE"] : null);
					$address .= (isset($rowMember["ADDR_ROAD"]) ? ' ถนน'.$rowMember["ADDR_ROAD"] : null);
					$address .= (isset($rowMember["TAMBOL_DESC"]) ? ' ต.'.$rowMember["TAMBOL_DESC"] : null);
					$address .= (isset($rowMember["DISTRICT_DESC"]) ? ' อ.'.$rowMember["DISTRICT_DESC"] : null);
					$address .= (isset($rowMember["PROVINCE_DESC"]) ? ' จ.'.$rowMember["PROVINCE_DESC"] : null);
					$address .= (isset($rowMember["ADDR_POSTCODE"]) ? ' '.$rowMember["ADDR_POSTCODE"] : null);
				}
				
				$arrayResult["PRENAME"] = $rowMember["PRENAME_SHORT"];
				$arrayResult["NAME"] = $rowMember["MEMB_NAME"];
				$arrayResult["SURNAME"] = $rowMember["MEMB_SURNAME"];
				$arrayResult["BIRTH_DATE"] = $lib->convertdate($rowMember["BIRTH_DATE"],"D m Y");
				$arrayResult["BIRTH_DATE_COUNT"] =  $lib->count_duration($rowMember["BIRTH_DATE"],"ym");
				$arrayResult["CARD_PERSON"] = $lib->formatcitizen($rowMember["CARD_PERSON"]);
				$arrayResult["MEMBER_DATE"] = $lib->convertdate($rowMember["MEMBER_DATE"],"D m Y");
				$arrayResult["MEMBER_DATE_COUNT"] = $lib->count_duration($rowMember["MEMBER_DATE"],"ym");
				$arrayResult["POSITION_DESC"] = $rowMember["POSITION_DESC"];
				$arrayResult["MEMBER_TYPE"] = $rowMember["MEMBTYPE_DESC"];
				$arrayResult["MEMBERGROUP_DESC"] = $rowMember["MEMBGROUP_DESC"];
				$arrayResult["FULL_ADDRESS_CURR"] = $address;
				$arrayResult["MEMBER_NO"] = $member_no;
				$arrayResult["IGNORE_INFO"] = ["CARD_PERSON","MEMBERGROUP_DESC","BIRTH_DATE","POSITION_DESC"];
				$arrayResult["RECEIVE_MONEY_FROM"] = $rowMember["MEMBGROUP_DESC"]." (".$rowMember["MEMBGROUP_CODE"].")";
				$arrayResult["RESULT"] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$getInfoMembTemp = $conoracle->prepare("SELECT mp.prename_short,mb.memb_name,mb.memb_surname,mb.birth_date,mb.card_person,
														mpos.position_desc,mg.membgroup_desc,mt.membtype_desc,TRIM(mg.membgroup_code) as membgroup_code,
														mb.ADDRESS_NO as ADDR_NO, mb.ADDRESS_SOI as ADDR_SOI,mb.ADDRESS_MOO as ADDR_MOO,mb.ADDRESS_ROAD AS ADDR_ROAD,
														mb.PROVINCE_CODE,
														MBT.TAMBOL_DESC AS TAMBOL_DESC,
														MBD.DISTRICT_DESC AS DISTRICT_DESC,
														MBP.PROVINCE_DESC AS PROVINCE_DESC,
														MBD.POSTCODE AS ADDR_POSTCODE
														FROM MBREQAPPL mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
														LEFT JOIN MBUCFMEMBGROUP mg ON mb.MEMBGROUP_CODE = mg.MEMBGROUP_CODE
														LEFT JOIN MBUCFMEMBTYPE mt ON mb.MEMBTYPE_CODE = mt.MEMBTYPE_CODE
														LEFT JOIN MBUCFTAMBOL MBT ON mb.TAMBOL_CODE = MBT.TAMBOL_CODE
														LEFT JOIN MBUCFPOSITION MPOS ON mb.POSITION_CODE = MPOS.POSITION_CODE
														LEFT JOIN MBUCFDISTRICT MBD ON mb.DISTRICT_CODE = MBD.DISTRICT_CODE
														LEFT JOIN MBUCFPROVINCE MBP ON mb.PROVINCE_CODE = MBP.PROVINCE_CODE
														WHERE mb.member_no = :member_no");
				$getInfoMembTemp->execute([':member_no' => $member_no]);
				$rowInfoTemp = $getInfoMembTemp->fetch(PDO::FETCH_ASSOC);
				$address = (isset($rowInfoTemp["ADDR_NO"]) ? $rowInfoTemp["ADDR_NO"] : null);
				if(isset($rowInfoTemp["PROVINCE_CODE"]) && $rowInfoTemp["PROVINCE_CODE"] == '10'){
					$address .= (isset($rowInfoTemp["ADDR_MOO"]) ? ' ม.'.$rowInfoTemp["ADDR_MOO"] : null);
					$address .= (isset($rowInfoTemp["ADDR_SOI"]) ? ' ซอย'.$rowInfoTemp["ADDR_SOI"] : null);
					$address .= (isset($rowInfoTemp["ADDR_VILLAGE"]) ? ' หมู่บ้าน'.$rowInfoTemp["ADDR_VILLAGE"] : null);
					$address .= (isset($rowInfoTemp["ADDR_ROAD"]) ? ' ถนน'.$rowInfoTemp["ADDR_ROAD"] : null);
					$address .= (isset($rowInfoTemp["TAMBOL_DESC"]) ? ' แขวง'.$rowInfoTemp["TAMBOL_DESC"] : null);
					$address .= (isset($rowInfoTemp["DISTRICT_DESC"]) ? ' เขต'.$rowInfoTemp["DISTRICT_DESC"] : null);
					$address .= (isset($rowInfoTemp["PROVINCE_DESC"]) ? ' '.$rowInfoTemp["PROVINCE_DESC"] : null);
					$address .= (isset($rowInfoTemp["ADDR_POSTCODE"]) ? ' '.$rowInfoTemp["ADDR_POSTCODE"] : null);
				}else{
					$address .= (isset($rowInfoTemp["ADDR_MOO"]) ? ' ม.'.$rowInfoTemp["ADDR_MOO"] : null);
					$address .= (isset($rowInfoTemp["ADDR_SOI"]) ? ' ซอย'.$rowInfoTemp["ADDR_SOI"] : null);
					$address .= (isset($rowInfoTemp["ADDR_VILLAGE"]) ? ' หมู่บ้าน'.$rowInfoTemp["ADDR_VILLAGE"] : null);
					$address .= (isset($rowInfoTemp["ADDR_ROAD"]) ? ' ถนน'.$rowInfoTemp["ADDR_ROAD"] : null);
					$address .= (isset($rowInfoTemp["TAMBOL_DESC"]) ? ' ต.'.$rowInfoTemp["TAMBOL_DESC"] : null);
					$address .= (isset($rowInfoTemp["DISTRICT_DESC"]) ? ' อ.'.$rowInfoTemp["DISTRICT_DESC"] : null);
					$address .= (isset($rowInfoTemp["PROVINCE_DESC"]) ? ' จ.'.$rowInfoTemp["PROVINCE_DESC"] : null);
					$address .= (isset($rowInfoTemp["ADDR_POSTCODE"]) ? ' '.$rowInfoTemp["ADDR_POSTCODE"] : null);
				}
				
				$arrayResult["PRENAME"] = $rowInfoTemp["PRENAME_SHORT"];
				$arrayResult["NAME"] = $rowInfoTemp["MEMB_NAME"];
				$arrayResult["SURNAME"] = $rowInfoTemp["MEMB_SURNAME"];
				$arrayResult["BIRTH_DATE"] = $lib->convertdate($rowInfoTemp["BIRTH_DATE"],"D m Y");
				$arrayResult["BIRTH_DATE_COUNT"] =  $lib->count_duration($rowInfoTemp["BIRTH_DATE"],"ym");
				$arrayResult["CARD_PERSON"] = $lib->formatcitizen($rowInfoTemp["CARD_PERSON"]);
				$arrayResult["MEMBER_DATE"] = $lib->convertdate($rowInfoTemp["MEMBER_DATE"],"D m Y");
				$arrayResult["MEMBER_DATE_COUNT"] = $lib->count_duration($rowInfoTemp["MEMBER_DATE"],"ym");
				$arrayResult["POSITION_DESC"] = $rowInfoTemp["POSITION_DESC"];
				$arrayResult["MEMBER_TYPE"] = $rowInfoTemp["MEMBTYPE_DESC"];
				$arrayResult["MEMBERGROUP_DESC"] = $rowInfoTemp["MEMBGROUP_DESC"];
				$arrayResult["FULL_ADDRESS_CURR"] = $address;
				$arrayResult["MEMBER_NO"] = $member_no;
				$arrayResult["IGNORE_INFO"] = ["CARD_PERSON","MEMBERGROUP_DESC","BIRTH_DATE","POSITION_DESC"];
				$arrayResult["RECEIVE_MONEY_FROM"] = $rowInfoTemp["MEMBGROUP_DESC"]." (".$rowInfoTemp["MEMBGROUP_CODE"].")";
				$arrayResult["RESULT"] = TRUE;
				require_once('../../include/exit_footer.php');
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0003";
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