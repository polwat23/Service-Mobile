<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','userusagereport',$conoracle)){
		$arrayExecute = array();
		$arrayGrpAll = array();
			if(isset($dataComing["start_date"]) && $dataComing["start_date"] != ""){
				$arrayExecute["start_date"] = $dataComing["start_date"];
			}
			if(isset($dataComing["end_date"]) && $dataComing["end_date"] != ""){
				$arrayExecute["end_date"] = $dataComing["end_date"];
			}
			if($dataComing["date_type"] == 'year'){
				$fetchReportUserLogin = $conoracle->prepare("SELECT member_no, device_name, login_date, logout_date, id_token ,is_login
														FROM  gcuserlogin 
														WHERE is_login != '-55'
															
															".(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? 
															"and TO_CHAR(login_date,'YYYY') >= :start_date" : null)."
															".(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? 
															"and TO_CHAR(login_date,'YYYY') <= :end_date" : null). 
													    " ORDER BY login_date DESC");
			}else if($dataComing["date_type"] == 'month'){
				$fetchReportUserLogin = $conoracle->prepare("SELECT member_no, device_name, login_date, logout_date, id_token ,is_login
														FROM  gcuserlogin 
														WHERE is_login != '-55'
															".(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? 
															"and TO_CHAR(login_date,'YYYY-MM') >= :start_date" : null)."
															".(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? 
															"and TO_CHAR(login_date,'YYYY-MM') <= :end_date" : null)." 
													    ORDER BY login_date DESC");
			}else if($dataComing["date_type"] == 'day'){
				$fetchReportUserLogin = $conoracle->prepare("SELECT member_no, device_name, login_date, logout_date, id_token ,is_login
														FROM  gcuserlogin 
														WHERE is_login != '-55'
															".(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? 
															"and TO_CHAR(login_date,'YYYY-MM-DD') >= :start_date" : null)."
															".(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? 
															"and TO_CHAR(login_date,'YYYY-MM-DD') <= :end_date" : null)." 
														ORDER BY login_date DESC");
			}
	
		$fetchReportUserLogin->execute($arrayExecute);

		$summary = 0;
		while($rowUserLogin = $fetchReportUserLogin->fetch(PDO::FETCH_ASSOC)){
			$arrayUserLogin = array();
			$arrayUserLogin["LOGIN_DATE"]==null?"-":$lib->convertdate($rowUserLogin["LOGIN_DATE"],'d m Y',true); 
			$arrayUserLogin["LOGIN_DATE"] = $rowUserLogin["LOGIN_DATE"]==null?"-":$lib->convertdate($rowUserLogin["LOGIN_DATE"],'d m Y',true);		
			$arrayUserLogin["LOGOUT_DATE"] =$rowUserLogin["LOGOUT_DATE"]==null?"-":$lib->convertdate($rowUserLogin["LOGOUT_DATE"],'d m Y',true); 
			$arrayUserLogin["DEVICE_NAME"] = $rowUserLogin["DEVICE_NAME"];
			$arrayUserLogin["IS_LOGIN"] = $rowUserLogin["IS_LOGIN"];
			$arrayUserLogin["MEMBER_NO"] = $rowUserLogin["MEMBER_NO"];
	
			$arrayGrpAll[] = $arrayUserLogin;
		}
		$arrayResult['REPORT_USER_LOGIN'] = $arrayGrpAll;
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