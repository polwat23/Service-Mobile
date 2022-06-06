<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','loancontract','member_no'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','checkloanpermiss')){
		$arrayGroup = array();
		$arrayContract = array();
		$arrayContractCheckGrp = array();
		$fetchLoanTypeCheck = $conmysql->prepare("SELECT CONTRACT_NO, MEMBER_NO, IS_CLOSESTATUS ,IS_CONFIRMBALANCE FROM gcconstantcontractno WHERE member_no = :member_no ");
		$fetchLoanTypeCheck->execute([':member_no' => $dataComing["member_no"]]);
		while($rowContractCheck = $fetchLoanTypeCheck->fetch(PDO::FETCH_ASSOC)){
			$arrayLoanCheck = $rowContractCheck;
			$arrayContractCheckGrp[] = $arrayLoanCheck;
		}
		
		$getBalanceMaster = $conoracle->prepare("SELECT max(confirmbal_date) as BALANCE_DATE  FROM cmconfirmbalance WHERE member_no =  :member_no");
		$getBalanceMaster->execute([':member_no' => $dataComing["member_no"]]);
		$rowBalMaster = $getBalanceMaster->fetch(PDO::FETCH_ASSOC);
		$arrHeader = array();
		$arrHeader["date_confirm"] = $lib->convertdate(date('Y-m-d',strtotime($rowBalMaster["BALANCE_DATE"])),'d M Y');
		
		$fetchConfirm = $conoracle->prepare("SELECT cm.balance_value,cm.bizzaccount_no,lcy.loantype_desc,lc.loancontract_no
												FROM cmconfirmbalance cm LEFT JOIN lccontmaster lc on cm.bizzaccount_no = lc.loancontract_no
												LEFT JOIN lccfloantype lcy on  lc.loantype_code = lcy.loantype_code 
												WHERE cm.bizz_system ='LON' 
												AND cm.member_no = :member_no
												AND cm.confirmbal_date	= to_date(:balance_date,'YYYY-MM-DD')");
		$fetchConfirm->execute([':member_no' => $dataComing["member_no"],
								 ':balance_date' => date('Y-m-d',strtotime($rowBalMaster["BALANCE_DATE"]))
								]);
		while($rowContractBalance = $fetchConfirm->fetch(PDO::FETCH_ASSOC)){
			$contract_no = preg_replace('/\//','',$rowContractBalance["LOANCONTRACT_NO"]);
			if(array_search($contract_no,array_column($arrayContractCheckGrp,'CONTRACT_NO')) === False){
				$arrayContract["IS_CONFIRMBALANCE"] = "0";		
			}else{
				$arrayContract["IS_CONFIRMBALANCE"] = $arrayContractCheckGrp[array_search($contract_no,array_column($arrayContractCheckGrp,'CONTRACT_NO'))]["IS_CONFIRMBALANCE"];
			}
			$arrayContract["CONTRACT_NO"] = $contract_no;
			$arrayContract["BALANCE_VALUE"] = number_format($rowContractBalance["BALANCE_VALUE"],2);
		}
		

		
		$fetchLoantype = $conoracle->prepare("SELECT mp.prename_desc||''||mb.memb_name||''|| mb.memb_ename as COOP_NAME , lt.LOANTYPE_DESC AS LOAN_TYPE,ln.loancontract_no,ln.principal_balance as LOAN_BALANCE,
												ln.loanapprove_amt as APPROVE_AMT,
												(SELECT max(operate_date) FROM lccontstatement WHERE loancontract_no = ln.loancontract_no) as LAST_OPERATE_DATE
												FROM lccontmaster ln LEFT JOIN LCCFLOANTYPE lt ON ln.LOANTYPE_CODE = lt.LOANTYPE_CODE 
																	 LEFT JOIN mbmembmaster mb on ln.member_no = mb.member_no 
																	 LEFT JOIN mbucfprename mp on  mb.prename_code = mp.prename_code
												WHERE ln.member_no = :member_no and ln.contract_status > 0 and ln.contract_status <> 8");
		$fetchLoantype->execute([':member_no' => $dataComing["member_no"]]);
		while($rowContract = $fetchLoantype->fetch(PDO::FETCH_ASSOC)){
			$contract_no = preg_replace('/\//','',$rowContract["LOANCONTRACT_NO"]);
			if(array_search($contract_no,array_column($arrayContractCheckGrp,'CONTRACT_NO')) === False){
				$arrayContract["IS_CLOSESTATUS"] = "0";		
			}else{
				$arrayContract["IS_CLOSESTATUS"] = $arrayContractCheckGrp[array_search($contract_no,array_column($arrayContractCheckGrp,'CONTRACT_NO'))]["IS_CLOSESTATUS"];
			}
			$arrayContract["CONTRACT_NO"] = $contract_no;
			$arrayContract["LOAN_TYPE"] = $rowContract["LOAN_TYPE"];
			$arrayContract["LOAN_BALANCE"] = number_format($rowContract["LOAN_BALANCE"],2);
			$arrayContract["APPROVE_AMT"] = number_format($rowContract["APPROVE_AMT"],2);
			$arrayGroup[] = $arrayContract;
		}

		if($dataComing["loancontract"] !== $arrayGroup){
			$resultUDiff = array_udiff($dataComing["loancontract"],$arrayGroup,function ($loanChange,$loanOri){
				if ($loanChange === $loanOri){
					return 0;
				}else{
					return ($loanChange>$loanOri) ? 1 : -1;
				}
			});
			foreach($resultUDiff as $value_diff){
				if(array_search($value_diff["CONTRACT_NO"],array_column($arrayContractCheckGrp,'CONTRACT_NO')) === False){
					$insertBulkCont[] = "('".$value_diff["CONTRACT_NO"]."','".$value_diff["IS_CLOSESTATUS"]."','".$dataComing["member_no"]."' ,'".$value_diff["IS_CONFIRMBALANCE"]."')";
					//$insertBulkContLog[]='CONTRACT_NO=> '.$value_diff["CONTRACT_NO"].' IS_CLOSESTATUS ='.$value_diff["IS_CLOSESTATUS"];
				}else{
					$updateConst = $conmysql->prepare("UPDATE gcconstantcontractno SET IS_CLOSESTATUS = :IS_CLOSESTATUS , IS_CONFIRMBALANCE = :IS_CONFIRMBALANCE WHERE CONTRACT_NO = :CONTRACT_NO");
					$updateConst->execute([
						':IS_CLOSESTATUS' => $value_diff["IS_CLOSESTATUS"],
						':IS_CONFIRMBALANCE' => $value_diff["IS_CONFIRMBALANCE"],
						':CONTRACT_NO' => $value_diff["CONTRACT_NO"]
					]);
					$updateConstLog = 'CONTRACT_NO => '.$value_diff["CONTRACT_NO"].' IS_CLOSESTATUS ='.$value_diff["IS_CLOSESTATUS"].' IS_CONFIRMBALANCE ='.$value_diff["IS_CONFIRMBALANCE"];
				}
			}
			$insertConst = $conmysql->prepare("INSERT gcconstantcontractno(CONTRACT_NO,IS_CLOSESTATUS,MEMBER_NO,IS_CONFIRMBALANCE)
															VALUES".implode(',',$insertBulkCont));
			$insertConst->execute();
			/*$arrayStruc = [
				':menu_name' => "checkloanpermiss",
				':username' => $payload["username"],
				':use_list' =>"edit constant contractno",
				':details' => implode(',',$insertBulkContLog).' '.$updateConstLog
			];*/
			$arrayResult['dataOld'] = $arrayGroup;
			//$log->writeLog('manageuser',$arrayStruc);	
			$arrayResult['RESULT'] = TRUE;
			require_once('../../../../include/exit_footer.php');
		}else{
			$arrayResult['RESULT'] = FALSE;
			$arrayResult['RESPONSE'] = "ข้อมูลไม่มีการเปลี่ยนแปลง กรุณาเลือกทำรายการ";
			require_once('../../../../include/exit_footer.php');
			
		}
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../../include/exit_footer.php');
		
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../../include/exit_footer.php');
	
}
?>