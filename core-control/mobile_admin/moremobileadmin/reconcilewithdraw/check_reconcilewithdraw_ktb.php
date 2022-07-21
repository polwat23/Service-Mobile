<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','operate_date'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','reconciledeposit',$conoracle)){
		$arrayGroup = array();
		$arrayCheckReconcile = array();
		$arrayMember = array();
		$fetchTrans = $conoracle->prepare("SELECT ref_no,from_account,destination,amount,fee_amt,penalty_amt,amount_receive,operate_date,member_no
											FROM gctransaction 
											WHERE TO_CHAR(operate_date,'YYYY-MM-DD') = '".$dataComing['operate_date']."' 
											AND result_transaction = '1' AND trans_flag = '-1' AND transfer_mode = '9'");
		$fetchTrans->execute();
		$arrayGroup["COOP_RECONCILE"] = array();
		$arrayGroup["BANK_RECONCILE"] = array();
		while($rowTrans = $fetchTrans->fetch(PDO::FETCH_ASSOC)){
			$arrTrans = array();
			$arrTrans["REF_NO"] = $rowTrans["REF_NO"];
			$arrTrans["FROM_ACCOUNT"] = $rowTrans["FROM_ACCOUNT"];
			$arrTrans["DESTINATION"] = $lib->formataccount($rowTrans["DESTINATION"],$func->getConstant('dep_format',$conoracle));
			$arrTrans["AMOUNT"] = number_format($rowTrans["AMOUNT"],2);
			$arrTrans["FEE_AMT"] = number_format($rowTrans["FEE_AMT"],2);
			$arrTrans["PENALTY_AMT"] = number_format($rowTrans["PENALTY_AMT"],2);
			$arrTrans["AMOUNT_RECEIVE"] = number_format($rowTrans["AMOUNT_RECEIVE"],2);
			$arrTrans["OPERATE_DATE"] = $lib->convertdate($rowTrans["OPERATE_DATE"],'d m Y',true);
			$arrTrans["MEMBER_NO"] = $rowTrans["MEMBER_NO"];
			$arrTrans["NET_AMOUNT"] = number_format($rowTrans["AMOUNT"]+$rowTrans["FEE_AMT"],2);
			$arrTrans["SIMULATE_KEY"] = $rowTrans["DESTINATION"].str_replace(".","",$rowTrans["AMOUNT"]);
			$arrTrans["SIMULATE_TIME"] = date_format(date_create($rowTrans["OPERATE_DATE"]),"YmdHi.s");
			
			//get member name
			if(isset($arrayMember[$rowTrans["member_no"]])){
				$arrTrans["MEMBER_FULLNAME"] = $arrayMember[$rowTrans["MEMBER_NO"]]["PRENAME_SHORT"].$arrayMember[$rowTrans["MEMBER_NO"]]["MEMB_NAME"]." ".$arrayMember[$rowTrans["MEMBER_NO"]]["MEMB_SURNAME"];
			}else{
				$fetchMember = $conoracle->prepare("SELECT mp.prename_short,mb.memb_name,mb.memb_surname,mb.member_no
											FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
											WHERE mb.member_no = :member_no");
				$fetchMember->execute([
					":member_no" => $rowTrans["MEMBER_NO"]
				]);
				while($rowMember = $fetchMember->fetch(PDO::FETCH_ASSOC)){
					$arrayMember[$rowTrans["MEMBER_NO"]] = array();
					$arrayMember[$rowTrans["MEMBER_NO"]]["PRENAME_SHORT"] = $rowMember["PRENAME_SHORT"];
					$arrayMember[$rowTrans["MEMBER_NO"]]["MEMB_NAME"] = $rowMember["MEMB_NAME"];
					$arrayMember[$rowTrans["MEMBER_NO"]]["MEMB_SURNAME"] = $rowMember["MEMB_SURNAME"];
					$arrTrans["MEMBER_FULLNAME"] = $rowMember["PRENAME_SHORT"].$rowMember["MEMB_NAME"]." ".$rowMember["MEMB_SURNAME"];
				}
			}
			
			$arrayGroup["COOP_RECONCILE"][] = $arrTrans;
		}
		
		$fetchReconcile = $conoracle->prepare("SELECT id_wtdreconcile, import_date, processing_date, processing_time, transaction_type, 
										credit_amount, fee_amount, net_amount, payer_account_no, payee_account_no, payee_account_bank_code, 
										reference1, reference2, effective_date, response_code, response_desc, transref_no, sys_ref_no, channel_id, filler 
										FROM reconcilewithdrawktb 
										WHERE effective_date = '".preg_replace('/-/','',$dataComing["operate_date"])."'");
		$fetchReconcile->execute();
		
		while($rowReconcile = $fetchReconcile->fetch(PDO::FETCH_ASSOC)){
			$arrTrans = array();
			$arrTrans["ID_WTDRECONCILE"] = $rowReconcile["ID_WTDRECONCILE"];
			$arrTrans["IMPORT_DATE"] = $rowReconcile["IMPORT_DATE"];
			$arrTrans["PROCESSING_DATE"] = $rowReconcile["PROCESSING_DATE"];
			$arrTrans["PROCESSING_TIME"] = $rowReconcile["PROCESSING_TIME"];
			$arrTrans["TRANSACTION_TYPE"] = $rowReconcile["TRANSACTION_TYPE"];
			$arrTrans["CREDIT_AMOUNT"] = $rowReconcile["CREDIT_AMOUNT"];
			$arrTrans["FEE_AMOUNT"] = $rowReconcile["FEE_AMOUNT"];
			$arrTrans["NET_AMOUNT"] = $rowReconcile["NET_AMOUNT"];
			$arrTrans["PAYER_ACCOUNT_NO"] = $rowReconcile["PAYER_ACCOUNT_NO"];
			$arrTrans["PAYEE_ACCOUNT_NO"] = $rowReconcile["PAYEE_ACCOUNT_NO"];
			$arrTrans["PAYEE_ACCOUNT_BANK_CODE"] = $rowReconcile["PAYEE_ACCOUNT_BANK_CODE"];
			$arrTrans["REFERENCE1"] = $rowReconcile["REFERENCE1"];
			$arrTrans["REFERENCE2"] = $rowReconcile["REFERENCE2"];
			$arrTrans["EFFECTIVE_DATE"] = $rowReconcile["EFFECTIVE_DATE"];
			$arrTrans["RESPONSE_CODE"] = $rowReconcile["RESPONSE_CODE"];
			$arrTrans["RESPONSE_DESC"] = $rowReconcile["RESPONSE_DESC"];
			$arrTrans["TRANSREF_NO"] = $rowReconcile["TRANSREF_NO"];
			$arrTrans["SYS_REF_NO"] = $rowReconcile["SYS_REF_NO"];
			$arrTrans["CHANNEL_ID"] = $rowReconcile["CHANNEL_ID"];
			$arrTrans["FILLER"] = $rowReconcile["FILLER"];
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