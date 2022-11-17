<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'GuaranteeInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		
		if(isset($dataComing["contract_no"])){
			$arrayGroupLoan = array();
			$contract_no = preg_replace('/\//','',$dataComing["contract_no"]);
			$getWhocollu = $conoracle->prepare("SELECT lnm.LCONT_AMOUNT_SAL as PRNBAL,lnm.LCONT_ID as LOANCONTRACT_NO,
												lnm.LCONT_MAX_INSTALL - lnm.LCONT_NUM_INST as LAST_PERIOD,
												lnm.LCONT_MAX_INSTALL as PERIOD,
												lt.L_TYPE_NAME as TYPE_DESC,
												lnm.LCONT_APPROVE_SAL as APPROVE_AMT
												FROM LOAN_M_CONTACT lnm LEFT JOIN LOAN_M_TYPE_NAME lt 
												ON lnm.L_TYPE_CODE = lt.L_TYPE_CODE WHERE lnm.LCONT_ID = :contract_no
												and lnm.LCONT_STATUS_CONT IN('H','A','A1')
                         						GROUP BY lnm.LCONT_ID,lt.L_TYPE_NAME,lnm.LCONT_AMOUNT_SAL,
												lnm.LCONT_MAX_INSTALL,lnm.LCONT_MAX_INSTALL - lnm.LCONT_NUM_INST,lnm.LCONT_APPROVE_SAL");
			$getWhocollu->execute([':contract_no' => $contract_no]);
			$rowWhocollu = $getWhocollu->fetch(PDO::FETCH_ASSOC);
			$arrayGroupLoan["LOAN_BALANCE"] = number_format($rowWhocollu["PRNBAL"],2);
			$arrayGroupLoan["LAST_PERIOD"] = $rowWhocollu["LAST_PERIOD"].' / '.$rowWhocollu["PERIOD"];
			$arrayGroupLoan['TYPE_DESC'] = $rowWhocollu["TYPE_DESC"];
			$arrayGroupLoan['APPROVE_AMT'] = number_format($rowWhocollu["APPROVE_AMT"],2);
			$arrayGroupLoan["CONTRACT_NO"] = $contract_no;
			$arrGrpAllLoan = array();
			$getTypeGuarantee = $conoracle->prepare("SELECT 
													(CASE WHEN LTD.LCONT_ID IS NULL 
													THEN '0'
													ELSE '1' END)
													as IS_DEED,
													(CASE WHEN LTG.LCONT_ID IS NULL 
													THEN '0'
													ELSE '1' END)
													as IS_GUR,
													(CASE WHEN LTS.LCONT_ID IS NULL 
													THEN '0'
													ELSE '1' END)
													as IS_SAVING
													FROM LOAN_M_CONTACT LNM LEFT JOIN LOAN_T_DEED LTD ON LNM.LCONT_ID = LTD.LCONT_ID
													LEFT JOIN LOAN_T_GUAR LTG ON LNM.LCONT_ID = LTG.LCONT_ID
													LEFT JOIN LOAN_T_SAVING LTS ON LNM.LCONT_ID = LTS.LCONT_ID
													WHERE LNM.LCONT_ID = :contract_no");
			$getTypeGuarantee->execute([':contract_no' => $rowWhocollu["LOANCONTRACT_NO"]]);
			$rowTypeGuarantee = $getTypeGuarantee->fetch(PDO::FETCH_ASSOC);
			if($rowTypeGuarantee["IS_DEED"] == '1'){
				$arrGroupAll = [];
				$arrGroupAll['LOANCOLLTYPE_CODE'] = '04';
				$arrGroupAll['COLLTYPE_DESC'] = "สินทรัพย์ค้ำประกัน";
				$getDeed = $conoracle->prepare("SELECT LD_TDEED_NO as COLLMAST_REFNO,LD_PLAND_NO as LAND_LANDNO,
												LD_PTUMBOL as POS_TUMBOL,LD_PAMPHUR as DISTRIC_DESC,LD_PROVINCE as PROVINCE_DESC
												FROM LOAN_T_DEED WHERE LCONT_ID = :contract_no");
				$getDeed->execute([':contract_no' => $rowWhocollu["LOANCONTRACT_NO"]]);
				while($rowDeed = $getDeed->fetch(PDO::FETCH_ASSOC)){
					$arrGroupAllMember = [];
					$address =  isset($rowDeed["COLLMAST_REFNO"]) && $rowDeed["COLLMAST_REFNO"] != "" ? "โฉนดเลขที่ ".$rowDeed["COLLMAST_REFNO"] : "";
					$address .= isset($rowDeed["LAND_LANDNO"]) && $rowDeed["LAND_LANDNO"] != "" ? " บ้านเลขที่ ".$rowDeed["LAND_LANDNO"] : "";
					$address .= isset($rowDeed["POS_TUMBOL"]) && $rowDeed["POS_TUMBOL"] != "" ? " ต.".$rowDeed["POS_TUMBOL"] : "";
					$address .= isset($rowDeed["DISTRIC_DESC"]) && $rowDeed["DISTRIC_DESC"] != "" ? " อ.".$rowDeed["DISTRIC_DESC"] : "";
					$address .= isset($rowDeed["PROVINCE_DESC"]) && $rowDeed["PROVINCE_DESC"] != "" ? " จ.".$rowDeed["PROVINCE_DESC"] : "";
					$arrGroupAllMember["DESCRIPTION"] = $address;
					$arrGroupAll['ASSET'][] = $arrGroupAllMember;
				}
				$arrGrpAllLoan[] = $arrGroupAll;
			}
			if($rowTypeGuarantee["IS_GUR"] == '1'){
				$arrGroupAll = [];
				$arrGroupAll['LOANCOLLTYPE_CODE'] = '01';
				$arrGroupAll['COLLTYPE_DESC'] = "คนค้ำประกัน";
				$getGuarantee = $conoracle->prepare("SELECT BR_NO_OTHER,MEM_ID,LG_FULLNAME
													FROM LOAN_T_GUAR WHERE LCONT_ID = :contract_no and MEM_CHK = 'N'");
				$getGuarantee->execute([':contract_no' => $rowWhocollu["LOANCONTRACT_NO"]]);
				while($rowGuarantee = $getGuarantee->fetch(PDO::FETCH_ASSOC)){
					$arrGroupAllMember = [];
					$getUcollwho = $conoracle->prepare("SELECT
														MEMB.FNAME as MEMB_NAME,MEMB.LNAME as MEMB_SURNAME,PRE.PTITLE_NAME as PRENAME_DESC,MEMB.ACCOUNT_ID
														FROM
														MEM_H_MEMBER MEMB
														LEFT JOIN MEM_M_PTITLE PRE ON MEMB.ptitle_id = PRE.ptitle_id
														WHERE
														MEMB.ID_CARD = :id_card and MEMB.BR_NO = :br_no");
					$getUcollwho->execute([
						':id_card' => $rowGuarantee["MEM_ID"],
						':br_no' => $rowGuarantee["BR_NO_OTHER"]
					]);
					$rowCollMember = $getUcollwho->fetch(PDO::FETCH_ASSOC);
					$arrayAvarTar = $func->getPathpic($rowCollMember["ACCOUNT_ID"]);
					$arrGroupAllMember["AVATAR_PATH"] = isset($arrayAvarTar["AVATAR_PATH"]) ? $config["URL_SERVICE"].$arrayAvarTar["AVATAR_PATH"] : null;
					$arrGroupAllMember["AVATAR_PATH_WEBP"] = isset($arrayAvarTar["AVATAR_PATH_WEBP"]) ? $config["URL_SERVICE"].$arrayAvarTar["AVATAR_PATH_WEBP"] : null;
					$arrGroupAllMember["FULL_NAME"] = $rowCollMember["PRENAME_DESC"].$rowCollMember["MEMB_NAME"].' '.$rowCollMember["MEMB_SURNAME"];
					$arrGroupAllMember["MEMBER_NO"] = $rowCollMember["ACCOUNT_ID"];
					$arrGroupAll['ASSET'][] = $arrGroupAllMember;
				}
				$arrGrpAllLoan[] = $arrGroupAll;
			}
			if($rowTypeGuarantee["IS_SAVING"] == '1'){
				$arrGroupAll = [];
				$arrGroupAll['LOANCOLLTYPE_CODE'] = '03';
				$arrGroupAll['COLLTYPE_DESC'] = "เงินฝากค้ำประกัน";
				$getGuarantee = $conoracle->prepare("SELECT LS_ACCNO as REF_COLLNO,LS_SAL_HOLD
													FROM LOAN_T_SAVING WHERE LCONT_ID = :contract_no and (FLAG = 'N' OR FLAG IS NULL)");
				$getGuarantee->execute([':contract_no' => $rowWhocollu["LOANCONTRACT_NO"]]);
				while($rowColl = $getGuarantee->fetch(PDO::FETCH_ASSOC)){
					$arrGroupAllMember = [];
					$getDataAcc = $conoracle->prepare("SELECT ACCOUNT_NAME FROM BK_H_SAVINGACCOUNT WHERE account_no = :account_no");
					$getDataAcc->execute([':account_no' => $rowColl["REF_COLLNO"]]);
					$rowAcc = $getDataAcc->fetch(PDO::FETCH_ASSOC);
					$arrGroupAllMember["DEPTACCOUNT_NO"] = $lib->formataccount_hidden($lib->formataccount($rowColl["REF_COLLNO"],$func->getConstant('dep_format')),$func->getConstant('hidden_dep'));
					$arrGroupAllMember["DEPTACCOUNT_NAME"] = $rowAcc["ACCOUNT_NAME"];
					$arrGroupAllMember["DEPT_AMT"] = number_format($rowColl["LS_SAL_HOLD"],2);
					$arrGroupAll['ASSET'][] = $arrGroupAllMember;
				}
				$arrGrpAllLoan[] = $arrGroupAll;
			}
			//gold
			$arrGroupAll = [];
			$arrGroupAll['LOANCOLLTYPE_CODE'] = '06';
			$arrGroupAll['COLLTYPE_DESC'] = "ทองค้ำประกัน";
			$getGuarantee = $conoracle->prepare("select td.lcont_id,td.br_no,td.lreg_recno,td.code,lmg.g_name,td.ltg_cost_appraisal  
												from loan_t_gold td INNER JOIN loan_m_contact c on c.lcont_id = td.lcont_id and c.br_no = td.br_no 
												INNER JOIN mem_h_member m on m.id_card = c.id_card INNER JOIN mem_m_ptitle p ON p.ptitle_id = m.ptitle_id 
												INNER JOIN loan_m_type_gold lmg ON lmg.g_code = td.ltg_type 
												WHERE c.lcont_amount_sal > 0 and td.lcont_id = :contract_no and (m.tried_flg ='0' or m.tried_flg ='N')");
			$getGuarantee->execute([':contract_no' => $rowWhocollu["LOANCONTRACT_NO"]]);
			while($rowColl = $getGuarantee->fetch(PDO::FETCH_ASSOC)){
				$arrGroupAllMember = [];
				$arrGroupAllMember["CATEGORY_NAME"] = $rowColl["G_NAME"];
				$arrGroupAllMember["COST_APPRAISAL"] = number_format($rowColl["LTG_COST_APPRAISAL"],2);
				$arrGroupAll['ASSET'][] = $arrGroupAllMember;
			}
			if(count($arrGroupAll['ASSET']) > 0){
				$arrGrpAllLoan[] = $arrGroupAll;
			}
			$arrayGroupLoan["GUARANTEE"] = $arrGrpAllLoan;
			$arrayResult['CONTRACT_COLL'][] = $arrayGroupLoan;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$arrGroupAllLoan = array();
			$getWhocollu = $conoracle->prepare("SELECT lnm.LCONT_AMOUNT_SAL as PRNBAL,lnm.LCONT_ID as LOANCONTRACT_NO,
												lnm.LCONT_MAX_INSTALL - lnm.LCONT_NUM_INST as LAST_PERIOD,
												lnm.LCONT_MAX_INSTALL as PERIOD,
												lt.L_TYPE_NAME as TYPE_DESC
												FROM LOAN_M_CONTACT lnm LEFT JOIN LOAN_M_TYPE_NAME lt 
												ON lnm.L_TYPE_CODE = lt.L_TYPE_CODE WHERE lnm.account_id = :member_no
												and lnm.LCONT_STATUS_CONT IN('H','A','A1')
                         						GROUP BY lnm.LCONT_ID,lt.L_TYPE_NAME,lnm.LCONT_AMOUNT_SAL,
												lnm.LCONT_MAX_INSTALL,lnm.LCONT_MAX_INSTALL - lnm.LCONT_NUM_INST");
			$getWhocollu->execute([':member_no' => $member_no]);
			while($rowWhocollu = $getWhocollu->fetch(PDO::FETCH_ASSOC)){
				$arrayGroupLoan = array();
				$arrayGroupLoan["LOAN_BALANCE"] = number_format($rowWhocollu["PRNBAL"],2);
				$arrayGroupLoan["LAST_PERIOD"] = $rowWhocollu["LAST_PERIOD"].' / '.$rowWhocollu["PERIOD"];
				$arrayGroupLoan['TYPE_DESC'] = $rowWhocollu["TYPE_DESC"];
				$arrayGroupLoan["CONTRACT_NO"] = $rowWhocollu["LOANCONTRACT_NO"];
				$arrGrpAllLoan = array();
				$getTypeGuarantee = $conoracle->prepare("SELECT 
														(CASE WHEN LTD.LCONT_ID IS NULL 
														THEN '0'
														ELSE '1' END)
														as IS_DEED,
														(CASE WHEN LTG.LCONT_ID IS NULL 
														THEN '0'
														ELSE '1' END)
														as IS_GUR,
														(CASE WHEN LTS.LCONT_ID IS NULL 
														THEN '0'
														ELSE '1' END)
														as IS_SAVING
														FROM LOAN_M_CONTACT LNM LEFT JOIN LOAN_T_DEED LTD ON LNM.LCONT_ID = LTD.LCONT_ID
														LEFT JOIN LOAN_T_GUAR LTG ON LNM.LCONT_ID = LTG.LCONT_ID
														LEFT JOIN LOAN_T_SAVING LTS ON LNM.LCONT_ID = LTS.LCONT_ID
														WHERE LNM.LCONT_ID = :contract_no");
				$getTypeGuarantee->execute([':contract_no' => $rowWhocollu["LOANCONTRACT_NO"]]);
				$rowTypeGuarantee = $getTypeGuarantee->fetch(PDO::FETCH_ASSOC);
				$arrGroupAll = array();
				if($rowTypeGuarantee["IS_DEED"] == '1'){
					$arrGroupAll = [];
					$arrGroupAll['LOANCOLLTYPE_CODE'] = '04';
					$arrGroupAll['COLLTYPE_DESC'] = "สินทรัพย์ค้ำประกัน";
					$getDeed = $conoracle->prepare("SELECT LD_TDEED_NO as COLLMAST_REFNO,LD_PLAND_NO as LAND_LANDNO,
													LD_PTUMBOL as POS_TUMBOL,LD_PAMPHUR as DISTRIC_DESC,LD_PROVINCE as PROVINCE_DESC
													FROM LOAN_T_DEED WHERE LCONT_ID = :contract_no");
					$getDeed->execute([':contract_no' => $rowWhocollu["LOANCONTRACT_NO"]]);
					while($rowDeed = $getDeed->fetch(PDO::FETCH_ASSOC)){
						$arrGroupAllMember = [];
						$address =  isset($rowDeed["COLLMAST_REFNO"]) && $rowDeed["COLLMAST_REFNO"] != "" ? "โฉนดเลขที่ ".$rowDeed["COLLMAST_REFNO"] : "";
						$address .= isset($rowDeed["LAND_LANDNO"]) && $rowDeed["LAND_LANDNO"] != "" ? " บ้านเลขที่ ".$rowDeed["LAND_LANDNO"] : "";
						$address .= isset($rowDeed["POS_TUMBOL"]) && $rowDeed["POS_TUMBOL"] != "" ? " ต.".$rowDeed["POS_TUMBOL"] : "";
						$address .= isset($rowDeed["DISTRIC_DESC"]) && $rowDeed["DISTRIC_DESC"] != "" ? " อ.".$rowDeed["DISTRIC_DESC"] : "";
						$address .= isset($rowDeed["PROVINCE_DESC"]) && $rowDeed["PROVINCE_DESC"] != "" ? " จ.".$rowDeed["PROVINCE_DESC"] : "";
						$arrGroupAllMember["DESCRIPTION"] = $address;
						$arrGroupAll['ASSET'][] = $arrGroupAllMember;
					}
					$arrGrpAllLoan[] = $arrGroupAll;
				}
				if($rowTypeGuarantee["IS_GUR"] == '1'){
					$arrGroupAll = [];
					$arrGroupAll['LOANCOLLTYPE_CODE'] = '01';
					$arrGroupAll['COLLTYPE_DESC'] = "คนค้ำประกัน";
					$getGuarantee = $conoracle->prepare("SELECT BR_NO_OTHER,MEM_ID,LG_FULLNAME
														FROM LOAN_T_GUAR WHERE LCONT_ID = :contract_no and MEM_CHK = 'N'");
					$getGuarantee->execute([':contract_no' => $rowWhocollu["LOANCONTRACT_NO"]]);
					while($rowGuarantee = $getGuarantee->fetch(PDO::FETCH_ASSOC)){
						$arrGroupAllMember = [];
						$getUcollwho = $conoracle->prepare("SELECT
															MEMB.FNAME as MEMB_NAME,MEMB.LNAME as MEMB_SURNAME,PRE.PTITLE_NAME as PRENAME_DESC,MEMB.ACCOUNT_ID
															FROM
															MEM_H_MEMBER MEMB
															LEFT JOIN MEM_M_PTITLE PRE ON MEMB.ptitle_id = PRE.ptitle_id
															WHERE
															MEMB.ID_CARD = :id_card and MEMB.BR_NO = :br_no");
						$getUcollwho->execute([
							':id_card' => $rowGuarantee["MEM_ID"],
							':br_no' => $rowGuarantee["BR_NO_OTHER"]
						]);
						$rowCollMember = $getUcollwho->fetch(PDO::FETCH_ASSOC);
						$arrayAvarTar = $func->getPathpic($rowCollMember["ACCOUNT_ID"]);
						$arrGroupAllMember["AVATAR_PATH"] = isset($arrayAvarTar["AVATAR_PATH"]) ? $config["URL_SERVICE"].$arrayAvarTar["AVATAR_PATH"] : null;
						$arrGroupAllMember["AVATAR_PATH_WEBP"] = isset($arrayAvarTar["AVATAR_PATH_WEBP"]) ? $config["URL_SERVICE"].$arrayAvarTar["AVATAR_PATH_WEBP"] : null;
						$arrGroupAllMember["FULL_NAME"] = $rowCollMember["PRENAME_DESC"].$rowCollMember["MEMB_NAME"].' '.$rowCollMember["MEMB_SURNAME"];
						$arrGroupAllMember["MEMBER_NO"] = $rowCollMember["ACCOUNT_ID"];
						$arrGroupAll['ASSET'][] = $arrGroupAllMember;
					}
					$arrGrpAllLoan[] = $arrGroupAll;
				}
				if($rowTypeGuarantee["IS_SAVING"] == '1'){
					$arrGroupAll = [];
					$arrGroupAll['LOANCOLLTYPE_CODE'] = '03';
					$arrGroupAll['COLLTYPE_DESC'] = "เงินฝากค้ำประกัน";
					$getGuarantee = $conoracle->prepare("SELECT LS_ACCNO as REF_COLLNO,LS_SAL_HOLD
														FROM LOAN_T_SAVING WHERE LCONT_ID = :contract_no and (FLAG = 'N' OR FLAG IS NULL)");
					$getGuarantee->execute([':contract_no' => $rowWhocollu["LOANCONTRACT_NO"]]);
					while($rowColl = $getGuarantee->fetch(PDO::FETCH_ASSOC)){
						$arrGroupAllMember = [];
						$getDataAcc = $conoracle->prepare("SELECT ACCOUNT_NAME FROM BK_H_SAVINGACCOUNT WHERE account_no = :account_no");
						$getDataAcc->execute([':account_no' => $rowColl["REF_COLLNO"]]);
						$rowAcc = $getDataAcc->fetch(PDO::FETCH_ASSOC);
						$arrGroupAllMember["DEPTACCOUNT_NO"] = $lib->formataccount_hidden($lib->formataccount($rowColl["REF_COLLNO"],$func->getConstant('dep_format')),$func->getConstant('hidden_dep'));
						$arrGroupAllMember["DEPTACCOUNT_NAME"] = $rowAcc["ACCOUNT_NAME"];
						$arrGroupAllMember["DEPT_AMT"] = number_format($rowColl["LS_SAL_HOLD"],2);
						$arrGroupAll['ASSET'][] = $arrGroupAllMember;
					}
					$arrGrpAllLoan[] = $arrGroupAll;
				}
				
				//gold
				$arrGroupAll = [];
				$arrGroupAll['LOANCOLLTYPE_CODE'] = '06';
				$arrGroupAll['COLLTYPE_DESC'] = "ทองค้ำประกัน";
				$getGuarantee = $conoracle->prepare("select td.lcont_id,td.br_no,td.lreg_recno,td.code,lmg.g_name,td.ltg_cost_appraisal  
													from loan_t_gold td INNER JOIN loan_m_contact c on c.lcont_id = td.lcont_id and c.br_no = td.br_no 
													INNER JOIN mem_h_member m on m.id_card = c.id_card INNER JOIN mem_m_ptitle p ON p.ptitle_id = m.ptitle_id 
													INNER JOIN loan_m_type_gold lmg ON lmg.g_code = td.ltg_type 
													WHERE c.lcont_amount_sal > 0 and td.lcont_id = :contract_no and (m.tried_flg ='0' or m.tried_flg ='N')");
				$getGuarantee->execute([':contract_no' => $rowWhocollu["LOANCONTRACT_NO"]]);
				while($rowColl = $getGuarantee->fetch(PDO::FETCH_ASSOC)){
					$arrGroupAllMember = [];
					$arrGroupAllMember["CATEGORY_NAME"] = $rowColl["G_NAME"];
					$arrGroupAllMember["COST_APPRAISAL"] = number_format($rowColl["LTG_COST_APPRAISAL"],2);
					$arrGroupAll['ASSET'][] = $arrGroupAllMember;
				}
				if(count($arrGroupAll['ASSET']) > 0){
					$arrGrpAllLoan[] = $arrGroupAll;
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