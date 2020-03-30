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
		if(isset($dataComing["age_start"]) && $dataComing["age_start"] != ''){
			$arrayExecute[':age_start'] = $dataComing["age_start"];
		}
		if(isset($dataComing["age_end"]) && $dataComing["age_end"] != ''){
			$arrayExecute[':age_end'] = $dataComing["age_end"];
		}
		if(empty($dataComing["member_no"]) && empty($dataComing["member_name"]) && empty($dataComing["province"]) && empty($dataComing["age_start"]) && empty($dataComing["age_end"])){
			$arrayResult['RESPONSE'] = "ไม่สามารถค้นหาได้เนื่องจากไม่ได้ระบุค่าที่ต้องการค้นหา";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
		$fetchMember = $conoracle->prepare("SELECT mp.prename_desc,mb.memb_name,mb.memb_surname,mb.mem_telmobile,mb.email,mb.member_no,
											mb.member_date,
											mb.ADDRESS_NO AS ADDR_NO, 
											mb.ADDRESS_MOO AS ADDR_MOO,
											mb.ADDRESS_SOI AS ADDR_SOI,
											mb.ADDRESS_VILLAGE AS ADDR_VILLAGE,
											mb.ADDRESS_ROAD AS ADDR_ROAD,
											MBT.TAMBOL_DESC AS TAMBOL_DESC,
											MBD.DISTRICT_DESC AS DISTRICT_DESC,
											MBP.PROVINCE_DESC AS PROVINCE_DESC,
											mb.POSTCODE AS ADDR_POSTCODE,
											mb.birth_date as BIRTH_DATE
											FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
											LEFT JOIN mbucftambol MBT ON mb.tambol_code = MBT.tambol_code
											LEFT JOIN mbucfdistrict MBD ON mb.district_code = MBD.district_code
											LEFT JOIN mbucfprovince MBP ON mb.province_code = MBP.province_code
											WHERE 1=1".(isset($dataComing["member_no"]) && $dataComing["member_no"] != '' ? " and mb.member_no = :member_no" : null).
											(isset($dataComing["member_name"]) && $dataComing["member_name"] != '' ? " and mb.memb_name LIKE :member_name" : null).
											(isset($arrayExecute[':member_surname']) ? " and mb.memb_surname LIKE :member_surname" : null).
											(isset($dataComing["province"]) && $dataComing["province"] != '' ? " and mb.province_code = :province_code" : null).
											(isset($dataComing["age_start"]) && $dataComing["age_start"] != '' ? " and ft_calage(mb.birth_date,sysdate,4) >= :age_start" : null).
											(isset($dataComing["age_end"]) && $dataComing["age_end"] != '' ? " and ft_calage(mb.birth_date,sysdate,4) <= :age_end" : null)
											);
		$fetchMember->execute($arrayExecute);
		while($rowMember = $fetchMember->fetch(PDO::FETCH_ASSOC)){
			$arrayGroup = array();
			$address = $rowMember["ADDR_NO"];
			$address .= (isset($rowMember["ADDR_MOO"]) ? '  ม.'.$rowMember["ADDR_MOO"] : null);
			$address .= (isset($rowMember["ADDR_SOI"]) ? '  ซอย'.$rowMember["ADDR_SOI"] : null);
			$address .= (isset($rowMember["ADDR_VILLAGE"]) ? '  หมู่บ้าน'.$rowMember["ADDR_VILLAGE"] : null);
			$address .= (isset($rowMember["ADDR_ROAD"]) ? '  ถนน'.$rowMember["ADDR_ROAD"] : null);
			$address .= (isset($rowMember["TAMBOL_DESC"]) ? '  ต.'.$rowMember["TAMBOL_DESC"] : null);
			$address .= (isset($rowMember["DISTRICT_DESC"]) ? '  อ.'.$rowMember["DISTRICT_DESC"] : null);
			$address .= (isset($rowMember["PROVINCE_DESC"]) ? '  จ.'.$rowMember["PROVINCE_DESC"] : null);
			$address .= (isset($rowMember["ADDR_POSTCODE"]) ? ' '.$rowMember["ADDR_POSTCODE"] : null);
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