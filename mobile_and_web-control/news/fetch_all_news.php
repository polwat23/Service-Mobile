<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'News')){
		$arrayGroupNews = array();
		$fetchNews = $conoracle->prepare("SELECT news_title,news_detail,path_img_header,create_by,update_date,id_news,link_news_more,news_html,file_upload
										FROM gcnews WHERE is_use = '1' ORDER BY create_date DESC LIMIT 5");
		$fetchNews->execute();
		while($rowNews = $fetchNews->fetch(PDO::FETCH_ASSOC)){
			$arrayNews = array();
			$arrayNews["TITLE"] = $lib->text_limit($rowNews["NEWS_TITLE"]);
			$arrayNews["DETAIL"] = $lib->text_limit($rowNews["NEWS_DETAIL"],100);
			$arrayNews["DETAIL_FULL"] = $rowNews["NEWS_DETAIL"];
			$arrayNews["NEWS_HTML"] = $rowNews["NEWS_HTML"];
			$arrayNews["IMAGE_HEADER"] = $rowNews["PATH_IMG_HEADER"];
			$arrayNews["UPDATE_DATE"] = $lib->convertdate($rowNews["UPDATE_DATE"],'D m Y',true);
			$arrayNews["ID_NEWS"] = $rowNews["ID_NEWS"];
			$arrayNews["CREATE_BY"] = $rowNews["CREATE_BY"];
			$arrayNews["LINK_NEWS_MORE"] = $rowNews["LINK_NEWS_MORE"];
			$arrayNews["FILE_UPLOAD"] = $rowNews["FILE_UPLOAD"];
			$arrayGroupNews[] = $arrayNews;
		}
		$arrayResult['NEWS'] = $arrayGroupNews;
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