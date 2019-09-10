<?php
require_once('../../autoload.php');

if(isset($dataComing["type_send"]) && isset($dataComing["type_history"]) && isset($dataComing["name_template"]) && isset($dataComing["payload"])){
	$conmysql_nottest = $con->connecttomysql();
	$template = $func->getTemplate($dataComing["name_template"],$conmysql);
	$arraySuccess = array();
	$arrayFalse = array();
	if($dataComing["type_history"] == '2'){
		if($dataComing["type_send"] == 'with_payload'){
			foreach($dataComing["payload"] as $data) {
				foreach($data["data"] as $value){
					$arrayDataSend = $lib->mergeTemplate($template["SUBJECT"],$template["BODY"],$value);
					$arrSendData["title"] = $arrayDataSend["SUBJECT"];
					$arrSendData["detail"] = $arrayDataSend["BODY"];
					$arrSendData["member_no"] = $data["member_no"];
					if($func->insertHistory($arrSendData,$dataComing["type_history"],$conmysql)){
						$arraySuccess[] = $arrSendData;
					}else{
						$arrayFalse[] = $arrSendData;
					}
				}
			}
		}
	}else{
		if($dataComing["type_send"] == 'someone'){
			$arrayPayload = array();
			$arrayToken = array();
			$arrayMessage["TITLE"] = $dataComing["payload"]["title"];
			$arrayMessage["DETAIL"] = $dataComing["payload"]["detail"];
			$member_no_text = "";
			foreach($dataComing["payload"]["member_no"] as $key => $member_no){
				if($key == 0){
					$member_no_text .= '"'.$member_no.'"';
				}else{
					$member_no_text .= ',"'.$member_no.'"';
				}
			}
			$getAPI = $conmysql->prepare("SELECT mdt.id_api
											FROM mdbuserlogin mul LEFT JOIN mdbtoken mdt ON mul.id_token = mdt.id_token
											WHERE mul.member_no IN(".$member_no_text.") and mul.receive_notify_news = '1' and mul.channel = 'mobile' 
											and mdt.rt_is_revoke = 0");
			$getAPI->execute();
			if($getAPI->rowCount() > 0){
				while($rowAPI = $getAPI->fetch()){
					$getToken = $conmysql_nottest->prepare("SELECT fcm_token FROM mdbapikey WHERE id_api = :id_api and is_revoke = 0");
					$getToken->execute([':id_api' => $rowAPI["id_api"]]);
					$rowToken = $getToken->fetch();
					$arrayToken[] = $rowToken["fcm_token"];
				}
				$arrayPayload["TO"] = $arrayToken;
				$arrayPayload["PAYLOAD"] = $arrayMessage;
				echo json_encode($arrayPayload);
			}else{
				$arrayResult['RESPONSE_CODE'] = "SQL400";
				$arrayResult['RESPONSE'] = "Empty user for send notify";
				$arrayResult['RESULT'] = FALSE;
				http_response_code(203);
				echo json_encode($arrayResult);
				exit();
			}
		}
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "PARAM400";
	$arrayResult['RESPONSE'] = "Not complete parameter";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(203);
	echo json_encode($arrayResult);
	exit();
}
?>