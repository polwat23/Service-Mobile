<?php
require_once(__DIR__.'/../extension/vendor/autoload.php');


use Twilio\Rest\Client;
/*
// Find your Account Sid and Auth Token at twilio.com/console
// DANGER! This is insecure. See http://twil.io/secure
$sid    = "ACa0cf4bb1107e054a0bfc52f3551e0442";
$token  = "730c463d6879b8ddbf3fc259a9eb19ba";
$twilio = new Client($sid, $token);

$incoming_phone_number = $twilio->incomingPhoneNumbers("+66820161367")
                                ->fetch();

print($incoming_phone_number);*/
$url = "https://api.twilio.com/2010-04-01/Accounts/AC5ebcfef9120d6ba8c834534ba9e05626/IncomingPhoneNumbers.json";
$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8', 'Accept: application/json'));
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt( $ch, CURLOPT_TIMEOUT, 180);
		$result = curl_exec($ch);
		if($result){
			curl_close($ch);
			print_r($result);
		}else{
			$text = '#PostData Error : '.date("Y-m-d H:i:s").' > '.$url.' | '.curl_error($ch);
			file_put_contents(__DIR__.'/../log/log_api_error.txt', $text . PHP_EOL, FILE_APPEND);
			curl_close ($ch);
			return false;
		}
?>