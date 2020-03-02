<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'log','loginlog')){
		$arrayGroup = array();
		$fetchBindAccountLog = $conmysql->prepare("SELECT unbin.id_logunbindaccount,unbin.member_no,unbin.id_userlogin,unbin.unbind_status,
		unbin.attempt_unbind_date,unbin.response_code,unbin.response_message,unbin.id_bindaccount,unbin.data_unbind_error,unbin.query_error,unbin.query_flag,login.channel,login.device_name
													FROM logunbindaccount unbin
													INNER JOIN gcuserlogin login
													 ON unbin.id_userlogin = login.id_userlogin");
		$fetchBindAccountLog->execute();
		while($rowBindAccountLog = $fetchBindAccountLog->fetch(PDO::FETCH_ASSOC)){
			$arrGroupBindAccountLog = array();
			$fetchBinAccountCoopNo = $conmysql->prepare("SELECT deptaccount_no_coop,deptaccount_no_bank FROM gcbindaccount WHERE id_bindaccount = '$rowBindAccountLog[id_bindaccount]' ");
			$fetchBinAccountCoopNo -> execute();
			$coop_no=$fetchBinAccountCoopNo-> fetch(PDO::FETCH_ASSOC);
			
			$arrGroupBindAccountLog["ID_LOGUNBINDACCOUNT"] = $rowBindAccountLog["id_logunbindaccount"];
			$arrGroupBindAccountLog["MEMBER_NO"] = $rowBindAccountLog["member_no"];
			$arrGroupBindAccountLog["UNBIND_STATUS"] = $rowBindAccountLog["unbind_status"];
			$arrGroupBindAccountLog["RESPONSE_CODE"] = $rowBindAccountLog["response_code"];
			$arrGroupBindAccountLog["ATTEMPT_UNBIND_DATE"] =  $lib->convertdate($rowBindAccountLog["attempt_unbind_date"],'d m Y',true); 
			$arrGroupBindAccountLog["RESPONSE_MESSAGE"] = $rowBindAccountLog["response_message"];
			$arrGroupBindAccountLog["DEVICE_NAME"] = $rowBindAccountLog["device_name"];
			$arrGroupBindAccountLog["CHANNEL"] = $rowBindAccountLog["channel"];
			$arrGroupBindAccountLog["ID_BIND_ACCOUNT"] = $rowBindAccountLog["id_bindaccount"];
			$arrGroupBindAccountLog["COOP_ACCOUNT_NO"] = $coop_no["deptaccount_no_coop"];
			$arrGroupBindAccountLog["BANK_ACCOUNT_NO"] = $coop_no["deptaccount_no_bank"];
			$arrGroupBindAccountLog["COOP_ACCOUNT_NO_FORMAT"]= $lib->formataccount($coop_no["deptaccount_no_coop"],$func->getConstant('dep_format'));
  		    $arrGroupBindAccountLog["DATA_UNBIND_ERROR"] = $rowBindAccountLog["data_unbind_error"];
			$arrGroupBindAccountLog["QUERY_ERROR"] = $rowBindAccountLog["query_error"];
			$arrGroupBindAccountLog["QUERY_FLAG"] = $rowBindAccountLog["query_flag"];
			
			$arrayGroup[] = $arrGroupBindAccountLog;
		}
		$arrayResult["UNBIND_ACCOUNT_LOG"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>