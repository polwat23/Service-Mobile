<?php
use Dompdf\Dompdf;

$dompdf = new DOMPDF();
function GeneratePDFContract($data,$lib) {
	//demo Data
	$header["หนี้คงเหลือ"] = number_format($data["principal_balance"],2);
	$header["ชำระแล้ว"] =  $data["last_periodpay"];
	$header["จาก"] = $data["period_payamt"];
	$header["รับเลขที่"] = '';
	$header["เลขที่"] = $data["requestdoc_no"];
	$header["วันที่"] = $lib->convertdate(date("Y-m-d"),"d M Y");
	$header["ชื่อ"] = $data["full_name"];
	$header["เลขที่สมาชิก"] = $data["member_no"];
	$header["ตามหนังสือกู้เงินที่"] = '4585244';
	$header["จำนวนเงินขอกู้"] = number_format($data["request_amt"],2);
	$header["จำนวนเงินขอกู้คำอ่าน"] = $lib->baht_text($data["request_amt"]);
	$header["อัตราดอกเบี้ย"] = number_format($data["interest_rate"],2);
	$header["อนุมัติ"] = '';



	$arrData[0]["เงินเดือน"] = $data["salary_amount"];
	$arrData[0]["จำนวนเงินที่ขอกู้"] = number_format($data["request_amt"],2);
	$arrData[0]["หักหนี้เดิม"] = number_format($data["deduct_debt09"],2);
	$arrData[0]["คงเหลือ"] = number_format($data["request_amt"] - $data["deduct_debt09"],2);

	$arrData[1]["เงินเดือน"] ="";
	$arrData[1]["จำนวนเงินที่ขอกู้"] ="";
	$arrData[1]["หักหนี้เดิม"] ="";
	$arrData[1]["คงเหลือ"] ="";

	$groupData["data_loan"]= $arrData;


	// อนุมัติ/ไม่อนุมัติ
	$ag_Data  = $header["อนุมัติ"]??"";
	if($ag_header === true){
		$agree ="checked";
		$not_agree ="";
	}else if($ag_header === false){
		$agree ="";
		$not_agree ="checked";
	}else{
		$agree ="";
		$not_agree ="";
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
				  font-size: 13pt;
				  line-height: 22px;
				}
				div{
					line-height: 22px;
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
					padding-left:50px;
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
					font-size:13pt;
					margin-top:-3px;
				}
				.border{
					border: 1px solid;
				}
				</style>';
	//ขนาด
	$html .= '<div style="margin:-30px 0px -20px 10px;"; >';

	//ส่วนหัว
	$html .= '
		<div style=" text-align: center; margin-top:40px;"><img src="../../resource/logo/logo.jpg" alt="" width="90" height="0"></div>
		<div style="border:0.5px solid #000000; width:205px; position:absolute; left:0px; top:0px; padding:5px; 5px;  ">
			<div style=" line-height: 30px; font-size:13pt">
				<div class="absolute" style="margin-left:55px; width:99px;"><div style="margin-top:5px;" class="header center nowrap">'.($header["หนี้คงเหลือ"]??null).'</div></div>
				หนี้คงเหลือ................................บาท
			</div>
			<div class="nowrap" style=" line-height: 30px;">
				<div class="absolute" style="margin-left:40px; width:40px;"><div style="margin-top:5px;" class="data center nowrap">'.($header["ชำระแล้ว"]??null).'</div></div>
				<div class="absolute" style="margin-left:119px; width:40px;"><div style="margin-top:5px;" class="data center nowrap">'.($header["จาก"]??null).'</div></div>
				ชำระแล้ว...........งวด จาก...........งวด
			</div>
			<div style="line-height: 30px;">
				<div class="absolute" style="margin-left:0px;"><div class="data nowrap center" style="margin-top:5px;">'.($header["รับเลขที่"]??null).'</div></div>
				รับเลขที่............................................       
			</div>
		</div>
		<div style="border:0.5px solid #000000; width:190px; position:absolute; right:0px; top:0px; padding:5px; 5px;   ">
			<div class="center" style="font-size:10pt; line-height: 18px;">
				<u>เอกสารประกอบการยื่นกู้</u>
			</div>
			<div style="font-size:10pt; line-height: 18px;" class="nowrap">
				1. สำเนาบัตรประจำตัวประชาชน หรือ สำเนาบัตร
			</div>
			<div style="font-size:10pt; line-height: 18px; margin-left:10px;" class="nowrap">
				ข้าราชการ (ที่ไม่หมดอายุ)
			</div>
			<div style="font-size:10pt; line-height: 18px;" class="nowrap">
				2. สำเนาบัญชีธนาคารกรุงไทย(ถ้ามี)
			</div>
			<div style="font-size:10pt; line-height: 18px;" class="nowrap">
				3. สลิปเงินเดือนปัจจุบันล่าสุด
			</div>
		</div>
		<div style="font-size: 22px;font-weight: bold; text-align:center; margin-center:30px; height: 45px; margin-top:10px;">คำขอกู้และสัญญาเงินกู้ฉุกเฉินดิจิทัล</div>
		<div>
		
		</div>
		<div class="flex" >
			<div>
				<div class="absolute" style="margin-left:30px;"><div class="data nowrap">'.($header["เลขที่"]??null).'</div></div>
				เลขที่......................................
			</div>
			<div style="margin-left:230;">
				<div class="absolute" style="margin-left:30px;"><div class="data nowrap" >'.($header["วันที่"]??null).'</div></div>
				วันที่.....................................................
			</div>
		</div>
		<div class="list  nowrap">
			<div class="absolute" style="margin-left:65px;  width:310px;"><div class="data nowrap center">'.($header["ชื่อ"]??null).'</div></div>	
			<div class="absolute" style="margin-left:435px;  width:125px;"><div class="data nowrap center">'.($header["เลขที่สมาชิก"]??null).'</div></div>	
			ข้อ 1 ขาพเจ้า...................................................................................................เลขที่สมาชิก.........................................ได้รับอนุมัติเงินกู้
		</div>
		<div>
			<div class="absolute" style="margin-left:175px;"><div class="data nowrap">'.($header["ตามหนังสือกู้เงินที่"]??null).'</div></div>	
			ฉุกเฉินดิจิทัล ตามหนังสือกู้เงินที่ DI..........................................
		</div>
		<div class="list  nowrap">
			<div class="absolute" style="margin-left:215px;  width:120px;"><div class="data nowrap center">'.($header["จำนวนเงินขอกู้"]??null).'</div></div>	
			<div class="absolute" style="margin-left:359px;  width:280px;"><div class="data nowrap center">'.($header["จำนวนเงินขอกู้คำอ่าน"]??null).'</div></div>	
			ข้อ 2 ข้าพเจ้ามีความประสงค์ขอกู้เงินจํานวน......................................บาท (.........................................................................................)
		</div>
		<div class="list  nowrap">ข้อ 3 ข้าพเจ้ายินยอมรับเงินกู้ตามจํานวนเงินที่สหกรณ์อนุมัติ และข้าพเจ้ายินยอมให้สหกรณ์หักหนี้เงินกู้ฉุกเฉินทุกประเภท และดอกเบี้ย</div>
		<div class="nowrap" style="letter-spacing:0.09px;">ค้างชําระ ที่ข้าพเจ้าต้องชําระต่อสหกรณ์ และอื่น ๆ (ถ้ามี) ก่อน และให้สหกรณ์จ่ายเงินกู้ส่วนที่เหลือ โดยนําฝากเข้าบัญชีเงินฝากออมทรัพย์ของ</div>
		<div class="nowrap" style="letter-spacing:0.21px;">ข้าพเจ้าที่มีอยู่ต่อสหกรณ์ หรือตามที่ระบุไว้ในหลักเกณฑ์เงินกู้สามัญ ประเภทนั้น ๆ ทั้งนี้ ให้ถือว่าข้าพเจ้าได้รับจํานวนเงินกู้ดังกล่าวในข้อ 2</div>
		<div>ไปครบถ้วนแล้ว</div>
		<div>
			<div class="absolute" style="margin-left:445px;  width:50px;"><div class="data nowrap center">'.($header["อัตราดอกเบี้ย"]??null).'</div></div>	
			<div class="list nowrap" style="letter-spacing:-0.01px">ข้อ 4 ข้าพเจ้าตกลงยินยอมให้สหกรณ์คิดดอกเบี้ยเงินกู้ตามสัญญานี้ในอัตราร้อยละ...............ต่อปี ในกรณี ที่สหกรณ์เปลี่ยนแปลงอัตรา</div>
		</div>
		<div>ดอกเบี้ย ข้าพเจ้ายินยอมให้ปรับเพิ่มหรือลดได้โดยไม่ต้องแจ้งให้ข้าพเจ้าทราบ</div>
		<div class="list  nowrap">ข้อ 5 ข้าพเจ้าตกลงชําระเงินต้นและดอกเบี้ยแก่สหกรณ์ทุกเดือน จํานวน 48 งวดเดือน โดยเริ่มชําระงวดแรก ภายใน 60 วันนับจากวันที่</div>
		<div>เริ่มสัญญาเงินกู้โดยวิธีผ่อนชําระเงินต้นพร้อมดอกเบี้ยเป็นงวดเท่ากันทุก ๆ เดือนยกเว้นงวดสุดท้าย</div>
		<div class="list">ข้าพเจ้าได้อ่านและเข้าใจข้อความในสัญญานี้แล้ว จึงได้ลงลายมือชื่อไว้เป็นสําคัญต่อหน้าพยาน</div>
		<div style="margin-top:30px"></div>
		<div class="flex" style="height:60px">
			<div>
				<div>ลงชื่อ..............................................................ผู้กู้</div>
				<div style="margin-left:23px;">
					<div class="absolute" style="margin-left:5px;  width:190px;"><div class="data nowrap center">'.($header["ชื่อ"]??null).'</div></div>
					(..............................................................)
				</div>
			</div>
			<div class="right">
				<div>ลงชื่อ..............................................................ผยาน</div>
				<div style="margin-right:23px;">(..............................................................)</div>
			</div>
		</div>
		<div style="font-weight:bold; color:red;">
			<div><u>คำเตือน</u> 1.ผู้ลงลายมือชื่อต้องเป็นบุคคลตามซึ่งที่ระบุไว้จริง มิฉะนั้นจะมีความผิดทางอาญา</div>
			<div class="nowrap" style="padding-left:47px">2.สมาชิกที่ผิดนัดการส่งเงินงวดชําระหนี้ไม่ว่าเงินต้นหรือดอกเบี้ยในปีใด มิให้ได้รับเงินเฉลี่ยคืนสําหรับปีนั้น ตามข้อบังคับ</div>
			<div style="padding-left:57px">สอ.รพช. จํากัด ข้อ 23</div>
		</div>
		<div class="center bold">สำหรับเจ้าหน้าที่</div>
		<div >
			<div class="absolute" style="margin-left:5px; ">
				<table style=" border-collapse: collapse; width:100%; margin-top:30px;">
					<tbody>';
				$dataTabel = $groupData["data_loan"]??[];
				foreach($dataTabel as $arrData){
					$html .='
					<tr>
						<td class="right" style="width:25%">'.($arrData["เงินเดือน"]??null).'</td>
						<td class="right" style="width:25%">'.($arrData["จำนวนเงินที่ขอกู้"]??null).'</td>
						<td class="right" style="width:25%">'.($arrData["หักหนี้เดิม"]??null).'</td>
						<td class="right" style="width:25%">'.($arrData["คงเหลือ"]??null).'</td>
					</tr>';
				}
		

	$html .='
					</tbody>
				</table>
			</div>
			<table style=" border-collapse: collapse; width:100%">
				<thead>
					<tr>
						<th style="width:25%">เงินเดือน</th>
						<th style="width:25%">จำนวนเงินที่ขอกู้</th>
						<th style="width:25%">หักหนี้เดิม</th>
						<th style="width:25%">คงเหลือ</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td class="right border" style="height:85px;"></td>
						<td class="right border"></td>
						<td class="right border"></td>
						<td class="right border"></td>
					</tr>
				</tbody>
			</table>
		</div>
		<div style="padding-left:20px; margin-top:10px;">ได้ตรวจสอบสิทธิการกู้ หลักประกัน พร้อมเอกสารต่างๆ ตามระเบียบและประกาศของสหกรณ์ รพช.จํากัด ครบถ้วนถูกต้องแล้ว</div>
		<div style="margin-top:30px;">
			<div class="center">(ลงชื่อ)..............................................เจ้าหน้าที่ฝ่ายทะเบียนหุ้น</div>
			<div style="margin-left:230px;">(..............................................)</div>
			<div class="flex" style="height:30px;">
				<div style="margin-left:30px;">
					<div style="position:absolute; top:-2px;"> 
						<input type="checkbox" style="margin-top:7px;" '.($agree??null).'> <b>อนุมัติ</b>
					</div>
				</div>
				<div style="margin-left:110px;">
					<div style="position:absolute; top:-2px;"> 
						<input type="checkbox" style="margin-top:7px;" '.($not_agree??null).'> <b>ไม่อนุมัติ</b>
					</div>
				</div>
				<div style="margin-left:230px;">
					วันที่........../.............../............
				</div>
			</div>
			<div style="margin-left:200px;">(ลงชื่อ)..............................................ผู้จัดการ</div>
			<div style="margin-left:230px;">(..............................................)</div>
		</div>
		';

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
	$pathfile_show = '/resource/pdf/request_loan/'.$data["requestdoc_no"].'.pdf?v='.time();
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
