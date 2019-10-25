<?php
require_once('../../autoload.php');

if(isset($author_token) && isset($payload) && isset($dataComing)){
	$status_token = $api->validate_jwttoken($author_token,$payload["exp"],$jwt_token,$config["SECRET_KEY_JWT"]);
	if($status_token){
		if(isset($dataComing["id_news"])){
			if($func->check_permission($payload["user_type"],$dataComing["menu_component"],$conmysql,'News')){
				$fetchDetailNews = $conmysql->prepare("SELECT mg.name_gallery,mg.update_date,mg.path_img_1,mg.path_img_2,mg.path_img_3,
													mg.path_img_4,mg.path_img_5,mb.news_title,mb.news_detail,mb.username
													FROM gcnews mb LEFT JOIN gcgallery mg ON mb.id_gallery = mg.id_gallery 
													WHERE mb.id_news = :id_news");
				$fetchDetailNews->execute([':id_news' => $dataComing["id_news"]]);
				$rowDetailNews = $fetchDetailNews->fetch();
				$arrayDetailNews = array();
				$arrayDetailNews["TITLE"] = $rowDetailNews["news_title"];
				$arrayDetailNews["DETAIL"] = $rowDetailNews["news_detail"];
				$arrayDetailNews["NAME_GALLERY"] = $rowDetailNews["name_gallery"];
				$arrayDetailNews["CREATE_BY"] = $rowDetailNews["username"];
				$arrayDetailNews["UPDATE_DATE"] = $lib->convertdate($rowDetailNews["update_date"],'D m Y',true);
				$path_img = array();
				for($i = 1; $i <=5; $i++){
					if(isset($rowDetailNews["path_img_$i"])){
						array_push($path_img, $rowDetailNews["path_img_$i"]);
					}
				}
				$arrayDetailNews["IMG"] = $path_img;
				$arrayResult['DETAIL_NEWS'] = $arrayDetailNews;
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE_CODE'] = "PARAM500";
				$arrayResult['RESPONSE'] = "Not permission this menu";
				$arrayResult['RESULT'] = FALSE;
				http_response_code(203);
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "PARAM400";
			$arrayResult['RESPONSE'] = "Not complete parameter";
			$arrayResult['RESULT'] = FALSE;
			http_response_code(203);
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "HEADER500";
		$arrayResult['RESPONSE'] = "Authorization token invalid";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(203);
		echo json_encode($arrayResult);
		exit();
	}
}
?>