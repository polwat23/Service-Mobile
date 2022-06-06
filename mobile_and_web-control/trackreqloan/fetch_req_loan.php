<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanRequestTrack')){
		$arrGrpReq = array();
		$arrLimit = array();
		$limit = $func->getConstant('limit_loanrequest');
		$arrayResult['LIMIT_DURATION'] = $limit;
		$date_before = date('Y-m-d',strtotime('-'.$limit.' months'));
		$date_now = date('Y-m-d');
		$Contractno = null;
		$fetchContractTypeCheck = $conmysql->prepare("SELECT balance_status FROM gcconstantbalanceconfirm WHERE member_no = :member_no");
		$fetchContractTypeCheck->execute([':member_no' => $payload["ref_memno"]]);
		$rowContractnoCheck = $fetchContractTypeCheck->fetch(PDO::FETCH_ASSOC);
		$Contractno  = $rowContractnoCheck["balance_status"] || "0" ;
		
		if($Contractno == "0"){
			if($dataComing["req_status"] == "8"){ //อยู่ระหว่างดำเนินการ
			$fetchReqLoan = $conoracle->prepare("select LCREQLOAN.LOANREQUEST_DOCNO as REQLOAN_DOC, 
											LCREQLOAN.MEMBER_NO,    
											LCREQLOAN.LOANREQUEST_DATE as APPROVE_DATE,    
											LCREQLOAN.LOANREQUEST_AMT  as REQUEST_AMT, 
											LCREQLOAN.LOANCREDIT_AMT  as LOANPERMIT_AMT, 
											LCREQLOAN.PERIOD_PAYMENT  as PERIOD_PAYMENT, 
											LCREQLOAN.PERIOD_INSTALLMENT as PERIOD, 
											LCCFLOANTYPE.LOANTYPE_CODE as LOANTYPE_CODE,
											LCCFLOANTYPE.LOANTYPE_DESC as LOANTYPE_DESC,         
											'8' as REQ_STATUS,					
											8 as request_flag 
											from lcreqloan LEFT  JOIN   LCCFLOANTYPE  ON  lcreqloan.LOANTYPE_CODE = LCCFLOANTYPE.LOANTYPE_CODE
											where 
											lcreqloan.member_no= :member_no and  lcreqloan.loanrequest_date BETWEEN to_date(:datebefore,'YYYY-MM-DD') and to_date(:datenow,'YYYY-MM-DD') and 
											lcreqloan.approve_date is null and lcreqloan.loanrequest_status = 8 ");
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
					$arrayReq["REQ_STATUS_DESC"] = $configError["REQ_LOAN_STATUS"][0][$rowReqLoan["REQUEST_FLAG"]][0][$lang_locale];
					//$arrayReq["LOANPERMIT_AMT"] = $rowReqLoan["LOANPERMIT_AMT"];
					$arrayReq["REMARK"] = $rowReqLoan["REMARK"];
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
										'-9' as REQ_STATUS,
										LCREQLOAN.REMARK as REMARK,
										0 as request_flag,
										LCREQLOAN.CONTSIGN_INTRATE
										FROM LCREQLOAN,      
										LCCFLOANTYPE
										WHERE  LCREQLOAN.BRANCH_ID = LCCFLOANTYPE.BRANCH_ID   
										AND LCREQLOAN.LOANTYPE_CODE = LCCFLOANTYPE.LOANTYPE_CODE   
										AND LCREQLOAN.LOANREQUEST_STATUS =  0
										AND LCREQLOAN.member_no = :member_no
										AND lcreqloan.LOANAPPROVE_DATE BETWEEN to_date(:datebefore,'YYYY-MM-DD') and to_date(:datenow,'YYYY-MM-DD') ");
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
					$arrayReq["REQ_STATUS_DESC"] = $configError["REQ_LOAN_STATUS"][0][$rowReqLoan["REQUEST_FLAG"]][0][$lang_locale];
					//$arrayReq["LOANPERMIT_AMT"] = $rowReqLoan["LOANPERMIT_AMT"];
					$arrayReq["CONTSIGN_INTRATE"] = number_format($rowReqLoan["CONTSIGN_INTRATE"])." %";
					$arrayReq["REMARK"] = $rowReqLoan["REMARK"];
					$arrGrpReq[] = $arrayReq;
				}
			}else if($dataComing["req_status"] == "0"){ //อนุมัติ/รอทำสัญญา 
					$fetchReqLoan = $conoracle->prepare("select LCREQLOAN.LOANREQUEST_DOCNO as REQLOAN_DOC, 
										LCREQLOAN.MEMBER_NO,    
										LCREQLOAN.LOANREQUEST_DATE as APPROVE_DATE,    
										LCREQLOAN.LOANREQUEST_AMT  as REQUEST_AMT, 
										LCREQLOAN.LOANCREDIT_AMT  as LOANPERMIT_AMT, 
										LCREQLOAN.PERIOD_PAYMENT  as PERIOD_PAYMENT, 
										LCREQLOAN.PERIOD_INSTALLMENT as PERIOD, 
										LCCFLOANTYPE.LOANTYPE_DESC as LOANTYPE_DESC,         
										'0' as REQ_STATUS,
										LCREQLOAN.REMARK as REMARK,
										1 as request_flag ,
										LCREQLOAN.CONTSIGN_INTRATE,
										from lcreqloan LEFT JOIN LCCFLOANTYPE ON lcreqloan.loantype_code  = LCCFLOANTYPE.loantype_code
										where lcreqloan.member_no= :member_no
										and lcreqloan.loanrequest_date  BETWEEN TO_DATE(:datebefore,'YYYY-MM-DD') and TO_DATE(:datenow,'YYYY-MM-DD')
										and lcreqloan.approve_date is not null and lcreqloan.loanrequest_status = 1  
										and  ( select count(lccontmaster.contsign_status) from lccontmaster 
										where lccontmaster.contsign_status = 1 and lccontmaster.contsign_date is not null 
										and lccontmaster.contract_status <> -9 and lccontmaster.loancontract_no =lcreqloan.loancontract_no ) = 0");
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
					$arrayReq["REQ_STATUS_DESC"] = $configError["REQ_LOAN_STATUS"][0][$rowReqLoan["REQUEST_FLAG"]][0][$lang_locale];
					//$arrayReq["LOANPERMIT_AMT"] = $rowReqLoan["LOANPERMIT_AMT"];
					$arrayReq["CONTSIGN_INTRATE"] = number_format($rowReqLoan["CONTSIGN_INTRATE"],2)." %";
					$arrayReq["REMARK"] = $rowReqLoan["REMARK"];
					$arrGrpReq[] = $arrayReq;
				}
			}else if($dataComing["req_status"] == "1"){ // ทำสัญญาแล้ว
					$fetchReqLoan = $conoracle->prepare("select LCREQLOAN.LOANREQUEST_DOCNO as REQLOAN_DOC, 
													LCREQLOAN.MEMBER_NO,    
													LCREQLOAN.LOANREQUEST_DATE as APPROVE_DATE,    
													LCREQLOAN.LOANREQUEST_AMT  as REQUEST_AMT, 
													LCREQLOAN.LOANCREDIT_AMT  as LOANPERMIT_AMT, 
													LCREQLOAN.PERIOD_PAYMENT  as PERIOD_PAYMENT, 
													LCREQLOAN.PERIOD_INSTALLMENT as PERIOD, 
													LCCFLOANTYPE.LOANTYPE_CODE as LOANTYPE_CODE,
													LCCFLOANTYPE.LOANTYPE_DESC as LOANTYPE_DESC,         
													'1'as REQ_STATUS,
													LCREQLOAN.REMARK as REMARK,
													lccontmaster.CONTSIGN_DATE,,
													LCREQLOAN.CONTSIGN_INTRATE,
													FT_GETCONTINTRATE(LCCONTMASTER.branch_id,LCCONTMASTER.loancontract_no,sysdate) as INT_CONTINTRATE,
													2 as request_flag 
													from lcreqloan LEFT JOIN  LCCFLOANTYPE ON  lcreqloan.LOANTYPE_CODE = LCCFLOANTYPE.LOANTYPE_CODE
													LEFT JOIN LCCONTMASTER ON lcreqloan.LOANREQUEST_DOCNO = LCCONTMASTER.LOANREQUEST_DOCNO
													where lcreqloan.member_no= :member_no
													AND lcreqloan.loanrequest_date BETWEEN to_date(:datebefore,'YYYY-MM-DD') and to_date(:datenow,'YYYY-MM-DD')
													and lcreqloan.approve_date is not null and lcreqloan.loanrequest_status = 1  and  
													(select count(lccontmaster.contsign_status) from lccontmaster 
													where lccontmaster.contsign_status = 1 and lccontmaster.contsign_date is not null 
													and lccontmaster.contract_status <> -9 and lccontmaster.loancontract_no =lcreqloan.loancontract_no ) >0");
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
					$arrayReq["REQ_STATUS_DESC"] = $configError["REQ_LOAN_STATUS"][0][$rowReqLoan["REQUEST_FLAG"]][0][$lang_locale];
					//$arrayReq["LOANPERMIT_AMT"] = $rowReqLoan["LOANPERMIT_AMT"];
					$arrayReq["CONTSIGN_AMT"] = $rowReqLoan["CONTSIGN_AMT"];
					$arrayReq["CONTSIGN_DATE"] = $lib->convertdate($rowReqLoan["CONTSIGN_DATE"],'d M Y');
					$arrayReq["CONTSIGN_INTRATE"] = number_format($rowReqLoan["CONTSIGN_INTRATE"],2)." %";
					$arrayReq["REMARK"] = $rowReqLoan["REMARK"];
					$arrGrpReq[] = $arrayReq;
				}
			}else{
				$fetchReqLoan = $conoracle->prepare("select LCREQLOAN.LOANREQUEST_DOCNO as REQLOAN_DOC, 
												LCREQLOAN.MEMBER_NO,    
												LCREQLOAN.LOANREQUEST_DATE as APPROVE_DATE,    
												LCREQLOAN.LOANREQUEST_AMT  as REQUEST_AMT, 
												LCREQLOAN.LOANCREDIT_AMT  as LOANPERMIT_AMT, 
												LCREQLOAN.PERIOD_PAYMENT  as PERIOD_PAYMENT, 
												LCREQLOAN.PERIOD_INSTALLMENT as PERIOD, 
												LCCFLOANTYPE.LOANTYPE_CODE as LOANTYPE_CODE,
												LCCFLOANTYPE.LOANTYPE_DESC as LOANTYPE_DESC,         
												'8' as REQ_STATUS,
												SYSDATE as CONTSIGN_DATE,
												LCREQLOAN.REMARK as REMARK,
												LCREQLOAN.CONTSIGN_INTRATE as  INT_CONTINTRATE,
												8 as request_flag 
												from lcreqloan LEFT  JOIN   LCCFLOANTYPE  ON  lcreqloan.LOANTYPE_CODE = LCCFLOANTYPE.LOANTYPE_CODE
												where 
												lcreqloan.member_no= :member_no and  lcreqloan.loanrequest_date BETWEEN to_date(:datebefore,'YYYY-MM-DD') and to_date(:datenow,'YYYY-MM-DD') and 
												lcreqloan.approve_date is null and lcreqloan.loanrequest_status = 8 

												UNION
												SELECT   LCREQLOAN.LOANREQUEST_DOCNO as REQLOAN_DOC, 
												LCREQLOAN.MEMBER_NO,    
												LCREQLOAN.LOANREQUEST_DATE as APPROVE_DATE,    
												LCREQLOAN.LOANREQUEST_AMT  as REQUEST_AMT, 
												LCREQLOAN.LOANCREDIT_AMT  as LOANPERMIT_AMT, 
												LCREQLOAN.PERIOD_PAYMENT  as PERIOD_PAYMENT, 
												LCREQLOAN.PERIOD_INSTALLMENT as PERIOD, 
												LCCFLOANTYPE.LOANTYPE_CODE as LOANTYPE_CODE,
												LCCFLOANTYPE.LOANTYPE_DESC as LOANTYPE_DESC,         
												'-9' as REQ_STATUS,
												SYSDATE as CONTSIGN_DATE,
												LCREQLOAN.REMARK as REMARK,
												LCREQLOAN.CONTSIGN_INTRATE as  INT_CONTINTRATE,
												0 as request_flag
												FROM LCREQLOAN,      
												LCCFLOANTYPE
												WHERE  LCREQLOAN.BRANCH_ID = LCCFLOANTYPE.BRANCH_ID   
												AND LCREQLOAN.LOANTYPE_CODE = LCCFLOANTYPE.LOANTYPE_CODE   
												AND LCREQLOAN.LOANREQUEST_STATUS =  0
												AND LCREQLOAN.member_no = :member_no
												AND lcreqloan.LOANAPPROVE_DATE BETWEEN to_date(:datebefore,'YYYY-MM-DD') and to_date(:datenow,'YYYY-MM-DD')

												UNION 
												select  LCREQLOAN.LOANREQUEST_DOCNO as REQLOAN_DOC, 
												LCREQLOAN.MEMBER_NO,    
												LCREQLOAN.LOANREQUEST_DATE as APPROVE_DATE,    
												LCREQLOAN.LOANREQUEST_AMT  as REQUEST_AMT, 
												LCREQLOAN.LOANCREDIT_AMT  as LOANPERMIT_AMT, 
												LCREQLOAN.PERIOD_PAYMENT  as PERIOD_PAYMENT, 
												LCREQLOAN.PERIOD_INSTALLMENT as PERIOD, 
												LCCFLOANTYPE.LOANTYPE_CODE as LOANTYPE_CODE,
												LCCFLOANTYPE.LOANTYPE_DESC as LOANTYPE_DESC,         
												'0' as REQ_STATUS,
												SYSDATE as CONTSIGN_DATE,
												LCREQLOAN.REMARK as REMARK,
												LCREQLOAN.CONTSIGN_INTRATE as  INT_CONTINTRATE,
												1 as request_flag 
												from lcreqloan LEFT JOIN LCCFLOANTYPE ON lcreqloan.loantype_code  = LCCFLOANTYPE.loantype_code
												where lcreqloan.member_no= :member_no
												and lcreqloan.loanrequest_date  BETWEEN TO_DATE(:datebefore,'YYYY-MM-DD') and TO_DATE(:datenow,'YYYY-MM-DD')
												and lcreqloan.approve_date is not null and lcreqloan.loanrequest_status = 1  
												and  ( select count(lccontmaster.contsign_status) from lccontmaster 
												where lccontmaster.contsign_status = 1 and lccontmaster.contsign_date is not null 
												and lccontmaster.contract_status <> -9 and lccontmaster.loancontract_no =lcreqloan.loancontract_no ) = 0
												UNION
												select  LCREQLOAN.LOANREQUEST_DOCNO as REQLOAN_DOC, 
												LCREQLOAN.MEMBER_NO,    
												LCREQLOAN.LOANREQUEST_DATE as APPROVE_DATE,    
												LCREQLOAN.LOANREQUEST_AMT  as REQUEST_AMT, 
												LCREQLOAN.LOANCREDIT_AMT  as LOANPERMIT_AMT, 
												LCREQLOAN.PERIOD_PAYMENT  as PERIOD_PAYMENT, 
												LCREQLOAN.PERIOD_INSTALLMENT as PERIOD, 
												LCCFLOANTYPE.LOANTYPE_CODE as LOANTYPE_CODE,
												LCCFLOANTYPE.LOANTYPE_DESC as LOANTYPE_DESC,         
												'1' as REQ_STATUS,
												lccontmaster.CONTSIGN_DATE,
												LCREQLOAN.REMARK as REMARK,
												FT_GETCONTINTRATE(LCCONTMASTER.branch_id,LCCONTMASTER.loancontract_no,sysdate) as INT_CONTINTRATE,
												2 as request_flag 
												from lcreqloan LEFT JOIN  LCCFLOANTYPE ON  lcreqloan.LOANTYPE_CODE = LCCFLOANTYPE.LOANTYPE_CODE
												LEFT JOIN lccontmaster ON lcreqloan.LOANREQUEST_DOCNO = lccontmaster.LOANREQUEST_DOCNO
												where lcreqloan.member_no= :member_no
												AND lcreqloan.loanrequest_date BETWEEN to_date(:datebefore,'YYYY-MM-DD') and to_date(:datenow,'YYYY-MM-DD')
												and lcreqloan.approve_date is not null and lcreqloan.loanrequest_status = 1  and  
												(select count(lccontmaster.contsign_status) from lccontmaster 
												where lccontmaster.contsign_status = 1 and lccontmaster.contsign_date is not null 
												and lccontmaster.contract_status <> -9 and lccontmaster.loancontract_no =lcreqloan.loancontract_no ) >0");
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
					$arrayReq["REQ_STATUS_DESC"] = $configError["REQ_LOAN_STATUS"][0][$rowReqLoan["REQUEST_FLAG"]][0][$lang_locale];
					//$arrayReq["LOANPERMIT_AMT"] = $rowReqLoan["LOANPERMIT_AMT"];
					$arrayReq["CONTSIGN_AMT"] = $rowReqLoan["CONTSIGN_AMT"];
					$arrayReq["CONTSIGN_DATE"] = $rowReqLoan["REQUEST_FLAG"] == "2" ? $lib->convertdate($rowReqLoan["CONTSIGN_DATE"],'d M Y') : null;
					$arrayReq["CONTSIGN_INTRATE"] = number_format($rowReqLoan["INT_CONTINTRATE"],2)." %";
					$arrayReq["REMARK"] = $rowReqLoan["REMARK"];
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