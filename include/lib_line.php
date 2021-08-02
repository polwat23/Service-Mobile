<?php

namespace Line;

class libraryLine {
	
	public function mergeTextMessage($message){
		$dataTemplate = array();
		$dataTemplate["type"] = "text";
		$dataTemplate["text"] = $message;
		return $dataTemplate;
	}
	
	public function mergeImageMessage($imageUrl){
		$dataTemplate = array();
		$dataTemplate["type"] = "image";
		$dataTemplate["originalContentUrl"] = $imageUrl;
		$dataTemplate["previewImageUrl"] = $imageUrl;
		return $dataTemplate;
	}
	
	public function mergeLocationMessage($title,$address,$latitude,$longitude){
		$dataTemplate = array();
		$dataTemplate["type"] = "location";
		$dataTemplate["title"] = $title;
		$dataTemplate["address"] = $address;
		$dataTemplate["latitude"] = $latitude;
		$dataTemplate["longitude"] = $longitude;
		return $dataTemplate;
	}
	
	public function sendLineBot($message){
		$json = file_get_contents(__DIR__.'/../config/config_constructor.json');
		$json_data = json_decode($json,true);
		$dataReturn = [];
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $json_data["LINEBOT_URL"]);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, [
			"authorization: Bearer ".$json_data["LINE_AUTH_KEY"],
			"cache-control: no-cache",
			"content-type: application/json; charset=UTF-8",
		]);
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($message));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

		$response = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);

		if ($err) {
			$dataReturn['RESULT'] = FALSE;
			$dataReturn['message'] = $err;
		} else {
			if($response == "{}"){
				$dataReturn['RESULT'] = TRUE;
				$dataReturn['message'] = 'Success';
			}else{
				$dataReturn['RESULT'] = FALSE;
				$dataReturn['message'] = $response;
			}
		}
		return $dataReturn;
	}
}
?>