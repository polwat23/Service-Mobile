<?php
require_once('../autoload.php');

use Dompdf\Dompdf;

$dompdf = new DOMPDF();

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'InvoicePayment')){
		$member_no = $configAS[$payload["ref_memno"]] ?? $payload["ref_memno"];
		$sum_principal_payment = 0;
		$sum_bfprnbal_amt  = 0;
		$sum_bfintarrear_amt = 0;
		$sum_bfintmtharr_amt = 0;
		$sum_item_payment = 0;
		$start_date = date('Y-m-d');
		$arrHeader = array();
		$arrDetail = array();
		$arrayContractCheckGrp = array();  //เช็คเเจ้งยอดชำระหนี้
		$fetchContractTypeCheck = $conmysql->prepare("SELECT balance_status FROM gcconstantbalanceconfirm WHERE member_no = :member_no");
		$fetchContractTypeCheck->execute([':member_no' => $member_no]);
		$rowContractnoCheck = $fetchContractTypeCheck->fetch(PDO::FETCH_ASSOC);
		$Contractno  = $rowContractnoCheck["balance_status"] ||"0";
		
		$fetchEndDate= $conmysql->prepare("SELECT end_date FROM GCENDCLOSEDATE");
		$fetchEndDate->execute();
		$rowEndDate = $fetchEndDate->fetch(PDO::FETCH_ASSOC);
		
		$getBalancedate = $conoracle->prepare("SELECT max(doc_date) as NOTICE_DATE , NOTICE_DOCNO  ,doc_date from CMNOTATIONREGISFILE where  member_no = :member_no
												AND  doc_date = (SELECT  max(doc_date) FROM  CMNOTATIONREGISFILE WHERE  member_no = :member_no ) 
												GROUP BY NOTICE_DOCNO ,doc_date");
		$getBalancedate->execute([':member_no' => $member_no]);
		$rowBalMaster = $getBalancedate->fetch(PDO::FETCH_ASSOC);
		$year = date('Y',strtotime($rowBalMaster["NOTICE_DATE"]))+543;
		$notice_date = date('dm',strtotime($rowBalMaster["NOTICE_DATE"])).$year;
		
		$end_date = date('Ymd',strtotime($rowEndDate["end_date"]));
		$datenow = date('Ymd',strtotime(date("Y-m-d")));
		
		if($end_date > $datenow){
			$getBalancedate = $conoracle->prepare("select is_view ,file_name FROM  CMNOTATIONREGISFILE where  member_no= :member_no AND notice_docno =:notice_docno ");
			$getBalancedate->execute([':member_no' => $member_no , 
									  ':notice_docno' => $rowBalMaster["NOTICE_DOCNO"]
									]);
			$rowBalIs_VIEW= $getBalancedate->fetch(PDO::FETCH_ASSOC);
			if($rowBalIs_VIEW["IS_VIEW"] == '1'){
				if($Contractno == "0"){
					$arrayResult['REPORT_URL'] = "https://apim.fsct.com/internal/".$rowBalIs_VIEW["FILE_NAME"]."";
					$arrayResult['RESULT'] = TRUE;
					$arrayResult['ADVICE'] = "หากสหกรณ์มีการรับเงินกู้หรือมีการชำระเงินกู้พิเศษหลังจากวันที่พิมพ์ด้านมุมบนขวา  กรุณาตรวจสอบยอดชำระอีกครั้ง หลังจากสหกรณ์ทำรายการเสร็จสิ้นเเล้วถัดไปอีก  5  วันทำการ  หรือติดต่อเจ้าหน้าที่  ชสอ.";
					require_once('../../include/exit_footer.php');
				}else{
					$arrayResult['RESPONSE_CODE'] = "WS0114";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					http_response_code(403);
					require_once('../../include/exit_footer.php');
				}
				
			}else{
				$arrayResult['WAITING_CONFIRM '] = "หากสหกรณ์มีการรับเงินกู้หรือมีการชำระเงินกู้พิเศษหลังจากวันที่พิมพ์ด้านมุมบนขวา  กรุณาตรวจสอบยอดชำระอีกครั้ง หลังจากสหกรณ์ทำรายการเสร็จสิ้นเเล้วถัดไปอีก  5  วันทำการ  หรือติดต่อเจ้าหน้าที่  ชสอ.";
				$arrayResult['WAITING_ '] = [':member_no' => $member_no , 
									  ':notice_docno' => $rowBalMaster["NOTICE_DOCNO"]
									];
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}
		}else{
			$arrayResult['WAITING_CONFIRM '] = "หากสหกรณ์มีการรับเงินกู้หรือมีการชำระเงินกู้พิเศษหลังจากวันที่พิมพ์ด้านมุมบนขวา  กรุณาตรวจสอบยอดชำระอีกครั้ง หลังจากสหกรณ์ทำรายการเสร็จสิ้นเเล้วถัดไปอีก  5  วันทำการ  หรือติดต่อเจ้าหน้าที่  ชสอ.";
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

/*function GeneratePdfDoc($arrDetail,$header,$sum_principal_payment,$sum_bfprnbal_amt,$sum_bfintarrear_amt,$sum_bfintmtharr_amt,$sum_item_payment) {
	$html = '<style>
	@font-face {
	  font-family: BrowalliaUPC;
	  src: url(../../resource/fonts/BrowalliaUPC.ttf);
	}
	@font-face {
		font-family: BrowalliaUPC;
		src: url(../../resource/fonts/BrowalliaUPC Bold.ttf);
		font-weight: bold;
	}
	* {
	  font-family: BrowalliaUPC;
	}

	body {
	  padding: 0 px;
	  font-size: 15px;
	}
	.sub-table div{
		padding : 5px;
	}
	.border-solid{
	  border:solid 1px;
	}
	.text-center{
	  text-align:center;
	}
	.text-right{
	  text-align:right;
	}
	.td-padding:{
	  padding:5px;
	}

	</style>

	<div style="display: flex;text-align: center;position: relative;margin-bottom: 15px;"></div>
	<div style="height:10px"></div>
	<div style="text-align:left;position: absolute;width:100%;margin-left: 120px"></div>';
	$html .= '<div style="padding: 5px;margin-left:auto; margin-top: 30px; font-size: 20px; font-weight: bold; text-align:center; border:solid 3px ; width:210px">แจ้งยอดเงินงวดชำระหนี้</div>';
	$html .= '<div style="margin: 0px 0 0px 0;">
	  <table style="width: 100%">
	  <tbody>
	  <tr>
	  <td style="width: 100px; font-size: 15px">ที่ ชสอ. </td>
	  <td style="width: 310px; ">'. $header["DOC_NO"] ."/". $header["YEAR"] . '</td>
	  <td style="width: 110px;font-size: 15px; text-align:right;">เลขที่ใบแจ้ง </td>
	  <td style="width: 10px; text-align:right;">' . $header["NOTICE_DOCNO"] . '</td>
	  </tr>
	  <tr>
	  <td style="width: 50px;font-size: 15px;">เลขที่สมาชิก/รหัสลูกค้า </td>
	  <td style="width: 310px;">' . $header["MEMBER_NO"] . '</td>
	  <td style="width: 110px;font-size: 15px; text-align:right;">วันที่พิมพ์ </td>
	  <td style="width: 10px; text-align:right;">' . $header["NOTICEDUE_DATE"] . '</td>
	  </tr>
	  <tr>
	  <td style="width: 50px;font-size: 15px;">ชื่อสมาชิก/ชื่อลูกค้า </td>
	  <td style="width: 310px;">' . $header["COOP_NAME"] . '</td>
	  </tr>
	  </tbody>
	  </table>
	  <table class="none-border" style="width: 55%;">
	  <tr>
		<td style="vertical-align: top;">ที่อยู่</td>
		<td>'.$header["ADDRESS"].'</td>
	  </tr>
	  <tr>
		<td style="vertical-align: top;">โทรศัพท์</td>
		<td>'.$header["ADDR_PHONE"].'   '.(isset($header["ADDR_FAX"]) ? ("โทรสาร  ".$header["ADDR_FAX"]."") : "").'</td>
	  </tr>
	</table>
	</div>
	<div style="margin-top: 15px;">
	<div style="border-top: 1px solid #3c3939de;">&nbsp;</div>
	<div style="margin-top:-25px;">รายละเอียดการส่งชำระ มีดังนี้</div>
	';

	$html .= '
	  <div style="margin-top:5px;">
		<table style="border-collapse: collapse; width:100%">
		  <thead>
			<tr>
			  <th class="border-solid text-center">กำหนดชำระ</th>
			  <th class="border-solid text-center">เลขที่สัญญา</th>
			  <th class="border-solid text-center">เงินต้นคงหลือ</th>
			  <th class="border-solid text-center">ดอกเบี้ยค้าง ชำระงวดก่อน</th>
			  <th class="border-solid text-center">ดอกเบี้ยคำนวน งวดปัจจุบัน</th>
			  <th class="border-solid text-center">เงินต้นถึง กำหนดชำระ</th>
			  <th class="border-solid text-center" style="width:110px;">รวม</th>
			</tr>
		  </thead>';
	//รายละเอียด dataReport
	$i = 0  ;
	$html .= '<tbody>';
	foreach ($arrDetail as $dataArr) {
		$i++;
	  $html .= '
		<tr>
		  <td class="border-solid text-center">'.($i == 1 ?  $dataArr["NOTICEDUE_DATE"] ?? null : null).'</td>
		  <td class="border-solid text-center">'.($dataArr["LOANCONTRACT_NO"] ?? null).'</td>
		  <td class="border-solid text-right td-padding">'.($dataArr["BFPRNBAL_AMT"] ?? null).'</td>
		  <td class="border-solid text-right td-padding">'.($dataArr["BFINTARREAR_AMT"] ?? null).'</td>
		  <td class="border-solid text-right td-padding">'.($dataArr["BFINTMTHARR_AMT"] ?? null).'</td>
		  <td class="border-solid text-right td-padding">'.($dataArr["PRINCIPAL_PAYMENT"] ?? null).'</td>
		  <td class="border-solid text-right td-padding">'.($dataArr["ITEM_PAYMENT"] ?? null).'</td>
		</tr>
	  ';
	}
	//รวม
	$html .= '
	  <tr>
		<th class="border-solid text-center" colspan=2>รวม</th>
		<th class="border-solid text-right">'.($sum_bfprnbal_amt ?? null).'</th>
		<th class="border-solid text-right">'.($sum_bfintarrear_amt  ?? null).'</th>
		<th class="border-solid text-right">'.($sum_bfintmtharr_amt ?? null).'</th>
		<th class="border-solid text-right">'.($sum_principal_payment ?? null).'</th>
		<th class="border-solid text-right">'.($sum_item_payment ?? null).'</th>
	  </tr>
	</tbody>
	</table>
  </div>
	';
	$html .= '
	 <table>
		<tbody>
			<tr>
			  <td>ชำระโดยโอนเข้าบัญชี :</td>
			  <td>ธนาคาร</td>
			  <td>'.$header["BANK_NAME"].'</td>
			</tr>
			<tr>
			  <td></td>
			  <td>สาขา </td>
			  <td>'.$header["BRANCH_NAME"].'</td>
		  </tr>
		  <tr>
			  <td></td>
			  <td>เลขที่บัญชี </td>
			  <td>'.$header["EXPENSE_ACCID"].'</td>
		  </tr>
		</tbody>
	 </table>
	 <br>
	 <div>
		<div style="display:inline; font-weight:bold;  text-decoration: underline; ">หมายเหตุ</div>
		<div  style="display:inline; margin-left:10px">โปรดชำระตามข้อมูลใบแจ้งยอดฉบับล่าสุดเพียงฉบับเดียวเท่านั้น</div>
	 </div>
	 <div style="margin-top:80px;">
		<div class="text-center"><img src="../../resource/utility_icon/signature/signature_loan.jpg"  height="60" /></div>
		<div class="text-center">(นางอัจฉรา สุจริตพงษ์)</div>
		<div class="text-center">ผู้จัดการฝ่ายสินเชื่อ</div>
		<div class="text-center">ชุมนุมสหกรณ์ออมทรัพย์แห่งประเทศไทย จำกัด</div>
	 </div>
	 <div>
	   <div style="position:fixed; bottom:30px; ">
		<div style="display:inline;">ฝ่ายสินเชื่อ ต่อ</div>
		<div style="display:inline;">212-216 , 223, 226 และ 227</div>
	   </div>
	 </div>
	';
	$dompdf = new Dompdf([
		'fontDir' => realpath('../../resource/fonts'),
		'chroot' => realpath('/'),
		'isRemoteEnabled' => true
	]);


	$dompdf->set_paper('A4');
	$dompdf->load_html($html);
	$dompdf->render();
	$pathfile = __DIR__.'/../../resource/pdf/docbalconfirm';
	if(!file_exists($pathfile)){
		mkdir($pathfile, 0777, true);
	}
	$pathfile = $pathfile.'/'.$arrHeader["member_no"].'.pdf';
	$pathfile_show = '/resource/pdf/docbalconfirm/'.$arrHeader["member_no"].'.pdf?v='.time();
	$arrayPDF = array();
	$output = $dompdf->output();
	if(file_put_contents($pathfile, $output)){
		$arrayPDF["RESULT"] = TRUE;
	}else{
		$arrayPDF["RESULT"] = FALSE;
	}
	$arrayPDF["PATH"] = $pathfile_show;
	return $arrayPDF; 
}*/
?>