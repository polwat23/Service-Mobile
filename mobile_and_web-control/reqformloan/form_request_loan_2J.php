<?php
use Dompdf\Dompdf;

$dompdf = new DOMPDF();
function GeneratePDFContract($data,$lib) {
	$arrRequestDate = explode(' ',$lib->convertdate(date('Y-m-d'),'d M Y'));
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
		  padding: 0;
		  font-size:16pt
		 
		}
		p{
			margin:0
		}
		.text-center{
		  text-align:center;
		}
		.text-right{
		  text-align:right;
		}
		.text-bold{
		  font-weight:bold;
		}
		.text-justify{
		   text-align:left;
		   word-wrap: break-word;
		}
		li{
		  line-height:29px;
		}
		.text-indent{
		  text-indent:47px;
		}
		.indent{
		 padding-left:47px;
		}
		.text-color-red{
		  color:red;
		}
		.text-color-blue{
		  color:#338dcd;
		}
		.nowrap{
		  white-space: nowrap;
		}
		.checkbox{
		  margin-top:7px;
		}
		.line-height{
		  line-height:24px;
		}
	</style>
	';
	
	$html .= '<div style=" height:auto; padding-left:0cm; margin-top:-15px; padding-right:0cm;padding-bottom: 60px;">';


	//ข้อมูล->  สัญญาเงินกู้เลขที่ | วันที่
	$html .= '
	  <div class="text-center"  style="position:absolute; top :91px; right:20px;  width:130px ">
		  
	  </div>
	  <div class="text-center"  style="position:absolute; top :122px; right:40px;  width:198px; ">
		
	</div>
	';


	// ข้อมูล-> เพศ | ชื่อ-สกุล | 
	$html .= '
	<div class="text-center"  style="position:absolute; top :163px; left:105px;">
		<input type="checkbox" '.($data["prename"] == 'นาย' ? 'checked' : '').' style="margin-top:7px; " > นาย
	</div>

	<div class="text-center"  style="position:absolute; top :163px; left:155px;">
		<input type="checkbox" '.($data["prename"] == 'นาง' ? 'checked' : '').' style="margin-top:7px; "  > นาง
	</div>

	<div class="text-center"  style="position:absolute; top :163px; left:200px;">
		<input type="checkbox" '.($data["prename"] == 'นางสาว' ? 'checked' : '').' style="margin-top:7px; " > นางสาว
	</div>

	<div class="text-center"  style="position:absolute; top :166px; left:284px;  width:269px;">
		'.$data["name"].'
	</div>

	<div class="text-center"  style="position:absolute; top :166px; right:50px;  width:50px;">
		'.$data["age"].'
	</div>
	';

	//ข้อมูล-> เลขประจำตัว | พนักงาน
	$html .= '
	  <div class="text-center"  style="position:absolute; top :192px; left:100px; width:70px;">
		'.$data["member_no"].'
	  </div>
	  <div  style="position:absolute; top :192px; left:220px; ">
		  <input type="checkbox" '.($data["emp_type"] == '01' ? 'checked' : '').' style="margin-top:7px; "  > พนักงาน กฟผ.
	  </div>
	  <div  style="position:absolute; top :192px; left:340px; ">
		  <input type="checkbox" '.($data["emp_type"] == '10' ? 'checked' : '').' style="margin-top:7px; "  > พนักงาน กฟผ.สัญญาจ้าง
	  </div>
	  <div  style="position:absolute; top :192px; right:29px; ">
		  <input type="checkbox" style="margin-top:7px; "  >  ลูกจ้างการฌาปนกิจ
	  </div>



	  <div  style="position:absolute; top :224px; left:0px; ">
		  <input type="checkbox" style="margin-top:7px; "  > สงเคราะห์ผู้ปฏิบัติงาน กฟผ.
	  </div>
	  <div  style="position:absolute; top :224px; left:198px; ">
		  <input type="checkbox" '.($data["emp_type"] == '05' ? 'checked' : '').' style="margin-top:7px; "  > พนักงานบริษัทในเครือ
	  </div>
	  <div  style="position:absolute; top :224px; left:362px; ">
		  <input type="checkbox" '.($data["emp_type"] == '02' ? 'checked' : '').' style="margin-top:7px; "  > เจ้าหน้าที่ สอ.กฟผ.
	  </div>
	  <div  style="position:absolute; top :224px; right:29px; ">
		  <input type="checkbox" '.($data["emp_type"] == '09' ? 'checked' : '').' style="margin-top:7px; "  >   สมาชิกออกจากงานแล้ว
	  </div>
	';

	// ข้อมูล-> ตำแหน่ง | แผนก | กอง | ฝ่าย

	$html .= '
	<div class="text-center"  style="position:absolute; top :252px; left:85px; width:120px; ">
		'.$data["position"].'
	</div>
	<div class="text-center"  style="position:absolute; top :252px; left:250px;  width:120px; ">
		'.$data["department_desc"].'
	</div>
	<div class="text-center"  style="position:absolute; top :252px; left:399px;  width:120px; ">
		'.$data["department_group"].'
	</div>
	<div class="text-center"  style="position:absolute; top :252px; right:39px;  width:115px; ">
		'.$data["department_position"].'
	</div>
	';

	// ข้อมูล-> เลขที่บัตรประชาชน | ออกวันที่  |หมดวันที่
	$html .= '
	  <div class="text-center"  style="position:absolute; top :282px; left:133px; width:128px; ">
		  '.$data["card_person"].'
	  </div>
	  <div class="text-center"  style="position:absolute; top :282px; left:340px; width:135px; ">
		  วันที่ออก
	  </div>
	  <div class="text-center"  style="position:absolute; top :282px; right:29px; width:135px">
		หมดวันที่
	  </div>
	';

	//  ข้อมูล-> อยู่บ้านเลขที่ | หมู่ที่ |ตำบล/แขวง | 
	$html .= '
	  <div class="text-center"  style="position:absolute; top :311px; left:100px; width:110px;">
		  '.$data["addr_no"].'
	  </div>
	  <div class="text-center"  style="position:absolute; top :311px; left:250px; width:80px;">
		  '.$data["addr_moo"].'
	  </div>
	  <div class="text-center"  style="position:absolute; top :311px; right:30px; width:250px;">
		'.$data["tambol_desc"].'
	  </div>
	';

	//  ข้อมูล-> อำเภอ/เขต | จังหวัด |รหัสไปรษณีย์  
	$html .= '
	  <div class="text-center"  style="position:absolute; top :340px; left:115px; width:134px; ">
		  '.$data["district_desc"].'
	  </div>
	  <div class="text-center"  style="position:absolute; top :340px; left:300px; width:175px; ">
		  '.$data["province_desc"].'
	  </div>
	  <div class="text-center"  style="position:absolute; top :340px; right:39px; width:100px; ">
		  '.$data["addr_postcode"].'
	  </div>
	';

	// ข้อมูล-> โทรศัพท์(มือถือ) |โทรศัพท์ที่ทำงาน | โทรศัพท์บ้าน

	$html .= '
	  <div class="text-center"  style="position:absolute; top :371px; left:0px; ">
		  <input type="checkbox" '.($data["mem_telmobile"] ? 'checked' : '').' style="margin-top:4px;" >  โทรศัพท์(มือถือ) ..........................      
		  <div style="position:absolute; left:120px; top:-1px;  width:100px;">
			'.$data["mem_telmobile"].'
		  </div>
	  </div>
	  <div class="text-center"  style="position:absolute; top :371px; left:231px; ">
		  <input type="checkbox" style="margin-top:4px;">  โทรศัพท์ที่ทำงาน ..........................   
		  <div style="position:absolute; left:122px; top: -1px;  width:100px; ">
			  
		  </div>   
	  </div>
	  <div class="text-center"  style="position:absolute; top :371px; right:29px; ">
		  <input type="checkbox" '.($data["mem_tel"] ? 'checked' : '').'style="margin-top:4px;">  โทรศัพท์บ้าน .......................... 
		  <div style="position:absolute; left:100px; top:-1px;  width:100px; ">
			'.$data["mem_tel"].'
		  </div>     
	  </div>
	  
	  ';

	  // ข้อมูล ข้อ1,ข้อ2 -> เป็นจำนวนเงิน ตัวเลข|เป็นจำนวนเงิน ตุัวอักษร | งวดรายเดือนเท่ากันงวดละ |  ร้อยล่ะต่อปี | รวมงวด
	$html .='
	  <div class="text-right text-color-red"  style="position:absolute; top :483px; left:94px;  width:85px;  ">
		  '.number_format($data["request_amt"],2).'
	  </div>
	  <div class="text-center text-color-red"  style="position:absolute; top :483px; right:41px;  width:399px; ">
		  '.$lib->baht_text($data["request_amt"]).'
	  </div>
	  <div class="text-right text-color-red"  style="position:absolute; top :512px; right:96px;  width:130px; ">
		 '.number_format($data["period_payment"],2).'
	  </div>
	  <div class="text-center text-color-red"  style="position:absolute; top :541px; right:69px;  width:90px; ">
		'.$data["int_rate"].'
	  </div>
	  <div class="text-center text-color-red"  style="position:absolute; top :569px; left:43px;  width:110px; ">
	  	'.$data["period"].'
	</div>
	';



	$html .= '
		<div class="text-center">
			 <img src="../../resource/logo/logo.jpg" style="width:80px;">
		</div>
		<div class="text-center text-bold">
			คำขอกู้และสัญญาเงินกู้สามัญผ่านแอพพลิเคชั่น
		</div>
		<div class="text-right">  
			สัญญาเงินกู้เลขที่ .....................................
		</div>
		<div class="text-right">  
			วันที่ ..........................................................
		</div>
	';


	$html .= '
		<div class=" text-indent nowrap " style="margin-top:15px; ">
			<span style="margin-right:163px;"> ข้าพเจ้า  </span>     ..............................................................................อายุ ............. ปี
		</div>
		<div style="">
			เลขประจำตัว ....................... เป็น  
		</div>
		<div>
			 &nbsp;
		</div>
		<div class="text-justify nowrap">
			ตำแหน่ง ................................... แผนก ................................. กอง .................................. ฝ่าย .................................
		</div>
		<div class="nowrap">
			เลขที่บัตรประชาชน ..................................... ออกวันที่ ...................................... หมดวันที่ ......................................
		</div>
		<div class="nowrap">
			อยู่บ้านเลขที่ ............................. หมู่ที่ ....................... ตำบล / แขวง ........................................................................
		</div>
		<div class="nowrap">
			อำเภอ / เขต ........................................ จังหวัด .................................................. รหัสไปรษณีย์ ...............................
		</div>
		<div class="nowrap">
		   &nbsp;
		</div>
		<div class="indent nowrap"style="letter-spacing: 0.27px;" >
		  ข้าพเจ้าขอทำคำขอกู้และสัญญาเงินกู้สามัญผ่านแอพพลิเคชั่นให้ไว้แก่สหกรณ์ออมทรัพย์การไฟฟ้าฝ่าย
		</div>
		<div>
		ผลิตแห่งประเทศไทย จำกัด ดังต่อไปนี้
		</div>

		<div class="indent text-color-red nowrap" style="letter-spacing:0.35px">
			ข้อ 1.  ข้าพเจ้ามีความประสงค์ขอกู้เงินกู้สามัญผ่านแอพพลิเคชั่นจากสหกรณ์ เพื่อใช้จ่ายในครอบครัว
		</div>
		<div class=" text-color-red nowrap ">
		  เป็นจำนวนเงิน .......................... บาท ( .................................................................................................................... )
		</div>
		<div class="text-color-red indent nowrap" style="letter-spacing:0.25px">
		  ข้อ 2.  ข้าพเจ้าสัญญาว่าจะชำระต้นเงินกู้เป็นงวดรายเดือนเท่ากันงวดละ ..................................... บาท
		</div>
		<div class="text-color-red nowrap">
		พร้อมด้วยดอกเบี้ยในอัตราตามที่สหกรณ์กำหนดและประกาศใช้ (ขณะทำสัญญาอัตราร้อยละ..........................ต่อปี)
		</div>
		<div class="text-color-red nowrap">
		  รวม ................................ งวดโดยชำระในวันทำการสุดท้ายของทุกเดือนจนกว่าจะชำระหนี้ครบถ้วน ทั้งนี้ นับตั้งแต่
		</div>
		<div class="text-color-red" >
		  วันทำสัญญานี้เป็นต้นไป
		</div>
		<div style="text-indent:48px;letter-spacing:0.27px;">
			ในกรณีสหกรณ์ประกาศปรับอัตราดอกเบี้ย ข้าพเจ้ายินยอมให้สหกรณ์ปรับอัตราดอกเบี้ยเพิ่มหรือลด<br/>อัตราดอกเบี้ยและปรับจำนวนเงินที่ต้องผ่อนชำระใหม่ ตั้งแต่วันที่มีผลบังคับใช้ โดยไม่จำเป็นต้องแจ้งให้<br/>ข้าพเจ้าทราบล่วงหน้า
		</div>
		<div style="letter-spacing:0.27px" class="indent nowrap">
		  ข้อ 3.  ในการกู้เงินแต่ละครั้ง ข้าพเจ้าจะทำรายการผ่านแอพพลิเคชั่น โดยใช้ชื่อและรหัสประจำตัวของ
		</div>
		<div class="text-justify">
		ข้าพเจ้า เมื่อข้าพเจ้าได้รับอนุมัติเงินกู้ตามที่ได้ทำรายการคำขอกู้เงินและสัญญาเงินกู้แล้ว ให้ถือว่าข้าพเจ้าผูกพัน
		เงินกู้ตามสัญญาฉบับนี้ เมื่อสหกรณ์โอนเงินกู้ตามจำนวนที่ผู้กู้ได้รับเข้าบัญชีเงินฝากสหกรณ์ประเภทออมทรัพย์
		</div>
		<div style="letter-spacing:0.2px" class="indent nowrap">
		  ข้อ 4.  เงินกู้ที่ได้รับอนุมัติตาม ข้อ 3. ข้าพเจ้ายินยอมให้สหกรณ์หักกลบลบหนี้เงินกู้เพื่อเหตุฉุกเฉิน และ
		</div>
		<div class="text-justify" style="letter-spacing:0.21px">
		หนี้เงินกู้เพื่อเหตุฉุกเฉิน เอทีเอ็ม และหนี้เงินกู้สามัญผ่านแอพพลิเคชั่นที่คงค้างพร้อมด้วยดอกเบี้ยถ้ามี  และให้
		สหกรณ์จ่ายเงินกู้ส่วนที่เหลือ โดยสหกรณ์โอนเงินกู้ตามจำนวนที่ผู้กู้จะได้รับเข้าบัญชีเงินฝากสหกรณ์ประเภท
		ออมทรัพย์ของผู้กู้ที่มีอยู่ในสหกรณ์ ทั้งนี้ ให้ถือว่าข้าพเจ้าได้รับเงินกู้ตามที่ได้รับอนุมัติไปแล้วครบถ้วน
		</div>
		<div>&nbsp;</div>
		<div style="letter-spacing:0.29px" class="indent nowrap">
		  ข้อ 5.  ข้าพเจ้ายินยอมให้ กฟผ. หรือหน่วยงานนายจ้างต้นสังกัด หักเงินได้รายเดือนและหรือเงินพึงได้
		</div>
		<div class="text-justify">
			อื่น ๆ ที่ กฟผ. หรือหน่วยงานนายจ้างต้นสังกัด พึงจ่ายให้ข้าพเจ้า ส่งให้แก่สหกรณ์เพื่อชำระหนี้หรือภาระผูกพัน
			อื่นใดที่ข้าพเจ้ามีต่อสหกรณ์ 
		</div>
		<div style="letter-spacing:0.23px" class="indent nowrap text-color-red">
		  ในกรณีที่ข้าพเจ้าพ้นสภาพการเป็นพนักงาน ออกจากหน่วยงาน ข้าพเจ้ายินยอมให้หักจากบัญชีเงินฝาก
		</div>
		<div class="text-color-red">
		<div style="position:absolute; top:143px; left:220px;  width:140px;" class="text-center" >
			'.$data["deptaccount_no_coop"].'
		</div>
		  สหกรณ์ประเภทออมทรัพย์เลขที่ ........................................ ในวันทำการสุดท้ายของทุกเดือน
		</div>

		<div class="indent">
		ข้าพเจ้าสัญญาว่าจะไม่ถอนคำยินยอมนี้ เว้นแต่จะได้รับคำยินยอมเป็นหนังสือจากสหกรณ์
		</div>

		<div style="letter-spacing:0.4px" class="indent nowrap">
		  ข้อ 6.  ข้าพเจ้ายินยอมให้สหกรณ์ใช้รหัสผ่านของข้าพเจ้าที่ใช้กับระบบสอบถามและพิมพ์ใบรับเงินได้
		</div>
		<div >
		  ของ กฟผ. เพื่อตรวจสอบความถูกต้องของใบรับเงินได้ที่ข้าพเจ้าสแกนประกอบในการขอกู้เงินจากสหกรณ์
		</div>

		<div  style=" letter-spacing: 0.55px;"  class="nowrap indent">
		  ข้อ 7.  ข้าพเจ้าได้รับทราบและยินยอมปฏิบัตามข้อตกลงและเงื่อนไขการใช้บริการเงินกู้สามัญผ่าน
		</div>
		<div class="text-justify" style="letter-spacing: 0.6px;">
			แอพพลิเคชั่น และหากมีเอกสารข้อตกลง เงื่อนไขเพิ่มเติมแนบท้ายคำขอกู้และสัญญาเงินกู้สามัญผ่าน
			แอพพลิเคชั่นให้ถือว่าเป็นส่วนหนึ่งของสัญญาเงินกู้ฉบับนี้ ซึ่งมีผลผูกพันข้าพเจ้าโดยสมบูรณ์
		</div>


		<div  style=" letter-spacing: 0.35px;"  class="nowrap indent">
		  ข้อ 8.  ข้าพเจ้ายินยอมให้ถือว่าไม่ว่ากรณีใด ๆ ตามข้อบังคับของสหกรณ์  ข้อ  24.  ให้เงินกู้นี้เป็นอันถึง
		</div>
		<div class="text-justify" style="letter-spacing:0.15px">
			กำหนดส่งคืนโดยสิ้นเชิงพร้อมทั้งดอกเบี้ยในทันที  โดยมิพักคำนึงถึงกำหนดเวลาที่ให้ไว้และข้าพเจ้ายินยอมให้
			สหกรณ์นำเงินทุนเรือนหุ้น เงินปันผล เงินเฉลี่ยคืน เงินฝาก หรือเงินอื่นใดทั้งหมดของข้าพเจ้าที่มีอยู่ในสหกรณ์
			มาชำระหนี้เงินกู้  ดอกเบี้ย ค่าธรรมเนียม และค่าภาระผูกพันใด ๆ ที่ข้าพเจ้ามีอยู่ต่อสหกรณ์จนครบถ้วนในทันที 
			โดยไม่จำเป็นต้องแจ้งให้ข้าพเจ้าทราบล่วงหน้า
		</div>

		<div  style=" letter-spacing: 0.37px;"  class="nowrap indent">
		  ข้อ 9.  กรณีที่หนี้เงินกู้ตามสัญญานี้ต้องเป็นอันถึงกำหนดส่งคืนโดยสิ้นเชิงพร้อมดอกเบี้ยในทันที  ตาม
		</div>
		<div class="text-justify"  style="letter-spacing:0.15px">
			ข้อ 7 ของสัญญานี้ หากข้าพเจ้ายังคงค้างชำระหนี้อยู่จนต้องมีการดำเนินคดีทางศาลแล้ว ข้าพเจ้ายินยอมให้ 
			สหกรณ์คิดดอกเบี้ยในอัตราร้อยละ 15 ต่อปี จากต้นเงินที่ค้างชำระนับถัดจากวันที่หนี้เป็นอันถึงกำหนดส่งคืน
			โดยสิ้นเชิงเป็นต้นไปจนกว่าจะชำระหนี้แล้วเสร็จ
		</div>


		<div  style=" letter-spacing: 0.2px;"  class="nowrap indent" >
		  ข้อ10.  เอกสารที่ข้าพเจ้าใช้ประกอบการขอกู้เงินมีความถูกต้องและเป็นจริงทุกประการหากมีการปลอม
		</div>
		<div class="nowrap">
			แปลงเอกสาร ข้าพเจ้ายินยอมให้สหกรณ์บังคับชำระหนี้ทั้งเงินต้นและดอกเบี้ยโดยพลันและดำเนินคดีตามกฎหมาย
		</div>

		
		<div  style=" letter-spacing: 0.65px;"  class="nowrap indent text-color-blue">
		  ข้าพเจ้าได้อ่าน รับทราบ <span class="text-color-red">เข้าใจ</span>และตกลงยินยอมปฏิบัติตามหลักเกณฑ์ และเงื่อนไขของประกาศ 
		</div>
		<div class="text-justify text-color-blue">
		  ระเบียบ และข้อบังคับของการกู้นี้ ข้าพเจ้าขอรับรองว่าข้อมูลที่ข้าพเจ้าได้ให้ไว้ในการลงทะเบียนขอกู้ ฯ ถูกต้อง
		  ตามความเป็นจริงทุกประการ
		</div>

		<div  style=" letter-spacing: 0.5px;"  class="nowrap indent text-color-blue">
		  หากข้อมูลดังกล่าวไม่ถูกต้องตามความเป็นจริงข้าพเจ้าตกลงยินยอมให้สหกรณ์ดำเนินการระงับการ
		</div>
		<div class="text-justify text-color-blue" style="letter-spacing:0.2px">
			พิจารณาเงินกู้ ฯ หรือชดใช้ความเสียหายที่เกิดขึ้นแล้วแต่กรณี และยินยอมให้สหกรณ์ดำเนินการตามกฎหมาย
			ต่อไป
		</div>

	';



	$html .= '
	<div style="position:absolute; font-weight:bold;">
	  <div style="position:absolute;"><input type="checkbox" style="margin-top:7px; " > ข้าพเจ้าได้อ่านและเข้าใจข้อความในคำขอกู้และสัญญาเงินกู้สามัญผ่านแอพพลิเคชั่นนี้โดยตลอดแล้ว </div>
	  <div style="position:absolute; top:30px;"> 
		<input type="checkbox" style="margin-top:7px; " > ยืนยัน
	  </div>
	  <div style="position:absolute; top:60px;">
		<input type="checkbox" style="margin-top:7px; " > เจ้าหน้าที่
		<input type="checkbox" style="margin-top:7px; " > ตรวจสอบถูกต้อง
		<input type="checkbox" style="margin-top:7px; " > ไม่ถูกต้อง
	  </div>
	  <div style="position:absolute; top:90px;">
	  <input type="checkbox" style="margin-top:7px; " > ผู้จัดการ หรือ รองผู้จัดการ หรือ ผู้จัดการฝ่ายสินเชื่อ
	  <input type="checkbox" style="margin-top:7px; " > อนุมัติ
	</div>

	</div>
	;';

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