<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'log','logtransfer')){
		$arrayGroup = array();
		$fetLogTranfer = $conmysql->prepare("SELECT 
																	rl.id_receiveloan,
																	rl.member_no,
																	rl.request_amt, 
																	rl.deptaccount_no,
																	rl.loancontract_no, 
																	rl.request_date,
																	rl.status_flag,
																	rl.response_code,
																	rl.response_message,
																	rl.id_userlogin,
																	login.device_name
																	FROM logreceiveloan rl
																	LEFT JOIN gcuserlogin login
																	ON login.id_userlogin = rl.id_userlogin
																	ORDER BY rl.request_date DESC");
		$fetLogTranfer->execute();
		$formatDept = $func->getConstant('dep_format');
		while($rowLogTransfer = $fetLogTranfer->fetch(PDO::FETCH_ASSOC)){
			$arrLogTransfer = array();
			$arrLogTransfer["ID_RECEIVELOAN"] = $rowLogTransfer["id_receiveloan"];
			$arrLogTransfer["MEMBER_NO"] = $rowLogTransfer["member_no"];
			$arrLogTransfer["DEVICE_NAME"] = $rowLogTransfer["device_name"];
			$arrLogTransfer["REQUEST_AMT"] = number_format($rowLogTransfer["request_amt"],2);
			$arrLogTransfer["DEPTACCOUNT_NO"] =$lib->formataccount( $rowLogTransfer["deptaccount_no"],$formatDept);
			$arrLogTransfer["LOANCONTRACT_NO"] = $rowLogTransfer["loancontract_no"];
			$arrLogTransfer["REQUEST_DATE"] =  $lib->convertdate($rowLogTransfer["request_date"],'d m Y',true); 
			$arrLogTransfer["STATUS_FLAG"] = $rowLogTransfer["status_flag"];
			$arrLogTransfer["RESPONSE_CODE"] = $rowLogTransfer["response_code"];
			$arrLogTransfer["RESPONSE_MESSAGE"] = $rowLogTransfer["response_message"];
			$arrLogTransfer["ID_USERLOGIN"] = $rowLogTransfer["id_userlogin"];

			$arrayGroup[] = $arrLogTransfer;
		}
		$arrayResult["LOG_RECEIVELOAN"] = $arrayGroup;
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