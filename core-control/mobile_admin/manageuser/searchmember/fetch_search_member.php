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
		
		if(isset($dataComing["member_no"]) && $dataComing["member_no"] != ''){
			$arrayExecute[':member_no'] = $dataComing["member_no"];
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
		
		$fetchAccount = $conmysql->prepare("SELECT member_no FROM gcmembonlineregis  where appl_status = '1'  ");
		$fetchAccount->execute();
		while($rowUser = $fetchAccount->fetch(PDO::FETCH_ASSOC)){
			$arrRegisterCoop[] = $rowUser["member_no"];
		}
		
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
											WHERE ".(count($arrRegisterCoop) > 0 ? (" mb.member_no IN(".implode(',',$arrRegisterCoop).")") : null)."
											and 1=1".(isset($dataComing["member_no"]) && $dataComing["member_no"] != '' ? " and mb.member_no = :member_no" : null).
											(isset($dataComing["member_name"]) && $dataComing["member_name"] != '' ? " and (TRIM(mb.memb_name) LIKE :member_name" : null).
											(isset($arrayExecute[':member_surname']) ? " and TRIM(mb.memb_ename) LIKE :member_surname)" : (isset($arrayExecute[':member_name']) ? " OR TRIM(mb.memb_ename) LIKE :member_name)" : null)).
											(isset($dataComing["province"]) && $dataComing["province"] != '' ? " and mb.province_code = :province_code" : null)
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
			$arrRegisterMember[] = $rowMember["MEMBER_NO"];

			$mdInfo = $conmysql->prepare("SELECT id ,member_no, md_name, md_type ,md_count  FROM gcmanagement 
									WHERE ".(count($arrRegisterMember) > 0 ? (" member_no IN(".implode(',',$arrRegisterMember).")") : null)."");
			$mdInfo->execute();
			while($rowUser = $mdInfo->fetch(PDO::FETCH_ASSOC)){
				$arrayMd = array();
			$arrayMd["ID"] = $rowUser["id"];
			$arrayMd["MD_NAME"] = $rowUser["md_name"];
			$arrayMd["MD_COUNT"] = $rowUser["md_count"];
			$arrayMd["MD_TYPE"] = $rowUser["md_type"];
			if($rowUser["md_type"] == "0"){			//ประธาน
				$arrayChairman = $rowUser["md_name"];
			}else if($rowUser["md_type"] == "1"){	//ผู้จัดการ
				$arrayManager = $rowUser["md_name"];
			}else if($rowUser["md_type"] == "2"){	//คณะกรรมการ
				$arrayBoard[] = $arrayMd;
			}else if($rowUser["md_type"] == "3"){	//ผู้ตรวจสอบกิจการ
				$arrayBusiness[] = $arrayMd;
			}else if($rowUser["md_type"] == "4"){	//จํานวนสมาชิก  ราย
				$arrayMember = $rowUser["md_count"];
			}else if($rowUser["md_type"] == "5"){	//เจ้าหน้าที่สหกรณ์ ราย
				$arrayOfficer = $rowUser["md_count"];
			}		
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