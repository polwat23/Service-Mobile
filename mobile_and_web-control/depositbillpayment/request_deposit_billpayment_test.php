<?php
ini_set('default_socket_timeout', 5000);
$can_pay = array();
$can_notpay = array();
$WS_STRC_DB = "Data Source=10.220.6.31/gcoop;Persist Security Info=True;User ID=iscotest;Password=iscotest;Unicode=True;coop_id=011001;coop_control=011001;";
try {
	$arrayGroup = array();
	$arrayGroup["account_id"] = '110000555';
	$arrayGroup["action_status"] = "1";
	$arrayGroup["atm_no"] = "MOBILE";
	$arrayGroup["atm_seqno"] = null;
	$arrayGroup["aviable_amt"] = null;
	$arrayGroup["bank_accid"] = null;
	$arrayGroup["bank_cd"] = null;
	$arrayGroup["branch_cd"] = null;
	$arrayGroup["coop_code"] = "ptt-test";
	$arrayGroup["coop_id"] = "011001";
	$arrayGroup["deptaccount_no"] = '2201000567';
	$arrayGroup["depttype_code"] = "DTE";
	$arrayGroup["entry_id"] = "MOBILE";
	$arrayGroup["fee_amt"] = 0;
	$arrayGroup["feeinclude_status"] = "1";
	$arrayGroup["item_amt"] = 5000;
	$arrayGroup["member_no"] = '10000';
	$arrayGroup["moneytype_code"] = "CBT";
	$arrayGroup["msg_output"] = null;
	$arrayGroup["msg_status"] = null;
	$arrayGroup["operate_date"] = date('c');
	$arrayGroup["oprate_cd"] = "003";
	$arrayGroup["post_status"] = "1";
	$arrayGroup["principal_amt"] = null;
	$arrayGroup["ref_app"] = null;
	$arrayGroup["ref_slipno"] = null;
	$arrayGroup["slipitemtype_code"] = "DTE";
	$arrayGroup["stmtitemtype_code"] = "DTE";
	$arrayGroup["system_cd"] = "02";
	$arrayGroup["withdrawable_amt"] = null;
	$ref_slipno = null;
	$clientWS = new SoapClient("http://10.220.6.31/CORE/GCOOP/wcfService125/n_deposit.svc?singleWsdl",array(
		'keep_alive' => false,
		'connection_timeout' => 5000
	));
	try {
		$argumentWS = [
			"as_wspass" => $WS_STRC_DB,
			"astr_dept_inf_serv" => $arrayGroup
		];
		$resultWS = $clientWS->__call("of_dept_inf_serv", array($argumentWS));
		$responseSoap = $resultWS->of_dept_inf_servResult;
		echo json_encode($responseSoap);
	}catch(Exception $e){
		var_dump($e);
	}
}catch(Exception $e){
	var_dump($e);
}
?>