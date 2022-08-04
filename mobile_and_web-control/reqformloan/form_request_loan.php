<?php
use Dompdf\Dompdf;

$dompdf = new DOMPDF();
function GeneratePDFContract($data,$lib) {
	//demo Data
$data["วันที่"] = $lib->convertdate(date("Y-m-d"),"d M Y");
$data["เวลา"] = date("H:i");
$data["เลขที่รับคำขอ"] = $data["requestdoc_no"];
$data["วันที่จ่ายเงินกู้"] = '';
$data["จำนวนเงินกู้"] = number_format($data["request_amt"],2);
$data["จำนวนเงินคำอ่าน"] = $lib->baht_text($data["request_amt"]);
$data["งวดชำระ"] = $data["period"];
$data["งวดล่ะ"] = number_format($data["period_payment"],2);
$data["เลขที่สมาชิก"] = $data["member_no"];
$data["วัตถุประสงค์"] = $data["objective"];
$data["ชื่อ"] = $data["full_name"]; 
$data["วันเกิด"] = $data["birth_date"];
$data["อายุ"] = $data["birth_date_raw"];
$data["เลขประจำตัวประชาชน"] = $data["card_person"];
$data["เบอร์โทร"] = $data["mem_telmobile"];
$data["ตำแหน่ง"] = $data["position"];
$data["หน่วยงาน"] = $data["pos_group"];
$data["อำเภอ"] = $data["district_desc"];
$data["จังหวัด"] = $data["province_desc"];
$data["อัตราเงินเดือน"] = number_format($data["salary_amount"],2);
$data["วันที่บรรจุเป็นข้าราชการ"] = $data["work_date"];
$data["อายุงานบรรจุปี"] = $data["work_date_raw"] != "" ? explode(" ",$lib->count_duration($data["work_date_raw"],"ym"))[0] : "";
$data["อายุงานบรรจุเดือน"] = $data["work_date_raw"] != "" ? explode(" ",$lib->count_duration($data["work_date_raw"],"ym"))[2] : "";
$data["อายุงานทำงานปี"] = $data["member_date_raw"] != "" ?  explode(" ",$lib->count_duration($data["member_date_raw"],"ym"))[0] : "";
$data["อายุงานทำงานเดือน"] = $data["member_date_raw"] != "" ? explode(" ",$lib->count_duration($data["member_date_raw"],"ym"))[2] : "";
$data["เกษียณอายุราชการ"] = $data["retry_date"];
$data["วันที่เริ่มทำงาน"] = $data["member_date"];
$data["บ้านเลขที่"] =  $data["addr_no"];
$data["หมู่ที่"] = $data["addr_moo"];
$data["ซอย"] = $data["addr_soi"];
$data["ถนน"] = $data["addr_road"];
$data["ตำบล"] = $data["tambol_desc"];
$data["รหัสไปรษณีย์"] = $data["addr_postcode"];
$data["เกี่ยวข้องกับผู้กู้เป็น"] = 'ลูกชาย';
$data["เงินค่าหุ้น"] = $data["sharestk_amt"];
$data["เงินฝาก"] = '111,000.00';
$data["เลขบัญชี"] = '1542451525';
$data["จำนวนเงิน"] = '111,000.00';
$data["อหังสาริมทรัพย์"] = '111,000.00';
$data["หลักทรัพย์"] = '111,000.00';
$data["อื่นๆ"] = '111,000.00';
$data["ชื่อบัญชี"] = 'ชื่อบัญชีเงินฝาก';
$data["เลขบัญชีธนาคาร"] = '1545854571';
$data["ดอกเบี้ย"] = 'คงยอด';
$data["จำนวนเงินขอกู้"] = '100,000.00'; 
$data["ประเภทเงินกู้"] ="เงินกู้เพื่อการลงทุนประกอบวิชาชีพ";
$data["สถานะภาพปัจจุบัน"] ="อื่นๆ" ;
$data["ที่เดียวกับที่อยู่ตามทะเบียนบ้าน"] = '';
$data["ค้ำประกัน"] = "เงินฝาก2";


//ที่อยู่ปัจบัน
$curr_addr = $data["ที่เดียวกับที่อยู่ตามทะเบียนบ้าน"]??null;

//สถานะภาพปัจจุบัน
/*$status = $data["สถานะภาพปัจจุบัน"]??null;
if($status == "โสด"){
	$โสด = "checked";
}else if($status =="สมรส"){
	$สมรส = "checked";
}else if($status == "หย่า"){
	$หย่า = "checked";
}else if($status == "คู่สมรสเสียชีวิต"){
	$คู่สมรสเสียชีวิต = "checked";
}else if($status == "อื่นๆ"){
	$อื่นๆ = "checked";
}*/

//กลุ่มเงินกุ้เงินกู้
$loantype_code =  $data["loantype_code"]??null;
$loangroup_code =  $data["loangroup_code"]??null;
if($loangroup_code == "01"){
	$เงินกู้เพื่อเหตุฉุกเฉิน = "checked";
}else if($loangroup_code == "03"){
	$เงินกู้พิเศษ = "checked";  //
}else if($loangroup_code == "02" && $loantype_code !="42" || $loantype_code !="43"){
	$เงินกู้สามัญเพื่อการอันจำเป็น = "checked";
}else if($loangroup_code == "02" && $loantype_code =="42" || $loantype_code =="43"){
	$เงินกู้สามัญเพื่อชำระหนี้สถาบันการเงินอื่น = "checked";
}
//ประเภทเงินกู้
if($loantype_code =="10"){
	$เงินกู้ฉุกเฉิน = "checked"; //
}else if($loantype_code =="11"){
	$เงินกู้ฉุกเฉินเบิกผ่านระบบATM ="checked";
}else if($loantype_code =="20"){
	$หุ้นค้ำประกันเงินฝากค้ำประกัน = "checked";
}else if($loantype_code == "23"){
	$บุคคลค้ำประกัน="checked";
}else if($loantype_code == "28"){
	$หลักทรัพย์ค้ำประกัน = "checked";
}else if($loantype_code == "38"){
	$เงินกู้พัฒนาวิชาชีพ ="checked";
}else if($loantype_code == "27"){
	$เงินกู้กองทุนเพื่อความมั่นคง = "checked";
}else if($loantype_code == "31"){
	$เงินกู้พิเศษอื่นๆ = "checked";
}else if($loantype_code =="33"){
	$เงินกู้เพื่อการเคหะสงเคราะห์ ="checked";
}else if($loantype_code == "34"){
	$เงินกู้เพื่อการลงทุนประกอบวิชาชีพ ="checked";
}else if($loantype_code == "42"){
	$บุคคลค้ำประกัน="checked";
}else if($loantype_code == "43"){
	$หลักทรัพย์ค้ำประกัน = "checked";
}

//ดอกเบี้ย
/*$insterest = ($data["ดอกเบี้ย"]??null);
if($insterest == "คงต้น"){
	$คงต้น = "checked";
}else if($insterest == "คงยอด"){
	$คงยอด = "checked";
}*/

//ประเภทค้ำประกัน

$sharestk = $data["ค้ำประกัน"]??null;
if($sharestk == "บุคคลค้ำประกัน"){
	$บุคคลค้ำประกัน = "checked";
}else if($sharestk == "เงินค่าหุ้น"){
	$เงินค่าหุ้น = "checked";
}else if($sharestk =="เงินฝาก1"){
	$เงินฝาก1 ="checked";
}else if($sharestk =="เงินฝาก2"){
	$เงินฝาก2 ="checked";
}else if($sharestk =="อหังสาริมทรัพย์"){
	$อหังสาริมทรัพย์ ="checked";
}else if($sharestk =="อื่นๆ"){
	$ค้ำประกันอื่นๆ ="checked";
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
			  font-size: 12pt;
			  line-height: 20px;
			}
			div{
				line-height: 18px;
				font-size: 13pt;
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
				text-indent:40px;
			}
			.sub-list{
				text-indent:80px;
			}
			th{
				border:1px solid;
				text-align:center;
				padding-bottom:5px;
				line-height:18px;
			}
			td{
				line-height:18px;
				padding:5px;
				border:1px solid;
			}
			.absolute{
				position:absolute;
			}
			.relative{
				position:relative;
			  }
			.data{
				font-size:12pt;
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
				padding-left:5px;
				line-height:22px;
			}
			.radio{
				margin-top:4px;
			}
			.checkbox{
				margin-top:6px;
			}
			.content{
				padding-left:5px; 
				padding-right:5px;
			}
			.detail{
				font-size:11pt;
				line-height:17px;
			}
			.tableData{
				font-size:11pt;
			}
			</style>';
//ขนาด
$html .= '<div style="margin:-25px -20px -20px -20px;" >';
//	<div class="absolute" style="margin-left:55px; width:99px;"><div style="margin-top:5px;" class="data center nowrap">'.($data["หนี้คงเหลือ"]??null).'</div></div>
//ส่วนหัว


//เริ่มหน้าที่ 1  
$html .= '<div class="wrapper-page">';
$html .= '
	<div>
		<div>
			<div style=" text-align: left; "><img src="../../resource/logo/logo.jpg" alt="" width="80" height="80"></div>
			<div class="absolute bold center" style="top:55px; margin-left:70px; font-size:20pt; width:225px;">คำขอกู้เงิน</div>
		</div>
		<div class="bold" style="margin-left:60px; font-size:16pt">
			สหกรณ์ออมทรัพย์ครูระยอง จำกัด
		</div>
		<div style="border:0.5px solid #000000; width:170px; position:absolute; right:165px; top:-30px; padding:15px 5px 15px 5px; font-weight:bold;">
			<div style=" line-height: 23px; font-size:13pt">
				<div class="absolute" style="margin-left:80px;"><div  class="data nowrap">'.($data["เลขที่รับคำขอ"]??null).'</div></div>
				เลขที่รับคำขอ...........................
			</div>
			<div class="nowrap" style=" line-height: 23px;">
				<div class="absolute" style="margin-left:40px;"><div  class="data nowrap">'.($data["วันที่"]??null).'</div></div>
				วันที่........................................
			</div>
			<div style="line-height: 23px;">
				<div class="absolute" style="margin-left:40px;"><div  class="data nowrap">'.($data["เวลา"]??null).'</div></div>
				เวลา........................................    
			</div>
		</div>
		<div style="border:0.5px solid #000000; width:170px; position:absolute; right:-20px; top:-30px; padding:15px 5px 15px 5px;  font-weight:bold; height:78px;" >
			<div style=" line-height: 23px; font-size:13pt">
				<div class="absolute" style="margin-left:80px;"><div  class="data nowrap">'.($data["เลขที่สัญญา"]??null).'</div></div>
				เลขที่สัญญา.............................
			</div>
			<div class="nowrap" style=" line-height: 23px;">
				<div class="absolute" style="margin-left:80px;"><div  class="data nowrap">'.($data["วันที่จ่ายเงินกู้"]??null).'</div></div>
				วันที่จ่ายเงินกู้...........................
			</div>
		</div>
	</div>
	<div class="border" style="margin-top:10px; padding:5px;  ">
		<div class="center bold" style="font-size:18pt; color:red;">
		<div class="absolute" style="border-bottom:2px double red; height:24px; width:103px; margin-left:315px; margin-top:-5px;"></div>**คำเตือน**</div>
		<div class="bold">1.ผู้ขอกกรอกข้อมูลให้ครบถ้วนถกต้อง ู ทุกข้อความ ด้วยลายมือของตนเอง มิฉะนั้นสหกรณ์ฯอาจไม่รับพิจารณา</div>
		<div class="bold">2.<u>ห้าม</u>ใช้นํ้ายาลบคําผิด ยางลบ ขูดลบ ข้อความ <u>ห้าม</u>ใช้ปากกาลบได้ การแก้ไขให้ใช้วิธีขีดฆ่าและลงลายมือชือกํากับเท่านั้น</div>
		<div class="bold">3.เอกสารประกอบการขอกู้ <u>ห้าม</u>ปลอมแปลงเอกสาร / <u>ห้าม</u>ใช้กระดาษ Reuse</div>
	</div>
	<div class="bold list" style="margin-top:5px;">
		เรียน คณะกรรมการดําเนินการสหกรณ์ออมทรัพย์ครูระยอง จํากัด 
	</div>
	<div class="sub-list">
		ข้าพเจ้าขอเสนอคําขอกู้ เพื่อพิจารณาดังต่อไปนี้  
	</div>
	<div style="margin-top:5px; line-height:23px; border:1px solid; padding-bottom:15px; ">
		<div class="flex" style="height:210px;">
			<div style="border-right:1px solid; width:58%; height:216px;"></div>
			<div style="width:60%; border-bottom:0.5px solid;   padding-bottom:13.5px;">
				<div class="center bold" style="padding:3px 5px 5px 5px ; width:80px; height:25px;  border:2px solid;  border-radius: 7px; margin-top:10px; margin-left:40%;">ประเภทเงินกู้</div>
				<div>
					<div class="padding-content" >
						<div class="absolute">
							<input class="radio" type="radio" '.($เงินกู้เพื่อเหตุฉุกเฉิน??null).'>
						</div>
						<div style="margin-left:30px; margin-top:4px;">เงินกู้เพื่อเหตุฉุกเฉิน</div>
					</div>
				</div>
				<div class="flex" style="height: 22px;">
					<div>
						<div class="absolute">
							<input class="checkbox" type="checkbox" style="margin-left:10px;"'.($เงินกู้ฉุกเฉิน??null).'>
							<div class="absolute" style="margin-left:3px; margin-top:4px;">เงินกู้ฉุกเฉิน</div>
						</div>
					</div>
					<div>
						<div class="absolute" style="margin-left:130px;">
							<input class="checkbox" type="checkbox" style="margin-left:10px;" '.($เงินกู้ฉุกเฉินเบิกผ่านระบบATM??null).'>
							<div class="absolute" style="margin-left:3px; margin-top:4px;">เงินกู้ฉุกเฉินเบิกผ่านระบบ ATM</div>
						</div>
					</div>
				</div>
				<div class="flex" style="height:22px;">
					<div>
						<div class="padding-content" >
							<div class="absolute">
								<input class="radio" type="radio" '.($เงินกู้สามัญเพื่อการอันจำเป็น??null).'>
							</div>
							<div style="margin-left:30px; margin-top:4px;">เงินกู้สามัญเพื่อการอันจำเป็นฯ</div>
						</div>
					</div>
					<div style="margin-left:190px;">
						<div class="padding-content" >
							<div class="absolute">
								<input class="radio" type="radio" '.($เงินกู้สามัญเพื่อชำระหนี้สถาบันการเงินอื่น??null).'>
							</div>
							<div style="margin-left:30px; margin-top:4px;">เงินกู้สามัญเพื่อชำระหนี้สถาบันการเงินอื่น</div>
						</div>
					</div>
				</div>
				<div class="flex" style="height: 22px;">
					<div>
						<div class="absolute">
							<input class="checkbox" type="checkbox" style="margin-left:10px;" '.($หุ้นค้ำประกันเงินฝากค้ำประกัน??null).'>
							<div class="absolute" style="margin-left:3px; margin-top:4px; ">หุ้นค้ำประกัน/เงินฝากค้ำประกัน</div>
						</div>
					</div>
					<div>
						<div class="absolute" style="margin-left:190px;">
							<input class="checkbox" type="checkbox" style="margin-left:10px;" '.($บุคคลค้ำประกัน??null).'>
							<div class="absolute" style="margin-left:3px; margin-top:4px;">บุคคลค้ำประกัน</div>
						</div>
					</div>
					<div>
						<div class="absolute" style="margin-left:300px;">
							<input class="checkbox" type="checkbox" style="margin-left:10px;" '.($หลักทรัพย์ค้ำประกัน??null).'>
							<div class="absolute" style="margin-left:3px; margin-top:4px;">หลักทรัพย์ค้ำประกัน</div>
						</div>
					</div>
				</div>
				<div>
					<div class="padding-content" >
						<div class="absolute">
							<input class="radio" type="radio" '.($เงินกู้พิเศษ??null).'>
						</div>
						<div style="margin-left:30px; margin-top:4px;">เงินกู้พิเศษ</div>
					</div>
				</div>
				<div class="flex" style="height: 22px;">
					<div>
						<div class="absolute">
							<input class="checkbox" type="checkbox" style="margin-left:10px;" '.($เงินกู้พัฒนาวิชาชีพ??null).'>
							<div class="absolute" style="margin-left:3px; margin-top:4px;">เงินกู้พัฒนาวิชาชีพ</div>
						</div>
					</div>
					<div>
						<div class="absolute" style="margin-left:130px;">
							<input class="checkbox" type="checkbox" style="margin-left:10px;"'.($เงินกู้กองทุนเพื่อความมั่นคง??null).'>
							<div class="absolute" style="margin-left:3px; margin-top:4px;">เงินกู้กองทุนเพื่อความมั่นคง</div>
						</div>
					</div>
					<div>
						<div class="absolute" style="margin-left:300px;">
							<input class="checkbox" type="checkbox" style="margin-left:10px;" '.($เงินกู้พิเศษอื่นๆ??null).'>
							<div class="absolute" style="margin-left:3px; margin-top:4px;">เงินกู้พิเศษอื่นๆ</div>
						</div>
					</div>
				</div>
				<div class="flex" style="height: 22px;">
					<div>
						<div class="absolute">
							<input class="checkbox" type="checkbox" style="margin-left:10px;" '.($เงินกู้เพื่อการเคหะสงเคราะห์??null).'>
							<div class="absolute" style="margin-left:3px; margin-top:4px;">เงินกู้เพื่อการเคหะสงเคราะห์</div>
						</div>
					</div>
					<div>
						<div class="absolute" style="margin-left:170px;">
							<input class="checkbox" type="checkbox" style="margin-left:10px;" '.($เงินกู้เพื่อการลงทุนประกอบวิชาชีพ??null).'>
							<div class="absolute" style="margin-left:3px; margin-top:4px;">เงินกู้เพื่อการลงทุนประกอบวิชาชีพ</div>
						</div>
					</div>
				</div>
			</div>
			
			<div style="width:40%;  border-bottom:0.5px solid;  solid; margin-left:60%; padding-bottom:2.5px; ">
				<div class="center bold" style="padding:3px 5px 5px 5px ; width:90px; height:25px;  border:2px solid;  border-radius: 7px; margin-top:10px; margin-left:33%; ">จำนวนเงินขอกู้</div>
				<div class="padding-content" style="margin-top:15px;">
					<div class="absolute " style="margin-left:60px; width:185px;"><div  class="data nowrap center">'.($data["จำนวนเงินกู้"]??null).'</div></div>
					จำนวนเงินกู้............................................................บาท
				</div>
				<div class="padding-content center" style="margin-top:20px;">
					<div class="absolute" style="margin-left:30px; width:230px;"><div style="margin-top:-3px;"  class="data nowrap center">'.($data["จำนวนเงินคำอ่าน"]??null).'</div></div>
					(.........................................................................)
				</div>
				<div class="padding-content" style="margin-top:5px;">
					<div class="absolute" style="margin-left:90px; width:25px;"><div style="margin-top:-3px;"  class="data nowrap center">'.($data["งวดชำระ"]??null).'</div></div>
					<div class="absolute" style="margin-left:175px; width:70px;"><div style="margin-top:-3px;"  class="data nowrap center">'.($data["งวดล่ะ"]??null).'</div></div>

					ส่งเงินงวดชำระหนี้.........งวด งวดล่ะ.......................บาท
				</div>
				<div class="padding-content center">(พร้อมด้วยดอกเบี้ยตามอัตราที่สหกรณ์กำหนด)</div>
				<div class="flex" style="height:30px;">
					<div>
						<div class="absolute" style="margin-left:30%">
							<input class="checkbox" type="checkbox" '.($คงต้น??null).'>
							<div class="absolute" style="margin-left:3px;">คงต้น</div>
						</div>
					</div>
					<div>
						<div class="absolute" style="right:30%">
							<input class="checkbox" type="checkbox" '.($คงยอด??null).'>
							<div class="absolute" style="margin-left:3px;">คงยอด</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div  style="border-bottom:solid 0.5px; padding-left:5px">
			<div style="height:1px;"></div>
			<div class="center bold" style="padding:3px 5px 5px 5px ; width:110px; height:25px;  border:2px solid;  border-radius: 7px; margin-top:15px; margin-left:41.5%; ">วัตถุประสงค์ขอกู้</div>
			<div style="margin-top:20px;">
				<div class="absolute" style="margin-left:290px; "><div style="margin-top:-3px;"  class="data nowrap ">'.($data["วัตถุประสงค์"]??null).'</div></div>
				ข้าพเจ้าขอกู้เงินจำนวนนี้เพื่อนำไปใช้ตามวัตถุประสงค์ดังนี้.................................................................................................................................................
			</div>
		</div>
		<div  style="padding-left:5px; margin-top:-5px;">
			<div class="bold" style="padding:3px 5px 5px 5px ; width:360px; height:25px;  border:2px solid;  border-radius: 7px; margin-top:10px; margin-left:0px; ">
				<div class="absolute" style="margin-left:260px; "><div style="margin-top:-3px;"  class="data nowrap ">'.($data["เลขที่สมาชิก"]??null).'</div></div>
				ข้อมูลส่วนบุคคล &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;   สมาชิกเลขทะเบียนที่...........................
			</div>
			<div>
				<div class="absolute" style="margin-left:37px; width:400px; "><div   class="data nowrap center ">'.($data["ชื่อ"]??null).'</div></div>
				<div class="absolute" style="margin-left:510px; width:170px; "><div   class="data nowrap center ">'.($data["วันเกิด"]??null).'</div></div>
				<div class="absolute" style="margin-left:699px; width:25px; "><div   class="data nowrap center ">'.($data["อายุ"]??null).'</div></div>


				ข้าพเจ้า................................................................................................................................วัน/เดือน/ปีเกิด.....................................................อายุ........ปี
			</div>
			<div>
				<div class="absolute" style="margin-left:110px; width:205px; "><div   class="data nowrap center ">'.($data["เลขประจำตัวประชาชน"]??null).'</div></div>
				เลขประจำตัวประชาชน.................................................................สถานะภาพปัจจุบัน
				<div class="absolute" style="margin-left:0px;">
					<input class="checkbox" type="checkbox" style="margin-left:10px;" '.($โสด??null).'>
					<div class="absolute" style="margin-left:3px;">โสด</div>
				</div>
				<div class="absolute" style="margin-left:50px;">
					<input class="checkbox" type="checkbox" style="margin-left:10px;" '.($สมรส??null).'>
					<div class="absolute" style="margin-left:3px;">สมรส</div>
				</div>
				<div class="absolute" style="margin-left:105px;">
					<input class="checkbox" type="checkbox" style="margin-left:10px;" '.($หย่า??null).'>
					<div class="absolute" style="margin-left:3px;">หย่า</div>
				</div>
				<div class="absolute" style="margin-left:150px;">
					<input class="checkbox" type="checkbox" style="margin-left:10px;"  '.($คู่สมรสเสียชีวิต??null).'>
					<div class="absolute" style="margin-left:3px;">คู่สมรสเสียชีวิต</div>
				</div>
				<div class="absolute" style="margin-left:250px;">
					<input class="checkbox" type="checkbox" style="margin-left:10px;" '.($อื่นๆ??null).'>
					<div class="absolute" style="margin-left:3px;">อื่นๆ</div>
				</div>
			</div>
			<div>
				<div class="absolute" style="margin-left:110px; width:180px; "><div   class="data nowrap ">'.($data["เบอร์โทร"]??null).'</div></div>
				เบอร์โทรศัพท์มือถือ......................................................................
			</div>
			<div class="center" style="padding:0px 5px 5px 5px ; width:120px; height:25px;  border:2px solid;  border-radius: 7px; margin-top:3px; margin-left:0px; ">ตำแหน่งหน้าที่การงาน</div>
			<div>
				<div class="absolute" style="margin-left:155px; width:230px; "><div class="data nowrap center">'.($data["ตำแหน่ง"]??null).'</div></div>
				<div class="absolute" style="margin-left:480px; width:240px; "><div class="data nowrap center">'.($data["หน่วยงาน"]??null).'</div></div>
					ขณะนี้ข้าพเจ้าทำงานในตำแหน่ง........................................................................โรงเรียน / หน่วยงาน...............................................................................
			</div>
			<div>
				<div class="absolute" style="margin-left:30px; width:240px; "><div class="data nowrap center">'.($data["อำเภอ"]??null).'</div></div>
				<div class="absolute" style="margin-left:300px; width:240px; "><div class="data nowrap center">'.($data["จังหวัด"]??null).'</div></div>
				<div class="absolute" style="margin-left:605px; width:100px; "><div class="data nowrap center">'.($data["อัตราเงินเดือน"]??null).'</div></div>
				อำเภอ...........................................................................จังหวัด...........................................................................อัตราเงินเดือน................................บาท
			</div>
			<div>
				<div class="absolute" style="margin-left:115px; width:220px; "><div class="data nowrap center">'.($data["วันที่บรรจุเป็นข้าราชการ"]??null).'</div></div>
				<div class="absolute" style="margin-left:370px; width:65px; "><div class="data nowrap center">'.($data["อายุงานบรรจุปี"]??null).'</div></div>
				<div class="absolute" style="margin-left:445px; width:35px; "><div class="data nowrap center">'.($data["อายุงานบรรจุเดือน"]??null).'</div></div>
				<div class="absolute" style="margin-left:640px; width:80px; "><div class="data nowrap center">'.($data["เกษียณอายุราชการ"]??null).'</div></div>
				วันที่บรรจุเป็นข้าราชการ.....................................................................อายุงาน....................ปี............เดือน เกษียณอายุราชการ ปี พ.ศ...........................
			</div>
			<div>
				<div class="absolute" style="margin-left:70px; width:230px; "><div class="data nowrap center">'.($data["วันที่เริ่มทำงาน"]??null).'</div></div>
				<div class="absolute" style="margin-left:342px; width:95px; "><div class="data nowrap center">'.($data["อายุงานทำงานปี"]??null).'</div></div>
				<div class="absolute" style="margin-left:445px; width:35px; "><div class="data nowrap center">'.($data["อายุงานทำงานเดือน"]??null).'</div></div>
				<div class="absolute" style="margin-left:640px; width:80px; "><div class="data nowrap center"></div></div>

				วันที่เริ่มทำงาน..........................................................................อายุงาน..............................ปี............เดือน  (เฉพาะกรณีสมาชิกตําแหน่งลูกจ้าง)
			</div>
			<div class="center bold" style="padding:0px 5px 5px 5px ; width:120px; height:25px;  border:2px solid;  border-radius: 7px; margin-top:3px; margin-left:0px; ">ที่อยู่ตามทะเบียนบ้าน</div>
			<div>
				<div class="absolute" style="margin-left:50px; width:60px; "><div class="data nowrap center">'.($data["บ้านเลขที่"]??null).'</div></div>
				<div class="absolute" style="margin-left:135px; width:40px; "><div class="data nowrap center">'.($data["หมู่ที่"]??null).'</div></div>
				<div class="absolute" style="margin-left:200px; width:90px; "><div class="data nowrap center">'.($data["ซอย"]??null).'</div></div>
				<div class="absolute" style="margin-left:310px; width:190px; "><div class="data nowrap center">'.($data["ถนน"]??null).'</div></div>
				<div class="absolute" style="margin-left:540px; width:190px; "><div class="data nowrap center">'.($data["ตำบล"]??null).'</div></div>
				บ้านเลขที่.....................หมู่ที่.............ซอย.............................ถนน..............................................................ตำบล..............................................................
			</div>
			<div>
				<div class="absolute" style="margin-left:30px; width:110px; "><div class="data nowrap center">'.($data["อำเภอ"]??null).'</div></div>
				<div class="absolute" style="margin-left:170px; width:120px; "><div class="data nowrap center">'.($data["จังหวัด"]??null).'</div></div>
				<div class="absolute" style="margin-left:370px; width:120px; "><div class="data nowrap">'.($data["รหัสไปรษณีย์"]??null).'</div></div>
				อำเภอ..................................จังหวัด.....................................รหัสไปษณีย์...................................................
			</div>
			<div>
				<div class="absolute" style="margin-left:230px; padding-top:5px;">
					<input class="checkbox" type="checkbox" style="margin-left:10px;" '.($curr_addr== true?"checked":"").'>
					<div class="absolute bold" style="margin-left:3px;">ที่เดียวกับที่อยู่ตามทะเบียนบ้าน</div>
				</div>
				<div class="center bold" style="padding:0px 5px 5px 5px ; width:180px; height:25px;  border:2px solid;  border-radius: 7px; margin-top:3px; margin-left:0px; ">ที่อยู่ปัจจุบัน ที่สามารถติดต่อได้</div>
			</div>
			<div>
				<div class="absolute" style="margin-left:50px; width:60px; "><div class="data nowrap center">'.($data["บ้านเลขที่"]??null).'</div></div>
				<div class="absolute" style="margin-left:135px; width:40px; "><div class="data nowrap center">'.($data["หมู่ที่"]??null).'</div></div>
				<div class="absolute" style="margin-left:200px; width:90px; "><div class="data nowrap center">'.($data["ซอย"]??null).'</div></div>
				<div class="absolute" style="margin-left:310px; width:190px; "><div class="data nowrap center">'.($data["ถนน"]??null).'</div></div>
				<div class="absolute" style="margin-left:540px; width:190px; "><div class="data nowrap center">'.($data["ตำบล"]??null).'</div></div>
				บ้านเลขที่.....................หมู่ที่.............ซอย.............................ถนน..............................................................ตำบล..............................................................
			</div>
			<div>
				<div class="absolute" style="margin-left:30px; width:110px; "><div class="data nowrap center">'.($data["อำเภอ"]??null).'</div></div>
				<div class="absolute" style="margin-left:170px; width:120px; "><div class="data nowrap center">'.($data["จังหวัด"]??null).'</div></div>
				<div class="absolute" style="margin-left:370px; width:120px; "><div class="data nowrap">'.($data["รหัสไปรษณีย์"]??null).'</div></div>
				อำเภอ..................................จังหวัด.....................................รหัสไปษณีย์...................................................
			</div>
			
		</div>
	</div>
	
	';



$html .= '</div>';
//สิ้นสุดหน้าที่1


//หน้าที่ 2  
$html .= '<div class="wrapper-page">';
$html .= '
		<div style="margin:0px 0px 0px 0px;" >
			<div class="center">2</div>
			<div  class="border" style="margin-top:20px; padding-left:5px; padding-bottom:10px; padding-right:5px;">
				<div class="center bold" style="padding:0px 5px 5px 5px ; width:120px; height:25px;  border:2px solid;  border-radius: 7px; margin-top:10px; margin-left:0px; ">ข้อมูลคู่สมรส</div>
				<div>
					<div class="absolute" style="margin-left:70px; width:310px; "><div class="data nowrap center">'.($data[""]??null).'</div></div>
					<div class="absolute" style="margin-left:450px; width:200px; "><div class="data nowrap center">'.($data[""]??null).'</div></div>
					<div class="absolute" style="margin-left:670px; width:50px; "><div class="data nowrap center">'.($data[""]??null).'</div></div>
					<div class="nowrap">ชื่อ - นามสกุล...................................................................................................วัน/เดือน/ปีเกิด...............................................................อายุ................ปี</div>
				</div>
				<div>
					<div class="absolute" style="margin-left:110px; width:220px; "><div class="data nowrap center">'.($data[""]??null).'</div></div>
					<div class="absolute" style="margin-left:375px; width:135px; "><div class="data nowrap center">'.($data[""]??null).'</div></div>
					<div class="absolute" style="margin-left:590px; width:85px; "><div class="data nowrap center">'.($data[""]??null).'</div></div>
					<div class="absolute" style="margin-left:690px; width:35px; "><div class="data nowrap center">'.($data[""]??null).'</div></div>
					เลขประจำตัวประชาชน......................................................................โทรศัพท์............................................ที่อยู่ปัจจุบันเลขที่.......................หมู่ที่............
				</div>
				<div>
					<div class="absolute" style="margin-left:50px; width:80px; "><div class="data nowrap center">'.($data[""]??null).'</div></div>
					<div class="absolute" style="margin-left:150px; width:150px; "><div class="data nowrap center">'.($data[""]??null).'</div></div>
					<div class="absolute" style="margin-left:328px; width:180px; "><div class="data nowrap center">'.($data[""]??null).'</div></div>
					<div class="absolute" style="margin-left:545px; width:180px; "><div class="data nowrap center">'.($data[""]??null).'</div></div>
					ตรอก/ซอย........................ถนน...............................................ตำบล...........................................................อำเภอ...........................................................
				</div>	
				<div>
					<div class="absolute" style="margin-left:35px; width:225px; "><div class="data nowrap center">'.($data[""]??null).'</div></div>
					<div class="absolute" style="margin-left:320px; width:205px; "><div class="data nowrap center">'.($data[""]??null).'</div></div>
					<div class="absolute" style="margin-left:595px; width:130px; "><div class="data nowrap center">'.($data[""]??null).'</div></div>
					จังหวัด........................................................................รหัสไปษณีย์..................................................................เบอร์โทรศัพท์............................................
				</div>
				<div class="center bold" style="padding:0px 5px 5px 5px ; width:390px; height:25px;  border:2px solid;  border-radius: 7px; margin-top:3px; margin-left:0px; ">บุคคลอ้างอิงที่สหกรณ์ออมทรัพย์ครูระยอง จำกัด สามารถติดต่อได้</div>
				<div class="nowrap">
					<div class="absolute" style="margin-left:70px; width:255px; "><div class="data nowrap center">'.($data[""]??null).'</div></div>
					<div class="absolute" style="margin-left:420px; width:115px; "><div class="data nowrap center">'.($data[""]??null).'</div></div>
					<div class="absolute" style="margin-left:607px; width:115px; "><div class="data nowrap center">'.($data[""]??null).'</div></div>
					ชื่อ - นามสกุล.................................................................................เกี่ยวข้องกับผู้กู้เป็น.....................................โทรศัพท์มือถือ.......................................
				</div>
				<div>
					<div class="absolute" style="margin-left:85px; width:85px; "><div class="data nowrap center">'.($data[""]??null).'</div></div>
					<div class="absolute" style="margin-left:195px; width:35px; "><div class="data nowrap center">'.($data[""]??null).'</div></div>
					<div class="absolute" style="margin-left:280px; width:117px; "><div class="data nowrap center">'.($data[""]??null).'</div></div>
					<div class="absolute" style="margin-left:421px; width:130px; "><div class="data nowrap center">'.($data[""]??null).'</div></div>
					<div class="absolute" style="margin-left:581px; width:150px; "><div class="data nowrap center">'.($data[""]??null).'</div></div>

					ที่อยู่ปัจจบันเลขที่............................หมู่ที่...........ตรอก/ซอย....................................ถนน..........................................ตำบล................................................
				</div>
				<div>
					<div class="absolute" style="margin-left:30px; width:197px; "><div class="data nowrap center">'.($data[""]??null).'</div></div>
					<div class="absolute" style="margin-left:255px; width:250px; "><div class="data nowrap center">'.($data[""]??null).'</div></div>
					<div class="absolute" style="margin-left:570px; width:160px; "><div class="data nowrap center">'.($data[""]??null).'</div></div>


					อำเภอ.............................................................จังหวัด...............................................................................รหัสไปรษณีย์...................................................
				</div>
				
				<div class="center bold"  style="padding:0px 5px 5px 5px ; width:490px; height:25px;  border:2px solid;  border-radius: 7px; margin-top:3px; margin-left:115px; ">
					ข้อมูลหลักประกัน(สำหรับเงินกู้สามัญ / เงินกู้พัฒนาวิชาชีพ / เงินกู้กองทุน / เงินกู้พิเศษอื่นๆ)
				</div>
				<div style="height:25px;">
					<div class="absolute">
						<input class="checkbox" type="checkbox" style="margin-left:0px;" '.($บุคคลค้ำประกัน??null).'>
						<div class="absolute" style="margin-left:3px;">บุคคลค้ำประกัน</div>
					</div>
				</div>
				<div style="margin-top:10px;">
					<table style="border-collapse: collapse; width:95%  ">
						<tr>
							<td class="center tableData">ที่</td>
							<td class="center tableData">ชื่อ - สกุล</td>
							<td class="center tableData">เลขทะเบียน</td>
							<td class="center tableData">ตำแหน่ง</td>
							<td class="center tableData" style="width:130px">โรงเรียน / หน่วยงาน</td>
							<td class="center tableData">เงินเดือน</td>
							<td class="center tableData">เบอร์โทร</td>
							<td class="center tableData">ลายมือชื่อ</td>
						</tr>';

			for($i=0; $i < 9; $i++){
				
					$html .='
						<tr>
							<td class="center tableData">'.($i+1).'</td>
							<td class="tableData">'.($data["guarantor"][$i]["FULLNAME"]??null).'</td>
							<td class="center tableData">'.($data["guarantor"][$i]["MEMBER_NO"]??null).'</td>
							<td class="center tableData">'.($data["guarantor"][$i]["POSITION_DESC"]??null).'</td>
							<td class="tableData">'.($data["guarantor"][$i]["MEMBGROUP_DESC"]??null).'</td>
							<td class="right tableData">'.($data["guarantor"][$i]["SALARY_AMOUNT"]??null).'</td>
							<td class="center tableData">'.($data["guarantor"][$i]["ADDR_MOBILEPHONE"]??null).'</td>
							<td></td>
						</tr>
					';
			}
		$html.='
					</table>
				</div>
				<div style="height:25px; margin-top:30px;">
					<div class="absolute">
						<input class="checkbox" type="checkbox" style="margin-left:0px;" '.($เงินค่าหุ้น??null).'>
					</div>
					<div  style="margin-left:20px;">
						<div class="absolute" style="margin-left:55px; "><div class="data nowrap">'.($data[""]??null).'</div></div>
						เงินค่าหุ้น.................................................................................................................................................................................................................
					</div>
				</div>
				<div style="height:25px;">
					<div class="absolute">
						<input class="checkbox" type="checkbox" style="margin-left:0px;"'.($เงินฝาก??null).'>
					</div>
					<div  style="margin-left:20px;">
						<div class="absolute" style="margin-left:45px; width:165px; "><div class="data nowrap center">'.($data[""]??null).'</div></div>
						<div class="absolute" style="margin-left:255px; width:175px; "><div class="data nowrap center">'.($data[""]??null).'</div></div>
						<div class="absolute" style="margin-left:480px; width:200px; "><div class="data nowrap center">'.($data[""]??null).'</div></div>
						เงินฝาก........................................................เลขบัญชี........................................................จำนวนเงิน...............................................................บาท
					</div>
				</div>
				<div style="margin-left:20px;">
					<div class="absolute" style="margin-left:55px; "><div class="data nowrap">'.($data[""]??null).'</div></div>
					ชื่อบัญชี...................................................................................................................................................................................................................
				</div>
				<div style="height:25px;">
					<div class="absolute">
						<input class="checkbox" type="checkbox" style="margin-left:0px;" '.($เงินฝาก??null).'>
					</div>
					<div  style="margin-left:20px;">
						<div class="absolute" style="margin-left:45px; width:165px; "><div class="data nowrap center">'.($data[""]??null).'</div></div>
						<div class="absolute" style="margin-left:255px; width:175px; "><div class="data nowrap center">'.($data[""]??null).'</div></div>
						<div class="absolute" style="margin-left:480px; width:200px; "><div class="data nowrap center">'.($data[""]??null).'</div></div>
						เงินฝาก........................................................เลขบัญชี........................................................จำนวนเงิน...............................................................บาท
					</div>
				</div>
				<div style="margin-left:20px;">
					<div class="absolute" style="margin-left:55px; "><div class="data nowrap">'.($data[""]??null).'</div></div>
					ชื่อบัญชี...................................................................................................................................................................................................................
				</div>
				<div class="flex" style="height:25px;">
					<div>
						<div class="absolute">
							<input class="checkbox" type="checkbox" style="margin-left:0px;" '.($อหังสาริมทรัพย์??null).'>
						</div>
						<div  style="margin-left:20px;">
							<div class="absolute" style="margin-left:95px; "><div class="data nowrap">'.($data[""]??null).'</div></div>
							อหังสาริมทรัพย์...............................................................................................................
						</div>
					</div>
					<div style="margin-left:450px;">
						<div class="absolute">
						<input class="checkbox" type="checkbox" style="margin-left:0px;">
						</div>
						<div  style="margin-left:20px;">
							<div class="absolute" style="margin-left:65px; "><div class="data nowrap">'.($data[""]??null).'</div></div>
							หลักทรัพย์...............................................................
						</div>
					</div>
				</div>
				<div style="height:25px;">
					<div class="absolute">
						<input class="checkbox" type="checkbox" style="margin-left:0px;" '.($ค้ำประกันอื่นๆ??null).'>
					</div>
					<div  style="margin-left:20px;">
						<div class="absolute" style="margin-left:35px; "><div class="data nowrap">'.($data[""]??null).'</div></div>
						อื่น ๆ........................................................................................................................................................................................................................
					</div>
			</div>
			</div>
		</div>
	';
$html .= '</div>';

//เริ่มหน้า 3
$html .= '<div class="wrapper-page " >
			<div class="center">3</div>
			<div>
				<div class="list" style="letter-spacing:0.35px;">ข้าพเจ้าขอรับรองว่าข้อความที่กล่าวข้างต้นเป็นข้อมูลปัจจุบันและเป็นความจริงทุกประการ และหากมีการแก้ไขเปลี่ยนแปลงข้อมูลใดที่ได้ </div>
				<div style="letter-spacing:0.58px;">ให้ไว่กับสหกรณ์ออมทรัพย์ครูระยอง จํากัด ข้าพเจ้าจะแจ้งให้ทราบทันที หากสหกรณ์ออมทรัพย์ครูระยอง จํากัด อนุมัติเงินกู้ให้แก้ข้าพเจ้า  </div>
				<div style="letter-spacing:0.56px;" class="nowrap">ข้าพเจ้ายินยอมที่จะปฏิบัติให้เป็นไปตามระเบียบและใช้จ่ายเงินตามวัตถุประสงค์ที่ขอกู้ทุกประการ และข้าพเจ้ายินยอมให้สหกรณ์ออมทรัพย์  </div>
				<div style="letter-spacing:0.6px;">ครูระยอง จํากัด ติดต่อสอบถาม สืบค้น ตรวจสอบ สถานภาพ และ/หรือเปิดเผยข้อมูลรายละเอียดบางประการหรือทั้งหมดของข้าพเจ้าจาก  </div>
				<div style="letter-spacing:0.395px;">หน่วยงานราชการ หรือหน่วยงานอื่นที่ เกี่ยวข้อง เช่น กรมสรรพากร กรมทะเบียนการค่า ทะเบียนประวัติราษฎร กรมการปกครอง บริษัทข้อมูล </div>
				<div style="letter-spacing:0.33px;"> เครดิต สถาบันการเงิน บุคคลหรือนิติบุคคลใดก็ได้ใน กรณีที่จําเป็นหรือ ในกรณีที่สหกรณ์ออมทรัพย์ครูระยอง จํากัด เห็นสมควร ตั้งแต่วันทําคํา </div>
				<div> ขอฉบับนี้เป็นต้นไป และข้าพเจ้าตกลงให้ความยินยอมนี้มีผลอยู่ตลอดไป โดยจะไม่ เพิกถอนไม่วาากรณีใด ๆ </div>
			</div>
			<div class="right" style="margin-top:30px; margin-right:80px;">ลงชื่อ....................................................................................ผู้ขอกู้</div>
			<div class="right" style="margin-right:105px;">
				<div class="absolute" style="margin-left:370px; width:268px; "><div class="data nowrap center">'.($data["ชื่อ"]??null).'</div></div>
				(....................................................................................)
			</div>
			<div class="right" style="margin-right:110px;">
				<div class="absolute" style="margin-left:490px; width:268px; "><div class="data nowrap left ">'.($data["วันที่"]??null).'</div></div>
				วันที่................................................................................
			</div>
			<div class="list nowrap">
				<div class="absolute" style="margin-left:110px; width:268px; "><div class="data nowrap left ">'.($data[""]??null).'</div></div>
				<b>เลขบัญชีธนาคารกรุงไทย</b>....................................................................................................................................................................................
			</div>
			
			<div class="border" style="margin-top:20px; padding-bottom:20px; ">
				<div class="center bold" style="border-bottom:1px solid; padding-bottom:5px;">บันทึกการพิจารณาให้ความเห็นของผู้บังคับบัญชา</div>
				<div style="padding-top:5px;">
					<div class="content">วันที่..........................................................</div>
				</div>
				<div class="list content">
					ข้าพเจ้าขอรับรองว่าผู้ขอกู้รายนี้ไม่อยู่ในระหว่างถูกตรวจสอบทางวินัยร้ายแรง หรือมีพฤติการณ์ซึ่งอาจถูกออกจารราชการหรืองานประจำ
				</div>
				<div class="content" style="padding-bottom:10px;">
					หรือถูกดําเนินคดีฟ้องล้มละลาย หรือถูกบังคับคดี 
				</div>
				<div class="nowrap">
					<div class="content">เพิ่มข้อชีแจงอื่น ๆ (ถ้ามี)...................................................................................................................................................................................................</div>
					<div class="content">........................................................................................................................................................................................................................................</div>
					<div class="content">........................................................................................................................................................................................................................................</div>
				</div>
				<div class="flex" style="height:25px; margin-top:20px;">
					<div class="content">ลายมือชื่อผู้บังคับบัญชา..................................................................................................</div>
					<div class="absolute content" style="right:-10px">ตำแหน่ง..............................................................................</div>
				</div>
				<div  style="margin-left:114px; margin-top:-5px;" >
					(..................................................................................................)
				</div>
				<div class="bold content" style="font-size:16pt; margin-top:10px;">
					<u>หนายเหตุ</u> สำหรับผู้บริหารโรงเรียน หรือข้าราชการบำนาญ ให้ลงนามรับรองตนเอง
				</div>
			</div>
			<div class="border" style="margin-top:20px; line-height:18px;">
				<div class="center bold">เอกสารประกอบคำขอกู้</div>
				<div class="content detail">1. สลิปเงินเดือน เดือนปัจจุบัน ขนาดกระดาษ A4 โดยผู้บังคับบัญชา ลงนามรับรอง </div>
				<div class="content detail">2. สําเนาหนาสมุดเงินฝากธนาคารกรุงไทย ชื่อบัญชีผู้กู้ </div>
				<div class="content detail">
					สําเนาบัตรประจําตัวประชาชน หรือ บัตรข้าราชการ ของผู้กู้ และคู่สมรส (ถ้ามี) กรณีสถานะ หม้าย แนบสําเนาใบมรณบัตร / กรณีสถานะ หย่า แนบสําเนาใบหย่า 
				</div>
				<div class="content detail">
					4. กรณีผู้กู้ กับคู่สมรส ไม่ใช่นามสกุลเดียวกัน แนบสําเนาทะเบียนสมรส 
				</div>
				<div class="content detail">
					5. สําเนาบัตรประจําตัวประชาชน หรือ บัตรข้าราชการ ของผู้ค้ําประกัน 
				</div>
				<div class="content detail">
					6. เอกสารเปลี่ยนชื่อ และนามสกุล (ถ้ามี)
				</div>
				<div class="content detail bold">
					กรณีใช้อสังหาริมทรัพย์ หรือหลักทรัพย์ค้ําประกัน มีเอกสารเพิ่มเติม
				</div>
				<div class="content detail">
					1. สําเนาทะเบียนบ้าน ของผู้กู้ และคู่สมรส  
				</div>
				<div class="content detail">
					2. สําเนาบัตรประจําตัวประชาชนเจ้าของหลักทรัพย์ และสําเนาทะเบียนบ้านเจ้าของหลักทรัพย์(ของคู่สมรส ถ้ามี) สําเนาทะเบียนสมรส (ถ้ามี) <br> กรณีสถานะ หม้าย แนบสําเนาใบมรณบัตร / กรณีสถานะ หย่า แนบสําเนาใบหย่า
				</div>
				<div class="content detail">
					3. สําเนาโฉนดที่ดิน 
				</div>
				<div class="content detail">
					4. ใบประเมินราคาที่ดิน / หลักทรัพย์ที่ส่วนราชการออกให้ (ฉบับจริง) ,แผนที่แผนที่สังเขปทางไปที่ดินที่จะนํามาจํานองกับสหกรณ์ , ภาพถ่ายหลักทรัพย์ค้ําประกัน 
				</div>
				<div class="content detail">
					5. สําเนาเอกสารแสดงสิทธิ์ในหลักทรัพย์
				</div>
			</div>';
//ปิดหน้า 3
$html .='</div>';
			


//เริ่มหน้า 4
$html .= '<div class="wrapper-page " >
			<div class="center">4</div>
			<div class="border" style="padding-bottom:5px;">
				<div class="bold center" style="padding-bottom:2px; border-bottom:1px solid">เฉพาะเจ้าหน้าที่สหกรณ์</div>
				<div class="center bold" style="margin-top:5px;">รายการเกี่ยวข้องกับวงเงินกู้ของผู้ขอกู้</div>
				<div class="list">
					<div class="" style="margin-top:10px; font-size:13pt">
						<div class="absolute" style="margin-left:100px; width:278px;"><div class="data nowrap center" style="font-size:13pt">'.($data["จำนวนเงินกู้"]??null).'</div></div>
						<b>จำนวนเงินขอกู้</b>........................................................................<b>บาท</b>
					</div>
				</div>
				<div style="padding-left:33px; padding-right:33px; margin-top:5px;">
					<table style="width:100%; border-collapse: collapse; ">
						<tr>
							<th style="width:50%;" colspan="2">รายละเอียดของผู้กู้</td>
							<th style="width:50%;" colspan="2">ประมาณการ รายการที่หักจากยอดขอกู้</td>
						</tr>
						<tr>
							<td>เงินได้รายเดือน</td>
							<td  style="width:90px;"class="right">&nbsp;</td>
							<td>เงินฝากค้ำประกัน</td>
							<td></td>
						</tr>
						<tr>
							<td>เงินค่าหุ้น</td>
							<td></td>
							<td>ค่าเบี้ยประกัน</td>
							<td></td>
						</tr>
						<tr>
							<td>หนี้เงินกู้สามัญเพื่อการอันจำเป็นฯ</td>
							<td></td>
							<td>ค่าธรรมเนียมกองทุน</td>
							<td></td>
						</tr>
						<tr>
							<td>หนี้เงินกู้สามัญเพื่อชำระหนี้สถาบันการเงินอื่น</td>
							<td></td>
							<td>
								เงินฝากออมทรัพย์พิเศษเพื่อ <br>
								ส่งเสริมการออมของสมาชิก
							</td>
							<td></td>
						</tr>
						<tr>
							<td>เงินกู้ฉุกเฉิน</td>
							<td></td>
							<td>
								ดอกเบี้ยเดือนแรก
							</td>
							<td></td>
						</tr>
						<tr>
							<td>เงินกู้พัฒนาวิชาชีพ</td>
							<td></td>
							<td>
								อื่นๆ
							</td>
							<td></td>
						</tr>
						<tr>
							<td>หนี้เงินกู้กองทุน</td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
						<tr>
							<td>หนี้เงินกู้อื่น ๆ</td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
					</table>
				
				</div>
				<div class="absolute" style="margin-left:185px; font-size:13pt;margin-top:30px;padding-top:30px">'.($data["จำนวนเงินกู้"]??null).'</div>
				<div class="bold content list" style="margin-top:37px; font-size:16pt">
				เงินกู้ที่ได้รับ.....................................................................บาท
				</div>
				
			</div>
			<div class="border" style="margin-top:15px">
				<div class="flex" style="height:130px;">

					<div style="width:50%;">
						<div class="bold center">เจ้าหน้าที่รับคำขอเงินกู้</div>
						<div class="padding-content" >
							<div class="absolute">
								<input class="radio" type="radio">
							</div>
							<div style="margin-left:20px;">เอกสารประกอบคำขอกู้ครบถ้วน ถูกต้อง</div>
						</div>
						<div class="content">
							เจ้าหน้าที่......................................................วันที่.......................................
						</div>
						<div style="margin-left:50px;">(......................................................)</div>
					</div>
					<div   style="width:50%; margin-left:50%">
						<div class="bold center">เจ้าหน้าที่รับคำขอเงินกู้</div>
						<div class="padding-content" >
							<div class="absolute">
								<input class="radio" type="radio">
							</div>
							<div style="margin-left:20px;">สัญญาเงินกู้ สัญญาค้ำประกัน ลงนามครบถ้วน ถูกต้อง</div>
						</div>
						<div class="content">
							เจ้าหน้าที่......................................................วันที่.......................................
						</div>
						<div style="margin-left:50px;">(......................................................)</div>
					</div>
				</div>
			</div>
			<div class="border" style="margin-top:20px">
				<div class="center bold">บันทึกการวินิจฉัย</div>
				<div class="padding-content" >
					<div class="absolute">
						<input class="radio" type="radio">
					</div>
					<div style="margin-left:20px;">พิจารณาโดยผู้จัดการในกรณีที่เป็นไปตามหลักเกณฑ์ที่กําหนดไว้ในระเบียบ ว่าด้วยการให้เงินกู้แก่สมาชิกสหกรณ์และได้รับมอบอํานาจจาก</div>
					<div>คณะกรรมการดําเนินการ </div>
				</div>
				<div class="flex" style="height:65px;">
					<div style="margin-left:30px;">อนุมัติให้กู้ได้.............................................บาท</div>
					<div style="margin-left:50%;">
						<div>....................................................................ผู้จัดการ</div>
						<div>(....................................................................)</div>
					</div>
				</div>
				<div class="padding-content">
					<div class="absolute">
						<input class="radio" type="radio">
					</div>
					<div style="margin-left:20px;"> พิจารณาโดยที่ประชุมคณะกรรมการเงินกู้ ในการประชุมครั้งที่.........................วันที่................................................... </div>
				</div>
				<div class="flex">
					<div style="margin-left:30px;">อนุมัติให้กู้ได้.............................................บาท</div>
					<div style="margin-left:50%;">
						<div>....................................................................เลขานุการกรรมการเงินกู้</div>
						<div>(....................................................................)</div>
					</div>
				</div>
				<div class="padding-content" >
					<div class="absolute">
						<input class="radio" type="radio">
					</div>
					<div style="margin-left:20px;">  พิจารณาโดยที่ประชุมคณะกรรมการดําเนินการ ในการประชุมครั้งที่................วันที่................................................... </div>
				</div>
				<div class="flex">
				<div style="margin-left:30px;">อนุมัติให้กู้ได้.............................................บาท</div>
					<div style="margin-left:50%;">
						<div>....................................................................เลขานุการกรรมการดําเนินการ</div>
						<div>(....................................................................)</div>
					</div>
				</div>
			</div>

';

$html .='</div>';


$html .= '</div>';
	$dompdf = new Dompdf([
		'fontDir' => realpath('../../resource/fonts'),
		'chroot' => realpath('/'),
		'isRemoteEnabled' => true
	]);

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