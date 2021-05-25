<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanRequestTrack')){
		$arrGrpReq = array();
		$arrLimit = array();
		$limit = $func->getConstant('limit_loanrequest');
		$arrLimit['LIMIT_DURATION'] = $limit;
		$date_before = date('Y-m-d',strtotime('-'.$limit.' months'));
		$date_now = date('Y-m-d');
		$Contractno = null;
		$fetchContractTypeCheck = $conmysql->prepare("SELECT balance_status FROM gcconstantbalanceconfirm WHERE member_no = :member_no");
		$fetchContractTypeCheck->execute([':member_no' => $payload["ref_memno"]]);
		$rowContractnoCheck = $fetchContractTypeCheck->fetch(PDO::FETCH_ASSOC);
		$Contractno  = $rowContractnoCheck["balance_status"] ;
		if($Contractno == "0"){
			if($dataComing["req_status"] == "8"){ //อยู่ระหว่างดำเนินการ
			$fetchReqLoan = $conoracle->prepare("SELECT  LCREQLOAN.LOANREQUEST_DOCNO as REQLOAN_DOC, 
										LCREQLOAN.MEMBER_NO,    
										LCREQLOAN.LOANREQUEST_DATE as APPROVE_DATE,    
										LCREQLOAN.LOANREQUEST_AMT  as REQUEST_AMT, 
										LCREQLOAN.LOANCREDIT_AMT  as LOANPERMIT_AMT, 
										LCREQLOAN.PERIOD_PAYMENT  as PERIOD_PAYMENT, 
										LCREQLOAN.PERIOD_INSTALLMENT as PERIOD, 
										LCCFLOANTYPE.LOANTYPE_CODE as LOANTYPE_CODE,
										LCCFLOANTYPE.LOANTYPE_DESC as LOANTYPE_DESC,         
										LCREQLOAN.LOANREQUEST_STATUS as REQ_STATUS,
										LCREQLOAN.REMARK as REMARK
										FROM LCREQLOAN,      
										LCCFLOANTYPE
										WHERE  LCREQLOAN.BRANCH_ID = LCCFLOANTYPE.BRANCH_ID   
										AND LCREQLOAN.LOANTYPE_CODE = LCCFLOANTYPE.LOANTYPE_CODE   
										AND LCREQLOAN.LOANREQUEST_STATUS =  8
										AND LCREQLOAN.member_no = :member_no
										AND lcreqloan.loanrequest_date BETWEEN to_date(:datebefore,'YYYY-MM-DD') and to_date(:datenow,'YYYY-MM-DD') ");
				$fetchReqLoan->execute([
					':member_no' => $payload["ref_memno"],
					':req_status' => $dataComing["req_status"],
					':datebefore' => $date_before,
					':datenow' => $date_now
				]);
				while($rowReqLoan = $fetchReqLoan->fetch(PDO::FETCH_ASSOC)){
					$arrayReq = array();
					$arrayReq["LOANTYPE_DESC"] = $rowReqLoan["LOANTYPE_DESC"];
					$arrayReq["REQLOAN_DOC"] = $rowReqLoan["REQLOAN_DOC"];
					$arrayReq["LOANTYPE_CODE"] = $rowReqLoan["LOANTYPE_CODE"];
					$arrayReq["REQUEST_AMT"] = $rowReqLoan["REQUEST_AMT"];
					$arrayReq["PERIOD_PAYMENT"] = $rowReqLoan["PERIOD_PAYMENT"];
					$arrayReq["PERIOD"] = $rowReqLoan["PERIOD"];
					$arrayReq["REQ_STATUS"] = $rowReqLoan["req_status"];
					$arrayReq["REQ_STATUS_DESC"] = $configError["REQ_LOAN_STATUS"][0][$rowReqLoan["REQ_STATUS"]][0][$lang_locale];
					$arrayReq["LOANPERMIT_AMT"] = $rowReqLoan["LOANPERMIT_AMT"];
					$arrayReq["REMARK"] = $rowReqLoan["REMARK"];
					$arrayReq["APPROVE_DATE"] = isset($rowReqLoan["APPROVE_DATE"]) && $rowReqLoan["APPROVE_DATE"] != "" ? $lib->convertdate($rowReqLoan["APPROVE_DATE"],'d m Y') : null;
					$arrGrpReq[] = $arrayReq;
				}
			}else if($dataComing["req_status"] == "-9"){ //ไม่อนุมัติ
				$fetchReqLoan = $conoracle->prepare("SELECT  LCREQLOAN.LOANREQUEST_DOCNO as REQLOAN_DOC, 
										LCREQLOAN.MEMBER_NO,    
										LCREQLOAN.LOANREQUEST_DATE as APPROVE_DATE,    
										LCREQLOAN.LOANREQUEST_AMT  as REQUEST_AMT, 
										LCREQLOAN.LOANCREDIT_AMT  as LOANPERMIT_AMT, 
										LCREQLOAN.PERIOD_PAYMENT  as PERIOD_PAYMENT, 
										LCREQLOAN.PERIOD_INSTALLMENT as PERIOD, 
										LCCFLOANTYPE.LOANTYPE_CODE as LOANTYPE_CODE,
										LCCFLOANTYPE.LOANTYPE_DESC as LOANTYPE_DESC,         
										LCREQLOAN.LOANREQUEST_STATUS as REQ_STATUS,
										LCREQLOAN.REMARK as REMARK
										FROM LCREQLOAN,      
										LCCFLOANTYPE
										WHERE  LCREQLOAN.BRANCH_ID = LCCFLOANTYPE.BRANCH_ID   
										AND LCREQLOAN.LOANTYPE_CODE = LCCFLOANTYPE.LOANTYPE_CODE   
										AND LCREQLOAN.LOANREQUEST_STATUS =  -9
										AND LCREQLOAN.member_no = :member_no
										AND lcreqloan.loanrequest_date BETWEEN to_date(:datebefore,'YYYY-MM-DD') and to_date(:datenow,'YYYY-MM-DD') ");
				$fetchReqLoan->execute([
					':member_no' => $payload["ref_memno"],
					':req_status' => $dataComing["req_status"],
					':datebefore' => $date_before,
					':datenow' => $date_now
				]);
				while($rowReqLoan = $fetchReqLoan->fetch(PDO::FETCH_ASSOC)){
					$arrayReq = array();
					$arrayReq["LOANTYPE_DESC"] = $rowReqLoan["LOANTYPE_DESC"];
					$arrayReq["REQLOAN_DOC"] = $rowReqLoan["REQLOAN_DOC"];
					$arrayReq["LOANTYPE_CODE"] = $rowReqLoan["LOANTYPE_CODE"];
					$arrayReq["REQUEST_AMT"] = $rowReqLoan["REQUEST_AMT"];
					$arrayReq["PERIOD_PAYMENT"] = $rowReqLoan["PERIOD_PAYMENT"];
					$arrayReq["PERIOD"] = $rowReqLoan["PERIOD"];
					$arrayReq["REQ_STATUS"] = $rowReqLoan["req_status"];
					$arrayReq["REQ_STATUS_DESC"] = $configError["REQ_LOAN_STATUS"][0][$rowReqLoan["REQ_STATUS"]][0][$lang_locale];
					$arrayReq["LOANPERMIT_AMT"] = $rowReqLoan["LOANPERMIT_AMT"];
					$arrayReq["REMARK"] = $rowReqLoan["REMARK"];
					$arrayReq["APPROVE_DATE"] = isset($rowReqLoan["APPROVE_DATE"]) && $rowReqLoan["APPROVE_DATE"] != "" ? $lib->convertdate($rowReqLoan["APPROVE_DATE"],'d m Y') : null;
					$arrGrpReq[] = $arrayReq;
				}
			}else if($dataComing["req_status"] == "0"){ //อนุมัติ/รอทำสัญญา 
					$fetchReqLoan = $conoracle->prepare("SELECT  LCREQLOAN.LOANREQUEST_DOCNO as REQLOAN_DOC, 
											LCREQLOAN.MEMBER_NO,    
											LCREQLOAN.LOANREQUEST_DATE as APPROVE_DATE,    
											LCREQLOAN.LOANREQUEST_AMT  as REQUEST_AMT, 
											LCREQLOAN.LOANCREDIT_AMT  as LOANPERMIT_AMT, 
											LCREQLOAN.PERIOD_PAYMENT  as PERIOD_PAYMENT, 
											LCREQLOAN.PERIOD_INSTALLMENT as PERIOD, 
											LCCFLOANTYPE.LOANTYPE_CODE as LOANTYPE_CODE,
											LCCFLOANTYPE.LOANTYPE_DESC as LOANTYPE_DESC,         
											LCREQLOAN.LOANREQUEST_STATUS as REQ_STATUS,
											LCREQLOAN.REMARK as REMARK
											FROM LCREQLOAN,      
											LCCFLOANTYPE,
											LCCONTMASTER 
											WHERE  LCREQLOAN.BRANCH_ID = LCCFLOANTYPE.BRANCH_ID   
											AND LCREQLOAN.LOANTYPE_CODE = LCCFLOANTYPE.LOANTYPE_CODE   
											AND LCREQLOAN.LOANREQUEST_DOCNO = lccontmaster.LOANREQUEST_DOCNO
											AND LCCONTMASTER.CONTSIGN_STATUS  = <> 1
											AND LCREQLOAN.member_no = :member_no
											AND lcreqloan.loanrequest_date BETWEEN to_date(:datebefore,'YYYY-MM-DD') and to_date(:datenow,'YYYY-MM-DD') ");
				$fetchReqLoan->execute([
					':member_no' => $payload["ref_memno"],
					':req_status' => $dataComing["req_status"],
					':datebefore' => $date_before,
					':datenow' => $date_now
				]);
				while($rowReqLoan = $fetchReqLoan->fetch(PDO::FETCH_ASSOC)){
					$arrayReq = array();
					$arrayReq["LOANTYPE_DESC"] = $rowReqLoan["LOANTYPE_DESC"];
					$arrayReq["REQLOAN_DOC"] = $rowReqLoan["REQLOAN_DOC"];
					$arrayReq["LOANTYPE_CODE"] = $rowReqLoan["LOANTYPE_CODE"];
					$arrayReq["REQUEST_AMT"] = $rowReqLoan["REQUEST_AMT"];
					$arrayReq["PERIOD_PAYMENT"] = $rowReqLoan["PERIOD_PAYMENT"];
					$arrayReq["PERIOD"] = $rowReqLoan["PERIOD"];
					$arrayReq["REQ_STATUS"] = $rowReqLoan["req_status"];
					$arrayReq["REQ_STATUS_DESC"] = $configError["REQ_LOAN_STATUS"][0][$rowReqLoan["REQ_STATUS"]][0][$lang_locale];
					$arrayReq["LOANPERMIT_AMT"] = $rowReqLoan["LOANPERMIT_AMT"];
					$arrayReq["REMARK"] = $rowReqLoan["REMARK"];
					$arrayReq["APPROVE_DATE"] = isset($rowReqLoan["APPROVE_DATE"]) && $rowReqLoan["APPROVE_DATE"] != "" ? $lib->convertdate($rowReqLoan["APPROVE_DATE"],'d m Y') : null;
					$arrGrpReq[] = $arrayReq;
				}
			}else if($dataComing["req_status"] == "1"){ // 
					$fetchReqLoan = $conoracle->prepare("SELECT  LCREQLOAN.LOANREQUEST_DOCNO as REQLOAN_DOC, 
											LCREQLOAN.MEMBER_NO,    
											LCREQLOAN.LOANREQUEST_DATE as APPROVE_DATE,    
											LCREQLOAN.LOANREQUEST_AMT  as REQUEST_AMT, 
											LCREQLOAN.LOANCREDIT_AMT  as LOANPERMIT_AMT, 
											LCREQLOAN.PERIOD_PAYMENT  as PERIOD_PAYMENT, 
											LCREQLOAN.PERIOD_INSTALLMENT as PERIOD, 
											LCCFLOANTYPE.LOANTYPE_CODE as LOANTYPE_CODE,
											LCCFLOANTYPE.LOANTYPE_DESC as LOANTYPE_DESC,         
											LCREQLOAN.LOANREQUEST_STATUS as REQ_STATUS,
											LCREQLOAN.REMARK as REMARK,
											LCCONTMASTER.CONTSIGN_DATE ,
											LCCONTMASTER.CONTSIGN_AMT 
											FROM LCREQLOAN,      
											LCCFLOANTYPE,
											LCCONTMASTER 
											WHERE  LCREQLOAN.BRANCH_ID = LCCFLOANTYPE.BRANCH_ID   
											AND LCREQLOAN.LOANTYPE_CODE = LCCFLOANTYPE.LOANTYPE_CODE   
											AND LCREQLOAN.LOANREQUEST_DOCNO = lccontmaster.LOANREQUEST_DOCNO
											AND LCCONTMASTER.CONTSIGN_STATUS  = 1
											AND LCREQLOAN.member_no = :member_no
											AND lcreqloan.loanrequest_date BETWEEN to_date(:datebefore,'YYYY-MM-DD') and to_date(:datenow,'YYYY-MM-DD') ");
				$fetchReqLoan->execute([
					':member_no' => $payload["ref_memno"],
					':req_status' => $dataComing["req_status"],
					':datebefore' => $date_before,
					':datenow' => $date_now
				]);
				while($rowReqLoan = $fetchReqLoan->fetch(PDO::FETCH_ASSOC)){
					$arrayReq = array();
					$arrayReq["LOANTYPE_DESC"] = $rowReqLoan["LOANTYPE_DESC"];
					$arrayReq["REQLOAN_DOC"] = $rowReqLoan["REQLOAN_DOC"];
					$arrayReq["LOANTYPE_CODE"] = $rowReqLoan["LOANTYPE_CODE"];
					$arrayReq["REQUEST_AMT"] = $rowReqLoan["REQUEST_AMT"];
					$arrayReq["PERIOD_PAYMENT"] = $rowReqLoan["PERIOD_PAYMENT"];
					$arrayReq["PERIOD"] = $rowReqLoan["PERIOD"];
					$arrayReq["REQ_STATUS"] = $rowReqLoan["req_status"];
					$arrayReq["REQ_STATUS_DESC"] = $configError["REQ_LOAN_STATUS"][0][$rowReqLoan["REQ_STATUS"]][0][$lang_locale];
					$arrayReq["LOANPERMIT_AMT"] = $rowReqLoan["LOANPERMIT_AMT"];
					$arrayReq["CONTSIGN_AMT"] = number_format($rowReqLoan["CONTSIGN_AMT"],2);
					$arrayReq["CONTSIGN_DATE"] = $lib->convertdate($rowReqLoan["CONTSIGN_DATE"],'d M Y');
					$arrayReq["REMARK"] = $rowReqLoan["REMARK"];
					$arrayReq["APPROVE_DATE"] = isset($rowReqLoan["APPROVE_DATE"]) && $rowReqLoan["APPROVE_DATE"] != "" ? $lib->convertdate($rowReqLoan["APPROVE_DATE"],'d m Y') : null;
					$arrGrpReq[] = $arrayReq;
				}
			}else{
				$fetchReqLoan = $conoracle->prepare("SELECT  LCREQLOAN.LOANREQUEST_DOCNO as REQLOAN_DOC, 
											LCREQLOAN.MEMBER_NO,    
											LCREQLOAN.LOANREQUEST_DATE as APPROVE_DATE,    
											LCREQLOAN.LOANREQUEST_AMT  as REQUEST_AMT, 
											LCREQLOAN.LOANCREDIT_AMT  as LOANPERMIT_AMT, 
											LCREQLOAN.PERIOD_PAYMENT  as PERIOD_PAYMENT, 
											LCREQLOAN.PERIOD_INSTALLMENT as PERIOD, 
											LCCFLOANTYPE.LOANTYPE_CODE as LOANTYPE_CODE,
											LCCFLOANTYPE.LOANTYPE_DESC as LOANTYPE_DESC,         
											LCREQLOAN.LOANREQUEST_STATUS as REQ_STATUS,
											LCREQLOAN.REMARK as REMARK,
											LCCONTMASTER.CONTSIGN_DATE ,
											LCCONTMASTER.CONTSIGN_AMT 
											FROM LCREQLOAN,      
											LCCFLOANTYPE,
											LCCONTMASTER 
											WHERE  LCREQLOAN.BRANCH_ID = LCCFLOANTYPE.BRANCH_ID   
											AND LCREQLOAN.LOANTYPE_CODE = LCCFLOANTYPE.LOANTYPE_CODE   
											AND LCREQLOAN.LOANREQUEST_DOCNO = lccontmaster.LOANREQUEST_DOCNO
											AND LCREQLOAN.member_no = :member_no
											AND lcreqloan.loanrequest_date BETWEEN to_date(:datebefore,'YYYY-MM-DD') and to_date(:datenow,'YYYY-MM-DD') ");
				$fetchReqLoan->execute([
					':member_no' => $payload["ref_memno"],
					':datebefore' => $date_before,
					':datenow' => $date_now
				]);
				while($rowReqLoan = $fetchReqLoan->fetch(PDO::FETCH_ASSOC)){
					$arrayReq = array();
					$arrayReq["LOANTYPE_DESC"] = $rowReqLoan["LOANTYPE_DESC"];
					$arrayReq["REQLOAN_DOC"] = $rowReqLoan["REQLOAN_DOC"];
					$arrayReq["LOANTYPE_CODE"] = $rowReqLoan["LOANTYPE_CODE"];
					$arrayReq["REQUEST_AMT"] = $rowReqLoan["REQUEST_AMT"];
					$arrayReq["PERIOD_PAYMENT"] = $rowReqLoan["PERIOD_PAYMENT"];
					$arrayReq["PERIOD"] = $rowReqLoan["PERIOD"];
					$arrayReq["REQ_STATUS"] = $rowReqLoan["REQ_STATUS"];
					$arrayReq["REQ_STATUS_DESC"] = $configError["REQ_LOAN_STATUS"][0][$rowReqLoan["REQ_STATUS"]][0][$lang_locale];
					$arrayReq["LOANPERMIT_AMT"] = $rowReqLoan["LOANPERMIT_AMT"];
					$arrayReq["CONTSIGN_AMT"] = number_format($rowReqLoan["CONTSIGN_AMT"],2);
					$arrayReq["CONTSIGN_DATE"] = $lib->convertdate($rowReqLoan["CONTSIGN_DATE"],'d M Y');				
					$arrayReq["REMARK"] = $rowReqLoan["REMARK"];
					$arrayReq["APPROVE_DATE"] = isset($rowReqLoan["APPROVE_DATE"]) && $rowReqLoan["APPROVE_DATE"] != "" ? $lib->convertdate($rowReqLoan["APPROVE_DATE"],'d m Y') : null;
					$arrGrpReq[] = $arrayReq;
				}
			}
			
			$arrayLoanStatusList = [];
			$arrayStatusItem = [];
			$arrayStatusItem["STATUS"] = "all";
			$arrayStatusItem["DESC"] = "ทั้งหมด";
			$arrayLoanStatusList[] = $arrayStatusItem;
			$arrayStatusItem = [];
			$arrayStatusItem["STATUS"] = "0";
			$arrayStatusItem["DESC"] = "อนุมัติ/รอทำสัญญา";
			$arrayLoanStatusList[] = $arrayStatusItem;
			$arrayStatusItem = [];
			$arrayStatusItem["STATUS"] = "-9";
			$arrayStatusItem["DESC"] = "ไม่อนุมัติ";
			$arrayLoanStatusList[] = $arrayStatusItem;
			$arrayStatusItem = [];
			$arrayStatusItem["STATUS"] = "8";
			$arrayStatusItem["DESC"] = "อยู่ระหว่างดำเนินการ";
			$arrayLoanStatusList[] = $arrayStatusItem;
			$arrayStatusItem = [];
			$arrayStatusItem["STATUS"] = "1";
			$arrayStatusItem["DESC"] = "ทำสัญญาแล้ว";
			$arrayLoanStatusList[] = $arrayStatusItem;
			
			
			$arrayResult['REQ_LIST'] = $arrGrpReq;
			$arrayResult['Limit'] = $arrLimit;
			$arrayResult['FILTER'] = $arrayLoanStatusList;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$arrayResult['RESPONSE_CODE'] = "WS0114";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			http_response_code(403);
			require_once('../../include/exit_footer.php');
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
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