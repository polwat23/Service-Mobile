<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ShareInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$getSharemasterinfo = $conoracle->prepare("SELECT (sharestk_amt * 10) as SHARE_AMT,(periodshare_amt * 10) as PERIOD_SHARE_AMT,sharebegin_amt
													FROM shsharemaster WHERE member_no = :member_no");
		$getSharemasterinfo->execute([':member_no' => $member_no]);
		$rowMastershare = $getSharemasterinfo->fetch(PDO::FETCH_ASSOC);
		if($rowMastershare){
			$arrGroupStm = array();
			$arrayResult['BRING_FORWARD'] = number_format($rowMastershare["SHAREBEGIN_AMT"],2);
			$arrayResult['SHARE_AMT'] = number_format($rowMastershare["SHARE_AMT"],2);
			$arrayResult['PERIOD_SHARE_AMT'] = number_format($rowMastershare["PERIOD_SHARE_AMT"],2);
			$limit = $func->getConstant('limit_stmshare');
			$arrayResult['LIMIT_DURATION'] = $limit;
			if($lib->checkCompleteArgument(["date_start"],$dataComing)){
				$date_before = $lib->convertdate($dataComing["date_start"],'y-n-d');
			}else{
				$date_before = date('Y-m-d',strtotime('-'.$limit.' months'));
			}
			if($lib->checkCompleteArgument(["date_end"],$dataComing)){
				$date_now = $lib->convertdate($dataComing["date_end"],'y-n-d');
			}else{
				$date_now = date('Y-m-d');
			}
			$getShareStatement = $conoracle->prepare("SELECT stm.operate_date,(stm.share_amount * 10) as PERIOD_SHARE_AMOUNT,
														stm.sharestk_amt as SUM_SHARE_AMT,sht.shritemtype_desc,stm.period
														FROM shsharestatement stm LEFT JOIN shucfshritemtype sht ON stm.shritemtype_code = sht.shritemtype_code
														WHERE stm.member_no = :member_no and stm.ENTRY_DATE
														BETWEEN to_date(:datebefore,'YYYY-MM-DD') and to_date(:datenow,'YYYY-MM-DD') ORDER BY stm.seq_no DESC");
			$getShareStatement->execute([
				':member_no' => $member_no,
				':datebefore' => $date_before,
				':datenow' => $date_now
			]);
			while($rowStm = $getShareStatement->fetch(PDO::FETCH_ASSOC)){
				$arrayStm = array();
				$arrayStm["OPERATE_DATE"] = $lib->convertdate($rowStm["OPERATE_DATE"],'D m Y');
				$arrayStm["PERIOD_SHARE_AMOUNT"] = number_format($rowStm["PERIOD_SHARE_AMOUNT"],2);
				$arrayStm["SUM_SHARE_AMT"] = number_format($rowStm["SUM_SHARE_AMT"],2);
				$arrayStm["SHARETYPE_DESC"] = $rowStm["SHRITEMTYPE_DESC"];
				$arrayStm["PERIOD"] = $rowStm["PERIOD"];
				$arrGroupStm[] = $arrayStm;
			}
			$arrayResult['STATEMENT'] = $arrGroupStm;
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			http_response_code(204);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>