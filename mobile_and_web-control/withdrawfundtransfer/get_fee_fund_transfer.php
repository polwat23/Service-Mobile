<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','deptaccount_no','amt_transfer'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransactionWithdrawDeposit')){
		//$clientWS = new SoapClient("http://web.coopsiam.com/CORE/GCOOP/WcfService125/n_deposit.svc?singleWsdl");
		$operate_date = date('c');
		/*try {
			$argumentWS = [
							"as_wspass" => "Data Source=web.coopsiam.com/gcoop;Persist Security Info=True;User ID=iscorfscmas;Password=iscorfscmas;Unicode=True;coop_id=050001;coop_control=050001;",
							"as_account_no" => $dataComing["deptaccount_no"],
							"as_itemtype_code" => "WTX",
							"adc_amt" => $dataComing["deptaccount_no"],
							"adtm_date" => $operate_date
			];
			$resultWS = $clientWS->of_chk_withdrawcount_amt($argumentWS);
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
		}*/
		$arrayResult['FEE_AMT'] = 0;
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
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