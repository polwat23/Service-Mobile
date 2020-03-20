<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'adminmobile','announce')){
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
													announce_date,
													effect_date,
													priority,
													username,
													flag_granted	
											 FROM gcannounce
											 WHERE id_announce !='-1'
												".(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? 
												"and date_format(announce_date,'%Y-%m-%d') >= :start_date" : null)."
												".(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? 
												"and date_format(announce_date,'%Y-%m-%d') <= :end_date" : null). 
											 "ORDER BY gcannounce.announce_date DESC");
		$fetchAnnounce->execute($arrayExecute);		
		while($rowAnnounce = $fetchAnnounce->fetch(PDO::FETCH_ASSOC)){
	
			$arrGroupAnnounce = array();
			$arrGroupAnnounce["ANNOUNCE_COVER"] = $rowAnnounce["announce_cover"];
		
			$arrGroupAnnounce["ANNOUNCE_TITLE"] = $rowAnnounce["announce_title"];
			$arrGroupAnnounce["ANNOUNCE_DETAIL"] = $rowAnnounce["announce_detail"];
			$arrGroupAnnounce["ANNOUNCE_DETAIL_SHORT"] = $lib->text_limit($rowAnnounce["announce_detail"],390);
			$arrGroupAnnounce["PRIORITY"] = $rowAnnounce["priority"];
			$arrGroupAnnounce["ANNOUNCE_DATE"] = $rowAnnounce["announce_date"];
			$arrGroupAnnounce["ANNOUNCE_DATE_FORMAT"] = $lib->convertdate($rowAnnounce["announce_date"],'d m Y',true); 
			$arrGroupAnnounce["USERNAME"] = $rowAnnounce["username"];
			$arrGroupAnnounce["FLAG_GRANTED"] = $rowAnnounce["flag_granted"];
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