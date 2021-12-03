<?php
use Dompdf\Dompdf;

$dompdf = new DOMPDF();

function GenerateReport($dataReport,$lib){
	$sumBalance = 0;
	$dept_month_arr = $lib->convertdate($dataReport["DEPT_MONTH_ARR"],'D m Y');
	$dept_month_arr = explode(" ", $dept_month_arr);
	$dept_month = $dept_month_arr[1];
	$dept_year = $dept_month_arr[2];
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
			<div>
			    <div style="position: absolute; left: 186px;">
				<div style="text-align: left;"><img src="../../resource/logo/logo.png" style="margin: 10px 0 0 5px" alt=""
					width="80" height="80" /></div>
			    </div>
			    <div style="text-align: center; margin-top: 30px;text-decoration: underline;font-weight: bold;">
				แบบชำระหนี้บางส่วน
			    </div>
			    <div style="padding-top: 24px;padding-left: 50%;">
				วันที่<span class="input-zone"><span class="input-value">'.$lib->convertdate(date("Y-m-d"),"D m Y").'
				</span>.......................................</span>
			    </div>
			    <div style="padding-top: 24px;">
				เรียน  ประธานคณะกรรมการดำเนินการสหกรณ์ออมทรัพย์พนักงานสยามคูโบต้า จำกัด
			    </div>
			    <div style="padding-left: 60px;padding-top: 24px;">
				ข้าพเจ้า (นาย/นาง/น.ส.) <span class="input-zone"><span class="input-value">'.$dataReport["MEMBER_FULLNAME"].'
				</span>.......................................................</span> เลขที่สมาชิก <span class="input-zone"><span class="input-value">'.$dataReport["MEMBER_NO"].'
				</span>.................................</span>
			    </div>
			    <div>
				เลขประจำตัวพนักงาน <span class="input-zone"><span class="input-value">'.$dataReport["EMP_NO"].'
				</span>......................</span> โดยขอชำระหนี้บางส่วนในวันที่  28 เดือน<span class="input-zone"><span class="input-value">'.$dept_month.'
				</span>.................</span>พ.ศ <span class="input-zone"><span class="input-value">'.$dept_year.'
				</span>................</span>
			    </div>
			    <div>
				สัญญาเลขที่<span class="input-zone"><span class="input-value">'.implode(",", $dataReport["CONTRACT"]).'
				</span>….......................................</span> จำนวนเงิน<span class="input-zone"><span class="input-value">'.$dataReport["PAYMENT_AMT"].'
				</span>.......................................</span>บาท
			    </div>
			    <div>
				ตามเงื่อนไข (โปรดทำเครื่องหมาย X ใน <div
				    style="display: inline-block;width: 0.8em; height: 0.8em; border: 1px solid #000000; border-radius: 0.4em;"></div>
				ที่ท่านเลือก)
			    </div>
			    <div style="width: 100%;padding-top: 24px;padding-left: 60px;">
				<div>
				    <div
					style="display: inline-block;width: 0.8em; height: 0.8em; border: 1px solid #000000; border-radius: 0.4em;position: relative;vertical-align: top;"><span style="position: absolute;top: -9px;left: 5px;">
					'.($dataReport["BANK_NO"] == '0830523395' ? 'X' : '').'
					</span></div>
				    <div style="display: inline-block;white-space: nowrap;">
					<div>
					    โอนเข้าบัญชีสหกรณ์ฯ    ชื่อบัญชี <span style="font-weight: bold;">“สหกรณ์ออมทรัพย์พนักงานสยามคูโบต้า จำกัด”</span>
					</div>
					<div>
					    <span style="font-weight: bold;">ธนาคารกรุงเทพ  สาขานวนคร</span>  เลขที่บัญชี <span style="font-weight: bold;">083-0-52339-5</span>
					</div>
				    </div>
				</div>
				<div>
				    <div
					style="display: inline-block;width: 0.8em; height: 0.8em; border: 1px solid #000000; border-radius: 0.4em;position: relative;vertical-align: top;"><span style="position: absolute;top: -9px;left: 5px;">
					'.($dataReport["BANK_NO"] == '4052405219' ? 'X': '').'
					</span></div>
				    <div style="display: inline-block;white-space: nowrap;">
					<div>
					    โอนเข้าบัญชีสหกรณ์ฯ    ชื่อบัญชี <span style="font-weight: bold;">“สหกรณ์ออมทรัพย์พนักงานสยามคูโบต้า จำกัด”</span>
					</div>
					<div>
					    <span style="font-weight: bold;">ธนาคารกสิกรไทย  สาขานวนคร</span>  เลขที่บัญชี <span style="font-weight: bold;">405-2-40521-9</span>
					</div>
				    </div>
				</div>
				<div>
				    <div
					style="display: inline-block;width: 0.8em; height: 0.8em; border: 1px solid #000000; border-radius: 0.4em;position: relative;vertical-align: top;"><span style="position: absolute;top: -9px;left: 5px;">
					'.($dataReport["BANK_NO"] == '3144173129' ? 'X' : '').'
					</span></div>
				    <div style="display: inline-block;white-space: nowrap;">
					<div>
					    โอนเข้าบัญชีสหกรณ์ฯ    ชื่อบัญชี <span style="font-weight: bold;">“สหกรณ์ออมทรัพย์พนักงานสยามคูโบต้า จำกัด”</span>
					</div>
					<div>
					    <span style="font-weight: bold;">ธนาคารไทยพาณิชย์  สาขานวนคร</span>  เลขที่บัญชี <span style="font-weight: bold;">314-4-17312-9</span>
					</div>
				    </div>
				</div>
			    </div>
			    <div style="padding-top: 42px;font-weight: bold;">
				<div style="display: inline-block;white-space: nowrap;vertical-align: top;padding-right: 8px;transform: translateY(-10px)">
				    หมายเหตุ .- 
				</div>
			       <div style="display: inline-block;white-space: nowrap;">
				    <div>
					กรุณาส่งเอกสารเพื่อแจ้งความประสงค์ภายในวันที่ 7 ของเดือน
				    </div>
				    <div>
					และโอนเงินในวันที่ 28 พร้อมส่งสำเนาใบโอนเงินให้สหกรณ์
				    </div>
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