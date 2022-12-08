<?php
namespace Line;

use Connection\connection;

class libraryLine {
	private $con;
	private $conmssql;
	function __construct() {
		$connection = new connection();
		$this->con = $connection->connecttomysql();
		$this->conmssql = $connection->connecttosqlserver();
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
	public function getMemberNo($line_token) {
		$getLimit = $this->con->prepare("SELECT member_no FROM gcmemberaccount WHERE line_token = :line_token");
		$getLimit->execute([':line_token' => $line_token]);
		if($getLimit->rowCount() > 0){
			$rowLimit = $getLimit->fetch(\PDO::FETCH_ASSOC);
			return $rowLimit["member_no"];
		}else{
			return false;
		}
	}
	public function getLineConstant($constant) {
		$getData = $this->con->prepare("SELECT constant_value FROM lbconstant WHERE constant_name = :constant");
		
		$getData->execute([':constant' => $constant]);
		if($getData->rowCount() > 0){
			$rowLimit = $getData->fetch(\PDO::FETCH_ASSOC);
			return $rowLimit["constant_value"];
		}else{
			return false;
		}
	}
	public function getLineIdNotify($member_no=null) {
		$getData = $this->con->prepare("SELECT line_token FROM lbnotify WHERE member_no = :member_no AND is_notify = '1'");
		
		$getData->execute([':member_no' => $member_no]);
		if($getData->rowCount() > 0){
			$rowLimit = $getData->fetch(\PDO::FETCH_ASSOC);
			return $rowLimit["line_token"];
		}else{
			return false;
		}
	}
	
	public function checkNotify($member_no=null,$detail=null,$ref=null) {
		$chkNotify = $this->con->prepare("SELECT * FROM lbhistory WHERE his_detail = :detail AND member_no = :member_no AND ref = :ref");
		$chkNotify->execute([
			":detail" => $detail,
			":member_no" => $member_no,
			":ref" => $ref
		]);
		$rowChkNotify = $chkNotify->fetch(\PDO::FETCH_ASSOC);
		if(sizeof($rowChkNotify) == 0 || empty($rowChkNotify)){
			return 1;
		}else{
			return 0;
		}
	}
	
	public function prepareMessageText($message){
		$arrResponse[0]["type"] = "text";
		$arrResponse[0]["label"] = $message;
		$arrResponse[0]["text"] = $message;
		//$arrResponse[0]["sender"]["name"] = 'Bot';
		//$arrResponse[0]["sender"]["iconUrl"] = 'https://line.me/conyprof';
		return $arrResponse;
	}
	public function prepareFlexMessage($altText,$body){
		$arrResponse[0]["type"] = "flex";
		$arrResponse[0]["altText"] = $altText;
		$arrResponse[0]["contents"] = $body;
		return $arrResponse;
	}
	public function sendLineBot($message){
		$json = file_get_contents(__DIR__.'/../config/config_linebot.json');
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
	
	public function notBindAccount(){
		$dataContent["type"] = "bubble";
		$dataContent["body"]["type"] = "box";
		$dataContent["body"]["layout"] = "vertical";
		$dataContent["body"]["contents"][0]["type"] = "text";
		$dataContent["body"]["contents"][0]["text"] = "ท่านยังไม่ได้ผูกบัญชี";
		$dataContent["body"]["contents"][0]["color"] = "#0EA7CA";
		$dataContent["body"]["contents"][1]["type"] = "text";
		$dataContent["body"]["contents"][1]["text"] = " กรุณาผูกบัญชีเพื่อดูข้อมูล";
		$dataContent["body"]["contents"][1]["size"] = "sm";
		$dataContent["body"]["contents"][1]["wrap"] = true;
		$dataContent["body"]["contents"][1]["offsetStart"] = "40px";
		$dataContent["body"]["contents"][2]["type"] = "button";
		$dataContent["body"]["contents"][2]["action"]["type"] = "message";
		$dataContent["body"]["contents"][2]["action"]["label"] = "ผูกบัญชี";
		$dataContent["body"]["contents"][2]["action"]["text"] = "ผูกบัญชี";
		$dataContent["body"]["contents"][2]["height"] = "sm";
		$dataContent["body"]["contents"][2]["style"] = "primary";
		$dataContent["body"]["contents"][2]["margin"] = "xl";
		$dataContent["body"]["contents"][2]["color"] = "#E3519D";
		return $dataContent;
	}

	public function mergeTextMessage($id){
		$dataTemplate = array();
		$dataTemplate["type"] = "text";
		$dataTemplate["text"] = $message;
		return $dataTemplate;
	}
	public function checkLineProfile($id){
		$json = file_get_contents(__DIR__.'/../config/config_linebot.json');
		$json_data = json_decode($json,true);
		$dataReturn = [];
		$message = [];
		$url = 'https://api.line.me/v2/bot/profile/'.$id;
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_POST, false);
		curl_setopt($curl, CURLOPT_HTTPHEADER, [
			"authorization: Bearer ".$json_data["LINE_AUTH_KEY"],
			"cache-control: no-cache",
			"content-type: application/json; charset=UTF-8",
		]);
		curl_setopt($curl, CURLOPT_POSTFIELDS, '');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,false);
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
		$json = file_get_contents(__DIR__.'/../config/config_linebot.json');
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
	
	public function sendLineBot2($message){
		$json = file_get_contents(__DIR__.'/../config/config_constructor.json');
		$json_data = json_decode($json,true);
		$dataReturn = [];
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $json_data["LINEBOT_URL"]);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, [
			"authorization: Bearer ".$json_data["LINE_AUTH_KEY2"],
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
	}*/
}
?>