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
			</style>
			<div>
				 <div style="position: absolute; left: 180px;">
					<div style="text-align: left;"><img src="../../resource/logo/logo.png" style="margin: 10px 0 0 5px" alt=""
							width="80" height="80" /></div>
				</div>
				<div style="text-align: center; font-weight: bold;margin-top: 30px;">
					ใบขอเปลี่ยนแปลงค่าหุ้น
				</div>
				<div style="padding-top: 24px;padding-left: 50%;">
					<div>
						เขียนที่ สหกรณ์ออมทรัพย์พนักงานสยามคูโบต้า จำกัด.
					</div>
					<div style="padding-left: 20px;">
						วันที่<span class="input-zone" style="margin-left: 20px;"><span class="input-value"
								style="padding-left: 10px;">'.$lib->convertdate(date("Y-m-d"),"D m Y").'
							</span>………………………………............</span>
					</div>
				</div>
				<div style="padding-top: 24px;">
					เรื่อง&emsp;ขอเปลี่ยนแปลงค่าหุ้น
				</div>
				<div style="">
					เรียน&emsp;คณะกรรมการดำเนินการสหกรณ์ออมทรัพย์พนักงานสยามคูโบต้า จำกัด
				</div>
				<div style="padding-left: 40px;padding-top: 16px;">
					ข้าพเจ้า ชื่อ <span class="input-zone"><span class="input-value">'.$dataReport["MEMBER_NAME"].'
						</span>………………………….............</span> นามสกุล <span class="input-zone"><span class="input-value">'.$dataReport["MEMBER_SURNAME"].'
						</span>.......................................</span> ส่วน <span class="input-zone"><span class="input-value">'.$dataReport["POSITION"].'
						</span>.................................</span>
				</div>
				<div style="">
					เลขประจำตัวพนักงาน <span class="input-zone"><span class="input-value">'.$dataReport["EMP_NO"].'
						</span>…………..............</span> เลขสมาชิก <span class="input-zone"><span class="input-value">'.$dataReport["MEMBER_NO"].'
						</span>……………………...........</span> โทรศัพท์มือถือ <span class="input-zone"><span class="input-value">'.$dataReport["PHONE_NUMBER"].'
						</span>……………………...........</span>
				</div>
				<div style="padding-left: 40px;">
					มีความประสงค์ที่จะ
				</div>
				<div style="padding-left: 40px;">
					<div style="display: inline-block;width: 0.8em; height: 0.8em; border: 1px solid #000000;position: relative;">
						'.($dataReport["REQUIREMENT"] == "1" ? $checked : "").'
					</div>
					ขอเพิ่มค่าหุ้น จากเดือนละ<span class="input-zone"><span class="input-value">'.($dataReport["REQUIREMENT"] == "1" ? $dataReport["OLD_SHR_PAYMENT"] : null).'
						</span>…………...........</span> บาท เป็นเดือนละ <span class="input-zone"><span class="input-value">'.($dataReport["REQUIREMENT"] == "1" ? $dataReport["SHARE_PERIOD_PAYMENT"] : null).'
						</span>…………...........</span> บาท โดยเริ่มตั้งแต่เดือน <span class="input-zone"><span class="input-value">'.($dataReport["REQUIREMENT"] == "1" ? $dataReport["EFFECT_MONTH"] : null).'
						</span>…………………….....</span>
				</div>
				<div style="padding-left: 40px;">
					<div style="display: inline-block;width: 0.8em; height: 0.8em; border: 1px solid #000000;position: relative;">
						'.($dataReport["REQUIREMENT"] == "2" ? $checked : "").'
					</div>
					ขอลดค่าหุ้น จากเดือนละ<span class="input-zone"><span class="input-value">'.($dataReport["REQUIREMENT"] == "2" ? $dataReport["OLD_SHR_PAYMENT"] : null).'
						</span>…………............</span> บาท เป็นเดือนละ <span class="input-zone"><span class="input-value">'.($dataReport["REQUIREMENT"] == "2" ? $dataReport["SHARE_PERIOD_PAYMENT"] : null).'
						</span>…………............</span> บาท โดยเริ่มตั้งแต่เดือน <span class="input-zone"><span class="input-value">'.($dataReport["REQUIREMENT"] == "2" ? $dataReport["EFFECT_MONTH"] : null).'
						</span>…………………….....</span>
				</div>
				<div style="padding-left: 40px;">
					<div style="display: inline-block;width: 0.8em; height: 0.8em; border: 1px solid #000000;position: relative;">
						'.($dataReport["REQUIREMENT"] == "3" ? $checked : "").'
					</div>
					ขอหยุดนำส่งค่าหุ้นรายเดือน โดยเริ่มตั้งแต่เดือน<span class="input-zone"><span class="input-value">'.($dataReport["REQUIREMENT"] == "3" ? $dataReport["EFFECT_MONTH"] : null).'
						</span>…………..............</span> เป็นต้นไป
				</div>
				<div style="padding-left: 40px;">
					<div style="display: inline-block;width: 0.8em; height: 0.8em; border: 1px solid #000000;position: relative;">
						'.($dataReport["REQUIREMENT"] == "4" ? $checked : "").'
					</div>
					ขอนำส่งค่าหุ้นรายเดือนต่อ จำนวน<span class="input-zone"><span class="input-value">'.($dataReport["REQUIREMENT"] == "4" ? $dataReport["SHARE_PERIOD_PAYMENT"] : null).'
						</span>…………..............</span> บาท โดยเริ่มตั้งแต่เดือน <span class="input-zone"><span
							class="input-value">'.($dataReport["REQUIREMENT"] == "4" ? $dataReport["EFFECT_MONTH"] : null).'
						</span>……………………...........</span> เป็นต้นไป
				</div>
				<div style="border-bottom: solid 2px black;margin-top: 24px;">

				</div>
				<div style="font-weight: bold;text-decoration: underline;padding-top: 16px;">
					สำหรับเจ้าหน้าที่สหกรณ์
				</div>
				<div style="padding-left: 40px;padding-top: 24px;">
					คณะกรรมการดำเนินการสหกรณ์ฯ มีมติวันที่ …………………………………………
				</div>
				<div style="padding-left: 120px;">
					<div style="display: inline-block;width: 0.8em; height: 0.8em; border: 1px solid #000000;position: relative;">
						<span style="position: absolute;top: -5px;left: 2px;font-weight: bold;">&#10003;</span>
					</div>
					อนุมัติ
				</div>
				<div style="padding-left: 120px;">
					<div style="display: inline-block;width: 0.8em; height: 0.8em; border: 1px solid #000000;position: relative;">
						<span style="position: absolute;top: -5px;left: 2px;font-weight: bold;">&#10003;</span>
					</div>
					ไม่อนุมัติ เนื่องจากเหตุผล<span class="input-zone"><span class="input-value">
						</span>…………………………………………………………………………</span>
				</div>
				<div style="text-align: center;padding-top: 24px;">
					<div>
						(…………………………………………………….)
					</div>
					<div>
						เจ้าหน้าที่สหกรณ์
					</div>
				</div>
				<div style="position: relative;padding-top: 24px;">
					<div style="font-weight: bold;text-decoration: underline;position: absolute;">
						หมายเหตุ
					</div>
					<div style="font-weight: bold;padding-left: 100px;">
						1. ขอเพิ่มหุ้น/ลดหุ้นได้ทุกเดือน (ลดหุ้นเดือน ก.พ. และ ส.ค. ไม่เสียค่าธรรมเนียม)
					</div>
					<div style="font-weight: bold;padding-left: 100px;">
						2. สมาชิกสามารถขอหยุดส่งค่าหุ้นได้ในกรณีที่ชำระเงินค่าหุ้นไม่น้อยกว่า 120 เดือน หรือ
					</div>
					<div style="font-weight: bold;padding-left: 120px;">
						มีเงินออมไม่น้อยกว่า 200,000.- บาท และไม่มีหนี้สินกับสหกรณ์ฯ
					</div>
					<div style="font-weight: bold;padding-left: 100px;">
						3. ส่งเอกสารถึงเจ้าหน้าที่สหกรณ์ ภายในวันที่ 7 ของเดือน
					</div>
					<div style="font-weight: bold;padding-left: 100px;">
						4. ขอลดหุ้นนอกเหนือจากเดือนในข้อ 1 มีค่าธรรมเนียมครั้งละ 50 บาท
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