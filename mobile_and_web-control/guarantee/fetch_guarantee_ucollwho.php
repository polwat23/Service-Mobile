<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'GuaranteeInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		
		$arrayGroupLoan = array();
		$getUcollwho = $conoracle->prepare("SELECT
											LCC.LOANCONTRACT_NO AS LOANCONTRACT_NO,
											LNTYPE.loantype_desc as TYPE_DESC,
											PRE.PRENAME_DESC,MEMB.MEMB_NAME,MEMB.MEMB_SURNAME,
											
											LCM.MEMBER_NO AS MEMBER_NO,
											NVL(LCM.LOANAPPROVE_AMT,0) as LOANAPPROVE_AMT,
											NVL(LCM.principal_balance,0) as LOAN_BALANCE,
											NVL(LCC.COLLACTIVE_AMT,0) as COLLACTIVE_AMT
											FROM
											LNCONTCOLL LCC LEFT JOIN LNCONTMASTER LCM ON  LCC.LOANCONTRACT_NO = LCM.LOANCONTRACT_NO
											LEFT JOIN MBMEMBMASTER MEMB ON LCM.MEMBER_NO = MEMB.MEMBER_NO
											LEFT JOIN MBUCFPRENAME PRE ON MEMB.PRENAME_CODE = PRE.PRENAME_CODE
											LEFT JOIN lnloantype LNTYPE  ON LCM.loantype_code = LNTYPE.loantype_code
											WHERE
											LCM.CONTRACT_STATUS > 0 and LCM.CONTRACT_STATUS <> 8
											AND LCC.LOANCOLLTYPE_CODE = '01'
											AND LCC.COLL_STATUS = '1'
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
			$arrayColl["LOAN_BALANCE"] = number_format($rowUcollwho["LOAN_BALANCE"],2);
			$arrayColl["APPROVE_AMT"] = number_format($rowUcollwho["LOANAPPROVE_AMT"],2);
			$arrayColl["GUARANTEE_AMT"] = number_format($rowUcollwho["COLLACTIVE_AMT"],2);
			$arrayColl["FULL_NAME"] = $rowUcollwho["PRENAME_DESC"].$rowUcollwho["MEMB_NAME"].' '.$rowUcollwho["MEMB_SURNAME"];
			$arrayGroupLoan[] = $arrayColl;
		}
		$getMembType = $conoracle->prepare("SELECT lg.MBTYPEPERM_FLAG,lg.MANGRTPERMGRP_CODE,m.MEMBTYPE_CODE,lg.MANGRTTIME_TYPE
										,sh.LAST_PERIOD,m.MEMBER_DATE,m.SALARY_AMOUNT,sh.SHARESTK_AMT * 10 as SHARESTK_VALUE,
										mg.MEMBGROUP_CONTROL
										FROM mbmembmaster m LEFT JOIN LNGRPMANGRTPERM lg ON m.member_type = lg.member_type
										LEFT JOIN shsharemaster sh ON m.member_no = sh.member_no
										LEFT JOIN mbucfmembgroup mg ON m.membgroup_code = mg.membgroup_code
										WHERE m.member_no = :member_no and lg.MANGRTPERMGRP_CODE <> '57'");
		$getMembType->execute([':member_no' => $member_no]);
		$rowMembType = $getMembType->fetch(PDO::FETCH_ASSOC);
		if($rowMembType["MBTYPEPERM_FLAG"] == 3){
			$timecheck = $rowMembType["LAST_PERIOD"];
		}else{
			$timecheck = $lib->count_duration($rowMembType["MEMBER_DATE"],'m');
		}
		if($rowMembType["MBTYPEPERM_FLAG"] == 1){
			$sqlCondition = $conoracle->prepare("SELECT MULTIPLE_SHARE, MULTIPLE_SALARY, MAXGRT_AMT 
											FROM lngrpmangrtpermdet
											WHERE mangrtpermgrp_code = :perm_code
											and :timechk between startmember_time and endmember_time 
											and membtype_code = :membtype_code order by seq_no");
			$sqlCondition->execute([
				':perm_code' => $rowMembType['MANGRTPERMGRP_CODE'],
				':timechk' => $timecheck,
				':membtype_code' => $rowMembType['MEMBTYPE_CODE']
			]);
		}else{
			$sqlCondition = $conoracle->prepare("SELECT MULTIPLE_SHARE, MULTIPLE_SALARY, MAXGRT_AMT 
											FROM LNGRPMANGRTPERMDET
											WHERE MANGRTPERMGRP_CODE = :perm_code
											AND :timechk BETWEEN STARTMEMBER_TIME AND ENDMEMBER_TIME ORDER BY SEQ_NO");
			$sqlCondition->execute([
				':perm_code' => $rowMembType['MANGRTPERMGRP_CODE'],
				':timechk' => $timecheck
			]);
		}
		$maxcredit = 90000000;
		while($rowCondition = $sqlCondition->fetch(PDO::FETCH_ASSOC)){
			$maxgrt_amt = $rowCondition['MAXGRT_AMT'];
			$collcredit = ($rowMembType['SALARY_AMOUNT'] * $rowCondition['MULTIPLE_SALARY']) + 
			($rowMembType['SHARESTK_VALUE'] * $rowCondition['MULTIPLE_SHARE']);
			if($collcredit > $maxgrt_amt){
				$collcredit = $maxgrt_amt;
			}
			if($collcredit < $maxcredit){
				$maxcredit = $collcredit;
			}
		}
		$creditSQL = $conoracle->prepare("SELECT sum( lnc.COLLACTIVE_AMT) as COLLACTIVE_AMT
										FROM LNCONTCOLL lnc LEFT JOIN LNCONTMASTER lnm ON lnc.LOANCONTRACT_NO = lnm.LOANCONTRACT_NO
										LEFT JOIN LNLOANTYPE lnt ON lnm.loantype_code = lnt.loantype_code
										WHERE 
										lnc.loancolltype_code = '01' and lnc.ref_collno = :member_no 
										AND lnm.contract_status > 0 AND lnc.coll_status = 1");
		$creditSQL->execute([':member_no' => $member_no]);
		$rowCredit = $creditSQL->fetch(PDO::FETCH_ASSOC);
		$arrayResult['CONTRACT_COLL'] = $arrayGroupLoan;
		$arrayResult['LIMIT_GUARANTEE'] = $maxcredit - $rowCredit["COLLACTIVE_AMT"];
		$arrayResult['MAX_CREDIT'] = $maxcredit;
		if($rowMembType["MEMBGROUP_CONTROL"] == '06'){
			$getBalance = $conoracle->prepare("SELECT NVL(SUM(principal_balance),0) as BALANCE_ALL FROM lncontmaster WHERE member_no = :member_no 
												and loantype_code IN('60','61','62') and contract_status > '0' and contract_status <> '8'");
			$getBalance->execute([':member_no' => $member_no]);
			$rowBalance = $getBalance->fetch(PDO::FETCH_ASSOC);
			
			$getBalanceGrp = $conoracle->prepare("SELECT NVL(SUM(principal_balance),0) as BALANCE_ALL FROM lncontmaster WHERE member_no = :member_no 
												and loantype_code IN('58') and contract_status > '0' and contract_status <> '8'");
			$getBalanceGrp->execute([':member_no' => $member_no]);
			$rowBalanceGrp = $getBalanceGrp->fetch(PDO::FETCH_ASSOC);
			$percentShare = $rowMembType['SHARESTK_VALUE'] * 10;
			$percentSalary = $rowMembType['SALARY_AMOUNT'] * 72;
			$valueNormal = min($percentShare,$percentSalary);
			$valueNormal -= $rowBalance["BALANCE_ALL"];
			$valueNormalGrp = 50000 - $rowBalanceGrp["BALANCE_ALL"];
			$arrLoanGuaAmt = array();
			$arrLoanGuaAmt[0]["LABEL"] = "สิทธิค้ำวงเงินค้ำสามัญ";
			$arrLoanGuaAmt[0]["VALUE"] = number_format($valueNormal,2)." บาท";
			$arrLoanGuaAmt[1]["LABEL"] = "สิทธิค้ำประกันเงินกู้สามัญพัฒนาคุณภาพชีวิต";
			$arrLoanGuaAmt[1]["VALUE"] = number_format($valueNormalGrp,2)." บาท";
		}else{
			$getLoantypeCustom = $conoracle->prepare("SELECT l.MAXLOAN_AMT,l.MULTIPLE_SALARY FROM lnloantypecustom l,mbmembmaster m 
													where l.loantype_code = '53' 
													AND TRUNC(MONTHS_BETWEEN (SYSDATE,m.member_date ) /12 *12) between
													l.startmember_time and l.endmember_time
													and m.member_no = :member_no");
			$getLoantypeCustom->execute([':member_no' => $member_no]);
			$rowCustom = $getLoantypeCustom->fetch(PDO::FETCH_ASSOC);
			$getBalance = $conoracle->prepare("SELECT NVL(SUM(principal_balance),0) as BALANCE_ALL FROM lncontmaster WHERE member_no = :member_no 
												and loantype_code IN('53','58') and contract_status > '0' and contract_status <> '8'");
			$getBalance->execute([':member_no' => $member_no]);
			$rowBalance = $getBalance->fetch(PDO::FETCH_ASSOC);
			$percentShare = $rowMembType['SHARESTK_VALUE'] * 0.30;
			$percentSalary = $rowMembType['SALARY_AMOUNT'] * $rowCustom["MULTIPLE_SALARY"];
			$valueNormal = min($percentShare,$percentSalary,$rowCustom["MAXLOAN_AMT"]);
			$valueNormal -= $rowBalance["BALANCE_ALL"];
			if($valueNormal < 0){
				$valueNormal = 0;
			}
			$arrLoanGuaAmt = array();
			$arrLoanGuaAmt[0]["LABEL"] = "สิทธิค้ำวงเงินค้ำสามัญ";
			$arrLoanGuaAmt[0]["VALUE"] = number_format($valueNormal,2)." บาท";

		}
		$arrayResult['LIMIT_GUARANTEE_LIST'] = $arrLoanGuaAmt;
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