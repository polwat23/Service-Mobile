<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanStatement')){
		$arrayResult = array();
		$arrayGroupSTM = array();
		$limit = $func->getConstant('limit_stmloan');
		$arrayResult['LIMIT_DURATION'] = $limit;
		if($lib->checkCompleteArgument(["date_start"],$dataComing)){
			$date_before = $lib->convertdate($dataComing["date_start"],'y-n-d');
		}else{
			$date_before = date('Y-m-d',strtotime('-'.$limit.' months'));
		}
		if($lib->checkCompleteArgument(["date_end"],$dataComing)){
			$date_now = $lib->convertdate($dataComing["date_end"],'y-n-d');
		}else{
			$date_now = date('Y-m-d');
		}
		$contract_no = preg_replace('/\//','',$dataComing["contract_no"]);
		$getStatement = $conoracle->prepare("SELECT lit.LOANITEMTYPE_DESC AS TYPE_DESC,lsm.operate_date,lsm.principal_payment as PRN_PAYMENT,
											lsm.interest_payment as INT_PAYMENT,sl.payinslip_no
											FROM lncontstatement lsm LEFT JOIN LNUCFLOANITEMTYPE lit
											ON lsm.LOANITEMTYPE_CODE = lit.LOANITEMTYPE_CODE 
											LEFT JOIN slslippayindet sl ON lsm.loancontract_no = sl.loancontract_no and lsm.period = sl.period
											WHERE lsm.loancontract_no = :contract_no and lsm.operate_date
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
		if(sizeof($arrayGroupSTM) > 0 || isset($new_token)){
			$arrayResult["STATEMENT"] = $arrayGroupSTM;
			if(isset($new_token)){
				$arrayResult['NEW_TOKEN'] = $new_token;
			}
			$arrayResult["RESULT"] = TRUE;
			echo json_encode($arrayResult);
		}else{
			http_response_code(204);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		if($lang_locale == 'th'){
			$arrayResult['RESPONSE_MESSAGE'] = "ท่านไม่มีสิทธิ์ใช้งานเมนูนี้";
		}else{
			$arrayResult['RESPONSE_MESSAGE'] = "You not have permission for this menu";
		}
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	if($lang_locale == 'th'){
		$arrayResult['RESPONSE_MESSAGE'] = "มีบางอย่างผิดพลาดกรุณาติดต่อสหกรณ์ #WS4004";
	}else{
		$arrayResult['RESPONSE_MESSAGE'] = "Something wrong please contact cooperative #WS4004";
	}
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>