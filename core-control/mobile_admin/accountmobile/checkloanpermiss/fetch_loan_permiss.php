<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','checkloanpermiss')){
		$arrayGroup = array();
		$arrGroupConfirm = array();
		$CoopName = null;
		$ref_memno = null;
		if($func->check_permission_core($payload,'mobileadmin','checkloanpermiss')){
		$arrAllLoan = array();
			if(empty($dataComing["member_no"])){
				$arrayResult['RESPONSE'] = "ไม่สามารถค้นหาได้เนื่องจากไม่ได้ระบุค่าที่ต้องการค้นหา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
			$ref_memno = $dataComing["member_no"];
			$checkMembno = $conmysql->prepare("SELECT member_no FROM gcmembonlineregis where  member_no = :member_no");
			$checkMembno->execute([':member_no' => $dataComing["member_no"]]);
			while($rowUser = $checkMembno->fetch(PDO::FETCH_ASSOC)){
				$member_no  = $rowUser["member_no"];
			}
			
			if(isset($member_no)){
				$arrayContractCheckGrp = array();
				$fetchContractTypeCheck = $conmysql->prepare("SELECT CONTRACT_NO, MEMBER_NO, IS_CLOSESTATUS ,IS_CONFIRMBALANCE FROM gcconstantcontractno WHERE member_no = :member_no");
				$fetchContractTypeCheck->execute([':member_no' => $ref_memno ]);
				while($rowContractnoCheck = $fetchContractTypeCheck->fetch(PDO::FETCH_ASSOC)){
					$arrayContractCheck = $rowContractnoCheck;
					$arrayContractCheckGrp[] = $arrayContractCheck;
				}
				
				
				$getBalanceMaster = $conoracle->prepare("SELECT max(confirmbal_date)as BALANCE_DATE  FROM cmconfirmbalance WHERE member_no =  :member_no");
				$getBalanceMaster->execute([':member_no' => $ref_memno]);
				$rowBalMaster = $getBalanceMaster->fetch(PDO::FETCH_ASSOC);
				$arrHeader = array();
				$arrHeader["date_confirm"] = $lib->convertdate(date('Y-m-d',strtotime($rowBalMaster["BALANCE_DATE"])),'d M Y');
				
				$fetchConfirm = $conoracle->prepare("SELECT cm.balance_value,cm.bizzaccount_no,lcy.loantype_desc,lc.loancontract_no
													FROM cmconfirmbalance cm LEFT JOIN lccontmaster lc on cm.bizzaccount_no = lc.loancontract_no
													LEFT JOIN lccfloantype lcy on  lc.loantype_code = lcy.loantype_code 
													WHERE cm.bizz_system ='LON' 
													AND cm.member_no = :member_no
													AND cm.confirmbal_date	= to_date(:balance_date,'YYYY-MM-DD')  ");
				$fetchConfirm->execute([':member_no' => $ref_memno,
										':balance_date' => date('Y-m-d',strtotime($rowBalMaster["BALANCE_DATE"]))
									]);
				while($rowContractBalance = $fetchConfirm->fetch(PDO::FETCH_ASSOC)){
					$contract_no = preg_replace('/\//','',$rowContractBalance["LOANCONTRACT_NO"]);
					if(array_search($contract_no,array_column($arrayContractCheckGrp,'CONTRACT_NO')) === False){
						$arrGroupConfirm[$contract_no]["IS_CONFIRMBALANCE"] = "0";
					}else{					
						$arrGroupConfirm[$contract_no]["IS_CONFIRMBALANCE"] = $arrayContractCheckGrp[array_search($contract_no,array_column($arrayContractCheckGrp,'CONTRACT_NO'))]["IS_CONFIRMBALANCE"];				
					}
					$arrGroupConfirm[$contract_no]["CONTRACT_NO"] = $contract_no;
					$arrGroupConfirm[$contract_no]["LOAN_TYPE"] = $rowContractBalance["LOANTYPE_DESC"];
					$arrGroupConfirm[$contract_no]["BALANCE_STATUS"] = "1";
					$arrGroupConfirm[$contract_no]["CONFIRM_DATE"] = date('Y-m-d',strtotime($rowBalMaster["BALANCE_DATE"]));
					$arrGroupConfirm[$contract_no]["BALANCE_VALUE"] = number_format($rowContractBalance["BALANCE_VALUE"],2);
				}
				
				$fetchLoantype = $conoracle->prepare("SELECT mp.prename_desc||''||mb.memb_name as COOP_NAME , lt.LOANTYPE_DESC AS LOAN_TYPE,ln.loancontract_no,ln.principal_balance as LOAN_BALANCE,
													ln.loanapprove_amt as APPROVE_AMT,
													(SELECT max(operate_date) FROM lccontstatement WHERE loancontract_no = ln.loancontract_no) as LAST_OPERATE_DATE
													FROM lccontmaster ln LEFT JOIN LCCFLOANTYPE lt ON ln.LOANTYPE_CODE = lt.LOANTYPE_CODE 
																		 LEFT JOIN mbmembmaster mb on ln.member_no = mb.member_no 
																		 LEFT JOIN mbucfprename mp on  mb.prename_code = mp.prename_code
													WHERE ln.member_no = :member_no and ln.contract_status = 1");
				$fetchLoantype->execute([':member_no' => $ref_memno]);
				while($rowContract = $fetchLoantype->fetch(PDO::FETCH_ASSOC)){
					$contract_no = preg_replace('/\//','',$rowContract["LOANCONTRACT_NO"]);
					if(array_search($contract_no,array_column($arrayContractCheckGrp,'CONTRACT_NO')) === False){
						$arrGroupConfirm[$contract_no]["IS_CLOSESTATUS"] = "0";
					}else{					
						$arrGroupConfirm[$contract_no]["IS_CLOSESTATUS"] = $arrayContractCheckGrp[array_search($contract_no,array_column($arrayContractCheckGrp,'CONTRACT_NO'))]["IS_CLOSESTATUS"];				
					}
					$CoopName = $rowContract["COOP_NAME"];
					$arrGroupConfirm[$contract_no]["CONTRACT_NO"] = $contract_no;
					$arrGroupConfirm[$contract_no]["LOAN_TYPE"] = $rowContract["LOAN_TYPE"];
					$arrGroupConfirm[$contract_no]["LOAN_BALANCE"] = number_format($rowContract["LOAN_BALANCE"],2);
				}
			}else{
				$arrayResult['RESPONSE'] = "ไม่พบสหกรณ์ กรุณาลงทะเบียน";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}		
		}
		$arrayResult["COOP_NAME"] = $CoopName;
		$arrayResult["MEMBER"] = $dataComing["member_no"];
		$arrayResult["CONFIRM_BALANCE"] = $arrGroupConfirm;
		$arrayResult["IS_ESTIMATE"] = TRUE; //เปิด checkbox ประมาณการสิทธิ์กู้
		$arrayResult["RESULT"] = TRUE;
		require_once('../../../../include/exit_footer.php');
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