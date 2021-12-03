<?php
use Dompdf\Dompdf;

$dompdf = new DOMPDF();

function GenerateReport($dataReport,$lib){
	$sumBalance = 0;
	$checked = '<img src="../../resource/utility_icon/check-icon.png" width="12px" height="12px" style="position: absolute;top: -2px;left: 2px"/>';
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
				  font-size: 15pt;
				}
				.sub-table div{
					padding : 5px;
				}
				.input-zone{
					position: relative;
				}
				.input-value{
					position: absolute;
					white-space: nowrap;
					left : 5px;
					top: -2px;
					font-weight: bold;
				}
				table {		
					table-layout: fixed;
					width: 100%;
				}
				tr td:nth-child(1),
				tr td:nth-child(2) {
				  width: 45%;
				  padding-right: 5px;
				}
				tr td:nth-child(3) {
				  width: 10%;
				} 
			</style>
			<div style="max-width: 80%;position: relative;">
			    <div style="position: absolute; left: 10px;">
				<div style="text-align: left;"><img src="../../resource/logo/logo.png" style="margin: 10px 0 0 5px" alt=""
					width="80" height="80" /></div>
			    </div>
			    <div style="text-align: left; margin-top: 20px;padding-left: 110px;">
				<div style="white-space: nowrap;">
				         สหกรณ์ออมทรัพย์พนักงานสยามคูโบต้า จำกัด
				 </div>
				<div style="white-space: nowrap;font-size: 17pt;transform: translateY(-7px);">
				    SIAM KUBOTA EMPLOYEE SAVING AND CREDIT COOPERATIVE, LIMITED
				 </div>
			    </div>
				 <div style="border-bottom: 2px solid #29c79b;width: 100%;margin-left: 110px;width: 580px;">
				 </div>
			    <div style="text-align: center; margin-top: 48px;padding-left: 150px;,padding-top: 24px;">
				'.$lib->convertdate(date("Y-m-d"),"D m Y").'
			    </div>
			    <div style="text-align: center; margin-top: 24px;white-space: nowrap;padding-left: 130px;">
				หนังสือฉบับนี้ สหกรณ์ออมทรัพย์พนักงานสยามคูโบต้า จำกัด ขอรับรองข้อมูลสมาชิก ดังต่อไปนี้
			    </div>
			    <div style="padding-top: 30px;padding-right: 135px;padding-left: 54px">
				<table style="max-width: 280px;border-collapse: collapse;">
				    <tr>
					<td style="white-space: nowrap;">ชื่อ – สกุล</td>
					<td style="text-align: right;white-space: nowrap;">'.$dataReport["MEMBER_FULLNAME"].'</td>
					<td style="text-align: left;max-width: 5px;white-space: nowrap;over"></td>
				    </tr>
				    <tr>
					<td style="white-space: nowrap;">รหัสพนักงาน</td>
					<td style="text-align: right;white-space: nowrap;">'.$dataReport["EMP_NO"].'</td>
					<td style="text-align: left;max-width: 5px;white-space: nowrap;"></td>
				    </tr>
				    <tr>
					<td style="white-space: nowrap;">เลขที่สมาชิก</td>
					<td style="text-align: right;white-space: nowrap;">'.$dataReport["MEMBER_NO"].'</td>
					<td style="text-align: left;max-width: 5px;white-space: nowrap;"></td>
				    </tr>
				    <tr>
					<td style="white-space: nowrap;">วันที่เริ่มเป็นสมาชิก</td>
					<td style="text-align: right;white-space: nowrap;">'.$lib->convertdate($dataReport["MEMBER_DATE"],"d/n/Y").'</td>
					<td style="text-align: left;width: 5px;white-space: nowrap;"></td>
				    </tr>
				    <tr>
					<td style="white-space: nowrap;">สังกัด/บริษัท</td>
					<td style="text-align: right;white-space: nowrap;">'.$dataReport["MEMBGROUP"].'</td>
					<td style="text-align: left;max-width: 5px;white-space: nowrap;"></td>
				    </tr>
				    <tr>
					<td style="white-space: nowrap;">ทุนเรือนหุ้นรายเดือน</td>
					<td style="text-align: right;white-space: nowrap;">'.$dataReport["PERIOD_SHARE_AMT"].'</td>
					<td style="text-align: left;max-width: 5px;white-space: nowrap;">บาท</td>
				    </tr>
				    <tr>
					<td style="white-space: nowrap;">ทุนเรือนหุ้นสะสม</td>
					<td style="text-align: right;white-space: nowrap;">'.$dataReport["SHARE_STK"].'</td>
					<td style="text-align: left;max-width: 5px;white-space: nowrap;">บาท</td>
				    </tr>
				    <tr>
					<td style="white-space: nowrap;">เงินงวดฉุกเฉิน</td>
					<td style="text-align: right;white-space: nowrap;">'.$dataReport["PERIOD_PAYMENT_01"].'</td>
					<td style="text-align: left;max-width: 5px;white-space: nowrap;">บาท</td>
				    </tr>
				    <tr>
					<td style="white-space: nowrap;">เงินกู้ฉุกเฉินคงเหลือ</td>
					<td style="text-align: right;white-space: nowrap;">'.$dataReport["LOAN_BALANCE_01"].'</td>
					<td style="text-align: left;max-width: 5px;white-space: nowrap;">บาท</td>
				    </tr>
				    <tr>
					<td style="white-space: nowrap;">เงินงวดสามัญ</td>
					<td style="text-align: right;white-space: nowrap;">'.$dataReport["PERIOD_PAYMENT_02"].'</td>
					<td style="text-align: left;max-width: 5px;white-space: nowrap;">บาท</td>
				    </tr>
				    <tr>
					<td style="white-space: nowrap;">เงินกู้สามัญคงเหลือ</td>
					<td style="text-align: right;white-space: nowrap;">'.$dataReport["LOAN_BALANCE_02"].'</td>
					<td style="text-align: left;max-width: 5px;white-space: nowrap;">บาท</td>
				    </tr>
				    <tr>
					<td colspan="3" style="white-space: nowrap;text-align: center;padding-top: 46px;padding-left: 35px;">หมายเหตุ  การกู้สามัญทั่วไปไม่ใช่เป็นการกู้เพื่อสินเชื่ออสังหาริมทรัพย์ใดๆ</td>
				    </tr>
				    <tr>
					<td colspan="3" style="white-space: nowrap;text-align: center;padding-left: 35px;">ได้ออกหนังสือรับรองฉบับนี้ให้ไว้เพื่อเป็นหลักฐาน</td>
				    </tr>
				</table>
			    </div>
				<div style="text-align: right;padding-top: 46px;padding-right: 54px">
				    <div>
						<br />
				    </div>
				    <div style="margin-right: 34px;">
					ผู้จัดการ
				    </div>
				</div>
				<div style="text-align: center;padding-top: 46px;white-space: nowrap;position: absolute;bottom: -12px;left: 40px">
					 <div style="border-bottom: 2px solid #29c79b;width: 700px;margin-left: -50px;">
					 </div>
				    <div>
					สำนักงาน :  101/19-24 หมู่ที่ 20 ถนนนวนคร 10 นวนคร  ต.คลองหนึ่ง  อ.คลองหลวง  จ.ปทุมธานี  12120
				    </div>
				    <div style="text-align: center;font-size: smaller;white-space: nowrap;">
					Office : 101/19-24 Moo 20, Navanakorn Industrial Estate, Tambon Khlongnueng, Amphur Khlongluang, Pathumthani 12120
				    </div>
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
	$pathfile = __DIR__.'/../../resource/pdf/req_document';
	if(!file_exists($pathfile)){
		mkdir($pathfile, 0777, true);
	}
	$pathfile = $pathfile.'/'.$dataReport["REQDOC_NO"].'.pdf';
	$pathfile_show = '/resource/pdf/req_document/'.$dataReport["REQDOC_NO"].'.pdf?v='.time();
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