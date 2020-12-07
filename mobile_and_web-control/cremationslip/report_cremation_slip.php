<?php
require_once('../autoload.php');

use Dompdf\Dompdf;

$dompdf = new DOMPDF();

if($lib->checkCompleteArgument(['menu_component','slip_type','receipt_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'CremationSlip')){
		$member_no = $configAS[$payload["member_no"]] ?? TRIM($payload["member_no"]);
		
		$header = array();
		$arrGroupDetail = array();
		
		//header
		if($dataComing["slip_type"] == "SWU"){
			$getCremationSWU = $conoracle->prepare("select wcdm.deptaccount_no,wcdm.wfaccount_name,wcrm.recv_period,wcrm.receipt_no,
												wcds.deptslip_date,
												CONCAT(to_char( wcds.deptslip_date,'dd/mm/'),to_char( wcds.deptslip_date,'yyyy')+543) as DEPTSLIP_DATE_FORMAT,
												wcds.operate_date,
												CONCAT(to_char( wcds.operate_date,'dd/mm/'),to_char( wcds.operate_date,'yyyy')+543) as OPERATE_DATE_DATE_FORMAT,
												wcds.deptslip_amt, to_char( wcds.deptslip_date,'yyyy')+543 as YEAR,
												wcdsd.prncslip_amt,wcdsd.slip_desc
												from scobkmsvwf.wcdeptmaster wcdm 
												join scobkmsvwf.wcrecievemonth wcrm on wcrm.member_no  = wcdm.member_no
												join scobkmsvwf.WCDEPTSLIP wcds on wcrm.receipt_no = wcds.deptslip_no
												join scobkmsvwf.WCDEPTSLIPDET wcdsd on wcds.deptslip_no = wcdsd.deptslip_no
												WHERE TRIM(wcdm.member_no) = :member_no and wcrm.wcitemtype_code = 'WPF' and wcrm.receipt_no = :receipt_no");
			$getCremationSWU->execute([
				':member_no' => $member_no,
				':receipt_no' => $dataComing['receipt_no']
			]);
			while($rowPeriodMW = $getCremationSWU->fetch(PDO::FETCH_ASSOC)){
				
				$header["member_no"] = $payload["member_no"];
				$header["member_name"] = $rowPeriodMW["WFACCOUNT_NAME"];
				$header["wfaccount_no"] = $rowPeriodMW["DEPTACCOUNT_NO"];
				$header["receipt_no"] = $rowPeriodMW["RECEIPT_NO"];
				$header["branch_id"] = $payload["branch_id"];
				$header["operate_date"] = $rowPeriodMW["OPERATE_DATE_DATE_FORMAT"];
				$header["deptslip_date_date"] = $rowPeriodMW["DEPTSLIP_DATE_FORMAT"];
				
				$arrayReport = array();
				$arrayReport["DETAIL"] = $rowPeriodMW["SLIP_DESC"];
				$arrayReport["AMT"] = number_format($rowPeriodMW["PRNCSLIP_AMT"],2);
				$arrayReport["AMT_NOTFORMAT"] = $rowPeriodMW["PRNCSLIP_AMT"];
				$arrGroupDetail[] = $arrayReport;
			}
		}else if($dataComing["slip_type"] == "SSOT"){
			//ครูไทย
			$getReceiveSSOT = $conoracle->prepare("select mb.card_person,ssot.prename || ssot.deptaccount_name || ' ' || ssot.deptaccount_sname as member_name, trim( ssot.deptslipbranch_no ) as deptslipbranch_no,
											ssot.FEE_YEAR,ssot.fee_amt,
											ssot.deptslip_date,
											CONCAT(to_char( ssot.deptslip_date,'dd/mm/'),to_char( ssot.deptslip_date,'yyyy')+543) as DEPTSLIP_DATE_FORMAT,
											ssot.wfmember_no , to_char( ssot.deptslip_date,'yyyy')+543 as YEAR
											from mbmembmaster mb
											join scobkmsvwf.wcrecieve_ssot ssot on ssot.card_person = mb.card_person
											WHERE TRIM(mb.member_no) = :member_no and deptslipbranch_no = :deptslipbranch_no");
			$getReceiveSSOT->execute([
				':member_no' => $member_no,
				':deptslipbranch_no' => $dataComing['receipt_no']
			]);
			while($rowReceiveSSOT = $getReceiveSSOT->fetch(PDO::FETCH_ASSOC)){	
				$header["member_no"] = $payload["member_no"];
				$header["member_name"] = $rowReceiveSSOT["MEMBER_NAME"];
				$header["wfmember_no"] = $rowReceiveSSOT["WFMEMBER_NO"];
				$arrSlipNo = str_split($rowReceiveSSOT["DEPTSLIPBRANCH_NO"]);
				$SlipNoText = "";
				for($i = 0;$i < sizeof($arrSlipNo);$i++){
					if($i == 4 || $i == 6){
						$SlipNoText .= '-'.$arrSlipNo[$i];
					}else{
						$SlipNoText .= $arrSlipNo[$i];
					}
				}
				$header["receipt_no"] = $rowReceiveSSOT["DEPTSLIPBRANCH_NO"];
				$header["receipt_no_format"] = $SlipNoText;
				$header["branch_id"] = $payload["branch_id"];
				$header["operate_date"] = $rowReceiveSSOT["DEPTSLIP_DATE_FORMAT"];
				$arrayReport = array();
				$arrayReport["DETAIL"] = "เงินสงเคราะห์ล่วงหน้าประจำปี ".$rowReceiveSSOT["YEAR"];
				$arrayReport["AMT"] = number_format($rowReceiveSSOT["FEE_YEAR"],2);
				$arrayReport["AMT_NOTFORMAT"] = $rowReceiveSSOT["FEE_YEAR"];
				$arrGroupDetail[] = $arrayReport;
				$arrayReport = array();
				$arrayReport["DETAIL"] = "ค่าบำรุงรายปีประจำปี ".$rowReceiveSSOT["YEAR"];
				$arrayReport["AMT"] = number_format($rowReceiveSSOT["FEE_AMT"],2);
				$arrayReport["AMT_NOTFORMAT"] = $rowReceiveSSOT["FEE_AMT"];
				$arrGroupDetail[] = $arrayReport;
			}				
		} else if($dataComing["slip_type"] == "FTSC"){
			$getReceiveFTSC = $conoracle->prepare("select mb.card_person,ftsc.prename || ftsc.deptaccount_name || ' ' || ftsc.deptaccount_sname as member_name, trim( ftsc.deptslipbranch_no ) as deptslipbranch_no,
											ftsc.FEE_YEAR,ftsc.fee_amt,
											ftsc.deptslip_date,
											CONCAT(to_char( ftsc.deptslip_date,'dd/mm/'),to_char( ftsc.deptslip_date,'yyyy')+543) as DEPTSLIP_DATE_FORMAT,
											ftsc.wfmember_no,ftsc.deptslip_no, to_char( ftsc.deptslip_date,'yyyy')+543 as YEAR
											from mbmembmaster mb
											join scobkmsvwf.wcrecieve_ftsc ftsc on TRIM(ftsc.card_person) = TRIM(mb.card_person)
											WHERE TRIM(mb.member_no) = :member_no and deptslipbranch_no = :deptslipbranch_no");
			$getReceiveFTSC->execute([
				':member_no' => $member_no,
				':deptslipbranch_no' => $dataComing['receipt_no']
			]);
			while($rowReceiveFTSC = $getReceiveFTSC->fetch(PDO::FETCH_ASSOC)){	
				$header["member_no"] = $payload["member_no"];
				$header["member_name"] = $rowReceiveFTSC["MEMBER_NAME"];
				$header["wfmember_no"] = $rowReceiveFTSC["WFMEMBER_NO"];
				$arrSlipNo = str_split($rowReceiveFTSC["DEPTSLIPBRANCH_NO"]);
				$SlipNoText = "";
				for($i = 0;$i < sizeof($arrSlipNo);$i++){
					if($i == 4 || $i == 6){
						$SlipNoText .= '-'.$arrSlipNo[$i];
					}else{
						$SlipNoText .= $arrSlipNo[$i];
					}
				}
				$header["receipt_no_format"] = $SlipNoText;
				$header["receipt_no"] = $rowReceiveFTSC["DEPTSLIPBRANCH_NO"];
				$header["receipt_no_raw"] = $rowReceiveFTSC["DEPTSLIP_NO"];
				$header["branch_id"] = $payload["branch_id"];
				$header["operate_date"] = $rowReceiveFTSC["DEPTSLIP_DATE_FORMAT"];
				$arrayReport = array();
				$arrayReport["DETAIL"] = "เงินสงเคราะห์ล่วงหน้าประจำปี ".$rowReceiveFTSC["YEAR"];
				$arrayReport["AMT"] = number_format($rowReceiveFTSC["FEE_YEAR"],2);
				$arrayReport["AMT_NOTFORMAT"] = $rowReceiveFTSC["FEE_YEAR"];
				$arrGroupDetail[] = $arrayReport;
				$arrayReport = array();
				$arrayReport["DETAIL"] = "ค่าบำรุงรายปีประจำปี ".$rowReceiveFTSC["YEAR"];
				$arrayReport["AMT"] = number_format($rowReceiveFTSC["FEE_AMT"],2);
				$arrayReport["AMT_NOTFORMAT"] = $rowReceiveFTSC["FEE_AMT"];
				$arrGroupDetail[] = $arrayReport;
			}				
		}
		
		if($dataComing["slip_type"] == "SWU"){
			$arrayPDF = GenerateReportSWU($arrGroupDetail,$header,$lib);
		}else if($dataComing["slip_type"] == "SSOT"){
			$arrayPDF = GenerateReportSSOT($arrGroupDetail,$header,$lib);
		}else if($dataComing["slip_type"] == "FTSC"){
			$arrayPDF = GenerateReportFTSC($arrGroupDetail,$header,$lib);
		}
		
		if($arrayPDF["RESULT"]){
			$arrayResult['REPORT_URL'] = $config["URL_SERVICE"].$arrayPDF["PATH"];
			$arrayResult['RESULT'] = TRUE;
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
			//$lib->sendLineNotify($message_error);
			$arrayResult['RESPONSE_CODE'] = "WS0044";
			$arrayResult['arrGroupDetail'] = $arrGroupDetail;
			$arrayResult['header'] = $header;
			$arrayResult['member_no'] = $member_no;
			$arrayResult['receipt_no'] = $dataComing['receipt_no'];
			$arrayResult['slip_type'] = $dataComing['slip_type'];
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
function GenerateReportSWU($dataReport,$header,$lib){
	$sumBalance = 0;			
	$color_theme = '#202020';
	$bg_color = '#afb6d4';
	$logo = '../../resource/logo/SWU.jpg';

	$html = '
		<style>

		@font-face {
		  font-family: THSarabun;
		  src: url(../../resource/fonts/THSarabun.ttf);
		}
		@font-face {
			font-family: "THSarabun";
			src: url(../../resource/fonts/THSarabun Bold.ttf);
			font-weight: bold;
		}
		* {
		  font-family: THSarabun;
		}

		body {
		  padding:0px;
		  font-size:20px;
		  color:'.$color_theme.';
		}
		p{
		  margin:0;
		}
		h5,h6{
		  margin:0;
		  font-weight:normal;
		}
		table{
			border:1.5px solid'.$color_theme.';
		}
		th{
		  text-align:center;
		  border:1.5px solid ;
		}
		.text-right{
		  text-align:right;
		}
		.text-center{
		  text-align:center
		}
		td{
		  border:1.5px solid ;
		  paddig:1px;
		}
		.text-head{
		  font-size:20px; 
		  line-height: 20px;
		  font-weight:bold
		}
		.text-subtitle{
		  font-size:20px; 
		  line-height: 20px;
		}
		.mert-table{
		  border-right:1.5px solid '.$color_theme.';
		  text-align:right;
		}
		.sum-tb{
		  border-right:1.5px  solid '.$color_theme.';
		}
		.bg-color{
		  background-color:'.$bg_color.';
		}
		</style>';

	 $html .='
		  <div style="height:auto;padding-bottom:1cm">
			<table style="border-collapse: collapse; border-left:none">
				<tr>
					<td style="text-align: center; vertical-align: middle;border:none;">
						<img src='.$logo.' style="width:90px"/>
					</td>
					<td style="text-align: left; vertical-align: middle;border:none;width: 100%;padding-left: 10px">
						<div style="text-align: center">
							<div class="text-head">ใบเสร็จรับเงิน</div>
							<div class="text-subtitle">สมาคมฌาปณกิจสงเคราะห์ สหกรณ์ออมทรัพย์ มหาวิทยาลัยศรีนครินทรวิโรฒ จำกัด</div>
						</div>
					</td>
				</tr>
			</table>
			<div style="border-bottom:1px solid '.$color_theme.';margin-top: 10px"></div>
			<table style="width:100%;  border-collapse: collapse; border-left:none">
				<tr>
					<td style="border:none;">
						<div>สำนักงาน 0001 : สำนักงานประสานมิตร</div>
					</td>
					<td style="border:none;">
						<div>วันที่ใบเสร็จ : '.$header["deptslip_date"].'</div>
					</td>
					<td style="border:none;">
						<div>วันที่จ่าย : '.$header["operate_date"].'</div>
					</td>
					<td style="border:none;text-align: right;">
						<div style="font-weight: bold">เลขที่ใบเสร็จ : '.$header["receipt_no"].'</div>
					</td>
				</tr>
			</table>
			<div>ได้รับเงินจาก '.$header["member_name"].' เลขสมาชิกสหกรณ์ '.$header["member_no"].' เลขฌาปณกิจ '.$header["wfaccount_no"].'</div>
	';

	$html .='
	  <div style="width:100%;">
	  <table style="width:100%;  border-collapse: collapse; border:none;margin-top: 10px ">
		  <thead>
			  <tr>
				 <th  style="font-weight: normal">ลำดับ</th>
				 <th style="font-weight: normal">รายการ</th>
				 <th style="font-weight: normal">จำนวนเงิน</th>
			  </tr>
		  </thead>
		 <tbody>
	';

	for($i = 0;$i < sizeof($dataReport); $i++){
	  $html .='
		<tr>
			<td style="padding-left: 5px;padding-right: 5px" class="text-center">'.($i+1).'.</td>
			<td style="padding-left: 5px;padding-right: 5px">'.($dataReport[$i]["DETAIL"] ?? null).'</td>
			<td style="padding-left: 5px;padding-right: 5px" class="text-right">'.($dataReport[$i]["AMT"] ?? null).'</td>
		</tr> ';
		$sumBalance += $dataReport[$i]["AMT_NOTFORMAT"];
	}

	//ยอดรวม
	$html .= '
	  <tr>
		<th>รวม</th>
		<th style="text-align: left;padding-left: 5px;padding-right: 5px">
		  '.$lib->baht_text($sumBalance).'
		</th>
		<th  class="text-right" style="padding-left: 5px;padding-right: 5px">
		  '.number_format($sumBalance,2).'
		</th>
	  </tr>
	';
	$html .='
		</tbody>
	  </table>
	  </div>
	<div style="margin-top: 40px">
	  <table style="width:100%;  border-collapse: collapse; border-left:none">
		<tr>
			<td style="border:none;width: 50%;">
				นายกสมาคมฯ/ผู้มีอำนาจ
			</td>
			<td style="border:none;;width: 50%;">
				ผู้รับเงิน
			</td>
		</tr>
	</table>
	</div>
	<div style="margin-top: 40px">
		<h6>วันที่พิมพ์</h6>
	</div>
	<div style="clear: both;"></div>
	</div>';

	$dompdf = new DOMPDF();
	$dompdf->set_paper('A4');
	$dompdf->load_html($html);
	$dompdf->render();
	$pathfile = __DIR__.'/../../resource/pdf/cremation_slip';
	if(!file_exists($pathfile)){
		mkdir($pathfile, 0777, true);
	}
	$rep_receipt_no = str_replace("/","_",$header["receipt_no"]);
	$pathfile = $pathfile.'/'.$header["member_no"]."_SWU_".$rep_receipt_no.'.pdf';
	$pathfile_show = '/resource/pdf/cremation_slip/'.$header["member_no"]."_SWU_".$rep_receipt_no.'.pdf?v='.time();
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

function GenerateReportFTSC($dataReport,$header,$lib){
	$sumBalance = 0;			
	$color_theme = '#202020';
	$bg_color = '#afb6d4';
	$logo = '../../resource/logo/FTSC.jpg';

	$html = '
		<style>

		@font-face {
		  font-family: THSarabun;
		  src: url(../../resource/fonts/THSarabun.ttf);
		}
		@font-face {
			font-family: "THSarabun";
			src: url(../../resource/fonts/THSarabun Bold.ttf);
			font-weight: bold;
		}
		* {
		  font-family: THSarabun;
		}

		body {
		  padding:0px;
		  font-size:20px;
		  color:'.$color_theme.';
		}
		p{
		  margin:0;
		}
		h5,h6{
		  margin:0;
		  font-weight:normal;
		}
		table{
			border:1.5px solid'.$color_theme.';
		}
		th{
		  text-align:center;
		  border:1.5px solid ;
		}
		.text-right{
		  text-align:right;
		}
		.text-center{
		  text-align:center
		}
		td{
		  border:1.5px solid ;
		  paddig:1px;
		}
		.text-head{
		  font-size:20px; 
		  line-height: 20px;
		  font-weight:bold
		}
		.text-subtitle{
		  font-size:20px; 
		  line-height: 20px;
		}
		.mert-table{
		  border-right:1.5px solid '.$color_theme.';
		  text-align:right;
		}
		.sum-tb{
		  border-right:1.5px  solid '.$color_theme.';
		}
		.bg-color{
		  background-color:'.$bg_color.';
		}
		</style>';

	 $html .='
		  <div style="height:auto;padding-bottom:1cm">
			<table style="width:100%;  border-collapse: collapse; border-left:none">
				<tr>
					<td style="text-align: center; vertical-align: middle;border:none;">
						<img src='.$logo.' style="width:90px"/>
					</td>
					<td style="text-align: center; vertical-align: middle;border:none;">
						<div class="text-head">ใบเสร็จรับเงิน [สำเนา]</div>
						<h5>ศูนย์ประสานงาน : 0047 - มหาวิทยาลัยศรีนครินทรวิโรฒ</h5>
						<div class="text-subtitle">สมาคมฌาปณกิจสงเคราะห์สมาชิกของชุมนุมสหกรณ์ออมทรัพย์แห่งประเทศไทย</div>
					</td>\
					<td style="vertical-align: top;border:none;white-space: nowrap">
						<h5>เลขที่ใบเสร็จ :   '.$header["receipt_no_raw"].'</h5>
						<h5>เลขที่ใบเสร็จศูนย์ :   '.$header["receipt_no_format"].'</h5>
					</td>
				</tr>
			</table>
			<div style="border-bottom:1px solid '.$color_theme.';margin-top: 10px"></div>
			<h5>เลขที่ 199 หมู่ที่ 2 ถนนนครอินทร์ ตำบลบางสีทอง อำเภอบางกรวย จังหวัดนนทบุรี 11130 โทรศัพท์ 0 2496 1337 โทรสาร 0 2496 1338</h5>
			<div class="text-subtitle" style="text-align: right;padding-right: 40px;">วันที่จ่าย :   '.$header["operate_date"].'</div>
			<h5 style="font-weight: bold">ได้รับเงินจาก '.$header["member_name"].' เลขสมาชิกสหกรณ์ '.$header["member_no"].' เลขฌาปณกิจ '.$header["wfmember_no"].'</h5>
	';

	$html .='
	  <div style="width:100%;">
	  <table style="width:100%;  border-collapse: collapse; border:none;margin-top: 10px ">
		  <thead>
			  <tr>
				 <th>ลำดับ</th>
				 <th>รายการ</th>
				 <th>จำนวนเงิน</th>
			  </tr>
		  </thead>
		 <tbody>
	';

	for($i = 0;$i < sizeof($dataReport); $i++){
	  $html .='
		<tr>
			<td style="padding-left: 5px;padding-right: 5px" class="text-center">'.($i+1).'.</td>
			<td style="padding-left: 5px;padding-right: 5px">'.($dataReport[$i]["DETAIL"] ?? null).'</td>
			<td style="padding-left: 5px;padding-right: 5px" class="text-right">'.($dataReport[$i]["AMT"] ?? null).'</td>
		</tr> ';
		$sumBalance += $dataReport[$i]["AMT_NOTFORMAT"];
	}

	//ยอดรวม
	$html .= '
	  <tr>
		<th>รวม</th>
		<th style="text-align: left;padding-left: 5px;padding-right: 5px">
		  '.$lib->baht_text($sumBalance).'
		</th>
		<th  class="text-right" style="padding-left: 5px;padding-right: 5px">
		  '.number_format($sumBalance,2).'
		</th>
	  </tr>
	';
	$html .='
		</tbody>
	  </table>
	  </div>
	<div style="margin-top: 40px">
	  <table style="width:100%;  border-collapse: collapse; border-left:none">
		<tr>
			<td style="border:none;width: 50%;">
				นายกสมาคมฯ/ผู้มีอำนาจ
			</td>
			<td style="border:none;;width: 50%;">
				ผู้รับเงิน
			</td>
		</tr>
	</table>
	</div>
	<div style="margin-top: 40px">
		<h6>วันที่พิมพ์</h6>
	</div>
	<div style="clear: both;"></div>
	</div>';

	$dompdf = new DOMPDF();
	$dompdf->set_paper('A4');
	$dompdf->load_html($html);
	$dompdf->render();
	$pathfile = __DIR__.'/../../resource/pdf/cremation_slip';
	if(!file_exists($pathfile)){
		mkdir($pathfile, 0777, true);
	}
	$rep_receipt_no = str_replace("/","_",$header["receipt_no"]);
	$pathfile = $pathfile.'/'.$header["member_no"]."_FTSC_".$rep_receipt_no.'.pdf';
	$pathfile_show = '/resource/pdf/cremation_slip/'.$header["member_no"]."_FTSC_".$rep_receipt_no.'.pdf?v='.time();
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

function GenerateReportSSOT($dataReport,$header,$lib){
	$sumBalance = 0;			
	$color_theme = '#202020';
	$bg_color = '#afb6d4';
	$logo = '../../resource/logo/SSOT.jpg';

	$html = '
		<style>

		@font-face {
		  font-family: THSarabun;
		  src: url(../../resource/fonts/THSarabun.ttf);
		}
		@font-face {
			font-family: "THSarabun";
			src: url(../../resource/fonts/THSarabun Bold.ttf);
			font-weight: bold;
		}
		* {
		  font-family: THSarabun;
		}

		body {
		  padding:0px;
		  font-size:20px;
		  color:'.$color_theme.';
		}
		p{
		  margin:0;
		}
		h5,h6{
		  margin:0;
		  font-weight:normal;
		}
		table{
			border:1.5px solid'.$color_theme.';
		}
		th{
		  text-align:center;
		  border:1.5px solid ;
		}
		.text-right{
		  text-align:right;
		}
		.text-center{
		  text-align:center
		}
		td{
		  border:1.5px solid ;
		  paddig:1px;
		}
		.text-head{
		  font-size:20px; 
		  line-height: 20px;
		  font-weight:bold
		}
		.text-subtitle{
		  font-size:20px; 
		  line-height: 20px;
		}
		.mert-table{
		  border-right:1.5px solid '.$color_theme.';
		  text-align:right;
		}
		.sum-tb{
		  border-right:1.5px  solid '.$color_theme.';
		}
		.bg-color{
		  background-color:'.$bg_color.';
		}
		</style>';

	 $html .='
		  <div style="height:auto;padding-bottom:1cm">
			<table style="width:100%;  border-collapse: collapse; border-left:none">
				<tr>
					<td style="text-align: center; vertical-align: middle;border:none;">
						<img src='.$logo.' style="width:90px"/>
					</td>
					<td style="text-align: center; vertical-align: middle;border:none;">
						<div class="text-head">ใบเสร็จรับเงิน [สำเนา]</div>
						<h5>ศูนย์ประสานงาน : 0047 - มหาวิทยาลัยศรีนครินทรวิโรฒ</h5>
						<div class="text-subtitle">สมาคมฌาปนกิจสงเคราะห์สมาชิกชุมนุมสหกร์ออมทรัพท์ครูไทย</div>
					</td>\
					<td style="vertical-align: top;border:none;white-space: nowrap;">
						<h5>เลขที่ใบเสร็จ :   '.$header["receipt_no_format"].'</h5>
					</td>
				</tr>
			</table>
			<div style="border-bottom:1px solid '.$color_theme.';margin-top: 10px"></div>
			<h5>เลขที่ 199 หมู่ที่ 2 ถนนนครอินทร์ ตำบลบางสีทอง อำเภอบางกรวย จังหวัดนนทบุรี 11130 โทรศัพท์ 0 2496 1337 โทรสาร 0 2496 1338</h5>
			<div class="text-subtitle" style="text-align: right;padding-right: 40px;">วันที่จ่าย :   '.$header["operate_date"].'</div>
			<h5 style="font-weight: bold">ได้รับเงินจาก '.$header["member_name"].' เลขสมาชิกสหกรณ์ '.$header["member_no"].'   เลขฌาปณกิจ '.$header["wfmember_no"].'</h5>
	';

	$html .='
	  <div style="width:100%;">
	  <table style="width:100%;  border-collapse: collapse; border:none;margin-top: 10px ">
		  <thead>
			  <tr>
				 <th style="font-weight: normal">ลำดับ</th>
				 <th style="font-weight: normal">รายการ</th>
				 <th style="font-weight: normal">จำนวนเงิน</th>
			  </tr>
		  </thead>
		 <tbody>
	';

	for($i = 0;$i < sizeof($dataReport); $i++){
	  $html .='
		<tr>
			<td style="padding-left: 5px;padding-right: 5px" class="text-center">'.($i+1).'.</td>
			<td style="padding-left: 5px;padding-right: 5px">'.($dataReport[$i]["DETAIL"] ?? null).'</td>
			<td style="padding-left: 5px;padding-right: 5px" class="text-right">'.($dataReport[$i]["AMT"] ?? null).'</td>
		</tr> ';
		$sumBalance += $dataReport[$i]["AMT_NOTFORMAT"];
	}

	//ยอดรวม
	$html .= '
	  <tr>
		<th>รวม</th>
		<th style="text-align: left;padding-left: 5px;padding-right: 5px">
		  '.$lib->baht_text($sumBalance).'
		</th>
		<th  class="text-right" style="padding-left: 5px;padding-right: 5px">
		  '.number_format($sumBalance,2).'
		</th>
	  </tr>
	';
	$html .='
		</tbody>
	  </table>
	  </div>
	<div style="margin-top: 40px">
	  <table style="width:100%;  border-collapse: collapse; border-left:none">
		<tr>
			<td style="border:none;width: 50%;">
				นายกสมาคมฯ/ผู้มีอำนาจ
			</td>
			<td style="border:none;;width: 50%;">
				ผู้รับเงิน
			</td>
		</tr>
	</table>
	</div>
	<div style="margin-top: 40px">
		<h6>วันที่พิมพ์</h6>
	</div>
	<div style="clear: both;"></div>
	</div>';

	$dompdf = new DOMPDF();
	$dompdf->set_paper('A4');
	$dompdf->load_html($html);
	$dompdf->render();
	$pathfile = __DIR__.'/../../resource/pdf/cremation_slip';
	if(!file_exists($pathfile)){
		mkdir($pathfile, 0777, true);
	}
	$rep_receipt_no = str_replace("/","_",$header["receipt_no"]);
	$pathfile = $pathfile.'/'.$header["member_no"]."_SSOT_".$rep_receipt_no.'.pdf';
	$pathfile_show = '/resource/pdf/cremation_slip/'.$header["member_no"]."_SSOT_".$rep_receipt_no.'.pdf?v='.time();
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