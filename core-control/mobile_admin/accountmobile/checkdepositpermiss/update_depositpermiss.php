<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','deptdata'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','checkdepositpermiss')){
		$arrayGroup = array();
		$arrayAccountnoCheckGrp = array();
		
		$fetchAccountnoCheck = $conmysql->prepare("SELECT DEPTACCOUNT_NO , MEMBER_NO, IS_CLOSESTATUS FROM gcconstantdeposit WHERE member_no = :member_no");
		$fetchAccountnoCheck->execute([':member_no' => $dataComing["member_no"]]);
		while($rowAccountnoCheck = $fetchAccountnoCheck->fetch(PDO::FETCH_ASSOC)){
			$arrayAccountnoCheck = $rowAccountnoCheck;
			$arrayAccountnoCheckGrp[] = $arrayAccountnoCheck;
		}
		
		$getBalanceMaster = $conoracle->prepare("SELECT max(confirmbal_date) as BALANCE_DATE  FROM cmconfirmbalance WHERE member_no =  :member_no");
		$getBalanceMaster->execute([':member_no' => $dataComing["member_no"]]);
		$rowBalMaster = $getBalanceMaster->fetch(PDO::FETCH_ASSOC);
		$arrHeader = array();
		
		$fetcAccountno = $conoracle->prepare("SELECT  cfb.member_no,mp.prename_desc||''||mb.memb_name||' '|| mb.memb_ename as COOP_NAME,
											cfb.BIZZACCOUNT_NO , cfb.BALANCE_VALUE as BALANCE_AMT , dp.DEPTTYPE_DESC
											FROM cmconfirmbalance cfb LEFT JOIN dpdeptmaster dm ON cfb.BIZZACCOUNT_NO = dm.deptaccount_no AND cfb.member_no = dm.member_no and dm.deptclose_status = 0
											LEFT JOIN dpdepttype dp   ON dm.depttype_code = dp.depttype_code AND dm.deptgroup_code = dp.deptgroup_code
											LEFT JOIN mbmembmaster mb ON cfb.member_no = mb.member_no
											LEFT JOIN mbucfprename mp on  mb.prename_code = mp.prename_code
											WHERE bizz_system	= 'DEP'  
											and cfb.member_no = :member_no
											and cfb.confirmbal_date =  to_date(:confirm_date,'YYYY-MM-DD')                 
											ORDER BY cfb.BIZZACCOUNT_NO");
		$fetcAccountno->execute([':member_no' => $ref_memno , 
								 ':confirm_date' => date('Y-m-d',strtotime($rowBalMaster["BALANCE_DATE"]))
							]);
		while($rowDepositBalance = $fetcAccountno->fetch(PDO::FETCH_ASSOC)){
			$account_no = preg_replace('/\//','',$rowDepositBalance["BIZZACCOUNT_NO"]);
			if(array_search($account_no,array_column($arrayAccountnoCheckGrp,'DEPTACCOUNT_NO')) === False){
				$arrayAccountNo["IS_CLOSESTATUS"] = "0";		
			}else{
				$arrayAccountNo["IS_CLOSESTATUS"] = $arrayAccountnoCheckGrp[array_search($account_no,array_column($arrayAccountnoCheckGrp,'DEPTACCOUNT_NO'))]["IS_CLOSESTATUS"];
			}
			$arrayAccountNo["DEPTACCOUNT_NO"] = $account_no;
			$arrayAccountNo["BALANCE_AMT"] = number_format($rowDepositBalance["BALANCE_AMT"],2);
		}
		
		
		if($dataComing["deptdata"] !== $arrayGroup){
			$resultUDiff = array_udiff($dataComing["deptdata"],$arrayGroup,function ($Change,$deptOri){
				if ($Change === $deptOri){
					return 0;
				}else{
					return ($Change>$deptOri) ? 1 : -1;
				}
			});
			foreach($resultUDiff as $value_diff){
				if(array_search($value_diff["DEPTACCOUNT_NO"],array_column($arrayAccountnoCheckGrp,'DEPTACCOUNT_NO')) === False){
					$insertBulkDept[] = "('".$value_diff["DEPTACCOUNT_NO"]."','".$value_diff["IS_CLOSESTATUS"]."','".$dataComing["member_no"]."')";
					//$insertBulkContLog[]='DEPTACCOUNT_NO=> '.$value_diff["DEPTACCOUNT_NO"].' IS_CLOSESTATUS ='.$value_diff["IS_CLOSESTATUS"];
				}else{
					$updateDept = $conmysql->prepare("UPDATE gcconstantdeposit SET IS_CLOSESTATUS = :IS_CLOSESTATUS  WHERE DEPTACCOUNT_NO = :DEPTACCOUNT_NO");
					$updateDept->execute([
						':IS_CLOSESTATUS' => $value_diff["IS_CLOSESTATUS"],
						':DEPTACCOUNT_NO' => $value_diff["DEPTACCOUNT_NO"]
					]);
					//$updateConstLog = 'DEPTACCOUNT_NO=> '.$value_diff["DEPTACCOUNT_NO"].' IS_CLOSESTATUS ='.$value_diff["IS_CLOSESTATUS"];
				}
			}
			$insertConst = $conmysql->prepare("INSERT gcconstantdeposit(DEPTACCOUNT_NO,IS_CLOSESTATUS,MEMBER_NO)
															VALUES".implode(',',$insertBulkDept));
			$insertConst->execute();
			$arrayResult['dataOld'] = $arrayGroup;
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