<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','searchmember',$conoracle)){
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
			require_once('../../../../include/exit_footer.php');
			
		}
		$fetchMember = $conoracle->prepare("SELECT mp.prename_short,mb.memb_name,mb.memb_surname,mb.birth_date,mb.email as email,mb.MEM_TELMOBILE as MEM_TELMOBILE,
											mb.member_date,mb.member_no
											FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
											WHERE 1=1".(isset($dataComing["member_no"]) && $dataComing["member_no"] != '' ? " and mb.member_no = :member_no" : null).
											(isset($dataComing["member_name"]) && $dataComing["member_name"] != '' ? " and (TRIM(mb.memb_name) LIKE :member_name" : null).
											(isset($arrayExecute[':member_surname']) ? " and TRIM(mb.memb_surname) LIKE :member_surname)" : (isset($arrayExecute[':member_name']) ? " OR TRIM(mb.memb_surname) LIKE :member_name)" : null)).
											(isset($dataComing["province"]) && $dataComing["province"] != '' ? " and mb.province_code = :province_code" : null)
											);
		$fetchMember->execute($arrayExecute);
		while($rowMember = $fetchMember->fetch(PDO::FETCH_ASSOC)){
			$arrayGroup = array();
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
													FROM mbmembmaster mb 
													LEFT JOIN MBMEMBADDRESS ma ON mb.MEMBER_NO = ma.MEMBER_NO
													LEFT JOIN MBUCFTAMBOL MBT ON ma.TAMBOL_CODE = MBT.TAMBOL_CODE
													LEFT JOIN MBUCFDISTRICT MBD ON ma.DISTRICT_CODE = MBD.DISTRICT_CODE
													LEFT JOIN MBUCFPROVINCE MBP ON ma.PROVINCE_CODE = MBP.PROVINCE_CODE
													WHERE mb.member_no = :member_no");
			$memberAddress->execute([':member_no' => $rowMember["MEMBER_NO"]]);
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
					$arrayGroup["FULL_ADDRESS_REGIS"] = $address;
				}else if($rowAddress["ADDRESS_CODE"] == '02'){
					$arrayGroup["FULL_ADDRESS_DOC"] = $address;
				}else{
					$arrayGroup["FULL_ADDRESS_CURR"] = $address;
				}
			}
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
		require_once('../../../../include/exit_footer.php');
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../../include/exit_footer.php');
		
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../../include/exit_footer.php');
	
}
?>