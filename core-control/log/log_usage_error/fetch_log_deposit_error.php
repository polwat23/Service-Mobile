<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'log','logdepositerror',$conoracle)){
		$arrayGroup = array();
		$fetchLogDepositError = $conoracle->prepare("SELECT  tb.id_deptransbankerr,
															tb.member_no,
															tb.transaction_date,
															tb.sigma_key,
															tb.amt_transfer,
															tb.response_code,
															tb.response_message,
															login.device_name,
															login.channel,
															tb.is_adj,
															gba.deptaccount_no_coop
													FROM logdepttransbankerror tb
													LEFT JOIN gcuserlogin login
													ON login.id_userlogin = tb.id_userlogin 
													LEFT JOIN gcbindaccount gba ON tb.sigma_key = gba.sigma_key
													ORDER BY tb.transaction_date DESC");
		$fetchLogDepositError->execute();
		while($rowLogDepositError = $fetchLogDepositError->fetch(PDO::FETCH_ASSOC)){
			$arrLogDepositError = array();
			$arrLogDepositError["ID_DPTRANSBANKERR"] = $rowLogDepositError["ID_DEPTRANSBANKERR"];
			$arrLogDepositError["MEMBER_NO"] = $rowLogDepositError["MEMBER_NO"];
			$arrLogDepositError["CHANNEL"] = $rowLogDepositError["CHANNEL"];
			$arrLogDepositError["ATTEMPT_BIND_DATE"] =  $lib->convertdate($rowLogDepositError["TRANSACTION_DATE"],'d m Y',true); 
			$arrLogDepositError["DEVICE_NAME"] = $rowLogDepositError["DEVICE_NAME"];
			$arrLogDepositError["AMT_TRANSFER"] = $rowLogDepositError["AMT_TRANSFER"];
			$arrLogDepositError["AMT_TRANSFER_FORMAT"] = number_format($rowLogDepositError["AMT_TRANSFER"],2);			
			$arrLogDepositError["SIGMA_KEY"] = $rowLogDepositError["SIGMA_KEY"];
			$arrLogDepositError["RESPONSE_CODE"] = $rowLogDepositError["RESPONSE_CODE"];
			$arrLogDepositError["RESPONSE_MESSAGE"] = $rowLogDepositError["RESPONSE_MESSAGE"];
			if($rowLogDepositError["is_adj"] == '8'){
				$checkAdj = $conoracle->prepare("SELECT DEPTSLIP_NO FROM dpdeptslip WHERE deptaccount_no = :deptaccount_no and amt_transfer = :amt_transfer
												and deptitemtype_code = 'DTX' and to_char(deptslip_date,'YYYY-MM-DD') = :date_oper");
				$checkAdj->execute([
					':deptaccount_no' => $rowLogDepositError["DEPTACCOUNT_NO_COOP"],
					':amt_transfer' => $rowLogDepositError["AMT_TRANSFER"],
					':date_oper' => date('Y-m-d',strtotime($rowLogDepositError["TRANSACTION_DATE"]))
				]);
				$rowAdj = $checkAdj->fetch(PDO::FETCH_ASSOC);
				if(empty($rowAdj["DEPTSLIP_NO"])){
					$arrLogDepositError["IS_ADJ"] = TRUE;
					$arrLogDepositError["ADJ_STATUS_DESC"] = 'รอ ADJ ยอด';
				}else{
					$arrLogDepositError["IS_ADJ"] = FALSE;
					$arrLogDepositError["ADJ_STATUS_DESC"] = 'ADJ ยอดเรียบร้อย';
				}
			}else{
				$arrLogDepositError["IS_ADJ"] = FALSE;
				if($rowLogDepositError["is_adj"] == '1'){
					$arrLogDepositError["ADJ_STATUS_DESC"] = 'ADJ ยอดเรียบร้อย';
				}
			}
			$arrayGroup[] = $arrLogDepositError;
		}
		$arrayResult["LOG_DEPOSIT_ERROR_DATA"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		require_once('../../../include/exit_footer.php');
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../include/exit_footer.php');
		
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../include/exit_footer.php');
	
}
?>