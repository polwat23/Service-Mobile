<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','searchmember')){
		$arrayGroupAll = array();
		$arrayExecute = array();
		if(isset($dataComing["member_no"]) && $dataComing["member_no"] != ''){
			$arrayExecute[':member_no'] = strtolower($lib->mb_str_pad($dataComing["member_no"]));
		}
		if(isset($dataComing["member_name"]) && $dataComing["member_name"] != ''){
			$arrName = explode(' ',$dataComing["member_name"]);
			if(isset($arrName[1])){
				$arrayExecute[':member_name'] = '%'.$arrName[0].'%';
				$arrayExecute[':member_surname'] = '%'.$arrName[1].'%';
			}else{
				$arrayExecute[':member_name'] = '%'.$arrName[0].'%';
			}
		}
		if(isset($dataComing["province"]) && $dataComing["province"] != ''){
			$arrayExecute[':province_code'] = $dataComing["province"];
		}
		if(empty($dataComing["member_no"]) && empty($dataComing["member_name"]) && empty($dataComing["province"])){
			$arrayResult['RESPONSE'] = "ไม่สามารถค้นหาได้เนื่องจากไม่ได้ระบุค่าที่ต้องการค้นหา";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
		$fetchMember = $conoracle->prepare("SELECT mp.prename_short,mb.memb_name,mb.memb_surname,mb.birth_date,mb.email_address as email,
											mb.mem_telmobile as MEM_TELMOBILE,
											TRIM(mb.member_no) as MEMBER_NO,
											mb.MEMB_ADDR as ADDR_NO,
											mb.ADDR_GROUP as ADDR_MOO,
											mb.SOI as ADDR_SOI,
											mb.MOOBAN as ADDR_VILLAGE,
											mb.ROAD as ADDR_ROAD,
											MB.TAMBOL AS TAMBOL_DESC,
											MBD.DISTRICT_DESC AS DISTRICT_DESC,
											MB.PROVINCE_CODE AS PROVINCE_CODE,
											MBP.PROVINCE_DESC AS PROVINCE_DESC,
											MB.POSTCODE AS ADDR_POSTCODE
											FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
											LEFT JOIN mbucfdistrict MBD ON mb.DISTRICT_CODE = MBD.district_code
											LEFT JOIN mbucfprovince MBP ON mb.province_code = MBP.province_code
											WHERE mb.resign_status = '0'".(isset($dataComing["member_no"]) && $dataComing["member_no"] != '' ? " and TRIM(mb.member_no) = :member_no" : null).
											(isset($dataComing["member_name"]) && $dataComing["member_name"] != '' ? " and (TRIM(mb.memb_name) LIKE :member_name" : null).
											(isset($arrayExecute[':member_surname']) ? " and TRIM(mb.memb_surname) LIKE :member_surname)" : (isset($arrayExecute[':member_name']) ? " OR TRIM(mb.memb_surname) LIKE :member_name)" : null)).
											(isset($dataComing["province"]) && $dataComing["province"] != '' ? " and mb.province_code = :province_code" : null)
											);
		$fetchMember->execute($arrayExecute);
		while($rowMember = $fetchMember->fetch(PDO::FETCH_ASSOC)){
			$arrayGroup = array();
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
			$arrayGroup["ADDRESS"] = $address;
			$arrayGroup["BIRTH_DATE"] = $lib->convertdate($rowMember["BIRTH_DATE"],"D m Y");
			$arrayGroup["BIRTH_DATE_COUNT"] =  $lib->count_duration($rowMember["BIRTH_DATE"],"ym");
			$arrayGroup["NAME"] = $rowMember["PRENAME_DESC"].$rowMember["MEMB_NAME"]." ".$rowMember["MEMB_SURNAME"];
			$arrayGroup["TEL"] = $lib->formatphone($rowMember["MEM_TELMOBILE"],'-');
			$arrayGroup["EMAIL"] = $rowMember["EMAIL"];
			$arrayGroup["MEMBER_NO"] = $rowMember["MEMBER_NO"];
			$arrayGroup["MEMBER_DATE"] = $lib->convertdate($rowMember["MEMBER_DATE"],'D m Y');
			$arrayGroupAll[] = $arrayGroup;
		}
		$arrayResult["MEMBER_DATA"] = $arrayGroupAll;
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>