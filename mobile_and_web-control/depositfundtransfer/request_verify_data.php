<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransactionDeposit')){
		$is_separate = $func->getConstant("separate_limit_amount_trans_online");
		$getLimitAllDay = $conoracle->prepare("SELECT total_limit FROM atmucftranslimit WHERE tran_desc = 'MOBILE_APP' and tran_status = 1");
		$getLimitAllDay->execute();
		$rowLimitAllDay = $getLimitAllDay->fetch(PDO::FETCH_ASSOC);
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		if($is_separate){
			$getSumAllDay = $conoracle->prepare("SELECT NVL(SUM(DEPTITEM_AMT),0) AS SUM_AMT FROM DPDEPTSTATEMENT 
												WHERE TO_CHAR(OPERATE_DATE,'YYYY-MM-DD') = TO_CHAR(SYSDATE,'YYYY-MM-DD') 
												and ITEM_STATUS = '1' and entry_id IN('MCOOP','ICOOP') and SUBSTR(deptitemtype_code,0,1) = 'D'");
		}else{
			$getSumAllDay = $conoracle->prepare("SELECT NVL(SUM(DEPTITEM_AMT),0) AS SUM_AMT FROM DPDEPTSTATEMENT 
												WHERE TO_CHAR(OPERATE_DATE,'YYYY-MM-DD') = TO_CHAR(SYSDATE,'YYYY-MM-DD') 
												and ITEM_STATUS = '1' and entry_id IN('MCOOP','ICOOP')");
		}
		$getSumAllDay->execute();
		$rowSumAllDay = $getSumAllDay->fetch(PDO::FETCH_ASSOC);
		if(($rowSumAllDay["SUM_AMT"] + $dataComing["amt_transfer"]) > $rowLimitAllDay["TOTAL_LIMIT"]){
			$arrayResult["RESPONSE_CODE"] = 'WS0043';
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
		$fetchMemberName = $conoracle->prepare("SELECT MP.PRENAME_DESC,MB.MEMB_NAME,MB.MEMB_SURNAME 
													FROM MBMEMBMASTER MB LEFT JOIN MBUCFPRENAME MP ON MB.PRENAME_CODE = MP.PRENAME_CODE
													WHERE MB.member_no = :member_no");
		$fetchMemberName->execute([
			':member_no' => $member_no
		]);
		$rowMember = $fetchMemberName->fetch(PDO::FETCH_ASSOC);
		$account_name_th = $rowMember["PRENAME_DESC"].$rowMember["MEMB_NAME"].' '.$rowMember["MEMB_SURNAME"];
		$arrayResult['FEE_AMT'] = $func->getConstant("fee_deposit_ktb");
		$arrayResult['ACCOUNT_NAME'] = $account_name_th;
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