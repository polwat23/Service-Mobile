<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'PayInSlip')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		
		$arrayGroupSlip = array();
		
		$getCpctSlip = $conoracle->prepare("
			select
				(to_number(SUBSTR(wcrecievemonth.recv_period,1,4))+1) as recv_year,
				wcrecievemonth.fee_year+20 as total_amt,
				trim(wcdeptmaster.coop_id)||'/'||trim(wcdeptmaster.deptaccount_no) as ref2
			from wcrecievemonth
				left join wcdeptmaster  on wcrecievemonth.wfmember_no  = wcdeptmaster.deptaccount_no
			where
				trim(wcrecievemonth.wfmember_no) = :member_no
				and wcrecievemonth.wcitemtype_code = 'FEE'
				and wcrecievemonth.coop_id in (select coop_id from cmucfcoopbranch where  coop_id not in ('091506') and from_cs = '09' )
				and wcrecievemonth.status_post = 8
				and wcdeptmaster.deptclose_status = 0
			");
		$getCpctSlip->execute([
				':member_no' => $member_no
		]);
		while($rowCpct = $getCpctSlip->fetch(PDO::FETCH_ASSOC)){
			$arrCpctSlip = array();
			$arrCpctSlip["SLIP_NAME"] = "ใบแจ้งหนี้ สสธท.";
			$arrCpctSlip["SLIP_DESC"] = "ประจำปี ".$rowCpct["RECV_YEAR"];
			$arrCpctSlip["SLIP_AMT"] = number_format($rowCpct["TOTAL_AMT"],2);
			$arrCpctSlip["SLIP_NO"] = $rowCpct["REF2"];
			$arrCpctSlip["SLIP_TYPE"] = "CPCT";
			$arrayGroupSlip[] = $arrCpctSlip;
		}
		
		$arrayResult['SLIP_LIST'] = $arrayGroupSlip;
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