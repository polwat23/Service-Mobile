<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'CremationInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrayDataWcGrp = array();
		$getDataWc = $conmssql->prepare("SELECT IGM.PERIODPAY_AMT AS AMT,IGM.INSCOST_BLANCE AS PROTECT_AMT,IST.INSCOMPANY_NAME AS CS_NAME,IGM.INSTYPE_CODE,
												IGM.STARTSAFE_DATE as PROTECTSTART_DATE,IGM.ENDSAFE_DATE as PROTECTEND_DATE
												FROM INSGROUPMASTER IGM LEFT JOIN INSURENCETYPE IST ON IGM.INSTYPE_CODE = IST.INSTYPE_CODE
												WHERE IGM.MEMBER_NO = :member_no and IGM.INSTYPE_CODE IN('01','03','02','08','09','10','11','12','13','14','15')");
		$getDataWc->execute([':member_no' => $member_no]);
		while($rowDataWc = $getDataWc->fetch(PDO::FETCH_ASSOC)){
			if(isset($rowDataWc["INSTYPE_CODE"]) && $rowDataWc["INSTYPE_CODE"] != ""){
				$arrayDataWc = array();
				//$arrayDataWc["DEPTACCOUNT_NO"] = $lib->formataccount($rowDataWc["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
				//$arrayDataWc["ACCOUNT_NAME"] = $rowDataWc["WFACCOUNT_NAME"];
				$arrayDataWc["CREMATION_TYPE"] = $rowDataWc["CS_NAME"];
				$arrayDataWc["AMOUNT_WC"] = number_format($rowDataWc["AMT"],2);
				/*$getPersonAccountWC = $conwc->prepare("SELECT WCCODEPOSIT.NAME,WCCODEPOSIT.SEQ_NO
													FROM WCDEPTMASTER LEFT JOIN WCCODEPOSIT 
													ON WCDEPTMASTER.DEPTACCOUNT_NO= WCCODEPOSIT.DEPTACCOUNT_NO 
													WHERE WCDEPTMASTER.DEPTCLOSE_STATUS = 0 AND TRIM(WCDEPTMASTER.DEPTACCOUNT_NO) = :account_no
													ORDER BY WCCODEPOSIT.SEQ_NO ASC");
				$getPersonAccountWC->execute([':account_no' => TRIM($rowDataWc["DEPTACCOUNT_NO"])]);
				while($rowPerson = $getPersonAccountWC->fetch(PDO::FETCH_ASSOC)){
					if(isset($rowPerson["NAME"]) && $rowPerson["NAME"] != ""){
						$arrPerson = array();
						$arrPerson["NAME"] = $rowPerson["NAME"];
						$arrPerson["SEQ_NO"] = $rowPerson["SEQ_NO"];
						$arrayDataWc["PERSON"][] = $arrPerson;
					}
				}*/
				$arrayDataWcGrp[] = $arrayDataWc;
			}
		}
		$arrayResult['NOTE'] = "เงินสงเคราะห์ศพ(ประมาณ) 600,000บาท";
		$arrayResult['CREMATION'] = $arrayDataWcGrp;
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
