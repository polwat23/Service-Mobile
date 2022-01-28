<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'GuaranteeInfo')){
		$member_no = $payload["ref_memno"];
		$arrayResult = array();
		$formatDept = $func->getConstant('dep_format');
		
		if(isset($dataComing["contract_no"])){
			$arrayGroupLoan = array();
			$contract_no = $dataComing["contract_no"];
			$getWhocollu = $conoracle->prepare("SELECT lnm.principal_balance as PRNBAL,
												lnm.loancontract_no,
												lnm.LAST_PERIODPAY as LAST_PERIOD,
												lnm.period_installment as PERIOD,
												lt.LOANTYPE_DESC as TYPE_DESC
												FROM LCCONTMASTER lnm LEFT JOIN lCCFLOANTYPE lt ON lnm.LOANTYPE_CODE = lt.LOANTYPE_CODE WHERE lnm.loancontract_no = :contract_no
												and lnm.contract_status = 1");
			$getWhocollu->execute([':contract_no' => $contract_no]);
			$rowWhocollu = $getWhocollu->fetch(PDO::FETCH_ASSOC);
			$arrayGroupLoan["LOAN_BALANCE"] = number_format($rowWhocollu["PRNBAL"],2);
			$arrayGroupLoan["LAST_PERIOD"] = $rowWhocollu["LAST_PERIOD"].' / '.$rowWhocollu["PERIOD"];
			$arrayGroupLoan['TYPE_DESC'] = $rowWhocollu["TYPE_DESC"];
			$arrayGroupLoan["CONTRACT_NO"] = $contract_no;
			$arrGrpAllLoan = array();
			$getCollDetail = $conoracle->prepare("SELECT DISTINCT lnc.LOANCOLLTYPE_CODE,lnc.REF_COLLNO,lnc.COLL_PERCENT,lnc.BASE_AMT,
																		lnc.DESCRIPTION ,lcy.LOANCOLLTYPE_DESC
																		FROM lccontcoll lnc LEFT JOIN lcreqloancoll llc ON lnc.LOANCOLLTYPE_CODE = llc.LOANCOLLTYPE_CODE
																		AND lnc.REF_COLLNO = llc.REF_COLLNO
																		LEFT JOIN LCUCFLOANCOLLTYPE lcy ON lnc.LOANCOLLTYPE_CODE = lcy.LOANCOLLTYPE_CODE
																		WHERE lnc.coll_status = '1' and lnc.loancontract_no  = :contract_no ORDER BY lnc.LOANCOLLTYPE_CODE ASC");
			$getCollDetail->execute([':contract_no' => $contract_no]);
			while($rowColl = $getCollDetail->fetch(PDO::FETCH_ASSOC)){
				$arrGroupAll = array();
				$arrGroupAllMember = array();
				$arrGroupAll['LOANCOLLTYPE_CODE'] = $rowColl["LOANCOLLTYPE_CODE"];
				$arrGroupAll['COLLTYPE_DESC'] = $rowColl["LOANCOLLTYPE_DESC"];
				
				if($rowColl["LOANCOLLTYPE_CODE"] == '01'){ //บุคคลค้ำ
					$whocolluMember = $conoracle->prepare("SELECT MUP.PRENAME_DESC,MMB.MEMB_NAME,MMB.MEMB_ENAME
																FROM MBMEMBMASTER MMB LEFT JOIN MBUCFPRENAME MUP ON MMB.PRENAME_CODE = MUP.PRENAME_CODE
																LEFT JOIN LCREQLOAN LC ON  MMB.MEMBER_NO  = LC.MEMBER_NO
																WHERE lc.loanrequest_docno = :member_no");
					$whocolluMember->execute([':member_no' => $rowColl["REF_COLLNO"]]);
					$rowCollMember = $whocolluMember->fetch(PDO::FETCH_ASSOC);
					$arrayAvarTar = $func->getPathpic($rowColl["REF_COLLNO"]);
					$arrGroupAllMember["FULL_NAME"] = $rowColl["DESCRIPTION"];
					//$arrGroupAllMember["PERSON_ID"] = $rowColl["REF_COLLNO"];
				}else if($rowColl["LOANCOLLTYPE_CODE"] == '02'){
					$whocolluDept = $conoracle->prepare("SELECT DEPTACCOUNT_NAME FROM dpdeptmaster
																WHERE deptaccount_no = :deptaccount_no");
					$whocolluDept->execute([':deptaccount_no' => $rowColl["REF_COLLNO"]]);
					$rowCollDept = $whocolluDept->fetch(PDO::FETCH_ASSOC);
					$arrGroupAllMember["DEPTACCOUNT_NO"] = $lib->formataccount($rowColl["REF_COLLNO"],$formatDept);
					$arrGroupAllMember["DEPTACCOUNT_NAME"] = $rowCollDept["DEPTACCOUNT_NAME"];
					$arrGroupAllMember["DEPT_AMT"] = number_format($rowColl["BASE_AMT"],2);
					$arrGroupAllMember["DEPT_AMT_NOFORMAT"] = $rowColl["BASE_AMT"];
					$arrGroupAllMember["DESCRIPTION"] = $rowColl["DESCRIPTION"];	
				}else if($rowColl["LOANCOLLTYPE_CODE"] == '03'){
					$whocolluDept = $conoracle->prepare("SELECT DEPTACCOUNT_NAME FROM dpdeptmaster
																WHERE deptaccount_no = :deptaccount_no");
					$whocolluDept->execute([':deptaccount_no' => $rowColl["REF_COLLNO"]]);
					$rowCollDept = $whocolluDept->fetch(PDO::FETCH_ASSOC);
					$arrGroupAllMember["DEPTACCOUNT_NO"] = $lib->formataccount($rowColl["REF_COLLNO"],$formatDept);
					$arrGroupAllMember["DEPTACCOUNT_NAME"] = $rowCollDept["DEPTACCOUNT_NAME"];
					$arrGroupAllMember["DEPT_AMT"] = number_format($rowColl["BASE_AMT"],2);
					$arrGroupAllMember["DEPT_AMT_NOFORMAT"] = $rowColl["BASE_AMT"];
					$arrGroupAllMember["DESCRIPTION"] = $rowColl["DESCRIPTION"];	
				}else if($rowColl["LOANCOLLTYPE_CODE"] == '04'){
					$whocolluAsset = $conoracle->prepare("SELECT lcm.COLLMAST_REFNO ,lcm.COLLMAST_DESC
															FROM lccollmaster lcm LEFT JOIN lccollmaster lcd ON lcm.COLLMAST_NO = lcd.COLLMAST_NO 
															WHERE lcm.collmast_no = :collmast_no");
					$whocolluAsset->execute([':collmast_no' => $rowColl["REF_COLLNO"]]);
					$rowCollAsset = $whocolluAsset->fetch(PDO::FETCH_ASSOC);
					$arrGroupAllMember["COLL_DOCNO"] = $rowCollAsset["COLLMAST_NO"];
					if(isset($rowCollAsset["COLLMAST_REFNO"]) && $rowCollAsset["COLLMAST_REFNO"] != ""){
						$address = $rowCollAsset["COLLMAST_DESC"];
				
						$arrGroupAllMember["DESCRIPTION"] = $address;
					}else{
						$arrGroupAllMember['DESCRIPTION'] = $rowColl["DESCRIPTION"];
					}
				}else if($rowColl["LOANCOLLTYPE_CODE"] == '05'){
					$arrGroupAllMember["REALTY_NO"] = $rowColl["REF_COLLNO"];
					$arrGroupAllMember["DEPT_AMT"] = number_format($rowColl["BASE_AMT"],2);
					$arrGroupAllMember["DEPT_AMT_NOFORMAT"] = $rowColl["BASE_AMT"];
					$arrGroupAllMember["DESCRIPTION"] = $rowColl["DESCRIPTION"];	
				}
				if(array_search($rowColl["LOANCOLLTYPE_CODE"],array_column($arrGrpAllLoan,'LOANCOLLTYPE_CODE')) === False){
					$arrGroupAll['ASSET'][] = $arrGroupAllMember;
					$arrGrpAllLoan[] = $arrGroupAll;
				}else{
					($arrGrpAllLoan[array_search($rowColl["LOANCOLLTYPE_CODE"],array_column($arrGrpAllLoan,'LOANCOLLTYPE_CODE'))]["ASSET"])[] = $arrGroupAllMember;
				}
			}
			$arrayGroupLoan["GUARANTEE"] = $arrGrpAllLoan;
			$arrayResult['CONTRACT_COLL'][] = $arrayGroupLoan;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$arrGroupAllLoan = array();
			
			$fetchContractTypeCheck = $conmysql->prepare("SELECT balance_status FROM gcconstantbalanceconfirm WHERE member_no = :member_no");
			$fetchContractTypeCheck->execute([':member_no' => $payload["ref_memno"]]);
			$rowContractnoCheck = $fetchContractTypeCheck->fetch(PDO::FETCH_ASSOC);
			$Contractno  = $rowContractnoCheck["balance_status"] || "0" ;
			
			if($Contractno == "0"){
				$getWhocollu = $conoracle->prepare("SELECT lnm.principal_balance as PRNBAL,lnm.loancontract_no,
												lnm.LAST_PERIODPAY as LAST_PERIOD,
												lnm.period_installment as PERIOD,
												lt.LOANTYPE_DESC as TYPE_DESC
												FROM lccontmaster lnm LEFT JOIN LCCFLOANTYPE lt ON lnm.LOANTYPE_CODE = lt.LOANTYPE_CODE WHERE lnm.member_no = :member_no AND lnm.contract_status = 1
												GROUP BY lnm.loancontract_no,lnm.LAST_PERIODPAY,lt.LOANTYPE_DESC,lnm.principal_balance,lnm.period_installment");
				$getWhocollu->execute([':member_no' => $member_no]);
				while($rowWhocollu = $getWhocollu->fetch(PDO::FETCH_ASSOC)){
					$arrayGroupLoan = array();
					$arrayGroupLoan["LOAN_BALANCE"] = number_format($rowWhocollu["PRNBAL"],2);
					$arrayGroupLoan["LAST_PERIOD"] = $rowWhocollu["LAST_PERIOD"].' / '.$rowWhocollu["PERIOD"];
					$arrayGroupLoan['TYPE_DESC'] = $rowWhocollu["TYPE_DESC"];
					$arrayGroupLoan["CONTRACT_NO"] = $rowWhocollu["LOANCONTRACT_NO"];
					$arrGrpAllLoan = array();
					$getCollDetail = $conoracle->prepare("SELECT DISTINCT lnc.LOANCOLLTYPE_CODE,lnc.REF_COLLNO,lnc.COLL_PERCENT,lnc.BASE_AMT,
																		lnc.DESCRIPTION ,lcy.LOANCOLLTYPE_DESC
																		FROM lccontcoll lnc LEFT JOIN lcreqloancoll llc ON lnc.LOANCOLLTYPE_CODE = llc.LOANCOLLTYPE_CODE
																		AND lnc.REF_COLLNO = llc.REF_COLLNO
																		LEFT JOIN LCUCFLOANCOLLTYPE lcy ON lnc.LOANCOLLTYPE_CODE = lcy.LOANCOLLTYPE_CODE
																		WHERE lnc.coll_status = '1' and lnc.loancontract_no  = :contract_no ORDER BY lnc.LOANCOLLTYPE_CODE ASC");
					$getCollDetail->execute([':contract_no' => $rowWhocollu["LOANCONTRACT_NO"]]);
					while($rowColl = $getCollDetail->fetch(PDO::FETCH_ASSOC)){
						$arrGroupAll = array();
						$arrGroupAllMember = array();
						$arrGroupAll['LOANCOLLTYPE_CODE'] = $rowColl["LOANCOLLTYPE_CODE"];
						$arrGroupAll['COLLTYPE_DESC'] = $rowColl["LOANCOLLTYPE_DESC"];
						if($rowColl["LOANCOLLTYPE_CODE"] == '01'){
							$whocolluMember = $conoracle->prepare("SELECT MUP.PRENAME_DESC,MMB.MEMB_NAME,MMB.MEMB_ENAME
																FROM MBMEMBMASTER MMB LEFT JOIN MBUCFPRENAME MUP ON MMB.PRENAME_CODE = MUP.PRENAME_CODE
																LEFT JOIN LCREQLOAN LC ON  MMB.MEMBER_NO  = LC.MEMBER_NO
																WHERE lc.loanrequest_docno = :member_no");
							$whocolluMember->execute([':member_no' => $rowColl["REF_COLLNO"]]);
							$rowCollMember = $whocolluMember->fetch(PDO::FETCH_ASSOC);
							$arrayAvarTar = $func->getPathpic($rowColl["REF_COLLNO"]);
							$arrGroupAllMember["FULL_NAME"] = $rowColl["DESCRIPTION"];
							//$arrGroupAllMember["PERSON_ID"] = $rowColl["REF_COLLNO"];
						}else if($rowColl["LOANCOLLTYPE_CODE"] == '02'){
							$whocolluDept = $conoracle->prepare("SELECT DEPTACCOUNT_NAME FROM dpdeptmaster
																WHERE deptaccount_no = :deptaccount_no");
							$whocolluDept->execute([':deptaccount_no' => $rowColl["REF_COLLNO"]]);
							$rowCollDept = $whocolluDept->fetch(PDO::FETCH_ASSOC);
							$arrGroupAllMember["DEPTACCOUNT_NO"] = $lib->formataccount($rowColl["REF_COLLNO"],$formatDept);
							$arrGroupAllMember["DEPTACCOUNT_NAME"] = $rowCollDept["DEPTACCOUNT_NAME"];
							$arrGroupAllMember["DEPT_AMT"] = number_format($rowColl["BASE_AMT"],2);
							$arrGroupAllMember["DEPT_AMT_NOFORMAT"] = $rowColl["BASE_AMT"];
							$arrGroupAllMember["DESCRIPTION"] = $rowColl["DESCRIPTION"];	
						}else if($rowColl["LOANCOLLTYPE_CODE"] == '03'){
							$whocolluDept = $conoracle->prepare("SELECT DEPTACCOUNT_NAME FROM dpdeptmaster
																WHERE deptaccount_no = :deptaccount_no");
							$whocolluDept->execute([':deptaccount_no' => $rowColl["REF_COLLNO"]]);
							$rowCollDept = $whocolluDept->fetch(PDO::FETCH_ASSOC);
							$arrGroupAllMember["DEPTACCOUNT_NO"] = $lib->formataccount($rowColl["REF_COLLNO"],$formatDept);
							$arrGroupAllMember["DEPTACCOUNT_NAME"] = $rowCollDept["DEPTACCOUNT_NAME"];
							$arrGroupAllMember["DEPT_AMT"] = number_format($rowColl["BASE_AMT"],2);
							$arrGroupAllMember["DEPT_AMT_NOFORMAT"] = $rowColl["BASE_AMT"];
							$arrGroupAllMember["DESCRIPTION"] = $rowColl["DESCRIPTION"];					
						}else if($rowColl["LOANCOLLTYPE_CODE"] == '04'){
							$whocolluAsset = $conoracle->prepare("SELECT lcm.COLLMAST_REFNO ,lcm.COLLMAST_DESC
																				FROM lccollmaster lcm LEFT JOIN lccollmaster lcd ON lcm.COLLMAST_NO = lcd.COLLMAST_NO 
																				WHERE lcm.collmast_no = :collmast_no");
							$whocolluAsset->execute([':collmast_no' => $rowColl["REF_COLLNO"]]);
							$rowCollAsset = $whocolluAsset->fetch(PDO::FETCH_ASSOC);
							$arrGroupAllMember["COLL_DOCNO"] = $rowCollAsset["COLLMAST_NO"];
							if(isset($rowCollAsset["COLLMAST_REFNO"]) && $rowCollAsset["COLLMAST_REFNO"] != ""){
								$address = $rowCollAsset["COLLMAST_DESC"];
						
								$arrGroupAllMember["DESCRIPTION"] = $address;
							}else{
								$arrGroupAllMember['DESCRIPTION'] = $rowColl["DESCRIPTION"];
							}
						}else if($rowColl["LOANCOLLTYPE_CODE"] == '05'){
							$arrGroupAllMember["REALTY_NO"] = $rowColl["REF_COLLNO"];
							$arrGroupAllMember["DEPT_AMT"] = number_format($rowColl["BASE_AMT"],2);
							$arrGroupAllMember["DEPT_AMT_NOFORMAT"] = $rowColl["BASE_AMT"];
							$arrGroupAllMember["DESCRIPTION"] = $rowColl["DESCRIPTION"];	
						}
						if(array_search($rowColl["LOANCOLLTYPE_CODE"],array_column($arrGrpAllLoan,'LOANCOLLTYPE_CODE')) === False){
							$arrGroupAll['ASSET'][] = $arrGroupAllMember;
							$arrGrpAllLoan[] = $arrGroupAll;
						}else{
							($arrGrpAllLoan[array_search($rowColl["LOANCOLLTYPE_CODE"],array_column($arrGrpAllLoan,'LOANCOLLTYPE_CODE'))]["ASSET"])[] = $arrGroupAllMember;
						}
					}
					$arrayGroupLoan["GUARANTEE"] = $arrGrpAllLoan;
					$arrGroupAllLoan[] = $arrayGroupLoan;
				}
				$arrayResult['CONTRACT_COLL'] = $arrGroupAllLoan;
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0114";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				http_response_code(403);
				require_once('../../include/exit_footer.php');
			}		
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