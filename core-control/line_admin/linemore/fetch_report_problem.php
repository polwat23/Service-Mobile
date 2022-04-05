<?php
require_once('../../autoload.php');
if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin',null)){
		$arrayGroup = array();
		$arrGroupMonth = array();
		$fetchUserlogin = $conmysql->prepare("SELECT lbp.id_reportproblem,lbp.text_report,lbp.detail,lbp.date,ac.member_no,lbp.line_token FROM lbreportproblem lbp
												LEFT JOIN gcmemberaccount ac ON lbp.line_token = ac.line_token
												ORDER BY lbp.date DESC");
		$fetchUserlogin->execute();
		while($rowProblem = $fetchUserlogin->fetch(PDO::FETCH_ASSOC)){
			$arrProblem = array();
			$arrProblem["ID_REPORTPROBLEM"] = $rowProblem["id_reportproblem"];
			$arrProblem["TEXT_REPORT"] = $rowProblem["text_report"];
			$arrProblem["DETAIL"] = $rowProblem["detail"];
			$arrProblem["DATE"] = $rowProblem["date"];
			$arrProblem["MEMBER_NO"] = $rowProblem["member_no"];
			$arrProblem["LINE_TOKEN"] = $rowProblem["line_token"];
			$arrayGroup[] = $arrProblem;
		}			
		$arrayResult["REPORT_PROBLEM_DATA"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
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