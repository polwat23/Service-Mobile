<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'CremationInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrDataWC = array();
		$getCremation = $conmssql->prepare("SELECT CASE WHEN wftype_code = '1' then 'สสธท.' else 
											CASE when wftype_code = '2' then 'กสธท.ล้านที่ 2' else
											CASE when wftype_code = '3' then 'กสธท.ล้านที่ 3' else 'สส.ชสอ' end end end as WFTYPE_DESC,
											MEMB_NAME,CARD_PERSON,WFMEMBER_NO
											FROM WFCOOPMASTER WHERE member_no = :member_no ORDER BY SEQ_NO");
		$getCremation->execute([':member_no' => $member_no]);
		while($rowCremation = $getCremation->fetch(PDO::FETCH_ASSOC)){
			$arrCremation = array();
			$arrayOther[0]["LABEL"] = "เลขบัตรประจำตัวประชาชน";
			$arrayOther[0]["VALUE"] = $rowCremation["CARD_PERSON"];
			$arrayOther[1]["LABEL"] = "เลขทะเบียน";
			$arrayOther[1]["VALUE"] = $rowCremation["WFMEMBER_NO"];
			$arrPerson["NAME"] = $rowCremation["MEMB_NAME"];
			$arrCremation["PERSON"][] = $arrPerson;
			$arrCremation["OTHER_INFO"] = $arrayOther;
			$arrCremation["CREMATION_TYPE"] = $rowCremation["WFTYPE_DESC"];
			$arrDataWC[] = $arrCremation;
 		}
		$arrayResult['CREMATION'] = $arrDataWC;
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