<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managenews',$conoracle)){
		$arrayGroup = array();
		$arrayExecute = array();
		if(isset($dataComing["start_date"]) && $dataComing["start_date"] != ""){
			$arrayExecute["start_date"] = $dataComing["start_date"];
		}
		if(isset($dataComing["end_date"]) && $dataComing["end_date"] != ""){
			$arrayExecute["end_date"] = $dataComing["end_date"];
		}
			
		$fetchNews = $conoracle->prepare("SELECT 
																id_news,
																news_title,
																news_detail,
																news_html,
																path_img_header,
																create_date,
																update_date,
																link_news_more,
																img_gallery_1,
																img_gallery_2,
																img_gallery_3,
																img_gallery_4,
																img_gallery_5,
																file_upload
															FROM gcnews
															WHERE is_use = '1' 
															".(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? 
																"and TO_CHAR(create_date,'yyyy-mm-dd') >= :start_date" : null)."
															".(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? 
																"and TO_CHAR(create_date,'yyyy-mm-dd') <= :end_date" : null). "
															and ROWNUM <= 20
															ORDER BY create_date DESC ");
		$fetchNews->execute($arrayExecute);
		while($rowNews = $fetchNews->fetch(PDO::FETCH_ASSOC)){
			$arrGroupNews = array();
			$arrGroupNews["ID_NEW"] = $rowNews["ID_NEWS"];
			$arrGroupNews["NEWS_TITLE"] = $rowNews["NEWS_TITLE"];
			$arrGroupNews["NEWS_DETAIL"] = $rowNews["NEWS_DETAIL"];
			$arrGroupNews["NEWS_HTML"] = file_get_contents(__DIR__.'/../../../..'.$rowNews["NEWS_HTML"]);
			$arrGroupNews["NEWS_DETAIL_SHORT"] = $lib->text_limit($rowNews["NEWS_DETAIL"],480);
			$arrGroupNews["PATH_IMG_HEADER"] = $rowNews["PATH_IMG_HEADER"];
			$arrGroupNews["LINK_News_MORE"] = $rowNews["LINK_NEWS_MORE"];
			$arrGroupNews["CREATE_DATE"] = $lib->convertdate($rowNews["CREATE_DATE"],'d m Y',true); 
			$arrGroupNews["UPDATE_DATE"] = $lib->convertdate($rowNews["UPDATE_DATE"],'d m Y',true);  
			$arrGroupNews["PATH_IMG_1"] = $rowNews["IMG_GALLERY_1"];
			$arrGroupNews["PATH_IMG_2"] = $rowNews["IMG_GALLERY_2"];
			$arrGroupNews["PATH_IMG_3"] = $rowNews["IMG_GALLERY_3"];
			$arrGroupNews["PATH_IMG_4"] = $rowNews["IMG_GALLERY_4"];
			$arrGroupNews["PATH_IMG_5"] = $rowNews["img_gallery_5"];
			$arrGroupNews["PATH_FILE"] = $rowNews["FILE_UPLOAD"];
			$arrGroupNews["str_count"] = $str_count;
			
			$arrayGroup[] = $arrGroupNews;
		}
		$arrayResult["NEWS_DATA"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		require_once('../../../../include/exit_footer.php');
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../../include/exit_footer.php');
		
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../../include/exit_footer.php');
	
}
?>