<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'BindAccountConsent')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$fetchDataMember = $conoracle->prepare("SELECT card_person,membcat_code FROM mbmembmaster WHERE member_no = :member_no");
		$fetchDataMember->execute([
			':member_no' => $member_no
		]);
		$rowDataMember = $fetchDataMember->fetch(PDO::FETCH_ASSOC);
		if(isset($rowDataMember["CARD_PERSON"])){
			$fetchConstantAllowDept = $conmysql->prepare("SELECT gat.deptaccount_no FROM gcuserallowacctransaction gat
															LEFT JOIN gcconstantaccountdept gad ON gat.id_accountconstant = gad.id_accountconstant
															WHERE gat.member_no = :member_no and gat.is_use = '1' 
															and gad.allow_deposit_outside = '1' and gad.allow_withdraw_outside = '1'");
			$fetchConstantAllowDept->execute([
				':member_no' => $payload["member_no"]
			]);
			if($fetchConstantAllowDept->rowCount() > 0){
				$arrayDeptAllow = array();
				while($rowAllowDept = $fetchConstantAllowDept->fetch(PDO::FETCH_ASSOC)){
					$arrayDeptAllow[] = $rowAllowDept["deptaccount_no"];
				}
				$arrAccBeenBind = array();
				$InitDeptAccountBeenBind = $conmysql->prepare("SELECT deptaccount_no_coop FROM gcbindaccount WHERE member_no = :member_no 
																and bindaccount_status NOT IN('6','7','8','-9') and deptaccount_no_coop IS NOT NULL");
				$InitDeptAccountBeenBind->execute([':member_no' => $payload["member_no"]]);
				while($rowAccountBeenbind = $InitDeptAccountBeenBind->fetch(PDO::FETCH_ASSOC)){
					$arrAccBeenBind[] = $rowAccountBeenbind["deptaccount_no_coop"];
				}
				if(sizeof($arrAccBeenBind) > 0){
					$fetchDataAccount = $conoracle->prepare("SELECT dpt.depttype_desc,dpm.deptaccount_no,dpm.deptaccount_name FROM dpdeptmaster dpm LEFT JOIN dpdepttype dpt 
															ON dpm.depttype_code = dpt.depttype_code 
															WHERE dpm.member_no = :member_no and dpt.membcat_code = :membcat_code and 
															dpm.deptaccount_no IN(".implode(',',$arrayDeptAllow).") and dpm.deptclose_status = 0 and dpm.acccont_type = '01'
															and dpm.deptaccount_no NOT IN(".implode(',',$arrAccBeenBind).")");
				}else{
					$fetchDataAccount = $conoracle->prepare("SELECT dpt.depttype_desc,dpm.deptaccount_no,dpm.deptaccount_name FROM dpdeptmaster dpm LEFT JOIN dpdepttype dpt 
															ON dpm.depttype_code = dpt.depttype_code 
															WHERE dpm.member_no = :member_no and dpt.membcat_code = :membcat_code and 
															dpm.deptaccount_no IN(".implode(',',$arrayDeptAllow).") and dpm.deptclose_status = 0 and dpm.acccont_type = '01'");
				}
				$fetchDataAccount->execute([
					':member_no' => $member_no,
					':membcat_code' => $rowDataMember["MEMBCAT_CODE"]
				]);
				$arrayGroupAccount = array();
				while($rowDataAccount = $fetchDataAccount->fetch(PDO::FETCH_ASSOC)){
					$arrayAccount = array();
					$arrayAccount["DEPTTYPE_DESC"] = $rowDataAccount["DEPTTYPE_DESC"];
					$arrayAccount["ACCOUNT_NO"] = $lib->formataccount($rowDataAccount["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
					$arrayAccount["ACCOUNT_NAME"] = preg_replace('/\"/','',$rowDataAccount["DEPTACCOUNT_NAME"]);
					$arrayGroupAccount[] = $arrayAccount;
				}
				if(sizeof($arrayGroupAccount) > 0){
					$arrayResult['ACCOUNT'] = $arrayGroupAccount;
					$getAccBankAllow = $conoracle->prepare("SELECT atr.account_code,REPLACE(cmb.account_format,'@','x') as account_format 
															FROM atmregistermobile atr LEFT JOIN cmucfbank cmb ON atr.expense_bank = cmb.bank_code
															WHERE atr.member_no = :member_no and atr.expense_bank = '006' and atr.appl_status = '1' 
															and atr.connect_status = '1'");
					$getAccBankAllow->execute([':member_no' => $member_no]);
					$rowAccBank = $getAccBankAllow->fetch(PDO::FETCH_ASSOC);
					if(isset($rowAccBank["ACCOUNT_CODE"]) && $rowAccBank["ACCOUNT_CODE"] != ""){
						$arrayResult['ACCOUNT_BANK_FORMAT'] = $lib->formataccount($rowAccBank["ACCOUNT_CODE"],$rowAccBank["ACCOUNT_FORMAT"]);
						$arrayResult['CITIZEN_ID_FORMAT'] = $lib->formatcitizen($rowDataMember["CARD_PERSON"]);
						$arrayResult['CITIZEN_ID'] = $rowDataMember["CARD_PERSON"];
						$arrayResult['IS_CHOOSE_ACCOUNT'] = FALSE;
						$arrayResult['RESULT'] = TRUE;
						require_once('../../include/exit_footer.php');
					}else{
						$getAccBankAllowATM = $conoracle->prepare("SELECT atr.account_code,REPLACE(cmb.account_format,'@','x') as account_format 
																FROM atmregister atr LEFT JOIN cmucfbank cmb ON atr.expense_bank = cmb.bank_code
																WHERE atr.member_no = :member_no and atr.expense_bank = '006' and atr.appl_status = '1'");
						$getAccBankAllowATM->execute([':member_no' => $member_no]);
						$rowAccBankATM = $getAccBankAllowATM->fetch(PDO::FETCH_ASSOC);
						if(isset($rowAccBankATM["ACCOUNT_CODE"]) && $rowAccBankATM["ACCOUNT_CODE"] != ""){
							$arrayResult['ACCOUNT_BANK_FORMAT'] = $lib->formataccount($rowAccBankATM["ACCOUNT_CODE"],$rowAccBankATM["ACCOUNT_FORMAT"]);
							$arrayResult['CITIZEN_ID_FORMAT'] = $lib->formatcitizen($rowDataMember["CARD_PERSON"]);
							$arrayResult['CITIZEN_ID'] = $rowDataMember["CARD_PERSON"];
							$arrayResult['IS_CHOOSE_ACCOUNT'] = FALSE;
							$arrayResult['RESULT'] = TRUE;
							require_once('../../include/exit_footer.php');
						}else{
							$arrayResult['RESPONSE_CODE'] = "WS0099";
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
							$arrayResult['RESULT'] = FALSE;
							require_once('../../include/exit_footer.php');
						}
					}
				}else{
					$arrayResult['RESPONSE_CODE'] = "WS0005";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0005";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
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