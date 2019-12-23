<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','type_history'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'Notification')){
		$arrGroupHis = array();
		$executeData = [
			':member_no' => $payload["member_no"],
			':his_type' => $dataComing["type_history"]
		];
		$extraQuery = "";
		if(isset($dataComing["fetch_type"])){
			switch($dataComing["fetch_type"]){
				case "refresh":
					$executeData[':id_history'] = isset($dataComing["id_history"]) ? $dataComing["id_history"] : 16777215; // max number mediumint(8) of id_history
					$extraQuery = "and id_history > :id_history";
					break;
				case "more":
					$executeData[':id_history'] = isset($dataComing["id_history"]) ? $dataComing["id_history"] : 0;
					$extraQuery = "and id_history < :id_history";
					break;
			}
		}
		$getHistory = $conmysql->prepare("SELECT id_history,his_title,his_detail,receive_date,his_read_status FROM gchistory 
											WHERE member_no = :member_no and his_type = :his_type $extraQuery ORDER BY id_history DESC LIMIT 10");
		$getHistory->execute($executeData);
		while($rowHistory = $getHistory->fetch()){
			$arrHistory = array();
			$arrHistory["TITLE"] = $rowHistory["his_title"];
			$arrHistory["DETAIL"] = $rowHistory["his_detail"];
			$arrHistory["READ_STATUS"] = $rowHistory["his_read_status"];
			$arrHistory["ID_HISTORY"] = $rowHistory["id_history"];
			$arrHistory["RECEIVE_DATE"] = $lib->convertdate($rowHistory["receive_date"],'D m Y',true);
			$arrGroupHis[] = $arrHistory;
		}
		if(sizeof($arrGroupHis) > 0 || isset($new_token)){
			$arrayResult['HISTORY'] = $arrGroupHis;
			if(isset($new_token)){
				$arrayResult['NEW_TOKEN'] = $new_token;
			}
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			http_response_code(204);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		if($lang_locale == 'th'){
			$arrayResult['RESPONSE_MESSAGE'] = "ท่านไม่มีสิทธิ์ใช้งานเมนูนี้";
		}else{
			$arrayResult['RESPONSE_MESSAGE'] = "You not have permission for this menu";
		}
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	if($lang_locale == 'th'){
		$arrayResult['RESPONSE_MESSAGE'] = "มีบางอย่างผิดพลาดกรุณาติดต่อสหกรณ์ #WS4004";
	}else{
		$arrayResult['RESPONSE_MESSAGE'] = "Something wrong please contact cooperative #WS4004";
	}
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>
