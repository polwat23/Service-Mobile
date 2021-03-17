<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'MemberInfo')){
		$arrayResult = array();
		//$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$memberInfoMobile = $conmysql->prepare("SELECT phone_number,email,path_avatar,member_no FROM gcmemberaccount WHERE member_no = :member_no");
		$memberInfoMobile->execute([':member_no' => $payload["ref_memno"]]);
		if($memberInfoMobile->rowCount() > 0){
			$rowInfoMobile = $memberInfoMobile->fetch(PDO::FETCH_ASSOC);
			$arrayResult["PHONE"] = $lib->formatphone($rowInfoMobile["phone_number"]);
			$arrayResult["EMAIL"] = $rowInfoMobile["email"];
			if(isset($rowInfoMobile["path_avatar"])){
				$arrayResult["AVATAR_PATH"] = $config["URL_SERVICE"].$rowInfoMobile["path_avatar"];
				$explodePathAvatar = explode('.',$rowInfoMobile["path_avatar"]);
				$arrayResult["AVATAR_PATH_WEBP"] = $config["URL_SERVICE"].$explodePathAvatar[0].'.webp';
			}else{
				$arrayResult["AVATAR_PATH"] = null;
				$arrayResult["AVATAR_PATH_WEBP"] = null;
			}
					
			$memberInfo = $conoracle->prepare("SELECT mb.memb_name,mb.memb_ename,mb.birth_date,'' as card_person,
													mb.member_date,'' as position_desc,mg.membgroup_desc,mt.membtype_desc,mb.coopregis_date,mb.coopregis_no,
													mb.memb_regno,mb.tax_id,mb.accyearclose_date,
													mb.ADDR_NO as ADDR_REG_NO,
													mb.ADDR_MOO as ADDR_REG_MOO,
													mb.ADDR_SOI as ADDR_REG_SOI,
													mb.ADDR_VILLAGE as ADDR_REG_VILLAGE,
													mb.ADDR_ROAD as ADDR_REG_ROAD,
													mb.ADDR_EMAIL as ADDR_REG_EMAIL,
													mb.ADDR_MOBILEPHONE as ADDR_REG_MOBILEPHONE,
													MBT.TAMBOL_DESC AS TAMBOL_REG_DESC,
													MBD.DISTRICT_DESC AS DISTRICT_REG_DESC,
													MB.PROVINCE_CODE AS PROVINCE_REG_CODE,
													MBP.PROVINCE_DESC AS PROVINCE_REG_DESC,
													MB.ADDR_POSTCODE AS ADDR_REG_POSTCODE,													
													MBT.TAMBOL_DESC AS TAMBOL_DESC,
													MBD.DISTRICT_DESC AS DISTRICT_DESC,										
													MBP.PROVINCE_DESC AS PROVINCE_DESC,
													'' AS ADDR_POSTCODE,
													'' as ADDR_NO,
													'' as ADDR_MOO,
													'' as ADDR_SOI,
													'' as ADDR_VILLAGE,
													'' as ADDR_ROAD,
													'' AS PROVINCE_CODE
													FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
													LEFT JOIN MBUCFMEMBGROUP mg ON mb.MEMBGROUP_CODE = mg.MEMBGROUP_CODE
													LEFT JOIN MBUCFMEMBTYPE mt ON mb.MEMBTYPE_CODE = mt.MEMBTYPE_CODE
													LEFT JOIN MBUCFTAMBOL MBT ON mb.TAMBOL_CODE = MBT.TAMBOL_CODE
													LEFT JOIN MBUCFDISTRICT MBD ON mb.DISTRICT_CODE = MBD.DISTRICT_CODE
													LEFT JOIN MBUCFPROVINCE MBP ON mb.PROVINCE_CODE = MBP.PROVINCE_CODE
													WHERE mb.member_no = :member_no");
			$memberInfo->execute([':member_no' => $payload["ref_memno"]]);
			$rowMember = $memberInfo->fetch(PDO::FETCH_ASSOC);
			
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
			$arrayResult["PRENAME"] = $rowMember["PRENAME_SHORT"];
			$arrayResult["NAME"] = $rowMember["MEMB_NAME"];
			$arrayResult["SURNAME"] = $rowMember["MEMB_SURNAME"];
			$arrayResult["ADDR_REG_MOBILEPHONE"] = $rowMember["ADDR_REG_MOBILEPHONE"];  //โทรศัพท์
			$arrayResult["ADDR_REG_EMAIL"] = $rowMember["ADDR_REG_EMAIL"];  // E-Mail 
			$arrayResult["COOPREGIS_DATE"] = $rowMember["COOPREGIS_DATE"];  //จดทะเบียนเมื่อวันที่
			$arrayResult["COOPREGIS_NO"] = $rowMember["COOPREGIS_NO"];  //ทะเบียนเลขที่
			$arrayResult["MEMB_REGNO"] = $rowMember["MEMB_REGNO"];  //เลข 13 หลักของสหกรณ์ 
			$arrayResult["TAX_ID"] = $rowMember["TAX_ID"];  //เลขประจำตัวผู้เสียภาษีอากร
			$arrayResult["ACCYEARCLOSE_DATE"] = $rowMember["ACCYEARCLOSE_DATE"];  //วันสิ้นปีทางบัญชี
			$arrayResult["BIRTH_DATE"] = $lib->convertdate($rowMember["BIRTH_DATE"],"D m Y");
			$arrayResult["BIRTH_DATE_COUNT"] =  $lib->count_duration($rowMember["BIRTH_DATE"],"ym");
			$arrayResult["CARD_PERSON"] = $lib->formatcitizen($rowMember["CARD_PERSON"]);
			$arrayResult["MEMBER_DATE"] = $lib->convertdate($rowMember["MEMBER_DATE"],"D m Y");
			$arrayResult["MEMBER_DATE_COUNT"] = $lib->count_duration($rowMember["MEMBER_DATE"],"ym");
			$arrayResult["POSITION_DESC"] = $rowMember["POSITION_DESC"];
			$arrayResult["MEMBER_TYPE"] = $rowMember["MEMBTYPE_DESC"];
			$arrayResult["MEMBERGROUP_DESC"] = $rowMember["MEMBGROUP_DESC"];
			$arrayResult["FULL_ADDRESS_CURR"] = $address;
			$arrayResult["FULL_ADDRESS_REG"] = $addressReg;
			$arrayResult["MEMBER_NO"] = $payload["ref_memno"];
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