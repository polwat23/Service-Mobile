<?php
require_once('../../../autoload.php');
if($lib->checkCompleteArgument(['unique_id','contdata'],$dataComing)){
	if($func->check_permission_core($payload,'line','constantslinenotifydeposit')){
		$arrayGroup = array();
		$arrayChkG = array();
		$fetchConstant = $conmysql->prepare("SELECT
												id_constantdept,
												dept_itemtype_code,
												allow_lbconstantdept
											FROM
												lbconstantdept
											ORDER BY dept_itemtype_code ASC");
		$fetchConstant->execute();
		while($rowMenuMobile = $fetchConstant->fetch(PDO::FETCH_ASSOC)){
			$arrConstans = array();
			$arrConstans["ID_CONSTANTDEPT"] = $rowMenuMobile["id_constantdept"];
			$arrConstans["DEPTITEMTYPE_CODE"] = $rowMenuMobile["dept_itemtype_code"];
			$arrConstans["ALLOW_LBCONSTANTDEPT"] = $rowMenuMobile["allow_lbconstantdept"];
			$arrayChkG[] = $arrConstans;
		}
			
		
		$fetchDepttype = $conoracle->prepare("SELECT DEPTITEMTYPE_CODE,DEPTITEMTYPE_DESC FROM DPUCFDEPTITEMTYPE ORDER BY DEPTITEMTYPE_CODE ASC  ");
		$fetchDepttype->execute();
		while($rowDepttype = $fetchDepttype->fetch(PDO::FETCH_ASSOC)){
			$arrayDepttype = array();
				if(array_search($rowDepttype["DEPTITEMTYPE_CODE"],array_column($arrayChkG,'DEPTITEMTYPE_CODE')) === False){
						$arrayDepttype["ALLOW_LBCONSTANTDEPT"] = 0;
				}else{
					$arrayDepttype["ALLOW_LBCONSTANTDEPT"] = $arrayChkG[array_search($rowDepttype["DEPTITEMTYPE_CODE"],array_column($arrayChkG,'DEPTITEMTYPE_CODE'))]["ALLOW_LBCONSTANTDEPT"];
				}				
			$arrayDepttype["DEPTITEMTYPE_CODE"] = $rowDepttype["DEPTITEMTYPE_CODE"];
			$arrayDepttype["DEPTITEMTYPE_DESC"] = $rowDepttype["DEPTITEMTYPE_DESC"];
			$arrayGroup[] = $arrayDepttype;
		}
		
		if($dataComing["contdata"] !== $arrayGroup){
			$resultUDiff = array_udiff($dataComing["contdata"],$arrayGroup,function ($loanChange,$loanOri){
				if ($loanChange === $loanOri){
					return 0;
				}else{
					return ($loanChange>$loanOri) ? 1 : -1;
				}
			});
			foreach($resultUDiff as $value_diff){
				if(array_search($value_diff["DEPTITEMTYPE_CODE"],array_column($arrayChkG,'DEPTITEMTYPE_CODE')) === False){
					$insertBulkCont[] = "('".$value_diff["DEPTITEMTYPE_CODE"]."','".$value_diff["ALLOW_SMSCONSTANTDEPT"]."')";
					$insertBulkContLog[]='DEPTITEMTYPE_CODE=> '.$value_diff["DEPTITEMTYPE_CODE"].' ALLOW_SMSCONSTANTDEPT ='.$value_diff["ALLOW_LBCONSTANTDEPT"];
				}else{
					$updateConst = $conmysql->prepare("UPDATE lbconstantdept SET allow_lbconstantdept = :ALLOW_LBCONSTANTDEPT WHERE dept_itemtype_code = :DEPTITEMTYPE_CODE");
					if($updateConst->execute([
						':ALLOW_LBCONSTANTDEPT' => $value_diff["ALLOW_SMSCONSTANTDEPT"],
						':DEPTITEMTYPE_CODE' => $value_diff["DEPTITEMTYPE_CODE"]
					])){
						$updateConstLog = 'DEPTITEMTYPE_CODE=> '.$value_diff["DEPTITEMTYPE_CODE"].' allow_lbconstantdept='.$value_diff["ALLOW_LBCONSTANTDEPT"];
					}
					
				}
			}
			
			
			$insertConst = $conmysql->prepare("INSERT lbconstantdept(dept_itemtype_code,allow_lbconstantdept)
															VALUES".implode(',',$insertBulkCont));
			$insertConst->execute();
			$arrayStruc = [
				':menu_name' => "constantslinenotifydeposit",
				':username' => $payload["username"],
				':use_list' =>"edit constant line dept",
				':details' => implode(',',$insertBulkContLog).' '.$updateConstLog
			];
			$log->writeLog('editsms',$arrayStruc);	
			$arrayResult['DATA'] = $updateConst;
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