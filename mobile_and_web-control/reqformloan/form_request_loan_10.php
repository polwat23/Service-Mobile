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
				margin-top:-30px;
		    }
		    .box {
				width: 200px;
				border: 2px solid black;
				padding: 7px;
				margin-top:-110px;
				margin-left:480px;
				font-size:18px;
		    }
		    .center {
				text-align:center;
				font-size:22px;
		    }
		    .right {
				text-align:right;
				font-size:20px;
		    }
		    .left {
				font-size:20px;
		    }
		    .leftBehide {
				font-size:20px;
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
				font-size:18px;
				position:absolute;
				z-index:2;
		    }
		    #req_no {
				margin-top:140px;
				left:560px;
		    }
		    #date {
				margin-top:167px;
				left:550px;
		    }
		    #name {
				margin-top:264px;
				left:150px;
		    }
		    #member_no {
				margin-top:264px;
				left:550px;
		    }
		    #position {
				margin-top:293px;
				left:240px;
				width: 100px;
		    }
		    #work_at {
				margin-top:293px;
				left:520px;
				width: 250px;
		    }
		    #position_group {
				margin-top:320px;
				left:125px;
		    }
		    #salary {
				margin-top:320px;
				left:540px;
		    }
		    #loan_amt {
				margin-top:373px;
				left:320px;
		    }
		    #loan_amt_desc {
				margin-top:373px;
				left:470px;
		    }
		    #objective {
				margin-top:400px;
				left:230px;
		    }
		    #period_payment {
				margin-top:428px;
				left:540px;
		    }
		    #period {
				margin-top:457px;
				left:220px;
				width:100px;
		    }
		    #endmonth {
				margin-top:482px;
				left:140px;
		    }
		    #namesecon {
				margin-top:619px;
				left:540px;
		    }
		    #tel {
				margin-top:645px;
				left:540px;
		    }
		    #dept_no {
				margin-top:749px;
				left:520px;
		    }
		    </style>
		    </head>
		    <body>
		    <div class="extend" id="req_no"><b>'.$data["requestdoc_no"].'</b></div>
		    <div class="extend" id="date"><b>'.$data["request_date"].'</b></div>
		    <div class="extend" id="name"><b>'.$data["full_name"].'</b></div>
		    <div class="extend" id="member_no"><b>'.$data["member_no"].'</b></div>
		    <div class="extend" id="position"><b>'.$data["position"].'</b></div>
		    <div class="extend" id="work_at"><b>'.$data["pos_group"].'</b></div>
		    <div class="extend" id="position_group"><b>'.$data["district_desc"].'</b></div>
		    <div class="extend" id="salary"><b>'.$data["salary_amount"].'</b></div>
		    <div class="extend" id="loan_amt"><b>'.number_format($data["request_amt"],2).'</b></div>
		    <div class="extend" id="loan_amt_desc"><b>'.$lib->baht_text($data["request_amt"]).'</b></div>
		    <div class="extend" id="objective"><b>'.$data["objective"].'</b></div>
		    <div class="extend" id="period_payment"><b>'.number_format($data["period_payment"],2).'</b></div>
		    <div class="extend" id="period"><b>'.$data["period"].'</b></div>
		    <div class="extend" id="endmonth"><b>'.$data["pay_date"].'</b></div>
		    <div class="extend" id="namesecon"><b>'.$data["name"].'</b></div>
		    <div class="extend" id="tel"><b>'.$lib->formatphone($data["tel"]).'</b></div>
		    <div class="extend" id="dept_no"><b>'.$data["dept_no"].'</b></div>
		    <div class="piccenter" >
			<img src="../../resource/logo/logo.jpg" style="width:120px" >
		    </div>
		    <div class="box">
			หนังสือกู้ที่ ....................../..................<br>
			วันที่ ............./....................../..............<br>
			บัญชีเงินกู้ที่ .......................................
		    </div>
		    <br>
		    <div class="center"><b>คำขอและหนังสือกู้เงินเพื่อเหตุฉุกเฉินออนไลน์</b></div>
		    <div class="right">
			ใบคำขอเลขที่.................................................<br>
			วันที่................................................................<br>
		    </div>
		    <br>
		    <div class="left">
			เรียน&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;คณะกรรมการดำเนินการสหกรณ์ออมทรัพย์ครูกาญจนบุรี จำกัด<br><br>
			<div class="tabline">ข้าพเจ้า.................................................................................
			สมาชิกเลขทะเบียนที่.................................................</div>
			รับราชการหรือทำงานประจำในตำแหน่ง.............................................
			โรงเรียน/สถานที่ทำงาน..............................................
			อำเภอ/หน่วยงาน......................................................
			จังหวัดกาญจนบุรี ได้รับเงินเดือนจำนวน......................................บาท
			ขอเสนอคำขอกู้เงินเพื่อเหตุฉุกเฉินออนไลน์ดังต่อไปนี้<br>
			<div class="tabline">ข้อ 1. ข้าพเจ้าขอกู้เงินสหกรณ์ ฯ จำนวน.......................................บาท 
			(.................................................................)</div>
			โดยจะนำไปใช้เพื่อการดังนี้ .................................................................................................................................................
			<br>
			<div class="tabline">ข้อ 2. ถ้าข้าพเจ้าได้รับเงินกู้ ข้าพเจ้าขอส่งเงินกู้คืน เป็นงวดรายเดือนเท่ากันงวดละ...............................................บาท</div>
			พร้อมด้วยดอกเบี้ยเป็นจำนวน...............................................งวด&nbsp; (ตามระเบียบที่สหกรณ์ ฯ กำหนด) &nbsp;โดยเริ่มส่งคืนเงินกู้ตั้งแต่
			ภายในสิ้นเดือน .................................................................. เป็นต้นไป<br>
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;ข้าพเจ้าผู้กู้ได้อ่านและรับทราบข้อกำหนดและเงื่อนไขในการใช้บริการขอกู้ฉุกเฉินออนไลน์ของสหกรณ์ตาม "ข้อกำหนด และเงื่อนไขการใช้บริการขอกู้ฉุกเฉินออนไลน์ต่อท้ายสัญญา"
			ในวันที่ทำคำขอนี้แล้วและตกลงยินยอมผูกพัน และรับปฏิบัติตามข้อ กำหนดและเงื่อนไขดังกล่าวหากข้าพเจ้าไม่ปฏิบัติตามข้อกำหนดและเงื่อนไขดังกล่าวจนเป็นเหตุ ให้เกิดความเสียหายใด ๆ ข้าพเจ้า ยินยอมรับผิดชอบทั้งสิ้น
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
		    <div class="checkboxreport"></div>
		    <div class="leftBehide">
			โอนเข้าบัญชีเงินฝากออมทรัพย์ ATM (เล่มฟ้า) เลขที่...........................................................................
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