<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','searchmember')){
		$arrayGroupAll = array();
		$arrayGroupMg = array();
		$arrayExecute = array();
		$arrRegisterCoop = array();
		$arrayManager = array();
		$arrayChairman = array();
		$arrayBoard = array();
		$arrayBusiness = array();
		$arrayMember = array();
		$arrayOfficer = array();
		$year = date(Y) +543;
		if(isset($dataComing["member_no"]) && $dataComing["member_no"] != ''){
			$arrMember = explode(' ',$dataComing["member_no"]);
			$arrayExecute[':member_no'] = '%'.$arrMember[0].'%';
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
		if(isset($dataComing["sector"]) && $dataComing["sector"] != ''){
			$arrayExecute[':secto_id'] = $dataComing["sector"];
		}
		if(empty($dataComing["member_no"]) && empty($dataComing["member_name"]) && empty($dataComing["province"])&& empty($dataComing["sector"])){
			$arrayResult['RESPONSE'] = "ไม่สามารถค้นหาได้เนื่องจากไม่ได้ระบุค่าที่ต้องการค้นหา";
			$arrayResult['RESULT'] = FALSE;
			require_once('../../../../include/exit_footer.php');
		}
		
		/*$fetchAccount = $conmysql->prepare("SELECT member_no FROM gcmembonlineregis  where appl_status = '1'  ");
		$fetchAccount->execute();
		while($rowUser = $fetchAccount->fetch(PDO::FETCH_ASSOC)){
			$arrRegisterCoop[] = $rowUser["member_no"];
		}*/
		
		$fetchMember = $conoracle->prepare("SELECT MP.PRENAME_DESC,MB.MEMB_NAME,MB.MEMB_ENAME,MB.BIRTH_DATE,MB.ADDR_EMAIL AS EMAIL,MB.ADDR_MOBILEPHONE AS MEM_TELMOBILE,
											MB.COOPREGIS_DATE,
											MB.CLOSE_DATE,
											MB.COOPREGIS_NO,
											MB.MEMB_REGNO,
											MB.TAX_ID,
											MB.ACCYEARCLOSE_DATE,
											MB.MEMBER_DATE,
											MB.MEMBER_NO,
											MB.ADDR_NO AS ADDR_NO,
											MB.ADDR_MOO AS ADDR_MOO,
											MB.ADDR_SOI AS ADDR_SOI,
											MB.ADDR_VILLAGE AS ADDR_VILLAGE,
											MB.ADDR_ROAD AS ADDR_ROAD,
											MBT.TAMBOL_DESC AS TAMBOL_DESC,
											MBD.DISTRICT_DESC AS DISTRICT_DESC,
											MB.PROVINCE_CODE,
											MBP.PROVINCE_DESC AS PROVINCE_DESC,
											MB.ADDR_POSTCODE AS ADDR_POSTCODE
											FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
											LEFT JOIN mbucftambol MBT ON mb.tambol_code = MBT.tambol_code
											LEFT JOIN mbucfdistrict MBD ON mb.district_code = MBD.district_code
											LEFT JOIN mbucfprovince MBP ON mb.province_code = MBP.province_code
											WHERE  mb.member_status = 1 and 1=1".(isset($dataComing["member_no"]) && $dataComing["member_no"] != '' ? " and mb.member_no LIKE :member_no" : null).
											(isset($dataComing["member_name"]) && $dataComing["member_name"] != '' ? " and (TRIM(mb.memb_name) LIKE :member_name" : null).
											(isset($arrayExecute[':member_surname']) ? " and TRIM(mb.memb_ename) LIKE :member_surname)" : (isset($arrayExecute[':member_name']) ? " OR TRIM(mb.memb_ename) LIKE :member_name)" : null)).
											(isset($dataComing["province"]) && $dataComing["province"] != '' ? " and mb.province_code = :province_code" : null).
											(isset($dataComing["sector"]) && $dataComing["sector"] != '' ? " and mb.sector_id = :secto_id" : null)
											);
		$fetchMember->execute($arrayExecute);
		while($rowMember = $fetchMember->fetch(PDO::FETCH_ASSOC)){
			$arrayGroup = array();
			$arrayMg = array();
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
			$arrayGroup["TEL"] = $rowMember["MEM_TELMOBILE"] ?? "-";
			$arrayGroup["EMAIL"] = $rowMember["EMAIL"];
			$arrayGroup["MEMBER_NO"] = $rowMember["MEMBER_NO"];
			$arrayGroup["MEMBER_DATE"] = $lib->convertdate($rowMember["MEMBER_DATE"],'D m Y')  ?? "-";
			$arrayGroup["COOPREGIS_DATE"] = $lib->convertdate($rowMember["COOPREGIS_DATE"],'D m Y')  ?? "-";//จดทะเบียนเมื่อวันที่
			$arrayGroup["CLOSE_DATE"] = $lib->convertdate($rowMember["CLOSE_DATE"],'D m Y') ?? "-";//วันสิ้นปีทางบัญชี
			$arrayGroup["COOPREGIS_NO"] = $rowMember["COOPREGIS_NO"] ?? "-" ;   //ทะเบียนเลขที่
			$arrayGroup["MEMB_REGNO"] = $rowMember["MEMB_REGNO"] ?? "-";    //เลข 13 หลักของสหกรณ์ 
			$arrayGroup["TAX_ID"] = $rowMember["TAX_ID"] ?? "-";    //เลขประจำตัวผู้เสียภาษีอากร
			$arrayGroup["MANAGEMENT_UPDATE_DATE"] =  $lib->convertdate(date('Y-m-d'),"d m Y");
			//$arrRegisterMember[] = $rowMember["MEMBER_NO"];

			$mdInfo = $conoracle->prepare("SELECT  MB.BOARD_NAME as MD_NAME,  MY.MEMBERSHIP_AMT as MD_COUNT, BDRANK_CODE as MD_TYPE,MB.ADD_NO as ADDR_NO,
										MB.ADDR_MOO as ADDR_MOO,MB.ADDR_SOI as ADDR_SOI,MB.ADDR_ROAD as ADDR_ROAD,MB.ADDR_DISTRICT AS DISTRICT_CODE,MB.ADDR_TAMBOL AS TAMBOL_CODE,
										MB.ADDR_PROVINCE AS PROVINCE_CODE,MBT.TAMBOL_DESC AS TAMBOL_REG_DESC,MBD.DISTRICT_DESC AS DISTRICT_REG_DESC,MBP.PROVINCE_DESC AS PROVINCE_REG_DESC,											
										MBT.TAMBOL_DESC AS TAMBOL_DESC,MBD.DISTRICT_DESC AS DISTRICT_DESC,MBP.PROVINCE_DESC AS PROVINCE_DESC,MB.BOARD_TEL,MB.BOARD_AGE,MB.BOARD_EMAIL,MB.PERSON_ID
										FROM MBMEMBDETYEARBOARD MB LEFT JOIN MBMEMBDETYEARBIZ MY ON MB.MEMBER_NO = MY.MEMBER_NO AND MB.BIZ_YEAR  = MY.BIZ_YEAR
										LEFT JOIN MBUCFTAMBOL MBT ON MB.ADDR_TAMBOL = MBT.TAMBOL_CODE
										LEFT JOIN MBUCFDISTRICT MBD ON MB.ADDR_DISTRICT = MBD.DISTRICT_CODE
										LEFT JOIN MBUCFPROVINCE MBP ON MB.ADDR_PROVINCE = MBP.PROVINCE_CODE
										WHERE  MB.MEMBER_NO = :member_no  AND MB.BIZ_YEAR = :year");
			$mdInfo->execute([':member_no' => $rowMember["MEMBER_NO"] ,':year' =>$year]);
			while($rowUser = $mdInfo->fetch(PDO::FETCH_ASSOC)){
			$arrayMd = array();
			$address = (isset($rowUser["ADDR_NO"]) ? $rowUser["ADDR_NO"] : null);
			if(isset($rowUser["PROVINCE_CODE"]) && $rowUser["PROVINCE_CODE"] == '10'){
				$address .= (isset($rowUser["ADDR_MOO"]) ? ' ม.'.$rowUser["ADDR_MOO"] : null);
				$address .= (isset($rowUser["ADDR_SOI"]) ? ' ซอย'.$rowUser["ADDR_SOI"] : null);
				$address .= (isset($rowUser["ADDR_ROAD"]) ? ' ถนน'.$rowUser["ADDR_ROAD"] : null);
				$address .= (isset($rowUser["TAMBOL_REG_DESC"]) ? ' แขวง'.$rowUser["TAMBOL_REG_DESC"] : null);
				$address .= (isset($rowUser["DISTRICT_REG_DESC"]) ? ' เขต'.$rowUser["DISTRICT_REG_DESC"] : null);
				$address .= (isset($rowUser["PROVINCE_REG_DESC"]) ? ' '.$rowUser["PROVINCE_REG_DESC"] : null);
			}else{
				$address .= (isset($rowUser["ADDR_MOO"]) ? ' ม.'.$rowUser["ADDR_MOO"] : null);
				$address .= (isset($rowUser["ADDR_SOI"]) ? ' ซอย'.$rowUser["ADDR_SOI"] : null);
				$address .= (isset($rowUser["ADDR_ROAD"]) ? ' ถนน'.$rowUser["ADDR_ROAD"] : null);
				$address .= (isset($rowUser["TAMBOL_REG_DESC"]) ? ' ต.'.$rowUser["TAMBOL_REG_DESC"] : null);
				$address .= (isset($rowUser["DISTRICT_REG_DESC"]) ? ' อ.'.$rowUser["DISTRICT_REG_DESC"] : null);
				$address .= (isset($rowUser["PROVINCE_REG_DESC"]) ? ' จ.'.$rowUser["PROVINCE_REG_DESC"] : null);
			}
			$arrayMd["BOARD_TEL"] = $rowUser["BOARD_TEL"];
			$arrayMd["BOARD_EMAIL"] = $rowUser["BOARD_EMAIL"];
			$arrayMd["PERSON_ID"] = $rowUser["PERSON_ID"];
			$arrayMd["ADDR_NO"] = $rowUser["ADDR_NO"];
			$arrayMd["ADDR_MOO"] = $rowUser["ADDR_MOO"];
			$arrayMd["ADDR_SOI"] = $rowUser["ADDR_SOI"];
			$arrayMd["ADDR_ROAD"] = $rowUser["ADDR_ROAD"];
			$arrayMd["DISTRICT_CODE"] = $rowUser["DISTRICT_CODE"];
			$arrayMd["TAMBOL_CODE"] = $rowUser["TAMBOL_CODE"];
			$arrayMd["PROVINCE_CODE"] = $rowUser["PROVINCE_CODE"];	
			$arrayMd["ADDRESS"] = $address;			
			$arrayMd["MD_NAME"] = $rowUser["MD_NAME"];
			
			if($rowUser["MD_TYPE"] == "01"){//ประธาน
				$arrayChairman = $arrayMd;
			}else if($rowUser["MD_TYPE"] == "09"){	//ผู้จัดการ
				$arrayManager = $arrayMd;
			}else if($rowUser["MD_TYPE"] == "08"){	//คณะกรรมการ
				$arrayBoard[] = $arrayMd;
			}else if($rowUser["MD_TYPE"] == "12"){	//ผู้ตรวจสอบกิจการ
				$arrayBusiness[] = $arrayMd;
			}
				$arrayMember = $rowUser["MD_COUNT"];
				$arrayOfficer  = $rowUser["MD_COUNT"];			
		}
			$arrayGroup["MEMBER_COUNT"] = $arrayMember  ?? "-";  //จํานวนสมาชิก
			$arrayGroup["PRESIDENT"] = $arrayChairman  ?? "-";		//ประธานกรรมการ
			$arrayGroup["BOARD"] =  $arrayBoard  ?? "-";		//รายชื่อคณะกรรมการ
			$arrayGroup["BUSINESS"] = $arrayBusiness  ?? "-";		//ผู้ตรวจสอบกิจการ
			$arrayGroup["MANAGER"] = $arrayManager ?? "-" ;		//ผู้จัดการ
			$arrayGroup["COOP_OFFICER"] = $arrayOfficer  ?? "-";		//เจ้าหน้าที่สหกรณ์
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