<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','deptaccount_no','source_type'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ManagementAccount')){
		$arrayTransaction = array();
		$fetchTransList = $conmysql->prepare("SELECT REF_NO,TRANSFER_MODE,DESTINATION,DESTINATION_TYPE,AMOUNT_RECEIVE,OPERATE_DATE
															FROM gctransaction WHERE member_no = :member_no and from_account = :deptaccount_no and result_transaction <> '-9'");
		$fetchTransList->execute([
			':member_no' => $payload["member_no"],
			':deptaccount_no' => $dataComing["deptaccount_no"]
		]);
		if($dataComing["source_type"] == "coop"){
			$fetchFormatAccBank = $conmysql->prepare("SELECT bank_format_account,bank_format_account_hide FROM csbankdisplay WHERE bank_code = '004' ");
			$fetchFormatAccBank->execute();
			$rowBankDS = $fetchFormatAccBank->fetch(PDO::FETCH_ASSOC);
		}
		while($rowTrans = $fetchTransList->fetch(PDO::FETCH_ASSOC)){
			$arrayTrans = array();
			$arrayTrans["REF_NO"] = $rowTrans["REF_NO"];
			$arrayTrans["AMOUNT"] = number_format($rowTrans["AMOUNT_RECEIVE"],2);
			$arrayTrans["OPERATE_DATE"] = $lib->convertdate($rowTrans["OPERATE_DATE"],'d m Y',true);
			if($rowTrans["TRANSFER_MODE"] == '1'){
				$arrayTrans["TRANSFER_MODE"] = "โอนเงินฝากไปบัญชีภายในสหกรณ์";
			}else if($rowTrans["TRANSFER_MODE"] == '2'){
				$arrayTrans["TRANSFER_MODE"] = "โอนเงินฝากไปชำระหนี้";
			}else if($rowTrans["TRANSFER_MODE"] == '3'){
				$arrayTrans["TRANSFER_MODE"] = "โอนเงินฝากไปซื้อหุ้น";
			}else{
				$arrayTrans["TRANSFER_MODE"] = "โอนเงินฝากไปยังบัญชีธนาคาร";
			}
			if($dataComing["source_type"] == "coop"){
				if($rowTrans["DESTINATION_TYPE"] == "1"){
					$arrayTrans["DESTINATION"] = $lib->formataccount($rowTrans["DESTINATION"],$rowBankDS["bank_format_account"]);
					$arrayTrans["DESTINATION_HIDDEN"] = $lib->formataccount_hidden($rowTrans["DESTINATION"],$rowBankDS["bank_format_account_hide"]);
				}else if($rowTrans["DESTINATION_TYPE"] == "3"){
					$contract_no = preg_replace('/\//','',$rowTrans["DESTINATION"]);
					$arrContract = array();
					$arrContract["CONTRACT_NO"] = $contract_no;
					if(mb_stripos($contract_no,'.') === FALSE){
						$loan_format = mb_substr($contract_no,0,2).'.'.mb_substr($contract_no,2,6).'/'.mb_substr($contract_no,8,2);
						if(mb_strlen($contract_no) == 10){
							$arrContract["CONTRACT_NO_FORMAT"] = $loan_format;
						}else if(mb_strlen($contract_no) == 11){
							$arrContract["CONTRACT_NO_FORMAT"] = $loan_format.'-'.mb_substr($contract_no,10);
						}
					}else{
						$arrContract["CONTRACT_NO_FORMAT"] = $contract_no;
					}
					$arrayTrans["DESTINATION"] = $lib->formataccount($rowTrans["DESTINATION"],$rowBankDS["bank_format_account"]);
					$arrayTrans["DESTINATION_HIDDEN"] = $lib->formataccount_hidden($rowTrans["DESTINATION"],$rowBankDS["bank_format_account_hide"]);
				}else{
					$arrayTrans["DESTINATION"] = $rowTrans["DESTINATION"];
				}
			}else{
				$arrayTrans["DESTINATION"] = $lib->formataccount($rowTrans["DESTINATION"],$func->getConstant('dep_format'));
				$arrayTrans["DESTINATION_HIDDEN"] = $lib->formataccount_hidden($rowTrans["DESTINATION"],$func->getConstant('hidden_dep'));
			}
			$arrayTransaction[] = $arrayTrans;
		}
		$arrayResult['TRANSACTION_LIST'] = $arrayTransaction;
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>