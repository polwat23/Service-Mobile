<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	$notiGroup = array();
	$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DocumentBeneciaryRequest')){
		$getRegDocument = $conmysql->prepare("SELECT REQDOC_NO, MEMBER_NO, DOCUMENTTYPE_CODE, FORM_VALUE, DOCUMENT_URL, REQ_STATUS, REQUEST_DATE, UPDATE_DATE 
										FROM GCREQDOCONLINE WHERE REQ_STATUS = '1' AND DOCUMENTTYPE_CODE = 'RRGT' AND MEMBER_NO = :member_no");
		$getRegDocument->execute([
			':member_no' => $payload["member_no"],
		]);
		$rowRegDocument = $getRegDocument->fetch(PDO::FETCH_ASSOC);
		
		if(isset($rowRegDocument["REQDOC_NO"])){
			$arrayResult['IS_NOTIFY_CBNF'] = false;
		}else{
			$getReqDocument = $conmysql->prepare("SELECT REQDOC_NO, MEMBER_NO, DOCUMENTTYPE_CODE, FORM_VALUE, DOCUMENT_URL, REQ_STATUS, REQUEST_DATE, UPDATE_DATE 
											FROM GCREQDOCONLINE WHERE REQ_STATUS NOT IN('9','-9') AND DOCUMENTTYPE_CODE = 'CBNF' AND MEMBER_NO = :member_no");
			$getReqDocument->execute([
				':member_no' => $payload["member_no"],
			]);
			$rowReqDocument = $getReqDocument->fetch(PDO::FETCH_ASSOC);
			
			if(isset($rowReqDocument["REQDOC_NO"])){
				$arrayResult['IS_NOTIFY_CBNF'] = false;
			}else{
				$notiArr = array();
				$notiArr['REMARK'] = 'ท่านยังไม่ได้กรอกข้อมูลผู้รับผลประโยชน์  กรุณากรอกข้อมูลที่เมนูใบคำขอผู้รับผลประโยชน์';
				$notiArr['TITLE'] = 'กรุณากรอกข้อมูลใบคำขอผู้รับผลประโยชน์';
				$notiArr['MENU_NAME'] = 'เมนูใบคำขอผู้รับผลประโยชน์';
				$notiArr['PATH'] = '/DocumentBeneciaryRequest';
				$notiArr['PATH_TEXT'] = 'ไปยังใบคำขอผู้รับผลประโยชน์';
				$notiArr['MENU_ICON'] = 'menu_documentreq';
				$notiGroup[] = $notiArr;
			}
		}
	}
	
	if($func->check_permission($payload["user_type"],$dataComing["menu_component_balconfirm"],'DocBalanceConfirm')){
		$getBalanceMaster = $conmssql->prepare("SELECT TOP 1 BALANCE_DATE FROM YRCONFIRMMASTER WHERE MEMBER_NO = :member_no ORDER BY BALANCE_DATE DESC");
		$getBalanceMaster->execute([':member_no' => $member_no]);
		$rowBalMaster = $getBalanceMaster->fetch(PDO::FETCH_ASSOC);
		
		$getBalStatus = $conmysql->prepare("SELECT confirm_date,confirm_flag,confirmlon_list, confirmshr_list, balance_date, remark, url_path FROM gcconfirmbalancelist WHERE member_no = :member_no and balance_date = :balance_date and is_use = '1'");
		$getBalStatus->execute([
			':member_no' => $member_no,
			':balance_date' => date('Y-m-d',strtotime($rowBalMaster["BALANCE_DATE"]))
		]);
		$rowBalStatus = $getBalStatus->fetch(PDO::FETCH_ASSOC);
		
		if(isset($rowBalStatus["balance_date"]) && $rowBalStatus["balance_date"] != ""){
			$arrayResult['IS_NOTIFY_CBNF'] = false;
		}else{
			$notiArr = array();
			$notiArr['REMARK'] = 'ท่านยังไม่ได้กรอกข้อมูลหนังสือยืนยันยอด  กรุณากรอกข้อมูลที่เมนูหนังสือยืนยันยอด';
			$notiArr['TITLE'] = 'กรุณากรอกข้อมูลหนังสือยืนยันยอด';
			$notiArr['MENU_NAME'] = 'หนังสือยืนยันยอด';
			$notiArr['PATH'] = '/DocBalanceConfirm';
			$notiArr['PATH_TEXT'] = 'ไปยังหนังสือยืนยันยอด';
			$notiArr['MENU_ICON'] = 'menu_doc_balance';
			$notiGroup[] = $notiArr;
		}
	}
	
	$arrayResult['NOTI_GROUP'] = $notiGroup;
	$arrayResult['RESULT'] = TRUE;
	require_once('../../include/exit_footer.php');
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