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
												and lnm.contract_status > 0 and lnm.contract_status <> 8");
			$getWhocollu->execute([':contract_no' => $contract_no]);
			$rowWhocollu = $getWhocollu->fetch(PDO::FETCH_ASSOC);
			$arrayGroupLoan['APPROVE_AMT'] = number_format($rowWhocollu["APPROVE_AMT"],2);
			$arrayGroupLoan['TYPE_DESC'] = $rowWhocollu["TYPE_DESC"];
			$arrayGroupLoan["CONTRACT_NO"] = $contract_no;
			$arrGrpAllLoan = array();
			$getCollDetail = $conoracle->prepare("SELECT DISTINCT lnc.LOANCOLLTYPE_CODE,llc.LOANCOLLTYPE_DESC,lnc.REF_COLLNO,lnc.COLL_PERCENT,
																lnc.DESCRIPTION
																FROM lncontcoll lnc LEFT JOIN lnucfloancolltype llc ON lnc.LOANCOLLTYPE_CODE = llc.LOANCOLLTYPE_CODE
																WHERE (lnc.coll_status = '1' OR lnc.coll_status IS NULL) and lnc.loancontract_no = :contract_no ORDER BY lnc.LOANCOLLTYPE_CODE ASC");
			$getCollDetail->execute([':contract_no' => $contract_no]);
			while($rowColl = $getCollDetail->fetch(PDO::FETCH_ASSOC)){
				$arrGroupAll = array();
				$arrGroupAllMember = array();
				$arrGroupAll['LOANCOLLTYPE_CODE'] = $rowColl["LOANCOLLTYPE_CODE"];
				$arrGroupAll['COLLTYPE_DESC'] = $rowColl["LOANCOLLTYPE_DESC"];
				if($rowColl["LOANCOLLTYPE_CODE"] == '01'){
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
				}else if($rowColl["LOANCOLLTYPE_CODE"] == '02'){
					$whocolluMember = $conoracle->prepare("SELECT MUP.PRENAME_DESC,MMB.MEMB_NAME,MMB.MEMB_SURNAME
														FROM MBMEMBMASTER MMB LEFT JOIN MBUCFPRENAME MUP ON MMB.PRENAME_CODE = MUP.PRENAME_CODE
														WHERE MMB.member_no = :member_no");
					$whocolluMember->execute([':member_no' => $rowColl["REF_COLLNO"]]);
					$rowCollMember = $whocolluMember->fetch(PDO::FETCH_ASSOC);
					$arrGroupAllMember["SHARE_COLL_AMT"] = number_format($rowWhocollu["PRNBAL"] * $rowColl["COLL_PERCENT"],2);
					$arrGroupAllMember["FULL_NAME"] = $rowCollMember["PRENAME_DESC"].$rowCollMember["MEMB_NAME"].' '.$rowCollMember["MEMB_SURNAME"];
					$arrGroupAllMember["MEMBER_NO"] = $rowColl["REF_COLLNO"];
				}else if($rowColl["LOANCOLLTYPE_CODE"] == '03'){
					$whocolluDept = $conoracle->prepare("SELECT DEPTACCOUNT_NAME FROM dpdeptmaster
														WHERE deptaccount_no = :deptaccount_no");
					$whocolluDept->execute([':deptaccount_no' => $rowColl["REF_COLLNO"]]);
					$rowCollDept = $whocolluDept->fetch(PDO::FETCH_ASSOC);
					$arrGroupAllMember["DEPTACCOUNT_NO"] = $lib->formataccount_hidden($lib->formataccount($rowColl["REF_COLLNO"],$func->getConstant('dep_format')),$func->getConstant('hidden_dep'));
					$arrGroupAllMember["DEPTACCOUNT_NAME"] = $rowCollDept["DEPTACCOUNT_NAME"];
					$arrGroupAllMember["DEPT_AMT"] = number_format($rowWhocollu["PRNBAL"] * $rowColl["COLL_PERCENT"],2);
				}else if($rowColl["LOANCOLLTYPE_CODE"] == '04'){
					$whocolluAsset = $conoracle->prepare("SELECT lcm.COLLMAST_REFNO,lcd.LAND_LANDNO,lcd.POS_TUMBOL,MBD.DISTRICT_DESC,MBP.PROVINCE_DESC,lcm.COLLMAST_NO
																		FROM lncollmaster lcm LEFT JOIN lncolldetail lcd ON lcm.COLLMAST_NO = lcd.COLLMAST_NO 
																		LEFT JOIN MBUCFDISTRICT MBD ON lcd.POS_DISTRICT = MBD.DISTRICT_CODE
																		LEFT JOIN MBUCFPROVINCE MBP ON lcd.POS_PROVINCE = MBP.PROVINCE_CODE
																		WHERE lcm.collmast_no = :collmast_no");
					$whocolluAsset->execute([':collmast_no' => $rowColl["REF_COLLNO"]]);
					$rowCollAsset = $whocolluAsset->fetch(PDO::FETCH_ASSOC);
					$arrGroupAllMember["COLL_DOCNO"] = $rowCollAsset["COLLMAST_NO"];
					if(isset($rowCollAsset["COLLMAST_REFNO"]) && $rowCollAsset["COLLMAST_REFNO"] != ""){
						$address =  isset($rowCollAsset["COLLMAST_REFNO"]) && $rowCollAsset["COLLMAST_REFNO"] != "" ? "â©¹´àÅ¢·Õè ".$rowCollAsset["COLLMAST_REFNO"] : "";
						$address .= isset($rowCollAsset["LAND_LANDNO"]) && $rowCollAsset["LAND_LANDNO"] != "" ? " ºéÒ¹àÅ¢·Õè ".$rowCollAsset["LAND_LANDNO"] : "";
						$address .= isset($rowCollAsset["POS_TUMBOL"]) && $rowCollAsset["POS_TUMBOL"] != "" ? " µ.".$rowCollAsset["POS_TUMBOL"] : "";
						$address .= isset($rowCollAsset["DISTRIC_DESC"]) && $rowCollAsset["DISTRIC_DESC"] != "" ? " Í.".$rowCollAsset["DISTRIC_DESC"] : "";
						$address .= isset($rowCollAsset["PROVINCE_DESC"]) && $rowCollAsset["PROVINCE_DESC"] != "" ? " ¨.".$rowCollAsset["PROVINCE_DESC"] : "";
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
			echo json_encode($arrayResult);
		}else{
			$arrGroupAllLoan = array();
			$getWhocollu = $conoracle->prepare("SELECT lnm.principal_balance as PRNBAL,lnm.loancontract_no,NVL(lnm.loanapprove_amt,0) as APPROVE_AMT,lt.LOANTYPE_DESC as TYPE_DESC
												FROM lncontmaster lnm LEFT JOIN LNLOANTYPE lt ON lnm.LOANTYPE_CODE = lt.LOANTYPE_CODE WHERE lnm.member_no = :member_no
												and lnm.contract_status > 0 and lnm.contract_status <> 8
                         						GROUP BY lnm.loancontract_no,NVL(lnm.loanapprove_amt,0),lt.LOANTYPE_DESC,lnm.principal_balance");
			$getWhocollu->execute([':member_no' => $member_no]);
			while($rowWhocollu = $getWhocollu->fetch(PDO::FETCH_ASSOC)){
				$arrayGroupLoan = array();
				$arrayGroupLoan['APPROVE_AMT'] = number_format($rowWhocollu["APPROVE_AMT"],2);
				$arrayGroupLoan['TYPE_DESC'] = $rowWhocollu["TYPE_DESC"];
				$arrayGroupLoan["CONTRACT_NO"] = $rowWhocollu["LOANCONTRACT_NO"];
				$arrGrpAllLoan = array();
				$getCollDetail = $conoracle->prepare("SELECT DISTINCT lnc.LOANCOLLTYPE_CODE,llc.LOANCOLLTYPE_DESC,lnc.REF_COLLNO,lnc.COLLACTIVE_PERCENT as COLL_PERCENT,
																	lnc.DESCRIPTION
																	FROM lncontcoll lnc LEFT JOIN lnucfloancolltype llc ON lnc.LOANCOLLTYPE_CODE = llc.LOANCOLLTYPE_CODE
																	WHERE (lnc.coll_status = '1' OR lnc.coll_status IS NULL) and lnc.loancontract_no = :contract_no ORDER BY lnc.LOANCOLLTYPE_CODE ASC");
				$getCollDetail->execute([':contract_no' => $rowWhocollu["LOANCONTRACT_NO"]]);
				while($rowColl = $getCollDetail->fetch(PDO::FETCH_ASSOC)){
					$arrGroupAll = array();
					$arrGroupAllMember = array();
					$arrGroupAll['LOANCOLLTYPE_CODE'] = $rowColl["LOANCOLLTYPE_CODE"];
					$arrGroupAll['COLLTYPE_DESC'] = $rowColl["LOANCOLLTYPE_DESC"];
					if($rowColl["LOANCOLLTYPE_CODE"] == '01'){
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
					}else if($rowColl["LOANCOLLTYPE_CODE"] == '02'){
						$whocolluMember = $conoracle->prepare("SELECT MUP.PRENAME_DESC,MMB.MEMB_NAME,MMB.MEMB_SURNAME
															FROM MBMEMBMASTER MMB LEFT JOIN MBUCFPRENAME MUP ON MMB.PRENAME_CODE = MUP.PRENAME_CODE
															WHERE MMB.member_no = :member_no");
						$whocolluMember->execute([':member_no' => $rowColl["REF_COLLNO"]]);
						$rowCollMember = $whocolluMember->fetch(PDO::FETCH_ASSOC);
						$arrGroupAllMember["SHARE_COLL_AMT"] = number_format($rowWhocollu["PRNBAL"] * $rowColl["COLL_PERCENT"],2);
						$arrGroupAllMember["FULL_NAME"] = $rowCollMember["PRENAME_DESC"].$rowCollMember["MEMB_NAME"].' '.$rowCollMember["MEMB_SURNAME"];
						$arrGroupAllMember["MEMBER_NO"] = $rowColl["REF_COLLNO"];
					}else if($rowColl["LOANCOLLTYPE_CODE"] == '03'){
						$whocolluDept = $conoracle->prepare("SELECT DEPTACCOUNT_NAME FROM dpdeptmaster
															WHERE deptaccount_no = :deptaccount_no");
						$whocolluDept->execute([':deptaccount_no' => $rowColl["REF_COLLNO"]]);
						$rowCollDept = $whocolluDept->fetch(PDO::FETCH_ASSOC);
						$arrGroupAllMember["DEPTACCOUNT_NO"] = $lib->formataccount_hidden($lib->formataccount($rowColl["REF_COLLNO"],$func->getConstant('dep_format')),$func->getConstant('hidden_dep'));
						$arrGroupAllMember["DEPTACCOUNT_NAME"] = $rowCollDept["DEPTACCOUNT_NAME"];
						$arrGroupAllMember["DEPT_AMT"] = number_format($rowWhocollu["PRNBAL"] * $rowColl["COLL_PERCENT"],2);
					}else if($rowColl["LOANCOLLTYPE_CODE"] == '04'){
						$whocolluAsset = $conoracle->prepare("SELECT lcm.LAND_LANDNO,lcm.POS_TAMBOL,MBD.DISTRICT_DESC,MBP.PROVINCE_DESC,lcm.COLLMAST_NO,lcm.COLLMAST_DESC
																			FROM lncollmaster lcm
																			LEFT JOIN MBUCFDISTRICT MBD ON lcm.POS_AMPHUR = MBD.DISTRICT_CODE
																			LEFT JOIN MBUCFPROVINCE MBP ON lcm.POS_PROVINCE = MBP.PROVINCE_CODE
																			WHERE TRIM(lcm.collmast_no) = :collmast_no");
						$whocolluAsset->execute([':collmast_no' => TRIM($rowColl["REF_COLLNO"])]);
						$rowCollAsset = $whocolluAsset->fetch(PDO::FETCH_ASSOC);
						$arrGroupAllMember["COLL_DOCNO"] = TRIM($rowColl["REF_COLLNO"]);
						if(isset($rowCollAsset["COLLMAST_DESC"]) && $rowCollAsset["COLLMAST_DESC"] != ""){
							$arrGroupAllMember['DESCRIPTION'] = $rowCollAsset["COLLMAST_DESC"];
						}else{
							if(isset($rowColl["DESCRIPTION"]) && $rowColl["DESCRIPTION"] != ""){
								$arrGroupAllMember['DESCRIPTION'] = $rowColl["DESCRIPTION"];
							}else{
								$address =  isset($rowColl["REF_COLLNO"]) && $rowColl["REF_COLLNO"] != "" ? "â©¹´àÅ¢·Õè ".TRIM($rowColl["REF_COLLNO"]) : "";
								$address .= isset($rowCollAsset["LAND_LANDNO"]) && $rowCollAsset["LAND_LANDNO"] != "" ? " ºéÒ¹àÅ¢·Õè ".$rowCollAsset["LAND_LANDNO"] : "";
								$address .= isset($rowCollAsset["POS_TUMBOL"]) && $rowCollAsset["POS_TUMBOL"] != "" ? " µ.".$rowCollAsset["POS_TUMBOL"] : "";
								$address .= isset($rowCollAsset["DISTRIC_DESC"]) && $rowCollAsset["DISTRIC_DESC"] != "" ? " Í.".$rowCollAsset["DISTRIC_DESC"] : "";
								$address .= isset($rowCollAsset["PROVINCE_DESC"]) && $rowCollAsset["PROVINCE_DESC"] != "" ? " ¨.".$rowCollAsset["PROVINCE_DESC"] : "";
								$arrGroupAllMember["DESCRIPTION"] = $address;
							}
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
		":error_desc" => "Êè§ Argument ÁÒäÁè¤Ãº "."\n".json_encode($dataComing),
		":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
	];
	$log->writeLog('errorusage',$logStruc);
	$message_error = "ä¿Åì ".$filename." Êè§ Argument ÁÒäÁè¤ÃºÁÒá¤è "."\n".json_encode($dataComing);
	$lib->sendLineNotify($message_error);
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>
