<?php
$anonymous = '';
require_once('../autoload.php');

if(!$anonymous){
	$arrGroupAnn = array();
	if(isset($dataComing["firsttime"])){
		$firstapp = '1';
	}else{
		$firstapp = '-1';
	}
	$fetchAnn = $conmysql->prepare("SELECT priority,announce_cover,announce_title,announce_detail
									FROM gcannounce 
									WHERE (DATE_FORMAT(effect_date,'%Y-%m-%d') = DATE_FORMAT(NOW(),'%Y-%m-%d')
									and DATE_FORMAT(NOW(),'%H%i') >= DATE_FORMAT(effect_date,'%H%i')) OR first_time = :first_time");
	$fetchAnn->execute([':first_time' => $firstapp]);
	while($rowAnn = $fetchAnn->fetch(PDO::FETCH_ASSOC)){
		$arrAnn = array();
		$arrAnn["PRIORITY"] = $rowAnn["priority"];
		$arrAnn["ANNOUNCE_COVER"] = $rowAnn["announce_cover"];
		$arrAnn["ANNOUNCE_TITLE"] = $rowAnn["announce_title"];
		$arrAnn["ANNOUNCE_DETAIL"] = $rowAnn["announce_detail"];
		$arrGroupAnn[] = $arrAnn;
	}
	$arrayResult['ANNOUNCE'] = $arrGroupAnn;
	$arrayResult['RESULT'] = TRUE;
	echo json_encode($arrayResult);
}else{
	$arrGroupAnn = array();
	if(isset($dataComing["firsttime"])){
		$firstapp = '1';
	}else{
		$firstapp = '-1';
	}
	$fetchAnn = $conmysql->prepare("SELECT flag_granted,priority,announce_cover,announce_title,announce_detail
									FROM gcannounce 
									WHERE (DATE_FORMAT(effect_date,'%Y-%m-%d') = DATE_FORMAT(NOW(),'%Y-%m-%d')
									and DATE_FORMAT(NOW(),'%H%i') >= DATE_FORMAT(effect_date,'%H%i') OR first_time = :first_time) and flag_granted = 'all'");
	$fetchAnn->execute([':first_time' => $firstapp]);
	while($rowAnn = $fetchAnn->fetch(PDO::FETCH_ASSOC)){
		$arrAnn = array();
		$arrAnn["FLAG_GRANTED"] = $rowAnn["flag_granted"];
		$arrAnn["PRIORITY"] = $rowAnn["priority"];
		$arrAnn["ANNOUNCE_COVER"] = $rowAnn["announce_cover"];
		$arrAnn["ANNOUNCE_TITLE"] = $rowAnn["announce_title"];
		$arrAnn["ANNOUNCE_DETAIL"] = $rowAnn["announce_detail"];
		$arrGroupAnn[] = $arrAnn;
	}
	$arrayResult['ANNOUNCE'] = $arrGroupAnn;
	$arrayResult['RESULT'] = TRUE;
	echo json_encode($arrayResult);
}
?>