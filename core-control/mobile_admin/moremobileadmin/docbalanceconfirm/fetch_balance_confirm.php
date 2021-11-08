<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','docbalanceconfirm')){
		$arrayGroupAll = array();

		$fetchDocbalance= $conmysql->prepare("SELECT member_no, confirm_remark, confirm_status, balance_date ,url_path FROM confirm_balance");
		$fetchDocbalance->execute();
		while($rowDocbalance = $fetchDocbalance->fetch(PDO::FETCH_ASSOC)){
			$arrayData = array();
			$arrayData["MEMBER_NO"] = $rowDocbalance["member_no"];
			$arrayData["CONFIRM_REMARK"] = $rowDocbalance["confirm_remark"];
			$arrayData["FIRM_STATUS"] = $rowDocbalance["confirm_status"];
			$arrayData["URL_PATH"] = $rowDocbalance["url_path"];
			if($rowDocbalance["confirm_status"] == "1"){
				$arrayData["CONFIRM_STATUS"] = "ยืนยันยอดถูกต้อง";
			}else{
				$arrayData["CONFIRM_STATUS"] = "ยืนยันยอดไม่ถูกต้อง";
			}
			$arrayData["BALANCE_DATE"] = $lib->convertdate($rowDocbalance["balance_date"],'d m Y'); 
			$arrayData["BALANCE_DATE_FORMAR"] = $rowDocbalance["balance_date"];
			$arrayGroupAll[] = $arrayData;
		}
		$arrayResult["BALANCE"] = $arrayGroupAll;
		$arrayResult["RESULT"] = TRUE;
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