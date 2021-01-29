<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_depttran'],$dataComing)){
	if($func->check_permission_core($payload,'log','logdepositerror')){
		$fetchLogDepositError = $conmysql->prepare("SELECT  
															tb.member_no,
															tb.transaction_date,
															tb.SIGMA_KEY,
															tb.amt_transfer,
															tb.response_code,
															tb.response_message,
															login.channel,
															tb.is_adj,
															gba.deptaccount_no_coop
													FROM logdepttransbankerror tb
													LEFT JOIN gcuserlogin login
													ON login.id_userlogin = tb.id_userlogin 
													LEFT JOIN gcbindaccount gba ON tb.sigma_key = gba.sigma_key
													WHERE tb.id_deptransbankerr = :id_deptransbankerr
													and tb.is_adj = '8'
													ORDER BY tb.transaction_date DESC");
		$fetchLogDepositError->execute([':id_depttran' => $dataComing["id_depttran"]]);
		$rowLogDepositError = $fetchLogDepositError->fetch(PDO::FETCH_ASSOC));
		if(isset($rowLogDepositError["SIGMA_KEY"]) && $rowLogDepositError["SIGMA_KEY"] != ""){
			$checkAdj = $conoracle->prepare("SELECT FROM dpdeptslip WHERE deptaccount_no = :deptaccount_no and amt_transfer = :amt_transfer
											and deptitemtype_code = 'DTX' and to_char(deptslip_date,'YYYY-MM-DD') = :date_oper");
			$checkAdj->execute([
				':deptaccount_no' => $rowLogDepositError["deptaccount_no_coop"],
				':amt_transfer' => $rowLogDepositError["amt_transfer"],
				':date_oper' => date('Y-m-d',strtotime($rowLogDepositError["transaction_date"]))
			]);
			$rowAdj = $checkAdj->fetch(PDO::FETCH_ASSOC);
			$arrayGroup = array();
			$arrayGroup["account_id"] = $rowAccid["DEFAULT_ACCID"];
			$arrayGroup["action_status"] = "1";
			$arrayGroup["atm_no"] = "mobile";
			$arrayGroup["atm_seqno"] = null;
			$arrayGroup["aviable_amt"] = null;
			$arrayGroup["bank_accid"] = $rowDataDeposit["deptaccount_no_bank"];
			$arrayGroup["bank_cd"] = $rowDataDeposit["bank_code"];
			$arrayGroup["branch_cd"] = null;
			$arrayGroup["coop_code"] = $config["COOP_KEY"];
			$arrayGroup["coop_id"] = $config["COOP_ID"];
			$arrayGroup["deptaccount_no"] = $coop_account_no;
			$arrayGroup["depttype_code"] = $rowDataDepttype["DEPTTYPE_CODE"];
			$arrayGroup["entry_id"] = $dataComing["channel"] == 'mobile_app' ? "MCOOP" : "ICOOP";
			if($rowDataDeposit["bank_code"] != '006'){
				$arrayGroup["fee_amt"] = $dataComing["fee_amt"];
			}else{
				$arrayGroup["fee_amt"] = 0;
			}
			$arrayGroup["feeinclude_status"] = "1";
			$arrayGroup["item_amt"] = $amt_transfer;
			$arrayGroup["member_no"] = $member_no;
			$arrayGroup["moneytype_code"] = "CBT";
			$arrayGroup["msg_output"] = null;
			$arrayGroup["msg_status"] = null;
			$arrayGroup["operate_date"] = $dateOperC;
			$arrayGroup["oprate_cd"] = "003";
			$arrayGroup["post_status"] = "1";
			$arrayGroup["principal_amt"] = null;
			$arrayGroup["ref_slipno"] = null;
			$arrayGroup["slipitemtype_code"] = $rowDataDeposit["itemtype_dep"];
			$arrayGroup["stmtitemtype_code"] = $rowDataDeposit["itemtype_dep"];
			$arrayGroup["system_cd"] = "02";
			$arrayGroup["withdrawable_amt"] = null;
			$ref_slipno = null;
			try {
				$clientWS = new SoapClient($config["URL_CORE_COOP"]."n_deposit.svc?singleWsdl",array(
					'keep_alive' => false,
					'connection_timeout' => 900
				));
				try{
					$argumentWS = [
						"as_wspass" => $config["WS_STRC_DB"],
						"astr_dept_inf_serv" => $arrayGroup
					];
					$resultWS = $clientWS->__call("of_dept_inf_serv", array($argumentWS));
					$responseSoap = $resultWS->of_dept_inf_servResult;
					if($responseSoap->msg_status == '0000'){
					}
				}catch(Throwable $e) {
				}
			}catch(SoapFault $e){
			}
		}else{
			$arrayResult['RESPONSE'] = "ไม่พบข้อมูลการทำรายการที่รอ ADJ";
			$arrayResult['RESULT'] = FALSE;
			require_once('../../../include/exit_footer.php');
		}
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../include/exit_footer.php');
		
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../include/exit_footer.php');
	
}
?>