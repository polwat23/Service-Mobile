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
		$arrayData = array();
		$arrayDocData = array();
		$arrGroupUserAcount = array();
		$year = date(Y) +543;
		$getSharemasterinfo = $conoracle->prepare("SELECT  BIZ_YEAR, COOPSTK_AMT,SHARE_MEMBER as SHARE_AMT FROM  mbreqappl WHERE member_no = :member_no");
		$getSharemasterinfo->execute([':member_no' => $payload["ref_memno"]]);		
		$rowMastershare = $getSharemasterinfo->fetch(PDO::FETCH_ASSOC);
		$arrayData['BIZ_YEAR'] = $rowMastershare["BIZ_YEAR"];
		$arrayData['SHARE_AMT'] = number_format($rowMastershare["SHARE_AMT"],2);
		$arrayData['COOPSTK_AMT'] = number_format($rowMastershare["COOPSTK_AMT"],2);
		
		$getDocType = $conmysql->prepare("SELECT docgrp_name, docgrp_no FROM docgroupcontrol where menu_component = 'Coopinfo' AND is_use ='1'");
		$getDocType->execute();
		while($rowDocType = $getDocType->fetch(PDO::FETCH_ASSOC)){
			$arrayDoc = array();
			$arrayDoc["DOCGRP_NAME"] = $rowDocType["docgrp_name"];
			$arrayDoc["DOCGRP_NO"] = $rowDocType["docgrp_no"];
			$arrayDocData[] = $arrayDoc;
		}
		
		//วันที่อัพเดรทเอกสาร
		$getDocTypeDate = $conmysql->prepare("SELECT create_date FROM gcmanagement WHERE member_no = :member_no AND is_updateoncore = '1' 
											  AND create_date = (select max(create_date) FROM gcmanagement WHERE member_no =:member_no AND  is_updateoncore = '1'  )");
		$getDocTypeDate->execute([':member_no' => $payload["ref_memno"]]);
		if($getDocTypeDate->rowCount() > 0){
			$rowDocTypeDate = $getDocTypeDate->fetch(PDO::FETCH_ASSOC);
			$management_update_date = $rowDocTypeDate["create_date"];
		}
		
		$mdInfo = $conoracle->prepare("SELECT  MB.BOARD_NAME as MD_NAME,  MY.MEMBERSHIP_AMT as MD_COUNT, BDRANK_CODE as MD_TYPE,MB.ADD_NO as ADDR_NO,
										MB.ADDR_MOO as ADDR_MOO,MB.ADDR_SOI as ADDR_SOI,MB.ADDR_ROAD as ADDR_ROAD,MB.ADDR_DISTRICT AS DISTRICT_CODE,MB.ADDR_TAMBOL AS TAMBOL_CODE,
										MB.ADDR_PROVINCE AS PROVINCE_CODE,MBT.TAMBOL_DESC AS TAMBOL_REG_DESC,MBD.DISTRICT_DESC AS DISTRICT_REG_DESC,MBP.PROVINCE_DESC AS PROVINCE_REG_DESC,											
										MBT.TAMBOL_DESC AS TAMBOL_DESC,MBD.DISTRICT_DESC AS DISTRICT_DESC,MBP.PROVINCE_DESC AS PROVINCE_DESC,MB.BOARD_TEL,MB.BOARD_AGE,MB.BOARD_EMAIL,MB.PERSON_ID,
										MB.MONTHCLOSEYEAR
										FROM MBMEMBDETYEARBOARD MB LEFT JOIN MBMEMBDETYEARBIZ MY ON MB.MEMBER_NO = MY.MEMBER_NO AND MB.BIZ_YEAR  = MY.BIZ_YEAR
										LEFT JOIN MBUCFTAMBOL MBT ON MB.ADDR_TAMBOL = MBT.TAMBOL_CODE
										LEFT JOIN MBUCFDISTRICT MBD ON MB.ADDR_DISTRICT = MBD.DISTRICT_CODE
										LEFT JOIN MBUCFPROVINCE MBP ON MB.ADDR_PROVINCE = MBP.PROVINCE_CODE
										WHERE  MB.MEMBER_NO = :member_no  AND MB.BIZ_YEAR = :year");
		$mdInfo->execute([':member_no' => $payload["ref_memno"] ,':year' => $year]);
		while($rowUser = $mdInfo->fetch(PDO::FETCH_ASSOC)){
			$arrayMd = array();
			$arrayMd["BOARD_TEL"] = $rowUser["BOARD_TEL"];
			$arrayMd["BOARD_AGE"] = $rowUser["BOARD_AGE"];
			$arrayMd["BOARD_EMAIL"] = $rowUser["BOARD_EMAIL"];
			$arrayMd["PERSON_ID"] = $rowUser["PERSON_ID"];
			$arrayMd["ADDR_NO"] = $rowUser["ADDR_NO"];
			$arrayMd["ADDR_MOO"] = $rowUser["ADDR_MOO"];
			$arrayMd["ADDR_SOI"] = $rowUser["ADDR_SOI"];
			$arrayMd["ADDR_ROAD"] = $rowUser["ADDR_ROAD"];
			$arrayMd["DISTRICT_CODE"] = $rowUser["DISTRICT_CODE"];
			$arrayMd["TAMBOL_CODE"] = $rowUser["TAMBOL_CODE"];
			$arrayMd["PROVINCE_CODE"] = $rowUser["PROVINCE_CODE"];
			$arrayMd["TAMBOL_DESC"] = $rowUser["TAMBOL_REG_DESC"];
			$arrayMd["DISTRICT_DESC"] = $rowUser["DISTRICT_REG_DESC"];
			$arrayMd["PROVINCE_DESC"] = $rowUser["PROVINCE_REG_DESC"];
			$arrayMd["MONTHCLOSEYEAR"] = $rowUser["MONTHCLOSEYEAR"];
			$arrayMd["MD_NAME"] = $rowUser["MD_NAME"];
			$arrayMd["MD_TYPE"] = $rowUser["MD_TYPE"];
			$arrayMd["MD_COUNT"] = $rowUser["MD_COUNT"];
			
			if($rowUser["MD_TYPE"] == "01"){  //ประธาน
				$arrayChairman[] = $arrayMd;
			}else if($rowUser["MD_TYPE"] == "09"){	//ผู้จัดการ
				$arrayManager[] = $arrayMd;
			}else if($rowUser["MD_TYPE"] == "08"){	//คณะกรรมการ
				$arrayBoard[] = $arrayMd;
			}else if($rowUser["MD_TYPE"] == "12"){	//ผู้ตรวจสอบกิจการ
				$arrayBusiness[] = $arrayMd;
			}
				$arrayMember["MD_COUNT"] = $arrayMd["MD_COUNT"];
				//$arrayOfficer["MD_COUNT"]  = $arrayMd["MD_COUNT"];		
		}
		

		$memberInfo = $conoracle->prepare("SELECT MP.PRENAME_DESC,MB.MEMB_NAME,MB.MEMB_ENAME,MB.BIRTH_DATE,
												MB.MEMBER_DATE,'' AS POSITION_DESC,
												MG.MEMBGROUP_DESC,
												MP.SUFFNAME_DESC,
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
												mb.MONTHCLOSEYEAR as MONTHCLOSEYEAR,
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
		$arrayData["TH_NAME"] = $rowMember["PRENAME_DESC"].$rowMember["MEMB_NAME"].' '.$rowMember["SUFFNAME_DESC"];
		//$arrayData["EN_NAME"] = $rowMember["MEMB_ENAME"];
		$arrayData["ADDR_PHONE"] = $rowMember["ADDR_PHONE"];  //โทรศัพท์
		$arrayData["ADDR_FAX"] = $rowMember["ADDR_FAX"];  //โทรศัพท์
		$arrayData["ADDR_EMAIL"] = $rowMember["ADDR_REG_EMAIL"];  // E-Mail 
		$arrayData["COOPREGIS_DATE"] = $rowMember["COOPREGIS_DATE"] ? $lib->convertdate($rowMember["COOPREGIS_DATE"],"D m Y") : null;
		$arrayData["COOPREGIS_NO"] = $rowMember["COOPREGIS_NO"];  //ทะเบียนเลขที่
		$arrayData["MEMB_REGNO"] = $rowMember["MEMB_REGNO"];  //เลข 13 หลักของสหกรณ์ 
		$arrayData["TAX_ID"] = $rowMember["TAX_ID"];  //เลขประจำตัวผู้เสียภาษีอากร
		$arrayData["ACCYEARCLOSE_DATE"] = $rowMember["ACCYEARCLOSE_DATE"] ? $lib->convertdate($rowMember["ACCYEARCLOSE_DATE"],"D m Y") : null;  //วันสิ้นปีทางบัญชี
		$arrayData["MONTHCLOSEYEAR"] = $rowMember["MONTHCLOSEYEAR"];
		$arrayData["MEMBER_DATE"] = $rowMember["MEMBER_DATE"] ? $lib->convertdate($rowMember["MEMBER_DATE"],"D m Y") : null;
		$arrayData["FULL_ADDRESS"] = $address;
		$arrayData["WEBSITE"] = null;  //เว็บไซต์
		$arrayData["MEMBER_NO"] = $payload["ref_memno"];
		$arrayData["MEMBER_COUNT"] = $arrayMember;  //จํานวนสมาชิก
		$arrayData["PRESIDENT"] = $arrayChairman;		//ประธานกรรมการ
		$arrayData["BOARD"] =  $arrayBoard;		//รายชื่อคณะกรรมการ
		$arrayData["MAX_BOARD"] =  15;		//จำนวน max รายชื่อคณะกรรมการ
		$arrayData["BUSINESS"] = $arrayBusiness;		//ผู้ตรวจสอบกิจการ
		$arrayData["MAX_BUSINESS"] = 5;		//จำนวน max ผู้ตรวจสอบกิจการ
		$arrayData["MANAGER"] = $arrayManager;		//ผู้จัดการ
		$arrayData["COOP_OFFICER"] = $arrayOfficer;		//เจ้าหน้าที่สหกรณ์
		$arrayData["DOCUMENTTYPE_LIST"] = $arrayDocData;		//เอกสาร	
		$arrayData["MANAGEMENT_UPDATE_DATE"] = $lib->convertdate($management_update_date,"D M Y") ?? "-";
		
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
		
		//select ใบคำขอเก่า
		$fetchUserManager= $conmysql->prepare("SELECT id_editdata,member_no, old_data as old_data_manger , incoming_data,update_date,username,document_path,is_updateoncore from gcmanagement where is_updateoncore in('0','-1') and member_no = :member_no");
		$fetchUserManager->execute([':member_no' => $payload["ref_memno"]]);
		if($rowUserManager = $fetchUserManager->fetch(PDO::FETCH_ASSOC)){
			$arrayMgGroup  = array();
			$manager  = json_decode(($rowUserManager["incoming_data"]), true);
			if($rowUserManager["is_updateoncore"] == '-1'){
				$manager["REQ_STATUS_DESC"] = 'รายการแก้ไขข้อมูลบริหารจัดการถูกปฏิเสธ';
				$manager["REQ_STATUS"] = $rowUserManager["is_updateoncore"];
			}else{
				$manager["REQ_STATUS_DESC"] = 'มีรายการแก้ไขข้อมูลบริหารจัดการรอดำเนินการ';
				$manager["REQ_STATUS"] = $rowUserManager["is_updateoncore"];
			}
			$manager["UPDATE_DATE"] = $lib->convertdate($rowUserManager["update_date"],'d m Y H-i-s',true);
			$manager["DOCUMENT_PATH"] = $rowUserManager["document_path"];
			$manager["ID_EDITDATA"] = $rowUserManager["id_editdata"];
			$manager["PRESIDENT"] = array($manager["PRESIDENT"]);
			$manager["MANAGER"] = array($manager["MANAGER"]);
			
			if(isset($manager["MEMBER_COUNT"])){
				$manager_document = array();
				$manager_document["MD_COUNT"] = $manager["MEMBER_COUNT"];
				$manager["MEMBER_COUNT"] = $manager_document;
			}
			if(isset($manager["OFFICER_COUNT"])){
				$officer_document = array();
				$officer_document["MD_COUNT"] = $manager["OFFICER_COUNT"];
				$manager["COOP_OFFICER"] = $officer_document;
			}
			$arrayMgGroup  = $manager;

		}
		$fetchUserAccount = $conmysql->prepare("SELECT id_editdata, member_no, edit_date, old_data, incoming_data,update_date,inputgroup_type,old_email,new_email,old_tel,new_tel,new_fax,old_website,new_website,old_coopregis_date,new_coopregis_date,
												old_accyearclose_date,new_accyearclose_date,old_coopregis_no,new_coopregis_no,old_memb_regno,new_memb_regno,old_tax_id,new_tax_id,old_share_stk,new_share_stk,username,document_path,is_updateoncore
											FROM gcmembereditdata WHERE is_updateoncore in('0','-1') and  member_no = :member_no");
		$fetchUserAccount->execute([':member_no' => $payload["ref_memno"]]);
		if($rowUser = $fetchUserAccount->fetch(PDO::FETCH_ASSOC)){
			$arrayGroup = array();
			$address_docno = json_decode(($rowUser["incoming_data"]), true); 
			if($rowUser["is_updateoncore"] == '-1'){
				$arrGroupUserAcount["REQ_STATUS_DESC"] = 'รายการแก้ไขข้อมูลทั่วไปสหกรณ์ถูกปฏิเสธ';
				$arrGroupUserAcount["REQ_STATUS"] = $rowUser["is_updateoncore"];
			}else{
				$arrGroupUserAcount["REQ_STATUS_DESC"] = 'มีรายการแก้ไขข้อมูลทั่วไปสหกรณ์รอดำเนินการ';
				$arrGroupUserAcount["REQ_STATUS"] = $rowUser["is_updateoncore"];
			}
			$arrGroupUserAcount["ID_EDITDATA"] = $rowUser["id_editdata"];
			$arrGroupUserAcount["MEMBER_NO"] = $rowUser["member_no"];
			$arrGroupUserAcount["EDIT_DATE"] = $lib->convertdate($rowUser["edit_date"],'d m Y H-i-s',true);
			$arrGroupUserAcount["UPDATE_DATE"] = $lib->convertdate($rowUser["update_date"],'d m Y H-i-s',true);
			$arrGroupUserAcount["ADDR_EMAIL"] = $rowUser["new_email"];
			$arrGroupUserAcount["ADDR_PHONE"] = $rowUser["new_tel"];
			$arrGroupUserAcount["ADDR_FAX"] = $rowUser["new_fax"];
			$arrGroupUserAcount["WEBSITE"] = $rowUser["new_website"];
			$arrGroupUserAcount["COOPREGIS_DATE"] = $rowUser["new_coopregis_date"] ? $lib->convertdate($rowUser["new_coopregis_date"],"D m Y") : null;
			$arrGroupUserAcount["ACCYEARCLOSE_DATE"] = $rowUser["new_accyearclose_date"] ? $lib->convertdate($rowUser["new_accyearclose_date"],"D m Y") : null;
			$arrGroupUserAcount["COOPREGIS_NO"] = $rowUser["new_coopregis_no"];
			$arrGroupUserAcount["MEMB_REGNO"] = $rowUser["new_memb_regno"];
			$arrGroupUserAcount["TAX_ID"] = $rowUser["new_tax_id"];
			//$arrGroupUserAcount["share_stk"] = number_format($rowUser["new_share_stk"],2);
			$arrGroupUserAcount["ADDR_MOO"] = $address_docno["addr_moo"];
			$arrGroupUserAcount["ADDR_NO"] = $address_docno["addr_no"];
			$arrGroupUserAcount["ADDR_POSTCODE"] = $address_docno["addr_postcode"];
			$arrGroupUserAcount["ADDR_ROAD"] = $address_docno["addr_road"];
			$arrGroupUserAcount["ADDR_SOI"] = $address_docno["addr_soi"];
			$arrGroupUserAcount["ADDR_VILLAGE"] = $address_docno["addr_village"];
			$arrGroupUserAcount["DISTRICT_CODE"] = $address_docno["district_code"];
			$arrGroupUserAcount["PROVINCE_CODE"] = $address_docno["province_code"];
			$arrGroupUserAcount["TAMBOL_CODE"] = $address_docno["tambol_code"];
			$arrGroupUserAcount["DOCUMENT_PATH"] = $rowUser["document_path"];

			
			$search_province = null;
			if(isset($address_docno["province_code"]) && $address_docno["province_code"] != ''){
				$search_province_index = array_search($address_docno["province_code"], array_column($arrayDataGeo["PROVINCE_LIST"], 'PROVINCE_CODE'));
				if(isset($search_province_index) && $search_province_index != ''){
					$search_province = $arrayDataGeo["PROVINCE_LIST"][$search_province_index]["PROVINCE_DESC"];
				}
			}
			$search_district = null;
			if(isset($address_docno["district_code"]) && $address_docno["district_code"] != ''){
				$search_district_index = array_search($address_docno["district_code"], array_column($arrayDataGeo["DISTRCIT_LIST"], 'DISTRICT_CODE'));
				if(isset($search_district_index) && $search_district_index != ''){
					$search_district = $arrayDataGeo["DISTRCIT_LIST"][$search_district_index]["DISTRICT_DESC"];
				}
			}
			$search_tambol = null;
			if(isset($address_docno["tambol_code"]) && $address_docno["tambol_code"] != ''){
				$search_tambol_index = array_search($address_docno["tambol_code"], array_column($arrayDataGeo["TAMBOL_LIST"], 'TAMBOL_CODE'));
				if(isset($search_tambol_index) && $search_tambol_index != ''){
					$search_tambol = $arrayDataGeo["TAMBOL_LIST"][$search_tambol_index]["TAMBOL_DESC"];
				}
			}
			$address = (isset($address_docno["addr_no"]) ? $address_docno["addr_no"] : null);
			if(isset($address_docno["province_code"]) && $address_docno["province_code"] == '10'){
				$address .= (isset($address_docno["addr_moo"]) ? ' ม.'.$address_docno["addr_moo"] : null);
				$address .= (isset($address_docno["addr_soi"]) ? ' ซอย'.$address_docno["addr_soi"] : null);
				$address .= (isset($address_docno["addr_village"]) ? ' หมู่บ้าน'.$address_docno["addr_village"] : null);
				$address .= (isset($address_docno["addr_road"]) ? ' ถนน'.$address_docno["addr_road"] : null);
				$address .= (isset($address_docno["TAMBOL_REG_DESC"]) ? ' แขวง'.$address_docno["TAMBOL_REG_DESC"] : null);
				$address .= (isset($address_docno["DISTRICT_REG_DESC"]) ? ' เขต'.$address_docno["DISTRICT_REG_DESC"] : null);
				$address .= (isset($address_docno["PROVINCE_REG_DESC"]) ? ' '.$address_docno["PROVINCE_REG_DESC"] : null);
				$address .= (isset($address_docno["addr_postcode"]) ? ' '.$address_docno["addr_postcode"] : null);
			}else{
				$address .= (isset($address_docno["addr_moo"]) ? ' ม.'.$address_docno["addr_moo"] : null);
				$address .= (isset($address_docno["addr_soi"]) ? ' ซอย'.$address_docno["addr_soi"] : null);
				$address .= (isset($address_docno["addr_village"]) ? ' หมู่บ้าน'.$address_docno["addr_village"] : null);
				$address .= (isset($address_docno["addr_road"]) ? ' ถนน'.$address_docno["addr_road"] : null);
				$address .= (isset($search_tambol) ? ' ต.'.$search_tambol : null);
				$address .= (isset($search_district) ? ' อ.'.$search_district : null);
				$address .= (isset($search_province) ? ' จ.'.$search_province : null);
				$address .= (isset($address_docno["addr_postcode"]) ? ' '.$address_docno["addr_postcode"] : null);
			}
			$arrGroupUserAcount["FULL_ADDRESS"] = $address;
			
			$arrayGroup = $arrGroupUserAcount;
		}	
		$arrayResult["COUNTRY"] = $arrayDataGeo;
		$arrayResult['DATA'] = $arrayData;
		$arrayResult['DATA_DOCNO'] = $arrayGroup;
		$arrayResult['DATAMG_DOCNO'] = $arrayMgGroup;
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
