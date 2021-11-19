<?php
require_once('../autoload.php');

use Dompdf\Dompdf;

$dompdf = new DOMPDF();

if($lib->checkCompleteArgument(['menu_component','recv_period'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SlipInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$receipt_No = $dataComing["recv_period"];
		$header = array();
		$fetchName = $conmssqlcoop->prepare("SELECT DISTINCT
											coCooptation.Prefixname as PRENAME_DESC, 
											coCooptation.Firstname as MEMB_NAME, 
											coCooptation.Lastname as MEMB_SURNAME, 
											coCooptation.Member_Id as MEMBER_NO, 
											coCooptation.department, 
											coDepartment.description, 
											coCompany.Description as  company_desc
											FROM   coReceipt LEFT JOIN  coCooptation ON coReceipt.Member_Id=coCooptation.Member_Id
											LEFT JOIN  coDepartment ON coCooptation.Company = coDepartment.Company AND coCooptation.Department=coDepartment.Department 
											LEFT JOIN coCompany ON coDepartment.Company=coCompany.Company
											WHERE coReceipt.status ='2' AND coReceipt.member_id = :member_no");
		$fetchName->execute([
			':member_no' => $member_no
		]);
		$rowName = $fetchName->fetch(PDO::FETCH_ASSOC);	
		$header["FULLNAME"] = $rowName["PRENAME_DESC"].' '.$rowName["MEMB_NAME"].' '.$rowName["MEMB_SURNAME"];
		$header["MEMBER_NO"] = $rowName["MEMBER_NO"];
		$header["receipt_No"] = $receipt_No;
		$header["company_desc"] = $rowName["company_desc"];
		$header["department_desc"] = "[".$rowName["department"]."]" ." ". TRIM($rowName["description"]);
		
		$getPaymentDetail = $conmssqlcoop->prepare("SELECT coReceipt.Receipt_No, 
											coReceipt.Paydate,
											coReceipt.Amount, 
											coReceipt.Type, 
											coReceipt.Loan_Doc_No,
											coInterestRate_Desc.Description,
											coReceipt.Principal, 
											coReceipt.Interest, 
											coReceiptType.LoanMember,
											coReceiptType.StockMember,
											coReceipt.Stock, 
											coReceiptType.Description as TYPE_DESC,
											coReceipt.Loan_Seq, 
											coReceipt.Loan_FromDate, 
											coReceipt.Loan_ToDate, 
											coReceipt.PrincipalBF, 
											coReceipt.Stock_OnHand, 
											coReceipt.Stock_OnHand_Value
											FROM  coReceipt  LEFT JOIN coReceiptType ON coReceipt.Type=coReceiptType.Type
											LEFT JOIN  coLoanMember ON coReceipt.Loan_Doc_No=coLoanMember.Doc_No
											LEFT  JOIN coInterestRate_Desc ON coLoanMember.Type=coInterestRate_Desc.Type
											WHERE coReceipt.status ='2' AND coReceipt.member_id = :member_no and coReceipt.Receipt_No = :recv_period
											ORDER BY coReceipt.Receipt_No");
		$getPaymentDetail->execute([
			':member_no' => $member_no,
			':recv_period' => $dataComing["recv_period"]
		]);	
		$arrGroupDetail = array();
		$sum_principal  = 0; $sum_interest  = 0; $sum_principalbf  = 0;
		$$header["haveShare"] = false;
		while($rowDetail = $getPaymentDetail->fetch(PDO::FETCH_ASSOC)){
			$arrDetail = array();
			$header["PAYDATE"] = $lib->convertdate($rowDetail["Paydate"],'d M Y');
			$arrDetail["Type"] = $rowDetail["Type"];
			if($rowDetail["Type"] == '10' || $rowDetail["Type"] == '32'){
				if($rowDetail["Type"] == '10'){
					$header["haveShare"] = true;
					$arrDetail["TYPE_DESC"] = $rowDetail["TYPE_DESC"]." จำนวน ".$rowDetail["Stock"] ." หุ้น";
				}else{
					$arrDetail["TYPE_DESC"] = $rowDetail["TYPE_DESC"];
				}
				$arrDetail["AMOUNT"] = number_format($rowDetail["Amount"],2);
				$header["STOCK_ONHAND"] = number_format($rowDetail["Stock_OnHand"]);
				$header["STOCK_ONHAND_VALUE"] = number_format($rowDetail["Stock_OnHand_Value"],2);
			}else if($rowDetail["Type"] == '20'){
				$arrDetail["LOANTYPE_DESC"] = $rowDetail["Description"] ." ที่ ".$rowDetail["Loan_Doc_No"];
				$arrDetail["PERIOD"] = $rowDetail["Loan_Seq"];
				$arrDetail["PRINCIPAL"] = number_format($rowDetail["Principal"],2);
				$arrDetail["INTEREST"] = number_format($rowDetail["Interest"],2);
				$arrDetail["PRINCIPALBF"] = number_format($rowDetail["PrincipalBF"],2);
				$arrDetail["AMOUNT"] = number_format($rowDetail["Amount"],2);
				$arrDetail["LOAN_FROMDATE"] = $lib->convertdate($rowDetail["Loan_FromDate"],'d/n/Y');			
				$arrDetail["LOAN_TODATE"] = $lib->convertdate($rowDetail["Loan_ToDate"],'d/n/Y');			
				$sum_principal += $rowDetail["Principal"];
				$sum_interest += $rowDetail["Interest"];
				$sum_principalbf += $rowDetail["PrincipalBF"];
				
			}else{
				$arrDetail["TYPE_DESC"] = $rowDetail["TYPE_DESC"];
				$arrDetail["AMOUNT"] = number_format($rowDetail["Amount"],2);
			}
			$amount_amt += $rowDetail["Amount"];
			$arrGroupDetail[] = $arrDetail;
		}	
		$sum_principal = number_format($sum_principal,2);
		$sum_interest = number_format($sum_interest,2);
		$sum_principalbf = number_format($sum_principalbf,2);
		$amount = number_format($amount_amt,2);
		$amount_baht =  $lib->baht_text($amount_amt);
		$arrayPDF = GenerateReport($arrGroupDetail,$header,$lib , $sum_principal ,$sum_interest , $sum_principalbf , $amount ,$amount_baht);
		if($arrayPDF["RESULT"]){
			$arrayResult['REPORT_URL'] = $config["URL_SERVICE"].$arrayPDF["PATH"];
			$arrayResult['RESULT'] = TRUE;
			$arrayResult['RESULT_AD'] = $amount_baht;
			require_once('../../include/exit_footer.php');
		}else{
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS0044",
				":error_desc" => "สร้าง PDF ไม่ได้ "."\n".json_encode($dataComing),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "สร้างไฟล์ PDF ไม่ได้ ".$filename."\n"."DATA => ".json_encode($dataComing);
			$lib->sendLineNotify($message_error);
			$arrayResult['RESPONSE_CODE'] = "WS0044";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
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
	/*$filename = basename(__FILE__, '.php');
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
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];*/
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
	
}

function GenerateReport($dataReport,$header,$lib, $sum_principal ,$sum_interest , $sum_principalbf , $amount ,$amount_baht){
	$html = '
	<style>
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
	body {
	  font-size:11pt;
	  line-height: 15px;

	}
	div{
	  font-size:11pt;
	}
	.center{
	  text-align:center
	}
	.right{
	  text-align:right
	}
	.bold{
	  font-weight:bold;
	}
	.nowrap{
	  white-space: nowrap;
	}
	.wrapper-page {
	  page-break-after: always;
	}

	.wrapper-page:last-child {
	  page-break-after: avoid;
	}
	table{
	  border-collapse: collapse;

	}
	.main-td{
	  border:0.25px solid green ;
	  text-align:center;
	  color:green;
	  padding:5px 5px;
	}
	th{
	  font-size:11pt;
	}

	p{
	  margin:0px;
	}
	.text-color{
	  color:green;
	}
	.data-text-color{
	  color:#000000;
	}
	.border-top{
	  border-top:1px solid;
	}
	.total{
	  border-bottom:2px double;
	}
	.flex{
	  display:flex;
	}
	.border{border:1px solid red}

	@page { size: 15.24cm 15.24cm; }
	</style>
	';

	//@page = ขนาดของกระดาษ 15.24 cm x  15.24 cm
	//ระยะขอบ
	$html .= '<div style=" margin: -30px -30px -50px -30px; ">';
	$html .= '
	  <div style="display:flex; height:80px;"  class="nowrap">
		  <div style="positionabsolute; margin-left:-12px; ">
			<img src="../../resource/logo/logo_recept.png" style="width:100px; " >
		  </div>
		  <div>
			  <div  class="center text-color bold" style="margin-top:5px; font-size:18pt;">  สหกรณ์ออมทรัพย์ เอ็ม บี เค กรุ๊ป จำกัด</div>
			  <div style="margin-top:10px; font-size:18pt;" class="center text-color bold">ใบรับเงิน</div>
			  <div style="font-size:11pt; height:20px;">
				 <div style="position:absolute; right:70px;" class="text-color">เลขที่</div> 
				 <div style="position:absolute; right:-30px;  width:90px; ">'.($header["receipt_No"]??null).'</div> 
			  </div>
			  <div style="margin-right:90px; font-size:11pt; margin-top:10px;" >
				<div style="position:absolute; right:70px;" class="text-color">วันที่</div> 
				<div style="position:absolute; right:-30px;  width:90px; ">'.($header["PAYDATE"]??null).'</div> 
			  </div>
		  </div>
	  </div>
	  <div style="display:flex; height:30px; "class="text-color">
		<div>ได้รับเงินจาก</div>
		<div style="margin-left:75px;" class="data-text-color">'.($header["FULLNAME"]??null).'</div>
	  </div>
	  <div style="display:flex; height:30px; "class="text-color">
		<div>สมาชิกเลขทะเบียนที่ </div>
		<div style="margin-left:90px; width:80px;" class="data-text-color center">'.($header["MEMBER_NO"]??null).'</div>
		<div style="margin-left:180px;">ดังต่อไปนี้</div>
		<div style="position:absolute; right:5px; font-size:10px; top:70px;" class="data-text-color">'.($header["company_desc"]??null).'</div> 
		<div style="position:absolute; right:5px; font-size:10px; top:80px;" class="data-text-color">'."แผนกฝ่าย ".($header["department_desc"]??null).'</div> 
	  </div>
	  <div>
	  <div style="position:absolute; ">
		<div style=" padding:5px;  height:20px; "></div>
		<div>
		  <table >
			 ';
			  
	foreach($dataReport AS $arrList){
	  if($arrList["Type"]!="20"){
		$html .='  
			<tr>
				  <td style="width:160px;" class=""></td>
				  <td style="width:85px;" class=" right"></td>
				  <td style="width:85px;" class=" right"></td>
				  <td style="width:95px;" class=" right"></td>
				  <td style="width:100px;" class=" right"></td>
			  </tr>		
		<tr>
		  <td style="width:160px; padding-left:3px;">'.($arrList["TYPE_DESC"]??null).'</td>
		  <td style="width:85px;" class=" right"></td>
		  <td style="width:85px;" class=" right"></td>
		  <td style="width:95px;" class=" right"></td>
		  <td style="width:100px;" class=" right">'.($arrList["AMOUNT"]??null).'</td>
	  </tr>
	   <tr>
			<td style="width:150px;"></td>
			<td style="width:85px;" class="right"><div ></div></td>
			<td style="width:85px;" class="right"><div ></div></td>
			<td style="width:95px;" class="right"><div ></div></td>
			<td style="width:100px;" class="right"></td>
		  </tr>     
	  ';

	  }else{
		$html .='   
		 <tr>
				  <td style="width:160px;" class=""></td>
				  <td style="width:85px;" class=" right">เงินต้น</td>
				  <td style="width:85px;" class=" right">ดอกเบี้ย</td>
				  <td style="width:95px;" class=" right">เงินต้นคงเหลือ</td>
				  <td style="width:100px;" class=" right"></td>
			  </tr>
		<tr>
		  <td style="width:160px; padding-left:3px;"> '.($arrList["LOANTYPE_DESC"]??null).'
		  <p style="padding-left: 5px;font-size:11px;">- งวดที่ '.$arrList["PERIOD"]." (".$arrList["LOAN_FROMDATE"]." - ".$arrList["LOAN_TODATE"].")".'</p></td>
		  <td style="width:85px;" class=" right">'.($arrList["PRINCIPAL"]??null).'</td>
		  <td style="width:85px;" class=" right">'.($arrList["INTEREST"]??null).'</td>
		  <td style="width:95px; margin-right:5px" class=" right">'.($arrList["PRINCIPALBF"]??null).'</td>
		  <td style="width:100px;" class=" right">'.($arrList["AMOUNT"]??null).'</td>
		</tr>
		<tr>
		  <td style="width:150px; text-indent:20px;">'.($arrList["รายละเอียด"]??null).'</td>
		  <td style="width:85px;" class="right"></td>
		  <td style="width:85px;" class="right"></td>
		  <td style="width:95px;" class="right"></td>
		  <td style="width:100px;" class="right"></td>
	  </tr>
	   <tr>
			<td style="width:150px;"></td>
			<td style="width:85px;" class="right"><div class="border-top total">'.($sum_principal??'&nbsp;').'</div></td>
			<td style="width:85px;" class="right"><div class="border-top total">'.($sum_interest??'&nbsp;').'</div></td>
			<td style="width:95px;" class="right"><div class="border-top total">'.($sum_principalbf??'&nbsp;').'</div></td>
			<td style="width:100px;" class="right"></td>
		  </tr>     
	  ';

	  }

	}

	$html .=' 
		     
		  </table>
		</div>
		';

	  //   <div class="flex" style="height:10px; padding:5px;">
	  //   <div class="border right" style="width:100px; margin-left:330px;">เงินต้นคงเหลือ</div>
	  //   <div class="border right" style="width:80px; margin-left:250px;">ดอกเบี้ย</div>
	  //   <div class="border right" style="width:90px; margin-left:158px;">เงินต้น</div>
	  // </div>

	   
	$html.='
	  </div>
		<div style="height:260px;">
		  <table style="width:100%;">
			  <tr>
				  <td class="main-td">รายการ</td>
				  <td class="main-td" style="width:20%">จำนวนเงิน</td>
			  </tr>
			  <tr>
				<td class="main-td" style="height:220px;"></td>
				<td class="main-td"></td>
			  </tr>
		  </table>
		  <div style="position:absolute; margin-top:-35px; font-size:9pt;    line-height: 13px;" >
			<div style="padding-left:5px;">
			  <div style="border-top:0.5px solid green;  position:absolute; width:109px ; margin-left:431px"></div>';
			   if($header["haveShare"]){
				$html .= '<div  style="margin-bottom:5px;">***หมายเหตุ หุ้นสะสม&nbsp;&nbsp;จำนวน  '.$header["STOCK_ONHAND"].' หุ้น&nbsp;&nbsp;เป็นมูลค่า&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; '.$header["STOCK_ONHAND_VALUE"].' บาท</div>';
			   }else{
				   $html .= '<div  style="margin-bottom:5px;"></div>';
			   }
			 $html .= '<div class="flex" style=" margin-top:5px; height:30px;">
				<div style="margin-left:40px;" class="bold">('.($amount_baht??null).')</div>
				<div style="margin-left:410px; " class="text-color">รวม</div>
				<div style="margin-left:490px; " class="bold">'.($amount??null).'</div>
			  </div>
			  
			</div>
		  </div>
	  </div>
	  </div>
	  <div class="flex" style="height:60px;">
			<div class="center" style="width:50%">
				<img src="../../resource/utility_icon/signature/payee.jpg" style="height:20px; margin-top:15px ">
				<div class="text-color" style="margin-top:10px;">เจ้าหน้าที่ผู้รับเงิน</div>
			</div>
			<div class="center" style="margin-left:50%; width:50%">
					<div class="" style="padding-top:25px;">'.($header["PAYDATE"]??null).'</div>
					<div class="text-color" style="padding-top:5px;">วันที่</div>
			</div>
	  </div>
	  <div class="flex">
	  <div class="center" style="width:50%">
		  <img src="../../resource/utility_icon/signature/manager.jpg" style="height:20px; margin-top:10px ">
		  <div class="text-color" style="margin-top:5px;">เจ้าหน้าที่ผู้รับเงิน</div>
	  </div>
	  <div class="center" style="margin-left:50%; width:50%">
		  <div  style="padding-top:10px;">'.($header["PAYDATE"]??null).'</div>
		  <div style="padding-top:5px;" class="text-color">วันที่</div>
	  </div>
	</div>
	';

	//ระยะขอบ
	$html .= '
	</div>';


	$dompdf = new Dompdf([
		'fontDir' => realpath('../../resource/fonts'),
		'tempDir' => realpath('../../resource/fonts'),
		'chroot' => realpath('/'),
		'isRemoteEnabled' => true
	]);

	$dompdf->set_paper('A4','landscape');
	$dompdf->load_html($html);
	$dompdf->render();
	$pathfile = __DIR__.'/../../resource/pdf/keeping_monthly';
	if(!file_exists($pathfile)){
		mkdir($pathfile, 0777, true);
	}
	$pathfile = $pathfile.'/'.$header["MEMBER_NO"].$header["RECEIPT_NO"].'.pdf';
	$pathfile_show = '/resource/pdf/keeping_monthly/'.$header["MEMBER_NO"].$header["RECEIPT_NO"].'.pdf?v='.time();
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