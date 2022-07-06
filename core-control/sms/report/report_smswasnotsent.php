<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'sms','reportsmsnotsuccess',$conoracle)){
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
		$fetchReport = $conoracle->prepare("SELECT topic,message,member_no,tel_mobile,send_date,send_by,cause_notsent,is_sendahead,id_smsnotsent,send_platform,his_path_image
											FROM smswasnotsent WHERE 1=1
											".(isset($dataComing["id_template"]) && $dataComing["id_template"] != '' ? "and id_smstemplate = :id_template" : null)."
											".(isset($dataComing["member_no"]) && $dataComing["member_no"] != '' ? "and member_no = :member_no" : null)."
											".(isset($dataComing["send_by"]) && $dataComing["send_by"] != '' ? "and send_by = :send_by" : null)."
											".(isset($dataComing["is_sendahead"]) && $dataComing["is_sendahead"] != '' ? "and is_sendahead = :is_sendahead" : null)."
											".(isset($dataComing["start_date"]) && $dataComing["start_date"] != '' ? "and TO_CHAR(send_date,'YYYY-MM-DD') >= :start_date" : null)."
											".(isset($dataComing["end_date"]) && $dataComing["end_date"] != '' ? "and TO_CHAR(send_date,'YYYY-MM-DD') <= :end_date" : null)." ORDER BY send_date DESC");
		$fetchReport->execute($arrayExecute);
		while($rowReport = $fetchReport->fetch(PDO::FETCH_ASSOC)){
			$arrayReport = array();
			$arrayReport["SMS_MESSAGE"] = $rowReport["MESSAGE"] ?? null;
			$arrayReport["MEMBER_NO"] = $rowReport["MEMBER_NO"] ?? null;
			$arrayReport["TEL_MOBILE"] = $lib->formatphone($rowReport["TEL_MOBILE"],'-');
			$arrayReport["SEND_DATE"] = isset($rowReport["SEND_DATE"]) ? $lib->convertdate($rowReport["SEND_DATE"],'d m Y',true) : null;
			$arrayReport["SEND_BY"] = $rowReport["SEND_BY"] ?? null;
			$arrayReport["CAUSE_NOTSENT"] = $rowReport["CAUSE_NOTSENT"] ?? null;
			$arrayReport["IS_SENDAHEAD"] = $rowReport["IS_SENDAHEAD"];
			$arrayReport["TOPIC"] = $rowReport["TOPIC"];
			$arrayReport["ID_SMSNOTSENT"] = $rowReport["ID_SMSNOTSENT"];
			$arrayReport["SEND_PLATFORM"] = $rowReport["SEND_PLATFORM"];
			$arrayReport["HIS_PATH_IMAGE"] = $rowReport["HIS_PATH_IMAGE"];
			$arrayAll[] = $arrayReport;
		}
		$arrayResult['LIST_REPORT'] = $arrayAll;
		$arrayResult['RESULT'] = TRUE;
		require_once('../../../include/exit_footer.php');
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../include/exit_footer.php');
		
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../include/exit_footer.php');
	
}
?>
