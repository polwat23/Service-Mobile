<?php
use Dompdf\Dompdf;

function GeneratePDFContract($data,$lib) {
	$arr_pay_month = explode(" ", $data["pay_date"]);
	$pay_month = $arr_pay_month[1];
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

	//ส่วนหัว
	$html .= '
		<div style=" text-align: center; margin-top:5px;"><img src="../../resource/logo/logo.jpg" alt="" width="80" height="0"></div>
		<div style="border:0.5px solid #000000; width:160px; position:absolute; right:0px; top:0px; padding:5px; 5px ">
			<div>
				หนังสือกู้ที่............./..............
			</div>
			<div>
				วันที่......../............./..............
			</div>
			<div>
				บัญชีเงินกู้ที่..........................         
			</div>
		</div>
		<div style="font-size: 22px;font-weight: bold; text-align:center; margin-center:30px; height: 45px; margin-top:10px;">คำขอและหนังสือกู้หุ้นออนไลน์</div>
		<div style="text-align:right">
			เลขที่............................................
		</div>
		<div style="text-align:right">
			วันที่.............................................
		</div>
		<div style="display:flex; height:30px">
			<div>เรียน</div>
			<div style="margin-left:40px; ">คณะกรรมการดำเนินการสหกรณ์ออมทรัพย์ครูกาญจบุรี จำกัด</div>
		</div>';

	// detail data
	$html.='<div style="position:absolute; right:0px; top:138px; width:150px; ">
				  '.$data["requestdoc_no"].'
			</div>
			<div style="position:absolute; right:0px; top:163px; width:150px; ">
				 '.$data["request_date"].'
			</div>
			<div style="position:absolute; left:95px; top:234px; width:170px; text-align:center;">
				'.$data["full_name"].'
			</div>
			<div style="position:absolute; left:400px; top:234px; width:110px; text-align:center;">
			  '.$data["card_person"].'
			</div>
			<div style="position:absolute; right:0px; top:234px; width:120px; text-align:center;">
			  '.$data["member_no"].'
			</div>
			<div style="position:absolute; left:240px; top:258px; width:200px; text-align:center;">
				'.$data["position"].'
			</div>
			<div style="position:absolute; right:5px; top:258px; width:130px; text-align:center;">
				'.$data["pos_group"].'
			</div>
			<div style="position:absolute; left:95px; top:284px; width:200px; text-align:center;">
				'.$data["district_desc"].'
			</div>
			<div style="position:absolute; right:23px; top:284px; width:130px; text-align:center;">
				 '.$data["salary_amount"].'
			</div>
			<div style="position:absolute; left:230px; top:348px; width:130px;  text-align:center;">
				'.number_format($data["request_amt"],2).'
			</div>
			<div style="position:absolute; right:0px; top:348px; width:310px;  text-align:center;  ">
				'.$lib->baht_text($data["request_amt"]).'
			</div>
			<div style="position:absolute; right:0px; top:374px; width:550px; ">
				'.$data["objective"].'
			</div>
			<div style="position:absolute; right:0px; top:413px; width:320px;">
				'.$data["option_pay"].'
			</div>
			<div style="position:absolute; left:40px; top:437px; width:135px;  text-align:center;">
				'.$data["period_payment"].'
			</div>
			<div style="position:absolute; left:390px; top:438px; width:90px;  text-align:center;">
				'.$data["period"].'
			</div>
			 <div style="position:absolute; left:392px; top:464px; width:90px;  text-align:center;">
				'.$pay_month.'
			</div>
	';

	$html .= '
		<div class="nowrap" style="padding-left:50px; margin-top: 15px;">
			ข้าพเจ้า...............................................เลขที่ประจำตัวประชาชน...............................สมาชิกเลขที่.................................
		</div>
		<div class="nowrap">
			รับราชการหรือทำงานหรือทำงานประจำตำแหน่ง...............................................โรงเรียน/สถานที่ทำงาน....................................
		</div>
		<div class="nowrap">
			อำเภอ/หน่วยงาน.......................................................จังหวัดกาญจบุรี ได้รับเงินได้รายเดือน จำนวน....................................บาท
		</div>
		<div>
			ขอเสนอกู้หุ้นออนไลน์ดังต่อไปนี้
		</div>
		<div style="margin-left: 50px; margin-top:15px" class="nowrap">
			ข้อ 1. ข้าพเจ้าขอกู้เงินหุ้น จำนวน...................................บาท (.....................................................................................)
		</div>
		<div  class="nowrap" >
			โดยจะนำไปใช้เพื่อการดังนี้.........................................................................................................................................................
		</div>
		<div style="margin-left: 50px; margin-top:15px" class="nowrap">
			ข้อ 2. ถ้าข้าพเจ้าได้รับเงินกู้ ข้าพเจ้าขอส่งเงินกู้คืน เป็นแบบ..........................................................................................
		</div>
		<div class="nowrap" style="letter-spacing:0.39">
			งวดล่ะ.................................บาท พร้อมด้วยดอกเบี้ยเป็นจำนวน.......................งวด (ตามระเบียบสหกรณ์ ฯ กำหนด)
		</div>
		<div style="letter-spacing:-0.2" >
			โดยยินยอมให้สหกรณ์หักเงิน ณ ที่จ่าย เริ่มส่งคืนเงินกู้ตั้งแต่ภายในสิ้นเดือน............................เป็นต้นไป
		</div>
		<div style="margin-left: 50px; letter-spacing:-0.03" class="nowrap">
			ข้าพเจ้าผู้กู้ได้อ่านและรับทราบข้อกําหนดและเงื่อนไขในการใช้บริการขอกู้หุ้นออนไลน์ของสหกรณ์ตาม “ข้อกําหนด และ
		</div>
		<div style="letter-spacing:-0.18" class="nowrap">
		เงื่อนไขการใช้บริการขอกู้หุ้นออนไลน์ต่อท้ายสัญญา” ในวันที่ทําคําขอนี้แล้วและตกลงยินยอมผูกพันและรับปฏิบัติตาม ข้อกําหนดและ
		</div>
		<div style="letter-spacing:-0.25" class="nowrap">
			เงื่อนไขดังกล่าวหากข้าพเจ้าไม่ปฏิบัติตามข้อกําหนดและเงื่อนไขดังกล่าวจนเป็นเหตุให้เกิดความเสียหายใด ๆ ข้าพเจ้า ยินยอมรับผิดชอบ
		</div>
		<div>
			ทั้งสิ้น โดยถือว่าข้าพเจ้าเป็นผู้ผิดสัญญาในการกู้ยืมเงินฉบับนี้
		</div>
		<div style="text-align:right;">
			ลงชื่อ.........................................................ผู้ขอกู้
		</div>
		<div style="text-align:right; margin-right:29px;">
			ติดต่อ.........................................................
		</div>
		<div style="margin-left:50px; letter-spacing:-0.18" class="nowrap">
			ในการจ่ายเงินกู้ให้แก่ข้าพเจ้า ข้าพเจ้ายินยอมให้โอนเงินกู้เข้าบัญชีเงินฝากออมทรัพย์ ATM (เล่มฟ้า) เลขที่.............................
		</div>
		<div>
			ถือว่าเป็นอันเสร็จสิ้นสมบูรณ์ ว่าข้าพเจ้าได้รับเงินกู้ไว้ถูกต้องแล้ว
		</div>
		<div style="text-align:right;">
			ลงชื่อ....................................................ผู้กู้/ผู้รับเงิน
		</div>
		<div>&nbsp;</div>
		<div style="display:flex; height:30px; ">
			<div style="font-weight:bold;">สำหรับเจ้าหน้าที่ ::</div>
			<div style="margin-left:123px;">ยอดอนุมัติ....................................................บาท</div>
			<div style="margin-left:418px;">หักหนี้เดิม........................................................บาท</div>
		</div>
		<div style="display:flex; height:30px; ">
			<div style="margin-left:123px;">คงเหลือจ่าย..................................................บาท</div>
			<div style="margin-left:418px;">ทำรายการเมื่อวันที่................................................</div>
		</div>

		<div style="margin-top:20px;">
			<div style="display:flex; height:30px;">
				  <div style="margin-left:15px; font-weight:bold;">
					ตรวจสอบสิทธิ์การให้เงินกู้เรียบร้อยแล้ว 
				  </div>
				  <div style="margin-left:451px; font-weight:bold;">
					จ่ายเงินถูกต้องแล้ว 
				  </div>
			</div>
		</div>
		<div style="margin-top:15px;">
			<div style="display:flex; height:30px;">
				  <div>........................................................เจ้าหน้าที่สินเชื่อ</div>
				  <div style="margin-left:418px" class="nowrap">....................................................เจ้าหน้าที่การเงิน</div>
			</div>
		</div>
		<div>
			<div style="display:flex; height:30px;">
				<div>........................................................</div>
				<div style="margin-left:418px" class="nowrap">....................................................</div>
			</div>
		</div>
		<div style="margin-top:20px;">
			<div style="display:flex; height:30px;">
				<div style="margin-left:55px; font-weight:bold;">
					เห็นควรอนุมัติ 
				</div>
				<div style="margin-left:491px; font-weight:bold;">
					อนุมัติ 
				</div>
			</div>
		</div>
		<div>
			<div style="display:flex; height:30px;">
				<div>........................................................หัวหน้าสินเชื่อ/รองผู้จัดการ</div>
				<div style="margin-left:418px" class="nowrap">....................................................ผู้จัดการ</div>
			</div>
		</div>
	';
	$html .= '</div>';
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