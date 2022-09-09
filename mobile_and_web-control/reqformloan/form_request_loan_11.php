<?php
use Dompdf\Dompdf;

$dompdf = new DOMPDF();
function GeneratePDFContract($data,$lib) {
$data["รับที่"] = 'สอ.กรมการแพทย์ จำกัด';
$data["หนี้คงเหลือ"] = '';
$data["กู้ที่"] = '';
$data["เขียนที่"] = 'DMS Saving';
$data["วันที่รับ"] = $lib->convertdate(date("Y-m-d"),"d M Y");
$data["วันที่กู้"] = $lib->convertdate(date("Y-m-d"),"d M Y");
$data["วันที่เขียน"] = $lib->convertdate(date("Y-m-d"),"d M Y");
$data["ชื่อ"] = $data["full_name"]; 
$data["เลขสมาชิก"] =  $data["member_no"];
$data["เงินเดือน"] = number_format($data["salary_amount"],2);
$data["สังกัด"] = $data["pos_group"];
$data["จำนวนเงินขอกู้"] = number_format($data["request_amt"],2);
$data["จำนวนเงินขอกู้คำอ่าน"] = $lib->baht_text($data["request_amt"]);
$data["วัตถุประสงค์"] = $data["objective"];
$data["จำนวนงวด"] = $data["period"];
$data["หุ้น"] = $data["sharestk_amt"];
$data["รายเดือน"] = number_format($data["period_payment"],2); 
$data["วงเงินกู้ฉุกเฉิน"] = '';
$data["หนี้ฉุกเฉินค้าง"] = '';
$data["วงเงินกู้ฉุกเฉินคงเหลือ"] = '';


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
			  
			  .wrapper-page:last-child {
				page-break-after: avoid;
			  }
			</style>';
//ขนาด
$html .= '<div style="margin:-40px 0px -30px 10px;" >';
//หน้า 1
$html .= '<div class="wrapper-page">';
//ส่วนหัว
$html .= '
	<div style=" text-align: center; margin-top:20px;"><img src="../../resource/logo/logo.jpg" alt="" width="100" height="0"></div>
	<div style="border:0.5px solid #000000; width:180px; position:absolute; left:40px; top:10px; padding:10px 10px;  ">
		<div>
			<div class="absolute" style="margin-left:30px;"><div class="data nowrap">'.($data["รับที่"]??null).'</div></div>
			รับที่........................................
		</div>
		<div>
			<div class="absolute" style="margin-left:30px;"><div class="data nowrap">'.($data["วันที่รับ"]??null).'</div></div>
			วันที่........................................
		</</div>
	</div>
	<div style="border:0.5px solid #000000; width:225px; position:absolute; right:0px; top:-15px; padding:10px; 10px; ">
		<div class="nowrap" style="font-size:15pt;">
			<div class="absolute" style="margin-left:70px;"><div class="data nowrap">'.($data["กู้ที่"]??null).'</div></div>
			หนังสือกู้ที่ ............................................
		</div>
		
		<div class="nowrap"  style="font-size:15pt;">
			<div class="absolute" style="margin-left:30px;"><div class="data nowrap">'.($data["วันที่กู้"]??null).'</div></div>
			วันที่.......................................................
		</div>
		<div class="nowrap"  style="font-size:15pt;">
			<div class="absolute" style="margin-left:70px;"><div class="data nowrap">'.($data["บัญชีเงินกู้ที่"]??null).'</div></div>
			บัญชีเงินกู้ที่............................................
		</div>
  	</div>
	<div style="font-size: 18pt;font-weight: bold; text-align:center; margin-center:30px; height: 45px; margin-top:10px;">คำขอกู้และหนังสือกู้เงินเพื่อเหตุฉุกเฉิน</div>
	<div class="flex" style="height:25px;">
		<div style="margin-left:365px; font-size:15pt;">
			<div class="absolute" style="margin-left:45px;"><div class="data nowrap">'.($data["เขียนที่"]??null).'</div></div>
			เขียนที่.................................................................
		</div>
	</div>
	<div style="margin-left:365px; font-size:15pt;">
		<div class="absolute" style="margin-left:45px;"><div class="data nowrap">'.($data["วันที่เขียน"]??null).'</div></div>
		วันที่.....................................................................
	</div>
	<div>
			เรียน	คณะกรรมการดำเนินการสหกรณ์ออมทรัพย์กรมการแพทย์ จำกัด
	</div>
	<div class="sub-list  nowrap" style="margin-top:15px;">
		<div class="absolute  center" style="margin-left:45px; width:290px;"><div class="data nowrap">'.($data["ชื่อ"]??null).'</div></div>
		<div class="absolute" style="margin-left:510px;"><div class="data nowrap">'.($data["เลขสมาชิก"]??null).'</div></div>
		ข้าพเจ้า..........................................................................สมาชิกสหกรณ์เลขทะเบียนที่.......................
	</div>
	<div class="nowrap">
		<div class="absolute" style="margin-left:55px;"><div class="data nowrap center" style="width: 120px;">'.($data["เงินเดือน"]??null).'</div></div>
		<div class="absolute" style="margin-left:240px;"><div class="data nowrap center" style="width: 320px;">'.($data["สังกัด"]??null).'</div></div>
		เงินเดือน................................บาท สังกัด..................................................................................ขอกู้เงินเพื่อเหตุฉุกเฉิน
	</div>
	<div class="nowrap">
		<div class="absolute" style="margin-left:65px;"><div class="data nowrap center" style="width: 125px;">'.($data["จำนวนเงินขอกู้"]??null).'</div></div>
		<div class="absolute" style="margin-left:225px;"><div class="data nowrap center" style="width: 360px;">'.($data["จำนวนเงินขอกู้คำอ่าน"]??null).'</div></div>
		จำนวนเงิน.................................บาท (..............................................................................................) เพื่อใช้ประโยชน์ 
	</div>
	<div class="nowrap">
		<div class="absolute" style="margin-left:0;"><div class="data nowrap" >'.($data["วัตถุประสงค์"]??null).'</div></div>
		<div class="absolute" style="margin-left:580px;"><div class="data nowrap center" style="width: 90px;">'.($data["จำนวนงวด"]??null).'</div></div>

		......................................................................................................................และขอส่งต้นเงินกู้คืน.......................งวด 
	</div>
	<div class="nowrap">
		<div class="absolute" style="margin-left:40px;"><div class="data nowrap center" style="width: 150px;">'.($data["จำนวนงวด"]??null).'</div></div>
		งวดล่ะ.......................................บาทพร้อมด้วยดอกเบี้ย
	</div>
	<div class="sub-list nowrap" style="letter-spacing:0.19px;">
		ข้าพเจ้ายินยอมให้ผู้บังคับบัญชาหรือผู้ที่ได้รับมอบหมายหักเงินได้รายเดือนของข้าพเจ้าส่งคืนเงินกู้
	</div>
	<div>
		ตาม ข้อบังคับ ระเบียบ และประกาศของสหกรณ์ทุกประการ
	</div>
	<div class="right">
			  ..................................................ผู้กู้ โทรศัพท์...................................
	</div>
	<div style="margin-left:287px;">
		<div class="absolute" style="margin-left:0;"><div class="data nowrap center" style="width: 200px;">'.($data["ชื่อ"]??null).'</div></div>
		 (..................................................)
	</div>
	<div class="flex" style="height: 60px;">
		<div style="margin-left:70px">
			<div>.....................................................พยาน</div>
			<div>(.....................................................)</div>
		</div>
		<div style="margin-left:390px">
			<div>.....................................................พยาน</div>
			<div>(.....................................................)</div>
		</div>
	</div>
	<div style ="border:1px solid;  padding:3px 10px;" >
		<div class="center bold">(สำหรับเจ้าหน้าที่สหกรณ์)</div>
		<div class="nowrap" style="margin-left:40px;">
			<div class="absolute" style="margin-left:68;"><div class="data nowrap center" style="width: 120px;">'.($data["เงินเดือน"]??null).'</div></div>
			<div class="absolute" style="margin-left:188;"><div class="data nowrap center" style="width: 120px;">'.($data["หุ้น"]??null).'</div></div>
			<div class="absolute" style="margin-left:365;"><div class="data nowrap center" style="width: 120px;">'.($data["วงเงินกู้ฉุกเฉิน"]??null).'</div></div>
			
			เงินได้รายเดือน..............................บาท หุ้น..............................บาท วงเงินกู้ฉุกเฉิน..............................บาท  
		</div>
		<div>
			<div class="absolute" style="margin-left:65;"><div class="data nowrap center" style="width: 120px;">'.($data["หนี้ฉุกเฉินค้าง"]??null).'</div></div>
			<div class="absolute" style="margin-left:275;"><div class="data nowrap center" style="width: 120px;">'.($data["วงเงินกู้ฉุกเฉินคงเหลือ"]??null).'</div></div>

			
			มีหนี้ฉุกเฉินค้าง..............................บาท  วงเงินกู้ฉุกเฉินคงเหลือ..............................บาท
		</div>
		<div class="flex" style="height:215px;">
			<div >
			  	<div class="border-top border-left border-right center bold" style="width:250px; margin-top:10px; background-color:#eeeeeeee; ">รายละเอียดกู้</div>
				  <div class="border" style="width:245px; padding-left:5px; background-color:#eeeeeeee;">
				  	<div class="flex" style="height:27px;">
					   <div style="font-size:14pt">ยอดกู้</div>
					   <div style="margin-left:110px; font-size:14pt;">
					   		<div class="absolute" style="margin-left:0;"><div class="data nowrap right" style="width: 85px;">'.($data["ยอดกู้"]??null).'</div></div>
					   	    ..........................บาท
					    </div>
					</div>
					<div class="flex" style="height:27px;">
					   <div style="font-size:14pt"><u><b>หัก</b></u> หนี้เก่า</div>
					   <div style="margin-left:110px; font-size:14pt;">
					    <div class="absolute" style="margin-left:0;"><div class="data nowrap right" style="width: 85px;">'.($data["หักหนี้เก่า"]??null).'</div></div>
					   ..........................บาท
					   </div>
					</div>
					<div class="flex" style="height:27px;">
					   <div style="font-size:14pt">คงเหลือ</div>
					   <div style="margin-left:110px; font-size:14pt;">
					   	 <div class="absolute" style="margin-left:0;"><div class="data nowrap right" style="width: 85px;">'.($data["คงเหลือ"]??null).'</div></div>
					   	..........................บาท
					   </div>
					</div>
				  </div>
				  <div class="border-left border-right" style="width:245px; padding-left:5px; border-bottom:solid 1px solid; font-size:14pt; background-color:#eeeeeeee;" >
					<div class="flex" style="height:27px;">
						<div style="font-size:14pt">ต้องคืนเงินก่อนยื่นกู้</div>
						<div style="margin-left:110px; font-size:14pt;">
							<div class="absolute" style="margin-left:0;"><div class="data nowrap right" style="width: 85px;">'.($data["คืนเงินก่อนยื่นกู้"]??null).'</div></div>
							..........................บาท
						</div>
					</div>
				  </div>
			</div>
		   <div style="margin-left:330px; " >
			  <div>....................................................เจ้าหน้าที่</div>
			  <div class="bold center">ความเห็นของเหรัญญิก</div>
			  <div> 
			  	&nbsp; <div style="position:absolute; top:-2px;"> <input type="checkbox" style="margin-top:6px;" > อนุมัติให้กู้ได้............................บาท</div>
			  </div>
			  <div class="right">(......................................................................)</div>
			  <div> 
			  	&nbsp; <div style="position:absolute; top:-2px;"> <input type="checkbox" style="margin-top:6px;" > ไม่อนุมัติ..................................บาท</div>
			  </div>
			  <div class="right">(......................................................................)</div>
			  <div style="margin-left:70px;">
			  	  .............................................เหรัญญิก
			  </div>
			  <div style="margin-left:70px;">
			  	วันที่........................................
			  </div>
		  	</div>
		</div>
	</div>
	<div> 
		<div class="list" style="margin-top:5px;"> 
			&nbsp; <div style="position:absolute; top:-2px;"> <input type="checkbox" style="margin-top:6px;" > <b>กรณีผู้กู้รับรับเงินกู้ด้วยตนเอง</b></div>
		</div>
		<div class="sub-list nowrap">
			ข้าพเจ้า..........................................................................ได้รับเงินกู้ จำนวน.................................บาท 
		</div>
		<div class="nowrap">
			(....................................................................) ตามหนังสือกู้นี้ไปเป็นการถูกต้องแล้ว เมื่อวันที่....................................
		</div>
	</div>
	<div style="margin-top:20px; margin-left:350px;">
		ลงชื่อ.............................................ผู้กู้/ผู้รับเงิน
	</div>
	<div style="margin-left:380px;">(.............................................)</div>
	<div style="font-size:11pt; margin-left:350px;">(ต้องลงลายมือชื่อในการรับเงินต่อหน้าเจ้าหน้าที่สหกรณ์ฯ)</div>
	
	';
$html .= '</div>';

//หน้า 2
$html .= '<div class="wrapper-page">';
$html .= '
	<div class="center">-2-</div>	
	<div>
		<div  style="margin-top:20px; margin-left:-10px;"> 
		&nbsp; <div style="position:absolute; top:-2px;"> <input type="checkbox" style="margin-top:6px;" > <b>กรณีผู้กู้รับรับเงินกู้ด้วยตนเอง</b>(เฉพาะธนาคารกรุงไทยเท่านั้น)</div>
		</div>
	</div>
	<div class="sub-list nowrap" style="margin-top:10px; letter-spacing:0.5px;">
		ข้าพเจ้าผู้กู้ไม่สามารถรับเงินกู้ด้วยตนเองได้ จึงขอให้สหกรณ์โอนเงินเข้าบัญชีธนาคารกรุงไทย       
	</div>
	<div>
		<div style="position:absolute">
			<div style="margin-top:8px; margin-left:70px;">
				<div class="flex" style="height:25px;">
					<div class="border" style=" width:20px; height:20px;"></div>	  
					<div class="border" style=" width:20px; height:20px; margin-left:25px;"></div>	
					<div class="border" style=" width:20px; height:20px; margin-left:50px;"></div>	 
					<div class="border" style=" width:20px; height:20px; margin-left:80px;"></div>	  
					<div class="border" style=" width:20px; height:20px; margin-left:110px;"></div>	 
					<div class="border" style=" width:20px; height:20px; margin-left:135px;"></div>	 
					<div class="border" style=" width:20px; height:20px; margin-left:160px;"></div>	 
					<div class="border" style=" width:20px; height:20px; margin-left:185px;"></div>	 
					<div class="border" style=" width:20px; height:20px; margin-left:210px;"></div>	 
					<div class="border" style=" width:20px; height:20px; margin-left:240px;"></div>	 
				</div>
			</div>
		</div>
		เลขที่บัญชี 
		<div style="margin-left:75px;  position:absolute;">-</div>
		<div style="margin-left:100px;  position:absolute;">-</div>
		<div style="margin-left:225px;  position:absolute;">-</div>		
		<div style="margin-left:265px;  position:absolute;">ชื่อบัญชี.............................................................................</div>		
	</div>
	<div class="nowrap">
		สาขา................................................................ประเภทออมทรัพย์ โดยให้ถือเอาวันที่โอนเงินเข้าบัญชีธนาคารเป็นวันที่
	</div>
	<div>
		ข้าพเจ้ายินยอมผูกพันตนเป็นลูกหนี้ต่อสหกรณ์ตั้งแต่วันที่สหกรณ์ได้จ่ายเงินกู้ให้แก่ผู้รับมอบอำนาจเป็นต้นไป		  
	</div>
	<div style="margin-top:30px; margin-left:350px;">
		ลงชื่อ.............................................ผู้กู้
	</div>
	<div style="margin-left:380px;">(.............................................)</div>
	<div>
		<div  style="margin-top:30px; margin-left:-10px;"> 
		&nbsp; <div style="position:absolute; top:-2px;"> <input type="checkbox" style="margin-top:6px;" > <b>กรณีผู้กู้มอบอำนาจให้ผู้อื่นรับเงินกู้แทน</div>
		</div>
	</div>
	<div class="sub-list nowrap">
		ข้าพเจ้าผู้กู้ไม่สามารถมารับเงินกู้นี้ด้วยตนเองได้ จึงขอมอบอำนาจให้ 
	</div>
	<div class="nowrap">
		ข้าพเจ้า..................................................................................................ได้รับเงิน จำนวน.....................................บาท
	</div>
	<div class="nowrap">สังกัด..................................................................................เป็นผู้รับเงินกู้ตามหนังสือกู้นี้จากสหกรณ์แทนข้าพเจ้าและ</div>
	<div>ข้าพเจ้ายินยอมผูกพันตนเป็นลูกหนี้ต่อสหกรณ์ตั้งแต่วันที่สหกรณ์ได้จ่ายเงินกู้ให้แก่ผู้รับมอบอำนาจเป็นต้นไป</div>
	<div style="padding-top:50px;">
		<div class="border absolute" style="width:300px; height:140px; padding:5px;">
			  <div class="center"><b>คำเตือน</b></div>
			  <div>กรณีมอบอำนาจขอให้ส่งสำเนาบัตรข้าราชการ/</div>
			  <div>บัตรประจำตัวประชาชนพร้อมรับรองสำเนาของ </div>
			  <div>ผู้มอบ และผู้รับมอบอำนาจมาแสดงต่อเจ้าหน้าที่</div>
			  <div>สหกรณ์</div>
		</div>
	</div>
	<div style="margin-left:380px; padding-top:20px;">.............................................ผู้มอบอำนาจ</div>
	<div style="margin-left:380px; margin-top:15px;">.............................................ผู้รับมอบอำนาจ</div>
	<div style="margin-left:380px; margin-top:15px;">.............................................พยาน</div>
	<div style="margin-left:380px; margin-top:15px;">(.............................................)</div>
	<div style="margin-left:380px; margin-top:15px;">.............................................พยาน</div>
	<div style="margin-left:380px; margin-top:15px;">(.............................................)</div>
	<div class="nowrap" style="margin-top:10px;">ข้าพเจ้า............................................................................................ได้รับเงิน จำนวน..........................................บาท</div>
	<div class="nowrap">
		(...................................................................) ตามหนังสือกู้นี้ไปเป็นการถูกต้องแล้ว เมื่อวันที่......................................         	                 
	</div>
	<div style="margin-top:30px; margin-left:350px;">
		ลงชื่อ.............................................ผู้รับเงิน
	</div>
	<div style="margin-left:380px;">(.............................................)</div>
	<div style="font-size:11pt; margin-left:350px;">(ต้องลงลายมือชื่อในการรับเงินต่อหน้าเจ้าหน้าที่สหกรณ์ฯ)</div>
	<div style="margin-top:30px;">
		...................................................................................................................................................................................
	</div>
	<div style="margin-top:30px; margin-left:350px;">
		ลงชื่อ.............................................เจ้าหน้าที่ผู้จ่ายเงิน
	</div>
	<div style="margin-left:380px">
	     .............../............../................
	</div>
';


$html .= '</div>';

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
