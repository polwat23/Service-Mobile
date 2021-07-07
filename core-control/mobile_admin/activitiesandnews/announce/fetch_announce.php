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
		$dateNow = date('YmdHis');
		$fetchAnnounce = $conoracle->prepare("SELECT id_announce,
													announce_cover,
													announce_title,
													announce_detail,
													announce_html,
													effect_date,
													due_date,
													is_show_between_due,
													is_update,
													priority,
													username,
													flag_granted,
													is_check,
													check_text,
													accept_text,
													cancel_text,
													TO_CHAR(effect_date,'yyyymmdd hh24:mi:ss') AS effect_date_check, 
													TO_CHAR(due_date,'yyyymmdd hh24:mi:ss') AS due_date_check
											 FROM gcannounce
											 WHERE id_announce <> '-1' and effect_date IS NOT NULL
													".(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? 
														"and TO_CHAR(effect_date,'YYYY-MM-DD')  <= :start_date" : null)."
													".(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? 
														"and TO_CHAR(due_date,'YYYY-MM-DD') >= :end_date" : null). " 
														 and ROWNUM <= 20
														 ORDER BY effect_date DESC");
		$fetchAnnounce->execute($arrayExecute);		
		while($rowAnnounce = $fetchAnnounce->fetch(PDO::FETCH_ASSOC)){
			$arrGroupAnnounce = array();
			$arrGroupAnnounce["ID_ANNOUNCE"] = $rowAnnounce["ID_ANNOUNCE"];
			$arrGroupAnnounce["ANNOUNCE_COVER"] = stream_get_contents($rowAnnounce["ANNOUNCE_COVER"]);
			$arrGroupAnnounce["ANNOUNCE_TITLE"] = $rowAnnounce["ANNOUNCE_TITLE"];
			$arrGroupAnnounce["ANNOUNCE_DETAIL"] = stream_get_contents($rowAnnounce["ANNOUNCE_DETAIL"]);
			$arrGroupAnnounce["ANNOUNCE_HTML"] = stream_get_contents($rowAnnounce["ANNOUNCE_HTML"]);
			$arrGroupAnnounce["PRIORITY"] = $rowAnnounce["PRIORITY"];
			$arrGroupAnnounce["USERNAME"] = $rowAnnounce["USERNAME"];
			$arrGroupAnnounce["IS_CHECK"] = $rowAnnounce["IS_CHECK"];
			$arrGroupAnnounce["CHECK_TEXT"] = $rowAnnounce["CHECK_TEXT"];
			$arrGroupAnnounce["ACCEPT_TEXT"] = $rowAnnounce["ACCEPT_TEXT"];
			$arrGroupAnnounce["CANCEL_TEXT"] = $rowAnnounce["CANCEL_TEXT"];
			$arrGroupAnnounce["FLAG_GRANTED"] = $rowAnnounce["FLAG_GRANTED"];	
			$arrGroupAnnounce["EFFECT_DATE"] = $rowAnnounce["EFFECT_DATE"];		
			$arrGroupAnnounce["DUE_DATE"] = $rowAnnounce["DUE_DATE"];	
			$arrGroupAnnounce["DUE_DATE_FORMAT"] = $lib->convertdate($rowAnnounce["DUE_DATE"],'d m Y',true); 
			$arrGroupAnnounce["IS_SHOW_BETWEEN_DUE"] = $rowAnnounce["IS_SHOW_BETWEEN_DUE"];
			$arrGroupAnnounce["IS_UPDATE"] = $rowAnnounce["IS_UPDATE"];
			$arrGroupAnnounce["EFFECT_DATE_FORMAT"] = $lib->convertdate($rowAnnounce["EFFECT_DATE"],'d m Y',true); 
						
			if(isset($rowAnnounce["EFFECT_DATE"]) && (($rowAnnounce["EFFECT_DATE_CHECK"] <= $dateNow && $dateNow <= $rowAnnounce["DUE_DATE_CHECK"]) || ($rowAnnounce["PRIORITY"] == 'high' || $rowAnnounce["PRIORITY"] == 'ask'))){
					$arrGroupAnnounce["ACTIVE"] = "now";
			}else if(isset($rowAnnounce["EFFECT_DATE"]) && $rowAnnounce["EFFECT_DATE_CHECK"] > $dateNow){
					$arrGroupAnnounce["ACTIVE"] = "future"; 
 			}else{
				$arrGroupAnnounce["ACTIVE"] = "actived"; 
			}
			
			$arrayGroup[] = $arrGroupAnnounce;
		}
		$arrayResult["ANNOUNCE_DATA"] = $arrayGroup;

		$arrayResult["RESULT"] = TRUE;
		require_once('../../../../include/exit_footer.php');
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../../include/exit_footer.php');
		
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../../include/exit_footer.php');
	
}
?>

