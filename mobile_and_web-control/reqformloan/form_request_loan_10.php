<?php
use Dompdf\Dompdf;

function GeneratePDFContract($data,$lib) {
	$thaimonth = ["","มกราคม","กุมภาพันธ์","มีนาคม","เมษายน","พฤษภาคม","มิถุนายน","กรกฏาคม","สิงหาคม","กันยายน","ตุลาคม","พฤศจิกายน","ธันวาคม"];
	$minispace = '&nbsp;&nbsp;';
	$space = '&nbsp;&nbsp;&nbsp;&nbsp;';
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
		  font-size:18px;
		  line-height: 18px;

		}
		.text-center{
		  text-align:center
		}
		.text-right{
		  text-align:right
		}
		.nowrap{
		  white-space: nowrap;
		}
		.wrapper-page {
		  page-break-after: always;
		}

		.wrapper-page:last-child {
		  page-break-after: avoid;
		}
		table{
		  border-collapse: collapse;
		  line-height: 20px
		}
		th,td{
		  border:1px solid;
		  text-align:center;
		}
		td{
		  font-weight:bold;
		  padding:10px;
		  height:30px;
		}
		</style>
		';


	$html .= '<div style=" margin: 0px 22px; ">';


	//หน้า 1
	$html .= '<div class="wrapper-page">';


	$html .= '
		<div style="border:1px solid #000000; width:140px; position:absolute; left:20px; top:20px; padding:5px; 5px ">
		  <div>
			  รับที่................/...................
		  </div>
		  <div>
			  วันที่......./................./..........
		  </div>
		</div>
		<div style="text-align:center">
		  <img src="../../resource/logo/logo.jpg" style="width:100px"/>
		</div>
		<div style="border:1px solid #000000; width:160px; position:absolute; right:20px; top:10px; padding:5px; 5px ">
		  <div>
			  หนังสือกู้ที่ ฉ............................
		  </div>
		  <div>
			  วันที่............/............................
		  </div>
		  <div>
			บัญชีเงินกู้ที่..............................
		  </div>
		</div>
	 
		<div style="text-align:center;  font-size:20px; font-weight:bold; margin-top:3px; "> 
			   คำขอและหนังสือกู้เงินเพื่อเหตุฉุกเฉิน
	   </div>
	   <div style="border:1px solid #000000; width:288px; position:absolute; left:20px; top:127px; padding:3px 5px; line-height:19px; ">
		  <div >
			  <b>คำเตือน</b> <span style="padding-left:30px; letter-spacing:0.3 ">ผู้ขอกู้ต้องกรอกข้อความตามรายการ</span> 
		  </div>
		  <div style="letter-spacing:0.64;">
			  ที่กำหนดไว้ในแบบคำขอกู้ด้วย<span style="text-decoration: underline;  border-bottom: 2px red; ">ลายมือของตนเอง</span>
		  </div>
		  <div>
			  โดยถูกต้องและครบถ้วนมิฉะนั้นสหกรณ์ไม่รับพิจารณา
		  </div>
	  </div>
	   <div style=" text-align:right;f width:100%; margin-top:40px;" >
			   เขียนที่........................................................................
	   </div>
	   <div style="text-align:right;">
		  วันที่..........เดือน...................................พ.ศ...............
	  </div>
	  <div>
		เรียน 
		<div style="position:absolute;  margin-left:20px;">
		 คณะกรรมการดําเนินการสหกรณ์ออมทรัพย์มหาวิทยาลัยแม่โจ้ จํากัด
		</div>
	  </div>
	';
	//เขียนที่
	$html .= '
		<div style="position: absolute; left:460px; top:161px;font-weight:bold;">
			  MJU Saving (Mobile Application)
		</div>
	';

	//วันที่ |เดือน | ปี
	$html .= '
	  <div class="text-center" style="position: absolute; right: 227px; top: 182px; width:45px;font-weight:bold;" >
		  '.date('d').'
	  </div>
	  <div class="text-center" style="position: absolute; right: 85px; top: 182px; width:129px;font-weight:bold;">
		'.$thaimonth[(int)date('m')].'
	  </div>
	  <div class="text-center" style="position: absolute;right: 25px; top: 182px; width:45px;font-weight:bold;">
		'.(date('Y')+543).'
	  </div>
	  ';

	$html .= '
	  <div style="line-height:20px;">
		  <div class="nowrap" style="padding-left:50px; letter-spacing:0.07px ">
		  ข้าพเจ้า................................................................................... สมาชิกเลขทะเบียนที่...................................... รับราชการ
		  </div>
		  <div class="nowrap" style="letter-spacing:0.01">
			 หรือทํางานประจําในตําแหน่ง...............................................................สังกัด........................................................................ได้รับเงิน
		  </div>
		  <div class="nowrap">
			รายเดือน................................บาท ขอเสนอคำขอกู้เพื่อเหตุฉุกเฉินดังต่อไปนี้ เพื่อ...............................................................................
		  </div>
		  <div>
		  ...........................................................................................................................................................................................................
		  </div>
		  <div class="nowrap" style="padding-left:50px; letter-spacing:0.01">
			ข้อ 1. ข้าพเจ้าขอกู้เงินของสหกรณ์จํานวน.........................บาท (........................................................................................)
		  </div>

		  <div class="nowrap" style="padding-left:50px; margin:0px; ">
			ข้อ 2. ในเวลานี้ข้าพเจ้ามีหุ้นอยู่ในสหกรณ์ รวม............................หุ้น เป็นเงิน.................................................................บาท
		  </div>
		  <div style="padding-left:50px; margin:0px; ">
			ข้อ 3. ข้าพเจ้ามีหนี้สินอยู่ต่อสหกรณ์ในฐานะผู้กู้ ดังต่อไปนี้
		  </div>';


	for($i = 1;$i <= 2;$i++) {
		$loanPrefix = mb_substr($data["old_contract"][$i - 1]["loancontract_no"],0,2);
		$loancontract_no = mb_substr($data["old_contract"][$i - 1]["loancontract_no"],2);
		$html .= '
			<div style="padding-left:98px;"> 
			<div>
			(' . $i . ') หนังสือกู้............................................................ ที่ .............../............................วันที่....................................
			<div style="position:absolute; left:150;font-weight:bold; ">
				'.($data["old_contract"][$i - 1]["loantype_desc"] ?? null).'
			</div>
			<div style="position:absolute; left:303;font-weight:bold; ">
				'.($loanPrefix ?? null).'
			</div>
			<div style="position:absolute; left:340;font-weight:bold; ">
				'.($loancontract_no ?? null).'
			</div>
			<div  class="nowrap" style="position:absolute; right:45;font-weight:bold; ">
				'.($data["old_contract"][$i - 1]["loanapprove_date"] ?? null).'
			</div>
			</div>

			</div>
			<div style="padding-left:98px;"> 
			<div>
			เพื่อ.............................................................ต้นเงินคงหลือ.....................................................บาท
			<div style="position:absolute; left:120;font-weight:bold; ">
				'.($data["old_contract"][$i - 1]["loanobjective_desc"] ?? null).'
			</div>
			<div style="position:absolute; right:150;font-weight:bold; ">
				'.($data["old_contract"][$i - 1]["principal_balance"] ?? null).'
			</div>
			</div>
			</div>
			';
	}

	$html .= '
		<div style="padding-left  :50px; margin:0px; ">
		ข้อ 4. ข้าพเจ้าได้รับเงินกู้ ข้าพเจ้าขอส่งเงินกู้คืนเป็นงวดรายเดือน ดังนี้
		</div>
		<div class="nowrap" style="padding-left:98px;"> 
		ชำระเงินต้น งวดล่ะ......................................บาท (.............................................................................................)
		<div class="text-center" style="position:absolute; left: 210px;  width: 105px;font-weight:bold;">'.number_format($data["period_payment"],2).'</div>
		<div class="text-center" style="position:absolute; left: 340px;  width: 317px;font-weight:bold;">'.$lib->baht_text($data["period_payment"]).'</div>
		</div>
		<div class="nowrap">
		<div class="text-center" style="position:absolute; left:135px; width:80px; font-weight:bold;">'.$data["period"].'</div>
		<div class="text-center" style="position:absolute; right:115px; width:218px;font-weight:bold; ">'.$lib->convertperiodkp((date('Y')+543).date('m')).'</div>
		พร้อมด้วยดอกเบี้ย รวม.......................งวด ทั้งนี้ตั้งแต่งวดประจำเดือน............................................................เป็นต้นไป
		</div>
		<div  style="padding-left  :50px; margin:0px; ">
		ข้อ 5. เมื่อข้าพเจ้าได้รับเงินกู้ข้าพเจ้าข้าพเจ้ายอมรับข้อผูกพันตามข้อบังคับของสหกรณ์ ดังนี้
		</div>
		<div class="nowrap" style="padding-left:98px; letter-spacing:0.13"> 
		5.1 ยินยอมให้ผู้บังคับบัญชา หรือเจ้าหน้าที่ผู้จ่ายเงินได้รายเดือนของข้าพเจ้า ที่ได้รับมอบหมายจากสหกรณ์หัก
		</div>
		<div>
		 เงินได้รายเดือนของข้าพเจ้า ตามจํานวนงวดชําระหนี้ ข้อ 4. เพื่อส่งต่อสหกรณ์
		</div>
		<div class="nowrap" style="padding-left:98px; letter-spacing:0.33;">
		5.2 ยอมให้ถือว่าในกรณีใด ๆ ดังกล่าวในข้อบังคับ ข้อ 14 ให้เงินกู้ขอกู้ไปฉากสหกรณ์เป็นอันถึงกําหนดส่ง
		</div>
		<div>
		คืนโดยสิ้นเชิงพร้อมทั้งดอกเบี้ยในทันที โดยมีพักคํานึงถึงกําหนดเวลาที่ตกลงไว้
		</div>
		<div class="nowrap" style="padding-left:98px; letter-spacing:-0.13 ">
		5.3 ถ้าประสงค์จะขอลาออก หรือย้ายจากราชการ หรืองานประจําตามข้อบังคับ ข้อ 15 31(3) จะแจ้งเป็นหนังสือให้
		</div>
		<div class="nowrap" style="letter-spacing:0.02">
		สหกรณ์ทราบและจัดการชําระหนี้ซึ่งมีอยู่ต่อสหกรณ์ให้เสร็จสิ้นเสียก่อน ถ้าข้าพเจ้าไม่หักการช่าระหนี้ให้เสร็จสิ้นตามที่กล่าวข้างต้น 
		</div>
		<div class="nowrap" style="letter-spacing:0.04">
		เมื่อข้าพเจ้าได้ลงชื่อรับเงินเดือน ค่าจ้าง เงินสะสม บําเหน็จบํานาญ เงินทุนเลี้ยงชีพ หรือเงินอื่นใดในหลักฐาน ที่ทางราชการหรือ
		</div>
		<div class="nowrap" style="letter-spacing:0.001">
		หน่วยงานเจ้าสังกัดจะจ่ายเงิน ให้แก่ข้าพเจ้าข้าพเจ้ายินยอมให้ เจ้าหน้าที่ผู้จ่ายเงินดังกล่าว หักเงินช้ำระหนี้พร้อมดอกเบี้ยส่งชําระหนี้ 
		</div>
		<div class="nowrap" style="letter-spacing:0.04">
		ต่อสหกรณ์ให้เสร็จสิ้นเสียก่อนได้
		</div>
		';

	$html .= '
		<div style="line-height:19px;">
		<div style="margin-top:20px;  text-align:right; margin-right:30px">
		...........................................................ผู้ขอกู้
		</div>
		<div style=" text-align:right; margin-right:57px; ">
		(...........................................................)
		</div>  

		<div style="margin-top:20px;  text-align:right; margin-right:30px;">
		...........................................................พยาน
		</div>
		<div style=" text-align:right; margin-right:57px;">
		(...........................................................)
		</div>  

		<div style="margin-top:20px;  text-align:right; margin-right:30px;">
		...........................................................พยาน
		</div>
		<div style=" text-align:right; margin-right:57px;">
		(...........................................................)
		</div>  
		</div>
		';
	$html .= '  </div>';

	//ข้อมูล 
	$html .= '
		<div class="text-center" style=" position:absolute; right:40px; bottom : 202px;  width:270px;font-weight:bold;">
			'.$data["name"].'
		</div>
		<div class="text-center" style=" position:absolute; left:70px; top : 224px;  width:270px;font-weight:bold;">
			'.$data["full_name"].'
		</div>
		<div class="text-center" style=" position:absolute; left:505px; top : 224px;  width:100px;font-weight:bold;">
			'.$data["member_no"].'
		</div>
		<div class="text-center" style=" position:absolute; left:115px; top : 247px;  width:210px;font-weight:bold;">
			'.$data["position"].'
		</div>
		<div class="text-center" style=" position:absolute; right:130px; top : 247px;  width:200px;font-weight:bold;">
			'.$data["pos_group"].'
		</div>
		<div class="text-center" style=" position:absolute; left:76px; top : 270px;  width:75px;font-weight:bold;">
			'.$data["salary_amount"].'
		</div>
		<div  style=" position:absolute; left:22px; top : 270px;  width:635px; text-indent:425px;font-weight:bold;">
			'.$data["objective"].'
		</div>
		<div class="text-center" style=" position:absolute; left:278px; top : 315px;  width:80px;font-weight:bold;">
			'.number_format($data["request_amt"],2).'
		</div>
		<div class="text-center" style=" position:absolute; right: 40px; top : 315px;  width:270px;font-weight:bold;">
			'.$lib->baht_text($data["request_amt"]).'
		</div>
		<div class="text-center" style=" position:absolute; left:330px; top : 337px;  width:30px;font-weight:bold;">
			'.$data["share_amt"].'
		</div>
		<div class="text-center" style=" position:absolute; right:48px; top : 337px;  width:228px;font-weight:bold;">
			'.$data["sharestk_amt"].'
		</div>
		';

	$html .= '</div>';

		//หน้า 2 
	$html .= '<div class="wrapper-page">';

	$html .= '
		<div class="text-center">2</div>
		<div style="margin-top:-3px;">
		ความเห็นของเจ้าหน้าที่การเงินของหน่วยงานต้นสังกัด
		</div>
		<div style="padding-left:108px; margin-top:5px;">
		มีเงินได้รายเดือนเหลือหัก ณ ที่จ่าย
		</div>
		<div style="padding-left:108px; margin-top:5px;">
		ไม่มีเงินได้รายเดือนเหลือหัก ณ ที่จ่าย
		</div>
		<div style="margin-top:30px; margin-right:20px; text-align:right;">
		(ลงชื่อ)...........................................................เจ้าหน้าที่การเงิน
		</div>
		<div style="text-align:right; margin-right:102px; margin-top:5px;">
		(...........................................................)
		</div>  
		<div class="text-center" style="margin-top:20px;">
		(รายการต่อไปนี้ เจ้าหน้าที่ของสหกรณ์กรอกเอง)
		</div>
		<div class="text-center">
		จํานวนเงินกู้................................. บาท
		</div>
		';
	$html .= '
		<table style="width:100%; margin-top:10px;">
		<thead>
		<tr>
		<th colspan=5 style="padding:0px 0px 5px 0px;">จํากัดวงเงินกู้เพื่อเหตุฉุกเฉิน</th>
		</tr>
		<tr>
		<th>เงินได้รายเดือน</th>
		<th style="width:20%;">ต้นเงินกู้สามัญ คงหลือ</th>
		<th style="width:20%;"> ต้นเงินกู้เพื่อเหตุ  ฉุกเฉินคงเหลือ </th>
		<th style="width:20%;">จำกัดวงเงินกู้</th>
		<th style="width:20%;">จำกัดวงเงินกู้ คงเหลือ</th>
		</tr>
		</thead>
		<tbody>
		<tr>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		<td></td>
		</tr>
		</tbody>
		</table>
		';

	$html .= '
		<div style="padding-left:58px; margin-top:5px;">
		เสนอผู้มีอำนาจอนุมัติ
		</div>
		<div style="padding-left:98px;">
		1. &nbsp;ได้ตรวจคำขอกู้แล้ว &nbsp;&nbsp; ผู้กู้มีสิทธิ์กู้ได้........................................................................บาท
		</div>
		<div style="padding-left:98px;">
		4. &nbsp;เห็นควรอนุมัติ/ไม่อนุมัติ
		</div>
		<div style="padding-left:98px;">
		5. &nbsp;ข้อชี้แจงอื่น ๆ ...............................................................................................................
		</div>
		<div style="margin-top:30px; margin-right:22px; text-align:right;">
		(ลงชื่อ)...........................................................เจ้าหน้าที่สินเชื่อ
		</div>
		<div style="text-align:right; margin-right:102px; margin-top:5px;">
		(...........................................................)
		</div>  
		<div>
		อนุมัติ/ไม่อนุมัติ
		</div>
		<div style="margin-top:0px;">
		...........................................................ประธานกรรมการ/รองประธานกรรมการ/ผู้จัดการ
		</div>
		<div style="margin-left:0px; margin-top:5px;">
		(...........................................................)
		</div>  
		<hr>
		<div class="nowrap"  style="padding-left:98px; ">
		ข้าพเจ้าผู้กู้ มอบอํานาจให้.......................................................................ตำแหน่ง................................................
		</div>
		<div>
		สังกัด.........................................................เป็นผู้รับเงินกู้ตามหนังสือกู้แทนข้าพเจ้า
		</div>
		<div style="position:absolute;border: 1px solid black;top:690px;width: 270px;height: 100px;">
		<div style="font-weight:bold;text-decoration: underline;text-align:center;">
			คำเตือน
		</div>
		<div style="padding:5px">
			<div>
				ถ้าท่านมอบฉันทะให้ผู้อื่นมารับเงินแทน
			</div>
			<div>
				โปรดฝากบัตรประจำตัวของท่านไปกับผู้รับเงิน
			</div>
			<div>
				แทนท่านด้วยเพื่อแสดงต่อเจ้าหน้าที่
			</div>
		</div>
		</div>
		<div style="padding-left:322px; margin-top:20px;">
		................................................................ผู้กู้
		</div>
		<div style="padding-left:322px; margin-top:15px;">
		................................................................ผู้รับมอบอำนาจ
		</div>
		<div style="padding-left:322px; margin-top:15px;">
		................................................................พยาน
		</div>
		<div style="padding-left:322px; margin-top:15px;">
		................................................................พยาน
		</div>
		<div class="nowrap" style="padding-left:58px; margin-top:10px;">
		ข้าพเจ้า................................................................................... ได้รับเงินกู้ จำนวน.....................................................บาท
		</div>
		<div class="nowrap">
		(.................................................................................................) ไปเป็นการถูกต้องแล้ว ณ วันที่............/........................../...............
		</div>
		<div>
		หรือโอนผ่านธนาคาร...............................................................สาขา............................................................เลขที่..............................
		</div>
		<div>
		(กรณีโอนผ่านธนาคารผู้กู้ต้องลงลายมือชื่อรับเงินมาด้วย)
		</div>
		<div style="padding-left:322px; margin-top:10px;">
		................................................................ผู้รับเงิน
		</div>
		<div style="padding-left:322px; margin-top:10px;">
		(ต้องลงลายมือชื่อในการรับเงินต่อหน้าเจ้าหน้าที่สหกรณ์)
		</div>
		<div style="padding-left:240px; margin-top:10px;">
		จ่ายเงินถูกต้องแล้ว
		</div>
		<div style="padding-left:322px; margin-top:5px;">
		................................................................ผู้รับเงิน
		</div>
		<div style="font-weight:bold;position:absolute;top:203px;left:336px;">
		'.number_format($data["request_amt"],2).'
		</div>
		<div style="font-weight:bold;position:absolute;top:325px;left:55px;font-size:20px;">
		'.$data["salary_amount"].'
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
	$pathfile = __DIR__.'/../../resource/pdf/request_loan';
	if(!file_exists($pathfile)){
		mkdir($pathfile, 0777, true);
	}
	$pathfile = $pathfile.'/'.$data["requestdoc_no"].'.pdf';
	$pathfile_show = '/resource/pdf/request_loan/'.$data["requestdoc_no"].'.pdf';
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