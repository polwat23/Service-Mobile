<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','checkdepositpermiss')){
		$arrayGroup = array();
		$CoopName = null;
		$ref_memno = null;
		if($func->check_permission_core($payload,'mobileadmin','checkdepositpermiss')){
		$arrAllLoan = array();
			if(empty($dataComing["member_no"])){
				$arrayResult['RESPONSE'] = "ไม่สามารถค้นหาได้เนื่องจากไม่ได้ระบุค่าที่ต้องการค้นหา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
			
			$checkMembno = $conmysql->prepare("SELECT ref_memno FROM gcmemberaccount WHERE  member_no = :member_no");
			$checkMembno->execute([':member_no' => $dataComing["member_no"]]);
			while($rowUser = $checkMembno->fetch(PDO::FETCH_ASSOC)){
				$ref_memno  = $rowUser["ref_memno"];
			}
			$getBalanceMaster = $conoracle->prepare("SELECT max(confirmbal_date) as BALANCE_DATE  FROM cmconfirmbalance WHERE member_no =  :member_no");
			$getBalanceMaster->execute([':member_no' => $ref_memno]);
			$rowBalMaster = $getBalanceMaster->fetch(PDO::FETCH_ASSOC);
			$arrHeader = array();

			$arrayAccountnoCheckGrp = array();
			$fetchAccountnoCheck = $conmysql->prepare("SELECT DEPTACCOUNT_NO , MEMBER_NO, IS_CLOSESTATUS FROM gcconstantdeposit WHERE member_no = :member_no");
			$fetchAccountnoCheck->execute([':member_no' => $ref_memno]);
			while($rowAccountnoCheck = $fetchAccountnoCheck->fetch(PDO::FETCH_ASSOC)){
				$arrayAccountnoCheck = $rowAccountnoCheck;
				$arrayAccountnoCheckGrp[] = $arrayAccountnoCheck;
			}
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
			while($rowAccountNo = $fetcAccountno->fetch(PDO::FETCH_ASSOC)){
				$arrayAccountno = array();
				$account_no = preg_replace('/\//','',$rowAccountNo["BIZZACCOUNT_NO"]);
				if(array_search($account_no,array_column($arrayAccountnoCheckGrp,'DEPTACCOUNT_NO')) === False){
					$arrayAccountno["IS_CLOSESTATUS"] = "0";
				}else{					
					$arrayAccountno["IS_CLOSESTATUS"] = $arrayAccountnoCheckGrp[array_search($account_no,array_column($arrayAccountnoCheckGrp,'DEPTACCOUNT_NO'))]["IS_CLOSESTATUS"];				
				}
				$arrGroupContract = array();
				$CoopName = $rowAccountNo["COOP_NAME"];
				$arrayAccountno["DEPTACCOUNT_NO"] = $account_no;
				$arrayAccountno["DEPTTYPE_DESC"] = $rowAccountNo["DEPTTYPE_DESC"];
				$arrayAccountno["CONFIRM_DATE"] = date('Y-m-d',strtotime($rowBalMaster["BALANCE_DATE"]));
				$arrayAccountno["BALANCE_AMT"] = number_format($rowAccountNo["BALANCE_AMT"],2);
				$arrayGroup[] = $arrayAccountno;
			}
			
		}
		$arrayResult["COOP_NAME"] = $CoopName;
		$arrayResult["MEMBER"] = $ref_memno;
		$arrayResult["ACCOUNT_CREDIT"] = $arrayGroup;
		$arrayResult["DATE"] = date('Y-m-d',strtotime($rowBalMaster["BALANCE_DATE"]));
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