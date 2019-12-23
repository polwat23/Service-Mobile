<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','from_deptaccount_no','to_deptaccount_no','amt_transfer','fee_transfer'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepInsideCoop')){
		$clientWS = new SoapClient("http://web.siamcoop.com/CORE/GCOOP/WcfService125/n_deposit.svc?singleWsdl");
		$from_account_no = preg_replace('/-/','',$dataComing["from_deptaccount_no"]);
		$to_account_no = preg_replace('/-/','',$dataComing["to_deptaccount_no"]);
		try {
			$argumentWS = [
							"as_wspass" => "Data Source=web.siamcoop.com/gcoop;Persist Security Info=True;User ID=iscorfscmas;Password=iscorfscmas;Unicode=True;coop_id=050001;coop_control=050001;",
							"as_src_deptaccount_no" => $from_account_no,
							"as_dest_deptaccount_no" => $to_account_no,
							"adtm_operate" => date('c'),
							"as_wslipitem_code" => "WTX",
							"as_dslipitem_code" => "DTX",
							"adc_amt" => $dataComing["amt_transfer"],
							"adc_fee" => $dataComing["fee_transfer"],
			];
			$resultWS = $clientWS->__call("of_withdraw_deposit_trans", array($argumentWS));
			$arrayResult['SLIP_NO'] = $resultWS->of_withdraw_deposit_transResult;
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}catch(SoapFault $e){
			$arrError = array();
			$arrError["MESSAGE"] = $e->getMessage();
			$arrError["ERROR_CODE"] = 'WS8001';
			$lib->addLogtoTxt($arrError,'soap_error');
			if($lang_locale == 'th'){
				$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถโอนภายในได้ กรุณาติดต่อสหกรณ์ #WS8001";
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = "Cannot fund transfer in cooperative please contact cooperative #WS8001";
			}
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
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