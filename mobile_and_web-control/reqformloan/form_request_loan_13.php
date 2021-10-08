<?php
use Dompdf\Dompdf;

function GeneratePDFContract($data,$lib) {
	$thaimonth = ["","มกราคม","กุมภาพันธ์","มีนาคม","เมษายน","พฤษภาคม","มิถุนายน","กรกฏาคม","สิงหาคม","กันยายน","ตุลาคม","พฤศจิกายน","ธันวาคม"];
	$minispace = '&nbsp;&nbsp;';
	$space = '&nbsp;&nbsp;&nbsp;&nbsp;';
	//demo data

	if($data["สถานะภาพ"]=="โสด"){
	  $single ='checked';
	}else if($data["สถานะภาพ"]=="สมรส"){
	  $engaged='checked';
	}else if($data["สถานะภาพ"]=="หม้าย"){
	  $widow='checked';
	}else if($data["สถานะภาพ"]=="อย่า"){
	  $divorce='checked';
	}else if($data["สถานะภาพ"]=="คู่สมรส"){
	  $marry='checked';
	}
	//ตำแหน่ง
	$vassal = '';
	$pensioner = '';
	$permanent_emp = '';
	$university_staff = '';
	$vassal_emp ='';
	$other = '';

	if($data["ตำแหน่ง"]=="ข้าราชการ"){
	  $vassal ='checked';
	}else if($data["ตำแหน่ง"]=="ลูกจ้างประจำ"){
	  $permanent_emp ='checked';
	}else if($data["ตำแหน่ง"]=="พนักงานมหาวิทยาลัย"){
	  $university_staff ='checked';
	}else if($data["ตำแหน่ง"]=="ข้าราชการบำนาญ"){
	  $pensioner ='checked';
	}else if($data["ตำแหน่ง"]=="พนักงานราชการ"){
	  $vassal_emp ='checked';
	}else if($data["ตำแหน่ง"]=="พนักงานส่วนงาน"){
	  $work_emp ='checked';
	}else if($data["ตำแหน่ง"]=="อื่นๆ"){
	  $other ='checked';
	}



	// เสนอผู้มีอำนาจอนุมัติ

	if($data["ผู้มีอำนาจอนุมัติ"]=='0'){
		//ไม่อนุมัติ
		$b_notaccep = 'checked';
	}else if($data["ผู้มีอำนาจอนุมัติ"]=='1'){
	  // อนุมัติ
	  $b_accept = 'checked';
	}

	//รายเดือนเหลือหัก 
	if($data["เงินได้รายเดือนเหลือหัก"]=='0'){
	  //มีเงินได้รายเดือนเหลือหัก
	  $have = 'checked';
	}else if($data["เงินได้รายเดือนเหลือหัก"]=='1'){
	// ไม่มีเงินได้รายเดือนเหลือหัก
	  $nothave = 'checked';
	}

	// ความเห็น
	if($data["ความเห็น"]=='0'){
	  //ไม่อนุมัติ
	  $notaccep = 'checked';
	}else if($data["ความเห็น"]=='1'){
	// อนุมัติ
	$accept = 'checked';
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
	  font-size:15pt;
	  line-height: 25px;

	}
	.text-center{
	  text-align:center
	}
	.text-right{
	  text-align:right
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
	table{
	  border-collapse: collapse;
	  line-height: 20px
	}
	th,td{
	  border:0.25px solid;
	  text-align:center;
	}
	th{
	  font-size:16px;
	}
	td{
	 
	  padding:2px;
	  height:19px;
	}
	p{
	  margin:0px;
	}

	</style>
	';


	$html .= '<div style=" margin: -30px 0px -50px 30px; ">';


	//หน้า 1
	$html .= '<div class="wrapper-page">';
	$html .= '
			<div style="width:180px; position:absolute; left:30px; top:20px; border:0.5px solid; padding:10px 10px ">
			  <div>
				<div style="position:absolute ">
					<div style="margin-top:-3px; margin-left:30px; width:150px;" >'.($data["รับที่"]??null).'</div>
				</div>
				  รับที่..........................................
			  </div>
			  <div>
				<div style="position:absolute ">
				  <div style="margin-top:-3px; margin-left:30px; width:150px;" >'.($data["วันที่"]??null).'</div>
				</div>
				  วันที่..........................................
			  </div>
			</div>
			<div style="text-align:center; margin-top:30px;" >
			  <img src="../../resource/logo/logo.jpg" style="width:75px"/>
			</div>
			<div style="border:0.5px  solid #000000; width:180px; position:absolute; right:0px; top:20px; padding:10px; 10px ">
			  <div>
				<div style="position:absolute ">
				  <div style="margin-top:-3px; margin-left:70px; width:150px; " >'.($data["หนังสือกู้ที่"]??null).'</div>
			   </div>
				  สัญญากู้ที่................................
			  </div>
			  <div>
				<div style="position:absolute ">
					<div style="margin-top:-3px; margin-left:40px; " >'.($data["วันที่"]??null).'</div>
				</div>
				  วันที่.........................................
			  </div>
			</div>
			<div style="text-align:center;  font-size:20px; font-weight:bold; margin-top:10px; "> 
				สัญญาเงินกู้ฉุกเฉินพร้อมเปย์
		   </div>
		  <div style="  width:100%; margin-top:10px; text-align:left;" >
			  <div style="position:absolute  ">
				<div style="margin-top:-3px;  margin-left:410px;  " class="text-center">'.($data["เขียนที่"]??null).'</div>
			  </div>
			 <div style="text-align:right;">เขียนที่........................................................................</div>
		  </div>
		  <div>
			<div style="position:absolute  ">
				<div style="margin-top:-3px;  margin-left:170px;" class="text-center">'.date('d').'</div>
			</div>
			<div style="position:absolute; height:30px;">
				<div style="margin-top:-3px;  margin-left:490px;">'.$thaimonth[(int)date('m')].'</div>
			</div>
			<div style="position:absolute; height:30px;">
				<div style="margin-top:-3px;  margin-left:630px;">'.(date('Y')+543).'</div>
			</div>
			<div style="text-align:right;">
			  วันที่..........เดือน...................................พ.ศ...............
			</div>
		  </div>
		  <div style="margin-top:5px; padding-left:40px;"  class="nowrap">
			<div style="position:absolute; height:30px;">
			  <div style="margin-top:-3px; margin-left:60px;  width:270px;" class="text-center">'.($data["full_name"]??null).'</div>
			</div>
			ข้าพเจ้าชื่อ......................................................................... เป็นสมาชิกสหกรณ์ออมทรัพย์มหาวิทยาลัยแม่โจ้ จำกัด 
		  </div>
		  <div class="nowrap">
			<div style="position:absolute; height:30px;">
			  <div style="margin-top:-3px; margin-left:75px;  width:80px;" class="text-center">'.($data["member_no"]??null).'</div>
			</div>
			<div style="letter-spacing:0.41px;">
				เลขทะเบียนที่.......................ประเภทบุคลากร
				<div style="position:absolute;" >
				  <input type="checkbox" style="margin-top:7px; margin-left:8px;  margin-right:6px;"'.($vassal??null).' >
				  <div style="position:absolute;" class="nowrap">ข้าราชการ </div>
			   </div>
			   <div style="position:absolute; left:420px;" >
				  <input type="checkbox" style="margin-top:7px; margin-left:8px;  margin-right:6px;"'.($permanent_emp??null).' >
				  <div style="position:absolute;" class="nowrap">ลูกจ้างประจำ</div>
			   </div>
			   <div style="position:absolute; left:540px;" >
				  <input type="checkbox" style="margin-top:7px; margin-left:8px;  margin-right:6px;"'.($university_staff??null).' >
				  <div style="position:absolute;" class="nowrap">พนักงานมหาวิทยาลัย</div>
			   </div> 
			</div>
		  </div>
		  <div style="height:25px;" class="nowrap">
			<div style="position:absolute; left:20px;" >
			  <input type="checkbox" style="margin-top:6px; margin-left:6px;  margin-right:6px;"'.($pensioner??null).' >
			  <div style="position:absolute;" class="nowrap">ข้าราชการบำนาญ/บำเหน็จรายเดือน </div>
			</div>
			<div style="position:absolute; left:260px;" >
			  <input type="checkbox" style="margin-top:6px; margin-left:6px;  margin-right:6px;"'.($vassal_emp??null).' >
			  <div style="position:absolute;" class="nowrap">พนักงานราชการ</div>
			</div>
			<div style="position:absolute; left:385px;" >
			  <input type="checkbox" style="margin-top:6px; margin-left:6px;  margin-right:6px;"'.($work_emp??null).' >
			  <div style="position:absolute;" class="nowrap">พนักงานส่วนงาน</div>
			</div>
			<div style="position:absolute; left:510px;" >
			  <input type="checkbox" style="margin-top:6px; margin-left:6px;  margin-right:6px;" '.($other??null).' >
			  <div style="position:absolute;" class="nowrap">
				  <div style="position:absolute; margin-left:30px; ">
					  <div style="margin-top:-3px;">'.$data["ตำแหน่งอื่นๆ"].'</div>
				  </div>
				อื่นๆ......................................
			  </div>
			</div> 
		  </div>
		  <div style="height:25px;" class="nowrap">
			<div style="position:absolute; margin-left:33px; ">
				<div style="margin-top:-3px; width:270px;" class="text-center nowrap">'.$data["pos_group"].'</div>
			</div>
			สังกัด.......................................................................... ซึ่งต่อไปนี้เรียกว่า “ผู้กู้” ได้ทำสัญญาเงินกู้ฉุกเฉินพร้อมเปย์ไว้ให้แก่
		  </div>
		  <div>
			  สหกรณ์ออมทรัพย์มหาวิทยาลัยแม่โจ้ จำกัด ซึ่งต่อไปนี้เรียกว่า “ผู้ให้กู้” เพื่อเป็นหลักฐาน ดังนี้
		  </div>
		  <div style="line-height:20px;">
			<div style="padding-left:40px;" class="nowrap">
				<div style="position:absolute ">
				  <div style="margin-top:-3px; margin-left:185px; width:140px; " class="text-center" >'.(number_format($data["request_amt"],2)??null).'</div>
				</div>
				<div style="position:absolute ">
				  <div style="margin-top:-3px; margin-left:360px; width:270px;" class="text-center" >'.($data["request_amt_text"]??null).'</div>
				</div>
				1.	ผู้กู้ได้ขอกู้เงินจากผู้ให้กู้ จำนวน...................................... บาท (..........................................................................)
			</div>
			<div style="padding-left:40px;" class="nowrap">
				2.	ผู้กู้ตกลงจะส่งคืนต้นเงินพร้อมดอกเบี้ยเงินกู้ให้แก่สหกรณ์ทุกสิ้นเดือนของทุกเดือน ในอัตราตามประกาศสหกรณ์
			</div>
			<div class="nowrap" style="letter-spacing:-0.12px;">
			  นับตั้งแต่วันที่รับเงินกู้ เป็นต้นไปจนกว่าจะชำระหนี้ครบถ้วน โดยจะชำระดอกเบี้ยเป็นรายเดือนทุก ๆ เดือน ติดต่อกันภายในวัน
			</div>
			<div class="nowrap" style="letter-spacing:-0.2px;">
			  สิ้นสุดของเดือน ในกรณีที่สหกรณ์มีการเปลี่ยนแปลงอัตราดอกเบี้ยโดยประกาศสหกรณ์ ข้าพเจ้ายินยอมให้สหกรณ์คิดดอกเบี้ย
			</div>
			<div class="nowrap" style="letter-spacing:-0.2px;">
			  ตามอัตราที่เปลี่ยนแปลงได้ทันที โดยไม่ต้องบอกกล่าวล่วงหน้าหรือได้รับความยินยอมจากข้าพเจ้าแต่อย่างใด และข้าพเจ้าจะไม่
			</div>
			<div>
			  โต้แย้งหรือยกเป็นข้อต่อสู้ใด ๆ ทั้งสิ้น
			</div>
			<div style="padding-left:40px; letter-spacing:0.06px;" class="nowrap">
				3.	ผู้กู้อาจเบิกเงินกู้ตามสัญญาเงินกู้ฉุกเฉินพร้อมเปย์ตามจำนวนที่ต้องการเมื่อใดก็ได้ แต่รวมกันแล้วทั้งต้นเงินและ
			</div>
			<div>
				ดอกเบี้ยค้างต้องไม่เกินวงเงินกู้ตามข้อ 1. และการเบิกเงินกู้ดังกล่าวให้ถือเป็นได้รับเงินกู้แล้วตามสัญญา
			</div>
			<div style="padding-left:58px; letter-spacing:0.17px;" class="nowrap">
			  วิธีเบิกเงินกู้ผ่านช่องทางโมบายแอปพลิเคชันของสหกรณ์ สมาชิกต้องปฏิบัติตามหลักเกณฑ์ วิธีการ และชำระ
			</div>
			<div>
			  ค่าธรรมเนียมการเบิกเงินกู้ตามที่ธนาคารและสหกรณ์ฯ กำหนด
			</div>
			<div style="padding-left:58px; letter-spacing:0.56px;" class="nowrap">
			  หากผู้กู้มีอายุการทำงานเหลือ 1 ปี สหกรณ์จะปิดการกดรับเงินกู้ ยกเว้นผู้กู้ที่เป็นพนักงานราชการหรือ
			</div>
			<div>
			  พนักงานส่วนงาน
			</div>
			<div style="padding-left:40px; letter-spacing:0.1px;" class="nowrap">
				4.	สัญญาเงินกู้ฉุกเฉินพร้อมเปย์มีอายุหนึ่งปี เมื่อสัญญาเงินกู้นี้ครบกำหนด หากสหกรณ์หรือข้าพเจ้ามิได้บอกเลิก
			</div>
			<div class="nowrap" style="letter-spacing:-0.06">สัญญา ให้ถือว่าสัญญาเงินกู้ฉบับนี้มีผลบังคับใช้ต่อไปไม่เกิน 3 ปี นับตั้งแต่วันทำสัญญา โดยมีเงื่อนไขและข้อตกลงคงเดิมทุก</div>
			<div class="nowrap" style="letter-spacing:-0.22"> ประการจนกว่าฝ่ายใดฝ่ายหนึ่งจะบอกเลิกสัญญา และหากมีการบอกเลิกสัญญาเมื่อใดก็ตาม ผู้กู้จะต้องชำระต้นเงินและดอกเบี้ย</div>
			<div>ทั้งหมดทันที</div>
			<div style="padding-left:40px; " class="nowrap">5.	ผู้กู้ตกลงยินยอมให้เจ้าหน้าที่ผู้จ่ายค่าจ้างหรือเงินเดือน หรือเงินบำนาญ หรือเงินบำเหน็จรายเดือน หรือเงินได้อื่น</div>
			<div class="nowrap" style="letter-spacing:0.08">ใดที่ทางราชการหรือนายจ้างจะพึงจ่ายให้ผู้กู้หักเงินดังกล่าวเพื่อชำระหนี้และดอกเบี้ยเงินกู้ ค่าธรรมเนียม และอื่น ๆ ตาม</div>
			<div class="nowrap" style="letter-spacing:-0.23">  รายการที่สหกรณ์เรียกเก็บให้แก่สหกรณ์เป็นลำดับแรกตลอดไป โดยสัญญานี้มีผลผูกพันผู้กู้ตลอดไปจนกว่าผู้กู้และสหกรณ์จะได้</div>
			<div>แก้ไขหรือยกเลิกสัญญานี้</div>
			<div style="padding-left:40px; " class="nowrap"> 6.	หากผู้กู้ขาดจากสมาชิกภาพตามข้อบังคับของสหกรณ์ ถ้าผู้กู้ไม่จัดการชำระคืนเงินกู้พร้อมทั้งดอกเบี้ยในทันที ผู้กู้ </div>
			<div class="nowrap" style="letter-spacing:-0.23">ยินยอมให้เจ้าหน้าที่ผู้จ่ายค่าจ้างหรือเงินเดือน หรือเงินบำนาญ หรือเงินบำเหน็จรายเดือน หรือกองทุนบำเหน็จบำนาญ</div>
			<div class="nowrap" style="letter-spacing:-0.08">ข้าราชการ หรือเงินกองทุนสำรองเลี้ยงชีพ หรือเงินอื่นใดที่ทางราชการหรือนายจ้างจะพึงจ่ายให้ผู้กู้หักเงินดังกล่าวชำระคืนต้น</div>
			<div>เงินกู้และดอกเบี้ยให้แก่ผู้ให้กู้ให้เสร็จสิ้นก่อนเจ้าหนี้รายอื่นได้</div>
			<div style="padding-left:58px; letter-spacing:0.2px;" class="nowrap">ผู้กู้ได้อ่านและเข้าใจข้อความในสัญญาเงินกู้ฉุกเฉินพร้อมเปย์แล้ว จึงลงลายมือชื่อไว้เป็นสำคัญต่อหน้าพยาน</div>
			<div>ณ วัน เดือน ปี ที่ระบุไว้ข้างต้น</div>
		  </div>
		  <div class="text-center">(ลงชื่อ)..................................................................ผู้กู้</div>
		  <div style="margin-left:225px;">
			<div style="position:absolute ">
			  <div style="margin-top:-3px; margin-left:0px; width:245px;" class="text-center">'.($data["full_name"]??null).'</div>
			</div>
			(..................................................................)
		  </div>
		  <div style="display:flex; height:25px;" class="nowrap">
			 <div>(ลงชื่อ)....................................................................พยาน</div>
			 <div style="margin-left:355px;">(ลงชื่อ)....................................................................พยาน</div>
		  </div>
		  <div style="display:flex">
			<div style="margin-left:33px;">(....................................................................)</div>
			<div style="margin-left:388px; height:25px;">(....................................................................)</div>
		  </div>
		  <div style="margin-top:-20px;" class="text-right">2/รายการต่อไปนี้...............................</div>
	  ';




	$html .= '</div>';

	//หน้า 2 
	$html .= '<div class="wrapper-page">';

	$html .= '
	  <div style="margin-top:20px;"  class="text-center">-2-</div>
	  <div style="margin-top:10px;"  class="text-center">รายการต่อไปนี้ เจ้าหน้าที่ของสหกรณ์กรอกเอง</div>
	  <div style="margin-top:20px;">
	  <div style="position:absolute;  ">
	  <div style="height:25px;" class="text-center"> </div>
	  ';
	  


	// ข้อมูลในตาราง
	$html .='
	<div style="display:flex; height:25px;">
	  <div style="width:150px;  " class="text-center">'.($data[""]??null).'</div>
	  <div style="margin-left:150px; width:193px;  " class="text-center">'.($data[""]??null).'</div>
	  <div style="margin-left:343px; width:175px;  " class="text-center">'.($data["วงเงินกู้"]??null).'</div>
	  <div style="margin-left:517px; width:155px;  " class="text-center">'.($data["วงเงินกู้คงเหลือ"]??null).'</div>
	</div>
	';


	 $html .='</div>
		  <table style="width:100%;">
			  <thead>
				  <tr>
					<th style="width:150px;">เงินได้รายเดือน</th>
					<th>มีค่าหุ้นอยู่</th>
					<th>หนี้เงินกู้คงเหลือ</th>
					<th style="width:150px;">วงเงินกู้คงเหลือ</th>
				  </tr>
			  </thead>
			  <tbody>
				<tr>
				  <td style="height:90px;"></td>
				  <td></td>
				  <td></td>
				  <td></td>
				</tr>
			  </tbody>
		  </table>
	  </div>
	  <div style="font-weight:bold; margin-top:10px; margin-left:10px;">เสนอผู้มีอำนาจอนุมัติ</div>
	  <div style="padding-left:105px;">
		<div style="position:absolute; margin-left:320px; ">
			<div style="margin-top:-3px;  width:190px;" class="text-center nowrap">'.$data["สิทธิ์กู้ได้"].'</div>
		</div>
		1.	ได้ตรวจสัญญาเงินกู้ฉุกเฉินพร้อมเปย์แล้ว	ผู้กู้มีสิทธิ์กู้ได้.................................................... บาท </div>
	  <div style="padding-left:105px;">
		2. เห็นควร
		<div style="position:absolute;" >
		  <input type="checkbox" style="margin-top:7px; margin-left:5px;  margin-right:5px;"'.($b_accept??null).' >
		  <div style="position:absolute;" class="nowrap">อนุมัติ </div>
		</div>
		<div style="position:absolute; margin-left:80px; " >
		  <input type="checkbox" style="margin-top:7px; margin-left:5px;  margin-right:5px;"'.($b_notaccep??null).' >
		  <div style="position:absolute;" class="nowrap">ไม่อนุมัติ </div>
		</div>
	  </div>
	  <div style="padding-left:105px;" class="nowrap">
		<div style="position:absolute; margin-left:110px; ">
		  <div style="margin-top:-3px;  width:150px; border:1px soid red;">'.$data["ข้อชี้แจงอื่น"].'</div>
		</div>
	  3.	ข้อชี้แจงอื่น ๆ ..................................................................................................................................
	  </div>
	  <div style="margin-top:25px;" class="text-right">(ลงชื่อ)....................................................................เจ้าหน้าที่สินเชื่อ/เจ้าหน้าที่ธุรกรรมสินเชื่อ </div>
	  <div style="margin-left:180px;">(....................................................................)</div>
	  <div style="margin-top:20px; margi-left:10px; font-weight:bold; ">
		  ความเห็นของเจ้าหน้าที่การเงินของมหาวิทยาลัย
	  </div>
	  <div style="margin-left:70px; height:25px;">
		<div style="position:absolute;" >
		  <input type="checkbox" style="margin-top:7px; margin-left:5px;  margin-right:5px;"'.($have??null).' >
		  <div style="position:absolute;" class="nowrap">มีเงินได้รายเดือนเหลือหัก ณ ที่จ่าย  </div>
		</div>
	  </div>
	  <div style="margin-left:70px; height:25px;">
		<div style="position:absolute;" >
		  <input type="checkbox" style="margin-top:7px; margin-left:5px;  margin-right:5px;"'.($nothave??null).' >
		  <div style="position:absolute;" class="nowrap">ไม่มีเงินได้รายเดือนเหลือหัก ณ ที่จ่าย</div>
		</div>
	  </div>
	  <div style="margin-top:25px;" class="text-right">(ลงชื่อ)....................................................................เจ้าหน้าที่การเงิน</div>
	  <div style="margin-left:328px;">(....................................................................)</div>
	  <div style="font-weight:bold; margin-top:40px">
		<div style="position:absolute; margin-left:50px;" >
		  <input type="checkbox" style="margin-top:7px; margin-left:7px;  margin-right:3px;"'.($accept??null).' >
		  <div style="position:absolute;" class="nowrap">อนุมัติ </div>
		</div>

		<div style="position:absolute; margin-left:120px;" >
		  <input type="checkbox" style="margin-top:7px; margin-left:7px;  margin-right:3px;"'.($notaccept??null).' >
		  <div style="position:absolute;" class="nowrap">ไม่อนุมัติ </div>
		</div>
		เห็นควร
	  </div>
	  <div style="margin-top:25px;" class="text-right">(ลงชื่อ)....................................................................ประธานกรรมการ/รองประธานกรรมการ/ผู้จัดการ</div>
	  <div style="margin-left:150px;">(....................................................................)</div>
	';
$html .= '
	</div>
</div>';
		
	
	
	
	
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