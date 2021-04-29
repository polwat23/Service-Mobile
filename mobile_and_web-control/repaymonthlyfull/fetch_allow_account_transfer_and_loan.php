<?php
require_once('../autoload.php');
require_once(__DIR__.'/../../include/cal_deposit_test.php');
use CalculateDepTest\CalculateDepTest;
$cal_dep = new CalculateDepTest();
$dbuser = 'iscotest';
$dbpass = 'iscotest';
$dbname = "(DESCRIPTION =
			(ADDRESS_LIST =
			  (ADDRESS = (PROTOCOL = TCP)(HOST = 192.168.0.226)(PORT = 1521))
			)
			(CONNECT_DATA =
			  (SERVICE_NAME = gcoop)
			)
		  )";
$conoracle = new PDO("oci:dbname=".$dbname.";charset=utf8", $dbuser, $dbpass);
$conoracle->query("ALTER SESSION SET NLS_DATE_FORMAT = 'DD-MM-YYYY HH24:MI:SS'");
$conoracle->query("ALTER SESSION SET NLS_DATE_LANGUAGE = 'AMERICAN'");

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'PayMonthlyFull')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGroupAccAllow = array();
		$arrGroupAccFav = array();
		$arrayDept = array();
		$formatDept = $func->getConstant('dep_format');
		$formatDeptHidden = $func->getConstant('hidden_dep');
		$getMemberType = $conoracle->prepare("SELECT MEMBER_TYPE,MEMBGROUP_CODE FROM mbmembmaster WHERE member_no = :member_no");
		$getMemberType->execute([':member_no' => $member_no]);
		$rowMembType = $getMemberType->fetch(PDO::FETCH_ASSOC);
		$checkMemberTypeAllow = $conmysql->prepare("SELECT id_extrapayment FROM gcextrapaymentmembertype 
													WHERE membertype_code = :member_type and is_use = '1'");
		$checkMemberTypeAllow->execute([':member_type' => $rowMembType["MEMBER_TYPE"]]);
		if($checkMemberTypeAllow->rowCount() > 0){
			$checkMemberGrpAllow = $conmysql->prepare("SELECT id_extrapayment FROM gcextrapaymentmembergroup 
														WHERE membgroup_code = :member_grp and is_use = '1'");
			$checkMemberGrpAllow->execute([':member_grp' => $rowMembType["MEMBGROUP_CODE"]]);
			if($checkMemberGrpAllow->rowCount() > 0){
				$fetchAccAllowTrans = $conmysql->prepare("SELECT gat.deptaccount_no FROM gcuserallowacctransaction gat
															LEFT JOIN gcconstantaccountdept gad ON gat.id_accountconstant = gad.id_accountconstant
															WHERE gat.member_no = :member_no and gat.is_use = '1' and gad.allow_pay_loan = '1'");
				$fetchAccAllowTrans->execute([':member_no' => $payload["member_no"]]);
				if($fetchAccAllowTrans->rowCount() > 0){
					while($rowAccAllow = $fetchAccAllowTrans->fetch(PDO::FETCH_ASSOC)){
						$arrayAcc[] = "'".$rowAccAllow["deptaccount_no"]."'";
					}
					$getDataBalAcc = $conoracle->prepare("SELECT dpm.deptaccount_no,dpm.deptaccount_name,dpt.depttype_desc,dpm.withdrawable_amt as prncbal,dpm.depttype_code
															FROM dpdeptmaster dpm LEFT JOIN dpdepttype dpt ON dpm.depttype_code = dpt.depttype_code
															WHERE dpm.deptaccount_no IN(".implode(',',$arrayAcc).") and dpm.deptclose_status = 0
															ORDER BY dpm.deptaccount_no ASC");
					$getDataBalAcc->execute();
					while($rowDataAccAllow = $getDataBalAcc->fetch(PDO::FETCH_ASSOC)){
						$arrAccAllow = array();
						$checkDep = $cal_dep->getSequestAmt($rowDataAccAllow["DEPTACCOUNT_NO"]);
						if($checkDep["CAN_WITHDRAW"]){
							$arrAccAllow["DEPTACCOUNT_NO"] = $rowDataAccAllow["DEPTACCOUNT_NO"];
							$arrAccAllow["DEPTACCOUNT_NO_FORMAT"] = $lib->formataccount($rowDataAccAllow["DEPTACCOUNT_NO"],$formatDept);
							$arrAccAllow["DEPTACCOUNT_NO_FORMAT_HIDE"] = $lib->formataccount_hidden($arrAccAllow["DEPTACCOUNT_NO_FORMAT"],$formatDeptHidden);
							$arrAccAllow["DEPTACCOUNT_NAME"] = preg_replace('/\"/','',$rowDataAccAllow["DEPTACCOUNT_NAME"]);
							$arrAccAllow["DEPT_TYPE"] = $rowDataAccAllow["DEPTTYPE_DESC"];
							$arrAccAllow["BALANCE"] = $cal_dep->getWithdrawable($rowDataAccAllow["DEPTACCOUNT_NO"]) - $checkDep["SEQUEST_AMOUNT"];
							$arrAccAllow["BALANCE_FORMAT"] = number_format($arrAccAllow["BALANCE"],2);
							$arrGroupAccAllow[] = $arrAccAllow;
						}
					}
					$arrayKeeping = array();
					$dateshow_kpmonth = $func->getConstant('dateshow_kpmonth');
					$keep_forward = $func->getConstant('process_keep_forward');
					$MonthForCheck = date('m');	
					$DayForCheck = date('d');
					$getLastReceive = $conoracle->prepare("SELECT * FROM (SELECT MAX(recv_period) as MAX_RECV,RECEIPT_NO,RECEIVE_AMT,KPSLIP_NO
																		FROM kptempreceive WHERE member_no = :member_no and keeping_status = '1' 
																		GROUP BY RECEIPT_NO,RECEIVE_AMT,KPSLIP_NO ORDER BY MAX_RECV DESC) WHERE rownum <= 1");
					$getLastReceive->execute([':member_no' => $member_no]);
					$rowLastRecv = $getLastReceive->fetch(PDO::FETCH_ASSOC);
					$checkHasBeenPay = $conoracle->prepare("SELECT RECV_PERIOD FROM kpmastreceive WHERE member_no = :member_no and recv_period = :max_recv and keeping_status = 1");
					$checkHasBeenPay->execute([
						':member_no' => $member_no,
						':max_recv' => $rowLastRecv["MAX_RECV"]
					]);
					$rowBeenPay = $checkHasBeenPay->fetch(PDO::FETCH_ASSOC);
					$max_recv = (int) substr($rowLastRecv["MAX_RECV"],4);
					if($keep_forward == '1'){
						if($MonthForCheck < $max_recv){
							http_response_code(204);
							
						}else{
							if($DayForCheck < $dateshow_kpmonth){
								http_response_code(204);
								
							}
						}
					}else{
						if($DayForCheck < $dateshow_kpmonth){
							http_response_code(204);
							
						}
					}
					if((isset($rowBeenPay["RECV_PERIOD"]) && $rowBeenPay["RECV_PERIOD"] != "") || (empty($rowLastRecv["MAX_RECV"]) && $rowLastRecv["MAX_RECV"] == "")){
						$date_process_kp = $func->getConstant('date_process_kp');
						if($date_process_kp < 10){
							$date_process_kp = '0'.$date_process_kp;
						}
						$MonthForCheckFuture = date('Ym',strtotime("+1 months"));
						$dateFuture = $MonthForCheckFuture.$date_process_kp;
						if(date('Ymd') > $dateFuture){
							http_response_code(204);
						}
					}
					$getPaymentDetail = $conoracle->prepare("SELECT 
																				CASE kut.system_code 
																				WHEN 'LON' THEN NVL(lt.LOANTYPE_DESC,kut.keepitemtype_desc) 
																				WHEN 'DEP' THEN NVL(dp.DEPTTYPE_DESC,kut.keepitemtype_desc) 
																				ELSE kut.keepitemtype_desc
																				END as TYPE_DESC,
																				kut.keepitemtype_grp as TYPE_GROUP,
																				case kut.keepitemtype_grp 
																					WHEN 'DEP' THEN kpd.description
																					WHEN 'LON' THEN kpd.loancontract_no
																				ELSE kpd.description END as PAY_ACCOUNT,
																				kpd.period,
																				kpd.description,
																				NVL(kpd.ITEM_PAYMENT * kut.SIGN_FLAG,0) AS ITEM_PAYMENT,
																				NVL(kpd.ITEM_BALANCE,0) AS ITEM_BALANCE,
																				NVL(kpd.principal_payment,0) AS PRN_BALANCE,
																				NVL(kpd.interest_payment,0) AS INT_BALANCE
																				FROM kptempreceivedet kpd LEFT JOIN KPUCFKEEPITEMTYPE kut ON 
																				kpd.keepitemtype_code = kut.keepitemtype_code
																				LEFT JOIN lnloantype lt ON kpd.shrlontype_code = lt.loantype_code
																				LEFT JOIN dpdepttype dp ON kpd.shrlontype_code = dp.depttype_code
																				WHERE kpd.member_no = :member_no and kpd.recv_period = :recv_period
																				and kpd.kpslip_no = :kpslip_no and kut.SIGN_FLAG = 1
																				ORDER BY kut.SORT_IN_RECEIVE ASC");
					$getPaymentDetail->execute([
						':member_no' => $member_no,
						':recv_period' => $rowLastRecv["MAX_RECV"],
						':kpslip_no' => $rowLastRecv["KPSLIP_NO"]
					]);
					$arrGroupDetail = array();
					while($rowDetail = $getPaymentDetail->fetch(PDO::FETCH_ASSOC)){
						$arrDetail = array();
						$arrDetail["TYPE_DESC"] = $rowDetail["TYPE_DESC"];
						if($rowDetail["TYPE_GROUP"] == 'SHR'){
							$arrDetail["PERIOD"] = $rowDetail["PERIOD"];
						}else if($rowDetail["TYPE_GROUP"] == 'LON'){
							$arrDetail["PAY_ACCOUNT"] = $rowDetail["PAY_ACCOUNT"];
							$arrDetail["PAY_ACCOUNT_LABEL"] = 'เลขสัญญา';
							$arrDetail["PERIOD"] = $rowDetail["PERIOD"];
							$arrDetail["PRN_BALANCE"] = number_format($rowDetail["PRN_BALANCE"],2);
							$arrDetail["INT_BALANCE"] = number_format($rowDetail["INT_BALANCE"],2);
						}else if($rowDetail["TYPE_GROUP"] == 'DEP'){
							$arrDetail["PAY_ACCOUNT"] = $lib->formataccount($rowDetail["PAY_ACCOUNT"],$func->getConstant('dep_format'));
							$arrDetail["PAY_ACCOUNT_LABEL"] = 'เลขบัญชี';
						}else if($rowDetail["TYPE_GROUP"] == "OTH"){
							$arrDetail["PAY_ACCOUNT"] = $rowDetail["PAY_ACCOUNT"];
							$arrDetail["PAY_ACCOUNT_LABEL"] = 'จ่าย';
						}
						if($rowDetail["ITEM_BALANCE"] > 0){
							$arrDetail["ITEM_BALANCE"] = number_format($rowDetail["ITEM_BALANCE"],2);
						}
						$arrDetail["ITEM_PAYMENT"] = number_format($rowDetail["ITEM_PAYMENT"],2);
						$arrGroupDetail[] = $arrDetail;
					}
					if(isset($rowLastRecv["MAX_RECV"]) && $rowLastRecv["MAX_RECV"] != ""){
						$arrayKeeping["RECEIVE_AMT"] = number_format($rowLastRecv["RECEIVE_AMT"],2);
						$arrayKeeping["RECV_PERIOD"] = TRIM($rowLastRecv["MAX_RECV"]);
						$arrayKeeping["SLIP_NO"] = $rowLastRecv["KPSLIP_NO"];
						$arrayKeeping["MONTH_RECEIVE"] = $lib->convertperiodkp(TRIM($rowLastRecv["MAX_RECV"]));
						$arrayKeeping['DETAIL'] = $arrGroupDetail;
					}
					$arrGroupAccBind = array();
					$fetchBindAccount = $conmysql->prepare("SELECT gba.id_bindaccount,gba.sigma_key,gba.deptaccount_no_coop,gba.deptaccount_no_bank,csb.bank_logo_path,gba.bank_code,
															csb.bank_format_account,csb.bank_format_account_hide,csb.bank_short_name
															FROM gcbindaccount gba LEFT JOIN csbankdisplay csb ON gba.bank_code = csb.bank_code
															WHERE gba.member_no = :member_no and gba.bindaccount_status = '1' ORDER BY gba.deptaccount_no_coop");
					$fetchBindAccount->execute([':member_no' => $payload["member_no"]]);
					if($fetchBindAccount->rowCount() > 0){
						while($rowAccBind = $fetchBindAccount->fetch(PDO::FETCH_ASSOC)){
							$arrAccBind = array();
							$arrAccBind["ID_BINDACCOUNT"] = $rowAccBind["id_bindaccount"];
							$arrAccBind["SIGMA_KEY"] = $rowAccBind["sigma_key"];
							$arrAccBind["BANK_NAME"] = $rowAccBind["bank_short_name"];
							$arrAccBind["BANK_CODE"] = $rowAccBind["bank_code"];
							$arrAccBind["BANK_LOGO"] = $config["URL_SERVICE"].$rowAccBind["bank_logo_path"];
							$explodePathLogo = explode('.',$rowAccBind["bank_logo_path"]);
							$arrAccBind["BANK_LOGO_WEBP"] = $config["URL_SERVICE"].$explodePathLogo[0].'.webp';
							if($rowAccBind["bank_code"] == '025'){
								$arrAccBind["DEPTACCOUNT_NO_BANK"] = $rowAccBind["deptaccount_no_bank"];
								$arrAccBind["DEPTACCOUNT_NO_BANK_FORMAT"] = $rowAccBind["deptaccount_no_bank"];
								$arrAccBind["DEPTACCOUNT_NO_BANK_FORMAT_HIDE"] = $rowAccBind["deptaccount_no_bank"];
							}else{
								$arrAccBind["DEPTACCOUNT_NO_BANK"] = $rowAccBind["deptaccount_no_bank"];
								$arrAccBind["DEPTACCOUNT_NO_BANK_FORMAT"] = $lib->formataccount($rowAccBind["deptaccount_no_bank"],$rowAccBind["bank_format_account"]);
								$arrAccBind["DEPTACCOUNT_NO_BANK_FORMAT_HIDE"] = $lib->formataccount_hidden($rowAccBind["deptaccount_no_bank"],$rowAccBind["bank_format_account_hide"]);
							}
							$arrGroupAccBind[] = $arrAccBind;
						}
					}
					if(sizeof($arrGroupAccAllow) > 0 || sizeof($arrGroupAccBind) > 0){
						$arrayResult['ACCOUNT_ALLOW'] = $arrGroupAccAllow;
						$arrayResult['BANK_ACCOUNT_ALLOW'] = $arrGroupAccBind;
						$arrayResult['KEEPING'] = $arrayKeeping;
						$arrayResult['FAV_SAVE_SOURCE'] = FALSE;
						$arrayResult['ALLOW_MEMO'] = FALSE;
						$arrayResult['SCHEDULE']["ENABLED"] = FALSE;
						$arrayResult['RESULT'] = TRUE;
						require_once('../../include/exit_footer.php');
					}else{
						$arrayResult['RESPONSE_CODE'] = "WS0023";
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
						
					}
				}else{
					$arrayResult['RESPONSE_CODE'] = "WS0023";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0111";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0110";
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