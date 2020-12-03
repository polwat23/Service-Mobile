<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','contdata'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','constantdeptaccount')){
		$arrayGroup = array();
		$arrayChkG = array();
		$fetchConstant = $conmysql->prepare("SELECT
																		id_accountconstant,
																		dept_type_code,
																		member_cate_code,
																		allow_transaction,
																		allow_showdetail
																	FROM
																		gcconstantaccountdept
																	ORDER BY dept_type_code ASC");
		$fetchConstant->execute();
		while($rowMenuMobile = $fetchConstant->fetch(PDO::FETCH_ASSOC)){
			$arrConstans = array();
			$arrConstans["ID_ACCCONSTANT"] = $rowMenuMobile["id_accountconstant"];
			$arrConstans["DEPTTYPE_CODE"] = $rowMenuMobile["dept_type_code"];
			$arrConstans["MEMBER_TYPE_CODE"] = $rowMenuMobile["member_cate_code"];
			$arrConstans["ALLOW_TRANSACTION"] = $rowMenuMobile["allow_transaction"];
			$arrConstans["ALLOW_SHOWDETAIL"] = $rowMenuMobile["allow_showdetail"];
			$arrayChkG[] = $arrConstans;
		}
		$fetchDepttype = $conmssql->prepare("SELECT DEPTTYPE_CODE,DEPTTYPE_DESC FROM DPDEPTTYPE ORDER BY DEPTTYPE_CODE ASC  ");
		$fetchDepttype->execute();
		while($rowDepttype = $fetchDepttype->fetch(PDO::FETCH_ASSOC)){
			$arrayDepttype = array();
				if(array_search($rowDepttype["DEPTTYPE_CODE"],array_column($arrayChkG,'DEPTTYPE_CODE')) === False){
						$arrayDepttype["ALLOW_TRANSACTION"] = 0;
						$arrayDepttype["MEMBER_TYPE_CODE"] = 'AL';
						$arrayDepttype["ALLOW_SHOWDETAIL"] = 0;
				}else{
					$arrayDepttype["ALLOW_TRANSACTION"] = $arrayChkG[array_search($rowDepttype["DEPTTYPE_CODE"],array_column($arrayChkG,'DEPTTYPE_CODE'))]["ALLOW_TRANSACTION"];
					$arrayDepttype["MEMBER_TYPE_CODE"] = $arrayChkG[array_search($rowDepttype["DEPTTYPE_CODE"],array_column($arrayChkG,'DEPTTYPE_CODE'))]["MEMBER_TYPE_CODE"];
					$arrayDepttype["ALLOW_SHOWDETAIL"] = $arrayChkG[array_search($rowDepttype["DEPTTYPE_CODE"],array_column($arrayChkG,'DEPTTYPE_CODE'))]["ALLOW_SHOWDETAIL"];
				}
				
			$arrayDepttype["DEPTTYPE_CODE"] = $rowDepttype["DEPTTYPE_CODE"];
			$arrayDepttype["DEPTTYPE_DESC"] = $rowDepttype["DEPTTYPE_DESC"];
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
					if(array_search($value_diff["DEPTTYPE_CODE"],array_column($arrayChkG,'DEPTTYPE_CODE')) === False){
						$insertBulkCont[] = "('".$value_diff["DEPTTYPE_CODE"]."','".$value_diff["MEMBER_TYPE_CODE"]."','".$value_diff["ALLOW_TRANSACTION"]."','".$value_diff["ALLOW_SHOWDETAIL"]."')";
						$insertBulkContLog[]='DEPTTYPE_CODE=> '.$value_diff["DEPTTYPE_CODE"].' MEMBER_TYPE_CODE ='.$value_diff["MEMBER_TYPE_CODE"].' ALLOW_TRANSACTION ='.$value_diff["ALLOW_TRANSACTION"].'ALLOW_SHOWDETAIL= '.$value_diff["ALLOW_SHOWDETAIL"];
					}else{
						$updateConst = $conmysql->prepare("UPDATE gcconstantaccountdept SET member_cate_code = :MEMBER_TYPE_CODE,allow_transaction = :ALLOW_TRANSACTION,allow_showdetail = :ALLOW_SHOWDETAIL WHERE dept_type_code = :DEPTTYPE_CODE");
						$updateConst->execute([
							':MEMBER_TYPE_CODE' => $value_diff["MEMBER_TYPE_CODE"],
							':ALLOW_TRANSACTION' => $value_diff["ALLOW_TRANSACTION"],
							':DEPTTYPE_CODE' => $value_diff["DEPTTYPE_CODE"],
							':ALLOW_SHOWDETAIL' => $value_diff["ALLOW_SHOWDETAIL"]
						]);
						$updateConstLog = 'DEPTTYPE_CODE=> '.$value_diff["DEPTTYPE_CODE"].' MEMBER_TYPE_CODE ='.$value_diff["MEMBER_TYPE_CODE"].' MEMBER_TYPE_CODE='.$value_diff["ALLOW_TRANSACTION"].' ALLOW_SHOWDETAIL'.$value_diff["ALLOW_SHOWDETAIL"];
					}
				}
				$insertConst = $conmysql->prepare("INSERT gcconstantaccountdept(dept_type_code,member_cate_code,allow_transaction,allow_showdetail)
																VALUES".implode(',',$insertBulkCont));
				$insertConst->execute();
				$arrayStruc = [
					':menu_name' => "constantdeptaccount",
					':username' => $payload["username"],
					':use_list' =>"edit constant dept",
					':details' => implode(',',$insertBulkContLog).' '.$updateConstLog
				];
				$log->writeLog('manageuser',$arrayStruc);	
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
