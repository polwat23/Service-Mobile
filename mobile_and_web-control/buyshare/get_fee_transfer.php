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

if($lib->checkCompleteArgument(['menu_component','deptaccount_no','amt_transfer'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferDepBuyShare')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		
		$getMembGroup = $conoracle->prepare("SELECT MEMBGROUP_CODE FROM mbmembmaster WHERE member_no = :member_no");
		$getMembGroup->execute([':member_no' => $member_no]);
		$rowMembGrp = $getMembGroup->fetch(PDO::FETCH_ASSOC);
		$grpAllow = substr($rowMembGrp["MEMBGROUP_CODE"],0,2);
		if($grpAllow != "S2" && $grpAllow != "Y2"){
			$arrayResult['RESPONSE_CODE'] = "WS0075";
			if(isset($configError["BUY_SHARES_ERR"][0]["0004"][0][$lang_locale])){
				$arrayResult['RESPONSE_MESSAGE'] = $configError["BUY_SHARES_ERR"][0]["0004"][0][$lang_locale];
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			}
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
		}
		$deptaccount_no = preg_replace('/-/','',$dataComing["deptaccount_no"]);
		$getCurrShare = $conoracle->prepare("SELECT SHARESTK_AMT FROM shsharemaster WHERE member_no = :member_no");
		$getCurrShare->execute([':member_no' => $member_no]);
		$rowCurrShare = $getCurrShare->fetch(PDO::FETCH_ASSOC);
		$sharereq_value = ($rowCurrShare["SHARESTK_AMT"] * 50) + $dataComing["amt_transfer"];
		
		$getConstantShare = $conoracle->prepare("SELECT MAXSHARE_HOLD,50 as SHAREROUND_FACTOR FROM SHSHARETYPE");
		$getConstantShare->execute();
		$rowContShare = $getConstantShare->fetch(PDO::FETCH_ASSOC);
		if($sharereq_value > $rowContShare["MAXSHARE_HOLD"]){
			$arrayResult['RESPONSE_CODE'] = "WS0075";
			if(isset($configError["BUY_SHARES_ERR"][0]["0001"][0][$lang_locale])){
				$arrayResult['RESPONSE_MESSAGE'] = str_replace('${MAXHOLD_AMT}',number_format($rowContShare["MAXSHARE_HOLD"],2),$configError["BUY_SHARES_ERR"][0]["0001"][0][$lang_locale]);
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			}
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
		if($sharereq_value < $rowContShare["SHAREROUND_FACTOR"]){
			$arrayResult['RESPONSE_CODE'] = "WS0075";
			if(isset($configError["BUY_SHARES_ERR"][0]["0003"][0][$lang_locale])){
				$arrayResult['RESPONSE_MESSAGE'] = str_replace('${SHAREROUND_FACTOR}',number_format($rowContShare["SHAREROUND_FACTOR"],2),$configError["BUY_SHARES_ERR"][0]["0003"][0][$lang_locale]);
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			}
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
		$getShareStmMonth = $conoracle->prepare("SELECT COUNT(SEQ_NO) as C_SHARE FROM shsharestatement 
												WHERE member_no = :member_no and shritemtype_code = 'SPX' and TO_CHAR(operate_date,'YYYYMM') = TO_CHAR(SYSDATE,'YYYYMM')");
		$getShareStmMonth->execute([':member_no' => $member_no]);
		$rowShareStm = $getShareStmMonth->fetch(PDO::FETCH_ASSOC);
		if($rowShareStm["C_SHARE"] > 0){
			$arrayResult['RESPONSE_CODE'] = "WS0102";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
		}
		$arrInitDep = $cal_dep->initDept($deptaccount_no,$dataComing["amt_transfer"],'WFS');
		if($arrInitDep["RESULT"]){
			$arrRightDep = $cal_dep->depositCheckWithdrawRights($deptaccount_no,$dataComing["amt_transfer"],$dataComing["menu_component"]);
			if($arrRightDep["RESULT"]){
				if(isset($arrInitDep["PENALTY_AMT"]) && $arrInitDep["PENALTY_AMT"] > 0){
					$arrayCaution['RESPONSE_MESSAGE'] = $configError["CAUTION_WITHDRAW"][0][$lang_locale];
					$arrayCaution['CANCEL_TEXT'] = $configError["BUTTON_TEXT"][0]["CANCEL_TEXT"][0][$lang_locale];
					$arrayCaution['CONFIRM_TEXT'] = $configError["BUTTON_TEXT"][0]["CONFIRM_TEXT"][0][$lang_locale];
					$arrayResult['CAUTION'] = $arrayCaution;
					$arrayResult['FEE_AMT'] = $arrInitDep["PENALTY_AMT"];
					$arrayResult['FEE_AMT_FORMAT'] = number_format($arrInitDep["PENALTY_AMT"],2);
				}
			
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
