<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ManagementAccount')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGroupAccAllow = array();
		$arrAccAllow = array();
		$arrAccAllowDep = array();
		$fetchAccountBeenAllow = $conmysql->prepare("SELECT gat.deptaccount_no,gat.is_use,gat.limit_transaction_amt,gct.allow_showdetail,
														gct.allow_withdraw_outside,gct.allow_withdraw_inside,gct.allow_deposit_outside,gct.allow_deposit_inside,
														gct.allow_buy_share,gct.allow_pay_loan
														FROM gcuserallowacctransaction gat LEFT JOIN gcconstantaccountdept gct ON gat.id_accountconstant = gct.id_accountconstant
														WHERE gat.member_no = :member_no and gat.is_use <> '-9'");
		$fetchAccountBeenAllow->execute([':member_no' => $payload["member_no"]]);
		while($rowAccBeenAllow = $fetchAccountBeenAllow->fetch(PDO::FETCH_ASSOC)){
			$arrAccBeenAllow = array();
			$getDetailAcc = $conoracle->prepare("SELECT TRIM(dpm.deptaccount_name) as DEPTACCOUNT_NAME,dpt.DEPTTYPE_DESC,dpm.depttype_code
													FROM dpdeptmaster dpm LEFT JOIN dpdepttype dpt ON dpm.depttype_code = dpt.depttype_code and dpm.membcat_code = dpt.membcat_code
													WHERE dpm.deptaccount_no = :deptaccount_no and dpm.deptclose_status = 0");
			$getDetailAcc->execute([':deptaccount_no' => $rowAccBeenAllow["deptaccount_no"]]);
			$rowDetailAcc = $getDetailAcc->fetch(PDO::FETCH_ASSOC);
			if(isset($rowDetailAcc["DEPTACCOUNT_NAME"])){
				$getDeptTypeAllow = $conmysql->prepare("SELECT allow_withdraw_outside,allow_withdraw_inside,allow_deposit_outside,
														allow_buy_share,allow_pay_loan,allow_showdetail
														FROM gcconstantaccountdept
														WHERE dept_type_code = :depttype_code");
				$getDeptTypeAllow->execute([
					':depttype_code' => $rowDetailAcc["DEPTTYPE_CODE"]
				]);
				$rowDeptTypeAllow = $getDeptTypeAllow->fetch(PDO::FETCH_ASSOC);
				if(($rowDeptTypeAllow["allow_withdraw_outside"] == '0' && $rowDeptTypeAllow["allow_deposit_outside"] == '0') 
				&& ($rowDeptTypeAllow["allow_withdraw_inside"] == '1' || $rowDeptTypeAllow["allow_deposit_inside"] == '1')){
					if($rowDeptTypeAllow["allow_buy_share"] == '1' && $rowDeptTypeAllow["allow_pay_loan"] == '1'){
						$arrAccBeenAllow["ALLOW_DESC"] = $configError['ALLOW_TRANS_INSIDE_ALL_FLAG_ON'][0][$lang_locale];
					}else if($rowDeptTypeAllow["allow_buy_share"] == '1' && $rowDeptTypeAllow["allow_pay_loan"] == '0'){
						$arrAccBeenAllow["ALLOW_DESC"] = $configError['ALLOW_TRANS_BUY_SHARE_FLAG_ON'][0][$lang_locale];
					}else if($rowDeptTypeAllow["allow_buy_share"] == '0' && $rowDeptTypeAllow["allow_pay_loan"] == '1'){
						$arrAccBeenAllow["ALLOW_DESC"] = $configError['ALLOW_TRANS_PAY_LOAN_FLAG_ON'][0][$lang_locale];
					}else{
						$arrAccBeenAllow["ALLOW_DESC"] = $configError['ALLOW_TRANS_INSIDE_FLAG_ON'][0][$lang_locale];
					}
				}else if(($rowDeptTypeAllow["allow_withdraw_outside"] == '1' || $rowDeptTypeAllow["allow_deposit_outside"] == '1')){
					if($rowDeptTypeAllow["allow_deposit_inside"] == '0' && $rowDeptTypeAllow["allow_withdraw_inside"] == '0'){
						$arrAccBeenAllow["ALLOW_DESC"] = $configError['ALLOW_TRANS_OUTSIDE_FLAG_ON'][0][$lang_locale];
					}else{
						$arrAccBeenAllow["ALLOW_DESC"] = $configError['ALLOW_TRANS_ALL_FLAG_ON'][0][$lang_locale];
					}
				}
				if($rowDeptTypeAllow["allow_showdetail"] == '1'){
					$arrAccBeenAllow["FLAG_NAME"] = $configError['ALLOW_ACC_SHOW_FLAG_ON'][0][$lang_locale];
				}else{
					$arrAccBeenAllow["FLAG_NAME"] = $configError['ALLOW_ACC_SHOW_FLAG_OFF'][0][$lang_locale];
				}
				$arrAccBeenAllow["DEPTACCOUNT_NAME"] = preg_replace('/\"/','',$rowDetailAcc["DEPTACCOUNT_NAME"]);
				$arrAccBeenAllow["DEPT_TYPE"] = $rowDetailAcc["DEPTTYPE_DESC"];
				$limit_coop = $func->getConstant("limit_withdraw");
				if($rowDeptTypeAllow["limit_transaction_amt"] > $limit_coop){
					$arrAccBeenAllow["LIMIT_TRANSACTION_AMT"] = $limit_coop;
				}else{
					$arrAccBeenAllow["LIMIT_TRANSACTION_AMT"] = $rowDeptTypeAllow["limit_transaction_amt"];
				}
				$arrAccBeenAllow["LIMIT_COOP_TRANS_AMT"] = $limit_coop;
				$arrAccBeenAllow["DEPTACCOUNT_NO"] = $rowAccBeenAllow["deptaccount_no"];
				$arrAccBeenAllow["DEPTACCOUNT_NO_FORMAT"] = $lib->formataccount($rowAccBeenAllow["deptaccount_no"],$func->getConstant('dep_format'));
				$arrAccBeenAllow["DEPTACCOUNT_NO_FORMAT_HIDE"] = $lib->formataccount_hidden($arrAccBeenAllow["DEPTACCOUNT_NO_FORMAT"],$func->getConstant('hidden_dep'));
				$arrAccBeenAllow["STATUS_ALLOW"] = $rowDeptTypeAllow["is_use"];
				$arrGroupAccAllow[] = $arrAccBeenAllow;
			}
		}
		$arrayResult['ACCOUNT_ALLOW'] = $arrGroupAccAllow;
		$arrayResult['RESULT'] = TRUE;
		require_once('../../include/exit_footer.php');
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