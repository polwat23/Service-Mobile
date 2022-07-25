<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'BindAccountConsent')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$fetchDataMember = $conoracle->prepare("SELECT TRIM(card_person) as CARD_PERSON FROM mbmembmaster WHERE member_no = :member_no");
		$fetchDataMember->execute([
			':member_no' => $member_no
		]);
		$rowDataMember = $fetchDataMember->fetch(PDO::FETCH_ASSOC);
		if(isset($rowDataMember["CARD_PERSON"])){
			$arrGrpAccFee = array();
			
			$getDeptAtm = $conoracle->prepare("SELECT COOP_ACC FROM atmdept WHERE member_no = :member_no");
			$getDeptAtm->execute([':member_no' => $member_no]);
			$rowDeptAtm = $getDeptAtm->fetch(PDO::FETCH_ASSOC);
			if(isset($rowDeptAtm["COOP_ACC"])){
				$getDepositAcc = $conoracle->prepare("select (select concat(concat(m.memb_name,' '),m.memb_surname) from mbmembmaster m 
												where d.member_no=m.member_no ) as DEPTACCOUNT_NAME,DEPTACCOUNT_NO,d.DEPTTYPE_CODE,dt.DEPTTYPE_DESC,PRNCBAL
												from   atmdept a LEFT JOIN  dpdeptmaster d  ON d.member_no = a.member_no  
												AND  d.deptaccount_no = a.coop_acc 
												AND d.depttype_code = a.depttype_code
												LEFT JOIN dpdepttype dt ON  d.depttype_code = dt.depttype_code
												where d.depttype_code in ( '01') AND  trim(d.member_no) = :member_no
												and d.deptclose_status= '0'
												order by deptaccount_no desc");
				
				$getDepositAcc->execute([':member_no' => $member_no]);
				while($rowDepAcc = $getDepositAcc->fetch(PDO::FETCH_ASSOC)){
				$arrAccFee = array();
				$arrAccFee['ACCOUNT_NO'] = $lib->formataccount($rowDepAcc["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
				$arrAccFee['ACCOUNT_NAME'] = TRIM($rowDepAcc["DEPTACCOUNT_NAME"]);
				$arrAccFee['BALANCE'] = number_format($rowDepAcc["PRNCBAL"],2);
				$arrAccFee['DEPTTYPE_DESC'] = $rowDepAcc["DEPTTYPE_DESC"];
				$arrGrpAccFee[] = $arrAccFee;
				}
				$arrayResult['REMARK_PAYFEE'] = $configError["REMARK_PAYFEE"][0][$lang_locale];
				$arrayResult['ACCOUNT_PAYFEE'] = $arrGrpAccFee;
				$arrayResult['CITIZEN_ID_FORMAT'] = $lib->formatcitizen($rowDataMember["CARD_PERSON"]);
				if($payload["member_no"] == 'ETNMODE3'){
					$arrayResult['CITIZEN_ID'] = '1530400073734';
				}else{
					$arrayResult['CITIZEN_ID'] = $rowDataMember["CARD_PERSON"];
				}
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$getDeptmaster= $conoracle->prepare("SELECT  DEPTACCOUNT_NO FROM dpdeptmaster WHERE member_no = :member_no");	
				$getDeptmaster->execute([':member_no' => $member_no]);
				while($rowAccIncoop = $getDeptmaster->fetch(PDO::FETCH_ASSOC)){
					$arrDeptNo[] = $rowAccIncoop["DEPTACCOUNT_NO"];
				}
				$getDeptAtmrollback = $conoracle->prepare("SELECT COOP_ACC FROM atmdept WHERE COOP_ACC IN(".implode(',',$arrDeptNo).")");
				$getDeptAtmrollback->execute([':member_no' => $member_no]);
				$rowDeptAtmrollback = $getDeptAtmrollback->fetch(PDO::FETCH_ASSOC);
				if(isset($rowDeptAtmrollback["COOP_ACC"])){
					$getDepositAcc = $conoracle->prepare("select (select concat(concat(m.memb_name,' '),m.memb_surname) from mbmembmaster m 
											where d.member_no = m.member_no ) as DEPTACCOUNT_NAME,DEPTACCOUNT_NO,d.DEPTTYPE_CODE,dt.DEPTTYPE_DESC,PRNCBAL
											from   dpdeptmaster d LEFT JOIN  atmdept a  ON d.member_no = a.member_no 
											AND  d.deptaccount_no = a.coop_acc 
											AND d.depttype_code = a.depttype_code
											LEFT JOIN dpdepttype dt ON  d.depttype_code = dt.depttype_code
											where d.depttype_code in ( '01')
											and d.deptaccount_no IN(".implode(',',$arrDeptNo).")
											and d.deptclose_status= '0'
											order by deptaccount_no desc");
					$getDepositAcc->execute();
					while($rowDepAcc = $getDepositAcc->fetch(PDO::FETCH_ASSOC)){
						$arrAccFee = array();
						$arrAccFee['ACCOUNT_NO'] = $lib->formataccount($rowDepAcc["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
						$arrAccFee['ACCOUNT_NAME'] = TRIM($rowDepAcc["DEPTACCOUNT_NAME"]);
						$arrAccFee['BALANCE'] = number_format($rowDepAcc["PRNCBAL"],2);
						$arrAccFee['DEPTTYPE_DESC'] = $rowDepAcc["DEPTTYPE_DESC"];
						$arrGrpAccFee[] = $arrAccFee;
					}						
				}
				$arrayResult['REMARK_PAYFEE'] = $configError["REMARK_PAYFEE"][0][$lang_locale];
				$arrayResult['ACCOUNT_PAYFEE'] = $arrGrpAccFee;
				$arrayResult['CITIZEN_ID_FORMAT'] = $lib->formatcitizen($rowDataMember["CARD_PERSON"]);
				if($payload["member_no"] == 'ETNMODE3'){
					$arrayResult['CITIZEN_ID'] = '1530400073734';
				}else{
					$arrayResult['CITIZEN_ID'] = $rowDataMember["CARD_PERSON"];
				}
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}	
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0003";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../include/exit_footer.php');
		
	}
}else{
	$filename = basename(__FILE__, '.php');
	$logStruc = [
		":error_menu" => $filename,
		":error_code" => "WS4004",
		":error_desc" => "ส่ง Argument มาไม่ครบ "."\n".json_encode($dataComing),
		":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
	];
	$log->writeLog('errorusage',$logStruc);
	$message_error = "ไฟล์ ".$filename." ส่ง Argument มาไม่ครบมาแค่ "."\n".json_encode($dataComing);
	$lib->sendLineNotify($message_error);
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
	
}
?>
