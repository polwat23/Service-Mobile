<?php
require_once('../autoload.php');

use Dompdf\Dompdf;

$dompdf = new DOMPDF();

if($lib->checkCompleteArgument(['menu_component','form_value_root_','documenttype_code'],$dataComing)){
	if($dataComing["documenttype_code"] == 'CBNF'){
		if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DocumentBeneciaryRequest')){
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0006";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			http_response_code(403);
			require_once('../../include/exit_footer.php');
		}
	}else if($dataComing["documenttype_code"] == 'CSHR'){
		if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DocumentShrPaymentRequest')){
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0006";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			http_response_code(403);
			require_once('../../include/exit_footer.php');
		}
	}else if($dataComing["documenttype_code"] == 'RRSN'){
		if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DocumentResignRequest')){
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0006";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			http_response_code(403);
			require_once('../../include/exit_footer.php');
		}
	
	}else if($dataComing["documenttype_code"] == 'RCER'){
		if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DocumentCertRequest')){
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0006";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			http_response_code(403);
			require_once('../../include/exit_footer.php');
		}
	}else if($dataComing["documenttype_code"] == 'PAYD'){
		if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DocumentPayDeptRequest')){
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0006";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			http_response_code(403);
			require_once('../../include/exit_footer.php');
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../include/exit_footer.php');
	}
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
			$getDocSystemPrefix = $conmysql->prepare("SELECT prefix_docno FROM docsystemprefix WHERE menu_component = :menu_component and is_use = '1'");
			$getDocSystemPrefix->execute([':menu_component' => $dataComing["documenttype_code"]]);
			
			$reqdoc_no = null;
			if($getDocSystemPrefix->rowCount() > 0){
				$rowDocPrefix = $getDocSystemPrefix->fetch(PDO::FETCH_ASSOC);
				$arrPrefixRaw = $func->PrefixGenerate($rowDocPrefix["prefix_docno"]);
				$arrPrefixSort = explode(',',$rowDocPrefix["prefix_docno"]);
				foreach($arrPrefixSort as $prefix){
					$reqdoc_no .= $arrPrefixRaw[$prefix];
				}
			}
			
			if(isset($reqdoc_no) && $reqdoc_no != ""){
				$getControlDoc = $conmysql->prepare("SELECT docgrp_no FROM docgroupcontrol WHERE is_use = '1' and menu_component = :menu_component");
				$getControlDoc->execute([':menu_component' => $dataComing["documenttype_code"]]);
				$rowConDoc = $getControlDoc->fetch(PDO::FETCH_ASSOC);
				
				$fetchData = $conmssql->prepare("SELECT MB.MEMB_NAME,MB.MEMB_SURNAME,MP.PRENAME_DESC,MB.SALARY_ID,MUP.POSITION_DESC,MB.EXPENSE_ACCID,MB.MEMBER_DATE,MG.MEMBGROUP_DESC
														FROM MBMEMBMASTER MB 
														LEFT JOIN MBUCFPRENAME MP ON MB.PRENAME_CODE = MP.PRENAME_CODE
														LEFT JOIN MBUCFPOSITION MUP ON MB.POSITION_CODE = MUP.POSITION_CODE
														LEFT JOIN MBUCFMEMBGROUP MG ON MB.MEMBGROUP_CODE = MG.MEMBGROUP_CODE
														WHERE MB.MEMBER_NO = :member_no");
				$fetchData->execute([
					':member_no' => $member_no
				]);
				$rowData = $fetchData->fetch(PDO::FETCH_ASSOC);
				$memberInfoMobile = $conmysql->prepare("SELECT phone_number,email FROM gcmemberaccount WHERE member_no = :member_no");
				$memberInfoMobile->execute([':member_no' => $payload["member_no"]]);
				$rowInfoMobile = $memberInfoMobile->fetch(PDO::FETCH_ASSOC);
				
				$pathFile = $config["URL_SERVICE"].'/resource/pdf/req_document/'.$reqdoc_no.'.pdf?v='.time();
				
				$arrOldContract = array();
				if($dataComing["documenttype_code"] == "PAYD"){
					$getOldContract = $conmssql->prepare("SELECT LM.PRINCIPAL_BALANCE,LT.LOANTYPE_DESC,LM.LOANCONTRACT_NO,LM.LAST_PERIODPAY, lt.LOANGROUP_CODE, lm.LOANTYPE_CODE, lm.PERIOD_PAYMENT
												FROM lncontmaster lm LEFT JOIN lnloantype lt ON lm.loantype_code = lt.loantype_code 
												WHERE lm.member_no = :member_no and lm.contract_status > 0 and lm.contract_status <> 8 and lt.loangroup_code = '02'");
					$getOldContract->execute([
						':member_no' => $member_no
					]);
					while($rowOldContract = $getOldContract->fetch(PDO::FETCH_ASSOC)){
						$arrContract = array();
						$contract_no = preg_replace('/\//','',$rowOldContract["LOANCONTRACT_NO"]);
						$arrContract["CONTRACT_NO"] = $contract_no;
						$arrContract['BALANCE'] = $rowOldContract["PRINCIPAL_BALANCE"];
						$arrContract['BALANCE_AND_INTEREST'] = $rowOldContract["PRINCIPAL_BALANCE"] + round($cal_loan->calculateInterest($rowOldContract["LOANCONTRACT_NO"]), 0, PHP_ROUND_HALF_DOWN);
						$arrContract['INTEREST'] = round($cal_loan->calculateInterest($rowOldContract["LOANCONTRACT_NO"]), 0, PHP_ROUND_HALF_DOWN);
						
						$arrOldContract[] = $arrContract;
					}
				}
				
				$conmysql->beginTransaction();
				$InsertFormOnline = $conmysql->prepare("INSERT INTO gcreqdoconline(reqdoc_no, member_no, documenttype_code, form_value, document_url, req_remark) 
													VALUES (:reqdoc_no, :member_no, :documenttype_code, :form_value,:document_url,:req_remark)");
				if($InsertFormOnline->execute([
					':reqdoc_no' => $reqdoc_no,
					':member_no' => $payload["member_no"],
					':documenttype_code' => $dataComing["documenttype_code"],
					':form_value' => json_encode($dataComing["form_value_root_"]),
					':document_url' => $pathFile,
					':req_remark' => $dataComing["documenttype_code"] == "PAYD" ? json_encode($arrOldContract) : "",
				])){
					if($dataComing["documenttype_code"] == "RRSN"){
						//หนี้เดิม
						$getSharemasterinfo = $conmssql->prepare("SELECT (sharestk_amt * 10) as SHARE_AMT, (periodshare_amt * 10) as PERIOD_SHARE_AMT
															FROM shsharemaster WHERE member_no = :member_no");
						$getSharemasterinfo->execute([':member_no' => $member_no]);
						$rowMastershare = $getSharemasterinfo->fetch(PDO::FETCH_ASSOC);
						$loan_prn_1 = 0;
						$loan_int_1 = 0;
						$loan_prn_2 = 0;
						$loan_int_2 = 0;
						$getOldContract = $conmssql->prepare("SELECT LM.PRINCIPAL_BALANCE,LT.LOANTYPE_DESC,LM.LOANCONTRACT_NO,LM.LAST_PERIODPAY, lt.LOANGROUP_CODE, lm.LOANTYPE_CODE, lm.PERIOD_PAYMENT
										FROM lncontmaster lm LEFT JOIN lnloantype lt ON lm.loantype_code = lt.loantype_code 
										WHERE lm.member_no = :member_no and lm.contract_status > 0 and lm.contract_status <> 8");
						$getOldContract->execute([
							':member_no' => $member_no
						]);

						while($rowOldContract = $getOldContract->fetch(PDO::FETCH_ASSOC)){
							if($rowOldContract["LOANGROUP_CODE"] == '01'){
								$loan_prn_1 += $rowOldContract["PRINCIPAL_BALANCE"];
								$loan_int_1 += $cal_loan->calculateInterestRevert($rowOldContract["LOANCONTRACT_NO"]);
							}else if($rowOldContract["LOANGROUP_CODE"] == '02'){
								$loan_prn_2 += $rowOldContract["PRINCIPAL_BALANCE"];
								$loan_int_2 += $cal_loan->calculateInterestRevert($rowOldContract["LOANCONTRACT_NO"]);
							}
						}
						$receive_net = $rowMastershare["SHARE_AMT"]-($loan_prn_2 + $loan_int_2+$loan_prn_1 + $loan_int_1);
						if($receive_net < 0){
							$conmysql->rollback();
							$arrayResult['RESPONSE_CODE'] = "";
							$arrayResult['RESPONSE_MESSAGE'] = "หนี้มากกว่าทุนเรือนหุ้น ไม่สามารถลาออกได้กรุณาติดต่อเจ้าหน้าที่ !";
							$arrayResult['RESULT'] = FALSE;
							require_once('../../include/exit_footer.php');
						}
					
						$effect_month_arr = $dataComing["form_value_root_"]["EFFECT_DATE"]["VALUE"] ? $lib->convertdate($dataComing["form_value_root_"]["EFFECT_DATE"]["VALUE"],"D m Y") : "";
						$effect_month_arr = explode(" ", $effect_month_arr);
						$effect_month = ($effect_month_arr[1] ?? "")." ".($effect_month_arr[2] ?? "");
						
						$arrGroupDetail = array();
						$arrGroupDetail["MEMBER_NO"] =  $member_no;
						$arrGroupDetail["EMP_NO"] = $rowData["SALARY_ID"];
						$arrGroupDetail["POSITION"] = $rowData["POSITION_DESC"];
						$arrGroupDetail["PHONE_NUMBER"] =  $rowInfoMobile["phone_number"];
						$arrGroupDetail["REQDOC_NO"] = $reqdoc_no;
						$arrGroupDetail["MEMBER_NAME"] =  $rowData["PRENAME_DESC"].$rowData["MEMB_NAME"];
						$arrGroupDetail["MEMBER_SURNAME"] =  $rowData["MEMB_SURNAME"];
						$arrGroupDetail["EFFECT_DATE"] =  $effect_month;
						$arrGroupDetail["REASON"] =  $dataComing["form_value_root_"]["REASON"]["VALUE"] ?? "";
						$arrGroupDetail["SHARE_STK"] =  $dataComing["is_confirm"] ? ($dataComing["form_value_root_"]["SHARE_STK"]["VALUE"] ?? "0") : number_format($dataComing["form_value_root_"]["SHARE_STK"]["VALUE"] ?? "0",2);
						$arrGroupDetail["LOAN_GROUP_BAL_2"] =  $dataComing["is_confirm"] ? ($dataComing["form_value_root_"]["LOAN_GROUP_BAL_2"]["VALUE"] ?? "0") : number_format($dataComing["form_value_root_"]["LOAN_GROUP_BAL_2"]["VALUE"] ?? "0",2);
						$arrGroupDetail["LOAN_GROUP_BAL_1"] =  $dataComing["is_confirm"] ? ($dataComing["form_value_root_"]["LOAN_GROUP_BAL_1"]["VALUE"] ?? "0") : number_format($dataComing["form_value_root_"]["LOAN_GROUP_BAL_1"]["VALUE"] ?? "0",2);
						$arrGroupDetail["LOAN_GROUP_INT_2"] =  $dataComing["is_confirm"] ? ($dataComing["form_value_root_"]["LOAN_GROUP_INT_2"]["VALUE"] ?? "0") : number_format($dataComing["form_value_root_"]["LOAN_GROUP_INT_2"]["VALUE"] ?? "0",2);
						$arrGroupDetail["LOAN_GROUP_INT_1"] =  $dataComing["is_confirm"] ? ($dataComing["form_value_root_"]["LOAN_GROUP_INT_1"]["VALUE"] ?? "0") : number_format($dataComing["form_value_root_"]["LOAN_GROUP_INT_1"]["VALUE"] ?? "0",2);
						$arrGroupDetail["RECEIVE_NET"] =  $dataComing["is_confirm"] ? ($dataComing["form_value_root_"]["RECEIVE_NET"]["VALUE"] ?? "0") : number_format($dataComing["form_value_root_"]["RECEIVE_NET"]["VALUE"] ?? "0",2);
						$arrGroupDetail["RESIGN_OPTION"] =  $dataComing["form_value_root_"]["RESIGN_OPTION"]["VALUE"] ?? "";
						$arrGroupDetail["RECEIVE_ACC"] =  $rowData["EXPENSE_ACCID"];
					}else if($dataComing["documenttype_code"] == "CBNF"){
						$arrGroupDetail = array();
						$arrGroupDetail["MEMBER_NO"] =  $member_no;
						$arrGroupDetail["EMP_NO"] = $rowData["SALARY_ID"];
						$arrGroupDetail["POSITION"] = $rowData["POSITION_DESC"];
						$arrGroupDetail["PHONE_NUMBER"] =  $rowInfoMobile["phone_number"];
						$arrGroupDetail["REQDOC_NO"] = $reqdoc_no;
						$arrGroupDetail["MEMBER_FULLNAME"] =  $rowData["PRENAME_DESC"].$rowData["MEMB_NAME"]." ".$rowData["MEMB_SURNAME"];
						$arrGroupDetail["EFFECT_DATE"] =  $dataComing["form_value_root_"]["EFFECT_DATE"]["VALUE"] ? $lib->convertdate($dataComing["form_value_root_"]["EFFECT_DATE"]["VALUE"],"D m Y") : "";
						$arrGroupDetail["BENEF_NAME_1"] =  $dataComing["form_value_root_"]["BENEF_NAME_1"]["VALUE"] ?? "";
						$arrGroupDetail["BENEF_NAME_2"] =  $dataComing["form_value_root_"]["BENEF_NAME_2"]["VALUE"] ?? "";
						$arrGroupDetail["BENEF_NAME_3"] =  $dataComing["form_value_root_"]["BENEF_NAME_3"]["VALUE"] ?? "";
						$arrGroupDetail["BENEF_NAME_4"] =  $dataComing["form_value_root_"]["BENEF_NAME_4"]["VALUE"] ?? "";
						$arrGroupDetail["BENEF_OPTION"] =  $dataComing["form_value_root_"]["BENEF_OPTION"]["VALUE"] ?? "";
						$arrGroupDetail["OPTION_VALUE"] =  $dataComing["form_value_root_"]["BENEF_OPTION"]["OPTION_VALUE"][$dataComing["form_value_root_"]["BENEF_OPTION"]["VALUE"]];
					}else if($dataComing["documenttype_code"] == "CSHR"){
						$requirement = $dataComing["form_value_root_"]["REQUIREMENT"]["VALUE"] ?? "";
						$old_payment = $dataComing["form_value_root_"]["OLD_SHR_PAYMENT"]["VALUE"] ?? null;
						$period_payment = $dataComing["form_value_root_"]["SHARE_PERIOD_PAYMENT"]["VALUE"] ?? null;
						$effect_month_arr = $dataComing["form_value_root_"]["EFFECT_MONTH"]["VALUE"] ? $lib->convertdate($dataComing["form_value_root_"]["EFFECT_MONTH"]["VALUE"],"D m Y") : "";
						$effect_month_arr = explode(" ", $effect_month_arr);
						$effect_month = ($effect_month_arr[1] ?? "")." ".($effect_month_arr[2] ?? "");
						// หุ้นขั้นต่ำ
						$sum_old_payment = 0;
						
						if($period_payment % 10 != 0){
							$arrayResult['RESPONSE_CODE'] = "";
							$arrayResult['RESPONSE_MESSAGE'] = "ค่าหุ้นรายเดือนไม่ถูกต้อง เนื่องจากหุ้นมีมูลค่าหุ้นละ 10 บาท กรุณาตรวจสอบค่าหุ้นรายเดือนและลองใหม่อีกครั้ง";
							$arrayResult['RESULT'] = FALSE;
							require_once('../../include/exit_footer.php');
						}else if($requirement != '3' && (!(isset($period_payment)) || $period_payment == "" || $period_payment < 200)){
							$conmysql->rollback();
							$arrayResult['RESPONSE_CODE'] = "";
							$arrayResult['RESPONSE_MESSAGE'] = "ค่าหุ้นรายเดือนขั้นต่ำ 200 บาท กรุณาตรวจสอบค่าหุ้นรายเดือนแล้วลองใหม่อีกครั้ง";
							$arrayResult['RESULT'] = FALSE;
							require_once('../../include/exit_footer.php');
						}
						
						if($requirement == '3'){
							$getOldContract = $conmssql->prepare("SELECT sum(lm.PRINCIPAL_BALANCE) as PRINCIPAL_BALANCE
										from lncontmaster lm
										WHERE lm.member_no = :member_no and lm.contract_status > 0 and lm.contract_status <> 8");
							$getOldContract->execute([
								':member_no' => $member_no
							]);

							$rowOldContract = $getOldContract->fetch(PDO::FETCH_ASSOC);
							$loan_balance = $rowOldContract["PRINCIPAL_BALANCE"] ?? 0;
							if($loan_balance > 0){
								$conmysql->rollback();
								$arrayResult['RESPONSE_CODE'] = "";
								$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถหยุดส่งค่าหุ้นได้ เนื่องจาก มีหนี้สินกับสหกรณ์ฯ";
								$arrayResult['RESULT'] = FALSE;
								require_once('../../include/exit_footer.php');
							}
							$getSharePeriodinfo = $conmssql->prepare("SELECT MAX(stm.PERIOD) as PERIOD
														FROM shsharestatement stm
														WHERE stm.member_no = :member_no and stm.shritemtype_code NOT IN ('B/F','DIV')");
							$getSharePeriodinfo->execute([':member_no' => $member_no]);
							$rowPeriodshare = $getSharePeriodinfo->fetch(PDO::FETCH_ASSOC);
							$last_shr_period = $rowPeriodshare["PERIOD"] ?? 0;
							$getSharemasterinfo = $conmssql->prepare("SELECT (sharestk_amt * 10) as SHARE_AMT
											FROM shsharemaster WHERE member_no = :member_no");
							$getSharemasterinfo->execute([':member_no' => $member_no]);
							$rowMastershare = $getSharemasterinfo->fetch(PDO::FETCH_ASSOC);
							$shr_amt = $rowMastershare["SHARE_AMT"] ?? 0;
							
							if($last_shr_period < 120 && $shr_amt < 200000){
									$conmysql->rollback();
									$arrayResult['RESPONSE_CODE'] = "";
									$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถหยุดส่งค่าหุ้นได้ เนื่องจากชำระเงินค่าหุ้นไม่ถึง 120 เดือน หรือมีหุ้นไม่ถึง 200,000 บาท";
									$arrayResult['RESULT'] = FALSE;
									require_once('../../include/exit_footer.php');
							}
						}else if($requirement == '1'){
							if((!(isset($period_payment)) || $period_payment == "" || $period_payment <= $old_payment)){
								$conmysql->rollback();
								$arrayResult['RESPONSE_CODE'] = "";
								$arrayResult['RESPONSE_MESSAGE'] = "กรณีขอเพิ่มค่าหุ้น  ค่าหุ้นรายเดือนใหม่ต้องมากกว่าค่าหุ้นรายเดือนปัจจุบัน กรุณาตรวจสอบค่าหุ้นรายเดือนแล้วลองใหม่อีกครั้ง";
								$arrayResult['RESULT'] = FALSE;
								require_once('../../include/exit_footer.php');
							}else{
								//คำนวณเงินเดือนคงเหลือ
								$getMemberIno = $conmssql->prepare("SELECT SALARY_AMOUNT FROM mbmembmaster WHERE member_no = :member_no");
								$getMemberIno->execute([':member_no' => $member_no]);
								$rowMember = $getMemberIno->fetch(PDO::FETCH_ASSOC);
								$salary = $rowMember["SALARY_AMOUNT"] ?? 0;
								$getOldContract = $conmssql->prepare("SELECT LM.PRINCIPAL_BALANCE,LT.LOANTYPE_DESC,LM.LOANCONTRACT_NO,LM.LAST_PERIODPAY, lt.LOANGROUP_CODE, lm.LOANTYPE_CODE, lm.PERIOD_PAYMENT
									FROM lncontmaster lm LEFT JOIN lnloantype lt ON lm.loantype_code = lt.loantype_code 
									WHERE lm.member_no = :member_no and lm.contract_status > 0 and lm.contract_status <> 8");
								$getOldContract->execute([
									':member_no' => $member_no
								]);

								while($rowOldContract = $getOldContract->fetch(PDO::FETCH_ASSOC)){
									$sum_old_payment += $rowOldContract["PERIOD_PAYMENT"];
								}
								$getMthOther = $conmssql->prepare("SELECT SUM(mthother_amt) as MTHOTHER_AMT FROM mbmembmthother WHERE member_no = :member_no and sign_flag = '-1'");
								$getMthOther->execute([':member_no' => $member_no]);
								$rowOther = $getMthOther->fetch(PDO::FETCH_ASSOC);
								$other_amt = $rowOther["MTHOTHER_AMT"] ?? 0;
								$sum_old_payment += $other_amt;
								$salary_balance = $salary - $sum_old_payment;
								if($period_payment > $salary_balance){
									$conmysql->rollback();
									$arrayResult['RESPONSE_CODE'] = "";
									$arrayResult['RESPONSE_MESSAGE'] = "จำนวนทุนเรือนหุ้นรายเดือนที่เพิ่ม เกินเงินเดือนคงเหลือสุทธิ กรุณาตรวจสอบค่าหุ้นรายเดือนแล้วลองใหม่อีกครั้ง";
									$arrayResult['RESULT'] = FALSE;
									require_once('../../include/exit_footer.php');
								}
							}
						}else if($requirement == '2' && (!(isset($period_payment)) || $period_payment == "" || $period_payment >= $old_payment)){
							$conmysql->rollback();
							$arrayResult['RESPONSE_CODE'] = "";
							$arrayResult['RESPONSE_MESSAGE'] = "กรณีขอลดค่าหุ้น  ค่าหุ้นรายเดือนใหม่ต้องน้อยกว่าค่าหุ้นรายเดือนปัจจุบัน กรุณาตรวจสอบค่าหุ้นรายเดือนแล้วลองใหม่อีกครั้ง";
							$arrayResult['RESULT'] = FALSE;
							require_once('../../include/exit_footer.php');
						}
						
						$arrGroupDetail = array();
						$arrGroupDetail["MEMBER_NO"] =  $member_no;
						$arrGroupDetail["EMP_NO"] = $rowData["SALARY_ID"];
						$arrGroupDetail["REQDOC_NO"] = $reqdoc_no;
						$arrGroupDetail["OLD_SHR_PAYMENT"] = number_format($old_payment, 2);
						$arrGroupDetail["MEMBER_NAME"] =  $rowData["PRENAME_DESC"].$rowData["MEMB_NAME"];
						$arrGroupDetail["MEMBER_SURNAME"] =  $rowData["MEMB_SURNAME"];
						$arrGroupDetail["EFFECT_MONTH"] =  $effect_month;
						$arrGroupDetail["SHARE_PERIOD_PAYMENT"] =  number_format($dataComing["form_value_root_"]["SHARE_PERIOD_PAYMENT"]["VALUE"] ?? 0, 2);
						$arrGroupDetail["REQUIREMENT"] =  $dataComing["form_value_root_"]["REQUIREMENT"]["VALUE"] ?? "";
					}else if($dataComing["documenttype_code"] == "RCER"){
						$arrGroupDetail = array();
						$arrGroupDetail["MEMBER_NO"] =  $member_no;
						$arrGroupDetail["MEMBER_DATE"] = $rowData["MEMBER_DATE"];
						$arrGroupDetail["EMP_NO"] = $rowData["SALARY_ID"];
						$arrGroupDetail["MEMBGROUP"] = $rowData["MEMBGROUP_DESC"];
						$arrGroupDetail["POSITION"] = $rowData["POSITION_DESC"];
						$arrGroupDetail["PHONE_NUMBER"] =  $rowInfoMobile["phone_number"];
						$arrGroupDetail["REQDOC_NO"] = $reqdoc_no;
						$arrGroupDetail["MEMBER_FULLNAME"] =  $rowData["PRENAME_DESC"].$rowData["MEMB_NAME"]." ".$rowData["MEMB_SURNAME"];
						$arrGroupDetail["PERIOD_SHARE_AMT"] =  number_format($dataComing["form_value_root_"]["PERIOD_SHARE_AMT"]["VALUE"] ?? 0, 2);
						$arrGroupDetail["SHARE_STK"] =  number_format($dataComing["form_value_root_"]["SHARE_STK"]["VALUE"] ?? 0, 2);
						$arrGroupDetail["PERIOD_PAYMENT_01"] =  number_format($dataComing["form_value_root_"]["PERIOD_PAYMENT_01"]["VALUE"] ?? 0, 2);
						$arrGroupDetail["LOAN_BALANCE_01"] =  number_format($dataComing["form_value_root_"]["LOAN_BALANCE_01"]["VALUE"] ?? 0, 2);
						$arrGroupDetail["PERIOD_PAYMENT_02"] =  number_format($dataComing["form_value_root_"]["PERIOD_PAYMENT_02"]["VALUE"] ?? 0, 2);
						$arrGroupDetail["LOAN_BALANCE_02"] =  number_format($dataComing["form_value_root_"]["LOAN_BALANCE_02"]["VALUE"] ?? 0, 2);
					}else if($dataComing["documenttype_code"] == "PAYD"){
						$arrGroupDetail = array();
						$arrGroupDetail["MEMBER_NO"] =  $member_no;
						$arrGroupDetail["MEMBER_DATE"] = $rowData["MEMBER_DATE"];
						$arrGroupDetail["EMP_NO"] = $rowData["SALARY_ID"];
						$arrGroupDetail["MEMBGROUP"] = $rowData["MEMBGROUP_DESC"];
						$arrGroupDetail["POSITION"] = $rowData["POSITION_DESC"];
						$arrGroupDetail["PHONE_NUMBER"] =  $rowInfoMobile["phone_number"];
						$arrGroupDetail["REQDOC_NO"] = $reqdoc_no;
						$arrGroupDetail["MEMBER_FULLNAME"] =  $rowData["PRENAME_DESC"].$rowData["MEMB_NAME"]." ".$rowData["MEMB_SURNAME"];
						$arrGroupDetail["DEPT_MONTH"] =  isset($dataComing["form_value_root_"]["DEPT_MONTH"]["VALUE"]) ? $lib->convertdate($dataComing["form_value_root_"]["DEPT_MONTH"]["VALUE"],'N m Y') : null;
						$arrGroupDetail["PAYMENT_AMT"] =  number_format($dataComing["form_value_root_"]["PAYMENT_AMT"]["VALUE"] ?? 0, 2);
						$arrGroupDetail["PAY_MONTH"] =  isset($dataComing["form_value_root_"]["PAY_MONTH"]["VALUE"]) ? $lib->convertdate($dataComing["form_value_root_"]["PAY_MONTH"]["VALUE"],'N m Y') : null;
						$arrGroupDetail["BANK_NO"] =  $dataComing["form_value_root_"]["BANK_NO"]["VALUE"] ?? null;
						$arrGroupDetail["PAY_MONTH_ARR"] =  $dataComing["form_value_root_"]["PAY_MONTH"]["VALUE"] ?? null;
						$arrGroupDetail["DEPT_MONTH_ARR"] =  $dataComing["form_value_root_"]["DEPT_MONTH"]["VALUE"] ?? null;
						$arrGroupDetail["CONTRACT"] =  $dataComing["form_value_root_"]["CONTRACT"]["VALUE"] ?? [];
					}else{
						$conmysql->rollback();
						$arrayResult['RESPONSE_CODE'] = "WS0124";
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
					}
					if($dataComing["is_confirm"]){
						include('form_request_document_'.$dataComing["documenttype_code"].'.php');
						$arrayPDF = GenerateReport($arrGroupDetail,$lib);
						if($arrayPDF["RESULT"]){
							$arrayResult['REPORT_URL'] = $config["URL_SERVICE"].$arrayPDF["PATH"];
							
							$insertDocMaster = $conmysql->prepare("INSERT INTO doclistmaster(doc_no,docgrp_no,doc_filename,doc_type,doc_address,member_no)
																	VALUES(:doc_no,:docgrp_no,:doc_filename,'pdf',:doc_address,:member_no)");
							$insertDocMaster->execute([
								':doc_no' => $reqdoc_no,
								':docgrp_no' => $rowConDoc["docgrp_no"],
								':doc_filename' => $reqdoc_no,
								':doc_address' => $pathFile,
								':member_no' => $payload["member_no"]
							]);
							$insertDocList = $conmysql->prepare("INSERT INTO doclistdetail(doc_no,member_no,new_filename,id_userlogin)
																	VALUES(:doc_no,:member_no,:file_name,:id_userlogin)");
							$insertDocList->execute([
								':doc_no' => $reqdoc_no,
								':member_no' => $payload["member_no"],
								':file_name' => $reqdoc_no.'.pdf',
								':id_userlogin' => $payload["id_userlogin"]
							]);
							$conmysql->commit();
							$arrayReportData = array();
							$arrayReportData["REQ_DOCNO"] = $reqdoc_no;
							$arrayReportData["REQ_DATE"] = $lib->convertdate(date("Y-m-d"),"D m Y");
							$arrayResult['REQ_DOCUMENT'] = $arrayReportData;
							$arrayResult['RESULT'] = TRUE;
							require_once('../../include/exit_footer.php');
						}else{
							$conmysql->rollback();
							$filename = basename(__FILE__, '.php');
							$logStruc = [
								":error_menu" => $filename,
								":error_code" => "WS0044",
								":error_desc" => "สร้าง PDF ไม่ได้ "."\n".json_encode($dataComing),
								":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
							];
							$log->writeLog('errorusage',$logStruc);
							$message_error = "สร้างไฟล์ PDF ไม่ได้ ".$filename."\n"."DATA => ".json_encode($dataComing);
							$lib->sendLineNotify($message_error);
							$arrayResult['RESPONSE_CODE'] = "WS0044";
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
							$arrayResult['RESULT'] = FALSE;
							require_once('../../include/exit_footer.php');
							
						}
					}else{
						$conmysql->rollback();
						$arrayResult['REGISTER_DATA'] = $arrGroupDetail;
						$arrayResult['RESULT'] = TRUE;
						require_once('../../include/exit_footer.php');
					}
				}else{
					$conmysql->rollback();
					$filename = basename(__FILE__, '.php');
					$logStruc = [
						":error_menu" => $filename,
						":error_code" => "WS1036",
						":error_desc" => "ขอใบคำขอออนไลน์ไม่ได้เพราะ Insert ลงตาราง gcreqdoconline ไม่ได้"."\n"."Query => ".$InsertFormOnline->queryString."\n"."Param => ". json_encode([
							':reqdoc_no' => $reqdoc_no,
							':member_no' => $payload["member_no"],
							':documenttype_code' => $dataComing["documenttype_code"],
							':form_value' => json_encode($dataComing["form_value_root_"]),
							':document_url' => $pathFile,
							':req_remark' => $dataComing["documenttype_code"] == "PAYD" ? json_encode($arrOldContract) : "",
						]),
						":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
					];
					$log->writeLog('errorusage',$logStruc);
					$message_error = "ขอใบคำขอออนไลน์ไม่ได้เพราะ Insert ลง gcreqdoconline ไม่ได้"."\n"."Query => ".$InsertFormOnline->queryString."\n"."Param => ". json_encode([
						':reqdoc_no' => $reqdoc_no,
						':member_no' => $payload["member_no"],
						':documenttype_code' => $dataComing["documenttype_code"],
						':form_value' => json_encode($dataComing["form_value_root_"]),
						':document_url' => $pathFile,
						':req_remark' => $dataComing["documenttype_code"] == "PAYD" ? json_encode($arrOldContract) : "",
					]);
					$lib->sendLineNotify($message_error);
					$arrayResult['RESPONSE_CODE'] = "WS1036";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
				}
			}else{
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS0063",
					":error_desc" => "เลขเอกสารเป็นค่าว่าง ไม่สามารถสร้างเลขเอกสารเป็นค่าว่างได้",
					":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$message_error = "เลขเอกสารเป็นค่าว่าง ไม่สามารถสร้างเลขเอกสารเป็นค่าว่างได้";
				$lib->sendLineNotify($message_error);
				$arrayResult['RESPONSE_CODE'] = "WS0063";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
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