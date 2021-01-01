<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransactionDeposit')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$min_amount_deposit = $func->getConstant("min_amount_deposit");
		if($dataComing["amt_transfer"] >= (int) $min_amount_deposit){
			/*$getAccDataDest = $conoracle->prepare("SELECT DPT.ACCOUNT_ID,DPM.WITHDRAW_COUNT,DPM.PRNCBAL,
													DPT.MINDEPT_AMT,DPT.LIMITDEPT_FLAG,DPT.LIMITDEPT_AMT,DPT.MAXBALANCE,DPT.MAXBALANCE_FLAG,
													DPM.DEPTTYPE_CODE,DPT.DEPTGROUP_CODE,DPM.WITHDRAWABLE_AMT,
													DPM.CHECKPEND_AMT,DPM.LASTCALINT_DATE
													FROM DPDEPTMASTER DPM 
													LEFT JOIN DPDEPTTYPE DPT ON DPM.DEPTTYPE_CODE = DPT.DEPTTYPE_CODE
													WHERE DPM.DEPTACCOUNT_NO = :account_no");
			$getAccDataDest->execute([':account_no' => $to_account_no]);
			$rowAccDataDest = $getAccDataDest->fetch(PDO::FETCH_ASSOC);*/
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
			require_once('../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0056";
			$arrayResult['RESPONSE_MESSAGE'] = str_replace('${min_amount_deposit}',number_format($min_amount_deposit,2),$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
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
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
	
}
?>