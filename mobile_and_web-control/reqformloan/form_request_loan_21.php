<?php
use Dompdf\Dompdf;

$dompdf = new DOMPDF();
function GeneratePDFContract($data,$lib) {
	$arrRequestDate = explode(' ',$lib->convertdate(date('Y-m-d'),'d M Y'));
	$html = '
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
		.text-center{
		  text-align:center;
		}
		.text-right{
		  text-align:right;
		}
		body{
		  font-size:14pt;
		}

		.indent{
		  padding-left:90px;
		}
		.sub-indent{
		  text-indent:80px;
		}
		.text-right{
		text-align:right;
		}
		.text-center{
		text-align:center
		}
		table{
			width: 100%;
			border: none;
		}
		td{
			border: none;
			padding: 0px; 
		}
	</style>
	';
	//--------------------------page 1-------------------------------------------------------------------//
	$html .= '
		  <div style=" height:auto; margin-left:0cm; margin-top:-15px; margin-right:0cm;padding-bottom: 60px;">
	 ';
	//--------------------  head  ----------------------------------------------------------------------

	//head  (logo) 
	$html .= '
	  <div class="text-center" style="transform: translateY(60px)">
		<img src="../../resource/logo/logo.jpg" style="width:90px;"/>
	  </div>
	';

	$html .= '
		<div style="position: absolute; right:-10px; top: 0px;">
			<div style="border: 1px solid black;width: 200px;height: 95px;line-height:0.5em;padding: 0 5px;">
				<p>หนังสือกู้ที่ ฉ...........................................</p>
				<p>วันที่ ................../.................../...............</p>
				<p>บัญชีเงินกู้ที่ .............................................</p>
			</div>
		</div>
	';

	$html .= '
		<div style="position: absolute; left:-25px; top: -10px;">
			 <div style=" width:247px;  height:140px; border:1px solid; padding:5px; font-size:17px; "> 
				  <div style="text-align:center; text-decoration: underline;">หลักฐานประกอบ</div>
				  <div style="text-decoration: underline;">
					1. สลิปเงินเดือนของผู้กู้รับรองโดยเจ้าหน้าที่การเงิน 
				  </div>
				  <div style="text-indent:5px;text-decoration: underline; margin-top:-5px">
					  ประจําหน่วยงาน(เดือนล่าสุด)
				  </div>
				  <div style="margin-top:-5px;text-decoration: underline;">
					2. สำเนาหน้าสมุดบัญชีเงินฝาก ธนาคารกรุงไทย
				  </div>
				  <div style="text-indent:5px;text-decoration: underline; margin-top:-5px;">
					หรือสำเนาหน้าสมุดบัญชีเงินฝากสหกรณ์
				  </div>
				  <div style="text-indent:5px;text-decoration: underline; margin-top:-5px;">
					(ออมทรัพย์ 00 หรือ ออมทรัพย์ 88)
				  </div>
			 </div>
		</div>
	';



	$html .= '
	<div style="padding-left: 36px;padding-right: 36px;">
	  <div style="margin-top:60px;text-align: center">
		คำขอและหนังสือกู้เงินเพื่อเหตุฉุกเฉินออนไลน์
	  </div>
	  <div style="text-align: right">
		ใบคำขอเลขที่………….……………..
	  </div>
	  <div style="margin-top:18px;text-align: right">
		วันที่………เดือน……………….พ.ศ…..............……..
	  </div>
	  <div style="margin-top:18px">
		เรียน คณะกรรมการดําเนินการสหกรณ์ออมทรัพย์สาธารณสุขเชียงราย จํากัด
	  </div>
	  <div style="margin-top:18px;" class="indent" >
		ข้าพเจ้า.....................................................................................สมาชิกเลขทะเบียนที่................................................
	  </div>
	  <div>
		รับราชการหรือทํางานประจําในตําแหน่ง.......................................................................สังกัด.................................................................
	  </div>
	  <div>
		อำเภอ………………………................................…..……จังหวัด…..…………….....…………………………โทรศัพท์..................................................
	  </div>
	  <div>
		ได้รับเงินเดือน  จำนวน........................................................................บาท  ขอเสนอคําขอกู้เงินเพื่อเหตุฉุกเฉินออนไลน์ดังต่อไปนี้
	  </div>
	  <div class="indent">
		ข้อ 1. ข้าพเจ้าขอกู้เงินของสหกรณ์ ฯ จํานวน................................บาท(...................................................)
	  </div>
	  <div>
		 โดยจะนําไปใช้เพื่อการดัง...........................................................................................................................
	  </div>
	  <div  class="indent">
		ข้อ 2. ถ้าข้าพเจ้าได้รับเงินกู้   ข้าพเจ้าขอส่งเงินกู้คืน   เป็นงวดรายเดือนเท่ากันงวดละ.......................................บาท
	  </div>
	  <div>
		พร้อมด้วยดอกเบี้ยเป็นจำนวน............................งวด   (ตามระเบียบที่สหกรณ์ ฯ  กำหนด)  โดยเริ่มส่งคืนเงินกู้ตั้งแต่
	  </div>
	  <div>
		ภายในสิ้นเดือน .................................เป็นต้นไป
	  </div>
	  <div  class="indent">
		ข้อ  3.  เมื่อข้าพเจ้าได้รับเงินกู้แล้วข้าพเจ้ายอมรับผูกพันตามข้อบังคับของสหกรณ์   ดังนี้
	  </div>
	  <div style="padding-left:132px;">
		3.1. ยินยอมให้ผู้บังคับบัญชา หรือเจ้าหน้าที่ผู้จ่ายเงินได้รายเดือนของข้าพเจ้าที่ได้รับมอบหมาย
	  </div>
	  <div>
		จากสหกรณ์ หักเงินได้รายเดือนของข้าพเจ้า ตามจำนวนงวดชำระหนี้ข้อ 2 เพื่อส่งต่อสหกรณ์ การยินยอมนี้ให้มีผลบังคับ
	  </div>
	  <div>
		ตลอดไป  จนกว่าหนี้เงินกู้นี้จะได้ชำระแก่สหกรณ์จนหมด  ผู้กู้ไม่เปลี่ยนแปลงคำยินยอมนี้ไม่ว่ากรณีใด  ๆ
	  </div>
	  <div style="padding-left:132px;">
  		3.2. ยอมให้ถือว่า ในกรณีใด ๆ  ดังกล่าวในข้อบังคับของสหกรณ์ ให้เงินกู้ที่ขอกู้ไปจากสหกรณ์
	  </div>
	  <div>
		เป็นอันถึงกำหนดส่งคืนโดยสิ้นเชิงพร้อมทั้งดอกเบี้ยในทันที โดยมิพักคำนึงถึงกำหนดเวลาที่ตกลงไว้
	  </div>
	  <div style="padding-left:132px;">
  		3.3. ถ้าประสงค์จะขอลาออกหรือย้ายจากราชการ  หรืองานประจำตามข้อบังคับของสหกรณ์
	  </div>
	  <div>
		จะแจ้งเป็นหนังสือให้สหกรณ์ทราบและจัดการชำระหนี้ซึ่งมีอยู่ต่อสหกรณ์ให้เสร็จสิ้นเสียก่อน ถ้าข้าพเจ้าไม่จัดการชำระหนี้
	  </div>
	  <div>
		ให้เสร็จสิ้นตามที่กล่าวข้างต้น เมื่อข้าพเจ้าได้ลงชื่อรับเงินเดือน ค่าจ้าง เงินสะสม บำเหน็จบำนาญเงินทุนเลี้ยงชีพหรือเงินอื่นใด  
	  </div>
	  <div>
		ในหลักฐานที่ทางราชการหรือหน่วยงานเจ้าสังกัดหรือนายจ้าง จะจ่ายเงินให้แก่ข้าพเจ้า  ข้าพเจ้ายินยอมให้เจ้าหน้าที่ผู้จ่ายเงิน
	  </div>
	  <div>
		ดังกล่าว  หักเงินชำระหนี้พร้อมดอกเบี้ยส่งชำระหนี้ต่อสหกรณ์ให้เสร็จสิ้นเสียก่อนได้
	  </div>
	  <div  class="indent" style="margin-top:10px;text-align: right">
		  <div style="text-align: right;">
			<div>
				ลงชื่อ………..........................…………………………….ผู้ขอกู้
			</div>
			<div style="margin-right: 23px">
				(……..........................……………………………..)
			</div>
		  </div>
	  </div>
	 </div>
	';

	$html .= '
		<div style="position:absolute; text-align:center; left:510px; top:3px; width:100px;font-weight:bold; ">
		  
		</div>
		<div style="position:absolute; text-align:center; left:515px; top:30px; width:100px;font-weight:bold; ">
		 '.$arrRequestDate[0].'
		</div>
		<div style="position:absolute; text-align:center; left:575px; top:30px; width:100px;font-weight:bold; ">
		 '.$arrRequestDate[1].'
		</div>
		<div style="position:absolute; text-align:center; left:625px; top:30px; width:100px;font-weight:bold; ">
		 '.$arrRequestDate[2].'
		</div>';
	// ข้อมูล เขียนที่ /วันที่ / เดือน/ พ.ศ.
	/*$html .= '
	  <div style="position:absolute; text-align:center; left:415px; top:136px; width:60px;font-weight:bold; ">
		  '.$arrRequestDate[0].'
	  </div>

	  <div style="position:absolute; text-align:center; left:505px; top:136px; width:115px;font-weight:bold; ">
		 '.$arrRequestDate[1].'
	  </div>

	  <div style="position:absolute; text-align:left; left:650px; top:136px;font-weight:bold; ">
		 '.$arrRequestDate[2].'
	  </div>
	';*/

	// ข้อมูล หน้าแรก/

	//  ชื่อ นามสกุล
	$html .= '
	<div style="position:absolute; text-align:center; left:105px; top:299px; width:315px;font-weight:bold ">
	   '.$data["full_name"].'
	</div>';

	//เลขสมาชิก
	$html .= '
	<div style="position:absolute; text-align:center; left:500px; top:299px; width:206px;font-weight:bold ">
		'.$data["member_no"].'
	</div>';

	// ตำแหน่ง
	$html .= '
	<div style="position:absolute; text-align:center; left:203px; top:327px; width:195px;font-weight:bold">
	   '.($data["position"] ?? "-").'
	</div>
	';

	// สังกัด
	$html .= '
	<div style="position:absolute; text-align:center; left:425px; top:327px; width:230px;font-weight:bold ">
		'.($data["pos_group"] ?? "-").'
	</div>
	';
	// district_desc
	$html .= '
	<div style="position:absolute; text-align:center; left:100px; top:354px; width:100px;font-weight:bold">
		'.$data["district_desc"].'
	</div>
	';
	
	// province_desc
	$html .= '
	<div style="position:absolute; text-align:center; left:320px; top:354px; width:150px;font-weight:bold">
		'.$data["province_desc"].'
	</div>
	';

	// โทรศัพท์
	$html .= '
	<div style="position:absolute; text-align:center; left:520px; top:354px; width:100px;font-weight:bold">
		'.($lib->formatphone($data["tel"],' ') ?? "-").'
	</div>
	';

	// รายเดือน
	$html .= '
	<div style="position:absolute; text-align:right; left:165px; top:383px;font-weight:bold ">
		'.($data["salary_amount"] ?? "-").'
	</div>
	';

	// จำนวนเงินกู้ตัวเลข
	$html .= '
	<div style="position:absolute; text-align:right; left:350px; top:409px;font-weight:bold">
		'.(number_format($data["request_amt"],2) ?? "-").'
	</div>
	';

	// จำนวนเงินกู้ ตัวอักษร
	$html .= '
	<div style="position:absolute; text-align:center; left:410px; top:409px; width:230px;font-weight:bold">
	  '.($lib->baht_text($data["request_amt"]) ?? "-").'
	</div>
	';

	// นำไปใช้เพื่อ
	$html .= '
	<div style="position:absolute; text-align:left; left:175px; top:437px;font-weight:bold">
	  '.($data["objective"] ?? "-").'
	</div>
	';

	// ที่
	$html .= '
	<div style="position:absolute; text-align:center; left:240px; top:473px; width:80px;font-weight:bold ">
	 
	</div>
	';

	// เพื่อ
	$html .= '
	<div style="position:absolute; text-align:center; left:143px; top:501px; width:240px;font-weight:bold ">

	</div>
	';

	// เงินต้นคงเหลือ
	$html .= '
	<div style="position:absolute; text-align:center; left:455px; top:501px; width:110px;font-weight:bold">

	</div>
	';

	// ดอกเบี้ย
	$html .= '
	<div style="position:absolute; text-align:center; left:605px; top:501px; width:80px;font-weight:bold">

	</div>
	';

	// งวด
	$html .= '
	<div style="position:absolute; text-align:center; left:185px; top:493px; width:70px;font-weight:bold">
	  '.($data["period"] ?? "-").'
	</div>
	';

	// วันที่
	$html .= '
	<div style="position:absolute; text-align:left; left:135px; top:520px;font-weight:bold">
		
	</div>
	';
	$periodPay = $data["request_amt"] / $data["period"];
	$periodPay = (int)$periodPay - ($periodPay % 100) + 100;
	// ชำระต่องวด
	$html .= '
	<div style="position:absolute; text-align:center; left:510px; top:467px; width:115px;font-weight:bold;">
	  '.(number_format($periodPay,2) ?? "-").'
	</div>
	';

	// (ชื่อ นามสกุล) ผู้กู้
	$html .= '
	<div style="position:absolute; text-align:center; left:420px; top:892px; width:217px;font-weight:bold;">
	 '.($data["full_name"] ?? "-").'
	</div>
	';

	// (ชื่อ นามสกุล) พยาน
	$html .= '
	<div style="position:absolute; text-align:center; left:260px; top:1018px; width:217px;font-weight:bold;">

	</div>
	';

	$html .= '</div>';

	//----------------------------------page 2 ---------------------------------------


	//head page 2
	$html .= '
	<div style=" height:auto; margin-left:0cm; margin-top:0px; margin-right:0cm;">
		<div style="text-align:center">-2-</div>
	';
	/*
	<div style="text-align:center; font-size:20pt;  font-weight:bold">
		 (สำหรับเจ้าหน้าที่ของสหกณ์)
		</div>
		<div class="text-center" style="margin-top:20px">
		  จำนวนเงินกู้.......................................................................บาท
		</div>
		*/
	
	$html .= '
	<div style="padding-left: 36px;padding-right: 36px;">
		  <div  class="indent" style="margin-top: 20px">
			ข้าพเจ้าผู้กู้ได้อ่านและรับทราบข้อกำหนดและเงื่อนไขในการใช้บริการขอกู้ฉุกเฉินออนไลน์ของทางสหกรณ์
		  </div>
		  <div>
			ตาม "ข้อกำหนดและเงื่อนไขการใช้บริการขอกู้ฉุกเฉินออนไลน์ต่อท้ายสัญญา" ในวันที่ทำคำขอนี้แล้วและตกลงยินยอมผูกพัน
		  </div>
		  <div>
			และรับปฏิบัติตามข้อกำหนดและเงื่อนไขดังกล่าว หากข้าพเจ้าไม่ปฏิบัติตามข้อกำหนดและเงื่อนไขดังกล่าวจนเป็นเหตุให้เกิด
		  </div>
		  <div>
			ความเสียหายใดๆ ข้าพเจ้ายินยอมรับผิดชอบทั้งสิ้น
		  </div>
		  <table class="none-border-table" cellspacing="0" cellpadding="0" style="margin-top: 20px">
			  <tr>
				<td style="vertical-align: top; padding-right: 30px;width: 50%;">
					<div style="border: 1px solid;padding: 10px">
						<div>
							รหัสผู้ใช้...............................…………........……………...
						</div>
						<div>
							เลขที่รายการ…………………......................………………
						</div>
						<div>
							วันที่ทำรายการ…..........…../………….......…../….........
						</div>
						<div>
							เวลาทำรายการ…………..…….............…..……..........น.
						</div>
					</div>
				</td>
				<td style="border: 1px solid;padding: 10px;width: 50%;">
					<div>
						ชื่อ – สกุล ผู้กู้..…………….…….................………..............
					</div>
					<div>
						ทะเบียนสมาชิก..............................................................
					</div>
					<div>
						เบอร์โทร.........................................................................
					</div>
					<div>
						เลขบัญชีเงินฝากสหกรณ์…...............…..............…….…....
					</div>
					<div>
						เลขบัญชีเงินฝากธนาคารกรุงไทย…................……….…....
					</div>
				</td>
			</tr>
		</table>
		<div style="text-align: center;font-size: 20px;margin-top: 20px;">
			สำหรับเจ้าหน้าที่
		</div>
		<div style="padding-left: 20px;padding-right: 20px;">
		  <table class="none-border-table" cellspacing="0" cellpadding="0" style="margin-top: 20px;border: 1px solid">
			  <tr>
			<td style="width: 50%;padding: 10px;float: left;">
				<div>
					ยอดอนุมัติ..................................................บาท
				</div>
				<div>
					คงเหลือจ่ายจริง..........................................บาท
				</div>
				<div>';
				if(isset($data["deptaccount_no_coop"]) && $data["deptaccount_no_coop"] != ""){
					$html .= '<input type="checkbox" checked style="transform: translateY(5);">โอนเข้าบัญชีเงินฝากสหกรณ์';
				}else{
					$html .= '<input type="checkbox" style="transform: translateY(5);">โอนเข้าบัญชีเงินฝากสหกรณ์';
				}
					
				$html .= '	
				</div>
				<div>';
					if(isset($data["deptaccount_no_bank"]) && $data["deptaccount_no_bank"] != ""){
						$html .= '<input type="checkbox" checked style="transform: translateY(5);">โอนเข้าบัญชีเงินฝากธนาคารกรุงไทย';
					}else{
						$html .= '<input type="checkbox" style="transform: translateY(5);">โอนเข้าบัญชีเงินฝากธนาคารกรุงไทย';
					}
				$html .= '
				</div>
			</td>
			<td style="width: 50%;padding: 10px;float: left;">
				<div>
					หักหนี้เดิมชำระ............................................บาท
				</div>
				<div>
					ทำรายการเมื่อวันที่.......…../………...…../…....……
				</div>
				<div>
					เลขที่.................................................
				</div>
				<div>
					เลขที่.................................................
				</div>
			</td>
			</tr>
		</table>
		</div>
		 <table class="none-border-table" cellspacing="0" cellpadding="0" style="margin-top: 20px;">
			<tr>
				<td style="width: 40%;padding: 10px;">
					<div style="font-weight: bold;">
						ตรวจสอบสิทธิ์การให้เงินกู้เรียบร้อยแล้ว
					</div>
					<div>
						……………...................……………………….เจ้าหน้าที่
					</div>
					<div>
						………………………………..................…….
					</div>
				</td>
				<td style="width: 40%;padding: 10px;">
					<div style="padding-left: 20px">
						<div style="font-weight: bold;padding-left: 40px;">
							เจ้าหน้าที่การเงิน
						</div>
						<div>
							……………...................……………………….เจ้าหน้าที่
						</div>
						<div>
							………………………………..................…….
						</div>
					</div>
				</td>
			</tr>
		</table>
		 <table class="none-border-table" cellspacing="0" cellpadding="0" style="margin-top: 20px;">
			<tr>
				<td style="width: 50%;padding: 10px;">
					<div style="font-weight: bold;padding-left: 60px;">
						เห็นควรอนุมัติ
					</div>
					<div>
						…..……………………………..........................…หัวหน้าสินเชื่อ/รองผู้จัดการ
					</div>
					<div>
						………………......................……………………..
					</div>
					<div>
						……..............……/…………….../…….........…
					</div>
				</td>
				<td style="width: 50%;padding: 10px;">
					<div style="font-weight: bold;padding-left: 60px;">
						อนุมัติ
					</div>
					<div>
						……………...................……………………….ผู้จัดการ
					</div>
					<div>
						………………………………..................…….
					</div>
					<div>
						………………………………..................…….
					</div>
				</td>
			</tr>
		</table>
	</div>
	';

	//ข้อมูลหน้า 2 
	//รหัสผู้ใช้
	$html .= '
	  <div style="position:absolute; text-align:left; left:120px; top:187px; width:287px;font-weight:bold;">
	  
	  </div>';
	//doc_no
	$html .= '
	  <div style="position:absolute; text-align:left; left:160px; top:216px; width:287px;font-weight:bold;">
	  '.$data["requestdoc_no"].'
	  </div>';
	  
	//date
	$html .= '
	  <div style="position:absolute; text-align:left; left:150px; top:244px; width:257px;font-weight:bold;">
		'.$arrRequestDate[0].'
	  </div>
	  <div style="position:absolute; text-align:left; left:196px; top:244px; width:257px;font-weight:bold;">
		'.$arrRequestDate[1].'
	  </div>
	  <div style="position:absolute; text-align:left; left:265px; top:244px; width:257px;font-weight:bold;">
		'.$arrRequestDate[2].'
	  </div>';

	//gวลา
	$html .= '
	  <div style="position:absolute; text-align:left; left:160px; top:271px; width:287px;font-weight:bold;">
		'.date("H:i").'
	  </div>';
	  
	  
	  
	//ชื่อผู้กู้
	$html .= '
	  <div style="position:absolute; text-align:center; left:380px; top:187px; width:287px;font-weight:bold;">
	  '.$data["full_name"].'
	  </div>';
	//member_no
	$html .= '
	  <div style="position:absolute; text-align:center; left:385px; top:216px; width:287px;font-weight:bold;">
	  '.$data["member_no"].'
	  </div>';
	  
	//tel
	$html .= '
	  <div style="position:absolute; text-align:center; left:380px; top:244px; width:257px;font-weight:bold;">
		'.($lib->formatphone($data["tel"],' ') ?? "-").'
	  </div>';

	//เลข บช สหกรณ์ 
	$html .= '
	  <div style="position:absolute; text-align:center; left:390px; top:271px; width:287px;font-weight:bold;">
		'.($data["deptaccount_no_coop"]).'
	  </div>';
	  
	//เลข บช ธนาคาร 
	$html .= '
	  <div style="position:absolute; text-align:center; left:410px; top:300px; width:287px;font-weight:bold;">
		  '.($data["deptaccount_no_bank"]).'
	  </div>';

	  
	//apv amt
	$html .= '
	  <div style="position:absolute; text-align:left; left:155px; top:445px; width:257px;font-weight:bold;">
	  
	  </div>';
	  
	//เลข บช สหกรณ์ 
	$html .= '
	  <div style="position:absolute; text-align:center; left:290px; top:473px; width:287px;font-weight:bold;">
		'.($data["deptaccount_no_coop"]).'
	  </div>';
	  
	//เลข บช ธนาคาร 
	$html .= '
	  <div style="position:absolute; text-align:center; left:290px; top:500px; width:287px;font-weight:bold;">
		  '.($data["deptaccount_no_bank"]).'
	  </div>';

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