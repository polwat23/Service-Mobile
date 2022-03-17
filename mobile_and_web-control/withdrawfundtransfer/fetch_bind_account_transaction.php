<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransactionWithdrawDeposit')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGroupAccBind = array();
		$fetchBindAccount = $conmysql->prepare("SELECT gba.sigma_key,gba.deptaccount_no_coop,gba.deptaccount_no_bank,csb.bank_logo_path,
												csb.bank_format_account,csb.bank_format_account_hide,gba.bank_code,csb.bank_short_name
												FROM gcbindaccount gba LEFT JOIN csbankdisplay csb ON gba.bank_code = csb.bank_code
												WHERE gba.member_no = :member_no and gba.bindaccount_status = '1'");
		$fetchBindAccount->execute([':member_no' => $payload["member_no"]]);
		if($fetchBindAccount->rowCount() > 0){
			while($rowAccBind = $fetchBindAccount->fetch(PDO::FETCH_ASSOC)){
				$arrAccBind = array();
				$arrAccBind["SIGMA_KEY"] = $rowAccBind["sigma_key"];
				$arrAccBind["BANK_NAME"] = $rowAccBind["bank_short_name"];
				$arrAccBind["BANK_CODE"] = $rowAccBind["bank_code"];
				$arrAccBind["BANK_LOGO"] = $config["URL_SERVICE"].$rowAccBind["bank_logo_path"];
				$explodePathLogo = explode('.',$rowAccBind["bank_logo_path"]);
				$arrAccBind["BANK_LOGO_WEBP"] = $config["URL_SERVICE"].$explodePathLogo[0].'.webp';
				$arrAccBind["DEPTACCOUNT_NO_BANK"] = $rowAccBind["deptaccount_no_bank"];
				$arrAccBind["DEPTACCOUNT_NO_BANK_FORMAT"] = $lib->formataccount($rowAccBind["deptaccount_no_bank"],$rowAccBind["bank_format_account"]);
				$arrAccBind["DEPTACCOUNT_NO_BANK_FORMAT_HIDE"] = $lib->formataccount_hidden($rowAccBind["deptaccount_no_bank"],$rowAccBind["bank_format_account_hide"]);
				$arrGroupAccBind["BIND"][] = $arrAccBind;
			}
			$fetchAccAllowTrans = $conmysql->prepare("SELECT gat.deptaccount_no 
													FROM gcuserallowacctransaction gat LEFT JOIN gcconstantaccountdept gct ON 
													gat.id_accountconstant = gct.id_accountconstant
													WHERE gct.allow_withdraw_outside = '1' and gat.member_no = :member_no and gat.is_use = '1'");
			$fetchAccAllowTrans->execute([':member_no' => $payload["member_no"]]);
			if($fetchAccAllowTrans->rowCount() > 0){
				while($rowAccAllow = $fetchAccAllowTrans->fetch(PDO::FETCH_ASSOC)){
					$arrayDept[] = "'".$rowAccAllow["deptaccount_no"]."'";
				}
				$getAllAcc = $conoracle->prepare("SELECT dpm.deptaccount_no,dpm.deptaccount_name,dpt.depttype_desc,dpm.depttype_code,dpm.PRNCBAL,
												dpm.sequest_amount,dpm.sequest_status,dpt.minprncbal,dpm.CHECKPEND_AMT
												FROM dpdeptmaster dpm LEFT JOIN dpdepttype dpt ON dpm.depttype_code = dpt.depttype_code and dpm.membcat_code = dpt.membcat_code
												WHERE dpm.deptclose_status = '0' and dpm.deptaccount_no IN(".implode(',',$arrayDept).")
												ORDER BY dpm.deptaccount_no");
				$getAllAcc->execute();
				while($rowDataAccAll = $getAllAcc->fetch(PDO::FETCH_ASSOC)){
					$getSeqAmt = $cal_dep->getSequestAmount($rowDataAccAll["DEPTACCOUNT_NO"],'WES');
					if($getSeqAmt["RESULT"]){
						$arrAccCoop = array();
						$arrAccCoop["DEPTACCOUNT_NO"] = $rowDataAccAll["DEPTACCOUNT_NO"];
						$arrAccCoop["DEPTACCOUNT_NO_FORMAT"] = $lib->formataccount($rowDataAccAll["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
						$arrAccCoop["DEPTACCOUNT_NO_FORMAT_HIDE"] = $lib->formataccount_hidden($arrAccCoop["DEPTACCOUNT_NO_FORMAT"],$func->getConstant('hidden_dep'));
						$arrAccCoop["ACCOUNT_NAME"] = preg_replace('/\"/','',$rowDataAccAll["DEPTACCOUNT_NAME"]);
						$arrAccCoop["DEPT_TYPE"] = $rowDataAccAll["DEPTTYPE_DESC"];
						if($getSeqAmt["GET_BALANCE"]){
							$arrAccCoop["BALANCE"] = $cal_dep->getWithdrawable($rowDataAccAll["DEPTACCOUNT_NO"]);
						}else{
							$arrAccCoop["BALANCE"] = $getSeqAmt["SEQUEST_AMOUNT"];
						}
						$arrAccCoop["BALANCE_FORMAT"] = number_format($arrAccCoop["BALANCE"],2);
						$arrGroupAccBind["COOP"][] = $arrAccCoop;
					}
				}
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0023";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
			if(sizeof($arrGroupAccBind["BIND"]) > 0 && sizeof($arrGroupAccBind["COOP"]) > 0){
				$arrayResult['IS_DEFAULT_BIND_ACCOUNT'] = FALSE;
				$arrayResult['IS_DEFAULT_COOP_ACCOUNT'] = FALSE;
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
			$arrayResult['RESPONSE_CODE'] = "WS0021";
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