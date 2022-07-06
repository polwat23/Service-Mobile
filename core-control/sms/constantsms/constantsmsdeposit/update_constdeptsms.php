<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','contdata'],$dataComing)){
	if($func->check_permission_core($payload,'sms','constantsmsdeposit',$conoracle)){
		$arrayGroup = array();
		$arrayChkG = array();
		$fetchConstant = $conoracle->prepare("SELECT
																		id_smsconstantdept,
																		dept_itemtype_code,
																		allow_smsconstantdept,
																		allow_notify
																	FROM
																		smsconstantdept
																	ORDER BY dept_itemtype_code ASC");
		$fetchConstant->execute();
		while($rowMenuMobile = $fetchConstant->fetch(PDO::FETCH_ASSOC)){
			$arrConstans = array();
			$arrConstans["ID_SMSCONSTANTDEPT"] = $rowMenuMobile["ID_SMSCONSTANTDEPT"];
			$arrConstans["DEPTITEMTYPE_CODE"] = $rowMenuMobile["DEPT_ITEMTYPE_CODE"];
			$arrConstans["ALLOW_SMSCONSTANTDEPT"] = $rowMenuMobile["ALLOW_SMSCONSTANTDEPT"];
			$arrConstans["ALLOW_NOTIFY"] = $rowMenuMobile["ALLOW_NOTIFY"];
			$arrayChkG[] = $arrConstans;
		}
		$fetchDepttype = $conoracle->prepare("SELECT DEPTITEMTYPE_CODE,DEPTITEMTYPE_DESC FROM DPUCFDEPTITEMTYPE ORDER BY DEPTITEMTYPE_CODE ASC  ");
		$fetchDepttype->execute();
		while($rowDepttype = $fetchDepttype->fetch(PDO::FETCH_ASSOC)){
			$arrayDepttype = array();
				if(array_search($rowDepttype["DEPTITEMTYPE_CODE"],array_column($arrayChkG,'DEPTITEMTYPE_CODE')) === False){
						$arrayDepttype["ALLOW_SMSCONSTANTDEPT"] = 0;
						$arrayDepttype["ALLOW_NOTIFY"] = 0;
				}else{
					$arrayDepttype["ALLOW_SMSCONSTANTDEPT"] = $arrayChkG[array_search($rowDepttype["DEPTITEMTYPE_CODE"],array_column($arrayChkG,'DEPTITEMTYPE_CODE'))]["ALLOW_SMSCONSTANTDEPT"];
					$arrayDepttype["ALLOW_NOTIFY"] = $arrayChkG[array_search($rowDepttype["DEPTITEMTYPE_CODE"],array_column($arrayChkG,'DEPTITEMTYPE_CODE'))]["ALLOW_NOTIFY"];
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
				$id_smsconstantdept = $func->getMaxTable('id_smsconstantdept' , 'smsconstantdept',$conoracle);
				foreach($resultUDiff as $value_diff){
					if(array_search($value_diff["DEPTITEMTYPE_CODE"],array_column($arrayChkG,'DEPTITEMTYPE_CODE')) === False){
						$insertBulkCont[] =  "INTO smsconstantdept(id_smsconstantdept,dept_itemtype_code,allow_smsconstantdept,allow_notify) VALUES(".$id_smsconstantdept.",'".$value_diff["DEPTITEMTYPE_CODE"]."','".$value_diff["ALLOW_SMSCONSTANTDEPT"]."','".$value_diff["ALLOW_NOTIFY"]."')";
						$insertBulkContLog[]='DEPTITEMTYPE_CODE=> '.$value_diff["DEPTITEMTYPE_CODE"].' ALLOW_SMSCONSTANTDEPT ='.$value_diff["ALLOW_SMSCONSTANTDEPT"].' ALLOW_NOTIFY ='.$value_diff["ALLOW_NOTIFY"];
						$id_smsconstantdept++;
					}else{
						$updateConst = $conoracle->prepare("UPDATE smsconstantdept SET allow_smsconstantdept = :ALLOW_SMSCONSTANTDEPT, allow_notify = :ALLOW_NOTIFY WHERE dept_itemtype_code = :DEPTITEMTYPE_CODE");
						$updateConst->execute([
							':ALLOW_SMSCONSTANTDEPT' => $value_diff["ALLOW_SMSCONSTANTDEPT"],
							':ALLOW_NOTIFY' => $value_diff["ALLOW_NOTIFY"],
							':DEPTITEMTYPE_CODE' => $value_diff["DEPTITEMTYPE_CODE"]
						]);
						$updateConstLog = 'DEPTITEMTYPE_CODE=> '.$value_diff["DEPTITEMTYPE_CODE"].' ALLOW_SMSCONSTANTDEPT='.$value_diff["ALLOW_SMSCONSTANTDEPT"].' ALLOW_NOTIFY='.$value_diff["ALLOW_NOTIFY"];
					}
				}
				$insertConst = $conoracle->prepare("INSERT ALL ".implode(' ',$insertBulkCont)." SELECT *  FROM DUAL");
				$insertConst->execute();
				$arrayStruc = [
					':menu_name' => "constantsmsdeposit",
					':username' => $payload["username"],
					':use_list' =>"edit constant sms dept",
					':details' => implode(',',$insertBulkContLog).' '.$updateConstLog
				];
				//$log->writeLog('editsms',$arrayStruc);	
				$arrayResult['RESULT'] = TRUE;
				$arrayResult['RESULT_A'] = $insertConst;
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