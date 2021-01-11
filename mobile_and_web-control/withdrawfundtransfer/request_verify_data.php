<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','bank_account_no','deptaccount_no','amt_transfer','sigma_key'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransactionWithdrawDeposit')){
		$fetchDataDeposit = $conmysql->prepare("SELECT csb.itemtype_wtd,csb.link_inquirywithd_coopdirect,gba.bank_code
												FROM gcbindaccount gba LEFT JOIN csbankdisplay csb ON gba.bank_code = csb.bank_code
												WHERE gba.sigma_key = :sigma_key");
		$fetchDataDeposit->execute([':sigma_key' => $dataComing["sigma_key"]]);
		$rowDataDeposit = $fetchDataDeposit->fetch(PDO::FETCH_ASSOC);
		$deptaccount_no = preg_replace('/-/','',$dataComing["deptaccount_no"]);
		$itemtypeWithdraw = $rowDataDeposit["itemtype_wtd"];
		$count_trans = 0;
		$dateTrans = date('c');
		$penalty_amt = 0;
		$amt_transfer = $dataComing["amt_transfer"];
		$checkStatusAcc = $conoracle->prepare("SELECT DPM.DEPTCLOSE_STATUS,DPT.DEPTGROUP_CODE FROM DPDEPTMASTER DPM 
													LEFT JOIN DPDEPTTYPE DPT ON DPM.DEPTTYPE_CODE = DPT.DEPTTYPE_CODE
													WHERE DPM.DEPTACCOUNT_NO = :account_no");
		$checkStatusAcc->execute([':account_no' => $deptaccount_no]);
		$rowStatusAcc = $checkStatusAcc->fetch(PDO::FETCH_ASSOC);
		if($rowStatusAcc["DEPTCLOSE_STATUS"] != '0'){
			$arrayResult['RESPONSE_CODE'] = "WS0089";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
		if($rowStatusAcc["DEPTGROUP_CODE"] == '01'){
			$arrayResult['RESPONSE_CODE'] = "WS0090";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
		$getTypeAcc = $conoracle->prepare("SELECT DEPTTYPE_CODE FROM dpdeptmaster WHERE deptaccount_no = :acc_no");
		$getTypeAcc->execute([':acc_no' => $deptaccount_no]);
		$rowType = $getTypeAcc->fetch(PDO::FETCH_ASSOC);
		$getDepPaytype = $conoracle->prepare("SELECT group_itemtpe as GRP_ITEMTYPE FROM dpucfrecppaytype WHERE recppaytype_code = :itemtype");
		$getDepPaytype->execute([':itemtype' => $itemtypeWithdraw]);
		$rowDepPay = $getDepPaytype->fetch(PDO::FETCH_ASSOC);
		$getContDeptType = $conoracle->prepare("SELECT MINWITD_AMT,NVL(s_maxwitd_inmonth,0) as MAXWITHD_INMONTH,NVL(withcount_flag,0) as IS_CHECK_PENALTY
												,NVL(s_period_inmonth,1) as PER_PERIOD_INCOUNT,NVL(withcount_unit,1) as PERIOD_UNIT_CHECK FROM dpdepttype WHERE depttype_code = :depttype_code");
		$getContDeptType->execute([':depttype_code' => $rowType["DEPTTYPE_CODE"]]);
		$rowContDeptType = $getContDeptType->fetch(PDO::FETCH_ASSOC);
		if($dataComing["amt_transfer"] < $rowContDeptType["MINWITD_AMT"]){
			$arrayResult['RESPONSE_CODE'] = "WS0056";
			$arrayResult['RESPONSE_MESSAGE'] = str_replace('${min_amount_deposit}',number_format($rowContDeptType["MINWITD_AMT"],2),$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
		if($rowContDeptType["IS_CHECK_PENALTY"] == '1'){
			$queryCheckPeriod = null;
			if($rowContDeptType["PER_PERIOD_INCOUNT"] > 0){
				if($rowContDeptType["PERIOD_UNIT_CHECK"] == '1'){
					$monthCheck = date('Ym',strtotime('-'.($rowContDeptType["PER_PERIOD_INCOUNT"] -1).' months'));
					$queryCheckPeriod = "and to_char(TRUNC(dps.operate_date),'YYYYMM') BETWEEN ".$monthCheck." and to_char(TRUNC(sysdate),'YYYYMM')";
				}else if($rowContDeptType["PERIOD_UNIT_CHECK"] == '2'){
					$thisMonth = date('m',strtotime($dateTrans));
					if($thisMonth >= 1 && $thisMonth <= 3){
						$queryCheckPeriod = "and to_char(TRUNC(dps.operate_date),'YYYYMM') BETWEEN to_char(TRUNC(sysdate),'YYYY') || '01' and to_char(TRUNC(sysdate),'YYYY') || '03'";
					}else if($thisMonth >= 4 && $thisMonth <= 6){
						$queryCheckPeriod = "and to_char(TRUNC(dps.operate_date),'YYYYMM') BETWEEN to_char(TRUNC(sysdate),'YYYY') || '04' and to_char(TRUNC(sysdate),'YYYY') || '06'";
					}else if($thisMonth >= 7 && $thisMonth <= 9){
						$queryCheckPeriod = "and to_char(TRUNC(dps.operate_date),'YYYYMM') BETWEEN to_char(TRUNC(sysdate),'YYYY') || '07' and to_char(TRUNC(sysdate),'YYYY') || '09'";
					}else{
						$queryCheckPeriod = "and to_char(TRUNC(dps.operate_date),'YYYYMM') BETWEEN to_char(TRUNC(sysdate),'YYYY') || '10' and to_char(TRUNC(sysdate),'YYYY') || '12'";
					}
				}else if($rowContDeptType["PERIOD_UNIT_CHECK"] == '3'){
					$monthCheck = date('Y',strtotime('-'.($rowContDeptType["PER_PERIOD_INCOUNT"]-1).' years'));
					$queryCheckPeriod = "and to_char(TRUNC(dps.operate_date),'YYYY') BETWEEN ".$monthCheck." and to_char(TRUNC(sysdate),'YYYY')";
				}else if($rowContDeptType["PERIOD_UNIT_CHECK"] == '4'){
					$queryCheckPeriod = "";
				}else{
					$queryCheckPeriod = "";
				}
			}
			if(substr($rowDepPay["GRP_ITEMTYPE"],0,1) == 'W'){
				$checkItemIsCount = $conoracle->prepare("SELECT COUNT(*) as IS_NOTCOUNT FROM dpucfwithncount 
														WHERE depttype_code = :depttype_code and deptitem_code = :itemtype");
				$checkItemIsCount->execute([
					':depttype_code' => $rowType["DEPTTYPE_CODE"],
					':itemtype' => $itemtypeWithdraw
				]);
				$rowItemCount = $checkItemIsCount->fetch(PDO::FETCH_ASSOC);
				if($rowItemCount["IS_NOTCOUNT"] > 0){
					$getCountTrans = $conoracle->prepare("SELECT COUNT(dps.SEQ_NO) as C_TRANS FROM dpdeptstatement dps 
														WHERE dps.deptaccount_no = :deptaccount_no and SUBSTR(dps.DEPTITEMTYPE_CODE,0,1) = 'W' 
														and dps.deptitemtype_code <> :itemtype_code and dps.item_status = '1' ".$queryCheckPeriod);
					$getCountTrans->execute([
						':deptaccount_no' => $deptaccount_no,
						':itemtype_code' => $itemtypeWithdraw
					]);
					$rowCountTrans = $getCountTrans->fetch(PDO::FETCH_ASSOC);
					$count_trans = $rowCountTrans["C_TRANS"];
				}else{
					$getCountTrans = $conoracle->prepare("SELECT COUNT(dps.SEQ_NO) as C_TRANS FROM dpdeptstatement dps 
														WHERE dps.deptaccount_no = :deptaccount_no and SUBSTR(dps.DEPTITEMTYPE_CODE,0,1) = 'W' and dps.item_status = '1' ".$queryCheckPeriod);
					$getCountTrans->execute([
						':deptaccount_no' => $deptaccount_no
					]);
					$rowCountTrans = $getCountTrans->fetch(PDO::FETCH_ASSOC);
					$count_trans = $rowCountTrans["C_TRANS"];
				}
			}
		}
		if($count_trans > $rowContDeptType["MAXWITHD_INMONTH"]){
			$getContDeptTypeFee = $conoracle->prepare("SELECT CHARGE_FLAG,s_chrg_amt1 as MIN_FEE,s_chrg_perc1 as PERCENT_FEE,s_chrg_amt2 as MAX_FEE 
														FROM dpdepttype WHERE depttype_code = :depttype_code");
			$getContDeptTypeFee->execute([':depttype_code' => $rowType["DEPTTYPE_CODE"]]);
			$rowContFee = $getContDeptTypeFee->fetch(PDO::FETCH_ASSOC);
			if($rowContFee["CHARGE_FLAG"] == '1'){
				$penalty_amt = $rowContFee["PERCENT_FEE"] * $dataComing["amt_transfer"];
			}
			if($penalty_amt < $rowContFee["MIN_FEE"]){
				$penalty_amt = $rowContFee["MIN_FEE"];
			}
			if($penalty_amt > $rowContFee["MAX_FEE"]){
				$penalty_amt = $rowContFee["MAX_FEE"];
			}
		}
		
		$getDataUser = $conmysql->prepare("SELECT citizen_id FROM gcbindaccount WHERE deptaccount_no_coop = :deptaccount_no 
											and member_no = :member_no and bindaccount_status = '1'");
		$getDataUser->execute([
			':deptaccount_no' => $deptaccount_no,
			':member_no' => $payload["member_no"]
		]);
		$rowDataUser = $getDataUser->fetch(PDO::FETCH_ASSOC);
		$dateOperC = date('c');
		$dateOper = date('Y-m-d H:i:s',strtotime($dateOperC));
		$arrSendData = array();
		$arrVerifyToken = array();
		$arrVerifyToken['exp'] = time() + 300;
		$arrVerifyToken['sigma_key'] = $dataComing["sigma_key"];
		$arrVerifyToken["coop_key"] = $config["COOP_KEY"];
		$arrVerifyToken['tran_date'] = $dateOper;
		$arrVerifyToken['amt_transfer'] = $amt_transfer;
		$arrVerifyToken['bank_account'] = $dataComing["bank_account_no"];
		$arrVerifyToken['citizen_id'] = $rowDataUser["citizen_id"];
		$verify_token =  $jwt_token->customPayload($arrVerifyToken, $config["SIGNATURE_KEY_VERIFY_API"]);
		$arrSendData["verify_token"] = $verify_token;
		$arrSendData["app_id"] = $config["APP_ID"];
		$responseAPI = $lib->posting_data($config["URL_API_COOPDIRECT"].$rowDataDeposit["link_inquirywithd_coopdirect"],$arrSendData);
		if(!$responseAPI["RESULT"]){
			$filename = basename(__FILE__, '.php');
			$arrayResult['RESPONSE_CODE'] = "WS0027";
			$arrayStruc = [
				':member_no' => $payload["member_no"],
				':id_userlogin' => $payload["id_userlogin"],
				':operate_date' => $dateOper,
				':deptaccount_no' => $deptaccount_no,
				':amt_transfer' => $amt_transfer,
				':response_code' => $arrayResult['RESPONSE_CODE'],
				':response_message' => $responseAPI["RESPONSE_MESSAGE"] ?? "ไม่สามารถติดต่อ CoopDirect Server ได้เนื่องจากไม่ได้ Allow IP ไว้"
			];
			$log->writeLog('withdrawtrans',$arrayStruc);
			$message_error = "ไม่สามารถติดต่อ CoopDirect Server เพราะ ".$responseAPI["RESPONSE_MESSAGE"]."\n".json_encode($arrVerifyToken);
			$lib->sendLineNotify($message_error);
			$func->MaintenanceMenu($dataComing["menu_component"]);
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
		$arrResponse = json_decode($responseAPI);
		if($arrResponse->RESULT){
			$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
			if($penalty_amt > 0){
				$arrayCaution['RESPONSE_MESSAGE'] = $configError["CAUTION_WITHDRAW"][0][$lang_locale];
				$arrayCaution['CANCEL_TEXT'] = $configError["BUTTON_TEXT"][0]["CANCEL_TEXT"][0][$lang_locale];
				$arrayCaution['CONFIRM_TEXT'] = $configError["BUTTON_TEXT"][0]["CONFIRM_TEXT"][0][$lang_locale];
				$arrayResult['CAUTION'] = $arrayCaution;
			}
			$arrayResult['PENALTY_AMT'] = $penalty_amt;
			$arrayResult['PENALTY_AMT_FORMAT'] = number_format($penalty_amt,2);
			if($rowDataDeposit["bank_code"] == '025'){
				$fetchMemberName = $conoracle->prepare("SELECT MP.PRENAME_DESC,MB.MEMB_NAME,MB.MEMB_SURNAME 
														FROM MBMEMBMASTER MB LEFT JOIN MBUCFPRENAME MP ON MB.PRENAME_CODE = MP.PRENAME_CODE
														WHERE MB.member_no = :member_no");
				$fetchMemberName->execute([
					':member_no' => $member_no
				]);
				$rowMember = $fetchMemberName->fetch(PDO::FETCH_ASSOC);
				$arrayResult['ACCOUNT_NAME'] = $rowMember["PRENAME_DESC"].$rowMember["MEMB_NAME"].' '.$rowMember["MEMB_SURNAME"];
				$arrayResult['SOURCE_REFNO'] = $arrResponse->SOURCE_REFNO;
				$arrayResult['ETN_REFNO'] = $arrResponse->ETN_REFNO;
			}else{
				$arrayResult['ACCOUNT_NAME'] = $arrResponse->ACCOUNT_NAME;
			}
			$arrayResult['FEE_AMT'] = $arrResponse->FEE_AMT;
			$arrayResult['FEE_AMT_FORMAT'] = number_format($arrResponse->FEE_AMT,2);
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0038";
			$arrayStruc = [
				':member_no' => $payload["member_no"],
				':id_userlogin' => $payload["id_userlogin"],
				':operate_date' => $dateOper,
				':deptaccount_no' => $deptaccount_no,
				':amt_transfer' => $amt_transfer,
				':response_code' => $arrResponse->RESPONSE_CODE,
				':response_message' => $arrResponse->RESPONSE_MESSAGE
			];
			$log->writeLog('withdrawtrans',$arrayStruc);
			if(isset($configError[$rowDataDeposit["bank_short_ename"]."_ERR"][0][$arrResponse->RESPONSE_CODE][0][$lang_locale])){
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$rowDataDeposit["bank_short_ename"]."_ERR"][0][$arrResponse->RESPONSE_CODE][0][$lang_locale];
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			}
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
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
