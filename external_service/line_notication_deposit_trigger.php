<?php
require_once('../linebot/autoloadConnection.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/function_util.php');
require_once(__DIR__.'/../include/lib_line.php');

use Utility\Library;
use Component\functions;
use Line\libraryLine;

$lineLib = new libraryLine();
$lib = new library();
$func = new functions();

$arrayStmItem = array();
$getStmItemTypeAllow = $conmysql->prepare("SELECT dept_itemtype_code FROM lbconstantdept WHERE allow_lbconstantdept = '1'");
$getStmItemTypeAllow->execute();
while($rowStmItemType = $getStmItemTypeAllow->fetch(PDO::FETCH_ASSOC)){
	$arrayStmItem[] = "'".$rowStmItemType["dept_itemtype_code"]."'";
}
$formatDept = $func->getConstant('hidden_dep');
$templateMessage = $func->getTemplateSystem('DepositInfo',1);
$fetchDataSTM = $conmssql->prepare("SELECT dsm.PRNCBAL,dsm.DEPTACCOUNT_NO,dit.DEPTITEMTYPE_DESC,dsm.DEPTITEM_AMT as AMOUNT,dm.MEMBER_NO,dsm.OPERATE_DATE,dsm.SEQ_NO
									FROM dpdeptstatement dsm LEFT JOIN dpucfdeptitemtype dit ON dsm.deptitemtype_code = dit.deptitemtype_code
									LEFT JOIN dpdeptmaster dm ON dsm.deptaccount_no = dm.deptaccount_no and dsm.coop_id = dm.coop_id
									WHERE dsm.operate_date BETWEEN (GETDATE() - 2) and GETDATE() and (dsm.sync_notify_flag IS NULL OR dsm.sync_notify_flag = '0') and dsm.deptitemtype_code IN(".implode(',',$arrayStmItem).")");
$fetchDataSTM->execute();
$data = array();
$exData = array();
$dataChkNotify = array();
while($rowSTM = $fetchDataSTM->fetch(PDO::FETCH_ASSOC)){
	if($lineLib->getLineIdNotify($rowSTM["MEMBER_NO"])){
		$dataMerge = array();
		$dataMerge["DEPTACCOUNT_NO"] = $lib->formataccount_hidden($rowSTM["DEPTACCOUNT_NO"],$formatDept);
		$dataMerge["AMOUNT"] = number_format($rowSTM["AMOUNT"],2);
		$dataMerge["BALANCE"] = number_format($rowSTM["PRNCBAL"],2);
		$dataMerge["ITEMTYPE_DESC"] = $rowSTM["DEPTITEMTYPE_DESC"];
		$dataMerge["DATETIME"] = isset($rowSTM["OPERATE_DATE"]) && $rowSTM["OPERATE_DATE"] != '' ? 
		$lib->convertdate($rowSTM["OPERATE_DATE"],'D m Y') : $lib->convertdate(date('Y-m-d H:i:s'),'D m Y');
		$message_endpoint = $lib->mergeTemplate($templateMessage["SUBJECT"],$templateMessage["BODY"],$dataMerge);
		
		$arrMessage["SUBJECT"] = $message_endpoint["SUBJECT"];
		$arrMessage["BODY"] = $message_endpoint["BODY"];
		
		$mesageData = preg_replace('/[\\\r\\\n]/','',$message_endpoint["BODY"],JSON_UNESCAPED_UNICODE);
		
		$dataPrepare = $lineLib->prepareMessageText($mesageData);
		$arrPostData["messages"] = $dataPrepare;
		$arrPostData["to"] = $lineLib->getLineIdNotify($rowSTM["MEMBER_NO"]);
	
		$dataChkNotify = $lineLib->checkNotify($rowSTM["MEMBER_NO"],$mesageData,$rowSTM["SEQ_NO"]);
		if($dataChkNotify == 1){
			$dataSendLib = $lineLib->sendPushLineBot($arrPostData);
			if($dataSendLib["RESULT"] == 1){
				$insertNotify =  $conmysql->prepare("INSERT INTO lbhistory(line_token, his_title, his_detail, member_no, send_by,ref) 
													  VALUES (:line_token,:his_title,:his_detail,:member_no,:send_by,:ref)");
				if($insertNotify->execute([
					':line_token' => $lineLib->getLineIdNotify($rowSTM["MEMBER_NO"]),
					':his_title' => $message_endpoint["SUBJECT"],
					':his_detail' => $mesageData,
					':member_no' => $rowSTM["MEMBER_NO"],
					':send_by' => 'system',
					':ref' => $rowSTM["SEQ_NO"]
				])){
						//null
				}else{
					$message_error = "Line Bot insert ลง  lbnotify  ไม่ได้".''."\n".'data => '.$insertNotify;
					$lib->sendLineNotify($message_error);
				}
			}else{
				$insertNotNotify =  $conmysql->prepare("INSERT INTO lognotnotifyline(line_token, his_title, his_detail, member_no, send_by,error) 
													  VALUES (:line_token,:his_title,:his_detail,:member_no,:send_by,:error)");
				if($insertNotNotify->execute([
					':line_token' => $lineLib->getLineIdNotify($rowSTM["MEMBER_NO"]),
					':his_title' => $message_endpoint["SUBJECT"],
					':his_detail' => $mesageData,
					':member_no' => $rowSTM["MEMBER_NO"],
					':send_by' => 'system',
					':error' => $dataSendLib["message"]
				])){
						//null
				}else{
					 $dataMessage = [
					':line_token' => $lineLib->getLineIdNotify($rowSTM["MEMBER_NO"]),
					':his_title' => $templateMessage["SUBJECT"],
					':his_detail' => $mesageData,
					':member_no' => $rowSTM["MEMBER_NO"],
					':send_by' => 'system',
					':error' => $dataSendLib["message"]
				];
					$message_error = "Line Bot insert ลง  lbnotnotify  ไม่ได้".''."\n".'data => '.$dataMessage;
					$lib->sendLineNotify($message_error);
				}
			}
		}
	}
	$exData[] = $rowSTM;
	//print_r();
}
print_r($exData);
?>