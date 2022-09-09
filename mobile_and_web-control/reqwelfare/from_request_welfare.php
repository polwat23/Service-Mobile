<?php
use Dompdf\Dompdf;

$dompdf = new DOMPDF();
function GeneratePDFWelfare($data,$lib) {
//demo Data
$data["เลขประจำตัวผู้สม้คร"] = $data["member_no"];
$data["วันที่"] = date('d');
$data["เดือน"] = (explode(' ',$lib->convertdate(date("Y-m-d"),"d M Y")))[1];
$data["year"] = (date('Y') + 543);
$data["คำนำหน้า"] = $data["prename_desc"]; 
$data["memb_name"] =  $data["memb_name"];
$data["memb_surname"] = $data["memb_surname"];
$data["fulname"] = 'นางสาวจันทรมณี สุดารัตน์';


$data["เลขที่สมาชิก"] = $data["member_no"];
$data["หน่วยงาน"] = $data["membgroup_desc"]; 
$data["มือถือ"] = $data["memb_tel"];

$data["คำนำหน้าผู้ขอทุน"] = 'นาย'; 
$data["ชื่อผู้ขอทุน"] = $data["assist_name"];
$data["assist_lastname"] = $data["assist_lastname"]; 
$data["อายุผู้ขอทุน"] = $data["age"];  
$data["ระดับการศึกษา"] = $data["education_level"];  
$data["ชื่อสถานศึกษา"] = $data["academy_name"];   
$data["เกรดเฉลี่ย"] = $data[""];   
$data["ชื่อบิดา"] = $data["father_name"];  
$data["บิดาเป็นสมาชิก"] = false;
$data["ชื่อมารดา"] = $data["mother_name"]; 
$data["มารดาเป็นสมาชิก"] = true;



$mr = null;
$mrs = null;
$miss = null;
$mr_r = null;
$mrs_r = null;
$miss_r = null;
$girl_r = null;
$masster_r = null;

$primary = null;
$junior_high_school = null;
$senior_high_school = null;
$vocational_certificate = null;
$high_vocational_certificate = null;
$Bachelor_degree = null;

$mother_member = null;
$mother_not_member = null;
$father_member = null;
$father_not_member = null;




if ($data["คำนำหน้า"] == 'นาย') {
	$mr = 'border';
} else if ($data["คำนำหน้า"] == 'นาง') {
	$mrs = 'border';
} else if ($data["คำนำหน้า"] == 'นางสาว') {
	$miss = 'border';
}

/*if ($data["คำนำหน้าผู้ขอทุน"] == 'นาย') {
	$mr_r = 'border';
} else if ($data["คำนำหน้าผู้ขอทุน"] == 'นางสาว') {
	$miss_r = 'border';
} else if ($data["คำนำหน้าผู้ขอทุน"] == 'ด.ช') {
	$masster_r = 'border';
} else if ($data["คำนำหน้าผู้ขอทุน"] == 'ด.ญ') {
	$girl_r = 'border';
}
*/

if ($data["ระดับการศึกษา"] == '01') {
	$primary = 'checked';
} else if ($data["ระดับการศึกษา"] == '03') {
	$junior_high_school = 'checked';
} else if ($data["ระดับการศึกษา"] == '05') {
	$senior_high_school = 'checked';
} else if ($data["ระดับการศึกษา"] == '02') {
	$vocational_certificate = 'checked';
} else if ($data["ระดับการศึกษา"] == '04') {
	$high_vocational_certificate = 'checked';
} else if ($data["ระดับการศึกษา"] == '06') {
	$Bachelor_degree = 'checked';
}


/*if ($data["บิดาเป็นสมาชิก"] == true) {
	$father_member = 'checked';
} else {
	$father_not_member = 'checked';
}

if ($data["มารดาเป็นสมาชิก"] == true) {
	$mother_member = 'checked';
} else {
	$mother_not_member = 'checked';
}*/

// $arrData[0]["เงินเดือน"] ="28,000.00";
// $arrData[0]["จำนวนเงินที่ขอกู้"] ="300,000.00";
// $arrData[0]["หักหนี้เดิม"] ="200,000.00";
// $arrData[0]["คงเหลือ"] ="100,000.00";

// $arrData[1]["เงินเดือน"] ="28,000.00";
// $arrData[1]["จำนวนเงินที่ขอกู้"] ="300,000.00";
// $arrData[1]["หักหนี้เดิม"] ="200,000.00";
// $arrData[1]["คงเหลือ"] ="100,000.00";



// $groupData["ข้อมูลขอกู้"]= $arrData;
$html = '
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Document</title>
</head>
<body>
	<meta charset="UTF-8">
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
			  font-size: 16pt;
			  line-height: 24px;
			}
			div{
				line-height: 24px;
				font-size: 16pt;
			}
			.nowrap{
				white-space: nowrap;
			  }
			.center{
				text-align:center;
			}
			.left{
				text-align:left;
			}
			.right{
				text-align:right;
			}
			.flex{
				display:flex;
			}
			.bold{
				font-weight:bold;
			}
			.list{
				padding-left:50px;
			}
			.sub-list{
				padding-left:100px;
			}
			th{
				border:1px solid;
				text-align:center;
				padding-bottom:5px;
			}
			td{
				line-height:20px;
				padding:5px;
			}
			.absolute{
				position:absolute;
			  }
			.data{
				font-size:15pt;
				margin-top:-2px;
			}
			.border{
				border: 1px solid;
			}
			.border-left{
				border-left: 1px solid;
			}
			.border-right{
				border-right: 1px solid;
			}
			.border-top{
				border-top: 1px solid;
			}
			.border-bottom
				border-bottom: 1px solid;
			}
			.wrapper-page {
				page-break-after: always;
			  }
			  .spac{
				margin-top:15px;
			  }
			  .tab{
				padding-left:15px;
			  }
			  
			.wrapper-page:last-child {
			page-break-after: avoid;
			}
			.inline{
				display:inline;
			}
			</style>';
//ขนาด
$html .= '<div  style="margin:-10px 0px -30px 10px;" >';
//หน้า 1
$html .= '<div class="wrapper-page">';
//ส่วนหัว
$html .= '
	<div>
		<div class="absolute" style="margin-left:90;"><div class="data nowrap center" style="width: 120px;">' . ($data["เลขประจำตัวผู้สม้คร"] ?? null) . '</div></div>
		เลขประจำตัวผู้สมัคร..............................
	</div>
	<div style=" text-align: center; margin-top:10px;"><img src="../../resource/logo/logo.jpg" alt="" width="100" height="0"></div>
	<div class="center bold" style="margin-top:20px;">แบบแสดงความจำนงขอรับทุนส่งเสริมการศึกษาบุตรสมาชิก ประจำปี ' . ($data["year"] ?? (date("Y") + 543)) . '</div>
	<div class="right spac">
		<div class="absolute" style="margin-left:270;"><div class="data nowrap center" style="width: 120px;">' . ($data["วันที่"] ?? null) . '</div></div>
		<div class="absolute" style="margin-left:335;"><div class="data nowrap center" style="width: 150px;">' . ($data["เดือน"] ?? null) . '</div></div>
		<div class="absolute" style="margin-left:455;"><div class="data nowrap center">' . ($data["year"] ?? null) . '</div></div>
		วันที่..............เดือน......................................พ.ศ...................
	</div>
	<div class="spac">เรียน&nbsp; &nbsp;ประธานกรรมการสหกรณ์ออมทรัพย์กรมการแพทย์ จำกัด </div>
	<div class ="spac nowrap"  style="padding-left:75px; letter-spacing:0.7px; ">
		ด้วยข้าพเจ้าประสงค์จะสมัครขอรับทุนส่งเสริมการศึกษาบุตรสมาชิก ประจำปี 2565 จึงขอแจ้ง
	</div>
	<div>
		รายละเอียดของข้าพเจ้า เพื่อประกอบการพิจารณา ดังต่อไปนี้
	</div>
	<div class="flex" style=" height:30px;">
		<div>1. ข้าพเจ้า</div>
		<div class="' . $mr . '" style="margin-left:65px;  width:30px; border-radius:10px;">นาย</div>
		<div style="margin-left:95px;">/</div>
		<div class="' . $mrs . '" style="margin-left:100px;  width:30px; border-radius:10px;">นาง</div>
		<div style="margin-left:130px">/</div>
		<div class="' . $miss . '" style="margin-left:140px;  width:50px; border-radius:10px;" >นางสาว</div>
		<div style="margin-left:190px;">
			<div class="absolute" style="margin-left:0;"><div class="data nowrap center" style="width: 225px;">' . ($data["memb_name"] ?? null) . '</div></div>
			<div class="absolute" style="margin-left:210;"><div class="data nowrap center " style="width: 225px;">' . ($data["memb_surname"] ?? null) . '</div></div>
			...........................................................นามสกุล.........................................................
		</div>
		
	</div>
	<div class="tab">
		<div class="absolute" style="margin-left:55;"><div class="data nowrap center" style="width: 90px;">' . ($data["เลขที่สมาชิก"] ?? null) . '</div></div>
		<div class="absolute" style="margin-left:170;"><div class="data nowrap center" style="width: 295px;">' . ($data["หน่วยงาน"] ?? null) . '</div></div>
		<div class="absolute" style="margin-left:410;"><div class="data nowrap center" style="">' . ($data["มือถือ"] ?? null) . '</div></div>
		สมาชิกเลขที่........................หน่วยงาน.............................................................................มือถือ..............................
	</div>
	<div class="tab" >
		มีความประสงค์ขอรับทุนส่งเสริมการศึกษาบุตรสมาชิก
	</div>
	<div class="flex" style=" height:30px;">
		<div>2. ให้กับ </div>
		<div class="' . $masster_r . '" style="margin-left:53px;  width:25px; border-radius:10px;">ด.ช</div>
		<div style="margin-left:85px;">/</div>
		<div class="' . $girl_r . '" style="margin-left:90px;  width:25px; border-radius:10px;">ด.ญ</div>
		<div style="margin-left:120px;">/</div>
		<div class="' . $mr_r . '" style="margin-left:125px;  width:25px; border-radius:10px;">นาย</div>
		<div style="margin-left:155px;">/</div>
		<div class="' . $miss_r . '"  style="margin-left:160px;  width:50px; border-radius:10px;">นางสาว</div>
		<div  class="nowrap"style="margin-left:211px;">
		<div class="absolute" style="margin-left:0;"><div class="data nowrap center" style="width: 180px;">' . ($data["ชื่อผู้ขอทุน"] ?? null) . '</div></div>
		<div class="absolute" style="margin-left:180;"><div class="data nowrap center" style="width: 170px;">' . ($data["assist_lastname"] ?? null) . '</div></div>
		<div class="absolute" style="margin-left:328;"><div class="data nowrap center" style="width: 30px;">' . ($data["อายุผู้ขอทุน"] ?? null) . '</div></div>
		................................................นามสกุล........................................... อายุ.........ปี
		</div>
	</div>
	<div>
			กำลังศึกษาอยู่ในระดับ
			<div style="position:absolute; top:-2px; margin-left:11px;"> <input type="checkbox" style="margin-top:6px; margin-right:10px;"' . ($primary ?? null) . ' >ประถมศึกษา</div>
			<div style="position:absolute; top:-2px; margin-left:211px;"> <input type="checkbox" style="margin-top:6px; margin-right:10px;" ' . ($vocational_certificate ?? null) . '>ประกาศศนียบัตรวิชาชีพ (ปวช.)</div>
	</div>
	<div>
		&nbsp;<div style="position:absolute; top:-2px; margin-left:145px;"> <input type="checkbox" style="margin-top:6px; margin-right:10px;" ' . ($junior_high_school ?? null) . ' >มัธยมศึกษาตอนต้น</div>
		&nbsp;<div style="position:absolute; top:-2px; margin-left:340px;"> <input type="checkbox" style="margin-top:6px; margin-right:10px;" ' . ($high_vocational_certificate ?? null) . ' >ประกาศศนียบัตรวิชาชีพชั้นสูง (ปวส.)</div>
	</div>
	<div>
		&nbsp;<div style="position:absolute; top:-2px; margin-left:145px;"> <input type="checkbox" style="margin-top:6px; margin-right:10px;" ' . ($senior_high_school ?? null) . '>มัธยมศึกษาตอนปลาย</div>
		&nbsp;<div style="position:absolute; top:-2px; margin-left:340px;"> <input type="checkbox" style="margin-top:6px; margin-right:10px;" ' . ($Bachelor_degree ?? null) . '>ปริญาญาตรี</div>
	</div>
	<div class="tab">
		<div class="absolute" style="margin-left:65;"><div class="data nowrap center" style="width: 400px;">' . ($data["ชื่อสถานศึกษา"] ?? null) . '</div></div>
		<div class="absolute" style="margin-left:410;"><div class="data nowrap center" style="width: 130px;">' . ($data["เกรดเฉลี่ย"] ?? null) . '</div></div>

		ชื่อสถานศึกษา.......................................................................................................เกรดเฉลี่ย.................................
	</div>
	<div>
		<div style="position:absolute; top:-2px; margin-left:470px;">  <input type="checkbox" style="margin-top:6px; margin-right:10px;" ' . $father_member . '>เป็นสมาชิก</div>
		<div style="position:absolute; top:-2px; margin-left:580px;">  <input type="checkbox" style="margin-top:6px; margin-right:10px;" ' . $father_not_member . '>ไม่เป็นสมาชิก</div>
		<div class="absolute" style="margin-left:45;"><div class="data nowrap center" style="width: 400px;">' . ($data["ชื่อบิดา"] ?? null) . '</div></div>
	 	4. ชื่อบิดา........................................................................................................
		 
	</div>
	<div class="tab">
		<div style="position:absolute; top:-2px; margin-left:455px;">  <input type="checkbox" style="margin-top:6px; margin-right:10px;" '.$mother_member.'>เป็นสมาชิก</div>
		<div style="position:absolute; top:-2px; margin-left:565px;">  <input type="checkbox" style="margin-top:6px; margin-right:10px;" '.$mother_not_member.'>ไม่เป็นสมาชิก</div>
		<div class="absolute" style="margin-left:45;"><div class="data nowrap center" style="width: 385px;">' . ($data["ชื่อมารดา"] ?? null) . '</div></div>
	 		ชื่อมารดา....................................................................................................
	</div>
	<div>
		5. ได้แนบหลักฐาน ซึ่ง<u>รับรองสำเนาถูกต้อง</u>ประกอบแบบแสดงความจำนง ดังนี้
	</div>
	<div class="list">1) สำเนาทะเบียนบ้านของบุตรที่ขอรับทุน</div>
	<div class="list">2) สำเนาผลการศึกษาของปีการศึกษา ' . ($data["year"] ?? (date("Y") + 543)) . ' หรือหนังสือรับรองผลการศึกษาปี ' . ($data["year"] ?? (date("Y") + 543)) . ' <u>ของบุตรที่ขอรับทุน</u></div>
	<div class="list">3) สำเนาหน้าสมุดบัญชีธนาคารกรุงไทย จำกัด หรือ ธนาคารไทยพาณิชย์ ประเภทออมทรัพย์ <u>ของสมาชิก</u></div>
	<div class="bold" style="padding-left:70px;">หากข้าพเจ้าแนบหลักฐานไม่ครบถ้วน หรือไม่ถูกต้องตามที่กำหนดให้ถือว่าข้าพเจ้าสละสิทธิ์ขอรับ</div>
	<div class="bold">ทุนส่งเสริมการศึกษาบุตรสมาชิก ประจำปี ' . ($data["year"] ?? (date("Y") + 543)) . '</div>
	<div style="padding-left:70px;">ข้าพเจ้าขอรับรองว่า ข้อความดังกล่าวข้างต้นเป็นความจริงทุกประการ</div>
	<div class="right" style="margin-top:40px;">ลงชื่อ......................................................สมาชิกผู้ขอรับทุน</div>
	<div style="margin-left:376px;">
		<div class="absolute" style="margin-left:0;"><div class="data nowrap center" style="width: 215px;">' . ($data["fulname"] ?? null) . '</div></div>
		(......................................................)
	</div>
    <div style="height:90px;">
			<b>หมายเหตุ : </b>
			<div style="position:absolute;">
				1. สมาชิก 1 คน สามารถขอรับทุนได้เพียง 1 ทุน
				<div>2. กรณี สามี/ภรรยา เป็นสมาชิกทั้ง 2 คน แต่มีบุตรเพียง 1 คน ให้ส่งขอรับทุนได้ตามสิทธิ</div>
				<div class="tab">แต่บุตรจะได้รับเพียง 1 ทุนเท่านั้น ถ้ามีบุตรมากกว่า 1 คน ให้ยื่นได้ตามคุณสมบัติข้อ 1.</div>
			</div>
	</div>
	<div class="bold" style="padding-left:70px;"><u>ยื่นแบบแสดงความจำนงขอรับทุนได้ตั้งแต่วันที่ 18 เมมายน - 31 พฤษภาคม 2565</u></div>
	';




$html .= '</div>';
//ปิดหน้า


$html .= '</div>';
$html .= '
			</body>
			</html>
';


$dompdf = new Dompdf([
	'fontDir' => realpath('../../resource/fonts'),
	'chroot' => realpath('/'),
	'isRemoteEnabled' => true
]);

$dompdf->load_html($html);
$dompdf->render();
$pathfile = __DIR__.'/../../resource/pdf/request_assist';
if(!file_exists($pathfile)){
	mkdir($pathfile, 0777, true);
}

$pathfile = $pathfile.'/'.$data["assist_docno"].'.pdf';
$pathfile_show = '/resource/pdf/request_assist/'.$data["assist_docno"].'.pdf';
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
