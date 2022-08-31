<?php
require_once('../autoload.php');

use Dompdf\Dompdf;

$dompdf = new DOMPDF();

if($lib->checkCompleteArgument(['menu_component','slip_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'PayInSlip')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$header = array();
		
		$getPaymentDetail = $conoracle->prepare("
			select
				(to_number(SUBSTR(wcrecievemonth.recv_period,1,4))+1) as recv_year,
				wcdeptmaster.wfaccount_name,
				wcdeptmaster.card_person,
				wcdeptmaster.deptaccount_no,
				wcrecievemonth.fee_year,
				wcrecievemonth.fee_year+20 as total_amt,
				ftreadtbath( wcrecievemonth.fee_year+20) as total_thaibath,
				trim(wcdeptmaster.coop_id)||'/'||trim(wcdeptmaster.deptaccount_no) as ref2
			from wcrecievemonth
				left join wcdeptmaster  on wcrecievemonth.wfmember_no  = wcdeptmaster.deptaccount_no
			where
				trim(wcrecievemonth.wfmember_no) = :member_no
				and wcrecievemonth.wcitemtype_code = 'FEE'
				and wcrecievemonth.coop_id in (select coop_id from cmucfcoopbranch where  coop_id not in ('091506') and from_cs = '09' )
				and wcrecievemonth.status_post = 8
				and wcdeptmaster.deptclose_status = 0
		");
		$getPaymentDetail->execute([
			':member_no' => $member_no
		]);
		
		$arrGroupDetail = array();
		while($rowDetail = $getPaymentDetail->fetch(PDO::FETCH_ASSOC)){

			$header["member_no"] = $member_no;
			$header["deptaccount_no"] = TRIM($rowDetail["DEPTACCOUNT_NO"]);
			$header["card_person"] = TRIM($rowDetail["CARD_PERSON"]);
			$header["fundaccount_no"] = '000000';
			$header["recv_year"] = TRIM($rowDetail["RECV_YEAR"]);
			$header["name"] = $rowDetail["WFACCOUNT_NAME"];
			$header["ref2"] = $rowDetail["REF2"] ?? "";
			$header["total_amt"] = number_format($rowDetail["TOTAL_AMT"] ?? "",2);
			$header["total_desc"] = $rowDetail["TOTAL_THAIBATH"] ?? "";
		}

		$arrayPDF = GenerateReport($arrGroupDetail,$header,$lib);
		if($arrayPDF["RESULT"]){
			if ($forceNewSecurity == true) {
				$arrayResult['REPORT_URL'] = $config["URL_SERVICE"]."/resource/get_resource?id=".hash("sha256", $arrayPDF["PATH"]);
				$arrayResult["REPORT_URL_TOKEN"] = $lib->generate_token_access_resource($arrayPDF["PATH"], $jwt_token, $config["SECRET_KEY_JWT"]);
			} else {
				$arrayResult['REPORT_URL'] = $config["URL_SERVICE"].$arrayPDF["PATH"];
			}
			$arrayResult['ALLOW_SHARE'] = TRUE;
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

function GenerateReport($dataReport,$header,$lib){
	$html = '
	<!DOCTYPE html>
	<html lang="en">
	<head>
		<meta charset="UTF-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Document</title>
	</head>
	<body>
		<meta charset="UTF-8">
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
				  font-size: 11pt;
				  line-height: 23px;
				}
				div{
					line-height: 20px;
					font-size: 11pt;
				}
				.nowrap{
					white-space: nowrap;
				  }
				.center{
					text-align:center;
				}
				.left{
					text-align:left;
				}
				.right{
					text-align:right;
				}
				.flex{
					display:flex;
				}
				.bold{
					font-weight:bold;
				}
				.list{
					padding-left:50px;
				}
				.sub-list{
					padding-left:100px;
				}
				th{
					text-align:center;
					padding-bottom:5px;
				}
				td{
					line-height:15px;
					padding:5px;
				}
				.absolute{
					position:absolute;
				}
				.data{
					font-size:15pt;
					margin-top:-2px;
				}
				.border{
					border: 1px solid;
					border-radius:3px;
				}
				.border-left{
					border-left: 1px solid;
				}
				.border-right{
					border-right: 1px solid;
				}
				.border-top{
					border-top: 1px solid;
				}
				.border-bottom
					border-bottom: 1px solid;
				}
				.wrapper-page {
					page-break-after: always;
				  }
				  .spac{
					margin-top:15px;
				  }
				  .tab{
					padding-left:15px;
				  }
				  
				.wrapper-page:last-child {
					page-break-after: avoid;
				}
				.inline{
					display:inline;
				}
				.border-collapse{
					border-collapse: collapse;
				}
				width-full{
					width:100%;
				}
				.header{
					border:1px solid;
					font-weight:bold;
					width:380px;
					padding:2px 10px;	
					background-color:#dddddd;
					border-radius:15px;
					text-align:center;
					font-size:14pt;
				}
				.color-red{
					color:red;
				}
				.detail-line-height{
					line-height:18px;
				}
				.bg-color{
					background-color:#dddddd;
				}
				
				</style>';

	$html .= '<div  style="margin:-20px -25px -25px -25px">';
	//หน้า 1
	$html .= '<div class="wrapper-page">';
	$html .='
		<div class="bold right" style="font-size:17pt">สำหรับลูกค้า</div>
		<div class="right">&nbsp;&nbsp;&nbsp;</div>
		<div style="margin-top:-20px">
			<div class="header">	
				ใบนำฝากชำระเงินค่าสินค้าหรือบริการ (Bill Payment Pay-In Slip)
			</div>
			<div class="bold absolute" style="right:0px; top:10px; font-size:14pt; " >
				โปรดเรียกเก็บค่าธรรมเนียมจากผู้ชำระเงิน*
			</div>
		</div>
		<div class="border" style="margin-top:0px;">
			<div class="absolute" ><img src="../../resource/logo/logo.jpg" alt="" width="50" style="margin-left:5px; margin-top:10px;"></div>
			<div style="margin-left:450px; position:absolute; border:1px solid; width:270px;  margin-top:40px; border-radius:15px; padding:5px 10px; height:65px;">
				<div class="detail-line-height">ชื่อ/Name '.($header["name"]??null).'</div>
				<div class="detail-line-height">รหัสลูกค้า/เลขบัตรประชาชน(Ref.1) '.($header["card_person"]??null).'</div>
				<div class="detail-line-height">หมายเลขอ้างอิง/Reference No (Ref.2) '.($header["ref2"]??null).'</div>
			</div>        
			<div style="padding-right:10px;" class="right">ธนาคาร.........................&nbsp; สาขา/Brance.........................&nbsp;  วันที่/Date.....................</div>
			<div style="margin-left:60px;">
				<div class="bold" style="margin-top:-10px">สมาคมฌาปนกิจสงเคราะห์สมาชิกสหกรณ์ออมทรัพย์สาธารณสุขไทย</div>
				<div>ที่อยู่ 199/8 อาคารสวัสดิการฌาปนกิจสงเคราะห์สหกรณ์ออมทรัพย์สาธารณสุขไทย (สฌ.สอ.)</div>
				<div>ชั้น 3 หมูที่ 2 ถนนนครอินทร์ ต.บางสีทอง อ.บางกรวย จ.นนทบุรี 11130</div>
				<div>โทรศัพท์ 0 2496 1340 Fax. 0 2496 1342</div>
			</div>
			<div style="height:60px;">
				<div class="absolute">
					<img src="../../resource/logo/logo_kungthai.jpg" alt="" width="25" style="margin-left:10px; margin-top:45px;">
				</div>
				<div style="margin-left:60px;">
					<div style="font-size:14pt; margin-top:20px;">เพื่อนำเข้าบัญชี <b>สมาคมฌาปนกิจสงเคราะห์สมาชิกสหกรณ์ออมทรัพย์สาธารณสุขไทย</b></div>
					<div style="margin-left:20px;">
						<div class="absolute" style="margin-left: -16px; margin-top: 5px; width: 10px; height: 10px; border-color: #000; border-style: solid;"></div>
						<div class="absolute">บมจ.ธนาคารกรุงไทย(รหัสหน่วยงาน/Company Code:92777) ค่าธรรมเนียม 10 บาททั่วประเทศ</div>
					</div>
				</div>
			</div>
			<div style="height:50px; margin-top:0px;">
				<div class="absolute">
					<img src="../../resource/logo/counterservice_7-11.jpg" alt="" height="30" style="margin-left:8px; margin-top:10px;">
				</div>
				<div style="margin-left:80px; margin-top:10px;">
					<div class="absolute" style="margin-left: -16px; margin-top: 5px; width: 10px; height: 10px; border-color: #000; border-style: solid;"></div>
					<div class="absolute">เคาน์เตอร์เซอร์วิส(ร้านเซเว่นอีเอฟเว่น)(Service 01)(ค่าธรรมเนียม 10 บาท ทั่วประเทศ)</div>
				</div>
				<div class="absolute">
					<img src="../../resource/logo/Barcode.jpg" alt="" height="45px" style="margin-left:470px; margin-top:-20px;">
				</div>
				<div class="abolute">
					<img src="../../resource/logo/QRCODE.jpg" alt="" width="80px" style="margin-left:650px; margin-top:-20px;">
				</div>
			</div>
			<div style="height:40px;">
				<div class="absolute">
					<img src="../../resource/logo/visa.jpg" alt="" width="25px" style="margin-left:13px; margin-top:-10px;">
				</div>
				<div style="margin-left:80px; margin-top:0px;">
					<div class="absolute" style="margin-left: -16px; margin-top: -5px; width: 10px; height: 10px; border-color: #000; border-style: solid;"></div>
					<div class="absolute" style="margin-top:-10px;">บัตรเครดิต (visa/master) คิดค่าบริการอัตราร้อยละ 1.25% จำนวนเงินที่ชำระ(ไม่รวมค่าธรรมเนียมช่องทาง)</div>
				</div>
			</div>
			<div style="padding:10px; margin-top:-10px">
				<table style="border-collapse: collapse; width:100%">
					<tbody>
						<tr>
							<th>รับชำระด้วยเงินสดเท่านั้น</th>
							<th class="border bg-color">จำนวนเงิน / Amount</th>
							<td class="border center">'.($header["total_amt"]??null).'</td>
							<th class="border bg-color" style="width:60px;">บาท / Baht</th>
						</tr>
						<tr>
							<th class="border bg-color">จำนวนเงินเป็นตัวอักษร / Amount in Word</th>
							<td colspan="3" class="border center">'.($header["total_desc"]??null).'</td>
						</tr>
					</tbody>
				</table>
				<div style="margin-top:10px;">
					<div style="margin-left:530px;">
						<table style="border-collapse: collapse; width:200px;">
							<tr>
								<th class="border bg-color">สำหรับเจ้าหน้าที่ธนาคาร</th>
							</tr>
							<tr>
								<td class="border" style="padding-top:25px;">ผู้รับเงิน...............................................</td>
							</tr>
						</table>
					</div>
					<div style="margin-top:-50px;">
						<div>ชื่อผู้นำฝาก / Deposit by...................................................................</div>
						<div>โทรศัพท์ / Telephone........................................................................</div>
					</div>
				</div>
			</div>
		</div>
		<div class="center">
			โปรดนำใบนำฝากนี้ไปชำระเงินได้ที่ บมจ.ธนาคารกรุงไทย/เคาน์เตอร์เซอร์วิส/7ELEVEN ทุกสาขาทั่วประเทศ
		</div>
			
		<div style="margin-top:30px; margin-bottom:30px;">
			<div class="absolute"><img src="../../resource/utility_icon/icon-cut.png" alt="" width="40px" style="margin-left:0px; margin-top:12px;"></div>
			<div style="border-bottom:1px dashed ;width:100%; ">&nbsp; </div>
		</div>
	';

	//สำหรับธนาคาร
	$html .='
	<div>
		<div class="bold right" style="font-size:17pt">สำหรับธนาคาร</div>
		<div style="margin-top:0px">
			<div class="bold absolute" style="right:0px; font-size:14pt; " >
				โปรดเรียกเก็บค่าธรรมเนียมจากผู้ชำระเงิน*
			</div>
			<div class="header">	
				ใบนำฝากชำระเงินค่าสินค้าหรือบริการ (Bill Payment Pay-In Slip)
			</div>
		</div>
		<div class="border" style="margin-top:0px;">
			<div class="absolute" ><img src="../../resource/logo/logo.jpg" alt="" width="50" style="margin-left:5px; margin-top:10px;"></div>
			<div style="margin-left:450px; position:absolute; border:1px solid; width:270px;  margin-top:40px;; border-radius:15px; padding:5px 10px; height:65px;">
				<div class="detail-line-height">ชื่อ/Name '.($header["name"]??null).'</div>
				<div class="detail-line-height">รหัสลูกค้า/เลขบัตรประชาชน(Ref.1) '.($header["card_person"]??null).'</div>
				<div class="detail-line-height">หมายเลขอ้างอิง/Reference No (Ref.2) '.($header["ref2"]??null).'</div>
			</div>        
			<div style="padding-right:10px;" class="right">ธนาคาร.........................&nbsp; สาขา/Brance.........................&nbsp;  วันที่/Date.....................</div>
			<div style="margin-left:60px;">
				<div class="bold" style="margin-top:-10px">สมาคมฌาปนกิจสงเคราะห์สมาชิกสหกรณ์ออมทรัพย์สาธารณสุขไทย</div>
				<div>ที่อยู่ 199/8 อาคารสวัสดิการฌาปนกิจสงเคราะห์สหกรณ์ออมทรัพย์สาธารณสุขไทย (สฌ.สอ.)</div>
				<div>ชั้น 3 หมูที่ 2 ถนนนครอินทร์ ต.บางสีทอง อ.บางกรวย จ.นนทบุรี 11130</div>
				<div>โทรศัพท์ 0 2496 1340 Fax. 0 2496 1342</div>
			</div>
			<div style="height:60px;">
				<div class="absolute">
					<img src="../../resource/logo/logo_kungthai.jpg" alt="" width="25" style="margin-left:10px; margin-top:45px;">
				</div>
				<div style="margin-left:60px;">
					<div style="font-size:14pt; margin-top:20px;">เพื่อนำเข้าบัญชี <b>สมาคมฌาปนกิจสงเคราะห์สมาชิกสหกรณ์ออมทรัพย์สาธารณสุขไทย</b></div>
					<div style="margin-left:20px;">
						<div class="absolute" style="margin-left: -16px; margin-top: 5px; width: 10px; height: 10px; border-color: #000; border-style: solid;"></div>
						<div class="absolute">บมจ.ธนาคารกรุงไทย(รหัสหน่วยงาน/Company Code:92777) ค่าธรรมเนียม 10 บาททั่วประเทศ</div>
					</div>
				</div>
			</div>
			<div style="height:50px; margin-top:0px;">
				<div class="absolute">
					<img src="../../resource/logo/counterservice_7-11.jpg" alt="" height="30" style="margin-left:8px; margin-top:10px;">
				</div>
				<div style="margin-left:80px; margin-top:10px;">
					<div class="absolute" style="margin-left: -16px; margin-top: 5px; width: 10px; height: 10px; border-color: #000; border-style: solid;"></div>
					<div class="absolute">เคาน์เตอร์เซอร์วิส(ร้านเซเว่นอีเอฟเว่น)(Service 01)(ค่าธรรมเนียม 10 บาท ทั่วประเทศ)</div>
				</div>
				<div class="absolute">
					<img src="../../resource/logo/Barcode.jpg" alt="" height="45px" style="margin-left:470px; margin-top:-20px;">
				</div>
				<div class="abolute">
					<img src="../../resource/logo/QRCODE.jpg" alt="" width="80px" style="margin-left:650px; margin-top:-20px;">
				</div>
			</div>
			<div style="height:40px;">
				<div class="absolute">
					<img src="../../resource/logo/visa.jpg" alt="" width="25px" style="margin-left:13px;margin-top:-10px;">
				</div>
				<div style="margin-left:80px; margin-top:0px;">
					<div class="absolute" style="margin-left: -16px; margin-top: -5px; width: 10px; height: 10px; border-color: #000; border-style: solid;"></div>
					<div class="absolute" style="margin-top:-10px;">บัตรเครดิต (visa/master) คิดค่าบริการอัตราร้อยละ 1.25% จำนวนเงินที่ชำระ(ไม่รวมค่าธรรมเนียมช่องทาง)</div>
				</div>
			</div>
			<div style="padding:10px; margin-top:-10px">
				<table style="border-collapse: collapse; width:100%">
					<tbody>
						<tr>
							<th>รับชำระด้วยเงินสดเท่านั้น</th>
							<th class="border bg-color">จำนวนเงิน / Amount</th>
							<td class="border center">'.($header["total_amt"]??null).'</td>
							<th class="border bg-color" style="width:60px;">บาท / Baht</th>
						</tr>
						<tr>
							<th class="border bg-color">จำนวนเงินเป็นตัวอักษร / Amount in Word</th>
							<td colspan="3" class="border center">'.($header["total_desc"]??null).'</td>
						</tr>
					</tbody>
				</table>
				<div style="margin-top:10px;">
					<div style="margin-left:530px;">
						<table style="border-collapse: collapse; width:200px;">
							<tr>
								<th class="border bg-color">สำหรับเจ้าหน้าที่ธนาคาร</th>
							</tr>
							<tr>
								<td class="border" style="padding-top:25px;">ผู้รับเงิน...............................................</td>
							</tr>
						</table>
					</div>
					<div style="margin-top:-50px;">
						<div>ชื่อผู้นำฝาก / Deposit by...................................................................</div>
						<div>โทรศัพท์ / Telephone........................................................................</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="center">
		โปรดนำใบนำฝากนี้ไปชำระเงินได้ที่ บมจ.ธนาคารกรุงไทย/เคาน์เตอร์เซอร์วิส/7ELEVEN ทุกสาขาทั่วประเทศ
	</div>
	';


	$html .= '
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
	$pathfile = __DIR__.'/../../resource/pdf/payinslip';
	if(!file_exists($pathfile)){
		mkdir($pathfile, 0777, true);
	}
	$pathfile = $pathfile.'/'.(trim($header["deptaccount_no"]) ?? '').'_'.(trim($header["fundaccount_no"])).'_'.(trim($header["recv_year"]) ?? '').'.pdf';
	$pathfile_show = '/resource/pdf/payinslip/'.(trim($header["deptaccount_no"]) ?? '').'_'.(trim($header["fundaccount_no"])).'_'.(trim($header["recv_year"]) ?? '').'.pdf?v='.time();
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