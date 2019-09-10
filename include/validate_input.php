<?php
$Json_Request = file_get_contents('php://input');
$jsonData = json_decode($Json_Request, TRUE);
$dataComing = array();


foreach($jsonData as $key => $data) {
	if(!is_array($data)){
		$dataComing[$key] = preg_replace('/[^ก-ฮA-Za-z0-9 \/\-@_${}(),:+!?.]/u','', strip_tags($data));
	}else{
		$dataComing[$key] = $data;
	}
}
?>