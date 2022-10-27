<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepBuyShare')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGroupAccAllow = array();
		$arrayAcc = array();
		$fetchAccAllowTrans = $conoracle->prepare("SELECT gat.deptaccount_no FROM gcuserallowacctransaction gat
													LEFT JOIN gcconstantaccountdept gad ON gat.id_accountconstant = gad.id_accountconstant
													WHERE gat.member_no = :member_no and gat.is_use = '1' and gad.allow_buy_share = '1'");
		$fetchAccAllowTrans->execute([':member_no' => $payload["member_no"]]);
		while($rowAccAllow = $fetchAccAllowTrans->fetch(PDO::FETCH_ASSOC)){
			$arrayAcc[] = "'".$rowAccAllow["DEPTACCOUNT_NO"]."'";
		}
		
		if(sizeof($arrayAcc) > 0){
			$getDataBalAcc = $conoracle->prepare("SELECT dpm.deptaccount_no,dpm.deptaccount_name,dpm.DEPT_OBJECTIVE,dpt.depttype_desc,dpm.prncbal as prncbal,dpm.depttype_code
													FROM dpdeptmaster dpm LEFT JOIN dpdepttype dpt ON dpm.depttype_code = dpt.depttype_code
													WHERE dpm.deptaccount_no IN(".implode(',',$arrayAcc).") and dpm.acccont_type = '01' and dpm.deptclose_status = 0
													ORDER BY dpm.deptaccount_no ASC");
			$getDataBalAcc->execute();
			while($rowDataAccAllow = $getDataBalAcc->fetch(PDO::FETCH_ASSOC)){
				$checkSeqAmt = $cal_dep->getSequestAmount($rowDataAccAllow["DEPTACCOUNT_NO"],'WTX');
				if($checkSeqAmt["RESULT"]){
					if($checkSeqAmt["CAN_WITHDRAW"]){
						$arrAccAllow = array();
						if(file_exists(__DIR__.'/../../resource/dept-type/'.$rowDataAccAllow["DEPTTYPE_CODE"].'.png')){
							$arrAccAllow["DEPT_TYPE_IMG"] = $config["URL_SERVICE"].'resource/dept-type/'.$rowDataAccAllow["DEPTTYPE_CODE"].'.png?v='.date('Ym');
						}else{
							$arrAccAllow["DEPT_TYPE_IMG"] = null;
						}
						$arrAccAllow["DEPTACCOUNT_NO"] = $rowDataAccAllow["DEPTACCOUNT_NO"];
						$arrAccAllow["DEPTACCOUNT_NO_FORMAT"] = $lib->formataccount($rowDataAccAllow["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
						$arrAccAllow["DEPTACCOUNT_NO_FORMAT_HIDE"] = $lib->formataccount_hidden($rowDataAccAllow["DEPTACCOUNT_NO"],$func->getConstant('hidden_dep'));
						$arrAccAllow["DEPTACCOUNT_NAME"] = preg_replace('/\"/','',$rowDataAccAllow["DEPTACCOUNT_NAME"] .' '.$rowDataAccAllow["DEPT_OBJECTIVE"]);
						$arrAccAllow["DEPT_TYPE"] = $rowDataAccAllow["DEPTTYPE_DESC"];
						$arrAccAllow["BALANCE"] = $checkSeqAmt["SEQUEST_AMOUNT"] ?? $cal_dep->getWithdrawable($rowDataAccAllow["DEPTACCOUNT_NO"]);
						$arrAccAllow["BALANCE_FORMAT"] = number_format($arrAccAllow["BALANCE"],2);
						$arrGroupAccAllow[] = $arrAccAllow;
					}else{
						$arrayResult['RESPONSE_CODE'] = "WS0104";
						$arrayResult['RESPONSE_MESSAGE'] = $checkSeqAmt["SEQUEST_DESC"];
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
					}
				}else{
					$arrayResult['RESPONSE_CODE'] = $checkSeqAmt["RESPONSE_CODE"];
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
				}
			}
			$fetchShare = $conoracle->prepare("SELECT sharestk_amt FROM shsharemaster WHERE member_no = :member_no");
			$fetchShare->execute([':member_no' => $member_no]);
			$rowShare = $fetchShare->fetch(PDO::FETCH_ASSOC);
			$arrayShare = array();
			$arrayShare["SHARE_AMT"] = number_format($rowShare["SHARESTK_AMT"]*10,2);
			$arrayShare["MEMBER_NO"] = $payload["member_no"];
			if(sizeof($arrGroupAccAllow) > 0){
				$getMembType = $conoracle->prepare("SELECT MEMBCAT_CODE FROM mbmembmaster WHERE member_no = :member_no");
				$getMembType->execute([':member_no' => $member_no]);
				$rowMembType = $getMembType->fetch(PDO::FETCH_ASSOC);
				if($rowMembType["MEMBCAT_CODE"] == '10'){
					$memb_type = '01';
				}else{
					$memb_type = '02';
				}
				$getConstantShare = $conoracle->prepare("SELECT UNITSHARE_VALUE FROM SHSHARETYPE WHERE SHARETYPE_CODE = :memb_type");
				$getConstantShare->execute([':memb_type' => $memb_type]);
				$rowContShare = $getConstantShare->fetch(PDO::FETCH_ASSOC);
				$arrayResult['STEP_MOD_AMT'] = $rowContShare["UNITSHARE_VALUE"];
				$arrayResult['ACCOUNT_ALLOW'] = $arrGroupAccAllow;
				$arrayResult['SHARE'] = $arrayShare;
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0023";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');			
			}
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