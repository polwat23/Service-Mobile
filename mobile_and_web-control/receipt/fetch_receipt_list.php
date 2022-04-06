<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SlipInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$limit_period = $func->getConstant('limit_kpmonth');
		$fetchName = $conmssql->prepare("SELECT MEMBCAT_CODE,MEMBER_REF
												FROM MBMEMBMASTER 
												WHERE member_no = :member_no");
		$fetchName->execute([
			':member_no' => $member_no
		]);
		$arrMemberNoRef = array();
		while($rowName = $fetchName->fetch(PDO::FETCH_ASSOC)){
			$arrMemberNoRef[] = "'".$rowName["MEMBER_REF"]."'";
		}
		$arrayGroupPeriod = array();
		$getPeriodKP = $conmssql->prepare("SELECT TOP ". $limit_period." RECV_PERIOD,RECEIPT_DATE,RECEIPT_NO,RECEIVE_AMT,KEEPING_STATUS 
															from kpmastreceive where member_no = :member_no ORDER BY recv_period DESC");
		$getPeriodKP->execute([
				':member_no' => $member_no
		]);
		while($rowPeriod = $getPeriodKP->fetch(PDO::FETCH_ASSOC)){
			$arrKpmonth = array();
			$arrKpmonth["PERIOD"] = $rowPeriod["RECV_PERIOD"];
			$arrKpmonth["MONTH_RECEIVE"] = $lib->convertperiodkp(TRIM($rowPeriod["RECV_PERIOD"]));
			$getKPDetail = $conmssql->prepare("SELECT ISNULL(SUM(KPD.ITEM_PAYMENT * KUT.SIGN_FLAG),0) AS ITEM_PAYMENT 
													FROM KPMASTRECEIVEDET KPD
													LEFT JOIN KPUCFKEEPITEMTYPE KUT ON 
													KPD.KEEPITEMTYPE_CODE = KUT.KEEPITEMTYPE_CODE
													where kpd.member_no = :member_no and kpd.recv_period = :recv_period");
			$getKPDetail->execute([
				':member_no' => $member_no,
				':recv_period' => $rowPeriod["RECV_PERIOD"]
			]);
			$rowKPDetali = $getKPDetail->fetch(PDO::FETCH_ASSOC);
			$arrKpmonth["SLIP_NO"] = $rowPeriod["RECEIPT_NO"];
			$arrKpmonth["SLIP_DATE"] = $lib->convertdate($rowPeriod["RECEIPT_DATE"],'d m Y');
			if(isset($rowPeriod["RECEIVE_AMT"]) && $rowPeriod["RECEIVE_AMT"] != ""){
				$arrKpmonth["RECEIVE_AMT"] = number_format($rowPeriod["RECEIVE_AMT"],2);
			}else{
				$arrKpmonth["RECEIVE_AMT"] = number_format($rowKPDetali["ITEM_PAYMENT"],2);
			}
			if($rowPeriod["KEEPING_STATUS"] == '-99' || $rowPeriod["KEEPING_STATUS"] == '-9'){
				$arrKpmonth["IS_CANCEL"] = TRUE;
			}else{
				$arrKpmonth["IS_CANCEL"] = FALSE;
			}
			$getPaymentDetailRef = $conmssql->prepare("SELECT RECEIVE_AMT ,MEMBER_NO  FROM KPMASTRECEIVE WHERE MEMBER_NO IN(".implode(',',$arrMemberNoRef).") AND recv_period = :recv_period");
			$getPaymentDetailRef->execute([':recv_period' => $rowPeriod["RECV_PERIOD"]]);
			$asslnvitData = array();
			while($rowRefAssoInvite = $getPaymentDetailRef->fetch(PDO::FETCH_ASSOC)){
				$arrAsslnvit = array();
				$arrAsslnvit["REF_MEMBER_NO"] = $rowRefAssoInvite["MEMBER_NO"];
				$arrAsslnvit["REF_AMOUNT"] = $rowRefAssoInvite["RECEIVE_AMT"];
				$asslnvitData[] =$arrAsslnvit;
			}
			if(sizeof($asslnvitData) > 0){
				$arrKpmonth['REF_ASSO_INVITE'] = $asslnvitData;
			}
			$arrayGroupPeriod[] = $arrKpmonth;
		}
		//เรียกเก็บของ บุคคลอ้างอิง
		$arrayResult['KEEPING_LIST'] = $arrayGroupPeriod;
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
