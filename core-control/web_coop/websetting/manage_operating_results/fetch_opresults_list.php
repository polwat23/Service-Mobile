<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','year'],$dataComing)){
	
	$arrayOpResultsList = [];		
	$fetchOpResultsList = $conmysql->prepare("SELECT id_opresultslist, list_name FROM webcoopopresultslist WHERE is_use <> '0'");
	$fetchOpResultsList->execute();
	while($rowList = $fetchOpResultsList->fetch(PDO::FETCH_ASSOC)){
			$arrList = [];
			$arrList["ID_OPRESULTSLIST"] = $rowList["id_opresultslist"];
			$arrList["LIST_NAME"] = $rowList["list_name"];
			
			// fetch opresults data
			$fetchOpResultsData = $conmysql->prepare("SELECT id_opresults, data_year, data_0, data_1, data_2, data_3, data_4, data_5,
													data_6, data_7, data_8, data_9, data_10, data_11, data_12 FROM webcoopopresultsdata 
													WHERE data_year = :year AND id_opresultslist = :id_opresultslist");
			$fetchOpResultsData->execute([
				':year' =>  $dataComing["year"],
				':id_opresultslist' =>  $rowList["id_opresultslist"]
			]);
			
			while($rowData = $fetchOpResultsData->fetch(PDO::FETCH_ASSOC)){
				$arrList["ID_OPRESULTS"] = $rowData["id_opresults"];
				$arrList["DATA_0"] = $rowData["data_0"];
				$arrList["DATA_1"] = $rowData["data_1"];
				$arrList["DATA_2"] = $rowData["data_2"];
				$arrList["DATA_3"] = $rowData["data_3"];
				$arrList["DATA_4"] = $rowData["data_4"];
				$arrList["DATA_5"] = $rowData["data_5"];
				$arrList["DATA_6"] = $rowData["data_6"];
				$arrList["DATA_7"] = $rowData["data_7"];
				$arrList["DATA_8"] = $rowData["data_8"];
				$arrList["DATA_9"] = $rowData["data_9"];
				$arrList["DATA_10"] = $rowData["data_10"];
				$arrList["DATA_11"] = $rowData["data_11"];
				$arrList["DATA_12"] = $rowData["data_12"];
			}
				
			$arrayOpResultsList[]=$arrList;
	}
		
	$arrayResult["OPRESULT_LIST"] = $arrayOpResultsList;
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);
	
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>