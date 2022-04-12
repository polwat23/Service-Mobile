<?php
use Dompdf\Dompdf;

$dompdf = new DOMPDF();
function GeneratePDFContract($data,$lib) {
	//demo Data
// ออมทรัพย์ธรรมดาเลขที่
// ช่องทางการโอน
$data["ช่องทางการโอน"] = '';
$data["บัญชีสหกรณ์"] = '';

$data["รับที่"] = '';
$data["วันที่"] = '';
$data["หนังสือกู้ที่"] = $data["requestdoc_no"];

$data["เลขที่ออมทรัพย์ธรรมดา"] = '';
$data["เลขที่ออมทรัพย์พิเศษ"] = '';
$data["ธนาคาร"] = '';
$data["เลขที่บัญชีธนาคาร"] = '';
$data["สาขา"] = '';
$data["เลขที่บัญชี"] = '';


$data["เขียนที่"] = 'แอพพลิเคชัน RID Saving';
$data["สมาชิกเลขทะเบียนที่1"] = $data["member_no"];;
$data["date2"] = $lib->convertdate(date("Y-m-d"),"d M Y");
$data["ชื่อ"] = $data["full_name"];
$data["อาชีพ"] = '';
$data["อายุ"] = '';
$data["อื่นๆ"] = '';
$data["วันที่บรรจุ"] = '';
$data["สถานะภาพ"] = '';
$data["ตำแหน่ง"] = $data["position"];
$data["สังกัด"] = $data["membgroup_desc"];
$data["โทรศัพท์"] = $data["addr_phone"];
$data["เงินได้รายเดือน"] = $data["salary_amount"]; 
$data["วันเดือนปีเกิด"] = $lib->convertdate($data["birth_date"],"d M Y"); 
$data["เกษียณอายุดราชการปี"] = $data["retry_date"]; 
$data["รวมอายุราชการ"] = '';
$data["มีความประสงค์ขอรับ"] = '';
$data["บำเหน็จรายเดือน"] = '';
$data["บ้านเลขที่"] = $data["addr_no"]; 
$data["หมู่ที่"] = $data["addr_moo"]; 
$data["ซอย"] = $data["addr_soi"]; 
$data["ถนน"] = $data["addr_road"]; 
$data["ตำบล"] = $data["tambol_desc"]; 
$data["อำเภอ"] = $data["district_desc"]; 
$data["จังหวัด"] = $data["province_desc"]; 
$data["รหัสไปรษณีย์"] = $data["addr_postcode"]; 
$data["จำนวนเงินกู้"] = number_format($data["request_amt"],2);
$data["จำนวนเงินกู้คำอ่าน"] = $lib->baht_text($data["request_amt"]);
$data["วัตถประสงค์"] = 'อื่นๆ';
$data["วัตถประสงค์อื่นๆ"] = $data["objective"];
//$data["มีหนี้สินที่ต้องหัก"] = 'อื่นๆ';
//$data["หนี้สินที่ต้องหักอื่นๆ"] = 'เงินกู้พิเศษ';
$data["งวดล่ะ"] =  number_format($data["period_payment"],2);
$data["จำนวนงวด"] = $data["period"]; 
$data["หุ้น"] = $data["share_bf"];
//$data["ประจำเดือน"] = 'พฤศจิกายน';
//$data["ประจำพ.ศ."] = '2565';
//$data["หลักประกันในวงเงิน"] = '200,000.00';
//$data["หลักประกัน"] = 'อื่นๆ';
//$data["หลักประกันอื่นๆ"] = "อสิงหาริมทรัพย์";
//$data["ยินยอมทำประกันชีวิตกลุ่ม"] = true;
//$data["ยินยอมทำประกันชีวิตกลุ่มในวงเงิน"] = '10,000.00';
//$data["หนังสือบอกกล่าวทวงถามให้ส่งให้กับข้าพเจ้า ณ"] = "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s";



//ผู้ค้ำ
/*$guarantee[0]["ลำดับที่"] = "1";
$guarantee[0]["ชื่อ"] = "นายร่ำรวยรุ่งเรือง เจริญทรัพย์";
$guarantee[0]["เลขที่สมาชิก"] = "000451";
$guarantee[0]["สังกัด"] = "โรงเรียนดีเด่น";
$guarantee[0]["เงินได้รายเดือน"] = "40,000.00";
$guarantee[1]["ลำดับที่"] = "2";
$guarantee[1]["ชื่อ"] = "นางณัฐกาล ไซวงค์";
$guarantee[1]["เลขที่สมาชิก"] = "000501";
$guarantee[1]["สังกัด"] = "โรงเรียนดีเด่น";
$guarantee[1]["เงินได้รายเดือน"] = "30,000.00";*/

//ช่องทางรับเงิน
if (($data["ช่องทางการโอน"] ?? '-') == 'โอนเงินเข้าบัญชีเงินฝากสหกรณ์') {
	$internal  = 'checked';
	//บัญชีสกรณ์ 
	if (($data["บัญชีสหกรณ์"] ?? '-') == 'ออมทรัพย์ธรรมดา') {
		$acc_nomal  = 'checked';
	} else if (($data["บัญชีสหกรณ์"] ?? '-') == 'ออมทรัพย์พิเศษ') {
		$acc_ex = 'checked';
	}
} else if (($data["ช่องทางการโอน"] ?? '-') == 'โอนเงินเข้าบัญชีเงินฝากธนาคาร') {
	$external  = 'checked';
}


//อาชีพ
if (($data["อาชีพ"] ?? '-') == 'ข้าราชการ') {
	$government = 'checked';
} else if (($data["อาชีพ"] ?? '-') == 'ลูกจ้างประจำ') {
	$permanent_employee = 'checked';
} else if (($data["อาชีพ"] ?? '-') == 'เจ้าหน้าที่ สอ.ชป.') {
	$sa_cp = 'checked';
} else if (($data["อาชีพ"] ?? '-') == 'อื่นๆ') {
	$other = 'checked';
}
//สถานะ

if (($data["สถานะภาพ"] ?? '-') == 'โสด') {
	$single = 'checked';
} else if (($data["สถานะภาพ"] ?? '-') == 'สมรส') {
	$marry = 'checked';
} else if (($data["สถานะภาพ"] ?? '-') == 'หย่า') {
	$divorce = 'checked';
} else if (($data["สถานะภาพ"] ?? '-') == 'หม้าย') {
	$widow = 'checked';
}
// มีความประสงค์ขอรับ
if (($data["มีความประสงค์ขอรับ"] ?? '-') == 'บำเหน็จ') {
	$reward = 'checked';
} else if (($data["มีความประสงค์ขอรับ"] ?? '-') == 'บำนาญ') {
	$pension = 'checked';
} else if (($data["มีความประสงค์ขอรับ"] ?? '-') == 'บำเหน็จรายเดือน') {
	$monthly_reward = 'checked';
}
//วัตถุประสงค์การกู้
if (($data["วัตถประสงค์"] ?? '-') == 'ชำระหนี้') {
	$pay_off_debt = 'checked';
} else if (($data["วัตถประสงค์"] ?? '-') == 'ซ่อมแซมบ้าน') {
	$home_repair = 'checked';
} else if (($data["วัตถประสงค์"] ?? '-') == 'การศึกษาบุตร') {
	$children_education = 'checked';
} else if (($data["วัตถประสงค์"] ?? '-') == 'ลงทุน') {
	$commit = 'checked';
} else if (($data["วัตถประสงค์"] ?? '-') == 'อื่นๆ') {
	$objective_other = 'checked';
}

//มีหนี้สินที่ต้องหัก
if (($data["มีหนี้สินที่ต้องหัก"] ?? '-') == 'เงินกู้ฉุกเฉิน') {
	$emergency_loan = 'checked';
} else if (($data["มีหนี้สินที่ต้องหัก"] ?? '-') == 'เงินกู้สามัญ') {
	$ordinary_loan = 'checked';
} else if (($data["มีหนี้สินที่ต้องหัก"] ?? '-') == 'เงินกู้เพื่อการเคหะจากธนาคารอาคารสงเคราะห์') {
	$housing = 'checked';
} else if (($data["มีหนี้สินที่ต้องหัก"] ?? '-') == 'อื่นๆ') {
	$re_other = 'checked';
}

//หลักค้ำประกัน
if (($data["หลักประกัน"] ?? '-') == 'บุคคลค้ำประกัน') {
	$people_guarantee = 'checked';
} else if (($data["หลักประกัน"] ?? '-') == 'สมุดบัญชีเงินฝากจำนำเป็นประกัน') {
	$acc_guarantee = 'checked';
} else if (($data["หลักประกัน"] ?? '-') == 'อื่นๆ') {
	$other_guarantee = 'checked';
}
//ยินยอมทำประกันชีวิตกลุ่ม
if (($data["ยินยอมทำประกันชีวิตกลุ่ม"] ?? '-') == true) {
	$agree_group = 'checked';
} else {
	$not_agree_group = 'checked';
}





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
			  font-size: 14pt;
			  line-height: 19px;
			}
			div{
				line-height: 21px;
				font-size: 14pt;
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
				margin-left:70px;
			}
			.sub-list{
				text-indent:90px;
			}
			th{
				border:1px solid;
				text-align:center;
				padding:15px 5px;
				line-height:18px;
				font-weight:normal;
			}
			td{
				line-height:14px;
				padding:2px 5px;
				border:1px solid;
				font-size:12pt;
			}
			.absolute{
				position:absolute;
			}
			.relative{
				position:relative;
			  }
			.data{
				font-size:13pt;
				margin-top:-2px;
			}
			.border{
				border: 1px solid;
			}
			.wrapper-page {
				page-break-after: always;
			}
			  
			.wrapper-page:last-child {
				page-break-after: avoid;
			}
			.underline-double {
				text-decoration-line: underline;
				text-decoration-style: double;
			}
			.padding-content{
				padding-left:4px;
				line-height:22px;
			}
			.radio{
				margin-top:4px;
			}
			.checkbox{
				margin-top:5px;
			}
			.content{
				padding-left:5px; 
				padding-right:5px;
			}
			.desc{
				line-height:18px;
				font-size:11pt;
			}
			.box{
				width:15px;
				height:15px;
				border:1px solid;
			}
			</style>';
//ระยะขอบกระดาษ
$html .= '<div style="margin:-20px -15px;">';

//ส่วนหัว


//เริ่มหน้าที่ 1  
$html .= '<div class="wrapper-page">';
$html .= '
	<div class="right" style="margin-right:50px;">แบบสอ.ชป. 009/2/5</div>
	<div class="border" style="padding:0px 5px 0px 5px">
		<div>
			<div class="absolute" style="margin-left:-5px;">
				<div style="border-right:1px solid; border-bottom:1px solid; width:270px; padding:5px; ">
					<div style="height:20px;">
						<div class="absolute">
							<input class="checkbox" type="checkbox" style="margin-left:5px;"' . ($internal ?? null) . '>
							<div class="absolute" style="margin-left:2px; font-size:13pt;">โอนเงินเข้าบัญชีเงินฝากสหกรณ์</div>
						</div>
					</div>
					<div style="height:20px; margin-left:25px;">
						<div class="absolute" style="margin-left:150px; "><div class="data nowrap">' . ($data["เลขที่ออมทรัพย์ธรรมดา"] ?? null) . '</div></div>
						<div class="absolute">
							<input class="checkbox" type="checkbox" style="margin-left:5px;"' . ($acc_nomal ?? null) . ' >
							<div class="absolute" style="margin-left:2px; font-size:13pt;">ออมทรัพย์ธรรมดา เลขที่............................</div>
						</div>
					</div>
					<div style="height:20px; margin-left:25px;">
						<div class="absolute" style="margin-left:150px; "><div class="data nowrap">' . ($data["เลขที่ออมทรัพย์พิเศษ"] ?? null) . '</div></div>
						<div class="absolute">
							<input class="checkbox" type="checkbox" style="margin-left:5px;" ' . ($acc_ex ?? null) . '>
							<div class="absolute" style="margin-left:2px; font-size:13pt;">ออมทรัพย์พิเศษ เลขที่................................</div>
						</div>
					</div>
					<div style="height:20px;">
						<div class="absolute" style="margin-left:190px; "><div class="data nowrap">' . ($data["ธนาคาร"] ?? null) . '</div></div>
						<div class="absolute">
							<input class="checkbox" type="checkbox" style="margin-left:5px;" ' . ($external ?? null) . '>
							<div class="absolute" style="margin-left:2px; font-size:13pt;">โอนเงินเข้าบัญชีเงินฝากธนาคาร.........................</div>
						</div>
					</div>
					<div>
						<div class="absolute" style="margin-left:30px; "><div class="data nowrap">' . ($data["สาขา"] ?? null) . '</div></div>
						<div class="absolute" style="margin-left:183px; "><div class="data nowrap">' . ($data["เลขที่บัญชีธนาคาร"] ?? null) . '</div></div>

						สาขา............................เลขที่บัญชี.........................
					</div>
				</div>
			</div>
			<div class="absolute" style="right:-15px;">
				<div style="border-left:1px solid; border-bottom:1px solid; width:255px; padding:15px 10px; height:83px; ">
					<div class="nowrap" style="font-size:13pt;">
						<div class="absolute" style="margin-left:25px; "><div class="data nowrap">' . ($data["รับที่"] ?? null) . '</div></div>
						<div class="absolute" style="margin-left:145px; "><div class="data nowrap">' . ($data["วันที่"] ?? null) . '</div></div>
						รับที่...........................&nbsp;&nbsp;&nbsp; วันที่.................................
					</div>
					<div style="font-size:13pt;">
						<div class="absolute" style="margin-left:65px; "><div class="data nowrap">' . ($data["หนังสือกู้ที่"] ?? null) . '</div></div>
						 หนังสือกู้ที่ ................................. 
					</div>
					<div style="font-size:13pt; margin-left:30px;">
						<div class="absolute" style="margin-left:35px; "><div class="data nowrap">' . ($data["วันที่"] ?? null) . '</div></div>
						วันที่........................................
					</div>
				</div>
			</div>
			<div>
				<div style=" text-align: center; margin-top:30px;"><img src="../../resource/logo/logo.jpg" alt="" width="80" height="0"></div>
				<div class="bold center" style=" margin-top:15px;">สหกรณ์ออมทรัพย์กรมชลประทาน จำกัด</div>
				<div class="bold center" >หนังสือขอกู้และสัญญากู้เงินสามัญ</div>
			</div>
			<div >
				<div class="absolute" style="margin-left:510px; "><div class="data nowrap">' . ($data["เขียนที่"] ?? null) . '</div></div>
				<div class="right" style="margin-right:60px">เขียนที่.................................................</div>
			</div>
			<div style="margin-left:310px;">
				<div class="absolute" style="margin-left:30px; "><div class="data nowrap">' . ($data["date2"] ?? null) . '</div></div>
				วันที่.....................................................
			</div>
			<div class="list">
				<div class="box absolute" style="margin-left:400px; top:6px;"></div>
				<div class="box absolute" style="margin-left:415px; top:6px;"></div>
				<div class="box absolute" style="margin-left:430px; top:6px;"></div>
				<div class="box absolute" style="margin-left:445px; top:6px;"></div>
				<div class="box absolute" style="margin-left:460px; top:6px;"></div>
				<div class="box absolute" style="margin-left:475px; top:6px;"></div>

				<div class="box absolute" style="margin-left:490px; top:6px;"></div>
				<div class="box absolute" style="margin-left:505px; top:6px;"></div>
				<div class="box absolute" style="margin-left:520px; top:6px;"></div>
				<div class="box absolute" style="margin-left:535px; top:6px;"></div>
				<div class="box absolute" style="margin-left:550px; top:6px;"></div>
				<div class="box absolute" style="margin-left:565px; top:6px;"></div>
				<div class="absolute" style="margin-left:45px; "><div class="data nowrap center" style="width:240px;">' . ($data["ชื่อ"] ?? null) . '</div></div>
				<div class="absolute" style="margin-left:405px; letter-spacing:8px;">' . ($data["สมาชิกเลขทะเบียนที่1"] ?? null) . '</div>
				<div class="absolute" style="margin-left:620px; "><div class="data nowrap">' . ($data["อายุ"] ?? null) . '</div></div>
				<div class="absolute" style="margin-left:595px;">อายุ.......ปี</div>
				<div>ข้าพเจ้า........................................................................ สมาชิกเลขทะเบียนที่ </div>
			</div>
	
			<div style="height:23px;">
				<div class="absolute">วันที่บรรจุเป็น</div>
				<div class="absolute" style="margin-left:85px; font-size:13pt" >
					<input  type="checkbox" style="margin-left:3px; margin-top:4px;"' . ($government ?? null) . '>
					<div class="absolute" style="margin-left:2px;">ข้าราชการ</div>
				</div>
				<div class="absolute" style="margin-left:175px; font-size:13pt" >
					<input  type="checkbox" style="margin-left:3px; margin-top:4px;"' . ($permanent_employee ?? null) . '>
					<div class="absolute" style="margin-left:2px;">ลูกจ้างประจำ</div>
				</div>
				<div class="absolute" style="margin-left:280px; font-size:13pt" >
					<input  type="checkbox" style="margin-left:3px; margin-top:4px;" ' . ($sa_cp ?? null) . '>
					<div class="absolute" style="margin-left:2px;">เจ้าหน้าที่ สอ.ชป.</div>
				</div>
				<div class="absolute" style="margin-left:400px; font-size:13pt" >
					<input  type="checkbox" style="margin-left:3px; margin-top:4px;"' . ($other ?? null) . '>
					<div class="absolute data" style="margin-left:35px;">' . ($data["อื่นๆ"] ?? null) . '</div>
					<div class="absolute data" style="margin-left:175px;">' . ($data["วันที่บรรจุ"] ?? null) . '</div>
					<div class="absolute" style="margin-left:2px;">อื่นๆ............................เมื่อวันที่.....................................</div>
				</div>
			</div>
			<div style="height:23px;">
				<div class="absolute">สถานะภาพ</div>
				<div class="absolute" style="margin-left:65px; font-size:13pt" >
					<input  type="checkbox" style="margin-left:3px; margin-top:4px;"' . ($single ?? null) . '>
					<div class="absolute" style="margin-left:2px;">โสด</div>
				</div>
				<div class="absolute" style="margin-left:115px; font-size:13pt" >
					<input  type="checkbox" style="margin-left:3px; margin-top:4px; "' . ($marry ?? null) . '>
					<div class="absolute" style="margin-left:2px;">สมรส</div>
				</div>
				<div class="absolute" style="margin-left:180px; font-size:13pt" >
					<input  type="checkbox" style="margin-left:3px; margin-top:4px; "' . ($divorce ?? null) . '>
					<div class="absolute" style="margin-left:2px;">หย่า</div>
				</div>
				<div class="absolute" style="margin-left:230px; font-size:13pt" >
					<input  type="checkbox" style="margin-left:3px; margin-top:4px; " ' . ($widow ?? null) . '>
					<div class="absolute" style="margin-left:2px;">หม้าย</div>
				</div>
				<div class="absolute" style="margin-left:290px; font-size:13pt" >
					<div class="absolute data center" style="margin-left:40px; width:145px;">' . ($data["ตำแหน่ง"] ?? null) . '</div>
					<div class="absolute data center" style="margin-left:218px; width:215px;">' . ($data["สังกัด"] ?? null) . '</div>

					<div class="absolute">ตำแหน่ง..........................................สังกัด...............................................................</div>
				</div>
			</div>
			<div class="nowrap">
				<div class="absolute" style="margin-left:50px; "><div class="data nowrap">' . ($data["โทรศัพท์"] ?? null) . '</div></div>
				<div class="absolute" style="margin-left:231px; "><div class="data nowrap center" style="width:95px;">' . ($data["เงินได้รายเดือน"] ?? null) . '</div></div>
				<div class="absolute" style="margin-left:435px; "><div class="data nowrap">' . ($data["วันเดือนปีเกิด"] ?? null) . '</div></div>
				<div class="absolute" style="margin-left:685px; "><div class="data nowrap">' . ($data["เกษียณอายุดราชการปี"] ?? null) . '</div></div>
				โทรศัพท์.......................ได้รับเงินได้รายเดือน............................บาท&nbsp;วัน/เดือน/ปีเกิด..................................เกษียณอายุราชการปี พ.ศ............
			</div>
			<div style="height:23px;">
				<div class="absolute" style="margin-left:100px; "><div class="data nowrap">' . ($data["รวมอายุราชการ"] ?? null) . '</div></div>
				<div class="absolute" style="margin-left:610px; "><div class="data nowrap">' . ($data["บำเหน็จรายเดือน"] ?? null) . '</div></div>
				<div class="absolute" >รวมอายุราชการ...............ปี มีความประสงค์ขอรับ</div>
				<div class="absolute" style="margin-left:280px; font-size:13pt" >
					<input  type="checkbox" style="margin-left:3px; margin-top:4px; " ' . ($reward ?? null) . '>
					<div class="absolute" style="margin-left:2px;">บำเหน็จ</div>
				</div>
				<div class="absolute" style="margin-left:360px; font-size:13pt" >
					<input  type="checkbox" style="margin-left:3px; margin-top:4px; " ' . ($pension ?? null) . '>
					<div class="absolute" style="margin-left:2px;">บำนาญ</div>
				</div>
				<div class="absolute" style="margin-left:435px; font-size:13pt" >
					<input  type="checkbox" style="margin-left:3px; margin-top:4px; " ' . ($monthly_reward ?? null) . '>
					<div class="absolute" style="margin-left:2px;">บำเหน็จรายเดือน เดือนล่ะ....................................</div>
				</div>
			</div>
			<div class="nowrap" style="height:23px";>
				<div class="absolute" style="margin-left:210px; "><div class="data nowrap">' . ($data["บ้านเลขที่"] ?? null) . '</div></div>
				<div class="absolute" style="margin-left:305px; "><div class="data nowrap">' . ($data["หมู่ที่"] ?? null) . '</div></div>
				<div class="absolute" style="margin-left:375px; "><div class="data nowrap">' . ($data["ซอย"] ?? null) . '</div></div>
				<div class="absolute" style="margin-left:480px; "><div class="data nowrap">' . ($data["ถนน"] ?? null) . '</div></div>
				ที่อยู่ปัจจุบันสำหรับติดต่อ&nbsp;&nbsp;&nbsp;&nbsp;บ้านเลขที่.................&nbsp;&nbsp;&nbsp;หมู่ที่..........&nbsp;&nbsp;&nbsp;ซอย....................&nbsp;&nbsp;&nbsp;ถนน..........................................................................
			</div>
			<div class="nowrap"  style="height:23px">
				<div class="absolute" style="margin-left:65px;"><div class="data nowrap  center" style="width:135px;">' . ($data["ตำบล"] ?? null) . '</div></div>
				<div class="absolute" style="margin-left:260px;"><div class="data nowrap  center" style="width:150px;">' . ($data["อำเภอ"] ?? null) . '</div></div>
				<div class="absolute" style="margin-left:450px;"><div class="data nowrap  center" style="width:140px;">' . ($data["จังหวัด"] ?? null) . '</div></div>
				<div class="absolute" style="margin-left:665px; "><div class="data nowrap">' . ($data["รหัสไปรษณีย์"] ?? null) . '</div></div>
				ตำบล/แขวง......................................... อำเภอ/เขต............................................. จังหวัด....................................... รหัสไปรษณีย์.................
			</div>
			<div class="nowrap"  style="height:23px">
				<div class="absolute" style="margin-left:260px; "><div class="data nowrap">' . ($data["หุ้น"] ?? null) . '</div></div>
				โทรศัพท์.....................&nbsp;&nbsp;ปัจจุบันมียอดทุนเรือนหุ้น.....................บาท&nbsp;&nbsp;ขอเสนอคำขอกู้เงินสามัญ เพื่อโปรดพิจารณาดังต่อไปนี้
			</div>
			<div class="list nowrap">
				<div class="absolute" style="margin-left:465px;"><div class="data nowrap  center" style="width:160px;">' . ($data["จำนวนเงินกู้"] ?? null) . '</div></div>
				1.&nbsp;&nbsp;ข้าพเจ้ากู้เงินสามัญจากสหกรณ์ออมทรัพย์กรมชลประทาน จำกัด (สอ.ชป.) จำนวนเงิน................................................บาท
			</div>
			<div class="nowrap"  style="height:23px">
				<div class="absolute" style="margin-left:30px;"><div class="data nowrap center" style="width:160px;">' . ($data["จำนวนเงินกู้คำอ่าน"] ?? null) . '</div></div>
				<div class="absolute">(.....................................................................) &nbsp;&nbsp;&nbsp;เพื่อนำไปใช้ในการ</div>
				<div class="absolute" style="margin-left:375px; font-size:13pt" >
					<input  type="checkbox" style="margin-left:3px; margin-top:4px;" ' . ($pay_off_debt ?? null) . '>
					<div class="absolute" style="margin-left:2px;">ชำระหนี้</div>
				</div>
				<div class="absolute" style="margin-left:465px; font-size:13pt" >
					<input  type="checkbox" style="margin-left:3px; margin-top:4px;"' . ($home_repair ?? null) . '>
					<div class="absolute" style="margin-left:2px;">ซ่อมแซมบ้าน</div>
				</div>
				<div class="absolute" style="margin-left:565px; font-size:13pt" >
					<input  type="checkbox" style="margin-left:3px; margin-top:4px;" ' . ($children_education ?? null) . '>
					<div class="absolute" style="margin-left:2px;">การศึกษาบุตร</div>
				</div>
			</div>
			<div class="nowrap" style="height:23px;">
				<div class="absolute" style="margin-left:110px;"><div class="data nowrap" style="width:160px;">' . ($data["วัตถประสงค์อื่นๆ"] ?? null) . '</div></div>
				<div class="absolute" style="margin-left:0px; font-size:13pt" >
					<input  type="checkbox" style="margin-left:3px; margin-top:4px;" ' . ($commit ?? null) . '>
					<div class="absolute" style="margin-left:2px;">ลงทุน</div>
				</div>
				<div class="absolute" style="margin-left:60px; font-size:13pt" >
					<input  type="checkbox" style="margin-left:3px; margin-top:4px;" ' . ($objective_other ?? null) . '>
					<div class="absolute" style="margin-left:2px;">อื่นๆ..........................................................</div>
				</div>
			</div>
			<div class="list nowrap">
				<u class="bold" style="font-size:13pt; letter-spacing:-0.2px">ในกรณีที่ข้าพเจ้าไม่สามารถกู้เงินได้ตามจํานวนเงินดังกล่าว ยินยอมให้ สอ.ชป. ปรับลดยอดเงินกู้ให้ตรงตามสิทธิของข้าพเจ้าได้</u>
			</div>
			<div class="list nowrap">
				ข้อ 2.&nbsp;&nbsp;ข้าพเจ้ามีหนี้สินที่ต้องหักจากเงินได้รายเดือนในฐานะผู้กู้ ดังต่อไปนี้ 
			</div>
			<div class="nowrap" style="height:23px;" >
				<div class="absolute" style="margin-left:550px;"><div class="data nowrap " >' . ($data["หนี้สินที่ต้องหักอื่นๆ"] ?? null) . '</div></div>
				<div class="absolute" style="margin-left:0px; font-size:13pt" >
					<input  type="checkbox" style="margin-left:3px; margin-top:4px; " ' . ($emergency_loan ?? null) . '>
					<div class="absolute" style="margin-left:2px;">เงินกู้ฉุกเฉิน</div>
				</div>
				<div class="absolute" style="margin-left:100px; font-size:13pt" >
					<input  type="checkbox" style="margin-left:3px; margin-top:4px; " ' . ($ordinary_loan ?? null) . '>
					<div class="absolute" style="margin-left:2px;">เงินกู้สามัญ</div>
				</div>
				<div class="absolute" style="margin-left:200px; font-size:13pt" >
					<input  type="checkbox" style="margin-left:3px; margin-top:4px; " ' . ($housing ?? null) . '>
					<div class="absolute" style="margin-left:2px;"> เงินกู้เพื่อการเคหะจากธนาคารอาคารสงเคราะห์</div>
				</div>
				<div class="absolute" style="margin-left:490px; font-size:13pt" >
					<input  type="checkbox" style="margin-left:3px; margin-top:4px; " ' . ($re_other ?? null) . '>
					<div class="absolute" style="margin-left:2px;">อื่น ๆ....................................................</div>
				</div>
			</div>
			<div class="list nowrap">
				<div class="absolute" style="margin-left:377px;"><div class="data nowrap  center" style="width:73px;" >' . ($data["งวดล่ะ"] ?? null) . '</div></div>
				ข้อ 3.&nbsp;&nbsp;ข้าพเจ้าสัญญาว่าจะชําระต้นเงินกู้เป็นงวครายเดือนเท่ากัน งวดละ......................บาท พร้อมด้วยดอกเบี้ยในอัตราตามที่ 
			</div>
			<div>
				<div class="absolute" style="margin-left:267px;"><div class="data nowrap  center" style="width:63px;" >' . ($data["จำนวนงวด"] ?? null) . '</div></div>
				<div class="nowrap" style="letter-spacing:0.1px;">กําหนดไว้ในระเบียบและประกาศของ สอ.ชป. รวม....................งวด หนี้ส่วนที่เหลือชําระเสร็จสิ้นภายในกําหนดอายุสัญญา ทั้งนี้ตั้งแต่งวด</div>
			</div>
			<div >
				<div class="absolute" style="margin-left:65px;"><div class="data nowrap  center" style="width:95px;" >' . ($data["ประจำเดือน"] ?? null) . '</div></div>
				<div class="absolute" style="margin-left:190px;"><div class="data nowrap" >' . ($data["ประจำพ.ศ."] ?? null) . '</div></div>

				<div style="letter-spacing:0.1px;">ประจําเดือน...........................พ.ศ................ เป็นต้นไป และหากข้าพเจ้าไม่ได้ระบุจํานวนงวดผ่อนชําระ ให้ถือว่าข้าพเจ้าตกลงและยินยอม</div>
			</div>
			<div>
				ผ่อนชําระต้นเงินกู้และดอกเบี้ยตามจํานวนงวดสูงสุดที่ สอ.ชป. กําหนดให้ผ่อนชําระได้ 
			</div>
			<div>
				<div class="list nowrap" style="letter-spacing:-0.1px;" ><u>ในกรณี สอ.ชป. ปรับอัตราดอกเบี้ยเงินกู้ ข้าพเจ้ายินยอมให้ สอ.ชป. เพิ่มหรือลดอัตราดอกเบี้ยได้ทันทีโดย สอ.ชป. ไม่จําเป็นต้อง </u></div>
			</div>
			<div><u>แจ้งให้ข้าพเจ้าทราบล่วงหน้า</u></div>
			<div class="list nowrap" style="letter-spacing:-0.05px;">
				ข้อ 4.&nbsp;&nbsp;ข้าพเจ้ายินยอมให้ สอ.ชป.หักดอกเบี้ยตั้งแต่วันที่ได้รับเงินกู้ ดอกเบี้ยเงินกู้ให้คิดตั้งแต่วันที่ได้รับเงินกู้จนถึงวันสิ้นเดือนนั้น 
			</div>
			<div class="list nowrap" style="letter-spacing:-0.08px;">
				ข้อ 5.&nbsp;&nbsp;ข้าพเจ้ายินยอมให้ผู้บังคับบัญชา หรือเจ้าหน้าที่ผู้จ่ายเงินได้รายเดือนของข้าพเจ้าหักเงินได้รายเดือนของข้าพเจ้า เพื่อชําระ 
			</div>
			<div>หนี้แก่ สอ.ชป.</div>
			<div class="list nowrap" style="letter-spacing:0.1px;">
				ข้อ 6.&nbsp;&nbsp;หากข้าพเจ้าทําผิดข้อบังคับ สอ.ชป. อันเกี่ยวกับการควบคุมหลักประกันและการเรียกคืนเงินกู้ไม่ว่ากรณีใด ๆ ข้าพเจ้า
			</div>
			<div>เงินกู้นี้เป็นอันถึงกําหนดส่งคืนโดยสิ้นเชิง พร้อมทั้งดอกเบี้ยในทันที โดยมีพักคํานึงถึงกําหนดเวลาที่ให้ไว้ </div>
			<div class="list nowrap" style="letter-spacing:-0.5px;">ข้อ 7.&nbsp;&nbsp;หากข้าพเจ้าจะขอโอนหรือย้ายหรือออกจากราชการหรือการขาดสมาชิกภาพ ตามข้อบังคับของ สอ.ชป. ข้าพเจ้าจะแจ้งเป็นหนังสือ</div>
			<div class="nowrap" style="letter-spacing:0.25px;">
				ให้ สอ.ชป. ทราบ และยินยอมให้ สอ.ชป.หักเงินค่าหุ้น และเงินอื่นใด ที่จะได้รับจาก สอ.ชป. เพื่อชําระหนี้สินซึ่งข้าพเจ้ามีอยู่ต่อ สอ.ชป.
			</div>
			<div class="nowrap" style="letter-spacing:0.16px;">ให้เสร็จสิ้นเสียก่อน หากข้าพเจ้าไม่ดําเนินการ ข้าพเจ้ายินยอมให้เจ้าหน้าที่ผู้จ่ายเงินบําเหน็จ บํานาญ หรือเงินอื่นใดที่ทางราชการจึงจ่าย</div>
			<div>หักเงินดังกล่าวเพื่อชําระหนี้แก่ สอ.ชป. </div>
			<div class="right" style="margin-right:30px">/ข้อ 8...</div>
		</div>
	</div>
	<div class="right" style="margin-right:60px;">มีนาคม 2564</div>
		';
$html .= '</div>';
//สิ้นสุดหน้าที่1
//หน้าที่ 2  
$html .= '<div class="wrapper-page">
	<div class="border" style="padding:0px 5px 0px 5px">
		<br>
		<div class="list">
			ข้อ 8.&nbsp;&nbsp;ข้าพเจ้าได้รับทราบข้อบังคับและระเบียบของ สอ.ชป. ที่เกี่ยวข้องแล้ว และขอถือว่าเป็นส่วนหนึ่งของสัญญานี้ 
		</div>
		<div class="list nowrap" style="letter-spacing:0.08px;">
			ข้อ 9.&nbsp;&nbsp;เมื่อ สอ.ชป. ได้จ่ายเงินกู้ให้ข้าพเจ้าไม่ว่าจะจ่ายด้วยการโอนเงินเข้าบัญชีเงินฝากธนาคารที่รับโอนเงินได้รายเดือน หรือ
		</div>
		<div class="nowrap" style="letter-spacing:0.43px;">
			บัญชีเงินฝาก สอ.ชป. ที่ข้าพเจ้าได้แจ้งต่อ สอ.ชป. ให้ถือว่าข้าพเจ้าได้รับเงินกู้จํานวนดังกล่าวไปครบถ้วนแล้ว และให้ถือว่าเอกสาร 
		</div>
		<div>
			การโอนเงินเป็นส่วนหนึ่งของสัญญานี้ ข้าพเจ้าขอรับผิดชอบค่าใช้จ่ายในการนี้ทั้งสิ้น 
		</div>
		<div class="list nowrap" style="letter-spacing:-0.1px;">
			ข้อ 10.&nbsp;&nbsp;ข้าพเจ้าขอใช้สิทธิเรียกร้องในเงินค่าหุ้นทั้งหมดและจะมีขึ้นในภายหน้ารวมทั้งเงินปันผลและเงินเฉลี่ยคืน จํานําเป็นประกัน 
		</div>
		<div class="nowrap" style="letter-spacing:0.2px">การชําระหนี้โดยยินยอมให้ สอ.ชป. เป็นผู้ใช้สิทธิเรียกร้องในเงินดังกล่าว รวมทั้งดอกผลชําระหนี้ได้ทันทีโดยมิพักต้องบอกกล่าวให้ทราบ</div>
		<div>ก่อนจนกว่า สอ.ชป. จะได้รับชําระหนี้ครบถ้วน </div>
		<div class="list nowrap" style="letter-spacing:-0.12px;">
			ข้อ 11.&nbsp;&nbsp;ข้าพเจ้ายอมชําระหนี้ให้รวมทั้งค่าใช้จ่ายในการติดตามทวงถาม การดําเนินคดี รวมทั้งค่าเสียหายอันเกิดขึ้นอย่างไม่มีจํากัด
		</div>
		<div class="list nowrap" >
			<div class="absolute" style="margin-left:442px;"><div class="data nowrap center" style="width:80px;" >' . ($data["หลักประกันในวงเงิน"] ?? null) . '</div></div>
			
			ข้อ 12.&nbsp;&nbsp;นอกจากค่าหุ้นที่ข้าพเจ้ามีอยู่ใน สอ.ชป. ข้าพเจ้าขอเสนอหลักประกันในวงเงิน........................บาท ดังต่อไปนี้ 
		</div>
		<div class="nowrap" style="height:23px;" >
			<div class="absolute" style="margin-left:55px;" >
				<input  type="checkbox" style="margin-top:4px;"' . ($people_guarantee ?? null) . '>
				<div class="absolute" style="margin-left:2px;">บุคคลค้ำประกัน</div>
			</div>
			<div class="absolute" style="margin-left:195px;" >
				<input  type="checkbox" style="margin-top:4px; " ' . ($acc_guarantee ?? null) . '>
				<div class="absolute" style="margin-left:2px;">สมุดบัญชีเงินฝากจำนำเป็นประกัน</div>
			</div>
			<div class="absolute" style="margin-left:460px; "><div class="data nowrap">' . ($data["หลักประกันอื่นๆ"] ?? null) . '</div></div>
			<div class="absolute" style="margin-left:410px;" >
				<input  type="checkbox" style="margin-top:4px; " ' . ($other_guarantee ?? null) . '>
				<div class="absolute" style="margin-left:2px;">อื่นๆ.............................................................................</div>
			</div>
		</div>
		<div style="margin-left:-5px; margin-right:-5px; margin-top:10px;">
			<table style=" border-collapse: collapse; width:100%;">
				<tr>
					<th style="width:40px;">ลำดับที่</th>
					<th >ชื่อ-สกุล</th>
					<th style="width:90px;">สมาชิกเลขทะเบียน</th>
					<th style="width:130px;">สังกัด</th>
					<th style="width:90px;">เงินได้รายเดือน</th>
					<th style="width:100px;">ลายมือชื่อผู้ค้ำประกัน</th>
				</tr>';
for ($i = 0; $i <= 6; $i++) {
	$html .= '
				<tr>
					<td class="center">' . ($guarantee[$i]["ลำดับที่"] ?? '&nbsp;') . '</td>
					<td>' . ($guarantee[$i]["ชื่อ"] ?? '&nbsp;') . '</td>
					<td class="center">' . ($guarantee[$i]["เลขที่สมาชิก"] ?? '&nbsp;') . '</td>
					<td>' . ($guarantee[$i]["สังกัด"] ?? '&nbsp;') . '</td>
					<td class="right">' . ($guarantee[$i]["เงินได้รายเดือน"] ?? '&nbsp;') . '</td>
					<td></td>
				</tr>
				';
}
$html .= '				
			</table>
		</div>
		<div class="nowrap" style="height:23px;" >
			<div class="absolute" style="margin-left:300px;"><div class="data nowrap center" style="width:80px; " >' . ($data["ยินยอมทำประกันชีวิตกลุ่มในวงเงิน"] ?? null) . '</div></div>
			<div class="absolute" style="margin-left:55px;" >
				<input  type="checkbox" style="margin-top:4px; "' . ($agree_group ?? null) . '>
				<div class="absolute" style="margin-left:2px;">ข้าพเจ้ายินยอมทําประกันชีวิตกลุ่มในวงเงิน........................บาท</div>
			</div>

			<div class="absolute" style="margin-left:440px;" >
				<input  type="checkbox" style="margin-top:4px;" ' . ($not_agree_group ?? null) . '>
				<div class="absolute" style="margin-left:2px;">ข้าพเจ้าไม่ทําประกันชีวิตกลุ่ม </div>
			</div>
		</div>
		<div class="list nowrap" style="margin-top:10px;">
			ข้อ 13.&nbsp;&nbsp;ข้าพเจ้าขอรับรองว่าข้าพเจ้าไม่อยู่ในระหว่างถูกฟ้องคดีล้มละลายหรือพิทักษ์ทรัพย์เด็ดขาดแต่อย่างใด และขอรับรองว่า
		</div>
		<div>ข้อความที่ข้าพเจ้าได้ระบุในคําขอกู้นั้นมีความเป็นจริงทุกประการ </div>
		<div >
			
			<div class="absolute"><div class="data  left" style="text-indent:410px;">' . ($data["หนังสือบอกกล่าวทวงถามให้ส่งให้กับข้าพเจ้า ณ"] ?? null) . '</div></div>
			<div class="list nowrap">ข้อ 14. ในการส่งหนังสือบอกกล่าวทวงถามให้ส่งให้กับข้าพเจ้า ณ............................................................................................. </div>
		</div>
		<div>.....................................................................................................................................................................................................................</div>
		<div>................................................... หรือตามที่ข้าพเจ้าได้แจ้งเปลี่ยนแปลงเป็นหนังสือให้ สอ.ชป. ทราบในภายหลัง</div>
		<div style="margin-top:10px;">
			<div class="flex" style="height:140px;">
				<div style="margin-left:10px;">
					<div>
						
						(ลงชื่อ)&nbsp;&nbsp;.....................................................ผู้กู้
					</div>
					<div style="margin-left:40px;">
						<div class="absolute" style="margin-left:px;"><div class="data nowrap center" style="width:180px;" >' . ($data["ชื่อ"] ?? null) . '</div></div>
						(.....................................................)
					</div>
					<div style="margin-top:10px;">
						(ลงชื่อ)&nbsp;&nbsp;.....................................................สามี/ภรรยาผู้ให้ความยินยอม
					</div>
					<div style="margin-left:40px;">
						(.....................................................)(เพฉาะกรณีผู้กู้มีคู่สมรส)
					</div>
				</div>
				<div style="margin-left:440px;">
					<div>(ลงชื่อ)&nbsp;&nbsp;......................................ผยาน</div>
					<div style="margin-left:40px;">(......................................)</div>
					<div style="margin-top:10px;">(ลงชื่อ)&nbsp;&nbsp;......................................ผู้ตรวจสอบ</div>
					<div style="margin-left:40px;">(......................................)</div>
					<div style="margin-left:15px;">เจ้าหน้าที่ช่วยงาน สอ.ชป. ส่วนภูมิภาค</div>
				</div>
			</div>
		</div>
		<div class="bold" style="font-size:13pt;"><u>คำเตือน</u>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<u>ผู้ลงลายมือชื่อต้องเป็นบุคคลตามชื่อที่ระบุไว้จริง มิฉะนั้นจะมีความผิดทางอาญา</u></div>
		<div style="margin-left:-5px; margin-top:10px;">
			<div class="flex" style="height:219px;">
				<div style="border-top:1px solid; border-right:1px solid; height:219px;  width:50%;">
					<div class="bold center">ความเห็นของผู้บังคับบัญชา</div>
					<div class="nowrap" style="height:23px;" >
						<div class="absolute" style="margin-left:35px;" >
							ข้าพเจ้าได้พิจารณาคำขอกู้นี้แล้ว
						</div>
						<div class="absolute" style="margin-left:210px;" >
							<input  type="checkbox" style="margin-top:4px;">
							<div class="absolute" style="margin-left:2px;">เห็นควรให้กู้ได้</div>
						</div>
					</div>
					<div class="nowrap" style="height:23px;" >
						<div class="absolute" style="margin-left:50px;" >
							<input  type="checkbox" style="margin-top:4px;">
							<div class="absolute" style="margin-left:2px;">ไม่สมควรให้กู้เพราะ...................................................</div>
						</div>
					</div>
					<div style="margin-left:60px; margin-top:20px;">
						(ลงชื่อ)&nbsp;&nbsp;.....................................
					</div>
					<div style="margin-left:100px;">
						(.....................................)
					</div>
					<div style="margin-left:40px;">
						ตำแหน่ง&nbsp;&nbsp;.........................................
					</div>
				</div>
				<div style="border-top:1px solid;  margin-left:50%; margin-right:-5px; padding-left:5px; width:50%;">
					<div class="bold" style="margin-left:30px;">เอกสารแนบคำขอกู้</div>
					<div class="desc">1. สําเนาใบรับเงินค่าหุ้นของผู้กู้เดือนปัจจุบัน </div>
					<div class="desc">2. สลิปเงินเดือนของผู้กู้เดือนปัจจุบัน </div>
					<div class="desc">3. สําเนาบัตรประจําตัวผู้กู้ที่ยังไม่หมดอายุ ต้องเป็นบัตรประชาชน, บัตรสมาชิก สอ.ชป. </div>
					<div class="desc nowrap" style="margin-left:13px; letter-spacing:-0.25px;">บัตรประจําตัวข้าราชการ บัตรประจําตัวเจ้าหน้าที่ของรัฐ หรือหนังสือรับรองลูกจ้างประจํา </div>
					<div class="desc nowrap" style="margin-left:13px; letter-spacing:0.6px;">ถ้าใช้ใบรับรองคําขอมีบัตรประชาชน หรือเปลี่ยนบัตรใหม่ ต้องมีรูปติดด้วย </div>
					<div class="desc" style="margin-left:13px;">พร้อมเจ้าของบัตรรับรองสําเนาถูกต้อง</div>
					<div class="desc">4. สําเนาบัตรประจําตัวคู่สมรสที่ยังไม่หมดอายุพร้อมเจ้าของบัตร รับรองสําเนาถูกต้อง </div>
					<div class="desc" style="letter-spacing:0.65px;">5. ใบสมัครประกันชีวิตกลุ่ม สําหรับผู้ที่กู้เงิน ตั้งแต่ 450,000.- บาท ขึ้นไป</div>
					<div class="desc" style="margin-left:13px; ">หรือกรณีที่ยอดเงินกู้เพิ่ม</div>
				</div>
			</div>
		</div>
	</div>
';

$html .= '</div>';
//สิ้นสุดหน้า2

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