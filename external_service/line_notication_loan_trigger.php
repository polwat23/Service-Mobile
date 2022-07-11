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
$getStmItemTypeAllow = $conmysql->prepare("SELECT loan_itemtype_code FROM lbconstantloan WHERE allow_lbconstantloan = '1'");
$getStmItemTypeAllow->execute();
while($rowStmItemType = $getStmItemTypeAllow->fetch(PDO::FETCH_ASSOC)){
	$arrayStmItem[] = "'".$rowStmItemType["loan_itemtype_code"]."'";
}
$formatDept = $func->getConstant('hidden_dep');
$templateMessage = $func->getTemplateSystem('LoanInfo',1);
$fetchDataSTM = $conoracle->prepare("SELECT lut.loanitemtype_desc,lcn.loancontract_no,lcn.OPERATE_DATE,lcm.member_no,lcn.seq_no,
									lcn.principal_payment,lcn.interest_payment,lcn.principal_balance
									from lncontstatement lcn LEFT JOIN lncontmaster lcm ON lcn.loancontract_no = lcm.loancontract_no
									LEFT JOIN lnucfloanitemtype lut ON lcn.loanitemtype_code = lut.loanitemtype_code
									WHERE lcn.operate_date BETWEEN (SYSDATE - 2) and SYSDATE and lcn.loanitemtype_code IN(".implode(',',$arrayStmItem).")");
$fetchDataSTM->execute();

$data = array();
$exData = array();
while($rowSTM = $fetchDataSTM->fetch(PDO::FETCH_ASSOC)){
	if($lineLib->getLineIdNotify($rowSTM["MEMBER_NO"])){
		$dataMerge = array();
		$dataMerge["LOANCONTRACT_NO"] = $rowSTM["LOANCONTRACT_NO"];
		$dataMerge["PRINCIPAL_PAYMENT"] = number_format($rowSTM["PRINCIPAL_PAYMENT"],2);
		$dataMerge["INTEREST_PAYMENT"] = number_format($rowSTM["INTEREST_PAYMENT"],2);
		$dataMerge["PRINCIPAL_BALANCE"] = number_format($rowSTM["PRINCIPAL_BALANCE"],2);
		$dataMerge["ITEMTYPE_DESC"] = $rowSTM["LOANITEMTYPE_DESC"];
		$dataMerge["DATETIME"] = isset($rowSTM["OPERATE_DATE"]) && $rowSTM["OPERATE_DATE"] != '' ? 
		$lib->convertdate($rowSTM["OPERATE_DATE"],'D m Y') : $lib->convertdate(date('Y-m-d H:i:s'),'D m Y');
		$message_endpoint = $lib->mergeTemplate($templateMessage["SUBJECT"],$templateMessage["BODY"],$dataMerge);
		
		$arrMessage["SUBJECT"] = $message_endpoint["SUBJECT"];
		$arrMessage["BODY"] = $message_endpoint["BODY"];
		
		$mesageData = preg_replace('/[\\\r\\\n]/','',$message_endpoint["BODY"],JSON_UNESCAPED_UNICODE);
		
		$dataPrepare = $lineLib->prepareMessageText($mesageData);
		$arrPostData["messages"] = $dataPrepare;
		$arrPostData["to"] = $lineLib->getLineIdNotify($rowSTM["MEMBER_NO"]);
		$seq_no = $rowSTM["SEQ_NO"];
		$member_no = $rowSTM["MEMBER_NO"];
		$dataChkNotify = $lineLib->checkNotify($member_no,$mesageData,$seq_no);
	
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
					$message_error = "Line Bot insert ลง  lbnotify  ไม่ได้".''."\n".'data => '.json_encode($insertNotify,JSON_UNESCAPED_UNICODE );
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
		}
		$data[] = $seq_no;
	}
	$exData[] = $rowSTM;
}
print_r($data);
print_r($exData);
