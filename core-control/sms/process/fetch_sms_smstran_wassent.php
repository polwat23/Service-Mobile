<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'sms','processsmsservicefee')){
		$MonthNow = date("Ym");
		$arrayGroup = array();
		$fetchSmsTranWassent = $conoracle->prepare("SELECT count(sm.id_smssent) as round_send,sm.member_no,sm.deptaccount_no,sc.request_flat_date,sc.accrued_amt,
												sc.smscsp_pay_type
												FROM smstranwassent sm LEFT JOIN smsconstantperson sc ON sm.deptaccount_no = sc.smscsp_account
												WHERE sm.process_flag = '0' and sm.is_receive = '1' GROUP BY sm.member_no,sm.deptaccount_no");
		$fetchSmsTranWassent->execute();
		while($rowSmsTranWassent = $fetchSmsTranWassent->fetch(PDO::FETCH_ASSOC)){
			$arrGroupSmsTranWassent = array();
			$arrGroupSmsTranWassent["MEMBER_NO"] = $rowSmsTranWassent["MEMBER_NO"];
			$arrGroupSmsTranWassent["ACCRUED_AMT"] = number_format($rowSmsTranWassent["ACCRUED_AMT"],2);
			$arrGroupSmsTranWassent["PROCESS_ROUND"] = number_format($rowSmsTranWassent["ROUND_SEND"],0);
			$arrGroupSmsTranWassent["DEPTACCOUNT_NO"] = $lib->formataccount($rowSmsTranWassent["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
			if($rowSmsTranWassent["SMSCSP_PAY_TYPE"] == '1'){
				if($MonthNow > $rowSmsTranWassent["REQUEST_FLAT_DATE"]){
					$arrGroupSmsTranWassent["PAY_TYPE"] = '1';
				}else{
					$arrGroupSmsTranWassent["PAY_TYPE"] = '0';
				}
			}else{
				$arrGroupSmsTranWassent["PAY_TYPE"] = $rowSmsTranWassent["SMSCSP_PAY_TYPE"];
			}
			$arrayGroup[] = $arrGroupSmsTranWassent;
		}
		$arrayResult["SMS_TRAN_WASSENT"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		require_once('../../../include/exit_footer.php');
		
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../include/exit_footer.php');
		
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../include/exit_footer.php');
	
}
?>