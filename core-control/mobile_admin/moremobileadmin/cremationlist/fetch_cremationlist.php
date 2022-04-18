<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','cremationlist')){
	
		$time=strtotime($dataComing["month"] ?? date("Y-m-d"));
		$month=date("m",$time);
		$year=date("Y",$time);
	
		$fetchCremationList = $conmysql->prepare("SELECT cremation_id, full_name, data_date, update_date, update_user FROM gccremationlist WHERE is_use = '1' AND MONTH(data_date) = :month AND YEAR(data_date) = :year");
		$fetchCremationList->execute([
			':month' => $month,
			':year' => $year
		]);
		
		$arrayCremation = array();
		while($rowCremation = $fetchCremationList->fetch(PDO::FETCH_ASSOC)){
			$arrayGroup = array();
			$arrayGroup["CREMATION_ID"] = $rowCremation["cremation_id"];
			$arrayGroup["FULL_NAME"] = $rowCremation["full_name"];
			$arrayGroup["DATA_DATE"] = $rowCremation["data_date"];
			$arrayGroup["UPDATE_DATE"] = $rowCremation["update_date"];
			$arrayGroup["UPDATE_USER"] = $rowCremation["update_user"];
			$arrayCremation[] = $arrayGroup;
		}
		
		$arrayResult["CREMATION_LIST"] = $arrayCremation;
		$arrayResult["RESULT"] = TRUE;
		require_once('../../../../include/exit_footer.php');
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../../include/exit_footer.php');
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../../include/exit_footer.php');
}
?>