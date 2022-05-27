<?php
namespace Line;

use Connection\connection;

class libraryLine {
	private $con;
	private $conmssql;
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
		$getData = $this->con->prepare("SELECT constant_value FROM lbconstant WHERE constant_name = :theme_color");
		$getData->execute([':theme_color' => $constant]);
		if($getData->rowCount() > 0){
			$rowLimit = $getData->fetch(\PDO::FETCH_ASSOC);
			return $rowLimit["constant_value"];
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
		curl_setopt($curl, CURLOPT_PROXY, '10.20.220.63');
		curl_setopt($curl, CURLOPT_PROXYPORT, '8080');

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
		curl_setopt($curl, CURLOPT_PROXY, '10.20.220.63');
		curl_setopt($curl, CURLOPT_PROXYPORT, '8080');
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
}
?>