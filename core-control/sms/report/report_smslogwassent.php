<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'sms','reportsmssuccess')){
		$arrayExecute = array();
		$arrayAll = array();
		if(isset($dataComing["id_template"]) && $dataComing["id_template"] != ''){
			$arrayExecute["id_template"] = $dataComing["id_template"];
		}
		if(isset($dataComing["member_no"]) && $dataComing["member_no"] != ''){
			$arrayExecute["member_no"] = strtolower($lib->mb_str_pad($dataComing["member_no"]));
		}
		if(isset($dataComing["send_by"]) && $dataComing["send_by"] != ''){
			$arrayExecute["send_by"] = $dataComing["send_by"];
		}
		if(isset($dataComing["start_date"]) && $dataComing["start_date"] != ''){
			$arrayExecute["start_date"] = $dataComing["start_date"];
		}
		if(isset($dataComing["end_date"]) && $dataComing["end_date"] != ''){
			$arrayExecute["end_date"] = $dataComing["end_date"];
		}
		if(isset($dataComing["is_sendahead"]) && $dataComing["is_sendahead"] != ''){
			$arrayExecute["is_sendahead"] = $dataComing["is_sendahead"];
		}
		$fetchReport = $conmysql->prepare("SELECT id_logsent,sms_message,member_no,tel_mobile,send_date,send_by,is_sendahead
											FROM smslogwassent WHERE 1=1
											".(isset($dataComing["id_template"]) && $dataComing["id_template"] != '' ? "and id_smstemplate = :id_template" : null)."
											".(isset($dataComing["member_no"]) && $dataComing["member_no"] != '' ? "and member_no = :member_no" : null)."
											".(isset($dataComing["send_by"]) && $dataComing["send_by"] != '' ? "and send_by = :send_by" : null)."
											".(isset($dataComing["is_sendahead"]) && $dataComing["is_sendahead"] != '' ? "and is_sendahead = :is_sendahead" : null)."
											".(isset($dataComing["start_date"]) && $dataComing["start_date"] != '' ? "and date_format(send_date,'%Y-%m-%d') >= :start_date" : null)."
											".(isset($dataComing["end_date"]) && $dataComing["end_date"] != '' ? "and date_format(send_date,'%Y-%m-%d') <= :end_date" : null)." ORDER BY send_date DESC");
		$fetchReport->execute($arrayExecute);
		while($rowReport = $fetchReport->fetch(PDO::FETCH_ASSOC)){
			$arrayReport = array();
			$arrayReport["ID_LOGSENT"] = $rowReport["id_logsent"];
			$arrayReport["SMS_MESSAGE"] = $rowReport["sms_message"] ?? null;
			$arrayReport["MEMBER_NO"] = $rowReport["member_no"] ?? null;
			$arrayReport["TEL_MOBILE"] = $lib->formatphone($rowReport["tel_mobile"],'-');
			$arrayReport["SEND_DATE"] = isset($rowReport["send_date"]) ? $lib->convertdate($rowReport["send_date"],'d m Y',true) : null;
			$arrayReport["SEND_BY"] = $rowReport["send_by"] ?? null;
			$arrayReport["IS_SENDAHEAD"] = $rowReport["is_sendahead"];
			$arrayAll[] = $arrayReport;
		}
		$arrayResult['LIST_REPORT'] = $arrayAll;
		$arrayResult['RESULT'] = TRUE;
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
