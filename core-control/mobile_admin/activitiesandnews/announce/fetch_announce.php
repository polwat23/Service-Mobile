<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'adminmobile','announce')){
		$arrayGroup = array();
		$fetchAnnounce = $conmysql->prepare("SELECT id_announce,
													announce_title,
													announce_detail,
													announce_date,
													priority,
													username
											 FROM gcannounce
											 ORDER BY gcannounce.announce_date DESC");
		$fetchAnnounce->execute();
		while($rowAnnounce = $fetchAnnounce->fetch(PDO::FETCH_ASSOC)){
			$arrGroupAnnounce = array();
			$arrGroupAnnounce["ID_ANNOUNCE"] = $rowAnnounce["id_announce"];
			$arrGroupAnnounce["ANNOUNCE_TITLE"] = $rowAnnounce["announce_title"];
			$arrGroupAnnounce["ANNOUNCE_DETAIL"] = $rowAnnounce["announce_detail"];
			$arrGroupAnnounce["PRIORITY"] = $rowAnnounce["priority"];
			$arrGroupAnnounce["ANNOUNCE_DATE"] = $rowAnnounce["announce_date"];
			$arrGroupAnnounce["ANNOUNCE_DATE_FORMAT"] = $lib->convertdate($rowAnnounce["announce_date"],'d m Y',true); 
			$arrGroupAnnounce["USERNAME"] = $rowAnnounce["username"];
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