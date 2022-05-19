<?php
ini_set('allow_url_fopen', '1');
$gensoftCSPublickey = openssl_pkey_get_public(file_get_contents('../config/cert/gensoft-client-to-server_pubkey.pem'));

print_r($gensoftCSPublickey);

?>