<?php
if($lineLib->checkBindAccount($user_id)){
	$data = $lineLib->getMemberNo($user_id);
	$themeColor = $lineLib->getLineConstant('theme_color');
	$member_no = $configAS[$data] ?? $data;
	$meberAccountData = array();
	$memberInfoMobile = $conmysql->prepare("SELECT phone_number,email,path_avatar,member_no FROM gcmemberaccount WHERE member_no = :member_no");
	$memberInfoMobile->execute([':member_no' => $data]);
	if($memberInfoMobile->rowCount() > 0){
		$rowInfoMobile = $memberInfoMobile->fetch(PDO::FETCH_ASSOC);
		$meberAccountData["PHONE"] = $lib->formatphone($rowInfoMobile["phone_number"]);
		$meberAccountData["EMAIL"] = $rowInfoMobile["email"];
		if(isset($rowInfoMobile["path_avatar"])){
			$meberAccountData["AVATAR_PATH"] = $config["URL_SERVICE"].$rowInfoMobile["path_avatar"];
			$explodePathAvatar = explode('.',$rowInfoMobile["path_avatar"]);
			$meberAccountData["AVATAR_PATH_WEBP"] = $config["URL_SERVICE"].$explodePathAvatar[0].'.webp';
		}else{
			$meberAccountData["AVATAR_PATH"] = null;
			$meberAccountData["AVATAR_PATH_WEBP"] = null;
		}
		$memberInfo = $conoracle->prepare("select mp.prename_desc AS prename_short,mb.memb_name,mb.memb_surname,mb.birth_date,mb.card_person,
												mb.member_date,mps.position_desc,mt.membtype_desc,
												mb.ADDR_NO as ADDR_NO,
												mb.ADDR_MOO as ADDR_MOO,
												mb.ADDR_SOI as ADDR_SOI, 
												mb.ADDR_VILLAGE as ADDR_VILLAGE,
												mb.ADDR_ROAD as ADDR_ROAD,
												MBT.TAMBOL_DESC AS TAMBOL_DESC,
												MBD.DISTRICT_DESC AS DISTRICT_DESC,
												MB.PROVINCE_CODE AS PROVINCE_CODE,
												MBP.PROVINCE_DESC AS PROVINCE_DESC,
												MB.ADDR_POSTCODE AS ADDR_POSTCODE,
												mbg.membgroup_desc as MEMBGROUP_DESC
												from mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
												LEFT JOIN mbucfposition mps ON mb.position_code = mps.position_code
												LEFT JOIN mbucfmembgroup mbg ON mb.membgroup_code = mbg.membgroup_code
												LEFT JOIN MBUCFMEMBTYPE mt ON mb.MEMBTYPE_CODE = mt.MEMBTYPE_CODE
												LEFT JOIN MBUCFTAMBOL MBT ON mb.TAMBOL_CODE = MBT.TAMBOL_CODE
												LEFT JOIN MBUCFDISTRICT MBD ON mb.amphur_code = MBD.DISTRICT_CODE
												LEFT JOIN MBUCFPROVINCE MBP ON mb.PROVINCE_CODE = MBP.PROVINCE_CODE
												WHERE mb.member_no = :member_no");
		$memberInfo->execute([':member_no' => $member_no]);
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
		$meberAccountData["PRENAME"] = $rowMember["PRENAME_SHORT"];
		$meberAccountData["NAME"] = $rowMember["MEMB_NAME"];
		$meberAccountData["SURNAME"] = $rowMember["MEMB_SURNAME"];
		$meberAccountData["BIRTH_DATE"] = $lib->convertdate($rowMember["BIRTH_DATE"],"D m Y");
		$meberAccountData["BIRTH_DATE_COUNT"] =  $lib->count_duration($rowMember["BIRTH_DATE"],"ym");
		$meberAccountData["CARD_PERSON"] = $lib->formatcitizen($rowMember["CARD_PERSON"]);
		$meberAccountData["MEMBER_DATE"] = $lib->convertdate($rowMember["MEMBER_DATE"],"D m Y");
		$meberAccountData["MEMBER_DATE_COUNT"] = $lib->count_duration($rowMember["MEMBER_DATE"],"ym");
		$meberAccountData["POSITION_DESC"] = $rowMember["POSITION_DESC"];
		$meberAccountData["MEMBER_TYPE"] = $rowMember["MEMBTYPE_DESC"];
		$meberAccountData["MEMBERGROUP_DESC"] = $rowMember["MEMBGROUP_DESC"];
		$meberAccountData["FULL_ADDRESS_CURR"] = $address ==""?"-":$address;
		$meberAccountData["MEMBER_NO"] = $member_no;

	}else{
		$meberAccountData['RESPONSE_MESSAGE'] = "ไม่พบข้อมูลส่วนตัว";	
	}
	if($memberInfoMobile->rowCount() > 0){
		$prename = ($meberAccountData["PRENAME"]??"-");
		$name  = ($meberAccountData["NAME"]??"-")." ".($meberAccountData["SURNAME"]??"-");
		$meberInforData = array();
		$meberInforData["type"] = "flex";
		$meberInforData["altText"] = "ข้อมูลส่วนตัว";
		$meberInforData["contents"]["type"] = "bubble";
		$meberInforData["contents"]["size"] = "mega";
		$meberInforData["contents"]["direction"] = "ltr";
		$meberInforData["contents"]["body"]["type"] = "box";
		$meberInforData["contents"]["body"]["layout"] = "vertical";
		$meberInforData["contents"]["body"]["spacing"] = "xs";
		$meberInforData["contents"]["body"]["contents"][0]["type"] = "box";
		$meberInforData["contents"]["body"]["contents"][0]["layout"] = "vertical";
		$meberInforData["contents"]["body"]["contents"][0]["width"] = "50%";
		$meberInforData["contents"]["body"]["contents"][0]["offsetStart"] = "25%";
		$meberInforData["contents"]["body"]["contents"][0]["cornerRadius"] = "100px";
		$meberInforData["contents"]["body"]["contents"][0]["contents"][0]["type"] = "image";
		$meberInforData["contents"]["body"]["contents"][0]["contents"][0]["url"] = ($meberAccountData["AVATAR_PATH"]??"https://cdn.thaicoop.co/icon/avatar.png");
		$meberInforData["contents"]["body"]["contents"][0]["contents"][0]["size"] = "full";
		$meberInforData["contents"]["body"]["contents"][0]["contents"][0]["aspectMode"] = "cover";
		$meberInforData["contents"]["body"]["contents"][1]["type"] = "text";
		$meberInforData["contents"]["body"]["contents"][1]["text"] = $prename." ".$name;
		$meberInforData["contents"]["body"]["contents"][1]["weight"] = "bold";
		$meberInforData["contents"]["body"]["contents"][1]["size"] = "sm";
		$meberInforData["contents"]["body"]["contents"][1]["align"] = "center";
		$meberInforData["contents"]["body"]["contents"][2]["type"] = "text";
		$meberInforData["contents"]["body"]["contents"][2]["text"] = "ประเภท";
		$meberInforData["contents"]["body"]["contents"][2]["size"] = "sm";
		$meberInforData["contents"]["body"]["contents"][2]["align"] = "center";
		$meberInforData["contents"]["body"]["contents"][2]["contents"][0]["type"] = "span";
		$meberInforData["contents"]["body"]["contents"][2]["contents"][0]["text"] = ($meberAccountData["MEMBER_NO"]??"-");
		$meberInforData["contents"]["body"]["contents"][2]["contents"][1]["type"] = "span";
		$meberInforData["contents"]["body"]["contents"][2]["contents"][1]["text"] = "(".($meberAccountData["MEMBER_TYPE"]??"-").")";
		$meberInforData["contents"]["body"]["contents"][3]["type"] = "separator";
		$meberInforData["contents"]["body"]["contents"][3]["margin"] = "md";
		$meberInforData["contents"]["body"]["contents"][4]["type"] = "text";
		$meberInforData["contents"]["body"]["contents"][4]["text"] = "ข้อมูลทั่วไป";
		$meberInforData["contents"]["body"]["contents"][4]["weight"] = "bold";
		$meberInforData["contents"]["body"]["contents"][4]["margin"] = "md";
		$meberInforData["contents"]["body"]["contents"][4]["size"] = "xs";
		$meberInforData["contents"]["body"]["contents"][4]["color"] = ($themeColor??"#000000");
		$meberInforData["contents"]["body"]["contents"][5]["type"] = "text";
		$meberInforData["contents"]["body"]["contents"][5]["text"] = "หมายเลขบัตรประจำตัวประชาชน";
		$meberInforData["contents"]["body"]["contents"][5]["size"] = "xs";
		$meberInforData["contents"]["body"]["contents"][5]["color"] = "#AAAAAA";
		$meberInforData["contents"]["body"]["contents"][6]["type"] = "text";
		$meberInforData["contents"]["body"]["contents"][6]["text"] = ($meberAccountData["CARD_PERSON"]??"-");
		$meberInforData["contents"]["body"]["contents"][6]["size"] = "xs";
		$meberInforData["contents"]["body"]["contents"][6]["color"] = "#000000";
		$meberInforData["contents"]["body"]["contents"][6]["align"] = "end";
		$meberInforData["contents"]["body"]["contents"][6]["offsetEnd"] = "0px";
		$meberInforData["contents"]["body"]["contents"][7]["type"] = "text";
		$meberInforData["contents"]["body"]["contents"][7]["text"] = "วันเกิด";
		$meberInforData["contents"]["body"]["contents"][7]["size"] = "xs";
		$meberInforData["contents"]["body"]["contents"][7]["color"] = "#AAAAAA";
		$meberInforData["contents"]["body"]["contents"][8]["type"] = "text";
		$meberInforData["contents"]["body"]["contents"][8]["text"] = ($meberAccountData["BIRTH_DATE"]??"-")."(".($meberAccountData["BIRTH_DATE_COUNT"]??"-").")";
		$meberInforData["contents"]["body"]["contents"][8]["size"] = "xs";
		$meberInforData["contents"]["body"]["contents"][8]["color"] = "#000000";
		$meberInforData["contents"]["body"]["contents"][8]["align"] = "end";
		$meberInforData["contents"]["body"]["contents"][9]["type"] = "text";
		$meberInforData["contents"]["body"]["contents"][9]["text"] = " วันที่เป็นสมาชิก";
		$meberInforData["contents"]["body"]["contents"][9]["size"] = "xs";
		$meberInforData["contents"]["body"]["contents"][9]["color"] = "#AAAAAA";
		$meberInforData["contents"]["body"]["contents"][10]["type"] = "text";
		$meberInforData["contents"]["body"]["contents"][10]["text"] = ($meberAccountData["MEMBER_DATE"]??"-")."(".($meberAccountData["MEMBER_DATE_COUNT"]??"-").")";
		$meberInforData["contents"]["body"]["contents"][10]["size"] = "xs";
		$meberInforData["contents"]["body"]["contents"][10]["color"] = "#000000";
		$meberInforData["contents"]["body"]["contents"][10]["align"] = "end";
		$meberInforData["contents"]["body"]["contents"][10]["wrap"] = true;
		$meberInforData["contents"]["body"]["contents"][11]["type"] = "text";
		$meberInforData["contents"]["body"]["contents"][11]["text"] = "ตำแหน่ง";
		$meberInforData["contents"]["body"]["contents"][11]["size"] = "xs";
		$meberInforData["contents"]["body"]["contents"][11]["color"] = "#AAAAAA";
		$meberInforData["contents"]["body"]["contents"][12]["type"] = "text";
		$meberInforData["contents"]["body"]["contents"][12]["text"] = ($meberAccountData["POSITION_DESC"]??"-");
		$meberInforData["contents"]["body"]["contents"][12]["size"] = "xs";
		$meberInforData["contents"]["body"]["contents"][12]["color"] = "#000000";
		$meberInforData["contents"]["body"]["contents"][12]["align"] = "end";
		$meberInforData["contents"]["body"]["contents"][13]["type"] = "text";
		$meberInforData["contents"]["body"]["contents"][13]["text"] = "สังกัด";
		$meberInforData["contents"]["body"]["contents"][13]["size"] = "xs";
		$meberInforData["contents"]["body"]["contents"][13]["color"] = "#AAAAAA";
		$meberInforData["contents"]["body"]["contents"][14]["type"] = "box";
		$meberInforData["contents"]["body"]["contents"][14]["layout"] = "vertical";
		$meberInforData["contents"]["body"]["contents"][14]["paddingStart"] = "20px";
		$meberInforData["contents"]["body"]["contents"][14]["contents"][0]["type"] = "text";
		$meberInforData["contents"]["body"]["contents"][14]["contents"][0]["text"] = ($meberAccountData["MEMBERGROUP_DESC"]??"-");
		$meberInforData["contents"]["body"]["contents"][14]["contents"][0]["size"] = "xs";
		$meberInforData["contents"]["body"]["contents"][14]["contents"][0]["color"] = "#000000";
		$meberInforData["contents"]["body"]["contents"][14]["contents"][0]["align"] = "end";
		$meberInforData["contents"]["body"]["contents"][14]["contents"][0]["wrap"] = true;
		$meberInforData["contents"]["body"]["contents"][15]["type"] = "separator";
		$meberInforData["contents"]["body"]["contents"][15]["margin"] = "md";
		$meberInforData["contents"]["body"]["contents"][16]["type"] = "text";
		$meberInforData["contents"]["body"]["contents"][16]["text"] = "ข้อมูลติดต่อ";
		$meberInforData["contents"]["body"]["contents"][16]["weight"] = "bold";
		$meberInforData["contents"]["body"]["contents"][16]["margin"] = "md";
		$meberInforData["contents"]["body"]["contents"][16]["size"] = "xs";
		$meberInforData["contents"]["body"]["contents"][16]["color"] = ($themeColor??"#000000");
		$meberInforData["contents"]["body"]["contents"][17]["type"] = "text";
		$meberInforData["contents"]["body"]["contents"][17]["text"] = "หมายเลขโทรศัพท์"; 
		$meberInforData["contents"]["body"]["contents"][17]["size"] = "xs";
		$meberInforData["contents"]["body"]["contents"][17]["color"] = "#AAAAAA";
		$meberInforData["contents"]["body"]["contents"][18]["type"] = "text";
		$meberInforData["contents"]["body"]["contents"][18]["text"] = ($meberAccountData["PHONE"]??"-");
		$meberInforData["contents"]["body"]["contents"][18]["size"] = "xs";
		$meberInforData["contents"]["body"]["contents"][18]["color"] = "#000000";
		$meberInforData["contents"]["body"]["contents"][18]["align"] = "end";
		$meberInforData["contents"]["body"]["contents"][19]["type"] = "text";
		$meberInforData["contents"]["body"]["contents"][19]["text"] = "อีเมล";
		$meberInforData["contents"]["body"]["contents"][19]["size"] = "xs";
		$meberInforData["contents"]["body"]["contents"][19]["color"] = "#AAAAAA";
		$meberInforData["contents"]["body"]["contents"][20]["type"] = "text";
		$meberInforData["contents"]["body"]["contents"][20]["text"] = ($meberAccountData["EMAIL"]??"-");
		$meberInforData["contents"]["body"]["contents"][20]["size"] = "xs";
		$meberInforData["contents"]["body"]["contents"][20]["color"] = "#000000";
		$meberInforData["contents"]["body"]["contents"][20]["align"] = "end";
		$meberInforData["contents"]["body"]["contents"][21]["type"] = "text";
		$meberInforData["contents"]["body"]["contents"][21]["text"] = "ที่อยู่ปัจจุบัน";
		$meberInforData["contents"]["body"]["contents"][21]["size"] = "xs";
		$meberInforData["contents"]["body"]["contents"][21]["color"] = "#AAAAAA";
		$meberInforData["contents"]["body"]["contents"][22]["type"] = "box";
		$meberInforData["contents"]["body"]["contents"][22]["layout"] = "vertical";
		$meberInforData["contents"]["body"]["contents"][22]["paddingStart"] = "20px";
		$meberInforData["contents"]["body"]["contents"][22]["contents"][0]["type"] = "text";
		$meberInforData["contents"]["body"]["contents"][22]["contents"][0]["text"] = ($meberAccountData["FULL_ADDRESS_CURR"]??"-");
		$meberInforData["contents"]["body"]["contents"][22]["contents"][0]["size"] = "xs";
		$meberInforData["contents"]["body"]["contents"][22]["contents"][0]["color"] = "#000000";
		$meberInforData["contents"]["body"]["contents"][22]["contents"][0]["wrap"] = true;
		$arrPostData["messages"][0] = $meberInforData;
		$arrPostData["replyToken"] = $reply_token; 
	}
}else{
	$messageResponse = "ท่านยังไม่ได้ผูกบัญชี กรุณาผูกบัญชีเพื่อดูข้อมูล";
	$dataPrepare = $lineLib->prepareMessageText($messageResponse);
	$arrPostData["messages"] = $dataPrepare;
	$arrPostData["replyToken"] = $reply_token;
}
?>