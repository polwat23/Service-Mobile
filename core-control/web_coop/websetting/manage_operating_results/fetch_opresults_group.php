<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	
	$arrayOpResultsGroup = [];		
	$fetchOpResultsGroup = $conmysql->prepare("SELECT id_opresultsgroup, group_name, group_order FROM webcoopopresultsgroup WHERE is_use <> '0'");
	$fetchOpResultsGroup->execute();
	while($rowGroup = $fetchOpResultsGroup->fetch(PDO::FETCH_ASSOC)){
			$arrGroup = [];
			$arrGroup["ID_OPRESULTSGROUP"] = $rowGroup["id_opresultsgroup"];
			$arrGroup["GROUP_NAME"] = $rowGroup["group_name"];
			$arrGroup["ORDER"] = $rowGroup["group_order"];
				
			$arrayOpResultsGroup[]=$arrGroup;
	}
	
	$arrayOpResultsList = [];		
	$fetchOpResultsList = $conmysql->prepare("SELECT id_opresultslist, list_name, parent_id, list_order FROM webcoopopresultslist WHERE is_use <> '0'");
	$fetchOpResultsList->execute();
	while($rowList = $fetchOpResultsList->fetch(PDO::FETCH_ASSOC)){
			$arrList = [];
			$arrList["ID_OPRESULTSLIST"] = $rowList["id_opresultslist"];
			$arrList["LIST_NAME"] = $rowList["list_name"];
			$arrList["PARENT_ID"] = $rowList["parent_id"];
			$arrList["ORDER"] = $rowList["list_order"];
				
			if($rowList["parent_id"] == "0" || $rowList["parent_id"] == null || $rowList["parent_id"] == ""){
				$arrGroup = [];
				$arrGroup["ID_OPRESULTSLIST"] = $rowList["id_opresultslist"];
				$arrGroup["LIST_NAME"] = $rowList["list_name"];
				$arrGroup["ORDER"] = $rowList["list_order"];
					
				$arrayOpResultsGroup[]=$arrGroup;
			}				
			$arrayOpResultsList[]=$arrList;
	}
		
	$arrayResult["OPRESULT_LIST"] = $arrayOpResultsList;
	$arrayResult["OPRESULT_GROUP"] = $arrayOpResultsGroup;
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);
	
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>