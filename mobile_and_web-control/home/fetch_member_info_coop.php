<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'CoopInfo')){
		$arrayConst = array();
		$arrayManager = array();
		$arrayChairman = array();
		$arrayBoard = array();
		$arrayBusiness = array();
		$arrayMember = array();
		$arrayOfficer = array();
		
		$mdInfo = $conmysql->prepare("SELECT id ,member_no, md_name, md_type ,md_count  FROM gcmanagement WHERE board_status = '1' AND member_no  = :member_no");
		$mdInfo->execute([':member_no' => $payload["ref_memno"]]);
		while($rowUser = $mdInfo->fetch(PDO::FETCH_ASSOC)){
			$arrayMd = array();
			$arrayMd["ID"] = $rowUser["id"];
			$arrayMd["MD_NAME"] = $rowUser["md_name"];
			$arrayMd["MD_TYPE"] = $rowUser["md_type"];
			$arrayMd["MD_COUNT"] = $rowUser["md_count"];
			if($rowUser["md_type"] == "0"){			//ประธาน
				$arrayChairman[] = $arrayMd;
			}else if($rowUser["md_type"] == "1"){	//ผู้จัดการ
				$arrayManager[] = $arrayMd;
			}else if($rowUser["md_type"] == "2"){	//คณะกรรมการ
				$arrayBoard[] = $arrayMd;
			}else if($rowUser["md_type"] == "3"){	//ผู้ตรวจสอบกิจการ
				$arrayBusiness[] = $arrayMd;
			}else if($rowUser["md_type"] == "4"){	//จํานวนสมาชิก  ราย
				$arrayMember = $arrayMd;
			}else if($rowUser["md_type"] == "5"){	//เจ้าหน้าที่สหกรณ์ ราย
				$arrayOfficer = $arrayMd;
			}		
		}
		
		
		$memberInfo = $conoracle->prepare("SELECT MP.PRENAME_DESC,MB.MEMB_NAME,MB.MEMB_ENAME,MB.BIRTH_DATE,
												MB.MEMBER_DATE,'' AS POSITION_DESC,
												MG.MEMBGROUP_DESC,
												MT.MEMBTYPE_DESC,
												MB.COOPREGIS_DATE,
												MB.COOPREGIS_NO,
												MB.MEMB_REGNO,MB.TAX_ID,
												MB.ACCYEARCLOSE_DATE,
												mb.ADDR_NO as ADDR_NO,
												mb.ADDR_MOO as ADDR_MOO,
												mb.ADDR_SOI as ADDR_SOI,
												mb.ADDR_VILLAGE as ADDR_VILLAGE,
												mb.ADDR_ROAD as ADDR_ROAD,
												mb.DISTRICT_CODE AS DISTRICT_CODE,
												mb.TAMBOL_CODE AS TAMBOL_CODE,
												mb.ADDR_EMAIL as ADDR_REG_EMAIL,
												mb.ADDR_MOBILEPHONE as ADDR_REG_MOBILEPHONE,
												mb.ADDR_PHONE as ADDR_PHONE,
												mb.ADDR_FAX as ADDR_FAX,
												MB.PROVINCE_CODE AS PROVINCE_CODE,
												MB.ADDR_POSTCODE AS ADDR_POSTCODE,
												MBT.TAMBOL_DESC AS TAMBOL_REG_DESC,
												MBD.DISTRICT_DESC AS DISTRICT_REG_DESC,
												MBP.PROVINCE_DESC AS PROVINCE_REG_DESC,											
												MBT.TAMBOL_DESC AS TAMBOL_DESC,
												MBD.DISTRICT_DESC AS DISTRICT_DESC,										
												MBP.PROVINCE_DESC AS PROVINCE_DESC
												FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
												LEFT JOIN MBUCFMEMBGROUP mg ON mb.MEMBGROUP_CODE = mg.MEMBGROUP_CODE
												LEFT JOIN MBUCFMEMBTYPE mt ON mb.MEMBTYPE_CODE = mt.MEMBTYPE_CODE
												LEFT JOIN MBUCFTAMBOL MBT ON mb.TAMBOL_CODE = MBT.TAMBOL_CODE
												LEFT JOIN MBUCFDISTRICT MBD ON mb.DISTRICT_CODE = MBD.DISTRICT_CODE
												LEFT JOIN MBUCFPROVINCE MBP ON mb.PROVINCE_CODE = MBP.PROVINCE_CODE
												WHERE mb.member_no = :member_no");
		$memberInfo->execute([':member_no' => $payload["ref_memno"]]);
		$rowMember = $memberInfo->fetch(PDO::FETCH_ASSOC);
		$arrayData = array();
		$address = (isset($rowMember["ADDR_NO"]) ? $rowMember["ADDR_NO"] : null);
		if(isset($rowMember["PROVINCE_CODE"]) && $rowMember["PROVINCE_CODE"] == '10'){
			$address .= (isset($rowMember["ADDR_MOO"]) ? ' ม.'.$rowMember["ADDR_MOO"] : null);
			$address .= (isset($rowMember["ADDR_SOI"]) ? ' ซอย'.$rowMember["ADDR_SOI"] : null);
			$address .= (isset($rowMember["ADDR_VILLAGE"]) ? ' หมู่บ้าน'.$rowMember["ADDR_VILLAGE"] : null);
			$address .= (isset($rowMember["ADDR_ROAD"]) ? ' ถนน'.$rowMember["ADDR_ROAD"] : null);
			$address .= (isset($rowMember["TAMBOL_REG_DESC"]) ? ' แขวง'.$rowMember["TAMBOL_REG_DESC"] : null);
			$address .= (isset($rowMember["DISTRICT_REG_DESC"]) ? ' เขต'.$rowMember["DISTRICT_REG_DESC"] : null);
			$address .= (isset($rowMember["PROVINCE_REG_DESC"]) ? ' '.$rowMember["PROVINCE_REG_DESC"] : null);
			$address .= (isset($rowMember["ADDR_POSTCODE"]) ? ' '.$rowMember["ADDR_POSTCODE"] : null);
		}else{
			$address .= (isset($rowMember["ADDR_MOO"]) ? ' ม.'.$rowMember["ADDR_MOO"] : null);
			$address .= (isset($rowMember["ADDR_SOI"]) ? ' ซอย'.$rowMember["ADDR_SOI"] : null);
			$address .= (isset($rowMember["ADDR_VILLAGE"]) ? ' หมู่บ้าน'.$rowMember["ADDR_VILLAGE"] : null);
			$address .= (isset($rowMember["ADDR_ROAD"]) ? ' ถนน'.$rowMember["ADDR_ROAD"] : null);
			$address .= (isset($rowMember["TAMBOL_REG_DESC"]) ? ' ต.'.$rowMember["TAMBOL_REG_DESC"] : null);
			$address .= (isset($rowMember["DISTRICT_REG_DESC"]) ? ' อ.'.$rowMember["DISTRICT_REG_DESC"] : null);
			$address .= (isset($rowMember["PROVINCE_REG_DESC"]) ? ' จ.'.$rowMember["PROVINCE_REG_DESC"] : null);
			$address .= (isset($rowMember["ADDR_POSTCODE"]) ? ' '.$rowMember["ADDR_POSTCODE"] : null);
		}
		$arrayData["ADDR_NO"] = $rowMember["ADDR_NO"];
		$arrayData["ADDR_MOO"] = $rowMember["ADDR_MOO"];
		$arrayData["ADDR_SOI"] = $rowMember["ADDR_SOI"];
		$arrayData["ADDR_VILLAGE"] = $rowMember["ADDR_VILLAGE"];
		$arrayData["ADDR_ROAD"] = $rowMember["ADDR_ROAD"];
		$arrayData["DISTRICT_CODE"] = $rowMember["DISTRICT_CODE"];
		$arrayData["ADDR_POSTCODE"] = $rowMember["ADDR_POSTCODE"];
		$arrayData["TAMBOL_CODE"] = $rowMember["TAMBOL_CODE"];
		$arrayData["PROVINCE_CODE"] = $rowMember["PROVINCE_CODE"];
		$arrayData["TH_NAME"] = $rowMember["PRENAME_DESC"].$rowMember["MEMB_NAME"];
		$arrayData["EN_NAME"] = $rowMember["MEMB_ENAME"];
		$arrayData["ADDR_PHONE"] = $rowMember["ADDR_PHONE"];  //โทรศัพท์
		$arrayData["ADDR_FAX"] = $rowMember["ADDR_FAX"];  //โทรศัพท์
		$arrayData["ADDR_EMAIL"] = $rowMember["ADDR_REG_EMAIL"];  // E-Mail 
		$arrayData["COOPREGIS_DATE"] = $lib->convertdate($rowMember["COOPREGIS_DATE"],"D m Y");
		$arrayData["COOPREGIS_NO"] = $rowMember["COOPREGIS_NO"];  //ทะเบียนเลขที่
		$arrayData["MEMB_REGNO"] = $rowMember["MEMB_REGNO"];  //เลข 13 หลักของสหกรณ์ 
		$arrayData["TAX_ID"] = $rowMember["TAX_ID"];  //เลขประจำตัวผู้เสียภาษีอากร
		$arrayData["ACCYEARCLOSE_DATE"] = $rowMember["ACCYEARCLOSE_DATE"];  //วันสิ้นปีทางบัญชี
		$arrayData["MEMBER_DATE"] = $lib->convertdate($rowMember["MEMBER_DATE"],"D m Y");
		$arrayData["FULL_ADDRESS"] = $address;
		$arrayData["WEBSITE"] = null;  //เว็บไซต์
		$arrayData["MEMBER_NO"] = $payload["ref_memno"];
		$arrayData["MEMBER_COUNT"] = $arrayMember;  //จํานวนสมาชิก
		$arrayData["PRESIDENT"] = $arrayChairman;		//ประธานกรรมการ
		$arrayData["BOARD"] =  $arrayBoard;		//รายชื่อคณะกรรมการ
		$arrayData["MAX_BOARD"] =  15;		//จำนวนรายชื่อคณะกรรมการ
		$arrayData["BUSINESS"] = $arrayBusiness;		//ผู้ตรวจสอบกิจการ
		$arrayData["MAX_BUSINESS"] = 5;		//จำนวนผู้ตรวจสอบกิจการ
		$arrayData["MANAGER"] = $arrayManager;		//ผู้จัดการ
		$arrayData["COOP_OFFICER"] = $arrayOfficer;		//เจ้าหน้าที่สหกรณ์	
		$arrAllTambol = array();
		$dataTambol = $conoracle->prepare("SELECT TAMBOL_CODE,TAMBOL_DESC,DISTRICT_CODE FROM MBUCFTAMBOL");
		$dataTambol->execute();
		while($rowtambol = $dataTambol->fetch(PDO::FETCH_ASSOC)){
			$arrTambol = array();
			$arrTambol["TAMBOL_CODE"] = $rowtambol["TAMBOL_CODE"];
			$arrTambol["TAMBOL_DESC"] = $rowtambol["TAMBOL_DESC"];
			$arrTambol["DISTRICT_CODE"] = $rowtambol["DISTRICT_CODE"];
			$arrAllTambol[] = $arrTambol;
		}
		$arrayDataGeo["TAMBOL_LIST"] = $arrAllTambol;
		$arrAllDistrcit = array();
		$dataDistrcit = $conoracle->prepare("SELECT DISTRICT_CODE,DISTRICT_DESC,PROVINCE_CODE,POSTCODE FROM MBUCFDISTRICT");
		$dataDistrcit->execute();
		while($rowdistrict = $dataDistrcit->fetch(PDO::FETCH_ASSOC)){
			$arrDistrict = array();
			$arrDistrict["DISTRICT_CODE"] = $rowdistrict["DISTRICT_CODE"];
			$arrDistrict["DISTRICT_DESC"] = $rowdistrict["DISTRICT_DESC"];
			$arrDistrict["PROVINCE_CODE"] = $rowdistrict["PROVINCE_CODE"];
			$arrDistrict["POSTCODE"] = $rowdistrict["POSTCODE"];
			$arrAllDistrcit[] = $arrDistrict;
		}
		$arrayDataGeo["DISTRCIT_LIST"] = $arrAllDistrcit;
		$arrAllProvince = array();
		$dataProvince = $conoracle->prepare("SELECT PROVINCE_CODE,PROVINCE_DESC FROM MBUCFPROVINCE");
		$dataProvince->execute();
		while($rowprovince = $dataProvince->fetch(PDO::FETCH_ASSOC)){
			$arrProvince = array();
			$arrProvince["PROVINCE_CODE"] = $rowprovince["PROVINCE_CODE"];
			$arrProvince["PROVINCE_DESC"] = $rowprovince["PROVINCE_DESC"];
			$arrAllProvince[] = $arrProvince;
		}
		$arrayDataGeo["PROVINCE_LIST"] = $arrAllProvince;
		
		$arrayResult["COUNTRY"] = $arrayDataGeo;
		$arrayResult['DATA'] = $arrayData;
		$arrayResult['RESULT'] = TRUE;
		require_once('../../include/exit_footer.php');
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
