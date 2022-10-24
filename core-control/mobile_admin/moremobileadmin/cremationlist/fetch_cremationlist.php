<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','cremationlist')){
	
	
		$conmssqlcmt = $con->connecttosqlservercmt();
		$fetchCremationCoop = $conmssqlcmt->prepare("SELECT WC_ID,COOP_SHORTNAME,COOP_NAME,COOP_CONTROL FROM WCCONTCOOP WHERE  coop_control = '051001'");
		$fetchCremationCoop->execute();
		
		$arrayCremationCoop = array();
		while($rowCremationCoop = $fetchCremationCoop->fetch(PDO::FETCH_ASSOC)){
			$arrayGroup = array();
			$arrayGroup["WC_ID"] = $rowCremationCoop["WC_ID"];
			$arrayGroup["COOP_SHORTNAME"] = $rowCremationCoop["COOP_SHORTNAME"];
			$arrayGroup["COOP_NAME"] = $rowCremationCoop["COOP_NAME"];
			$arrayCremationCoop[$rowCremationCoop["WC_ID"]] = $arrayGroup;
		}
		
		
		$time=strtotime($dataComing["month"] ?? date("Y-m-d"));
		$month=date("m",$time);
		$year=date("Y",$time);
		
		$arrayExecute = array();
		$arrayExecute["month"] = $month;
		$arrayExecute["year"] = $year;
		if(isset($dataComing["cremation_coop"]) && $dataComing["cremation_coop"] != ""){
			$arrayExecute["cremation_coop"] = $dataComing["cremation_coop"];
		}

	
		$fetchCremationList = $conmysql->prepare("SELECT cremation_id, full_name, data_date, update_date, update_user,cremation_amt, cremation_coop FROM gccremationlist WHERE is_use = '1' AND MONTH(data_date) = :month AND YEAR(data_date) = :year".(isset($dataComing["cremation_coop"]) && $dataComing["cremation_coop"] != "" ? " AND cremation_coop = :cremation_coop" : ""));
		$fetchCremationList->execute($arrayExecute);
		
		$arrayCremation = array();
		while($rowCremation = $fetchCremationList->fetch(PDO::FETCH_ASSOC)){
			$arrayGroup = array();
			$arrayGroup["CREMATION_ID"] = $rowCremation["cremation_id"];
			$arrayGroup["FULL_NAME"] = $rowCremation["full_name"];
			$arrayGroup["CREMATION_AMT"] = number_format($rowCremation["cremation_amt"],2);
			$arrayGroup["DATA_DATE"] = $rowCremation["data_date"];
			$arrayGroup["UPDATE_DATE"] = $rowCremation["update_date"];
			$arrayGroup["UPDATE_USER"] = $rowCremation["update_user"];
			$arrayGroup["CREMATION_COOP"] = $rowCremation["cremation_coop"];
			$arrayGroup["CREMATION_COOP_DESC"] =$arrayCremationCoop[$rowCremation["cremation_coop"]]["COOP_SHORTNAME"]." - ".$arrayCremationCoop[$rowCremation["cremation_coop"]]["COOP_NAME"];
			
			$arrayCremation[] = $arrayGroup;
		}
		
		$arrayResult["CREMATION_LIST"] = $arrayCremation;
		$arrayResult["fetchCremationList"] = $fetchCremationList;
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