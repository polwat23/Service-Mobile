<?php
use Dompdf\Dompdf;

$dompdf = new DOMPDF();
function GeneratePDFContract($data,$lib) {

	$data["สัญญาที่"]="";
	$ordinary = $data["coll_02"] > 0 ?'checked' : '';
	$special = $data["coll_01"] > 0 ?'checked' : '';
	$data["เขียนที่"]="แอปพลิเคชัน STOU SAVING";
	$data["วันที่"]=(explode(' ',$lib->convertdate(date("Y-m-d"),"d M Y")))[0];
	$data["เดือน"]=(explode(' ',$lib->convertdate(date("Y-m-d"),"d M Y")))[1];
	$data["ปี"]=(explode(' ',$lib->convertdate(date("Y-m-d"),"d M Y")))[2];
	$data["อายุ"] = (explode(' ',$lib->count_duration($data["birth_date"],"ym")))[0];
	$data["ชื่อ"]=$data["full_name"];
	$data["เลขที่สมาชิก"]=$data["member_no"];
	$data["สังกัด"]=$data["pos_group"];
	$data["บ้านเลขที่"] = "21/4";
	$data["หมู่ที่"] = "4";
	$data["ซอย"] = "14";
	$data["ถนน"] = "หนองป่า";
	$data["ตำแหน่ง"]=$data["position"];
	$data["โทร"]=$data["phone"];
	$data["เงินเดือน"]=$data["salary_amount"];
	$data["เงินได้อื่น"]="";
	$data["รวมเป็นเงิน"]="";
	$data["ภาษี"]="";
	$data["กบข"]="";
	$data["กสจ"]="";
	$data["ขพค"]="";
	$data["ชพค"]="";
	$data["กู้ธนาคาร"]="";
	$data["ค่ารถ"]="";
	$data["สำรอง"]="";
	$data["อัตราดอกเบี้ย"]=$data["int_rate"];
	$data["จำนวน"]="";
	$data["จำนวนขอกู้"]=number_format($data["request_amt"],2);
	$data["จำนวนหุ้น"]=number_format($data["share_amt"]/10);
	$data["มูลค่าหุ้น"]=number_format($data["share_amt"],2);
	$data["จำนวนขอกู้คำอ่าน"]=$lib->baht_text($data["request_amt"]);
	$data["ตำบล"]="ตำบล";
	$data["อำเภอ"]="อำเภอ";
	$data["จังหวัด"]="จังหวัด";
	$data["โทรศัพท์"]="รอ";
	$data["จำนวนเงิน"]=number_format($data["request_amt"],2);
	$data["จำนวนงินคำอ่าน"]=$lib->baht_text($data["request_amt"]);
	$data["วัตถุประสงค์"]=$data["objective"];
	$data["บัญชีสหกรณ์เลขที่"]="รอ";
	$data["เงินกู้สามัญ"]="";//$data["coll_02"];
	$data["เงินกู้พิเศษ"]="";//$data["coll_01"];
	$data["คงเหลือ"]="";
	$data["ประเภท"]="เงินกู้สามัญ";
	$data["กู้โดย"]="check";
	$data["จำนวนงวด"]=$data["period"];
	$data["งวดละ"]=$data["period_payment"];
	$data["วันสิ้นเดือน"]="";
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
				font-size:13pt;
				margin-top:-2px;
				white-space: nowrap;
			}
			body {
				padding: 0 ;
				font-size: 14pt;
				line-height: 20px;
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
			.border{
				border:1px solid ;
			}
			.padding-10{
				padding:10px;
			}
			.padding-20{
				padding:20px;
			}
		</style>';
	//ระยะขอบ กระดาษ
$html .= '<div style="margin:-10px 10px -30px ">';
//หน้าที่ 1
$html .='<div class="wrapper-page">';
$html .= '
		<div style="margin-top:-20px;">
		<div style=" text-align: center; margin-top:5px;"><img src="../../resource/logo/logo.jpg" alt="" width="80" height="0"></div>
		</div>
		<div style="border:0.5px solid #000000; width:160px; position:absolute; left:10px; top:0px; padding:5px; font-size:13pt; line-height:15px ">
			<div> รับวันที่.......................................</div>
			<div>กำหนดรับเงิน.............................</div>
			<div>เวลา...........................................</div>
		</div>
		<div style="border:0.5px solid #000000; width:160px; position:absolute; right:10px; top:-5px; padding:10px 5px; font-size:13pt; line-height:15px ">
		  <div> หนังสือขอกู้ที่...............................</div>
		  <div>วันที่............................................</div>
		  <div>บัญชีกู้เงินที่.................................</div>
		</div>
		<div style="font-size: 15pt; margin-top:10px" class="center bold">คำขอกู้เงินและหนังสือกู้เงินเพื่อเหตุฉุกเฉิน</div>
		<div style="font-size: 15pt;">
		<div class="nowrap" style="position:absolute; margin-left:285px;  "><div class="data">'.($data["สัญญาที่"]??null).'</div></div>
			<div class="center bold">สัญญาที่..........................................</div>
		</div>
		<div style="font-size: 15pt; " class="center bold">สหกรณ์ออมทรัพย์มหาวิทยาลัยสุโขทัยธรรมธิราช จำกัด</div>

		<div style="margin-top:10px; ">
			<div style="position:absolute; margin-left:510px;"><div class="data">'.($data["เขียนที่"]??null).'</div></div>
			<div class="right">
			เขียนที่......................................................</div>
		</div>
		<div >
			<div style="position:absolute; margin-left:495px; "><div class="data">'.($data["วันที่"]??null).'</div></div>
			<div style="position:absolute; margin-left:540px;"><div class="data center"  style="width:85px;">'.($data["เดือน"]??null).'</div></div>
			<div style="position:absolute; margin-left:650px;"><div class="data">'.($data["ปี"]??null).'</div></div>
			<div class="right">
				วันที่........เดือน.......................พ.ศ............
			</div>
		</div>
		
		<div style="display:flex;  height:30px;">
			<div style="">เรียน</div>
			<div style="margin-left:40px;" class="bold">คณะกรรมการดำเนินการ</div>
		</div>
		<div class="list" style="margin-top:10px;">
			<div class="center" style="position:absolute; margin-left:39px; width:260px;"><div class="data">'.($data["ชื่อ"]??null).'</div></div>
			<div class="center" style="position:absolute; margin-left:400px; width:75px;"><div class="data">'.($data["เลขที่สมาชิก"]??null).'</div></div>
				ข้าพเจ้า...........................................................................สมาชิกสหกรณ์เลขที่......................รับราชาการ/ปฏิบัติงานประจำ
		</div>
		<div class="nowrap">
			<div class="center nowrap" style="position:absolute; margin-left:55px; width:200px;"><div class="data">'.($data["ตำแหน่ง"]??null).'</div></div>
			<div class="center nowrap" style="position:absolute; margin-left:285px; width:260px;"><div class="data">'.($data["สังกัด"]??null).'</div></div>
			<div class="center nowrap" style="position:absolute; margin-left:560px; width:120px;"><div class="data">'.($data["โทร"]??null).'</div></div>
			ในตำแหน่ง...........................................................สังกัด................................................................................โทร..............................
		</div>
		<div>
			<div class="center nowrap" style="position:absolute; margin-left:85px; width:120px;"><div class="data">'.($data["เงินเดือน"]??null).'</div></div>
			<div class="nowrap " style="position:absolute; margin-left:320px; "><div class="data">'.($data["เงินได้อื่น"]??null).'</div></div>
			<div class="center nowrap" style="position:absolute; margin-left:519px; width:135px;"><div class="data">'.($data["จำนวน"]??null).'</div></div>

			อัดตราเงินเดือน....................................บาท เงินได้อื่น (ระบุ)................................................ จำนวน.........................................บาท
		</div>
		<div>
		<div class="center nowrap" style="position:absolute; margin-left:55px; width:150px"><div class="data">'.($data["รวมเป็นเงิน"]??null).'</div></div>
			รวมเป็นเงิน.........................................บาท ขอเสนอคำขอกู้เพิ่มเหตุฉุกเฉินดังนี้
		</div>
		<div class="flex">
			<div class="center nowrap" style="position:absolute; margin-left:268px; width:110px;"><div class="data">'.($data["จำนวนขอกู้"]??null).'</div></div>
			<div class="center nowrap" style="position:absolute; margin-left:410px; width:260px;"><div class="data">'.($data["จำนวนขอกู้คำอ่าน"]??null).'</div></div>
			<div class="list nowrap"> ข้อ 1&nbsp;&nbsp;ข้าพเจ้าขอกู้เงินจากสหกรณ์ จํานวน................................บาท (...............................................................................)</div>
		</div>
		<div class="nowrap">
			<div class="nowrap" style="position:absolute; margin-left:178px;"><div class="data">'.($data["วัตถุประสงค์"]??null).'</div></div>
			โดยจะนําไปใช้เพื่อการดังต่อไปนี้........................................................................................................................................................
		</div>
		<div class="list nowrap">
			<div class="nowrap  center" style="position:absolute; margin-left:255px; width:65px;"><div class="data">'.($data["จำนวนหุ้น"]??null).'</div></div>
			<div class="nowrap  center" style="position:absolute; margin-left:381px; width:132px;"><div class="data">'.($data["มูลค่าหุ้น"]??null).'</div></div>
		ข้อ 2&nbsp;&nbsp;ในเวลานี้ข้าพเจ้ามีหุ้นอยู่ในสหกรณ์จํานวน....................หุ้น เป็นเงิน........................................บาท ได้ค้ำประกันเงินกู้
		</div>
		<div class="flex ">
			<div>
				<div style="position:absolute;"> 
					<div class="nowrap center" style="position:absolute; margin-left:80px; margin-top:2px; width:100px; nowrap"><div>'.($data["เงินกู้สามัญ"]??null).'</div></div>
					<input type="checkbox" style="margin-top:7px;" '.($ordinary??null).'> เงินกู้สามัญ............................บาท
				</div>
			</div>
			<div>
				<div style="position:absolute; margin-left:200px;"> 
					<div class="nowrap center" style="position:absolute; margin-left:75px; margin-top:2px; width:100px; nowrap"><div>'.($data["เงินกู้พิเศษ"]??null).'</div></div>
					<input type="checkbox" style="margin-top:7px;" '.($special??null).'> เงินกู้พิเศษ.............................บาท
				</div>
			</div>
			<div>
				<div class="nowrap" style="position:absolute; margin-left:400px;"> 
				<div class="nowrap center" style="position:absolute; margin-left:40px; margin-top:2px; width:110px; nowrap"><div>'.($data["คงเหลือ"]??null).'</div></div>
					 <div style="margin-top:6px;">คงเหลือ................................บาท ข้าพเจ้าส่งเงินค่าหุ้น</div>
				</div>
			</div>
		</div>
		<div class="nowrap">
			<div class="nowrap center" style="position:absolute; margin-left:120px; width:100px;"><div class="data">'.($data["period_share_amt"]??null).'</div></div>
			<div class="nowrap center" style="position:absolute; margin-left:268px;  width:83px;"><div class="data">'.($data["ภาษี"]??null).'</div></div>
			<div class="nowrap center" style="position:absolute; margin-left:400px;  width:110px;"><div class="data">'.($data["กบข"]??null).'</div></div>
			<div class="nowrap center" style="position:absolute; margin-left:565px;  width:95px;"><div class="data">'.($data["กสจ"]??null).'</div></div>
			รายเดือนในอัตราเดือนละ.........................บาท ภาษี.........................บาท กบข.................................บาท กสจ............................บาท
		</div>
		<div class="nowrap">
			<div class="nowrap center" style="position:absolute; margin-left:23px; width:75px;"><div class="data">'.($data["ขพค"]??null).'</div></div>
			<div class="nowrap center" style="position:absolute; margin-left:165px; width:75px;"><div class="data">'.($data["ชพค"]??null).'</div></div>
			<div class="nowrap center" style="position:absolute; margin-left:310px; width:75px;"><div class="data">'.($data["กู้ธนาคาร"]??null).'</div></div>
			<div class="nowrap center" style="position:absolute; margin-left:440px; width:75px;"><div class="data">'.($data["ค่ารถ"]??null).'</div></div>
			<div class="nowrap center" style="position:absolute; margin-left:583px; width:73px;"><div class="data">'.($data["สำรอง"]??null).'</div></div>

			ขพค......................บาท กู้ ชพค......................บาท กู้ธนาคาร.....................บาท ค่ารถ.....................บาท สำรองฯ......................บาท
		</div>
		<div class="list nowrap">
			<div class="nowrap " style="position:absolute; margin-left:402px; "><div class="data">'.($data["อัตราดอกเบี้ย"]??null).'</div></div>
			
			<div style="letter-spacing:-0.15px">ช้อ 3&nbsp;&nbsp; ถ้าข้าพเจ้ารับเงินกู้แล้ว ข้าพเจ้าขอส่งคืนเงินกู้พร้อมดอกเบี้ยอัตราร้อยละ.........ต่อปี โดยชําระคืนเงินต้นงวดละเท่าๆ กัน </div>
		</div>
		<div class="nowrap">
			<div class="nowrap center" style="position:absolute; margin-left:35px; width:45px;"><div class="data">'.($data["จำนวนงวด"]??null).'</div></div>
			<div class="nowrap center" style="position:absolute; margin-left:130px; width:85px;"><div class="data">'.($data["งวดละ"]??null).'</div></div>
			<div class="nowrap center" style="position:absolute; margin-left:318px; width:105px;"><div class="data">'.($data["วันสิ้นเดือน"]??null).'</div></div>


			จํานวน ...........งวด ๆ ละ..........................บาท ณ วันสิ้นเดือน...............................เป็นต้นไป
		</div>
		<div class="list nowrap">
			ข้อ 4 เมื่อข้าพเจ้าได้รับเงินกู้แล้ว ข้าพเจ้ายอมรับผูกพันตามข้อบังคับสหกรณ์ ดังนี้
		</div>
		<div class="sub-list nowrap" style="letter-spacing:-0.3px">
		4.1 ยินยอมให้ผู้บังคับบัญชาหรือเจ้าหน้าที่ผู้จ่ายเงินได้รายเดือนของข้าพเจ้า ที่ได้รับมอบหมายจากสหกรณ์ หักเงินได้รายเดือน
		</div>
		<div>
			ของข้าพเจ้าตามจํานวนงวดชําระหนี้ตามข้อ 3 เพื่อส่งต่อสหกรณ์
		</div>

		<div class="sub-list nowrap" style="letter-spacing:-0.23px">
			4.2 ยอมให้ถือว่าในกรณีใดๆ ดังกล่าวในระเบียบและข้อบังคับของสหกรณ์ออมทรัพย์มหาวิทยาลัยสุโขทัยธรรมาธิราช จํากัด
		</div>
		<div>
			ให้เงินกู้ที่ขอกู้ไปจากสหกรณ์เป็นอันถึงกําหนดส่งคืนโดยสิ้นเชิงพร้อมดอกเบี้ยในทันที โดยมีพักคํานึงถึงกําหนดเวลาที่ตกลงกันไว้
		</div>
		<div class="sub-list nowrap"  style="letter-spacing:-0.1px;">
		4.3 ถ้าประสงค์จะขอลาออก หรือย้ายจากราชการ หรืองานประจํา ตามข้อบังคับสหกรณ์ออมทรัพย์มหาวิทยาลัยสุโขทัย-
		</div>
		<div class="nowrap" style="letter-spacing:-0.19px;">
			ธรรมาธิราช จํากัด ข้าพเจ้าจะแจ้งเป็นหนังสือให้สหกรณ์ทราบ และจะจัดการชําระหนี้ซึ่งมีอยู่ต่อสหกรณ์ให้เสร็จสิ้นเสียก่อน ถ้าข้าพเจ้าไม่
		</div>
		<div class="nowrap" style="letter-spacing:-0.15px;">
			จัดการชําระหนี้สินให้เสร็จสิ้นตามที่กล่าวข้างต้น เมื่อข้าพเจ้าได้ลงชื่อรับเงินเดือน ค่าจ้าง เงินสะสม บําเหน็จ บํานาญ เงินทุนเลี้ยงชีพหรือ
		</div>
		<div class="nowrap" style="letter-spacing:-0.11px;">
			เงินอื่นใดในหลักฐานที่ทางราชการหรือหน่วยงานเจ้าสังกัต จะจ่ายเงินให้แก่ข้าพเจ้า ข้าพเจ้ายินยอมให้เจ้าหน้าที่ผู้จ่ายเงินดังกล่าวหักเงิน 
		</div>
		<div class="nowrap">
			ชําระหนี้พร้อมด้วยดอกเบี้ยส่งชําระหนี้ต่อสหกรณ์ให้เสร็จสิ้นเสียก่อนได้
		</div>
		<div class="right" style="margin-top:20px;">
			(ลงชื่อ)...........................................................สมาชิกผู้ขอกู้เงิน
		</div>

		<div style="border:2px solid; width:100%; height:210px; margin-top:20px; padding:5px;">
			<div class="bold " style="font-size:15pt;"><u>กรณีมอบอำนาจให้ผู้่อื่นรับเงินกู้แทน</u></div>
			<div class="nowrap list" style="margin-top:15px;">
				 ข้าพเจ้าได้มอบอำนาจให้...............................................................................ตำแหน่ง.......................................................
			</div>
			<div>
				สังกัด.............................................................โทร....................................เป็นผู้รับเงินกู้ฉุกเฉินแทนข้าพเจ้า
			</div>
			<div>
				<div style="width:50%; position:absolute;">
					<div class="list" style="margin-top:10px;" >
						(ลงชื่อ)....................................................ผู้กู้
					</div>
					<div style="margin-left:85px;">(....................................................)</div>
					<div class="list" >
						(ลงชื่อ)....................................................ผยาน
					</div>
					<div style="margin-left:85px;">(....................................................)</div>
				</div>
				<div style="width:50%; margin-left:50%; position:absolute;">
					<div class="right" style="margin-top:10px;" >
						(ลงชื่อ)....................................................ผู้รับมอบอำนาจ
					</div>
					<div style="margin-left:70px;">
					(....................................................)
					</div>
					<div class="right" style="margin-top:10px; margin-right:50px;" >
						(ลงชื่อ)....................................................ผยาน
					</div>
					<div style="margin-left:70px;">
					(....................................................)
					</div>
				</div>
			</div>
		</div>
		';
		//ปิดหน้า1
		$html .='</div>';




	//เริ่มหน้า 2 	
	$html .='
	<div class="wrapper-page">
	<div  style="border:1px solid;">
		<div class="center bold" style="font-size:18pt; padding-top:20px;">
				(เฉพาะเจ้าหน้าที่สหกรณ์ฯ)
		</div>
		<div class="center" style="margin-top:10px;">
			จำนวนเงินขอกู้....................................บาท
		</div>
		<div style="margin-top:10px;">
		 <table style="width:100%;  border-collapse: collapse;">
			<tr>
				<th class="center border padding-10">อัตราเงินเดือน</th>
				<th class="center border padding-10"><div>จำกัดวงเงินกู้</div><div>ฉุกเฉิน</div></th>
				<th class="center border padding-10"><div>จำนวนเงิน</div><div>อนุมัติให้กู้</div></th>
				<th class="center border padding-10"><div>ชำระ</div><div>หนี้เดิม</div></th>
				<th class="center border padding-10"><div>จำนวนเงิน</div><div>ที่ได้รับ</div></th>
			</tr>
			<tr>
				<th class="center border padding-10">&nbsp;</th>
				<th class="center border padding-10">&nbsp;</th>
				<th class="center border padding-10">&nbsp;</th>
				<th class="center border padding-10">&nbsp;</th>
				<th class="center border padding-10">&nbsp;</th>
			</tr>
		 </table>
		</div>
	</div>
	<div style="margin-top:20px;">
		<div style="margin-left:100px;">1. &nbsp;ผู้กู้เคยผิดนัดการส่งเงินงวดชำระหนี้หรือขาดส่งค่าหุ้นรายเดื่อนหรือไม่.....................</div>
		<div style="margin-left:100px;">2. &nbsp;ภาระส่งชำระต่อสังกัด.................................................................บาท</div>
		<div style="margin-left:100px;">3. &nbsp;ภาระส่งชำระต่อสหกรณ์ ฯ รวม..................................................บาท</div>
		<div style="margin-left:100px;">4. &nbsp;ชำระเงินกู้ฉุกเฉินสัญญาเดิมแล้ว..................................................งวด</div>
		<div style="margin-left:100px;">5. &nbsp;เงินเดือนคงเหลือ.........................................................................บาท</div>
	</div>
	<div  style="margin-top:30px; margin-left:342px;">
	(ลงชื่อ)....................................................เจ้าหน้าที่ผู้ตรวจสอบ
	</div>
	<div style="margin-left:379px;">....................................................</div>
	<div style="margin-right:380px;" class="right">เห็นควร อนุมัติ/ไม่อนุมัติ</div>
	<div  style="margin-left:342px;">
	(ลงชื่อ)....................................................หน้าฝ่ายสินเชื่อ
	</div>
	<div style="margin-left:379px;">....................................................</div>
	<div style="margin-right:380px;" class="right">อนุมัติ/ไม่อนุมัติ</div>
	<div  style="margin-left:342px;">
	(ลงชื่อ)....................................................ผู้อนุมัติ
	</div>
	<div style="margin-left:379px;">....................................................</div>

	<div style="margin-top:30px;  font-size:18pt;" class="border center bold padding-20">
			การรับเงิน
	</div>
	<div  style="border-left:1px solid; border-right:1px solid; border-bottom:1px solid; height:310px;">
		<div style="position:absolute; margin-left:50%; height:310px; border-right:1px solid;"></div>
		<div style="position:absolute; margin-left:50%; width:50%; padding: 0 5px; 0 5px;">
			<div style="margin-top:30px;">ได้จ่ายเงินกู้ไปแล้วถูกต้องแล้ว</div>
			<div  style="margin-top:25px; margin-left:65px;">(ลงชื่อ)..........................................เจ้าหน้าที่สินเชื่อ</div>
			<div  style="margin-left:102px;">..........................................</div>
			<div  style="margin-top:25px; margin-left:65px;">(ลงชื่อ)..........................................เจ้าหน้าการเงิน</div>
			<div  style="margin-left:102px;">..........................................</div>
		</div>
		<div style="position:absolute: width:50%; padding: 0 5px; 0 5px;">
			<div class="nowrap" style=" margin-top:30px;">ข้าพเจ้า.....................................................................................</div>
			<div class="nowrap">ได้รับเงินกู้จำนวน................................................................บาท</div>
			<div class="nowrap">(................................................................................................)</div>
			<div class="nowrap">ไปเป็นการถูกต้องแล้ว ณ วันที่...................................................</div>
			<div class="nowrap" style="width:50%; margin-top:30px; margin-left:95px;">(ลงชื่อ)...............................................ผู้รับเงิน</div>
			<div class="nowrap center" style="width:50%; margin-top:30px;">หมายเลขบัตรประจำตัวประชาชน/บัตรข้าราชการ</div>
			<div class="nowrap center" style="width:50%; margin-top:20px;">......................................................</div>
		</div>

	</div>
	';
	//ปิดหน้า 2
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