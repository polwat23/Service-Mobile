<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'sms','processsmsservicefee')){
		$arrayGroup = array();
		$fetchSmsTranWassent = $conmysql->prepare("SELECT
													sms_message,
													member_no,
													tel_mobile,
													send_date,
													send_by
												FROM
													smstranwassent
												WHERE process_flag = '0'");
		$fetchSmsTranWassent->execute();
		while($rowSmsTranWassent = $fetchSmsTranWassent->fetch(PDO::FETCH_ASSOC)){
			$arrGroupSmsTranWassent = array();
			$arrGroupSmsTranWassent["MEMBER_NO"] = $rowSmsTranWassent["member_no"];
			$arrGroupSmsTranWassent["SMS_MESSAGE"] = $rowSmsTranWassent["sms_message"];
			$arrGroupSmsTranWassent["TEL_MOBILE"] = $rowSmsTranWassent["tel_mobile"];
			$arrGroupSmsTranWassent["SEND_BY"] = $rowSmsTranWassent["send_by"];
			$arrGroupSmsTranWassent["SEND_DATE"] = $lib->convertdate($rowSmsTranWassent["send_date"],'d m Y',true);
			$arrayGroup[] = $arrGroupSmsTranWassent;
		}
		$arrayResult["SMS_TRAN_WASSENT"] = $arrayGroup;
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