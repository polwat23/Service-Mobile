<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	
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
												history,
												playstore,
												appstore,
												huawei,
												youtube_name,
												youtube_url
											 FROM
											 webcoopprofile
											 
											   ");
	$fetchCoopProfile->execute();
	while($rowCoopProfile = $fetchCoopProfile->fetch(PDO::FETCH_ASSOC)){
		$arrCoopProfile["ID_WEBCOOPPROFILE"] = $rowCoopProfile["id_webcoopprofile"];
		$arrCoopProfile["ADDRESS"] = $rowCoopProfile["address"];
		$arrCoopProfile["LOCATION"] = $rowCoopProfile["location"];
		$arrCoopProfile["TEL"] = $rowCoopProfile["tel"];
		$arrCoopProfile["FAX"] = $rowCoopProfile["fax"];
		$arrCoopProfile["FACEBOOK_NAME"] = $rowCoopProfile["facebook_name"];
		$arrCoopProfile["FACEBOOK_URL"] = $rowCoopProfile["facebook_url"];
		$arrCoopProfile["LINE_NAME"] = $rowCoopProfile["line_name"];
		$arrCoopProfile["LINE_URL"] = $rowCoopProfile["line_url"];
		$arrCoopProfile["EMAIL"] = $rowCoopProfile["email"];
		$arrCoopProfile["VISION"] = $rowCoopProfile["vision"];
		$arrCoopProfile["PLAYSTORE"] = $rowCoopProfile["playstore"];
		$arrCoopProfile["HUAWEI"] = $rowCoopProfile["huawei"];
		$arrCoopProfile["APPSTORE"] = $rowCoopProfile["appstore"];
		$arrCoopProfile["YOUTUBE_NAME"] = $rowCoopProfile["youtube_name"];
		$arrCoopProfile["YOUTUBE_URL"] = $rowCoopProfile["youtube_url"];
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
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>