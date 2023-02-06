<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'CremationInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrayDataWcGrp = array();
		$getDataWc = $conmssql->prepare("SELECT cp.COOP_NAME as CS_NAME,mt.MEMBER_NO,mt.DEPTACCOUNT_NO,mt.DEPTACCOUNT_NAME,mt.DEPTACCOUNT_SNAME,mt.WFTYPE_CODE,mt.CARD_PERSON
										from wcdeptmaster mt 
										left join wcreqchg_dept chg on mt.deptaccount_no = chg.deptaccount_no and mt.coop_id = chg.coop_id and mt.wc_id = chg.wc_id
										left join wccontcoop cp on  mt.coop_id = cp.coop_id  and mt.coop_id = cp.coop_id and mt.wc_id = cp.wc_id
										where mt.member_no = :member_no");
		$getDataWc->execute([':member_no' => $member_no]);
		while($rowDataWc = $getDataWc->fetch(PDO::FETCH_ASSOC)){
			$arrayDataWc = array();
			$arrayDataWc["DEPTACCOUNT_NO"] = $rowDataWc["DEPTACCOUNT_NO"];
			$arrayDataWc["ACCOUNT_NAME"] = $rowDataWc["DEPTACCOUNT_NAME"]." ".$rowDataWc["DEPTACCOUNT_SNAME"];
			$arrayDataWc["CREMATION_TYPE"] = $rowDataWc["CS_NAME"];
			$arrayDataWc["CREMATION_CODE"] = $rowDataWc["WFTYPE_CODE"];
			$arrayDataWc["CARD_PERSON"] = $rowDataWc["CARD_PERSON"];
			$getPersonAccountWC = $conmssql->prepare("SELECT co.SEQ_NO, co.transferee_name as NAME
									   FROM wcdeptmaster mt 
									   LEFT JOIN wccodeposit co on mt.deptaccount_no = co.deptaccount_no and mt.coop_id = co.coop_id and mt.wc_id = co.wc_id
									   LEFT JOIN wccontcoop wc on mt.coop_id = wc.coop_id and mt.wc_id = wc.wc_id
									   WHERE LTRIM(RTRIM(mt.DEPTACCOUNT_NO)) = :account_no
									   ORDER BY mt.wc_id,co.seq_no");
			$getPersonAccountWC->execute([':account_no' => TRIM($rowDataWc["DEPTACCOUNT_NO"])]);
			while($rowPerson = $getPersonAccountWC->fetch(PDO::FETCH_ASSOC)){
				if(isset($rowPerson["NAME"]) && $rowPerson["NAME"] != ""){
					$arrPerson = array();
					$arrPerson["NAME"] = $rowPerson["NAME"];
					$arrPerson["SEQ_NO"] = $rowPerson["SEQ_NO"];
					$arrayDataWc["PERSON"][] = $arrPerson;
				}
			}
			$arrayDataWcGrp[] = $arrayDataWc;
		}
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
