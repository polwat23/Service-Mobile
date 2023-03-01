<?php
//$arrayResult=convertArray($arrayResult,false);

$response = json_encode($arrayResult, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);

//file_put_contents($_SERVER['SCRIPT_NAME']."_in.log", json_encode($dataComing));
//file_put_contents($_SERVER['SCRIPT_NAME']."_out.log", $response );
//file_put_contents($_SERVER['SCRIPT_NAME']."_result.log", print_r($arrayResult,true) );


if ($forceNewSecurity == true) {
	$signature = "";
	openssl_sign($response, $signature, $gensoftSCPrivatekey, OPENSSL_ALGO_SHA512);
	header("Response_token: ".base64_encode($signature));
}


ob_flush();
echo $response;
ob_end_clean();
exit();
?>