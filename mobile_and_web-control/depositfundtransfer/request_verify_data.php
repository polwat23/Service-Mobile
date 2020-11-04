<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransactionDeposit')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$min_amount_deposit = $func->getConstant("min_amount_deposit");
		if($dataComing["amt_transfer"] >= (int) $min_amount_deposit){
			$getSumTransactionInMonth = $conoracle->prepare("SELECT NVL(SUM(DPS.DEPTSLIP_NETAMT),0) AS NETAMT FROM 
																						DPUCFRECPPAYTYPE DPP, DPDEPTSLIP DPS, DPDEPTMASTER DPM WHERE 
																						DPP.RECPPAYTYPE_CODE = DPS.RECPPAYTYPE_CODE AND
																						DPS.DEPTACCOUNT_NO = DPM.DEPTACCOUNT_NO AND
																						DPP.CHKLIMITDEPTPERSON_FLAG = 1 AND 
																						DPS.ITEM_STATUS = 1 AND
																						TO_CHAR(DPS.DEPTSLIP_DATE, 'YYYYMM') = TO_CHAR(SYSDATE,  'YYYYMM') AND
																						DPM.MEMBER_NO = :member_no");
			$getSumTransactionInMonth->execute([':member_no' => $member_no]);
			$rowSumTran = $getSumTransactionInMonth->fetch(PDO::FETCH_ASSOC);
			$amt_transfer = $dataComing["amt_transfer"] + $rowSumTran["NETAMT"];
			$getMemberType = $conoracle->prepare("SELECT MEMBER_TYPE FROM mbmembmaster WHERE member_no = :member_no");
			$getMemberType->execute([':member_no' => $member_no]);
			$rowmbType = $getMemberType->fetch(PDO::FETCH_ASSOC);
			if($rowmbType["MEMBER_TYPE"] == '1'){
				$checkLimitDeptPerMonth = $conoracle->prepare("SELECT LIMITDEPTPERSON_AMT FROM DPDEPTCONSTANT WHERE COOP_ID = '001001'");
				$checkLimitDeptPerMonth->execute();
				$rowLimitDept = $checkLimitDeptPerMonth->fetch(PDO::FETCH_ASSOC);
				if($amt_transfer >= $rowLimitDept["LIMITDEPTPERSON_AMT"]){
					$remain_amount_deposit_per_month = intval($rowLimitDept["LIMITDEPTPERSON_AMT"]) - $rowSumTran["NETAMT"];
					$arrayResult['RESPONSE_CODE'] = "WS0085";
					$arrayResult['RESPONSE_MESSAGE'] = str_replace('${remain_amount_deposit_per_month}',number_format($remain_amount_deposit_per_month,2),$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}else{
				$limit_dept = $func->getConstant("limit_deposit_asso");
				if($amt_transfer >= $limit_dept){
					$remain_amount_deposit_per_month = intval($limit_dept) - $rowSumTran["NETAMT"];
					$arrayResult['RESPONSE_CODE'] = "WS0085";
					$arrayResult['RESPONSE_MESSAGE'] = str_replace('${remain_amount_deposit_per_month}',number_format($remain_amount_deposit_per_month,2),$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}
			$fetchMemberName = $conoracle->prepare("SELECT MP.PRENAME_DESC,MB.MEMB_NAME,MB.MEMB_SURNAME 
														FROM MBMEMBMASTER MB LEFT JOIN MBUCFPRENAME MP ON MB.PRENAME_CODE = MP.PRENAME_CODE
														WHERE MB.member_no = :member_no");
			$fetchMemberName->execute([
				':member_no' => $member_no
			]);
			$rowMember = $fetchMemberName->fetch(PDO::FETCH_ASSOC);
			$account_name_th = $rowMember["PRENAME_DESC"].$rowMember["MEMB_NAME"].' '.$rowMember["MEMB_SURNAME"];
			$arrayResult['FEE_AMT'] = 0;
			$arrayResult['ACCOUNT_NAME'] = $account_name_th;
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0056";
			$arrayResult['RESPONSE_MESSAGE'] = str_replace('${min_amount_deposit}',number_format($min_amount_deposit,2),$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
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