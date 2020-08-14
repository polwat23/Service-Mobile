<?php
require_once('../autoloadConnection.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/function_util.php');

use Utility\Library;
use Component\functions;

$lib = new library();
$func = new functions();

$jsonConfig = file_get_contents(__DIR__.'/../config/config_constructor.json');
$config = json_decode($jsonConfig,true);
$MonthNow = date("Ym");
$arrayStmItem = array();
$getStmItemTypeAllow = $conmysql->prepare("SELECT dept_itemtype_code FROM smsconstantdept WHERE allow_smsconstantdept = '1'");
$getStmItemTypeAllow->execute();
while($rowStmItemType = $getStmItemTypeAllow->fetch(PDO::FETCH_ASSOC)){
	$arrayStmItem[] = "'".$rowStmItemType["dept_itemtype_code"]."'";
}
$arrSMSCont = array();
$getSMSConstant = $conmysql->prepare("SELECT smscs_name,smscs_value FROM smsconstantsystem");
$getSMSConstant->execute();
while($rowSMSConstant = $getSMSConstant->fetch(PDO::FETCH_ASSOC)){
	$arrSMSCont[$rowSMSConstant["smscs_name"]] = $rowSMSConstant["smscs_value"];
}
$templateMessage = $func->getTemplateSystem('DepositInfo',1);
if(isset($templateMessage)){
	$fetchDataSTM = $conoracle->prepare("SELECT dit.SIGN_FLAG,dsm.PRNCBAL,dsm.DEPTACCOUNT_NO,dit.DEPTITEMTYPE_DESC,dsm.DEPTITEM_AMT as AMOUNT,dm.MEMBER_NO,dsm.OPERATE_DATE,dsm.SEQ_NO,
										mb.MEM_TELMOBILE
										FROM dpdeptstatement dsm LEFT JOIN dpucfdeptitemtype dit ON dsm.deptitemtype_code = dit.deptitemtype_code
										LEFT JOIN dpdeptmaster dm ON dsm.deptaccount_no = dm.deptaccount_no and dsm.coop_id = dm.coop_id
										LEFT JOIN mbmembmaster mb ON dm.member_no = mb.member_no
										WHERE dsm.operate_date BETWEEN (SYSDATE - 2) and SYSDATE and dsm.sync_notify_flag = '0' and dsm.deptitemtype_code IN(".implode(',',$arrayStmItem).")");
	$fetchDataSTM->execute();
	while($rowSTM = $fetchDataSTM->fetch(PDO::FETCH_ASSOC)){
		$getOperDateLastSeqNo = $conoracle->prepare("SELECT OPERATE_DATE FROM DPDEPTSTATEMENT WHERE DEPTACCOUNT_NO = :deptaccount_no and SEQ_NO = :seq_no");
		$getOperDateLastSeqNo->execute([
			':deptaccount_no' => $rowSTM["DEPTACCOUNT_NO"],
			':seq_no' => $rowSTM["SEQ_NO"] - 1
		]);
		$rowOper = $getOperDateLastSeqNo->fetch(PDO::FETCH_ASSOC);
		if(isset($rowOper["OPERATE_DATE"])){
			$durationIdle = $lib->count_duration($rowOper["OPERATE_DATE"],'d');
		}else{
			$durationIdle = 0;
		}
		if($rowSTM["SIGN_FLAG"] == '1'){
			if($rowSTM["AMOUNT"] >= $arrSMSCont["limit_dept_send_free"] || $durationIdle >= 180){
				$dataMerge["ITEMTYPE_DESC"] = $rowSTM["DEPTITEMTYPE_DESC"];
				$dataMerge["DEPTACCOUNT_NO"] = $lib->formataccount_hidden($rowSTM["DEPTACCOUNT_NO"],$func->getConstant('hidden_dep'));
				$dataMerge["AMOUNT"] = number_format($rowSTM["AMOUNT"],2);
				$dataMerge["DATETIME"] = $lib->convertdate($rowSTM["OPERATE_DATE"],'d m Y');
				$message_endpoint = $lib->mergeTemplate($templateMessage["SUBJECT"],$templateMessage["BODY"],$dataMerge);
				$arrayDest["cmd_sms"] = "CMD=".$config["CMD_SMS"]."&FROM=".$config["FROM_SERVICES_SMS"]."&TO=66".(substr($rowSTM["MEM_TELMOBILE"],1,9))."&REPORT=Y&CHARGE=".$config["CHARGE_SMS"]."&CODE=".$config["CODE_SMS"]."&CTYPE=UNICODE&CONTENT=".$lib->unicodeMessageEncode($message_endpoint["BODY"]);
				$arraySendSMS = $lib->sendSMS($arrayDest);
				if($arraySendSMS["RESULT"]){
					$arrayComing["TEL"] = $rowSTM["MEM_TELMOBILE"];
					$arrayComing["MEMBER_NO"] = $rowSTM["MEMBER_NO"];
					$arrayTel[] = $arrayComing;
					$func->logSMSWasSent(null,$message_endpoint["BODY"],$arrayTel,'system');
					$updateFlagDP = $conoracle->prepare("UPDATE dpdeptstatement SET sync_notify_flag = '1' WHERE deptaccount_no = :deptaccount_no and seq_no = :seq_no");
					$updateFlagDP->execute([
						':deptaccount_no' => $rowSTM["DEPTACCOUNT_NO"],
						':seq_no' => $rowSTM["SEQ_NO"]
					]);
				}
			}else{
				$checkRights = $conmysql->prepare("SELECT is_mindeposit,smscsp_mindeposit,smscsp_pay_type,request_flat_date
												FROM smsconstantperson WHERE smscsp_member_no = :member_no and smscsp_account = :deptaccount_no and is_use = '1'");
				$checkRights->execute([
					':member_no' => $rowSTM["MEMBER_NO"],
					':deptaccount_no' => $rowSTM["DEPTACCOUNT_NO"]
				]);
				if($checkRights->rowCount() > 0){
					$rowRights = $checkRights->fetch(PDO::FETCH_ASSOC);
					if($rowRights["is_mindeposit"] == '1'){
						if($rowSTM["AMOUNT"] >= $rowRights["smscsp_mindeposit"]){
							if($rowRights["smscsp_pay_type"] == '1'){
								if($MonthNow > $rowRights["request_flat_date"]){
									$dataMerge["ITEMTYPE_DESC"] = $rowSTM["DEPTITEMTYPE_DESC"];
									$dataMerge["DEPTACCOUNT_NO"] = $lib->formataccount_hidden($rowSTM["DEPTACCOUNT_NO"],$func->getConstant('hidden_dep'));
									$dataMerge["AMOUNT"] = number_format($rowSTM["AMOUNT"],2);
									$dataMerge["DATETIME"] = $lib->convertdate($rowSTM["OPERATE_DATE"],'d m Y');
									$message_endpoint = $lib->mergeTemplate($templateMessage["SUBJECT"],$templateMessage["BODY"],$dataMerge);
									$insertSmsTran = $conmysql->prepare("INSERT INTO smstranwassent(sms_message,member_no,tel_mobile,payment_keep,deptaccount_no,send_by)
																		VALUES(:message,:member_no,:tel_mobile,'0',:deptaccount_no,'system')");
									if($insertSmsTran->execute([
										':message' => $message_endpoint["BODY"],
										':member_no' => $rowSTM["MEMBER_NO"],
										':tel_mobile' => $rowSTM["MEM_TELMOBILE"],
										':deptaccount_no' => $rowSTM["DEPTACCOUNT_NO"]
									])){
										$lastId = $conmysql->lastInsertId();
										$arrayDest["cmd_sms"] = "CMD=".$config["CMD_SMS"]."&FROM=".$config["FROM_SERVICES_SMS"]."&TO=66".(substr($rowSTM["MEM_TELMOBILE"],1,9))."&REPORT=Y&CHARGE=".$config["CHARGE_SMS"]."&CODE=".$config["CODE_SMS"]."&CTYPE=UNICODE&CONTENT=".$lib->unicodeMessageEncode($message_endpoint["BODY"]);
										$arraySendSMS = $lib->sendSMS($arrayDest);
										if($arraySendSMS["RESULT"]){
											$updateTrans = $conmysql->prepare("UPDATE smstranwassent SET smid = :smid WHERE id_smssent = :lastId");
											$updateTrans->execute([
												':smid' => $arraySendSMS["SMID"],
												':lastId' => $lastId
											]);
											$updateFlagDP = $conoracle->prepare("UPDATE dpdeptstatement SET sync_notify_flag = '1' WHERE deptaccount_no = :deptaccount_no and seq_no = :seq_no");
											$updateFlagDP->execute([
												':deptaccount_no' => $rowSTM["DEPTACCOUNT_NO"],
												':seq_no' => $rowSTM["SEQ_NO"]
											]);
										}else{
											$delTrans = $conmysql->prepare("DELETE FROM smstranwassent WHERE id_smssent = :id_smssent");
											$delTrans->execute([':id_smssent' => $lastId]);
										}
									}
								}else{
									$dataMerge["ITEMTYPE_DESC"] = $rowSTM["DEPTITEMTYPE_DESC"];
									$dataMerge["DEPTACCOUNT_NO"] = $lib->formataccount_hidden($rowSTM["DEPTACCOUNT_NO"],$func->getConstant('hidden_dep'));
									$dataMerge["AMOUNT"] = number_format($rowSTM["AMOUNT"],2);
									$dataMerge["DATETIME"] = $lib->convertdate($rowSTM["OPERATE_DATE"],'d m Y');
									$message_endpoint = $lib->mergeTemplate($templateMessage["SUBJECT"],$templateMessage["BODY"],$dataMerge);
									$insertSmsTran = $conmysql->prepare("INSERT INTO smstranwassent(sms_message,member_no,tel_mobile,deptaccount_no,send_by)
																	VALUES(:message,:member_no,:tel_mobile,:deptaccount_no,'system')");
									if($insertSmsTran->execute([
										':message' => $message_endpoint["BODY"],
										':member_no' => $rowSTM["MEMBER_NO"],
										':tel_mobile' => $rowSTM["MEM_TELMOBILE"],
										':deptaccount_no' => $rowSTM["DEPTACCOUNT_NO"]
									])){
										$lastId = $conmysql->lastInsertId();
										$arrayDest["cmd_sms"] = "CMD=".$config["CMD_SMS"]."&FROM=".$config["FROM_SERVICES_SMS"]."&TO=66".(substr($rowSTM["MEM_TELMOBILE"],1,9))."&REPORT=Y&CHARGE=".$config["CHARGE_SMS"]."&CODE=".$config["CODE_SMS"]."&CTYPE=UNICODE&CONTENT=".$lib->unicodeMessageEncode($message_endpoint["BODY"]);
										$arraySendSMS = $lib->sendSMS($arrayDest);
										if($arraySendSMS["RESULT"]){
											$updateTrans = $conmysql->prepare("UPDATE smstranwassent SET smid = :smid WHERE id_smssent = :lastId");
											$updateTrans->execute([
												':smid' => $arraySendSMS["SMID"],
												':lastId' => $lastId
											]);
											$updateFlagDP = $conoracle->prepare("UPDATE dpdeptstatement SET sync_notify_flag = '1' WHERE deptaccount_no = :deptaccount_no and seq_no = :seq_no");
											$updateFlagDP->execute([
												':deptaccount_no' => $rowSTM["DEPTACCOUNT_NO"],
												':seq_no' => $rowSTM["SEQ_NO"]
											]);
										}else{
											$delTrans = $conmysql->prepare("DELETE FROM smstranwassent WHERE id_smssent = :id_smssent");
											$delTrans->execute([':id_smssent' => $lastId]);
										}
									}
								}
							}else{
								$dataMerge["ITEMTYPE_DESC"] = $rowSTM["DEPTITEMTYPE_DESC"];
								$dataMerge["DEPTACCOUNT_NO"] = $lib->formataccount_hidden($rowSTM["DEPTACCOUNT_NO"],$func->getConstant('hidden_dep'));
								$dataMerge["AMOUNT"] = number_format($rowSTM["AMOUNT"],2);
								$dataMerge["DATETIME"] = $lib->convertdate($rowSTM["OPERATE_DATE"],'d m Y');
								$message_endpoint = $lib->mergeTemplate($templateMessage["SUBJECT"],$templateMessage["BODY"],$dataMerge);
								$insertSmsTran = $conmysql->prepare("INSERT INTO smstranwassent(sms_message,member_no,tel_mobile,deptaccount_no,send_by)
																VALUES(:message,:member_no,:tel_mobile,:deptaccount_no,'system')");
								if($insertSmsTran->execute([
									':message' => $message_endpoint["BODY"],
									':member_no' => $rowSTM["MEMBER_NO"],
									':tel_mobile' => $rowSTM["MEM_TELMOBILE"],
									':deptaccount_no' => $rowSTM["DEPTACCOUNT_NO"]
								])){
									$lastId = $conmysql->lastInsertId();
									$arrayDest["cmd_sms"] = "CMD=".$config["CMD_SMS"]."&FROM=".$config["FROM_SERVICES_SMS"]."&TO=66".(substr($rowSTM["MEM_TELMOBILE"],1,9))."&REPORT=Y&CHARGE=".$config["CHARGE_SMS"]."&CODE=".$config["CODE_SMS"]."&CTYPE=UNICODE&CONTENT=".$lib->unicodeMessageEncode($message_endpoint["BODY"]);
									$arraySendSMS = $lib->sendSMS($arrayDest);
									if($arraySendSMS["RESULT"]){
										$updateTrans = $conmysql->prepare("UPDATE smstranwassent SET smid = :smid WHERE id_smssent = :lastId");
										$updateTrans->execute([
											':smid' => $arraySendSMS["SMID"],
											':lastId' => $lastId
										]);
										$updateFlagDP = $conoracle->prepare("UPDATE dpdeptstatement SET sync_notify_flag = '1' WHERE deptaccount_no = :deptaccount_no and seq_no = :seq_no");
										$updateFlagDP->execute([
											':deptaccount_no' => $rowSTM["DEPTACCOUNT_NO"],
											':seq_no' => $rowSTM["SEQ_NO"]
										]);
									}else{
										$delTrans = $conmysql->prepare("DELETE FROM smstranwassent WHERE id_smssent = :id_smssent");
										$delTrans->execute([':id_smssent' => $lastId]);
									}
								}
							}
						}
					}
				}
			}
		}else{
			if($rowSTM["AMOUNT"] >= $arrSMSCont["limit_withdraw_send_free"] || $durationIdle >= 180){
				$dataMerge["ITEMTYPE_DESC"] = $rowSTM["DEPTITEMTYPE_DESC"];
				$dataMerge["DEPTACCOUNT_NO"] = $lib->formataccount_hidden($rowSTM["DEPTACCOUNT_NO"],$func->getConstant('hidden_dep'));
				$dataMerge["AMOUNT"] = number_format($rowSTM["AMOUNT"],2);
				$dataMerge["DATETIME"] = $lib->convertdate($rowSTM["OPERATE_DATE"],'d m Y');
				$message_endpoint = $lib->mergeTemplate($templateMessage["SUBJECT"],$templateMessage["BODY"],$dataMerge);
				$arrayDest["cmd_sms"] = "CMD=".$config["CMD_SMS"]."&FROM=".$config["FROM_SERVICES_SMS"]."&TO=66".(substr($rowSTM["MEM_TELMOBILE"],1,9))."&REPORT=Y&CHARGE=".$config["CHARGE_SMS"]."&CODE=".$config["CODE_SMS"]."&CTYPE=UNICODE&CONTENT=".$lib->unicodeMessageEncode($message_endpoint["BODY"]);
				$arraySendSMS = $lib->sendSMS($arrayDest);
				if($arraySendSMS["RESULT"]){
					$arrayComing["TEL"] = $rowSTM["MEM_TELMOBILE"];
					$arrayComing["MEMBER_NO"] = $rowSTM["MEMBER_NO"];
					$arrayTel[] = $arrayComing;
					$func->logSMSWasSent(null,$message_endpoint["BODY"],$arrayTel,'system');
					$updateFlagDP = $conoracle->prepare("UPDATE dpdeptstatement SET sync_notify_flag = '1' WHERE deptaccount_no = :deptaccount_no and seq_no = :seq_no");
					$updateFlagDP->execute([
						':deptaccount_no' => $rowSTM["DEPTACCOUNT_NO"],
						':seq_no' => $rowSTM["SEQ_NO"]
					]);
				}
			}else{
				$checkRights = $conmysql->prepare("SELECT is_minwithdraw,smscsp_minwithdraw,smscsp_pay_type,request_flat_date
												FROM smsconstantperson WHERE smscsp_member_no = :member_no and smscsp_account = :deptaccount_no and is_use = '1'");
				$checkRights->execute([
					':member_no' => $rowSTM["MEMBER_NO"],
					':deptaccount_no' => $rowSTM["DEPTACCOUNT_NO"]
				]);
				if($checkRights->rowCount() > 0){
					$rowRights = $checkRights->fetch(PDO::FETCH_ASSOC);
					if($rowRights["is_minwithdraw"] == '1'){
						if($rowSTM["AMOUNT"] >= $rowRights["smscsp_minwithdraw"]){
							if($rowRights["smscsp_pay_type"] == '1'){
								if($MonthNow > $rowRights["request_flat_date"]){
									$dataMerge["ITEMTYPE_DESC"] = $rowSTM["DEPTITEMTYPE_DESC"];
									$dataMerge["DEPTACCOUNT_NO"] = $lib->formataccount_hidden($rowSTM["DEPTACCOUNT_NO"],$func->getConstant('hidden_dep'));
									$dataMerge["AMOUNT"] = number_format($rowSTM["AMOUNT"],2);
									$dataMerge["DATETIME"] = $lib->convertdate($rowSTM["OPERATE_DATE"],'d m Y');
									$message_endpoint = $lib->mergeTemplate($templateMessage["SUBJECT"],$templateMessage["BODY"],$dataMerge);
									$insertSmsTran = $conmysql->prepare("INSERT INTO smstranwassent(sms_message,member_no,tel_mobile,payment_keep,deptaccount_no,send_by)
																		VALUES(:message,:member_no,:tel_mobile,'0',:deptaccount_no,'system')");
									if($insertSmsTran->execute([
										':message' => $message_endpoint["BODY"],
										':member_no' => $rowSTM["MEMBER_NO"],
										':tel_mobile' => $rowSTM["MEM_TELMOBILE"],
										':deptaccount_no' => $rowSTM["DEPTACCOUNT_NO"]
									])){
										$lastId = $conmysql->lastInsertId();
										$arrayDest["cmd_sms"] = "CMD=".$config["CMD_SMS"]."&FROM=".$config["FROM_SERVICES_SMS"]."&TO=66".(substr($rowSTM["MEM_TELMOBILE"],1,9))."&REPORT=Y&CHARGE=".$config["CHARGE_SMS"]."&CODE=".$config["CODE_SMS"]."&CTYPE=UNICODE&CONTENT=".$lib->unicodeMessageEncode($message_endpoint["BODY"]);
										$arraySendSMS = $lib->sendSMS($arrayDest);
										if($arraySendSMS["RESULT"]){
											$updateTrans = $conmysql->prepare("UPDATE smstranwassent SET smid = :smid WHERE id_smssent = :lastId");
											$updateTrans->execute([
												':smid' => $arraySendSMS["SMID"],
												':lastId' => $lastId
											]);
											$updateFlagDP = $conoracle->prepare("UPDATE dpdeptstatement SET sync_notify_flag = '1' WHERE deptaccount_no = :deptaccount_no and seq_no = :seq_no");
											$updateFlagDP->execute([
												':deptaccount_no' => $rowSTM["DEPTACCOUNT_NO"],
												':seq_no' => $rowSTM["SEQ_NO"]
											]);
										}else{
											$delTrans = $conmysql->prepare("DELETE FROM smstranwassent WHERE id_smssent = :id_smssent");
											$delTrans->execute([':id_smssent' => $lastId]);
										}
									}
								}else{
									$dataMerge["ITEMTYPE_DESC"] = $rowSTM["DEPTITEMTYPE_DESC"];
									$dataMerge["DEPTACCOUNT_NO"] = $lib->formataccount_hidden($rowSTM["DEPTACCOUNT_NO"],$func->getConstant('hidden_dep'));
									$dataMerge["AMOUNT"] = number_format($rowSTM["AMOUNT"],2);
									$dataMerge["DATETIME"] = $lib->convertdate($rowSTM["OPERATE_DATE"],'d m Y');
									$message_endpoint = $lib->mergeTemplate($templateMessage["SUBJECT"],$templateMessage["BODY"],$dataMerge);
									$insertSmsTran = $conmysql->prepare("INSERT INTO smstranwassent(sms_message,member_no,tel_mobile,deptaccount_no,send_by)
																	VALUES(:message,:member_no,:tel_mobile,:deptaccount_no,'system')");
									if($insertSmsTran->execute([
										':message' => $message_endpoint["BODY"],
										':member_no' => $rowSTM["MEMBER_NO"],
										':tel_mobile' => $rowSTM["MEM_TELMOBILE"],
										':deptaccount_no' => $rowSTM["DEPTACCOUNT_NO"]
									])){
										$lastId = $conmysql->lastInsertId();
										$arrayDest["cmd_sms"] = "CMD=".$config["CMD_SMS"]."&FROM=".$config["FROM_SERVICES_SMS"]."&TO=66".(substr($rowSTM["MEM_TELMOBILE"],1,9))."&REPORT=Y&CHARGE=".$config["CHARGE_SMS"]."&CODE=".$config["CODE_SMS"]."&CTYPE=UNICODE&CONTENT=".$lib->unicodeMessageEncode($message_endpoint["BODY"]);
										$arraySendSMS = $lib->sendSMS($arrayDest);
										if($arraySendSMS["RESULT"]){
											$updateTrans = $conmysql->prepare("UPDATE smstranwassent SET smid = :smid WHERE id_smssent = :lastId");
											$updateTrans->execute([
												':smid' => $arraySendSMS["SMID"],
												':lastId' => $lastId
											]);
											$updateFlagDP = $conoracle->prepare("UPDATE dpdeptstatement SET sync_notify_flag = '1' WHERE deptaccount_no = :deptaccount_no and seq_no = :seq_no");
											$updateFlagDP->execute([
												':deptaccount_no' => $rowSTM["DEPTACCOUNT_NO"],
												':seq_no' => $rowSTM["SEQ_NO"]
											]);
										}else{
											$delTrans = $conmysql->prepare("DELETE FROM smstranwassent WHERE id_smssent = :id_smssent");
											$delTrans->execute([':id_smssent' => $lastId]);
										}
									}
								}
							}else{
								$dataMerge["ITEMTYPE_DESC"] = $rowSTM["DEPTITEMTYPE_DESC"];
								$dataMerge["DEPTACCOUNT_NO"] = $lib->formataccount_hidden($rowSTM["DEPTACCOUNT_NO"],$func->getConstant('hidden_dep'));
								$dataMerge["AMOUNT"] = number_format($rowSTM["AMOUNT"],2);
								$dataMerge["DATETIME"] = $lib->convertdate($rowSTM["OPERATE_DATE"],'d m Y');
								$message_endpoint = $lib->mergeTemplate($templateMessage["SUBJECT"],$templateMessage["BODY"],$dataMerge);
								$insertSmsTran = $conmysql->prepare("INSERT INTO smstranwassent(sms_message,member_no,tel_mobile,deptaccount_no,send_by)
																VALUES(:message,:member_no,:tel_mobile,:deptaccount_no,'system')");
								if($insertSmsTran->execute([
									':message' => $message_endpoint["BODY"],
									':member_no' => $rowSTM["MEMBER_NO"],
									':tel_mobile' => $rowSTM["MEM_TELMOBILE"],
									':deptaccount_no' => $rowSTM["DEPTACCOUNT_NO"]
								])){
									$lastId = $conmysql->lastInsertId();
									$arrayDest["cmd_sms"] = "CMD=".$config["CMD_SMS"]."&FROM=".$config["FROM_SERVICES_SMS"]."&TO=66".(substr($rowSTM["MEM_TELMOBILE"],1,9))."&REPORT=Y&CHARGE=".$config["CHARGE_SMS"]."&CODE=".$config["CODE_SMS"]."&CTYPE=UNICODE&CONTENT=".$lib->unicodeMessageEncode($message_endpoint["BODY"]);
									$arraySendSMS = $lib->sendSMS($arrayDest);
									if($arraySendSMS["RESULT"]){
										$updateTrans = $conmysql->prepare("UPDATE smstranwassent SET smid = :smid WHERE id_smssent = :lastId");
										$updateTrans->execute([
											':smid' => $arraySendSMS["SMID"],
											':lastId' => $lastId
										]);
										$updateFlagDP = $conoracle->prepare("UPDATE dpdeptstatement SET sync_notify_flag = '1' WHERE deptaccount_no = :deptaccount_no and seq_no = :seq_no");
										$updateFlagDP->execute([
											':deptaccount_no' => $rowSTM["DEPTACCOUNT_NO"],
											':seq_no' => $rowSTM["SEQ_NO"]
										]);
									}else{
										$delTrans = $conmysql->prepare("DELETE FROM smstranwassent WHERE id_smssent = :id_smssent");
										$delTrans->execute([':id_smssent' => $lastId]);
									}
								}
							}
						}
					}
				}
			}
		}
	}
}

?>