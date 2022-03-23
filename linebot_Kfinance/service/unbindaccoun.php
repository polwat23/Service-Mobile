<?php 
	$delete = $conmysql->prepare("DELETE FROM kfinancelinememberaccount WHERE line_token = :line_token ");
	if($delete->execute([
		':line_token' => $user_id
	])){
		$ms = "ยกเลิกผูกสำเร็จ";
		$dataSms = $lineLib->mergeTextMessage($ms);
		$arrPostData['messages'][0] = $dataSms;
		$arrPostData['replyToken'] = $reply_token;
	}else{
		$ms = "ไม่สามารถยกเลิกได้";
		$dataSms = $lineLib->mergeTextMessage($ms);
		$arrPostData['messages'][0] = $dataSms;
		$arrPostData['replyToken'] = $reply_token;
	}
?>