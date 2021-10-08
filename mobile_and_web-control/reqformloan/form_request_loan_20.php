<?php
use Dompdf\Dompdf;

function GeneratePDFContract($data,$lib) {
	$thaimonth = ["","มกราคม","กุมภาพันธ์","มีนาคม","เมษายน","พฤษภาคม","มิถุนายน","กรกฏาคม","สิงหาคม","กันยายน","ตุลาคม","พฤศจิกายน","ธันวาคม"];
	$minispace = '&nbsp;&nbsp;';
	$space = '&nbsp;&nbsp;&nbsp;&nbsp;';
	//ตำแหน่ง
	$vassal = '';
	$pensioner = '';
	$permanent_emp = '';
	$university_staff = '';
	$vassal_emp ='';
	$other = '';

	if($header["ตำแหน่ง"]=="ข้าราชการ"){
	  $vassal ='checked';
	}else if($header["ตำแหน่ง"]=="ข้าราชการบำนาญ"){
	  $pensioner ='checked';
	}else if($header["ตำแหน่ง"]=="ลูกจ้างประจำ"){
	  $permanent_emp ='checked';
	}else if($header["ตำแหน่ง"]=="พนักงานมหาวิทยาลัย"){
	  $university_staff ='checked';
	}else if($header["ตำแหน่ง"]=="พนักงานราชการ"){
	  $vassal_emp ='checked';
	}else if($header["ตำแหน่ง"]=="อื่นๆ"){
	  $other ='checked';
	}


	if($data["option_pay"]=='1'){  //คงยอด
		$select1 = 'checked';
	}else if($data["option_pay"]=='0'){  //คงต้น
		$select2 = 'checked';
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
	  border:0.5px solid;
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

	$html .= '<div style=" margin: -20px 0px 0px 0px; ">';


	//หน้า 1
	$html .= '<div class="wrapper-page">';


	$html .= '
			<div style="width:160px; position:absolute; left:0px; top:20px; ">
			  <div>
				<div style="position:absolute ">
					<div style="margin-top:-3px; margin-left:30px; width:150px;" >'.($header["รับที่"]??null).'</div>
				</div>
				  รับที่..........................................
			  </div>
			  <div>
				<div style="position:absolute ">
				  <div style="margin-top:-3px; margin-left:30px; width:150px;" >'.($header["วันที่"]??null).'</div>
				</div>
				  วันที่..........................................
			  </div>
			</div>
			<div style="text-align:center">
			  <img src="../../resource/logo/logo.jpg" style="width:90px"/>
			</div>
			<div style="border:0.5px  solid #000000; width:200px; position:absolute; right:20px; top:10px; padding:5px; 5px ">
			  <div>
				<div style="position:absolute ">
				  <div style="margin-top:-3px; margin-left:70px; width:150px; " >'.($header["หนังสือกู้ที่"]??null).'</div>
			   </div>
				  หนังสือกู้ที่................................
			  </div>
			  <div>
				<div style="position:absolute ">
					<div style="margin-top:-3px; margin-left:40px; " >'.($header["วันที่"]??null).'</div>
				</div>
				  วันที่.........................................
			  </div>
			  <div>
				<div style="position:absolute ">
					<div style="margin-top:-3px; margin-left:70px; " >'.($header["บัญชีเงินกู้ที่"]??null).'</div>
				</div>
				บัญชีเงินกู้ที่.............................         
			  </div>
			</div>
		 
			<div style="text-align:center;  font-size:20px; font-weight:bold; margin-top:20px; "> 
				   คำขอกู้เงินสามัญ
		   </div>
		   <div style="border:0.5px solid #000000; width:290px; position:absolute; left:0px; top:154px; padding:5px; line-height:19px; font-size:17px  ">
			  <div >
				  <b>คำเตือน</b> <span>ผู้ขอกู้ต้องกรอกข้อความตามรายการที่กำหนดไว้</span> 
			  </div>
			  <div style="">
				  ในแบบคำขอกู้ <span style="text-decoration: underline;  border-bottom: 2px; ">ด้วยลายมือของตนเอง</span> โดยถูกต้องและ
			  </div>
			  <div>
				  ครบถ้วนมิฉะนั้นสหกรณ์ไม่รับพิจารณา
			  </div>
		  </div>
		  <div style="margin-right:20px;">
			<div style=" text-align:right; width:100%; margin-top:20px;" >
			  <div style="position:absolute ">
				  <div style="margin-top:-3px; margin-left:450px;  text-align:left; " >MJU Saving (Mobile Application)</div>
			  </div>
			  เขียนที่...................................................................
			</div>
			<div style="text-align:right;">
				<div style="position:absolute">					
					<div style="margin-top: -2px">
						<div class="text-center" style="position: absolute; right: 215px;  width:45px;" >
							'.date('d').'
						 </div>
						 <div class="text-center" style="position: absolute; right: 85px; width:129px;">
							'.$thaimonth[(int)date('m')].'
						 </div>
						 <div class="text-center" style="position: absolute;right: 10px;  width:45px;">
							'.(date('Y')+543).'
						 </div>
					</div>
				</div>
				
				 วันที่..........เดือน..............................พ.ศ...............
			</div>
		  </div>
		  <div style="margin-top:35px;">
			เรียน   คณะกรรมการดําเนินการสหกรณ์ออมทรัพย์มหาวิทยาลัยแม่โจ้ จํากัด
		  </div>
		  <div style="padding-left:50px;" class="nowrap">
			<div style="position:absolute ">
			  <div style="margin-top:-3px; margin-left:45px;   width:345px;" class="text-center">'.($data["full_name"]??null).'</div>
			</div>
			<div style="position:absolute;">
				  <div style="margin-top:-3px; margin-left:510px;   width:145px;" class="text-center">'.($data["member_no"]??null).'</div>
			</div>
			ข้าพเจ้า................................................................................................สมาชิกเลขทะเบียนที่........................................
		  </div>
		  <div  class="nowrap">
			<div style="position:absolute;">
			  <div style="margin-top:-3px; margin-left:164px;    width:250px;" class="text-center">'.($data["phone"]??null).'</div>
			</div>
			<div style="position:absolute;">
			  <div style="margin-top:-3px; margin-left:515px;  width:182px;" class="text-center">'.($data["หมายเลขภายใน"]??null).'</div>
			</div>
			หมายเลขโทรศัพท์ที่ติดต่อได้.........................................................................หมายเลขภายใน....................................................
		  </div>
		  <div style="height:30px;">
			<div style="position:absolute;" class="nowrap">
			  <label>สถานะภาพ</label><input type="checkbox" style="margin-top:7px; margin-left:10px;" '.$single.'>โสด
			  <input type="checkbox" style="margin-top:7px; margin-left:10px;"'.$engaged.' >สมรส
			  <input type="checkbox" style="margin-top:7px; margin-left:10px;" '.$widow.'>หม้าย
			  <input type="checkbox" style="margin-top:7px; margin-left:10px;"'.$divorce.'>อย่า
			  <input type="checkbox" style="margin-top:7px; margin-left:10px;" '.$marry.'>คู่สมรส(ถ้ามี) ชื่อ............................................................อายุ.........ปี
			</div>  
			  <div style="position:absolute;">
				  <div style="margin-top:-3px; margin-left:420px;  width:215px; " class="text-center">'.($data["คู่สมรส"]??null).'</div>
			  </div>
			  <div style="position:absolute;">
				<div style="margin-top:-3px; margin-left:650px;  width:50px;" class="text-center">'.($data["อายุ"]??null).'</div>
			  </div>
		  </div>
		  <div class="nowrap">
			ขอเสนอคำขอกู้เงินสามัญ เพื่อคณะกรรมการดำเนินการสหกรณ์ออมทรัพย์ มหาวิทยาลัยแม่โจ้ จำกัด โปรดพิจารณาดังต่อไปนี้
		  </div>
		  <div style="padding-left:40px;" class="nowrap">
			<div style="position:absolute;">
			  <div style="margin-top:-3px; margin-left:270px;   width:370px;" class="text-center">'.(number_format($data["request_amt"],2)??null).'</div>
			</div>
			ข้อ 1. ข้าพเจ้าขอกู้เงินสามัญของสหกรณ์ จำนวน......................................................................................................บาท
		  </div>
		  <div class="nowrap">
			<div style="position:absolute;">
			  <div style="margin-top:-3px; margin-left:0px;   width:510px;" class="text-center">'.($data["request_amt_text"]??null).'</div>
			</div>
		  (............................................................................................................................................)โดยจะนำไปใช้เพื่อการดังต่อไปนี้
		</div>
		<div>
		  <div style="position:absolute;">
			<div style="margin-top:-3px; margin-left:40px;   width:510px;">'.($data["objective"]??null).'</div>
		  </div>
		..................................................................................................................................................................................................
		</div>
		<div style="height:30px;">
		  <div style="position:absolute; padding-left:40px;" class="nowrap">
			<label> ข้อ 2. ในเวลานี้ข้าพเจ้าทำงานประจำตำแหน่ง</label><input type="checkbox" style="margin-top:7px; margin-left:10px;" '.$vassal.'>ข้าราชการ
			<input type="checkbox" style="margin-top:7px; margin-left:10px;" '.$pensioner.'>ข้าราชการบำนาญ
			<input type="checkbox" style="margin-top:7px; margin-left:10px;" '.$permanent_emp.'>ลูกจ้างประจำ
		  </div>  
	  </div>
	  <div style="height:30px;">
		<div style="position:absolute;" class="nowrap">
		  <input type="checkbox" style="margin-top:7px;"'.$university_staff.'>พนักงานมหาวิทยาลัย
		  <input type="checkbox" style="margin-top:7px; margin-left:10px;"'.$vassal_emp.'>พนักงานราชการ
		  <input type="checkbox" style="margin-top:7px; margin-left:10px;" '.$other.'>อื่น ๆ..........................................กองหรือคณะ..........................................
		</div>  
		<div style="position:absolute;">
			  <div style="margin-top:-3px; margin-left:320px;   width:150px;" class="text-center">'.($data["ตำแหน่งอื่นๆ"]??null).'</div>
		</div>
		<div style="position:absolute;">
			  <div style="margin-top:-3px; margin-left:550px;   width:150px;" class="text-center">'.($data["แผนก"]??null).'</div>
		</div>
	  </div>
	  <div>
		<div style="position:absolute;">
			<div style="margin-top:-3px; margin-left:220px;   width:305px; " class="text-center">'.($data["salary_amount"]??null).'</div>
		</div>
		มหาวิทยาลัยแม่โจ้ ได้รับเงินได้รายเดือน....................................................................................บาท
	  </div>
	  <div style="padding-left:40px;" class="nowrap">
		<div style="position:absolute;">
		  <div style="margin-top:-3px; margin-left:240px;   width:155px;" class="text-center">'.($data["period_share_amount"]??null).'</div>
		</div>
		<div style="position:absolute;">
			<div style="margin-top:-3px; margin-left:520px;   width:115px;" class="text-center">'.($data["sharestk_amt"]??null).'</div>
		</div>
		ข้อ 3. ข้าพเจ้าส่งเงินค่าหุ้นรายเดือนอยู่ในอัตรา.....................................บาท และมีทุนเรือนหุ้น.................................บาท 
	  </div>
	  <div style="padding-left:40px;" class="nowrap">
		ข้อ 4. ข้าพเจ้ามีหนี้สินอยู่ต่อสหกรณ์ในฐานะผู้กู้ ดังต่อไปนี้ 
	  </div>
	  ';


	$no = 1;
	foreach($dataRepor["หนี้สิน"] AS $loanArr){
	  $html.='
	<div style="padding-left:80px; " class="nowrap">
	  <div style="position:absolute; left:160px;   width:200px; margin-top:-2px;" class="text-center">
		   <div style="margin-top:-2px;">'.$loanArr["หนังสือกู้ที่"].'</div>
	  </div>
	  <div style="position:absolute; left:390px;   width:285px; margin-top:-2px;" class="text-center">
		<div style="margin-top:-2px;">'.$loanArr["วันที่"].'</div>
	  </div>
	  ('.$no.') หนังสือกู้ที่.........................................................วันที่...............................................................................
	</div>
	<div style="padding-left:100px;" class="nowrap">
	  <div style="position:absolute; left:120px;   width:210px; margin-top:-2px;  " class="text-center">
		 <div style="margin-top:-2px;">'.$data["เพื่อ"].'</div>
	  </div>
	  <div style="position:absolute; left:410px;   width:270px; margin-top:-2px; " class="text-center">
		<div style="margin-top:-2px;">'.$data["ต้นเงินคงเหลือ"].'</div>
	  </div>
	   เพื่อ.........................................................ต้นเงินคงเหลือ..........................................................................บาท
	</div>
	  ';
	$no++;
	}



	$html .='<div style="height:30px;">
	  <div style="position:absolute; padding-left:40px;" class="nowrap">
	  <div style="position:absolute;">
		<div style="margin-top:-3px; margin-left:410px;   width:95px; " class="text-center nowrap">'.($data["option_pay"]=='1' ? number_format($data["period_payment"],2)??null : null).'</div>
	  </div>
	  <div style="position:absolute;">
		  <div style="margin-top:-3px; margin-left:585px;   width:40px;  " class="text-center nowrap">'.($data["option_pay"]=='1' ? $data["period"]??null : null).'</div>
	  </div>
		<label> ข้อ 5. ข้าพเจ้ามีความต้องการ</label><input type="checkbox" style="margin-top:7px; margin-left:25px; margin-right:8px;" '.$select1.'>ชำระเงินต้นพร้อมดอกเบี้ย เดือนละ............................บาท จำนวน.............งวด
	  </div> 
	  
	</div>
	  <div style="height:30px;">
		 <div style="position:absolute;">
	  <div style="margin-top:-3px; margin-left:370px;   width:100px;" class="text-center nowrap">'.($data["option_pay"]=='0' ? number_format($data["period_payment"],2)??null : null).'</div>
	  </div>
		<div style="position:absolute;">
		  <div style="margin-top:-3px; margin-left:625px;   width:40px;" class="text-center nowrap">'.($data["option_pay"]=='0' ? $data["period"]??null : null).'</div>
		</div>
	  <div style="position:absolute; padding-left:206px;" class="nowrap">
		<input type="checkbox" style="margin-top:7px; margin-left:25px; margin-right:8px;"'.$select2.'>ชำระเงินต้น เดือนละ............................บาท พร้อมดอกเบี้ย จำนวน............งวด
	  </div> 
	</div>
	<div style="padding-left:40px;" class="nowrap">
		ข้อ 6. ในการรับเงินกู้ข้าพเจ้าได้ทำหนังสือสำหรับเงินกู้สามัญให้ไว้ต่อสหกรณ์ตามแบบที่สหกรณ์กำหนด
	</div>
	<div style="padding-left:40px;" class="nowrap">
		ข้อ 7. ในการกู้เงินตามคำขอกู่้นี้ ข้าพเจ้าได้นำไปใช้จ่ายในครอบครัว ซึ่งมีคู่สมรสได้รับทราบแล้ว
	</div>
	<div style="margin-top:30px;"class="text-right" >
		ลงชื่อ......................................................ผู้ขอกู้
	</div>
	<div style="margin-right:30px;" class="text-right">
		<div style="position:absolute;">
			<div style="margin-top:-3px; margin-left:471px;   width:200px;" class="text-center nowrap">'.($data["full_name"]??null).'</div>
		</div>
		(......................................................)
	</div>

	';




	$html .= '</div>';

	//หน้า 2 
	$html .= '<div style="font-size:12pt; margin:0px -15px 0px -15px;" class="wrapper-page">';

	$html .= '
	  <div style="font-size:16pt; font-weight:bold; margin-top:-20px;"  class="text-center">คำเสนอค้ำประกัน</div>

	';

	$html .= '
	   <table style="width:100%; margin-top:10px; font-size:11pt">
		 <thead>
			<tr>
			  <th style="width:5%;"><p>ลำ</p> <p>ดับ</p> <p>ที่</p></th>
			  <th>ชื่อ-นามสกุล</th>
			  <th style="width:7%;"><p>สมาชิก</p><p>เลข</p><p>ทะเบียน</p></th>
			  <th style="width:7%;"><p>เงินได้</p><p>รายเดือน</p><p>(บาท)</p></th>
			  <th style="width:30%;"><p>เป็นผู้ค้ำประกันอื่น</p><p>ให้กับผู้กู้ชื่อ</p></th>
			  <th style="width:20%;"><p>ข้าพเจ้าผูกพันตนที่จะค้ำประกัน</p><p>ตามลำดับคำขอข้างต้นนี้จึง</p><p>ลงลายมือชื่อไว้เป็นสำคัญ</p></th>
			</tr>
		 </thead>
		 <tbody>';
		  
		 for($i=0;$i<=21;$i++){
			$html.='
			<tr>
			  <td>'.($guarantee[$i]["ลำดับที่"]??null).'</td>
			  <td>'.($guarantee[$i]["ชื่อ"]??null).'</td>
			  <td>'.($guarantee[$i]["เลขที่สมาชิก"]??null).'</td>
			  <td>'.($guarantee[$i]["เงินได้รายเดือน"]??null).'</td>
			  <td>'.($guarantee[$i]["เป็นผู้ค้ำประกันอื่น"]??null).'</td>
			  <td>'.($guarantee[$i]["เป็นผู้ค้ำประกันใหกับผู้ชื่อ"]??null).'</td>
			</tr>
			';
		 }
		 

	//      $guarantee[0]["ลำดับที่"]="1";
	// $guarantee[0]["ชื่อ"]=" ชื่อ นามสกุล";
	// $guarantee[0]["เลขที่สมาชิก"]="000124";
	// $guarantee[0]["เงินได้รายเดือน"]="20000";
	// $guarantee[0]["เป็นผู้ค้ำประกันใหกับผู้ชื่อ"]="ชื่อ";

	$html.='
		 </tbody>
	   </table>
	   <div style="margin-left:20px;  font-weight:bold;"  class="nowrap">
		 <div style="text-decoration: underline; display:inline;">คำเตือน</div> 
		 <div style="display:inline;" class="nowrap">สมาชิกคนหนึ่งจะมีหนี้สินต่อสหกรณ์ในฐานะผู้ค้ำประกัน โดยจำนวนต้นเงินรวมทั้งสิ้นที่ตนจะกู้เงินสามัญไม่ได้</div>
	   </div>
	   <div style="margin-right:85px; font-weight:bold; margin-top:5px;" class="text-right">
		(ลงชื่อ.................................................ผู้ขอกู้)
	  </div>
	   <div style="margin-right:113px;" class="text-right">
		<div style="position:absolute ">
		  <div style="margin-top:-3px; margin-left:450px;" class="text-center" >'.($data["full_name"]??null).'</div>
		</div>
		(........................................................)
		</div>
	   <div style="display:flex; height:120px;">
		  <div style="width:50%;">
			  <div style="font-weight:bold;">ความเห็นของเจ้าหน้าที่การเงินประจำหน่วยงาน</div>
			  <div style="height:30px; margin-top:-5px;">
				<div style="position:absolute; font-weight:bold;">
				  <input type="checkbox" style="margin-top:7px; margin-left:25px; margin-right:8px;"> มีเงินได้รายเดือนเหลือหัก ณ ที่จ่าย
				</div>
			  </div>
			  <div style="margin-left:25px; font-weight:bold;">............................................................เจ้าหน้าที่การเงิน</div>
			  <div style="margin-left:25px; font-weight:bold;">(............................................................)</div>
		  </div>
		  <div style=" margin-left:350px; width:50%;">
			 <div style="font-weight:bold; "> พิจารณาแล้วเห็นว่า สมควรให้กู้ได้/ไม่ได้</div>
			 <div style="margin-top:-5px;"><div style="margin-left:30px;">.....................................................................</div></div>
			 <div style="margin-left:25px; font-weight:bold;">...............................................................ผู้แทนหน่วยงาน</div>
			 <div style="margin-left:25px; font-weight:bold;">(............................................................)</div>
		  </div>
	   </div>
	   <div style="font-weight:bold;" class="text-center">บันทึกการพิจารณาให้ความเห็นของผู้บังคับบัญชา</div>
	   <div style="margin-left:400px; fonw-weight:bold;">วันที่.........................................................................</div>
	   <div style="fonw-weight:bold; font-size:13pt; padding-left:60px; letter-spacing:0.07 " class="nowrap">ข้าพเจ้า ได้พิจารณาตามความรู้เห็นและตามที่ได้สอบถามแล้วเห็นสมควรให้กู้เงินสหกรณ์ และขอรับรองว่าขณะนี้ผู้กู้ไม่อยู่ในระหว่าง</div>
	   <div style="font-size:13pt;">การถูกสอบสวนหรือต้องโทษทางวินัย</div>
	   <div style="font-weight:bold; margin-left:250px; margin-top:-5px;">ลามมือชื่อ.........................................................................</div>
	   <div style="font-weight:bold; margin-left:295px; ">(.........................................................................)</div>
	   <div style="font-weight:bold; position:absolute; left:250px;">ตำแหน่ง............................................................................</div>
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