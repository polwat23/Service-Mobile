<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransactionWithdrawDeposit')){
		$time = date("Hi");
		if($time >= 0000 && $time <= 0200){
			$arrayResult['RESPONSE_CODE'] = "WS0035";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
		$arrGroupAccBind = array();
		$fetchBindAccount = $conmysql->prepare("SELECT gba.sigma_key,gba.deptaccount_no_coop,gba.deptaccount_no_bank,csb.bank_logo_path,
												csb.bank_format_account,csb.bank_format_account_hide
												FROM gcbindaccount gba LEFT JOIN csbankdisplay csb ON gba.bank_code = csb.bank_code
												WHERE gba.member_no = :member_no and gba.bindaccount_status = '1'");
		$fetchBindAccount->execute([':member_no' => $payload["member_no"]]);
		if($fetchBindAccount->rowCount() > 0){
			while($rowAccBind = $fetchBindAccount->fetch()){
				$arrAccBind = array();
				$arrAccBind["SIGMA_KEY"] = $rowAccBind["sigma_key"];
				$arrAccBind["DEPTACCOUNT_NO"] = $rowAccBind["deptaccount_no_coop"];
				$arrAccBind["DEPTACCOUNT_NO_FORMAT"] = $lib->formataccount($rowAccBind["deptaccount_no_coop"],$func->getConstant('dep_format'));
				$arrAccBind["DEPTACCOUNT_NO_FORMAT_HIDE"] = $lib->formataccount_hidden($rowAccBind["deptaccount_no_coop"],$func->getConstant('hidden_dep'));
				$arrAccBind["BANK_LOGO"] = $rowAccBind["bank_logo_path"];
				$explodePathLogo = explode('.',$rowAccBind["bank_logo_path"]);
				$arrAccBind["BANK_LOGO_WEBP"] = $explodePathLogo[0].'.webp';
				$arrAccBind["DEPTACCOUNT_NO_BANK"] = $rowAccBind["deptaccount_no_bank"];
				$arrAccBind["DEPTACCOUNT_NO_BANK_FORMAT"] = $lib->formataccount($rowAccBind["deptaccount_no_bank"],$rowAccBind["bank_format_account"]);
				$arrAccBind["DEPTACCOUNT_NO_BANK_FORMAT_HIDE"] = $lib->formataccount_hidden($rowAccBind["deptaccount_no_bank"],$rowAccBind["bank_format_account_hide"]);
				$getDataAcc = $conoracle->prepare("SELECT dpm.deptaccount_name,dpt.depttype_desc,dpm.prncbal
													FROM dpdeptmaster dpm LEFT JOIN dpdepttype dpt ON dpm.depttype_code = dpt.depttype_code
													and dpm.membcat_code = dpt.membcat_code
													WHERE dpm.deptaccount_no = :deptaccount_no");
				$getDataAcc->execute([':deptaccount_no' => $rowAccBind["deptaccount_no_coop"]]);
				$rowDataAcc = $getDataAcc->fetch();
				if(isset($rowDataAcc["DEPTTYPE_DESC"])){
					$arrAccBind["ACCOUNT_NAME"] = preg_replace('/\"/','',$rowDataAcc["DEPTACCOUNT_NAME"]);
					$arrAccBind["DEPT_TYPE"] = $rowDataAcc["DEPTTYPE_DESC"];
					$arrAccBind["BALANCE"] = $rowDataAcc["PRNCBAL"];
					$arrAccBind["BALANCE_FORMAT"] = number_format($rowDataAcc["PRNCBAL"],2);
					$arrGroupAccBind[] = $arrAccBind;
				}
			}
			if(sizeof($arrGroupAccBind) > 0 || isset($new_token)){
				$arrayResult['ACCOUNT'] = $arrGroupAccBind;
				if(isset($new_token)){
					$arrayResult['NEW_TOKEN'] = $new_token;
				}
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0023";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0023";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
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
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>