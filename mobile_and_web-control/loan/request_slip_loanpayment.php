<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component','loancontract_no','principle','interest','payment_amt','is_confirm'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$arrAllLoan = array();
		
		$getContract = $conmssql->prepare("SELECT lt.LOANTYPE_DESC AS LOAN_TYPE,RTRIM(ln.LOANCONTRACT_NO) as LOANCONTRACT_NO,ln.principal_balance as LOAN_BALANCE,ln.LOANTYPE_CODE,lt.LOANGROUP_CODE, 
											ln.loanapprove_amt as APPROVE_AMT,ln.STARTCONT_DATE,ln.PERIOD_PAYMENT,ln.period_payamt as PERIOD,
											ln.LAST_PERIODPAY as LAST_PERIOD,
											(SELECT max(operate_date) FROM lncontstatement WHERE loancontract_no = ln.loancontract_no) as LAST_OPERATE_DATE
											FROM lncontmaster ln LEFT JOIN LNLOANTYPE lt ON ln.LOANTYPE_CODE = lt.LOANTYPE_CODE 
											WHERE ln.member_no = :member_no and ln.contract_status > 0 and ln.contract_status <> 8
											AND  ln.loancontract_no = :loancontract_no");
		$getContract->execute([
			':member_no' => $member_no,
			':loancontract_no' => $dataComing["loancontract_no"]
		]);
		$rowContract = $getContract->fetch(PDO::FETCH_ASSOC);
		$interest = $cal_loan->calculateInterestRevert($rowContract["LOANCONTRACT_NO"]);
		/*if(date("d") == 7){
			$arrayResult['RESPONSE_CODE'] = "";
			$arrayResult['RESPONSE_MESSAGE'] = "ปิดรับชำระหนี้แล้ว กรุณาทำรายการใหม่อีกครั้งในภายหลัง";
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
		}else */
		if($rowContract["LOANGROUP_CODE"] == '01'){
			if($dataComing["payment_amt"] < ($rowContract["LOAN_BALANCE"] + $interest)){
				$arrayResult['RESPONSE_CODE'] = "";
				$arrayResult['RESPONSE_MESSAGE'] = "การชำระหนี้ เงินกู้ฉุกเฉิน ต้องชำระหนี้คงเหลือทั้งหมดที่เป็นเงินต้น+ดอกเบี้ย";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		}else{
			if($dataComing["payment_amt"] < $interest){
				$arrayResult['RESPONSE_CODE'] = "";
				$arrayResult['RESPONSE_MESSAGE'] = "ยอดชำระหนี้ต้องมากกว่าดอกเบี้ย ณ วันที่จ่าย";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		}
		
		if($dataComing["is_confirm"] == '1'){
			$slipPayment = null;
			if(isset($dataComing["upload_slip"]) && $dataComing["upload_slip"] != ""){
				$subpath = 'slip'.$payload["member_no"].$dataComing["loancontract_no"].time();
				$destination = __DIR__.'/../../resource/slippaydept';
				$data_Img = explode(',',$dataComing["upload_slip"]);
				$info_img = explode('/',$data_Img[0]);
				$ext_img = str_replace('base64','',$info_img[1]);
				if(!file_exists($destination)){
					mkdir($destination, 0777, true);
				}
				if($ext_img == 'png' || $ext_img == 'jpg' || $ext_img == 'jpeg'){
					$createImage = $lib->base64_to_img($dataComing["upload_slip"],$subpath,$destination,null);
				}else if($ext_img == 'pdf'){
					$createImage = $lib->base64_to_pdf($dataComing["upload_slip"],$subpath,$destination);
				}
				if($createImage == 'oversize'){
					$arrayResult['RESPONSE_CODE'] = "WS0008";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
					
				}else{
					if($createImage){
						$directory = __DIR__.'/../../resource/slippaydept';
						$fullPathSalary = __DIR__.'/../../resource/slippaydept/'.$createImage["normal_path"];
						$slipPayment = $config["URL_SERVICE"]."resource/slippaydept/".$createImage["normal_path"];
					}
				}
			}
			
			if(isset($slipPayment) && $slipPayment != ""){
				$payment_acc = null;
				if($dataComing["payment_acc"] == 0){
					$payment_acc = "ธนาคารกรุงเทพ - นวนคร 083-052-3395";
				}else if($dataComing["payment_acc"] == 1){
					$payment_acc = "ธนาคารกสิกรไทย - นวนคร 405-240-5219";
				}else if($dataComing["payment_acc"] == 2){
					$payment_acc = "ธนาคารไทยพาณิชย์ - คลองหลวง 314-417-3129";
				}
				$InsertSlipPayDept = $conmysql->prepare("INSERT INTO gcslippaydept(member_no, loancontract_no, principle, interest, payment_amt, slip_url, payment_acc) 
													VALUES (:member_no, :loancontract_no, :principle, :interest, :payment_amt, :slip_url, :payment_acc)");
				if($InsertSlipPayDept->execute([
					':member_no' => $payload["member_no"],
					':loancontract_no' => $dataComing["loancontract_no"],
					':principle' => str_replace(',', '', $dataComing["principle"]),
					':interest' => str_replace(',', '', $dataComing["interest"]),
					':payment_amt' => str_replace(',', '', $dataComing["payment_amt"]),
					':slip_url' => $slipPayment,
					':payment_acc' => $payment_acc,
				])){
				
				}else{
					$filename = basename(__FILE__, '.php');
					$logStruc = [
						":error_menu" => $filename,
						":error_code" => "WS1036",
						":error_desc" => "อัปโหลดสลิปไม่ได้เพราะ Insert ลงตาราง gcslippaydept ไม่ได้"."\n"."Query => ".$InsertSlipPayDept->queryString."\n"."Param => ". json_encode([
							':member_no' => $payload["member_no"],
							':loancontract_no' => $dataComing["loancontract_no"],
							':principle' => $dataComing["principle"],
							':interest' => $dataComing["interest"],
							':payment_amt' => $dataComing["payment_amt"],
							':slip_url' => $slipPayment
						]),
						":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
					];
					$log->writeLog('errorusage',$logStruc);
					$message_error = "อัปโหลดสลิปไม่ได้เพราะ Insert ลง gcslippaydept ไม่ได้"."\n"."Query => ".$InsertSlipPayDept->queryString."\n"."Param => ". json_encode([
							':member_no' => $payload["member_no"],
							':loancontract_no' => $dataComing["loancontract_no"],
							':principle' => $dataComing["principle"],
							':interest' => $dataComing["interest"],
							':payment_amt' => $dataComing["payment_amt"],
							':slip_url' => $slipPayment
					]);
					$lib->sendLineNotify($message_error);
					$arrayResult['RESPONSE_CODE'] = "WS1036";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
				}
				
				$arrayResult['LOANCONTRACT_NO'] = $dataComing["loancontract_no"];
				$arrayResult['PRINCIPLE'] = $dataComing["principle"];
				$arrayResult['INTEREST'] =  $dataComing["interest"];
				$arrayResult['PAYMENT_AMT'] =  $dataComing["payment_amt"];
				$arrayResult['PAYMENT_ACC'] = $payment_acc;
				$arrayResult['SLIP_URL'] = $slipPayment;
				$arrayResult['OPERATE_DATE'] = $lib->convertdate(date("Y-m-d h:i:s"),"D m Y",true);
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE_CODE'] = "";
				$arrayResult['RESPONSE_MESSAGE'] = "อัปโหลดสลิปล้มเหลว กรุณาติดต่อสหกรณ์หรือลองใหม่อีกครั้งในภายหลัง";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		}else{
			$arrayResult['RESULT'] = TRUE;
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