<?php
$skip_autoload = true;
require_once('../autoload.php');

if($lib->checkCompleteArgument(['id_announce'],$dataComing)){
	if($dataComing["priority"] == 'ask'){
		$insertResponseAnn = $conmysql->prepare("INSERT INTO logacceptannounce(member_no,id_announce,status_accept,id_userlogin)
												VALUES(:member_no,:id_announce,:status_accept,:id_userlogin)");
		if($insertResponseAnn->execute([
			':member_no' => $payload["member_no"],
			':id_announce' => $dataComing["id_announce"],
			':status_accept' => $dataComing["status_accept"],
			':id_userlogin' => $payload["id_userlogin"]
		])){
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrExecute = [
				':member_no' => $payload["member_no"],
				':id_announce' => $dataComing["id_announce"],
				':status_accept' => $dataComing["status_accept"],
				':id_userlogin' => $payload["id_userlogin"]
			];
			$arrError = array();
			$arrError["EXECUTE"] = $arrExecute;
			$arrError["QUERY"] = $insertResponseAnn;
			$lib->addLogtoTxt($arrError,'response_log_announce');
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
		}
	}else{
		$insertResponseAnn = $conmysql->prepare("INSERT INTO logacceptannounce(member_no,id_announce,id_userlogin)
												VALUES(:member_no,:id_announce,:id_userlogin)");
		if($insertResponseAnn->execute([
			':member_no' => $payload["member_no"],
			':id_announce' => $dataComing["id_announce"],
			':id_userlogin' => $payload["id_userlogin"]
		])){
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
		}else{
			$arrExecute = [
				':member_no' => $payload["member_no"],
				':id_announce' => $dataComing["id_announce"],
				':id_userlogin' => $payload["id_userlogin"]
			];
			$arrError = array();
			$arrError["EXECUTE"] = $arrExecute;
			$arrError["QUERY"] = $insertResponseAnn;
			$lib->addLogtoTxt($arrError,'response_log_announce');
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
		}
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>
