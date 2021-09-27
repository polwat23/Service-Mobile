<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','processmonthlybilling')){
		$getProcessList = $conmysql->prepare("SELECT kpslip_no,amt_transfer,pay_date,process_status FROM gcprocesspaymentmonthly");
		$getProcessList->execute();
		$arrGrpProcess = array();
		while($rowProcess = $getProcessList->fetch(PDO::FETCH_ASSOC)){
			$arrProcess = array();
			$arrProcess["KPSLIP_NO"] = $rowProcess["kpslip_no"];
			$arrProcess["AMT_TRANSFER"] = number_format($rowProcess["amt_transfer"],2);
			$arrProcess["PAY_DATE"] = $lib->convertdate($rowProcess["pay_date"],'d M Y');
			$arrProcess["PAY_DATE_RAW"] = $rowProcess["pay_date"];
			$arrProcess["PROCESS_STATUS"] = $rowProcess["process_status"];
			$arrProcess["PROCESS_STATUS_DESC"] = $rowProcess["process_status"] == '0' ? "ยังไม่ได้ประมวล" : "ประมวลเรียบร้อย";
			$arrGrpProcess[] = $arrProcess;
		}
		$arrayResult['PROCESS_LIST'] = $arrGrpProcess;
		$arrayResult['RESULT'] = TRUE;
		require_once('../../../../include/exit_footer.php');
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../../include/exit_footer.php');
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../../include/exit_footer.php');
}
?>