<?php
use Dompdf\Dompdf;

$dompdf = new DOMPDF();
function GeneratePDFContract($data,$lib) {
	
$current_date = $lib->convertdate(date("Y-m-d"),"D m Y");
$current_date_arr = explode(" ",$current_date);

$data["เขียนที่"]="แอพพลิเคชั่น PHY SAVING";
$data["วันที่"]=$current_date_arr[0];
$data["เดือน"]=$current_date_arr[1];
$data["ปี"]=$current_date_arr[2];
$data["อายุ"] = $data["birth_date_count"];
$data["ชื่อ"]=$data["full_name"];
$data["เลขที่สมาชิก"]=$data["member_no"];
$data["สังกัด"]=$data["pos_group"];
$data["บ้านเลขที่"] = $data["addr_no"];
$data["หมู่ที่"] = $data["addr_moo"];
$data["ซอย"] = $data["addr_soi"];
$data["ถนน"] = $data["addr_road"];


$data["ตำแหน่ง"]=$data["position"];
$data["ตำบล"]=$data["tambol"];
$data["อำเภอ"]=$data["district"];
$data["จังหวัด"]=$data["province"];
$data["โทรศัพท์"]=$data["phone"];
$data["จำนวนเงิน"]=$data["request_amt"];
$data["จำนวนงินคำอ่าน"]=$lib->baht_text($data["request_amt"]);
$data["วัตถุประสงค์"]=$data["objective"];
$data["บัญชีสหกรณ์เลขที่"]=$data["deptaccount_no_coop"];
if($data["loantype_code"] == "21"){
	$data["ประเภท"]="normal";
}else if($data["loantype_code"] == "21"){
	$data["ประเภท"]="atm";
}else if($data["loantype_code"] == "22"){
	$data["ประเภท"]="loan";
}
$data["กู้โดย"]="ac_coop";

//ประเภทเงินกู้ 
$type = $data["ประเภท"]??null;
if($type=='atm'){
	//ฉุกเฉิน atm
	$atm = 'checked';
  }else if($type=='normal'){
  // ฉุกเฉินปกติ
	$normal = 'checked';
  }else if($type =='loan'){
	  //5% ของทุนเรือนหุ้นต้นปี
	  $loan = 'checked';
  }

//ขอกู้โดย
$asset = $data["กู้โดย"]??null;
if($asset=='cash'){
	//เงินสด
	$cash = 'checked';
}else if($asset=='check'){
  // เช็ค
	$check = 'checked';
}else if($asset =='ac_coop'){
	  //บัญชีสหกรณ์
	  $ac_coop = 'checked';
}


//html
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
		.bold{
			font-weight:bold;
		}
		* {
			font-family: TH Niramit AS;
		}
		.center{
			text-align:center;
		}
		.left{
			text-align:right;
		}
		.right{
			text-align:right;
		}
		.data{
			font-size:15pt;
			margin-top:-2px;
			font-weight: bold;
		}
		body {
			padding: 0 ;
			font-size: 16pt;
			line-height: 25px;
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
		.list{
			padding-left:50px;
		}
		.sub-list{
			padding-left:72px;
		}
		.flex{
			display:flex;
			height: 30px;
		}
	</style>';
//ระยะขอบ กระดาษ
$html .= '<div style="margin:0px 10px -20px 10px; ">';

//หน้าที่ 1
$html .='<div class="wrapper-page">';
$html .= '
		<div style="margin-top:-20px;">
		<div style=" text-align: center; margin-top:5px;"><img src="../../resource/logo/logo.jpg" alt="" width="90" height="0"></div>
		</div>
		<div style="border:0.5px solid #000000; width:180px; position:absolute; left:0px; top:0px; padding:5px; 5px ">
			<div>
				ทะเบียนที่รับ............./..............
			</div>
			<div>
				วันที่......../............./..................
			</div>
		</div>
		<div style="font-size: 18pt; margin-top:10px" class="center bold">คำขอกู้เงินและหนังสือกู้เงินเพื่อเหตุฉุกเฉิน</div>
		<div style="font-size: 17pt; margin-top:5px" class="center bold">สหกรณ์ออมทรัพย์ครูพะเยา จำกัด</div>
		<div style="margin-left:380px ">
			<div style="position:absolute; margin-left:50px;"><div class="data">'.($data["เขียนที่"]??null).'</div></div>
			เขียนที่......................................................
		</div>
		<div style="margin-left:380px">
			<div style="position:absolute; margin-left:35px; "><div class="data">'.($data["วันที่"]??null).'</div></div>
			<div style="position:absolute; margin-left:85px;"><div class="data center"  style="width:85px;">'.($data["เดือน"]??null).'</div></div>
			<div style="position:absolute; margin-left:207px;"><div class="data">'.($data["ปี"]??null).'</div></div>
			วันที่........เดือน.......................พ.ศ............
		</div>
		<div style="display:flex;  height:30px;">
			<div style="">เรียน</div>
			<div class="list">คณะกรรมการดำเนินการสหกรณ์ออมทรัพย์ครูพะเยา จำกัด</div>
		</div>
		<div class="list">
			<div class="center" style="position:absolute; margin-left:65px; width:310px;"><div class="data">'.($data["ชื่อ"]??null).'</div></div>
			<div class="center" style="position:absolute; margin-left:500px; width:90px;"><div class="data">'.($data["เลขที่สมาชิก"]??null).'</div></div>
			1.&nbsp;&nbsp;ข้าพเจ้า.................................................................................สมาชิกเลขทะเบียนที่......................สังกัด/
		</div>
		<div>
			<div style="position:absolute; margin-left:55px;"><div class="data">'.($data["สังกัด"]??null).'</div></div>
			<div style="position:absolute; margin-left:260px;"><div class="data">'.($data["ตำแหน่ง"]??null).'</div></div>
			<div style="position:absolute; margin-left:398px;"><div class="data">'.($data["อำเภอ"]??null).'</div></div>
			<div style="position:absolute; margin-left:560px;"><div class="data">'.($data["จังหวัด"]??null).'</div></div>

			โรงเรียน........................................ตำแหน่ง..........................อำเภอ...............................จังหวัด................................
		</div>
		<div>
			<div style="position:absolute; margin-left:55px;"><div class="data">'.($data["โทรศัพท์"]??null).'</div></div>
			โทรศัพท์.........................
		</div>
		<div class="flex">
			<div class="list">2.&nbsp;&nbsp;ข้าพเจ้าขอกู้ยืมเงินเพื่อเหตุฉุกเฉิน ประเภท</div>
			<div style="margin-left:350px;">
				<div style="position:absolute; top:-4px;"> 
					<input type="checkbox" style="margin-top:7px;" '.($atm??null).'> ฉุกเฉิน ATM  
				</div>
			</div>
		</div>
		<div style="height:30px;">
			<div style="margin-left:350px;">
				<div style="position:absolute; top:-4px;"> 
					<input type="checkbox" style="margin-top:7px;" '.($normal??null).'> ฉุกเฉินปกติ
				</div>
			</div>
		</div>
		<div style="height:30px;">
			<div style="margin-left:350px;">
				<div style="position:absolute; top:-4px;"> 
					<input type="checkbox" style="margin-top:7px;" '.($loan??null).'> 5% ของทุนเรือนหุ้นต้นปี (กู้เงินปันผล)
				</div>
			</div>
		</div>
		<div class="list nowrap">
			<div style="position:absolute; margin-left:255px;"><div class="data">'.($data["จำนวนเงิน"]??null).'</div></div>
			<div class="nowrap center" style="position:absolute; margin-left:380px; width:250px;"><div class="data">'.($data["จำนวนงินคำอ่าน"]??null).'</div></div>
			3.&nbsp;&nbsp;ข้าพเจ้าขอกู้ขืมเงินของสหกรณ์ จำนวน.........................บาท (...............................................................)
		</div>
		<div class="list">
			<div class="nowrap" style="position:absolute; margin-left:150px;"><div class="data">'.($data["วัตถุประสงค์"]??null).'</div></div>
			4.&nbsp;&nbsp;วัตถุประสงค์ในการกู้...........................................
		</div>
		<div class="list">5.&nbsp;&nbsp;เมื่อข้าพเจ้าได้รับเงินกู้แล้ว ข้าพเจ้ายอมรับข้อผูกพันตามข้อบังคับของสหกรณ์ฯ ดังนี้</div>
		<div class="sub-list nowrap" style="letter-spacing:0.34px">5.1&nbsp;ข้าพเจ้ายินยอมให้ผู้บังคับบัญชา หรือเจ้าหน้าที่ผู้จ่ายเงินได้รายเดือนของข้าพเจ้า หักเงินได้ราย</div>
		<div>เดือนของข้าพเจ้า ส่งให้กับสหกรณ์ฯ เป็นงวดรายเดือน ตามงวดชำระหนี้ได้กำหนดในแบบอนุมัติเงินกู้</div>
		<div class="sub-list nowrap" style="letter-spacing:1.12px">5.2&nbsp;ข้าพเจ้ายอมให้ถือว่ากรณีใดๆ ดังกล่าวให้ข้อบังคับ 18(1)(2)(3) และ (4) ให้กู้ที่ได้รับ</div>
		<div style="letter-spacing:0.5px">จากสหกรณ์ เป็นอันกำหนดส่งคืนโดยสิ้นเชิงพร้อมดอกเบี้ย โดยมิพักคำนึงถึงกำหนดเวลาที่ตกลงกันไว้</div>
		<div class="sub-list nowrap" style="letter-spacing:0.7px">5.3&nbsp;ถ้าข้าพเจ้าประสงค์จะลาออกหรือย้ายจากราชการ หรืองานประจำตามข้อบังคับข้อ 33(3)</div>
		<div class="nowrap" style="letter-spacing:0.6px">จะแจ้งเป็นหนังสือให้สหกรณ์ฯทราบ และจะจัดการชําระหนี้สินซึ่งมีอยู่ต่อสหกรณ์ฯ ให้เสร็จสิ้นเสียก่อน ถ้า</div>
		<div class="nowrap" style="letter-spacing:0.57px">ข้าพเจ้าไม่จัดการชําระหนี้ให้เสร็จสิ้นตามที่กล่าวข้างต้น เมื่อข้าพเจ้าได้ลงชื่อรับเงินเดือน ค่าจ้าง เงินสะสม </div>
		<div class="nowrap" style="letter-spacing:0.57px">บําเหน็จ บํานาญ เงินทุนสํารองเลี้ยงชีพ หรือเงินอื่นใดในหลักฐานที่ทางราชการ หรือหน่วยงานเจ้าสังกัดจะ </div>
		<div class="nowrap" style="letter-spacing:0.13px">จ่ายเงินให้แก่ข้าพเจ้า ข้าพเจ้ายินยอมให้เจ้าหน้าที่ผู้จ่ายเงินดังกล่าว หักเงินชําระหนี้พร้อมด้วยดอกเบี้ยส่งชําระหนี้ </div>
		<div>ต่อสหกรณ์ฯ ให้เสร็จสิ้นเสียก่อน</div>
		<div class="flex list">
			<div>6.&nbsp;&nbsp;ข้าพเจ้าขอรับเงินกู้โดย </div>
			<div style="margin-left:170px;">
				<div style="position:absolute; top:-2px;"> 
					<input type="checkbox" style="margin-top:7px;" '.($check??null).'> เช็ค
				</div>
			</div>
			<div style="margin-left:220px;">
				<div style="position:absolute; top:-2px;"> 
					<input type="checkbox" style="margin-top:7px;" '.($cash??null).'> เงินสด
				</div>
			</div>
			<div style="margin-left:290px;">
				<div style="position:absolute; top:-2px;"> 
					<div class="nowrap" style="position:absolute; margin-left:160px;"><div class="data">'.($data["บัญชีสหกรณ์เลขที่"]??null).'</div></div>
					<input type="checkbox" style="margin-top:7px;" '.($ac_coop??null).'> เข้าบัญชีสหกรณ์เลขที่..............................
				</div>
			</div>
		</div>
		<div style="border:2px solid; width:100%; height:210px;">
		   <div >
				<div style="width:60%; position:absolute; padding-left:10px;">
					<div class="bold">บันทึกความเห็นของผู้บังคับบัญชา</div>
					<div>......................................................................................................</div>
					<div>......................................................................................................</div>
					<div style="margin-top:10px;">ลงชื่อ............................................</div>
					<div style="margin-left:30px;">(............................................)</div>
					<div>ตำแหน่ง.......................................</div>
					<div class="bold">(สมาชิกที่ดำรงตำแหน่งหัวหน้าหน่วยงานให้รองรับตนเองได้)</div>
		
				</div>
				<div style="width:40%; margin-left:60%; postiont:absolute; ">
					<div style="position:absolute; border-left:2px solid; height:210px;"></div>
					<div class="bold" style="margin-top:30px; margin-left:10px;">ลงชื่อ..................................ผู้ขอกู้</div>
					<div class="bold" style="margin-left:40px;">(..................................)</div>

					<div class="bold" style="margin-top:30px; margin-left:10px;">ลงชื่อ..................................ผู้ขอกู้</div>
					<div class="bold" style="margin-left:40px;">(..................................)</div>
				</div>
			</div>
			</div>

		';
		//ปิดหน้า1
		$html .='</div>';




	//เริ่มหน้า 2 	
		$html .='<div style="margin-left:90px;" class="wrapper-page">
		<div style="height:30px;"></div>
		<div style="margin-top:50px; height:25px;">
			<div style="border:0.5px solid #000000; width:180px; position:absolute; left:70px; top:30px; padding:5px; 5px ">
				<div>
					ทะเบียนที่รับ............./..............
				</div>
				<div>
					วันที่......../............./..................
				</div>
			</div>
	  </div>
	  <div class="center bold" style="font-size:18pt;">
			หนังสือมอบอำนาจ
	  </div>
	  <div class="right">
		 เขียนที่............................................................
	  </div>
	  <div class="right">
		 วันที่............เดือน................................พ.ศ..................
	  </div>
	  <div class="list nowrap">
			โดยหนังสือฉบับนี้ข้าพเจ้า.....................................................................................................
	  </div>
	  <div>
			ถือบัตรประจำตัว
			<div style="position:absolute; top:-4px; margin-left:10px;"> 
				<input type="checkbox" style="margin-top:7px;"> ข้าราชการ
			</div>
			<div style="position:absolute; top:-4px; margin-left:100px;"> 
				<input type="checkbox" style="margin-top:7px;"> ประชาชน เลขที่.....................................................................
			</div>
	  </div>
	  <div class="nowrap">
		สัญชาติ...............เชื้อชาติ...............อยู่บ้านเลขที่...........ถนน...........................................หมู่ที่...........
	  </div>
	<div>
	  ตำบล........................อำเภอ........................จังหวัด...........................
	</div>
	<div class="list nowrap">
	  ขอมอบอำนาจให้..................................................................................................................
	</div>
	<div>
		ถือบัตรประจำตัว
		<div style="position:absolute; top:-4px; margin-left:10px;"> 
			<input type="checkbox" style="margin-top:7px;"> ข้าราชการ
		</div>
		<div style="position:absolute; top:-4px; margin-left:100px;"> 
			<input type="checkbox" style="margin-top:7px;"> ประชาชน เลขที่.....................................................................
		</div>
		<div class="nowrap">
			สัญชาติ...............เชื้อชาติ...............อยู่บ้านเลขที่...........ถนน...........................................หมู่ที่...........
		</div>
		<div>
			 ตำบล........................อำเภอ........................จังหวัด...........................
		</div>
		<div class="nowrap" style="letter-spacing:0.25px;">
			อํานาจรับเงินกู้ ซึ่งข้าพเจ้าเป็นผู้กู้จากสหกรณ์ออมทรัพย์ครูพะเยา จํากัด ตามคําขอกู้เงินเพื่อเหตุ 
		</div>
		<div class="nowrap" style="letter-spacing:0.3px;">
			ฉุกเฉินตามทะเบียนรับข้างต้น และข้าพเจ้าขอรับผิดชอบในการใดๆที่ผู้รับมอบอํานาจของข้าพเจ้า 
		</div>
		<div>
			ได้กระทําไปตามที่มอบอํานาจนี้เสมือนหนึ่งว่าข้าพเจ้าได้กระทําการด้วยตัวเอง
		</div>
		
	</div>
	<div style="margin-top:40px; margin-left:150px;">
		ลงชื่อ...............................................ผู้มอบอำนาจ
	</div>
	<div style="margin-left:179px;" >
		(...............................................)
	</div>

	<div  style="margin-top:25px; margin-left:150px;">
		ลงชื่อ...............................................ผู้รับมอบอำนาจ
	</div>
	<div style="margin-left:179px;" >
		(...............................................)
	</div>


	<div  style="margin-top:25px; margin-left:150px;">
		ลงชื่อ...............................................ผยาน
	</div>
	<div style="margin-left:179px;" >
		(...............................................)
	</div>

	<div  style="margin-top:25px; margin-left:150px;">
		ลงชื่อ...............................................ผยาน
	</div>
	<div style="margin-left:179px;" >
		(...............................................)
	</div>

	<div style="border-bottom:2px solid; margin-top:60px;"></div>
	<div class="bold" style="padding-top:10px; font-size:18pt;">
		***แนบสำเนาบัตรประจำตัวของผู้รับมอบอำนาจ
	</div>';
	//ปิดหน้า 2
	$html .='</div>';



	//เริ่มหน้า 3
	$html .='<div class="wrapper-page" style="margin-left:20px; margin-right:20px;">';
	$html .='
		<div>
			<div class="bold center" style="font-size:18pt; margin-top:40px;">
				หนังสือยินยอมหักเงินเดือน (ผู้กู้)
			</div>
		
			<div  style="margin-top:20px;">
				<div style="position:absolute; margin-left:445px;"><div class="data nowrap">'.($data["เขียนที่"]??null).'</div></div>
				<div class="right">ทำที่....................................................</div>
			</div>
			<div>
				<div style="position:absolute; margin-left:355px;"><div class="data">'.($data["วันที่"]??null).'</div></div>
				<div style="position:absolute; margin-left:440px;"><div class="data">'.($data["เดือน"]??null).'</div></div>
				<div style="position:absolute; margin-left:580px;"><div class="data">'.($data["ปี"]??null).'</div></div>
				<div class="right">วันที่.............เดือน.................................พ.ศ...................</div>
			</div>
			<div class="list nowrap">
				<div style="position:absolute; margin-left:75px;  width:230px;"><div class="data center">'.($data["ชื่อ"]??null).'</div></div>
				<div style="position:absolute; margin-left:340px;"><div class="data">'.($data["อายุ"]??null).'</div></div>
				<div style="position:absolute; margin-left:505px;"><div class="data">'.($data["บ้านเลขที่"]??null).'</div></div>
				<div style="position:absolute; margin-left:565px;"><div class="data">'.($data["หมู่ที่"]??null).'</div></div>
				ข้าพเจ้า(ผู้กู้).............................................................อายุ........ปี ปัจจุบันอยู่บ้านเลขที่.........หมู่ที่.......
			</div>
			<div class="nowrap">
				<div style="position:absolute; margin-left:60px; width:120px;"><div class="data center nowrap">'.($data["ซอย"]??null).'</div></div>
				<div style="position:absolute; margin-left:210px; width:180px;"><div class="data center nowrap">'.($data["ถนน"]??null).'</div></div>
				<div style="position:absolute; margin-left:460px; width:180px;"><div class="data center nowrap">'.($data["ตำบล"]??null).'</div></div>
				ตรอก/ซอย..............................ถนน...............................................ตำบล/แขวง...............................................
			</div>
			<div class="nowrap">
			<div style="position:absolute; margin-left:60px; width:170px;"><div class="data center nowrap">'.($data["อำเภอ"]??null).'</div></div>
			<div style="position:absolute; margin-left:270px;"><div class="data  nowrap">'.($data["จังหวัด"]??null).'</div></div>
				อำเภอ/เขต.........................................จังหวัด...........................เป็นสมาชิกสหกรณ์ออมทรัพย์ครูพะเยา จํากัด
			</div>
			<div class="nowrap">
				<div style="position:absolute; margin-left:110px; width:130px;"><div class="data center nowrap">'.($data["เลขที่สมาชิก"]??null).'</div></div>
				เลขทะเบียนสมาชิก..................................มีความประสงค์ให้&nbsp;&nbsp;ส่วนราชการหักเงินและส่งให้สหกรณ์ออมทรัพย์
			</div>
			<div class="nowrap">ที่ข้าพเจ้าเป็นสมาชิก จึงมีหนังสือให้ความยินยอม ดังนี้ </div>
			<div class="nowrap list" style="letter-spacing:0.15px;">ข้อ 1. ยินยอมให้เจ้าหน้าที่ผู้จ่ายเงิน หักเงินเดือน ค่าจ้าง หรือเงินบํานาญที่ข้าพเจ้าจึงได้รับจากทาง </div>
			<div class="nowrap" style="letter-spacing:0.17px;">ราชการตามที่สหกรณ์ออมทรัพย์ครูพะเยา จํากัด แจ้งในแต่ละเดือนและส่งชําระหนี้ ชําระค่าหุ้นหรือเงินอื่น  </div>
			<div class="nowrap">แล้วแต่กรณี ให้สหกรณ์ออมทรัพย์ครูพะเยา จํากัด แทนข้าพเจ้า</div>
			<div class="nowrap list" style="letter-spacing:-0.08px;">ข้อ 2. กรณีข้าพเจ้าพ้นจากการเป็นข้าราชการ/ลูกจ้าง และได้รับบําเหน็จ ข้าพเจ้าจะจัดการชําระหนี้ซึ่ง </div>
			<div class="nowrap" style="letter-spacing:-0.08px;">มีอยู่ต่อสหกรณ์ฯให้เสร็จสิ้นเสียก่อน โดยจะยินยอมให้เจ้าหน้าที่ผู้จ่ายเงิน หักเงินจากบําเหน็จและหรือเงินอื่นใด</div>
			<div class="nowrap" style="letter-spacing:-0.01px;"> ที่ข้าพเจ้าจึงได้รับจากทางราชการ และส่งเงินให้กับสหกรณ์ออมทรัพย์ครูพะเยา จํากัด ตามจํานวนที่ได้รับแจ้ง</div>
			<div>แทนข้าพเจ้า</div>
			<div class="nowrap list" style="letter-spacing:0.1px;">ข้อ 3. การหักเงินเดือน ค่าจ้าง เงินบํานาญ หรือเงินบําเหน็จ ไม่ว่ากรณีใด เมื่อได้หักชําระหนี้แก่ทาง </div>
			<div> ราชการแล้ว (ถ้ามี) ยินยอมให้หักเงินส่งให้สหกรณ์ออมทรัพย์ครูพะเยา จํากัด ก่อนเป็นอันดับแรก</div>
			<div class="nowrap list">ข้อ 4. หนังสือยินยอมนี้ให้มีผลตั้งแต่บัดนี้เป็นต้นไป และข้าพเจ้าสัญญาว่า จะไม่ถอนการให้คํายินยอม </div>
			<div class="nowrap" style="letter-spacing:0.84px;">ทั้งหมดหรือบางส่วน เว้นแต่จะได้รับคํายินยอมเป็นหนังสือจากสหกรณ์ออมทรัพย์ครูพะเยา จํากัด </div>
			<div class="nowrap" style="letter-spacing:0.2px;">หนังสือยินยอมฉบับนี้ทําขึ้นโดยความสมัครใจของข้าพเจ้าเอง ได้ตรวจสอบข้อความและถ้อยคําในหนังสือนี้ </div>
			<div>ทั้งหมดแล้ว ตรงตามเจตนารมณ์ของข้าพเจ้าทุกประการ จึงลงลายมือชื่อไว้เป็นหลักฐาน</div>

			<div class="center" style="margin-top:80px;">ลงชื่อ..................................................ผู้ให้ความยินยอม(ผู้กู้)</div>
			<div style="margin-left:176px;">
			<div style="position:absolute; margin-left:5px; width:190px;"><div class="data center nowrap">'.($data["ชื่อ"]??null).'</div></div>
				(..................................................)
			</div>
			<div style="margin-top:70px; height:70px;">
				<div style="width:50%;position:absolute; ">
					<div class="center">ลงซื่อ.......................................พยาน</div>
					<div class="center">(...........................................)</div>
				</div>
				<div style="width:50%; position:absolute; right:23">
					<div class="center">ลงซื่อ.......................................พยาน</div>
					<div class="center">(...........................................)</div>
				</div>
			</div>
		</div>
	';

	//ปิดหน้า3
	$html .='</div>';

	//ระยะขอบ
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