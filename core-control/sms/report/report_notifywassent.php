<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'sms','reportnotifysuccess')){
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
		$fetchReport = $conmysql->prepare("SELECT id_history, his_type, his_title, his_detail, his_path_image, his_read_status, his_del_status, member_no, 
											receive_date, read_date, send_by, id_smstemplate, is_sendahead
											FROM gchistory WHERE 1=1
											".(isset($dataComing["id_template"]) && $dataComing["id_template"] != '' ? "and id_smstemplate = :id_template" : null)."
											".(isset($dataComing["member_no"]) && $dataComing["member_no"] != '' ? "and member_no = :member_no" : null)."
											".(isset($dataComing["send_by"]) && $dataComing["send_by"] != '' ? "and send_by = :send_by" : null)."
											".(isset($dataComing["is_sendahead"]) && $dataComing["is_sendahead"] != '' ? "and is_sendahead = :is_sendahead" : null)."
											".(isset($dataComing["start_date"]) && $dataComing["start_date"] != '' ? "and date_format(receive_date,'%Y-%m-%d') >= :start_date" : null)."
											".(isset($dataComing["end_date"]) && $dataComing["end_date"] != '' ? "and date_format(receive_date,'%Y-%m-%d') <= :end_date" : null)." ORDER BY receive_date DESC");
		$fetchReport->execute($arrayExecute);
		while($rowReport = $fetchReport->fetch(PDO::FETCH_ASSOC)){
			$arrayReport = array();
			$arrayReport["ID_HISTORY"] = $rowReport["id_history"] ?? null;
			$arrayReport["HIS_TYPE"] = $rowReport["his_type"] ?? null;
			$arrayReport["HIS_TITLE"] = $rowReport["his_title"] ?? null;
			$arrayReport["HIS_DETAIL"] = $rowReport["his_detail"] ?? null;
			$arrayReport["HIS_PATH_IMAGE"] = $rowReport["his_path_image"] ?? null;
			$arrayReport["HIS_READ_STATUS"] = $rowReport["his_read_status"] ?? null;
			$arrayReport["HIS_DEL_STATUS"] = $rowReport["his_del_status"] ?? null;
			$arrayReport["MEMBER_NO"] = $rowReport["member_no"] ?? null;
			$arrayReport["RECEIVE_DATE"] = isset($rowReport["receive_date"]) ? $lib->convertdate($rowReport["receive_date"],'d m Y',true) : null;
			$arrayReport["READ_DATE"] = isset($rowReport["read_date"]) ? $lib->convertdate($rowReport["read_date"],'d m Y',true) : null;
			$arrayReport["SEND_BY"] = $rowReport["send_by"] ?? null;
			$arrayReport["ID_SMSTEMPLATE"] = $rowReport["id_smstemplate"] ?? null;
			$arrayReport["IS_SENDAHEAD"] = $rowReport["is_sendahead"] ?? null;
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