<?php
use Dompdf\Dompdf;

$dompdf = new DOMPDF();

function GenerateReport($dataReport,$lib){
	$sumBalance = 0;
	$checked = '<img src="../../resource/utility_icon/check-icon.png" width="12px" height="12px" style="position: absolute;top: -4px;left: 2px"/>';
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
					margin: 10px;
				}
				body {
					font-size: 13pt;
					padding: 4px;
				}

				.sub-table div {
					padding: 5px;
				}

				.input-zone {
					position: relative;
				}

				.input-value {
					position: absolute;
					white-space: nowrap;
					left: 5px;
					top: -2px;
					font-weight: bold;
				}
			</style>
			<div>
				<div style="position: absolute; left: 30px;">
					<div style="text-align: left;"><img src="../../resource/logo/logo.png" style="margin: 10px 0 0 5px" alt=""
							width="80" height="80" /></div>
				</div>
				<div style="text-align: center; font-weight: bold;margin-top: 30px;">
					ใบสมัครเข้าเป็นสมาชิก
				</div>
				<div style="text-align: center; font-weight: bold;">
					สหกรณ์ออมทรัพย์พนักงานสยามคูโบต้า จำกัด
				</div>
				<div style="text-align: right;padding-top: 16px;padding-right: 100px;">
					<div>
						เขียนที่ สหกรณ์ออมทรัพย์พนักงานสยามคูโบต้า จำกัด.
					</div>
					<div style="padding-right: 120px;">
					วันที่<span class="input-zone"><span class="input-value" style="padding-left: 378px;">'.$lib->convertdate(date("Y-m-d"),"D m Y").'
							</span>………………………………................…</span>
					</div>
				</div>
				<div style="padding-top: 16px;">
					เรียน คณะกรรมการดำเนินการสหกรณ์ออมทรัพย์พนักงานสยามคูโบต้า จำกัด
				</div>
				<div style="padding-left: 50px;padding-top: 16px;">
					ข้าพเจ้า ชื่อ <span class="input-zone"><span class="input-value">'.$dataReport["MEMBER_FULLNAME"].'
						</span>…………………….................……...........</span> รหัสพนักงาน <span class="input-zone"><span class="input-value">'.$dataReport["EMP_NO"].'
						</span>................</span> เลขบัตรประชาชน <span class="input-zone"><span class="input-value">'.str_replace('-','',$dataReport["MEMBER_CARDID"]).'
						</span>................................</span> โทรศัพท์มือถือ <span class="input-zone"><span class="input-value">'.$dataReport["MEMBER_MOBILEPHONE"].'
						</span>.........................</span>
				</div>
				<div style="padding-top: 6px;">
					ตั้งบ้านเรือนอยู่ ณ บ้านเลขที่ <span class="input-zone"><span class="input-value">'.$dataReport["ADDRESS_ADDR_NO"].'
						</span>…………...................</span> หมู่ที่ <span class="input-zone"><span class="input-value">'.$dataReport["ADDRESS_VILLAGE_NO"].'
						</span>……………………...........</span> ถนน <span class="input-zone"><span class="input-value">'.$dataReport["ADDRESS_ROAD"].'
						</span>……………………........................</span> ตำบล <span class="input-zone"><span class="input-value">'.$dataReport["ADDRESS_TAMBOL_CODE"].'
						</span>……………………...........</span>
				</div>
				<div style="padding-top: 6px;">
					อำเภอ <span class="input-zone"><span class="input-value">'.$dataReport["ADDRESS_DISTRICT_CODE"].'
						</span>…………................................</span> จังหวัด <span class="input-zone"><span class="input-value">'.$dataReport["ADDRESS_PROVINE_CODE"].'
						</span>……………………................</span> ได้ทราบวัตถุประสงค์ของสหกรณ์ฯ โดยตลอดแล้ว และ
				</div>
				<div style="padding-top: 6px;">
					เห็นชอบในวัตถุประสงค์ของสหกรณ์ฯ จึงขอสมัครเป็นสมาชิกของสหกรณ์ฯ ในฐานะเป็นผู้เข้าชื่อขอจดทะเบียนสหกรณ์ฯ และ
				</div>
				<div style="padding-top: 6px;">
					ขอให้ถ้อยคำเป็นหลักฐาน ดังต่อไปนี้
				</div>
				<div style="padding-left: 50px;padding-top: 6px;">
					ข้อ 1. ข้าพเจ้าเป็นพนักงานของบริษัท สยามคูโบต้าคอร์ปอเรชั่น จำกัด หรือบริษัทในเครือฯ หรือพนักงาน บริษัท
					ซิเมนต์ไทยโฮลดิ้ง จำกัด
				</div>
				<div style="padding-left: 65px;padding-top: 6px;">
					ที่ได้รับมอบหมายให้ปฏิบัติงานภายใต้สังกัดบริษัทสยามคูโบต้าคอร์ปอเรชั่นจำกัดหรือบริษัทในเครือฯ
				</div>
				<div style="padding-left: 50px;padding-top: 6px;">
					ข้อ 2. ข้าพเจ้ามิได้เป็นสมาชิกในสหกรณ์ออมทรัพย์อื่น ซึ่งมีวัตถุประสงค์ในประเภทเดียวกัน
				</div>
				<div style="padding-left: 50px;padding-top: 6px;">
					ข้อ 3. ข้าพเจ้าได้เข้าเป็นสมาชิกในขั้นนี้ ข้าพเจ้าขอแสดงความจำนงส่งเงินค่าหุ้นรายเดือนต่อสหกรณ์ฯ ในอัตรา
				</div>
				<div style="padding-top: 6px;">
					เดือนละ <span class="input-zone"><span class="input-value">'.number_format($dataReport["SHARE_PERIOD_PAYMENT"], 2).'
						</span>………….............................</span> บาท <span style="padding-left: 45px;">(จำนวนเงินตัวอักษร)</span> <span class="input-zone"><span
							class="input-value">'.$lib->baht_text($dataReport["SHARE_PERIOD_PAYMENT"]).'
						</span>……………………..............................................</span> (มูลค่าหุ้นละ 10 บาท ขั้นต่ำ 20 หุ้น)
				</div>
				<div style="padding-left: 50px;padding-top: 6px;">
					อนึ่ง ในกรณีที่ข้าพเจ้าถึงแก่กรรม ให้จ่ายเงินส่วนที่ข้าพเจ้ามีอยู่ในสหกรณ์ฯ แก่ผู้รับประโยชน์ ดังนี้
				</div>
				<div style="padding-top: 6px;padding-left: 170px;">
					1. <span class="input-zone" style="padding-right: 80px;"><span class="input-value">'.$dataReport["BENEF_NAME_1"].'
						</span>…..............…………………………………….................</span> 3. <span class="input-zone"><span
							class="input-value">'.$dataReport["BENEF_NAME_3"].'
						</span>………………................……………………...................</span>
				</div>
				<div style="padding-top: 16px;padding-left: 170px;">
					2. <span class="input-zone" style="padding-right: 80px;"><span class="input-value">'.$dataReport["BENEF_NAME_2"].'
						</span>…..............…………………………………….................</span> 4. <span class="input-zone"><span
							class="input-value">'.$dataReport["BENEF_NAME_4"].'
						</span>………………................……………………...................</span>
				</div>
				<div style="width: 100%;padding-left: 50px;padding-top: 12px;">
					<div style="display: inline-block; width: 20%;">
						<div style="display: inline-block;width: 0.8em; height: 0.8em; border: 1px solid #000000;position: relative;">
							'.($dataReport["BENEF_OPTION"] == "1" ? $checked : "").'
						</div>
						<div style="display: inline-block;white-space: nowrap;">ตามเงื่อนไข</div>
					</div>
					<div style="display: inline-block; width: 20%;">
						<div style="display: inline-block;width: 0.8em; height: 0.8em; border: 1px solid #000000;position: relative;">
							'.($dataReport["BENEF_OPTION"] == "2" ? $checked : "").'
						</div>
						<div style="display: inline-block;white-space: nowrap;">ตามส่วนเท่ากัน</div>
					</div>
					<div style="display: inline-block; width: 20%;">
						<div style="display: inline-block;width: 0.8em; height: 0.8em; border: 1px solid #000000;position: relative;">
							'.($dataReport["BENEF_OPTION"] == "3" ? $checked : "").'
						</div>
						<div style="display: inline-block;white-space: nowrap;">ตามลำดับก่อนหลัง</div>
					</div>
					<div style="display: inline-block; width: 30%;">
						<div style="display: inline-block;width: 0.8em; height: 0.8em; border: 1px solid #000000;position: relative;">
							'.($dataReport["BENEF_OPTION"] == "4" ? $checked : "").'
						</div>
						<div style="display: inline-block;white-space: nowrap;">อื่น ๆ(ระบุ) <span
								class="input-zone">…………………………<span class="input-value">'.($dataReport["BENEF_OPTION"] == '4' ? $dataReport["OPTION_VALUE"] : '').'
								</span< /span>
						</div>
					</div>
				</div>
				<div style="padding-left: 50px;padding-top: 0px;">
					ข้อ 4. ถ้าข้าพเจ้าได้เป็นสมาชิก ข้าพเจ้ายินยอมและร้องขอให้ผู้บังคับบัญชา หรือเจ้าหน้าที่จ่ายเงินได้รายเดือน
				</div>
				<div style="padding-top: 6px;">
					ของข้าพเจ้า หักจำนวนเงินค่าหุ้นรายเดือนและจำนวนเงินงวดชำระหนี้พร้อมดอกเบี้ย ซึ่งข้าพเจ้าต้องส่งต่อสหกรณ์ฯ
					นั้นจากเงิน
				</div>
				<div style="padding-top: 6px;">
					ได้รายเดือนของข้าพเจ้า เพื่อจ่ายให้สหกรณ์ฯ
				</div>
				<div style="padding-left: 50px;padding-top: 6px;">
					ข้อ 5. ข้าพเจ้าสัญญาว่า ถ้า คณะกรรมการดำเนินการสหกรณ์ฯ เห็นชอบให้ข้าพเจ้าเป็นสมาชิก และเมื่อได้จดทะเบียน
				</div>
				<div style="padding-top: 6px;">
					สหกรณ์แล้ว ข้าพเจ้าจะลงลายมือชื่อในทะเบียนสมาชิกพร้อมทั้งชำระค่าธรรมเนียมแรกเข้า และเงินค่าหุ้นตามข้อบังคับต่อ
				</div>
				<div style="padding-top: 6px;">
					สหกรณ์ฯ ให้เสร็จภายในวันและเวลาที่คณะกรรมการดำเนินการกำหนด การชำระจำนวนเงินดังกล่าวนี้ ข้าพเจ้ายินยอมและ
				</div>
				<div style="padding-top: 6px;">
					ขอร้องให้ปฏิบัติตามความในข้อ 4 ด้วย
				</div>
				<div style="padding-left: 50px;padding-top: 6px;">
					ข้อ 6. ถ้าข้าพเจ้าได้เป็นสมาชิกจะปฏิบัติตามข้อบังคับ ระเบียบการและมติของสหกรณ์ฯ ทุกประการ
				</div>
				<table style="border: solid 1px #000000;width: 100%;margin-top: 12px;">
					<tr>
						<td style="font-weight: bold;border-right: 1px solid #000000;padding: 20px 5px;width: 50%">
							<div style="display: inline-block;white-space: nowrap;">
								สำหรับเจ้าหน้าที่ : <span style="padding-left: 100px;">เลขที่สมาชิก______________</span>
							</div>
						</td>
						<td style="font-weight: bold;padding: 20px 5px;">
							<div style="display: inline-block;white-space: nowrap;">
								ผู้ตรวจสอบ________________________ <span>วันที่_________________</span>
							</div>
						</td>
					</tr>
				</table>
				<div style="padding-top: 6px;font-weight: bold;">
					<span style="text-decoration: underline;">หมายเหตุ</span> ส่งเอกสารถึงเจ้าหน้าที่สหกรณ์  ภายในวันที่ 7 ของเดือน (ค่าธรรมเนียม 50 บาท) 
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