<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'TransferInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrGroupMove = array();
		$getTransfer = $conoracle->prepare("SELECT s.TRN_DOCNO,s.ENTRY_DATE,
													c_old.coopbranch_desc as OLD_BRANCH_DESC,
													c_new.coopbranch_desc as NEW_BRANCH_DESC
												 FROM  wctransfermember s , cmucfcoopbranch c_new , cmucfcoopbranch c_old
													WHERE  trim(s.deptaccount_no)=  :member_no
													and (s.old_coop_id = c_old.coop_id (+))
													and (s.new_coop_id = c_new.coop_id (+))
													order by s.ENTRY_DATE desc");
		$getTransfer->execute([':member_no' => $member_no]);
		while($rowDataOra = $getTransfer->fetch(PDO::FETCH_ASSOC)){
			$arrMove = array();
			$arrMove["TRN_DOCNO"] = $rowDataOra["TRN_DOCNO"];
			$arrMove["ENTRY_DATE"] = $lib->convertdate($rowDataOra["ENTRY_DATE"],"D m Y");
			$arrMove["OLD_BRANCH_DESC"] = $rowDataOra["OLD_BRANCH_DESC"];
			$arrMove["NEW_BRANCH_DESC"] = $rowDataOra["NEW_BRANCH_DESC"];
			
			$arrGroupMove[] = $arrMove;
		}
		$arrayResult['TRANSFER'] = $arrGroupMove;
		$arrayResult['RESULT'] = TRUE;
		require_once('../../include/exit_footer.php');
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../include/exit_footer.php');
		
	}
}else{
	$filename = basename(__FILE__, '.php');
	$logStruc = [
		":error_menu" => $filename,
		":error_code" => "WS4004",
		":error_desc" => "ส่ง Argument มาไม่ครบ "."\n".json_encode($dataComing),
		":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
	];
	$log->writeLog('errorusage',$logStruc);
	$message_error = "ไฟล์ ".$filename." ส่ง Argument มาไม่ครบมาแค่ "."\n".json_encode($dataComing);
	$lib->sendLineNotify($message_error);
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
	
}
?>