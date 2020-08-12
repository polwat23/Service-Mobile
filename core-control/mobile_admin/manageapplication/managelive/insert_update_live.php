<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','live_url','live_title'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managelive')){
		
		$fetchBG = $conmysql->prepare("SELECT id_live, live_url,live_title, update_date, is_use, update_by FROM gclive");
								
		$fetchBG->execute();
		$arrayGroup = array();
		while($rowbg = $fetchBG->fetch(PDO::FETCH_ASSOC)){
			$arrGroupBg = array();
			$arrGroupBg["ID_LIVE"] = $rowbg["id_live"];
			$arrGroupBg["LIVE_URL"] = $rowbg["live_url"];
			$arrGroupBg["LIVE_TITLE"] = $rowbg["live_title"];
			$arrGroupBg["UPDATE_DATE"] = $rowbg["update_date"];
			$arrGroupBg["IS_USE"] = $rowbg["is_use"];
			$arrGroupBg["UPDATE_BY"] = $rowbg["update_by"];
			$arrayGroup[] = $arrGroupBg;
		}
		
		if(count($arrayGroup) > 0){
			$insertIntoInfo = $conmysql->prepare("UPDATE gclive SET live_url = :live_url,live_title = :live_title, update_by = :username WHERE id_live = :id_live");
			if($insertIntoInfo->execute([
				':live_url' => $dataComing["live_url"],
				'live_title' => $dataComing["live_title"],
				'username' => $payload["username"],
				'id_live' => $arrayGroup[0]["ID_LIVE"]
			])){
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = "แก้ไขข้อมูลไม่สำเร็จไม่สำเร็จ";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$insert_news = $conmysql->prepare("INSERT INTO gclive(live_url,live_title,update_by) VALUES (:live_url,:live_title,:username)");
			if($insert_news->execute([
					':live_url' => $dataComing["live_url"],
					'live_title' => $dataComing["live_title"],
					'username' => $payload["username"]
			])){
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE_MESSAGE'] = "แก้ไขข้อมูลไม่สำเร็จไม่สำเร็จ";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}
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