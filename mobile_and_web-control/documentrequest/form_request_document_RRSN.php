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
				html {
					margin: 0px;
				}
				body {
					padding: 0px 0px 10px 0px;
					font-size: 12pt;
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
				table tr td {
					border-collapse: collapse;
				}
			</style>
			<div>
				 <div style="position: absolute; left: 100px;">
					<div style="text-align: left;"><img src="../../resource/logo/logo.png" style="margin: 10px 0 0 5px" alt=""
							width="80" height="80" /></div>
				</div>
				<div style="text-align: center; font-weight: bold;margin-top: 30px;">
					แบบฟอร์มการลาออกจากสหกรณ์ออมทรัพย์พนักงานสยามคูโบต้า จำกัด
				</div>
				<div style="padding-top: 24px;padding-left: 65%;">
					<div style="padding-left: 20px;">
						วันที่<span class="input-zone" style="margin-left: 20px;"><span class="input-value"
								style="padding-left: 10px;">'.$lib->convertdate(date("Y-m-d"),"D m Y").'
							</span>………………………………............</span>
					</div>
				</div>
				<div style="font-weight: bold;text-decoration: underline;padding-left: 12px;padding-bottom: 2px;">
					ส่วนที่ 1 สำหรับสมาชิก
				</div>
				<div style="border: solid 1px black;padding: 4px 12px;">
					<div>
						เรื่อง&emsp;ขอลาออกจากสมาชิกสหกรณ์ออมทรัพย์พนักงานสยามคูโบต้า จำกัด
					</div>
					<div>
						เรียน&emsp;คณะกรรมการดำเนินการสหกรณ์ออมทรัพย์พนักงานสยามคูโบต้า จำกัด
					</div>
					<div style="padding-left: 65px;">
						ข้าพเจ้า ชื่อ <span class="input-zone"><span class="input-value">'.$dataReport["MEMBER_NAME"].'
							</span>………………………….......................................</span> นามสกุล <span class="input-zone"><span class="input-value">'.$dataReport["MEMBER_SURNAME"].'
							</span>....................................................</span> เลขประจำตัวพนักงาน <span class="input-zone"><span
								class="input-value">'.$dataReport["EMP_NO"].'
							</span>.................................</span>
					</div>
					<div>
						เลขสมาชิก <span class="input-zone"><span class="input-value">'.$dataReport["MEMBER_NO"].'
							</span>………………………….............</span> โทรศัพท์มือถือ <span class="input-zone"><span
								class="input-value">'.$dataReport["PHONE_NUMBER"].'
							</span>....................................</span> มีความประสงค์ที่จะขอลาออกจากการเป็นสมาชิกสหกรณ์ฯ
					</div>
					<div>
						เนื่องจาก <span class="input-zone"><span class="input-value">'.$dataReport["REASON"].'
							</span>………………………….................................................................</span> วันที่มีผล <span class="input-zone"><span class="input-value">'.$dataReport["EFFECT_DATE"].'
							</span>.......................................</span>
					</div>
					<div>
						โดยข้าพเจ้ามีความประสงค์ที่จะให้สหกรณ์ฯ
					</div>
					<div style="padding-left: 75px;">
						<div
							style="display: inline-block;width: 0.8em; height: 0.8em; border: 1px solid #000000;position: relative;">
							'.($dataReport["RESIGN_OPTION"] == "1" ? $checked : "").'
						</div>
						จ่ายค่าหุ้นคืนทั้งหมดหลังจากคณะกรรมการดำเนินการอนุมัติ โดยดำเนินการจ่ายพร้อมค่าจ้างประจำเดือน
					</div>
					<div style="padding-left: 75px;white-space: nowrap;">
						<div
							style="display: inline-block;width: 0.8em; height: 0.8em; border: 1px solid #000000;position: relative;">
							'.($dataReport["RESIGN_OPTION"] == "2" ? $checked : "").'
						</div>
						จ่ายค่าหุ้น,เงินปันผลและเงินเฉลี่ยคืน ภายหลังการจัดสรรกำไรสุทธิประจำปี โดยดำเนินการจ่ายคืน
						พร้อมค่าจ้างประจำเดือน ม.ค. (เฉพาะทุนเรือนหุ้น)
					</div>
					<div style="padding-left: 75px;">
						<div
							style="display: inline-block;width: 0.8em; height: 0.8em; border: 1px solid #000000;position: relative;">
							'.($dataReport["RESIGN_OPTION"] == "3" ? $checked : "").'
						</div>
						คงหุ้นไว้กับสหกรณ์ฯระยะเวลาสูงสุดไม่เกิน 5 ปี (กรณีเกษียณอายุ)
					</div>
					<div style="padding-left: 95px;">
						(กรณีต้องการลาออกก่อนครบกำหนดระยะเวลา 5 ปี ให้แจ้งความจำนงกับทางสหกรณ์ฯภายในวันที่ 7 ของเดือน)
					</div>
					<div>
						จึงเรียนมาเพื่อโปรดพิจารณาให้ลาออกจากการเป็นสมาชิกสหกรณ์ฯ ตามความประสงค์ด้วย
					</div>
				</div>
				<div style="font-weight: bold;padding-left: 12px;padding-bottom: 2px;">
					<span style="text-decoration: underline;">ส่วนที่ 2 สำหรับสมาชิก</span> <span style="color: red;font-size: smaller">(กรอกให้ครบทุกช่อง
						เฉพาะช่องผู้รับเงินสำคัญที่สุดหากสมาชิกไม่กรอกเจ้าหน้าที่จะยังไม่โอนเงินค่าหุ้นคืนสมาชิกในรอบเดือนนั้นๆ)</span>
				</div>
				<div style="border: solid 1px black;padding: 0px">
					<table style="width: 100%;border-collapse: collapse;">
						<tr>
							<td style="width: 5px;white-space: nowrap;padding-right: 8px;">ใบสำคัญจ่ายเงิน ชื่อ</td>
							<td style="width: 125px;white-space: nowrap;">
							<span class="input-zone"><span class="input-value">'.$dataReport["MEMBER_NAME"].'
								</span>..........................................</span>
							</td>
							<td style="width: 125px;text-align: center;">นามสกุล</td>
							<td style="width: 125px;white-space: nowrap;">
							<span class="input-zone"><span class="input-value">'.$dataReport["MEMBER_SURNAME"].'
								</span>..........................................</span>
							</td>
							<td style="text-align: center;" colspan="2">จำนวนทุนเรือนหุ้นทั้งหมด</td>
							<td style="width: 100px;">
							<span class="input-zone"><span class="input-value">'.number_format($dataReport["SHARE_STK"],2).'
								</span>........................................</span>
							</td>
							<td style="width: 50px;text-align: center;">บาท</td>
						</tr>
						<tr>
							<td style="text-align: right;white-space: nowrap;padding-right: 8px;">เงินสามัญคงเหลือ</td>
							<td>
							<span class="input-zone"><span class="input-value">'.number_format($dataReport["LOAN_GROUP_BAL_2"],2).'
								</span>..........................................</span>
							</td>
							<td style="width: 125px;text-align: center;">ดอกเบี้ย</td>
							<td style="width: 125px;white-space: nowrap;">
							<span class="input-zone"><span class="input-value">'.number_format($dataReport["LOAN_GROUP_INT_2"],2).'
								</span>..........................................</span>
							</td>
							<td style="width: 50px;text-align: center;">รวมยอด</td>
							<td colspan="2">
							<span class="input-zone"><span class="input-value">'.number_format(($dataReport["LOAN_GROUP_BAL_2"] + $dataReport["LOAN_GROUP_INT_2"]),2).'
								</span>..........................................</span>
							</td>
							<td></td>
						</tr>
						<tr>
							<td style="text-align: right;white-space: nowrap;padding-right: 8px;">เงินฉุกเฉินคงเหลือ</td>
							<td style="width: 125px;white-space: nowrap;">
							<span class="input-zone"><span class="input-value">'.number_format($dataReport["LOAN_GROUP_BAL_1"],2).'
								</span>..........................................</span>
							</td>
							<td style="width: 125px;text-align: center;">ดอกเบี้ย</td>
							<td style="width: 125px;white-space: nowrap;">
							<span class="input-zone"><span class="input-value">'.number_format($dataReport["LOAN_GROUP_INT_1"],2).'
								</span>..........................................</span>
							</td>
							<td style="width: 50px;text-align: center;">รวมยอด</td>
							<td colspan="2">
							<span class="input-zone"><span class="input-value">'.number_format(($dataReport["LOAN_GROUP_BAL_1"] + $dataReport["LOAN_GROUP_INT_1"]),2).'
								</span>..........................................</span>
							</td>
							<td></td>
						</tr>
						<tr>
							<td style="text-align: right;" colspan="5">รวมทั้งหมด</td>
							<td colspan="2">
							<span class="input-zone"><span class="input-value">'.number_format(($dataReport["LOAN_GROUP_BAL_2"] + $dataReport["LOAN_GROUP_INT_2"] + $dataReport["LOAN_GROUP_BAL_1"] + $dataReport["LOAN_GROUP_INT_1"]),2).'
								</span>..........................................</span>
							</td>
							<td></td>
						</tr>
						<tr>
							<td colspan="5" style="text-align: right; padding-right: 60px;">จำนวนยอดที่ได้รับ</td>
							<td colspan="2">
							<span class="input-zone"><span class="input-value">'.number_format(($dataReport["RECEIVE_NET"]),2).'
								</span>..........................................</span>
							</td>
							<td></td>
						</tr>
						<tr>
							<td style="padding-left: 12px;">บัญชีธนาคาร</td>
							<td colspan="2">
							<span class="input-zone"><span class="input-value">'.($dataReport["RECEIVE_BANK"]).'
								</span>..........................................</span>
								</td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td style="padding-left: 12px;">โอนเข้าเลขที่บัญชี</td>
							<td colspan="2">
							<span class="input-zone"><span class="input-value">'.($dataReport["RECEIVE_ACC"]).'
								</span>..........................................</span>
								</td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td colspan="8" style="font-size: 1.1em;color: red;font-weight: bold;padding-left: 12px;">
								สมาชิกจะได้รับเงินคืนในวันเดียวกันกับค่าจ้างประจำเดือนแต่เงินจะเข้าภายใน 17.00
								น.และไม่มียอดแจ้งในสลิปเงินเดือน</td>
						</tr>
					</table>
				</div>
				<div style="font-weight: bold;text-decoration: underline;padding-left: 12px;padding-bottom: 2px;">
					ส่วนที่ 3 สำหรับเจ้าหน้าที่
				</div>
				<div style="border: solid 1px black;padding: 0px 12px;">
					<div style="font-weight: bold;text-decoration: underline;">
						บันทึกสำหรับเจ้าหน้าที่สหกรณ์
					</div>
					<div style="padding-left: 30px;">
						1. มีเงินหุ้นคงเหลือในสหกรณ์ฯเมื่อวันที่ <span class="input-zone"><span class="input-value">
							</span>....…..</span>/<span class="input-zone"><span class="input-value">
							</span>….....….</span>/<span class="input-zone"><span class="input-value">
							</span>……..</span>เป็นจำนวนเงิน<span class="input-zone"><span class="input-value">
							</span>…………….....……….</span>บาท
					</div>
					<div style="padding-left: 30px;">
						2. มีหนี้ต่อสหกรณ์ฯในฐานะผู้กู้คงเหลือเมื่อวันที่ <span class="input-zone"><span class="input-value">
							</span>....…..</span>/<span class="input-zone"><span class="input-value">
							</span>….....….</span>/<span class="input-zone"><span class="input-value">
							</span>……..</span>
					</div>
					<div style="padding-left: 50px;">
						- เงินกู้ฉุกเฉิน (เงินต้น<span class="input-zone"><span class="input-value">'.number_format($dataReport["LOAN_GROUP_BAL_1"],2).'
							</span>.................................</span>บาท ดอกเบี้ย<span class="input-zone"><span
								class="input-value">'.number_format($dataReport["LOAN_GROUP_INT_1"],2).'
							</span>...............................</span>บาท) รวมยอด<span class="input-zone"><span
								class="input-value">'.number_format(($dataReport["LOAN_GROUP_BAL_1"] + $dataReport["LOAN_GROUP_INT_1"]),2).'
							</span>................................</span>บาท
					</div>
					<div style="padding-left: 50px;">
						- เงินกู้สามัญ (เงินต้น<span class="input-zone"><span class="input-value">'.number_format($dataReport["LOAN_GROUP_BAL_2"],2).'
							</span>.................................</span>บาท ดอกเบี้ย<span class="input-zone"><span
								class="input-value">'.number_format($dataReport["LOAN_GROUP_INT_2"],2).'
							</span>...............................</span>บาท) รวมยอด<span class="input-zone"><span
								class="input-value">'.number_format(($dataReport["LOAN_GROUP_BAL_2"] + $dataReport["LOAN_GROUP_INT_2"]),2).'
							</span>................................</span>บาท หนี้รวม<span class="input-zone"><span
								class="input-value">'.number_format(($dataReport["LOAN_GROUP_BAL_2"] + $dataReport["LOAN_GROUP_INT_2"] + $dataReport["LOAN_GROUP_BAL_1"] + $dataReport["LOAN_GROUP_INT_1"]),2).'
							</span>.................................</span>บาท
					</div>
					<div style="padding-left: 30px;">
						3. สรุปยอดเงินที่
					</div>
					<table style="width: 80%;margin-left: auto;margin-right: auto;">
						<tr>
							<td>
								<div
									style="display: inline-block;width: 0.8em; height: 0.8em; border: 1px solid #000000;position: relative;">
									'.($dataReport["RECEIVE_NET"] > 0 ? $checked : "").'
								</div>
								จ่ายคืนสมาชิก จำนวน<span class="input-zone"><span class="input-value">'.($dataReport["RECEIVE_NET"] > 0 ? number_format(abs($dataReport["RECEIVE_NET"]),2) : "").'
									</span>.................................</span>บาท
							</td>
							<td>
								<div
									style="display: inline-block;width: 0.8em; height: 0.8em; border: 1px solid #000000;position: relative;">
									'.($dataReport["RECEIVE_NET"] < 0 ? $checked : "").'
								</div>
								เรียกเก็บเงินจากสมาชิก จำนวน<span class="input-zone"><span class="input-value">'.($dataReport["RECEIVE_NET"] < 0 ? number_format(abs($dataReport["RECEIVE_NET"]),2) : "").'
									</span>.................................</span>บาท
							</td>
						</tr>
					</table>
					<table style="width: 100%;">
						<tr>
							<td style="width: 66%;">
							</td>
							<td style="text-align: center;">
								<div>
									(…………………………………………………….)
								</div>
								<div>
									เจ้าหน้าที่สหกรณ์
								</div>
							</td>
						</tr>
					</table>
					<div style="margin-bottom: 2px;">
						<span style="text-decoration: underline;">หมายเหตุ</span> ที่ประชุมคณะกรรมการดำเนินการ  วันที่<span class="input-zone"><span class="input-value">
							</span>.................................</span> อนุมัติให้ลาออกจากการเป็นสมาชิกตามความประสงค์
					</div>
				</div>
				<div style="color: red;font-weight: bold;padding-left: 12px;">
					<span style="text-decoration: underline;margin-right: 20px;">หมายเหตุ</span>    ส่งเอกสารถึงเจ้าหน้าที่สหกรณ์  ภายในวันที่ 7 ของเดือน 
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