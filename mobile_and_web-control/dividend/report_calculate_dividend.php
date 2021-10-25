<?php
require_once('../autoload.php');

use Dompdf\Dompdf;


if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DividendInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$pay_no = $dataComing["year"];
		$memberInfo = $conmssqlcoop->prepare("SELECT  PREFIXNAME,FIRSTNAME,LASTNAME
											  FROM cocooptation   where member_id =:member_no ");
		$memberInfo->execute([':member_no' => $member_no]);
		$rowMember = $memberInfo->fetch(PDO::FETCH_ASSOC);
		$arrHeader = array();
		$arrDetail = array();
		$arrHeader["FULL_NAME"] = $rowMember["PREFIXNAME"].$rowMember["FIRSTNAME"]." ".$rowMember["LASTNAME"];
		$arrHeader["MEMBER_ID"] = $member_no;
		
		$sum_div_amt = 0 ;
		$getDividend = $conmssqlcoop->prepare("SELECT pc.YEAR_PAYDATE,PC.MONTH_PAYDATE,pc.PARAMATER1,pc.PARAMATER2,
											pc.PARAMATER3,pc.OUTPUT,cm.PROCESS_STARTDATE, cm.PROCESS_ENDDATE , cm.PAY_DATE ,pc.PAY_NO ,cm.PROCESS_ACTIVEDATE
											FROM coPay_TransactionCalculate pc LEFT JOIN copay_transaction ct ON pc.pay_no = ct.pay_no
											AND pc.member_id = ct.member_id AND pc.type = ct.type
											LEFT JOIN  copay_master cm ON pc.pay_no = cm.pay_no AND pc.type = cm.type
											WHERE pc.type ='D' and  pc.pay_no = :pay_no and pc.member_id = :member_no
											ORDER BY pc.year_paydate,pc.month_paydate asc");
		$getDividend->execute([
			':pay_no' => $pay_no,
			':member_no' => $member_no
		]);
		while($rowDividend = $getDividend->fetch(PDO::FETCH_ASSOC)){
			$arrDividend = array();
			$arrDividend["YEAR"] = $rowDividend["YEAR_PAYDATE"].'/'.$rowDividend["MONTH_PAYDATE"];
			$arrDividend["SHARE_MONTH"] = number_format($rowDividend["PARAMATER1"],2);
			$arrDividend["DIV_AMT"] = number_format($rowDividend["OUTPUT"],2);
			$arrDividend["CALCULATE"] = $rowDividend["PARAMATER1"].' * '.$rowDividend["PARAMATER2"].' / '.' 100 / 12 * '.$rowDividend["PARAMATER3"];			
			$sum_div_amt += $rowDividend["OUTPUT"];
			$arrHeader["PAY_NO"] = $dataComing["pay_no"];
			$arrHeader["PROCESS_STARTDATE"] = $lib->convertdate($rowDividend["PROCESS_STARTDATE"],'d/n/Y');
			$arrHeader["PROCESS_ENDDATE"] = $lib->convertdate($rowDividend["PROCESS_ENDDATE"],'d/n/Y');
			$arrHeader["PROCESS_ACTIVEDATE"] = $lib->convertdate($rowDividend["PROCESS_ACTIVEDATE"],'d/n/Y');
			$arrHeader["PAY_DATE"] = $lib->convertdate($rowDividend["PAY_DATE"],'d/n/Y');
			$arrDetail[] = $arrDividend;
			
			
		}
		$arrayPDF = GeneratePdfDoc($arrHeader,$arrDetail,$sum_div_amt);
		if($arrayPDF["RESULT"]){
			$arrayResult['REPORT_URL'] = $config["URL_SERVICE"].$arrayPDF["PATH"];
			//$arrayResult['BALANCE_DATE'] = date('Y-m-d',strtotime($rowBalMaster["BALANCE_DATE"]));
			$arrayResult['arrDetail']  = $arrDetail;
			$arrayResult['arrHeader']  = $arrHeader;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
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
function GeneratePdfDoc($header,$arrDetail,$sum_div_amt) {
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
	  font-size:14pt;
	  line-height: 20px;
	}
	.text-center{
	  text-align:center
	}
	.text-right{
	  text-align:right
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
	  border-collapse: collapse
	}
	th{
	  border-top:0.5px solid ;
	  border-bottom:0.5px solid  ;

	  text-align:center;
	}
	th,td{
	  padding:7px 12px; 
	}

	p{
	  margin:0px;
	}
	.text-color{
	  color:#47aaff;
	}
	.data-text-color{
	  color:#000000;
	}
	.bg-color{
		background-color:#f2fbff;
	}
	</style>
	';


	//ระยะขอบ
	$html .= '<div style=" margin: 0px;    ">';

	$html .='
	  <div style="font-size:16pt; font-weight:bold; color:#00008B;" class="text-center">สหกรณ์อมทรัพย์ เอ็ม บี เค กรุ๊ป จำกัด MBK</div>
		<div style="color:#00008B; margin-top:5px;" class="text-center">รายงานการจ่ายเงินปันผล
		  <div style="position:absolute; right:0px; color:green;">'.($header["วันที่พิม"]??null).'</div>
		</div>
		<div>
		   <div style="display:inline;">งวดที่</div>
		   <div style="display:inline;">'.($header["PAY_NO"]??null).'</div>
		   <div style="display:inline;">คำนวณจาก</div>
		   <div style="display:inline;">วันที่</div>
		   <div style="display:inline;">'.($header["PROCESS_STARTDATE"]??null).'</div>
		   <div style="display:inline;">ถึง</div>
		   <div style="display:inline;">'.($header["PROCESS_ENDDATE"]??null).'</div>
		   <div style="display:inline;">เฉพาะที่เป็นสมาชิก</div>
		   <div style="display:inline;">ถึงวันที่</div>
		   <div style="display:inline;">'.($header["PROCESS_ACTIVEDATE"]??null).'</div>
		</div>
		<div>
		  <div style="display:inline;">อัตราปันผล</div>
		  <div style="display:inline; padding-left:10px; padding-right:10px;">6.00%</div>
		  <div style="display:inline;">วันที่จ่ายปันผล</div>
		  <div style="display:inline;">'.($header["PAY_DATE"]??null).'</div>
		</div>
		<div>
		  <div style="display:inline; padding-right:10px;">รหัส</div>
		  <div style="display:inline;">'.($header["MEMBER_ID"]??null).'</div>
		</div>
		<div>
		  <div style="display:inline; padding-right:10px;">ชื่อ-สกุล</div>
		  <div style="display:inline;">'.($header["FULL_NAME"]??null).'</div>
		</div>
		<div style="line-height:15px; margin-top:10px;">
			<table style="width:100%">
				<tr>
					<th style="width:40px;">ปี/เดือน</th>
					<th style="width:120px;">ถือหุ้นรายเดือน</th>
					<th style="width:120px;">เงินปันผล(บาท)</th>
					<th style="text-align:left">การคำนวณ</th>
				</tr>';

	foreach($arrDetail AS $dataReport){
	$html.='           
				<tr>
				  <td>'.($dataReport["YEAR"]??null).'</td>
				  <td class="text-right">'.($dataReport["SHARE_MONTH"]??null).'</td>
				  <td class="text-right">'.($dataReport["DIV_AMT"]??null).'</td>
				  <td style="text-align:left;">'.($dataReport["CALCULATE"]??null).'</td>
				</tr>';
				}
	  $html.=' 
			  <tr>
				<th></th>		
				<th class="text-right">รวม</th>
				<th class="text-right">'.number_format($sum_div_amt,2).'</th>
				<th></th>
			  </tr>
			</table>
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


	$dompdf->set_paper('A4');
	$dompdf->load_html($html);
	$dompdf->render();
	$pathfile = __DIR__.'/../../resource/pdf/dividend';
	if(!file_exists($pathfile)){
		mkdir($pathfile, 0777, true);
	}
	$pathfile = $pathfile.'/'.$arrHeader["MEMBER_ID"].'.pdf';
	$pathfile_show = '/resource/pdf/dividend/'.$arrHeader["MEMBER_ID"].'.pdf?v='.time();
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