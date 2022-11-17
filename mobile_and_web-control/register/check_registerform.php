<?php
require_once('../autoload.php');

use Dompdf\Dompdf;

$dompdf = new DOMPDF();

if ($lib->checkCompleteArgument(['form_value_root_', 'api_token', 'unique_id'], $dataComing)) {
	$arrPayload = $auth->check_apitoken($dataComing["api_token"], $config["SECRET_KEY_JWT"]);
	if (!$arrPayload["VALIDATE"]) {
		$filename = basename(__FILE__, '.php');
		$logStruc = [
			":error_menu" => $filename,
			":error_code" => "WS0001",
			":error_desc" => "ไม่สามารถยืนยันข้อมูลได้" . "\n" . json_encode($dataComing),
			":error_device" => $dataComing["channel"] . ' - ' . $dataComing["unique_id"] . ' on V.' . $dataComing["app_version"]
		];
		$log->writeLog('errorusage', $logStruc);
		$arrayResult['RESPONSE_CODE'] = "WS0001";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(401);
		require_once('../../include/exit_footer.php');
	}

	if (false) {
		//$arrayResult['RESPONSE_CODE'] = "WS0020";
		//$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		require_once('../../include/exit_footer.php');
	} else {
		$arrDocReq = array();
		$card_id = str_replace('-', '', $dataComing["form_value_root_"]["MEMBER_CARDID"]["VALUE"] ?? "");
		$emp_no = $card_id;

		$getReqDocument = $conmysql->prepare("SELECT reqdoc_no, document_url, req_status FROM gcreqdoconline 
											WHERE documenttype_code = 'RRGT' AND member_no = :emp_no AND req_status not IN('-9','9')");
		$getReqDocument->execute([':emp_no' => $emp_no]);
		while ($rowPrename = $getReqDocument->fetch(PDO::FETCH_ASSOC)) {
			$docArr = array();
			$docArr["REQDOC_NO"] = $rowPrename["reqdoc_no"];
			$docArr["DOCUMENT_URL"] = $rowPrename["document_url"];
			$docArr["REQ_STATUS"] = $rowPrename["req_status"];
			$arrDocReq[] = $docArr;
		}

		if (count($arrDocReq) < 1 || true) {
			//$rowInfoMobile = $fetchMemberInfo->fetch(PDO::FETCH_ASSOC);
			if (true) {
				if (true) {
					$shr_period_payment = $dataComing["form_value_root_"]["SHARE_PERIOD_PAYMENT"]["VALUE"] ?? "";

					if ($shr_period_payment % 10 != 0) {
						$arrayResult['RESPONSE_CODE'] = "";
						$arrayResult['RESPONSE_MESSAGE'] = "ค่าหุ้นรายเดือนไม่ถูกต้อง เนื่องจากหุ้นมีมูลค่าหุ้นละ 10 บาท กรุณาตรวจสอบค่าหุ้นรายเดือนและลองใหม่อีกครั้ง";
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
					} else if ($shr_period_payment < 100) {
						$arrayResult['RESPONSE_CODE'] = "";
						$arrayResult['RESPONSE_MESSAGE'] = "ค่าหุ้นรายเดือนขั้นต่ำ 100 บาท กรุณาตรวจสอบค่าหุ้นรายเดือนและลองใหม่อีกครั้ง";
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
					}

					$arrGroupDetail = array();
					$arrGroupDetail["EMP_NO"] = $emp_no;
					$arrGroupDetail["MEMBER_PRENAME"] = ($dataComing["form_value_root_"]["MEMBER_PRENAME"]["VALUE"] ?? "");
					$arrGroupDetail["MEMBER_NAME"] = ($dataComing["form_value_root_"]["MEMBER_NAME"]["VALUE"] ?? "");
					$arrGroupDetail["MEMBER_SURNAME"] = ($dataComing["form_value_root_"]["MEMBER_SURNAME"]["VALUE"] ?? "");
					$arrGroupDetail["MEMBER_CARDID"] =  $card_id;
					$arrGroupDetail["GEN_OPTION"] =  $dataComing["form_value_root_"]["GEN_OPTION"]["VALUE"] ?? "";
					$arrGroupDetail["STATUS_OPTION"] =  $dataComing["form_value_root_"]["STATUS_OPTION"]["VALUE"] ?? "";
					$arrGroupDetail["MEMBER_MOBILEPHONE"] =  $dataComing["form_value_root_"]["MEMBER_MOBILEPHONE"]["VALUE"] ?? "";
					$arrGroupDetail["MEMBER_BIRTHDATE"] = $dataComing["form_value_root_"]["MEMBER_BIRTHDATE"]["VALUE"] ?? "";
					$arrGroupDetail["ADDRESS_ADDR_NO"] =  $dataComing["form_value_root_"]["ADDRESS_ADDR_NO"]["VALUE"] ?? "";
					$arrGroupDetail["ADDRESS_VILLAGE_NO"] =  $dataComing["form_value_root_"]["ADDRESS_VILLAGE_NO"]["VALUE"] ?? "";
					$arrGroupDetail["ADDRESS_ROAD"] =  $dataComing["form_value_root_"]["ADDRESS_ROAD"]["VALUE"] ?? "";
					$arrGroupDetail["ADDRESS_SOI"] =  $dataComing["form_value_root_"]["ADDRESS_SOI"]["VALUE"] ?? "";
					$arrGroupDetail["ADDRESS_PROVINE_CODE"] =  $dataComing["form_value_root_"]["ADDRESS_PROVINE_CODE"]["VALUE"]["PROVINCE_DESC"] ?? "";
					$arrGroupDetail["ADDRESS_PROVINE_ID"] =  $dataComing["form_value_root_"]["ADDRESS_PROVINE_CODE"]["VALUE"]["PROVINCE_CODE"] ?? "";
					$arrGroupDetail["ADDRESS_DISTRICT_CODE"] =  $dataComing["form_value_root_"]["ADDRESS_DISTRICT_CODE"]["VALUE"]["DISTRICT_DESC"] ?? "";
					$arrGroupDetail["ADDRESS_DISTRICT_ID"] =  $dataComing["form_value_root_"]["ADDRESS_DISTRICT_CODE"]["VALUE"]["DISTRICT_CODE"] ?? "";
					$arrGroupDetail["ADDRESS_TAMBOL_CODE"] =  $dataComing["form_value_root_"]["ADDRESS_TAMBOL_CODE"]["VALUE"]["TAMBOL_DESC"] ?? "";
					$arrGroupDetail["ADDRESS_POSTCODE"] =  $dataComing["form_value_root_"]["ADDRESS_TAMBOL_CODE"]["VALUE"]["POSTCODE"] ?? "";
					$arrGroupDetail["ADDRESS2_ADDR_NO"] =  $dataComing["form_value_root_"]["ADDRESS2_ADDR_NO"]["VALUE"] ?? "";
					$arrGroupDetail["ADDRESS2_VILLAGE_NO"] =  $dataComing["form_value_root_"]["ADDRESS2_VILLAGE_NO"]["VALUE"] ?? "";
					$arrGroupDetail["ADDRESS2_ROAD"] =  $dataComing["form_value_root_"]["ADDRESS2_ROAD"]["VALUE"] ?? "";
					$arrGroupDetail["ADDRESS2_SOI"] =  $dataComing["form_value_root_"]["ADDRESS2_SOI"]["VALUE"] ?? "";
					$arrGroupDetail["ADDRESS2_PROVINE_CODE"] =  $dataComing["form_value_root_"]["ADDRESS2_PROVINE_CODE"]["VALUE"]["PROVINCE_DESC"] ?? "";
					$arrGroupDetail["ADDRESS2_PROVINE_ID"] =  $dataComing["form_value_root_"]["ADDRESS2_PROVINE_CODE"]["VALUE"]["PROVINCE_CODE"] ?? "";
					$arrGroupDetail["ADDRESS2_DISTRICT_CODE"] =  $dataComing["form_value_root_"]["ADDRESS2_DISTRICT_CODE"]["VALUE"]["DISTRICT_DESC"] ?? "";
					$arrGroupDetail["ADDRESS2_DISTRICT_ID"] =  $dataComing["form_value_root_"]["ADDRESS2_DISTRICT_CODE"]["VALUE"]["DISTRICT_CODE"] ?? "";
					$arrGroupDetail["ADDRESS2_TAMBOL_CODE"] =  $dataComing["form_value_root_"]["ADDRESS2_TAMBOL_CODE"]["VALUE"]["TAMBOL_DESC"] ?? "";
					$arrGroupDetail["ADDRESS2_POSTCODE"] =  $dataComing["form_value_root_"]["ADDRESS2_TAMBOL_CODE"]["VALUE"]["POSTCODE"] ?? "";
					$arrGroupDetail["MEMBER_CARDID_DISTRICT_ID"] =  $dataComing["form_value_root_"]["MEMBER_CARDID_DISTRICT_CODE"]["VALUE"]["DISTRICT_CODE"] ?? "";
					$arrGroupDetail["MEMBER_CARDID_PROVINE_ID"] =  $dataComing["form_value_root_"]["MEMBER_CARDID_PROVINE_CODE"]["VALUE"]["PROVINCE_CODE"] ?? "";


					$arrGroupDetail["MEMBER_SARALY"] =  $dataComing["form_value_root_"]["MEMBER_SARALY"]["VALUE"] ?? "";
					$arrGroupDetail["DAD_NAME"] =  $dataComing["form_value_root_"]["DAD_NAME"]["VALUE"] ?? "";
					$arrGroupDetail["MOM_NAME"] =  $dataComing["form_value_root_"]["MOM_NAME"]["VALUE"] ?? "";
					$arrGroupDetail["SHARE_FIRST_PAYMENT"] =  $dataComing["form_value_root_"]["SHARE_FIRST_PAYMENT"]["VALUE"] ?? "";
					$arrGroupDetail["SHARE_PERIOD_PAYMENT"] =  $dataComing["form_value_root_"]["SHARE_PERIOD_PAYMENT"]["VALUE"] ?? "";
					$arrGroupDetail["SPOUSE_NAME"] =  $dataComing["form_value_root_"]["SPOUSE_NAME"]["VALUE"] ?? "";
					$arrGroupDetail["SPOUSE_CARDID"] =  $dataComing["form_value_root_"]["SPOUSE_CARDID"]["VALUE"] ?? "";
					$arrGroupDetail["CHILD"] =  $dataComing["form_value_root_"]["CHILD"]["VALUE"] ?? "";
					$arrGroupDetail["CHILD_MALE"] =  $dataComing["form_value_root_"]["CHILD_MALE"]["VALUE"] ?? "";
					$arrGroupDetail["CHILD_FEMALE"] =  $dataComing["form_value_root_"]["CHILD_FEMALE"]["VALUE"] ?? "";
					$arrGroupDetail["BENEF_NAME_1"] =  $dataComing["form_value_root_"]["BENEF_NAME_1"]["VALUE"] ?? "";
					$arrGroupDetail["BENEF_AGE_1"] =  $dataComing["form_value_root_"]["BENEF_AGE_1"]["VALUE"] ?? "";
					$arrGroupDetail["BENEF_ADDRESS_1_ADDR_NO"] =  $dataComing["form_value_root_"]["BENEF_ADDRESS_1_ADDR_NO"]["VALUE"] ?? "";
					$arrGroupDetail["BENEF_ADDRESS_1_VILLAGE_NO"] =  $dataComing["form_value_root_"]["BENEF_ADDRESS_1_VILLAGE_NO"]["VALUE"] ?? "";
					$arrGroupDetail["BENEF_ADDRESS_1_ROAD"] =  $dataComing["form_value_root_"]["BENEF_ADDRESS_1_ROAD"]["VALUE"] ?? "";
					$arrGroupDetail["BENEF_ADDRESS_1_SOI"] =  $dataComing["form_value_root_"]["BENEF_ADDRESS_1_SOI"]["VALUE"] ?? "";
					$arrGroupDetail["BENEF_ADDRESS_1_PROVINE_CODE"] =  $dataComing["form_value_root_"]["BENEF_ADDRESS_1_PROVINE_CODE"]["VALUE"]["PROVINCE_DESC"] ?? "";
					$arrGroupDetail["BENEF_ADDRESS_1_PROVINE_ID"] =  $dataComing["form_value_root_"]["BENEF_ADDRESS_1_PROVINE_CODE"]["VALUE"]["PROVINCE_CODE"] ?? "";
					$arrGroupDetail["BENEF_ADDRESS_1_DISTRICT_CODE"] =  $dataComing["form_value_root_"]["BENEF_ADDRESS_1_DISTRICT_CODE"]["VALUE"]["DISTRICT_DESC"] ?? "";
					$arrGroupDetail["BENEF_ADDRESS_1_DISTRICT_ID"] =  $dataComing["form_value_root_"]["BENEF_ADDRESS_1_DISTRICT_CODE"]["VALUE"]["DISTRICT_CODE"] ?? "";
					$arrGroupDetail["BENEF_ADDRESS_1_TAMBOL_CODE"] =  $dataComing["form_value_root_"]["BENEF_ADDRESS_1_TAMBOL_CODE"]["VALUE"]["TAMBOL_DESC"] ?? "";
					$arrGroupDetail["BENEF_ADDRESS_1_POST_CODE"] =  $dataComing["form_value_root_"]["BENEF_ADDRESS_1_TAMBOL_CODE"]["VALUE"]["POSTCODE"] ?? "";
					$arrGroupDetail["BENEF_PHONE_1"] =  $dataComing["form_value_root_"]["BENEF_PHONE_1"]["VALUE"] ?? "";
					$arrGroupDetail["BENEF_RELATION_1"] =  $dataComing["form_value_root_"]["BENEF_PHONE_1"]["VALUE"] ?? "";

					$arrGroupDetail["MEMBER_CAREER"] =  $dataComing["form_value_root_"]["MEMBER_CAREER"]["VALUE"] ?? "";

					$arrGroupDetail["BENEF_NAME_2"] =  $dataComing["form_value_root_"]["BENEF_NAME_2"]["VALUE"] ?? "";
					$arrGroupDetail["BENEF_AGE_2"] =  $dataComing["form_value_root_"]["BENEF_AGE_2"]["VALUE"] ?? "";
					$arrGroupDetail["BENEF_ADDRESS_2_ADDR_NO"] =  $dataComing["form_value_root_"]["BENEF_ADDRESS_2_ADDR_NO"]["VALUE"] ?? "";
					$arrGroupDetail["BENEF_ADDRESS_2_VILLAGE_NO"] =  $dataComing["form_value_root_"]["BENEF_ADDRESS_2_VILLAGE_NO"]["VALUE"] ?? "";
					$arrGroupDetail["BENEF_ADDRESS_2_ROAD"] =  $dataComing["form_value_root_"]["BENEF_ADDRESS_2_ROAD"]["VALUE"] ?? "";
					$arrGroupDetail["BENEF_ADDRESS_2_SOI"] =  $dataComing["form_value_root_"]["BENEF_ADDRESS_2_SOI"]["VALUE"] ?? "";
					$arrGroupDetail["BENEF_ADDRESS_2_PROVINE_CODE"] =  $dataComing["form_value_root_"]["BENEF_ADDRESS_2_PROVINE_CODE"]["VALUE"]["PROVINCE_DESC"] ?? "";
					$arrGroupDetail["BENEF_ADDRESS_2_PROVINE_ID"] =  $dataComing["form_value_root_"]["BENEF_ADDRESS_2_PROVINE_CODE"]["VALUE"]["PROVINCE_CODE"] ?? "";
					$arrGroupDetail["BENEF_ADDRESS_2_DISTRICT_CODE"] =  $dataComing["form_value_root_"]["BENEF_ADDRESS_2_DISTRICT_CODE"]["VALUE"]["DISTRICT_DESC"] ?? "";
					$arrGroupDetail["BENEF_ADDRESS_2_TAMBOL_CODE"] =  $dataComing["form_value_root_"]["BENEF_ADDRESS_2_TAMBOL_CODE"]["VALUE"]["TAMBOL_DESC"] ?? "";
					$arrGroupDetail["BENEF_PHONE_2"] =  $dataComing["form_value_root_"]["BENEF_PHONE_2"]["VALUE"] ?? "";
					$arrGroupDetail["BENEF_RELATION_2"] =  $dataComing["form_value_root_"]["BENEF_RELATION_2"]["VALUE"] ?? "";

					$arrGroupDetail["BENEF_NAME_3"] =  $dataComing["form_value_root_"]["BENEF_NAME_3"]["VALUE"] ?? "";
					$arrGroupDetail["BENEF_AGE_3"] =  $dataComing["form_value_root_"]["BENEF_AGE_3"]["VALUE"] ?? "";
					$arrGroupDetail["BENEF_ADDRESS_3_ADDR_NO"] =  $dataComing["form_value_root_"]["BENEF_ADDRESS_3_ADDR_NO"]["VALUE"] ?? "";
					$arrGroupDetail["BENEF_ADDRESS_3_VILLAGE_NO"] =  $dataComing["form_value_root_"]["BENEF_ADDRESS_3_VILLAGE_NO"]["VALUE"] ?? "";
					$arrGroupDetail["BENEF_ADDRESS_3_ROAD"] =  $dataComing["form_value_root_"]["BENEF_ADDRESS_3_ROAD"]["VALUE"] ?? "";
					$arrGroupDetail["BENEF_ADDRESS_3_SOI"] =  $dataComing["form_value_root_"]["BENEF_ADDRESS_3_SOI"]["VALUE"] ?? "";
					$arrGroupDetail["BENEF_ADDRESS_3_PROVINE_CODE"] =  $dataComing["form_value_root_"]["BENEF_ADDRESS_3_PROVINE_CODE"]["VALUE"]["PROVINCE_DESC"] ?? "";
					$arrGroupDetail["BENEF_ADDRESS_3_PROVINE_ID"] =  $dataComing["form_value_root_"]["BENEF_ADDRESS_3_PROVINE_CODE"]["VALUE"]["PROVINCE_CODE"] ?? "";
					$arrGroupDetail["BENEF_ADDRESS_3_DISTRICT_CODE"] =  $dataComing["form_value_root_"]["BENEF_ADDRESS_3_DISTRICT_CODE"]["VALUE"]["DISTRICT_DESC"] ?? "";
					$arrGroupDetail["BENEF_ADDRESS_3_TAMBOL_CODE"] =  $dataComing["form_value_root_"]["BENEF_ADDRESS_3_TAMBOL_CODE"]["VALUE"]["TAMBOL_DESC"] ?? "";
					$arrGroupDetail["BENEF_PHONE_3"] =  $dataComing["form_value_root_"]["BENEF_PHONE_3"]["VALUE"] ?? "";
					$arrGroupDetail["BENEF_RELATION_3"] =  $dataComing["form_value_root_"]["BENEF_RELATION_3"]["VALUE"] ?? "";
					$arrGroupDetail["MEMBER_BRANCH"] =  $dataComing["form_value_root_"]["MEMBER_BRANCH"]["VALUE"] ?? "";
					$arrGroupDetail["MEMBER_RELIG"] =  $dataComing["form_value_root_"]["MEMBER_RELIG"]["VALUE"] ?? "";
					
					//data
					$arrRegister = array();
					$arrRegister1 = array();
					$arrRegister1["SUB_TITLE"] = "ข้อมูลสมาชิก";
					$arrRegister1["SUB_DATA"] = array();
					//group 1
					$arrRegisterData = array();
					$fetchBR = $conoracle->prepare("SELECT BR_NO,BR_NAME FROM BK_M_BRANCH where BR_NO = :id");
					$fetchBR->execute([':id' => $arrGroupDetail["MEMBER_BRANCH"]]);
					$rowBR = $fetchBR->fetch(PDO::FETCH_ASSOC);
					$arrRegisterSubData = array();
					$arrRegisterSubData["LABEL"] = "ต้องการเป็นสมาชิกสาขา";
					$arrRegisterSubData["VALUE"] = $rowBR["BR_NAME"];
					$arrRegisterData[] = $arrRegisterSubData;
					$arrRegister1["SUB_DATA"][] = $arrRegisterData;
					//group 2
					$arrRegisterData = array();
					$fetchPrename = $conoracle->prepare("SELECT PTITLE_ID, PTITLE_NAME FROM MEM_M_PTITLE where PTITLE_ID = :id");
					$fetchPrename->execute([':id' => $arrGroupDetail["MEMBER_PRENAME"]]);
					$rowPrename = $fetchPrename->fetch(PDO::FETCH_ASSOC);
					$arrRegisterSubData = array();
					$arrRegisterSubData["LABEL"] = "ชื่อ - นามสกุล";
					$arrRegisterSubData["VALUE"] = $rowPrename["PTITLE_NAME"].$arrGroupDetail["MEMBER_NAME"]." ".$arrGroupDetail["MEMBER_SURNAME"];
					$arrRegisterData[] = $arrRegisterSubData;
					$arrRegisterSubData = array();
					$arrRegisterSubData["LABEL"] = "เลขบัตรประชาชน";
					$arrRegisterSubData["VALUE"] = $arrGroupDetail["MEMBER_CARDID"];
					$arrRegisterData[] = $arrRegisterSubData;
					$arrRegisterSubData = array();
					$arrRegisterSubData["LABEL"] = "วัน/เดือน/ปีเกิด";
					$arrRegisterSubData["VALUE"] = $lib->convertdate($arrGroupDetail["MEMBER_BIRTHDATE"],"D m Y");;
					$arrRegisterData[] = $arrRegisterSubData;
					$arrRegister1["SUB_DATA"][] = $arrRegisterData;
					//group 3
					$arrRegisterData = array();
					$arrRegisterSubData = array();
					$arrRegisterSubData["LABEL"] = "เพศ";
					$arrRegisterSubData["VALUE"] = $arrGroupDetail["GEN_OPTION"] == '1' ? "ชาย" : "หญิง";
					$arrRegisterData[] = $arrRegisterSubData;
					$arrRegisterSubData = array();
					$arrRegisterSubData["LABEL"] = "สถานะภาพสมรส";
					if($arrGroupDetail["STATUS_OPTION"] == "1"){
						$arrRegisterSubData["VALUE"] = "โสด";
					} else if($arrGroupDetail["STATUS_OPTION"] == "2"){
						$arrRegisterSubData["VALUE"] = "สมรส";
					} else if($arrGroupDetail["STATUS_OPTION"] == "3"){
						$arrRegisterSubData["VALUE"] = "หย่า";
					} else if($arrGroupDetail["STATUS_OPTION"] == "4"){
						$arrRegisterSubData["VALUE"] = "หม้าย";
					}
					$arrRegisterData[] = $arrRegisterSubData;
					$fetchRelig = $conoracle->prepare("SELECT RELIG_ID,RELIG_NAME FROM MEM_M_RELIGION where RELIG_ID = :id");
					$fetchRelig->execute([':id' => $arrGroupDetail["MEMBER_RELIG"]]);
					$rowRelig = $fetchRelig->fetch(PDO::FETCH_ASSOC);
					$arrRegisterSubData = array();
					$arrRegisterSubData["LABEL"] = "ศาสนา";
					$arrRegisterSubData["VALUE"] = $rowRelig["RELIG_NAME"];
					$arrRegisterData[] = $arrRegisterSubData;
					$fetchCareer = $conoracle->prepare("SELECT LINEPOST_ID, LINEPOST_NAME from MEM_M_LINEPOST where LINEPOST_ID = :id");
					$fetchCareer->execute([':id' => $arrGroupDetail["MEMBER_CAREER"]]);
					$rowCareer = $fetchCareer->fetch(PDO::FETCH_ASSOC);
					$arrRegisterSubData = array();
					$arrRegisterSubData["LABEL"] = "อาชีพ";
					$arrRegisterSubData["VALUE"] = $rowCareer["LINEPOST_NAME"];
					$arrRegisterData[] = $arrRegisterSubData;
					$arrRegisterSubData = array();
					$arrRegisterSubData["LABEL"] = "เงินเดือน";
					$arrRegisterSubData["VALUE"] = $arrGroupDetail["MEMBER_SARALY"];
					$arrRegisterData[] = $arrRegisterSubData;
					$arrRegister1["SUB_DATA"][] = $arrRegisterData;
					//group 4
					$arrRegisterData = array();
					$arrRegisterSubData = array();
					$arrRegisterSubData["LABEL"] = "ชื่อบิดา";
					$arrRegisterSubData["VALUE"] = $arrGroupDetail["DAD_NAME"];
					$arrRegisterData[] = $arrRegisterSubData;
					$arrRegisterSubData = array();
					$arrRegisterSubData["LABEL"] = "ชื่อมารดา";
					$arrRegisterSubData["VALUE"] = $arrGroupDetail["MOM_NAME"];
					$arrRegisterData[] = $arrRegisterSubData;
					$arrRegister1["SUB_DATA"][] = $arrRegisterData;
					//push data
					$arrRegister[] = $arrRegister1;
					//data 2
					$arrRegister1 = array();
					$arrRegister1["SUB_TITLE"] = "ข้อมูลที่อยู่";
					$arrRegister1["SUB_DATA"] = array();
					//group 1
					$arrRegisterData = array();
					$address = (isset($arrGroupDetail["ADDRESS_ADDR_NO"]) ? $arrGroupDetail["ADDRESS_ADDR_NO"] : null);
					if(isset($arrGroupDetail["ADDRESS_PROVINE_ID"]) && $arrGroupDetail["ADDRESS_PROVINE_ID"] == '10'){
						$address .= (isset($arrGroupDetail["ADDRESS_VILLAGE_NO"]) ? ' ม.'.$arrGroupDetail["ADDRESS_VILLAGE_NO"] : null);
						$address .= (isset($arrGroupDetail["ADDRESS_SOI"]) ? ' ซอย'.$arrGroupDetail["ADDRESS_SOI"] : null);
						$address .= (isset($arrGroupDetail["ADDRESS_ROAD"]) ? ' ถนน'.$arrGroupDetail["ADDRESS_ROAD"] : null);
						$address .= (isset($arrGroupDetail["ADDRESS_TAMBOL_CODE"]) ? ' แขวง'.$arrGroupDetail["ADDRESS_TAMBOL_CODE"] : null);
						$address .= (isset($arrGroupDetail["ADDRESS_DISTRICT_CODE"]) ? ' เขต'.$arrGroupDetail["ADDRESS_DISTRICT_CODE"] : null);
						$address .= (isset($arrGroupDetail["ADDRESS_PROVINE_CODE"]) ? ' '.$arrGroupDetail["ADDRESS_PROVINE_CODE"] : null);
						$address .= (isset($arrGroupDetail["ADDRESS_POSTCODE"]) ? ' '.$arrGroupDetail["ADDRESS_POSTCODE"] : null);
					}else{
						$address .= (isset($arrGroupDetail["ADDRESS_VILLAGE_NO"]) ? ' ม.'.$arrGroupDetail["ADDRESS_VILLAGE_NO"] : null);
						$address .= (isset($arrGroupDetail["ADDRESS_SOI"]) ? ' ซอย'.$arrGroupDetail["ADDRESS_SOI"] : null);
						$address .= (isset($arrGroupDetail["ADDRESS_ROAD"]) ? ' ถนน'.$arrGroupDetail["ADDRESS_ROAD"] : null);
						$address .= (isset($arrGroupDetail["ADDRESS_TAMBOL_CODE"]) ? ' ต.'.$arrGroupDetail["ADDRESS_TAMBOL_CODE"] : null);
						$address .= (isset($arrGroupDetail["ADDRESS_DISTRICT_CODE"]) ? ' อ.'.$arrGroupDetail["ADDRESS_DISTRICT_CODE"] : null);
						$address .= (isset($arrGroupDetail["ADDRESS_PROVINE_CODE"]) ? ' จ.'.$arrGroupDetail["ADDRESS_PROVINE_CODE"] : null);
						$address .= (isset($arrGroupDetail["ADDRESS_POSTCODE"]) ? ' '.$arrGroupDetail["ADDRESS_POSTCODE"] : null);
					}
					$arrRegisterSubData = array();
					$arrRegisterSubData["LABEL"] = "ที่อยู่ปัจจุบัน";
					$arrRegisterSubData["VALUE"] = $address;
					$arrRegisterData[] = $arrRegisterSubData;
					$arrRegister1["SUB_DATA"][] = $arrRegisterData;
					//group 2
					$arrRegisterData = array();
					$address = (isset($arrGroupDetail["ADDRESS2_ADDR_NO"]) ? $arrGroupDetail["ADDRESS2_ADDR_NO"] : null);
					if(isset($arrGroupDetail["ADDRESS2_PROVINE_ID"]) && $arrGroupDetail["ADDRESS2_PROVINE_ID"] == '10'){
						$address .= (isset($arrGroupDetail["ADDRESS2_VILLAGE_NO"]) ? ' ม.'.$arrGroupDetail["ADDRESS2_VILLAGE_NO"] : null);
						$address .= (isset($arrGroupDetail["ADDRESS2_SOI"]) ? ' ซอย'.$arrGroupDetail["ADDRESS2_SOI"] : null);
						$address .= (isset($arrGroupDetail["ADDRESS2_ROAD"]) ? ' ถนน'.$arrGroupDetail["ADDRESS2_ROAD"] : null);
						$address .= (isset($arrGroupDetail["ADDRESS2_TAMBOL_CODE"]) ? ' แขวง'.$arrGroupDetail["ADDRESS2_TAMBOL_CODE"] : null);
						$address .= (isset($arrGroupDetail["ADDRESS2_DISTRICT_CODE"]) ? ' เขต'.$arrGroupDetail["ADDRESS2_DISTRICT_CODE"] : null);
						$address .= (isset($arrGroupDetail["ADDRESS2_PROVINE_CODE"]) ? ' '.$arrGroupDetail["ADDRESS2_PROVINE_CODE"] : null);
						$address .= (isset($arrGroupDetail["ADDRESS2_POSTCODE"]) ? ' '.$arrGroupDetail["ADDRESS2_POSTCODE"] : null);
					}else{
						$address .= (isset($arrGroupDetail["ADDRESS2_VILLAGE_NO"]) ? ' ม.'.$arrGroupDetail["ADDRESS2_VILLAGE_NO"] : null);
						$address .= (isset($arrGroupDetail["ADDRESS2_SOI"]) ? ' ซอย'.$arrGroupDetail["ADDRESS2_SOI"] : null);
						$address .= (isset($arrGroupDetail["ADDRESS2_ROAD"]) ? ' ถนน'.$arrGroupDetail["ADDRESS2_ROAD"] : null);
						$address .= (isset($arrGroupDetail["ADDRESS2_TAMBOL_CODE"]) ? ' ต.'.$arrGroupDetail["ADDRESS2_TAMBOL_CODE"] : null);
						$address .= (isset($arrGroupDetail["ADDRESS2_DISTRICT_CODE"]) ? ' อ.'.$arrGroupDetail["ADDRESS2_DISTRICT_CODE"] : null);
						$address .= (isset($arrGroupDetail["ADDRESS2_PROVINE_CODE"]) ? ' จ.'.$arrGroupDetail["ADDRESS2_PROVINE_CODE"] : null);
						$address .= (isset($arrGroupDetail["ADDRESS2_POSTCODE"]) ? ' '.$arrGroupDetail["ADDRESS2_POSTCODE"] : null);
					}
					$arrRegisterSubData = array();
					$arrRegisterSubData["LABEL"] = "ที่อยู่ส่งเอกสาร";
					$arrRegisterSubData["VALUE"] = $address;
					$arrRegisterData[] = $arrRegisterSubData;
					$arrRegister1["SUB_DATA"][] = $arrRegisterData;
					//group 3
					$arrRegisterData = array();
					$arrRegisterSubData = array();
					$arrRegisterSubData["LABEL"] = "โทรศัพท์มือถือ";
					$arrRegisterSubData["VALUE"] = $arrGroupDetail["MEMBER_MOBILEPHONE"];
					$arrRegisterData[] = $arrRegisterSubData;
					$arrRegister1["SUB_DATA"][] = $arrRegisterData;
					//push data
					$arrRegister[] = $arrRegister1;
					//data 3
					$arrRegister1 = array();
					$arrRegister1["SUB_TITLE"] = "หุ้น";
					$arrRegister1["SUB_DATA"] = array();
					//group 1
					$arrRegisterData = array();
					$arrRegisterSubData = array();
					$arrRegisterSubData["LABEL"] = "ส่งค่าหุ้นรายเดือน";
					$arrRegisterSubData["VALUE"] = $arrGroupDetail["SHARE_PERIOD_PAYMENT"];
					$arrRegisterData[] = $arrRegisterSubData;
					$arrRegisterSubData = array();
					$arrRegisterSubData["LABEL"] = "ชำระหุ้นครั้งแรก";
					$arrRegisterSubData["VALUE"] = $arrGroupDetail["SHARE_FIRST_PAYMENT"];
					$arrRegisterData[] = $arrRegisterSubData;
					$arrRegister1["SUB_DATA"][] = $arrRegisterData;
					//push data
					$arrRegister[] = $arrRegister1;
					//data 4
					$arrRegister1 = array();
					$arrRegister1["SUB_TITLE"] = "รายละเอียดคู่สมรส";
					$arrRegister1["SUB_DATA"] = array();
					//group 1
					$arrRegisterData = array();
					$arrRegisterSubData = array();
					$arrRegisterSubData["LABEL"] = "ชื่อ - สกุลคู่สมรส";
					$arrRegisterSubData["VALUE"] = $arrGroupDetail["SPOUSE_NAME"];
					$arrRegisterData[] = $arrRegisterSubData;
					$arrRegisterSubData = array();
					$arrRegisterSubData["LABEL"] = "เลขบัตรประชาชน";
					$arrRegisterSubData["VALUE"] = $arrGroupDetail["SPOUSE_CARDID"];
					$arrRegisterData[] = $arrRegisterSubData;
					$arrRegisterSubData = array();
					$arrRegisterSubData["LABEL"] = "จำนวนบุตร";
					$arrRegisterSubData["VALUE"] = $arrGroupDetail["CHILD"];
					$arrRegisterData[] = $arrRegisterSubData;
					$arrRegisterSubData = array();
					$arrRegisterSubData["LABEL"] = "ชาย";
					$arrRegisterSubData["VALUE"] = $arrGroupDetail["CHILD_MALE"];
					$arrRegisterData[] = $arrRegisterSubData;
					$arrRegisterSubData = array();
					$arrRegisterSubData["LABEL"] = "หญิง";
					$arrRegisterSubData["VALUE"] = $arrGroupDetail["CHILD_FEMALE"];
					$arrRegisterData[] = $arrRegisterSubData;
					$arrRegister1["SUB_DATA"][] = $arrRegisterData;
					//push data
					$arrRegister[] = $arrRegister1;
					//data 5
					$arrRegister1 = array();
					$arrRegister1["SUB_TITLE"] = "ผู้รับผลประโยชน์";
					$arrRegister1["SUB_DATA"] = array();
					//group 1
					$arrRegisterData = array();
					$arrRegisterSubData = array();
					$arrRegisterSubData["LABEL"] = "ชื่อ - นามสกุล ผู้รับผลประโยชน์ 1.";
					$arrRegisterSubData["VALUE"] = $arrGroupDetail["BENEF_NAME_1"];
					$arrRegisterData[] = $arrRegisterSubData;
					$arrRegisterSubData = array();
					$arrRegisterSubData["LABEL"] = "อายุ";
					$arrRegisterSubData["VALUE"] = $arrGroupDetail["BENEF_AGE_1"];
					$arrRegisterData[] = $arrRegisterSubData;
					$address = (isset($arrGroupDetail["BENEF_ADDRESS_1_ADDR_NO"]) ? $arrGroupDetail["BENEF_ADDRESS_1_ADDR_NO"] : null);
					if(isset($arrGroupDetail["BENEF_ADDRESS_1_PROVINE_ID"]) && $arrGroupDetail["BENEF_ADDRESS_1_PROVINE_ID"] == '10'){
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_1_VILLAGE_NO"]) ? ' ม.'.$arrGroupDetail["BENEF_ADDRESS_1_VILLAGE_NO"] : null);
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_1_SOI"]) ? ' ซอย'.$arrGroupDetail["BENEF_ADDRESS_1_SOI"] : null);
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_1_ROAD"]) ? ' ถนน'.$arrGroupDetail["BENEF_ADDRESS_1_ROAD"] : null);
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_1_TAMBOL_CODE"]) ? ' แขวง'.$arrGroupDetail["BENEF_ADDRESS_1_TAMBOL_CODE"] : null);
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_1_DISTRICT_CODE"]) ? ' เขต'.$arrGroupDetail["BENEF_ADDRESS_1_DISTRICT_CODE"] : null);
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_1_PROVINE_CODE"]) ? ' '.$arrGroupDetail["BENEF_ADDRESS_1_PROVINE_CODE"] : null);
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_1_POSTCODE"]) ? ' '.$arrGroupDetail["BENEF_ADDRESS_1_POSTCODE"] : null);
					}else{
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_1_VILLAGE_NO"]) ? ' ม.'.$arrGroupDetail["BENEF_ADDRESS_1_VILLAGE_NO"] : null);
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_1_SOI"]) ? ' ซอย'.$arrGroupDetail["BENEF_ADDRESS_1_SOI"] : null);
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_1_ROAD"]) ? ' ถนน'.$arrGroupDetail["BENEF_ADDRESS_1_ROAD"] : null);
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_1_TAMBOL_CODE"]) ? ' ต.'.$arrGroupDetail["BENEF_ADDRESS_1_TAMBOL_CODE"] : null);
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_1_DISTRICT_CODE"]) ? ' อ.'.$arrGroupDetail["BENEF_ADDRESS_1_DISTRICT_CODE"] : null);
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_1_PROVINE_CODE"]) ? ' จ.'.$arrGroupDetail["BENEF_ADDRESS_1_PROVINE_CODE"] : null);
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_1_POSTCODE"]) ? ' '.$arrGroupDetail["BENEF_ADDRESS_1_POSTCODE"] : null);
					}
					$arrRegisterSubData = array();
					$arrRegisterSubData["LABEL"] = "ที่อยู่";
					$arrRegisterSubData["VALUE"] = $address;
					$arrRegisterData[] = $arrRegisterSubData;
					$arrRegisterSubData = array();
					$arrRegisterSubData["LABEL"] = "โทรศัพท์มือถือ";
					$arrRegisterSubData["VALUE"] = $arrGroupDetail["BENEF_PHONE_1"];
					$arrRegisterData[] = $arrRegisterSubData;
					$arrRegisterSubData = array();
					$arrRegisterSubData["LABEL"] = "ความสัมพันธ์";
					$arrRegisterSubData["VALUE"] = $arrGroupDetail["BENEF_RELATION_1"];
					$arrRegisterData[] = $arrRegisterSubData;
					$arrRegister1["SUB_DATA"][] = $arrRegisterData;
					//group 2
					$arrRegisterData = array();
					$arrRegisterSubData = array();
					$arrRegisterSubData["LABEL"] = "ชื่อ - นามสกุล ผู้รับผลประโยชน์ 2.";
					$arrRegisterSubData["VALUE"] = $arrGroupDetail["BENEF_NAME_2"];
					$arrRegisterData[] = $arrRegisterSubData;
					$arrRegisterSubData = array();
					$arrRegisterSubData["LABEL"] = "อายุ";
					$arrRegisterSubData["VALUE"] = $arrGroupDetail["BENEF_AGE_2"];
					$arrRegisterData[] = $arrRegisterSubData;
					$address = (isset($arrGroupDetail["BENEF_ADDRESS_2_ADDR_NO"]) ? $arrGroupDetail["BENEF_ADDRESS_2_ADDR_NO"] : null);
					if(isset($arrGroupDetail["BENEF_ADDRESS_2_PROVINE_ID"]) && $arrGroupDetail["BENEF_ADDRESS_2_PROVINE_ID"] == '10'){
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_2_VILLAGE_NO"]) ? ' ม.'.$arrGroupDetail["BENEF_ADDRESS_2_VILLAGE_NO"] : null);
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_2_SOI"]) ? ' ซอย'.$arrGroupDetail["BENEF_ADDRESS_2_SOI"] : null);
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_2_ROAD"]) ? ' ถนน'.$arrGroupDetail["BENEF_ADDRESS_2_ROAD"] : null);
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_2_TAMBOL_CODE"]) ? ' แขวง'.$arrGroupDetail["BENEF_ADDRESS_2_TAMBOL_CODE"] : null);
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_2_DISTRICT_CODE"]) ? ' เขต'.$arrGroupDetail["BENEF_ADDRESS_2_DISTRICT_CODE"] : null);
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_2_PROVINE_CODE"]) ? ' '.$arrGroupDetail["BENEF_ADDRESS_2_PROVINE_CODE"] : null);
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_2_POSTCODE"]) ? ' '.$arrGroupDetail["BENEF_ADDRESS_2_POSTCODE"] : null);
					}else{
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_2_VILLAGE_NO"]) ? ' ม.'.$arrGroupDetail["BENEF_ADDRESS_2_VILLAGE_NO"] : null);
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_2_SOI"]) ? ' ซอย'.$arrGroupDetail["BENEF_ADDRESS_2_SOI"] : null);
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_2_ROAD"]) ? ' ถนน'.$arrGroupDetail["BENEF_ADDRESS_2_ROAD"] : null);
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_2_TAMBOL_CODE"]) ? ' ต.'.$arrGroupDetail["BENEF_ADDRESS_2_TAMBOL_CODE"] : null);
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_2_DISTRICT_CODE"]) ? ' อ.'.$arrGroupDetail["BENEF_ADDRESS_2_DISTRICT_CODE"] : null);
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_2_PROVINE_CODE"]) ? ' จ.'.$arrGroupDetail["BENEF_ADDRESS_2_PROVINE_CODE"] : null);
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_2_POSTCODE"]) ? ' '.$arrGroupDetail["BENEF_ADDRESS_2_POSTCODE"] : null);
					}
					$arrRegisterSubData = array();
					$arrRegisterSubData["LABEL"] = "ที่อยู่";
					$arrRegisterSubData["VALUE"] = $address;
					$arrRegisterData[] = $arrRegisterSubData;
					$arrRegisterSubData = array();
					$arrRegisterSubData["LABEL"] = "โทรศัพท์มือถือ";
					$arrRegisterSubData["VALUE"] = $arrGroupDetail["BENEF_PHONE_2"];
					$arrRegisterData[] = $arrRegisterSubData;
					$arrRegisterSubData = array();
					$arrRegisterSubData["LABEL"] = "ความสัมพันธ์";
					$arrRegisterSubData["VALUE"] = $arrGroupDetail["BENEF_RELATION_2"];
					$arrRegisterData[] = $arrRegisterSubData;
					$arrRegister1["SUB_DATA"][] = $arrRegisterData;
					//group 3
					$arrRegisterData = array();
					$arrRegisterSubData = array();
					$arrRegisterSubData["LABEL"] = "ชื่อ - นามสกุล ผู้รับผลประโยชน์ 3.";
					$arrRegisterSubData["VALUE"] = $arrGroupDetail["BENEF_NAME_3"];
					$arrRegisterData[] = $arrRegisterSubData;
					$arrRegisterSubData = array();
					$arrRegisterSubData["LABEL"] = "อายุ";
					$arrRegisterSubData["VALUE"] = $arrGroupDetail["BENEF_AGE_3"];
					$arrRegisterData[] = $arrRegisterSubData;
					$address = (isset($arrGroupDetail["BENEF_ADDRESS_3_ADDR_NO"]) ? $arrGroupDetail["BENEF_ADDRESS_3_ADDR_NO"] : null);
					if(isset($arrGroupDetail["BENEF_ADDRESS_3_PROVINE_ID"]) && $arrGroupDetail["BENEF_ADDRESS_3_PROVINE_ID"] == '10'){
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_3_VILLAGE_NO"]) ? ' ม.'.$arrGroupDetail["BENEF_ADDRESS_3_VILLAGE_NO"] : null);
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_3_SOI"]) ? ' ซอย'.$arrGroupDetail["BENEF_ADDRESS_3_SOI"] : null);
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_3_ROAD"]) ? ' ถนน'.$arrGroupDetail["BENEF_ADDRESS_3_ROAD"] : null);
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_3_TAMBOL_CODE"]) ? ' แขวง'.$arrGroupDetail["BENEF_ADDRESS_3_TAMBOL_CODE"] : null);
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_3_DISTRICT_CODE"]) ? ' เขต'.$arrGroupDetail["BENEF_ADDRESS_3_DISTRICT_CODE"] : null);
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_3_PROVINE_CODE"]) ? ' '.$arrGroupDetail["BENEF_ADDRESS_3_PROVINE_CODE"] : null);
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_3_POSTCODE"]) ? ' '.$arrGroupDetail["BENEF_ADDRESS_3_POSTCODE"] : null);
					}else{
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_3_VILLAGE_NO"]) ? ' ม.'.$arrGroupDetail["BENEF_ADDRESS_3_VILLAGE_NO"] : null);
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_3_SOI"]) ? ' ซอย'.$arrGroupDetail["BENEF_ADDRESS_3_SOI"] : null);
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_3_ROAD"]) ? ' ถนน'.$arrGroupDetail["BENEF_ADDRESS_3_ROAD"] : null);
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_3_TAMBOL_CODE"]) ? ' ต.'.$arrGroupDetail["BENEF_ADDRESS_3_TAMBOL_CODE"] : null);
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_3_DISTRICT_CODE"]) ? ' อ.'.$arrGroupDetail["BENEF_ADDRESS_3_DISTRICT_CODE"] : null);
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_3_PROVINE_CODE"]) ? ' จ.'.$arrGroupDetail["BENEF_ADDRESS_3_PROVINE_CODE"] : null);
						$address .= (isset($arrGroupDetail["BENEF_ADDRESS_3_POSTCODE"]) ? ' '.$arrGroupDetail["BENEF_ADDRESS_3_POSTCODE"] : null);
					}
					$arrRegisterSubData = array();
					$arrRegisterSubData["LABEL"] = "ที่อยู่";
					$arrRegisterSubData["VALUE"] = $address;
					$arrRegisterData[] = $arrRegisterSubData;
					$arrRegisterSubData = array();
					$arrRegisterSubData["LABEL"] = "โทรศัพท์มือถือ";
					$arrRegisterSubData["VALUE"] = $arrGroupDetail["BENEF_PHONE_3"];
					$arrRegisterData[] = $arrRegisterSubData;
					$arrRegisterSubData = array();
					$arrRegisterSubData["LABEL"] = "ความสัมพันธ์";
					$arrRegisterSubData["VALUE"] = $arrGroupDetail["BENEF_RELATION_3"];
					$arrRegisterData[] = $arrRegisterSubData;
					$arrRegister1["SUB_DATA"][] = $arrRegisterData;
					//push data
					$arrRegister[] = $arrRegister1;
					
					$arrayResult['REGISTER_FORM_DATA'] = $arrRegister;

					if ($dataComing["is_confirm"]) {
						$getDocSystemPrefix = $conmysql->prepare("SELECT prefix_docno FROM docsystemprefix WHERE menu_component = :menu_component and is_use = '1'");
						$getDocSystemPrefix->execute([':menu_component' => 'RRGT']);

						$reqdoc_no = null;
						if ($getDocSystemPrefix->rowCount() > 0) {
							$rowDocPrefix = $getDocSystemPrefix->fetch(PDO::FETCH_ASSOC);
							$arrPrefixRaw = $func->PrefixGenerate($rowDocPrefix["prefix_docno"]);
							$arrPrefixSort = explode(',', $rowDocPrefix["prefix_docno"]);
							foreach ($arrPrefixSort as $prefix) {
								$reqdoc_no .= $arrPrefixRaw[$prefix];
							}
						}

						if (isset($reqdoc_no) && $reqdoc_no != "") {
							$getControlDoc = $conmysql->prepare("SELECT docgrp_no FROM docgroupcontrol WHERE is_use = '1' and menu_component = :menu_component");
							$getControlDoc->execute([':menu_component' => 'RRGT']);
							$rowConDoc = $getControlDoc->fetch(PDO::FETCH_ASSOC);

							$pathFile = $config["URL_SERVICE"] . '/resource/pdf/req_document/' . $reqdoc_no . '.pdf?v=' . time();
							$conmysql->beginTransaction();
							$InsertFormOnline = $conmysql->prepare("INSERT INTO gcreqdoconline(reqdoc_no, member_no, documenttype_code, form_value, document_url) 
																VALUES (:reqdoc_no, :member_no, :documenttype_code, :form_value,:document_url)");
							if ($InsertFormOnline->execute([
								':reqdoc_no' => $reqdoc_no,
								':member_no' => $emp_no,
								':documenttype_code' => 'RRGT',
								':form_value' => json_encode($dataComing["form_value_root_"]),
								':document_url' => $pathFile,
							])) {
								$arrGroupDetail["REQDOC_NO"] = $reqdoc_no;

								/*include('form_request_document_RRGT.php');
								$arrayPDF = GenerateReport($arrGroupDetail,$lib);
								if($arrayPDF["RESULT"]){*/
								if (true) {
									//$arrayResult['REPORT_URL'] = $config["URL_SERVICE"].$arrayPDF["PATH"];

									$insertDocMaster = $conmysql->prepare("INSERT INTO doclistmaster(doc_no,docgrp_no,doc_filename,doc_type,doc_address,member_no)
																			VALUES(:doc_no,:docgrp_no,:doc_filename,'pdf',:doc_address,:member_no)");
									$insertDocMaster->execute([
										':doc_no' => $reqdoc_no,
										':docgrp_no' => $rowConDoc["docgrp_no"],
										':doc_filename' => $reqdoc_no,
										':doc_address' => $pathFile,
										':member_no' => $emp_no,
									]);
									$insertDocList = $conmysql->prepare("INSERT INTO doclistdetail(doc_no,member_no,new_filename,id_userlogin)
																			VALUES(:doc_no,:member_no,:file_name,:id_userlogin)");
									$insertDocList->execute([
										':doc_no' => $reqdoc_no,
										':member_no' => $emp_no,
										':file_name' => $reqdoc_no . '.pdf',
										':id_userlogin' => $payload["id_userlogin"]
									]);

									//insert register
									$selectMaxCandidate = $conoracle->prepare("select max(can_no)+1 as max_docno from mem_t_candidate where br_no = :br_no");
									$selectMaxCandidate->execute([
										':br_no' => $arrGroupDetail["MEMBER_BRANCH"]
									]);
									$rowMaxCandidate = $selectMaxCandidate->fetch(PDO::FETCH_ASSOC);
									$docNo = $rowMaxCandidate["MAX_DOCNO"];
									$conoracle->beginTransaction();

									$insertMTCandidate = $conoracle->prepare("INSERT INTO mem_t_candidate 
                                        (can_no,br_no,ptitle_id,can_fname,can_lname,memtype_id,can_dmybirth,sex,blo_group,father,mother,marriage_status,
                                        address,moo_addr,tumbol,tanon,soi,district_id,province_id,zip_code,mobile_tel,can_id_card,district_card_id,
                                        province_card_id,card_date,end_date,
                                        status_id,division_id,location_id,section_id,subsection_id,rec_date,can_accept_flg,
                                        mem_usrid,flg_online,mem_pass,dmy_tried,tried_cause_id,
                                        shr_no,shr_qty,shr_first_bth,shr_incv,shr_incv_bth,pay_type,paymonth_amt,flag_pay,
                                        can_comission,mem_pic,mem_sign,can_pay_date,shr_old,can_time,can_monno,can_year,can_com_wel,
                                        can_mem_id,shr_sum_old,mem_date,shr_qty_sen,in_date,update_flg,relig_id,sum_child,sum_man,sum_woman,age,address2,moo_addr2,
                                        tumbol2,tanon2,soi2,district_id2,province_id2,zipcode2,house_tel2,mobile_tel2,name_moo,name_moo2,status_cardid) 
                                        VALUES (:can_no,:br_no,:ptitle_id,:can_fname,:can_lname,'1',TO_DATE(:can_dmybirth,'yyyy-mm-dd HH24:mi:ss'),:sex,'ไม่ระบุ',:father,:mother,:marriage_status,
                                        :address,:moo_addr,:tumbol,:tanon,:soi,:district_id,:province_id,:zip_code,:mobile_tel,:can_id_card,:district_card_id,
                                        :province_card_id,TO_DATE('01/02/2000','dd/MM/yyyy HH24:mi:ss'),TO_DATE('04/05/2003','dd/MM/yyyy HH24:mi:ss'),
                                        '01','001',NULL,NULL,NULL,SYSDATE,'3',
                                        'APP01','0',NULL,NULL,NULL,
                                        '01',:shr_qty,:shr_first_bth,:shr_incv,:shr_incv_bth,NULL,NULL,NULL,
                                        50.00,'','',SYSDATE,NULL,'1',12,2022,NULL,
                                        NULL,NULL,NULL,0,NULL,NULL,:relig_id,:sum_child,:sum_man,:sum_woman,:age,:address2,:moo_addr2,
                                        :tumbol2,:tanon2,:soi2,:district_id2,:province_id2,:zipcode2,:house_tel2,:mobile_tel2,'-','-','0')
									");
									if ($insertMTCandidate->execute([
										':can_no' => $docNo,
										':br_no' => $arrGroupDetail["MEMBER_BRANCH"],
										':ptitle_id' => $arrGroupDetail["MEMBER_PRENAME"],
										':can_fname' => $arrGroupDetail["MEMBER_NAME"],
										':can_lname' => $arrGroupDetail["MEMBER_SURNAME"],
										':can_dmybirth' => $arrGroupDetail["MEMBER_BIRTHDATE"],
										':sex' => $arrGroupDetail["GEN_OPTION"],
										':father' => $arrGroupDetail["DAD_NAME"],
										':mother' => $arrGroupDetail["MOM_NAME"],
										':marriage_status' => $arrGroupDetail["STATUS_OPTION"],
										':address' => $arrGroupDetail["ADDRESS_ADDR_NO"],
										':moo_addr' => $arrGroupDetail["ADDRESS_VILLAGE_NO"],
										':tumbol' => $arrGroupDetail["ADDRESS_TAMBOL_CODE"],
										':tanon' => $arrGroupDetail["ADDRESS_ROAD"],
										':soi' => $arrGroupDetail["ADDRESS_SOI"],
										':district_id' => $arrGroupDetail["ADDRESS_DISTRICT_ID"],
										':province_id' => $arrGroupDetail["ADDRESS_PROVINE_ID"],
										':zip_code' => $arrGroupDetail["ADDRESS_POSTCODE"],
										':mobile_tel' => $arrGroupDetail["MEMBER_MOBILEPHONE"],
										':can_id_card' => $arrGroupDetail["MEMBER_CARDID"],
										':district_card_id' => $arrGroupDetail["MEMBER_CARDID_DISTRICT_ID"],
										':province_card_id' =>  $arrGroupDetail["MEMBER_CARDID_PROVINE_ID"],
										':shr_qty' => ($arrGroupDetail["SHARE_FIRST_PAYMENT"] ?? 0) / 10,
										':shr_first_bth' => ($arrGroupDetail["SHARE_FIRST_PAYMENT"] ?? 0),
										':shr_incv' => ($arrGroupDetail["SHARE_PERIOD_PAYMENT"] ?? 0) / 10,
										':shr_incv_bth' => ($arrGroupDetail["SHARE_PERIOD_PAYMENT"] ?? 0),
										':relig_id' => $arrGroupDetail["MEMBER_RELIG"],
										':sum_child' => $arrGroupDetail["CHILD"],
										':sum_man' => $arrGroupDetail["CHILD_MALE"],
										':sum_woman' => $arrGroupDetail["CHILD_FEMALE"],
										':age' => $lib->count_duration($arrGroupDetail["MEMBER_BIRTHDATE"],"y"),
										'address2' => $arrGroupDetail["ADDRESS2_ADDR_NO"],
										':moo_addr2' => $arrGroupDetail["ADDRESS2_VILLAGE_NO"],
										':tumbol2' => $arrGroupDetail["ADDRESS2_TAMBOL_CODE"],
										':tanon2' => $arrGroupDetail["ADDRESS2_ROAD"],
										':soi2' => $arrGroupDetail["ADDRESS2_SOI"],
										':district_id2' => $arrGroupDetail["ADDRESS2_DISTRICT_ID"],
										':province_id2' => $arrGroupDetail["ADDRESS2_PROVINE_ID"],
										':zipcode2' => $arrGroupDetail["ADDRESS2_POSTCODE"],
										':house_tel2' => "",
										':mobile_tel2' => $arrGroupDetail["MEMBER_MOBILEPHONE"],
									])) {
										//insert mem_t_office
										$insertMTOffice = $conoracle->prepare("INSERT INTO mem_t_office (can_no,br_no,linepost_id,salary) VALUES (:can_no,:br_no,:linepost_id,:salary)");
										if ($insertMTOffice->execute([
											':can_no' => $docNo,
											':br_no' => $arrGroupDetail["MEMBER_BRANCH"],
											':linepost_id' => $arrGroupDetail["MEMBER_CAREER"],
											':salary' => $arrGroupDetail["MEMBER_SARALY"],
										])) {
											//insert mem_t_spouse
											if (isset($arrGroupDetail["SPOUSE_NAME"])) {
												$insertMTSpouse = $conoracle->prepare("INSERT INTO mem_t_spouse (can_no,br_no,spouse_no,spouse_name,id_card,child_num) VALUES (:can_no,:br_no,:spouse_no,:spouse_name,:id_card,:child_num)");
												if ($insertMTSpouse->execute([
													':can_no' => $docNo,
													':br_no' => $arrGroupDetail["MEMBER_BRANCH"],
													':spouse_no' => 1,
													':spouse_name' => $arrGroupDetail["SPOUSE_NAME"],
													':id_card' => str_replace("-","",$arrGroupDetail["SPOUSE_CARDID"]),
													':child_num' => $arrGroupDetail["CHILD"]
												])) {
												} else {
													$conoracle->rollback();
													$conmysql->rollback();
													$filename = basename(__FILE__, '.php');
													$logStruc = [
														":error_menu" => $filename,
														":error_code" => "WS1036",
														":error_desc" => "สมัครสมาชิกสหกรณ์ไม่ได้เพราะ Insert ลงตาราง mem_t_spouse ไม่ได้" . "\n" . "Query => " . $insertMTSpouse->queryString . "\n" . "Param => " . json_encode([
															':reqdoc_no' => $reqdoc_no,
															':member_no' => $emp_no,
															':documenttype_code' => 'RRGT',
															':form_value' => json_encode($dataComing["form_value_root_"]),
															':document_url' => $pathFile,
														]),
														":error_device" => $dataComing["channel"] . ' - ' . $dataComing["unique_id"] . ' on V.' . $dataComing["app_version"]
													];
													$log->writeLog('errorusage', $logStruc);
													$message_error = "สมัครสมาชิกสหกรณ์ไม่ได้เพราะ Insert ลง mem_t_spouse ไม่ได้" . "\n" . "Query => " . $insertMTSpouse->queryString . "\n" . "Param => " . json_encode([
														':reqdoc_no' => $reqdoc_no,
														':member_no' => $emp_no,
														':documenttype_code' => 'RRGT',
														':form_value' => json_encode($dataComing["form_value_root_"]),
														':document_url' => $pathFile,
													]);
													$lib->sendLineNotify($message_error);
													$arrayResult['RESPONSE_CODE'] = "WS0044";
													$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
													$arrayResult['RESULT'] = FALSE;
													$arrayResult['insertMTSpouse'] = [
														':can_no' => $docNo,
														':br_no' => '121',
														':spouse_no' => 1,
														':spouse_name' => $arrGroupDetail["SPOUSE_NAME"],
														':id_card' => str_replace("-","",$arrGroupDetail["SPOUSE_CARDID"]),
														':child_num' => $arrGroupDetail["CHILD"]
													];
													require_once('../../include/exit_footer.php');
												}
											}
											//insert mem_t_useful
											if (isset($arrGroupDetail["BENEF_NAME_1"])) {
												$insertMTUseful = $conoracle->prepare("INSERT INTO mem_t_useful (can_no,br_no,uf_seq,uf_name,age,home_no,moo,soi,road,tambol,district_id,province_id,post_code,tel,relation,recdate) 
											VALUES (:can_no,:br_no,:uf_seq,:uf_name,:age,:home_no,:moo,:soi,:road,:tambol,:district_id,:province_id,:post_code,:tel,:relation,TO_DATE(SYSDATE,'dd/MM/yyyy HH24:mi:ss'))");
												if ($insertMTUseful->execute([
													':can_no' => $docNo,
													':br_no' => $arrGroupDetail["MEMBER_BRANCH"],
													':uf_seq' => 1,
													':uf_name' => $arrGroupDetail["BENEF_NAME_1"],
													':age' => $arrGroupDetail["BENEF_AGE_1"],
													':home_no' => $arrGroupDetail["BENEF_ADDRESS_1_ADDR_NO"],
													':moo' => $arrGroupDetail["BENEF_ADDRESS_1_VILLAGE_NO"],
													':soi' => $arrGroupDetail["BENEF_ADDRESS_1_SOI"],
													':road' => $arrGroupDetail["BENEF_ADDRESS_1_ROAD"],
													':tambol' => $arrGroupDetail["BENEF_ADDRESS_1_TAMBOL_CODE"],
													':district_id' => $arrGroupDetail["BENEF_ADDRESS_1_DISTRICT_ID"],
													':province_id' => $arrGroupDetail["BENEF_ADDRESS_1_PROVINE_ID"],
													':post_code' => $arrGroupDetail["BENEF_ADDRESS_1_POST_CODE"],
													':tel' => $arrGroupDetail["BENEF_PHONE_1"],
													':relation' => $arrGroupDetail["BENEF_RELATION_1"],
												])) {
												} else {
													$conoracle->rollback();
													$conmysql->rollback();
													$filename = basename(__FILE__, '.php');
													$logStruc = [
														":error_menu" => $filename,
														":error_code" => "WS1036",
														":error_desc" => "สมัครสมาชิกสหกรณ์ไม่ได้เพราะ Insert ลงตาราง mem_t_useful 1 ไม่ได้" . "\n" . "Query => " . $insertMTUseful->queryString . "\n" . "Param => " . json_encode([
															':can_no' => $docNo,
															':br_no' => $arrGroupDetail["MEMBER_BRANCH"],
															':uf_seq' => 1,
															':uf_name' => $arrGroupDetail["BENEF_NAME_1"],
															':age' => $arrGroupDetail["BENEF_AGE_1"],
															':home_no' => $arrGroupDetail["BENEF_ADDRESS_1_ADDR_NO"],
															':moo' => $arrGroupDetail["BENEF_ADDRESS_1_VILLAGE_NO"],
															':soi' => $arrGroupDetail["BENEF_ADDRESS_1_SOI"],
															':road' => $arrGroupDetail["BENEF_ADDRESS_1_ROAD"],
															':tambol' => $arrGroupDetail["BENEF_ADDRESS_1_TAMBOL_CODE"],
															':district_id' => $arrGroupDetail["BENEF_ADDRESS_1_DISTRICT_ID"],
															':province_id' => $arrGroupDetail["BENEF_ADDRESS_1_PROVINE_ID"],
															':post_code' => $arrGroupDetail["BENEF_ADDRESS_1_POST_CODE"],
															':tel' => $arrGroupDetail["BENEF_PHONE_1"],
															':relation' => $arrGroupDetail["BENEF_RELATION_1"],
														]),
														":error_device" => $dataComing["channel"] . ' - ' . $dataComing["unique_id"] . ' on V.' . $dataComing["app_version"]
													];
													$log->writeLog('errorusage', $logStruc);
													$message_error = "สมัครสมาชิกสหกรณ์ไม่ได้เพราะ Insert ลง mem_t_useful ไม่ได้" . "\n" . "Query => " . $insertMTUseful->queryString . "\n" . "Param => " . json_encode([
														':reqdoc_no' => $reqdoc_no,
														':member_no' => $emp_no,
														':documenttype_code' => 'RRGT',
														':form_value' => json_encode($dataComing["form_value_root_"]),
														':document_url' => $pathFile,
													]);
													$lib->sendLineNotify($message_error);
													$arrayResult['RESPONSE_CODE'] = "WS0044";
													$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
													$arrayResult['RESULT'] = FALSE;
													require_once('../../include/exit_footer.php');
												}
											}
											//insert mem_t_useful 2
											if (isset($arrGroupDetail["BENEF_NAME_2"])) {
												$insertMTUseful = $conoracle->prepare("INSERT INTO mem_t_useful (can_no,br_no,uf_seq,uf_name,age,home_no,moo,soi,road,tambol,district_id,province_id,post_code,tel,relation,recdate) 
											VALUES (:can_no,:br_no,:uf_seq,:uf_name,:age,:home_no,:moo,:soi,:road,:tambol,:district_id,:province_id,:post_code,:tel,:relation,TO_DATE(SYSDATE,'dd/MM/yyyy HH24:mi:ss'))");
												if ($insertMTUseful->execute([
													':can_no' => $docNo,
													':br_no' => $arrGroupDetail["MEMBER_BRANCH"],
													':uf_seq' => 2,
													':uf_name' => $arrGroupDetail["BENEF_NAME_2"],
													':age' => $arrGroupDetail["BENEF_AGE_2"],
													':home_no' => $arrGroupDetail["BENEF_ADDRESS_2_ADDR_NO"],
													':moo' => $arrGroupDetail["BENEF_ADDRESS_2_VILLAGE_NO"],
													':soi' => $arrGroupDetail["BENEF_ADDRESS_2_SOI"],
													':road' => $arrGroupDetail["BENEF_ADDRESS_2_ROAD"],
													':tambol' => $arrGroupDetail["BENEF_ADDRESS_2_TAMBOL_CODE"],
													':district_id' => $arrGroupDetail["BENEF_ADDRESS_2_DISTRICT_ID"],
													':province_id' => $arrGroupDetail["BENEF_ADDRESS_2_PROVINE_ID"],
													':post_code' => $arrGroupDetail["BENEF_ADDRESS_2_POST_CODE"],
													':tel' => $arrGroupDetail["BENEF_PHONE_2"],
													':relation' => $arrGroupDetail["BENEF_RELATION_2"],
												])) {
												} else {
													$conoracle->rollback();
													$conmysql->rollback();
													$filename = basename(__FILE__, '.php');
													$logStruc = [
														":error_menu" => $filename,
														":error_code" => "WS1036",
														":error_desc" => "สมัครสมาชิกสหกรณ์ไม่ได้เพราะ Insert ลงตาราง mem_t_useful 2 ไม่ได้" . "\n" . "Query => " . $insertMTUseful->queryString . "\n" . "Param => " . json_encode([
															':can_no' => $docNo,
															':br_no' => $arrGroupDetail["MEMBER_BRANCH"],
															':uf_seq' => 2,
															':uf_name' => $arrGroupDetail["BENEF_NAME_2"],
															':age' => $arrGroupDetail["BENEF_AGE_2"],
															':home_no' => $arrGroupDetail["BENEF_ADDRESS_2_ADDR_NO"],
															':moo' => $arrGroupDetail["BENEF_ADDRESS_2_VILLAGE_NO"],
															':soi' => $arrGroupDetail["BENEF_ADDRESS_2_SOI"],
															':road' => $arrGroupDetail["BENEF_ADDRESS_2_ROAD"],
															':tambol' => $arrGroupDetail["BENEF_ADDRESS_2_TAMBOL_CODE"],
															':district_id' => $arrGroupDetail["BENEF_ADDRESS_2_DISTRICT_ID"],
															':province_id' => $arrGroupDetail["BENEF_ADDRESS_2_PROVINE_ID"],
															':post_code' => $arrGroupDetail["BENEF_ADDRESS_2_POST_CODE"],
															':tel' => $arrGroupDetail["BENEF_PHONE_2"],
															':relation' => $arrGroupDetail["BENEF_RELATION_2"],
														]),
														":error_device" => $dataComing["channel"] . ' - ' . $dataComing["unique_id"] . ' on V.' . $dataComing["app_version"]
													];
													$log->writeLog('errorusage', $logStruc);
													$message_error = "สมัครสมาชิกสหกรณ์ไม่ได้เพราะ Insert ลง mem_t_useful ไม่ได้" . "\n" . "Query => " . $insertMTUseful->queryString . "\n" . "Param => " . json_encode([
														':reqdoc_no' => $reqdoc_no,
														':member_no' => $emp_no,
														':documenttype_code' => 'RRGT',
														':form_value' => json_encode($dataComing["form_value_root_"]),
														':document_url' => $pathFile,
													]);
													$lib->sendLineNotify($message_error);
													$arrayResult['RESPONSE_CODE'] = "WS0044";
													$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
													$arrayResult['RESULT'] = FALSE;
													require_once('../../include/exit_footer.php');
												}
											}
											//insert mem_t_useful 3
											if (isset($arrGroupDetail["BENEF_NAME_3"])) {
												$insertMTUseful = $conoracle->prepare("INSERT INTO mem_t_useful (can_no,br_no,uf_seq,uf_name,age,home_no,moo,soi,road,tambol,district_id,province_id,post_code,tel,relation,recdate) 
											VALUES (:can_no,:br_no,:uf_seq,:uf_name,:age,:home_no,:moo,:soi,:road,:tambol,:district_id,:province_id,:post_code,:tel,:relation,TO_DATE(SYSDATE,'dd/MM/yyyy HH24:mi:ss'))");
												if ($insertMTUseful->execute([
													':can_no' => $docNo,
													':br_no' => $arrGroupDetail["MEMBER_BRANCH"],
													':uf_seq' => 3,
													':uf_name' => $arrGroupDetail["BENEF_NAME_3"],
													':age' => $arrGroupDetail["BENEF_AGE_3"],
													':home_no' => $arrGroupDetail["BENEF_ADDRESS_3_ADDR_NO"],
													':moo' => $arrGroupDetail["BENEF_ADDRESS_3_VILLAGE_NO"],
													':soi' => $arrGroupDetail["BENEF_ADDRESS_3_SOI"],
													':road' => $arrGroupDetail["BENEF_ADDRESS_3_ROAD"],
													':tambol' => $arrGroupDetail["BENEF_ADDRESS_3_TAMBOL_CODE"],
													':district_id' => $arrGroupDetail["BENEF_ADDRESS_3_DISTRICT_ID"],
													':province_id' => $arrGroupDetail["BENEF_ADDRESS_3_PROVINE_ID"],
													':post_code' => $arrGroupDetail["BENEF_ADDRESS_3_POST_CODE"],
													':tel' => $arrGroupDetail["BENEF_PHONE_3"],
													':relation' => $arrGroupDetail["BENEF_RELATION_3"],
												])) {
												} else {
													$conoracle->rollback();
													$conmysql->rollback();
													$filename = basename(__FILE__, '.php');
													$logStruc = [
														":error_menu" => $filename,
														":error_code" => "WS1036",
														":error_desc" => "สมัครสมาชิกสหกรณ์ไม่ได้เพราะ Insert ลงตาราง mem_t_useful 3 ไม่ได้" . "\n" . "Query => " . $insertMTUseful->queryString . "\n" . "Param => " . json_encode([
															':can_no' => $docNo,
															':br_no' => $arrGroupDetail["MEMBER_BRANCH"],
															':uf_seq' => 3,
															':uf_name' => $arrGroupDetail["BENEF_NAME_2"],
															':age' => $arrGroupDetail["BENEF_AGE_2"],
															':home_no' => $arrGroupDetail["BENEF_ADDRESS_2_ADDR_NO"],
															':moo' => $arrGroupDetail["BENEF_ADDRESS_2_VILLAGE_NO"],
															':soi' => $arrGroupDetail["BENEF_ADDRESS_2_SOI"],
															':road' => $arrGroupDetail["BENEF_ADDRESS_2_ROAD"],
															':tambol' => $arrGroupDetail["BENEF_ADDRESS_2_TAMBOL_CODE"],
															':district_id' => $arrGroupDetail["BENEF_ADDRESS_2_DISTRICT_ID"],
															':province_id' => $arrGroupDetail["BENEF_ADDRESS_2_PROVINE_ID"],
															':post_code' => $arrGroupDetail["BENEF_ADDRESS_2_POST_CODE"],
															':tel' => $arrGroupDetail["BENEF_PHONE_2"],
															':relation' => $arrGroupDetail["BENEF_RELATION_2"],
														]),
														":error_device" => $dataComing["channel"] . ' - ' . $dataComing["unique_id"] . ' on V.' . $dataComing["app_version"]
													];
													$log->writeLog('errorusage', $logStruc);
													$message_error = "สมัครสมาชิกสหกรณ์ไม่ได้เพราะ Insert ลง mem_t_useful ไม่ได้" . "\n" . "Query => " . $insertMTUseful->queryString . "\n" . "Param => " . json_encode([
														':reqdoc_no' => $reqdoc_no,
														':member_no' => $emp_no,
														':documenttype_code' => 'RRGT',
														':form_value' => json_encode($dataComing["form_value_root_"]),
														':document_url' => $pathFile,
													]);
													$lib->sendLineNotify($message_error);
													$arrayResult['RESPONSE_CODE'] = "WS0044";
													$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
													$arrayResult['RESULT'] = FALSE;
													require_once('../../include/exit_footer.php');
												}
											}
										} else {
											$conoracle->rollback();
											$conmysql->rollback();
											$filename = basename(__FILE__, '.php');
											$logStruc = [
												":error_menu" => $filename,
												":error_code" => "WS1036",
												":error_desc" => "สมัครสมาชิกสหกรณ์ไม่ได้เพราะ Insert ลงตาราง mem_t_office ไม่ได้" . "\n" . "Query => " . $insertMTOffice->queryString . "\n" . "Param => " . json_encode([
													':reqdoc_no' => $reqdoc_no,
													':member_no' => $emp_no,
													':documenttype_code' => 'RRGT',
													':form_value' => json_encode($dataComing["form_value_root_"]),
													':document_url' => $pathFile,
												]),
												":error_device" => $dataComing["channel"] . ' - ' . $dataComing["unique_id"] . ' on V.' . $dataComing["app_version"]
											];
											$log->writeLog('errorusage', $logStruc);
											$message_error = "สมัครสมาชิกสหกรณ์ไม่ได้เพราะ Insert ลง mem_t_office ไม่ได้" . "\n" . "Query => " . $insertMTOffice->queryString . "\n" . "Param => " . json_encode([
												':reqdoc_no' => $reqdoc_no,
												':member_no' => $emp_no,
												':documenttype_code' => 'RRGT',
												':form_value' => json_encode($dataComing["form_value_root_"]),
												':document_url' => $pathFile,
											]);
											$lib->sendLineNotify($message_error);
											$arrayResult['RESPONSE_CODE'] = "WS0044";
											$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
											$arrayResult['RESULT'] = FALSE;
											require_once('../../include/exit_footer.php');
										}
									} else {
										$conoracle->rollback();
										$conmysql->rollback();
										$filename = basename(__FILE__, '.php');
										$logStruc = [
											":error_menu" => $filename,
											":error_code" => "WS1036",
											":error_desc" => "สมัครสมาชิกสหกรณ์ไม่ได้เพราะ Insert ลงตาราง mem_t_candidate ไม่ได้" . "\n" . "Query => " . $insertMTCandidate->queryString . "\n" . "Param => " . json_encode([
												':reqdoc_no' => $reqdoc_no,
												':member_no' => $emp_no,
												':documenttype_code' => 'RRGT',
												':form_value' => json_encode($dataComing["form_value_root_"]),
												':document_url' => $pathFile,
											]),
											":error_device" => $dataComing["channel"] . ' - ' . $dataComing["unique_id"] . ' on V.' . $dataComing["app_version"]
										];
										$log->writeLog('errorusage', $logStruc);
										$message_error = "สมัครสมาชิกสหกรณ์ไม่ได้เพราะ Insert ลง mem_t_candidate ไม่ได้" . "\n" . "Query => " . $insertMTCandidate->queryString . "\n" . "Param => " . json_encode([
											':reqdoc_no' => $reqdoc_no,
											':member_no' => $emp_no,
											':documenttype_code' => 'RRGT',
											':form_value' => json_encode($dataComing["form_value_root_"]),
											':document_url' => $pathFile,
										]);
										$lib->sendLineNotify($message_error);
										$arrayResult['RESPONSE_CODE'] = "WS0044";
										$arrayResult['RESPONSE_MESSAGE'] = $insertMTCandidate->errorInfo();
										$arrayResult['RESULT'] = FALSE;
										
										$arrayResult['docNo'] = [
										':can_no' => $docNo,
										':ptitle_id' => $arrGroupDetail["MEMBER_PRENAME"],
										':can_fname' => $arrGroupDetail["MEMBER_NAME"],
										':can_lname' => $arrGroupDetail["MEMBER_SURNAME"],
										':can_dmybirth' => $arrGroupDetail["MEMBER_BIRTHDATE"],
										':sex' => $arrGroupDetail["GEN_OPTION"],
										':father' => $arrGroupDetail["DAD_NAME"],
										':mother' => $arrGroupDetail["MOM_NAME"],
										':marriage_status' => $arrGroupDetail["STATUS_OPTION"],
										':address' => $arrGroupDetail["ADDRESS_ADDR_NO"],
										':moo_addr' => $arrGroupDetail["ADDRESS_VILLAGE_NO"],
										':tumbol' => $arrGroupDetail["ADDRESS_TAMBOL_CODE"],
										':tanon' => $arrGroupDetail["ADDRESS_ROAD"],
										':soi' => $arrGroupDetail["ADDRESS_SOI"],
										':district_id' => $arrGroupDetail["ADDRESS_DISTRICT_ID"],
										':province_id' => $arrGroupDetail["ADDRESS_PROVINE_ID"],
										':zip_code' => $arrGroupDetail["ADDRESS_POSTCODE"],
										':mobile_tel' => $arrGroupDetail["MEMBER_MOBILEPHONE"],
										':can_id_card' => $arrGroupDetail["MEMBER_CARDID"],
										':district_card_id' => $arrGroupDetail["MEMBER_CARDID_DISTRICT_ID"],
										':province_card_id' =>  $arrGroupDetail["MEMBER_CARDID_PROVINE_ID"],
										':shr_qty' => ($arrGroupDetail["SHARE_FIRST_PAYMENT"] ?? 0) / 100,
										':shr_first_bth' => ($arrGroupDetail["SHARE_FIRST_PAYMENT"] ?? 0),
										':shr_incv' => ($arrGroupDetail["SHARE_PERIOD_PAYMENT"] ?? 0) / 100,
										':shr_incv_bth' => ($arrGroupDetail["SHARE_PERIOD_PAYMENT"] ?? 0),
										':relig_id' => $arrGroupDetail["MEMBER_RELIG"],
										':sum_child' => $arrGroupDetail["CHILD"],
										':sum_man' => $arrGroupDetail["CHILD_MALE"],
										':sum_woman' => $arrGroupDetail["CHILD_FEMALE"],
										':age' => $lib->count_duration($arrGroupDetail["MEMBER_BIRTHDATE"],"y"),
										'address2' => $arrGroupDetail["ADDRESS2_ADDR_NO"],
										':moo_addr2' => $arrGroupDetail["ADDRESS2_VILLAGE_NO"],
										':tumbol2' => $arrGroupDetail["ADDRESS2_TAMBOL_CODE"],
										':tanon2' => $arrGroupDetail["ADDRESS2_ROAD"],
										':soi2' => $arrGroupDetail["ADDRESS2_SOI"],
										':district_id2' => $arrGroupDetail["ADDRESS2_DISTRICT_ID"],
										':province_id2' => $arrGroupDetail["ADDRESS2_PROVINE_ID"],
										':zipcode2' => $arrGroupDetail["ADDRESS2_POSTCODE"],
										':house_tel2' => "",
										':mobile_tel2' => $arrGroupDetail["MEMBER_MOBILEPHONE"],
									];
									$arrayResult['datacoming'] = $dataComing;
										require_once('../../include/exit_footer.php');
									}

									$conoracle->commit();
									$conmysql->commit();

									$arrayResult['REQDOC_NO'] = $reqdoc_no;
									$arrayResult['RESULT'] = TRUE;
									require_once('../../include/exit_footer.php');
								} else {
									$conmysql->rollback();
									$filename = basename(__FILE__, '.php');
									$logStruc = [
										":error_menu" => $filename,
										":error_code" => "WS1036",
										":error_desc" => "สมัครสมาชิกสหกรณ์ไม่ได้เพราะ Insert ลงตาราง gcreqdoconline ไม่ได้" . "\n" . "Query => " . $insertMTCandidate->queryString . "\n" . "Param => " . json_encode([
											':reqdoc_no' => $reqdoc_no,
											':member_no' => $emp_no,
											':documenttype_code' => 'RRGT',
											':form_value' => json_encode($dataComing["form_value_root_"]),
											':document_url' => $pathFile,
										]),
										":error_device" => $dataComing["channel"] . ' - ' . $dataComing["unique_id"] . ' on V.' . $dataComing["app_version"]
									];
									$log->writeLog('errorusage', $logStruc);
									$message_error = "สร้างไฟล์ PDF ไม่ได้ " . $filename . "\n" . "DATA => " . json_encode($dataComing);
									$lib->sendLineNotify($message_error);
									$arrayResult['RESPONSE_CODE'] = "WS0044";
									$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
									$arrayResult['RESULT'] = FALSE;
									require_once('../../include/exit_footer.php');
								}
							} else {
								$conmysql->rollback();
								$filename = basename(__FILE__, '.php');
								$logStruc = [
									":error_menu" => $filename,
									":error_code" => "WS1036",
									":error_desc" => "สมัครสมาชิกสหกรณ์ไม่ได้เพราะ Insert ลงตาราง mem_t_candidate ไม่ได้" . "\n" . "Query => " . $InsertFormOnline->queryString . "\n" . "Param => " . json_encode([
										':reqdoc_no' => $reqdoc_no,
										':member_no' => $emp_no,
										':documenttype_code' => 'RRGT',
										':form_value' => json_encode($dataComing["form_value_root_"]),
										':document_url' => $pathFile,
									]),
									":error_device" => $dataComing["channel"] . ' - ' . $dataComing["unique_id"] . ' on V.' . $dataComing["app_version"]
								];
								$log->writeLog('errorusage', $logStruc);
								$message_error = "สมัครสมาชิกสหกรณ์ไม่ได้เพราะ Insert ลง gcreqdoconline ไม่ได้" . "\n" . "Query => " . $InsertFormOnline->queryString . "\n" . "Param => " . json_encode([
									':reqdoc_no' => $reqdoc_no,
									':member_no' => $emp_no,
									':documenttype_code' => 'RRGT',
									':form_value' => json_encode($dataComing["form_value_root_"]),
									':document_url' => $pathFile,
								]);
								$lib->sendLineNotify($message_error);
								$arrayResult['RESPONSE_CODE'] = "WS1036";
								$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
								$arrayResult['RESULT'] = FALSE;
								require_once('../../include/exit_footer.php');
							}
						} else {
							$filename = basename(__FILE__, '.php');
							$logStruc = [
								":error_menu" => $filename,
								":error_code" => "WS0063",
								":error_desc" => "เลขเอกสารเป็นค่าว่าง ไม่สามารถสร้างเลขเอกสารเป็นค่าว่างได้",
								":error_device" => $dataComing["channel"] . ' - ' . $dataComing["unique_id"] . ' on V.' . $dataComing["app_version"]
							];
							$log->writeLog('errorusage', $logStruc);
							$message_error = "เลขเอกสารเป็นค่าว่าง ไม่สามารถสร้างเลขเอกสารเป็นค่าว่างได้";
							$lib->sendLineNotify($message_error);
							$arrayResult['RESPONSE_CODE'] = "WS0063";
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
							$arrayResult['RESULT'] = FALSE;
							require_once('../../include/exit_footer.php');
						}
					} else {
						$arrayResult['REGISTER_DATA'] = $arrGroupDetail;
						$arrayResult['RESULT'] = TRUE;
						require_once('../../include/exit_footer.php');
					}
				} else {
					$arrayResult['RESPONSE_CODE'] = "";
					$arrayResult['RESPONSE_MESSAGE'] = "ท่านเป็นสมาชิกสหกรณ์แล้วจึงไม่สามารถสมัครซ้ำได้ กรุณาตรวจสอบรหัสพนักงานและลองใหม่อีกครั้ง";
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
				}
			} else {
				$arrayResult['RESPONSE_CODE'] = "";
				$arrayResult['RESPONSE_MESSAGE'] = "รหัสพนักงานหรือหมายเลขบัตรประชนไม่ถูกต้อง กรุณาตรวจสอบข้อมูลและลองใหม่อีกครั้ง";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		} else {
			$getReqDocumentApv = $conmysql->prepare("SELECT reqdoc_no, document_url, req_status FROM gcreqdoconline 
											WHERE documenttype_code = 'RRGT' AND member_no = :emp_no AND req_status IN('1')");
			$getReqDocumentApv->execute([':emp_no' => $emp_no]);
			$rowAPV = $getReqDocumentApv->fetch(PDO::FETCH_ASSOC);
			if (isset($rowAPV["req_status"]) && $rowAPV["req_status"] == "1") {
				$arrayResult['DOCUMMMENT_REQ'] = $arrDocReq;
				$arrayResult['RESPONSE_CODE'] = "";
				$arrayResult['RESPONSE_MESSAGE'] = "สหกรณ์ได้อนุมัติใบคำขอของท่านแล้ว สามารถตรวจสอบเลขสมาชิกได้จากหน้าจอเข้าสู่ระบบ หากมีคำถามเพิ่มเติมกรุณาติดต่อสหกรณ์";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			} else {
				$arrayResult['DOCUMMMENT_REQ'] = $arrDocReq;
				$arrayResult['RESPONSE_CODE'] = "";
				$arrayResult['RESPONSE_MESSAGE'] = "ท่านได้ส่งใบคำขอไปแล้วและอยู่ในระหว่างดำเนินการ หากมีคำถามเพิ่มเติมกรุณาติดต่อสหกรณ์";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		}
	}
} else {
	$filename = basename(__FILE__, '.php');
	$logStruc = [
		":error_menu" => $filename,
		":error_code" => "WS4004",
		":error_desc" => "ส่ง Argument มาไม่ครบ " . "\n" . json_encode($dataComing),
		":error_device" => $dataComing["channel"] . ' - ' . $dataComing["unique_id"] . ' on V.' . $dataComing["app_version"]
	];
	$log->writeLog('errorusage', $logStruc);
	$message_error = "ไฟล์ " . $filename . " ส่ง Argument มาไม่ครบมาแค่ " . "\n" . json_encode($dataComing);
	$lib->sendLineNotify($message_error);
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
}
