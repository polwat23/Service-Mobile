<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DeptTicket')){
		$member_no = $payload["ref_memno"];
		$arrAllAccount = array();
		
		$limit = $func->getConstant('limit_ticket');
		$arrayResult['LIMIT_TICKET'] = $limit;
		$date_before = date('Y-m-d',strtotime('-'.$limit.' months'));
		$date_now = date('Y-m-d');
		
		$getSumAllAccount = $conoracle->prepare("SELECT SUM(DM.PRNC_AMT) AS SUM_BALANCE 
											  FROM (SELECT DPDEPTPRNCFIXED.PRNC_AMT FROM DPDEPTMASTER, DPDEPTPRNCFIXED
											  WHERE DPDEPTPRNCFIXED.DEPTACCOUNT_NO = DPDEPTMASTER.DEPTACCOUNT_NO     
											  AND DPDEPTMASTER.DEPTGROUP_CODE = DPDEPTPRNCFIXED.DEPTGROUP_CODE    
											  AND TRIM(DPDEPTMASTER.MEMBER_NO) = :member_no   
											  AND DPDEPTPRNCFIXED.DEPTGROUP_CODE = '03'   
											  AND DPDEPTMASTER.DEPTOPEN_DATE between  to_date(:datebefore,'YYYY-MM-DD') AND to_date(:datenow,'YYYY-MM-DD')   
											  AND DPDEPTMASTER.DEPTCLOSE_STATUS = 0  
											  GROUP BY  DPDEPTMASTER.MEMBER_NO, DPDEPTMASTER.DEPTACCOUNT_NO, DPDEPTPRNCFIXED.PRNC_AMT) dm ");
		$getSumAllAccount->execute([
				':member_no' => $member_no,
				':datebefore' => $date_before,
				':datenow' => $date_now
			]);
		$rowSumbalance = $getSumAllAccount->fetch(PDO::FETCH_ASSOC);
		$arrayResult['SUM_BALANCE'] = number_format($rowSumbalance["SUM_BALANCE"],2);
		
		$formatDept = $func->getConstant('dep_format');
		$formatDeptHidden = $func->getConstant('hidden_dep');
		$getAccount = $conoracle->prepare("SELECT DPDEPTPRNCFIXED.PRNC_DATE,DPDEPTMASTER.DEPTACCOUNT_NAME,DPDEPTMASTER.MEMBER_NO,DPDEPTMASTER.DEPTACCOUNT_NO,   
									DPDEPTPRNCFIXED.INTEREST_RATE,DPDEPTPRNCFIXED.PRNCDUE_NMONTH,DPDEPTPRNCFIXED.PRNCDUE_DATE AS PRNCDUE_DATE,  DPDEPTTYPE.DEPTTYPE_DESC,     
									DECODE(DPDEPTMASTER.DEPTCLOSE_STATUS, 1 , 0 , ((DPDEPTPRNCFIXED.PRNC_AMT * (DPDEPTPRNCFIXED.PRNCDUE_DATE - MAX(DECODE(DPINTDUEDATE.RECV_INTFLAG, 1, NVL(DPINTDUEDATE.END_CALINT_DATE, DPDEPTPRNCFIXED.PRNC_DATE), DPDEPTPRNCFIXED.PRNC_DATE ) ) ) / 365 * DPDEPTPRNCFIXED.INTEREST_RATE/ 100) ) ) as INT_AMT,   
									DPDEPTPRNCFIXED.PRNC_AMT  AS BALANCE 
									FROM DPDEPTMASTER, DPDEPTPRNCFIXED, DPDEPTTYPE, DPINTDUEDATE
									WHERE DPDEPTPRNCFIXED.DEPTACCOUNT_NO = DPDEPTMASTER.DEPTACCOUNT_NO       
									AND DPDEPTMASTER.DEPTTYPE_CODE = DPDEPTTYPE.DEPTTYPE_CODE    
									AND DPDEPTMASTER.DEPTGROUP_CODE = DPDEPTPRNCFIXED.DEPTGROUP_CODE    
									AND DPDEPTMASTER.DEPTGROUP_CODE = DPDEPTTYPE.DEPTGROUP_CODE    
									AND DPDEPTPRNCFIXED.PRINC_ID = DPINTDUEDATE.PRINC_ID(+) 
									AND TRIM(DPDEPTMASTER.MEMBER_NO) = :member_no
									AND DPDEPTTYPE.DEPTGROUP_CODE = '03'    
									AND DPDEPTMASTER.DEPTOPEN_DATE between to_date(:datebefore,'YYYY-MM-DD') AND to_date(:datenow,'YYYY-MM-DD')    
									AND DPDEPTMASTER.DEPTCLOSE_STATUS = 0  
									GROUP BY DPINTDUEDATE.PRINC_ID,DPDEPTPRNCFIXED.PRNC_DATE, DPDEPTMASTER.DEPTACCOUNT_NAME,DPDEPTMASTER.MEMBER_NO,DPDEPTMASTER.DEPTACCOUNT_NO,   
									DPDEPTPRNCFIXED.INTEREST_RATE,DPDEPTPRNCFIXED.PRNCDUE_NMONTH,DPDEPTPRNCFIXED.PRNCDUE_DATE,DPDEPTMASTER.DEPTCLOSE_STATUS,
									DPDEPTPRNCFIXED.PRNC_AMT,DPDEPTPRNCFIXED.PRNCDUE_DATE,DPDEPTTYPE.DEPTTYPE_DESC");
		$getAccount->execute([
				':member_no' => $member_no,
				':datebefore' => $date_before,
				':datenow' => $date_now
			]);
		while($rowAccount = $getAccount->fetch(PDO::FETCH_ASSOC)){
			$arrAccount = array();
			$arrGroupAccount = array();
			$account_no = $lib->formataccount($rowAccount["DEPTACCOUNT_NO"],$formatDept);
			$arrAccount["DEPTACCOUNT_NAME"] = preg_replace('/\"/','',TRIM($rowAccount["DEPTACCOUNT_NAME"]));
			$arrAccount["DEPTACCOUNT_NO"] = $account_no;
			$arrAccount["BALANCE"] = number_format($rowAccount["BALANCE"],2);
			$arrAccount["INTEREST_RATE"] = number_format($rowAccount["INTEREST_RATE"],2);
			$arrAccount["PRNCDUE_DATE"] = $lib->convertdate($rowAccount["PRNCDUE_DATE"],'y-n-d');
			$arrAccount["PRNCDUE_NMONTH"] = $rowAccount["PRNCDUE_NMONTH"];
			$arrAccount["INT_AMT"] = number_format($rowAccount["INT_AMT"],2);
			$arrGroupAccount['TYPE_ACCOUNT'] = $rowAccount["DEPTTYPE_DESC"];
			if(array_search($rowAccount["DEPTTYPE_DESC"],array_column($arrAllAccount,'TYPE_ACCOUNT')) === False){
				($arrGroupAccount['ACCOUNT'])[] = $arrAccount;
				$arrAllAccount[] = $arrGroupAccount;
			}else{
				($arrAllAccount[array_search($rowAccount["DEPTTYPE_DESC"],array_column($arrAllAccount,'TYPE_ACCOUNT'))]["ACCOUNT"])[] = $arrAccount;
			}
		}
		$arrayResult['DETAIL_TICKET'] = $arrAllAccount;
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