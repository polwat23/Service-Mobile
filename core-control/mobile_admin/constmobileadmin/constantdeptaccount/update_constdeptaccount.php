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
																		allow_deposit_inside,
																		allow_withdraw_inside,
																		allow_deposit_outside,
																		allow_withdraw_outside,
																		allow_pay_loan,
																		allow_buyshare,
																		allow_receive_loan
																	FROM
																		gcconstantaccountdept
																	ORDER BY dept_type_code ASC");
		$fetchConstant->execute();
		while($rowMenuMobile = $fetchConstant->fetch(PDO::FETCH_ASSOC)){
			$arrConstans = array();
			$arrConstans["ID_ACCCONSTANT"] = $rowMenuMobile["id_accountconstant"];
			$arrConstans["DEPTTYPE_CODE"] = $rowMenuMobile["dept_type_code"];
			$arrConstans["MEMBER_TYPE_CODE"] = $rowMenuMobile["member_cate_code"];
			$arrConstans["ALLOW_DEPOSIT_INSIDE"] = $rowMenuMobile["allow_deposit_inside"];
			$arrConstans["ALLOW_WITHDRAW_INSIDE"] = $rowMenuMobile["allow_withdraw_inside"];
			$arrConstans["ALLOW_DEPOSIT_OUTSIDE"] = $rowMenuMobile["allow_deposit_outside"];
			$arrConstans["ALLOW_WITHDRAW_OUTSIDE"] = $rowMenuMobile["allow_withdraw_outside"];
			$arrConstans["ALLOW_PAYLOAN"] = $rowMenuMobile["allow_pay_loan"];
			$arrConstans["ALLOW_BUYSHARE"] = $rowMenuMobile["allow_buyshare"];
			$arrConstans["ALLOW_RECEIVE_LOAN"] = $rowMenuMobile["allow_receive_loan"];
			$arrayChkG[] = $arrConstans;
		}
		$fetchDepttype = $conmssql->prepare("SELECT DEPTTYPE_CODE,DEPTTYPE_DESC FROM DPDEPTTYPE ORDER BY DEPTTYPE_CODE ASC  ");
		$fetchDepttype->execute();
		while($rowDepttype = $fetchDepttype->fetch(PDO::FETCH_ASSOC)){
			$arrayDepttype = array();
				if(array_search($rowDepttype["DEPTTYPE_CODE"],array_column($arrayChkG,'DEPTTYPE_CODE')) === False){
						$arrayDepttype["ALLOW_DEPOSIT_INSIDE"] = 0;
						$arrayDepttype["ALLOW_WITHDRAW_INSIDE"] = 0;
						$arrayDepttype["ALLOW_DEPOSIT_OUTSIDE"] = 0;
						$arrayDepttype["ALLOW_WITHDRAW_OUTSIDE"] = 0;
						$arrayDepttype["ALLOW_PAYLOAN"] = 0;
						$arrayDepttype["ALLOW_BUYSHARE"] = 0;
						$arrayDepttype["ALLOW_RECEIVE_LOAN"] = 0;
						$arrayDepttype["MEMBER_TYPE_CODE"] = 'AL';
				}else{
					$arrayDepttype["ALLOW_DEPOSIT_INSIDE"] = $arrayChkG[array_search($rowDepttype["DEPTTYPE_CODE"],array_column($arrayChkG,'DEPTTYPE_CODE'))]["ALLOW_DEPOSIT_INSIDE"];
					$arrayDepttype["ALLOW_WITHDRAW_INSIDE"] = $arrayChkG[array_search($rowDepttype["DEPTTYPE_CODE"],array_column($arrayChkG,'DEPTTYPE_CODE'))]["ALLOW_WITHDRAW_INSIDE"];
					$arrayDepttype["ALLOW_DEPOSIT_OUTSIDE"] = $arrayChkG[array_search($rowDepttype["DEPTTYPE_CODE"],array_column($arrayChkG,'DEPTTYPE_CODE'))]["ALLOW_DEPOSIT_OUTSIDE"];
					$arrayDepttype["ALLOW_WITHDRAW_OUTSIDE"] = $arrayChkG[array_search($rowDepttype["DEPTTYPE_CODE"],array_column($arrayChkG,'DEPTTYPE_CODE'))]["ALLOW_WITHDRAW_OUTSIDE"];
					$arrayDepttype["ALLOW_PAYLOAN"] = $arrayChkG[array_search($rowDepttype["DEPTTYPE_CODE"],array_column($arrayChkG,'DEPTTYPE_CODE'))]["ALLOW_PAYLOAN"];
					$arrayDepttype["ALLOW_BUYSHARE"] = $arrayChkG[array_search($rowDepttype["DEPTTYPE_CODE"],array_column($arrayChkG,'DEPTTYPE_CODE'))]["ALLOW_BUYSHARE"];
					$arrayDepttype["ALLOW_BUYSHARE"] = $arrayChkG[array_search($rowDepttype["DEPTTYPE_CODE"],array_column($arrayChkG,'DEPTTYPE_CODE'))]["ALLOW_BUYSHARE"];
					$arrayDepttype["MEMBER_TYPE_CODE"] = $arrayChkG[array_search($rowDepttype["DEPTTYPE_CODE"],array_column($arrayChkG,'DEPTTYPE_CODE'))]["MEMBER_TYPE_CODE"];
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
						$insertBulkCont[] = "('".$value_diff["DEPTTYPE_CODE"]."','".$value_diff["MEMBER_TYPE_CODE"]."','".$value_diff["ALLOW_DEPOSIT_INSIDE"]."','".$value_diff["ALLOW_WITHDRAW_INSIDE"]."','".$value_diff["ALLOW_DEPOSIT_OUTSIDE"]."','".$value_diff["ALLOW_WITHDRAW_OUTSIDE"]."','".$value_diff["ALLOW_PAYLOAN"]."','".$value_diff["ALLOW_BUYSHARE"]."')";
						$insertBulkContLog[]='DEPTTYPE_CODE=> '.$value_diff["DEPTTYPE_CODE"].' MEMBER_TYPE_CODE ='.$value_diff["MEMBER_TYPE_CODE"].' ALLOW_DEPOSIT_INSIDE ='.$value_diff["ALLOW_DEPOSIT_INSIDE"].' ALLOW_WITHDRAW_INSIDE ='.$value_diff["ALLOW_WITHDRAW_INSIDE"].' ALLOW_DEPOSIT_OUTSIDE ='.$value_diff["ALLOW_DEPOSIT_OUTSIDE"].' ALLOW_WITHDRAW_OUTSIDE ='.
						$value_diff["ALLOW_WITHDRAW_OUTSIDE"].' ALLOW_PAYLOAN ='.$value_diff["ALLOW_PAYLOAN"].' ALLOW_BUYSHARE ='.$value_diff["ALLOW_BUYSHARE"].' ALLOW_RECEIVE_LOAN ='.$value_diff["ALLOW_RECEIVE_LOAN"];
					}else{
						$updateConst = $conmysql->prepare("UPDATE gcconstantaccountdept 
																			SET member_cate_code = :MEMBER_TYPE_CODE,
																			allow_deposit_inside = :ALLOW_DEPOSIT_INSIDE,
																			allow_withdraw_inside = :ALLOW_WITHDRAW_INSIDE,
																			allow_deposit_outside = :ALLOW_DEPOSIT_OUTSIDE,
																			allow_withdraw_outside = :ALLOW_WITHDRAW_OUTSIDE,
																			allow_pay_loan = :ALLOW_PAYLOAN,
																			allow_buyshare = :ALLOW_BUYSHARE,
																			allow_receive_loan = :ALLOW_RECEIVE_LOAN
																			WHERE dept_type_code = :DEPTTYPE_CODE");
						$updateConst->execute([
							':MEMBER_TYPE_CODE' => $value_diff["MEMBER_TYPE_CODE"],
							':ALLOW_DEPOSIT_INSIDE' => $value_diff["ALLOW_DEPOSIT_INSIDE"],
							':ALLOW_WITHDRAW_INSIDE' => $value_diff["ALLOW_WITHDRAW_INSIDE"],
							':ALLOW_DEPOSIT_OUTSIDE' => $value_diff["ALLOW_DEPOSIT_OUTSIDE"],
							':ALLOW_WITHDRAW_OUTSIDE' => $value_diff["ALLOW_WITHDRAW_OUTSIDE"],
							':ALLOW_PAYLOAN' => $value_diff["ALLOW_PAYLOAN"],
							':ALLOW_BUYSHARE' => $value_diff["ALLOW_BUYSHARE"],
							':ALLOW_RECEIVE_LOAN' => $value_diff["ALLOW_RECEIVE_LOAN"],
							':DEPTTYPE_CODE' => $value_diff["DEPTTYPE_CODE"]
						]);
						$updateConstLog = 'DEPTTYPE_CODE=> '.$value_diff["DEPTTYPE_CODE"].' MEMBER_TYPE_CODE ='.$value_diff["MEMBER_TYPE_CODE"].' ALLOW_DEPOSIT_INSIDE='.
						$value_diff["ALLOW_DEPOSIT_INSIDE"].' ALLOW_WITHDRAW_INSIDE='.$value_diff["ALLOW_WITHDRAW_INSIDE"].' ALLOW_DEPOSIT_OUTSIDE='.$value_diff["ALLOW_DEPOSIT_OUTSIDE"].
						' ALLOW_WITHDRAW_OUTSIDE='.$value_diff["ALLOW_WITHDRAW_OUTSIDE"].' ALLOW_PAY_LOAN='.$value_diff["ALLOW_PAYLOAN"].' ALLOW_RECEIVE_LOAN='.$value_diff["ALLOW_RECEIVE_LOAN"];
					}
				}
				$insertConst = $conmysql->prepare("INSERT gcconstantaccountdept(dept_type_code,member_cate_code,allow_deposit_inside,allow_withdraw_inside,allow_deposit_outside,allow_withdraw_outside,allow_pay_loan,allow_buyshare,allow_receive_loan)
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