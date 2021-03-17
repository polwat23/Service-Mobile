<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	
	$arrayGroup = array();
	$arrayGroupFile = array();
	$img_head_web_news = array();
	$fetchHolidayCoop = $conmysql->prepare("SELECT 
												holiday_id,
												title,
												start_date,
												end_date,
												detail
											FROM  webcoopholidaycalendar
								
											ORDER BY start_date ASC
											");
	$fetchHolidayCoop->execute();
	while($rowHolidayCoop = $fetchHolidayCoop->fetch(PDO::FETCH_ASSOC)){
		$arrHolidayCoop["HOLIDAY_ID"] = $rowHolidayCoop["holiday_id"];
		$arrHolidayCoop["TITLE"] = $rowHolidayCoop["title"];
		$arrHolidayCoop["DETAIL"] = $rowHolidayCoop["detail"];
		$arrHolidayCoop["START_DATE"] = $rowHolidayCoop["start_date"];
		$arrHolidayCoop["END_DATE"] = $rowHolidayCoop["end_date"];
		$arrHolidayCoop["START_DATE_FORMAT"] = $lib->convertdate($rowHolidayCoop["start_date"],'d m Y',false); 
		$arrHolidayCoop["END_DATE_FORMAT"] = $lib->convertdate($rowHolidayCoop["end_date"],'d m Y',false);  
		$arrayGroup[] = $arrHolidayCoop;
	}
	$arrayResult["HOLIDAY_DATA"] = $arrayGroup;
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);
		
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>