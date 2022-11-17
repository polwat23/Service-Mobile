<?php
require_once('../autoload.php');
	$arrayGroupRelig = array();
	$fetchRelig = $conoracle->prepare("SELECT RELIG_ID,RELIG_NAME FROM MEM_M_RELIGION");
	$fetchRelig->execute($arrayExecute);
	while($rowRelig = $fetchRelig->fetch(PDO::FETCH_ASSOC)){
		$arrData = array();
		$arrData["VALUE"] = $rowRelig["RELIG_ID"];
		$arrData["LABEL"] = $rowRelig["RELIG_NAME"];
		
		$arrayGroupRelig[] = $arrData;
	}
	
	$arrayGroupBranch = array();
	$fetchBranch = $conoracle->prepare("SELECT BR_NO,BR_NAME FROM BK_M_BRANCH WHERE BR_NO != '001'");
	$fetchBranch->execute($arrayExecute);
	while($rowBranch = $fetchBranch->fetch(PDO::FETCH_ASSOC)){
		$arrData = array();
		$arrData["VALUE"] = $rowBranch["BR_NO"];
		$arrData["LABEL"] = $rowBranch["BR_NAME"];
		
		$arrayGroupBranch[] = $arrData;
	}

	$arrayGroupPrename = array();
	$fetchPrename = $conoracle->prepare("SELECT PTITLE_ID, PTITLE_NAME FROM MEM_M_PTITLE");
	$fetchPrename->execute($arrayExecute);
	while($rowPrename = $fetchPrename->fetch(PDO::FETCH_ASSOC)){
		$arrData = array();
		$arrData["VALUE"] = $rowPrename["PTITLE_ID"];
		$arrData["LABEL"] = $rowPrename["PTITLE_NAME"];
		
		$arrayGroupPrename[] = $arrData;
	}
	
	$arrayGroupCareer = array();
	$fetchCareer = $conoracle->prepare("SELECT PTITLE_ID, PTITLE_NAME FROM MEM_M_PTITLE");
	$fetchCareer->execute($arrayExecute);
	while($rowCareer = $fetchCareer->fetch(PDO::FETCH_ASSOC)){
		$arrData = array();
		$arrData["VALUE"] = $rowCareer["PTITLE_ID"];
		$arrData["LABEL"] = $rowCareer["PTITLE_NAME"];
		
		$arrayGroupCareer[] = $arrData;
	}
	
	$arrAllTambol = array();
	$arrayDataGeo = array();
	$dataTambol = $conoracle->prepare("select s.ID, p.NAME_IN_THAI as P_NAME,d.NAME_IN_THAI as D_NAME,s.NAME_IN_THAI as S_NAME,s.ZIP_CODE from SUBDISTRICTS s  INNER JOIN DISTRICTS d ON s.DISTRICT_ID = d.ID  INNER JOIN PROVINCES p ON p.id = d.PROVINCE_ID");
	$dataTambol->execute();
	while($rowtambol = $dataTambol->fetch(PDO::FETCH_ASSOC)){
		$arrTambol = array();
		$arrTambol["TAMBOL_CODE"] = $rowtambol["ID"];
		$arrTambol["TAMBOL_DESC"] = $rowtambol["S_NAME"];
		$arrTambol["DISTRICT_DESC"] = $rowtambol["D_NAME"];
		$arrTambol["PROVINCE_DESC"] = $rowtambol["P_NAME"];
		$arrTambol["POSTCODE"] = $rowtambol["ZIP_CODE"];
		$arrAllTambol[] = $arrTambol;
	}
	$arrayDataGeo["TAMBOL_LIST"] = $arrAllTambol;
	$arrAllDistrcit = array();
	$dataDistrcit = $conoracle->prepare("SELECT DISTRICT_ID, DISTRICT_NAME,PROVINCE_ID FROM MEM_M_DISTRICT");
	$dataDistrcit->execute();
	while($rowdistrict = $dataDistrcit->fetch(PDO::FETCH_ASSOC)){
		$arrDistrict = array();
		$arrDistrict["DISTRICT_CODE"] = $rowdistrict["DISTRICT_ID"];
		$arrDistrict["DISTRICT_DESC"] = $rowdistrict["DISTRICT_NAME"];
		$arrDistrict["PROVINCE_CODE"] = $rowdistrict["PROVINCE_ID"];
		$arrDistrict["POSTCODE"] = $rowdistrict["POSTCODE"];
		$arrAllDistrcit[] = $arrDistrict;
	}
	$arrayDataGeo["DISTRICT_LIST"] = $arrAllDistrcit;
	$arrAllProvince = array();
	$dataProvince = $conoracle->prepare("SELECT PROVINCE_ID,PROVINCE_NAME as PROVINCE_NAME FROM MEM_M_PROVINCE");
	$dataProvince->execute();
	while($rowprovince = $dataProvince->fetch(PDO::FETCH_ASSOC)){
		$arrProvince = array();
		$arrProvince["PROVINCE_CODE"] = $rowprovince["PROVINCE_ID"];
		$arrProvince["PROVINCE_DESC"] = $rowprovince["PROVINCE_NAME"];
		$arrAllProvince[] = $arrProvince;
	}
	$arrayDataGeo["PROVINCE_LIST"] = $arrAllProvince;
	
	$arrAllCareer = array();
	$fetchCareer = $conoracle->prepare("SELECT LINEPOST_ID, LINEPOST_NAME from MEM_M_LINEPOST");
	$fetchCareer->execute();
	while($rowCareer = $fetchCareer->fetch(PDO::FETCH_ASSOC)){
		$arrData = array();
		$arrData["VALUE"] = $rowCareer["LINEPOST_ID"];
		$arrData["LABEL"] = $rowCareer["LINEPOST_NAME"];
		
		$arrAllCareer[] = $arrData;
	}
	
	$getFormatForm = $conmysql->prepare("SELECT id_format_req_doc, documenttype_code, form_label, form_key, group_id, max_value, min_value,
								form_type, colspan, fullwidth, required, placeholder, default_value, form_option, optionquery_key, maxwidth, mask 
								FROM gcformatreqdocument
								WHERE is_use = '1' AND documenttype_code = 'RRGT' order by form_order");
	$getFormatForm->execute();
	
	$arrayForm = array();
	$arrayForm["GROUP_ID"] = "1";
	$arrayForm["GROUP_DESC"] = null;
	$arrayGrpData[] = $arrayForm;
	$arrayForm = array();
	$arrayForm["GROUP_ID"] = "2";
	$arrayForm["GROUP_DESC"] = "รายชื่อผู้รับผลประโยชน์";
	$arrayGrpData[] = $arrayForm;
	
	while($rowForm = $getFormatForm->fetch(PDO::FETCH_ASSOC)){
		if($rowForm["form_key"] == "SHARE_PERIOD_PAYMENT"){
			$arrayForm = array();
			$arrayForm["ID_FORMAT_REQ_DOC"] = null;
			$arrayForm["DOCUMENTTYPE_CODE"] = "RRSN";
			$arrayForm["FORM_LABEL"] = "แจ้งเตือนซื้อหุ้น";
			$arrayForm["FORM_KEY"] = "RESIGN_REMARK";
			$arrayForm["FORM_TYPE"] = "remark";
			$arrayForm["COLSPAN"] = "24";
			$arrayForm["FULLWIDTH"] = false;
			$arrayForm["REQUIRED"] = false;
			$arrayForm["PLACEHOLDER"] = "มูลค่าหุ้นละ 10 บาท ขั้นต่ำ 30 หุ้น";
			$arrayForm["DEFAULT_VALUE"] = null;
			$arrayForm["FORM_OPTION"] = null;
			$arrayForm["MAXWIDTH"] = null;
			$arrayForm["GROUP_ID"] = "3";
			$arrayForm["MAX_VALUE"] = '#3f51b5';
			$arrayForm["MIN_VALUE"] = '#3f51b522';
			$arrayGrpForm[] = $arrayForm;
		}
				
		$arrayForm = array();
		$arrayForm["ID_FORMAT_REQ_DOC"] = $rowForm["id_format_req_doc"];
		$arrayForm["DOCUMENTTYPE_CODE"] = $rowForm["documenttype_code"];
		$arrayForm["FORM_LABEL"] = $rowForm["form_label"];
		$arrayForm["FORM_KEY"] = $rowForm["form_key"];
		$arrayForm["FORM_TYPE"] = $rowForm["form_type"];
		$arrayForm["COLSPAN"] = $rowForm["colspan"];
		$arrayForm["FULLWIDTH"] = $rowForm["fullwidth"] == 1 ? true : false;
		$arrayForm["REQUIRED"] = $rowForm["required"] == 1 ? true : false;
		$arrayForm["PLACEHOLDER"] = $rowForm["placeholder"];
		$arrayForm["DEFAULT_VALUE"] = $rowForm["default_value"];
		$arrayForm["FORM_OPTION"] = $rowForm["form_option"];
		$arrayForm["OPTIONQUERY_KEY"] = $rowForm["optionquery_key"];
		$arrayForm["MAXWIDTH"] = $rowForm["maxwidth"];
		$arrayForm["GROUP_ID"] = $rowForm["group_id"];
		$arrayForm["MAX_VALUE"] = $rowForm["max_value"];
		$arrayForm["MIN_VALUE"] = $rowForm["min_value"];
		$arrayForm["MASK"] = $rowForm["mask"];
		//lock effect month
		if($rowForm["form_key"] == "EFFECT_MONTH"){
			$day = date('d');
			if($day > 7){
				$dateNow = new DateTime('first day of this month');
				$dateNow->modify('+1 month');
				$dateNow = $dateNow->format('Y-m-d');
				$arrayForm["DEFAULT_VALUE"] = $dateNow;
			}else{
				$dateNow = new DateTime('first day of this month');
				$dateNow = $dateNow->format('Y-m-d');
				$arrayForm["DEFAULT_VALUE"] = $dateNow;
			}
			$arrayForm["FORM_TYPE"] = "remark";
			$date_arr = explode(" ",$lib->convertdate($dateNow,"D M Y"));
			$arrayForm["PLACEHOLDER"] = $rowForm["form_label"].' '.$date_arr[1].' '.$date_arr[2];
			$arrayForm["MAX_VALUE"] = '#ffffff';
			$arrayForm["MIN_VALUE"] = '#dc5349';
		}
		$arrayGrpForm[] = $arrayForm;
	}
	
	$is_disabled = false;
	/*if(date("d") == 7){
		$is_disabled = false;
		$arrayResult['REQDOC_REMARK_TITLE'] = "ปิดรับเอกสารใบคำขอ";
		$arrayResult['REQDOC_REMARK_DETAIL'] = "ปิดรับเอกสารใบคำขอ สมัครสมาชิก ทุกวันที่ 7 ของเดือน";
	}*/
	
	$arrGrp = array();
	$arrGrp["GROUP_ID"] = "1";
	$arrGrp["GROUP_DESC"] = "ข้อมูลสมาชิก";
	$arrFormGrp[] = $arrGrp;
	$arrGrp["GROUP_ID"] = "2";
	$arrGrp["GROUP_DESC"] = "ที่อยู่ปัจจุบัน";
	$arrFormGrp[] = $arrGrp;
	$arrGrp["GROUP_ID"] = "3";
	$arrGrp["GROUP_DESC"] = "หุ้น";
	$arrFormGrp[] = $arrGrp;
	$arrGrp["GROUP_ID"] = "4";
	$arrGrp["GROUP_DESC"] = "รายละเอียดคู่สมรส";
	$arrFormGrp[] = $arrGrp;
	$arrGrp["GROUP_ID"] = "5";
	$arrGrp["GROUP_DESC"] = "ผู้รับผลประโยชน์";
	$arrFormGrp[] = $arrGrp;
	
	$arrayResult['DISABLED_REQ'] = $is_disabled;
	$arrayResult['FORM_REQDOCUMENT'] = $arrayGrpForm;
	$arrayResult['FORM_GROUP'] = $arrayGrpData;
	$arrayResult["COUNTRY"] = $arrayDataGeo;
	$arrayResult["PRENAME_UCF"] = $arrayGroupPrename;
	$arrayResult["BRANCH_UCF"] = $arrayGroupBranch;
	$arrayResult["RELIG_UCF"] = $arrayGroupRelig;
	
	
	$arrayResult["CAREER_UCF"] = $arrAllCareer;
	$arrayResult["FORM_GROUP"] = $arrFormGrp;
	$arrayResult['RESULT'] = TRUE;
	require_once('../../include/exit_footer.php');
?>