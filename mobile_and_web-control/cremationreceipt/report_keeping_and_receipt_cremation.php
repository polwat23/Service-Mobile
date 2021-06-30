<?php
require_once('../autoload.php');

use Dompdf\Dompdf;

$dompdf = new DOMPDF();

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SlipCremation')){
		$member_no = $configAS[$payload["member_no"]] ?? TRIM($payload["member_no"]);
		if($dataComing["is_extra"]){
			$getReceiptDataHeader = $conoracle->prepare("SELECT wfd.DEPTSLIP_AMT,wfm.WFACCOUNT_NAME,wfm.DEPTACCOUNT_NO,wfd.DEPTSLIP_NO,wfd.ENTRY_ID,wfd.DEPTSLIP_AMT,
															wfm.MEMBGROUP_CODE,wfd.DEPTSLIP_DATE
															FROM wfdeptslip wfd LEFT JOIN wfdeptmaster wfm ON wfd.DEPTACCOUNT_NO = wfm.DEPTACCOUNT_NO 
															WHERE TRIM(wfd.deptslip_no) = :slip_no");
			$getReceiptDataHeader->execute([':slip_no' => $dataComing["receipt_no"]]);
			$rowReceiptData = $getReceiptDataHeader->fetch(PDO::FETCH_ASSOC);
			$header["receipt_no"] = $dataComing["receipt_no"];
			$header["full_name"] = $rowReceiptData["WFACCOUNT_NAME"];
			$header["slip_amt"] = $rowReceiptData["DEPTSLIP_AMT"];
			$header["member_no"] = trim($rowReceiptData["DEPTACCOUNT_NO"]);
			$header["entry_id"] = trim($rowReceiptData["ENTRY_ID"]);
			$sqlGetMembGrp = $conoracle->prepare("SELECT (B.MEMBGROUP_DESC || ' / ' || A.MEMBGROUP_DESC ) AS MEMBGROUP_CODE_STR 
													FROM MBUCFMEMBGROUP A LEFT JOIN MBUCFMEMBGROUP B ON A.MEMBGROUP_CONTROL = B.MEMBGROUP_CODE 
													WHERE A.MEMBGROUP_CODE = :MEMBGRP");
			$sqlGetMembGrp->execute([':MEMBGRP' => $rowReceiptData["MEMBGROUP_CODE"]]);
			$rowMembGrp = $sqlGetMembGrp->fetch(PDO::FETCH_ASSOC);
			$header["member_group"] = $rowMembGrp["MEMBGROUP_CODE_STR"];
			$header["slip_date"] = $lib->convertdate($rowReceiptData["DEPTSLIP_DATE"],'d/n/Y');
			$getDetailSlipDet = $conoracle->prepare("SELECT SUM( CASE WHEN DEPTITEMTYPE_CODE = 'FEE' THEN PRNCSLIP_AMT ELSE 0.00 END ) AS REGISTER_FEE,
													  SUM( CASE WHEN DEPTITEMTYPE_CODE = 'WPF' THEN PRNCSLIP_AMT ELSE 0.00 END ) AS FUTURE_FEE,
													  SUM( CASE WHEN DEPTITEMTYPE_CODE = 'WFY' THEN PRNCSLIP_AMT ELSE 0.00 END ) AS MA_FEE,
													  SUM( CASE WHEN DEPTITEMTYPE_CODE IN( 'WPX', 'WPC', 'WRM', 'WRF' ) THEN PRNCSLIP_AMT ELSE 0.00 END ) AS ETC_FEE,
													  MAX( CASE WHEN DEPTITEMTYPE_CODE IN( 'WPX', 'WPC', 'WRM', 'WRF' ) THEN SLIP_DESC ELSE '' END ) AS ETC_DESC,
													  SUM( CASE WHEN DEPTITEMTYPE_CODE = 'WDN' THEN PRNCSLIP_AMT ELSE 0.00 END ) AS DONATE_FEE,
													  MAX( CASE WHEN DEPTITEMTYPE_CODE = 'WDN' THEN SLIP_DESC ELSE '' END ) AS DONATE_DESC 
													  FROM WFDEPTSLIPDET WHERE TRIM(DEPTSLIP_NO) = :slip_no");
			$getDetailSlipDet->execute([':slip_no' => $dataComing["receipt_no"]]);
			$rowDetailSlip = $getDetailSlipDet->fetch(PDO::FETCH_ASSOC);
			$arrGroupDetail = array();
			$arrGroupDetail["REGISTER_FEE"] = number_format($rowDetailSlip["REGISTER_FEE"],2);
			$arrGroupDetail["FUTURE_FEE"] = number_format($rowDetailSlip["FUTURE_FEE"],2);
			$arrGroupDetail["MA_FEE"] = number_format($rowDetailSlip["MA_FEE"],2);
			$arrGroupDetail["ETC_FEE"] = number_format($rowDetailSlip["ETC_FEE"],2);
			$arrGroupDetail["ETC_DESC"] = $rowDetailSlip["ETC_DESC"];
			$arrGroupDetail["DONATE_DESC"] = $rowDetailSlip["DONATE_DESC"];
			$arrGroupDetail["DONATE_FEE"] = number_format($rowDetailSlip["DONATE_FEE"],2);
			$arrayPDF = GenerateReportExtra($arrGroupDetail,$header,$lib);
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
				$lib->sendLineNotify($message_error);
				$arrayResult['RESPONSE_CODE'] = "WS0044";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
		}else{
			$getKeepingData = $conoracle->prepare("SELECT STATUS_POST,OPERATE_DATE,MEMBGROUP_CODE,RECEIPT_NO,CARCASS_AMT,
													WFACCOUNT_NAME,INS_AMT,FEE_YEAR,ADDADVANCE_AMT,WFMEMBER_NO 
													FROM WFRECIEVEMONTH 
													WHERE TRIM(MEMBER_NO) = :member_no AND RECV_PERIOD = :recv_period");
			$getKeepingData->execute([
				':member_no' => $member_no,
				':recv_period' => $dataComing["recv_period"]
			]);
			$rowKeeping = $getKeepingData->fetch(PDO::FETCH_ASSOC);
			$header["recv_period"] = $lib->convertperiodkp(TRIM($dataComing["recv_period"]));
			$header["member_no"] = $payload["member_no"];
			$header["wcmember_no"] = $rowKeeping["WFMEMBER_NO"];
			$header["status_post"] = $rowKeeping["STATUS_POST"];
			$header["sumall_pay"] = $rowKeeping["CARCASS_AMT"] + $rowKeeping["INS_AMT"] + $rowKeeping["FEE_YEAR"] + $rowKeeping["ADDADVANCE_AMT"];
			$header["receipt_no"] = TRIM($rowKeeping["RECEIPT_NO"]);
			$sqlGetMembGrp = $conoracle->prepare("SELECT (B.MEMBGROUP_DESC || ' / ' || A.MEMBGROUP_DESC ) AS MEMBGROUP_CODE_STR 
													FROM MBUCFMEMBGROUP A LEFT JOIN MBUCFMEMBGROUP B ON A.MEMBGROUP_CONTROL = B.MEMBGROUP_CODE 
													WHERE A.MEMBGROUP_CODE = :MEMBGRP");
			$sqlGetMembGrp->execute([':MEMBGRP' => $rowKeeping["MEMBGROUP_CODE"]]);
			$rowMembGrp = $sqlGetMembGrp->fetch(PDO::FETCH_ASSOC);
			$getAmtDiePerson = $conoracle->prepare("SELECT COUNT(wfaccount_name) AS DIE_AMT FROM WFDEPTMASTER 
													WHERE KPRECV_PERIOD = :recv_period ORDER BY DIE_DATE ASC, DEPTACCOUNT_NO ASC");
			$getAmtDiePerson->execute([':recv_period' => $dataComing["recv_period"]]);
			$rowdie = $getAmtDiePerson->fetch(PDO::FETCH_ASSOC);
			$arrDiePerson = array();
			$getDiePerson = $conoracle->prepare("SELECT WFACCOUNT_NAME FROM WFDEPTMASTER 
													WHERE KPRECV_PERIOD = :recv_period ORDER BY DIE_DATE ASC, DEPTACCOUNT_NO ASC");
			$getDiePerson->execute([':recv_period' => $dataComing["recv_period"]]);
			while($rowPersondie = $getDiePerson->fetch(PDO::FETCH_ASSOC)){
				$arrDiePerson[] = $rowPersondie["WFACCOUNT_NAME"];
			}
			$arrGroupDetail = array();
			$arrGroupDetail[0]["LABEL"] = "ค่าสงเคราะห์ศพ จำนวน ".$rowdie["DIE_AMT"]." ราย";
			$arrGroupDetail[0]["AMOUNT"] = number_format($rowKeeping["CARCASS_AMT"],2);
			$arrGroupDetail[1]["LABEL"] = "ค่าสงเคราะห์ศพค้างจ่าย";
			$arrGroupDetail[1]["AMOUNT"] = number_format($rowKeeping["INS_AMT"],2);
			$arrGroupDetail[2]["LABEL"] = "ค่าบำรุงรายปี";
			$arrGroupDetail[2]["AMOUNT"] = number_format($rowKeeping["FEE_YEAR"],2);
			$arrGroupDetail[3]["LABEL"] = "เงินสงเคราะห์ล่วงหน้า";
			$arrGroupDetail[3]["AMOUNT"] = number_format($rowKeeping["ADDADVANCE_AMT"],2);
			$arrGroupDetail[4]["LABEL"] = "รายชื่อผู้เสียชีวิต ประจำเดือน ".$lib->convertperiodkp(TRIM($dataComing["recv_period"]));
			$arrGroupDetail[5]["LABEL"] = $arrDiePerson;
			$header["fullname"] = $rowKeeping["WFACCOUNT_NAME"];
			$header["member_group"] = $rowMembGrp["MEMBGROUP_CODE_STR"];
			$header["die_amt"] = $rowdie["DIE_AMT"];
			$header["operate_date"] = $lib->convertdate($rowKeeping["OPERATE_DATE"],'D/n/Y');
			$arrayPDF = GenerateReport($arrGroupDetail,$header,$lib);
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
				$lib->sendLineNotify($message_error);
				$arrayResult['RESPONSE_CODE'] = "WS0044";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
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

function GenerateReportExtra($dataReport,$header,$lib){
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
			  padding: 0 ;
			  font: size: 15pt;
			  line-height: 22px;
			}
			div{
				line-height: 22px;
				font-size: 15pt;
				font-
			}
			.nowrap{
				white-space: nowrap;
			  }
			</style>';
	//ขนาด
	$html .= '<div style="margin:0 auto;">';


	$html .= '
		<div style=" text-align: center; margin-top:5px;"><img src="../../resource/logo/logo_wc.gif" alt="" width="80" height="0"></div>
		<div style="position:absolute; top:0px; right:45px;">
			<table>
				  <tr>
					<td>เลขที่</td>
					<td class="text-color">'.$header["receipt_no"].'</td>
				  </tr>
				  </table>
		</div>
		<div style="font-size: 22px;font-weight: bold; text-align:center; margin-center:30px; margin-top:10px;">สมาคมฌาปนกิจสงเคราะห์</div>
		<div style="font-size: 22px;font-weight: bold; text-align:center;">สหกรณ์ออมทรัพย์ข้าราชการกระทรวงศึกษาธิการ จำกัด</div>
		<div style="text-align:center;">319 อาคารสมานฉันท์ ชั้น 3 ถนนพิษณุโลก แขวงดุสิต เขตดุสิต กรุงเทพฯ 10300</div>
		<div style="text-align:center;">โทรศัพท์ : 0-2628-7500-3, 098-8309-093 โทรสาร : 0-2628-7504-5 www.moecoop.com</div>
		<div style="font-size: 22px;font-weight: bold; text-align:center; margin-top:10px;">ใบเสร็จรับเงิน</div>
			<table style="width: 100%;">
				<tbody>
				<tr>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td>วันที่</td>
					<td class="text-color">' . $header["slip_date"] . '</td>
				</tr>
				<tr>
					<td style="width: 60px; font-size: 18px;  ">ได้รับเงินจาก </td>
					<td style="width: 200px;" class="text-color">' .$header["full_name"]. '</td>
					<td style="width: 30px;font-size: 18px;">สังกัด &nbsp;</td>
					<td style="width: 220px;" class="text-color">' . $header["member_group"] . '</td>
					<td style="width: 10px;">&nbsp;</td>
					<td style="width: 80px;font-size: 18px;">เลขที่สมาชิก</td>
					<td class="text-color">' . $header["member_no"] . '</td>
				</tr>
				</tbody>
			</table>
			<div style="height:5px;"></div>
			<div style="display:flex;width: 100%;height: 40px; ">
				<div style="border-left:1px solid; border-top:1px solid; border-bottom:1px solid; width: 511px; text-align:center; font-weight:bold;">รายการ</div>
				<div style="border:1px solid ; width: 212px; text-align:center; margin-left:511px; font-weight:bold;">จำนวนเงิน</div>
			</div>';
			//detail
	$html .= '		
			<div style="border-left:1px solid; border-right:1px solid; border-bottom:1px solid; margin-top:-15px; height:160px;">
				<div style="position:absolute;   left: 511px">
				  <div style="border-left:1px solid; height:160px;"></div>
				</div>
				 <div>
					<div style="display:flex; height:27px;"> 
						<div style="padding-left:15px;  width:20px;">1.</div>
						<div style="margin-left:40px;  width:459px;">เงินค่าสมัคร</div>
						<div style="margin-left:511px;  text-align:right; padding-right:20px;">'.$dataReport["REGISTER_FEE"].'</div>
					</div>
					<div style="display:flex; height:27px;"> 
						<div style="padding-left:15px;  width:20px;">2.</div>
						<div style="margin-left:40px;  width:459px;">เงินสงเคราะห์ล่วงหน้า</div>
						<div style="margin-left:511px;  text-align:right; padding-right:20px;">'.$dataReport["FUTURE_FEE"].'</div>
					</div>
					<div style="display:flex; height:27px;"> 
						<div style="padding-left:15px;  width:20px;">3.</div>
						<div style="margin-left:40px;  width:459px;">ค่าบำรุง</div>
						<div style="margin-left:511px;  text-align:right; padding-right:20px;">'.$dataReport["MA_FEE"].'</div>
						</div>
					<div style="display:flex; height:27px;"> 
						<div style="padding-left:15px;  width:20px;">4.</div>
						<div style="margin-left:40px;  width:459px;">เงินบริจาค</div>
						<div style="margin-left:511px;  text-align:right; padding-right:20px;">'.$dataReport["DONATE_FEE"].'</div>
					</div>
					<div style="display:flex; height:27px;"> 
						<div style="padding-left:15px;  width:20px;">5.</div>
						<div style="margin-left:40px;  width:459px;">
							<div style="position:absolute; text-indent:50px;top:-1px;" class="text-color">
									'.$dataReport["ETC_DESC"].'
							</div>
							<div style="position:absolute; text-indent:70px;top:23px;margin-left:-20px;" class="text-color">
									'.$dataReport["DONATE_DESC"].'
							</div>
							<p style="margin:0;">อื่น ๆ..................................................................................................................</p>
							<p style="margin:0;">..........................................................................................................................</p>
						</div>
						<div style="margin-left:511px;  text-align:right; padding-right:20px;" class="text-color">'.$dataReport["ETC_FEE"].'</div>
					</div>	
				 </div>
			</div>';
	//footer	
	$html .= '		
			<div style="height:120px; border-left:1px solid;  border-right:1px solid; border-bottom:1px solid;">
				<div style="display:flex; height:47px;"> 
				   <div style="margin-left:15px; font-weight:bold;">รวมเงิน</div>
				   <div style="margin-left:80px;" class="text-color">('.$lib->baht_text($header["slip_amt"]).')</div>
				   <div style="margin-left:510px; border-left:solid 1px black; border-bottom:solid 1px black;  text-align:right; padding-right:20px; height:30px;" class="text-color">'.number_format($header["slip_amt"],2).'</div>
				</div>	';
	if($header["entry_id"] == 'ann'){
		$html .= '<div style="text-align:right; "><img src="../../resource/utility_icon/signature/ann.png "width="40" style="margin-right:150px;margin-top:-10px; "/></div>';
		$html .= '<div style="text-align:right; padding-right:50px; margin-top:-30px;">
				   <div>  ลงชื่อ............................................ผู้รับเงิน</div>
				</div>
				<div style="text-align:center; margin-left:390px; margin-top:-5px;  margin-right:20px">
				   ( นางสาววราภรณ์   หอมเนียม)
				</div>
			</div>
		';
	}else if($header["entry_id"] == 'addy'){
		$html .= '<div style="text-align:right; "><img src="../../resource/utility_icon/signature/addy.jpg "width="40"  style="margin-right:150px;margin-top:-10px; "/></div>';
		$html .= '<div style="text-align:right; padding-right:50px; margin-top:-30px;">
				   <div>  ลงชื่อ............................................ผู้รับเงิน</div>
				</div>
				<div style="text-align:center; margin-left:390px; margin-top:-5px;  margin-right:20px">
				   (นางกฤษณา   ยืนยง)
				</div>
			</div>
		';
	}
	
	$html .= '</div>';
	$dompdf = new Dompdf([
		'fontDir' => realpath('../../resource/fonts'),
		'chroot' => realpath('/'),
		'isRemoteEnabled' => true
	]);

	//$dompdf->set_paper('A4', 'landscape');
	$dompdf->load_html($html);
	$dompdf->render();
	$pathfile = __DIR__.'/../../resource/pdf/cremation';
	if(!file_exists($pathfile)){
		mkdir($pathfile, 0777, true);
	}
	$pathfile = $pathfile.'/'.$header["member_no"].$header["receipt_no"].'.pdf';
	$pathfile_show = '/resource/pdf/cremation/'.urlencode($header["member_no"]).$header["receipt_no"].'.pdf?v='.time();
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

function GenerateReport($dataReport,$header,$lib){
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

		body {
		  padding: 0 px;
		}
		.sub-table div{
			padding : 5px;
		}
		</style>

		<div style="display: flex;text-align: center;position: relative;margin-bottom: 0px;">
		<div style="text-align: left;"><img src="../../resource/logo/logo_wc.gif" style="margin: -10px 0 0 5px" alt="" width="80" height="80" /></div>
		<div style="text-align:left;position: absolute;width:100%;margin-left: 120px">';
		$html .= '<p style="margin-top: -10px;font-size: 22px;font-weight: bold">สมาคมฌาปนกิจสงเคราะห์สหกรณ์ออมทรัพย์ข้าราชการกระทรวงศึกษาธิการ จำกัด</p>
		<p style="margin-top: -27px;font-size: 18px;">319 อาคารสมานฉันท์ ชั้น 3 ถนนพิษณุโลก แขวงดุสิต เขตดุสิต กรุงเทพฯ 10300</p>
		<p style="margin-top: -25px;font-size: 18px;">โทรศัพท์ : 0-2628-7500-3, 098-8309-093 โทรสาร : 0-2628-7504-5 www.moecoop.com</p>
		</div>
		</div>
		<div style="margin: -30px 0 -30px 0;">';
		if ($header["status_post"] == '1') {
		  $html .= '<p style="font-size: 25px;font-weight: bold;margin-left: 320px;">ใบเสร็จรับเงิน</p>';
		} else {
		  $html .= '<p style="font-size: 25px;font-weight: bold;margin-left: 320px;">ใบเรียกเก็บเงิน</p>';
		}
		$html .= '<table style="width: 100%;margin-top:-30px;margin-bottom: 27px;">
		<tbody>
		<tr>
		';
		if ($header["status_post"] == '1') {
			$html .= '<td style="width: 80px; font-size: 18px; ">วันที่ใบเสร็จ :</td>
			<td style="width: 310px; ">' . $header["operate_date"] . '</td>
			<td style="width: 50px;font-size: 18px;">เลขที่ใบเสร็จ :</td>
			<td style="width: 101px;">' . $header["receipt_no"] . '</td>';
		}else{
			$html .= '<td style="width: 80px; font-size: 18px; ">วันที่ :</td>
			<td style="width: 310px; ">' . $header["operate_date"] . '</td>
			<td style="width: 50px;font-size: 18px;">เลขที่ :</td>
			<td style="width: 101px;">' . $header["receipt_no"] . '</td>';	
		}
		$html .= '</tr>
		<tr>
		<td style="width: 50px;font-size: 18px;">ได้รับเงินจาก :</td>
		<td style="width: 310px;">' . $header["fullname"] . '</td>
		<td style="width: 50px;font-size: 18px;">เลขสมาชิก :</td>
		<td style="width: 101px;">' . $header["wcmember_no"] . '</td>
		</tr>
		<tr>
		<td style="width: 50px;font-size: 18px;">หน่วยงาน :</td>
		<td style="width: 310px;">' . $header["member_group"] . '</td>
		<td style="width: 50px;font-size: 18px;"></td>
		<td style="width: 101px;"></td>
		</tr>
		</tbody>
		</table>
		</div>
		<div>
		<div style="display:flex;width: 100%;height: 20px;" class="sub-table">
		<div style="border-bottom: 0.5px solid #CFCFCF;border-top: 0.5px solid #CFCFCF;">&nbsp;</div>
		<div style="width: 500px;text-align: center;font-size: 18px;font-weight: bold;padding-top: 1px;">รายการ</div>
		<div style="width: 150px;text-align: right;font-size: 18px;font-weight: bold;margin-left: 550px;padding-top: 1px;">จำนวนเงิน</div>
		</div>';


		// Detail
		$html .= '<div style="width: 100%;" class="sub-table">';
		foreach ($dataReport as $dataArr) {
			if (is_array($dataArr["LABEL"])) {
				$dataPersonForLoop = array_chunk($dataArr["LABEL"],3);
				$html .= '<table style="height: 30px;padding:10px;">';
					foreach($dataPersonForLoop as $key => $value){
						$html .= '<tr>';
						if(isset($dataPersonForLoop[$key][0])){
							$html .= '<td style="padding:0 20px;">'.($dataPersonForLoop[$key][0] ?? null).'</td>';
						}
						if(isset($dataPersonForLoop[$key][1])){
							$html .= '<td style="padding:0 20px;">'.($dataPersonForLoop[$key][1] ?? null).'</td>';
						}
						if(isset($dataPersonForLoop[$key][2])){
							$html .= '<td style="padding:0 20px;">'.($dataPersonForLoop[$key][2] ?? null).'</td>';
						}
						$html .= '</tr>';
					}
				$html .= '</table>';
			} else {
				$html .= '<div style="display:flex;height: 30px;padding:0px">
					<div style="width: 550px;text-align: left;font-size: 18px;">
						<div>' . $dataArr["LABEL"] . '</div>
					</div>
					<div style="width: 150px;text-align: right;font-size: 18px;margin-left: 550px;">
					<div>' . ($dataArr["AMOUNT"] ?? null) . '</div>
					</div>
					</div>';
			}
		}
		$html .= '</div>';
		// Footer
		$html .= '<div style="display:flex;width: 100%;height: 40px" class="sub-table">
		<div style="border-top: 0.5px solid #CFCFCF;border-bottom: 0.5px solid #CFCFCF;">&nbsp;</div>
		<div style="width: 300px;text-align:center;height: 30px;font-size: 18px;padding-top: 0px;font-weight:bold;  ">(-'.$lib->baht_text($header["sumall_pay"]).'-)</div>
		<div style="width: 110px;height: 30px;margin-left: 313px;padding-top: 0px;">&nbsp;</div>
		<div style="width: 110px;text-align: center;font-size: 18px;padding-top: 0px;height:30px;margin-left: 430px;font-weight:bold; ">
		รวม
		</div>
		<div style="width: 150px;text-align: right;height: 30px;margin-left: 544px;padding-top: 0px;font-size: 18px;font-weight:bold;">' . number_format($header["sumall_pay"], 2) . '</div>
		</div>
		</div>
		<div>
		<div style="display:inline;font-weight:bold;padding: 20px;">ลงชื่อ</div>
		<div style="display:inline;font-weight:bold;"><img src="../../resource/utility_icon/signature/fin.jpg" width="100" height="50" style="margin-top:10px;margin-left: 30px"/></div>
		<div style="display:inline;font-weight:bold;padding: 20px;margin-left: 30px;">เหรัญญิก</div>
		<div style="display:inline;font-weight:bold;padding: 20px;margin-left: 40px; ">ลงชื่อ</div>
		<div style="display:inline;font-weight:bold;padding: 20px;"><img src="../../resource/utility_icon/signature/staff.jpg" width="100" height="50" style="margin-top:10px;margin-left: 50px"/></div>
		<div style="display:inline;font-weight:bold;padding: 20px;margin-left: 20px;">เจ้าหน้าที่</div>
		</div>
		';

	$dompdf = new Dompdf([
		'fontDir' => realpath('../../resource/fonts'),
		'chroot' => realpath('/'),
		'isRemoteEnabled' => true
	]);

	//$dompdf->set_paper('A4', 'landscape');
	$dompdf->load_html($html);
	$dompdf->render();
	$pathfile = __DIR__.'/../../resource/pdf/cremation';
	if(!file_exists($pathfile)){
		mkdir($pathfile, 0777, true);
	}
	$pathfile = $pathfile.'/'.$header["member_no"].$header["receipt_no"].'.pdf';
	$pathfile_show = '/resource/pdf/cremation/'.urlencode($header["member_no"]).$header["receipt_no"].'.pdf?v='.time();
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
