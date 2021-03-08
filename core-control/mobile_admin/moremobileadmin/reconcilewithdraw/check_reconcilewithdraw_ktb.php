<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','operate_date'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','reconciledeposit')){
		$arrayGroup = array();
		$arrayCheckReconcile = array();
		$arrayMember = array();
		$fetchTrans = $conmysql->prepare("SELECT ref_no,from_account,destination,amount,fee_amt,penalty_amt,amount_receive,operate_date,member_no
											FROM gctransaction 
											WHERE date(operate_date) = :operate_date AND result_transaction = '1' AND trans_flag = '-1' AND transfer_mode = '9'");
		$fetchTrans->execute([
			':operate_date' => $dataComing['operate_date']
		]);
		$arrayGroup["COOP_RECONCILE"] = array();
		$arrayGroup["BANK_RECONCILE"] = array();
		while($rowTrans = $fetchTrans->fetch()){
			$arrTrans = array();
			$arrTrans["REF_NO"] = $rowTrans["ref_no"];
			$arrTrans["FROM_ACCOUNT"] = $rowTrans["from_account"];
			$arrTrans["DESTINATION"] = $lib->formataccount($rowTrans["destination"],$func->getConstant('dep_format'));
			$arrTrans["AMOUNT"] = number_format($rowTrans["amount"],2);
			$arrTrans["FEE_AMT"] = number_format($rowTrans["fee_amt"],2);
			$arrTrans["PENALTY_AMT"] = number_format($rowTrans["penalty_amt"],2);
			$arrTrans["AMOUNT_RECEIVE"] = number_format($rowTrans["amount_receive"],2);
			$arrTrans["OPERATE_DATE"] = $lib->convertdate($rowTrans["operate_date"],'d m Y',true);
			$arrTrans["MEMBER_NO"] = $rowTrans["member_no"];
			$arrTrans["NET_AMOUNT"] = number_format($rowTrans["amount"]+$rowTrans["fee_amt"],2);
			$arrTrans["SIMULATE_KEY"] = $rowTrans["destination"].str_replace(".","",$rowTrans["amount"]);
			$arrTrans["SIMULATE_TIME"] = date_format(date_create($rowTrans["operate_date"]),"YmdHi.s");
			
			//get member name
			if(isset($arrayMember[$rowTrans["member_no"]])){
				$arrTrans["MEMBER_FULLNAME"] = $arrayMember[$rowTrans["member_no"]]["PRENAME_SHORT"].$arrayMember[$rowTrans["member_no"]]["MEMB_NAME"]." ".$arrayMember[$rowTrans["member_no"]]["MEMB_SURNAME"];
			}else{
				$fetchMember = $conoracle->prepare("SELECT mp.prename_short,mb.memb_name,mb.memb_surname,mb.member_no
											FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
											WHERE mb.member_no = :member_no");
				$fetchMember->execute([
					":member_no" => $rowTrans["member_no"]
				]);
				while($rowMember = $fetchMember->fetch(PDO::FETCH_ASSOC)){
					$arrayMember[$rowTrans["member_no"]] = array();
					$arrayMember[$rowTrans["member_no"]]["PRENAME_SHORT"] = $rowMember["PRENAME_SHORT"];
					$arrayMember[$rowTrans["member_no"]]["MEMB_NAME"] = $rowMember["MEMB_NAME"];
					$arrayMember[$rowTrans["member_no"]]["MEMB_SURNAME"] = $rowMember["MEMB_SURNAME"];
					$arrTrans["MEMBER_FULLNAME"] = $rowMember["PRENAME_SHORT"].$rowMember["MEMB_NAME"]." ".$rowMember["MEMB_SURNAME"];
				}
			}
			
			$arrayGroup["COOP_RECONCILE"][] = $arrTrans;
		}
		
		$fetchReconcile = $conmysql->prepare("SELECT id_wtdreconcile, import_date, processing_date, processing_time, transaction_type, 
										credit_amount, fee_amount, net_amount, payer_account_no, payee_account_no, payee_account_bank_code, 
										reference1, reference2, effective_date, response_code, response_desc, transref_no, sys_ref_no, channel_id, filler 
										FROM reconcilewithdrawktb 
										WHERE effective_date = :effective_date");
		$fetchReconcile->execute([
			':effective_date' => str_replace('-','',$dataComing['operate_date'])
		]);
		
		while($rowReconcile = $fetchReconcile->fetch()){
			$arrTrans = array();
			$arrTrans["ID_WTDRECONCILE"] = $rowReconcile["id_wtdreconcile"];
			$arrTrans["IMPORT_DATE"] = $rowReconcile["import_date"];
			$arrTrans["PROCESSING_DATE"] = $rowReconcile["processing_date"];
			$arrTrans["PROCESSING_TIME"] = $rowReconcile["processing_time"];
			$arrTrans["TRANSACTION_TYPE"] = $rowReconcile["transaction_type"];
			$arrTrans["CREDIT_AMOUNT"] = $rowReconcile["credit_amount"];
			$arrTrans["FEE_AMOUNT"] = $rowReconcile["fee_amount"];
			$arrTrans["NET_AMOUNT"] = $rowReconcile["net_amount"];
			$arrTrans["PAYER_ACCOUNT_NO"] = $rowReconcile["payer_account_no"];
			$arrTrans["PAYEE_ACCOUNT_NO"] = $rowReconcile["payee_account_no"];
			$arrTrans["PAYEE_ACCOUNT_BANK_CODE"] = $rowReconcile["payee_account_bank_code"];
			$arrTrans["REFERENCE1"] = $rowReconcile["reference1"];
			$arrTrans["REFERENCE2"] = $rowReconcile["reference2"];
			$arrTrans["EFFECTIVE_DATE"] = $rowReconcile["effective_date"];
			$arrTrans["RESPONSE_CODE"] = $rowReconcile["response_code"];
			$arrTrans["RESPONSE_DESC"] = $rowReconcile["response_desc"];
			$arrTrans["TRANSREF_NO"] = $rowReconcile["transref_no"];
			$arrTrans["SYS_REF_NO"] = $rowReconcile["sys_ref_no"];
			$arrTrans["CHANNEL_ID"] = $rowReconcile["channel_id"];
			$arrTrans["FILLER"] = $rowReconcile["filler"];
			$arrayGroup["BANK_RECONCILE"][] = $arrTrans;
		}
		
		$arrayResult['BANK_RECONCILE'] = $arrayGroup["BANK_RECONCILE"];
		$arrayResult['COOP_RECONCILE'] = $arrayGroup["COOP_RECONCILE"];
		$arrayResult['RESULT'] = TRUE;
		require_once('../../../../include/exit_footer.php');
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../../include/exit_footer.php');
		
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../../include/exit_footer.php');
	
}
?>