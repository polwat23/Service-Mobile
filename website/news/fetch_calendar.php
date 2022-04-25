<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){

		$arrayGroup = array();
		$arrayGroupFile = array();
		$img_head_web_news = array();
		$fetchHolidayCoop = $conmysql->prepare("SELECT
													id_task,
													task_topic,
													task_detail,
													start_date,
													end_date
												FROM
													webcoopgctaskevent
												WHERE
													DATE_FORMAT(start_date, '%Y-%m-%d') >= :date_now OR DATE_FORMAT(end_date, '%Y-%m-%d') >= :date_now
												ORDER BY
													start_date ASC");
		$fetchHolidayCoop->execute([
				':date_now' => $dataComing["date_now"]
			]);
		while($rowHolidayCoop = $fetchHolidayCoop->fetch(PDO::FETCH_ASSOC)){
			

			$arrHolidayCoop["ID"] = $rowHolidayCoop["id_task"];
			$arrHolidayCoop["TITLE"] = $rowHolidayCoop["task_topic"];
			$arrHolidayCoop["DETAIL"] = $rowHolidayCoop["task_detail"];
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