<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_depttran'],$dataComing)){
	if($func->check_permission_core($payload,'log','logdepositerror')){
		$fetchLogDepositError = $conmysql->prepare("SELECT  
															tb.member_no,
															tb.transaction_date,
															tb.sigma_key,
															tb.amt_transfer,
															tb.response_code,
															tb.response_message,
															login.channel,
															tb.is_adj,
															gba.deptaccount_no_coop,
															gba.deptaccount_no_bank,
															gba.bank_code,
															gt.coop_slip_no
													FROM logdepttransbankerror tb
													LEFT JOIN gcuserlogin login
													ON login.id_userlogin = tb.id_userlogin 
													LEFT JOIN gcbindaccount gba ON tb.sigma_key = gba.sigma_key
													LEFT JOIN gctransaction gt ON tb.ref_no = gt.ref_no
													WHERE tb.id_deptransbankerr = :id_depttran
													and tb.is_adj = '1'
													ORDER BY tb.transaction_date DESC");
		$fetchLogDepositError->execute([':id_depttran' => $dataComing["id_depttran"]]);
		$rowLogDepositError = $fetchLogDepositError->fetch(PDO::FETCH_ASSOC);
		if(isset($rowLogDepositError["sigma_key"]) && $rowLogDepositError["sigma_key"] != "" && isset($rowLogDepositError["coop_slip_no"]) && $rowLogDepositError["coop_slip_no"] != ""){
			$dateOperC = date('c',strtotime(date('Y-m-d',strtotime($rowLogDepositError["transaction_date"]))));
			$fetchDepttype = $conoracle->prepare("SELECT depttype_code FROM dpdeptmaster WHERE deptaccount_no = :deptaccount_no");
			$fetchDepttype->execute([':deptaccount_no' => $rowLogDepositError["deptaccount_no_coop"]]);
			$rowDataDepttype = $fetchDepttype->fetch(PDO::FETCH_ASSOC);
			$arrayGroup = array();
			$arrayGroup["account_id"] = null;
			$arrayGroup["action_status"] = "1";
			$arrayGroup["atm_no"] = $rowLogDepositError["coop_slip_no"];
			$arrayGroup["atm_seqno"] = null;
			$arrayGroup["aviable_amt"] = null;
			$arrayGroup["bank_accid"] = $rowLogDepositError["deptaccount_no_bank"];
			$arrayGroup["bank_cd"] = $rowLogDepositError["bank_code"];
			$arrayGroup["branch_cd"] = null;
			$arrayGroup["coop_code"] = $config["COOP_KEY"];
			$arrayGroup["coop_id"] = $config["COOP_ID"];
			$arrayGroup["deptaccount_no"] =  $rowLogDepositError["deptaccount_no_coop"];
			$arrayGroup["depttype_code"] = $rowDataDepttype["DEPTTYPE_CODE"];
			$arrayGroup["entry_id"] = "KBANK";
			$arrayGroup["fee_amt"] = "0";
			$arrayGroup["feeinclude_status"] = "1";
			$arrayGroup["item_amt"] = $rowLogDepositError["amt_transfer"];
			$arrayGroup["member_no"] = $rowLogDepositError["member_no"];
			$arrayGroup["moneytype_code"] = "CBT";
			$arrayGroup["msg_output"] = null;
			$arrayGroup["msg_status"] = null;
			$arrayGroup["operate_date"] = $dateOperC;
			$arrayGroup["oprate_cd"] = "002";
			$arrayGroup["post_status"] = "-1";
			$arrayGroup["principal_amt"] = null;
			$arrayGroup["ref_slipno"] = null;
			$arrayGroup["slipitemtype_code"] = "DTB";
			$arrayGroup["stmtitemtype_code"] = "WTB";
			$arrayGroup["system_cd"] = "02";
			$arrayGroup["withdrawable_amt"] = null;
			$ref_slipno = null;
			try {
				$clientWS = new SoapClient($config["URL_CORE_COOP"]."n_deposit.svc?singleWsdl",array(
					'keep_alive' => false,
					'connection_timeout' => 900
				));
				try {
					$argumentWS = [
						"as_wspass" => $config["WS_STRC_DB"],
						"astr_dept_inf_serv" => $arrayGroup
					];
					$resultWS = $clientWS->__call("of_dept_inf_serv_cen", array($argumentWS));
					$responseSoap = $resultWS->of_dept_inf_serv_cenResult;
					if($responseSoap->msg_status == '0000'){
						$updateStatusADJ = $conmysql->prepare("UPDATE logdepttransbankerror SET is_adj = '8' WHERE id_deptransbankerr = :id_depttran");
						$updateStatusADJ->execute([':id_depttran' => $dataComing["id_depttran"]]);
						$arrayStruc = [
							':menu_name' => "logdepositerror",
							':username' => $payload["username"],
							':use_list' => "ย้อนรายการ adj รายการเงินฝากเลขสลิปเงินฝาก ".$rowLogDepositError["coop_slip_no"],
							':details' => "ย้อนรายการ ADJ สำเร็จ"
						];
						$log->writeLog('manageuser',$arrayStruc);
						$arrayResult['RESULT'] = TRUE;
						require_once('../../../include/exit_footer.php');
					}else{
						$arrayStruc = [
							':menu_name' => "logdepositerror",
							':username' => $payload["username"],
							':use_list' => "ย้อนรายการ adj รายการเงินฝากเลขสลิปเงินฝาก ".$rowLogDepositError["coop_slip_no"],
							':details' => "ย้อนรายการ ADJ ไม่สำเร็จ เพราะ ".$responseSoap->msg_output
						];
						$log->writeLog('manageuser',$arrayStruc);
						$arrayResult['RESPONSE'] = "ทำรายการไม่สำเร็จ ย้อนรายการ ADJ ยอดไม่ได้ ให้ดู log ทำรายการของเจ้าหน้าที่";
						$arrayResult['RESULT'] = FALSE;
						require_once('../../../include/exit_footer.php');
					}
				}catch(Throwable $e) {
					$arrayStruc = [
						':menu_name' => "logdepositerror",
						':username' => $payload["username"],
						':use_list' => "ย้อนรายการ adj รายการเงินฝากเลขสลิปเงินฝาก ".$rowLogDepositError["coop_slip_no"],
						':details' => "ย้อนรายการ ADJ ไม่สำเร็จ เพราะ ".$e->getMessage()
					];
					$log->writeLog('manageuser',$arrayStruc);
					$arrayResult['RESPONSE'] = "ทำรายการไม่สำเร็จ ย้อนรายการ ADJ ยอดไม่ได้ ให้ดู log ทำรายการของเจ้าหน้าที่";
					$arrayResult['RESULT'] = FALSE;
					require_once('../../../include/exit_footer.php');
				}
			}catch(SoapFault $e){
				$arrayStruc = [
					':menu_name' => "logdepositerror",
					':username' => $payload["username"],
					':use_list' => "ย้อนรายการ adj รายการเงินฝากเลขสลิปเงินฝาก ".$rowLogDepositError["ref_no"],
					':details' => "ย้อนรายการ ADJ ไม่สำเร็จ เพราะ ".$e->getMessage()
				];
				$log->writeLog('manageuser',$arrayStruc);
				$arrayResult['RESPONSE'] = "ทำรายการไม่สำเร็จ ย้อนรายการ ADJ ยอดไม่ได้ ให้ดู log ทำรายการของเจ้าหน้าที่";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../include/exit_footer.php');
			}
		}else{
			$arrayResult['RESPONSE'] = "ไม่พบข้อมูลการทำรายการที่ ADJ แล้ว";
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