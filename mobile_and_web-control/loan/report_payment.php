<?php
require_once('../autoload.php');

use Dompdf\Dompdf;

$dompdf = new DOMPDF();

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanInfo')){
		$member_no = $configAS[$payload["ref_memno"]] ?? $payload["ref_memno"];
		$loancontract_no = $dataComing["contract_no"];
		$sum_principal_payment = 0; 
		$sum_bfprnbal_amt  = 0; 
		$sum_bfintarrear_amt = 0;
		$sum_bfintmtharr_amt = 0;
		$sum_item_payment = 0;
		$arrHeader = array();
		$arrDetail = array();
		$limit = $func->getConstant('limit_stmloan');
		if($lib->checkCompleteArgument(["date_start"],$dataComing)){
			$date_before = $lib->convertdate($dataComing["date_start"],'y-n-d');
		}else{
			$date_before = date('Y-m-d',strtotime('-'.$limit.' months'));
		}
		if($lib->checkCompleteArgument(["date_end"],$dataComing)){
			$date_now = $lib->convertdate($dataComing["date_end"],'y-n-d');
		}else{
			$date_now = date('Y-m-d');
		}
		$getBalanceLoan = $conoracle->prepare("SELECT MBUCFPRENAME.SHTPRENAME_DESC || MBMEMBMASTER.MEMB_NAME  ||' '||MBUCFPRENAME.SHTSUFFNAME_DESC as COOP_NAME, 
											SLSLIPPAYOUT.PAYOUTSLIP_NO ,
											SLSLIPPAYOUT.SLIPTYPE_CODE ,
											SLSLIPPAYOUT.MEMBER_NO ,
											SLSLIPPAYOUT.SLIP_DATE ,
											SLSLIPPAYOUT.LOANCONTRACT_NO ,
											SLSLIPPAYOUT.RCV_PERIOD ,
											SLSLIPPAYOUT.PAYOUT_AMT ,
											SLSLIPPAYOUT.PAYOUTCLR_AMT ,
											SLSLIPPAYOUT.PAYOUTNET_AMT ,
											SLSLIPPAYOUT.SLIP_STATUS ,
											SLSLIPPAYOUT.SLIPCLEAR_NO ,
											LCCFLOANTYPE.LOANGROUP_CODE
											FROM SLSLIPPAYOUT ,	MBMEMBMASTER , MBUCFPRENAME , LCCFLOANTYPE , LCCONTMASTER
											WHERE ( SLSLIPPAYOUT.MEMBRANCH_ID = MBMEMBMASTER.BRANCH_ID )
											AND ( SLSLIPPAYOUT.MEMBER_NO = MBMEMBMASTER.MEMBER_NO )
											AND ( MBMEMBMASTER.PRENAME_CODE = MBUCFPRENAME.PRENAME_CODE )
											AND ( SLSLIPPAYOUT.LOANCONTRACT_NO = LCCONTMASTER.LOANCONTRACT_NO )
											AND ( LCCONTMASTER.LOANTYPE_CODE = LCCFLOANTYPE.LOANTYPE_CODE )
											AND ( SLSLIPPAYOUT.SLIPTYPE_CODE = 'LWD' )
											AND ( LCCONTMASTER.LOANCONTRACT_NO = :loancontract_no )
											AND (SLSLIPPAYOUT.SLIP_DATE BETWEEN to_date(:datebefore,'YYYY-MM-DD') and to_date(:datenow,'YYYY-MM-DD'))
											AND ( SLSLIPPAYOUT.SLIP_STATUS = 1 )
											AND ( SLSLIPPAYOUT.PAYOUTCONFIRM_STATUS = 1 )
											ORDER BY SLSLIPPAYOUT.PAYOUTSLIP_NO ");
		$getBalanceLoan->execute([
			':loancontract_no' => $loancontract_no,
			':datebefore' => $date_before,
			':datenow' => $date_now
		]);
		while($rowInvoiceLoan = $getBalanceLoan->fetch(PDO::FETCH_ASSOC)){
			$arrBalDetail = array();
			$arrBalDetail["SLIP_DATE"] =  $lib->convertdate($rowInvoiceLoan["SLIP_DATE"],'d/n/Y');	//วันที่ชำระ  
			$arrBalDetail["PAYOUTSLIP_NO"] = $rowInvoiceLoan["PAYOUTSLIP_NO"];
			$arrBalDetail["LOANCONTRACT_NO"] = $rowInvoiceLoan["LOANCONTRACT_NO"]; 
			$arrBalDetail["MEMBER_NO"] = $rowInvoiceLoan["MEMBER_NO"];
			$arrBalDetail["COOP_NAME"] = $rowInvoiceLoan["COOP_NAME"];
			$arrBalDetail["RCV_PERIOD"] = $rowInvoiceLoan["RCV_PERIOD"];
			$arrBalDetail["PAYOUT_AMT"] = $rowInvoiceLoan["PAYOUT_AMT"];
			$arrBalDetail["PAYOUTNET_AMT"] = $rowInvoiceLoan["PAYOUTNET_AMT"];
			$arrBalDetail["PAYOUTCLR_AMT"] = $rowInvoiceLoan["PAYOUTCLR_AMT"];
			
			//หัก
			$arrBalDetail["RECEIVE"] = array();
			$getReceiveLoan = $conoracle->prepare("SELECT SLSLIPPAYINDET.SLIPITEMTYPE_CODE,   
												SLSLIPPAYINDET.LOANCONTRACT_NO,   
												SLSLIPPAYINDET.PRINCIPAL_PAYAMT,   
												SLSLIPPAYINDET.INTEREST_PAYAMT,   
												SLSLIPPAYINDET.ITEM_PAYAMT,   
												SLUCFSLIPITEMTYPE.SLIPITEMTYPE_CODE,
												(CASE WHEN SLUCFSLIPITEMTYPE.SLIPITEMTYPE_CODE = 'LON' then 'สัญญา'  || ' ' ||  SLSLIPPAYINDET.LOANCONTRACT_NO
												else TRIM(SLUCFSLIPITEMTYPE.SLIPITEMTYPE_DESC)  end  ) as SLIPITEMTYPE_DESC
												FROM SLSLIPPAYINDET,   
												SLUCFSLIPITEMTYPE,   
												SLSLIPPAYIN  
												WHERE ( SLSLIPPAYINDET.SLIPITEMTYPE_CODE = SLUCFSLIPITEMTYPE.SLIPITEMTYPE_CODE ) and  
												( SLSLIPPAYINDET.BRANCH_ID = SLSLIPPAYIN.BRANCH_ID ) and  
												( SLSLIPPAYINDET.PAYINSLIP_NO = SLSLIPPAYIN.PAYINSLIP_NO ) and  
												( slslippayin.ref_slipno = :as_slipno ) AND  
												( slslippayindet.operate_flag = 1 ) AND  
												( slslippayin.ref_system = 'LRC')");
			$getReceiveLoan->execute([':as_slipno' => $arrBalDetail["PAYOUTSLIP_NO"]]);
			while($rowReceiveLoan = $getReceiveLoan->fetch(PDO::FETCH_ASSOC)){
				$arrayReceive = array();
				$arrayReceive["SLIPITEMTYPE_DESC"] = $rowReceiveLoan["SLIPITEMTYPE_DESC"];
				$arrayReceive["SLIPITEMTYPE_CODE"] = $rowReceiveLoan["SLIPITEMTYPE_CODE"];
				$arrayReceive["PRINCIPAL_PAYAMT"] = $rowReceiveLoan["PRINCIPAL_PAYAMT"];
				$arrayReceive["INTEREST_PAYAMT"] = $rowReceiveLoan["INTEREST_PAYAMT"];
				$arrayReceive["ITEM_PAYAMT"] = $rowReceiveLoan["ITEM_PAYAMT"];	
				$arrBalDetail["RECEIVE"][] = $arrayReceive;
			}
			
			//จ่าย
			$arrBalDetail["PAY"] = array();
			$getPayLoan = $conoracle->prepare("SELECT SLSLIPPAYOUTEXPENSE.MONEYTYPE_CODE,
										'' as MONEYTYPE,
										CMUCFMONEYTYPE.MONEYTYPE_DESC,
										TRIM(SLSLIPPAYOUTEXPENSE.EXPENSE_ACCID) AS EXPENSE_ACCID,
										CMUCFBANK.BANK_SHORTNAME_T,
										SLSLIPPAYOUTEXPENSE.EXPENSE_AMT,
										CMUCFBANK.ACCOUNT_FORMAT,
										CMUCFBANKBRANCH.BRANCH_NAME,
										SLSLIPPAYOUTEXPENSE.SEQ_NO,
										'' AS CHEQUE_NAMEPAY
										FROM SLSLIPPAYOUTEXPENSE,CMUCFBANK,CMUCFMONEYTYPE,CMUCFBANKBRANCH
										WHERE( SLSLIPPAYOUTEXPENSE.EXPENSE_BANK = CMUCFBANK.BANK_CODE ) 
										AND ( SLSLIPPAYOUTEXPENSE.MONEYTYPE_CODE = CMUCFMONEYTYPE.MONEYTYPE_CODE ) 
										AND ( CMUCFBANK.BANK_CODE = CMUCFBANKBRANCH.BANK_CODE ) 
										AND ( SLSLIPPAYOUTEXPENSE.EXPENSE_BRANCH = CMUCFBANKBRANCH.BRANCH_ID ) 
										AND ( SLSLIPPAYOUTEXPENSE.PAYOUTSLIP_NO = :as_slipno )
										AND ( CMUCFMONEYTYPE.MONEYTYPE_GROUP ='CBT' )
										UNION
										SELECT SLSLIPPAYOUTEXPENSE.MONEYTYPE_CODE,
										(CASE WHEN SLSLIPPAYOUTEXPENSE.moneytype_code = 'CHQ' THEN 'สั่งจ่าย' ||' '||  SLSLIPPAYOUTEXPENSE.CHEQUE_NAMEPAY
										WHEN SLSLIPPAYOUTEXPENSE.moneytype_code = 'CHQ' and SLSLIPPAYOUTEXPENSE.moneytype_code = 'DEP' THEN 'เลขที่บัญชี '
										WHEN SLSLIPPAYOUTEXPENSE.moneytype_code = 'CHQ' and  SLSLIPPAYOUTEXPENSE.moneytype_code = 'PRM' THEN 'เลขที่ตั่ว ' 
										else '' end) as MONEYTYPE,
										CMUCFMONEYTYPE.MONEYTYPE_DESC,'','',
										SLSLIPPAYOUTEXPENSE.EXPENSE_AMT,'','',
										SLSLIPPAYOUTEXPENSE.SEQ_NO,
										SLSLIPPAYOUTEXPENSE.CHEQUE_NAMEPAY
										FROM SLSLIPPAYOUTEXPENSE,CMUCFMONEYTYPE
										WHERE( SLSLIPPAYOUTEXPENSE.MONEYTYPE_CODE = CMUCFMONEYTYPE.MONEYTYPE_CODE ) 
										AND ( SLSLIPPAYOUTEXPENSE.PAYOUTSLIP_NO = :as_slipno )
										AND ( CMUCFMONEYTYPE.MONEYTYPE_GROUP <> 'CBT' )");
			$getPayLoan->execute([':as_slipno' => $arrBalDetail["PAYOUTSLIP_NO"]]);
			while($rowPayLoan = $getPayLoan->fetch(PDO::FETCH_ASSOC)){
				$arrayPay = array();
				$arrayPay["MONEYTYPE_CODE"] = $rowPayLoan["MONEYTYPE_CODE"];
				$arrayPay["MONEYTYPE_DESC"] = $rowPayLoan["MONEYTYPE_DESC"];
				$arrayPay["BANK_SHORTNAME_T"] = $rowPayLoan["BANK_SHORTNAME_T"];
				$arrayPay["MONEYTYPE"] = $rowPayLoan["MONEYTYPE"];
				$arrayPay["BRANCH_NAME"] = $rowPayLoan["BRANCH_NAME"];	
				$arrayPay["EXPENSE_ACCID"] = $lib->formataccountReport($rowPayLoan["EXPENSE_ACCID"],"xxx-x-xxxxx-x");	
				$arrayPay["EXPENSE_AMT"] = $rowPayLoan["EXPENSE_AMT"];	
				$arrBalDetail["PAY"][] = $arrayPay;
			}
			
			$sum_payout_amt += $rowInvoiceLoan["PAYOUT_AMT"];
			$sum_payoutclr_amt += $rowInvoiceLoan["PAYOUTCLR_AMT"];
			$sum_payoutnet_amt   += $rowInvoiceLoan["PAYOUTNET_AMT"];
			$date = $lib->convertdate($rowInvoiceLoan["SLIP_DATE"],'d/n/Y');
			$arrDetail[] = $arrBalDetail;	
		}	
		$count =  count($arrDetail); //count เลขสัญญา


		if(sizeof($arrDetail) > 0 ){
			$arrayPDF = GeneratePdfDoc($arrDetail,$sum_payout_amt,$sum_payoutclr_amt,$sum_payoutnet_amt,$lib,$count,$date);
			if($arrayPDF["RESULT"]){
				$arrayResult['REPORT_URL'] = $config["URL_SERVICE"].$arrayPDF["PATH"];
				$arrayResult['INVOICE'] = $arrDetail;
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0044";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
		}else{
			//$arrayResult['INVOICE'] = FALSE;
			$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถออกรายงานได้ เนื่องจากสัญญาถูกปิดไปเเล้ว";
			$arrayResult['MESSAGE'] = $date_before;
			$arrayResult['MESSAGE_01'] = $date_now;
			$arrayResult['RESULT'] = FALSE;
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

function GeneratePdfDoc($arrDetail,$sum_payout_amt,$sum_payoutclr_amt,$sum_payoutnet_amt,$lib,$count,$date){
	$html = '<style>
	@font-face {
	  font-family: TH Niramit AS;
	  src: url(../../resource/fonts/TH Niramit AS.ttf);
	}
	@font-face {
		font-family: TH Niramit AS;
		src: url(../../resource/fonts/TH Niramit AS Bold.ttf);
		font-weight: bold;
	}
	* {
	  font-family: TH Niramit AS;
	}
	.nowrap{
	  white-space: nowrap;
	}
	text-center{
	  text-align:center;
	}
	text-right{
	  text-align:right;
	}

	body {
	  padding: 0 px;
	  font-size: 15px;
	}
	.sub-table div{
		padding :2px 5px;
	}

	</style>
	<div style="position:fixed; left:900px; top:-10px;">
		<div style="display:inline;  font-size:15px;">วันที่พิมพ์</div>
		<div style="display:inline;  font-size:15px;">'.$lib->convertdate(date('d-m-Y'),'d/n/Y').'</div>
		
	</div>
	<div style="margin:-10px -20px -30px -20px ">
	<div style="display: flex;text-align: center;position: relative;"></div>
	<div style="text-align:left;position: absolute;width:100%;margin-left: 120px"></div>';
	$html .= '<div style="margin-top:-10px ;font-size: 22px;font-weight: bold; text-align:center; width:100%">รายการจ่ายเงินกู้ และ การหักกลบ</div>';
	$html .= '<div style="font-size: 20px;font-weight: bold; text-align:center; width:100%">ณ วันที่ ' . ($date ?? "-") . '</div>';
	$html .= '<div style="margin: 10px 0px 0px 0px">
	  <div style="width: 100%;" class="sub-table">
		<div style="border-bottom: 1px solid #3c3939de;border-top: 1px solid #3c3939de;">
		  <div>
			<div style="display:inline;font-weight:bold; margin-left:-15px">วันที่จ่ายเงินกู้</div>
			<div style="display:inline;font-weight:bold;">เลขที่ใบจ่าย</div>
			<div style="display:inline;font-weight:bold; padding-left:20px;">เลขที่สัญญา</div>
			<div style="display:inline;font-weight:bold;">เลขที่สมาชิก</div>
			<div style="display:inline;font-weight:bold; padding:0 110px 0 110px">ชื่อสหกรณ์</div>
			<div style="display:inline;font-weight:bold; padding:0 10px 0 20px">ครั้งที่จ่าย</div>
			<div style="display:inline;font-weight:bold; padding-left:45px;">จำนวนเงินที่จ่าย</div>
			<div style="display:inline;font-weight:bold; padding-left:80px;">หักชำระ</div>
			<div style="display:inline;font-weight:bold;margin-left:105px; ">จ่ายสุทธิ</div>
		  </div>
		  <div>
			<div style="display:inline;font-weight:bold; padding-left: 90px">ประเภทรายการ</div>
			<div style="display:inline;font-weight:bold; padding-left:40px;">ธนาคาร</div>
			<div style="display:inline;font-weight:bold; padding-left:120px;">สาขา</div>
			<div style="display:inline;font-weight:bold; padding-left:200px;">เลขที่บัญชี</div>
			<div style="display:inline;font-weight:bold; padding-left:192px;">จำนวนเงิน</div>
		  </div>
		</div>
	  </div>
	</div>';


	foreach ($arrDetail as $dataArr) {
	  $html .= '
	  <div style="display:flex;height: 30px;padding:0px">
		<div  style=" width:90px; text-align:center;" >' . ($dataArr["SLIP_DATE"] ?? null) . '</div>
		<div  style=" width:90px; margin-left:70;">' . ($dataArr["PAYOUTSLIP_NO"] ?? null) . '</div>
		<div  style=" width:90px; margin-left:138; ">' . ($dataArr["LOANCONTRACT_NO"] ?? null) . '</div>
		<div  style=" width:80px; margin-left:263px;  text-align:center;">' . ($dataArr["MEMBER_NO"] ?? null) . '</div>
		<div  style=" width:320px; margin-left:343px;">' . ($dataArr["COOP_NAME"] ?? null) . '</div>
		<div  style=" width:75px; margin-left:600px;">' . ($dataArr["RCV_PERIOD"] ?? null) . '</div>
		<div  style=" width:120px; margin-left:660px; text-align:right;">' . (number_format($dataArr["PAYOUT_AMT"],2) ?? null) . '</div>
		<div  style=" width:120px; margin-left:795px; text-align:right;">' . (number_format($dataArr["PAYOUTCLR_AMT"],2) ?? null) . '</div>
		<div  style=" width:120px; margin-left:935px; text-align:right;">' . (number_format($dataArr["PAYOUTNET_AMT"],2) ?? null) . '</div>
	</div>';

		//หัก
		$no = 1;
		foreach ($dataArr["RECEIVE"] as $Listdel) {
			if($Listdel["SLIPITEMTYPE_CODE"] == "LON"){
				$html .= '<div style="display:flex;height: 30px;padding:0px">';
				
				if($no == 1) {
				$html .= ' <div style="margin-left:100px;   text-decoration: underline;">หัก</div>
						  <div style="margin-left:230px;"></div>';
				}
				$html .= '
					   <div style="margin-left:130px;">' . $Listdel["SLIPITEMTYPE_DESC"] . '</div>    
				';
				if($no == 1) {
				$html .= '<div style="margin-left:260px;">เงินต้น</div>';
				}
				$html .= '
				  <div style="margin-left:270px; width:150px; text-align:right; ">' .(number_format($Listdel["PRINCIPAL_PAYAMT"],2) ?? null) . '</div>   
				  <div style="margin-left:430px;">บาท</div>';
				if($no == 1) {
				$html .= '  <div style="margin-left:470px;">ดอกเบี้ย</div>';
				}
				$html .= '<div style="margin-left:530px; width:150px; text-align:right; ">' . (number_format($Listdel["INTEREST_PAYAMT"],2) ?? null)  . '</div> ';
				if($no == 1) {
				$html .= '<div style="margin-left:700px;">รวม</div>';
				}
				$html .= ' <div  style=" width:115px; margin-left:800px; text-align:right;">' . (number_format($Listdel["ITEM_PAYAMT"],2) ?? null) . '</div>
				<div style="margin-left:960px">บาท</div>';
				$html .= ' </div> ';
				$no++;
			}else{
				$html .= '
				 <div style="display:flex;height: 30px;padding:0px">
					<div style="margin-left:100px;   text-decoration: underline;">หัก</div>
					<div style="margin-left:130px;">'.($Listdel["SLIPITEMTYPE_DESC"] ?? null).'</div>
					<div style="margin-left:237px;"></div>
					<div style="margin-left:407px; width:220px; "></div>
					<div  style=" width:100px; margin-left:636px;"></div>
					<div  style=" width:115px; margin-left:800px; text-align:right;">' . (number_format($Listdel["ITEM_PAYAMT"],2) ?? null) . '</div>
					<div  style=" width:130px; margin-left:965px; text-align:right;"></div>
					<div  style=" width:130px; margin-left:960px;">บาท</div>
				</div>';
			}  
		}
		
		//จ่าย
		foreach($dataArr["PAY"] as $lisData) {
			if($lisData["MONEYTYPE_CODE"] == "CBT"){	
				$html .= '
				<div style="display:flex;height: 30px;padding:0px">
				<div style="margin-left:100px;   text-decoration: underline;">จ่าย</div>
				<div style="margin-left:130px;">' . ($lisData["MONEYTYPE_DESC"] ?? null) . '</div>
				<div style="margin-left:237px;">' . ($lisData["BANK_SHORTNAME_T"] ?? null) . '</div>
				<div style="margin-left:407px;   width:220px; ">' . ($lisData["BRANCH_NAME"] ?? null) . '</div>
				<div  style=" width:100px; margin-left:600px;">' . ($lisData["EXPENSE_ACCID"] ?? null) . '</div> 
				<div  style=" width:115px; margin-left:800px; text-align:right;">' . (number_format($lisData["EXPENSE_AMT"]) ?? null) . '</div>
				<div  style=" width:130px; margin-left:955px; text-align:right;"></div>
				<div  style=" width:130px; margin-left:950px;">บาท</div>
				</div>';
			}else{
				$html .= '
				<div style="display:flex;height: 30px;padding:0px">
				<div style="margin-left:100px;   text-decoration: underline;">จ่าย</div>
				<div style="margin-left:130px;">' . ($lisData["MONEYTYPE_DESC"] ?? null) . '</div>
				<div style="margin-left:237px;"></div>
				<div style="margin-left:407px;   width:220px; "></div>
				<div  style=" width:100px; margin-left:636px;">' . (number_format($lisData["EXPENSE_AMT"],2) ?? null) . '</div>
				<div  style=" width:115px; margin-left:834px; text-align:right;"></div>
				<div  style=" width:130px; margin-left:965px; text-align:right;"></div>
				</div>
				';
			}	
		}
	}

	$html .= '
	  <div style=" border-top:1px dashed; border-bottom:1px dashed; height:30px; ">
		 <div style="display:flex; font-weight:bold;" class="nowrap" >
			  <div style="width:80px;">รวมจ่ายวันที่</div>
			  <div style=" width:80px; margin-left:80px;">' . ($date ?? null) . '</div>
			  <div style="margin-left:237px;  width:50px;">' . ($count ?? null) . '</div>
			  <div style="margin-left:287px;  width:50px;">สัญญา</div>
			  <div style="margin-left:390px;   width:50px;  ">1</div>
			  <div style="margin-left:440px;   width:50px;  ">สหกรณ์</div>
			  <div style="width:130px; margin-left:650px; text-align:right;">' . (number_format($sum_payout_amt,2) ?? null) . '</div>
			  <div style="width:125px; margin-left:790px; text-align:right;">' . (number_format($sum_payoutclr_amt,2) ?? null) . '</div>
			  <div style="width:100px; margin-left:955px; text-align:right;" >' . (number_format($sum_payoutnet_amt,2) ?? null) . '</div>
			    <div style="width:125px; margin-left:834px; text-align:right;"></div>
		   </div>
		 </div>
	';
	$html .= '</div>';

	$dompdf = new DOMPDF();
	$dompdf->set_paper('A4','landscape');
	$dompdf->load_html($html);
	$dompdf->render();
	$pathfile = __DIR__.'/../../resource/pdf/payment';
	if(!file_exists($pathfile)){
		mkdir($pathfile, 0777, true);
	}
	$pathfile = $pathfile.'/'.$arrDetail[0]["LOANCONTRACT_NO"].'.pdf';
	$pathfile_show = '/resource/pdf/payment/'.$arrDetail[0]["LOANCONTRACT_NO"].'.pdf?v='.time();
	$arrayPDF = array();
	$output = $dompdf->output();
	if(file_put_contents($pathfile, $output)){
		$arrayPDF["RESULT"] = TRUE;
	}else{
		$arrayPDF["RESULT"] = FALSE;
	}
	$arrayPDF["PATH"] = $pathfile_show;
	return $arrayPDF; 
}
?>