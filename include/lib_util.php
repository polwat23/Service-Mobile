<?php

namespace Utility;

class library {
	
	public function generate_token(){
		$data = openssl_random_pseudo_bytes( 16 );
		$data[6] = chr( ord( $data[6] ) & 0x0f | 0x40 );
		$data[8] = chr( ord( $data[8] ) & 0x3f | 0x80 );
		return vsprintf( '%s%s-%s-%s-%s-%s%s%s', str_split( bin2hex( $data ), 4 ) );
	}
	public function convertdate($date,$format="D m Y",$is_time=false){
		$date = preg_replace('|/|','-',$date);
		$thaimonth = ["","มกราคม","กุมภาพันธ์","มีนาคม","เมษายน","พฤษภาคม","มิถุนายน","กรกฏาคม","สิงหาคม","กันยายน","ตุลาคม","พฤศจิกายน","ธันวาคม"];
		$thaishort = ["","ม.ค.","ก.พ.","มี.ค.","เม.ย.","พ.ค.","มิ.ย.","ก.ค.","ส.ค.","ก.ย.","ต.ค.","พ.ย.","ธ.ค."];
		$arrSeparate = [" ","-","/"];
		if($is_time){
			$dateConvert = date("Y-m-d-H-i-s",strtotime($date));
		}else{
			$dateConvert = date("Y-m-d",strtotime($date));
		}
		$separate;
		foreach($arrSeparate as $sep_value) {
			if(strpos($format, $sep_value)){
				$separate = $sep_value;
				break;
			}
		}
		$datearray = explode('-',$dateConvert);
		$formatArray = explode($separate,$format);
		$dateConverted;
		foreach($formatArray as $key_format => $value_format) {
			if($key_format == 0){
				switch($value_format){
					case "D" :
					case "d" : $dateConverted = $datearray[2];
						break;
					case "Y" : $dateConverted = ($datearray[0]+543);
						break;
					case "y" : $dateConverted = $datearray[0];
						break;				
				}
			}else{
				switch($value_format){
					case "D" :
					case "d" : $dateConverted .= $separate.$datearray[2];
						break;
					case "M" : $dateConverted .= $separate.$thaimonth[$datearray[1]*1];
						break;
					case "m" : $dateConverted .= $separate.$thaishort[$datearray[1]*1];
						break;
					case "N" :
					case "n" : $dateConverted .= $separate.$datearray[1];
						break;
					case "Y" : $dateConverted .= $separate.($datearray[0]+543);
						break;
					case "y" : $dateConverted .= $separate.($datearray[0]);
						break;
				}
			}
		}
		if($is_time){
			$dateConverted .= ' เวลา '.$datearray[3].':'.$datearray[4].(isset($datearray[5]) && $datearray[5] > 0 ? ':'.$datearray[5] : null).' น.';
		}
		return $dateConverted;
	}
	public function count_duration($date,$format="ym"){
		$date = preg_replace('|/|','-',$date);
		$dateconverted = new \DateTime($date);
		$dateNow = new \DateTime(date('d-m-Y'));
		$date_duration = $dateNow->diff($dateconverted);
		if($format == "ym"){
			return  $date_duration->y ." ปี " .$date_duration->m." เดือน";
		}else if($format == "m"){
			return (($date_duration->y)*12)+($date_duration->m);			
		}else if($format == "d"){
			return $date_duration->days;			
		}     
	}
	public function formatcitizen($idcard,$separate=" "){
		if(isset($idcard)){
			$str1 = substr($idcard,0,1);
			$str2 = substr($idcard,1,4);
			$str3 = substr($idcard,5,5);
			$str4 = substr($idcard,10,2);
			$str5 = substr($idcard,12,1);
			return $str1.$separate.$str2.$separate.$str3.$separate.$str4.$separate.$str5;
		}else{
			return '-';
		}
	}
	public function formatphone($phone,$separate=" "){
		if(isset($phone)){
			$str1 = substr($phone,0,3);
			$str2 = substr($phone,3,3);
			$str3 = substr($phone,6,4);
			return $str1.$separate.$str2.$separate.$str3;
		}else{
			return '-';
		}
	}
	public function formataccount($account_no,$format) {
		if(isset($account_no) && isset($format)){
			$formatArray = explode('-',$format);
			$account_text = '';
			for($i = 0;$i < sizeof($formatArray);$i++){
				if($i == 0){
					$account_text = substr($account_no,$i,strlen($formatArray[$i]));
				}else{
					$account_text .= '-'.substr($account_no,strlen(preg_replace('/-/','',$account_text)),strlen($formatArray[$i]));
				}
			}
			return $account_text;
		}else{
			return '-';
		}
	}
	public function formataccount_hidden($account_no,$format) {
		if(isset($account_no) && isset($format)){
			$account_text = '';
			if(strlen($account_no) === 10){
				$account_no = $this->formataccount($account_no,$format);
			}
			for($i = 0; $i < strlen($account_no);$i++){
				if($format[$i] == 'h'){
					$account_text .= 'x';
				}else{
					$account_text .= $account_no[$i];
				}
			}
			return $account_text;
		}else{
			return '-';
		}
	}
	public function formatcontract($contract_no,$format) {
		if(isset($contract_no) && isset($format)){
			$formatArray = explode('/',$format);
			$contract_text = '';
			for($i = 0;$i < sizeof($formatArray);$i++){
				if($i == 0){
					$contract_text = mb_substr($contract_no,$i,mb_strlen($formatArray[$i]));
				}else{
					$contract_text .= '/'.mb_substr($contract_no,mb_strlen(preg_replace('/-/','',$contract_text)));
				}
			}
			return $contract_text;
		}else{
			return '-';
		}
	}
	public function convertperiodkp($period){
		if(isset($period)){
			$thaimonth = ["","มกราคม","กุมภาพันธ์","มีนาคม","เมษายน","พฤษภาคม","มิถุนายน","กรกฏาคม","สิงหาคม","กันยายน","ตุลาคม","พฤศจิกายน","ธันวาคม"];
			$year = substr($period,0,4);
			$monthOne = str_replace('0','',substr($period,4,1));
			$monthTwo= substr($period,5);
			$month = $monthOne.$monthTwo;
			return $thaimonth[$month].' '.$year;
		}else{ 
			return ""; 
		}
	}
	public function mergeTemplate($template_subject,$template_body,$data=[]) {
		$arrayText = array();
		preg_match_all('/\\${(.*?)\\}/',$template_subject,$arrayColSubject);
		preg_match_all('/\\${(.*?)\\}/',$template_body,$arrayColBody);
		foreach($arrayColSubject[1] as $key => $column){
			if(isset($data[strtoupper($column)])){
				$template_subject = preg_replace('/\\'.$arrayColSubject[0][$key].'/',$data[strtoupper($column)],$template_subject);
			}
		}
		foreach($arrayColBody[1] as $key => $column){
			if(isset($data[strtoupper($column)])){
				$template_body = preg_replace('/\\'.$arrayColBody[0][$key].'/',$data[strtoupper($column)],$template_body);
			}
		}
		$arrayText["SUBJECT"] = $template_subject;
		$arrayText["BODY"] = $template_body;
		return $arrayText;
	}
	public function sendMail($email,$subject,$body,$mailFunction) {
		$json = file_get_contents(__DIR__.'/../json/config_constructor.json');
		$json_data = json_decode($json,true);
		$mailFunction->SMTPDebug = 0;
		$mailFunction->isSMTP();
		$mailFunction->SMTPOptions = [
			'ssl' => [
				'verify_peer' => false,
				'verify_peer_name' => false,
				'allow_self_signed' => true
			]
		];
		$mailFunction->Host = 'mail.isocare.co.th';
		$mailFunction->SMTPAuth = true;
		$mailFunction->Username = 'no-reply@isocare.co.th';
		$mailFunction->Password = '@Iso1888';
		$mailFunction->SMTPSecure = 'tls';
		$mailFunction->Port = 587;
		$mailFunction->CharSet = 'UTF-8';
		$mailFunction->setFrom('no-reply@isocare.co.th', $json_data["NAME_APP"]);
		$mailFunction->addAddress($email);
		$mailFunction->isHTML(true);
		$mailFunction->Subject = $subject;
		$mailFunction->Body = $body;
		if(!$mailFunction->send()){
			$text = '#Mail Error : '.date("Y-m-d H:i:s").' > Send to : '.$email.' # '.$mailFunction->ErrorInfo;
			file_put_contents(__DIR__.'/../log/log_error.txt', $text . PHP_EOL, FILE_APPEND);
			return false;
		}else{
			return true;
		}
	}
	public function base64_to_img($encode_string,$file_name,$output_file,$webP=null) {
		if(self::getBase64ImageSize($encode_string) < 1500){
			$data_Img = explode(',',$encode_string);
			$dataImg = base64_decode($data_Img[1]);
			$info_img = explode('/',$data_Img[0]);
			$ext_img = str_replace('base64','',$info_img[1]);
			$im_string = imageCreateFromString($dataImg);
			if (!$im_string) {
				return false;
			}else{
				if(isset($webP)){
					$filename = $file_name.'.'.$ext_img;
					$destination = $output_file.'/'.$filename;
					$webP_destination = $output_file.'/'.$file_name.'.webp';
					if($ext_img == 'png'){
						imagepng($im_string, $destination, 2);
						$webP->convert($destination,$webP_destination,[]);
						$arrPath = array();
						$arrPath["normal_path"] = $filename;
						$arrPath["webP_path"] = $file_name.'.webp';
						return $arrPath;
					}else if($ext_img == 'jpg' || $ext_img == 'jpeg'){
						imagejpeg($im_string, $destination, 70);
						$webP->convert($destination,$webP_destination,[]);
						$arrPath = array();
						$arrPath["normal_path"] = $filename;
						$arrPath["webP_path"] = $file_name.'.webp';
						return $arrPath;
					}else{
						return false;
					}
				}else{
					$filename = $file_name.'.'.$ext_img;
					$destination = $output_file.'/'.$filename;
					if($ext_img == 'png'){
						imagepng($im_string, $destination, 2);
						$arrPath = array();
						$arrPath["normal_path"] = $filename;
						return $arrPath;
					}else if($ext_img == 'jpg' || $ext_img == 'jpeg'){
						imagejpeg($im_string, $destination, 70);
						$arrPath = array();
						$arrPath["normal_path"] = $filename;
						return $arrPath;
					}else{
						return false;
					}
				}
			}
		}else{
			return 'oversize';
		}
	}
	private function getBase64ImageSize($base64Image){
		try{
			$size_in_bytes = (int) (strlen(rtrim($base64Image, '=')) * 3 / 4);
			$size_in_kb    = $size_in_bytes / 1024;
			
			return $size_in_kb;
		}
		catch(Exception $e){
			return $e;
		}
	}
	public function text_limit($text, $limit = 50, $end = '...'){
		if (mb_strwidth($text, 'UTF-8') <= $limit) {
			return $text;
		}
		return rtrim(mb_strimwidth($text, 0, $limit, '', 'UTF-8')).$end;
	}
	public function randomText($type='number',$length=4){
		if($type == 'number'){
			$characters = '0123456789';
		}else if($type == 'string'){
			$characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
		}else{
			$characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		}
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}
	public function sendNotify($payload,$type_send){
		$json = file_get_contents(__DIR__.'/../json/config_constructor.json');
		$json_data = json_decode($json,true);
		if (!defined('API_ACCESS_KEY')) define( 'API_ACCESS_KEY', $json_data["FIREBASE_SECRET_KEY"] );
		if($type_send == 'person'){
			$data = [
				"registration_ids" => $payload["TO"],
				"priority" => "high",
				"notification" => [
					"title" => $payload["PAYLOAD"]["SUBJECT"],
					"body" => $payload["PAYLOAD"]["BODY"],
					"icon" => $json_data["ICON"],
					"sound" => "default",
					"image" => $payload["PAYLOAD"]["PATH_IMAGE"] ?? null
				]
			];
		}else if($type_send == 'all'){
			$data = [
				"to" => $payload["TO"],
				"priority" => "high",
				"notification" => [
					"title" => $payload["PAYLOAD"]["SUBJECT"],
					"body" => $payload["PAYLOAD"]["BODY"],
					"icon" => $json_data["ICON"],
					"sound" => "default",
					"image" => $payload["PAYLOAD"]["PATH_IMAGE"] ?? null
				]
			];
		}
		$headers = [
			 'Authorization: key=' . API_ACCESS_KEY, 
			 'Content-Type: application/json'
		];      
		$ch = curl_init();  

		curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );                                                                  
		curl_setopt( $ch,CURLOPT_POST, true );  
		curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode($data));                                                                  
																												 
		$result = curl_exec($ch);
		
		if(isset($result)){
			$resultNoti = json_decode($result);
			curl_close ($ch);
			if(isset($resultNoti)){
				if($resultNoti->success){
					return true;
				}else{
					$text = '#Notify Error : '.date("Y-m-d H:i:s").' > '.json_encode($payload["TO"]).' | '.json_encode($resultNoti);
					file_put_contents(__DIR__.'/../log/log_error.txt', $text . PHP_EOL, FILE_APPEND);
					return false;
				}
			}else{
				return false;
			}
		}else{
			$text = '#Notify Error : '.date("Y-m-d H:i:s").' > '.json_encode($payload["TO"]).' | '.curl_error($ch);
			file_put_contents(__DIR__.'/../log/log_error.txt', $text . PHP_EOL, FILE_APPEND);
			curl_close ($ch);
			return false;
		}
	}
	public function fetch_payloadJWT($token,$jwt_function,$secret_key){
		return $jwt_function->getPayload($token, $secret_key);
	}
	public function checkCompleteArgument($dataincome,$dataComing) {
		foreach($dataincome as $data){
			if(isset($dataComing[$data]) && ($dataComing[$data] == '0' || !empty($dataComing[$data]))){
				continue;
			}else{
				return false;
			}
		}
		return true;
	}
	public function addLogtoTxt($dataLog,$pathfile){
		file_put_contents(__DIR__.'/../log/'.$pathfile.'.txt', json_encode($dataLog) . PHP_EOL, FILE_APPEND);
	}
	public function getClientIP() {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if(isset($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = '';
        return $ipaddress;
    }
	
	public function getDeviceName() { 
        $userAgent = strtolower($_SERVER['HTTP_USER_AGENT']); 
        if(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== FALSE){
			$name = 'Internet explorer';
		}else if(strpos($_SERVER['HTTP_USER_AGENT'], 'Trident') !== FALSE){
			$name =  'Internet explorer';
		}else if(strpos($_SERVER['HTTP_USER_AGENT'], 'Firefox') !== FALSE){
			$name =  'Mozilla Firefox';
		}else if(strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== FALSE){
			$name =  'Google Chrome';
		}else if(strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mini') !== FALSE){
			$name =  "Opera Mini";
		}else if(strpos($_SERVER['HTTP_USER_AGENT'], 'Opera') !== FALSE){
			$name =  "Opera";
		}else if(strpos($_SERVER['HTTP_USER_AGENT'], 'Safari') !== FALSE){
		   $name =  "Safari";
		}else{
		   $name =  'Something else';
		}
        if (preg_match('/.+(?:rv|it|ra|ie)[\/: ]([\d.]+)/', $userAgent, $matches)) { 
            $version = $matches[1]; 
        }else { 
            $version = 'unknown'; 
        }
        if (preg_match('/linux/', $userAgent)) { 
            $os_platform = 'linux'; 
        }elseif (preg_match('/macintosh|mac os x/', $userAgent)) { 
            $os_platform = 'mac'; 
        }elseif (preg_match('/windows|win32/', $userAgent)) { 
            $os_platform = 'windows'; 
        }else { 
            $os_platform = 'unrecognized'; 
        }
		$device_name = $name.' V.'.$version.' ('.$os_platform.')';
        return $device_name;
    }
	public function getnumberofYear($year){
		$days=0; 
		for($month=1;$month<=12;$month++){ 
			$days = $days + cal_days_in_month(CAL_GREGORIAN,$month,$year);
		}
		return $days;
	}
	public function posting_data($url,$payload) {
		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode($payload) );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8', 'Accept: application/json'));
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt( $ch, CURLOPT_TIMEOUT, 180);
		$result = curl_exec($ch);
		if($result){
			curl_close($ch);
			return $result;
		}else{
			$text = '#PostData Error : '.date("Y-m-d H:i:s").' > '.$url.' | '.curl_error($ch);
			file_put_contents(__DIR__.'/../log/log_api_error.txt', $text . PHP_EOL, FILE_APPEND);
			curl_close ($ch);
			return false;
		}
	}
}
?>