<?php
require_once('../autoload.php');

$dbhost = "127.0.0.1";
$dbuser = "root";
$dbpass = "EXAT2022";
$dbname = "mobile_exat_test";

$conmysql = new PDO("mysql:dbname={$dbname};host={$dbhost}", $dbuser, $dbpass);
$conmysql->exec("set names utf8mb4");


$dbnameOra = "(DESCRIPTION =
			(ADDRESS_LIST =
			  (ADDRESS = (PROTOCOL = TCP)(HOST = 192.168.1.201)(PORT = 1521))
			)
			(CONNECT_DATA =
			  (SERVICE_NAME = iorcl)
			)
		  )";
$conoracle = new PDO("oci:dbname=".$dbnameOra.";charset=utf8", "iscotest", "iscotest");
$conoracle->query("ALTER SESSION SET NLS_DATE_FORMAT = 'DD-MM-YYYY HH24:MI:SS'");
$conoracle->query("ALTER SESSION SET NLS_DATE_LANGUAGE = 'AMERICAN'");

if($lib->checkCompleteArgument(['menu_component','amt_transfer','list_payment'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'PayShareLoan')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$sum_fee_amt = 0;
		if(isset($dataComing["deptaccount_no"]) && $dataComing["deptaccount_no"] != ""){
			$deptaccount_no = preg_replace('/-/','',$dataComing["deptaccount_no"]);
			$arrInitDep = $cal_dep->initDept($deptaccount_no,$dataComing["amt_transfer"],'WIM');
			if($arrInitDep["RESULT"]){
				$arrRightDep = $cal_dep->depositCheckWithdrawRights($deptaccount_no,$dataComing["amt_transfer"],$dataComing["menu_component"]);
				if($arrRightDep["RESULT"]){
					$arrayShare = array();
					$grpListPayment = array();
					foreach($dataComing["list_payment"] as $listPayment){
						if($listPayment["payment_type"] == 'share'){
							$getMembGroup = $conoracle->prepare("SELECT MEMBGROUP_CODE FROM mbmembmaster WHERE member_no = :member_no");
							$getMembGroup->execute([':member_no' => $member_no]);
							$rowMembGrp = $getMembGroup->fetch(PDO::FETCH_ASSOC);
							$grpAllow = substr($rowMembGrp["MEMBGROUP_CODE"],0,2);
							if($grpAllow == "S2" || $grpAllow == "Y2"){
								$getLastBuy = $conoracle->prepare("SELECT NVL(SUM(SHARE_AMOUNT) * 50,0) as AMOUNT_PAID FROM shsharestatement WHERE member_no = :member_no 
																	and TRUNC(to_char(slip_date,'YYYYMM')) = TRUNC(to_char(SYSDATE,'YYYYMM'))");
								$getLastBuy->execute([':member_no' => $member_no]);
								$rowLastBuy = $getLastBuy->fetch(PDO::FETCH_ASSOC);
								$fetchShare = $conoracle->prepare("SELECT sharestk_amt,periodshare_amt FROM shsharemaster WHERE member_no = :member_no");
								$fetchShare->execute([':member_no' => $member_no]);
								$rowShare = $fetchShare->fetch(PDO::FETCH_ASSOC);
								if($rowLastBuy["AMOUNT_PAID"] + $listPayment["amt_transfer"] > $rowShare["PERIODSHARE_AMT"] * 50){
									$arrayResult['RESPONSE_CODE'] = "BUY_SHARE_OVER_LIMIT";
									$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
									$arrayResult['RESULT'] = FALSE;
									require_once('../../include/exit_footer.php');
								}
							}
							$listPayment["fee_amt"] = 0;
							$sum_fee_amt += $listPayment["fee_amt"];
							$grpListPayment[] = $listPayment;
						}else if($listPayment["payment_type"] == 'loan'){
							$fetchLoanRepay = $conoracle->prepare("SELECT PRINCIPAL_BALANCE,INTEREST_RETURN,RKEEP_PRINCIPAL
																	FROM lncontmaster
																	WHERE loancontract_no = :loancontract_no");
							$fetchLoanRepay->execute([':loancontract_no' => $listPayment["destination"]]);
							$rowLoan = $fetchLoanRepay->fetch(PDO::FETCH_ASSOC);
							$interest = $cal_loan->calculateIntAPI($listPayment["destination"],$listPayment["amt_transfer"]);
							if($interest["INT_PERIOD"] > 0){
								if($listPayment["amt_transfer"] > ($rowLoan["PRINCIPAL_BALANCE"] - $rowLoan["RKEEP_PRINCIPAL"]) + $interest["INT_PERIOD"]){
									$arrayResult['RESPONSE_CODE'] = "WS0098";
									$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
									$arrayResult['RESULT'] = FALSE;
									require_once('../../include/exit_footer.php');
								}
							}
							if($interest["INT_PERIOD"] > 0){
								$prinPay = 0;
								if($dataComing["amt_transfer"] < $interest["INT_PERIOD"]){
									$interest["INT_PERIOD"] = $listPayment["amt_transfer"];
								}else{
									$prinPay = $listPayment["amt_transfer"] - $interest["INT_PERIOD"];
								}
								if($prinPay < 0){
									$prinPay = 0;
								}
								$listPayment["payment_int"] = $interest["INT_PERIOD"];
								$listPayment["payment_prin"] = $prinPay;
							}else{
								$listPayment["payment_prin"] = $listPayment["amt_transfer"];
							}
							$listPayment["fee_amt"] = 0;
							$sum_fee_amt += $listPayment["fee_amt"];
							$grpListPayment[] = $listPayment;
						}else{
							$arrayResult['RESPONSE_CODE'] = "WS0006";
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
							$arrayResult['RESULT'] = FALSE;
							http_response_code(403);
							require_once('../../include/exit_footer.php');
						}
					}
					$arrayResult['LIST_PAYMENT'] = $grpListPayment;
					$arrayResult['FEE_AMT'] = $sum_fee_amt;
					$arrayResult['SUM_AMT'] = $dataComing["amt_transfer"] + $sum_fee_amt;
					$arrayResult['RESULT'] = TRUE;
					require_once('../../include/exit_footer.php');
				}else{
					$arrayResult['RESPONSE_CODE'] = $arrRightDep["RESPONSE_CODE"];
					if($arrRightDep["RESPONSE_CODE"] == 'WS0056'){
						$arrayResult['RESPONSE_MESSAGE'] = str_replace('${min_amount_deposit}',number_format($arrRightDep["MINWITD_AMT"],2),$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
					}else{
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					}
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
				}
			}else{
				$arrayResult['RESPONSE_CODE'] = $arrInitDep["RESPONSE_CODE"];
				if($arrInitDep["RESPONSE_CODE"] == 'WS0056'){
					$arrayResult['RESPONSE_MESSAGE'] = str_replace('${min_amount_deposit}',number_format($arrInitDep["MINWITD_AMT"],2),$configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale]);
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				}
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		}else{
			
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