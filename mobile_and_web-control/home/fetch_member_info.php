<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],$conmysql,'MemberInfo')){
		$arrayResult = array();
		$member_no = $payload["member_no"];
		$memberInfoMobile = $conmysql->prepare("SELECT phone_number,email,path_avatar,member_no FROM gcmemberaccount WHERE member_no = :member_no");
		$memberInfoMobile->execute([':member_no' => $member_no]);
		if($memberInfoMobile->rowCount() > 0){
			$rowInfoMobile = $memberInfoMobile->fetch();
			$arrayResult["PHONE"] = $lib->formatphone($rowInfoMobile["phone_number"]);
			$arrayResult["EMAIL"] = $rowInfoMobile["email"];
			$arrayResult["AVATAR_PATH"] = $rowInfoMobile["path_avatar"];
			$explodePathAvatar = explode('.',$rowInfoMobile["path_avatar"]);
			$arrayResult["AVATAR_PATH_WEBP"] = $explodePathAvatar[0].'.webp';
			if($member_no == "dev@mode"){
				$arrayResult["PRENAME"] = "นาย";
				$arrayResult["NAME"] = "ไอโซแคร์";
				$arrayResult["SURNAME"] = "ซิสเต็มส์";
				$arrayResult["BIRTH_DATE"] = $lib->convertdate("18-08-1995","D m Y");
				$arrayResult["BIRTH_DATE_COUNT"] =  $lib->count_duration("18-08-1995","ym");
				$arrayResult["CARD_PERSON"] = $lib->formatcitizen("9999999999999");
				$arrayResult["MEMBER_DATE"] = $lib->convertdate("18-11-2017","D m Y");
				$arrayResult["MEMBER_DATE_COUNT"] = $lib->count_duration("18-11-2017","ym");
				$arrayResult["POSITION_DESC"] = "ผู้พัฒนา";
				$arrayResult["MEMBER_TYPE"] = "สมาชิกพิเศษ";
				$arrayResult["MEMBERGROUP_DESC"] = "บริษัท เจนซอฟท์ จำกัด";
				$arrayResult["FULL_ADDRESS"] = "219/14 ม.8 ถ.วงแหวนรอบกลาง ต.สันผีเสื้อ อ.เมือง จ.เชียงใหม่ 50300";
			}else if($member_no == "salemode"){
				$arrayResult["PRENAME"] = "นาย";
				$arrayResult["NAME"] = "ไอโซแคร์";
				$arrayResult["SURNAME"] = "ซิสเต็มส์";
				$arrayResult["BIRTH_DATE"] = $lib->convertdate("18-08-1995","D m Y");
				$arrayResult["BIRTH_DATE_COUNT"] =  $lib->count_duration("18-08-1995","ym");
				$arrayResult["CARD_PERSON"] = $lib->formatcitizen("9999999999999");
				$arrayResult["MEMBER_DATE"] = $lib->convertdate("18-11-2017","D m Y");
				$arrayResult["MEMBER_DATE_COUNT"] = $lib->count_duration("18-11-2017","ym");
				$arrayResult["POSITION_DESC"] = "นักต่อรองเจรจา";
				$arrayResult["MEMBER_TYPE"] = "สมาชิกพิเศษ";
				$arrayResult["MEMBERGROUP_DESC"] = "บริษัท เจนซอฟท์ จำกัด";
				$arrayResult["FULL_ADDRESS"] = "219/14 ม.8 ถ.วงแหวนรอบกลาง ต.สันผีเสื้อ อ.เมือง จ.เชียงใหม่ 50300";
			}else{
				$memberInfo = $conoracle->prepare("SELECT mp.prename_short,mb.memb_name,mb.memb_surname,mb.birth_date,mb.card_person,
													mb.member_date,mb.position_desc,mg.membgroup_desc,mt.membtype_desc,
													mb.ADDR_NO AS ADDR_NO, 
													mb.ADDR_MOO AS ADDR_MOO,
													mb.ADDR_SOI AS ADDR_SOI,
													mb.ADDR_VILLAGE AS ADDR_VILLAGE,
													mb.ADDR_ROAD AS ADDR_ROAD,
													MBT.TAMBOL_DESC AS TAMBOL_DESC,
													MBD.DISTRICT_DESC AS DISTRICT_DESC,
													MBP.PROVINCE_DESC AS PROVINCE_DESC,
													MBD.POSTCODE AS ADDR_POSTCODE
													FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
													LEFT JOIN MBUCFMEMBGROUP mg ON mb.MEMBGROUP_CODE = mg.MEMBGROUP_CODE
													LEFT JOIN MBUCFMEMBTYPE mt ON mb.MEMBTYPE_CODE = mt.MEMBTYPE_CODE
													LEFT JOIN MBUCFTAMBOL MBT ON mb.TAMBOL_CODE = MBT.TAMBOL_CODE
													LEFT JOIN MBUCFDISTRICT MBD ON mb.AMPHUR_CODE = MBD.DISTRICT_CODE
													LEFT JOIN MBUCFPROVINCE MBP ON mb.PROVINCE_CODE = MBP.PROVINCE_CODE
													WHERE mb.member_no = :member_no");
				$memberInfo->execute([':member_no' => $member_no]);
				$rowMember = $memberInfo->fetch();
				if(isset($rowMember)){
					$address = $rowMember["ADDR_NO"];
					$address .= (isset($rowMember["ADDR_MOO"]) ? ' ม.'.$rowMember["ADDR_MOO"] : null);
					$address .= (isset($rowMember["ADDR_SOI"]) ? ' ซอย'.$rowMember["ADDR_SOI"] : null);
					$address .= (isset($rowMember["ADDR_VILLAGE"]) ? ' หมู่บ้าน'.$rowMember["ADDR_VILLAGE"] : null);
					$address .= (isset($rowMember["ADDR_ROAD"]) ? ' ถนน'.$rowMember["ADDR_ROAD"] : null);
					$address .= (isset($rowMember["TAMBOL_DESC"]) ? ' ต.'.$rowMember["TAMBOL_DESC"] : null);
					$address .= (isset($rowMember["DISTRICT_DESC"]) ? ' อ.'.$rowMember["DISTRICT_DESC"] : null);
					$address .= (isset($rowMember["PROVINCE_DESC"]) ? ' จ.'.$rowMember["PROVINCE_DESC"] : null);
					$address .= (isset($rowMember["ADDR_POSTCODE"]) ? $rowMember["ADDR_POSTCODE"] : null);
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
					$arrayResult["FULL_ADDRESS"] = $address;
				}else{
					http_response_code(204);
					exit();
				}
			}
			if(isset($new_token)){
				$arrayResult['NEW_TOKEN'] = $new_token;
			}
			$arrayResult["MEMBER_NO"] = $member_no;
			$arrayResult["RESULT"] = TRUE;
			echo json_encode($arrayResult);
		}else{
			http_response_code(204);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = "Not complete argument";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>