<?php
require_once('autoloadExt.php');

if($lib->checkCompleteArgument(['member_no','item_amt','deptaccount_no','depttype_code','bank_cd','branch_cd','bank_accid','atm_no',
'atm_seqno','fee_inc_status','entry_id','stmt_type','slip_type','acc_id'],$_GET)){
	$arrayGroup = array();
	$arrayGroup["account_id"] = $_GET["acc_id"];
	$arrayGroup["action_status"] = "1";
	$arrayGroup["atm_no"] = $_GET["atm_no"];
	$arrayGroup["atm_seqno"] = $_GET["atm_seqno"];
	$arrayGroup["aviable_amt"] = null;
	$arrayGroup["bank_accid"] = $_GET["bank_accid"];
	$arrayGroup["bank_cd"] = $_GET["bank_cd"];
	$arrayGroup["branch_cd"] = $_GET["branch_cd"];
	$arrayGroup["coop_code"] = $config["COOP_CODE"];
	$arrayGroup["coop_id"] = $config["COOP_ID"];
	$arrayGroup["deptaccount_no"] = $_GET["deptaccount_no"];
	$arrayGroup["depttype_code"] = $_GET["depttype_code"];
	$arrayGroup["entry_id"] = $_GET["entry_id"];
	$arrayGroup["fee_amt"] = "0";
	$arrayGroup["feeinclude_status"] = $_GET["fee_inc_status"];
	$arrayGroup["item_amt"] = $_GET["item_amt"];
	$arrayGroup["member_no"] = $_GET["member_no"];
	$arrayGroup["moneytype_code"] = $config["MONEYTYPE_CODE"];
	$arrayGroup["msg_output"] = null;
	$arrayGroup["msg_status"] = null;
	$arrayGroup["operate_date"] = date('c');
	$arrayGroup["oprate_cd"] = "003";
	$arrayGroup["post_status"] = "1";
	$arrayGroup["principal_amt"] = null;
	$arrayGroup["ref_slipno"] = null;
	$arrayGroup["slipitemtype_code"] = $_GET["slip_type"];
	$arrayGroup["stmtitemtype_code"] = $_GET["stmt_type"];
	$arrayGroup["system_cd"] = $config["SYSTEM_CD"];
	$arrayGroup["withdrawable_amt"] = null;
	
	$clientWS = new SoapClient("http://web.siamcoop.com/CORE/GCOOP/WcfService125/n_deposit.svc?singleWsdl");
	try {
		$argumentWS = [
				"as_wspass" => "Data Source=web.siamcoop.com/gcoop;Persist Security Info=True;User ID=iscorfscmas;Password=iscorfscmas;Unicode=True;coop_id=050001;coop_control=050001;",
				"astr_dept_inf_serv" => $arrayGroup
		];
		$resultWS = $clientWS->__call("of_dept_inf_serv", array($argumentWS));
		$arrayResult['RETURN'] = $resultWS->of_dept_inf_servResult;
		$arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);
	}catch(SoapFault $e){
		$arrayResult['RESPONSE_CODE'] = "WS2001";
		$arrayResult['RESPONSE_MESSAGE'] = $e->getMessage();
		$arrayResult['RESULT'] = FALSE;
		http_response_code(400);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "ET0004";
	$arrayResult['RESPONSE_MESSAGE'] = "Payload not complete";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>