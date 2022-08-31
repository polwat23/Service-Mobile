<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SlipInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		// $limit_period = $func->getConstant('limit_kpmonth');
		$arrayGroupPeriod = array();
		$getPeriodKP = $conoracle->prepare("
			SELECT
				TRIM(TO_CHAR(TO_NUMBER(SUBSTR(RECV.RECV_PERIOD,1,4))+1)) AS KEEP_YEAR, 
				SL.DEPTSLIP_NO,
				SL.DEPTSLIP_DATE, 
				SL.DEPTSLIP_AMT, 
				SL.COOP_ID
			FROM WCRECIEVEMONTH RECV
				JOIN WCDEPTSLIP SL ON (SL.DEPTACCOUNT_NO = RECV.WFMEMBER_NO AND SL.DEPTITEMTYPE_CODE = 'WPF' AND SL.RECV_PERIOD = RECV.RECV_PERIOD AND SL.ITEM_STATUS = 1)
			WHERE RECV.WCITEMTYPE_CODE = 'FEE'
				AND TRIM(RECV.WFMEMBER_NO) = :member_no
				AND RECV.STATUS_POST IN (1,2)
				ORDER BY RECV.RECV_PERIOD DESC");
		$getPeriodKP->execute([
				':member_no' => $member_no
		]);
		while($rowPeriod = $getPeriodKP->fetch(PDO::FETCH_ASSOC)){
			$arrKpmonth = array();
			$arrKpmonth["PERIOD"] = $rowPeriod["DEPTSLIP_NO"];
			$arrKpmonth["MONTH_RECEIVE"] = "ประจำปี ".$rowPeriod["KEEP_YEAR"];
			$arrKpmonth["SLIP_NO"] = $rowPeriod["DEPTSLIP_NO"];
			$arrKpmonth["SLIP_DATE"] = $lib->convertdate($rowPeriod["DEPTSLIP_DATE"],'d m Y');
			$arrKpmonth["RECEIVE_AMT"] = number_format($rowPeriod["DEPTSLIP_AMT"],2);
			$arrayGroupPeriod[] = $arrKpmonth;
		}
		
		$getRegister = $conoracle->prepare("
			SELECT
				WB.DEPTSLIPBRANCH_NO,
				WS.DEPTSLIP_DATE,
				(select sum(PRNCSLIP_AMT) from wcdeptslipdet where deptslip_no = WS.DEPTSLIP_NO and  coop_id =   WS.COOP_ID) as total
			FROM WCREQDEPOSIT WQ
				JOIN WCDEPTSLIP WS ON (WQ.DEPTREQUEST_DOCNO = WS.DEPTACCOUNT_NO AND WQ.COOP_ID = WS.COOP_ID ) 
				JOIN WCDEPTSLIPBRANCH WB ON (WB.DEPTSLIP_NO = WS.DEPTSLIP_NO AND WB.COOP_ID = WS.COOP_ID)
			WHERE 
				WS.DEPTITEMTYPE_CODE  = 'WFF'
				AND TRIM(WQ.DEPTACCOUNT_NO) = :member_no
		");
		$getRegister->execute([
			':member_no' => $member_no
		]);
		while($rowRegister = $getRegister->fetch(PDO::FETCH_ASSOC)){
			$arrReg = array();
			$arrReg["PERIOD"] = 'register';
			$arrReg["MONTH_RECEIVE"] = "สมัครใหม่";
			$arrReg["SLIP_NO"] = $rowRegister["DEPTSLIPBRANCH_NO"];
			$arrReg["SLIP_DATE"] = $lib->convertdate($rowRegister["DEPTSLIP_DATE"],'d m Y');
			$arrReg["RECEIVE_AMT"] = number_format($rowRegister["TOTAL"],2);
			
			$arrayGroupPeriod[] = $arrReg;
		}
		
		$arrayResult['KEEPING_LIST'] = $arrayGroupPeriod;
		$arrayResult['RESULT'] = TRUE;
		$arrayResult['SHOW_SLIP_REPORT'] = TRUE;
		$arrayResult['DISABLE_SLIPDETAIL'] = TRUE;
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