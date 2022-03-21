<?php
require_once('../autoload.php');


use Dompdf\Dompdf;

$dompdf = new DOMPDF();

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DocBalanceConfirm')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$getBalanceMaster = $conoracle->prepare("SELECT max(TO_CHAR(balance_date, 'YYYY-MM-DD'))  as BALANCE_DATE FROM YRCONFIRMMASTER WHERE member_no = :member_no");
		$getBalanceMaster->execute([':member_no' => $member_no]);
		$rowBalMaster = $getBalanceMaster->fetch(PDO::FETCH_ASSOC);
		
		$getBalStatus = $conmysql->prepare("SELECT id_confirm FROM gcconfirmbalancelist WHERE member_no = :member_no and balance_date = :balance_date");
		$getBalStatus->execute([
			':member_no' => $member_no,
			':balance_date' => date('Y-m-d',strtotime($rowBalMaster["BALANCE_DATE"]))
		]);
		$rowBalStatus = $getBalStatus->fetch(PDO::FETCH_ASSOC);
		if(isset($rowBalStatus["id_confirm"])){
			$arrayResult['IS_CONFIRM'] = TRUE;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}
		if(isset($rowBalMaster["BALANCE_DATE"]) && isset($rowBalMaster["BALANCE_DATE"]) != ""){
			$memberInfo = $conoracle->prepare("SELECT mp.PRENAME_SHORT as PRENAME_DESC,mb.MEMB_NAME,mb.MEMB_SURNAME,
													mg.MEMBGROUP_DESC
													FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
													LEFT JOIN MBUCFMEMBGROUP mg ON mb.MEMBGROUP_CODE = mg.MEMBGROUP_CODE
													WHERE mb.member_no = :member_no");
			$memberInfo->execute([':member_no' => $member_no]);
			$rowMember = $memberInfo->fetch(PDO::FETCH_ASSOC);
			$arrHeader = array();
			$arrDetail = array();
			$arrHeader["full_name"] = $rowMember["PRENAME_DESC"].$rowMember["MEMB_NAME"]." ".$rowMember["MEMB_SURNAME"];
			$arrHeader["member_group"] = $rowMember["MEMBGROUP_DESC"];
			$arrHeader["member_no"] = $member_no;
			$arrHeader["date_confirm"] = $lib->convertdate(date('Y-m-d',strtotime($rowBalMaster["BALANCE_DATE"])),'d M Y');
			$getBalanceDetail = $conoracle->prepare("SELECT BALANCE_AMT,BIZZTYPE_CODE,BIZZACCOUNT_NO,FROM_SYSTEM AS CONFIRMTYPE_CODE FROM yrconfirmstatement 
													WHERE member_no = :member_no and TO_CHAR(balance_date, 'YYYY-MM-DD') = :balance_date and FROM_SYSTEM NOT IN('GRT')
													ORDER BY SEQ_NO ASC");
			$getBalanceDetail->execute([
				':member_no' => $member_no,
				':balance_date' => $rowBalMaster["BALANCE_DATE"]
			]);
			while($rowBalDetail = $getBalanceDetail->fetch(PDO::FETCH_ASSOC)){
				$arrBalDetail = array();
				if($rowBalDetail["CONFIRMTYPE_CODE"] == "DEP"){
					$arrBalDetail["BIZZACCOUNT_NO"] = $rowBalDetail["BIZZACCOUNT_NO"];
					$arrBalDetail["BALANCE_AMT"] = number_format($rowBalDetail["BALANCE_AMT"],2);
					$arrDetail["DEP"][] = $arrBalDetail;
				}else if($rowBalDetail["CONFIRMTYPE_CODE"] == "LON"){
					$arrBalDetail["BIZZACCOUNT_NO"] = $rowBalDetail["BIZZACCOUNT_NO"];
					$arrBalDetail["BALANCE_AMT"] = number_format($rowBalDetail["BALANCE_AMT"],2);
					$arrDetail["LON"][] = $arrBalDetail;
				}else if($rowBalDetail["CONFIRMTYPE_CODE"] == "SHR"){
					$arrBalDetail["BIZZACCOUNT_NO"] = "SHR";
					$arrBalDetail["BALANCE_AMT"] = number_format($rowBalDetail["BALANCE_AMT"],2);
					$arrDetail["SHR"] = $arrBalDetail;
				}
			}
			$arrayResult['DATA_CONFIRM'] = $arrDetail;
			$arrayResult['IS_CONFIRM'] = FALSE;
			$arrayResult['DISABLED_CONFIRM'] = FALSE;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$arrayResult['RESULT'] = FALSE;
			http_response_code(204);
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