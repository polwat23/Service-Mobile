<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'GuaranteeInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrayResult = array();
		if(isset($dataComing["contract_no"])){
			$arrayGroupLoan = array();
			$contract_no = preg_replace('/\//','',$dataComing["contract_no"]);
			$getWhocollu = $conoracle->prepare("SELECT lnm.principal_balance as PRNBAL,lnm.loancontract_no,NVL(lnm.loanapprove_amt,0) as APPROVE_AMT,lt.LOANTYPE_DESC as TYPE_DESC
												FROM lncontmaster lnm LEFT JOIN LNLOANTYPE lt ON lnm.LOANTYPE_CODE = lt.LOANTYPE_CODE WHERE lnm.loancontract_no = :contract_no
												and lnm.contract_status > 0");
			$getWhocollu->execute([':contract_no' => $contract_no]);
			$rowWhocollu = $getWhocollu->fetch(PDO::FETCH_ASSOC);
			$arrayGroupLoan['APPROVE_AMT'] = number_format($rowWhocollu["APPROVE_AMT"],2);
			$arrayGroupLoan['TYPE_DESC'] = $rowWhocollu["TYPE_DESC"];
			if(mb_stripos($contract_no,'.') === FALSE){
				$loan_format = mb_substr($contract_no,0,2).'.'.mb_substr($contract_no,2,6).'/'.mb_substr($contract_no,8,2);
				if(mb_strlen($contract_no) == 10){
					$arrayGroupLoan["CONTRACT_NO"] = $loan_format;
				}else if(mb_strlen($contract_no) == 11){
					$arrayGroupLoan["CONTRACT_NO"] = $loan_format.'-'.mb_substr($contract_no,10);
				}
			}else{
				$arrayGroupLoan["CONTRACT_NO"] = $contract_no;
			}
			$arrGrpAllLoan = array();
			$getCollDetail = $conoracle->prepare("SELECT DISTINCT lnc.LOANCOLLTYPE_CODE,llc.LOANCOLLTYPE_DESC,lnc.REF_COLLNO,lnc.COLL_PERCENT,
																lnc.DESCRIPTION
																FROM lncontcoll lnc LEFT JOIN lnucfloancolltype llc ON lnc.LOANCOLLTYPE_CODE = llc.LOANCOLLTYPE_CODE
																WHERE lnc.coll_status = '1' and lnc.loancontract_no = :contract_no ORDER BY lnc.LOANCOLLTYPE_CODE ASC");
			$getCollDetail->execute([':contract_no' => $contract_no]);
			while($rowColl = $getCollDetail->fetch(PDO::FETCH_ASSOC)){
				$arrGroupAll = array();
				$arrGroupAll['LOANCOLLTYPE_CODE'] = $rowColl["LOANCOLLTYPE_CODE"];
				$arrGroupAll['COLLTYPE_DESC'] = $rowColl["LOANCOLLTYPE_DESC"];
				if($rowColl["LOANCOLLTYPE_CODE"] == '01'){
					$arrGroupAllMember = array();
					$whocolluMember = $conoracle->prepare("SELECT MUP.PRENAME_DESC,MMB.MEMB_NAME,MMB.MEMB_SURNAME
														FROM MBMEMBMASTER MMB LEFT JOIN MBUCFPRENAME MUP ON MMB.PRENAME_CODE = MUP.PRENAME_CODE
														WHERE MMB.member_no = :member_no");
					$whocolluMember->execute([':member_no' => $rowColl["REF_COLLNO"]]);
					$rowCollMember = $whocolluMember->fetch(PDO::FETCH_ASSOC);
					$arrayAvarTar = $func->getPathpic($rowColl["REF_COLLNO"]);
					$arrGroupAllMember["AVATAR_PATH"] = isset($arrayAvarTar["AVATAR_PATH"]) ? $config["URL_SERVICE"].$arrayAvarTar["AVATAR_PATH"] : null;
					$arrGroupAllMember["AVATAR_PATH_WEBP"] = isset($arrayAvarTar["AVATAR_PATH_WEBP"]) ? $config["URL_SERVICE"].$arrayAvarTar["AVATAR_PATH_WEBP"] : null;
					$arrGroupAllMember["FULL_NAME"] = $rowCollMember["PRENAME_DESC"].$rowCollMember["MEMB_NAME"].' '.$rowCollMember["MEMB_SURNAME"];
					$arrGroupAllMember["MEMBER_NO"] = $rowColl["REF_COLLNO"];
					$arrGroupAll['ASSET'] = $arrGroupAllMember;
				}
				if($rowColl["LOANCOLLTYPE_CODE"] == '02'){
					$arrGroupAllShare = array();
					$whocolluMember = $conoracle->prepare("SELECT MUP.PRENAME_DESC,MMB.MEMB_NAME,MMB.MEMB_SURNAME
														FROM MBMEMBMASTER MMB LEFT JOIN MBUCFPRENAME MUP ON MMB.PRENAME_CODE = MUP.PRENAME_CODE
														WHERE MMB.member_no = :member_no");
					$whocolluMember->execute([':member_no' => $rowColl["REF_COLLNO"]]);
					$rowCollMember = $whocolluMember->fetch(PDO::FETCH_ASSOC);
					$arrGroupAllShare["SHARE_COLL_AMT"] = number_format($rowWhocollu["PRNBAL"] * $rowColl["COLL_PERCENT"],2);
					$arrGroupAllShare["FULL_NAME"] = $rowCollMember["PRENAME_DESC"].$rowCollMember["MEMB_NAME"].' '.$rowCollMember["MEMB_SURNAME"];
					$arrGroupAllShare["MEMBER_NO"] = $rowColl["REF_COLLNO"];
					$arrGroupAll['ASSET'] = $arrGroupAllShare;
				}
				if($rowColl["LOANCOLLTYPE_CODE"] == '03'){
					$arrGroupAllDep = array();
					$whocolluDept = $conoracle->prepare("SELECT DEPTACCOUNT_NAME FROM dpdeptmaster
														WHERE deptaccount_no = :deptaccount_no");
					$whocolluDept->execute([':deptaccount_no' => $rowColl["REF_COLLNO"]]);
					$rowCollDept = $whocolluDept->fetch(PDO::FETCH_ASSOC);
					$arrGroupAllDep["DEPTACCOUNT_NO"] = $lib->formataccount_hidden($lib->formataccount($rowColl["REF_COLLNO"],$func->getConstant('dep_format')),$func->getConstant('hidden_dep'));
					$arrGroupAllDep["DEPTACCOUNT_NAME"] = $rowCollDept["DEPTACCOUNT_NAME"];
					$arrGroupAllDep["DEPT_AMT"] = number_format($rowWhocollu["PRNBAL"] * $rowColl["COLL_PERCENT"],2);
					$arrGroupAll['ASSET'] = $arrGroupAllDep;
				}
				if($rowColl["LOANCOLLTYPE_CODE"] == '04'){
					$arrGroupAllAsset = array();
					$whocolluAsset = $conoracle->prepare("SELECT lcm.COLLMAST_REFNO,lcd.LAND_LANDNO,lcd.POS_TUMBOL,MBD.DISTRICT_DESC,MBP.PROVINCE_DESC,lcm.COLLMAST_NO
																		FROM lncollmaster lcm LEFT JOIN lncolldetail lcd ON lcm.COLLMAST_NO = lcd.COLLMAST_NO 
																		LEFT JOIN MBUCFDISTRICT MBD ON lcd.POS_DISTRICT = MBD.DISTRICT_CODE
																		LEFT JOIN MBUCFPROVINCE MBP ON lcd.POS_PROVINCE = MBP.PROVINCE_CODE
																		WHERE lcm.collmast_no = :collmast_no");
					$whocolluAsset->execute([':collmast_no' => $rowColl["REF_COLLNO"]]);
					$rowCollAsset = $whocolluAsset->fetch(PDO::FETCH_ASSOC);
					$arrGroupAllAsset["COLL_DOCNO"] = $rowCollAsset["COLLMAST_NO"];
					if(isset($rowCollAsset["COLLMAST_REFNO"]) && $rowCollAsset["COLLMAST_REFNO"] != ""){
						$address =  isset($rowCollAsset["COLLMAST_REFNO"]) && $rowCollAsset["COLLMAST_REFNO"] != "" ? "โฉนดเลขที่ ".$rowCollAsset["COLLMAST_REFNO"] : "";
						$address .= isset($rowCollAsset["LAND_LANDNO"]) && $rowCollAsset["LAND_LANDNO"] != "" ? " บ้านเลขที่ ".$rowCollAsset["LAND_LANDNO"] : "";
						$address .= isset($rowCollAsset["POS_TUMBOL"]) && $rowCollAsset["POS_TUMBOL"] != "" ? " ต.".$rowCollAsset["POS_TUMBOL"] : "";
						$address .= isset($rowCollAsset["DISTRIC_DESC"]) && $rowCollAsset["DISTRIC_DESC"] != "" ? " อ.".$rowCollAsset["DISTRIC_DESC"] : "";
						$address .= isset($rowCollAsset["PROVINCE_DESC"]) && $rowCollAsset["PROVINCE_DESC"] != "" ? " จ.".$rowCollAsset["PROVINCE_DESC"] : "";
						$arrGroupAllAsset["DESCRIPTION"] = $address;
					}else{
						$arrGroupAllAsset['DESCRIPTION'] = $rowColl["DESCRIPTION"];
					}
					$arrGroupAll['ASSET'] = $arrGroupAllAsset;
				}
				if($rowColl["LOANCOLLTYPE_CODE"] == '05'){
					$arrGroupAllAsset['DESCRIPTION'] = $rowColl["DESCRIPTION"];
					$arrGroupAll['ASSET'] = $arrGroupAllAsset;
				}
				$arrGrpAllLoan[] = $arrGroupAll;
			}
			$arrayGroupLoan["GUARANTEE"] = $arrGrpAllLoan;
			$arrayResult['CONTRACT_COLL'] = $arrayGroupLoan;
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrGroupAllLoan = array();
			$getWhocollu = $conoracle->prepare("SELECT lnm.principal_balance as PRNBAL,lnm.loancontract_no,NVL(lnm.loanapprove_amt,0) as APPROVE_AMT,lt.LOANTYPE_DESC as TYPE_DESC
												FROM lncontmaster lnm LEFT JOIN LNLOANTYPE lt ON lnm.LOANTYPE_CODE = lt.LOANTYPE_CODE WHERE lnm.member_no = :member_no
												and lnm.contract_status > 0
                         						GROUP BY lnm.loancontract_no,NVL(lnm.loanapprove_amt,0),lt.LOANTYPE_DESC,lnm.principal_balance");
			$getWhocollu->execute([':member_no' => $member_no]);
			while($rowWhocollu = $getWhocollu->fetch(PDO::FETCH_ASSOC)){
				$arrayGroupLoan = array();
				$arrayGroupLoan['APPROVE_AMT'] = number_format($rowWhocollu["APPROVE_AMT"],2);
				$arrayGroupLoan['TYPE_DESC'] = $rowWhocollu["TYPE_DESC"];
				$contract_no = $rowWhocollu["LOANCONTRACT_NO"];
				if(mb_stripos($contract_no,'.') === FALSE){
					$loan_format = mb_substr($contract_no,0,2).'.'.mb_substr($contract_no,2,6).'/'.mb_substr($contract_no,8,2);
					if(mb_strlen($contract_no) == 10){
						$arrayGroupLoan["CONTRACT_NO"] = $loan_format;
					}else if(mb_strlen($contract_no) == 11){
						$arrayGroupLoan["CONTRACT_NO"] = $loan_format.'-'.mb_substr($contract_no,10);
					}
				}else{
					$arrayGroupLoan["CONTRACT_NO"] = $contract_no;
				}
				$arrGrpAllLoan = array();
				$getCollDetail = $conoracle->prepare("SELECT DISTINCT lnc.LOANCOLLTYPE_CODE,llc.LOANCOLLTYPE_DESC,lnc.REF_COLLNO,lnc.COLL_PERCENT,
																	lnc.DESCRIPTION
																	FROM lncontcoll lnc LEFT JOIN lnucfloancolltype llc ON lnc.LOANCOLLTYPE_CODE = llc.LOANCOLLTYPE_CODE
																	WHERE lnc.coll_status = '1' and lnc.loancontract_no = :contract_no ORDER BY lnc.LOANCOLLTYPE_CODE ASC");
				$getCollDetail->execute([':contract_no' => $contract_no]);
				while($rowColl = $getCollDetail->fetch(PDO::FETCH_ASSOC)){
					$arrGroupAll = array();
					$arrGroupAll['LOANCOLLTYPE_CODE'] = $rowColl["LOANCOLLTYPE_CODE"];
					$arrGroupAll['COLLTYPE_DESC'] = $rowColl["LOANCOLLTYPE_DESC"];
					if($rowColl["LOANCOLLTYPE_CODE"] == '01'){
						$arrGroupAllMember = array();
						$whocolluMember = $conoracle->prepare("SELECT MUP.PRENAME_DESC,MMB.MEMB_NAME,MMB.MEMB_SURNAME
															FROM MBMEMBMASTER MMB LEFT JOIN MBUCFPRENAME MUP ON MMB.PRENAME_CODE = MUP.PRENAME_CODE
															WHERE MMB.member_no = :member_no");
						$whocolluMember->execute([':member_no' => $rowColl["REF_COLLNO"]]);
						$rowCollMember = $whocolluMember->fetch(PDO::FETCH_ASSOC);
						$arrayAvarTar = $func->getPathpic($rowColl["REF_COLLNO"]);
						$arrGroupAllMember["AVATAR_PATH"] = isset($arrayAvarTar["AVATAR_PATH"]) ? $config["URL_SERVICE"].$arrayAvarTar["AVATAR_PATH"] : null;
						$arrGroupAllMember["AVATAR_PATH_WEBP"] = isset($arrayAvarTar["AVATAR_PATH_WEBP"]) ? $config["URL_SERVICE"].$arrayAvarTar["AVATAR_PATH_WEBP"] : null;
						$arrGroupAllMember["FULL_NAME"] = $rowCollMember["PRENAME_DESC"].$rowCollMember["MEMB_NAME"].' '.$rowCollMember["MEMB_SURNAME"];
						$arrGroupAllMember["MEMBER_NO"] = $rowColl["REF_COLLNO"];
						$arrGroupAll['ASSET'] = $arrGroupAllMember;
					}
					if($rowColl["LOANCOLLTYPE_CODE"] == '02'){
						$arrGroupAllShare = array();
						$whocolluMember = $conoracle->prepare("SELECT MUP.PRENAME_DESC,MMB.MEMB_NAME,MMB.MEMB_SURNAME
															FROM MBMEMBMASTER MMB LEFT JOIN MBUCFPRENAME MUP ON MMB.PRENAME_CODE = MUP.PRENAME_CODE
															WHERE MMB.member_no = :member_no");
						$whocolluMember->execute([':member_no' => $rowColl["REF_COLLNO"]]);
						$rowCollMember = $whocolluMember->fetch(PDO::FETCH_ASSOC);
						$arrGroupAllShare["SHARE_COLL_AMT"] = number_format($rowWhocollu["PRNBAL"] * $rowColl["COLL_PERCENT"],2);
						$arrGroupAllShare["FULL_NAME"] = $rowCollMember["PRENAME_DESC"].$rowCollMember["MEMB_NAME"].' '.$rowCollMember["MEMB_SURNAME"];
						$arrGroupAllShare["MEMBER_NO"] = $rowColl["REF_COLLNO"];
						$arrGroupAll['ASSET'] = $arrGroupAllShare;
					}
					if($rowColl["LOANCOLLTYPE_CODE"] == '03'){
						$arrGroupAllDep = array();
						$whocolluDept = $conoracle->prepare("SELECT DEPTACCOUNT_NAME FROM dpdeptmaster
															WHERE deptaccount_no = :deptaccount_no");
						$whocolluDept->execute([':deptaccount_no' => $rowColl["REF_COLLNO"]]);
						$rowCollDept = $whocolluDept->fetch(PDO::FETCH_ASSOC);
						$arrGroupAllDep["DEPTACCOUNT_NO"] = $lib->formataccount_hidden($lib->formataccount($rowColl["REF_COLLNO"],$func->getConstant('dep_format')),$func->getConstant('hidden_dep'));
						$arrGroupAllDep["DEPTACCOUNT_NAME"] = $rowCollDept["DEPTACCOUNT_NAME"];
						$arrGroupAllDep["DEPT_AMT"] = number_format($rowWhocollu["PRNBAL"] * $rowColl["COLL_PERCENT"],2);
						$arrGroupAll['ASSET'] = $arrGroupAllDep;
					}
					if($rowColl["LOANCOLLTYPE_CODE"] == '04'){
						$arrGroupAllAsset = array();
						$whocolluAsset = $conoracle->prepare("SELECT lcm.COLLMAST_REFNO,lcd.LAND_LANDNO,lcd.POS_TUMBOL,MBD.DISTRICT_DESC,MBP.PROVINCE_DESC,lcm.COLLMAST_NO
																			FROM lncollmaster lcm LEFT JOIN lncolldetail lcd ON lcm.COLLMAST_NO = lcd.COLLMAST_NO 
																			LEFT JOIN MBUCFDISTRICT MBD ON lcd.POS_DISTRICT = MBD.DISTRICT_CODE
																			LEFT JOIN MBUCFPROVINCE MBP ON lcd.POS_PROVINCE = MBP.PROVINCE_CODE
																			WHERE lcm.collmast_no = :collmast_no");
						$whocolluAsset->execute([':collmast_no' => $rowColl["REF_COLLNO"]]);
						$rowCollAsset = $whocolluAsset->fetch(PDO::FETCH_ASSOC);
						$arrGroupAllAsset["COLL_DOCNO"] = $rowCollAsset["COLLMAST_NO"];
						if(isset($rowCollAsset["COLLMAST_REFNO"]) && $rowCollAsset["COLLMAST_REFNO"] != ""){
							$address =  isset($rowCollAsset["COLLMAST_REFNO"]) && $rowCollAsset["COLLMAST_REFNO"] != "" ? "โฉนดเลขที่ ".$rowCollAsset["COLLMAST_REFNO"] : "";
							$address .= isset($rowCollAsset["LAND_LANDNO"]) && $rowCollAsset["LAND_LANDNO"] != "" ? " บ้านเลขที่ ".$rowCollAsset["LAND_LANDNO"] : "";
							$address .= isset($rowCollAsset["POS_TUMBOL"]) && $rowCollAsset["POS_TUMBOL"] != "" ? " ต.".$rowCollAsset["POS_TUMBOL"] : "";
							$address .= isset($rowCollAsset["DISTRIC_DESC"]) && $rowCollAsset["DISTRIC_DESC"] != "" ? " อ.".$rowCollAsset["DISTRIC_DESC"] : "";
							$address .= isset($rowCollAsset["PROVINCE_DESC"]) && $rowCollAsset["PROVINCE_DESC"] != "" ? " จ.".$rowCollAsset["PROVINCE_DESC"] : "";
							$arrGroupAllAsset["DESCRIPTION"] = $address;
						}else{
							$arrGroupAllAsset['DESCRIPTION'] = $rowColl["DESCRIPTION"];
						}
						$arrGroupAll['ASSET'] = $arrGroupAllAsset;
					}
					if($rowColl["LOANCOLLTYPE_CODE"] == '05'){
						$arrGroupAllAsset['DESCRIPTION'] = $rowColl["DESCRIPTION"];
						$arrGroupAll['ASSET'] = $arrGroupAllAsset;
					}
					$arrGrpAllLoan[] = $arrGroupAll;
				}
				$arrayGroupLoan["GUARANTEE"] = $arrGrpAllLoan;
				$arrGroupAllLoan[] = $arrayGroupLoan;
			}
			$arrayResult['CONTRACT_COLL'] = $arrGroupAllLoan;
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
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
	echo json_encode($arrayResult);
	exit();
}
?>