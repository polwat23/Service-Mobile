<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_news'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managenews')){

		$update_news= $conmysql->prepare("UPDATE gcnews SET 
												news_title = :news_title,
												news_detail = :news_detail,
												path_img_header=:path_img_header,
												link_news_more = :link_news_more,
												create_by = :create_by
										  WHERE id_news = :id_news;");
			if($update_news->execute([
				':id_news' =>  $dataComing["id_news"],
				':news_title' =>  $dataComing["news_title"],
				':news_detail' =>  $dataComing["news_detail"],
				':path_img_header' => $dataComing["path_img_header"],
				':link_news_more' =>  $dataComing["link_news_more"],
				//':id_gallery' =>  $last_id_gallary,
				':create_by' => $dataComing["create_by"]
			])){
				$arrayResult["RESULT"] = TRUE;
				echo json_encode($arrayResult);

			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มข่าวสารได้ กรุณาติดต่อผู้พัฒนา ";
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