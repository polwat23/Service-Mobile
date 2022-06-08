<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	
	$arrayGroup = array();
	$fetchlinkGroup = $conmysql->prepare("SELECT
												webcooplink_id,
												name,
                                                link,
                                                is_use,
												create_by,
												create_date,
												update_date,
												update_by
											FROM
												webcooplink
											WHERE
												is_use <> '-9'
											"
										);
	$fetchlinkGroup->execute();
	while($rowlinkGroup = $fetchlinkGroup->fetch(PDO::FETCH_ASSOC)){
		$arrGroupStatement["WEBCOOPLINK_ID"] = $rowlinkGroup["webcooplink_id"];
		$arrGroupStatement["NAME"] = $rowlinkGroup["name"];
        $arrGroupStatement["LINK"] = $rowlinkGroup["link"];
        $arrGroupStatement["IS_USE"] = $rowlinkGroup["is_use"];
		$arrGroupStatement["UPDATE_BY"] = $rowlinkGroup["update_by"];
		$arrGroupStatement["CREATE_BY"] = $rowlinkGroup["create_by"];
		$arrGroupStatement["CREATE_DATE"] = $lib->convertdate($rowlinkGroup["create_date"],'d m Y',true); 
		$arrGroupStatement["UPDATE_DATE"] = $lib->convertdate($rowlinkGroup["update_date"],'d m Y',true);  
		$arrayGroup[] = $arrGroupStatement;
	}
	$arrayResult["LINK_GROUP_DATA"] = $arrayGroup;
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);

}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>