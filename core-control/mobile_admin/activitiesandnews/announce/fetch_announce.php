<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','announce')){
		$arrayExecute = array();
		$arrayGroup = array();
		
			if(isset($dataComing["start_date"]) && $dataComing["start_date"] != ""){
				$arrayExecute["start_date"] = $dataComing["start_date"];
			}
			if(isset($dataComing["end_date"]) && $dataComing["end_date"] != ""){
				$arrayExecute["end_date"] = $dataComing["end_date"];
			}
			
		$fetchAnnounce = $conmysql->prepare("SELECT id_announce,
													announce_cover,
													announce_title,
													announce_detail,
													announce_html,
													announce_date,
													effect_date,
													due_date,
													is_show_between_due,
													is_update,
													priority,
													username,
													flag_granted,
													effect_date,	
													date_format(effect_date,'%Y-%m-%d') AS 'effect_day',
													date_format(effect_date,'%H:%i') AS 'effect_time'
											 FROM gcannounce
											 WHERE id_announce !='-1'
													".(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? 
														"and date_format(effect_date,'%Y-%m-%d') >= :start_date" : null)."
													".(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? 
														"and date_format(effect_date,'%Y-%m-%d') <= :end_date" : null). " ORDER BY effect_date DESC");
		$fetchAnnounce->execute($arrayExecute);		
		while($rowAnnounce = $fetchAnnounce->fetch(PDO::FETCH_ASSOC)){
			$day_now=date("Y-m-d");
			$time_now=date("H:i");
			$arrGroupAnnounce = array();
			$arrGroupAnnounce["ID_ANNOUNCE"] = $rowAnnounce["id_announce"];
			$arrGroupAnnounce["ANNOUNCE_COVER"] = $rowAnnounce["announce_cover"];
			$arrGroupAnnounce["ANNOUNCE_TITLE"] = $rowAnnounce["announce_title"];
			$arrGroupAnnounce["ANNOUNCE_DETAIL"] = $rowAnnounce["announce_detail"];
			$arrGroupAnnounce["ANNOUNCE_DETAIL_SHORT"] = $lib->text_limit($rowAnnounce["announce_detail"],390);
			$arrGroupAnnounce["ANNOUNCE_HTML"] = $rowAnnounce["announce_html"];
			$arrGroupAnnounce["PRIORITY"] = $rowAnnounce["priority"];
			$arrGroupAnnounce["ANNOUNCE_DATE"] = $rowAnnounce["announce_date"];
			$arrGroupAnnounce["ANNOUNCE_DATE_FORMAT"] = $lib->convertdate($rowAnnounce["announce_date"],'d m Y',true); 
			$arrGroupAnnounce["USERNAME"] = $rowAnnounce["username"];
			$arrGroupAnnounce["FLAG_GRANTED"] = $rowAnnounce["flag_granted"];	
			$arrGroupAnnounce["EFFECT_DATE"] = $rowAnnounce["effect_date"];		
			$arrGroupAnnounce["DUE_DATE"] = $rowAnnounce["due_date"];	
				$arrGroupAnnounce["DUE_DATE_FORMAT"] = $lib->convertdate($rowAnnounce["due_date"],'d m Y',true); 
			$arrGroupAnnounce["IS_SHOW_BETWEEN_DUE"] = $rowAnnounce["is_show_between_due"];
			$arrGroupAnnounce["IS_UPDATE"] = $rowAnnounce["is_update"];
			$arrGroupAnnounce["EFFECT_DATE_FORMAT"] = $lib->convertdate($rowAnnounce["effect_date"],'d m Y',true); 
						
			if($day_now==$rowAnnounce["effect_day"]&&$time_now>=$rowAnnounce["effect_time"]&&$time_now<=$rowAnnounce["due_date"] ){
					$arrGroupAnnounce["ACTIVE"] = "now";
			}else if(($day_now==$rowAnnounce["effect_day"]&&$time_now<=$rowAnnounce["effect_time"])||$day_now<$rowAnnounce["effect_day"]){
					$arrGroupAnnounce["ACTIVE"] = "future"; 
 			}else{
				$arrGroupAnnounce["ACTIVE"] = "actived"; 
			}
			
			$arrayGroup[] = $arrGroupAnnounce;
		}
		$arrayResult["ANNOUNCE_DATA"] = $arrayGroup;

		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>

