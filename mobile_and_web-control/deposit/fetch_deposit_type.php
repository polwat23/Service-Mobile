<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DepositInfo')){
		$member_no = $payload["ref_memno"];
		$date_now = $dataComing["date"] ??  $date_now = date('Y-m-d');
		$arrAllAccount = array();
		$getSumAllAccount = $conoracle->prepare("SELECT SUM(DPDEPTPRNCFIXED.PRNC_AMT) as SUM_BALANCE
										FROM DPDEPTMASTER, DPDEPTPRNCFIXED, DPDEPTTYPE, DPINTDUEDATE
										WHERE ( DPDEPTPRNCFIXED.DEPTACCOUNT_NO = DPDEPTMASTER.DEPTACCOUNT_NO ) 
										AND ( DPDEPTPRNCFIXED.MASTER_BRANCH_ID = DPDEPTMASTER.BRANCH_ID ) 
										AND ( DPDEPTMASTER.DEPTTYPE_CODE = DPDEPTTYPE.DEPTTYPE_CODE ) 
										AND ( DPDEPTMASTER.DEPTTYPE_BRANCH_ID = DPDEPTTYPE.BRANCH_ID ) 
										AND ( DPDEPTMASTER.DEPTGROUP_CODE = DPDEPTPRNCFIXED.DEPTGROUP_CODE ) 
										AND ( DPDEPTMASTER.DEPTGROUP_CODE = DPDEPTTYPE.DEPTGROUP_CODE ) 
										AND (DPDEPTPRNCFIXED.PRINC_ID = DPINTDUEDATE.PRINC_ID(+)) 
										AND ( ( TRIM(DPDEPTMASTER.MEMBER_NO) = :member_no) 
										AND ( DPDEPTTYPE.DEPTGROUP_CODE = '01' ) 
										AND (DPDEPTMASTER.DEPTOPEN_DATE <= TO_DATE(:datenow,'YYYY-MM-DD') ) 
										AND ((DPDEPTMASTER.DEPTCLOSE_DATE > TO_DATE(:datenow,'YYYY-MM-DD') 
										AND DPDEPTMASTER.DEPTCLOSE_STATUS = 1) OR DPDEPTMASTER.DEPTCLOSE_STATUS = 0) AND
										(DPDEPTMASTER.CANCEL_DATE < TO_DATE(:datenow,'YYYY-MM-DD') OR DPDEPTMASTER.CANCEL_DATE IS NULL) )
										ORDER BY DPDEPTPRNCFIXED.PRNCDUE_DATE ASC");
		$getSumAllAccount->execute([':member_no' => $member_no,
									':datenow' => $date_now
		]);
		$rowSumbalance = $getSumAllAccount->fetch(PDO::FETCH_ASSOC);
		$arrayResult['SUM_BALANCE'] = number_format($rowSumbalance["SUM_BALANCE"],2);
		$formatDept = $func->getConstant('dep_format');
		$formatDeptHidden = $func->getConstant('hidden_dep');
		
		$getAccount = $conoracle->prepare("SELECT DPDEPTPRNCFIXED.PRNC_DATE,
										DPDEPTMASTER.DEPTACCOUNT_NAME,
										DPDEPTMASTER.MEMBER_NO,
										SUBSTR(DPDEPTMASTER.DEPTPASSBOOK_NO,5) as DEPTPASSBOOK_NO,
										DPDEPTMASTER.DEPTACCOUNT_NO,
										DPDEPTPRNCFIXED.INTEREST_RATE,
										DPDEPTPRNCFIXED.PRNCDUE_NMONTH,
										NVL(DPDEPTPRNCFIXED.PRNCDUE_DATE, TO_DATE(:datenow,'YYYY-MM-DD') ) AS PRNCDUE_DATE,
										DPDEPTTYPE.DEPTTYPE_DESC,
										DPDEPTTYPE.DEPTTYPE_CODE,
										DPDEPTPRNCFIXED.PRNC_AMT,
										DECODE(DPDEPTMASTER.DEPTCLOSE_STATUS, 1 , 0 , ((DPDEPTPRNCFIXED.PRNC_AMT * (DPDEPTPRNCFIXED.PRNCDUE_DATE - MAX(DECODE(DPINTDUEDATE.RECV_INTFLAG, 1, NVL(DPINTDUEDATE.END_CALINT_DATE, DPDEPTPRNCFIXED.PRNC_DATE), DPDEPTPRNCFIXED.PRNC_DATE ) ) ) / 365 * DPDEPTPRNCFIXED.INTEREST_RATE/ 100) ) ) AS INT_AMT
										FROM DPDEPTMASTER, DPDEPTPRNCFIXED, DPDEPTTYPE, DPINTDUEDATE
										WHERE ( DPDEPTPRNCFIXED.DEPTACCOUNT_NO = DPDEPTMASTER.DEPTACCOUNT_NO ) 
										AND ( DPDEPTPRNCFIXED.MASTER_BRANCH_ID = DPDEPTMASTER.BRANCH_ID ) 
										AND ( DPDEPTMASTER.DEPTTYPE_CODE = DPDEPTTYPE.DEPTTYPE_CODE ) 
										AND ( DPDEPTMASTER.DEPTTYPE_BRANCH_ID = DPDEPTTYPE.BRANCH_ID ) 
										AND ( DPDEPTMASTER.DEPTGROUP_CODE = DPDEPTPRNCFIXED.DEPTGROUP_CODE ) 
										AND ( DPDEPTMASTER.DEPTGROUP_CODE = DPDEPTTYPE.DEPTGROUP_CODE ) 
										AND (DPDEPTPRNCFIXED.PRINC_ID = DPINTDUEDATE.PRINC_ID(+)) 
										AND ( ( TRIM(DPDEPTMASTER.MEMBER_NO) = :member_no) 
										AND ( DPDEPTTYPE.DEPTGROUP_CODE = '01' ) 
										AND (DPDEPTMASTER.DEPTOPEN_DATE <= TO_DATE(:datenow,'YYYY-MM-DD') ) 
										AND ((DPDEPTMASTER.DEPTCLOSE_DATE > TO_DATE(:datenow,'YYYY-MM-DD') 
										AND DPDEPTMASTER.DEPTCLOSE_STATUS = 1) OR DPDEPTMASTER.DEPTCLOSE_STATUS = 0) AND
										(DPDEPTMASTER.CANCEL_DATE < TO_DATE(:datenow,'YYYY-MM-DD') OR DPDEPTMASTER.CANCEL_DATE IS NULL) )
										GROUP BY DPINTDUEDATE.PRINC_ID,DPDEPTPRNCFIXED.PRNC_DATE,DPDEPTMASTER.DEPTACCOUNT_NAME,DPDEPTMASTER.MEMBER_NO,
										DPDEPTMASTER.DEPTPASSBOOK_NO,DPDEPTMASTER.DEPTACCOUNT_NO,DPDEPTPRNCFIXED.INTEREST_RATE,DPDEPTPRNCFIXED.PRNCDUE_NMONTH,
										DPDEPTPRNCFIXED.PRNCDUE_DATE,DPDEPTMASTER.DEPTCLOSE_STATUS,DPDEPTPRNCFIXED.PRNC_AMT,DPDEPTPRNCFIXED.PRNCDUE_DATE,
										DPDEPTTYPE.DEPTTYPE_DESC,DPDEPTMASTER.DEPTACCOUNT_NO,DPDEPTTYPE.DEPTTYPE_CODE
										ORDER BY DPDEPTPRNCFIXED.PRNCDUE_DATE ASC");
		$getAccount->execute([
				':member_no' => $member_no,
				':datenow' => $date_now
			]);
		while($rowAccount = $getAccount->fetch(PDO::FETCH_ASSOC)){
			$arrAccount = array();
			$arrGroupAccount = array();
			$account_no = $lib->formataccount($rowAccount["DEPTACCOUNT_NO"],$formatDept);
			$arrayHeaderAcc = array();
			$arrAccount["DEPTACCOUNT_NO"] = $account_no;
			$arrAccount["PRNC_DATE"] = $lib->convertdate($rowAccount["PRNC_DATE"],'d M Y');
			$arrAccount["DEPTACCOUNT_NAME"] = $rowAccount["DEPTACCOUNT_NAME"];
			$arrAccount["MEMBER_NO"] = $rowAccount["MEMBER_NO"];
			$arrAccount["DEPTPASSBOOK_NO"] = $rowAccount["DEPTPASSBOOK_NO"];
			$arrAccount["INTEREST_RATE"] =  number_format($rowAccount["INTEREST_RATE"],3);
			$arrAccount["PRNCDUE_NMONTH"] = $rowAccount["PRNCDUE_NMONTH"];
			$arrAccount["PRNCDUE_DATE"] = $lib->convertdate($rowAccount["PRNCDUE_DATE"],'d M Y');
			$arrAccount["PRNC_AMT"] = number_format($rowAccount["PRNC_AMT"],2);
			$arrAccount["INT_AMT"] = number_format($rowAccount["INT_AMT"],2);
			$arrGroupAccount['TYPE_ACCOUNT'] = $rowAccount["DEPTTYPE_DESC"];
			$arrGroupAccount['DEPT_TYPE_CODE'] = $rowAccount["DEPTTYPE_CODE"];
			if(array_search($rowAccount["DEPTTYPE_DESC"],array_column($arrAllAccount,'TYPE_ACCOUNT')) === False){
				($arrGroupAccount['ACCOUNT'])[] = $arrAccount;
				$arrAllAccount[] = $arrGroupAccount;
			}else{
				($arrAllAccount[array_search($rowAccount["DEPTTYPE_DESC"],array_column($arrAllAccount,'TYPE_ACCOUNT'))]["ACCOUNT"])[] = $arrAccount;
			}
		}
		
		$arrayResult['DETAIL_DEPOSIT'] = $arrAllAccount;
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