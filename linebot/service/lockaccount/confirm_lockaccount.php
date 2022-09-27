<?php

if($lineLib->checkBindAccount($user_id)){

	$data = $lineLib->getMemberNo($user_id);
	$themeColor = $lineLib->getLineConstant('theme_color');
	$member_no = $configAS[$data] ?? $data;
	
	
	$fetchUserAccount = $conmysql->prepare("SELECT account_status
											FROM gcmemberaccount WHERE line_token = :line_token ");
	$fetchUserAccount->execute([
		':line_token' => $user_id
	]);
	 $userData = $fetchUserAccount->fetch(PDO::FETCH_ASSOC);
	 $account_status = $userData["account_status"];
		 if($account_status == '-8'){
			$messageResponse = 'บัญชีของคุณ ถูกล็อกไปแล้ว หากต้องการใช้งานกรุณาติดต่อสหกรณ์';
			$dataPrepare = $lineLib->prepareMessageText($messageResponse);
			$arrPostData["messages"] = $dataPrepare;
			$arrPostData["replyToken"] = $reply_token;
		 }else{
			 
			
			 $updateStatus = $conmysql->prepare("UPDATE gcmemberaccount SET prev_acc_status = :prev_acc_status,account_status = :account_status,
													counter_wrongpass = 0
												WHERE member_no = :member_no");
			if($updateStatus->execute([
				':account_status' => '-8',
				':member_no' => $data,
				':prev_acc_status' => $account_status
			])){
				$messageResponse = "ล็อคบัญชีสำเร็จ หากต้องการใช้งานกรุณาติดต่อสหกรณ์";
				$dataPrepare = $lineLib->prepareMessageText($messageResponse);
				$arrPostData["messages"] = $dataPrepare;
				$arrPostData["replyToken"] = $reply_token;
				
			}else{
				$messageResponse = json_encode([
				':account_status' => '-8',
				':member_no' => $data,
				':prev_acc_status' => $account_status
			]);
				$dataPrepare = $lineLib->prepareMessageText($messageResponse);
				$arrPostData["messages"] = $dataPrepare;
				$arrPostData["replyToken"] = $reply_token;
			}
			 
			 
	
		 }
		
	
		


	 
	



	
}else{
	$altText = "ท่านยังไม่ได้ผูกบัญชี";
	$dataMs = $lineLib->notBindAccount();
	$dataPrepare = $lineLib->prepareFlexMessage($altText,$dataMs);
	$arrPostData["messages"] = $dataPrepare;
	$arrPostData["replyToken"] = $reply_token;
}
	

?>