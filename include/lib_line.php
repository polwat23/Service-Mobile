<?php

namespace Line;

class libraryLine {
	

	public function mergeTextMessage($message){
		$dataTemplate = array();
		$dataTemplate["type"] = "text";
		$dataTemplate["text"] = $message;
		return $dataTemplate;
	}
	
	public function stickerMessage($packageId,$stickerId){
		$dataTemplate = array();
		$dataTemplate["type"] = "sticker";
		$dataTemplate["packageId"] = $packageId;
		$dataTemplate["stickerId"] = $stickerId;
		return $dataTemplate;
	}
	
	public function mergeImageMessage($imageUrl){
		$dataTemplate = array();
		$dataTemplate["type"] = "image";
		$dataTemplate["originalContentUrl"] = $imageUrl;
		$dataTemplate["previewImageUrl"] = $imageUrl;
		return $dataTemplate;
	}
	
	public function videoMessage($originalContentUrl,$previewImageUrl){
		$dataTemplate = array();
		$dataTemplate["type"] = "video";
		$dataTemplate["originalContentUrl"] = $originalContentUrl;
		$dataTemplate["previewImageUrl"] = $previewImageUrl;
		return $dataTemplate;
	}
	
	public function audioMessage($originalContentUrl,$duration){
		$dataTemplate = array();
		$dataTemplate["type"] = "audio";
		$dataTemplate["originalContentUrl"] = $originalContentUrl;
		$dataTemplate["duration"] = $duration;
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
	
	public function mergeMessageAction($text,$label){
		$dataTemplate = array();
		$dataTemplate["type"] = "text";
		$dataTemplate["text"] = $text;
		$dataTemplate["quickReply"]["items"][0]["type"] = "action";
		$dataTemplate["quickReply"]["items"][0]["action"]["type"] = "message";
		$dataTemplate["quickReply"]["items"][0]["action"]["label"] = $label;
		$dataTemplate["quickReply"]["items"][0]["action"]["text"] = $text;
		return $dataTemplate;
	}
	
	public function mergeDetetimePickerAction($label,$data,$mode,$initial,$max,$min){
		 function convertdatetimeLineFormat($datetime){
			 $dateData = date_create($datetime);
			$date= date_format($dateData, 'Y-m-d');
			$time =date_format($dateData,'H:i');
			$dateFormat = $date.'t'.$time;
			return $dateFormat;
		}
		 
		$dataTemplate = array();
		if($mode =="datetime"){
			$initialData = convertdatetimeLineFormat($initial);
			$maxData= convertdatetimeLineFormat($max);
			$minData=convertdatetimeLineFormat($min);
				
		}else{
			$initialData=$initial;
			$maxData=$max;
			$minData=$min;
		}
		$dataTemplate["type"] = "text";
		$dataTemplate["text"] = $label;
		$dataTemplate["quickReply"]["items"][0]["type"] = "action";
		$dataTemplate["quickReply"]["items"][0]["action"]["type"] = "datetimepicker";
		$dataTemplate["quickReply"]["items"][0]["action"]["label"] = "Select date";
		$dataTemplate["quickReply"]["items"][0]["action"]["data"] = $data;
		$dataTemplate["quickReply"]["items"][0]["action"]["mode"] = $mode;
		if(isset($initial) && $initial != ""){
			$dataTemplate["quickReply"]["items"][0]["action"]["initial"] = $initialData;
		}
		if(isset($max) && $max != ""){
			$dataTemplate["quickReply"]["items"][0]["action"]["max"] = $maxData;
		}
		if(isset($min) && $min != ""){
			$dataTemplate["quickReply"]["items"][0]["action"]["min"] = $minData;
		}
		return $dataTemplate;
	}
	
	public function mergePostbackAction($text,$label,$data){
		$dataTemplate = array();
		$dataTemplate["type"] = "text";
		$dataTemplate["text"] = $text;
		$dataTemplate["quickReply"]["items"][0]["type"] = "action";
		$dataTemplate["quickReply"]["items"][0]["action"]["type"] = "postback";
		$dataTemplate["quickReply"]["items"][0]["action"]["label"] = $label;
		$dataTemplate["quickReply"]["items"][0]["action"]["data"] = $data;
		return $dataTemplate;
	}
	
	public function mergeCameraAction($text,$label){
		$dataTemplate = array();
		$dataTemplate["type"] = "text";
		$dataTemplate["text"] = $label;
		$dataTemplate["quickReply"]["items"][0]["type"] = "action";
		$dataTemplate["quickReply"]["items"][0]["action"]["type"] = "camera";
		$dataTemplate["quickReply"]["items"][0]["action"]["label"] = $label??"ถ่ายรูป";
		return $dataTemplate;
	}
	
	public function mergeCameraRollAction($text,$label){
		$dataTemplate = array();
		$dataTemplate["type"] = "text";
		$dataTemplate["text"] = $label;
		$dataTemplate["quickReply"]["items"][0]["type"] = "action";
		$dataTemplate["quickReply"]["items"][0]["action"]["type"] = "cameraRoll";
		$dataTemplate["quickReply"]["items"][0]["action"]["label"] = "Gallery";
		return $dataTemplate;
	}
	
	public function mergeLocationAction($text,$label){
		$dataTemplate = array();
		$dataTemplate["type"] = "text";
		$dataTemplate["text"] = $label;
		$dataTemplate["quickReply"]["items"][0]["type"] = "action";
		$dataTemplate["quickReply"]["items"][0]["action"]["type"] = "location";
		$dataTemplate["quickReply"]["items"][0]["action"]["label"] = "location";
		return $dataTemplate;
	}
	
	public function mergeUrlAction($text,$url,$label){
		$dataTemplate = array();
		$dataTemplate["type"] = "text";
		$dataTemplate["text"] = $text;
		$dataTemplate["quickReply"]["items"][0]["type"] = "action";
		$dataTemplate["quickReply"]["items"][0]["action"]["type"] = "uri";
		$dataTemplate["quickReply"]["items"][0]["action"]["label"] = $label??"label";
		$dataTemplate["quickReply"]["items"][0]["action"]["uri"] = $url;
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