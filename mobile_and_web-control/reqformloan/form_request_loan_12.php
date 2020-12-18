<?php
use Dompdf\Dompdf;

$dompdf = new DOMPDF();
function GeneratePDFContract($data,$lib) {
	$html = '<!DOCTYPE html>
            <head>
            <style>
			@font-face {
			  font-family: THSarabun;
			  src: url(../../resource/fonts/THSarabun.ttf);
			}
			@font-face {
				font-family: "THSarabun";
				src: url(../../resource/fonts/THSarabun Bold.ttf);
				font-weight: bold;
			}
			* {
			  font-family: THSarabun;
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
				margin-top:182px;
				left:560px;
		    }
		    #date {
				margin-top:215px;
				left:550px;
		    }
            #name {
				margin-top:292px;
				left:150px;
		    }
		    #member_no {
				margin-top:292px;
				left:550px;
		    }
            #position {
				margin-top:322px;
				left:240px;
				width: 100px;
		    }
		    #work_at {
				margin-top:322px;
				left:520px;
				width: 250px;
		    }
		    #position_group {
				margin-top:352px;
				left:125px;
		    }
		    #salary {
				margin-top:352px;
				left:540px;
		    }
		    #loan_amt {
				margin-top:412px;
				left:320px;
		    }
		    #loan_amt_desc {
				margin-top:412px;
				left:480px;
		    }
		    #objective {
				margin-top:441px;
				left:230px;
		    }
            #period_payment {
				margin-top:462px;
				left:540px;
		    }
		    #period {
				margin-top:493px;
				left:220px;
				width:100px;
		    }
            #endmonth {
				margin-top:522px;
				left:140px;
		    }
		    #namesecon {
				margin-top:650px;
				left:540px;
		    }
		    #tel {
				margin-top:679px;
				left:540px;
		    }
		    #dept_no {
				margin-top:790px;
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
            <div class="extend" id="namesecon"><b>'.$data["name"].'</b></div>
		    <div class="extend" id="tel"><b>'.$lib->formatphone($data["tel"]).'</b></div>
		    <div class="extend" id="dept_no"><b>'.$data["dept_no"].'</b></div>
            <div class="piccenter" >
                <img src="../../resource/logo/logo.jpg" style="width:120px" >
            </div>
            <div class="box">
                หนังสือกู้ที่ ....................../..................<br>
                วันที่ ............./....................../............<br>
                บัญชีเงินกู้ที่ .......................................
            </div>
            <br>
            <div class="center"><b>ฉุกเฉินปันผลดำรงชีพ</b><br><b>คำขอและหนังสือกู้เงินออนไลน์โครงการพิเศษเงินปันผลและเฉลี่ยคืน</b></div>
            <div class="right">
                ใบคำขอเลขที่.................................................<br>
                วันที่................................................................<br>
            </div>
            <br>
            <div class="left">
                เรียน&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;คณะกรรมการดำเนินการสหกรณ์ออมทรัพย์ครูกาญจนบุรี จำกัด<br>
                <div class="tabline">ข้าพเจ้า...........................................................................................
                สมาชิกเลขทะเบียนที่........................................................</div>
                รับราชการหรือทำงานประจำในตำแหน่ง......................................................
                โรงเรียน/สถานที่ทำงาน....................................................
                อำเภอ/หน่วยงาน..............................................................
                จังหวัดกาญจนบุรี ได้รับเงินเดือนจำนวน...............................................บาท
                ขอเสนอคำขอกู้เงินฉุกเฉินปันผลดำรงชีพออนไลน์ดังต่อไปนี้<br>
                <div class="tabline">ข้อ 1. ข้าพเจ้าขอกู้เงินตามโครงการนี้ จำนวน..........................................บาท 
                (..................................................................)</div>
                โดยจะนำไปใช้เพื่อการดังนี้ .....................................................................................................................................................................
                <br>
                <div class="tabline">ข้อ 2. ถ้าข้าพเจ้าได้รับเงินกู้ ข้าพเจ้ายินยอมชำระดอกเบี้ยเป็นรายเดือนทุกสิ้นเดือน และงวดสุดท้ายชำระเงินต้นพร้อม</div>
                ดอกเบี้ยโดยหักจากเงินปันผลและเฉลี่ยคืนของปีที่อนุมัติให้กู้&nbsp;หรือส่งหัก ณ ที่จ่ายต้นสังกัดเงินต้นพร้อมดอกเบี้ยในคราวถัดไป<br>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;ข้าพเจ้าผู้กู้ได้อ่านและรับทราบข้อกำหนดและเงื่อนไขในการใช้บริการขอกู้ฉุกเฉินปันผลดำรงชีพออนไลน์ของสหกรณ์ตาม "ข้อกำหนดและเงือนไขในการใช้บริการขอกู้ฉุกเฉินปันผลดำรงชีพออนไลน์ต่อท้ายสัญญา" ในวันที่ทำคำขอนี้แล้วและตกลงยินยอม
		ผูกพันและรับปฏิบัติตามข้อ กำหนดและเงื่อนไขดังกล่าวหากข้าพเจ้าไม่ปฏิบัติตามข้อกำหนดและเงื่อนไขดังกล่าวจนเป็นเหตุ ให้เกิดความเสียหายใด ๆ ข้าพเจ้า ยินยอมรับผิดชอบทั้งสิ้น
            </div>
            <div class="right">
                ลงชื่อ........................................................ผู้ขอกู้<br>
                ติดต่อ.................................................................<br>
            </div>
            <br>
            <div class="left">
                <b>สำหรับเจ้าหน้าที่ :: </b> ยอดอนุมัติ.....................................................บาท&nbsp;&nbsp;&nbsp;  
                หักหนี้เดิมชำระ..............................................................บาท
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                คงเหลือจ่ายจริง............................................บาท
                &nbsp;&nbsp;&nbsp;ทำรายการเมื่อวันที่.............../............................../...............
            </div>
            <div class="checkboxreport"></div>
            <div class="leftBehide">
                โอนเข้าบัญชีเงินฝากออมทรัพย์ ATM (088 เล่มฟ้า) เลขที่...........................................................................
            </div>
            <br>
            <div class="left">
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>ตรวจสอบสิทธิ์การให้เงินกู้เรียบร้อยแล้ว</b>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>จ่ายเงินถูกต้องแล้ว</b>
                ..............................................................เจ้าหน้าที่
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                ..............................................................เจ้าหน้าที่การเงิน
                ...............................................................
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                ..............................................................
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>เห็นควรอนุมัติ</b>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>อนุมัติ</b>
                ..............................................................หัวหน้าสินเชื่อ/รองผู้จัดการ
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                ..............................................................ผู้จัดการ
            </div>
            </body>
            </html>';
	$dompdf = new DOMPDF();
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