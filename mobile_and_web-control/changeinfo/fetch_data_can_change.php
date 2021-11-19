<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SettingMemberInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrayConst = array();
		$arrayDataGrp = array();
		$getConstChangeInfo = $conmssql->prepare("SELECT const_code,is_change FROM gcconstantchangeinfo");
		$getConstChangeInfo->execute();
		while($rowConst = $getConstChangeInfo->fetch(PDO::FETCH_ASSOC)){
			$arrayConst[$rowConst["const_code"]] = $rowConst["is_change"];
		}
		if($arrayConst["email"] == '1'){
			$checkOldForEmail = $conmssql->prepare("SELECT COUNT(id_editdata) as C_FORMNOTAPPROVE FROM gcmembereditdata 
												WHERE member_no = :member_no and inputgroup_type = 'email' and is_updateoncore = '0'");
			$checkOldForEmail->execute([':member_no' => $payload["member_no"]]);
			$rowOldFormEmail = $checkOldForEmail->fetch(PDO::FETCH_ASSOC);
			if($rowOldFormEmail["C_FORMNOTAPPROVE"] > 0){
				$arrayResult['EMAIL_CAN_CHANGE'] = FALSE;
			}else{
				$getEmail = $conmssqlcoop->prepare("SELECT email FROM COCOOPTATION WHERE member_id = :member_no");
				$getEmail->execute([':member_no' => $member_no]);
				$rowEmail = $getEmail->fetch(PDO::FETCH_ASSOC);
				$arrayDataGrp["EMAIL"] = $rowEmail["email"];
				$arrayResult['EMAIL_CAN_CHANGE'] = TRUE;
			}
		}
		if($arrayConst["tel"] == '1'){
			$checkOldFormTel = $conmssql->prepare("SELECT COUNT(id_editdata) as C_FORMNOTAPPROVE FROM gcmembereditdata 
												WHERE member_no = :member_no and inputgroup_type = 'tel' and is_updateoncore = '0'");
			$checkOldFormTel->execute([':member_no' => $payload["member_no"]]);
			$rowOldFormTel = $checkOldFormTel->fetch(PDO::FETCH_ASSOC);
			if($rowOldFormTel["C_FORMNOTAPPROVE"] > 0){
				$arrayResult['TEL_CAN_CHANGE'] = FALSE;
			}else{
				$getPhone = $conmssqlcoop->prepare("SELECT telephone as phone_number FROM COCOOPTATION WHERE member_id = :member_no");
				$getPhone->execute([':member_no' => $member_no]);
				$rowPhone = $getPhone->fetch(PDO::FETCH_ASSOC);
				$arrayDataGrp["PHONE_NUMBER"] = $rowPhone["phone_number"];
				$arrayResult['TEL_CAN_CHANGE'] = TRUE;
			}
		}
		if($arrayConst["address"] == '1'){
			$checkOldFormAddress = $conmssql->prepare("SELECT COUNT(id_editdata) as C_FORMNOTAPPROVE FROM gcmembereditdata 
												WHERE member_no = :member_no and inputgroup_type = 'address' and is_updateoncore = '0'");
			$checkOldFormAddress->execute([':member_no' => $payload["member_no"]]);
			$rowOldFormAddress = $checkOldFormAddress->fetch(PDO::FETCH_ASSOC);
			if($rowOldFormAddress["C_FORMNOTAPPROVE"] > 0){
				$arrayResult['ADDRESS_CAN_CHANGE'] = FALSE;
			}else{
				$getAddress = $conmssqlcoop->prepare("SELECT ADDRESS1 FROM COCOOPTATION WHERE member_id = :member_no");
				$getAddress->execute([':member_no' => $member_no]);
				$rowAddress = $getAddress->fetch(PDO::FETCH_ASSOC);
				$arrAddress["FULL_ADDRESS"] = $rowAddress["ADDRESS1"];
				$arrayDataGrp["CURR_ADDRESS"] = $arrAddress;
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
				$arrayDataGeo["DISTRCIT_LIST"] = $arrAllDistrcit;
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
				$arrayResult["COUNTRY"] = $arrayDataGeo;
				$arrayResult['ADDRESS_CAN_CHANGE'] = TRUE;
			}
			

		}
		$arrayResult['IS_OTP'] = FALSE;
		$arrayResult['IS_SMS'] = TRUE;
		$arrayResult['IS_EMAIL'] = TRUE;
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