<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','showinvoice')){
		if(sizeof($dataComing["is_view"]) > 0){
			$updateisview= $conoracle->prepare("UPDATE LCNOTICEMTHRECV SET is_view = '1' WHERE notice_docno in('".implode("','",$dataComing["is_view"])."')");
			if($updateisview->execute()){

			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถเเสดงใบเเจ้งยอดหนี้";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
		}
		
		if(sizeof($dataComing["isnot_view"]) > 0){
			$updateisnotview = $conoracle->prepare("UPDATE LCNOTICEMTHRECV SET is_view = '0' WHERE notice_docno in('".implode("','",$dataComing["isnot_view"])."')");
			if($updateisnotview->execute()){

			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถเเสดงใบเเจ้งยอดหนี้";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
		}
	$arrayResult["RESULT"] = TRUE;
	require_once('../../../../include/exit_footer.php');	
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