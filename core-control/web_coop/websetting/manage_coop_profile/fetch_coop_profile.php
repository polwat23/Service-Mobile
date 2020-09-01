<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'webcoop','managecoopprofile')){
		$arrayGroup = array();
		$fetchCoopProfile = $conmysql->prepare("SELECT
													id_webcoopprofile,
													address,
													location,
													tel,
													fax,
													facebook_name,
													facebook_url,
													line_name,
													line_url,
													email,
													vision,
													mission,
													update_date,
													objective,
													history
												 FROM
												 webcoopprofile
												 
												   ");
		$fetchCoopProfile->execute();
		while($rowCoopProfile = $fetchCoopProfile->fetch(PDO::FETCH_ASSOC)){
			$arrCoopProfile["ID_WEBCOOPPROFILE"] = $rowCoopProfile["id_webcoopprofile"];
			$arrCoopProfile["ADDRESS"] = $rowCoopProfile["address"];
			$arrCoopProfile["LOCATION"] = $rowCoopProfile["location"];
			$arrCoopProfile["TEL"] = $rowCoopProfile["tel"];
		//	$arrCoopProfile["CREATE_DATE"] = $lib->convertdate($rowCoopProfile["create_date"],'d m Y',true); 
			$arrCoopProfile["FAX"] = $rowCoopProfile["fax"];
			$arrCoopProfile["FACEBOOK_NAME"] = $rowCoopProfile["facebook_name"];
			$arrCoopProfile["FACEBOOK_URL"] = $rowCoopProfile["facebook_url"];
			$arrCoopProfile["LINE_NAME"] = $rowCoopProfile["line_name"];
			$arrCoopProfile["LINE_URL"] = $rowCoopProfile["line_url"];
			$arrCoopProfile["EMAIL"] = $rowCoopProfile["email"];
			$arrCoopProfile["VISION"] = $rowCoopProfile["vision"];
			$arrCoopProfile["HISTORY"] = $rowCoopProfile["history"];
			$arrCoopProfile["UPDATE_DATE"] = $rowCoopProfile["update_date"];
		
			
			$mission = explode(',',$rowCoopProfile["mission"]);
			$objective = explode(',',$rowCoopProfile["objective"]);
	
			$arrCoopProfile["MISSION"] = $mission;
			$arrCoopProfile["OBJECTIVE"] = $objective;
			$arrayGroup[] = $arrCoopProfile;
		}
		$arrayResult["PROFILE_DATA"] = $arrayGroup;
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