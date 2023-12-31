<?php
require_once('../autoload.php');

if(isset($author_token) && isset($payload) && isset($dataComing)){
	$status_token = $api->validate_jwttoken($author_token,$payload["exp"],$jwt_token,$config["SECRET_KEY_JWT"]);
	if($status_token){
		if($func->check_permission($payload["user_type"],$dataComing["menu_component"],$conmysql,'News')){
			$arrayGroupNews = array();
			$fetchNews = $conmysql->prepare("SELECT news_title,news_detail,path_img_header,username,update_date,id_news,link_news_more
											FROM gcnews LIMIT 5");
			$fetchNews->execute();
			while($rowNews = $fetchNews->fetch()){
				$arrayNews = array();
				$arrayNews["TITLE"] = $lib->text_limit($rowNews["news_title"]);
				$arrayNews["DETAIL"] = $lib->text_limit($rowNews["news_detail"],100);
				$arrayNews["IMAGE_HEADER"] = $rowNews["path_img_header"];
				$arrayNews["UPDATE_DATE"] = $lib->convertdate($rowNews["update_date"],'D m Y',true);
				$arrayNews["ID_NEWS"] = $rowNews["id_news"];
				$arrayNews["CREATE_BY"] = $rowNews["username"];
				$arrayNews["LINK_NEWS_MORE"] = $rowNews["link_news_more"];
				$arrayGroupNews[] = $arrayNews;
			}
			$arrayResult['NEWS'] = $arrayGroupNews;
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
		$arrayResult['RESPONSE_CODE'] = "HEADER500";
		$arrayResult['RESPONSE'] = "Authorization token invalid";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(203);
		echo json_encode($arrayResult);
		exit();
	}
}
?>