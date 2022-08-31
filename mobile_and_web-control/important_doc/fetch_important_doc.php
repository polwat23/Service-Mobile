<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ImportantDoc')){
		$member_no = $configAS[$payload["member_no"]] ?? TRIM($payload["member_no"]);
		
		$arrayGroupRegister = array();
		$getCpctRegister = $conoracle->prepare("
			SELECT 
				TRIM(CMUCFCOOPBRANCH.COOPBRANCH_ID || '-' || WCDEPTMASTER.DEPTACCOUNT_NO) AS DOC_NO,
				to_date('01/'||to_char(ADD_MONTHS(WCDEPTMASTER.APPLY_DATE,1),'mm/yyyy'),'dd/mm/yyyy') as DOC_DATE
			FROM
				WCDEPTMASTER 
				LEFT JOIN CMUCFCOOPBRANCH ON (WCDEPTMASTER.COOP_ID = CMUCFCOOPBRANCH.COOP_ID)
			WHERE
				TRIM(WCDEPTMASTER.DEPTACCOUNT_NO) = :member_no");
		$getCpctRegister->execute([':member_no' => $member_no]);
		
		while($rowCpct = $getCpctRegister->fetch(PDO::FETCH_ASSOC)){
			$arrayReg = array();
			$arrayReg["DOC_NAME"] = "หนังสือสำคัญแสดงการเป็นสมาชิก สสธท";
			$arrayReg["DOC_NO"] = $rowCpct["DOC_NO"];
			$arrayReg["DOC_DATE"] = $lib->convertdate($rowCpct["DOC_DATE"],'d m Y');
			$arrayGroupRegister[] = $arrayReg;
		}
		
		$getFundRegister = $conoracle->prepare("
			SELECT 
				WCFUNDMASTER.FUNDTYPE_CODE as DOC_TYPE,
				TRIM(CMUCFCOOPBRANCH.COOPBRANCH_ID || '-' || WCFUNDMASTER.FUNDACCOUNT_NO) AS DOC_NO,
				(select acci_Date from wcucffundround where fundopen_date = WCFUNDMASTER.FUNDOPEN_DATE and fundtype_code =  WCFUNDMASTER.FUNDTYPE_CODE) as DOC_DATE
			FROM
				WCFUNDMASTER 
				LEFT JOIN WCDEPTMASTER ON (WCFUNDMASTER.DEPTACCOUNT_NO = WCDEPTMASTER.DEPTACCOUNT_NO)
				LEFT JOIN CMUCFCOOPBRANCH ON (WCDEPTMASTER.COOP_ID = CMUCFCOOPBRANCH.COOP_ID)
			WHERE
				WCFUNDMASTER.FUNDTYPE_CODE IN ('001', '002') AND
				TRIM(WCDEPTMASTER.DEPTACCOUNT_NO) = :member_no
				ORDER BY WCFUNDMASTER.FUNDTYPE_CODE");
		$getFundRegister->execute([':member_no' => $member_no]);
		
		while($rowFund = $getFundRegister->fetch(PDO::FETCH_ASSOC)){
			$arrayFund = array();
			if ($rowFund["DOC_TYPE"] == '001') {
				$arrayFund["DOC_NAME"] = "หนังสือสำคัญแสดงการเป็นสมาชิก กองทุน ล้านที่ 2";
			} else {
				$arrayFund["DOC_NAME"] = "หนังสือสำคัญแสดงการเป็นสมาชิก กองทุน ล้านที่ 3";
			}
			$arrayFund["DOC_TYPE"] = $rowFund["DOC_TYPE"] ?? "";
			$arrayFund["DOC_NO"] = $rowFund["DOC_NO"];
			$arrayFund["DOC_DATE"] = $lib->convertdate($rowFund["DOC_DATE"],'d m Y');
			$arrayGroupRegister[] = $arrayFund;
		}
		
		$arrayResult['DOC'] = $arrayGroupRegister;
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