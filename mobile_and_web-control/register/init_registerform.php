<?php
require_once('../autoload.php');

	$arrayGroupPrename = array();
	$fetchPrename = $conmssql->prepare("SELECT PRENAME_CODE, PRENAME_SHORT, PRENAME_DESC FROM MBUCFPRENAME");
	$fetchPrename->execute($arrayExecute);
	while($rowPrename = $fetchPrename->fetch(PDO::FETCH_ASSOC)){
		$arrData = array();
		$arrData["VALUE"] = $rowPrename["PRENAME_CODE"];
		$arrData["LABEL"] = $rowPrename["PRENAME_SHORT"] ?? $rowPrename["PRENAME_DESC"];
		
		$arrayGroupPrename[] = $arrData;
	}
	
	$arrAllTambol = array();
	$arrayDataGeo = array();
	$dataTambol = $conmssql->prepare("SELECT TAMBOL_CODE,TAMBOL_DESC,DISTRICT_CODE FROM MBUCFTAMBOL");
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
	$dataDistrcit = $conmssql->prepare("SELECT DISTRICT_CODE,DISTRICT_DESC,PROVINCE_CODE,POSTCODE FROM MBUCFDISTRICT");
	$dataDistrcit->execute();
	while($rowdistrict = $dataDistrcit->fetch(PDO::FETCH_ASSOC)){
		$arrDistrict = array();
		$arrDistrict["DISTRICT_CODE"] = $rowdistrict["DISTRICT_CODE"];
		$arrDistrict["DISTRICT_DESC"] = $rowdistrict["DISTRICT_DESC"];
		$arrDistrict["PROVINCE_CODE"] = $rowdistrict["PROVINCE_CODE"];
		$arrDistrict["POSTCODE"] = $rowdistrict["POSTCODE"];
		$arrAllDistrcit[] = $arrDistrict;
	}
	$arrayDataGeo["DISTRICT_LIST"] = $arrAllDistrcit;
	$arrAllProvince = array();
	$dataProvince = $conmssql->prepare("SELECT PROVINCE_CODE,PROVINCE_DESC FROM MBUCFPROVINCE");
	$dataProvince->execute();
	while($rowprovince = $dataProvince->fetch(PDO::FETCH_ASSOC)){
		$arrProvince = array();
		$arrProvince["PROVINCE_CODE"] = $rowprovince["PROVINCE_CODE"];
		$arrProvince["PROVINCE_DESC"] = $rowprovince["PROVINCE_DESC"];
		$arrAllProvince[] = $arrProvince;
	}
	$arrayDataGeo["PROVINCE_LIST"] = $arrAllProvince;
	
	$getFormatForm = $conmysql->prepare("SELECT id_format_req_doc, documenttype_code, form_label, form_key, group_id, max_value, min_value,
								form_type, colspan, fullwidth, required, placeholder, default_value, form_option, optionquery_key, maxwidth, mask 
								FROM gcformatreqdocument
								WHERE is_use = '1' AND documenttype_code = 'RRGT'");
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
			$arrayForm["PLACEHOLDER"] = "มูลค่าหุ้นละ 10 บาท ขั้นต่ำ 20 หุ้น";
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
				$dateNow = new DateTime('now');
				$dateNow->modify('+1 month');
				$dateNow = $dateNow->format('Y-m-d');
				$arrayForm["DEFAULT_VALUE"] = $dateNow;
			}else{
				$dateNow = new DateTime('now');
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
	
	$arrayResult['DISABLED_REQ'] = $is_disabled;
	$arrayResult['FORM_REQDOCUMENT'] = $arrayGrpForm;
	$arrayResult['FORM_GROUP'] = $arrayGrpData;
	$arrayResult["COUNTRY"] = $arrayDataGeo;
	$arrayResult["PRENAME_UCF"] = $arrayGroupPrename;
	$arrayResult['RESULT'] = TRUE;
	require_once('../../include/exit_footer.php');
?>