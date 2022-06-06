<?php
date_default_timezone_set("Asia/Bangkok");

require_once(__DIR__.'/../include/connection.php');
use Connection\connection;

$con = new connection();
$conmysql = $con->connecttomysql();
$arrayGroupNews = array();
header( "Content-type: text/xml");
$rss = "<?xml version='1.0' encoding='UTF-8'?><rss version='2.0'>";
$fetchNews = $conmysql->prepare("SELECT news_title,news_detail,path_img_header,create_by,update_date,id_news,link_news_more,news_html,file_upload
								FROM gcnews WHERE is_use = '1' ORDER BY update_date DESC LIMIT 5");
$fetchNews->execute();
while($rowNews = $fetchNews->fetch(PDO::FETCH_ASSOC)){
	$rss .= "<news>";
	$rss .= "<title>".$rowNews["news_title"]."</title>";
	$rss .= "<banner>".$rowNews["path_img_header"]."</banner>";
	$rss .= "<create_by>".$rowNews["create_by"]."</create_by>";
	$rss .= "<last_update>".$rowNews["update_date"]."</last_update>";
	$rss .= "<link_more>".$rowNews["link_news_more"]."</link_more>";
	$rss .= "<detail>".htmlentities($rowNews["news_html"])."</detail>";
	$rss .= "<file_upload>".$rowNews["file_upload"]."</file_upload>";
	$rss .= "</news>";
}


$rss .= "</rss>";
echo $rss;
exit();

?>