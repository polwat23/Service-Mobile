<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','deptaccount_no','amt_transfer'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransactionWithdrawDeposit')){
		$clientWS = new SoapClient("http://web.siamcoop.com/CORE/GCOOP/WcfService125/n_deposit.svc?singleWsdl");
		try {
			$argumentWS = [
							"as_wspass" => "Data Source=web.siamcoop.com/gcoop;Persist Security Info=True;User ID=iscorfscmas;Password=iscorfscmas;Unicode=True;coop_id=050001;coop_control=050001;",
							"as_account_no" => $dataComing["deptaccount_no"],
							"as_itemtype_code" => "WTX",
							"adc_amt" => $dataComing["deptaccount_no"],
							"adtm_date" => date('c')
			];
			$resultWS = $clientWS->__call("of_chk_withdrawcount_amt", array($argumentWS));
			$arrayResult['FEE_AMT'] = $resultWS->of_chk_withdrawcount_amtResult;
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}catch(Throwable $e){
			$arrayResult['RESPONSE_CODE'] = "WS2001";
			$arrayResult['RESPONSE_MESSAGE'] = $e->getMessage();
			$arrayResult['RESULT'] = FALSE;
			http_response_code(400);
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = "Not complete argument";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>