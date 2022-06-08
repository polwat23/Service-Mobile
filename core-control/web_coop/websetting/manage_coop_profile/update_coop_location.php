<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_webcoopprofile',,$dataComing)){
									
	if(isset($dataComing["history_html_root_"])){
	$history = '<!DOCTYPE HTML>
							<html>
							<head>
						  <meta charset="UTF-8">
						  <meta name="viewport" content="width=device-width, initial-scale=1.0">
						  '.$dataComing["history_html_root_"].'
						  </body>
							</html>';
	}
	$groupmission = $dataComing["mission"];
	$mission = implode(",",$groupmission);
	$objective = implode(",",$dataComing["objective"]);
    $update_location = $conmysql->prepare("UPDATE 
													webcoopprofile
												SET
													location = :location,
												
												WHERE
													id_webcoopprofile = :id_webcoopprofile
												");
	if($update_location->execute([
			':location' =>  $dataComing["location"],
	])){
			$arrayResult['RESULT'] = True;
			echo json_encode($arrayResult);
	}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถอัพเดทได้ กรุณาติดต่อผู้พัฒนา ";
			$arrayResult['RESULT'] = FALSE;
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