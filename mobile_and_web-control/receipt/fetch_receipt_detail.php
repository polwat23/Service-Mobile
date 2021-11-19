<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','recv_period'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SlipInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$showSplitSlip = $func->getConstant('show_split_slip_report');
		$arrGroupDetail = array();
		$getDetailKP = $conmssqlcoop->prepare("SELECT
											coReceipt.Receipt_No,  
											coReceipt.Member_Id, 
											coReceipt.Paydate, 
											coReceipt.amount, 
											coReceipt.Type as TYPE_GROUP ,
											coReceipt.Loan_Doc_No, 
											coInterestRate_Desc.Description, 
											coReceipt.Principal, 
											coReceipt.Interest, 
											coReceipt.Stock, 
											coReceiptType.Description as TYPE_DESC, 
											coReceipt.Loan_Seq, 
											coReceipt.PrincipalBF, 
											coReceipt.Stock_OnHand, 
											coReceipt.Stock_OnHand_Value
											FROM  Cooptation.dbo.coReceipt coReceipt LEFT JOIN Cooptation.dbo.coCooptation coCooptation ON coReceipt.Member_Id=coCooptation.Member_Id
											LEFT JOIN Cooptation.dbo.coReceiptType coReceiptType ON coReceipt.Type=coReceiptType.Type
											LEFT  JOIN Cooptation.dbo.coLoanMember coLoanMember ON coReceipt.Loan_Doc_No=coLoanMember.Doc_No
											LEFT  JOIN Cooptation.dbo.coInterestRate_Desc coInterestRate_Desc ON coLoanMember.Type=coInterestRate_Desc.Type
											WHERE coReceipt.status ='2' AND coReceipt.member_id = :member_no and coReceipt.receipt_no  = :recv_period
											ORDER BY coReceipt.paydate  desc");
		$getDetailKP->execute([
			':member_no' => $member_no,
			':recv_period' => $dataComing["recv_period"]
		]);
		while($rowDetail = $getDetailKP->fetch(PDO::FETCH_ASSOC)){
			$arrDetail = array();		
			if($rowDetail["TYPE_GROUP"] == '10'){
				$arrDetail["TYPE_DESC"] = "หุ้น";
				$arrDetail["ITEM_PAYMENT"] = number_format($rowDetail["amount"],2);
				$arrDetail["ITEM_BALANCE"] = number_format($rowDetail["amount"],2);
			}else if($rowDetail["TYPE_GROUP"] == '20'){
				$arrDetail["TYPE_DESC"] = $rowDetail["TYPE_DESC"];
				$arrDetail["ITEM_PAYMENT"] = number_format($rowDetail["amount"],2);
				$arrDetail["PAY_ACCOUNT"] = $rowDetail["Loan_Doc_No"];
				$arrDetail["PRN_BALANCE"] = number_format($rowDetail["Principal"],2);
				$arrDetail["INT_BALANCE"] = number_format($rowDetail["Interest"],2);
				$arrDetail["ITEM_BALANCE"] = number_format($rowDetail["PrincipalBF"],2);
			}else{
				$arrDetail["TYPE_DESC"] = $rowDetail["TYPE_DESC"];
				$arrDetail["ITEM_PAYMENT"] = number_format($rowDetail["amount"],2);
				$arrDetail["ITEM_BALANCE"] = number_format($rowDetail["amount"],2);
			}
			
			$arrDetail["SEQ_NO"] = $rowDetail["SEQ_NO"];
			$arrGroupDetail[] = $arrDetail;
		}
		$arrayResult['SPLIT_SLIP'] = $showSplitSlip == "1" ? TRUE : FALSE;
		$arrayResult['SHOW_SLIP_REPORT'] = TRUE;
		$arrayResult['DETAIL'] = $arrGroupDetail;
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
