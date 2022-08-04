<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'BeneficiaryInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGroupBNF = array();
		$getBeneficiary = $conoracle->prepare("select mu.UF_NAME,mu.HOME_NO,mu.PROVINCE_ID,mu.MOO,mu.SOI,mu.ROAD,mu.TAMBOL,mu.POST_CODE,mu.RELATION,
									MD.DISTRICT_NAME, MPO.PROVINCE_NAME
									from MEM_H_MEMBER mm 
									INNER JOIN MEM_M_USEFUL mu on (mm.MEM_ID=mu.MEM_ID and mm.BR_NO=mu.BR_NO) 
									LEFT JOIN MEM_M_DISTRICT MD ON mu.DISTRICT_ID = MD.DISTRICT_ID AND mu.PROVINCE_ID = MD.PROVINCE_ID
									LEFT JOIN MEM_M_PROVINCE MPO ON mu.PROVINCE_ID = MPO.PROVINCE_ID
									where mm.MEM_ID = :mem_id and mm.BR_NO = :br_no");
		$getBeneficiary->execute([
			':br_no' => substr($member_no,0,3),
			':mem_id' => substr($member_no,-5,5),
		]);
		while($rowBenefit = $getBeneficiary->fetch(PDO::FETCH_ASSOC)){
			$arrBenefit = array();
			$arrBenefit["FULL_NAME"] = $rowBenefit["UF_NAME"];
			$address = (isset($rowBenefit["HOME_NO"]) ? $rowBenefit["HOME_NO"] : null);
			if(isset($rowBenefit["PROVINCE_ID"]) && $rowBenefit["PROVINCE_ID"] == '10'){
				$address .= (isset($rowBenefit["MOO"]) ? ' ม.'.$rowBenefit["MOO"] : null);
				$address .= (isset($rowBenefit["SOI"]) ? ' ซอย'.$rowBenefit["SOI"] : null);
				$address .= (isset($rowBenefit["ROAD"]) ? ' ถนน'.$rowBenefit["ROAD"] : null);
				$address .= (isset($rowBenefit["TAMBOL"]) ? ' แขวง'.$rowBenefit["TAMBOL"] : null);
				$address .= (isset($rowBenefit["DISTRICT_NAME"]) ? ' เขต'.$rowBenefit["DISTRICT_NAME"] : null);
				$address .= (isset($rowBenefit["PROVINCE_NAME"]) ? ' '.$rowBenefit["PROVINCE_NAME"] : null);
				$address .= (isset($rowBenefit["POST_CODE"]) ? ' '.$rowBenefit["POST_CODE"] : null);
			}else{
				$address .= (isset($rowBenefit["MOO"]) ? ' ม.'.$rowBenefit["MOO"] : null);
				$address .= (isset($rowBenefit["SOI"]) ? ' ซอย'.$rowBenefit["SOI"] : null);
				$address .= (isset($rowBenefit["ROAD"]) ? ' ถนน'.$rowBenefit["ROAD"] : null);
				$address .= (isset($rowBenefit["TAMBOL"]) ? ' ต.'.$rowBenefit["TAMBOL"] : null);
				$address .= (isset($rowBenefit["DISTRICT_NAME"]) ? ' อ.'.$rowBenefit["DISTRICT_NAME"] : null);
				$address .= (isset($rowBenefit["PROVINCE_NAME"]) ? ' จ.'.$rowBenefit["PROVINCE_NAME"] : null);
				$address .= (isset($rowBenefit["POST_CODE"]) ? ' '.$rowBenefit["POST_CODE"] : null);
			}
			$arrBenefit["ADDRESS"] = $address;
			$arrBenefit["RELATION"] = $rowBenefit["RELATION"];
			$arrBenefit["TYPE_PERCENT"] = 'text';
			$arrBenefit["PERCENT_TEXT"] = isset($rowBenefit["REMARK"]) && $rowBenefit["REMARK"] != "" ? $rowBenefit["REMARK"] : "แบ่งให้เท่า ๆ กัน";
			$arrBenefit["PERCENT"] = filter_var($rowBenefit["REMARK"], FILTER_SANITIZE_NUMBER_INT);
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