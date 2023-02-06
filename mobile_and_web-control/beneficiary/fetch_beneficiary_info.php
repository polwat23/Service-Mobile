<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'BeneficiaryInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGroupBNF = array();
		$getBeneficiary = $conmssql->prepare("SELECT MP.PRENAME_DESC,MG.GAIN_NAME,MG.GAIN_SURNAME,MG.ADDRESS_NO,MG.CARD_PERSON,MG.BIRTH_DATE,
												MG.ADDRESS_MOO,MG.ADDRESS_SOI,MG.ADDRESS_VILLAGE,MG.ADDRESS_ROAD,MBT.TAMBOL_DESC AS TAMBOL_DESC,
												MBD.DISTRICT_DESC AS DISTRICT_DESC,
												mg.PROVINCE_CODE AS PROVINCE_CODE,
												MBP.PROVINCE_DESC AS PROVINCE_DESC,mc.GAIN_CONCERN,mg.POSTCODE , mg.GAIN_ADDRESS
												FROM mbgainmaster mg LEFT JOIN mbucfprename mp ON mg.prename_code = mp.prename_code
												LEFT JOIN mbucfgainconcern mc ON LTRIM(RTRIM(mg.gain_relation)) =  LTRIM(RTRIM(mc.concern_code))
												LEFT JOIN MBUCFTAMBOL MBT ON mg.TAMBOL_CODE = MBT.TAMBOL_CODE
												LEFT JOIN MBUCFDISTRICT MBD ON mg.DISTRICT_CODE = MBD.DISTRICT_CODE
												LEFT JOIN MBUCFPROVINCE MBP ON mg.PROVINCE_CODE = MBP.PROVINCE_CODE	
												WHERE mg.member_no = :member_no ORDER BY mg.SEQ_NO");
		$getBeneficiary->execute([':member_no' => $member_no]);
		while($rowBenefit = $getBeneficiary->fetch(PDO::FETCH_ASSOC)){
			$address = (isset($rowBenefit["ADDRESS_NO"]) ? $rowBenefit["ADDRESS_NO"] : null);
			$arrBenefit = array();
			$arrBenefit["FULL_NAME"] = $rowBenefit["PRENAME_DESC"].$rowBenefit["GAIN_NAME"].' '.$rowBenefit["GAIN_SURNAME"];
			if(isset($rowBenefit["PROVINCE_CODE"]) && $rowBenefit["PROVINCE_CODE"] == '10'){
				$address .= (isset($rowBenefit["ADDRESS_MOO"]) && $rowBenefit["ADDRESS_MOO"] != "" ? ' ม.'.$rowBenefit["ADDRESS_MOO"] : null);
				$address .= (isset($rowBenefit["ADDRESS_SOI"]) && $rowBenefit["ADDRESS_SOI"] != "" ? ' ซอย'.$rowBenefit["ADDRESS_SOI"] : null);
				$address .= (isset($rowBenefit["ADDRESS_VILLAGE"]) && $rowBenefit["ADDRESS_VILLAGE"] != "" ? ' หมู่บ้าน'.$rowBenefit["ADDRESS_VILLAGE"] : null);
				$address .= (isset($rowBenefit["ADDRESS_ROAD"]) && $rowBenefit["ADDRESS_ROAD"] != "" ? ' ถนน'.$rowBenefit["ADDRESS_ROAD"] : null);
				$address .= (isset($rowBenefit["TAMBOL_DESC"]) && $rowBenefit["TAMBOL_DESC"] != "" ? ' แขวง'.$rowBenefit["TAMBOL_DESC"] : null);
				$address .= (isset($rowBenefit["DISTRICT_DESC"]) && $rowBenefit["DISTRICT_DESC"] != "" ? ' เขต'.$rowBenefit["DISTRICT_DESC"] : null);
				$address .= (isset($rowBenefit["PROVINCE_DESC"]) && $rowBenefit["PROVINCE_DESC"] != "" ? ' '.$rowBenefit["PROVINCE_DESC"] : null);
				$address .= (isset($rowBenefit["POSTCODE"]) && $rowBenefit["POSTCODE"] != "" ? ' '.$rowBenefit["POSTCODE"] : null);
			}else{
				$address .= (isset($rowBenefit["ADDRESS_MOO"]) && $rowBenefit["ADDRESS_MOO"] != "" ? ' ม.'.$rowBenefit["ADDRESS_MOO"] : null);
				$address .= (isset($rowBenefit["ADDRESS_SOI"]) && $rowBenefit["ADDRESS_SOI"] != "" ? ' ซอย'.$rowBenefit["ADDRESS_SOI"] : null);
				$address .= (isset($rowBenefit["ADDRESS_VILLAGE"]) && $rowBenefit["ADDRESS_VILLAGE"] != "" ? ' หมู่บ้าน'.$rowBenefit["ADDRESS_VILLAGE"] : null);
				$address .= (isset($rowBenefit["ADDRESS_ROAD"]) && $rowBenefit["ADDRESS_ROAD"] != "" ? ' ถนน'.$rowBenefit["ADDRESS_ROAD"] : null);
				$address .= (isset($rowBenefit["TAMBOL_DESC"]) && $rowBenefit["TAMBOL_DESC"] != "" ? ' ต.'.$rowBenefit["TAMBOL_DESC"] : null);
				$address .= (isset($rowBenefit["DISTRICT_DESC"]) && $rowBenefit["DISTRICT_DESC"] != "" ? ' อ.'.$rowBenefit["DISTRICT_DESC"] : null);
				$address .= (isset($rowBenefit["PROVINCE_DESC"]) && $rowBenefit["PROVINCE_DESC"] != "" ? ' จ.'.$rowBenefit["PROVINCE_DESC"] : null);
				$address .= (isset($rowBenefit["POSTCODE"]) && $rowBenefit["POSTCODE"] != "" ? ' '.$rowBenefit["POSTCODE"] : null);
			}
			$arrOtherInfo[0]["LABEL"] = "เลขบัตรประจำตัวประชาชน";
			$arrOtherInfo[0]["VALUE"] = $rowBenefit["CARD_PERSON"];
			$arrOtherInfo[1]["LABEL"] = "ที่อยู่";
			$arrOtherInfo[1]["VALUE"] = $rowBenefit["GAIN_ADDRESS"];
			$arrBenefit["ADDRESS"] = $address;
			$arrBenefit["OTHER_INFO"] = $arrOtherInfo;
			$arrBenefit["RELATION"] = $rowBenefit["GAIN_CONCERN"];
			$arrGroupBNF[] = $arrBenefit;
		}
		$arrayResult['BENEFICIARY'] = $arrGroupBNF;
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