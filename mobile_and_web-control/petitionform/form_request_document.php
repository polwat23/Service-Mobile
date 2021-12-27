<?php
use Dompdf\Dompdf;

$dompdf = new DOMPDF();

function GenerateReport($dataReport,$lib){
	$sumBalance = 0;
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
				  padding: 40px;
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
				<div style="text-align: left;font-size: 10pt;">
					สอ. 12 – คําร้อง mobile app.
				</div>
				<div style="text-align: center;">
					สหกรณ์ออมทรัพย์มหาวิทยาลัย ศรีนครินทรวิโรฒ จํากัด
				</div>
				<div style="text-align: center;font-weight: bold;">
					คําร้องท่ัวไป (mobile app.)
				</div>
				<div style="text-align: right;">
					เขียนที่............................................
				</div>
				<div style="text-align: center;">
					วันที่.........................................
				</div>
				<div style="padding-left: 40px;white-space: nowrap;">
					ข้าพเจ้า<span class="input-zone"><span
							class="input-value">'.$dataReport["FULLNAME"].'
						</span>.........................................................</span>สมาชิกเลขที่<span
						class="input-zone"><span class="input-value">'.$dataReport["MEMBER_NO"].'
						</span>............................</span>โทรศัพท์<span class="input-zone"><span class="input-value">'.$dataReport["TEL"].'
						</span>...............................</span>
				</div>
				<div>
					หน่วย<span class="input-zone"><span class="input-value">'.$dataReport["MEMBGROUP_DESC"].'
						</span>....................................................................</span>ขอให้ดำเนินการดังนี้
				</div>
				<div style="padding-left: 60px;">
					<div>
						<div
							style="width: 0.8em;height: 0.8em;border-radius: 0.4em;border: 1px solid black;display: inline-block;vertical-align: middle;position: relative;">
							'.($dataReport["PETITIONFORM_CODE"] == '4' ? '<span style="font-size: 1.3em;font-weight: bold;position: absolute;top: -14px;left: 3px;">X</span>' : '').'
						</div>
						<div style="display:inline-block;vertical-align: middle;">
							เปลี่ยนแปลงประวัติส่วนตัว
						</div>
					</div>
					<div>
						<div
							style="width: 0.8em;height: 0.8em;border-radius: 0.4em;border: 1px solid black;display: inline-block;vertical-align: middle;position: relative;">
							'.($dataReport["PETITIONFORM_CODE"] == '3' ? '<span style="font-size: 1.3em;font-weight: bold;position: absolute;top: -14px;left: 3px;">X</span>' : '').'
						</div>
						<div style="display:inline-block;vertical-align: middle;">
							เปลี่ยนแปลงการส่งเงินสะสมค่าหุ้น
						</div>
					</div>
					<div>
						<div
							style="width: 0.8em;height: 0.8em;border-radius: 0.4em;border: 1px solid black;display: inline-block;vertical-align: middle;position: relative;">
							'.($dataReport["PETITIONFORM_CODE"] == '2' ? '<span style="font-size: 1.3em;font-weight: bold;position: absolute;top: -14px;left: 3px;">X</span>' : '').'
						</div>
						<div style="display:inline-block;vertical-align: middle;">
							เปลี่ยนแปลงเงินฝาก
						</div>
					</div>
					<div>
						<div
							style="width: 0.8em;height: 0.8em;border-radius: 0.4em;border: 1px solid black;display: inline-block;vertical-align: middle;position: relative;">
							'.($dataReport["PETITIONFORM_CODE"] == '1' ? '<span style="font-size: 1.3em;font-weight: bold;position: absolute;top: -14px;left: 3px;">X</span>' : '').'
						</div>
						<div style="display:inline-block;vertical-align: middle;">
							เปลี่ยนแปลงเงินกู้
						</div>
					</div>
					<div>
						<div
							style="width: 0.8em;height: 0.8em;border-radius: 0.4em;border: 1px solid black;display: inline-block;vertical-align: middle;position: relative;">
							'.($dataReport["PETITIONFORM_CODE"] == '-99' ? '<span style="font-size: 1.3em;font-weight: bold;position: absolute;top: -14px;left: 3px;">X</span>' : '').'
						</div>
						<div style="display:inline-block;vertical-align: middle;">
							อื่นๆ<span class="input-zone"><span class="input-value">
							'.($dataReport["PETITIONFORM_CODE"] == '-99' ? $dataReport["PETITIONFORM_DESC"] : '').'
							</span>....................................................</span>
						</div>
					</div>
				</div>
				<div style="padding-left: 40px;">
					<span class="input-zone"><span
							class="input-value" style="max-width: 550px;width: 550px; word-wrap: break-word; white-space:pre-wrap;text-indent: 100px;">'.$dataReport["REQ_DESC"].'
						</span>ชี้แจงรายละเอียด.............................................................................................................................</span>
				</div>
				<div style="padding-left: 40px;">
					........................................................................................................................................................
				</div>
				<div style="padding-left: 40px;">
					........................................................................................................................................................
				</div>
				<div style="padding-left: 40px;">
					ทั้งนี้ตั้งแต่วันที่<span class="input-zone"><span
						class="input-value">'.$dataReport["EFFECT_DATE_D"].'
					</span>..........</span>เดือน<span class="input-zone"><span
						class="input-value">'.$dataReport["EFFECT_DATE_M"].'
					</span>............................</span>พ.ศ. <span class="input-zone"><span
						class="input-value">'.$dataReport["EFFECT_DATE_Y"].'
					</span>........................</span>
				</div>
				<div style="text-align: right;padding-top: 16px;padding-right: 36px;padding-bottom: 16px;">
					ลงช่ือ.................................................. (สมาชิก)
				</div>
				<div>
					<div style="display:inline-block;vertical-align: top;padding-right: 10px;font-weight: bold;">
						หมายเหตุ
					</div>
					<div style="display:inline-block;vertical-align: top;">
						<div>
							1 หนึ่งคําร้อง หนึ่งเรื่อง
						</div>
						<div>
							2 โปรดเขียนรายละเอียดให้ชัดเจนและสหกรณ์อาจต้องขอรายละเอียดเพิ่มเติม
						</div>
					</div>
				</div>
				<div>
					......................................................................................................................................................................
				</div>
				<div style="font-weight: bold;">
					เฉพาะเจ้าหน้าที่สหกรณ์
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
