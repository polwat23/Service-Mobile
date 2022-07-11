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

$templateMessage = $func->getTemplateSystem('GuaranteeInfo',1);
$fetchDataGuarantee = $conoracle->prepare("SELECT mp.prename_desc || mb.memb_name || ' ' || mb.memb_surname as FULL_NAME,mb.member_no,
										lcc.LOANCONTRACT_NO,lcc.seq_no,
										 lcc.REF_COLLNO, lcm.startcont_date as STARTCONT_DATE,lt.loantype_desc as LOAN_TYPE,lcm.loanapprove_amt as AMOUNT
										FROM lncontcoll lcc 
										LEFT JOIN lncontmaster lcm ON lcc.loancontract_no = lcm.loancontract_no and lcc.coop_id = lcm.coop_id
										LEFT JOIN lnloantype lt ON lcm.loantype_code = lt.loantype_code
										LEFT JOIN mbmembmaster mb ON lcm.member_no = mb.member_no
										LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
										WHERE lcm.startcont_date BETWEEN (SYSDATE - 2) and SYSDATE and lcc.sync_notify_flag = '0' and lcc.coll_status = '1' and lcm.contract_status = '1' and lcc.loancolltype_code = '01' ");
$fetchDataGuarantee->execute();
$data = array();
$dataNitify = array();
while($rowGuarantee = $fetchDataGuarantee->fetch(PDO::FETCH_ASSOC)){
	if($lineLib->getLineIdNotify($rowGuarantee["MEMBER_NO"])){
		$dataMerge = array();
		$dataMerge["LOANCONTRACT_NO"] = $rowGuarantee["LOANCONTRACT_NO"];
		$dataMerge["AMOUNT"] = number_format($rowGuarantee["AMOUNT"],2);
		$dataMerge["FULL_NAME"] = $rowGuarantee["FULL_NAME"];
		$dataMerge["LOAN_TYPE"] = $rowGuarantee["LOAN_TYPE"];
		$dataMerge["APPROVE_DATE"] = isset($rowGuarantee["STARTCONT_DATE"]) && $rowGuarantee["STARTCONT_DATE"] != '' ? 
		$lib->convertdate($rowGuarantee["STARTCONT_DATE"],'D m Y') : $lib->convertdate(date('Y-m-d H:i:s'),'D m Y');
		$message_endpoint = $lib->mergeTemplate($templateMessage["SUBJECT"],$templateMessage["BODY"],$dataMerge);
		$mesageData = preg_replace('/[\\\r\\\n]/','',$message_endpoint["BODY"],JSON_UNESCAPED_UNICODE);
		$dataNitify[] = $mesageData;
		
		$dataPrepare = $lineLib->prepareMessageText($mesageData);
		$arrPostData["messages"] = $dataPrepare;
		$arrPostData["to"] = $lineLib->getLineIdNotify($rowGuarantee["MEMBER_NO"]);
		$seq_no = $rowGuarantee["SEQ_NO"];
		$member_no = $rowGuarantee["MEMBER_NO"];
		$dataChkNotify = $lineLib->checkNotify($member_no,$mesageData,$seq_no);
		if($dataChkNotify == 1){
			$dataSendLib = $lineLib->sendPushLineBot($arrPostData);
			if($dataSendLib["RESULT"] == 1){
				$insertNotify =  $conmysql->prepare("INSERT INTO lbhistory(line_token, his_title, his_detail, member_no, send_by,ref) 
													  VALUES (:line_token,:his_title,:his_detail,:member_no,:send_by, :ref)");
				if($insertNotify->execute([
					':line_token' => $lineLib->getLineIdNotify($rowGuarantee["MEMBER_NO"]),
					':his_title' => $message_endpoint["SUBJECT"],
					':his_detail' => $mesageData,
					':member_no' => $rowGuarantee["MEMBER_NO"],
					':send_by' => 'system',
					':ref' => $rowGuarantee["SEQ_NO"]
				])){
				
				
				}else{
					$message_error = "Line Bot insert ลง  lbnotify  ไม่ได้".''."\n".'data => '.$insertNotify;
					$lib->sendLineNotify($message_error);
				}
			}else{
				$insertNotNotify =  $conmysql->prepare("INSERT INTO lognotnotifyline(line_token, his_title, his_detail, member_no, send_by,error) 
													  VALUES (:line_token,:his_title,:his_detail,:member_no,:send_by,:error)");
				if($insertNotNotify->execute([
					':line_token' => $lineLib->getLineIdNotify($rowGuarantee["MEMBER_NO"]),
					':his_title' => $message_endpoint["SUBJECT"],
					':his_detail' => $mesageData,
					':member_no' => $rowGuarantee["MEMBER_NO"],
					':send_by' => 'system',
					':error' => $dataSendLib["message"]
				])){
				
				
				}else{
					 $dataMessage = [
					':line_token' => $lineLib->getLineIdNotify($rowGuarantee["MEMBER_NO"]),
					':his_title' => $templateMessage["SUBJECT"],
					':his_detail' => $mesageData,
					':member_no' => $rowGuarantee["MEMBER_NO"],
					':send_by' => 'system',
					':error' => $dataSendLib["message"]
				];
					$message_error = "Line Bot insert ลง  lbnotnotify  ไม่ได้".''."\n".'data => '.$dataMessage;
					$lib->sendLineNotify($message_error);
				}
			}
		}
	}
	$data[] = $rowGuarantee;
}

print_r($data);
//print_r($dataNitify);
?>