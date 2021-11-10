<?php
use Dompdf\Dompdf;

function GeneratePDFContract($data,$lib) {
	$html = '
		    <!DOCTYPE html>
		    <head>
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
		
		    .piccenter {
				text-align:center;
				margin-top:-20px;
		    }
		    .box {
				width: 200px;
				border: 2px solid black;
				padding: 7px;
				margin-top:-110px;
				margin-left:480px;
				font-size:17px;
		    }
		    .center {
				text-align:center;
				font-size:21px;
		    }
		    .right {
				text-align:right;
				font-size:19px;
		    }
		    .left {
				font-size:19px;
		    }
		    .leftBehide {
				font-size:19px;
				margin-top:-20px;
				margin-left:140px;
		    }
		    .tabline {
				margin-left:48px;
		    }
		    .checkboxreport {
				width: 18px;
				height: 18px;
				border: 2px solid black;
				margin-left:108px;
		    }
		    .extend {
				font-size:17px;
				position:absolute;
				z-index:2;
		    }
		    #req_no {
				
				margin-top:161px;
				left:560px;
		    }
		    #date {
				margin-top:189px;
				left:550px;
		    }
		    #name {
				margin-top:281px;
				left:89px;
				text-align:center;
				width:190px;
		    }
			#card_person{
				margin-top:281px;
				left:405px;
			}
		    #member_no {
				margin-top:281px;
				left:600px;
		    }
		    #position {
				margin-top:307px;
				left:212px;
				width: 170px;
				text-align:center;
		    }
		    #work_at {
				margin-top:307px;
				left:520px;
				width: 170px;
				text-align:center;
		    }
		    #position_group {
				margin-top:333px;
				left:125px;
				
		    }
		    #salary {
				margin-top:333px;
				left:540px;
		    }
		     #loan_amt {
				margin-top:385px;
				left:300px;
			
		    }
		    #loan_amt_desc {
				margin-top:385px;
				left:470px;
		    }
		    #objective {
				margin-top:410px;
				left:190px;
		    }

		
		      #period_persen {
				margin-top:439px;
				left:435px;
				width:100px;
		    }
		    #namesecon {
				text-align:center;
				margin-top:667px;
				left:480px;
				width:190px;
		    }
		    #tel {
				margin-top:697px;
				left:540px;
		    }
		    #dept_no {
				margin-top:516px;
				left:593px;
			
		    }
			.nowrap{
				white-space: nowrap;
			  }
		    </style>
		    </head>
		    <body>
		
				<div class="extend" id="req_no"><b>'.$data["requestdoc_no"].'</b></div>
				<div class="extend" id="date"><b>'.$data["request_date"].'</b></div>
				<div class="extend" id="name"><b>'.$data["full_name"].'</b></div>
				<div class="extend" id="member_no"><b>'.$data["member_no"].'</b></div>
				<div class="extend" id="card_person"><b>'.$data["card_person"].'</b></div>
				<div class="extend" id="position"><b>'.$data["position"].'</b></div>
				<div class="extend" id="work_at"><b>'.$data["pos_group"].'</b></div>
				<div class="extend" id="position_group"><b>'.$data["district_desc"].'</b></div>
				<div class="extend" id="salary"><b>'.$data["salary_amount"].'</b></div>
				<div class="extend" id="loan_amt"><b>'.number_format($data["request_amt"],2).'</b></div>
				<div class="extend" id="loan_amt_desc"><b>'.$lib->baht_text($data["request_amt"]).'</b></div>
				<div class="extend" id="objective"><b>'.$data["objective"].'</b></div>
				<div class="extend" id="namesecon"><b>'.$data["name"].'</b></div>
				<div class="extend" id="tel"><b>'.$lib->formatphone($data["tel"]).'</b></div>
				<div class="extend" id="dept_no"><b>'.$data["dept_no"].'</b></div>
				<div class="piccenter" >
			<img src="../../resource/logo/logo.jpg" style="width:100px" >
		    </div>
		    <div class="box">
			หนังสือกู้ที่ ....................../..................<br>
			วันที่ ............./....................../..............<br>
			บัญชีเงินกู้ที่ .......................................
		    </div>
		    <br>
		         <div class="center"><b>ฉุกเฉินปันผลดำรงชีพออนไลน์</b><br><b>คำขอและหนังสือกู้เงินออนไลน์โครงการพิเศษเงินปันผลและเฉลี่ยคืน</b></div>
		    <div class="right">
			ใบคำขอเลขที่.................................................<br>
			วันที่................................................................<br>
		    </div>
		    <br>
		    <div class="left">
			เรียน&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;คณะกรรมการดำเนินการสหกรณ์ออมทรัพย์ครูกาญจนบุรี จำกัด<br><br>
			<div class="tabline nowrap" >
				ข้าพเจ้า.....................................................
				เลขประจำตัวประชาชน....................................
				สมาชิกเลขที่................................</div>
			<div class="nowrap">
				รับราชการหรือทำงานประจำในตำแหน่ง..................................................
				โรงเรียน/สถานที่ทำงาน.......................................................
			</div>
			<div class="nowrap">
				อำเภอ/หน่วยงาน...............................................................
				จังหวัดกาญจนบุรี ได้รับเงินเดือนจำนวน............................................บาท
			</div>
			ขอเสนอกู้ฉุกเฉินปันผลดำรงชีพออนไลน์ดังต่อไปนี<br>
			<div class="tabline">ข้อ 1. ข้าพเจ้าขอกู้เงินสหกรณ์ ฯ จำนวน.......................................บาท 
			(..............................................................................)</div>
			โดยจะนำไปใช้เพื่อการดังนี้ ................................................................................................................................................................
			<br>
			<div class="tabline">ข้อ 2. การชำระคืนหนี้เงินกู้ ข้าพเจ้ายินยอมชำระดอกเบี้ยในอัตราร้อยละ.............เป็นรายเดือนทุกสิ้นเดือน และงวดสุดท้าย</div>
			ชำระเงินต้นพร้อมดอกเบี้ยโดยหักจากเงินปันผลและเฉลี่ยคืนของปีที่อนุมัติให้กู้ และหรือส่งหัก ณ ที่จ่ายต้นสังกัด เงินต้นพร้อมดอกเบี้ย<br>
			ในเดือนถัดไป กรณีที่เงินปันผลและเงินเฉลี่ยคืนถูกบังคับคดี
			<div class="nowrap">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;การจ่ายเงินกู้ให้แก่ข้าพเจ้า ข้าพเจ้ายินยอมให้โอนเงินกู้เข้าบัญชีเงินฝากออมทรัพย์ ATM (เล่มฟ้า) เลขที...............................</div>
			ถือว่าเป็นอันเสร็จสิ้นสมบูรณ์ว่าข้าพเจ้าได้รับเงินกู้ไว้ถูกต้องแล้ว<br>
			<div style="letter-spacing:0.17" class="nowrap">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;ข้าพเจ้าผู้กู้ได้อ่านและรับทราบข้อกำหนดและเงื่อนไขในการใช้บริการขอกู้ฉุกเฉินออนไลน์ของสหกรณ์ตาม "ข้อกำหนด</div>
			<div style="letter-spacing:0.15" class="nowrap">และเงื่อนไขการใช้บริการขอกู้ฉุกเฉินออนไลน์ต่อท้ายสัญญา" ในวันที่ทำคำขอนี้แล้วและตกลงยินยอมผูกพัน และรับปฏิบัติตามข้อ </div>
			<div style="letter-spacing:0.15" class="nowrap">กำหนดและเงื่อนไขดังกล่าวหากข้าพเจ้าไม่ปฏิบัติตามข้อกำหนดและเงื่อนไขดังกล่าวจนเป็นเหตุ ให้เกิดความเสียหายใด ๆ ข้าพเจ้า</div> 
			<div>ยินยอมรับผิดชอบทั้งสิ้น โดยถือว่าข้าพเจ้าเป็นผู้ผิดสัญญาในการกู้ยืมเงินฉบับบนี้</div>
		   </div>
		    <div class="right">
			ลงชื่อ........................................................ผู้ขอกู้<br>
			ติดต่อ.................................................................<br>
		    </div>
		    <br>
		    <div class="left">
			<b>สำหรับเจ้าหน้าที่ :: </b> ยอดอนุมัติ.............................................บาท&nbsp;&nbsp;&nbsp;  
			หักหนี้เดิมชำระ.......................................................บาท
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			คงเหลือจ่ายจริง........................................บาท
			&nbsp;&nbsp;&nbsp;ทำรายการเมื่อวันที่............./........................../...............
		    </div>
		 
		    <div class="left" style="margin-top: 15px;">
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>ตรวจสอบสิทธิ์การให้เงินกู้เรียบร้อยแล้ว</b>
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>จ่ายเงินถูกต้องแล้ว</b>
			.......................................................เจ้าหน้าที่
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			.......................................................เจ้าหน้าที่การเงิน
			.......................................................
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			.........................................................
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>เห็นควรอนุมัติ</b>
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>อนุมัติ</b>
			......................................................หัวหน้าสินเชื่อ/รองผู้จัดการ
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			......................................................ผู้จัดการ
		    </div>
		    </body>
		    </html>
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