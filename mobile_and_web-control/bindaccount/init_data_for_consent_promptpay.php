<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'BindAccountConsent')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$fetchDataMember = $conoracle->prepare("SELECT id_card as CARD_PERSON FROM MEM_H_MEMBER WHERE account_id = :member_no");
		$fetchDataMember->execute([
			':member_no' => $member_no
		]);
		$rowDataMember = $fetchDataMember->fetch(PDO::FETCH_ASSOC);
		if(isset($rowDataMember["CARD_PERSON"])){
			$fetchConstantAllowDept = $conmysql->prepare("SELECT gat.deptaccount_no FROM gcuserallowacctransaction gat
															WHERE gat.member_no = :member_no and gat.is_use = '1'");
			$fetchConstantAllowDept->execute([
				':member_no' => $payload["member_no"]
			]);
			if($fetchConstantAllowDept->rowCount() > 0){
				$arrayDeptAllow = array();
				while($rowAllowDept = $fetchConstantAllowDept->fetch(PDO::FETCH_ASSOC)){
					$arrayDeptAllow[] = "'".$rowAllowDept["deptaccount_no"]."'";
				}
				
				$getDepositAcc = $conoracle->prepare("SELECT dp.account_no as DEPTACCOUNT_NO,dp.account_name as DEPTACCOUNT_NAME,dp.BALANCE as PRNCBAL,
													dt.ACC_DESC as DEPTTYPE_DESC 
													FROM BK_H_SAVINGACCOUNT dp LEFT JOIN BK_M_ACC_TYPE dt ON dp.ACC_TYPE = dt.ACC_TYPE
													WHERE dp.account_no IN(".implode(',',$arrayDeptAllow).") and dp.ACC_STATUS = 'O' and dp.JOIN_FLAG='N' and dp.ACC_TYPE='02' ");
				$getDepositAcc->execute();
				while($rowDepAcc = $getDepositAcc->fetch(PDO::FETCH_ASSOC)){
					$checkAccJoint = $conmysql->prepare("SELECT deptaccount_no FROM gcdeptaccountjoint WHERE deptaccount_no = :deptaccount_no and is_joint = '1'");
					$checkAccJoint->execute([':deptaccount_no' => TRIM($rowDepAcc["DEPTACCOUNT_NO"])]);
					if($checkAccJoint->rowCount() > 0){
					}else{
						$arrAccFee = array();
						$arrAccFee['ACCOUNT_NO'] = $lib->formataccount($rowDepAcc["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
						$arrAccFee['ACCOUNT_NAME'] = TRIM($rowDepAcc["DEPTACCOUNT_NAME"]);
						$arrAccFee['BALANCE'] = number_format($rowDepAcc["PRNCBAL"],2);
						$arrAccFee['DEPTTYPE_DESC'] = $rowDepAcc["DEPTTYPE_DESC"];
						$arrGrpAccFee[] = $arrAccFee;
					}
				}
				$arrayResult['REMARK_PAYFEE'] = $configError["REMARK_PAYFEE"][0][$lang_locale];
				$arrayResult['ACCOUNT_PAYFEE'] = $arrGrpAccFee;

				$arrayResult['CITIZEN_ID_FORMAT'] = $lib->formatcitizen($rowDataMember["CARD_PERSON"]);
				$arrayResult['CITIZEN_ID'] = $rowDataMember["CARD_PERSON"];
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
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
