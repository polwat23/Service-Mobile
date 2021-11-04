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
	
	public function mergeMessageAction($groupDataTemplate){
		$dataTemplate = array();
		$dataTemplate["type"] = "text";
		$dataTemplate["text"] = $groupDataTemplate[0]["TITLE"];
		$index=0;
		foreach($groupDataTemplate AS $arrTemplate){
			$dataTemplate["quickReply"]["items"][$index]["type"] = "action";
			$dataTemplate["quickReply"]["items"][$index]["action"]["type"] = "message";
			$dataTemplate["quickReply"]["items"][$index]["action"]["label"] = $arrTemplate["LABEL"];
			$dataTemplate["quickReply"]["items"][$index]["action"]["text"] = $arrTemplate["TEXT"];
			$index++;
		}
		return $dataTemplate;
	}
	
	public function mergeDetetimePickerAction($groupDataTemplate){
		 function convertdatetimeLineFormat($datetime){
			$dateData = date_create($datetime);
			$date= date_format($dateData, 'Y-m-d');
			$time =date_format($dateData,'H:i');
			$dateFormat = $date.'t'.$time;
			return $dateFormat;
		}
		$dataTemplate = array();
		if($groupDataTemplate[0]["MODE"] =="datetime"){
			$initialData = convertdatetimeLineFormat($groupDataTemplate[0]["INITIAL"]);
			$maxData= convertdatetimeLineFormat($groupDataTemplate[0]["MAX"]);
			$minData=convertdatetimeLineFormat($groupDataTemplate[0]["MIN"]);	
		}else{
			$initialData = $groupDataTemplate[0]["INITIAL"];
			$maxData=$groupDataTemplate[0]["MAX"];
			$minData=$groupDataTemplate[0]["MIN"];
		}
		$dataTemplate["type"] = "text";
		$dataTemplate["text"] = $groupDataTemplate[0]["TITLE"];
		$dataTemplate["quickReply"]["items"][0]["type"] = "action";
		$dataTemplate["quickReply"]["items"][0]["action"]["type"] = "datetimepicker";
		$dataTemplate["quickReply"]["items"][0]["action"]["label"] = $groupDataTemplate[0]["LABEL"]??"Select date";
		$dataTemplate["quickReply"]["items"][0]["action"]["data"] = $groupDataTemplate[0]["DATA"];
		$dataTemplate["quickReply"]["items"][0]["action"]["mode"] = $groupDataTemplate[0]["MODE"];
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
	
	public function mergePostbackAction($groupDataTemplate){
		$dataTemplate["type"] = "text";
		$dataTemplate["text"] = $groupDataTemplate[0]["TITLE"];
		$index=0;
		foreach($groupDataTemplate AS $arrTemplate){
			$dataTemplate["quickReply"]["items"][$index]["type"] = "action";
			$dataTemplate["quickReply"]["items"][$index]["action"]["type"] = "postback";
			$dataTemplate["quickReply"]["items"][$index]["action"]["text"] = $arrTemplate["TEXT"];
			$dataTemplate["quickReply"]["items"][$index]["action"]["label"] = $arrTemplate["LABEL"];
			$dataTemplate["quickReply"]["items"][$index]["action"]["data"] = $arrTemplate["DATA"];
			$index++;
		}
		return $dataTemplate;
	}
	
	public function mergeCameraAction($groupDataTemplate){
		$dataTemplate = array();
		$dataTemplate["type"] = "text";
		$dataTemplate["text"] = $groupDataTemplate[0]["TITLE"];
		$index=0;
		foreach($groupDataTemplate AS $arrTemplate){
			$dataTemplate["quickReply"]["items"][$index]["type"] = "action";
			$dataTemplate["quickReply"]["items"][$index]["action"]["type"] = "camera";
			$dataTemplate["quickReply"]["items"][$index]["action"]["label"] = $arrTemplate["LABEL"]??"ถ่ายรูป";
			$index++;
		}
		return $dataTemplate;
	}

	
	public function mergeCameraRollAction($groupDataTemplate){
		$dataTemplate["type"] = "text";
		$dataTemplate["text"] = $groupDataTemplate[0]["TITLE"];
		$index=0;
		foreach($groupDataTemplate AS $arrTemplate){
			$dataTemplate["quickReply"]["items"][$index]["type"] = "action";
			$dataTemplate["quickReply"]["items"][$index]["action"]["type"] = "cameraRoll";
			$dataTemplate["quickReply"]["items"][$index]["action"]["label"] = $arrTemplate["LABEL"]??"อัลบั้มรูปภาพ";
			$index++;
		}
		return $dataTemplate;
	}
	
	public function mergeLocationAction($groupDataTemplate){
		$dataTemplate["type"] = "text";
		$dataTemplate["text"] = $groupDataTemplate[0]["TITLE"];
		$index=0;
		foreach($groupDataTemplate AS $arrTemplate){
			$dataTemplate["quickReply"]["items"][$index]["type"] = "action";
			$dataTemplate["quickReply"]["items"][$index]["action"]["type"] = "location";
			$dataTemplate["quickReply"]["items"][$index]["action"]["label"] = $arrTemplate["LABEL"]??"location";
			$index++;
		}
		return $dataTemplate;
	}
	
	public function mergeUrlAction($groupDataTemplate){
		$dataTemplate = array();
		$dataTemplate["type"] = "text";
		$dataTemplate["text"] = $groupDataTemplate[0]["TITLE"];
		$index=0;
		foreach($groupDataTemplate AS $arrTemplate){
			$dataTemplate["quickReply"]["items"][$index]["type"] = "action";
			$dataTemplate["quickReply"]["items"][$index]["action"]["type"] = "uri";
			$dataTemplate["quickReply"]["items"][$index]["action"]["label"] = $arrTemplate["LABEL"];
			$dataTemplate["quickReply"]["items"][$index]["action"]["uri"] = $arrTemplate["URL"];
			$index++;
		}
		return $dataTemplate;
	}
	
	public function mergeImageCarouselTemplate($groupDataTemplate){
		function convertdatetimeLineFormat($datetime){
			$dateData = date_create($datetime);
			$date= date_format($dateData, 'Y-m-d');
			$time =date_format($dateData,'H:i');
			$dateFormat = $date.'t'.$time;
			return $dateFormat;
		}
		
		$dataTemplate = array();
		$dataTemplate["type"] = "template";
		$dataTemplate["altText"] = "this is an image carousel template";
		$dataTemplate["template"]["type"] = "image_carousel";
		$index=0;
		foreach($groupDataTemplate AS $arrTemplate){
			$dataTemplate["template"]["columns"][$index]["imageUrl"] = $arrTemplate["IMAGE_URL"];
			if($arrTemplate["TYPE"] == "uri"){
				$dataTemplate["template"]["columns"][$index]["action"]["type"] = "uri";
				$dataTemplate["template"]["columns"][$index]["action"]["label"] = $arrTemplate["LABEL"];
				$dataTemplate["template"]["columns"][$index]["action"]["uri"] = $arrTemplate["URL"];
			}else if($arrTemplate["TYPE"] == "postback"){
				$dataTemplate["template"]["columns"][$index]["action"]["type"] = "postback";
				$dataTemplate["template"]["columns"][$index]["action"]["label"] = $arrTemplate["LABEL"];
				$dataTemplate["template"]["columns"][$index]["action"]["text"] = $arrTemplate["TEXT"];
				$dataTemplate["template"]["columns"][$index]["action"]["data"] = $arrTemplate["DATA"];
			}else if($arrTemplate["TYPE"] == "datetime_picker"){
				if($arrTemplate["MODE"] =="datetime"){
					$initialData = convertdatetimeLineFormat($arrTemplate["INITIAL"]);
					$maxData = convertdatetimeLineFormat($arrTemplate["MAX"]);
					$minData = convertdatetimeLineFormat($arrTemplate["MIN"]);	
				}else{
					$initialData = $arrTemplate["INITIAL"];
					$maxData = $arrTemplate["MAX"];
					$minData = $arrTemplate["MIN"];
				}
				$dataTemplate["template"]["columns"][$index]["action"]["type"] = "datetimepicker";
				$dataTemplate["template"]["columns"][$index]["action"]["label"] = $arrTemplate["LABEL"];
				$dataTemplate["template"]["columns"][$index]["action"]["data"] =  $arrTemplate["DATA"];
				$dataTemplate["template"]["columns"][$index]["action"]["mode"] =  $arrTemplate["MODE"];
				
				if(isset($arrTemplate["INITIAL"]) && $arrTemplate["INITIAL"] != ""){
					$dataTemplate["template"]["columns"][$index]["action"]["initial"] = $initialData;
				}
			
				if(isset($arrTemplate["MAX"]) && $arrTemplate["MAX"] != ""){
					$dataTemplate["quickReply"]["items"][0]["action"]["min"] = $maxData;
				}
				if(isset($arrTemplate["MIN"]) && $arrTemplate["MIN"] != ""){
					$dataTemplate["quickReply"]["items"][0]["action"]["min"] = $minData;
				}
			}else{
				$dataTemplate["template"]["columns"][$index]["action"]["type"] = "message";
				$dataTemplate["template"]["columns"][$index]["action"]["label"] = "ว่าง";
				$dataTemplate["template"]["columns"][$index]["action"]["text"] = "ว่าง";
			}
			$index++;			
		}
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