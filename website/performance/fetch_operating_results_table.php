<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['unique_id','year','view'],$dataComing)){
	
	$arrayOpResultsGroup = [];	
	$arrayOpResultsColumn = [];	
	if($dataComing['view'] == "month"){	
		$month_arr = ['ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
		$month_index = 0;
		foreach($month_arr as $month_item){
			$arrayOpResultsColumn[] = [
				"COLUMN_KEY" => "DATA_".$month_index,
				"COLUMN_NAME" => $month_item." ".(((int)$dataComing["year"])+543),
			];
			$month_index++;
		}	
	
		$fetchOpResultsGroup = $conmysql->prepare("SELECT id_opresultsgroup, group_name, group_order FROM webcoopopresultsgroup WHERE is_use <> '0'");
		$fetchOpResultsGroup->execute();
		while($rowGroup = $fetchOpResultsGroup->fetch(PDO::FETCH_ASSOC)){
				$arrGroup = [];
				$arrGroup["LIST_NAME"] = $rowGroup["group_name"];
				$arrGroup["ORDER"] = $rowGroup["group_order"];
				$arrGroup["IS_GROUP"] = true;
				$arrGroup["SUB_DATA"] = [];
				$arrGroup["DATA_0"] = 0;
				$arrGroup["DATA_1"] = 0;
				$arrGroup["DATA_2"] = 0;
				$arrGroup["DATA_3"] = 0;
				$arrGroup["DATA_4"] = 0;
				$arrGroup["DATA_5"] = 0;
				$arrGroup["DATA_6"] = 0;
				$arrGroup["DATA_7"] = 0;
				$arrGroup["DATA_8"] = 0;
				$arrGroup["DATA_9"] = 0;
				$arrGroup["DATA_10"] = 0;
				$arrGroup["DATA_11"] = 0;
				$arrGroup["DATA_12"] = 0;
				
				//sub
				$fetchOpResultsList = $conmysql->prepare("SELECT id_opresultslist, list_name, parent_id, list_order FROM webcoopopresultslist WHERE parent_id = :id_opresultsgroup AND is_use <> '0'");
				$fetchOpResultsList->execute([
					':id_opresultsgroup' => $rowGroup["id_opresultsgroup"]
				]);
				while($rowList = $fetchOpResultsList->fetch(PDO::FETCH_ASSOC)){
					$arrList = [];
					$arrList["ID_OPRESULTSLIST"] = $rowList["id_opresultslist"];
					$arrList["LIST_NAME"] = $rowList["list_name"];
					$arrList["PARENT_ID"] = $rowList["parent_id"];
					$arrList["ORDER"] = $rowList["list_order"];
					
					// fetch opresults data
					$fetchOpResultsData = $conmysql->prepare("SELECT id_opresults, data_year, data_0, data_1, data_2, data_3, data_4, data_5,
															data_6, data_7, data_8, data_9, data_10, data_11, data_12 FROM webcoopopresultsdata 
															WHERE data_year = :year AND id_opresultslist = :id_opresultslist");
					$fetchOpResultsData->execute([
						':year' =>  $dataComing["year"],
						':id_opresultslist' =>  $rowList["id_opresultslist"]
					]);
					
					while($rowData = $fetchOpResultsData->fetch(PDO::FETCH_ASSOC)){
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
					
					$arrGroup["DATA_0"] = $arrGroup["DATA_0"] + ($arrList["DATA_0"] ?? 0);
					$arrGroup["DATA_1"] = $arrGroup["DATA_1"] + ($arrList["DATA_1"] ?? 0);
					$arrGroup["DATA_2"] = $arrGroup["DATA_2"] + ($arrList["DATA_2"] ?? 0);
					$arrGroup["DATA_3"] = $arrGroup["DATA_3"] + ($arrList["DATA_3"] ?? 0);
					$arrGroup["DATA_4"] = $arrGroup["DATA_4"] + ($arrList["DATA_4"] ?? 0);
					$arrGroup["DATA_5"] = $arrGroup["DATA_5"] + ($arrList["DATA_5"] ?? 0);
					$arrGroup["DATA_6"]= $arrGroup["DATA_6"] + ($arrList["DATA_6"] ?? 0);
					$arrGroup["DATA_7"] = $arrGroup["DATA_7"] + ($arrList["DATA_7"] ?? 0);
					$arrGroup["DATA_8"] = $arrGroup["DATA_8"] + ($arrList["DATA_8"] ?? 0);
					$arrGroup["DATA_9"] = $arrGroup["DATA_9"] + ($arrList["DATA_9"] ?? 0);
					$arrGroup["DATA_10"] = $arrGroup["DATA_10"] + ($arrList["DATA_10"] ?? 0);
					$arrGroup["DATA_11"] = $arrGroup["DATA_11"] + ($arrList["DATA_11"] ?? 0);
					$arrGroup["DATA_12"] = $arrGroup["DATA_12"] + ($arrList["DATA_12"] ?? 0);
					
						
					$arrGroup["SUB_DATA"][]=$arrList;
				}	
				$arrayOpResultsGroup[]=$arrGroup;
		}
		
		$fetchOpResultsList = $conmysql->prepare("SELECT id_opresultslist, list_name, parent_id, list_order FROM webcoopopresultslist WHERE parent_id = '0' OR parent_id IS NULL AND is_use <> '0'");
		$fetchOpResultsList->execute();
		while($rowList = $fetchOpResultsList->fetch(PDO::FETCH_ASSOC)){
			$arrGroup = [];
			$arrGroup["ID_OPRESULTSLIST"] = $rowList["id_opresultslist"];
			$arrGroup["LIST_NAME"] = $rowList["list_name"];
			$arrGroup["PARENT_ID"] = $rowList["parent_id"];
			$arrGroup["ORDER"] = $rowList["list_order"];
				$arrGroup["IS_GROUP"] = false;
			
			// fetch opresults data
			$fetchOpResultsData = $conmysql->prepare("SELECT id_opresults, data_year, data_0, data_1, data_2, data_3, data_4, data_5,
													data_6, data_7, data_8, data_9, data_10, data_11, data_12 FROM webcoopopresultsdata 
													WHERE data_year = :year AND id_opresultslist = :id_opresultslist");
			$fetchOpResultsData->execute([
				':year' =>  $dataComing["year"],
				':id_opresultslist' =>  $rowList["id_opresultslist"]
			]);
			
			while($rowData = $fetchOpResultsData->fetch(PDO::FETCH_ASSOC)){
				$arrGroup["DATA_0"] = $rowData["data_0"];
				$arrGroup["DATA_1"] = $rowData["data_1"];
				$arrGroup["DATA_2"] = $rowData["data_2"];
				$arrGroup["DATA_3"] = $rowData["data_3"];
				$arrGroup["DATA_4"] = $rowData["data_4"];
				$arrGroup["DATA_5"] = $rowData["data_5"];
				$arrGroup["DATA_6"] = $rowData["data_6"];
				$arrGroup["DATA_7"] = $rowData["data_7"];
				$arrGroup["DATA_8"] = $rowData["data_8"];
				$arrGroup["DATA_9"] = $rowData["data_9"];
				$arrGroup["DATA_10"] = $rowData["data_10"];
				$arrGroup["DATA_11"] = $rowData["data_11"];
				$arrGroup["DATA_12"] = $rowData["data_12"];
			}
			
			$arrayOpResultsGroup[]=$arrGroup;
		}	
	}else if($dataComing['view'] == "pastyear"){
		$year_now = date('Y');
		$yeat_arr = [$year_now, $year_now - 1, $year_now - 2, $year_now - 3,$year_now - 4];
		$year_index = 0;
		foreach($yeat_arr as $year_item){
			$arrayOpResultsColumn[] = [
				"COLUMN_KEY" => "YEAR_".$year_index,
				"COLUMN_NAME" => (((int)$year_item)+543),
			];
			$year_index++;
		}	
		
		$fetchOpResultsGroup = $conmysql->prepare("SELECT id_opresultsgroup, group_name, group_order FROM webcoopopresultsgroup WHERE is_use <> '0'");
		$fetchOpResultsGroup->execute();
		while($rowGroup = $fetchOpResultsGroup->fetch(PDO::FETCH_ASSOC)){
				$arrGroup = [];
				$arrGroup["LIST_NAME"] = $rowGroup["group_name"];
				$arrGroup["ORDER"] = $rowGroup["group_order"];
				$arrGroup["IS_GROUP"] = true;
				$arrGroup["SUB_DATA"] = [];
				$arrGroup["YEAR_0"] = 0;
				$arrGroup["YEAR_1"] = 0;
				$arrGroup["YEAR_2"] = 0;
				$arrGroup["YEAR_3"] = 0;
				$arrGroup["YEAR_4"] = 0;
				//sub
				$fetchOpResultsList = $conmysql->prepare("SELECT id_opresultslist, list_name, parent_id, list_order FROM webcoopopresultslist WHERE parent_id = :id_opresultsgroup AND is_use <> '0'");
				$fetchOpResultsList->execute([
					':id_opresultsgroup' => $rowGroup["id_opresultsgroup"]
				]);
				while($rowList = $fetchOpResultsList->fetch(PDO::FETCH_ASSOC)){
					$arrList = [];
					$arrList["ID_OPRESULTSLIST"] = $rowList["id_opresultslist"];
					$arrList["LIST_NAME"] = $rowList["list_name"];
					$arrList["PARENT_ID"] = $rowList["parent_id"];
					$arrList["ORDER"] = $rowList["list_order"];
					
					$year_index = 0;
					foreach($yeat_arr as $year_item){
						// fetch opresults data
						$fetchOpResultsData = $conmysql->prepare("SELECT id_opresults, data_year, data_0 FROM webcoopopresultsdata 
																WHERE data_year = :year AND id_opresultslist = :id_opresultslist");
						$fetchOpResultsData->execute([
							':year' =>  $year_item,
							':id_opresultslist' =>  $rowList["id_opresultslist"]
						]);
						
						while($rowData = $fetchOpResultsData->fetch(PDO::FETCH_ASSOC)){
							$arrList["YEAR_".$year_index] = $rowData["data_0"];
						}
						
						$arrGroup["YEAR_".$year_index] = $arrGroup["YEAR_".$year_index] + ($arrList["YEAR_".$year_index] ?? 0);
						
						$year_index++;
					}
						
					$arrGroup["SUB_DATA"][]=$arrList;
				}	
				$arrayOpResultsGroup[]=$arrGroup;
		}
		
		$fetchOpResultsList = $conmysql->prepare("SELECT id_opresultslist, list_name, parent_id, list_order FROM webcoopopresultslist WHERE parent_id = '0' OR parent_id IS NULL AND is_use <> '0'");
		$fetchOpResultsList->execute();
		while($rowList = $fetchOpResultsList->fetch(PDO::FETCH_ASSOC)){
			$arrGroup = [];
			$arrGroup["ID_OPRESULTSLIST"] = $rowList["id_opresultslist"];
			$arrGroup["LIST_NAME"] = $rowList["list_name"];
			$arrGroup["PARENT_ID"] = $rowList["parent_id"];
			$arrGroup["ORDER"] = $rowList["list_order"];
			$arrGroup["IS_GROUP"] = false;
			
			$year_index = 0;
			foreach($yeat_arr as $year_item){
				// fetch opresults data
				$fetchOpResultsData = $conmysql->prepare("SELECT id_opresults, data_year, data_0 FROM webcoopopresultsdata 
														WHERE data_year = :year AND id_opresultslist = :id_opresultslist");
				$fetchOpResultsData->execute([
					':year' =>  $year_item,
					':id_opresultslist' =>  $rowList["id_opresultslist"]
				]);
				
				while($rowData = $fetchOpResultsData->fetch(PDO::FETCH_ASSOC)){
					$arrGroup["YEAR_".$year_index] = $rowData["data_0"];
				}
			}
				
			$arrayOpResultsGroup[]=$arrGroup;
		}	
	}
	$arrayResult["OPRESULT_TABLE"] = $arrayOpResultsGroup;
	$arrayResult["OPRESULT_COLUMN"] = $arrayOpResultsColumn;
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);
	
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>