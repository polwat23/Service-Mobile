<?php
require_once('../autoload.php');

if ($lib->checkCompleteArgument(['unique_id'],$dataComing)) {
	if ($func->check_permission($payload["user_type"], $dataComing["menu_component"], 'SettingMemberInfo')) {
		$member_no = $configAS[$payload["ref_memno"]] ?? $payload["ref_memno"];
		$conmysql->beginTransaction();
		$year = date(Y) +543;
		$arrayData = array();
		//president
		
		$newArr = array();
		$newArr["BOARD"] = $dataComing["board"];
		$newArr["PRESIDENT"] = $dataComing["president"]["VALUE"];
		$newArr["BUSINESS"] = $dataComing["business"];
		$newArr["MANAGER"] = $dataComing["manager"]["VALUE"];
		$newArr["OFFICER_COUNT"] = $dataComing["officer_count"]["VALUE"];
		$newArr["MEMBER_COUNT"] = $dataComing["member_count"]["VALUE"];
		$incomming = json_encode($newArr);
		
		
		$mdInfo = $conoracle->prepare("SELECT  MB.BOARD_NAME as MD_NAME,  MY.MEMBERSHIP_AMT as MD_COUNT, BDRANK_CODE as MD_TYPE,MB.ADD_NO as ADDR_NO,
										MB.ADDR_MOO as ADDR_MOO,MB.ADDR_SOI as ADDR_SOI,MB.ADDR_ROAD as ADDR_ROAD,MB.ADDR_DISTRICT AS DISTRICT_CODE,MB.ADDR_TAMBOL AS TAMBOL_CODE,
										MB.ADDR_PROVINCE AS PROVINCE_CODE,MBT.TAMBOL_DESC AS TAMBOL_REG_DESC,MBD.DISTRICT_DESC AS DISTRICT_REG_DESC,MBP.PROVINCE_DESC AS PROVINCE_REG_DESC,											
										MBT.TAMBOL_DESC AS TAMBOL_DESC,MBD.DISTRICT_DESC AS DISTRICT_DESC,MBP.PROVINCE_DESC AS PROVINCE_DESC,MB.BOARD_TEL,MB.BOARD_AGE,MB.BOARD_EMAIL,MB.PERSON_ID
										FROM MBMEMBDETYEARBOARD MB LEFT JOIN MBMEMBDETYEARBIZ MY ON MB.MEMBER_NO = MY.MEMBER_NO AND MB.BIZ_YEAR  = MY.BIZ_YEAR
										LEFT JOIN MBUCFTAMBOL MBT ON MB.ADDR_TAMBOL = MBT.TAMBOL_CODE
										LEFT JOIN MBUCFDISTRICT MBD ON MB.ADDR_DISTRICT = MBD.DISTRICT_CODE
										LEFT JOIN MBUCFPROVINCE MBP ON MB.ADDR_PROVINCE = MBP.PROVINCE_CODE
										WHERE  MB.MEMBER_NO = :member_no  AND MB.BIZ_YEAR = :year");
		$mdInfo->execute([':member_no' => $payload["ref_memno"] ,':year' =>$year ]);
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
			$arrayMd["ADDRESS"] = $address;			
			$arrayMd["MD_NAME"] = $rowUser["MD_NAME"];
			
			
			if($rowUser["MD_TYPE"] == "01"){			//ประธาน
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

			$arrayData["MEMBER_COUNT"] = $arrayMember;  //จํานวนสมาชิก
			$arrayData["PRESIDENT"] = $arrayChairman;		//ประธานกรรมการ
			$arrayData["BOARD"] =  $arrayBoard;		//รายชื่อคณะกรรมการ
			$arrayData["BUSINESS"] = $arrayBusiness;		//ผู้ตรวจสอบกิจการ
			$arrayData["MANAGER"] = $arrayManager;		//ผู้จัดการ
			$arrayData["OFFICER_COUNT"] = $arrayOfficer;		//เจ้าหน้าที่สหกรณ์			
		}
		$old_data = json_encode($arrayData);
		
		if (isset($incomming) && $incomming != "" ) {
			$insertPresData = $conmysql->prepare("INSERT INTO gcmanagement(member_no, old_data, incoming_data,username) 
										VALUES (:member_no,:old_data,:incomming,:username)");
			if ($insertPresData->execute([':member_no' => $member_no,
				':old_data' => $old_data,
				':incomming' => $incomming,
				':username'=> $payload["member_no"]
			])) {
				$arrayResult["RESULT_EDIT"] = TRUE;
			} else {
				$arrayResult["RESULT_EDIT"] = FALSE;
			}
		}
		
		if (isset($arrayResult["RESULT_EDIT"]) && !$arrayResult["RESULT_EDIT"]) {
			$conmysql->rollback();
			$arrayResult['RESPONSE_CODE'] = "WS1039";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
		} else {
			$conmysql->commit();
			$arrayResult['RESULT'] = TRUE;
			$arrayResult['DATA'] = json_encode($incomming);
			require_once('../../include/exit_footer.php');
		}
	} else {
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../include/exit_footer.php');
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
