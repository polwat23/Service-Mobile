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
					$contract_text = mb_substr($contract_no,$i,strlen($formatArray[$i]));
				}else{
					$contract_text .= '/'.mb_substr($contract_no,strlen(preg_replace('/-/','',$contract_text)),strlen($formatArray[$i]));
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
	public function base64_to_img($encode_string,$file_name,$output_file) {
		$data_Img = explode(',',$encode_string);
		$dataImg = base64_decode($data_Img[1]);
		$info_img = explode('/',$data_Img[0]);
		$ext_img = str_replace('base64','',$info_img[1]);
		$im_string = imageCreateFromString($dataImg);
		if (!$im_string) {
			return false;
		}else{
			$filename = $file_name.'.'.$ext_img;
			$destination = $output_file.'/'.$filename;
			if($ext_img == 'png'){
				imagepng($im_string, $destination, 2);
				return $filename;
			}else if($ext_img == 'jpg' || $ext_img == 'jpeg'){
				imagejpeg($im_string, $destination, 70);
				return $filename;
			}else{
				return false;
			}
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
		define( 'API_ACCESS_KEY', $json_data["FIREBASE_SECRET_KEY"] );
		if($type_send == 'someone'){
			$data = [
				"registration_ids" => $payload["TO"],
				"priority" => $payload["PRIORITY"],
				"notification" => [
					"title" => $payload["PAYLOAD"]["TITLE"],
					"body" => $payload["PAYLOAD"]["BODY"],
					"sound" => $payload["PAYLOAD"]["SOUND"],
					"icon" => $json_data["ICON"]
				]
			];
		}else if($type_send == 'all'){
			$data = [
				"to" => $payload["TO"],
				"priority" => $payload["PRIORITY"],
				"notification" => [
					"title" => $payload["PAYLOAD"]["TITLE"],
					"body" => $payload["PAYLOAD"]["BODY"],
					"sound" => $payload["PAYLOAD"]["SOUND"],
					"icon" => $json_data["ICON"]
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
		
		if($result){
			$resultNoti = json_decode($result);
			curl_close ($ch);
			if($resultNoti->success){
				return true;
			}else{
				$text = '#Notify Error : '.date("Y-m-d H:i:s").' > '.json_encode($payload["TO"]).' | '.json_encode($resultNoti->results);
				file_put_contents(__DIR__.'/../log/log_error.txt', $text . PHP_EOL, FILE_APPEND);
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
}
?>