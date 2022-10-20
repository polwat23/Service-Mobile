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
				<div style="position: absolute; top :left: 30px;">
					<div style="text-align: left;"><img src="../../resource/logo/logo.png" style="margin: 10px 0 0 5px" alt=""
							width="80" height="80" /></div>
				</div>
				<div style="text-align: center; font-weight: bold;margin-top: 30px;text-decoration: underline;">
					แบบขอแจ้งเปลี่ยนแปลงผู้รับประโยชน์ของสหกรณ์ออมทรัพย์พนักงานฯ
				</div>
				<div style="text-align: center;padding-top: 24px;">
					<div>
					วันที่<span class="input-zone"><span class="input-value" style="left: 285px;">'.$lib->convertdate(date("Y-m-d"),"D m Y").'
					</span>…………………………………</span>
					</div>
				</div>
				<div style="font-weight: bold;padding-top: 24px;">
					เรียน     ประธานคณะกรรมการดำเนินการ ฯ
				</div>
				<div style="padding-left: 60px;padding-top: 24px;">
					ข้าพเจ้า (นาย/ นาง/ น.ส.) <span class="input-zone"><span class="input-value">'.$dataReport["MEMBER_FULLNAME"].'
					</span>………………………………………….................</span> รหัสพนักงาน <span class="input-zone"><span class="input-value">'.$dataReport["EMP_NO"].'
					</span>.........................</span>
				</div>
				<div style="padding-top: 12px;">
					เลขสมาชิก <span class="input-zone"><span class="input-value">'.$dataReport["MEMBER_NO"].'
					</span>…………..............</span>ขอแจ้งเปลี่ยนแปลงผู้รับประโยชน์ของข้าพเจ้า โดยให้มีผลตั้งแต่วันที่<span class="input-zone"><span class="input-value">'.$dataReport["EFFECT_DATE"].'
					</span>……………………...........</span>เป็นต้นไป
				</div>
				<div style="padding-left: 60px;padding-top: 32px;">
					<span style="font-weight: bold;text-decoration: underline;">ผู้รับประโยชน์</span> (กรณีข้าพเจ้าถึงแก่กรรม)
				</div>
				<div style="padding-top: 16px;">
					1<span class="input-zone"><span class="input-value">'.$dataReport["BENEF_NAME_1"].'
					</span>…..............…………………………………….................</span>ความสัมพันธ์กับสมาชิก<span class="input-zone"><span class="input-value">'.$dataReport["BENEF_MEMBER_1"].'
					</span>………………................……………</span>เปอร์เซ็นต์<span class="input-zone"><span class="input-value">'.(isset($dataReport["BENEF_PERCENT_1"]) && $dataReport["BENEF_PERCENT_1"] != "" ? $dataReport["BENEF_PERCENT_1"]."%" : "" ).'
					</span>………………..........</span>
				</div>
				<div style="padding-top: 16px;">
					1<span class="input-zone"><span class="input-value">'.$dataReport["BENEF_NAME_2"].'
					</span>…..............…………………………………….................</span>ความสัมพันธ์กับสมาชิก<span class="input-zone"><span class="input-value">'.$dataReport["BENEF_MEMBER_2"].'
					</span>………………................……………</span>เปอร์เซ็นต์<span class="input-zone"><span class="input-value">'.(isset($dataReport["BENEF_PERCENT_2"]) && $dataReport["BENEF_PERCENT_2"] != "" ? $dataReport["BENEF_PERCENT_2"]."%" : "" ).'
					</span>………………..........</span>
				</div>
				<div style="padding-top: 16px;">
					1<span class="input-zone"><span class="input-value">'.$dataReport["BENEF_NAME_3"].'
					</span>…..............…………………………………….................</span>ความสัมพันธ์กับสมาชิก<span class="input-zone"><span class="input-value">'.$dataReport["BENEF_MEMBER_3"].'
					</span>………………................……………</span>เปอร์เซ็นต์<span class="input-zone"><span class="input-value">'.(isset($dataReport["BENEF_PERCENT_3"]) && $dataReport["BENEF_PERCENT_3"] != "" ? $dataReport["BENEF_PERCENT_3"]."%" : "" ).'
					</span>………………..........</span>
				</div>
				<div style="padding-top: 16px;">
					1<span class="input-zone"><span class="input-value">'.$dataReport["BENEF_NAME_4"].'
					</span>…..............…………………………………….................</span>ความสัมพันธ์กับสมาชิก<span class="input-zone"><span class="input-value">'.$dataReport["BENEF_MEMBER_4"].'
					</span>………………................……………</span>เปอร์เซ็นต์<span class="input-zone"><span class="input-value">'.(isset($dataReport["BENEF_PERCENT_4"]) && $dataReport["BENEF_PERCENT_4"] != "" ? $dataReport["BENEF_PERCENT_4"]."%" : "" ).'
					</span>………………..........</span>
				</div>
				<div style="padding-top: 16px;">
					1<span class="input-zone"><span class="input-value">'.$dataReport["BENEF_NAME_5"].'
					</span>…..............…………………………………….................</span>ความสัมพันธ์กับสมาชิก<span class="input-zone"><span class="input-value">'.$dataReport["BENEF_MEMBER_5"].'
					</span>………………................……………</span>เปอร์เซ็นต์<span class="input-zone"><span class="input-value">'.(isset($dataReport["BENEF_PERCENT_5"]) && $dataReport["BENEF_PERCENT_5"] != "" ? $dataReport["BENEF_PERCENT_5"]."%" : "" ).'
					</span>………………..........</span>
				</div>
				<div style="padding-top: 16px;">
					ข้าพเจ้าขอรับรองว่าข้อมูลข้างต้นเป็นความจริงและสมบูรณ์ ข้าพเจ้ารับทราบและตกลงยอมรับตามข้อมูลดังกล่าว
				</div>
				<div style="padding-top: 16px;padding-left: 256px;">
					ลงชื่อ <span class="input-zone"><span class="input-value">'.$dataReport["MEMBER_NAME"].'
					</span>…………………………………………………</span> ผู้แจ้งความประสงค์
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