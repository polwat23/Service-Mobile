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
										,sh.LAST_PERIOD,m.MEMBER_DATE,m.SALARY_AMOUNT,sh.SHARESTK_AMT * 10 as SHARESTK_VALUE
										FROM mbmembmaster m LEFT JOIN LNGRPMANGRTPERM lg ON m.member_type = lg.member_type
										LEFT JOIN shsharemaster sh ON m.member_no = sh.member_no
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