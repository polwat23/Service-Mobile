<?php
namespace Line;

use Connection\connection;

class libraryLine {
	private $con;
	
	function __construct() {
		$connection = new connection();
		$this->con = $connection->connecttomysql();
	}
	
	public function checkBindAccount($line_token){
		$fetchBindAccount = $this->con->prepare("SELECT member_no FROM gcmemberaccount WHERE line_token = :line_token");
		$fetchBindAccount->execute([':line_token' => $line_token]);
		if($fetchBindAccount->rowCount() > 0){
			return true;
		}else{
			return false;
		}
	}

	public function prepareMessageText($message){
		$arrResponse[0]["type"] = "text";
		$arrResponse[0]["label"] = $message;
		$arrResponse[0]["text"] = $message;
		return $arrResponse;
	}
	public function prepareFlexMessage($altText,$body){
		$arrResponse[0]["type"] = "flex";
		$arrResponse[0]["altText"] = $altText;
		$arrResponse[0]["contents"] = $body;
		return $arrResponse;
	}
	public function sendLineBot($message){
		$json = file_get_contents(__DIR__.'/../config/config_linebotKfinance.json');
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
	/*
	public function sendApiToServer($data,$url){
		$json = file_get_contents(__DIR__.'/../config/config_linebotKfinance.json');
		$json_data = json_decode($json,true);
		$dataReturn = [];
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, [
			"authorization: Bearer ".$json_data["LINE_AUTH_KEY"],
			"cache-control: no-cache",
			"content-type: application/json; charset=UTF-8"
		]);
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

		$response = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);
		curl_exec($curl)
		if ($err) {
			$dataReturn['RESULT'] = FALSE;
			$dataReturn['DATA'] = $err;
		} else {
			if($response == "{}"){
				$dataReturn['RESULT'] = TRUE;
				$dataReturn['DATA'] = 'Success';

			}else{
				$dataReturn['RESULT'] = FALSE;
				$dataReturn['DATA'] = $response;
			}
		}
		return $dataReturn;
	}
	*/
	public function sendApiToServer($data,$url){
		$json = file_get_contents(__DIR__.'/../config/config_linebot.json');
		$json_data = json_decode($json,true);
		$dataReturn = [];
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_POST, false);
		curl_setopt($curl, CURLOPT_HTTPHEADER, [
			"authorization: Bearer ".$json_data["LINE_AUTH_KEY"],
			"cache-control: no-cache",
			"content-type: application/json; charset=UTF-8",
		]);
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
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
	
	
	
	public function sendPushLineBot($message){
		$json = file_get_contents(__DIR__.'/../config/config_linebot.json');
		$json_data = json_decode($json,true);
		$dataReturn = [];
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $json_data["LINEBOTPUSH_URL"]);
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
		
	//Kfinance
	public function flexExData(){
		$dataTemplate = array();	
		$dataTemplate["type"] = "flex";
		$dataTemplate["altText"] = "ข้อมูลสินเชื่อ";
		$dataTemplate["contents"]["type"] = "bubble";
		$dataTemplate["contents"]["body"]["type"] = "box";
		$dataTemplate["contents"]["body"]["layout"] = "vertical";
		$dataTemplate["contents"]["body"]["contents"][0]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][0]["text"] = "Kfinance";
		$dataTemplate["contents"]["body"]["contents"][0]["weight"] = "bold";
		$dataTemplate["contents"]["body"]["contents"][0]["size"] = "xxl";
		$dataTemplate["contents"]["body"]["contents"][0]["margin"] = "md";
		$dataTemplate["contents"]["body"]["contents"][1]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][1]["text"] = "เลขที่สัญญา : 41254214/124";
		$dataTemplate["contents"]["body"]["contents"][1]["size"] = "xs";
		$dataTemplate["contents"]["body"]["contents"][1]["color"] = "#aaaaaa";
		$dataTemplate["contents"]["body"]["contents"][1]["wrap"] = true;
		$dataTemplate["contents"]["body"]["contents"][1]["align"] = "end";
		$dataTemplate["contents"]["body"]["contents"][2]["type"] = "box";
		$dataTemplate["contents"]["body"]["contents"][2]["layout"] = "vertical";
		$dataTemplate["contents"]["body"]["contents"][2]["contents"][0]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][2]["contents"][0]["text"] = "ยี่ห้อ/รุ่น :  KAVASKI Ninja 300 ABS";
		$dataTemplate["contents"]["body"]["contents"][2]["contents"][0]["size"] = "xs";
		$dataTemplate["contents"]["body"]["contents"][2]["contents"][0]["color"] = "#aaaaaa";
		$dataTemplate["contents"]["body"]["contents"][3]["type"] = "separator";
		$dataTemplate["contents"]["body"]["contents"][3]["margin"] = "xxl";
		$dataTemplate["contents"]["body"]["contents"][4]["type"] = "box";
		$dataTemplate["contents"]["body"]["contents"][4]["layout"] = "vertical";
		$dataTemplate["contents"]["body"]["contents"][4]["margin"] = "xxl";
		$dataTemplate["contents"]["body"]["contents"][4]["spacing"] = "sm";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][0]["type"] = "box";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][0]["layout"] = "horizontal";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][0]["contents"][0]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][0]["contents"][0]["text"] = "ค่างวดทั้งหมด";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][0]["contents"][0]["size"] = "sm";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][0]["contents"][0]["color"] = "#3498DB";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][0]["contents"][0]["flex"] = 0;
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][0]["contents"][0]["weight"] = "bold";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][0]["contents"][1]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][0]["contents"][1]["text"] = "198,480 บาท";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][0]["contents"][1]["size"] = "sm";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][0]["contents"][1]["color"] = "#3498DB";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][0]["contents"][1]["align"] = "end";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][0]["contents"][1]["weight"] = "bold";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][1]["type"] = "box";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][1]["layout"] = "horizontal";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][1]["contents"][0]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][1]["contents"][0]["text"] = "ชำระแล้ว";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][1]["contents"][0]["size"] = "sm";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][1]["contents"][0]["color"] = "#555555";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][1]["contents"][0]["flex"] = 0;
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][1]["contents"][1]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][1]["contents"][1]["text"] = "190210";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][1]["contents"][1]["size"] = "sm";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][1]["contents"][1]["color"] = "#111111";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][1]["contents"][1]["align"] = "end";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][2]["type"] = "box";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][2]["layout"] = "horizontal";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][2]["contents"][0]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][2]["contents"][0]["text"] = "งวดปัจจุบัน/ทั้งหมด";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][2]["contents"][0]["size"] = "sm";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][2]["contents"][0]["color"] = "#555555";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][2]["contents"][0]["flex"] = 0;
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][2]["contents"][1]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][2]["contents"][1]["size"] = "sm";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][2]["contents"][1]["color"] = "#111111";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][2]["contents"][1]["align"] = "end";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][2]["contents"][1]["contents"][0]["type"] = "span";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][2]["contents"][1]["contents"][0]["text"] = "47";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][2]["contents"][1]["contents"][0]["color"] = "#3498DB";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][2]["contents"][1]["contents"][0]["weight"] = "bold";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][2]["contents"][1]["contents"][1]["type"] = "span";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][2]["contents"][1]["contents"][1]["text"] = "/48";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][2]["contents"][1]["contents"][1]["weight"] = "bold";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][3]["type"] = "separator";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][3]["margin"] = "xxl";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][4]["type"] = "box";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][4]["layout"] = "horizontal";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][4]["margin"] = "xxl";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][4]["contents"][0]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][4]["contents"][0]["text"] = "ค่างวดคงเหลือ";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][4]["contents"][0]["size"] = "sm";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][4]["contents"][0]["color"] = "#555555";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][4]["contents"][1]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][4]["contents"][1]["text"] = "8,170";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][4]["contents"][1]["size"] = "sm";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][4]["contents"][1]["color"] = "#111111";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][4]["contents"][1]["align"] = "end";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][5]["type"] = "box";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][5]["layout"] = "horizontal";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][5]["contents"][0]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][5]["contents"][0]["text"] = "ผ่อนงวดละ(บาท)";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][5]["contents"][0]["size"] = "sm";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][5]["contents"][0]["color"] = "#555555";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][5]["contents"][1]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][5]["contents"][1]["text"] = "4,135 ";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][5]["contents"][1]["size"] = "sm";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][5]["contents"][1]["color"] = "#111111";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][5]["contents"][1]["align"] = "end";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][6]["type"] = "box";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][6]["layout"] = "horizontal";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][6]["contents"][0]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][6]["contents"][0]["text"] = "ยอดชำระงวดปัจจุบัน";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][6]["contents"][0]["size"] = "sm";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][6]["contents"][0]["color"] = "#555555";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][6]["contents"][1]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][6]["contents"][1]["text"] = "4,135";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][6]["contents"][1]["size"] = "sm";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][6]["contents"][1]["color"] = "#111111";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][6]["contents"][1]["align"] = "end";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][7]["type"] = "box";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][7]["layout"] = "horizontal";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][7]["contents"][0]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][7]["contents"][0]["text"] = "กำหนดชำระวันที่";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][7]["contents"][0]["size"] = "sm";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][7]["contents"][0]["color"] = "#555555";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][7]["contents"][1]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][7]["contents"][1]["size"] = "sm";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][7]["contents"][1]["align"] = "end";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][7]["contents"][1]["text"] = "02";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][7]["contents"][1]["color"] = "#555555";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["type"] = "box";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["layout"] = "horizontal";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][0]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][0]["text"] = "วันที่ครบกำหนดงวดสุดท้าย";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][0]["size"] = "sm";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][0]["color"] = "#555555";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][0]["wrap"] = true;
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][1]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][1]["text"] = "02/01/2022";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][1]["align"] = "end";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][1]["color"] = "#555555";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][1]["size"] = "sm";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][1]["gravity"] = "center";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["type"] = "box";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["layout"] = "horizontal";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][0]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][0]["text"] = "สถานะสัญญา";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][0]["size"] = "sm";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][1]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][1]["text"] = "ปกติ";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][1]["color"] = "#3498DB";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][1]["size"] = "sm";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][1]["align"] = "end";
		$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][1]["weight"] = "bold";
		$dataTemplate["contents"]["body"]["contents"][5]["type"] = "separator";
		$dataTemplate["contents"]["body"]["contents"][5]["margin"] = "xxl";
		$dataTemplate["contents"]["body"]["contents"][6]["type"] = "box";
		$dataTemplate["contents"]["body"]["contents"][6]["layout"] = "vertical";
		$dataTemplate["contents"]["body"]["contents"][6]["margin"] = "md";
		$dataTemplate["contents"]["body"]["contents"][6]["contents"][0]["type"] = "box";
		$dataTemplate["contents"]["body"]["contents"][6]["contents"][0]["layout"] = "baseline";
		$dataTemplate["contents"]["body"]["contents"][6]["contents"][0]["contents"][0]["type"] = "icon";
		$dataTemplate["contents"]["body"]["contents"][6]["contents"][0]["contents"][0]["url"] = "https://cdn.thaicoop.co/icon/pdf.png";
		$dataTemplate["contents"]["body"]["contents"][6]["contents"][0]["contents"][0]["offsetStart"] = "60px";
		$dataTemplate["contents"]["body"]["contents"][6]["contents"][0]["contents"][0]["size"] = "md";
		$dataTemplate["contents"]["body"]["contents"][6]["contents"][0]["contents"][1]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][6]["contents"][0]["contents"][1]["text"] = "ดูการทำรายการ";
		$dataTemplate["contents"]["body"]["contents"][6]["contents"][0]["contents"][1]["align"] = "center";
		$dataTemplate["contents"]["body"]["contents"][6]["contents"][0]["contents"][1]["color"] = "#3498DB";
		$dataTemplate["contents"]["body"]["contents"][6]["contents"][0]["contents"][1]["weight"] = "bold";
		$dataTemplate["contents"]["body"]["contents"][6]["contents"][0]["justifyContent"] = "flex-end";
		$dataTemplate["contents"]["body"]["contents"][6]["contents"][0]["alignItems"] = "flex-end";
		$dataTemplate["contents"]["body"]["contents"][6]["height"] = "40px";
		$dataTemplate["contents"]["body"]["contents"][6]["width"] = "100%";
		$dataTemplate["contents"]["body"]["contents"][6]["borderWidth"] = "normal";
		$dataTemplate["contents"]["body"]["contents"][6]["borderColor"] = "#3498DB";
		$dataTemplate["contents"]["body"]["contents"][6]["cornerRadius"] = "lg";
		$dataTemplate["contents"]["body"]["contents"][6]["justifyContent"] = "center";
		$dataTemplate["contents"]["body"]["contents"][6]["action"]["type"] = "message";
		$dataTemplate["contents"]["body"]["contents"][6]["action"]["label"] = "action";
		$dataTemplate["contents"]["body"]["contents"][6]["action"]["text"] = "ดูรายการทั้งหมด";
		return $dataTemplate;
	}
	//Kfinance
	public function flexRecept(){
	
	$dataTemplate = array();
	$dataTemplate["type"] = "flex";
	$dataTemplate["altText"] = "รายการสินเชื่อ";
	$dataTemplate["contents"]["type"] = "bubble";
	$dataTemplate["contents"]["body"]["type"] = "box";
	$dataTemplate["contents"]["body"]["layout"] = "vertical";
	$dataTemplate["contents"]["body"]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][0]["weight"] = "bold";
	$dataTemplate["contents"]["body"]["contents"][0]["size"] = "xxl";
	$dataTemplate["contents"]["body"]["contents"][0]["margin"] = "md";
	$dataTemplate["contents"]["body"]["contents"][0]["contents"][0]["type"] = "span";
	$dataTemplate["contents"]["body"]["contents"][0]["contents"][0]["text"] = "K";
	$dataTemplate["contents"]["body"]["contents"][0]["contents"][0]["color"] = "#F4D03F";
	$dataTemplate["contents"]["body"]["contents"][0]["contents"][1]["type"] = "span";
	$dataTemplate["contents"]["body"]["contents"][0]["contents"][1]["text"] = "finance";
	$dataTemplate["contents"]["body"]["contents"][0]["contents"][1]["color"] = "#3498DB";
	$dataTemplate["contents"]["body"]["contents"][1]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][1]["text"] = "เลขที่สัญญา : 41254214/124";
	$dataTemplate["contents"]["body"]["contents"][1]["size"] = "xs";
	$dataTemplate["contents"]["body"]["contents"][1]["color"] = "#aaaaaa";
	$dataTemplate["contents"]["body"]["contents"][1]["wrap"] = true;
	$dataTemplate["contents"]["body"]["contents"][1]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][2]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][2]["text"] = "ยี่ห้อ/รุ่น :  KAVASKI Ninja 300 ABS";
	$dataTemplate["contents"]["body"]["contents"][2]["size"] = "xs";
	$dataTemplate["contents"]["body"]["contents"][2]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][2]["color"] = "#aaaaaa";
	$dataTemplate["contents"]["body"]["contents"][3]["type"] = "separator";
	$dataTemplate["contents"]["body"]["contents"][3]["margin"] = "xxl";
	$dataTemplate["contents"]["body"]["contents"][3]["color"] = "#3498DB";
	$dataTemplate["contents"]["body"]["contents"][4]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["layout"] = "vertical";
	$dataTemplate["contents"]["body"]["contents"][4]["margin"] = "xxl";
	$dataTemplate["contents"]["body"]["contents"][4]["spacing"] = "sm";
	
	
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][0]["text"] = "งวดที่ 13";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][0]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][0]["weight"] = "bold";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][1]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][1]["layout"] = "horizontal";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][1]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][1]["contents"][0]["text"] = "วันที่ชำระ";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][1]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][1]["contents"][0]["color"] = "#555555";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][1]["contents"][0]["flex"] = 0;
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][1]["contents"][1]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][1]["contents"][1]["text"] = "30/07/2564";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][1]["contents"][1]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][1]["contents"][1]["color"] = "#111111";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][1]["contents"][1]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][2]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][2]["layout"] = "horizontal";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][2]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][2]["contents"][0]["text"] = "ยอดชำระ/งวด";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][2]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][2]["contents"][0]["color"] = "#555555";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][2]["contents"][0]["flex"] = 0;
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][2]["contents"][1]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][2]["contents"][1]["text"] = "2,400 บาท";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][2]["contents"][1]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][2]["contents"][1]["color"] = "#111111";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][2]["contents"][1]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][3]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][3]["layout"] = "horizontal";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][3]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][3]["contents"][0]["text"] = "ค่าปรับ";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][3]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][3]["contents"][0]["color"] = "#555555";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][3]["contents"][0]["flex"] = 0;
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][3]["contents"][1]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][3]["contents"][1]["text"] = "-";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][3]["contents"][1]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][3]["contents"][1]["color"] = "#111111";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][3]["contents"][1]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][4]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][4]["layout"] = "horizontal";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][4]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][4]["contents"][0]["text"] = "ดอกเบี้ย";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][4]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][4]["contents"][0]["color"] = "#555555";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][4]["contents"][0]["flex"] = 0;
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][4]["contents"][1]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][4]["contents"][1]["text"] = "1,034 บาท";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][4]["contents"][1]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][4]["contents"][1]["color"] = "#111111";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][4]["contents"][1]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][5]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][5]["layout"] = "horizontal";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][5]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][5]["contents"][0]["text"] = "คงเหลือ";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][5]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][5]["contents"][0]["color"] = "#555555";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][5]["contents"][0]["flex"] = 0;
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][5]["contents"][1]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][5]["contents"][1]["text"] = "48,634 บาท";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][5]["contents"][1]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][5]["contents"][1]["color"] = "#111111";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][5]["contents"][1]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][6]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][6]["layout"] = "vertical";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][6]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][6]["contents"][0]["text"] = "เรียกดูใบเสร็จ";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][6]["contents"][0]["gravity"] = "center";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][6]["contents"][0]["align"] = "center";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][6]["width"] = "100%";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][6]["height"] = "40px";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][6]["borderWidth"] = "medium";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][6]["borderColor"] = "#3498DB";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][6]["justifyContent"] = "center";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][6]["action"]["type"] = "message";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][6]["action"]["label"] = "action";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][6]["action"]["text"] = "รายละเอียดใบเสร็จ";
	

	$dataTemplate["contents"]["body"]["contents"][4]["contents"][7]["type"] = "separator";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][7]["margin"] = "xxl";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["layout"] = "vertical";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["margin"] = "xxl";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["spacing"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][0]["text"] = "งวดที่ 12";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][0]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][0]["weight"] = "bold";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][1]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][1]["layout"] = "horizontal";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][1]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][1]["contents"][0]["text"] = "วันที่ชำระ";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][1]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][1]["contents"][0]["color"] = "#555555";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][1]["contents"][0]["flex"] = 0;
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][1]["contents"][1]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][1]["contents"][1]["text"] = "30/07/2564";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][1]["contents"][1]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][1]["contents"][1]["color"] = "#111111";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][1]["contents"][1]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][2]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][2]["layout"] = "horizontal";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][2]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][2]["contents"][0]["text"] = "ยอดชำระ/งวด";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][2]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][2]["contents"][0]["color"] = "#555555";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][2]["contents"][0]["flex"] = 0;
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][2]["contents"][1]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][2]["contents"][1]["text"] = "2,400 บาท";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][2]["contents"][1]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][2]["contents"][1]["color"] = "#111111";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][2]["contents"][1]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][3]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][3]["layout"] = "horizontal";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][3]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][3]["contents"][0]["text"] = "ค่าปรับ";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][3]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][3]["contents"][0]["color"] = "#555555";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][3]["contents"][0]["flex"] = 0;
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][3]["contents"][1]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][3]["contents"][1]["text"] = "-";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][3]["contents"][1]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][3]["contents"][1]["color"] = "#111111";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][3]["contents"][1]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][4]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][4]["layout"] = "horizontal";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][4]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][4]["contents"][0]["text"] = "ดอกเบี้ย";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][4]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][4]["contents"][0]["color"] = "#555555";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][4]["contents"][0]["flex"] = 0;
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][4]["contents"][1]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][4]["contents"][1]["text"] = "1,034 บาท";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][4]["contents"][1]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][4]["contents"][1]["color"] = "#111111";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][4]["contents"][1]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][5]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][5]["layout"] = "horizontal";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][5]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][5]["contents"][0]["text"] = "คงเหลือ";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][5]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][5]["contents"][0]["color"] = "#555555";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][5]["contents"][0]["flex"] = 0;
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][5]["contents"][1]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][5]["contents"][1]["text"] = "48,634 บาท";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][5]["contents"][1]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][5]["contents"][1]["color"] = "#111111";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][5]["contents"][1]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][6]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][6]["layout"] = "vertical";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][6]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][6]["contents"][0]["text"] = "เรียกดูใบเสร็จ";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][6]["contents"][0]["gravity"] = "center";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][6]["contents"][0]["align"] = "center";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][6]["width"] = "100%";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][6]["height"] = "40px";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][6]["borderWidth"] = "medium";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][6]["borderColor"] = "#3498DB";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][6]["justifyContent"] = "center";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][6]["action"]["type"] = "message";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][6]["action"]["label"] = "action";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][6]["action"]["text"] = "รายละเอียดใบเสร็จ";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][7]["type"] = "separator";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][8]["contents"][7]["margin"] = "xxl";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["layout"] = "vertical";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["margin"] = "xxl";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["spacing"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][0]["text"] = "งวดที่ 11";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][0]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][0]["weight"] = "bold";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][1]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][1]["layout"] = "horizontal";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][1]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][1]["contents"][0]["text"] = "วันที่ชำระ";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][1]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][1]["contents"][0]["color"] = "#555555";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][1]["contents"][0]["flex"] = 0;
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][1]["contents"][1]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][1]["contents"][1]["text"] = "30/07/2564";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][1]["contents"][1]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][1]["contents"][1]["color"] = "#111111";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][1]["contents"][1]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][2]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][2]["layout"] = "horizontal";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][2]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][2]["contents"][0]["text"] = "ยอดชำระ/งวด";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][2]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][2]["contents"][0]["color"] = "#555555";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][2]["contents"][0]["flex"] = 0;
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][2]["contents"][1]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][2]["contents"][1]["text"] = "2,400 บาท";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][2]["contents"][1]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][2]["contents"][1]["color"] = "#111111";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][2]["contents"][1]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][3]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][3]["layout"] = "horizontal";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][3]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][3]["contents"][0]["text"] = "ค่าปรับ";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][3]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][3]["contents"][0]["color"] = "#555555";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][3]["contents"][0]["flex"] = 0;
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][3]["contents"][1]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][3]["contents"][1]["text"] = "-";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][3]["contents"][1]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][3]["contents"][1]["color"] = "#111111";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][3]["contents"][1]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][4]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][4]["layout"] = "horizontal";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][4]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][4]["contents"][0]["text"] = "ดอกเบี้ย";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][4]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][4]["contents"][0]["color"] = "#555555";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][4]["contents"][0]["flex"] = 0;
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][4]["contents"][1]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][4]["contents"][1]["text"] = "1,034 บาท";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][4]["contents"][1]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][4]["contents"][1]["color"] = "#111111";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][4]["contents"][1]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][5]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][5]["layout"] = "horizontal";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][5]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][5]["contents"][0]["text"] = "คงเหลือ";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][5]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][5]["contents"][0]["color"] = "#555555";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][5]["contents"][0]["flex"] = 0;
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][5]["contents"][1]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][5]["contents"][1]["text"] = "48,634 บาท";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][5]["contents"][1]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][5]["contents"][1]["color"] = "#111111";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][5]["contents"][1]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][6]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][6]["layout"] = "vertical";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][6]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][6]["contents"][0]["text"] = "เรียกดูใบเสร็จ";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][6]["contents"][0]["gravity"] = "center";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][6]["contents"][0]["align"] = "center";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][6]["width"] = "100%";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][6]["height"] = "40px";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][6]["borderWidth"] = "medium";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][6]["borderColor"] = "#3498DB";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][6]["justifyContent"] = "center";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][6]["action"]["type"] = "message";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][6]["action"]["label"] = "action";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][6]["action"]["text"] = "รายละเอียดใบเสร็จ";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][7]["type"] = "separator";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][9]["contents"][7]["margin"] = "xxl";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["layout"] = "vertical";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["margin"] = "xxl";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["spacing"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][0]["text"] = "งวดที่ 10";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][0]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][0]["weight"] = "bold";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][1]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][1]["layout"] = "horizontal";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][1]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][1]["contents"][0]["text"] = "วันที่ชำระ";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][1]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][1]["contents"][0]["color"] = "#555555";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][1]["contents"][0]["flex"] = 0;
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][1]["contents"][1]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][1]["contents"][1]["text"] = "30/07/2564";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][1]["contents"][1]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][1]["contents"][1]["color"] = "#111111";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][1]["contents"][1]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][2]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][2]["layout"] = "horizontal";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][2]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][2]["contents"][0]["text"] = "ยอดชำระ/งวด";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][2]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][2]["contents"][0]["color"] = "#555555";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][2]["contents"][0]["flex"] = 0;
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][2]["contents"][1]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][2]["contents"][1]["text"] = "2,400 บาท";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][2]["contents"][1]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][2]["contents"][1]["color"] = "#111111";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][2]["contents"][1]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][3]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][3]["layout"] = "horizontal";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][3]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][3]["contents"][0]["text"] = "ค่าปรับ";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][3]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][3]["contents"][0]["color"] = "#555555";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][3]["contents"][0]["flex"] = 0;
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][3]["contents"][1]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][3]["contents"][1]["text"] = "-";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][3]["contents"][1]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][3]["contents"][1]["color"] = "#111111";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][3]["contents"][1]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][4]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][4]["layout"] = "horizontal";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][4]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][4]["contents"][0]["text"] = "ดอกเบี้ย";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][4]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][4]["contents"][0]["color"] = "#555555";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][4]["contents"][0]["flex"] = 0;
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][4]["contents"][1]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][4]["contents"][1]["text"] = "1,034 บาท";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][4]["contents"][1]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][4]["contents"][1]["color"] = "#111111";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][4]["contents"][1]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][5]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][5]["layout"] = "horizontal";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][5]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][5]["contents"][0]["text"] = "คงเหลือ";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][5]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][5]["contents"][0]["color"] = "#555555";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][5]["contents"][0]["flex"] = 0;
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][5]["contents"][1]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][5]["contents"][1]["text"] = "48,634 บาท";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][5]["contents"][1]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][5]["contents"][1]["color"] = "#111111";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][5]["contents"][1]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][6]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][6]["layout"] = "vertical";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][6]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][6]["contents"][0]["text"] = "เรียกดูใบเสร็จ";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][6]["contents"][0]["gravity"] = "center";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][6]["contents"][0]["align"] = "center";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][6]["width"] = "100%";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][6]["height"] = "40px";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][6]["borderWidth"] = "medium";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][6]["borderColor"] = "#3498DB";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][6]["justifyContent"] = "center";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][6]["action"]["type"] = "message";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][6]["action"]["label"] = "action";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][6]["action"]["text"] = "รายละเอียดใบเสร็จ";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][7]["type"] = "separator";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][10]["contents"][7]["margin"] = "xxl";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["layout"] = "vertical";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["margin"] = "xxl";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["spacing"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][0]["text"] = "งวดที่ 9";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][0]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][0]["weight"] = "bold";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][1]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][1]["layout"] = "horizontal";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][1]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][1]["contents"][0]["text"] = "วันที่ชำระ";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][1]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][1]["contents"][0]["color"] = "#555555";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][1]["contents"][0]["flex"] = 0;
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][1]["contents"][1]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][1]["contents"][1]["text"] = "30/07/2564";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][1]["contents"][1]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][1]["contents"][1]["color"] = "#111111";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][1]["contents"][1]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][2]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][2]["layout"] = "horizontal";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][2]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][2]["contents"][0]["text"] = "ยอดชำระ/งวด";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][2]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][2]["contents"][0]["color"] = "#555555";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][2]["contents"][0]["flex"] = 0;
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][2]["contents"][1]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][2]["contents"][1]["text"] = "2,400 บาท";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][2]["contents"][1]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][2]["contents"][1]["color"] = "#111111";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][2]["contents"][1]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][3]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][3]["layout"] = "horizontal";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][3]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][3]["contents"][0]["text"] = "ค่าปรับ";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][3]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][3]["contents"][0]["color"] = "#555555";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][3]["contents"][0]["flex"] = 0;
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][3]["contents"][1]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][3]["contents"][1]["text"] = "-";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][3]["contents"][1]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][3]["contents"][1]["color"] = "#111111";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][3]["contents"][1]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][4]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][4]["layout"] = "horizontal";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][4]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][4]["contents"][0]["text"] = "ดอกเบี้ย";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][4]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][4]["contents"][0]["color"] = "#555555";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][4]["contents"][0]["flex"] = 0;
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][4]["contents"][1]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][4]["contents"][1]["text"] = "1,034 บาท";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][4]["contents"][1]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][4]["contents"][1]["color"] = "#111111";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][4]["contents"][1]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][5]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][5]["layout"] = "horizontal";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][5]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][5]["contents"][0]["text"] = "คงเหลือ";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][5]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][5]["contents"][0]["color"] = "#555555";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][5]["contents"][0]["flex"] = 0;
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][5]["contents"][1]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][5]["contents"][1]["text"] = "48,634 บาท";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][5]["contents"][1]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][5]["contents"][1]["color"] = "#111111";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][5]["contents"][1]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][6]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][6]["layout"] = "vertical";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][6]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][6]["contents"][0]["text"] = "เรียกดูใบเสร็จ";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][6]["contents"][0]["gravity"] = "center";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][6]["contents"][0]["align"] = "center";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][6]["width"] = "100%";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][6]["height"] = "40px";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][6]["borderWidth"] = "medium";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][6]["borderColor"] = "#3498DB";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][6]["justifyContent"] = "center";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][6]["action"]["type"] = "message";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][6]["action"]["label"] = "action";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][6]["action"]["text"] = "รายละเอียดใบเสร็จ";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][7]["type"] = "separator";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][11]["contents"][7]["margin"] = "xxl";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["layout"] = "vertical";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["margin"] = "xxl";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["spacing"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][0]["text"] = "งวดที่ 8";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][0]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][0]["weight"] = "bold";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][1]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][1]["layout"] = "horizontal";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][1]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][1]["contents"][0]["text"] = "วันที่ชำระ";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][1]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][1]["contents"][0]["color"] = "#555555";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][1]["contents"][0]["flex"] = 0;
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][1]["contents"][1]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][1]["contents"][1]["text"] = "30/07/2564";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][1]["contents"][1]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][1]["contents"][1]["color"] = "#111111";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][1]["contents"][1]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][2]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][2]["layout"] = "horizontal";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][2]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][2]["contents"][0]["text"] = "ยอดชำระ/งวด";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][2]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][2]["contents"][0]["color"] = "#555555";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][2]["contents"][0]["flex"] = 0;
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][2]["contents"][1]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][2]["contents"][1]["text"] = "2,400 บาท";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][2]["contents"][1]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][2]["contents"][1]["color"] = "#111111";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][2]["contents"][1]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][3]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][3]["layout"] = "horizontal";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][3]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][3]["contents"][0]["text"] = "ค่าปรับ";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][3]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][3]["contents"][0]["color"] = "#555555";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][3]["contents"][0]["flex"] = 0;
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][3]["contents"][1]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][3]["contents"][1]["text"] = "-";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][3]["contents"][1]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][3]["contents"][1]["color"] = "#111111";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][3]["contents"][1]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][4]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][4]["layout"] = "horizontal";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][4]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][4]["contents"][0]["text"] = "ดอกเบี้ย";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][4]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][4]["contents"][0]["color"] = "#555555";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][4]["contents"][0]["flex"] = 0;
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][4]["contents"][1]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][4]["contents"][1]["text"] = "1,034 บาท";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][4]["contents"][1]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][4]["contents"][1]["color"] = "#111111";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][4]["contents"][1]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][5]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][5]["layout"] = "horizontal";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][5]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][5]["contents"][0]["text"] = "คงเหลือ";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][5]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][5]["contents"][0]["color"] = "#555555";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][5]["contents"][0]["flex"] = 0;
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][5]["contents"][1]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][5]["contents"][1]["text"] = "48,634 บาท";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][5]["contents"][1]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][5]["contents"][1]["color"] = "#111111";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][5]["contents"][1]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][6]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][6]["layout"] = "vertical";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][6]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][6]["contents"][0]["text"] = "เรียกดูใบเสร็จ";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][6]["contents"][0]["gravity"] = "center";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][6]["contents"][0]["align"] = "center";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][6]["width"] = "100%";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][6]["height"] = "40px";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][6]["borderWidth"] = "medium";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][6]["borderColor"] = "#3498DB";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][6]["justifyContent"] = "center";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][6]["action"]["type"] = "message";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][6]["action"]["label"] = "action";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][6]["action"]["text"] = "รายละเอียดใบเสร็จ";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][7]["type"] = "separator";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][12]["contents"][7]["margin"] = "xxl";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["layout"] = "vertical";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["margin"] = "xxl";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["spacing"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][0]["text"] = "งวดที่ 7";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][0]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][0]["weight"] = "bold";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][1]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][1]["layout"] = "horizontal";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][1]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][1]["contents"][0]["text"] = "วันที่ชำระ";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][1]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][1]["contents"][0]["color"] = "#555555";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][1]["contents"][0]["flex"] = 0;
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][1]["contents"][1]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][1]["contents"][1]["text"] = "30/07/2564";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][1]["contents"][1]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][1]["contents"][1]["color"] = "#111111";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][1]["contents"][1]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][2]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][2]["layout"] = "horizontal";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][2]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][2]["contents"][0]["text"] = "ยอดชำระ/งวด";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][2]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][2]["contents"][0]["color"] = "#555555";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][2]["contents"][0]["flex"] = 0;
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][2]["contents"][1]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][2]["contents"][1]["text"] = "2,400 บาท";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][2]["contents"][1]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][2]["contents"][1]["color"] = "#111111";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][2]["contents"][1]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][3]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][3]["layout"] = "horizontal";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][3]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][3]["contents"][0]["text"] = "ค่าปรับ";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][3]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][3]["contents"][0]["color"] = "#555555";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][3]["contents"][0]["flex"] = 0;
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][3]["contents"][1]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][3]["contents"][1]["text"] = "-";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][3]["contents"][1]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][3]["contents"][1]["color"] = "#111111";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][3]["contents"][1]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][4]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][4]["layout"] = "horizontal";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][4]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][4]["contents"][0]["text"] = "ดอกเบี้ย";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][4]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][4]["contents"][0]["color"] = "#555555";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][4]["contents"][0]["flex"] = 0;
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][4]["contents"][1]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][4]["contents"][1]["text"] = "1,034 บาท";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][4]["contents"][1]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][4]["contents"][1]["color"] = "#111111";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][4]["contents"][1]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][5]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][5]["layout"] = "horizontal";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][5]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][5]["contents"][0]["text"] = "คงเหลือ";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][5]["contents"][0]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][5]["contents"][0]["color"] = "#555555";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][5]["contents"][0]["flex"] = 0;
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][5]["contents"][1]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][5]["contents"][1]["text"] = "48,634 บาท";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][5]["contents"][1]["size"] = "sm";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][5]["contents"][1]["color"] = "#111111";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][5]["contents"][1]["align"] = "end";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][6]["type"] = "box";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][6]["layout"] = "vertical";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][6]["contents"][0]["type"] = "text";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][6]["contents"][0]["text"] = "เรียกดูใบเสร็จ";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][6]["contents"][0]["gravity"] = "center";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][6]["contents"][0]["align"] = "center";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][6]["width"] = "100%";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][6]["height"] = "40px";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][6]["borderWidth"] = "medium";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][6]["borderColor"] = "#3498DB";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][6]["justifyContent"] = "center";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][6]["action"]["type"] = "message";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][6]["action"]["label"] = "action";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][6]["action"]["text"] = "รายละเอียดใบเสร็จ";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][7]["type"] = "separator";
	$dataTemplate["contents"]["body"]["contents"][4]["contents"][13]["contents"][7]["margin"] = "xxl";
		return $dataTemplate;
	}
	//Kfinance
	public function flexLoan(){
		$dataTemplate = array();
		$dataTemplate["type"] = "flex";
		$dataTemplate["altText"] = "This is a Flex Message";
		$dataTemplate["contents"]["type"] = "carousel";
		$dataTemplate["contents"]["contents"][0]["type"] = "bubble";
		$dataTemplate["contents"]["contents"][0]["size"] = "mega";
		$dataTemplate["contents"]["contents"][0]["header"]["type"] = "box";
		$dataTemplate["contents"]["contents"][0]["header"]["layout"] = "vertical";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][0]["type"] = "box";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][0]["layout"] = "horizontal";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][0]["contents"][0]["type"] = "text";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][0]["contents"][0]["text"] = "หนี้";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][0]["contents"][0]["color"] = "#ffffff";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][0]["contents"][0]["align"] = "start";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][0]["contents"][0]["size"] = "md";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][0]["contents"][0]["gravity"] = "center";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][0]["contents"][1]["type"] = "text";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][0]["contents"][1]["text"] = "2 สัญญา";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][0]["contents"][1]["align"] = "end";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][0]["contents"][1]["size"] = "xs";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][0]["contents"][1]["color"] = "#ffffff";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][1]["type"] = "text";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][1]["text"] = "หนี้คงเหลือรวม";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][1]["align"] = "center";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][2]["type"] = "text";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][2]["text"] = "100,000.00 บาท";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][2]["color"] = "#ffffff";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][2]["align"] = "center";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][3]["type"] = "text";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][3]["text"] = "เงินกู้ฉุกเฉิน 90%";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][3]["color"] = "#ffffff";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][3]["align"] = "start";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][3]["size"] = "xs";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][3]["gravity"] = "center";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][3]["margin"] = "lg";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][4]["type"] = "box";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][4]["layout"] = "vertical";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][4]["contents"][0]["type"] = "box";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][4]["contents"][0]["layout"] = "vertical";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][4]["contents"][0]["contents"][0]["type"] = "filler";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][4]["contents"][0]["width"] = "90%";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][4]["contents"][0]["backgroundColor"] = "#0D8186";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][4]["contents"][0]["height"] = "6px";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][4]["backgroundColor"] = "#9FD8E36E";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][4]["height"] = "6px";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][4]["margin"] = "sm";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][5]["type"] = "text";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][5]["text"] = "เงินสามัญ 70%";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][5]["color"] = "#ffffff";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][5]["align"] = "start";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][5]["size"] = "xs";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][5]["gravity"] = "center";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][5]["margin"] = "lg";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][6]["type"] = "box";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][6]["layout"] = "vertical";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][6]["contents"][0]["type"] = "box";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][6]["contents"][0]["layout"] = "vertical";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][6]["contents"][0]["contents"][0]["type"] = "filler";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][6]["contents"][0]["width"] = "70%";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][6]["contents"][0]["backgroundColor"] = "#0D8186";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][6]["contents"][0]["height"] = "6px";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][6]["backgroundColor"] = "#9FD8E36E";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][6]["height"] = "6px";
		$dataTemplate["contents"]["contents"][0]["header"]["contents"][6]["margin"] = "sm";
		$dataTemplate["contents"]["contents"][0]["header"]["backgroundColor"] = "#27ACB2";
		$dataTemplate["contents"]["contents"][0]["header"]["paddingTop"] = "19px";
		$dataTemplate["contents"]["contents"][0]["header"]["paddingAll"] = "12px";
		$dataTemplate["contents"]["contents"][0]["header"]["paddingBottom"] = "16px";
		$dataTemplate["contents"]["contents"][0]["body"]["type"] = "box";
		$dataTemplate["contents"]["contents"][0]["body"]["layout"] = "vertical";
		$dataTemplate["contents"]["contents"][0]["body"]["contents"][0]["type"] = "box";
		$dataTemplate["contents"]["contents"][0]["body"]["contents"][0]["layout"] = "vertical";
		$dataTemplate["contents"]["contents"][0]["body"]["contents"][0]["contents"][0]["type"] = "text";
		$dataTemplate["contents"]["contents"][0]["body"]["contents"][0]["contents"][0]["text"] = "เงินกู้ฉุกเฉิน";
		$dataTemplate["contents"]["contents"][0]["body"]["contents"][0]["contents"][0]["color"] = "#27ACB2";
		$dataTemplate["contents"]["contents"][0]["body"]["contents"][0]["contents"][0]["size"] = "sm";
		$dataTemplate["contents"]["contents"][0]["body"]["contents"][0]["contents"][0]["wrap"] = true;
		$dataTemplate["contents"]["contents"][0]["body"]["contents"][0]["contents"][1]["type"] = "box";
		$dataTemplate["contents"]["contents"][0]["body"]["contents"][0]["contents"][1]["layout"] = "horizontal";
		$dataTemplate["contents"]["contents"][0]["body"]["contents"][0]["contents"][1]["contents"][0]["type"] = "text";
		$dataTemplate["contents"]["contents"][0]["body"]["contents"][0]["contents"][1]["contents"][0]["text"] = "142541454/2555";
		$dataTemplate["contents"]["contents"][0]["body"]["contents"][0]["contents"][1]["contents"][0]["size"] = "xs";
		$dataTemplate["contents"]["contents"][0]["body"]["contents"][0]["contents"][1]["contents"][0]["offsetStart"] = "10px";
		$dataTemplate["contents"]["contents"][0]["body"]["contents"][0]["contents"][1]["contents"][1]["type"] = "text";
		$dataTemplate["contents"]["contents"][0]["body"]["contents"][0]["contents"][1]["contents"][1]["text"] = "90,000.00 บาท";
		$dataTemplate["contents"]["contents"][0]["body"]["contents"][0]["contents"][1]["contents"][1]["size"] = "xs";
		$dataTemplate["contents"]["contents"][0]["body"]["contents"][0]["contents"][1]["contents"][1]["align"] = "end";
		$dataTemplate["contents"]["contents"][0]["body"]["contents"][0]["flex"] = 1;
		$dataTemplate["contents"]["contents"][0]["body"]["contents"][1]["type"] = "separator";
		$dataTemplate["contents"]["contents"][0]["body"]["contents"][2]["type"] = "text";
		$dataTemplate["contents"]["contents"][0]["body"]["contents"][2]["text"] = "เงินกู้สามัญ";
		$dataTemplate["contents"]["contents"][0]["body"]["contents"][2]["color"] = "#27ACB2";
		$dataTemplate["contents"]["contents"][0]["body"]["contents"][2]["size"] = "sm";
		$dataTemplate["contents"]["contents"][0]["body"]["contents"][3]["type"] = "box";
		$dataTemplate["contents"]["contents"][0]["body"]["contents"][3]["layout"] = "horizontal";
		$dataTemplate["contents"]["contents"][0]["body"]["contents"][3]["contents"][0]["type"] = "text";
		$dataTemplate["contents"]["contents"][0]["body"]["contents"][3]["contents"][0]["text"] = "ส521542454";
		$dataTemplate["contents"]["contents"][0]["body"]["contents"][3]["contents"][0]["size"] = "xs";
		$dataTemplate["contents"]["contents"][0]["body"]["contents"][3]["contents"][0]["offsetStart"] = "10px";
		$dataTemplate["contents"]["contents"][0]["body"]["contents"][3]["contents"][1]["type"] = "text";
		$dataTemplate["contents"]["contents"][0]["body"]["contents"][3]["contents"][1]["text"] = "10,000.00 บาท";
		$dataTemplate["contents"]["contents"][0]["body"]["contents"][3]["contents"][1]["size"] = "xs";
		$dataTemplate["contents"]["contents"][0]["body"]["contents"][3]["contents"][1]["align"] = "end";
		$dataTemplate["contents"]["contents"][0]["body"]["spacing"] = "md";
		$dataTemplate["contents"]["contents"][0]["body"]["paddingAll"] = "12px";
		return $dataTemplate;
	}
	
	//Kfinance
	public function meberInfor(){
		$dataTemplate = array();
	
		$dataTemplate["type"] = "flex";
		$dataTemplate["altText"] = "This is a Flex Message";
		$dataTemplate["contents"]["type"] = "bubble";
		$dataTemplate["contents"]["size"] = "mega";
		$dataTemplate["contents"]["direction"] = "ltr";
		$dataTemplate["contents"]["hero"]["type"] = "image";
		$dataTemplate["contents"]["hero"]["url"] = "https://cdn.thaicoop.co/isocare/logo.jpg";
		$dataTemplate["contents"]["hero"]["align"] = "center";
		$dataTemplate["contents"]["hero"]["gravity"] = "center";
		$dataTemplate["contents"]["hero"]["size"] = "full";
		$dataTemplate["contents"]["hero"]["aspectRatio"] = "1.51:1";
		$dataTemplate["contents"]["hero"]["aspectMode"] = "cover";
		$dataTemplate["contents"]["body"]["type"] = "box";
		$dataTemplate["contents"]["body"]["layout"] = "vertical";
		$dataTemplate["contents"]["body"]["spacing"] = "xs";
		$dataTemplate["contents"]["body"]["contents"][0]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][0]["text"] = "อโซแคร์ ซิสเต็มส์ ";
		$dataTemplate["contents"]["body"]["contents"][0]["weight"] = "bold";
		$dataTemplate["contents"]["body"]["contents"][0]["size"] = "lg";
		$dataTemplate["contents"]["body"]["contents"][0]["align"] = "center";
		$dataTemplate["contents"]["body"]["contents"][1]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][1]["text"] = "ประเภ่ท";
		$dataTemplate["contents"]["body"]["contents"][1]["align"] = "center";
		$dataTemplate["contents"]["body"]["contents"][1]["contents"][0]["type"] = "span";
		$dataTemplate["contents"]["body"]["contents"][1]["contents"][0]["text"] = "00000078";
		$dataTemplate["contents"]["body"]["contents"][1]["contents"][1]["type"] = "span";
		$dataTemplate["contents"]["body"]["contents"][1]["contents"][1]["text"] = "(สมาชิกปกติ)";
		$dataTemplate["contents"]["body"]["contents"][2]["type"] = "separator";
		$dataTemplate["contents"]["body"]["contents"][3]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][3]["text"] = "ข้อมูลทั่วไป";
		$dataTemplate["contents"]["body"]["contents"][3]["weight"] = "bold";
		$dataTemplate["contents"]["body"]["contents"][3]["color"] = "#121212FF";
		$dataTemplate["contents"]["body"]["contents"][4]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][4]["text"] = "หมายเลขบัตรประจำตัวประชาชน";
		$dataTemplate["contents"]["body"]["contents"][5]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][5]["text"] = "1-5009-00200-30-0";
		$dataTemplate["contents"]["body"]["contents"][5]["size"] = "md";
		$dataTemplate["contents"]["body"]["contents"][5]["color"] = "#0EA7CAFF";
		$dataTemplate["contents"]["body"]["contents"][5]["align"] = "end";
		$dataTemplate["contents"]["body"]["contents"][5]["offsetEnd"] = "0px";
		$dataTemplate["contents"]["body"]["contents"][6]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][6]["text"] = "วันเกิด";
		$dataTemplate["contents"]["body"]["contents"][7]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][7]["text"] = "22 ก.ค. 2536 (27 ปี 7 เดือน)";
		$dataTemplate["contents"]["body"]["contents"][7]["color"] = "#0EA7CAFF";
		$dataTemplate["contents"]["body"]["contents"][7]["align"] = "end";
		$dataTemplate["contents"]["body"]["contents"][8]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][8]["text"] = " วันที่เป็นสมาชิก";
		$dataTemplate["contents"]["body"]["contents"][9]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][9]["text"] = "26 มี.ค. 2557 (6 ปี 11 เดือน)";
		$dataTemplate["contents"]["body"]["contents"][9]["color"] = "#0EA7CAFF";
		$dataTemplate["contents"]["body"]["contents"][9]["align"] = "end";
		$dataTemplate["contents"]["body"]["contents"][9]["wrap"] = true;
		$dataTemplate["contents"]["body"]["contents"][10]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][10]["text"] = "ตำแหน่ง";
		$dataTemplate["contents"]["body"]["contents"][11]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][11]["text"] = "ผู้ช่วยพยาบาล";
		$dataTemplate["contents"]["body"]["contents"][11]["color"] = "#0EA7CAFF";
		$dataTemplate["contents"]["body"]["contents"][11]["align"] = "end";
		$dataTemplate["contents"]["body"]["contents"][12]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][12]["text"] = "สังกัด";
		$dataTemplate["contents"]["body"]["contents"][13]["type"] = "box";
		$dataTemplate["contents"]["body"]["contents"][13]["layout"] = "vertical";
		$dataTemplate["contents"]["body"]["contents"][13]["paddingStart"] = "20px";
		$dataTemplate["contents"]["body"]["contents"][13]["contents"][0]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][13]["contents"][0]["text"] = "พนักงานมหาวิทยาลัย(ส่วนงาน)รามา";
		$dataTemplate["contents"]["body"]["contents"][13]["contents"][0]["color"] = "#0EA7CAFF";
		$dataTemplate["contents"]["body"]["contents"][13]["contents"][0]["align"] = "start";
		$dataTemplate["contents"]["body"]["contents"][13]["contents"][0]["wrap"] = true;
		$dataTemplate["contents"]["body"]["contents"][14]["type"] = "separator";
		$dataTemplate["contents"]["body"]["contents"][15]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][15]["text"] = "ข้อมูลติดต่อ";
		$dataTemplate["contents"]["body"]["contents"][15]["weight"] = "bold";
		$dataTemplate["contents"]["body"]["contents"][15]["color"] = "#121212FF";
		$dataTemplate["contents"]["body"]["contents"][16]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][16]["text"] = "หมายเลขโทรศัพท์";
		$dataTemplate["contents"]["body"]["contents"][17]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][17]["text"] = "063-789-1888";
		$dataTemplate["contents"]["body"]["contents"][17]["color"] = "#0EA7CAFF";
		$dataTemplate["contents"]["body"]["contents"][17]["align"] = "end";
		$dataTemplate["contents"]["body"]["contents"][18]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][18]["text"] = "อีเมล";
		$dataTemplate["contents"]["body"]["contents"][19]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][19]["text"] = "isocare@iscocare.com";
		$dataTemplate["contents"]["body"]["contents"][19]["color"] = "#0EA7CAFF";
		$dataTemplate["contents"]["body"]["contents"][19]["align"] = "end";
		$dataTemplate["contents"]["body"]["contents"][20]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][20]["text"] = "ที่อยู่ปัจจุบัน";
		$dataTemplate["contents"]["body"]["contents"][21]["type"] = "box";
		$dataTemplate["contents"]["body"]["contents"][21]["layout"] = "vertical";
		$dataTemplate["contents"]["body"]["contents"][21]["paddingStart"] = "20px";
		$dataTemplate["contents"]["body"]["contents"][21]["contents"][0]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][21]["contents"][0]["text"] = "270 หอพักพยาบาล 2 ห้อง912 ถนนพระราม 6 แขวงทุ่งพญาไท เขตราชเทวี กรุงเทพมหานคร 10400";
		$dataTemplate["contents"]["body"]["contents"][21]["contents"][0]["color"] = "#0EA7CAFF";
		$dataTemplate["contents"]["body"]["contents"][21]["contents"][0]["wrap"] = true;
		$dataTemplate["contents"]["body"]["contents"][22]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][22]["text"] = "ที่อยู่ในทะเบียนบ้าน";
		$dataTemplate["contents"]["body"]["contents"][23]["type"] = "box";
		$dataTemplate["contents"]["body"]["contents"][23]["layout"] = "vertical";
		$dataTemplate["contents"]["body"]["contents"][23]["paddingStart"] = "20px";
		$dataTemplate["contents"]["body"]["contents"][23]["contents"][0]["type"] = "text";
		$dataTemplate["contents"]["body"]["contents"][23]["contents"][0]["text"] = "270 หอพักพยาบาล 2 ห้อง912 ถนนพระราม 6 แขวงทุ่งพญาไท เขตราชเทวี กรุงเทพมหานคร 10400";
		$dataTemplate["contents"]["body"]["contents"][23]["contents"][0]["color"] = "#0EA7CAFF";
		$dataTemplate["contents"]["body"]["contents"][23]["contents"][0]["wrap"] = true;

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
	
	
	
	
}
?>