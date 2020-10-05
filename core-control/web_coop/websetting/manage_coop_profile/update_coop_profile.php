<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id',
								'id_webcoopprofile',
								'address',
								'email',
								'facebook_name',
								'facebook_url',
								'fax','line_name',
								'line_url',
								'location',
								'tel',
								'vision',
								'objective']
								,$dataComing)){
									
	if($func->check_permission_core($payload,'webcoop','managecoopprofile')){
		
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
		  $update_coop_profile = $conmysql->prepare("UPDATE 
														webcoopprofile
													SET
														address = :address,
														location = :location,
														tel = :tel,
														fax = :fax,
														facebook_name = :facebook_name,
														facebook_url = :facebook_url,
														line_name = :line_name,
														line_url = :line_url,
														email = :email,
														vision = :vision,
														mission = :mission,
														objective = :objective,
														history = :history,
														playstore = :playstore,
														appstore = :appstore
													WHERE
														id_webcoopprofile = :id_webcoopprofile
													");
			if($update_coop_profile->execute([
				':address' =>  $dataComing["address"],
				':location' =>  $dataComing["location"],
				':tel' =>  $dataComing["tel"],
				':fax' =>  $dataComing["fax"],
				':facebook_name' =>  $dataComing["facebook_name"],
				':facebook_url' =>  $dataComing["facebook_url"],
				':line_name' =>  $dataComing["line_name"],
				':line_url' =>  $dataComing["line_url"],
				':email' =>  $dataComing["email"],
				':vision' =>  $dataComing["vision"],
				':mission' => $mission,
				':objective' => $objective,
				':history' => $history,
				':id_webcoopprofile' =>  $dataComing["id_webcoopprofile"],
				':playstore' =>  $dataComing["playstore"],
				':appstore' =>  $dataComing["appstore"]
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