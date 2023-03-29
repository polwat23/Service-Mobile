<?php
$arrPostData = array();
$arrPostData['replyToken'] = $reply_token;

if($message == "จัดการบัญชี" || $message == "ผูกบัญชี"){
	require_once('./service/bindaccount.php');
}else if($message == "ยกเลิกผูกบัญชี"){
	require_once('./service/unbindaccount.php');
}else if($message == "เมนูทั้งหมด" || $message == "เมนูสหกรณ์" || $message == "เมนู"  ){
	require_once('./service/allmenu.php');
}else if($message == "เงินฝาก" ){
	require_once('./service/deposit/deposit.php');
}else if($message == "เงินกู้"){
	require_once('./service/loan/loan.php');
}else if($message == "ข้อมูลส่วนตัว" || $message == "ข้อมูลของฉัน"){
	require_once('./service/member_info.php');
}else if($message == "หุ้น" || $message == "loan" ){
	require_once('./service/share/share_info.php');
}else if($message == "ค้ำประกัน" || $message == "ภาระค้ำประกัน"){
	require_once('./service/guarantee/guarantee_info.php');
}else if($message == "ข้อมูลใครค้ำคุณ" || $message == "ใครค้ำคุณ" || $message == "ใครค้ำฉัน" ){
	require_once('./service/guarantee/guarantee_whocollu.php');
}else if($message == "ภาระค้ำประกันของฉัน" || $message == "ข้อมูลภาระค้ำประกันของฉัน" || $message == "คุณค้ำใคร" || $message == "ฉันค้ำใคร"){
	require_once('./service/guarantee/guarantee_ucollwho.php');
}else if($message == "เรียกเก็บประจำเดือน" || $message == "เรียกเก็บ"){
	require_once('./service/keeping/keeping.php');
}else if($message == "ปันผล" || $message == "เฉลี่ยคืน"){
	require_once('./service/dividend.php');
}else if($message == "ผู้รับผลประโยชน์"){
	require_once('./service/beneficiary.php');
}else if($message == "--สิทธิ์กู้โดยประมาณ" || $message == "--สิทธิ์กู้"){
	require_once('./service/credit/loan_creadit.php');
}else if($message == "ฌาปนกิจ"){
	require_once('./service/cremation.php');
}else if($message == "ใบเสร็จ"){
	require_once('./service/receipt/receipt_list.php');
}else if($message == "กองทุนสวัสดิการ"){
	require_once('./service/fund/fund_type.php');
}else if($message == "แจ้งปัญหา"){
	require_once('./service/report/report_problem.php');
}else if($message == "กิจกรรมสหกรณ์"){
	require_once('./service/event.php');
}else if($message == "แจ้งเตือน"){
	require_once('./service/notify/line_notify.php');
}else if($message == " เปิดการแจ้งเตือน" || $message == " ปิดการแจ้งเตือน" ){
	require_once('./service/notify/update_notify.php');
}else if($message == "ติดตามใบคำขอกู้" || $message == "ติดตามใบคำขอกู้ออนไลน์" ){
	require_once('./service/trackreqloan.php');
}else if($message == "#ล็อคบัญชี"){
	require_once('./service/lockaccount/lockaccount.php');
}else if($message == "#ยืนยันการล็อคบัญชี"){
	require_once('./service/lockaccount/confirm_lockaccount.php');
}else{
	$pattern = "/[\s:\;\/]/"; 
	$arrMessage = preg_split($pattern, $message,-1,PREG_SPLIT_NO_EMPTY);
	$incomeWord = $arrMessage[0]??null;
	if($lineLib->checkBindAccount($user_id)){
		if($incomeWord == "ดูรายการเคลื่อนไหวเงินฝาก" || $incomeWord == "รายการเคลื่อนไหวเงินฝาก"){
			$deptNo = $arrMessage[1]??null;
			require_once('./service/deposit/deposit_statement.php');
		}else if($incomeWord == "ดูรายการเคลื่อนไหวเงินกู้" || $incomeWord == "รายการเคลื่อนไหวเงินกู้"){
			$loanContract_no = $arrMessage[1]??null;
			require_once('./service/loan/loan_statement.php');
		}else if($incomeWord == "ประเภทเงินฝาก"){
			$depttype = $arrMessage[1]??null;
			require_once('./service/deposit/deposit_type.php'); 
		}else if($incomeWord == "ประเภทเงินกู้"){
			$loan_type = $arrMessage[1]??null;
			require_once('./service/loan/loan_type.php'); 
		}else if($incomeWord == "ใบเสร็จ"){
			$kpslip_no = $arrMessage[1]??null;
			require_once('./service/receipt/receipt_detail.php'); 
		}else if($incomeWord == "ใบเสร็จกองทุนสวัสดิการ" || $incomeWord == "ดูใบเสร็จกองทุนสวัสดิการ"){
			$fund_account = $arrMessage[1]??null;
			require_once('./service/fund/fund_recept.php'); 
		}else if($incomeWord == "แจ้งปัญหา"){
			require_once('./service/report/report_problem.php');
		}else{
			//บันทึกลงข้อความไม่ได้ตอบ
			require_once('./service/notrespondmessage.php');
		}
	}else if($incomeWord == "แจ้งปัญหา"){
		require_once('./service/report/report_problem.php');
	}else{
		require_once('./service/bindaccount_by_otp.php');
	}
}

$TextTemplate = $conmysql->prepare("SELECT ltm.id_ref,ltm.type_message,lic.menu_component,lic.file_service
													FROM lbincoming lic LEFT JOIN lbtextmaptype ltm ON lic.id_textincome = ltm.id_textincome
													WHERE lic.text_income = :txt_income and lic.is_use = '1'");
$TextTemplate->execute([':txt_income' => $message]);
$dataT = array();
if($TextTemplate->rowCount() > 0){
	$indexMs = 0;
	while($rowTemplate = $TextTemplate->fetch(PDO::FETCH_ASSOC)){
		$getTableName = $conmysql->prepare("SELECT table_name,condition_key FROM lbmaptypetablename WHERE type_message = :type_message");
		$getTableName->execute([':type_message' => $rowTemplate["type_message"]]);
		$rowTableName = $getTableName->fetch(PDO::FETCH_ASSOC);
		if($rowTemplate["type_message"] == 'image'){
			$getDataTemplate = $conmysql->prepare("SELECT image_url FROM ".$rowTableName["table_name"]." WHERE ".$rowTableName["condition_key"]." = :id_ref and is_use = '1'");
			$getDataTemplate->execute([':id_ref' => $rowTemplate["id_ref"]]);
			$rowDataTemplate = $getDataTemplate->fetch(PDO::FETCH_ASSOC);
			$dataTemplate = $lineLib->mergeImageMessage($rowDataTemplate["image_url"]);
			$arrPostData['messages'][$indexMs] = $dataTemplate;
		}else if($rowTemplate["type_message"] == 'location'){
			$getDataTemplate = $conmysql->prepare("SELECT title, address, latitude,longtitude FROM ".$rowTableName["table_name"]." WHERE ".$rowTableName["condition_key"]." = :id_ref and is_use = '1'");
			$getDataTemplate->execute([':id_ref' => $rowTemplate["id_ref"]]);
			$rowDataTemplate = $getDataTemplate->fetch(PDO::FETCH_ASSOC);
			$dataTemplate = $lineLib->mergeLocationMessage($rowDataTemplate["title"],$rowDataTemplate["address"],$rowDataTemplate["latitude"],$rowDataTemplate["longtitude"]);
			$arrPostData['messages'][$indexMs] = $dataTemplate;
		}else if($rowTemplate["type_message"] == 'text'){
			$getDataTemplate = $conmysql->prepare("SELECT text_message  FROM ".$rowTableName["table_name"]." WHERE ".$rowTableName["condition_key"]." = :id_ref and is_use = '1'");
			$getDataTemplate->execute([':id_ref' => $rowTemplate["id_ref"]]);
			$rowDataTemplate = $getDataTemplate->fetch(PDO::FETCH_ASSOC);
			$dataTemplate = $lineLib->mergeTextMessage($rowDataTemplate["text_message"]);
			$arrPostData['messages'][$indexMs] = $dataTemplate;
		}else if($rowTemplate["type_message"] == 'quick_reply'){						
	
			$getDataTemplate = $conmysql->prepare("SELECT ac.id_action,ac.type,ac.url,ac.area_x,ac.area_y,ac.width,ac.height,ac.label,ac.data,ac.data,ac.mode,ac.initial,ac.max,ac.min,ac.text, qm.text AS title
													FROM  lbquickmessagemap qmm
													LEFT JOIN lbaction ac ON ac.id_action = qmm.action_id
													LEFT JOIN lbquickmessage qm ON qm.id_quickmsg = qmm.quickmessage_id
													WHERE qmm.is_use = '1' AND ac.is_use ='1' AND qmm.quickmessage_id = :id_ref");
			$getDataTemplate->execute([':id_ref' => $rowTemplate["id_ref"]]);
			$groupDataTemplate = array();
			$typeAction = null;
			while($rowDataTemplate = $getDataTemplate->fetch(PDO::FETCH_ASSOC)){
				$arrData = array();
				$arrData["ID_ACTION"] = $rowDataTemplate["id_action"];
				$arrData["TYPE"] = $rowDataTemplate["type"];
				$arrData["TITLE"] = $rowDataTemplate["title"];
				$arrData["URL"] = $rowDataTemplate["url"];
				$arrData["AREA_X"] = $rowDataTemplate["area_x"];
				$arrData["AREA_Y"] = $rowDataTemplate["area_y"];
				$arrData["WIDTH"] = $rowDataTemplate["width"];
				$arrData["HEIGHT"] = $rowDataTemplate["height"];
				$arrData["LABEL"] = $rowDataTemplate["label"];
				$arrData["DATA"] = $rowDataTemplate["data"];
				$arrData["MODE"] = $rowDataTemplate["mode"];
				$arrData["INITIAL"] = $rowDataTemplate["initial"];
				$arrData["MAX"] = $rowDataTemplate["max"];
				$arrData["MIN"] = $rowDataTemplate["min"];
				$arrData["TEXT"] = $rowDataTemplate["text"];
				$typeAction = $rowDataTemplate["type"];
				$groupDataTemplate[] = $arrData;
			}
			
			if($typeAction=='message'){
				$dataTemplate = $lineLib->mergeMessageAction($groupDataTemplate);
				$arrPostData['messages'][$indexMs] = $dataTemplate;
			}else if($typeAction=='uri'){
				file_put_contents(__DIR__.'/../log/response.txt', json_encode($groupDataTemplate) . PHP_EOL, FILE_APPEND);
				$dataTemplate = $lineLib->mergeUrlAction($groupDataTemplate);
				$arrPostData['messages'][$indexMs] = $dataTemplate;
			}else if($typeAction=='datetime_picker'){
				$dataTemplate = $lineLib->mergeDetetimePickerAction($groupDataTemplate);
				$arrPostData['messages'][$indexMs] = $dataTemplate;
			}else if($typeAction =='camera'){
				$dataTemplate = $lineLib->mergeCameraAction($groupDataTemplate);
				$arrPostData['messages'][$indexMs] = $dataTemplate;
			}else if($typeAction =='camera_roll'){
				$dataTemplate = $lineLib->mergeCameraRollAction($groupDataTemplate);
				$arrPostData['messages'][$indexMs] = $dataTemplate;
			}else if($typeAction=='postback'){

				$dataTemplate = $lineLib->mergePostbackAction($groupDataTemplate);
				$arrPostData['messages'][$indexMs] = $dataTemplate;
			}else if($typeAction=='location'){
				$dataTemplate = $lineLib->mergeLocationAction($groupDataTemplate);
				$arrPostData['messages'][$indexMs] = $dataTemplate;
			}else {
				file_put_contents(__DIR__.'/../log/response.txt', json_encode($groupDataTemplate) . PHP_EOL, FILE_APPEND);
				$dataTemplate = $lineLib->mergeTextMessage("ลง else".$rowTemplate["id_ref"].$typeAction);
				$arrPostData['messages'][$indexMs
				] = $dataTemplate;
			}
			

		}
		//$dataT[]=$rowTemplate;
		$indexMs ++;
	}
}
file_put_contents('Msgresponse.txt', json_encode($rowTableName,JSON_UNESCAPED_UNICODE ) . PHP_EOL, FILE_APPEND);
require_once('./service/incomtext.php');
require_once(__DIR__.'./replyresponse.php');
	
?>