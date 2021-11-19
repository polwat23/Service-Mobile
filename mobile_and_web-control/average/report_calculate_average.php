<?php
require_once('../autoload.php');

use Dompdf\Dompdf;

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DividendAverageInfo')){
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
		$getAverage = $conmssqlcoop->prepare("select pc.PARAMATER1,
										pc.paramater2 as REMARK,
										ct.interest as INTEREST_AMT,
										pc.output as AVG_AMT,
										pc.paramater3 as AVG_RATE,
										cpm.PROCESS_STARTDATE,cpm.PROCESS_ENDDATE , cpm.PAY_DATE ,cpm.PROCESS_ACTIVEDATE
										from coPay_Transaction ct LEFT JOIN coPay_TransactionCalculate pc ON ct.member_id = pc.member_id 
										AND ct.pay_no = pc.pay_no AND ct.type = pc.type
										LEFT JOIN coPay_Master cpm ON ct.pay_no = cpm.pay_no AND ct.type = cpm.type
										where ct.type ='A' AND ct.pay_no = :pay_no AND ct.member_id = :member_no");
		$getAverage->execute([
			':pay_no' => $pay_no,
			':member_no' => $member_no
		]);
		while($rowAverage = $getAverage->fetch(PDO::FETCH_ASSOC)){
			$arrAverage = array();
			$arrAverage["REMARK"] = $rowAverage["PARAMATER1"].'  '.$rowAverage["REMARK"];
			$arrAverage["INTEREST_AMT"] = number_format($rowAverage["INTEREST_AMT"],2);
			$arrAverage["AVG_AMT"] = number_format($rowAverage["AVG_AMT"],2);
			$arrAverage["AVG_RATE"] = $rowAverage["AVG_RATE"] * 100  ." %";			
			$sum_avg_amt += $rowAverage["AVG_AMT"];
			$arrHeader["PAY_NO"] = $pay_no;
			$arrHeader["PROCESS_STARTDATE"] = $lib->convertdate($rowAverage["PROCESS_STARTDATE"],'d/n/Y');
			$arrHeader["PROCESS_ENDDATE"] = $lib->convertdate($rowAverage["PROCESS_ENDDATE"],'d/n/Y');
			$arrHeader["PROCESS_ACTIVEDATE"] = $lib->convertdate($rowAverage["PROCESS_ACTIVEDATE"],'d/n/Y');
			$arrHeader["PAY_DATE"] = $lib->convertdate($rowAverage["PAY_DATE"],'d/n/Y');
			$arrDetail[] = $arrAverage;
			
			
		}
		$arrayPDF = GeneratePdfDoc($arrHeader,$arrDetail,$sum_avg_amt);
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
function GeneratePdfDoc($header,$arrDetail,$sum_avg_amt) {
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
	.text-left{
	  text-align:left;
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
	  border-top:1px solid ;
	  border-bottom:1px solid  ;

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
	  <div style="font-size:16pt; font-weight:bold; color:#00008B;" class="text-center">สหกรณ์อมทรัพย์ เอ็ม บี เค กรุ๊ป จำกัด</div>
		<div style="color:#00008B; margin-top:5px;" class="text-center">รายงานการเจ่ายเงินปันผลเฉลี่ยคืนให้แก่สมาชิก
		  <div style="position:absolute; right:0px; color:green;">'.($header["วันที่พิม"]??null).'</div>
		</div>
		<div style="margin-top:5px">
		   <div style="display:inline;">งวดที่</div>
		   <div style="display:inline;">'.($header["PAY_NO"]??null).'</div>
		   <div style="display:inline;">คำนวนจาก</div>
		   <div style="display:inline;">วันที่</div>
		   <div style="display:inline;">'.($header["PROCESS_STARTDATE"]??null).'</div>
		   <div style="display:inline;">ถึง</div>
		   <div style="display:inline;">'.($header["PROCESS_ENDDATE"]??null).'</div>
		   <div style="display:inline;">เฉพาะที่เป็นสมาชิก</div>
		   <div style="display:inline;">ถึงวันที่</div>
		   <div style="display:inline;">'.($header["PROCESS_ACTIVEDATE"]??null).'</div>
		</div>
		<div>
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
					<th class="text-left" >รายการ</th>
					<th style="width:120px;">ยอดเงิน</th>
					<th style="width:120px;">การเฉลี่ยคืน</th>
					<th style="width:120px;" class="text-right">รวมเป็นเงิน(บาท)</th>
				</tr>';

	foreach($arrDetail AS $dataReport){
	$html.='           
				<tr>
				  <td>'.($dataReport["REMARK"]??null).'</td>
				  <td class="text-center">'.($dataReport["INTEREST_AMT"]??null).'</td>
				  <td class="text-center">'.($dataReport["AVG_RATE"]??null).'</td>
				  <td class="text-right">'.($dataReport["AVG_AMT"]??null).'</td>
				</tr>';
				}
	  $html.=' 
			  <tr style="line-height:20px">
				<th></th>
				<th></th>
				<th class="text-right">
					<div style="font-weight:normal;">รวมทั้งสิ้น</div>
					<div style="font-weight:normal;">ปันเศษขึ้น</div>
				</th>
				<th class="text-right">
					<div>'.number_format($sum_avg_amt,2).'</div>
					<div>'.number_format(ceil($sum_avg_amt),2).'</div>
				</th>
			  </tr>
			</table>
		</div>
	';
	
	$dompdf = new Dompdf([
		'fontDir' => realpath('../../resource/fonts'),
		'tempDir' => realpath('../../resource/fonts'),
		'chroot' => realpath('/'),
		'isRemoteEnabled' => true
	]);


	$dompdf->set_paper('A4');
	$dompdf->load_html($html);
	$dompdf->render();
	$pathfile = __DIR__.'/../../resource/pdf/avg';
	if(!file_exists($pathfile)){
		mkdir($pathfile, 0777, true);
	}
	$pathfile = $pathfile.'/'.$header["MEMBER_ID"].'.pdf';
	$pathfile_show = '/resource/pdf/avg/'.$header["MEMBER_ID"].'.pdf?v='.time();
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