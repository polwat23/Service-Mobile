<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'sms','processsmsservicefee')){
		$arrayGroup = array();
		$sumFee = 0;
		$sumRound = 0;
		$fetchSmsContant = $conmysql->prepare("SELECT
													smscs_value
												FROM
													smsconstantsystem
												WHERE
													smscs_name = 'sms_fee_amt_per_trans'");
		$fetchSmsContant->execute();	
		$rowfee = $fetchSmsContant->fetch(PDO::FETCH_ASSOC);
		$fetchSmsTranWassent = $conmysql->prepare("SELECT
													count(id_smssent) as round_send,
													member_no,
													tel_mobile
												FROM
													smstranwassent
												WHERE payment_keep = '1' AND process_flag = '0' GROUP BY member_no,tel_mobile");
		$fetchSmsTranWassent->execute();
		while($rowSmsTranWassent = $fetchSmsTranWassent->fetch(PDO::FETCH_ASSOC)){
			$FeeNet = $rowfee["smscs_value"] * $rowSmsTranWassent["round_send"];
			$arrGroupSmsTranWassent = array();
			$arrGroupSmsTranWassent["MEMBER_NO"] = $rowSmsTranWassent["member_no"];
			$arrGroupSmsTranWassent["PROCESS_ROUND"] = $rowSmsTranWassent["round_send"];
			$arrGroupSmsTranWassent["TEL_MOBILE"] = $rowSmsTranWassent["tel_mobile"];
			$arrGroupSmsTranWassent["FEE"] = $FeeNet;
			$arrGroupSmsTranWassent["FEE_FORMAT"] =  number_format($FeeNet,2);
			$arrayGroup[] = $arrGroupSmsTranWassent;
			$sumFee += $FeeNet;
			$sumRound += $rowSmsTranWassent["round_send"];
		}
		$arrayResult["SMS_TRAN_WASSENT"] = $arrayGroup;
		$arrayResult["SUM_FEE"] =number_format($sumFee,2);
		$arrayResult["SUM_ROUND"] = $sumRound;
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);
		
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>