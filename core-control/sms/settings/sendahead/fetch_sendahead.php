<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'sms','manageahead',$conoracle)){
		$arrGroupSendAhead = array();
		if(isset($dataComing["id_sendahead"])){
			$fetchGroup = $conoracle->prepare("SELECT id_sendahead,send_topic,send_message,destination,
												send_date,send_platform,send_image,create_by,is_import
												FROM smssendahead WHERE is_use = '1' and id_sendahead = :id_sendahead");
			$fetchGroup->execute([':id_sendahead' => $dataComing["id_sendahead"]]);
			while($rowSendAhead = $fetchGroup->fetch(PDO::FETCH_ASSOC)){
				$arrGroupSendAhead["ID_SENDAHEAD"] = $rowSendAhead["ID_SENDAHEAD"];
				$arrGroupSendAhead["SEND_TOPIC"] = $rowSendAhead["SEND_TOPIC"];
				$arrGroupSendAhead["SEND_MESSAGE"] = $rowSendAhead["SEND_MESSAGE"];
				$arrGroupSendAhead["SEND_DATE"] = $lib->convertdate($rowSendAhead["SEND_DATE"],'D m Y H i s',true);
				$arrGroupSendAhead["SEND_DATE_NOT_FORMAT"] = $rowSendAhead["SEND_DATE"];
				if($rowSendAhead["IS_IMPORT"] == '0'){
					$arrGroupSendAhead["DESTINATION"] = explode(',',$rowSendAhead["DESTINATION"]);
				}else{
					$arrGroupSendAhead["DESTINATION"] = json_decode($rowSendAhead["DESTINATION"]);
				}
				$arrGroupSendAhead["IS_IMPORT"] = $rowSendAhead["IS_IMPORT"];
				$arrGroupSendAhead["SEND_PLATFORM"] = $rowSendAhead["SEND_PLATFORM"];
				$arrGroupSendAhead["CREATE_BY"] = $rowSendAhead["CREATE_BY"];
				$arrGroupSendAhead["SEND_IMAGE"] = isset($rowSendAhead["SEND_IMAGE"]) ? $config["URL_SERVICE"].$rowSendAhead["SEND_IMAGE"] : null;
			}
		}else{
			$fetchSendAhead = $conoracle->prepare("SELECT id_sendahead,send_message,destination,send_date,create_by,is_import
													FROM smssendahead WHERE is_use = '1'");
			$fetchSendAhead->execute();
			while($rowSendAhead = $fetchSendAhead->fetch(PDO::FETCH_ASSOC)){
				$arrSendAhead = array();
				$arrSendAhead["ID_SENDAHEAD"] = $rowSendAhead["ID_SENDAHEAD"];
				$arrSendAhead["SEND_MESSAGE"] = $rowSendAhead["SEND_MESSAGE"];
				$arrSendAhead["CREATE_BY"] = $rowSendAhead["CREATE_BY"];
				$arrSendAhead["SEND_DATE"] = $lib->convertdate($rowSendAhead["SEND_DATE"],'D m Y H i s',true);
				if($rowSendAhead["IS_IMPORT"] == '0'){
					$arrSendAhead["DESTINATION"] = explode(',',$rowSendAhead["DESTINATION"]);
				}else{
					$arrSendAhead["DESTINATION"] = json_decode($rowSendAhead["DESTINATION"]);
				}
				$arrSendAhead["IS_IMPORT"] = $rowSendAhead["IS_IMPORT"];
				$arrGroupSendAhead[] = $arrSendAhead;
			}
		}
		$arrayResult['SEND_AHEAD'] = $arrGroupSendAhead;
		$arrayResult['RESULT'] = TRUE;
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