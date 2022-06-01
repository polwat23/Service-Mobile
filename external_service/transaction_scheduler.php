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
										bank_code,id_transchedule,id_userlogin,start_date,end_date FROM gctransactionschedule WHERE scheduler_status = '0'
										and DATE_FORMAT(scheduler_date,'%Y-%m-%d') = DATE_FORMAT(NOW(),'%Y-%m-%d')");
$getTranTaskList->execute();
while($rowTaskList = $getTranTaskList->fetch(PDO::FETCH_ASSOC)){
	$conmysql->beginTransaction();
	if($rowTaskList["scheduler_type"] == '1'){
		if($rowTaskList["transaction_type"] == '1'){
			$fetchAccAllowTrans = $conmysql->prepare("SELECT gat.deptaccount_no FROM gcuserallowacctransaction gat
														LEFT JOIN gcconstantaccountdept gad ON gat.id_accountconstant = gad.id_accountconstant
														WHERE gat.deptaccount_no = :deptaccount_no and gat.is_use = '1' and (gad.allow_deposit_inside = '1' OR gad.allow_withdraw_inside = '1')");
			$fetchAccAllowTrans->execute([':deptaccount_no' => $rowTaskList["from_account"]]);
			if($fetchAccAllowTrans->rowCount() > 0){
				$checkSelfTransfer = $conoracle->prepare("SELECT MEMBER_NO FROM dpdeptmaster WHERE deptaccount_no = :deptaccount_no");
				$checkSelfTransfer->execute([':deptaccount_no' => $rowTaskList['destination']]);
				$rowSelfTran = $checkSelfTransfer->fetch(PDO::FETCH_ASSOC);
				$arrBodyInq = array();
				if($rowSelfTran["MEMBER_NO"] == $rowTaskList['member_no']){
					$arrBodyInq['menu_component'] = "TransferSelfDepInsideCoop";
				}else{
					$arrBodyInq['menu_component'] = "TransferDepInsideCoop";
				}
				$arrBodyInq['deptaccount_no'] = $rowTaskList['from_account'];
				$arrBodyInq['amt_transfer'] = $rowTaskList['amt_transfer'];
				$arrBodyInq['channel'] = 'mobile_app';
				$arrPayloadNew = array();
				$arrPayloadNew['id_userlogin'] = $rowTaskList['id_userlogin'];
				$arrPayloadNew['member_no'] = $rowTaskList['member_no'];
				$arrPayloadNew['user_type'] = '0';
				$arrPayloadNew['exp'] = time() + intval($func->getConstant("limit_session_timeout"));
				$access_token = $jwt_token->customPayload($arrPayloadNew, $config["SECRET_KEY_JWT"]);
				$headerInq[] = "Authorization: Bearer ".$access_token;
				$headerInq[] = "transaction_scheduler: 1";
				$responseAPIInq = $lib->posting_data($config["URL_SERVICE"].'mobile_and_web-control/transferinsidecoop/get_fee_fund_transfer',$arrBodyInq,$headerInq);
				$arrResponseAPIInq = json_decode($responseAPIInq);
				if($arrResponseAPIInq->RESULT){
					$arrBody = array();
					if($rowSelfTran["MEMBER_NO"] == $rowTaskList['member_no']){
						$arrBody['menu_component'] = "TransferSelfDepInsideCoop";
					}else{
						$arrBody['menu_component'] = "TransferDepInsideCoop";
					}
					$arrBody['from_deptaccount_no'] = $rowTaskList['from_account'];
					$arrBody['to_deptaccount_no'] = $rowTaskList['destination'];
					$arrBody['amt_transfer'] = $rowTaskList['amt_transfer'];
					$arrBody['penalty_amt'] = $arrResponseAPIInq->PENALTY_AMT;
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
					$arrResponseAPI = json_decode($responseAPI);
					if($arrResponseAPI->RESULT){
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
							':text_error' => $arrResponseAPI->RESPONSE_MESSAGE
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
																VALUES(:id_transchedule,:seq_no,'-99',:text_error)");
					$insertStmScheduler->execute([
						':id_transchedule' => $rowTaskList["id_transchedule"],
						':seq_no' => $rowMaxSeqNo["M_SEQ_NO"] + 1,
						':text_error' => $arrResponseAPIInq->RESPONSE_MESSAGE
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
		}else if($rowTaskList["transaction_type"] == '2'){
			if(isset($rowTaskList["bank_code"])){
				$fetchAccountBeenBind = $conmysql->prepare("SELECT sigma_key,deptaccount_no_coop,deptaccount_no_bank
															FROM gcbindaccount
															WHERE id_bindaccount = :id_bindaccount and bindaccount_status = '1'");
				$fetchAccountBeenBind->execute([
					':id_bindaccount' => $rowTaskList["from_account"]
				]);
				if($fetchAccountBeenBind->rowCount() > 0){
					$rowFetchAcc = $fetchAccountBeenBind->fetch(PDO::FETCH_ASSOC);
					$arrBodyInq = array();
					$arrBodyInq['menu_component'] = "TransferDepPayLoan";
					$arrBodyInq['sigma_key'] = $rowFetchAcc['sigma_key'];
					$arrBodyInq['amt_transfer'] = $rowTaskList['amt_transfer'];
					$arrBodyInq['channel'] = 'mobile_app';
					$arrBodyInq['loancontract_no'] = $rowTaskList['destination'];
					$arrPayloadNew = array();
					$arrPayloadNew['id_userlogin'] = $rowTaskList['id_userlogin'];
					$arrPayloadNew['member_no'] = $rowTaskList['member_no'];
					$arrPayloadNew['user_type'] = '0';
					$arrPayloadNew['exp'] = time() + intval($func->getConstant("limit_session_timeout"));
					$access_token = $jwt_token->customPayload($arrPayloadNew, $config["SECRET_KEY_JWT"]);
					$headerInq[] = "Authorization: Bearer ".$access_token;
					$headerInq[] = "transaction_scheduler: 1";
					$responseAPIInq = $lib->posting_data($config["URL_SERVICE"].'mobile_and_web-control/repayloan/confirm_repay_loan',$arrBodyInq,$headerInq);
					$arrResponseAPIInq = json_decode($responseAPIInq);
					if($arrResponseAPIInq->RESULT){
						$arrBody = array();
						$arrBody['menu_component'] = "TransferDepPayLoan";
						$arrBody['deptaccount_no_bank'] = $rowFetchAcc['deptaccount_no_bank'];
						$arrBody['contract_no'] = $rowTaskList['destination'];
						$arrBody['sigma_key'] = $rowFetchAcc['sigma_key'];
						$arrBody['amt_transfer'] = $rowTaskList['amt_transfer'];
						$arrBody['fee_amt'] = $arrResponseAPIInq->FEE_AMT ?? 0;
						if($rowTaskList["bank_code"] == '025'){
							$arrBody['ETN_REFNO'] = $arrResponseAPIInq->ETN_REFNO;
							$arrBody['SOURCE_REFNO'] = $arrResponseAPIInq->SOURCE_REFNO;
						}
						$arrBody['channel'] = 'mobile_app';
						$arrPayloadNew = array();
						$arrPayloadNew['id_userlogin'] = $rowTaskList['id_userlogin'];
						$arrPayloadNew['member_no'] = $rowTaskList['member_no'];
						$arrPayloadNew['user_type'] = '0';
						$arrPayloadNew['exp'] = time() + intval($func->getConstant("limit_session_timeout"));
						$access_token = $jwt_token->customPayload($arrPayloadNew, $config["SECRET_KEY_JWT"]);
						$header[] = "Authorization: Bearer ".$access_token;
						$header[] = "transaction_scheduler: 1";
						$responseAPI = $lib->posting_data($config["URL_SERVICE"].'mobile_and_web-control/repayloan/request_payment_loan_bank',$arrBody,$header);
						$arrResponseAPI = json_decode($responseAPI);
						if($arrResponseAPI->RESULT){
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
								':text_error' => $arrResponseAPI->RESPONSE_MESSAGE
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
																	VALUES(:id_transchedule,:seq_no,'-99',:text_error)");
						$insertStmScheduler->execute([
							':id_transchedule' => $rowTaskList["id_transchedule"],
							':seq_no' => $rowMaxSeqNo["M_SEQ_NO"] + 1,
							':text_error' => $arrResponseAPIInq->RESPONSE_MESSAGE
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
			}else{
				$fetchAccAllowTrans = $conmysql->prepare("SELECT gat.deptaccount_no FROM gcuserallowacctransaction gat
															LEFT JOIN gcconstantaccountdept gad ON gat.id_accountconstant = gad.id_accountconstant
															WHERE gat.deptaccount_no = :deptaccount_no and gat.is_use = '1' and gad.allow_pay_loan = '1'");
				$fetchAccAllowTrans->execute([':deptaccount_no' => $rowTaskList["from_account"]]);
				if($fetchAccAllowTrans->rowCount() > 0){
					$arrBodyInq = array();
					$arrBodyInq['menu_component'] = "TransferDepPayLoan";
					$arrBodyInq['deptaccount_no'] = $rowTaskList['from_account'];
					$arrBodyInq['amt_transfer'] = $rowTaskList['amt_transfer'];
					$arrBodyInq['channel'] = 'mobile_app';
					$arrBodyInq['loancontract_no'] = $rowTaskList['destination'];
					$arrPayloadNew = array();
					$arrPayloadNew['id_userlogin'] = $rowTaskList['id_userlogin'];
					$arrPayloadNew['member_no'] = $rowTaskList['member_no'];
					$arrPayloadNew['user_type'] = '0';
					$arrPayloadNew['exp'] = time() + intval($func->getConstant("limit_session_timeout"));
					$access_token = $jwt_token->customPayload($arrPayloadNew, $config["SECRET_KEY_JWT"]);
					$headerInq[] = "Authorization: Bearer ".$access_token;
					$headerInq[] = "transaction_scheduler: 1";
					$responseAPIInq = $lib->posting_data($config["URL_SERVICE"].'mobile_and_web-control/repayloan/confirm_repay_loan',$arrBodyInq,$headerInq);
					$arrResponseAPIInq = json_decode($responseAPIInq);
					if($arrResponseAPIInq->RESULT){
						$arrBody = array();
						$arrBody['menu_component'] = "TransferDepPayLoan";
						$arrBody['deptaccount_no'] = $rowTaskList['from_account'];
						$arrBody['contract_no'] = $rowTaskList['destination'];
						$arrBody['amt_transfer'] = $rowTaskList['amt_transfer'];
						$arrBody['penalty_amt'] = $arrResponseAPIInq->PENALTY_AMT;
						$arrBody['channel'] = 'mobile_app';
						$arrPayloadNew = array();
						$arrPayloadNew['id_userlogin'] = $rowTaskList['id_userlogin'];
						$arrPayloadNew['member_no'] = $rowTaskList['member_no'];
						$arrPayloadNew['user_type'] = '0';
						$arrPayloadNew['exp'] = time() + intval($func->getConstant("limit_session_timeout"));
						$access_token = $jwt_token->customPayload($arrPayloadNew, $config["SECRET_KEY_JWT"]);
						$header[] = "Authorization: Bearer ".$access_token;
						$header[] = "transaction_scheduler: 1";
						$responseAPI = $lib->posting_data($config["URL_SERVICE"].'mobile_and_web-control/repayloan/request_payment_loan',$arrBody,$header);
						$arrResponseAPI = json_decode($responseAPI);
						if($arrResponseAPI->RESULT){
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
								':text_error' => $arrResponseAPI->RESPONSE_MESSAGE
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
																	VALUES(:id_transchedule,:seq_no,'-99',:text_error)");
						$insertStmScheduler->execute([
							':id_transchedule' => $rowTaskList["id_transchedule"],
							':seq_no' => $rowMaxSeqNo["M_SEQ_NO"] + 1,
							':text_error' => $arrResponseAPIInq->RESPONSE_MESSAGE
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
		}else if($rowTaskList["transaction_type"] == '4'){
			$fetchAccountBeenBind = $conmysql->prepare("SELECT sigma_key,deptaccount_no_coop
														FROM gcbindaccount
														WHERE id_bindaccount = :id_bindaccount and bindaccount_status = '1'");
			$fetchAccountBeenBind->execute([
				':id_bindaccount' => $rowTaskList["from_account"]
			]);
			if($fetchAccountBeenBind->rowCount() > 0){
				$rowFetchAcc = $fetchAccountBeenBind->fetch(PDO::FETCH_ASSOC);
				$fetchAccAllowTrans = $conmysql->prepare("SELECT gat.deptaccount_no FROM gcuserallowacctransaction gat
															LEFT JOIN gcconstantaccountdept gad ON gat.id_accountconstant = gad.id_accountconstant
															WHERE gat.deptaccount_no = :deptaccount_no and gat.is_use = '1' and gad.allow_deposit_outside = '1'");
				$fetchAccAllowTrans->execute([':deptaccount_no' => $rowFetchAcc["deptaccount_no_coop"]]);
				if($fetchAccAllowTrans->rowCount() > 0){
					$arrBodyInq = array();
					$arrBodyInq['menu_component'] = "TransactionDeposit";
					$arrBodyInq['sigma_key'] = $rowFetchAcc['sigma_key'];
					$arrBodyInq['deptaccount_no'] = $rowFetchAcc['deptaccount_no_coop'];
					$arrBodyInq['amt_transfer'] = $rowTaskList['amt_transfer'];
					$arrBodyInq['channel'] = 'mobile_app';
					$arrPayloadNew = array();
					$arrPayloadNew['id_userlogin'] = $rowTaskList['id_userlogin'];
					$arrPayloadNew['member_no'] = $rowTaskList['member_no'];
					$arrPayloadNew['user_type'] = '0';
					$arrPayloadNew['exp'] = time() + intval($func->getConstant("limit_session_timeout"));
					$access_token = $jwt_token->customPayload($arrPayloadNew, $config["SECRET_KEY_JWT"]);
					$headerInq[] = "Authorization: Bearer ".$access_token;
					$headerInq[] = "transaction_scheduler: 1";
					$responseAPIInq = $lib->posting_data($config["URL_SERVICE"].'mobile_and_web-control/depositfundtransfer/request_verify_data',$arrBodyInq,$headerInq);
					$arrResponseAPIInq = json_decode($responseAPIInq);
					if($arrResponseAPIInq->RESULT){
						$arrBody = array();
						$arrBody['menu_component'] = "TransactionDeposit";
						$arrBody['coop_account_no'] = $rowFetchAcc['deptaccount_no_coop'];
						$arrBody['sigma_key'] = $rowFetchAcc['sigma_key'];
						$arrBody['amt_transfer'] = $rowTaskList['amt_transfer'];
						$arrBody['fee_amt'] = $arrResponseAPIInq->FEE_AMT;
						if($rowTaskList["bank_code"] == '025'){
							$arrBody['ETN_REFNO'] = $arrResponseAPIInq->ETN_REFNO;
							$arrBody['SOURCE_REFNO'] = $arrResponseAPIInq->SOURCE_REFNO;
						}
						$arrBody['channel'] = 'mobile_app';
						$arrPayloadNew = array();
						$arrPayloadNew['id_userlogin'] = $rowTaskList['id_userlogin'];
						$arrPayloadNew['member_no'] = $rowTaskList['member_no'];
						$arrPayloadNew['user_type'] = '0';
						$arrPayloadNew['exp'] = time() + intval($func->getConstant("limit_session_timeout"));
						$access_token = $jwt_token->customPayload($arrPayloadNew, $config["SECRET_KEY_JWT"]);
						$header[] = "Authorization: Bearer ".$access_token;
						$header[] = "transaction_scheduler: 1";
						$responseAPI = $lib->posting_data($config["URL_SERVICE"].'mobile_and_web-control/depositfundtransfer/request_deposit_bank_payment',$arrBody,$header);
						$arrResponseAPI = json_decode($responseAPI);
						if($arrResponseAPI->RESULT){
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
								':text_error' => $arrResponseAPI->RESPONSE_MESSAGE
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
																	VALUES(:id_transchedule,:seq_no,'-99',:text_error)");
						$insertStmScheduler->execute([
							':id_transchedule' => $rowTaskList["id_transchedule"],
							':seq_no' => $rowMaxSeqNo["M_SEQ_NO"] + 1,
							':text_error' => $arrResponseAPIInq->RESPONSE_MESSAGE
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
		}else if($rowTaskList["transaction_type"] == '5'){
			$fetchAccountBeenBind = $conmysql->prepare("SELECT sigma_key,deptaccount_no_coop,deptaccount_no_bank
														FROM gcbindaccount
														WHERE id_bindaccount = :id_bindaccount and bindaccount_status = '1'");
			$fetchAccountBeenBind->execute([
				':id_bindaccount' => $rowTaskList["destination"]
			]);
			if($fetchAccountBeenBind->rowCount() > 0){
				$rowFetchAcc = $fetchAccountBeenBind->fetch(PDO::FETCH_ASSOC);
				$fetchAccAllowTrans = $conmysql->prepare("SELECT gat.deptaccount_no FROM gcuserallowacctransaction gat
															LEFT JOIN gcconstantaccountdept gad ON gat.id_accountconstant = gad.id_accountconstant
															WHERE gat.deptaccount_no = :deptaccount_no and gat.is_use = '1' and gad.allow_withdraw_outside = '1'");
				$fetchAccAllowTrans->execute([':deptaccount_no' => $rowFetchAcc["deptaccount_no_coop"]]);
				if($fetchAccAllowTrans->rowCount() > 0){
					$arrBodyInq = array();
					$arrBodyInq['menu_component'] = "TransactionWithdrawDeposit";
					$arrBodyInq['sigma_key'] = $rowFetchAcc['sigma_key'];
					$arrBodyInq['bank_account_no'] = $rowFetchAcc['deptaccount_no_bank'];
					$arrBodyInq['deptaccount_no'] = $rowFetchAcc['deptaccount_no_coop'];
					$arrBodyInq['amt_transfer'] = $rowTaskList['amt_transfer'];
					$arrBodyInq['channel'] = 'mobile_app';
					$arrPayloadNew = array();
					$arrPayloadNew['id_userlogin'] = $rowTaskList['id_userlogin'];
					$arrPayloadNew['member_no'] = $rowTaskList['member_no'];
					$arrPayloadNew['user_type'] = '0';
					$arrPayloadNew['exp'] = time() + intval($func->getConstant("limit_session_timeout"));
					$access_token = $jwt_token->customPayload($arrPayloadNew, $config["SECRET_KEY_JWT"]);
					$headerInq[] = "Authorization: Bearer ".$access_token;
					$headerInq[] = "transaction_scheduler: 1";
					$responseAPIInq = $lib->posting_data($config["URL_SERVICE"].'mobile_and_web-control/withdrawfundtransfer/request_verify_data',$arrBodyInq,$headerInq);
					$arrResponseAPIInq = json_decode($responseAPIInq);
					if($arrResponseAPIInq->RESULT){
						$arrBody = array();
						$arrBody['menu_component'] = "TransactionWithdrawDeposit";
						$arrBody['coop_account_no'] = $rowFetchAcc['deptaccount_no_coop'];
						$arrBody['sigma_key'] = $rowFetchAcc['sigma_key'];
						$arrBody['amt_transfer'] = $rowTaskList['amt_transfer'];
						$arrBody['fee_amt'] = $arrResponseAPIInq->FEE_AMT ?? 0;
						$arrBody['penalty_amt'] = $arrResponseAPIInq->PENALTY_AMT ?? 0;
						if($rowTaskList["bank_code"] == '025'){
							$arrBody['ETN_REFNO'] = $arrResponseAPIInq->ETN_REFNO;
							$arrBody['SOURCE_REFNO'] = $arrResponseAPIInq->SOURCE_REFNO;
						}
						$arrBody['channel'] = 'mobile_app';
						$arrPayloadNew = array();
						$arrPayloadNew['id_userlogin'] = $rowTaskList['id_userlogin'];
						$arrPayloadNew['member_no'] = $rowTaskList['member_no'];
						$arrPayloadNew['user_type'] = '0';
						$arrPayloadNew['exp'] = time() + intval($func->getConstant("limit_session_timeout"));
						$access_token = $jwt_token->customPayload($arrPayloadNew, $config["SECRET_KEY_JWT"]);
						$header[] = "Authorization: Bearer ".$access_token;
						$header[] = "transaction_scheduler: 1";
						$responseAPI = $lib->posting_data($config["URL_SERVICE"].'mobile_and_web-control/withdrawfundtransfer/request_withdraw_bank_payment',$arrBody,$header);
						$arrResponseAPI = json_decode($responseAPI);
						if($arrResponseAPI->RESULT){
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
								':text_error' => $arrResponseAPI->RESPONSE_MESSAGE
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
																	VALUES(:id_transchedule,:seq_no,'-99',:text_error)");
						$insertStmScheduler->execute([
							':id_transchedule' => $rowTaskList["id_transchedule"],
							':seq_no' => $rowMaxSeqNo["M_SEQ_NO"] + 1,
							':text_error' => $arrResponseAPIInq->RESPONSE_MESSAGE
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
	}else if($rowTaskList["scheduler_type"] == '2'){ //update task status & scheduler date
		$start_date = $rowTaskList["start_date"];
		$every = intval($rowTaskList["every_date"]);
		$scheduler_date = null;
		//วันที่เริ่มต้น > every_date รายการจะเริ่มเดือนถัดไป
		$last_day_of_next_month = date('Y-m-d', strtotime('last day of next month'));
		if($every <= date('t', strtotime($last_day_of_next_month))) {
			//วันที่ของเดือนมีจริง
			$scheduler_date = date('Y-m-d', strtotime(date('Y-m', strtotime($last_day_of_next_month)).'-'.$every));
		}else {
			//วันที่ไม่มีจริง ปัดเป็นวันสุดท้ายของเดือน
			$scheduler_date = $last_day_of_next_month;
		}
		$end_date = $rowTaskList["end_date"] ?? null;
		
		if(!empty($end_date) && (strtotime($end_date) < strtotime($scheduler_date))){
			//ถ้าวันที่สินสุดน้อยกว่าวันที่รอทำรายการให้ยกเลิกรายการ
			$expire_scheduler = TRUE;
		}
		if($rowTaskList["transaction_type"] == '1'){
			$fetchAccAllowTrans = $conmysql->prepare("SELECT gat.deptaccount_no FROM gcuserallowacctransaction gat
														LEFT JOIN gcconstantaccountdept gad ON gat.id_accountconstant = gad.id_accountconstant
														WHERE gat.deptaccount_no = :deptaccount_no and gat.is_use = '1' and (gad.allow_deposit_inside = '1' OR gad.allow_withdraw_inside = '1')");
			$fetchAccAllowTrans->execute([':deptaccount_no' => $rowTaskList["from_account"]]);
			if($fetchAccAllowTrans->rowCount() > 0){
				$checkSelfTransfer = $conoracle->prepare("SELECT MEMBER_NO FROM dpdeptmaster WHERE deptaccount_no = :deptaccount_no");
				$checkSelfTransfer->execute([':deptaccount_no' => $rowTaskList['destination']]);
				$rowSelfTran = $checkSelfTransfer->fetch(PDO::FETCH_ASSOC);
				$arrBodyInq = array();
				if($rowSelfTran["MEMBER_NO"] == $rowTaskList['member_no']){
					$arrBodyInq['menu_component'] = "TransferSelfDepInsideCoop";
				}else{
					$arrBodyInq['menu_component'] = "TransferDepInsideCoop";
				}
				$arrBodyInq['deptaccount_no'] = $rowTaskList['from_account'];
				$arrBodyInq['amt_transfer'] = $rowTaskList['amt_transfer'];
				$arrBodyInq['channel'] = 'mobile_app';
				$arrPayloadNew = array();
				$arrPayloadNew['id_userlogin'] = $rowTaskList['id_userlogin'];
				$arrPayloadNew['member_no'] = $rowTaskList['member_no'];
				$arrPayloadNew['user_type'] = '0';
				$arrPayloadNew['exp'] = time() + intval($func->getConstant("limit_session_timeout"));
				$access_token = $jwt_token->customPayload($arrPayloadNew, $config["SECRET_KEY_JWT"]);
				$headerInq[] = "Authorization: Bearer ".$access_token;
				$headerInq[] = "transaction_scheduler: 1";
				$responseAPIInq = $lib->posting_data($config["URL_SERVICE"].'mobile_and_web-control/transferinsidecoop/get_fee_fund_transfer',$arrBodyInq,$headerInq);
				$arrResponseAPIInq = json_decode($responseAPIInq);
				if($arrResponseAPIInq->RESULT){
					$arrBody = array();
					if($rowSelfTran["MEMBER_NO"] == $rowTaskList['member_no']){
						$arrBody['menu_component'] = "TransferSelfDepInsideCoop";
					}else{
						$arrBody['menu_component'] = "TransferDepInsideCoop";
					}
					$arrBody['from_deptaccount_no'] = $rowTaskList['from_account'];
					$arrBody['to_deptaccount_no'] = $rowTaskList['destination'];
					$arrBody['amt_transfer'] = $rowTaskList['amt_transfer'];
					$arrBody['penalty_amt'] = $arrResponseAPIInq->PENALTY_AMT;
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
					$arrResponseAPI = json_decode($responseAPI);
					if($arrResponseAPI->RESULT){
						if($expire_scheduler){
							$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_status = '1' WHERE id_transchedule = :id_transchedule");
							$updateFailTrans->execute([':id_transchedule' => $rowTaskList["id_transchedule"]]);
						}else{
							$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_date = :scheduler_date WHERE id_transchedule = :id_transchedule");
							$updateFailTrans->execute([
								':scheduler_date' => $scheduler_date,
								':id_transchedule' => $rowTaskList["id_transchedule"]
							]);
						}
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
						if($expire_scheduler){
							$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_status = '-99' WHERE id_transchedule = :id_transchedule");
							$updateFailTrans->execute([':id_transchedule' => $rowTaskList["id_transchedule"]]);
						}else{
							$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_date = :scheduler_date WHERE id_transchedule = :id_transchedule");
							$updateFailTrans->execute([
								':scheduler_date' => $scheduler_date,
								':id_transchedule' => $rowTaskList["id_transchedule"]
							]);
						}
						$getMaxSeqNo = $conmysql->prepare("SELECT IFNULL(MAX(SEQ_NO),0) as M_SEQ_NO FROM gctransactionschedulestatement WHERE id_transchedule = :id_transchedule");
						$getMaxSeqNo->execute([':id_transchedule' => $rowTaskList["id_transchedule"]]);
						$rowMaxSeqNo = $getMaxSeqNo->fetch(PDO::FETCH_ASSOC);
						$insertStmScheduler = $conmysql->prepare("INSERT INTO gctransactionschedulestatement(id_transchedule,seq_no,scheduler_status,scheduler_detail) 
																	VALUES(:id_transchedule,:seq_no,'-99',:text_error)");
						$insertStmScheduler->execute([
							':id_transchedule' => $rowTaskList["id_transchedule"],
							':seq_no' => $rowMaxSeqNo["M_SEQ_NO"] + 1,
							':text_error' => $arrResponseAPI->RESPONSE_MESSAGE
						]);
						$conmysql->commit();
					}
				}else{
					if($expire_scheduler){
						$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_status = '-99' WHERE id_transchedule = :id_transchedule");
						$updateFailTrans->execute([':id_transchedule' => $rowTaskList["id_transchedule"]]);
					}else{
						$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_date = :scheduler_date WHERE id_transchedule = :id_transchedule");
						$updateFailTrans->execute([
							':scheduler_date' => $scheduler_date,
							':id_transchedule' => $rowTaskList["id_transchedule"]
						]);
					}
					$getMaxSeqNo = $conmysql->prepare("SELECT IFNULL(MAX(SEQ_NO),0) as M_SEQ_NO FROM gctransactionschedulestatement WHERE id_transchedule = :id_transchedule");
					$getMaxSeqNo->execute([':id_transchedule' => $rowTaskList["id_transchedule"]]);
					$rowMaxSeqNo = $getMaxSeqNo->fetch(PDO::FETCH_ASSOC);
					$insertStmScheduler = $conmysql->prepare("INSERT INTO gctransactionschedulestatement(id_transchedule,seq_no,scheduler_status,scheduler_detail) 
																VALUES(:id_transchedule,:seq_no,'-99',:text_error)");
					$insertStmScheduler->execute([
						':id_transchedule' => $rowTaskList["id_transchedule"],
						':seq_no' => $rowMaxSeqNo["M_SEQ_NO"] + 1,
						':text_error' => $arrResponseAPIInq->RESPONSE_MESSAGE
					]);
					$conmysql->commit();
				}
			}else{
				if($expire_scheduler){
					$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_status = '-99' WHERE id_transchedule = :id_transchedule");
					$updateFailTrans->execute([':id_transchedule' => $rowTaskList["id_transchedule"]]);
				}else{
					$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_date = :scheduler_date WHERE id_transchedule = :id_transchedule");
					$updateFailTrans->execute([
						':scheduler_date' => $scheduler_date,
						':id_transchedule' => $rowTaskList["id_transchedule"]
					]);
				}
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
		}else if($rowTaskList["transaction_type"] == '2'){
			if(isset($rowTaskList["bank_code"])){
				$fetchAccountBeenBind = $conmysql->prepare("SELECT sigma_key,deptaccount_no_coop,deptaccount_no_bank
															FROM gcbindaccount
															WHERE id_bindaccount = :id_bindaccount and bindaccount_status = '1'");
				$fetchAccountBeenBind->execute([
					':id_bindaccount' => $rowTaskList["from_account"]
				]);
				if($fetchAccountBeenBind->rowCount() > 0){
					$rowFetchAcc = $fetchAccountBeenBind->fetch(PDO::FETCH_ASSOC);
					$arrBodyInq = array();
					$arrBodyInq['menu_component'] = "TransferDepPayLoan";
					$arrBodyInq['sigma_key'] = $rowFetchAcc['sigma_key'];
					$arrBodyInq['amt_transfer'] = $rowTaskList['amt_transfer'];
					$arrBodyInq['channel'] = 'mobile_app';
					$arrBodyInq['loancontract_no'] = $rowTaskList['destination'];
					$arrPayloadNew = array();
					$arrPayloadNew['id_userlogin'] = $rowTaskList['id_userlogin'];
					$arrPayloadNew['member_no'] = $rowTaskList['member_no'];
					$arrPayloadNew['user_type'] = '0';
					$arrPayloadNew['exp'] = time() + intval($func->getConstant("limit_session_timeout"));
					$access_token = $jwt_token->customPayload($arrPayloadNew, $config["SECRET_KEY_JWT"]);
					$headerInq[] = "Authorization: Bearer ".$access_token;
					$headerInq[] = "transaction_scheduler: 1";
					$responseAPIInq = $lib->posting_data($config["URL_SERVICE"].'mobile_and_web-control/repayloan/confirm_repay_loan',$arrBodyInq,$headerInq);
					$arrResponseAPIInq = json_decode($responseAPIInq);
					if($arrResponseAPIInq->RESULT){
						$arrBody = array();
						$arrBody['menu_component'] = "TransferDepPayLoan";
						$arrBody['deptaccount_no_bank'] = $rowFetchAcc['deptaccount_no_bank'];
						$arrBody['contract_no'] = $rowTaskList['destination'];
						$arrBody['sigma_key'] = $rowFetchAcc['sigma_key'];
						$arrBody['amt_transfer'] = $rowTaskList['amt_transfer'];
						$arrBody['fee_amt'] = $arrResponseAPIInq->FEE_AMT ?? 0;
						if($rowTaskList["bank_code"] == '025'){
							$arrBody['ETN_REFNO'] = $arrResponseAPIInq->ETN_REFNO;
							$arrBody['SOURCE_REFNO'] = $arrResponseAPIInq->SOURCE_REFNO;
						}
						$arrBody['channel'] = 'mobile_app';
						$arrPayloadNew = array();
						$arrPayloadNew['id_userlogin'] = $rowTaskList['id_userlogin'];
						$arrPayloadNew['member_no'] = $rowTaskList['member_no'];
						$arrPayloadNew['user_type'] = '0';
						$arrPayloadNew['exp'] = time() + intval($func->getConstant("limit_session_timeout"));
						$access_token = $jwt_token->customPayload($arrPayloadNew, $config["SECRET_KEY_JWT"]);
						$header[] = "Authorization: Bearer ".$access_token;
						$header[] = "transaction_scheduler: 1";
						$responseAPI = $lib->posting_data($config["URL_SERVICE"].'mobile_and_web-control/repayloan/request_payment_loan_bank',$arrBody,$header);
						$arrResponseAPI = json_decode($responseAPI);
						if($arrResponseAPI->RESULT){
							if($expire_scheduler){
								$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_status = '1' WHERE id_transchedule = :id_transchedule");
								$updateFailTrans->execute([':id_transchedule' => $rowTaskList["id_transchedule"]]);
							}else{
								$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_date = :scheduler_date WHERE id_transchedule = :id_transchedule");
								$updateFailTrans->execute([
									':scheduler_date' => $scheduler_date,
									':id_transchedule' => $rowTaskList["id_transchedule"]
								]);
							}
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
							if($expire_scheduler){
								$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_status = '-99' WHERE id_transchedule = :id_transchedule");
								$updateFailTrans->execute([':id_transchedule' => $rowTaskList["id_transchedule"]]);
							}else{
								$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_date = :scheduler_date WHERE id_transchedule = :id_transchedule");
								$updateFailTrans->execute([
									':scheduler_date' => $scheduler_date,
									':id_transchedule' => $rowTaskList["id_transchedule"]
								]);
							}
							$getMaxSeqNo = $conmysql->prepare("SELECT IFNULL(MAX(SEQ_NO),0) as M_SEQ_NO FROM gctransactionschedulestatement WHERE id_transchedule = :id_transchedule");
							$getMaxSeqNo->execute([':id_transchedule' => $rowTaskList["id_transchedule"]]);
							$rowMaxSeqNo = $getMaxSeqNo->fetch(PDO::FETCH_ASSOC);
							$insertStmScheduler = $conmysql->prepare("INSERT INTO gctransactionschedulestatement(id_transchedule,seq_no,scheduler_status,scheduler_detail) 
																		VALUES(:id_transchedule,:seq_no,'-99',:text_error)");
							$insertStmScheduler->execute([
								':id_transchedule' => $rowTaskList["id_transchedule"],
								':seq_no' => $rowMaxSeqNo["M_SEQ_NO"] + 1,
								':text_error' => $arrResponseAPI->RESPONSE_MESSAGE
							]);
							$conmysql->commit();
						}
					}else{
						if($expire_scheduler){
							$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_status = '-99' WHERE id_transchedule = :id_transchedule");
							$updateFailTrans->execute([':id_transchedule' => $rowTaskList["id_transchedule"]]);
						}else{
							$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_date = :scheduler_date WHERE id_transchedule = :id_transchedule");
							$updateFailTrans->execute([
								':scheduler_date' => $scheduler_date,
								':id_transchedule' => $rowTaskList["id_transchedule"]
							]);
						}
						$getMaxSeqNo = $conmysql->prepare("SELECT IFNULL(MAX(SEQ_NO),0) as M_SEQ_NO FROM gctransactionschedulestatement WHERE id_transchedule = :id_transchedule");
						$getMaxSeqNo->execute([':id_transchedule' => $rowTaskList["id_transchedule"]]);
						$rowMaxSeqNo = $getMaxSeqNo->fetch(PDO::FETCH_ASSOC);
						$insertStmScheduler = $conmysql->prepare("INSERT INTO gctransactionschedulestatement(id_transchedule,seq_no,scheduler_status,scheduler_detail) 
																	VALUES(:id_transchedule,:seq_no,'-99',:text_error)");
						$insertStmScheduler->execute([
							':id_transchedule' => $rowTaskList["id_transchedule"],
							':seq_no' => $rowMaxSeqNo["M_SEQ_NO"] + 1,
							':text_error' => $arrResponseAPIInq->RESPONSE_MESSAGE
						]);
						$conmysql->commit();
					}
				}else{
					if($expire_scheduler){
						$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_status = '-99' WHERE id_transchedule = :id_transchedule");
						$updateFailTrans->execute([':id_transchedule' => $rowTaskList["id_transchedule"]]);
					}else{
						$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_date = :scheduler_date WHERE id_transchedule = :id_transchedule");
						$updateFailTrans->execute([
							':scheduler_date' => $scheduler_date,
							':id_transchedule' => $rowTaskList["id_transchedule"]
						]);
					}
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
			}else{
				$fetchAccAllowTrans = $conmysql->prepare("SELECT gat.deptaccount_no FROM gcuserallowacctransaction gat
															LEFT JOIN gcconstantaccountdept gad ON gat.id_accountconstant = gad.id_accountconstant
															WHERE gat.deptaccount_no = :deptaccount_no and gat.is_use = '1' and gad.allow_pay_loan = '1'");
				$fetchAccAllowTrans->execute([':deptaccount_no' => $rowTaskList["from_account"]]);
				if($fetchAccAllowTrans->rowCount() > 0){
					$arrBodyInq = array();
					$arrBodyInq['menu_component'] = "TransferDepPayLoan";
					$arrBodyInq['deptaccount_no'] = $rowTaskList['from_account'];
					$arrBodyInq['amt_transfer'] = $rowTaskList['amt_transfer'];
					$arrBodyInq['channel'] = 'mobile_app';
					$arrBodyInq['loancontract_no'] = $rowTaskList['destination'];
					$arrPayloadNew = array();
					$arrPayloadNew['id_userlogin'] = $rowTaskList['id_userlogin'];
					$arrPayloadNew['member_no'] = $rowTaskList['member_no'];
					$arrPayloadNew['user_type'] = '0';
					$arrPayloadNew['exp'] = time() + intval($func->getConstant("limit_session_timeout"));
					$access_token = $jwt_token->customPayload($arrPayloadNew, $config["SECRET_KEY_JWT"]);
					$headerInq[] = "Authorization: Bearer ".$access_token;
					$headerInq[] = "transaction_scheduler: 1";
					$responseAPIInq = $lib->posting_data($config["URL_SERVICE"].'mobile_and_web-control/repayloan/confirm_repay_loan',$arrBodyInq,$headerInq);
					$arrResponseAPIInq = json_decode($responseAPIInq);
					if($arrResponseAPIInq->RESULT){
						$arrBody = array();
						$arrBody['menu_component'] = "TransferDepPayLoan";
						$arrBody['deptaccount_no'] = $rowTaskList['from_account'];
						$arrBody['contract_no'] = $rowTaskList['destination'];
						$arrBody['amt_transfer'] = $rowTaskList['amt_transfer'];
						$arrBody['penalty_amt'] = $arrResponseAPIInq->PENALTY_AMT;
						$arrBody['channel'] = 'mobile_app';
						$arrPayloadNew = array();
						$arrPayloadNew['id_userlogin'] = $rowTaskList['id_userlogin'];
						$arrPayloadNew['member_no'] = $rowTaskList['member_no'];
						$arrPayloadNew['user_type'] = '0';
						$arrPayloadNew['exp'] = time() + intval($func->getConstant("limit_session_timeout"));
						$access_token = $jwt_token->customPayload($arrPayloadNew, $config["SECRET_KEY_JWT"]);
						$header[] = "Authorization: Bearer ".$access_token;
						$header[] = "transaction_scheduler: 1";
						$responseAPI = $lib->posting_data($config["URL_SERVICE"].'mobile_and_web-control/repayloan/request_payment_loan',$arrBody,$header);
						$arrResponseAPI = json_decode($responseAPI);
						if($arrResponseAPI->RESULT){
							if($expire_scheduler){
								$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_status = '1' WHERE id_transchedule = :id_transchedule");
								$updateFailTrans->execute([':id_transchedule' => $rowTaskList["id_transchedule"]]);
							}else{
								$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_date = :scheduler_date WHERE id_transchedule = :id_transchedule");
								$updateFailTrans->execute([
									':scheduler_date' => $scheduler_date,
									':id_transchedule' => $rowTaskList["id_transchedule"]
								]);
							}
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
							if($expire_scheduler){
								$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_status = '-99' WHERE id_transchedule = :id_transchedule");
								$updateFailTrans->execute([':id_transchedule' => $rowTaskList["id_transchedule"]]);
							}else{
								$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_date = :scheduler_date WHERE id_transchedule = :id_transchedule");
								$updateFailTrans->execute([
									':scheduler_date' => $scheduler_date,
									':id_transchedule' => $rowTaskList["id_transchedule"]
								]);
							}
							$getMaxSeqNo = $conmysql->prepare("SELECT IFNULL(MAX(SEQ_NO),0) as M_SEQ_NO FROM gctransactionschedulestatement WHERE id_transchedule = :id_transchedule");
							$getMaxSeqNo->execute([':id_transchedule' => $rowTaskList["id_transchedule"]]);
							$rowMaxSeqNo = $getMaxSeqNo->fetch(PDO::FETCH_ASSOC);
							$insertStmScheduler = $conmysql->prepare("INSERT INTO gctransactionschedulestatement(id_transchedule,seq_no,scheduler_status,scheduler_detail) 
																		VALUES(:id_transchedule,:seq_no,'-99',:text_error)");
							$insertStmScheduler->execute([
								':id_transchedule' => $rowTaskList["id_transchedule"],
								':seq_no' => $rowMaxSeqNo["M_SEQ_NO"] + 1,
								':text_error' => $arrResponseAPI->RESPONSE_MESSAGE
							]);
							$conmysql->commit();
						}
					}else{
						if($expire_scheduler){
							$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_status = '-99' WHERE id_transchedule = :id_transchedule");
							$updateFailTrans->execute([':id_transchedule' => $rowTaskList["id_transchedule"]]);
						}else{
							$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_date = :scheduler_date WHERE id_transchedule = :id_transchedule");
							$updateFailTrans->execute([
								':scheduler_date' => $scheduler_date,
								':id_transchedule' => $rowTaskList["id_transchedule"]
							]);
						}
						$getMaxSeqNo = $conmysql->prepare("SELECT IFNULL(MAX(SEQ_NO),0) as M_SEQ_NO FROM gctransactionschedulestatement WHERE id_transchedule = :id_transchedule");
						$getMaxSeqNo->execute([':id_transchedule' => $rowTaskList["id_transchedule"]]);
						$rowMaxSeqNo = $getMaxSeqNo->fetch(PDO::FETCH_ASSOC);
						$insertStmScheduler = $conmysql->prepare("INSERT INTO gctransactionschedulestatement(id_transchedule,seq_no,scheduler_status,scheduler_detail) 
																	VALUES(:id_transchedule,:seq_no,'-99',:text_error)");
						$insertStmScheduler->execute([
							':id_transchedule' => $rowTaskList["id_transchedule"],
							':seq_no' => $rowMaxSeqNo["M_SEQ_NO"] + 1,
							':text_error' => $arrResponseAPIInq->RESPONSE_MESSAGE
						]);
						$conmysql->commit();
					}
				}else{
					if($expire_scheduler){
						$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_status = '-99' WHERE id_transchedule = :id_transchedule");
						$updateFailTrans->execute([':id_transchedule' => $rowTaskList["id_transchedule"]]);
					}else{
						$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_date = :scheduler_date WHERE id_transchedule = :id_transchedule");
						$updateFailTrans->execute([
							':scheduler_date' => $scheduler_date,
							':id_transchedule' => $rowTaskList["id_transchedule"]
						]);
					}
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
		}else if($rowTaskList["transaction_type"] == '4'){
			$fetchAccountBeenBind = $conmysql->prepare("SELECT sigma_key,deptaccount_no_coop
														FROM gcbindaccount
														WHERE id_bindaccount = :id_bindaccount and bindaccount_status = '1'");
			$fetchAccountBeenBind->execute([
				':id_bindaccount' => $rowTaskList["from_account"]
			]);
			if($fetchAccountBeenBind->rowCount() > 0){
				$rowFetchAcc = $fetchAccountBeenBind->fetch(PDO::FETCH_ASSOC);
				$fetchAccAllowTrans = $conmysql->prepare("SELECT gat.deptaccount_no FROM gcuserallowacctransaction gat
															LEFT JOIN gcconstantaccountdept gad ON gat.id_accountconstant = gad.id_accountconstant
															WHERE gat.deptaccount_no = :deptaccount_no and gat.is_use = '1' and gad.allow_deposit_outside = '1'");
				$fetchAccAllowTrans->execute([':deptaccount_no' => $rowFetchAcc["deptaccount_no_coop"]]);
				if($fetchAccAllowTrans->rowCount() > 0){
					$arrBodyInq = array();
					$arrBodyInq['menu_component'] = "TransactionDeposit";
					$arrBodyInq['sigma_key'] = $rowFetchAcc['sigma_key'];
					$arrBodyInq['deptaccount_no'] = $rowFetchAcc['deptaccount_no_coop'];
					$arrBodyInq['amt_transfer'] = $rowTaskList['amt_transfer'];
					$arrBodyInq['channel'] = 'mobile_app';
					$arrPayloadNew = array();
					$arrPayloadNew['id_userlogin'] = $rowTaskList['id_userlogin'];
					$arrPayloadNew['member_no'] = $rowTaskList['member_no'];
					$arrPayloadNew['user_type'] = '0';
					$arrPayloadNew['exp'] = time() + intval($func->getConstant("limit_session_timeout"));
					$access_token = $jwt_token->customPayload($arrPayloadNew, $config["SECRET_KEY_JWT"]);
					$headerInq[] = "Authorization: Bearer ".$access_token;
					$headerInq[] = "transaction_scheduler: 1";
					$responseAPIInq = $lib->posting_data($config["URL_SERVICE"].'mobile_and_web-control/depositfundtransfer/request_verify_data',$arrBodyInq,$headerInq);
					$arrResponseAPIInq = json_decode($responseAPIInq);
					if($arrResponseAPIInq->RESULT){
						$arrBody = array();
						$arrBody['menu_component'] = "TransactionDeposit";
						$arrBody['coop_account_no'] = $rowFetchAcc['deptaccount_no_coop'];
						$arrBody['sigma_key'] = $rowFetchAcc['sigma_key'];
						$arrBody['amt_transfer'] = $rowTaskList['amt_transfer'];
						$arrBody['fee_amt'] = $arrResponseAPIInq->FEE_AMT;
						if($rowTaskList["bank_code"] == '025'){
							$arrBody['ETN_REFNO'] = $arrResponseAPIInq->ETN_REFNO;
							$arrBody['SOURCE_REFNO'] = $arrResponseAPIInq->SOURCE_REFNO;
						}
						$arrBody['channel'] = 'mobile_app';
						$arrPayloadNew = array();
						$arrPayloadNew['id_userlogin'] = $rowTaskList['id_userlogin'];
						$arrPayloadNew['member_no'] = $rowTaskList['member_no'];
						$arrPayloadNew['user_type'] = '0';
						$arrPayloadNew['exp'] = time() + intval($func->getConstant("limit_session_timeout"));
						$access_token = $jwt_token->customPayload($arrPayloadNew, $config["SECRET_KEY_JWT"]);
						$header[] = "Authorization: Bearer ".$access_token;
						$header[] = "transaction_scheduler: 1";
						$responseAPI = $lib->posting_data($config["URL_SERVICE"].'mobile_and_web-control/depositfundtransfer/request_deposit_bank_payment',$arrBody,$header);
						$arrResponseAPI = json_decode($responseAPI);
						if($arrResponseAPI->RESULT){
							if($expire_scheduler){
								$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_status = '1' WHERE id_transchedule = :id_transchedule");
								$updateFailTrans->execute([':id_transchedule' => $rowTaskList["id_transchedule"]]);
							}else{
								$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_date = :scheduler_date WHERE id_transchedule = :id_transchedule");
								$updateFailTrans->execute([
									':scheduler_date' => $scheduler_date,
									':id_transchedule' => $rowTaskList["id_transchedule"]
								]);
							}
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
							if($expire_scheduler){
								$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_status = '-99' WHERE id_transchedule = :id_transchedule");
								$updateFailTrans->execute([':id_transchedule' => $rowTaskList["id_transchedule"]]);
							}else{
								$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_date = :scheduler_date WHERE id_transchedule = :id_transchedule");
								$updateFailTrans->execute([
									':scheduler_date' => $scheduler_date,
									':id_transchedule' => $rowTaskList["id_transchedule"]
								]);
							}
							$getMaxSeqNo = $conmysql->prepare("SELECT IFNULL(MAX(SEQ_NO),0) as M_SEQ_NO FROM gctransactionschedulestatement WHERE id_transchedule = :id_transchedule");
							$getMaxSeqNo->execute([':id_transchedule' => $rowTaskList["id_transchedule"]]);
							$rowMaxSeqNo = $getMaxSeqNo->fetch(PDO::FETCH_ASSOC);
							$insertStmScheduler = $conmysql->prepare("INSERT INTO gctransactionschedulestatement(id_transchedule,seq_no,scheduler_status,scheduler_detail) 
																		VALUES(:id_transchedule,:seq_no,'-99',:text_error)");
							$insertStmScheduler->execute([
								':id_transchedule' => $rowTaskList["id_transchedule"],
								':seq_no' => $rowMaxSeqNo["M_SEQ_NO"] + 1,
								':text_error' => $arrResponseAPI->RESPONSE_MESSAGE
							]);
							$conmysql->commit();
						}
					}else{
						if($expire_scheduler){
							$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_status = '-99' WHERE id_transchedule = :id_transchedule");
							$updateFailTrans->execute([':id_transchedule' => $rowTaskList["id_transchedule"]]);
						}else{
							$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_date = :scheduler_date WHERE id_transchedule = :id_transchedule");
							$updateFailTrans->execute([
								':scheduler_date' => $scheduler_date,
								':id_transchedule' => $rowTaskList["id_transchedule"]
							]);
						}
						$getMaxSeqNo = $conmysql->prepare("SELECT IFNULL(MAX(SEQ_NO),0) as M_SEQ_NO FROM gctransactionschedulestatement WHERE id_transchedule = :id_transchedule");
						$getMaxSeqNo->execute([':id_transchedule' => $rowTaskList["id_transchedule"]]);
						$rowMaxSeqNo = $getMaxSeqNo->fetch(PDO::FETCH_ASSOC);
						$insertStmScheduler = $conmysql->prepare("INSERT INTO gctransactionschedulestatement(id_transchedule,seq_no,scheduler_status,scheduler_detail) 
																	VALUES(:id_transchedule,:seq_no,'-99',:text_error)");
						$insertStmScheduler->execute([
							':id_transchedule' => $rowTaskList["id_transchedule"],
							':seq_no' => $rowMaxSeqNo["M_SEQ_NO"] + 1,
							':text_error' => $arrResponseAPIInq->RESPONSE_MESSAGE
						]);
						$conmysql->commit();
					}
				}else{
					if($expire_scheduler){
						$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_status = '-99' WHERE id_transchedule = :id_transchedule");
						$updateFailTrans->execute([':id_transchedule' => $rowTaskList["id_transchedule"]]);
					}else{
						$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_date = :scheduler_date WHERE id_transchedule = :id_transchedule");
						$updateFailTrans->execute([
							':scheduler_date' => $scheduler_date,
							':id_transchedule' => $rowTaskList["id_transchedule"]
						]);
					}
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
			}else{
				if($expire_scheduler){
					$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_status = '-99' WHERE id_transchedule = :id_transchedule");
					$updateFailTrans->execute([':id_transchedule' => $rowTaskList["id_transchedule"]]);
				}else{
					$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_date = :scheduler_date WHERE id_transchedule = :id_transchedule");
					$updateFailTrans->execute([
						':scheduler_date' => $scheduler_date,
						':id_transchedule' => $rowTaskList["id_transchedule"]
					]);
				}
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
		}else if($rowTaskList["transaction_type"] == '5'){
			$fetchAccountBeenBind = $conmysql->prepare("SELECT sigma_key,deptaccount_no_coop,deptaccount_no_bank
														FROM gcbindaccount
														WHERE id_bindaccount = :id_bindaccount and bindaccount_status = '1'");
			$fetchAccountBeenBind->execute([
				':id_bindaccount' => $rowTaskList["destination"]
			]);
			if($fetchAccountBeenBind->rowCount() > 0){
				$rowFetchAcc = $fetchAccountBeenBind->fetch(PDO::FETCH_ASSOC);
				$fetchAccAllowTrans = $conmysql->prepare("SELECT gat.deptaccount_no FROM gcuserallowacctransaction gat
															LEFT JOIN gcconstantaccountdept gad ON gat.id_accountconstant = gad.id_accountconstant
															WHERE gat.deptaccount_no = :deptaccount_no and gat.is_use = '1' and gad.allow_withdraw_outside = '1'");
				$fetchAccAllowTrans->execute([':deptaccount_no' => $rowFetchAcc["deptaccount_no_coop"]]);
				if($fetchAccAllowTrans->rowCount() > 0){
					$arrBodyInq = array();
					$arrBodyInq['menu_component'] = "TransactionWithdrawDeposit";
					$arrBodyInq['sigma_key'] = $rowFetchAcc['sigma_key'];
					$arrBodyInq['bank_account_no'] = $rowFetchAcc['deptaccount_no_bank'];
					$arrBodyInq['deptaccount_no'] = $rowFetchAcc['deptaccount_no_coop'];
					$arrBodyInq['amt_transfer'] = $rowTaskList['amt_transfer'];
					$arrBodyInq['channel'] = 'mobile_app';
					$arrPayloadNew = array();
					$arrPayloadNew['id_userlogin'] = $rowTaskList['id_userlogin'];
					$arrPayloadNew['member_no'] = $rowTaskList['member_no'];
					$arrPayloadNew['user_type'] = '0';
					$arrPayloadNew['exp'] = time() + intval($func->getConstant("limit_session_timeout"));
					$access_token = $jwt_token->customPayload($arrPayloadNew, $config["SECRET_KEY_JWT"]);
					$headerInq[] = "Authorization: Bearer ".$access_token;
					$headerInq[] = "transaction_scheduler: 1";
					$responseAPIInq = $lib->posting_data($config["URL_SERVICE"].'mobile_and_web-control/withdrawfundtransfer/request_verify_data',$arrBodyInq,$headerInq);
					$arrResponseAPIInq = json_decode($responseAPIInq);
					if($arrResponseAPIInq->RESULT){
						$arrBody = array();
						$arrBody['menu_component'] = "TransactionWithdrawDeposit";
						$arrBody['coop_account_no'] = $rowFetchAcc['deptaccount_no_coop'];
						$arrBody['sigma_key'] = $rowFetchAcc['sigma_key'];
						$arrBody['amt_transfer'] = $rowTaskList['amt_transfer'];
						$arrBody['fee_amt'] = $arrResponseAPIInq->FEE_AMT ?? 0;
						$arrBody['penalty_amt'] = $arrResponseAPIInq->PENALTY_AMT ?? 0;
						if($rowTaskList["bank_code"] == '025'){
							$arrBody['ETN_REFNO'] = $arrResponseAPIInq->ETN_REFNO;
							$arrBody['SOURCE_REFNO'] = $arrResponseAPIInq->SOURCE_REFNO;
						}
						$arrBody['channel'] = 'mobile_app';
						$arrPayloadNew = array();
						$arrPayloadNew['id_userlogin'] = $rowTaskList['id_userlogin'];
						$arrPayloadNew['member_no'] = $rowTaskList['member_no'];
						$arrPayloadNew['user_type'] = '0';
						$arrPayloadNew['exp'] = time() + intval($func->getConstant("limit_session_timeout"));
						$access_token = $jwt_token->customPayload($arrPayloadNew, $config["SECRET_KEY_JWT"]);
						$header[] = "Authorization: Bearer ".$access_token;
						$header[] = "transaction_scheduler: 1";
						$responseAPI = $lib->posting_data($config["URL_SERVICE"].'mobile_and_web-control/withdrawfundtransfer/request_withdraw_bank_payment',$arrBody,$header);
						$arrResponseAPI = json_decode($responseAPI);
						if($arrResponseAPI->RESULT){
							if($expire_scheduler){
								$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_status = '1' WHERE id_transchedule = :id_transchedule");
								$updateFailTrans->execute([':id_transchedule' => $rowTaskList["id_transchedule"]]);
							}else{
								$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_date = :scheduler_date WHERE id_transchedule = :id_transchedule");
								$updateFailTrans->execute([
									':scheduler_date' => $scheduler_date,
									':id_transchedule' => $rowTaskList["id_transchedule"]
								]);
							}
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
							if($expire_scheduler){
								$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_status = '-99' WHERE id_transchedule = :id_transchedule");
								$updateFailTrans->execute([':id_transchedule' => $rowTaskList["id_transchedule"]]);
							}else{
								$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_date = :scheduler_date WHERE id_transchedule = :id_transchedule");
								$updateFailTrans->execute([
									':scheduler_date' => $scheduler_date,
									':id_transchedule' => $rowTaskList["id_transchedule"]
								]);
							}
							$getMaxSeqNo = $conmysql->prepare("SELECT IFNULL(MAX(SEQ_NO),0) as M_SEQ_NO FROM gctransactionschedulestatement WHERE id_transchedule = :id_transchedule");
							$getMaxSeqNo->execute([':id_transchedule' => $rowTaskList["id_transchedule"]]);
							$rowMaxSeqNo = $getMaxSeqNo->fetch(PDO::FETCH_ASSOC);
							$insertStmScheduler = $conmysql->prepare("INSERT INTO gctransactionschedulestatement(id_transchedule,seq_no,scheduler_status,scheduler_detail) 
																		VALUES(:id_transchedule,:seq_no,'-99',:text_error)");
							$insertStmScheduler->execute([
								':id_transchedule' => $rowTaskList["id_transchedule"],
								':seq_no' => $rowMaxSeqNo["M_SEQ_NO"] + 1,
								':text_error' => $arrResponseAPI->RESPONSE_MESSAGE
							]);
							$conmysql->commit();
						}
					}else{
						if($expire_scheduler){
							$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_status = '-99' WHERE id_transchedule = :id_transchedule");
							$updateFailTrans->execute([':id_transchedule' => $rowTaskList["id_transchedule"]]);
						}else{
							$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_date = :scheduler_date WHERE id_transchedule = :id_transchedule");
							$updateFailTrans->execute([
								':scheduler_date' => $scheduler_date,
								':id_transchedule' => $rowTaskList["id_transchedule"]
							]);
						}
						$getMaxSeqNo = $conmysql->prepare("SELECT IFNULL(MAX(SEQ_NO),0) as M_SEQ_NO FROM gctransactionschedulestatement WHERE id_transchedule = :id_transchedule");
						$getMaxSeqNo->execute([':id_transchedule' => $rowTaskList["id_transchedule"]]);
						$rowMaxSeqNo = $getMaxSeqNo->fetch(PDO::FETCH_ASSOC);
						$insertStmScheduler = $conmysql->prepare("INSERT INTO gctransactionschedulestatement(id_transchedule,seq_no,scheduler_status,scheduler_detail) 
																	VALUES(:id_transchedule,:seq_no,'-99',:text_error)");
						$insertStmScheduler->execute([
							':id_transchedule' => $rowTaskList["id_transchedule"],
							':seq_no' => $rowMaxSeqNo["M_SEQ_NO"] + 1,
							':text_error' => $arrResponseAPIInq->RESPONSE_MESSAGE
						]);
						$conmysql->commit();
					}
				}else{
					if($expire_scheduler){
						$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_status = '-99' WHERE id_transchedule = :id_transchedule");
						$updateFailTrans->execute([':id_transchedule' => $rowTaskList["id_transchedule"]]);
					}else{
						$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_date = :scheduler_date WHERE id_transchedule = :id_transchedule");
						$updateFailTrans->execute([
							':scheduler_date' => $scheduler_date,
							':id_transchedule' => $rowTaskList["id_transchedule"]
						]);
					}
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
			}else{
				if($expire_scheduler){
					$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_status = '-99' WHERE id_transchedule = :id_transchedule");
					$updateFailTrans->execute([':id_transchedule' => $rowTaskList["id_transchedule"]]);
				}else{
					$updateFailTrans = $conmysql->prepare("UPDATE gctransactionschedule SET scheduler_date = :scheduler_date WHERE id_transchedule = :id_transchedule");
					$updateFailTrans->execute([
						':scheduler_date' => $scheduler_date,
						':id_transchedule' => $rowTaskList["id_transchedule"]
					]);
				}
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
	}
}
?>
