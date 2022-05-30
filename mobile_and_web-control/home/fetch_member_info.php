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
			$memberInfo = $conoracle->prepare("SELECT mp.PTITLE_NAME as PRENAME_SHORT,MB.FNAME as MEMB_NAME,MB.LNAME as MEMB_SURNAME
													,mb.DMY_BIRTH as BIRTH_DATE,mb.id_card as CARD_PERSON,
													mb.MEM_DATE as member_date,
													mb.ADDRESS as ADDR_NO,
													mb.MOO_ADDR as ADDR_MOO,
													mb.SOI as ADDR_SOI,
													mb.TANON as ADDR_ROAD,
													mb.TUMBOL AS TAMBOL_DESC,
													MD.DISTRICT_NAME AS DISTRICT_DESC,
													MB.PROVINCE_ID AS PROVINCE_CODE,
													MPO.PROVINCE_NAME AS PROVINCE_DESC,
													MB.ZIP_CODE AS ADDR_POSTCODE
													FROM 
													MEM_H_MEMBER MB LEFT JOIN MEM_M_PTITLE MP ON mb.ptitle_id = mp.ptitle_id
													LEFT JOIN MEM_M_DISTRICT MD ON MB.DISTRICT_ID = MD.DISTRICT_ID AND MB.PROVINCE_ID = MD.PROVINCE_ID
													LEFT JOIN MEM_M_PROVINCE MPO ON MB.PROVINCE_ID = MPO.PROVINCE_ID
													WHERE mb.account_id = :member_no");
			$memberInfo->execute([':member_no' => $member_no]);
			$rowMember = $memberInfo->fetch(PDO::FETCH_ASSOC);
			$address = (isset($rowMember["ADDR_NO"]) ? $rowMember["ADDR_NO"] : null);
			if(isset($rowMember["PROVINCE_CODE"]) && $rowMember["PROVINCE_CODE"] == '10'){
				$address .= (isset($rowMember["ADDR_MOO"]) ? ' ม.'.$rowMember["ADDR_MOO"] : null);
				$address .= (isset($rowMember["ADDR_SOI"]) ? ' ซอย'.$rowMember["ADDR_SOI"] : null);
				$address .= (isset($rowMember["ADDR_ROAD"]) ? ' ถนน'.$rowMember["ADDR_ROAD"] : null);
				$address .= (isset($rowMember["TAMBOL_DESC"]) ? ' แขวง'.$rowMember["TAMBOL_DESC"] : null);
				$address .= (isset($rowMember["DISTRICT_DESC"]) ? ' เขต'.$rowMember["DISTRICT_DESC"] : null);
				$address .= (isset($rowMember["PROVINCE_DESC"]) ? ' '.$rowMember["PROVINCE_DESC"] : null);
				$address .= (isset($rowMember["ADDR_POSTCODE"]) ? ' '.$rowMember["ADDR_POSTCODE"] : null);
			}else{
				$address .= (isset($rowMember["ADDR_MOO"]) ? ' ม.'.$rowMember["ADDR_MOO"] : null);
				$address .= (isset($rowMember["ADDR_SOI"]) ? ' ซอย'.$rowMember["ADDR_SOI"] : null);
				$address .= (isset($rowMember["ADDR_ROAD"]) ? ' ถนน'.$rowMember["ADDR_ROAD"] : null);
				$address .= (isset($rowMember["TAMBOL_DESC"]) ? ' ต.'.$rowMember["TAMBOL_DESC"] : null);
				$address .= (isset($rowMember["DISTRICT_DESC"]) ? ' อ.'.$rowMember["DISTRICT_DESC"] : null);
				$address .= (isset($rowMember["PROVINCE_DESC"]) ? ' จ.'.$rowMember["PROVINCE_DESC"] : null);
				$address .= (isset($rowMember["ADDR_POSTCODE"]) ? ' '.$rowMember["ADDR_POSTCODE"] : null);
			}
			
			$memberInfoView = $conoracle->prepare("SELECT ADDRESS_MAIL,MOO_ADDR_MAIL,TANON_MAIL,SOI_MAIL,TUMBOL_MAIL,DISTRICT_NAME,PROVINCE_NAME,
										PROVINCE_ID,ZIPCODE_MAIL 
										FROM VIEW_MEMBER_FULL 
										WHERE ACCOUNT_ID = :member_no");
			$memberInfoView->execute([':member_no' => $member_no]);
			$rowMemberView = $memberInfoView->fetch(PDO::FETCH_ASSOC);
			$addressDoc = (isset($rowMemberView["ADDRESS_MAIL"]) ? $rowMemberView["ADDRESS_MAIL"] : null);
			if(isset($rowMemberView["PROVINCE_ID"]) && $rowMemberView["PROVINCE_ID"] == '10'){
				$addressDoc .= (isset($rowMemberView["MOO_ADDR_MAIL"]) ? ' ม.'.$rowMemberView["MOO_ADDR_MAIL"] : null);
				$addressDoc .= (isset($rowMemberView["SOI_MAIL"]) ? ' ซอย'.$rowMemberView["SOI_MAIL"] : null);
				$addressDoc .= (isset($rowMemberView["TANON_MAIL"]) ? ' ถนน'.$rowMemberView["TANON_MAIL"] : null);
				$addressDoc .= (isset($rowMemberView["TUMBOL_MAIL"]) ? ' แขวง'.$rowMemberView["TUMBOL_MAIL"] : null);
				$addressDoc .= (isset($rowMemberView["DISTRICT_NAME"]) ? ' เขต'.$rowMemberView["DISTRICT_NAME"] : null);
				$addressDoc .= (isset($rowMemberView["PROVINCE_NAME"]) ? ' '.$rowMemberView["PROVINCE_NAME"] : null);
				$addressDoc .= (isset($rowMemberView["ZIPCODE_MAIL"]) ? ' '.$rowMemberView["ZIPCODE_MAIL"] : null);
			}else{
				$addressDoc .= (isset($rowMemberView["MOO_ADDR_MAIL"]) ? ' ม.'.$rowMemberView["MOO_ADDR_MAIL"] : null);
				$addressDoc .= (isset($rowMemberView["SOI_MAIL"]) ? ' ซอย'.$rowMemberView["SOI_MAIL"] : null);
				$addressDoc .= (isset($rowMemberView["TANON_MAIL"]) ? ' ถนน'.$rowMemberView["TANON_MAIL"] : null);
				$addressDoc .= (isset($rowMemberView["TUMBOL_MAIL"]) ? ' ต.'.$rowMemberView["TUMBOL_MAIL"] : null);
				$addressDoc .= (isset($rowMemberView["DISTRICT_NAME"]) ? ' อ.'.$rowMemberView["DISTRICT_NAME"] : null);
				$addressDoc .= (isset($rowMemberView["PROVINCE_NAME"]) ? ' จ.'.$rowMemberView["PROVINCE_NAME"] : null);
				$addressDoc .= (isset($rowMemberView["ZIPCODE_MAIL"]) ? ' '.$rowMemberView["ZIPCODE_MAIL"] : null);
			}
			
			$memberSalary = $conoracle->prepare("select LINEPOST_NAME,SALARY from VIEW_MEM_OFFICE where MEM_ID = :mem_id and br_no = :br_no");
			$memberSalary->execute([
				':br_no' => substr($member_no,0,3),
				':mem_id' => substr($member_no,-5,5),
			]);
			$rowMemberSalary = $memberSalary->fetch(PDO::FETCH_ASSOC);
			
			
			$arrayResult["PRENAME"] = $rowMember["PRENAME_SHORT"];
			$arrayResult["NAME"] = $rowMember["MEMB_NAME"];
			$arrayResult["SURNAME"] = $rowMember["MEMB_SURNAME"];
			$arrayResult["BIRTH_DATE"] = $lib->convertdate($rowMember["BIRTH_DATE"],"D m Y");
			$arrayResult["BIRTH_DATE_COUNT"] =  $lib->count_duration($rowMember["BIRTH_DATE"],"ym");
			$arrayResult["CARD_PERSON"] = $lib->formatcitizen($rowMember["CARD_PERSON"]);
			$arrayResult["MEMBER_DATE"] = $lib->convertdate($rowMember["MEMBER_DATE"],"D m Y");
			$arrayResult["MEMBER_DATE_COUNT"] = $lib->count_duration($rowMember["MEMBER_DATE"],"ym");
			$arrayResult["FULL_ADDRESS_CURR"] = $address;
			$arrayResult["FULL_ADDRESS_DOC"] = $addressDoc;
			$arrayResult["MEMBER_NO"] = $member_no;
			$arrayResult["OCCUPATION"] = $rowMemberSalary["LINEPOST_NAME"];
			$arrayResult["SALARY"] = $rowMemberSalary["SALARY"] ? number_format($rowMemberSalary["SALARY"], 2) : "-";
			$arrayResult["RESULT"] = TRUE;
			require_once('../../include/exit_footer.php');
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