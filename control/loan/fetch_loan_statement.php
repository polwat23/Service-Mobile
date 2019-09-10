<?php
require_once('../../autoload.php');

if(isset($dataComing["access_token"]) && isset($dataComing["unique_id"])
&& isset($dataComing["user_type"]) && isset($dataComing["menu_component"]) && isset($dataComing["contract_no"]) && isset($dataComing["refresh_token"])){
	$is_accessToken = $api->check_accesstoken($dataComing["access_token"],$conmysql);
	$new_token = null;
	if(!$is_accessToken){
		$is_refreshToken_arr = $api->refresh_accesstoken($dataComing["refresh_token"],$dataComing["unique_id"],$conmysql,$lib,$dataComing["channel"]);
		if(!$is_refreshToken_arr){
			$arrayResult['RESPONSE_CODE'] = "SQL409";
			$arrayResult['RESPONSE'] = "Invalid Access Maybe AccessToken and RefreshToken is not correct";
			$arrayResult['RESULT'] = FALSE;
			http_response_code(203);
			echo json_encode($arrayResult);
			exit();
		}else{
			$new_token = $is_refreshToken_arr["ACCESS_TOKEN"];
		}
	}
	if($func->check_permission($dataComing["user_type"],$dataComing["menu_component"],$conmysql,'LoanStatement')){
		$arrayResult = array();
		$arrayGroupSTM = array();
		$limit = $func->getConstant('limit_stmloan',$conmysql);
		$arrayResult['LIMIT_DURATION'] = $limit;
		if(isset($dataComing["date_start"])){
			$date_before = $lib->convertdate($dataComing["date_start"],'y-n-d');
		}else{
			$date_before = date('Y-m-d',strtotime('-'.$limit.' months'));
		}
		$date_now = date('Y-m-d');
		$contract_no = preg_replace('/\//','',$dataComing["contract_no"]);
		$getLoanMaster = $conoracle->prepare("SELECT startcont_date,period_payment,period_payamt as PERIOD,
											LAST_PERIODPAY as LAST_PERIOD FROM lncontmaster WHERE loancontract_no = :contract_no");
		$getLoanMaster->execute([':contract_no' => $contract_no]);
		$rowLoanMaster = $getLoanMaster->fetch();
		$arrayResult["STARTCONT_DATE"] = $lib->convertdate($rowLoanMaster["STARTCONT_DATE"],'D m Y');
		$arrayResult["PERIOD_PAYMENT"] = number_format($rowLoanMaster["PERIOD_PAYMENT"],2);
		$arrayResult["PERIOD"] = $rowLoanMaster["LAST_PERIOD"].' / '.$rowLoanMaster["PERIOD"];
		$getStatement = $conoracle->prepare("SELECT lit.LOANITEMTYPE_DESC AS TYPE_DESC,lsm.operate_date,lsm.principal_payment as PRN_PAYMENT,
											lsm.interest_payment as INT_PAYMENT,sl.payinslip_no
											FROM lncontstatement lsm LEFT JOIN LNUCFLOANITEMTYPE lit
											ON lsm.LOANITEMTYPE_CODE = lit.LOANITEMTYPE_CODE 
											LEFT JOIN slslippayindet sl ON lsm.loancontract_no = sl.loancontract_no and lsm.period = sl.period
											WHERE lsm.loancontract_no = :contract_no and lsm.ENTRY_DATE
											BETWEEN to_date(:datebefore,'YYYY-MM-DD') and to_date(:datenow,'YYYY-MM-DD') ORDER BY lsm.SEQ_NO DESC");
		$getStatement->execute([
			':contract_no' => $contract_no,
			':datebefore' => $date_before,
			':datenow' => $date_now
		]);
		while($rowStm = $getStatement->fetch()){
			$arrSTM = array();
			$arrSTM["TYPE_DESC"] = $rowStm["TYPE_DESC"];
			$arrSTM["SLIP_NO"] = $rowStm["PAYINSLIP_NO"];
			$arrSTM["OPERATE_DATE"] = $lib->convertdate($rowStm["OPERATE_DATE"],'D m Y');
			$arrSTM["PRN_PAYMENT"] = number_format($rowStm["PRN_PAYMENT"],2);
			$arrSTM["INT_PAYMENT"] = number_format($rowStm["INT_PAYMENT"],2);
			$arrSTM["SUM_PAYMENT"] = number_format($rowStm["INT_PAYMENT"] + $rowStm["PRN_PAYMENT"],2);
			$arrayGroupSTM[] = $arrSTM;
		}
		$arrayResult["STATEMENT"] = $arrayGroupSTM;
		if(isset($new_token)){
			$arrayResult['NEW_TOKEN'] = $new_token;
		}
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE_CODE'] = "PARAM500";
		$arrayResult['RESPONSE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(203);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "PARAM400";
	$arrayResult['RESPONSE'] = "Not complete parameter";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(203);
	echo json_encode($arrayResult);
	exit();
}
?>