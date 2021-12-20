<?php
$arrPostData = array();
$arrPostData['replyToken'] = $reply_token;

if($message == "ผูกบัญชี"){
	require_once('./service/bindaccount.php');
}

require_once(__DIR__.'./replyresponse.php');
?>