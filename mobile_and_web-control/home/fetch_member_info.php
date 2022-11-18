<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'MemberInfo')){
		$arrayResult = array();
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
			$memberInfo = $conoracle->prepare("SELECT 
													MB.WFACCOUNT_NAME AS WFACCOUNT_NAME,
													MB.WFBIRTHDAY_DATE AS BIRTH_DATE,
													trim(MB.CARD_PERSON) AS CARD_PERSON,
													MB.PHONE AS ADDR_PHONE,
													MB.DEPTOPEN_DATE AS MEMBER_DATE,
													MB.CARREER AS POSITION_DESC,
													MBG1.COOPBRANCH_DESC  AS COORDINATION_CENTER,
													MBT.WCMEMBERTYPE_DESC AS MEMBTYPE_DESC,
													MB.MATE_NAME as MATE_NAME,
													MB.DIE_DATE as DIE_DATE,
													MB.MANAGE_CORPSE_NAME as MANAGE_CORPSE_NAME,
													MB.CONTACT_ADDRESS||' อำเภอ/เขต '||(select p.district_desc from mbucfdistrict p where p.district_code = mb.other_ampher_code)||' จังหวัด '||(select p.province_desc from mbucfprovince p where p.province_code = mb.other_province_code)||' '||MB.other_postcode as OTHER_CONTACT_ADDRESS,
													trim(MB.MEMBER_NO) as MEMBER_NO_COOP,
													trim(FMT.FUNDACCOUNT_NO) as FUND_NO,
													FMT.FUNDOPEN_DATE AS FUND_DATE,
													CASE FMT.FUNDCLOSE_STATUS WHEN 0 THEN 'ปกติ' WHEN -9 THEN 'ยกเลิก' ELSE RES_F.RESIGNCAUSE_DESC  END as FUND_STATUS,
													trim(FR.ROUND_REGIS) AS FUND_ROUND
												FROM WCDEPTMASTER MB
													JOIN CMUCFCOOPBRANCH MBG1 ON ( MB.COOP_ID = MBG1.COOP_ID ) 
													LEFT JOIN WCMEMBERTYPE MBT ON ( MB.WFTYPE_CODE = MBT.WFTYPE_CODE  )  
													LEFT JOIN WCUCFRESIGNCAUSE RES ON ( MB.RESIGNCAUSE_CODE = RES.RESIGNCAUSE_CODE )
													LEFT JOIN WCFUNDMASTER FMT ON (FMT.DEPTACCOUNT_NO = MB.DEPTACCOUNT_NO AND FMT.COOP_ID = MB.COOP_ID
													AND  (FMT.FUNDCLOSE_STATUS = 0 or FMT.RESIGNCAUSE_CODE = '02' ))
													LEFT JOIN WCUCFFUNDROUND FR on (FR.fundopen_date = FMT.fundopen_date)
													LEFT JOIN  MBUCFRESIGNCAUSE RES_F on (FMT.resigncause_code = RES_F.resigncause_code)
													WHERE  ( MBG1.COOP_CONTROL ='060000'  )  
														AND MB.DEPTCLOSE_STATUS <> -9
														AND trim(MB.DEPTACCOUNT_NO) = :member_no");
			$memberInfo->execute([':member_no' => $member_no]);
			$rowMember = $memberInfo->fetch(PDO::FETCH_ASSOC);
			/*
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
			$addressReg = (isset($rowMember["ADDR_REG_NO"]) ? $rowMember["ADDR_REG_NO"] : null);
			if(isset($rowMember["PROVINCE_REG_CODE"]) && $rowMember["PROVINCE_REG_CODE"] == '10'){
				$addressReg .= (isset($rowMember["ADDR_REG_MOO"]) ? ' ม.'.$rowMember["ADDR_REG_MOO"] : null);
				$addressReg .= (isset($rowMember["ADDR_REG_SOI"]) ? ' ซอย'.$rowMember["ADDR_REG_SOI"] : null);
				$addressReg .= (isset($rowMember["ADDR_REG_VILLAGE"]) ? ' หมู่บ้าน'.$rowMember["ADDR_REG_VILLAGE"] : null);
				$addressReg .= (isset($rowMember["ADDR_REG_ROAD"]) ? ' ถนน'.$rowMember["ADDR_REG_ROAD"] : null);
				$addressReg .= (isset($rowMember["TAMBOL_REG_DESC"]) ? ' แขวง'.$rowMember["TAMBOL_REG_DESC"] : null);
				$addressReg .= (isset($rowMember["DISTRICT_REG_DESC"]) ? ' เขต'.$rowMember["DISTRICT_REG_DESC"] : null);
				$addressReg .= (isset($rowMember["PROVINCE_REG_DESC"]) ? ' '.$rowMember["PROVINCE_REG_DESC"] : null);
				$addressReg .= (isset($rowMember["ADDR_REG_POSTCODE"]) ? ' '.$rowMember["ADDR_REG_POSTCODE"] : null);
			}else{
				$addressReg .= (isset($rowMember["ADDR_REG_MOO"]) ? ' ม.'.$rowMember["ADDR_REG_MOO"] : null);
				$addressReg .= (isset($rowMember["ADDR_REG_SOI"]) ? ' ซอย'.$rowMember["ADDR_REG_SOI"] : null);
				$addressReg .= (isset($rowMember["ADDR_REG_VILLAGE"]) ? ' หมู่บ้าน'.$rowMember["ADDR_REG_VILLAGE"] : null);
				$addressReg .= (isset($rowMember["ADDR_REG_ROAD"]) ? ' ถนน'.$rowMember["ADDR_REG_ROAD"] : null);
				$addressReg .= (isset($rowMember["TAMBOL_REG_DESC"]) ? ' ต.'.$rowMember["TAMBOL_REG_DESC"] : null);
				$addressReg .= (isset($rowMember["DISTRICT_REG_DESC"]) ? ' อ.'.$rowMember["DISTRICT_REG_DESC"] : null);
				$addressReg .= (isset($rowMember["PROVINCE_REG_DESC"]) ? ' จ.'.$rowMember["PROVINCE_REG_DESC"] : null);
				$addressReg .= (isset($rowMember["ADDR_REG_POSTCODE"]) ? ' '.$rowMember["ADDR_REG_POSTCODE"] : null);
			}
			*/
			//$arrayResult["PRENAME"] = $rowMember["PRENAME_SHORT"];
			$arrayResult["NAME"] = $rowMember["WFACCOUNT_NAME"];
			$arrayResult["SURNAME"] = "";
			$arrayResult["BIRTH_DATE"] = $lib->convertdate($rowMember["BIRTH_DATE"],"D m Y");
			$arrayResult["BIRTH_DATE_COUNT"] =  $lib->count_duration($rowMember["BIRTH_DATE"],"ym");
			$arrayResult["CARD_PERSON"] = $lib->formatcitizen($rowMember["CARD_PERSON"]);
			$arrayResult["MEMBER_DATE"] = $lib->convertdate($rowMember["MEMBER_DATE"],"D m Y");
			$arrayResult["MEMBER_DATE_COUNT"] = $lib->count_duration($rowMember["MEMBER_DATE"],"ym");
			// $arrayResult["POSITION_DESC"] = $rowMember["POSITION_DESC"];
			$arrayResult["MEMBER_TYPE"] = $rowMember["MEMBTYPE_DESC"];
			// $arrayResult["MEMBERGROUP_DESC"] = $rowMember["MEMBGROUP_DESC"];
			$arrayResult["MATE_NAME"] = $rowMember["MATE_NAME"];
			$arrayResult["DIE_DATE"] = $rowMember["DIE_DATE"];
			$arrayResult["MANAGE_CORPSE_NAME"] = $rowMember["MANAGE_CORPSE_NAME"];
			$arrayResult["FUND_NO"] = $rowMember["FUND_NO"];
			$arrayResult["FUND_DATE"] = $rowMember["FUND_DATE"] ? $lib->convertdate($rowMember["FUND_DATE"],"D m Y") : null;
			$arrayResult["FUND_DATE_COUNT"] = $lib->count_duration($rowMember["FUND_DATE"],"ym");
			$arrayResult["FUND_STATUS"] = $rowMember["FUND_STATUS"];
			$arrayResult["FUND_ROUND"] = $rowMember["FUND_ROUND"];
			$arrayResult["COORDINATION_CENTER"] = $rowMember["COORDINATION_CENTER"];
			$arrayResult["MEMBER_NO_COOP"] = $rowMember["MEMBER_NO_COOP"];
			$arrayResult["FULL_ADDRESS_CURR"] = $rowMember["OTHER_CONTACT_ADDRESS"];
			// $arrayResult["FULL_ADDRESS_REG"] = $addressReg;
			$arrayResult["MEMBER_NO"] = $member_no;
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