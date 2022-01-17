<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','reqloan_doc'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanRequestTrack')){
		$arrGrpReq = array();
		$fetchReqLoan = $conmysql->prepare("SELECT rf.reqattach_id,rf.file_id,rf.reqdoc_no,rf.file_path,f.file_name FROM gcreqloanattachment rf 
									LEFT JOIN gcreqfileattachment f ON f.file_id = rf.file_id
									WHERE rf.reqdoc_no = :reqloan_doc");
		$fetchReqLoan->execute([':reqloan_doc' => $dataComing["reqloan_doc"]]);
		while($rowReqLoan = $fetchReqLoan->fetch(PDO::FETCH_ASSOC)){
			$arrayReq["FILE_PATH"] = $rowReqLoan["file_path"];
			$arrayReq["FILE_NAME"] = $rowReqLoan["file_name"];
			$arrGrpReq[] = $arrayReq;
		}
		
		$arrayResult['ATTACHFILE_LIST'] = $arrGrpReq;
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