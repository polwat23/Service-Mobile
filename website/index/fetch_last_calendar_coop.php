<?php
require_once('../autoload.php');

		$arrayGroup = array();
		$arrayGroupFile = array();
		$img_head_web_news = array();
		$fetchHolidayCoop = $conmysql->prepare("SELECT 
													id_task,
													task_topic,
													task_detail,
													start_date,
													end_date
												FROM  webcoopgctaskevent
												WHERE
													date_format(start_date,'%Y-%m-%d') >= :date_now 
													or date_format(end_date,'%Y-%m-%d') >= :date_now 
												ORDER BY start_date ASC
												LIMIT 3
												");
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

?>