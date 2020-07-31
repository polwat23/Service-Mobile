<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'sms','processsmsservicefee')){
		$arrayGroup = array();
		$sumFee = 0;
		$sumRount = 0;
		$fetchSmsContant = $conmysql->prepare("SELECT
													smscs_value
												FROM
													smsconstantsystem
												WHERE
													smscs_name = :smscs_name");
		$fetchSmsContant->execute([
			':smscs_name' => 'sms_fee_amt_per_trans',
		]);	
		$fee = $fetchSmsContant->fetch(PDO::FETCH_ASSOC);
		$fetchSmsTranWassent = $conmysql->prepare("SELECT
													sms_message,
													member_no,
													tel_mobile,
													process_round,
													send_date,
													send_by
												FROM
													smstranwassent
												WHERE payment_keep = :payment_keep AND process_flag = :process_flag");
		$fetchSmsTranWassent->execute([
			':payment_keep' => '1',
			':process_flag' => '0',
		]);
		while($rowSmsTranWassent = $fetchSmsTranWassent->fetch(PDO::FETCH_ASSOC)){
			$arrGroupSmsTranWassent = array();
			$arrGroupSmsTranWassent["MEMBER_NO"] = $rowSmsTranWassent["member_no"];
			$arrGroupSmsTranWassent["SMS_MESSAGE"] = $rowSmsTranWassent["sms_message"];
			$arrGroupSmsTranWassent["TEL_MOBILE"] = $rowSmsTranWassent["tel_mobile"];
			$arrGroupSmsTranWassent["PROCESS_ROUND"] = $rowSmsTranWassent["process_round"];
			$arrGroupSmsTranWassent["SEND_BY"] = $rowSmsTranWassent["send_by"];
			$arrGroupSmsTranWassent["FEE"] = $fee["smscs_value"]* $rowSmsTranWassent["process_round"];
			$arrGroupSmsTranWassent["FEE_FORMAT"] =  number_format($fee["smscs_value"]* $rowSmsTranWassent["process_round"],2);
			$arrGroupSmsTranWassent["SEND_DATE"] = $lib->convertdate($rowSmsTranWassent["send_date"],'d m Y',true);
			$arrayGroup[] = $arrGroupSmsTranWassent;
			$sumFee += ($fee["smscs_value"]* $rowSmsTranWassent["process_round"]);
			$sumRount += $rowSmsTranWassent["process_round"];
		}
		$arrayResult["SMS_TRAN_WASSENT"] = $arrayGroup;
		$arrayResult["SUM_FEE"] =number_format($sumFee,2);
		$arrayResult["SUM_ROUND"] =$sumRount;
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