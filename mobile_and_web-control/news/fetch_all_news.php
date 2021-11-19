<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'News')){
		$arrayGroupNews = array();
		$fetchNews = $conmssql->prepare("SELECT TOP 10 announce_title as news_title,announce_detail as news_detail,announce_cover as path_img_header,
										username as create_by,update_date,id_announce as id_news,category
										FROM gcannounce WHERE 
										CONVERT(CHAR,GETDATE(),20) BETWEEN 
										CONVERT(CHAR,effect_date,20) AND CONVERT(CHAR,due_date,20)
										ORDER BY update_date DESC");
		$fetchNews->execute();
		while($rowNews = $fetchNews->fetch(PDO::FETCH_ASSOC)){
			$arrayNews = array();
			$arrayNews["TITLE"] = $lib->text_limit($rowNews["news_title"]);
			$arrayNews["DETAIL"] = $lib->text_limit($rowNews["news_detail"],100);
			$arrayNews["DETAIL_FULL"] = $rowNews["news_detail"];
			$arrayNews["IMAGE_HEADER"] = $rowNews["path_img_header"];
			$arrayNews["UPDATE_DATE"] = $lib->convertdate($rowNews["update_date"],'D m Y',true);
			$arrayNews["UPDATE_RAW"] = $rowNews["update_date"];
			$arrayNews["ID_NEWS"] = $rowNews["id_news"];
			$arrayNews["CREATE_BY"] = $rowNews["create_by"];
			$arrayNews["CATEGORY_LIST"] = array();
			foreach(json_decode($rowNews["category"],true) as $category){
				$getCateInfo = $conmssql->prepare("SELECT category_code,category_desc FROM gccategory WHERE category_code = :code");
				$getCateInfo->execute([':code' => $category]);
				$rowCate = $getCateInfo->fetch(PDO::FETCH_ASSOC);
				$arrayNews["CATEGORY"] = $rowCate["category_code"];
				$arrayNews["CATEGORY_DESC"] = $rowCate["category_desc"];
				$arrayNews["CATEGORY_LIST"][] = $rowCate["category_code"];
			}
			//$arrayNews["LINK_NEWS_MORE"] = $rowNews["link_news_more"];
			//$arrayNews["FILE_UPLOAD"] = $rowNews["file_upload"];
			$arrayGroupNews[] = $arrayNews;
		}
		$getCategory = $conmssql->prepare("SELECT category_code,category_desc FROM gccategory WHERE is_use = '1'");
		$getCategory->execute();
		$arrGrpCate = array();
		while($rowCate = $getCategory->fetch(PDO::FETCH_ASSOC)){
			$arrCate = array();
			$arrCate["CATEGORY"] = $rowCate["category_code"];
			$arrCate["CATEGORY_DESC"] = $rowCate["category_desc"];
			$arrGrpCate[] = $arrCate;
		}
		$arrayResult['CATEGORY_LIST'] = $arrGrpCate;
		$arrayResult['ALLOW_SAVENEWS'] = FALSE;
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