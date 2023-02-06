<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'GuaranteeInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		
		$arrayGroupLoan = array();
		$getUcollwho = $conmssql->prepare("SELECT
											RTRIM(LCC.LOANCONTRACT_NO) AS LOANCONTRACT_NO,
											LNTYPE.loantype_desc as TYPE_DESC,
											PRE.PRENAME_DESC,MEMB.MEMB_NAME,MEMB.MEMB_SURNAME,
											LCM.MEMBER_NO AS MEMBER_NO,
											ISNULL(LCM.LOANAPPROVE_AMT,0) as LOANAPPROVE_AMT,
											ISNULL(LCM.PRINCIPAL_BALANCE,0) as LOAN_BALANCE
											FROM
											LNCONTCOLL LCC LEFT JOIN LNCONTMASTER LCM ON  LCC.LOANCONTRACT_NO = LCM.LOANCONTRACT_NO
											LEFT JOIN MBMEMBMASTER MEMB ON LCM.MEMBER_NO = MEMB.MEMBER_NO
											LEFT JOIN MBUCFPRENAME PRE ON MEMB.PRENAME_CODE = PRE.PRENAME_CODE
											LEFT JOIN lnloantype LNTYPE  ON LCM.loantype_code = LNTYPE.loantype_code
											WHERE
											LCM.CONTRACT_STATUS > 0 and LCM.CONTRACT_STATUS <> 8
											AND LCC.LOANCOLLTYPE_CODE = '01'
											AND LCC.REF_COLLNO = :member_no");
		$getUcollwho->execute([':member_no' => $member_no]);
		while($rowUcollwho = $getUcollwho->fetch(PDO::FETCH_ASSOC)){
			$arrayColl = array();
			$arrayColl["CONTRACT_NO"] = $rowUcollwho["LOANCONTRACT_NO"];
			$arrayColl["TYPE_DESC"] = $rowUcollwho["TYPE_DESC"];
			$arrayColl["MEMBER_NO"] = $rowUcollwho["MEMBER_NO"];
			$arrayAvarTar = $func->getPathpic($rowUcollwho["MEMBER_NO"]);
			$arrayColl["AVATAR_PATH"] = isset($arrayAvarTar["AVATAR_PATH"]) ? $config["URL_SERVICE"].$arrayAvarTar["AVATAR_PATH"] : null;
			$arrayColl["AVATAR_PATH_WEBP"] = isset($arrayAvarTar["AVATAR_PATH_WEBP"]) ? $config["URL_SERVICE"].$arrayAvarTar["AVATAR_PATH_WEBP"] : null;
			$arrayColl["APPROVE_AMT"] = number_format($rowUcollwho["LOANAPPROVE_AMT"],2);
			$arrayColl["LOAN_BALANCE"] = number_format($rowUcollwho["LOAN_BALANCE"],2);
			$arrayColl["FULL_NAME"] = $rowUcollwho["PRENAME_DESC"].$rowUcollwho["MEMB_NAME"].' '.$rowUcollwho["MEMB_SURNAME"];
			$arrayGroupLoan[] = $arrayColl;
		}
		$clientWS = new SoapClient($config["URL_CORE_COOP"]."n_loan.svc?singleWsdl");
		$structureReqLoan = array();
		$structureReqLoan["coop_id"] = $config["COOP_ID"];
		$structureReqLoan["member_no"] = $member_no;		
		$structureReqLoan["loantype_code"] = "22";
		$structureReqLoan["operate_date"] = date('c');
		$structureReqLoan["colltype_code"] = "01";		
		/*$structureReqLoan["action_status"] = 0;
		$structureReqLoan["approve_amt"] = 0;
		$structureReqLoan["buyshr_amt"] = 0;
		$structureReqLoan["contcredit_flag"] = 0;
		$structureReqLoan["emerbalance_amt"] = 0;
		$structureReqLoan["fee_amt"] = 0;
		$structureReqLoan["feeinclude_status"] = 0;
		$structureReqLoan["intpayment_clr"] = 0;
		$structureReqLoan["intrate_amt"] = 0;
		$structureReqLoan["item_amt"] = 0;
		$structureReqLoan["lastperiod_payamt"] = 0;
		$structureReqLoan["loancredit_amt"] = 0;
		$structureReqLoan["loanpayment_type"] = 0;
		$structureReqLoan["loanpermiss_amt"] = 0;
		$structureReqLoan["loanrequest_amt"] = 0;
		$structureReqLoan["maxloanrequest_amt"] = 0;
		$structureReqLoan["maxperiod_payment"] = 0;
		$structureReqLoan["maxreceive_amt"] = 0;
		$structureReqLoan["nombalance_amt"] = 0;
		$structureReqLoan["period_payamt"] = 0;
		$structureReqLoan["period_payment"] = 0;
		$structureReqLoan["post_status"] = 0;
		$structureReqLoan["prinbal_clr"] = 0;
		$structureReqLoan["principal_amt"] = 0;	
		$structureReqLoan["roundpay_factor"] = 0;
		$structureReqLoan["sharestk_value"] = 0;
		$structureReqLoan["specbalace_amt"] = 0;
		$structureReqLoan["withdrawable_amt"] = 0;*/
		try {
			$argumentWS = [
				"as_wspass" => $config["WS_STRC_DB"],
				"atr_lnatm" => $structureReqLoan
			];
			$resultWS = $clientWS->__call("of_getcollpermissmemno_IVR", array($argumentWS));
			$responseSoap = $resultWS->atr_lnatm;
			//$lib->sendLineNotify(json_encode($responseSoap->loanrequest_amt,JSON_UNESCAPED_UNICODE));
			$arrayResult['LIMIT_GUARANTEE_LIST'] = number_format($responseSoap->loanrequest_amt,2);
		}catch(SoapFault $e){
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS0061",
				":error_desc" => "ไม่สามารถคำนวณวงเงินค้ำประกันคงเหลือได้ "."\n"."Error => ".$e->getMessage()."\n".json_encode($e),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			//$message_error = "ไฟล์ ".$filename." ไม่สามารถคำนวณวงเงินค้ำประกันคงเหลือได้ "."\n"."Error => "."\n".json_encode($e)."\n"."DATA => ".json_encode($dataComing);
			
			$arrayResult['RESPONSE_CODE'] = 'WS0061';
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}

		$arrayResult['CONTRACT_COLL'] = $arrayGroupLoan;
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