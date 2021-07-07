<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['id_news'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'News')){
		$fetchDetailNews = $conoracle->prepare("SELECT gn.update_date,gn.img_gallery_1,gn.img_gallery_2,gn.img_gallery_3,
											gn.img_gallery_4,gn.img_gallery_5,gn.news_title,gn.news_detail,gn.create_by,gn.link_news_more,gn.news_html,gn.file_upload
											FROM gcnews gn
											WHERE gn.id_news = :id_news and gn.is_use = '1'");
		$fetchDetailNews->execute([':id_news' => $dataComing["id_news"]]);
		$rowDetailNews = $fetchDetailNews->fetch(PDO::FETCH_ASSOC);
		$arrayDetailNews = array();
		$arrayDetailNews["TITLE"] = $rowDetailNews["NEWS_TITLE"];
		$arrayDetailNews["LINK_NEWS_MORE"] = $rowDetailNews["LINK_NEWS_MORE"];
		$arrayDetailNews["DETAIL"] = $rowDetailNews["NEWS_DETAIL"];
		$arrayDetailNews["NEWS_HTML"] = $rowDetailNews["NEWS_HTML"];
		$arrayDetailNews["FILE_UPLOAD"] = $rowDetailNews["FILE_UPLOAD"];
		$arrayDetailNews["CREATE_BY"] = $rowDetailNews["CREATE_BY"];
		$arrayDetailNews["UPDATE_DATE"] = $lib->convertdate($rowDetailNews["UPDATE_DATE"],'D m Y',true);
		$path_img = array();
		if(isset($rowDetailNews["IMG_GALLERY_1"])){
			$path_img[] = $rowDetailNews["IMG_GALLERY_1"];
		}
		if(isset($rowDetailNews["IMG_GALLERY_2"])){
			$path_img[] = $rowDetailNews["IMG_GALLERY_2"];
		}
		if(isset($rowDetailNews["IMG_GALLERY_3"])){
			$path_img[] = $rowDetailNews["IMG_GALLERY_3"];
		}
		if(isset($rowDetailNews["IMG_GALLERY_4"])){
			$path_img[] = $rowDetailNews["IMG_GALLERY_4"];
		}
		if(isset($rowDetailNews["IMG_GALLERY_5"])){
			$path_img[] = $rowDetailNews["IMG_GALLERY_5"];
		}
		$arrayDetailNews["IMG"] = $path_img;
		$arrayResult['DETAIL_NEWS'] = $arrayDetailNews;
		$arrayResult['RESULT'] = TRUE;
		require_once('../../include/exit_footer.php');
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../include/exit_footer.php');
		
	}
}else{
	$filename = basename(__FILE__, '.php');
	$logStruc = [
		":error_menu" => $filename,
		":error_code" => "WS4004",
		":error_desc" => "ส่ง Argument มาไม่ครบ "."\n".json_encode($dataComing),
		":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
	];
	$log->writeLog('errorusage',$logStruc);
	$message_error = "ไฟล์ ".$filename." ส่ง Argument มาไม่ครบมาแค่ "."\n".json_encode($dataComing);
	$lib->sendLineNotify($message_error);
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
	
}
?>