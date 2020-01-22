<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'sms','manageahead')){
		$arrGroupSendAhead = array();
		if(isset($dataComing["id_sendahead"])){
			$fetchGroup = $conmysql->prepare("SELECT id_sendahead,send_topic,send_message,destination,repeat_send,send_date,amount_repeat,send_platform,send_image
													FROM smssendahead WHERE is_use = '1' and id_sendahead = :id_sendahead");
			$fetchGroup->execute([':id_sendahead' => $dataComing["id_sendahead"]]);
			while($rowSendAhead = $fetchGroup->fetch()){
				$arrGroupSendAhead["ID_SENDAHEAD"] = $rowSendAhead["id_sendahead"];
				$arrGroupSendAhead["SEND_TOPIC"] = $rowSendAhead["send_topic"];
				$arrGroupSendAhead["SEND_MESSAGE"] = $rowSendAhead["send_message"];
				$arrGroupSendAhead["REPEAT_STATUS"] = $rowSendAhead["repeat_send"];
				$arrGroupSendAhead["SEND_DATE"] = $lib->convertdate($rowSendAhead["send_date"],'D m Y');
				$arrGroupSendAhead["SEND_DATE_NOT_FORMAT"] = $rowSendAhead["send_date"];
				$arrGroupSendAhead["AMOUNT_REPEAT"] = number_format($rowSendAhead["amount_repeat"],0);
				$arrGroupSendAhead["DESTINATION"] = $rowSendAhead["destination"];
				$arrGroupSendAhead["SEND_PLATFORM"] = $rowSendAhead["send_platform"];
				$arrGroupSendAhead["SEND_IMAGE"] = isset($rowSendAhead["send_image"]) ? $config["URL_SERVICE"].$rowSendAhead["send_image"] : null;
			}
		}else{
			$fetchSendAhead = $conmysql->prepare("SELECT id_sendahead,send_message,destination,repeat_send,send_date,amount_repeat
													FROM smssendahead WHERE is_use = '1'");
			$fetchSendAhead->execute();
			while($rowSendAhead = $fetchSendAhead->fetch()){
				$arrSendAhead = array();
				$arrSendAhead["ID_SENDAHEAD"] = $rowSendAhead["id_sendahead"];
				$arrSendAhead["SEND_MESSAGE"] = $rowSendAhead["send_message"];
				$arrSendAhead["REPEAT_STATUS"] = $rowSendAhead["repeat_send"];
				$arrSendAhead["SEND_DATE"] = $lib->convertdate($rowSendAhead["send_date"],'D m Y');
				$arrSendAhead["AMOUNT_REPEAT"] = number_format($rowSendAhead["amount_repeat"],0);
				$arrSendAhead["DESTINATION"] = explode(',',$rowSendAhead["destination"]);
				$arrGroupSendAhead[] = $arrSendAhead;
			}
		}
		$arrayResult['SEND_AHEAD'] = $arrGroupSendAhead;
		$arrayResult['RESULT'] = TRUE;
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