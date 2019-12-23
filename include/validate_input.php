<?php

$Json_Request = file_get_contents("php://input");
$jsonData = json_decode($Json_Request,TRUE);
$dataComing = array();

if(isset($jsonData) && is_array($jsonData)){
	foreach($jsonData as $key => $data){
		if(!is_array($data)){
			if(strpos($key,'_spc_') === false){
				$dataComing[$key] = preg_replace('/[^\p{Thai}A-Za-z0-9 \/\-@_${}(),#<>=:+!?.]/u','', strip_tags($data));
			}else{
				$dataComing[$key] = $data;
			}
		}else{
			$dataComing[$key] = array_map(function($text){
				return preg_replace('/[^\p{Thai}A-Za-z0-9 \/\-@_${}(),#<>=:+!?.]/u','', $text);
			},$data);
		}
	}
}
?>