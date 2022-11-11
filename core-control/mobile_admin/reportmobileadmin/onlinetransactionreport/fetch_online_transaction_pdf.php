<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','depttransaction')){
		$arrayExecute = array();
		$arrayGrpAll = array();

		if(isset($dataComing["trrans_type"]) && $dataComing["trrans_type"] != ""){
			if($dataComing["trrans_type"] == "payloan"){
				$arrayExecute["trrans_type"] = "2";
			}else if($dataComing["trrans_type"] == "buyshare"){
				$arrayExecute["trrans_type"] = "3";
			}else if($dataComing["trrans_type"] == "transfer"){
				$arrayExecute["trrans_type"] = "1";
			}else if($dataComing["trrans_type"] == "receiveloan"){
				$arrayExecute["trrans_type"] = "4";
			}else if($dataComing["trrans_type"] == "exttransfer"){
				$arrayExecute["trrans_type"] = "9";
			}else if($dataComing["trrans_type"] == "withdraw"){
				$arrayExecute["trrans_type"] = "9";
			}else if($dataComing["trrans_type"] == "deposit"){
				$arrayExecute["trrans_type"] = "9";
			}else if($dataComing["trrans_type"] == "internal_tran"){
				$arrayExecute["trrans_type"] != "9";
			}else {
				$arrayExecute["trrans_type"] = "-1";
			}
		}else{
			$arrayExecute["trrans_type"] = "-1";
		}
		
		if(isset($dataComing["start_date"]) && $dataComing["start_date"] != ""){
			$arrayExecute["start_date"] = $dataComing["start_date"];
		}
		if(isset($dataComing["end_date"]) && $dataComing["end_date"] != ""){
			$arrayExecute["end_date"] = $dataComing["end_date"];
		}
		if(isset($dataComing["ref_no"]) && $dataComing["ref_no"] != ""){
			$arrayExecute["ref_no"] = $dataComing["ref_no"];
		}
		if(isset($dataComing["member_no"]) && $dataComing["member_no"] != ""){
			$arrayExecute["member_no"] = strtolower($lib->mb_str_pad($dataComing["member_no"]));
		}
		$dept_count10  = 0; 
		$inside_dept10 = 0;
		$sum_amout_share = 0;
		$fetchReconcile = $conmysql->prepare("SELECT ref_no,trans_flag,transaction_type_code,from_account,destination,operate_date,amount,transfer_mode,
														penalty_amt,fee_amt,amount_receive,result_transaction,member_no
														FROM gctransaction
														WHERE (".
														($dataComing["trrans_type"] != "internal_tran" ? 
														"(transfer_mode ".($dataComing["trrans_type"] == "transaction" ? "!=" : "=")." :trrans_type)" : null)."
														".($dataComing["trrans_type"] == "internal_tran" ? 
														"(transfer_mode != '9')" : null)."
														".($dataComing["trrans_type"] == "withdraw" ? 
														"and (transaction_type_code = 'WIM' and trans_flag = '-1')" : null)."
														".($dataComing["trrans_type"] == "deposit" ? 
														"and (transaction_type_code = 'DIM' and trans_flag = '1')" : null)."
														".($dataComing["trrans_type"] == "payloan" ? 
														"or (transfer_mode = '9' and transaction_type_code = 'WFS')" : null)."
														".($dataComing["trrans_type"] == "receiveloan" ? 
														"or (transfer_mode = '9' and transaction_type_code = 'DAP')" : null).")
														".(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? 
														"and operate_date >= :start_date" : null)."
														".(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? 
														"and operate_date <= :end_date" : null)." 
														".(isset($dataComing["ref_no"]) && $dataComing["ref_no"] != "" ? 
														"and ref_no <= :ref_no" : null)." 
														".(isset($dataComing["member_no"]) && $dataComing["member_no"] != "" ? 
														"and member_no = :member_no" : null)." ORDER BY operate_date DESC");
														
		$fetchReconcile->execute($arrayExecute);

		$summary = 0;
		$formatDept = $func->getConstant('dep_format');
		$arrayLoanExternal = array();
		$arrayLoanExternalList = array();
		$arrayLoanInside = array();
		$arrayLoanInsideList = array();
		while($rowRecon = $fetchReconcile->fetch(PDO::FETCH_ASSOC)){
			$arrayRecon = array();
			
			$getMemName = $conoracle->prepare("select memb_name ,memb_surname  from mbmembmaster where member_no = :member_no");
			$getMemName->execute([
				':member_no' => $rowRecon["member_no"]
			]);
			$rowName = $getMemName->fetch(PDO::FETCH_ASSOC);
			$arrayRecon["FULL_NAME"] = $rowName["MEMB_NAME"] .' '. $rowName["MEMB_SURNAME"];
			
			$arrayRecon["TRANSFER_MODE"] = $rowRecon["transfer_mode"];
			$arrayRecon["TRANSACTION_TYPE_CODE"] = $rowRecon["transaction_type_code"];
			$arrayRecon["FROM_ACCOUNT_FORMAT"] = $rowRecon["from_account"];
			$arrayRecon["FROM_ACCOUNT"] = $rowRecon["from_account"];
			$arrayRecon["DESTINATION_FORMAT"] = (isset($dataComing["destination"]) && $dataComing["destination"] != "") ?
											$rowRecon["destination"] : null;
			$arrayRecon["REF_NO"] = $rowRecon["ref_no"];
			$arrayRecon["DESTINATION"] = $rowRecon["destination"];
			$arrayRecon["TRANS_FLAG"] = $rowRecon["trans_flag"];
			$arrayRecon["OPERATE_DATE"] = $lib->convertdate($rowRecon["operate_date"],'d m Y',true);
			$arrayRecon["AMOUNT"] = number_format($rowRecon["amount"],2);
			$arrayRecon["PENALTY_AMT"] = number_format($rowRecon["penalty_amt"],2);
			$arrayRecon["FEE_AMT"] = number_format($rowRecon["fee_amt"],2);
			$arrayRecon["RESULT_FEEAMT"] = $rowRecon["fee_amt"];
			$arrayRecon["RESULT_TRANSACTION"] = $rowRecon["result_transaction"];
			$arrayRecon["MEMBER_NO"] = $rowRecon["member_no"];
			$arrayRecon["RECEIVE_AMT"] = number_format($rowRecon["amount_receive"],2);
			if($rowRecon["transfer_mode"] == '1'){
				$arrayRecon["TRANSFER_TYPE"] = 'ธุรกรรมภายใน';
			} else if($rowRecon["transfer_mode"] == '2' || ($rowRecon["transfer_mode"] == '9' && $rowRecon["transaction_type_code"] == 'WFS')){
				$arrayRecon["TRANSFER_TYPE"] = 'ชำระหนี้';
			} else if($rowRecon["transfer_mode"] == '3'){
				$arrayRecon["TRANSFER_TYPE"] = 'ซื้อหุ้น';
			} else if($rowRecon["transfer_mode"] == '4' || ($rowRecon["transfer_mode"] == '9' && $rowRecon["transaction_type_code"] == 'DAP')){
				$arrayRecon["TRANSFER_TYPE"] = 'จ่ายเงินกู้';
			} else if($rowRecon["transfer_mode"] == '9'){
				$arrayRecon["TRANSFER_TYPE"] = 'ธุรกรรมภายนอก';
			} else if($rowRecon["transfer_mode"] == '9' && ($rowRecon["trans_flag"] == '-1' && $rowRecon["transaction_type_code"] == 'WIM')){
				$arrayRecon["TRANSFER_TYPE"] = 'ถอน';
			} else if($rowRecon["transfer_mode"] == '9' && ($rowRecon["trans_flag"] == '1' && $rowRecon["transaction_type_code"] == 'DIM')){
				$arrayRecon["TRANSFER_TYPE"] = 'ฝาก';
			}
			
			if($rowRecon["transfer_mode"] == '9'){
				if($rowRecon["transaction_type_code"] == 'WTX'){//ฝากภายนอกธนาคาร
					$getDepttype = $conoracle->prepare("SELECT depttype_code FROM dpdeptmaster  WHERE deptaccount_no = :destination");
					$getDepttype->execute([
						':destination' => $rowRecon["destination"]
					]);
					$rowDepttype = $getDepttype->fetch(PDO::FETCH_ASSOC);
					$deposit_external_10 += $rowRecon["amount"];
					$summary_feeamt_withdraw += ($rowRecon["fee_amt"] ?? 0);
					$dept_count10++;
				}else if($rowRecon["transaction_type_code"] == 'DTX'){//ฝากภายนอกออมทรัพย์
					$getDepttype = $conoracle->prepare("SELECT depttype_code FROM dpdeptmaster  WHERE deptaccount_no = :destination");
					$getDepttype->execute([
						':destination' => $rowRecon["destination"]
					]);
					$rowDepttype = $getDepttype->fetch(PDO::FETCH_ASSOC);
					//$deposit_external_20 += $rowRecon["amount"];
					if($rowDepttype["DEPTTYPE_CODE"] =='01'){
						$deposit_external_20 += $rowRecon["amount"];
						$deposit20++;
						$dept_count20++;
					}else if($rowDepttype["DEPTTYPE_CODE"] =='02'){
						$deposit_external_30 += $rowRecon["amount"];
						$deposit30++;
						$dept_count30++;
					}else if($rowDepttype["DEPTTYPE_CODE"] =='07'){
						$deposit_external_70 += $rowRecon["amount"];
						$deposit70++;
						$dept_count70++;
					}	
					$summary_feeamt_deposit += ($rowRecon["fee_amt"] ?? 0);
				}
				
				if($rowRecon["transaction_type_code"] == 'WTX'){//ถอนภายนอกออมทรัพย์
					$getDepttype = $conoracle->prepare("SELECT depttype_code FROM dpdeptmaster  WHERE deptaccount_no = :destination");
					$getDepttype->execute([
						':destination' => $rowRecon["from_account"]
					]);
					$rowDepttype = $getDepttype->fetch(PDO::FETCH_ASSOC);

					if($rowDepttype["DEPTTYPE_CODE"] =='01'){
						$withdraw_external_20 += $rowRecon["amount"]  + $rowRecon["fee_amt"];
						$withdraw20++;
					}else if($rowDepttype["DEPTTYPE_CODE"] =='02'){
						$withdraw_external_30 += $rowRecon["amount"]  + $rowRecon["fee_amt"];
						$withdraw30++;
					}else if($rowDepttype["DEPTTYPE_CODE"] =='07'){
						$withdraw_external_70 += $rowRecon["amount"]  + $rowRecon["fee_amt"];
						$withdraw70++;
					}					
				}else if($rowRecon["transaction_type_code"] == 'DTX'){//ถอนภายนอกธนาคาร
					$getDepttype = $conoracle->prepare("SELECT depttype_code FROM dpdeptmaster  WHERE deptaccount_no = :destination");
					$getDepttype->execute([
						':destination' => $rowRecon["destination"]
					]);
					$rowDepttype = $getDepttype->fetch(PDO::FETCH_ASSOC);
					$withdraw_external_10 += $rowRecon["amount"];
					$withdraw10++;
				}else if($rowRecon["transaction_type_code"] == 'WIM'){ //ถอนภายนอก
					$getDepttype = $conoracle->prepare("SELECT depttype_code FROM dpdeptmaster  WHERE deptaccount_no = :from_account");
					$getDepttype->execute([
						':from_account' => $rowRecon["from_account"]
					]);
					$rowDepttype = $getDepttype->fetch(PDO::FETCH_ASSOC);
					if($rowDepttype["DEPTTYPE_CODE"] =='01'){
						$withdraw_external_20 += $rowRecon["amount"];
						$withdraw20++;
					}else if($rowDepttype["DEPTTYPE_CODE"] =='02'){
						$withdraw_external_30 += $rowRecon["amount"];
						$withdraw30++;
					}else if($rowDepttype["DEPTTYPE_CODE"] =='07'){
						$withdraw_external_70 += $rowRecon["amount"];
						$withdraw70++;
					}					
				}else if($rowRecon["transaction_type_code"] == 'WFS'){ //โอนชำระหนี้
					$getLontype = $conoracle->prepare("SELECT lty.loangroup_code, lty.loantype_desc, lty.loantype_code FROM lncontmaster ln LEFT JOIN lnloantype lty ON ln.loantype_code = lty.loantype_code  WHERE ln.loancontract_no = :destination");
					$getLontype->execute([
						':destination' => $rowRecon["destination"]
					]);
					$rowLontype = $getLontype->fetch(PDO::FETCH_ASSOC);
					
					$getRePayLoan = $conmysql->prepare("SELECT amount, fee_amt, penalty_amt, principal, interest FROM gcrepayloan WHERE ref_no = :ref_no");
					$getRePayLoan->execute([
						':ref_no' => $rowRecon["ref_no"]
					]);
					$rowRePayLoan = $getRePayLoan->fetch(PDO::FETCH_ASSOC);
					if(isset($arrayLoanInsideList[$rowLontype["LOANTYPE_CODE"]])){
						$arrayLoanInsideList[$rowLontype["LOANTYPE_CODE"]]["PRN_AMT"] += ($rowRePayLoan["principal"] ?? 0);
						$arrayLoanInsideList[$rowLontype["LOANTYPE_CODE"]]["INT_AMT"] += ($rowRePayLoan["interest"] ?? 0);
						$arrayLoanInsideList[$rowLontype["LOANTYPE_CODE"]]["COUNT"]++;
						$getloan_external_01 += (($rowRePayLoan["principal"] ?? 0)+($rowRePayLoan["interest"] ?? 0));
						$summary_feeamt_deposit += ($rowRecon["fee_amt"] ?? 0);
						$withdraw_external_10 += $rowRecon["amount"];
						$withdraw10++;
					}else{
						$arrayLoanInside[] = $rowLontype["LOANTYPE_CODE"];
						$arrData = array();
						$arrData["LOANTYPE_DESC"] = $rowLontype["LOANTYPE_DESC"];
						$arrData["PRN_AMT"] = $rowRePayLoan["principal"] ?? 0;
						$arrData["INT_AMT"] = $rowRePayLoan["interest"] ?? 0;
						$arrData["COUNT"] = 1;
						$arrayLoanInsideList[$rowLontype["LOANTYPE_CODE"]] = $arrData;
						$getloan_external_01 += (($rowRePayLoan["principal"] ?? 0)+($rowRePayLoan["interest"] ?? 0));
						$summary_feeamt_deposit += ($rowRecon["fee_amt"] ?? 0);
						$withdraw_external_10 += $rowRecon["amount"];
						$withdraw10++;
					}
					if($rowLontype["LOANGROUP_CODE"] == '01'){ //ชำระหนี้ ฉฉ
						$payment_external_01 += $rowRecon["amount"];
						$prn_external_01 += $rowRePayLoan["principal"];
						$int_external_01 += $rowRePayLoan["interest"];
						$payment01++;
					}else if($rowLontype["LOANGROUP_CODE"] == '02'){//ชำระหนี้ สามัญ
						$payment_external_02 += $rowRecon["amount"];
						$prn_external_02 += $rowRePayLoan["principal"];
						$int_external_02 += $rowRePayLoan["interest"];
						$payment02++;
					}else if($rowLontype["LOANGROUP_CODE"] == '03'){//ชำระหนี้ พิเศษ
						$payment_external_03 += $rowRecon["amount"];
						$prn_external_03 += $rowRePayLoan["principal"];
						$int_external_03 += $rowRePayLoan["interest"];
						$payment03++;
					}
					
				}else if($rowRecon["transaction_type_code"] == 'DAP'){ //จ่ายเงินกู้
					$getLontype = $conoracle->prepare("SELECT lty.loangroup_code FROM lncontmaster ln LEFT JOIN lnloantype lty ON ln.loantype_code = lty.loantype_code  WHERE ln.loancontract_no = :from_account");
					$getLontype->execute([
						':from_account' => $rowRecon["from_account"]
					]);
					$rowLontype = $getLontype->fetch(PDO::FETCH_ASSOC); 
					if($rowLontype["LOANGROUP_CODE"] == '01'){ //ชำระหนี้ ฉฉ
						$getloan_external_01 += $rowRecon["amount"];
						$getloan01++;
					}	
				}
			}else{
				if($rowRecon["transaction_type_code"] == 'WIM'){//ฝากภายใน	
					$getDepttype = $conoracle->prepare("SELECT depttype_code FROM dpdeptmaster  WHERE deptaccount_no = :destination");
					$getDepttype->execute([
						':destination' => $rowRecon["destination"]
					]);
					$rowDepttype = $getDepttype->fetch(PDO::FETCH_ASSOC);
					if($rowDepttype["DEPTTYPE_CODE"] =='01'){
						$deposit_inside_10 += $rowRecon["amount"];
						$inside_dept10 ++;
					}else if($rowDepttype["DEPTTYPE_CODE"] == '02'){
						$deposit_inside_20 += $rowRecon["amount"];
						$inside_dept20++;					
					}else if($rowDepttype["DEPTTYPE_CODE"] == '07'){
						$deposit_inside_70 += $rowRecon["amount"];
						$inside_dept70++;					
					}
				}
				if($rowRecon["transaction_type_code"] == 'WIM'){ //ถอนภายใน
					$getDepttype = $conoracle->prepare("SELECT depttype_code FROM dpdeptmaster  WHERE deptaccount_no = :from_account");
					$getDepttype->execute([
						':from_account' => $rowRecon["from_account"]
					]);
					$rowDepttype = $getDepttype->fetch(PDO::FETCH_ASSOC);
					if($rowDepttype["DEPTTYPE_CODE"] =='01'){
						$withdraw_inside_10 += $rowRecon["amount"];
						$inside_withdraw10++;
					}else if($rowDepttype["DEPTTYPE_CODE"] =='02'){
						$withdraw_inside_20 += $rowRecon["amount"];
						$inside_withdraw20++;
					}else if($rowDepttype["DEPTTYPE_CODE"] == '07'){
						$withdraw_inside_70 += $rowRecon["amount"];
						$inside_withdraw70++;		
					}			
				}else if($rowRecon["transaction_type_code"] == 'WFS'){ //โอนชำระหนี้/ซื้อหุ้นภายใน
					$getLontype = $conoracle->prepare("SELECT lty.loangroup_code, lty.loantype_desc, lty.loantype_code FROM lncontmaster ln LEFT JOIN lnloantype lty ON ln.loantype_code = lty.loantype_code  WHERE ln.loancontract_no = :destination");
					$getLontype->execute([
						':destination' => $rowRecon["destination"]
					]);
					$rowLontype = $getLontype->fetch(PDO::FETCH_ASSOC);
					$getRePayLoan = $conmysql->prepare("SELECT amount, fee_amt, penalty_amt, principal, interest FROM gcrepayloan WHERE ref_no = :ref_no");
					$getRePayLoan->execute([
						':ref_no' => $rowRecon["ref_no"]
					]);
					$rowRePayLoan = $getRePayLoan->fetch(PDO::FETCH_ASSOC);
					//ประเภทเงินฝาก
					$getDept = $conoracle->prepare("SELECT depttype_code FROM dpdeptmaster  WHERE deptaccount_no = :from_account");
					$getDept->execute([
						':from_account' => $rowRecon["from_account"]
					]);
					$rowDept= $getDept->fetch(PDO::FETCH_ASSOC);
					
					if(isset($rowLontype["LOANTYPE_CODE"])){
						//ชำระหนี้
						$arrayLoanExternalList[$rowLontype["LOANTYPE_CODE"]]["PRN_AMT"] += ($rowRePayLoan["principal"] ?? 0);
						$arrayLoanExternalList[$rowLontype["LOANTYPE_CODE"]]["INT_AMT"] += ($rowRePayLoan["interest"] ?? 0);
						$arrayLoanExternalList[$rowLontype["LOANTYPE_CODE"]]["COUNT"]++;
						$arrayLoanExternalList[$rowLontype["LOANTYPE_CODE"]]["LOANTYPE_DESC"]=$rowLontype["LOANTYPE_DESC"];
					}else{
						//ซื้อหุ้น
						$sum_amout_share += $rowRecon["amount"];
						$arrayLoanExternal[] = $rowLontype["LOANTYPE_CODE"];
						
						$arrData["LOANTYPE_DESC"] = 'ซื้อหุ้น';
						$arrData["PRN_AMT"] = $sum_amout_share;
						$arrData["INT_AMT"] =  0;
						$arrData["COUNT"] ++;
						$arrayLoanExternalList[$rowLontype["LOANTYPE_CODE"]] = $arrData;
						if($rowDept["DEPTTYPE_CODE"] =='01'){ 
							$withdraw_inside_10 += $rowRecon["amount"];
					
						}else if($rowDept["DEPTTYPE_CODE"] =='02'){
							$withdraw_inside_20 += $rowRecon["amount"];
							
						}else if($rowDept["DEPTTYPE_CODE"] =='07'){
							$withdraw_inside_70 += $rowRecon["amount"];
						}
					
					}
					if($rowLontype["LOANGROUP_CODE"] == '01'){ //ชำระหนี้ ฉฉ
						$payment_inside_01 += $rowRecon["amount"];
						$prn_inside_01 += $rowRePayLoan["principal"];
						$int_inside_01 += $rowRePayLoan["interest"];
						$inside_payment01++;
					}else if($rowLontype["LOANGROUP_CODE"] == '02'){//ชำระหนี้ สามัญ
						$payment_inside_02 += $rowRecon["amount"];
						$prn_inside_02 += $rowRePayLoan["principal"];
						$int_inside_02 += $rowRePayLoan["interest"];
						$inside_payment02++;
					}else if($rowLontype["LOANGROUP_CODE"] == '03'){//ชำระหนี้ พิเศษ
						$payment_inside_03 += $rowRecon["amount"];
						$prn_inside_03 += $rowRePayLoan["principal"];
						$int_inside_03 += $rowRePayLoan["interest"];
						$inside_payment03++;
					}
							
					//************* ชำระหนี้ฝั่งถอน  ถอนไปชำระหนี้*****************
					$getWithdraw = $conoracle->prepare("SELECT depttype_code FROM dpdeptmaster  WHERE deptaccount_no = :from_account");
					$getWithdraw->execute([
						':from_account' => $rowRecon["from_account"]
					]);
					$rowWithdraw= $getWithdraw->fetch(PDO::FETCH_ASSOC);
					if($rowWithdraw["DEPTTYPE_CODE"] =='01'){ 
						$payment10 = ($payment_inside_01 ?? 0) + ($payment_inside_02 ?? 0) + ($payment_inside_03 ?? 0);
						$inside_withdraw10++;
					}else if($rowWithdraw["DEPTTYPE_CODE"] =='02'){
						$payment20 = ($payment_inside_01 ?? 0) + ($payment_inside_02 ?? 0) + ($payment_inside_03 ?? 0);
						$inside_withdraw20++;
					}else if($rowWithdraw["DEPTTYPE_CODE"] =='07'){
						$payment70 = ($payment_inside_07 ?? 0) + ($payment_inside_07 ?? 0) + ($payment_inside_07 ?? 0);
						$inside_withdraw70++;
					}		
				}else if($rowRecon["transaction_type_code"] == 'DAP'){ //จ่ายเงินกู้
					$getLontype = $conoracle->prepare("SELECT lty.loangroup_code FROM lncontmaster ln LEFT JOIN lnloantype lty ON ln.loantype_code = lty.loantype_code  WHERE ln.loancontract_no = :from_account");
					$getLontype->execute([
						':from_account' => $rowRecon["from_account"]
					]);
					$rowLontype = $getLontype->fetch(PDO::FETCH_ASSOC); 
					if($rowLontype["LOANGROUP_CODE"] == '01'){ //ชำระหนี้ ฉฉ
						$getloan_inside_01 += $rowRecon["amount"];
						$inside_getloan01++;
						//$inside_dept10++;
						
						$getDept = $conoracle->prepare("SELECT depttype_code FROM dpdeptmaster  WHERE deptaccount_no = :destination");
						$getDept->execute([
							':destination' => $rowRecon["destination"]
						]);
						$rowDept= $getDept->fetch(PDO::FETCH_ASSOC);
						if($rowDept["DEPTTYPE_CODE"] =='01'){ 
							$depositinside_10 = ($getloan_inside_01 ?? 0);
							$inside_dept10++;
						}else if($rowDept["DEPTTYPE_CODE"] =='02'){
							$depositinside_20 += ($getloan_inside_01 ?? 0);
							$inside_dept20++;
						}else if($rowDept["DEPTTYPE_CODE"] =='07'){
							$depositinside_70 += ($getloan_inside_01 ?? 0);
							$inside_dept70++;
						}		
					}					
				}
			}
			
			if( $rowRecon["result_transaction"]=='1'){
				$summary += $rowRecon["amount"];
			}else{
				$summary += 0;
			}
			
			$summary_feeamt += $rowRecon["fee_amt"];
			$penalty_amt += $rowRecon["penalty_amt"];	
			$arrayGrpAll[] = $arrayRecon;
		}
		$withdraw_inside_10  = ($withdraw_inside_10 ?? 0) + ($payment10 ?? 0); //ถอนเงินชำระหนี้
		$withdraw_inside_20  = ($withdraw_inside_20 ?? 0) + ($payment20 ?? 0);	
		
		$deposit_inside_10 = ($deposit_inside_10 ?? 0) + ($depositinside_10 ?? 0); //จ่ายเงิยกู้
		$deposit_inside_20 = ($deposit_inside_20 ?? 0) + ($depositinside_20 ?? 0);
		//รวมโอนภายนอก
		$sum_deposit_external = ($deposit_external_10 ?? 0)  + ($deposit_external_20 ?? 0)  + ($deposit_external_30 ?? 0)  + ($deposit_external_70 ?? 0) + ($payment_external_01 ?? 0) + ($payment_external_02 ?? 0) + ($payment_external_03 ?? 0);
		$sum_withdraw_external = ($withdraw_external_10 ?? 0) + ($withdraw_external_20 ?? 0) + ($withdraw_external_30 ?? 0) + ($withdraw_external_70 ?? 0);  // + ($getloan_external_01 ?? 0);

		//รวมโอนภายใน
		$sum_deposit_inside = ($deposit_inside_10 ?? 0) + ($deposit_inside_20 ?? 0) + ($deposit_inside_70 ?? 0) + ($payment_inside_01 ?? 0) + ($payment_inside_02 ?? 0) + ($payment_inside_03 ?? 0) + ($sum_amout_share??0);
		$sum_withdraw_inside = ($withdraw_inside_10 ?? 0) + ($withdraw_inside_20 ?? 0) + ($withdraw_inside_70 ?? 0) + ($getloan_inside_01 ?? 0) ;
		
		$arrayResult['SUMMARY_FEEAMT'] = number_format($summary_feeamt,2);
		$arrayResult['SUMMARY_FEEAMT_DEPOSIT'] = number_format($summary_feeamt_deposit,2);
		$arrayResult['SUMMARY_FEEAMT_WITHDRAW'] = number_format($summary_feeamt_withdraw,2);
		$arrayResult['PENALTY_AMT'] = number_format($penalty_amt,2);
		$arrayResult['SUM_DEPOSIT_INSIDE'] = number_format($sum_deposit_inside,2);
		$arrayResult['SUM_WITHDRAW_INSIDE'] = number_format($sum_withdraw_inside,2); 
		$arrayResult['DEPOSIT_INSIDE_10'] = number_format($deposit_inside_10  ?? '0',2);
		$arrayResult['INSIDE_DEPT10'] = $inside_dept10 ?? 0;
		$arrayResult['DEPOSIT_INSIDE_20'] = number_format($deposit_inside_20 ?? '0',2);
		$arrayResult['INSIDE_DEPT20'] = $inside_dept20 ?? 0;
		$arrayResult['DEPOSIT_INSIDE_70'] = number_format($deposit_inside_70 ?? '0',2);
		$arrayResult['INSIDE_DEPT70'] = $inside_dept70 ?? 0;
		$arrayResult['WITHDRAW_INSIDE_10'] = number_format($withdraw_inside_10 ?? '0',2);
		$arrayResult['INSIDE_WITHDRAW10'] = $inside_withdraw10 ?? 0;
		$arrayResult['WITHDRAW_INSIDE_20'] = number_format($withdraw_inside_20 ?? '0',2);
		$arrayResult['INSIDE_WITHDRAW20'] = $inside_withdraw20 ?? 0;
		$arrayResult['WITHDRAW_INSIDE_70'] = number_format($withdraw_inside_70 ?? '0',2);
		$arrayResult['INSIDE_WITHDRAW70'] = $inside_withdraw70 ?? 0;
		$arrayResult['PAYMENT_INSIDE_01'] = number_format($payment_inside_01 ?? '0',2);
		$arrayResult['INSIDE_PAYMENT01'] = $inside_payment01 ?? 0;
		$arrayResult['PAYMENT_INSIDE_02'] = number_format($payment_inside_02 ?? '0',2);
		$arrayResult['INSIDE_PAYMENT02'] = $inside_payment02 ?? 0;
		$arrayResult['PAYMENT_INSIDE_03'] = number_format($payment_inside_03 ?? '0',2);
		$arrayResult['INSIDE_PAYMENT03'] = $inside_payment03 ?? 0;
		$arrayResult['PRN_INSIDE_01'] = number_format($prn_inside_01 ?? '0',2);
		$arrayResult['INT_INSIDE_01'] = number_format($int_inside_01 ?? '0',2);
		$arrayResult['PRN_INSIDE_02'] = number_format($prn_inside_02 ?? '0',2);
		$arrayResult['INT_INSIDE_02'] = number_format($int_inside_02 ?? '0',2);
		$arrayResult['PRN_INSIDE_03'] = number_format($prn_inside_03 ?? '0',2);
		$arrayResult['INT_INSIDE_03'] = number_format($int_inside_03 ?? '0',2);
		
		$arrayResult['GETLOAN_INSIDE_01'] = number_format($getloan_inside_01 ?? '0',2);
		$arrayResult['INSIDE_GETLOAN01'] = $inside_getloan01 ?? 0;

		$arrayResult['SUM_DEPOSIT_EXTERNAL'] = number_format($sum_deposit_external,2);
		$arrayResult['SUM_WITHDRAW_EXTERNAL'] = number_format($sum_withdraw_external,2);
		$arrayResult['DEPOSIT_EXTERNAL_10'] = number_format($deposit_external_10 ?? '0',2);
		$arrayResult['DEPT_COUNT10'] = $dept_count10 ?? 0;
		$arrayResult['DEPOSIT_EXTERNAL_20'] = number_format($deposit_external_20 ?? '0',2);
		$arrayResult['DEPT_COUNT20'] = $dept_count20 ?? 0;
		$arrayResult['DEPOSIT_EXTERNAL_30'] = number_format($deposit_external_30 ?? '0',2);
		$arrayResult['DEPT_COUNT30'] = $dept_count30 ?? 0;
		$arrayResult['DEPOSIT_EXTERNAL_70'] = number_format($deposit_external_70 ?? '0',2);
		$arrayResult['DEPT_COUNT70'] = $dept_count70 ?? 0;
		$arrayResult['WITHDRAW_EXTERNAL_10'] = number_format($withdraw_external_10 ?? '0',2);
		$arrayResult['WITHDRAW10'] = $withdraw10 ?? 0;
		$arrayResult['WITHDRAW_EXTERNAL_20'] = number_format($withdraw_external_20 ?? '0',2);
		$arrayResult['WITHDRAW20'] = $withdraw20 ?? 0;
		$arrayResult['WITHDRAW_EXTERNAL_30'] = number_format($withdraw_external_30 ?? '0',2);
		$arrayResult['WITHDRAW30'] = $withdraw30 ?? 0;
		$arrayResult['WITHDRAW_EXTERNAL_70'] = number_format($withdraw_external_70 ?? '0',2);
		$arrayResult['WITHDRAW70'] = $withdraw70 ?? 0;
		$arrayResult['PAYMENT_EXTERNAL_01'] = number_format($payment_external_01 ?? '0',2);
		$arrayResult['PAYMENT01'] = $payment01 ?? 0;
		$arrayResult['PAYMENT_EXTERNAL_02'] = number_format($payment_external_02 ?? '0',2);
		$arrayResult['PAYMENT02'] = $payment02 ?? 0;
		$arrayResult['PAYMENT_EXTERNAL_03'] = number_format($payment_external_03 ?? '0',2);
		$arrayResult['PAYMENT03'] = $payment03 ?? 0;
		$arrayResult['PRN_EXTERNAL_01'] = number_format($prn_external_01 ?? '0',2);
		$arrayResult['INT_EXTERNAL_01'] = number_format($int_external_01 ?? '0',2);
		$arrayResult['PRN_EXTERNAL_02'] = number_format($prn_external_02 ?? '0',2);
		$arrayResult['INT_EXTERNAL_02'] = number_format($int_external_02 ?? '0',2);
		$arrayResult['PRN_EXTERNAL_03'] = number_format($prn_external_03 ?? '0',2);
		$arrayResult['INT_EXTERNAL_03'] = number_format($int_external_03 ?? '0',2);
		
		$arrayResult['GETLOAN_EXTERNAL_01'] = number_format($getloan_external_01 ?? '0',2);
		$arrayResult['GETLOAN01'] = $getloan01 ?? 0;
		$arrayResult['SUM_AMT_EXPORT'] = number_format($sum_deposit_external+$sum_withdraw_external+$sum_deposit_inside+$sum_withdraw_inside ?? '0',2);

		$arrayResult['SUMMARY'] = $summary;
		$arrayResult['SUMMARY_FORMAT'] = number_format($summary,2);
		$arrayResult['DEPT_TRANSACTION'] = $arrayGrpAll;
		$arrayResult['arrayLoanInsideList'] = $arrayLoanInsideList;
		$arrayResult['dataList'] = $arrayLoanExternalList;
		$arrayResult['data'] = $data;
		
		//ภายนอก
		$arrExternalList = array();
		//ฝาก
		$arrExternalList[] = "รวมฝาก : ".number_format($sum_deposit_external,2)." บาท";
		if($dept_count10 > 0){
			$arrExternalList[] = "- ธนาคาร : ".($dept_count10 ?? 0)." รายการ ".number_format($deposit_external_10 ?? '0',2)." บาท";
		}
		if($dept_count20 > 0){
			$arrExternalList[] = "- ออมทรัพย์ : ".($dept_count20 ?? 0)." รายการ ".number_format($deposit_external_20 ?? '0',2)." บาท";
		}
		if($dept_count30 > 0){
			$arrExternalList[] = "- ออมทรัพย์พิเศษ : ".($dept_count30 ?? 0)." รายการ ".number_format($deposit_external_30 ?? '0',2)." บาท";
		}
		if($dept_count70 > 0){
			$arrExternalList[] = "- ออมทรัพย์พิเศษเกษียณอายุ : ".($dept_count70 ?? 0)." รายการ ".number_format($deposit_external_70 ?? '0',2)." บาท";
		}
		foreach($arrayLoanInsideList as $value){
			$arrExternalList[] = "- ".$value["LOANTYPE_DESC"]." : ".($value["COUNT"] ?? 0)." รายการ ".number_format($value["PRN_AMT"] ?? '0',2)." / ".number_format($value["INT_AMT"] ?? '0',2)." บาท";
		}
		//ถอน
		$arrExternalList[] = "รวมถอน : ".number_format($sum_withdraw_external,2)." บาท";
		if($withdraw10 > 0){
			$arrExternalList[] = "- ธนาคาร : ".($withdraw10 ?? 0)." รายการ ".number_format($withdraw_external_10 ?? '0',2)." บาท";
		}
		if($withdraw20 > 0){
			$arrExternalList[] = "- ออมทรัพย์ : ".($withdraw20 ?? 0)." รายการ ".number_format($withdraw_external_20 ?? '0',2)." บาท";
		}
		if($withdraw30 > 0){
			$arrExternalList[] = "- ออมทรัพย์พิเศษ : ".($withdraw30 ?? 0)." รายการ ".number_format($withdraw_external_30 ?? '0',2)." บาท";
		}
		if($withdraw70 > 0){
			$arrExternalList[] = "- ออมทรัพย์พิเศษเกษียณอายุ : ".($withdraw70 ?? 0)." รายการ ".number_format($withdraw_external_70 ?? '0',2)." บาท";
		}
		
		
		//ค่าธรรมเนียม
		if($summary_feeamt_withdraw > 0){
			$arrExternalList[] = "ค่าธรรมเนียมถอน : ".number_format($summary_feeamt_withdraw ?? '0',2)." บาท";
		}
		if($summary_feeamt_deposit > 0){
			$arrExternalList[] = "ค่าธรรมเนียมฝาก : ".number_format($summary_feeamt_deposit ?? '0',2)." บาท";
		}
		if($penalty_amt > 0){
			$arrExternalList[] = "รวมค่าปรับ : ".number_format($penalty_amt ?? '0',2)." บาท";
		}
		$arrayResult['EXTERNAL_LIST'] = $arrExternalList;
		
		
		
		//ภายใน
		$arrInsideList = array();
		//ฝาก
		$arrInsideList[] = "รวมฝาก : ".number_format($sum_deposit_inside,2)." บาท";
		if($inside_dept10 > 0){
			$arrInsideList[] = "- ออมทรัพย์ : ".($inside_dept10 ?? 0)." รายการ ".number_format($deposit_inside_10 ?? '0',2)." บาท";
		}
		if($inside_dept20 > 0){
			$arrInsideList[] = "- ออมทรัพย์พิเศษ : ".($inside_dept20 ?? 0)." รายการ ".number_format($deposit_inside_20 ?? '0',2)." บาท";
		}
		if($inside_dept70 > 0){
			$arrInsideList[] = "- ออมทรัพย์พิเศษเกษียณอายุ : ".($inside_dept70 ?? 0)." รายการ ".number_format($deposit_inside_70 ?? '0',2)." บาท";
		}
		foreach($arrayLoanExternalList as $value){
			$inAmt = $value["INT_AMT"]==0?null:(" / ".number_format($value["INT_AMT"] ?? '0',2)." บาท");
			$arrInsideList[] = "- ".$value["LOANTYPE_DESC"]." : ".($value["COUNT"] ?? 0)." รายการ ".number_format($value["PRN_AMT"] ?? '0',2).$intAmt;
		}
		//ถอน
		$arrInsideList[] = "รวมถอน : ".number_format($sum_withdraw_inside,2)." บาท";
		if($inside_withdraw10 > 0){
			$arrInsideList[] = "- ออมทรัพย์ : ".($inside_withdraw10 ?? 0)." รายการ ".number_format($withdraw_inside_10 ?? '0',2)." บาท";
		}
		if($inside_withdraw20 > 0){
			$arrInsideList[] = "- ออมทรัพย์พิเศษ : ".($inside_withdraw20 ?? 0)." รายการ ".number_format($withdraw_inside_20 ?? '0',2)." บาท";
		}
		if($inside_withdraw70 > 0){
			$arrInsideList[] = "- ออมทรัพย์พิเศษเกษียณอายุ : ".($inside_withdraw70 ?? 0)." รายการ ".number_format($withdraw_inside_70 ?? '0',2)." บาท";
		}
		$arrayResult['INSIDE_LIST'] = $arrInsideList;
		
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