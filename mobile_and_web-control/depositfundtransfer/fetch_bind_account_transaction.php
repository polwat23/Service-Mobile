<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransactionDeposit')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGroupAccBind = array();
		$fetchBindAccount = $conoracle->prepare("SELECT gba.sigma_key,gba.deptaccount_no_coop,gba.deptaccount_no_bank,csb.bank_logo_path,
												csb.bank_format_account,csb.bank_format_account_hide,csb.bank_short_name,gba.bindaccount_status
												FROM gcbindaccount gba LEFT JOIN csbankdisplay csb ON gba.bank_code = csb.bank_code
												WHERE gba.member_no = :member_no and gba.bindaccount_status IN('1','7') ORDER BY gba.deptaccount_no_coop,gba.bindaccount_status");
		$fetchBindAccount->execute([':member_no' => $payload["member_no"]]);
		
		$rowAccBind = $fetchBindAccount->fetch(PDO::FETCH_ASSOC);
		if(isset($rowAccBind["SIGMA_KEY"])){
			$getAccBankAllow = $conoracle->prepare("SELECT atr.account_code,REPLACE(cmb.account_format,'@','x') as account_format 
													FROM atmregistermobile atr LEFT JOIN cmucfbank cmb ON atr.expense_bank = cmb.bank_code
													WHERE atr.member_no = :member_no and atr.expense_bank = '006' and atr.appl_status = '1' 
													and atr.connect_status = '1' and atr.cancel_id IS NULL");
			$getAccBankAllow->execute([':member_no' => $member_no]);
			$rowAccBank = $getAccBankAllow->fetch(PDO::FETCH_ASSOC);
			if(isset($rowAccBank["ACCOUNT_CODE"]) && $rowAccBank["ACCOUNT_CODE"] != "" && 
			$rowAccBind["DEPTACCOUNT_NO_BANK"] == $rowAccBank["ACCOUNT_CODE"]){
				if($rowAccBind["BINDACCOUNT_STATUS"] == '7'){
					$updateStatus = $conoracle->prepare("UPDATE gcbindaccount SET bindaccount_status = '1' WHERE sigma_key = :sigma_key");
					$updateStatus->execute(['sigma_key' => $rowAccBind["SIGMA_KEY"]]);
					$rowAccBind["BINDACCOUNT_STATUS"] = '1';
				}
			}else{
				$getAccBankAllowATM = $conoracle->prepare("SELECT atr.account_code,REPLACE(cmb.account_format,'@','x') as account_format 
														FROM atmregister atr LEFT JOIN cmucfbank cmb ON atr.expense_bank = cmb.bank_code
														WHERE atr.member_no = :member_no and atr.expense_bank = '006' and atr.appl_status = '1'
														and atr.cancel_id IS NULL");
				$getAccBankAllowATM->execute([':member_no' => $member_no]);
				$rowAccBankATM = $getAccBankAllowATM->fetch(PDO::FETCH_ASSOC);
				if(isset($rowAccBankATM["ACCOUNT_CODE"]) && $rowAccBankATM["ACCOUNT_CODE"] != "" && 
				$rowAccBind["DEPTACCOUNT_NO_BANK"] == $rowAccBankATM["ACCOUNT_CODE"]){
					if($rowAccBind["BINDACCOUNT_STATUS"] == '7'){
						$updateStatus = $conoracle->prepare("UPDATE gcbindaccount SET bindaccount_status = '1' WHERE sigma_key = :sigma_key");
						$updateStatus->execute(['sigma_key' => $rowAccBind["SIGMA_KEY"]]);
						$rowAccBind["BINDACCOUNT_STATUS"] = '1';
					}
				}else{
					$arrayResult['RESPONSE_CODE'] = "WS0099";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
				}
			}
			$fetchAccountBeenAllow = $conoracle->prepare("SELECT gat.deptaccount_no 
												FROM gcuserallowacctransaction gat LEFT JOIN gcconstantaccountdept gct ON 
												gat.id_accountconstant = gct.id_accountconstant
												WHERE gct.allow_deposit_outside = '1' and gat.deptaccount_no = :deptaccount_no and gat.is_use = '1'");
			$fetchAccountBeenAllow->execute([':deptaccount_no' =>  $rowAccBind["DEPTACCOUNT_NO_COOP"]]);
			$arrAccBind = $fetchAccountBeenAllow->fetch(PDO::FETCH_ASSOC);
			if(isset($arrAccBind["DEPTACCOUNT_NO"]) && $rowAccBind["BINDACCOUNT_STATUS"] == '1'){
				$arrAccBind = array();
				$arrAccBind["SIGMA_KEY"] = $rowAccBind["SIGMA_KEY"];
				$arrAccBind["BANK_NAME"] = $rowAccBind["BANK_SHORT_NAME"];
				$arrAccBind["DEPTACCOUNT_NO"] = $rowAccBind["DEPTACCOUNT_NO_COOP"];
				$arrAccBind["DEPTACCOUNT_NO_FORMAT"] = $lib->formataccount($rowAccBind["DEPTACCOUNT_NO_COOP"],$func->getConstant('dep_format'));
				$arrAccBind["DEPTACCOUNT_NO_FORMAT_HIDE"] = $lib->formataccount_hidden($rowAccBind["DEPTACCOUNT_NO_COOP"],$func->getConstant('hidden_dep'));
				$arrAccBind["BANK_LOGO"] = $config["URL_SERVICE"].$rowAccBind["BANK_LOGO_PATH"];
				$explodePathLogo = explode('.',$rowAccBind["BANK_LOGO_PATH"]);
				$arrAccBind["BANK_LOGO_WEBP"] = $config["URL_SERVICE"].$explodePathLogo[0].'.webp';
				$arrAccBind["DEPTACCOUNT_NO_BANK"] = $rowAccBind["deptaccount_no_bank"];
				$arrAccBind["DEPTACCOUNT_NO_BANK_FORMAT"] = $lib->formataccount($rowAccBind["DEPTACCOUNT_NO_BANK"],$rowAccBind["BANK_FORMAT_ACCOUNT"]);
				$arrAccBind["DEPTACCOUNT_NO_BANK_FORMAT_HIDE"] = $lib->formataccount_hidden($rowAccBind["DEPTACCOUNT_NO_BANK"],$rowAccBind["BANK_FORMAT_ACCOUNT_HIDE"]);
				$getDataAcc = $conoracle->prepare("SELECT TRIM(dpm.deptaccount_name) as DEPTACCOUNT_NAME,dpm.DEPT_OBJECTIVE,dpt.depttype_desc,dpm.prncbal,dpm.depttype_code
													FROM dpdeptmaster dpm LEFT JOIN dpdepttype dpt ON dpm.depttype_code = dpt.depttype_code
													WHERE dpm.deptaccount_no = :deptaccount_no and dpm.deptclose_status = 0 and dpm.acccont_type = '01'");
				$getDataAcc->execute([':deptaccount_no' => $rowAccBind["DEPTACCOUNT_NO_COOP"]]);
				$rowDataAcc = $getDataAcc->fetch(PDO::FETCH_ASSOC);
				if(isset($rowDataAcc["DEPTTYPE_DESC"])){
					if(file_exists(__DIR__.'/../../resource/dept-type/'.$rowDataAcc["DEPTTYPE_CODE"].'.png')){
						$arrAccBind["DEPT_TYPE_IMG"] = $config["URL_SERVICE"].'resource/dept-type/'.$rowDataAcc["DEPTTYPE_CODE"].'.png?v='.date('Ym');
					}else{
						$arrAccBind["DEPT_TYPE_IMG"] = null;
					}
					$arrAccBind["ACCOUNT_NAME"] = preg_replace('/\"/','',trim($rowDataAcc["DEPTACCOUNT_NAME"].' '.$rowDataAcc["DEPT_OBJECTIVE"]));
					$arrAccBind["DEPT_TYPE"] = $rowDataAcc["DEPTTYPE_DESC"];
					$arrAccBind["BALANCE"] = $rowDataAcc["PRNCBAL"];
					$arrAccBind["BALANCE_FORMAT"] = number_format($rowDataAcc["PRNCBAL"],2);
					$arrGroupAccBind[] = $arrAccBind;
				}
			}
		}
		if(sizeof($arrGroupAccBind) > 0){
			$arrayResult['ACCOUNT'] = $arrGroupAccBind;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0023";
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
