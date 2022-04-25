<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	
	$arrayGroup = array();
	$fetchbalanceSheetGroup = $conmysql->prepare("SELECT
												id_groupdownload,
												name,
												parent,
												create_by,
												create_date,
												update_date,
												update_by
											FROM
												webcoopgroupdownload
											WHERE is_use <> '-9' AND parent ='0'
											");
	$fetchbalanceSheetGroup->execute();
	while($rowGroupFile = $fetchbalanceSheetGroup->fetch(PDO::FETCH_ASSOC)){
		$arrGroupStatement["ID_GROUPDOWNLOAD"] = $rowGroupFile["id_groupdownload"];
		$arrGroupStatement["PARENT"] = $rowGroupFile["parent"];
		$arrGroupStatement["NAME"] = $rowGroupFile["name"];
		$arrGroupStatement["UPDATE_BY"] = $rowGroupFile["update_by"];
		$arrGroupStatement["CREATE_BY"] = $rowGroupFile["create_by"];
		$arrGroupStatement["CREATE_DATE"] = $lib->convertdate($rowGroupFile["create_date"],'d m Y',true); 
		$arrGroupStatement["UPDATE_DATE"] = $lib->convertdate($rowGroupFile["update_date"],'d m Y',true);  
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