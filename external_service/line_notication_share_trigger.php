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
$getStmItemTypeAllow = $conmysql->prepare("SELECT share_itemtype_code FROM lbconstantshare WHERE allow_lbconstantshare = '1'");
$getStmItemTypeAllow->execute();
while($rowStmItemType = $getStmItemTypeAllow->fetch(PDO::FETCH_ASSOC)){
	$arrayStmItem[] = "'".$rowStmItemType["share_itemtype_code"]."'";
}


$formatDept = $func->getConstant('hidden_dep');
$templateMessage = $func->getTemplateSystem('ShareInfo',1);
$fetchDataSTM = $conoracle->prepare("SELECT SHS.SEQ_NO,SHS.OPERATE_DATE,SHS.MEMBER_NO,(SHS.SHARE_AMOUNT * 10) AS AMOUNT,
												(SHS.SHARESTK_AMT * 10) AS SHARE_BALANCE,SHI.SHRITEMTYPE_DESC
												FROM SHSHARESTATEMENT SHS LEFT JOIN SHUCFSHRITEMTYPE SHI ON SHS.SHRITEMTYPE_CODE = SHI.SHRITEMTYPE_CODE
												WHERE SHS.OPERATE_DATE BETWEEN (SYSDATE - 2) and SYSDATE AND SHS.SYNC_NOTIFY_FLAG = '0' AND SHS.SHRITEMTYPE_CODE IN(".implode(',',$arrayStmItem).")");
$fetchDataSTM->execute();
$data = array();
$exData = array();

while($rowSTM = $fetchDataSTM->fetch(PDO::FETCH_ASSOC)){


	if($lineLib->getLineIdNotify($rowSTM["MEMBER_NO"])){
		$dataMerge = array();
		$dataMerge["AMOUNT"] = number_format($rowSTM["AMOUNT"],2);
		$dataMerge["SHARE_BALANCE"] = number_format($rowSTM["SHARE_BALANCE"],2);
		$dataMerge["ITEMTYPE_DESC"] = $rowSTM["SHRITEMTYPE_DESC"];
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
			$data[] = $rowSTM;
		}
	}
	$exData[] = $rowSTM;
}


print_r($data);
print_r($exData);
?>
