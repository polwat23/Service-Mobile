<?php
require_once('../autoload.php');
if($lib->checkCompleteArgument(['emp_no','api_token','unique_id'],$dataComing)){
	$arrPayload = $auth->check_apitoken($dataComing["api_token"],$config["SECRET_KEY_JWT"]);
	if(!$arrPayload["VALIDATE"]){
		$filename = basename(__FILE__, '.php');
		$logStruc = [
			":error_menu" => $filename,
			":error_code" => "WS0001",
			":error_desc" => "ไม่สามารถยืนยันข้อมูลได้"."\n".json_encode($dataComing),
			":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
		];
		$log->writeLog('errorusage',$logStruc);
		$arrayResult['RESPONSE_CODE'] = "WS0001";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(401);
		require_once('../../include/exit_footer.php');
		
	}
	
		$arrDocReq = array();
		$emp_no = $dataComing["emp_no"];
		
		$getReqDocument = $conmysql->prepare("SELECT reqdoc_no, document_url, req_status FROM gcreqdoconline 
											WHERE documenttype_code = 'RRGT' AND member_no = :emp_no AND req_status not IN('1','-9','9')");
		$getReqDocument->execute([':emp_no' => $emp_no]);
		while($rowPrename = $getReqDocument->fetch(PDO::FETCH_ASSOC)){
			$docArr = array();
			$docArr["REQDOC_NO"] = $rowPrename["reqdoc_no"];
			$docArr["DOCUMENT_URL"] = $rowPrename["document_url"];
			$docArr["REQ_STATUS"] = $rowPrename["req_status"];
			$arrDocReq[] = $docArr;
		}
		
		if(count($arrDocReq) < 1){
			//memmber register list
			$fetchMemberInfo = $conmssql->prepare("SELECT MB.MEMBER_NO
											FROM MBMEMBMASTER MB
											WHERE MB.SALARY_ID = :emp_no and resign_status != 1");
			$fetchMemberInfo->execute([
				':emp_no' => $emp_no
			]);
			//AND MB.MEMBER_STATUS = '-1' 
			$rowInfoMobile = $fetchMemberInfo->fetch(PDO::FETCH_ASSOC);
			if(isset($rowInfoMobile["MEMBER_NO"])){
				$arrayResult["MEMBER_NO"] = $rowInfoMobile["MEMBER_NO"];
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE_CODE'] = "";
				$arrayResult['RESPONSE_MESSAGE'] = "ท่านยังไม่ได้เป็นสมาชิกสหกรณ์หรือรหัสพนักงานไม่ถูกต้อง กรุณาตรวจสอบรหัสพนักงานและลองใหม่อีกครั้ง";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		}else{
			$arrayResult['RESPONSE_CODE'] = "";
			$arrayResult['RESPONSE_MESSAGE'] = "ท่านได้ส่งใบคำขอไปแล้วและอยู่ในระหว่างดำเนินการ หากมีคำถามเพิ่มเติมกรุณาติดต่อสหกรณ์";
			$arrayResult['RESULT'] = FALSE;
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