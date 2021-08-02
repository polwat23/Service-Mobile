<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_textmessage','text_message'],$dataComing)){
	if($func->check_permission_core($payload,'line','linerestextmsg')){
		if(isset($dataComing["text_message"]) || $dataComing["text_message"] != ''){
			$updateText = $conmysql->prepare("UPDATE lbtextmessage SET text_message = :text_message, update_by = :update_by
													WHERE id_textmessage = :id_textmessage");
			if($updateText->execute([
				':text_message' => $dataComing["text_message"],
				':update_by' => $payload["username"],
				':id_textmessage' => $dataComing["id_textmessage"]
			])){
				$arrayResult["RESULT"] = TRUE;
				require_once('../../../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขข้อความได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
				
			}
		}else{
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขเป็นค่าว่างได้";
			$arrayResult['RESULT'] = FALSE;
			require_once('../../../../include/exit_footer.php');
			
		}
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../../include/exit_footer.php');
		
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../../include/exit_footer.php');
	
}
?>