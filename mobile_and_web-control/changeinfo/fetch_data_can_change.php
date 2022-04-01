<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SettingMemberInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrayConst = array();
		$arrayDataGrp = array();
		$getConstChangeInfo = $conmysql->prepare("SELECT const_code,is_change FROM gcconstantchangeinfo");
		$getConstChangeInfo->execute();
		while($rowConst = $getConstChangeInfo->fetch(PDO::FETCH_ASSOC)){
			$arrayConst[$rowConst["const_code"]] = $rowConst["is_change"];
		}
		if($arrayConst["email"] == '1'){
			$getEmail = $conmysql->prepare("SELECT email FROM gcmemberaccount WHERE member_no = :member_no");
			$getEmail->execute([':member_no' => $payload["member_no"]]);
			$rowEmail = $getEmail->fetch(PDO::FETCH_ASSOC);
			$arrayDataGrp["EMAIL"] = $rowEmail["email"];
		}
		if($arrayConst["tel"] == '1'){
			$getPhone = $conmysql->prepare("SELECT phone_number FROM gcmemberaccount WHERE member_no = :member_no");
			$getPhone->execute([':member_no' => $payload["member_no"]]);
			$rowPhone = $getPhone->fetch(PDO::FETCH_ASSOC);
			$arrayDataGrp["PHONE_NUMBER"] = $rowPhone["phone_number"];
		}
		if($arrayConst["address"] == '1'){
			$getAddr = $conoracle->prepare("SELECT mb.CURRADDR_NO as ADDR_NO,
													mb.CURRADDR_MOO as ADDR_MOO,
													mb.CURRADDR_SOI as ADDR_SOI,
													mb.CURRADDR_VILLAGE as ADDR_VILLAGE,
													mb.CURRADDR_ROAD as ADDR_ROAD,
													MBT.TAMBOL_DESC AS TAMBOL_DESC,
													MBD.DISTRICT_DESC AS DISTRICT_DESC,
													MB.CURRPROVINCE_CODE AS PROVINCE_CODE,
													MBP.PROVINCE_DESC AS PROVINCE_DESC,
													MB.CURRADDR_POSTCODE AS ADDR_POSTCODE,
													mb.CURRAMPHUR_CODE AS DISTRICT_CODE,
													mb.CURRTAMBOL_CODE AS TAMBOL_CODE
													FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
													LEFT JOIN MBUCFMEMBGROUP mg ON mb.MEMBGROUP_CODE = mg.MEMBGROUP_CODE
													LEFT JOIN MBUCFMEMBTYPE mt ON mb.MEMBTYPE_CODE = mt.MEMBTYPE_CODE
													LEFT JOIN MBUCFTAMBOL MBT ON mb.CURRTAMBOL_CODE = MBT.TAMBOL_CODE
													LEFT JOIN MBUCFDISTRICT MBD ON mb.CURRAMPHUR_CODE = MBD.DISTRICT_CODE
													LEFT JOIN MBUCFPROVINCE MBP ON mb.CURRPROVINCE_CODE = MBP.PROVINCE_CODE
													WHERE mb.member_no = :member_no");
			$getAddr->execute([':member_no' => $member_no]);
			$rowAddr = $getAddr->fetch(PDO::FETCH_ASSOC);
			$arrAddress["ADDR_NO"] = $rowAddr["ADDR_NO"];
			$arrAddress["ADDR_MOO"] = $rowAddr["ADDR_MOO"];
			$arrAddress["ADDR_SOI"] = $rowAddr["ADDR_SOI"];
			$arrAddress["ADDR_VILLAGE"] = $rowAddr["ADDR_VILLAGE"];
			$arrAddress["ADDR_ROAD"] = $rowAddr["ADDR_ROAD"];
			$arrAddress["DISTRICT_CODE"] = $rowAddr["DISTRICT_CODE"];
			$arrAddress["ADDR_POSTCODE"] = $rowAddr["ADDR_POSTCODE"];
			$arrAddress["TAMBOL_CODE"] = $rowAddr["TAMBOL_CODE"];
			$arrAddress["PROVINCE_CODE"] = $rowAddr["PROVINCE_CODE"];
			$arrayDataGrp["CURR_ADDRESS"] = $arrAddress;
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
		}
		$arrayResult['IS_OTP'] = FALSE;
		$arrayResult['DATA'] = $arrayDataGrp;
		$arrayResult['EMAIL_CAN_CHANGE'] = $arrayConst["email"] == '1' ? TRUE : FALSE;
		$arrayResult['ADDRESS_CAN_CHANGE'] = $arrayConst["address"] == '1' ? TRUE : FALSE;
		$arrayResult['TEL_CAN_CHANGE'] = $arrayConst["tel"] == '1' ? TRUE : FALSE;
		$arrayResult['IS_OTP'] = FALSE;
		$arrayResult['IS_SMS'] = FALSE;
		$arrayResult['IS_EMAIL'] = FALSE;
		$arrayResult['DATA'] = $arrayDataGrp;
		$arrayResult['REQ_EMAIL'] = FALSE;
		$arrayResult['REQ_TEL'] = FALSE;
		$arrayResult['REQ_ADDRESS'] = FALSE;
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