<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','checkloanpermiss')){
		$arrayGroup = array();
		$CoopName = null;
		$ref_memno = null;
		if($func->check_permission_core($payload,'mobileadmin','checkloanpermiss')){
		$arrAllLoan = array();
			if(empty($dataComing["member_no"])){
				$arrayResult['RESPONSE'] = "ไม่สามารถค้นหาได้เนื่องจากไม่ได้ระบุค่าที่ต้องการค้นหา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
			
			$checkMembno = $conmysql->prepare("SELECT ref_memno FROM gcmemberaccount where  member_no = :member_no");
			$checkMembno->execute([':member_no' => $dataComing["member_no"]]);
			while($rowUser = $checkMembno->fetch(PDO::FETCH_ASSOC)){
				$ref_memno  = $rowUser["ref_memno"];
			}

			$arrayContractCheckGrp = array();
			$fetchContractTypeCheck = $conmysql->prepare("SELECT CONTRACT_NO, MEMBER_NO, IS_CLOSESTATUS FROM gcconstantcontractno WHERE member_no = :member_no");
			$fetchContractTypeCheck->execute([':member_no' => $ref_memno]);
			while($rowContractnoCheck = $fetchContractTypeCheck->fetch(PDO::FETCH_ASSOC)){
				$arrayContractCheck = $rowContractnoCheck;
				$arrayContractCheckGrp[] = $arrayContractCheck;
			}
			$fetchLoantype = $conoracle->prepare("SELECT mp.prename_desc||''||mb.memb_name||' '|| mb.memb_ename as COOP_NAME , lt.LOANTYPE_DESC AS LOAN_TYPE,ln.loancontract_no,ln.principal_balance as LOAN_BALANCE,
												ln.loanapprove_amt as APPROVE_AMT,
												(SELECT max(operate_date) FROM lccontstatement WHERE loancontract_no = ln.loancontract_no) as LAST_OPERATE_DATE
												FROM lccontmaster ln LEFT JOIN LCCFLOANTYPE lt ON ln.LOANTYPE_CODE = lt.LOANTYPE_CODE 
																	 LEFT JOIN mbmembmaster mb on ln.member_no = mb.member_no 
																	 LEFT JOIN mbucfprename mp on  mb.prename_code = mp.prename_code
												WHERE ln.member_no = :member_no and ln.contract_status > 0 and ln.contract_status <> 8");
			$fetchLoantype->execute([':member_no' => $ref_memno]);
			while($rowContract = $fetchLoantype->fetch(PDO::FETCH_ASSOC)){
				$arrayContractno = array();
				$contract_no = preg_replace('/\//','',$rowContract["LOANCONTRACT_NO"]);
				if(array_search($contract_no,array_column($arrayContractCheckGrp,'CONTRACT_NO')) === False){
					$arrayContractno["IS_CLOSESTATUS"] = "0";
				}else{					
					$arrayContractno["IS_CLOSESTATUS"] = $arrayContractCheckGrp[array_search($contract_no,array_column($arrayContractCheckGrp,'CONTRACT_NO'))]["IS_CLOSESTATUS"];				
				}
				$arrGroupContract = array();
				$CoopName = $rowContract["COOP_NAME"];
				$arrayContractno["CONTRACT_NO"] = $contract_no;
				$arrayContractno["LOAN_TYPE"] = $rowContract["LOAN_TYPE"];
				$arrayContractno["LOAN_BALANCE"] = number_format($rowContract["LOAN_BALANCE"],2);
				$arrayContractno["APPROVE_AMT"] = number_format($rowContract["APPROVE_AMT"],2);
				$arrayContractno['TYPE_LOAN'] = $rowContract["LOAN_TYPE"];
				$arrayGroup[] = $arrayContractno;
			}
		}
		$arrayResult["COOP_NAME"] = $CoopName;
		$arrayResult["MEMBER"] = $ref_memno;
		$arrayResult["LOAN_CREDIT"] = $arrayGroup;
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