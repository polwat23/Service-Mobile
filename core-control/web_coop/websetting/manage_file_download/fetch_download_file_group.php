<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	
	$arrayGroup = array();
	$fetchbalanceSheetGroup = $conmysql->prepare("SELECT
												id_groupdownload,
												name,
												create_by,
												create_date,
												update_date,
												update_by
											FROM
												webcoopgroupdownload
											WHERE
												is_use ='1'
											");
	$fetchbalanceSheetGroup->execute();
	while($rowBalanceSheetGroup = $fetchbalanceSheetGroup->fetch(PDO::FETCH_ASSOC)){
		$arrGroupStatement["ID_GOROUPDOWNLOAD"] = $rowBalanceSheetGroup["id_groupdownload"];
		$arrGroupStatement["NAME"] = $rowBalanceSheetGroup["name"];;
		$arrGroupStatement["UPDATE_BY"] = $rowBalanceSheetGroup["update_by"];
		$arrGroupStatement["CREATE_BY"] = $rowBalanceSheetGroup["create_by"];
		$arrGroupStatement["CREATE_DATE"] = $lib->convertdate($rowBalanceSheetGroup["create_date"],'d m Y',true); 
		$arrGroupStatement["UPDATE_DATE"] = $lib->convertdate($rowBalanceSheetGroup["update_date"],'d m Y',true);  
		$arrayGroup[] = $arrGroupStatement;
	}
	$arrayResult["DOWNLOAD_GROUP_DATA"] = $arrayGroup;
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);

}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>