<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'News')){
		$arrayGroupNews = array();
		$fetchNews = $conmysql->prepare("SELECT news_title,news_detail,path_img_header,create_by,update_date,id_news,link_news_more
										FROM gcnews LIMIT 5");
		$fetchNews->execute();
		while($rowNews = $fetchNews->fetch()){
			$arrayNews = array();
			$arrayNews["TITLE"] = $lib->text_limit($rowNews["news_title"]);
			$arrayNews["DETAIL"] = $lib->text_limit($rowNews["news_detail"],100);
			$arrayNews["IMAGE_HEADER"] = $rowNews["path_img_header"];
			$arrayNews["UPDATE_DATE"] = $lib->convertdate($rowNews["update_date"],'D m Y',true);
			$arrayNews["ID_NEWS"] = $rowNews["id_news"];
			$arrayNews["CREATE_BY"] = $rowNews["create_by"];
			$arrayNews["LINK_NEWS_MORE"] = $rowNews["link_news_more"];
			$arrayGroupNews[] = $arrayNews;
		}
		if(sizeof($arrayGroupNews) > 0 || isset($new_token)){
			$arrayResult['NEWS'] = $arrayGroupNews;
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