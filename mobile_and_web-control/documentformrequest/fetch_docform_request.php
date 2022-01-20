<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DocumentFormRequest')){
		$arrFormTyper = array();
		$arrReqDoc = array();
		$arrayExecute = array();
		
		$arrayExecute["member_no"] = $payload["member_no"];
		if(isset($dataComing["start_date"]) && $dataComing["start_date"] != ""){
			$arrayExecute["start_date"] = $dataComing["start_date"];
		}
		if(isset($dataComing["end_date"]) && $dataComing["end_date"] != ""){
			$arrayExecute["end_date"] = $dataComing["end_date"];
		}
		
		$getFormType = $conmysql->prepare("SELECT reqdocformtype_id, documenttype_desc, documentform_url FROM gcreqdocformtype WHERE is_use = '1'");
		$getFormType->execute();
		while($rowFormType = $getFormType->fetch(PDO::FETCH_ASSOC)){
			$arrDoc = array();
			$arrDoc["REQDOCFORMTYPE_ID"] = $rowFormType["reqdocformtype_id"] ;
			$arrDoc["DOCUMENTTYPE_DESC"] = $rowFormType["documenttype_desc"] ;
			$arrDoc["DOCUMENTFORM_URL"] = $rowFormType["documentform_url"] ;
			$arrFormTyper[] = $arrDoc;
		}
		$getReqDoc = $conmysql->prepare("SELECT df.reqdoc_no, df.reqdocformtype_id, df.document_url, df.req_status, df.remark, df.request_date, df.update_date, dt.documenttype_desc
													FROM gcreqdocformonline df
													LEFT JOIN gcreqdocformtype dt ON dt.reqdocformtype_id = df.reqdocformtype_id
													WHERE df.member_no = :member_no
													".(isset($dataComing["start_date"]) && $dataComing["start_date"] != "" ? 
													"and date_format(df.request_date,'%Y-%m-%d') >= :start_date" : null)."
													".(isset($dataComing["end_date"]) && $dataComing["end_date"] != "" ? 
													"and date_format(df.request_date,'%Y-%m-%d') <= :end_date" : null)."
													 ORDER BY df.request_date DESC");
		$getReqDoc->execute($arrayExecute);
		while($rowReqDoc = $getReqDoc->fetch(PDO::FETCH_ASSOC)){
			$arrDoc = array();
			$arrDoc["REQDOC_NO"] = $rowReqDoc["reqdoc_no"] ;
			$arrDoc["REQDOCFORMTYPE_ID"] = $rowReqDoc["reqdocformtype_id"] ;
			$arrDoc["DOCUMENT_URL"] = $rowReqDoc["document_url"] ;
			$arrDoc["REQ_STATUS"] = $rowReqDoc["req_status"] ;
			$arrDoc["REQ_STATUS_DESC"] = $configError["REQ_LOAN_STATUS"][0][$rowReqDoc["req_status"]][0][$lang_locale];
			if($rowReqDoc["req_status"] == '-9' || $rowReqDoc["req_status"] == '9'){
				$arrDoc["REQ_STATUS_COLOR"] = "#e6694a";
			}else if($rowReqDoc["req_status"] == '1'){
				$arrDoc["REQ_STATUS_COLOR"] = "#01a063";
			}else{
				$arrDoc["REQ_STATUS_COLOR"] = "#007cf7";
			}
			
			$arrDoc["REMARK"] = $rowReqDoc["remark"] ;
			$arrDoc["REQUEST_DATE_RAW"] = $rowReqDoc["request_date"] ;
			$arrDoc["REQUEST_DATE"] = $lib->convertdate($rowReqDoc["request_date"] ,'D m Y',true);
			$arrDoc["UPDATE_DATE_RAW"] = $rowReqDoc["update_date"] ;
			$arrDoc["UPDATE_DATE"] = $lib->convertdate($rowReqDoc["update_date"] ,'D m Y',true);
			$arrDoc["DOCUMENTTYPE_DESC"] = $rowReqDoc["documenttype_desc"] ;
			
			$arrReqDoc[] = $arrDoc;
		}
		
		$arrayResult["REQFORM_TYPE"] = $arrFormTyper;
		$arrayResult["REQ_DOCUMENT"] = $arrReqDoc;
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