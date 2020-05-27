<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','deptaccount_no','id_accountconstant'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ManagementAccount')){
		if(is_string($dataComing["deptaccount_no"]) && is_string($dataComing["id_accountconstant"])){
			$insertDeptAllow = $conmysql->prepare("INSERT INTO gcuserallowacctransaction(deptaccount_no,member_no,limit_transaction_amt,id_accountconstant) 
													VALUES(:deptaccount_no,:member_no,:limit_transaction_amt,:id_accountconstant)");
			if($insertDeptAllow->execute([
				':deptaccount_no' => $dataComing["deptaccount_no"],
				':member_no' => $payload["member_no"],
				':limit_transaction_amt' => $func->getConstant("limit_withdraw"),
				':id_accountconstant' => $dataComing["id_accountconstant"]
			])){
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrExecute = [
					':deptaccount_no' => $dataComing["deptaccount_no"],
					':member_no' => $payload["member_no"],
					':limit_transaction_amt' => $func->getConstant("limit_withdraw"),
					':id_accountconstant' => $dataComing["id_accountconstant"]
				];
				$arrError = array();
				$arrError["EXECUTE"] = $arrExecute;
				$arrError["QUERY"] = $insertDeptAllow;
				$arrError["ERROR_CODE"] = 'WS1023';
				$lib->addLogtoTxt($arrError,'manageaccount_error');
				$arrayResult['RESPONSE_CODE'] = "WS1023";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$limit_trans = $func->getConstant("limit_withdraw");
			$bulkInsertData = array();
			foreach($dataComing["deptaccount_no"] as  $key => $deptaccount_no){
				$bulkInsertData[] = "('".$deptaccount_no."','".$payload["member_no"]."','".$limit_trans."',".$dataComing["id_accountconstant"][$key].")";
			}
			$insertDeptAllow = $conmysql->prepare("INSERT INTO gcuserallowacctransaction(deptaccount_no,member_no,limit_transaction_amt,id_accountconstant) 
													VALUES".implode(",",$bulkInsertData));
			if($insertDeptAllow->execute()){
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrError = array();
				$arrError["EXECUTE"] = $bulkInsertData;
				$arrError["QUERY"] = $insertDeptAllow;
				$arrError["ERROR_CODE"] = 'WS1023';
				$lib->addLogtoTxt($arrError,'manageaccount_error');
				$arrayResult['RESPONSE_CODE'] = "WS1023";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}
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