<?php
use Dompdf\Dompdf;

$dompdf = new DOMPDF();
function GeneratePDFContract($data,$lib) {
//demo Data
$data["หนังสือกู้ที่"] = $data["requestdoc_no"];
$data["ทับ"] = '';

$data["เขียนที่"] = 'Nan Teacher Saving';
$data["วันที่"] = date('d');
$data["เดือน"] = (explode(' ',$lib->convertdate(date("Y-m-d"),"d M Y")))[1];
$data["ปี"] = intval(date("Y")+543);
$data["ชื่อ"] = $data["full_name"];
$data["สมาชิกเลขทะเบียนที่"] = $data["member_no"];
$data["บัตรประจำตัวประชาชนเลขที่"] = $data["card_person"];
$data["ตำแหน่ง"] = $data["position"]; 
$data["สังกัด"] = $data["pos_group"];  
$data["อำเภอ"] = $data["district_desc"];
$data["รายเดือน"] = $data["salary_amount"];
$data["กู้เป็นวงเงิน"] = number_format($data["request_amt"],2);
$data["กู้เป็นวงเงินคำอ่าน"] = $lib->baht_text($data["request_amt"]);
$data["มีสิทธิ์กู้ได้"] = '';
$data["หนี้คงเหลือ"] = '';

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
				margin-left:50px;
			}
			.sub-list{
				text-indent:90px;
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
			.detail{
				font-size:11pt;
				line-height:17px;
			}
			.desc{
				line-height:25px;
				font-size:15pt;
			}
			</style>';
	//ระยะขอบกระดาษ
	$html .= '<div style="margin:-10px 10px;">';

	//ส่วนหัว


	//เริ่มหน้าที่ 1  
	$html .= '<div class="wrapper-page">';
	$html .= '
		<div>
			<div class="absolute" style="margin-left:-20px;">
				<div style="border:1px solid green; width:175px; padding:5px; ">
					<div class="center bold" style="font-size:13pt;">ความเห็นของผู้บังคับบัญชา</div>
					<div style="height:30px;">
						<div class="absolute" style="font-size:13pt;">
							<input class="checkbox" type="checkbox" style="margin-left:5px;">
							<div class="absolute" style="margin-left:2px;">ควรอนุมัติ</div>
						</div>
						<div class="absolute" style="margin-left:85px; font-size:13pt" >
							<input class="checkbox" type="checkbox" style="margin-left:3px;">
							<div class="absolute" style="margin-left:2px;">ไม่ควรอนุมัติ</div>
						</div>
					</div>
					<div class="center" style="font-size:13pt;">ลงชื่อ.......................................</div>
					<div style="margin-left:35px;">(.......................................)</div>
					<div class="center" style="font-size:13pt;">ตำแหน่ง..................................</div>
				</div>
			</div>
			<div class="absolute" style="margin-top:149px; margin-left:-20px; ">
				<div style="border:1px solid green; width:175px; padding:5px; font-size:13pt;">
					<div class="center bold" style="font-size:13pt;">ความเห็นของหัวหน้าหน่วย</div>
					<div style="height:30px;">
						<div class="absolute" style="font-size:13pt;">
							<input class="checkbox" type="checkbox" style="margin-left:5px;">
							<div class="absolute" style="margin-left:2px;">ควรอนุมัติ</div>
						</div>
						<div class="absolute" style="margin-left:85px;" >
							<input class="checkbox" type="checkbox" style="margin-left:3px;">
							<div class="absolute" style="margin-left:2px; font-size:13pt">ไม่ควรอนุมัติ</div>
						</div>
					</div>
					<div class="center">ลงชื่อ.......................................</div>
					<div style="margin-left:35px;">(.......................................)</div>
					<div class="center">ตำแหน่ง..................................</div>
				</div>
			</div>
			<div class="absolute" style="right:10px;">
				<div style="border:1px solid; width:255px; padding:3px 10px; ">
					<div class="nowrap" style="letter-spacing:0.45px; font-size:13pt;">
						<span class="bold" style="margin-right:10px;"><u>คำเตือน</u></span &nbsp; &nbsp;ผู้ขอกู้ต้องกรอกข้อความตามรายการ 
					</div>
					<div style="letter-spacing:0.95px; font-size:13pt;">
						ที่กำหนดไว้ในแบบคำขอกู้นี้โดยถูกต้อง และ 
					</div>
					<div style="font-size:13pt;">
						ครบถ้วน มิฉะนั้นสหกรณ์จะไม่รับพิจารณา 
					</div>
				</div>
			</div>
			<div class="absolute" style="right:10px; top:70px;">
				<div style="border-left:1px solid; border-right:1px solid; border-bottom:1px solid; width:255px; padding:3px 10px; ">
					<div class="absolute" style="margin-left:85px; margin-top:10px; "><div style="margin-top:-3px; width:100px;"  class="data nowrap center">'.($data["หนังสือกู้ที่"]??null).'</div></div>
					<div class="absolute" style="margin-left:145px; margin-top:10px; "><div style="margin-top:-3px; width:110px;"  class="data nowrap center">'.($data["ทับ"]??null).'</div></div>
					<div class="nowrap bold" style="letter-spacing:0.45px; font-size:13pt; margin-top:10px;">
							หนังสือกู้ที่...............................................
					</div>
			
				</div>
			</div>
			<div>
				<div style=" text-align: center; margin-top:30px;"><img src="../../resource/logo/logo.jpg" alt="" width="90" height="0"></div>
				<div class="bold center" style="font-size:19pt; margin-top:30px;">คำขอและหนังสือกู้เงินเพื่อเหตุฉุกเฉิน</div>
			</div>
			<div style="margin-top:30px;">
				<div class="absolute" style="margin-left:520px; "><div style="margin-top:-3px;"  class="data nowrap ">'.($data["เขียนที่"]??null).'</div></div>
				<div class="right">เขียนที่.........................................................</div>
			</div>
			<div  style="margin-top:10px;">
				<div class="absolute" style="margin-left:450px; "><div style="margin-top:-3px;"  class="data nowrap ">'.($data["วันที่"]??null).'</div></div>
				<div class="absolute" style="margin-left:530px; "><div style="margin-top:-3px;"  class="data nowrap ">'.($data["เดือน"]??null).'</div></div>
				<div class="absolute" style="margin-left:630px; "><div style="margin-top:-3px;"  class="data nowrap ">'.($data["ปี"]??null).'</div></div>

				<div class="right">วันที่..................เดือน.........................พ.ศ..................</div>
			</div>
		</div>
		<div  style="margin-top:35px;" >
			<div>
				<div class="absolute" style="margin-left:90px; "><div style="margin-top:-3px; width:310px;"  class="data nowrap center">'.($data["ชื่อ"]??null).'</div></div>
				<div class="absolute" style="margin-left:530px; "><div style="margin-top:-3px;"  class="data nowrap">'.($data["สมาชิกเลขทะเบียนที่"]??null).'</div></div>
				<div class="list nowrap">ข้าพเจ้า.............................................................................................สมาชิกเลขทะเบียนที่.................................................</div>
			</div>	
			<div class="nowrap">
				<div class="absolute" style="margin-left:230px; "><div style="margin-top:-3px;"  class="data nowrap">'.($data["บัตรประจำตัวประชาชนเลขที่"]??null).'</div></div>
				<div class="absolute" style="margin-left:530px; "><div style="margin-top:-3px;"  class="data nowrap">'.($data["ตำแหน่ง"]??null).'</div></div>
				บัตรประจำตัวประชาชนเลขที่...............................................................................................ตำแหน่ง.................................................
			</div>
			<div class="nowrap">
				<div class="absolute" style="margin-left:75px; "><div style="margin-top:-3px; width:255px; "  class="data nowrap center ">'.($data["สังกัด"]??null).'</div></div>
				<div class="absolute" style="margin-left:370px; "><div style="margin-top:-3px; width:175px; "  class="data nowrap center">'.($data["อำเภอ"]??null).'</div></div>

				สังกัดโรงเรียน..............................................................................อำเภอ......................................................จังหวัดน่าน ได้รับเงินได้</div>
			<div >
				<div class="absolute" style="margin-left:50px; "><div style="margin-top:-3px; width:165px; "  class="data nowrap center">'.($data["รายเดือน"]??null).'</div></div>
				<div class="nowrap" style="letter-spacing:0.25px;">รายเดือน..............................................บาท ซึ่งต่อไปนี้เรียกว่า "ผู้กู้" ได้ทำหนังสือสัญญากู้เงินฉุกเฉินไว้ต่อสหกรณ์ออมทรัพย์</div>
			</div>
			<div class="nowrap">ครูน่าน จำกัด ซึ่งต่อไปเรียกว่า "ผู้ให้กู้" เป็นหลักฐานดังต่อไปนี้</div>
			<div>
				<div class="absolute" style="margin-left:280px; "><div style="margin-top:-3px; width:120px; "  class="data nowrap center">'.($data["กู้เป็นวงเงิน"]??null).'</div></div>
				<div class="absolute" style="margin-left:425px; "><div style="margin-top:-3px; width:250px; "  class="data nowrap center">'.($data["กู้เป็นวงเงินคำอ่าน"]??null).'</div></div>
				<div class="list nowrap">
					ข้อ 1. ผู้กู้ขอกู้เงินจากผู้ให้กู้เป็นวงเงินจำนวน....................................บาท(..........................................................................)
				</div>
			</div>
			<div class="list">
				<div style="letter-spacing:0.25px;">ข้อ 2. ผู้ขอกู้ยินยอมให้ผู้ให้กู้ หักเงินกู้เพื่อเหตุฉุกเฉินที่ผู้กู้ค้างชำระ จากเงินกู้ตามข้อ 1. ก่อน และผู้ให้กู้ได้ส่งมอบเงิน</div>
			</div>
			<div>ส่วนที่เหลือให้กับผู้กู้</div>
			<div class="list">
				ข้อ 3. ผู้ขอกู้ได้รับเงินสดจำนวนทีเหลือตามข้อ 2. ไปโดยถูกต้องครบถ้วนในวันทำสัญญาฉบับนี้แล้ว
			</div>
			<div class="list nowrap" style="letter-spacing:0.62px;">
				ข้อ 4. ผู้กู้ตกลงว่าจะชำระหนี้เป็นงวดรายเดือน ภายในวันทำการทุกสิ้นเดือน งวดการชำระหนี้ ผู้กู้ต้องชำระหนี้
			</div>
			<div>
				ให้เสร็จสิ้นภายใน 12 งวดเดือน นับจากวันที่ผู้กู้ได้รับเงินเป็นที่เรียบร้อยแล้ว
			</div>
			<div class="list nowrap " style="letter-spacing:0.55px">ข้อ 5. ผู้กู้ยินยอมให้ผู้บังคับบัญชาหรือเจ้าที่ผู้มีหน้าที่จ่ายเงินได้รายเดือนของผู้กู้ หักเงินงวดชำระหนี้ตามข้อ 4. </div>
			<div>เพื่อส่งให้กับผู้ให้กู้</div>
			<div class="list nowrap" style="letter-spacing:0.17px">ข้อ 6. หากผู้กู้ไม่อาจชำระเงินงวดตามที่กำหนดไว้ จะต้องติดต่อกับผู้ให้กู้โดยเร็ว หรือภายในระยะเวลาที่ผู้กู้แจ้งให้ทราบ</div>
			<div class="list nowrap" style="letter-spacing:0.39px;">ข้อ 7. ผู้กู้ทราบ และเข้าใจดี ขอยอมรับผูกพันตามข้อบังคับ และระเบียบว่าด้วยการให้เงินกู้แก่สมาชิกฯ ของผู้กู้ที่ได้</div>
			<div class="nowrap">กำหนดขึ้นถือปฏิบัติทุกประการ รวมทั้งหากมีการแก้ไขเพิ่มเติมในภายหน้าด้วย ซึ่งผู้ให้กู้ไม่จำเป็นต้องแจ้งให้ผู้กู้ทราบล่วงหน้า</div>
			<div class="list nowrap" style="letter-spacing:0.28px;">ข้อ 8. หากผู้กู้ผิดนัดชำระหนี้ งวดหนึ่งงวดใด ให้ถือว่าผิดนัดชำระหนี้ทั้งหมด สัญญากู้เป็นอันถึงกำหนดชำระโดยพลัน</div>
			<div class="nowrap" style="letter-spacing:0.37px;">ผู้กู้ยินยอมให้ผู้ให้กู้นำเงินทุนเรือนหุ้น เงินปันผล/เฉลี่ยคืนมาชำระหนี้ได้ก่อน และยินยอมให้ฟ้องร้องบังคับคดีได้ทันที และผู้กู้</div>
			<div>ยอมชดใช้ค่าเสียหายที่ผู้ให้กู้ต้องใช้จ่ายไปในการดำเนินการคดีแก่ผู้ให้ผู้โดยครบถ้วน</div>
			<div class="list">ผู้กู้ได้อ่านข้อความในคำขอกู้และหนังสือกู้เงินแล้ว จึงได้ลงลายมือชื่อไว้เป็นสำคัญต่อหน้าพยาน ณ วันที่ทำหนังสือ</div>
		</div>
		<div class="flex">
			<div>
				<div class="absolute" style="border:1px solid; width:300px; margin-left:20px; margin-top:45px; height:140px; padding:5px 10px 10px; 10px; ">
					<div class="center bold desc"><u>คำชีแจง</u></div>
					<div class="bold desc">ผู้กู้จะต้องมาลงลายมือชื่อต่อหน้าเจ้าหน้าที่</div>
					<div class="bold desc">ณ สหกรณ์ออมทรัพย์ครูน่าน จำกัด เท่านั้น</div>
					<div class="bold desc">และเจ้าหน้าที่สหกรณ์ออมทรัพย์ครูน่าน จำกัด</div>
					<div class="bold desc">จะลงลายมือชื่อเป็นพยานในหนังสือกู้เงิน</div>
				</div>
			</div>
			<div style="margin-left:54%;">
				<div class="left" style="margin-right:20px; margin-top:30px;">...........................................................&nbsp;&nbsp;&nbsp;ผู้กู้</div>
				<div class="left" style="margin-right:40px;">
					<div class="absolute" style="margin-left:5px; "><div style="margin-top:-3px; width:200px;"  class="data nowrap center">'.($data["ชื่อ"]??null).'</div></div>
					(.............................................................)
				</div>
				<div class="left">...........................................................&nbsp;&nbsp;&nbsp;คู่สมรส</div>
				<div class="left" style="margin-right:40px;">(.............................................................)</div>
				<div class="left">...........................................................&nbsp;&nbsp;&nbsp;พยาน</div>
				<div class="left" style="margin-right:40px;">(.............................................................)</div>
				<div class="left">...........................................................&nbsp;&nbsp;&nbsp;พยาน</div>
				<div class="left" style="margin-right:40px;">(.............................................................)</div>
			</div>
		</div>';
	$html .= '</div>';
	//สิ้นสุดหน้าที่1
	//หน้าที่ 2  
	$html .= '<div class="wrapper-page">
			<div class="bold center" style="border:1px solid; padding:5px 10px 10px 10px; width:210px; margin-left:230px; ">สำหรับเจ้าหน้าที่สหกรณ์ออมทรัพย์</div>		
			<div  style="margin-top:40px;">
				<div class="absolute" style="margin-left:230px; "><div style="margin-top:-3px; width:165px;"  class="data nowrap center">'.($data["มีสิทธิ์กู้ได้"]??null).'</div></div>
				<div class="absolute" style="margin-left:490px; "><div style="margin-top:-3px; width:165px;"  class="data nowrap center">'.($data["หนี้คงเหลือ"]??null).'</div></div>
				<div class="list nowrap">ได้ตรวจสอบแล้วสมาชิกมีสิทธิ์กู้ได้..................................................บาท มีหนี้คงเหลือ.................................................บาท</div>
			</div>
			<div style="margin-left:47%;">
				<div class="left" style="margin-right:20px; margin-top:30px;">............................................................เจ้าหน้าที่ฝ่ายสินเชื่อ</div>
				<div>................../...................../...................</div>
			</div>
			<div style="height:30px; margin-left:50px; margin-top:30px;">
				<div class="bold" style="height:30px;">
					เห็นควร
					<div class="absolute" style="font-size:13pt;">
						<input class="checkbox" type="checkbox" style="margin-left:5px;">
						<div class="absolute " style="margin-left:2px;">อนุมัติ</div>
					</div>
					<div class="absolute" style="margin-left:85px; font-size:13pt" >
						<input class="checkbox" type="checkbox" style="margin-left:3px;">
						<div class="absolute " style="margin-left:2px;">ไม่อนุมัติ เนื่องจาก................................................................................</div>
					</div>
				</div>
			</div>
			<div style="margin-left:47%;">
				<div class="left" style="margin-right:20px; margin-top:30px;">.............................................................หัวหน้าฝ่าย/ผู้ช่วยหัวหน้าฝ่าย</div>
				<div>................../...................../...................</div>
			</div>
			<div style="height:30px; margin-left:50px; margin-top:30px;">
				<div class="bold" style="height:30px;">
					เห็นควร
					<div class="absolute" style="font-size:13pt;">
						<input class="checkbox" type="checkbox" style="margin-left:5px;">
						<div class="absolute " style="margin-left:2px;">อนุมัติ</div>
					</div>
					<div class="absolute" style="margin-left:85px; font-size:13pt" >
						<input class="checkbox" type="checkbox" style="margin-left:3px;">
						<div class="absolute " style="margin-left:2px;">ไม่อนุมัติ เนื่องจาก................................................................................</div>
					</div>
				</div>
			</div>
			<div style="margin-left:47%;">
				<div class="left" style="margin-right:20px; margin-top:30px;">.............................................................ผู้จัดการ/รองผู้จัดการ</div>
				<div>................../...................../...................</div>

				<div class="left" style="margin-right:20px; margin-top:30px;">............................................................ประธานกรรมการหรือ</div>
				<div>................../...................../...................รองประธานกรรมการ หรือ</div>
				<div style="margin-left:205px;"> เลขานุการ หรือเหรัญญิก</div>
			</div>
			<div  style="margin-left:47%; border:2px solid; padding:10px;  margin-top:60px; line-height: 25px; ">
				<div class="center bold desc"><u>เอกสารประกอบคำขอกู้เงิน</u></div>
				<div class="desc">1. สำเนาบัตรประจำตัวประชาชนของผู้กู้1 ฉบับ</div>
				<div class="desc">2. สำเนาทะเบียนบ้านผู้กู้ 1 ฉบับ</div>
				<div class="desc">3. สำเนาบัตรประจำตัวคู่สมรสผู้กู้ 1 ฉบับ</div>
				<div class="desc">4. สำเนาทะเบียนสมรส 1 ฉบับ</div>
				<div class="desc">5. สำเนาทะเบียนหย่า 1 ฉบับ (กรณีหย่า)</div>
				<div class="desc">6. สำเนาใบมรณบัตร 1 ฉบับ (กรณีคู่สมรสเสียชีวิต)</div>
				<div class="desc">7. สลิปเงินเดือน ย้อนหลัง 3 เดือน</div>
				<div class="desc">8. สำเนาสมุดบัญชีเงินฝากของสหกรณ์ 1 ฉบับ</div>
				<div class="desc">9. ผู้กู้ที่มีอายุ 55 ปีขึ้นไป ต้องมีหนังสือรับรองเงินเดือน</div>
				<div class="desc" style="margin-left:15px;">หลังเกษียณอายุราชการ</div>

			</div>
			
	';

	$html .= '</div>';
	//สิ้นสุดหน้า2
	$html .= '</div>';


	$html .= '</div>';

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