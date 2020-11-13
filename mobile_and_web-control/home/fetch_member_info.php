<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'MemberInfo')){
		$arrayResult = array();
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$memberInfoMobile = $conmysql->prepare("SELECT email,path_avatar,member_no FROM gcmemberaccount WHERE member_no = :member_no");
		$memberInfoMobile->execute([':member_no' => $payload["member_no"]]);
		if($memberInfoMobile->rowCount() > 0){
			$rowInfoMobile = $memberInfoMobile->fetch(PDO::FETCH_ASSOC);
			$arrayResult["EMAIL"] = $rowInfoMobile["email"];
			if(isset($rowInfoMobile["path_avatar"])){
				$arrayResult["AVATAR_PATH"] = $config["URL_SERVICE"].$rowInfoMobile["path_avatar"];
				$explodePathAvatar = explode('.',$rowInfoMobile["path_avatar"]);
				$arrayResult["AVATAR_PATH_WEBP"] = $config["URL_SERVICE"].$explodePathAvatar[0].'.webp';
			}else{
				$arrayResult["AVATAR_PATH"] = null;
				$arrayResult["AVATAR_PATH_WEBP"] = null;
			}
			$memberInfo = $conoracle->prepare("SELECT mp.prename_short,mb.memb_name,mb.memb_surname,mb.birth_date,mb.card_person,
												mb.member_date,mpos.position_desc,mg.membgroup_desc,mt.membtype_desc,mb.mem_telmobile,
												mb.ADDRESS_NO as ADDR_NO,mb.ADDRESS_MOO as ADDR_MOO,mb.ADDRESS_ROAD as ADDR_ROAD,
												mb.ADDRESS_SOI as ADDR_SOI,mb.ADDRESS_VILLAGE as ADDR_VILLAGE,
												mbt.TAMBOL_DESC,mbd.DISTRICT_DESC,mbp.PROVINCE_DESC,mb.POSTCODE as ADDR_POSTCODE,mb.PROVINCE_CODE
												FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
												LEFT JOIN MBUCFPOSITION mpos ON mb.position_code = mpos.position_code
												LEFT JOIN MBUCFMEMBGROUP mg ON mb.MEMBGROUP_CODE = mg.MEMBGROUP_CODE
												LEFT JOIN MBUCFMEMBTYPE mt ON mb.MEMBTYPE_CODE = mt.MEMBTYPE_CODE
												LEFT JOIN MBUCFTAMBOL MBT ON mb.TAMBOL_CODE = MBT.TAMBOL_CODE
												LEFT JOIN MBUCFDISTRICT MBD ON mb.DISTRICT_CODE = MBD.DISTRICT_CODE
												LEFT JOIN MBUCFPROVINCE MBP ON mb.PROVINCE_CODE = MBP.PROVINCE_CODE
												WHERE mb.member_no = :member_no");
			$memberInfo->execute([':member_no' => $member_no]);
			$rowMember = $memberInfo->fetch(PDO::FETCH_ASSOC);
			$address = $rowMember["ADDR_NO"];
			if(isset($rowMember["PROVINCE_CODE"]) && $rowMember["PROVINCE_CODE"] == '10'){
				$address .= (isset($rowMember["ADDR_MOO"]) ? ' ม.'.$rowMember["ADDR_MOO"] : null);
				$address .= (isset($rowMember["ADDR_SOI"]) ? ' ซอย'.$rowMember["ADDR_SOI"] : null);
				$address .= (isset($rowMember["ADDR_VILLAGE"]) ? ' หมู่บ้าน'.$rowMember["ADDR_VILLAGE"] : null);
				$address .= (isset($rowMember["ADDR_ROAD"]) ? ' ถนน'.$rowMember["ADDR_ROAD"] : null);
				$address .= (isset($rowMember["TAMBOL_DESC"]) ? ' แขวง '.$rowMember["TAMBOL_DESC"] : null);
				$address .= (isset($rowMember["DISTRICT_DESC"]) ? ' เขต '.$rowMember["DISTRICT_DESC"] : null);
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
			$arrayResult["FULL_ADDRESS_CURR"] = $address;
			$memberAddress = $conoracle->prepare("SELECT 
													ma.ADDRESS_CODE AS ADDRESS_CODE,
													ma.ADDRESS_NO AS ADDR_NO, 
													ma.ADDRESS_MOO AS ADDR_MOO,
													ma.ADDRESS_SOI AS ADDR_SOI,
													ma.ADDRESS_VILLAGE AS ADDR_VILLAGE,
													ma.ADDRESS_ROAD AS ADDR_ROAD,
													MBT.TAMBOL_DESC AS TAMBOL_DESC,
													MBD.DISTRICT_DESC AS DISTRICT_DESC,
													MBP.PROVINCE_DESC AS PROVINCE_DESC,
													ma.PROVINCE_CODE AS PROVINCE_CODE,
													ma.POSTCODE AS ADDR_POSTCODE
													FROM MBMEMBADDRESS ma LEFT JOIN mbmembmaster mb ON mb.MEMBER_NO = ma.MEMBER_NO
													LEFT JOIN MBUCFTAMBOL MBT ON ma.TAMBOL_CODE = MBT.TAMBOL_CODE
													LEFT JOIN MBUCFDISTRICT MBD ON ma.DISTRICT_CODE = MBD.DISTRICT_CODE
													LEFT JOIN MBUCFPROVINCE MBP ON ma.PROVINCE_CODE = MBP.PROVINCE_CODE
													WHERE mb.member_no = :member_no");
			$memberAddress->execute([':member_no' => $member_no]);
			while($rowAddress = $memberAddress->fetch(PDO::FETCH_ASSOC)){
				$address = $rowAddress["ADDR_NO"];
				if(isset($rowAddress["PROVINCE_CODE"]) && $rowAddress["PROVINCE_CODE"] == '10'){
					$address .= (isset($rowAddress["ADDR_MOO"]) ? ' ม.'.$rowAddress["ADDR_MOO"] : null);
					$address .= (isset($rowAddress["ADDR_SOI"]) ? ' ซอย'.$rowAddress["ADDR_SOI"] : null);
					$address .= (isset($rowAddress["ADDR_VILLAGE"]) ? ' หมู่บ้าน'.$rowAddress["ADDR_VILLAGE"] : null);
					$address .= (isset($rowAddress["ADDR_ROAD"]) ? ' ถนน'.$rowAddress["ADDR_ROAD"] : null);
					$address .= (isset($rowAddress["TAMBOL_DESC"]) ? ' แขวง '.$rowAddress["TAMBOL_DESC"] : null);
					$address .= (isset($rowAddress["DISTRICT_DESC"]) ? ' เขต '.$rowAddress["DISTRICT_DESC"] : null);
					$address .= (isset($rowAddress["PROVINCE_DESC"]) ? ' '.$rowAddress["PROVINCE_DESC"] : null);
					$address .= (isset($rowAddress["ADDR_POSTCODE"]) ? ' '.$rowAddress["ADDR_POSTCODE"] : null);
				}else{
					$address .= (isset($rowAddress["ADDR_MOO"]) ? ' ม.'.$rowAddress["ADDR_MOO"] : null);
					$address .= (isset($rowAddress["ADDR_SOI"]) ? ' ซอย'.$rowAddress["ADDR_SOI"] : null);
					$address .= (isset($rowAddress["ADDR_VILLAGE"]) ? ' หมู่บ้าน'.$rowAddress["ADDR_VILLAGE"] : null);
					$address .= (isset($rowAddress["ADDR_ROAD"]) ? ' ถนน'.$rowAddress["ADDR_ROAD"] : null);
					$address .= (isset($rowAddress["TAMBOL_DESC"]) ? ' ต.'.$rowAddress["TAMBOL_DESC"] : null);
					$address .= (isset($rowAddress["DISTRICT_DESC"]) ? ' อ.'.$rowAddress["DISTRICT_DESC"] : null);
					$address .= (isset($rowAddress["PROVINCE_DESC"]) ? ' จ.'.$rowAddress["PROVINCE_DESC"] : null);
					$address .= (isset($rowAddress["ADDR_POSTCODE"]) ? ' '.$rowAddress["ADDR_POSTCODE"] : null);
				}
				if($rowAddress["ADDRESS_CODE"] == '01'){
					$arrayResult["FULL_ADDRESS_REGIS"] = $address;
				}else if($rowAddress["ADDRESS_CODE"] == '02'){
					$arrayResult["FULL_ADDRESS_DOC"] = $address;
				}else{
					$arrayResult["FULL_ADDRESS_CURR"] = $address;
				}
			}
			$getSignature = $conoracle->prepare("SELECT fm.base64_img,fmt.mimetypes,fm.data_type FROM fomimagemaster fm LEFT JOIN fomucfmimetype fmt ON fm.data_type = fmt.typefile
												where fm.system_code = 'mbshr' and fm.column_name = 'member_no' 
												and fm.column_data = :member_no and fm.img_type_code = '002' and rownum <= 1 ORDER BY fm.seq_no DESC");
			$getSignature->execute([':member_no' => $member_no]);
			$rowSignature = $getSignature->fetch(PDO::FETCH_ASSOC);
			$DataURLBase64 = isset($rowSignature["BASE64_IMG"]) ? "data:".$rowSignature["MIMETYPES"].";base64,".base64_encode(stream_get_contents($rowSignature["BASE64_IMG"])) : null;
			if(isset($DataURLBase64) && $DataURLBase64 != ''){
				$arrayResult['DATA_TYPE'] = $rowSignature["DATA_TYPE"] ?? 'pdf';
				$arrayResult['SIGNATURE'] = $DataURLBase64;
			}
			$arrayResult["PHONE"] = $lib->formatphone($rowMember["MEM_TELMOBILE"]);
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
			$arrayResult["MEMBER_NO"] = $member_no;
			$arrayResult["RESULT"] = TRUE;
			echo json_encode($arrayResult);

		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0003";
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