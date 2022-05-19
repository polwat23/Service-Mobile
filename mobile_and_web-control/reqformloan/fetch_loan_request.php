<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanRequestForm')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$checkisAllowReq = $conmysql->prepare("SELECT member_no FROM gcallowmemberreqloan WHERE member_no = :member_no and is_allow = '1'");
		$checkisAllowReq->execute([':member_no' => $member_no]);
		
		$memberInfo = $conoracle->prepare("SELECT mb.birth_date,
												mb.member_date,mg.membgroup_desc,mt.membtype_desc
												FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
												LEFT JOIN MBUCFMEMBGROUP mg ON mb.MEMBGROUP_CODE = mg.MEMBGROUP_CODE
												LEFT JOIN MBUCFMEMBTYPE mt ON mb.MEMBTYPE_CODE = mt.MEMBTYPE_CODE
												WHERE mb.member_no = :member_no");
		$memberInfo->execute([':member_no' => $member_no]);
		$rowMember = $memberInfo->fetch(PDO::FETCH_ASSOC);
		
		$count_bdate_year = $lib->count_duration($rowMember["BIRTH_DATE"],"m")/12;
		$member_date_count = $lib->count_duration($rowMember["MEMBER_DATE"],"m");
		$typeMember = substr($member_no,2,1);
		//if($checkisReq->rowCount() > 0 && ($typeMember != '9' && $typeMember != '8')){
			$arrGrpLoan = array();
			$arrCanCal = array();
			$fetchLoanCanCal = $conmysql->prepare("SELECT loantype_code FROM gcconstanttypeloan WHERE is_loanrequest = '1'");
			$fetchLoanCanCal->execute();
			while($rowCanCal = $fetchLoanCanCal->fetch(PDO::FETCH_ASSOC)){
				$arrCanCal[] = $rowCanCal["loantype_code"];
			}
			$fetchLoanIntRate = $conoracle->prepare("SELECT lnt.LOANTYPE_DESC,lnt.LOANTYPE_CODE,lnd.INTEREST_RATE FROM lnloantype lnt LEFT JOIN lncfloanintratedet lnd 
																	ON lnt.INTTABRATE_CODE = lnd.LOANINTRATE_CODE
																	WHERE lnt.loantype_code IN(".implode(',',$arrCanCal).") and SYSDATE BETWEEN lnd.EFFECTIVE_DATE and lnd.EXPIRE_DATE ORDER BY lnt.loantype_code");
			$fetchLoanIntRate->execute();
			while($rowIntRate = $fetchLoanIntRate->fetch(PDO::FETCH_ASSOC)){
				$arrayDetailLoan = array();
				$CheckIsReq = $conmysql->prepare("SELECT reqloan_doc,req_status
															FROM gcreqloan WHERE loantype_code = :loantype_code and member_no = :member_no and req_status NOT IN('-9','9','1')");
				$CheckIsReq->execute([
					':loantype_code' => $rowIntRate["LOANTYPE_CODE"],
					':member_no' => $payload["member_no"]
				]);
				if($CheckIsReq->rowCount() > 0){
					$rowIsReq = $CheckIsReq->fetch(PDO::FETCH_ASSOC);
					$arrayDetailLoan["FLAG_NAME"] = $configError["REQ_FLAG_DESC"][0][$lang_locale];
					$arrayDetailLoan["IS_REQ"] = FALSE;
					$arrayDetailLoan["REQ_STATUS"] = $configError["REQ_LOAN_STATUS"][0][$rowIsReq["req_status"]][0][$lang_locale];
				}else{
					if($rowIntRate["LOANTYPE_CODE"] == '09'){
						if($typeMember != '9' && $typeMember != '8'){
							if($checkisAllowReq->rowCount() > 0){
								$arrayDetailLoan["IS_REQ"] = TRUE;
							}else{
								$arrayDetailLoan["FLAG_NAME"] = $configError["WS0087"][0][$lang_locale];
								$arrayDetailLoan["IS_REQ"] = FALSE;
							}
						}else{
							$arrayDetailLoan["FLAG_NAME"] = $configError["WS0087"][0][$lang_locale];
							$arrayDetailLoan["IS_REQ"] = FALSE;
						}
					}else{
						if($rowIntRate["LOANTYPE_CODE"] == '41'){
							// สามัญ ก
							$fetchOldContract = $conoracle->prepare("SELECT LM.STARTCONT_DATE,LM.LOANCONTRACT_NO,LM.PRINCIPAL_BALANCE, LM.LAST_PERIODPAY
															FROM LNCONTMASTER LM 
															WHERE LM.MEMBER_NO = :member_no AND LM.LOANTYPE_CODE = :loantype_code
															AND LM.CONTRACT_STATUS > 0 AND LM.CONTRACT_STATUS <> 8");
							$fetchOldContract->execute([
								':member_no' => $member_no,
								':loantype_code' => $rowIntRate["LOANTYPE_CODE"]
							]);
							$rowOldContract = $fetchOldContract->fetch(PDO::FETCH_ASSOC);
							
							//ข้อมูลเงินกู้ หากมีจะไม่สามารถกู้ประเภทนี้ได้
							$fetchContract = $conoracle->prepare("SELECT LM.STARTCONT_DATE,LM.LOANCONTRACT_NO,LM.PRINCIPAL_BALANCE, LM.LAST_PERIODPAY
															FROM LNCONTMASTER LM 
															WHERE LM.MEMBER_NO = :member_no AND LM.LOANTYPE_CODE IN('09')
															AND LM.CONTRACT_STATUS > 0 AND LM.CONTRACT_STATUS <> 8");
							$fetchContract->execute([
								':member_no' => $member_no
							]);
							$rowContract = $fetchContract->fetch(PDO::FETCH_ASSOC);
							
							if(isset($rowContract["LOANCONTRACT_NO"])){
								$arrayDetailLoan["FLAG_NAME"] = "ไม่สามารถกู้ได้เนื่องจากมีหนี้เงินกู้สามัญประกันชีวิต";
								$arrayDetailLoan["IS_REQ"] = FALSE;
							}else if(isset($rowOldContract["LAST_PERIODPAY"]) && $rowOldContract["LAST_PERIODPAY"] < 6){
								$arrayDetailLoan["FLAG_NAME"] = "ต้องชำระสัญญาเดิม 6 งวดขึ้นไป จึงจะขอกู้ได้";
								$arrayDetailLoan["IS_REQ"] = FALSE;
							}else{
								if($typeMember == '8'){
									if($count_bdate_year > 60){
										$arrayDetailLoan["FLAG_NAME"] = "อายุต้องไม่เกิน 60 ปี";
										$arrayDetailLoan["IS_REQ"] = FALSE;
									}else{
										$arrayDetailLoan["IS_REQ"] = TRUE;
									}
								}else{
									if($member_date_count < 1){
										$arrayDetailLoan["FLAG_NAME"] = "ต้องเป็นสมาชิกอย่างน้อย 1 เดือนขึ้นไป";
										$arrayDetailLoan["IS_REQ"] = FALSE;
									}else{
										$arrayDetailLoan["IS_REQ"] = TRUE;
									}
								}
							}
						}else if($rowIntRate["LOANTYPE_CODE"] == '13'){
							// สามัญ = ข
							$fetchOldContract = $conoracle->prepare("SELECT LM.STARTCONT_DATE,LM.LOANCONTRACT_NO,LM.PRINCIPAL_BALANCE, LM.LAST_PERIODPAY
															FROM LNCONTMASTER LM 
															WHERE LM.MEMBER_NO = :member_no AND LM.LOANTYPE_CODE = :loantype_code
															AND LM.CONTRACT_STATUS > 0 AND LM.CONTRACT_STATUS <> 8");
							$fetchOldContract->execute([
								':member_no' => $member_no,
								':loantype_code' => $rowIntRate["LOANTYPE_CODE"]
							]);
							$rowOldContract = $fetchOldContract->fetch(PDO::FETCH_ASSOC);
							
							if(isset($rowOldContract["LAST_PERIODPAY"]) && $rowOldContract["LAST_PERIODPAY"] < 6){
								$arrayDetailLoan["FLAG_NAME"] = "ต้องชำระสัญญาเดิม 6 งวดขึ้นไป จึงจะขอกู้ได้";
								$arrayDetailLoan["IS_REQ"] = FALSE;
							}else{
								if($member_date_count < 1){
									$arrayDetailLoan["FLAG_NAME"] = "ต้องเป้นสมาชิกอย่างน้อย 1 เดือนขึ้นไป";
									$arrayDetailLoan["IS_REQ"] = FALSE;
								}else{
									$arrayDetailLoan["IS_REQ"] = TRUE;
								}
							}
						}else if($rowIntRate["LOANTYPE_CODE"] == '42'){
							// สามัญ ค
							$fetchOldContract = $conoracle->prepare("SELECT LM.STARTCONT_DATE,LM.LOANCONTRACT_NO,LM.PRINCIPAL_BALANCE, LM.LAST_PERIODPAY
															FROM LNCONTMASTER LM 
															WHERE LM.MEMBER_NO = :member_no AND LM.LOANTYPE_CODE = :loantype_code
															AND LM.CONTRACT_STATUS > 0 AND LM.CONTRACT_STATUS <> 8");
							$fetchOldContract->execute([
								':member_no' => $member_no,
								':loantype_code' => $rowIntRate["LOANTYPE_CODE"]
							]);
							$rowOldContract = $fetchOldContract->fetch(PDO::FETCH_ASSOC);
							
							//ข้อมูลเงินกู้ หากมีจะไม่สามารถกู้ประเภทนี้ได้
							$fetchContract = $conoracle->prepare("SELECT LM.STARTCONT_DATE,LM.LOANCONTRACT_NO,LM.PRINCIPAL_BALANCE, LM.LAST_PERIODPAY
															FROM LNCONTMASTER LM 
															WHERE LM.MEMBER_NO = :member_no AND LM.LOANTYPE_CODE IN('10','49')
															AND LM.CONTRACT_STATUS > 0 AND LM.CONTRACT_STATUS <> 8");
							$fetchContract->execute([
								':member_no' => $member_no
							]);
							$rowContract = $fetchContract->fetch(PDO::FETCH_ASSOC);
							
							if(isset($rowContract["LOANCONTRACT_NO"])){
								$arrayDetailLoan["FLAG_NAME"] = "ไม่สามารถกู้ได้เนื่องจากมีหนี้เงินกู้สามัญประกันชีวิตหรือสามัญปรับโครงสร้างหนี้";
								$arrayDetailLoan["IS_REQ"] = FALSE;
							}else if(isset($rowOldContract["LAST_PERIODPAY"]) && $rowOldContract["LAST_PERIODPAY"] < 12){
								$arrayDetailLoan["FLAG_NAME"] = "ต้องชำระสัญญาเดิม 12 งวดขึ้นไป จึงจะขอกู้ได้";
								$arrayDetailLoan["IS_REQ"] = FALSE;
							}else if($member_date_count < 1){
								$arrayDetailLoan["FLAG_NAME"] = "ต้องเป้นสมาชิกอย่างน้อย 1 เดือนขึ้นไป";
								$arrayDetailLoan["IS_REQ"] = FALSE;
							}else if($count_bdate_year > 60){
								$arrayDetailLoan["FLAG_NAME"] = "อายุต้องไม่เกิน 60 ปี";
								$arrayDetailLoan["IS_REQ"] = FALSE;
							}else{
								$arrayDetailLoan["IS_REQ"] = TRUE;
							}
						}else if($rowIntRate["LOANTYPE_CODE"] == '10'){
							// สามัญ จ
							$fetchOldContract = $conoracle->prepare("SELECT LM.STARTCONT_DATE,LM.LOANCONTRACT_NO,LM.PRINCIPAL_BALANCE, LM.LAST_PERIODPAY
															FROM LNCONTMASTER LM 
															WHERE LM.MEMBER_NO = :member_no AND LM.LOANTYPE_CODE = :loantype_code
															AND LM.CONTRACT_STATUS > 0 AND LM.CONTRACT_STATUS <> 8");
							$fetchOldContract->execute([
								':member_no' => $member_no,
								':loantype_code' => $rowIntRate["LOANTYPE_CODE"]
							]);
							$rowOldContract = $fetchOldContract->fetch(PDO::FETCH_ASSOC);
							
							if($member_date_count < 3){
								$arrayDetailLoan["FLAG_NAME"] = "ต้องเป็นสมาชิกอย่างน้อย 3 เดือนขึ้นไป";
								$arrayDetailLoan["IS_REQ"] = FALSE;
							}else{
								$arrayDetailLoan["IS_REQ"] = TRUE;
							}
						}else if($rowIntRate["LOANTYPE_CODE"] == '52'){
							// สามัญ จ
							$fetchOldContract = $conoracle->prepare("SELECT LM.STARTCONT_DATE,LM.LOANCONTRACT_NO,LM.PRINCIPAL_BALANCE, LM.LAST_PERIODPAY
															FROM LNCONTMASTER LM 
															WHERE LM.MEMBER_NO = :member_no AND LM.LOANTYPE_CODE = :loantype_code
															AND LM.CONTRACT_STATUS > 0 AND LM.CONTRACT_STATUS <> 8");
							$fetchOldContract->execute([
								':member_no' => $member_no,
								':loantype_code' => $rowIntRate["LOANTYPE_CODE"]
							]);
							$rowOldContract = $fetchOldContract->fetch(PDO::FETCH_ASSOC);
							
							if($member_date_count < 3){
								$arrayDetailLoan["FLAG_NAME"] = "ต้องเป็นสมาชิกอย่างน้อย 24 เดือนขึ้นไป";
								$arrayDetailLoan["IS_REQ"] = FALSE;
							}else{
								$arrayDetailLoan["IS_REQ"] = TRUE;
							}
						}else if($rowIntRate["LOANTYPE_CODE"] == '09'){
							if($member_date_count < 3){
								$arrayDetailLoan["FLAG_NAME"] = "ต้องเป้นสมาชิกอย่างน้อย 3 เดือนขึ้นไป";
								$arrayDetailLoan["IS_REQ"] = FALSE;
							}else{
								$arrayDetailLoan["IS_REQ"] = TRUE;
							}
						}else if($rowIntRate["LOANTYPE_CODE"] == '19'){
							// สามัญ พ
							$fetchOldContract = $conoracle->prepare("SELECT LM.STARTCONT_DATE,LM.LOANCONTRACT_NO,LM.PRINCIPAL_BALANCE, LM.LAST_PERIODPAY
															FROM LNCONTMASTER LM 
															WHERE LM.MEMBER_NO = :member_no AND LM.LOANTYPE_CODE = :loantype_code
															AND LM.CONTRACT_STATUS > 0 AND LM.CONTRACT_STATUS <> 8");
							$fetchOldContract->execute([
								':member_no' => $member_no,
								':loantype_code' => $rowIntRate["LOANTYPE_CODE"]
							]);
							$rowOldContract = $fetchOldContract->fetch(PDO::FETCH_ASSOC);
							
							if(isset($rowOldContract["LAST_PERIODPAY"]) && $rowOldContract["LAST_PERIODPAY"] < 6){
								$arrayDetailLoan["FLAG_NAME"] = "ต้องชำระสัญญาเดิม 6 งวดขึ้นไป จึงจะขอกู้ได้";
								$arrayDetailLoan["IS_REQ"] = FALSE;
							}else if($member_date_count < 1){
								$arrayDetailLoan["FLAG_NAME"] = "ต้องเป้นสมาชิกอย่างน้อย 1 เดือนขึ้นไป";
								$arrayDetailLoan["IS_REQ"] = FALSE;
							}else{
								$arrayDetailLoan["IS_REQ"] = TRUE;
							}
						}else if($rowIntRate["LOANTYPE_CODE"] == '51'){
							// สามัญ สห
							//หากมีเงินกู้สามัญทุกประเภษ จะไม่สามารถกู้ได้
							$fetchContractGroup = $conoracle->prepare("SELECT LM.STARTCONT_DATE,LM.LOANCONTRACT_NO,LM.PRINCIPAL_BALANCE, LM.LAST_PERIODPAY
															FROM LNCONTMASTER LM 
															LEFT JOIN LNLOANTYPE LT ON LT.LOANTYPE_CODE = LM.LOANTYPE_CODE
															WHERE LM.MEMBER_NO = :member_no AND LOANGROUP_CODE = '02' AND LM.LOANTYPE_CODE != '44'
															AND LM.CONTRACT_STATUS > 0 AND LM.CONTRACT_STATUS <> 8");
							$fetchContractGroup->execute([
								':member_no' => $member_no
							]);
							$rowContractGroup = $fetchContractGroup->fetch(PDO::FETCH_ASSOC);
							
							if($typeMember != '9' && $typeMember != '8'){
								if($checkisAllowReq->rowCount() > 0){
									if($member_date_count < 6){
										$arrayDetailLoan["FLAG_NAME"] = "ต้องเป้นสมาชิกอย่างน้อย 1 เดือนขึ้นไป";
										$arrayDetailLoan["IS_REQ"] = FALSE;
									}else{
										$arrayDetailLoan["IS_REQ"] = TRUE;
									}
								}else{
									$arrayDetailLoan["FLAG_NAME"] = $configError["WS0087"][0][$lang_locale];
									$arrayDetailLoan["IS_REQ"] = FALSE;
								}
							}else{
								$arrayDetailLoan["FLAG_NAME"] = $configError["WS0087"][0][$lang_locale];
								$arrayDetailLoan["IS_REQ"] = FALSE;
							}
						}else{
							$arrayDetailLoan["IS_REQ"] = TRUE;
						}
					}
				}
				$arrayDetailLoan["LOANTYPE_CODE"] = $rowIntRate["LOANTYPE_CODE"];
				$arrayDetailLoan["LOANTYPE_DESC"] = $rowIntRate["LOANTYPE_DESC"];
				$arrayDetailLoan["INT_RATE"] = number_format($rowIntRate["INTEREST_RATE"],2);
				$arrGrpLoan[] = $arrayDetailLoan;
			}
			$arrayResult["LOAN_LIST"] = $arrGrpLoan;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		/*}else{
			$arrayResult['RESPONSE_CODE'] = "WS0087";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}*/
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
