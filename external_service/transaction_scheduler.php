<?php
ini_set('display_errors', false);
ini_set('error_log', __DIR__.'/../log/error.log');
error_reporting(E_ERROR);

require_once(__DIR__.'/../extension/vendor/autoload.php');
require_once(__DIR__.'/../include/connection.php');
require_once(__DIR__.'/../include/validate_input.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/function_util.php');

use Connection\connection;
use Utility\Library;
use Component\functions;
use ReallySimpleJWT\{Token,Parse,Jwt,Validate,Encode};
use ReallySimpleJWT\Exception\ValidateException;

$lib = new library();
$func = new functions();
$con = new connection();
$jwt_token = new Token();

$conmysql = $con->connecttomysql();
$conoracle = $con->connecttooracle();

$jsonConfig = file_get_contents(__DIR__.'/../config/config_constructor.json');
$config = json_decode($jsonConfig,true);

$getTranTaskList = $conmysql->prepare("SELECT member_no,transaction_type,from_account,destination,scheduler_type,every_date,amt_transfer,
										bank_code,id_transchedule,id_userlogin FROM gctransactionschedule WHERE scheduler_status = '0'
										and DATE_FORMAT(scheduler_date,'%Y-%m-%d') = DATE_FORMAT(NOW(),'%Y-%m-%d')");
$getTranTaskList->execute();
while($rowTaskList = $getTranTaskList->fetch(PDO::FETCH_ASSOC)){
	$conmysql->beginTransaction();
	if($rowTaskList["transaction_type"] == '1'){
		$fetchAccAllowTrans = $conmysql->prepare("SELECT gat.deptaccount_no FROM gcuserallowacctransaction gat
													LEFT JOIN gcconstantaccountdept gad ON gat.id_accountconstant = gad.id_accountconstant
													WHERE gat.deptaccount_no = :deptaccount_no and gat.is_use = '1' and (gad.allow_deposit_inside = '1' OR gad.allow_withdraw_inside = '1')");
		$fetchAccAllowTrans->execute([':deptaccount_no' => $rowTaskList["from_account"]]);
		if($fetchAccAllowTrans->rowCount() > 0){
			$arrBody = array();
			$arrBody['menu_component'] = "TransferSelfDepInsideCoop";
			$arrBody['from_deptaccount_no'] = $rowTaskList['from_account'];
			$arrBody['to_deptaccount_no'] = $rowTaskList['destination'];
			$arrBody['amt_transfer'] = $rowTaskList['amt_transfer'];
			$arrBody['penalty_amt'] = 0;
			$arrBody['channel'] = 'mobile_app';
			$arrPayloadNew = array();
			$arrPayloadNew['id_userlogin'] = $rowTaskList['id_userlogin'];
			$arrPayloadNew['member_no'] = $rowTaskList['member_no'];
			$arrPayloadNew['user_type'] = '0';
			$arrPayloadNew['exp'] = time() + intval($func->getConstant("limit_session_timeout"));
			$access_token = $jwt_token->customPayload($arrPayloadNew, $config["SECRET_KEY_JWT"]);
			$header[] = "Authorization: Bearer ".$access_token;
			$header[] = "transaction_scheduler: 1";
			$responseAPI = $lib->posting_data($config["URL_SERVICE"].'mobile_and_web-control/transferinsidecoop/fund_transfer_in_coop',$arrBody,$header);
			if($responseAPI["RESULT"]){
				$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_status = '1' WHERE id_transchedule = :id_transchedule");
				$updateFailTrans->execute([':id_transchedule' => $rowTaskList["id_transchedule"]]);
				$getMaxSeqNo = $conmysql->prepare("SELECT IFNULL(MAX(SEQ_NO),0) as M_SEQ_NO FROM gctransactionschedulestatement WHERE id_transchedule = :id_transchedule");
				$getMaxSeqNo->execute([':id_transchedule' => $rowTaskList["id_transchedule"]]);
				$rowMaxSeqNo = $getMaxSeqNo->fetch(PDO::FETCH_ASSOC);
				$insertStmScheduler = $conmysql->prepare("INSERT INTO gctransactionschedulestatement(id_transchedule,seq_no,scheduler_status) 
															VALUES(:id_transchedule,:seq_no,'1')");
				$insertStmScheduler->execute([
					':id_transchedule' => $rowTaskList["id_transchedule"],
					':seq_no' => $rowMaxSeqNo["M_SEQ_NO"] + 1
				]);
				$conmysql->commit();
			}else{
				$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_status = '-99' WHERE id_transchedule = :id_transchedule");
				$updateFailTrans->execute([':id_transchedule' => $rowTaskList["id_transchedule"]]);
				$getMaxSeqNo = $conmysql->prepare("SELECT IFNULL(MAX(SEQ_NO),0) as M_SEQ_NO FROM gctransactionschedulestatement WHERE id_transchedule = :id_transchedule");
				$getMaxSeqNo->execute([':id_transchedule' => $rowTaskList["id_transchedule"]]);
				$rowMaxSeqNo = $getMaxSeqNo->fetch(PDO::FETCH_ASSOC);
				$insertStmScheduler = $conmysql->prepare("INSERT INTO gctransactionschedulestatement(id_transchedule,seq_no,scheduler_status,scheduler_detail) 
															VALUES(:id_transchedule,:seq_no,'-99',:text_error)");
				$insertStmScheduler->execute([
					':id_transchedule' => $rowTaskList["id_transchedule"],
					':seq_no' => $rowMaxSeqNo["M_SEQ_NO"] + 1,
					':text_error' => $responseAPI["RESPONSE_MESSAGE"]
				]);
				$conmysql->commit();
			}
		}else{
			$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_status = '-99' WHERE id_transchedule = :id_transchedule");
			$updateFailTrans->execute([':id_transchedule' => $rowTaskList["id_transchedule"]]);
			$getMaxSeqNo = $conmysql->prepare("SELECT IFNULL(MAX(SEQ_NO),0) as M_SEQ_NO FROM gctransactionschedulestatement WHERE id_transchedule = :id_transchedule");
			$getMaxSeqNo->execute([':id_transchedule' => $rowTaskList["id_transchedule"]]);
			$rowMaxSeqNo = $getMaxSeqNo->fetch(PDO::FETCH_ASSOC);
			$insertStmScheduler = $conmysql->prepare("INSERT INTO gctransactionschedulestatement(id_transchedule,seq_no,scheduler_status,scheduler_detail) 
														VALUES(:id_transchedule,:seq_no,'-99','ไม่พบการผูกบัญชีหรือท่านได้ยกเลิกการผูกบัญชีไปแล้วทำให้รายการถูกยกเลิกอัตโนมัติ')");
			$insertStmScheduler->execute([
				':id_transchedule' => $rowTaskList["id_transchedule"],
				':seq_no' => $rowMaxSeqNo["M_SEQ_NO"] + 1
			]);
			$conmysql->commit();
		}
	}
	//update task status & scheduler date
	if($rowTaskList["scheduler_type"] == '1'){
		
	}else if($rowTaskList["scheduler_type"] == '2'){
		
	}
}
?>