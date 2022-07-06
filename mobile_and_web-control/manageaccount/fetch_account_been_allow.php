<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ManagementAccount')){
		$arrGroupAccAllow = array();
		$fetchAccountBeenAllow = $conoracle->prepare("SELECT gat.deptaccount_no,gat.is_use
														FROM gcuserallowacctransaction gat
														WHERE gat.member_no = :member_no and gat.is_use <> '-9' ORDER BY gat.deptaccount_no ASC");
		$fetchAccountBeenAllow->execute([':member_no' => $payload["member_no"]]);
		while($rowAccBeenAllow = $fetchAccountBeenAllow->fetch(PDO::FETCH_ASSOC)){
			$arrAccBeenAllow = array();
			$getDetailAcc = $conoracle->prepare("SELECT TRIM(dpm.deptaccount_name) as DEPTACCOUNT_NAME,dpt.depttype_desc,dpm.depttype_code,dpm.acccont_type
													FROM dpdeptmaster dpm LEFT JOIN dpdepttype dpt ON dpm.depttype_code = dpt.depttype_code
													WHERE dpm.deptaccount_no = :deptaccount_no and dpm.deptclose_status = 0");
			$getDetailAcc->execute([':deptaccount_no' => $rowAccBeenAllow["DEPTACCOUNT_NO"]]);
			$rowDetailAcc = $getDetailAcc->fetch(PDO::FETCH_ASSOC);
			if(isset($rowDetailAcc["DEPTACCOUNT_NAME"])){
				if($rowDetailAcc["ACCCONT_TYPE"] != '01'){
					$arrAccBeenAllow["FLAG_NAME"] = $configError['ACC_JOIN_FLAG_OFF'][0][$lang_locale];
				}
				$getDeptTypeAllow = $conoracle->prepare("SELECT allow_withdraw_outside,allow_withdraw_inside,allow_deposit_outside,allow_deposit_inside,
														allow_buy_share,allow_pay_loan
														FROM gcconstantaccountdept
														WHERE dept_type_code = :depttype_code");
				$getDeptTypeAllow->execute([
					':depttype_code' => $rowDetailAcc["DEPTTYPE_CODE"]
				]);
				$rowDeptTypeAllow = $getDeptTypeAllow->fetch(PDO::FETCH_ASSOC);
				if($rowDeptTypeAllow["ALLOW_WITHDRAW_OUTSIDE"] == '0' && $rowDeptTypeAllow["ALLOW_WITHDRAW_INSIDE"] == '1' && 
				$rowDeptTypeAllow["ALLOW_DEPOSIT_OUTSIDE"] == '0' && $rowDeptTypeAllow["ALLOW_DEPOSIT_INSIDE"] == '1'){
					if($rowDeptTypeAllow["ALLOW_BUY_SHARE"] == '1' && $rowDeptTypeAllow["ALLOW_PAY_LOAN"] == '1'){
						$arrAccBeenAllow["ALLOW_DESC"] = $configError['ALLOW_TRANS_INSIDE_ALL_FLAG_ON'][0][$lang_locale];
					}else if($rowDeptTypeAllow["ALLOW_BUY_SHARE"] == '1' && $rowDeptTypeAllow["ALLOW_PAY_LOAN"] == '0'){
						$arrAccBeenAllow["ALLOW_DESC"] = $configError['ALLOW_TRANS_BUY_SHARE_FLAG_ON'][0][$lang_locale];
					}else if($rowDeptTypeAllow["ALLOW_BUY_SHARE"] == '0' && $rowDeptTypeAllow["ALLOW_PAY_LOAN"] == '1'){
						$arrAccBeenAllow["ALLOW_DESC"] = $configError['ALLOW_TRANS_PAY_LOAN_FLAG_ON'][0][$lang_locale];
					}else{
						$arrAccBeenAllow["ALLOW_DESC"] = $configError['ALLOW_TRANS_INSIDE_FLAG_ON'][0][$lang_locale];
					}
				}else if($rowDeptTypeAllow["ALLOW_WITHDRAW_OUTSIDE"] == '1' && $rowDeptTypeAllow["ALLOW_DEPOSIT_OUTSIDE"] == '1'){
					if($rowDeptTypeAllow["ALLOW_DEPOSIT_INSIDE"] == '0' && $rowDeptTypeAllow["ALLOW_WITHDRAW_INSIDE"] == '0'){
						$arrAccBeenAllow["ALLOW_DESC"] = $configError['ALLOW_TRANS_OUTSIDE_FLAG_ON'][0][$lang_locale];
					}else{
						$arrAccBeenAllow["ALLOW_DESC"] = $configError['ALLOW_TRANS_ALL_FLAG_ON'][0][$lang_locale];
					}
				}
				if(file_exists(__DIR__.'/../../resource/dept-type/'.$rowDetailAcc["DEPTTYPE_CODE"].'.png')){
					$arrAccBeenAllow["DEPT_TYPE_IMG"] = $config["URL_SERVICE"].'resource/dept-type/'.$rowDetailAcc["DEPTTYPE_CODE"].'.png?v='.date('Ym');
				}else{
					$arrAccBeenAllow["DEPT_TYPE_IMG"] = null;
				}
				$arrAccBeenAllow["DEPTACCOUNT_NAME"] = preg_replace('/\"/','',trim($rowDetailAcc["DEPTACCOUNT_NAME"]));
				$arrAccBeenAllow["DEPT_TYPE"] = $rowDetailAcc["DEPTTYPE_DESC"];
				$arrAccBeenAllow["DEPTACCOUNT_NO"] = $rowAccBeenAllow["DEPTACCOUNT_NO"];
				$arrAccBeenAllow["DEPTACCOUNT_NO_FORMAT"] = $lib->formataccount($rowAccBeenAllow["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
				$arrAccBeenAllow["DEPTACCOUNT_NO_FORMAT_HIDE"] = $lib->formataccount_hidden($rowAccBeenAllow["DEPTACCOUNT_NO"],$func->getConstant('hidden_dep'));
				$arrAccBeenAllow["STATUS_ALLOW"] = $rowAccBeenAllow["IS_USE"];
				$arrGroupAccAllow[] = $arrAccBeenAllow;
			}
		}
		if(sizeof($arrGroupAccAllow) > 0){
			$arrayResult['ACCOUNT_ALLOW'] = $arrGroupAccAllow;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
			
		}else{
			http_response_code(204);
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