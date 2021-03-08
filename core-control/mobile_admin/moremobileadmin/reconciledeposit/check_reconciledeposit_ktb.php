<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','reconcile_data','operate_date'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','reconciledeposit')){
		$arrayGroup = array();
		$arrayCheckReconcile = array();
		$arrayMember = array();
		$fetchTrans = $conmysql->prepare("SELECT ref_no,from_account,destination,amount,fee_amt,penalty_amt,amount_receive,operate_date,member_no
											FROM gctransaction 
											WHERE date(operate_date) = :operate_date AND result_transaction = '1' AND trans_flag = '1' AND transfer_mode = '9'");
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
		
		$arrayResult['BANK_RECONCILE'] = $dataComing["reconcile_data"];
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