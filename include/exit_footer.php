<?php

if($arrayResult["RESULT"] === FALSE){
	$arrayResult["HEADER_CUSTOM"] = $configError["ERROR_HEADER_CUSTOM"][0][$lang_locale];
}

$response = json_encode($arrayResult, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
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