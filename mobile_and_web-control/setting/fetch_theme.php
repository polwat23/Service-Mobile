<?php
	require_once('../../autoload.php');
	
    $json = json_decode(file_get_contents('../../json/theme.json'), true); 
	$deviceResolution = 'fhd';
    if(isset($dataComing['resolution'])){
        if($dataComing['resolution'] >= 1440){
            $deviceResolution = 'qhd';
        }else if($dataComing['resolution'] >= 1080){
            $deviceResolution = 'fhd';
        }else if($dataComing['resolution'] >= 720){
            $deviceResolution = 'hd';
        }else{
            $deviceResolution = 'sd';
        }
    }
    $responseData = [];
    $responseData['default'] = $json['default'];
    $theme = [];
    foreach($json['theme'] as $value){
        $getImageBySize = [];
        $getImageBySize['name'] = $value['name'];
        $getImageBySize['url'] = $value[$deviceResolution];
        array_push($theme, $getImageBySize);
    }
    $responseData['theme'] = $theme;
    echo json_encode($responseData);
?>