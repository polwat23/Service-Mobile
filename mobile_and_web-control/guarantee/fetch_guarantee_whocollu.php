<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'GuaranteeInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		
		if(isset($dataComing["contract_no"])){
			$arrayGroupLoan = array();
			$contract_no = preg_replace('/\//','',$dataComing["contract_no"]);
			$getWhocollu = $conmssql->prepare("SELECT lnm.principal_balance as PRNBAL,lnm.loancontract_no,IsNull(lnm.loanapprove_amt,0) as APPROVE_AMT,lt.LOANTYPE_DESC as TYPE_DESC
												FROM lncontmaster lnm LEFT JOIN LNLOANTYPE lt ON lnm.LOANTYPE_CODE = lt.LOANTYPE_CODE WHERE lnm.loancontract_no = :contract_no
												and lnm.contract_status > 0 and lnm.contract_status <> 8");
			$getWhocollu->execute([':contract_no' => $contract_no]);
			$rowWhocollu = $getWhocollu->fetch(PDO::FETCH_ASSOC);
			$arrayGroupLoan['APPROVE_AMT'] = number_format($rowWhocollu["APPROVE_AMT"],2);
			$arrayGroupLoan['TYPE_DESC'] = $rowWhocollu["TYPE_DESC"];
			$arrayGroupLoan["CONTRACT_NO"] = $contract_no;
			$arrGrpAllLoan = array();
			$getCollDetail = $conmssql->prepare("SELECT DISTINCT lnc.LOANCOLLTYPE_CODE,llc.LOANCOLLTYPE_DESC,lnc.REF_COLLNO,lnc.COLL_PERCENT,
																lnc.DESCRIPTION
																FROM lncontcoll lnc LEFT JOIN lnucfloancolltype llc ON lnc.LOANCOLLTYPE_CODE = llc.LOANCOLLTYPE_CODE
																WHERE lnc.coll_status = '1' and lnc.loancontract_no = :contract_no ORDER BY lnc.LOANCOLLTYPE_CODE ASC");
			$getCollDetail->execute([':contract_no' => $contract_no]);
			while($rowColl = $getCollDetail->fetch(PDO::FETCH_ASSOC)){
				$arrGroupAll = array();
				$arrGroupAllMember = array();
				$arrGroupAll['LOANCOLLTYPE_CODE'] = $rowColl["LOANCOLLTYPE_CODE"];
				$arrGroupAll['COLLTYPE_DESC'] = $rowColl["LOANCOLLTYPE_DESC"];
				if($rowColl["LOANCOLLTYPE_CODE"] == '01'){
					$whocolluMember = $conmssql->prepare("SELECT MUP.PRENAME_DESC,MMB.MEMB_NAME,MMB.MEMB_SURNAME
														FROM MBMEMBMASTER MMB LEFT JOIN MBUCFPRENAME MUP ON MMB.PRENAME_CODE = MUP.PRENAME_CODE
														WHERE MMB.member_no = :member_no");
					$whocolluMember->execute([':member_no' => $rowColl["REF_COLLNO"]]);
					$rowCollMember = $whocolluMember->fetch(PDO::FETCH_ASSOC);
					$arrayAvarTar = $func->getPathpic($rowColl["REF_COLLNO"]);
					$arrGroupAllMember["AVATAR_PATH"] = isset($arrayAvarTar["AVATAR_PATH"]) ? $config["URL_SERVICE"].$arrayAvarTar["AVATAR_PATH"] : null;
					$arrGroupAllMember["AVATAR_PATH_WEBP"] = isset($arrayAvarTar["AVATAR_PATH_WEBP"]) ? $config["URL_SERVICE"].$arrayAvarTar["AVATAR_PATH_WEBP"] : null;
					$arrGroupAllMember["FULL_NAME"] = $rowCollMember["PRENAME_DESC"].$rowCollMember["MEMB_NAME"].' '.$rowCollMember["MEMB_SURNAME"];
					$arrGroupAllMember["MEMBER_NO"] = $rowColl["REF_COLLNO"];
				}else if($rowColl["LOANCOLLTYPE_CODE"] == '02'){
					$whocolluMember = $conmssql->prepare("SELECT MUP.PRENAME_DESC,MMB.MEMB_NAME,MMB.MEMB_SURNAME
														FROM MBMEMBMASTER MMB LEFT JOIN MBUCFPRENAME MUP ON MMB.PRENAME_CODE = MUP.PRENAME_CODE
														WHERE MMB.member_no = :member_no");
					$whocolluMember->execute([':member_no' => $rowColl["REF_COLLNO"]]);
					$rowCollMember = $whocolluMember->fetch(PDO::FETCH_ASSOC);
					$arrGroupAllMember["SHARE_COLL_AMT"] = number_format($rowWhocollu["PRNBAL"] * $rowColl["COLL_PERCENT"],2);
					$arrGroupAllMember["FULL_NAME"] = $rowCollMember["PRENAME_DESC"].$rowCollMember["MEMB_NAME"].' '.$rowCollMember["MEMB_SURNAME"];
					$arrGroupAllMember["MEMBER_NO"] = $rowColl["REF_COLLNO"];
				}else if($rowColl["LOANCOLLTYPE_CODE"] == '03'){
					$whocolluDept = $conmssql->prepare("SELECT DEPTACCOUNT_NAME FROM dpdeptmaster
														WHERE deptaccount_no = :deptaccount_no");
					$whocolluDept->execute([':deptaccount_no' => $rowColl["REF_COLLNO"]]);
					$rowCollDept = $whocolluDept->fetch(PDO::FETCH_ASSOC);
					$arrGroupAllMember["DEPTACCOUNT_NO"] = $lib->formataccount_hidden($lib->formataccount($rowColl["REF_COLLNO"],$func->getConstant('dep_format')),$func->getConstant('hidden_dep'));
					$arrGroupAllMember["DEPTACCOUNT_NAME"] = $rowCollDept["DEPTACCOUNT_NAME"];
					$arrGroupAllMember["DEPT_AMT"] = number_format($rowWhocollu["PRNBAL"] * $rowColl["COLL_PERCENT"],2);
				}else if($rowColl["LOANCOLLTYPE_CODE"] == '04'){
					$whocolluAsset = $conmssql->prepare("SELECT lcm.COLLMAST_REFNO,lcd.LAND_LANDNO,lcd.POS_TUMBOL,MBD.DISTRICT_DESC,MBP.PROVINCE_DESC,lcm.COLLMAST_NO
																		FROM lncollmaster lcm LEFT JOIN lncolldetail lcd ON lcm.COLLMAST_NO = lcd.COLLMAST_NO 
																		LEFT JOIN MBUCFDISTRICT MBD ON lcd.POS_DISTRICT = MBD.DISTRICT_CODE
																		LEFT JOIN MBUCFPROVINCE MBP ON lcd.POS_PROVINCE = MBP.PROVINCE_CODE
																		WHERE lcm.collmast_no = :collmast_no");
					$whocolluAsset->execute([':collmast_no' => $rowColl["REF_COLLNO"]]);
					$rowCollAsset = $whocolluAsset->fetch(PDO::FETCH_ASSOC);
					$arrGroupAllMember["COLL_DOCNO"] = $rowCollAsset["COLLMAST_NO"];
					if(isset($rowCollAsset["COLLMAST_REFNO"]) && $rowCollAsset["COLLMAST_REFNO"] != ""){
						$address =  isset($rowCollAsset["COLLMAST_REFNO"]) && $rowCollAsset["COLLMAST_REFNO"] != "" ? "โฉนดเลขที่ ".$rowCollAsset["COLLMAST_REFNO"] : "";
						$address .= isset($rowCollAsset["LAND_LANDNO"]) && $rowCollAsset["LAND_LANDNO"] != "" ? " บ้านเลขที่ ".$rowCollAsset["LAND_LANDNO"] : "";
						$address .= isset($rowCollAsset["POS_TUMBOL"]) && $rowCollAsset["POS_TUMBOL"] != "" ? " ต.".$rowCollAsset["POS_TUMBOL"] : "";
						$address .= isset($rowCollAsset["DISTRIC_DESC"]) && $rowCollAsset["DISTRIC_DESC"] != "" ? " อ.".$rowCollAsset["DISTRIC_DESC"] : "";
						$address .= isset($rowCollAsset["PROVINCE_DESC"]) && $rowCollAsset["PROVINCE_DESC"] != "" ? " จ.".$rowCollAsset["PROVINCE_DESC"] : "";
						$arrGroupAllMember["DESCRIPTION"] = $address;
					}else{
						$arrGroupAllMember['DESCRIPTION'] = $rowColl["DESCRIPTION"];
					}
				}else if($rowColl["LOANCOLLTYPE_CODE"] == '05'){
					$arrGroupAllMember['DESCRIPTION'] = $rowColl["DESCRIPTION"];
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
			$getWhocollu = $conmssqlcoop->prepare("SELECT (isnull(lnm.amount,0) - isnull(lnm.principal_actual,0))  as PRNBAL, lnm.doc_no as LOANCONTRACT_NO,
												IsNull(lnm.amount,0) as APPROVE_AMT,lt.description as TYPE_DESC,lnm.HOLD,lnm.HOLD_PRINCIPALONLY
												FROM coloanmember lnm LEFT JOIN cointerestrate_desc lt ON lnm.TYPE = lt.TYPE 
												WHERE lnm.member_id = :member_no and lnm.status ='A'
												GROUP BY lnm.doc_no,IsNull(lnm.amount,0),lt.description,lnm.principal_actual,lnm.HOLD,lnm.HOLD_PRINCIPALONLY");
			$getWhocollu->execute([':member_no' => $member_no]);
			while($rowWhocollu = $getWhocollu->fetch(PDO::FETCH_ASSOC)){
				$arrayGroupLoan = array();
				if($rowWhocollu["HOLD"] == "1"){
					$arrayGroupLoan["CONTRACT_STATUS"] = "พักชำระทั้งหมด";
				}else if($rowWhocollu["HOLD_PRINCIPALONLY"] == "1"){
					$arrayGroupLoan["CONTRACT_STATUS"] = "พักชำระเฉพาะเงินต้น";
				}
				$arrayGroupLoan['APPROVE_AMT'] = number_format($rowWhocollu["APPROVE_AMT"],2);
				$arrayGroupLoan['TYPE_DESC'] = $rowWhocollu["TYPE_DESC"];
				$arrayGroupLoan["CONTRACT_NO"] = $rowWhocollu["LOANCONTRACT_NO"];
				$arrGrpAllLoan = array();
		
				$getCollDetail = $conmssqlcoop->prepare("SELECT Collateral_Stock  as LOANCOLLTYPE_CODE, 
														(SELECT member_id_col FROM cocollateral WHERE doc_no = :contract_no1 ) as REF_COLLNO
														FROM coloanmember   
														WHERE doc_no = :contract_no2 ");
				$getCollDetail->execute([
										':contract_no1' => $rowWhocollu["LOANCONTRACT_NO"],
										':contract_no2' => $rowWhocollu["LOANCONTRACT_NO"]
				]);	
				while($rowColl = $getCollDetail->fetch(PDO::FETCH_ASSOC)){
					$arrGroupAll = array();
					$arrGroupAllMember = array();
					$arrGroupAll['LOANCOLLTYPE_CODE'] = $rowColl["LOANCOLLTYPE_CODE"];
					if($rowColl["LOANCOLLTYPE_CODE"] == '0'){
						$whocolluMember = $conmssqlcoop->prepare("SELECT prefixname as PRENAME_DESC,firstname as MEMB_NAME,lastname as MEMB_SURNAME
																FROM cocooptation 									
																WHERE member_id = :member_id ");
						$whocolluMember->execute([':member_id' => $rowColl["REF_COLLNO"]]);
						$rowCollMember = $whocolluMember->fetch(PDO::FETCH_ASSOC);
						$arrayAvarTar = $func->getPathpic($rowColl["REF_COLLNO"]);
						$arrGroupAll['COLLTYPE_DESC'] = "คนค้ำประกัน";
						$arrGroupAllMember["AVATAR_PATH"] = isset($arrayAvarTar["AVATAR_PATH"]) ? $config["URL_SERVICE"].$arrayAvarTar["AVATAR_PATH"] : null;
						$arrGroupAllMember["AVATAR_PATH_WEBP"] = isset($arrayAvarTar["AVATAR_PATH_WEBP"]) ? $config["URL_SERVICE"].$arrayAvarTar["AVATAR_PATH_WEBP"] : null;
						$arrGroupAllMember["FULL_NAME"] = $rowCollMember["PRENAME_DESC"].$rowCollMember["MEMB_NAME"].' '.$rowCollMember["MEMB_SURNAME"];
						$arrGroupAllMember["MEMBER_NO"] = $rowColl["REF_COLLNO"];
					}
					else if($rowColl["LOANCOLLTYPE_CODE"] == '1'){
						$whocolluMember = $conmssqlcoop->prepare("SELECT prefixname as PRENAME_DESC,firstname as MEMB_NAME,lastname as MEMB_SURNAME
																FROM cocooptation 									
																WHERE member_id = :member_no");
						$whocolluMember->execute([':member_no' => $member_no]);
						$rowCollMember = $whocolluMember->fetch(PDO::FETCH_ASSOC);
						$arrGroupAll['COLLTYPE_DESC'] = "หุ้นค้ำประกัน";
						$arrGroupAllMember["SHARE_COLL_AMT"] = number_format($rowWhocollu["APPROVE_AMT"], 2);
						$arrGroupAllMember["FULL_NAME"] = $rowCollMember["PRENAME_DESC"].$rowCollMember["MEMB_NAME"].' '.$rowCollMember["MEMB_SURNAME"];
						$arrGroupAllMember["MEMBER_NO"] = $member_no;
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
