<?php
use Dompdf\Dompdf;

$dompdf = new DOMPDF();
function GeneratePDFContract($data,$lib) {
	$minispace = '&nbsp;&nbsp;';
	$space = '&nbsp;&nbsp;&nbsp;&nbsp;';
	$html = '<style>
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
			body {
			  padding: 0 30px;
			}
			.sub-table div{
			  padding : 5px;
			}
			.text-input {
				font-weight:bold;
				font-size: 20px;
				border-bottom: 1px dotted black;
			}
			.text-header {
				font-size: 18px;
				border-bottom: 1px dotted black;
			}
			</style>';
	$html .= '<div style="display: flex;text-align: center;">
			<div>
				  <img src="../../resource/logo/logo.jpg" style="width:100px"/>
				</div>
				<div style="text-align:center;width:100%;margin-left: 0px; ">
					<p style="margin-top: 110px;font-size: 20px;font-weight: bold;;">
					   ใบคําขอกู้เพื่อเหตุฉุกเฉินออนไลน์ 
					</p>
			   </div>
			   <div style="position: absolute; right: 15px; top: 0px;">
				   <table style="width:100%">
				   <tr>
				   <td style="width: 100%"></td>
						<td>
						   <div style=" border: solid 1px #000000;padding: 5px;">
									<div style="font-size: 18px;white-space: nowrap;">
									  เลขที่สัญญาเงินกู้ '.$data["loan_prefix"].'.................................
									 </div>
									<div style="font-size: 18px;white-space: nowrap; ">
									วันที่<span class="text-header">'.$minispace.date('d').$minispace.'/'.$minispace.(explode(' ',$lib->convertdate(date("Y-m-d"),"d m Y")))[1].$minispace.'/'.$minispace.(date('Y') + 543).$minispace.'</span>
									</div>
									<div style="font-size: 18px;white-space: nowrap;">
									  เลขที่ใบคำขอกู้ออนไลน์<span class="text-header">'.$minispace.$data["requestdoc_no"].$minispace.'</span>
									 </div>
						   </div>
						   </td>
					   </tr>
					 </table>
			   </div>
				<div style="position: absolute; right: 15px; top: 140px; width:100%">
							<p style="font-size: 20px; text-align:right; ">
							   เขียนที่ Surin Saving (Mobile Application)
							</p>
				</div>
			   <div style="position: absolute; right: 15px; top: 176px; width:100%;">
				<p style="font-size: 20px;  text-align:right; ">
				  วันที่............เดือน..........................พ.ศ..............
				 </p>
			  </div>
			  <div style="position: absolute; right: 190px; top: 173px; width:40px; ">
				<p style="font-size: 20px; ">
				  '.date('d').'
				</p>
			  </div>
			  <div style="position: absolute; right: 80px; top: 173px; width:85px;">
				<p style="font-size: 20px; ">
				'.(explode(' ',$lib->convertdate(date("Y-m-d"),"d M Y")))[1].'
				</p>
			  </div>
			  <div style="position: absolute;right: 15px; top: 173px; width:45px;  ">
				<p style="font-size: 20px; ">
				'.(date('Y') + 543).'
				</p>
			  </div>
			  </div>
			  <div style="position: absolute; left: 20px; top: 210px; width:100%">
			<p style="font-size: 20px; text-align:left">
			เรียน 
			</p>
		  </div>
		  <div style="position: absolute; left: 61px; top: 210px; width:100%">
			<p style="font-size: 20px; text-align:left">
			   คณะกรรมการดําเนินการสหกรณ์ออมทรัพย์ครูสุรินทร์ จํากัด
			</p>
		  </div>
		    <div style="position: absolute; left: 20px; top: 258px; right:0px; width:660px; font-size: 20px; ">
			  <p style="text-indent:50px;  text-align:left;">
			  ข้าพเจ้า.......................................................................... สมาชิกเลขทะเบียนที่...................................... รับราชการหรือ
			  ทํางานประจําในตําแหน่ง........................................................โรงเรียนหรือที่ทําการ.......................................................................
			  อำเภอ.........................................จังหวัดสุรินทร์ ได้รับเงินได้รายเดือน ๆ ละ...................................................บาท  มีหุ้นอยู่ใน สหกรณ์ออมทรัพย์ครูสุรินทร์ จํากัด
			  ณ วันที่ 31 ธันวาคม พ.ศ.................เป็นเงิน.........................................บาท  ขอเสนอ คําขอกู้เงินเพื่อเหตุฉุกเฉินดังต่อไปนี้
			  </p>
			  <p style="text-indent:50px; margin:0px; margin-top:-20px;  text-align:left;">
				ข้อ 1. ข้าพเจ้าขอกู้เงินของสหกรณ์จํานวน........................................บาท (....................................................................)
				โดยจะนําไปใช้เพื่อการดังต่อไปนี้ (ชี้แจงเหตุฉุกเฉินที่จําเป็นต้องขอกู้เงิน)......................................................................................<br>
				โดยโอนเงินเข้าบัญชีเงินฝากธนาคารกรุงไทย เลขที่บัญชี ................................... ชื่อบัญชี .............................................................
			  </p>
			  <p style="text-indent:50px; margin:0px; text-align:left;">
				ข้อ 2. ถ้าข้าพเจ้าได้รับเงินกู้ ข้าพเจ้าขอส่งชําระเงินดอกเบี้ยเป็นรายเดือน และส่งคืนเงินกู้เพื่อเหตุฉุกเฉินเต็มจํานวน พร้อมดอกเบี้ยเดือนสุดท้ายให้เสร็จสิ้นภายใน 12 เดือน
			  </p>
			  <p style="text-indent:55px; margin:0px; text-align:left;">
			  ข้อ 3. เมื่อข้าพเจ้าได้รับเงินแล้ว ข้าพเจ้ายอมรับผูกพันตามข้อบังคับของสหกรณ์ ดังนี้
			  </p>
			  <p style="text-indent:93px; margin:0px;">
				 3.1 ยินยอมให้ผู้บังคับบัญชา หรือเจ้าหน้าที่ผู้จ่ายเงินได้รายเดือนของข้าพเจ้า หักเงินได้รายเดือนของ ข้าพเจ้าตามจํานวนงวดชําระหนี้ ข้อ 2 เพื่อส่งต่อสหกรณ์
			  </p>
			  <div style="width:670px ">
				  <p style="text-indent:93px; margin:0px;">
				  3.2 ยอมให้ถือว่าในกรณีใด ๆ ดังกล่าวในข้อบังคับข้อ 17 ให้เงินกู้ที่ขอกู้ไปจากสหกรณ์เป็นอันถึงกําหนดส่งคืน โดยสิ้นเชิงพร้อมด้วยดอกเบี้ยในทันที โดยมิพักคํานึงถึงกําหนดเวลาที่ตกลงไว้
				</p>
			  </div>
			 
			  <p style="text-indent:97px; margin:0px;">
				3.3. ถ้าประสงค์จะลาออกหรือย้ายจากราชการ หรืองานประจําตามข้อบังคับข้อ 41 และข้อ 43 จะแจ้งเป็น 
			  </p>
			  <p style=" margin:0px; text-align:justify">
				 หนังสือให้สหกรณ์ทราบ และจัดการชําระหนี้ซึ่งมีอยู่ตามสหกรณ์ให้เสร็จสิ้นเสียก่อน ถ้าข้าพเจ้าไม่จัดการหนี้ให้เสร็จสิ้น ตามที่กล่าวข้างต้น เมื่อข้าพเจ้าได้ลงชื่อรับเงินเดือน ค่าจ้าง เงินสะสม เงินบําเหน็จบํานาญ เงินทุนเลี้ยงชีพ หรือเงินอื่นใด ในหลักฐานที่ทางราชการหน่วยงานเจ้าของสังกัดจะจ่ายเงินให้แก่ข้าพเจ้า ข้าพเจ้ายินยอมให้เจ้าหน้าที่ผู้จ่ายเงินดังกล่าวหักเงิน ชําระหนี้ พร้อมด้วยดอกเบี้ยส่งชําระหนี้ต่อสหกรณ์ให้เสร็จสิ้นเสียก่อนได้
			  </p>
			  <p style="text-indent:50px; margin:0px;  text-align:left;">
				ข้อ 4. หากมีการบังคับใดๆ ก็ตาม ข้าพเจ้ายินยอมให้สหกรณ์โอนหุ้นของข้าพเจ้าชําระหนี้สหกรณ์ก่อน และหากมีการ ฟ้องร้องคดีต่อศาลยุติธรรม ข้าพเจ้ายินยอมให้มีการฟ้องร้อง ณ ศาลจังหวัดสุรินทร์
			  </p>
			  <p style="text-indent:50px; margin:0px;  text-align:left;">
				 หนังสือนี้ ข้าพเจ้าอ่านและเข้าใจทั้งหมดแล้ว
			  </p>
			  <p style="text-indent:50px; margin-top:15px;  text-align:center;">
				 ลงชื่อ...........................................................ผู้กู้ / ผู้รับเงิน
			  </p>
			  <p style="text-indent:232px;  margin-top:-20px; text-align:left;">
			  (...........................................................)
		   </p>
		  </div>
		  <div style="position: absolute; left: 103px; top: 271px; width:243px; text-align:center;font-weight:bold; ">
			<div style="font-size: 20px; ">
			  '.$data["full_name"].'
			</div>
		  </div>

		  <div style="position: absolute; right: 105px; top: 271px; width:127px; text-align:center;font-weight:bold; ">
			<div style="font-size: 20px; ">
			  '.$data["member_no"].'
			</div>
		  </div>

		  <div style="position: absolute; left: 137px; top: 301px; width:140px; text-align:center;font-weight:bold;  ">
			<div style="font-size: 20px; ">
			  '.$data["position"].'
			</div>  
		  </div>

		  
		  <div style="position: absolute; left: 440px; top: 301px; width:210px; text-align:center;font-weight:bold; ">
			<div style="font-size: 20px;white-space: nowrap ">
			  '.$data["pos_group_code"].' '.$data["pos_group"].'
			</div>  
		  </div>

		  <div style="position: absolute; left: 30px; top: 331px; width:120px; text-align:center;font-weight:bold;">
			<div style="font-size: 20px; ">
			  '.$data["district_desc"].'
			</div>  
		  </div>

		  <div style="position: absolute; right: 135px; top: 331px; width:165px; text-align:center;font-weight:bold; ">
			<div style="font-size: 20px; ">
			 '.$data["salary_amount"].'
			</div>  
		  </div>

		  <div style="position: absolute; left: 335px; top: 361px; width:56px; text-align:center;font-weight:bold;  ">
			<div style="font-size: 20px; ">
			  '.(date('Y') + 542).'
			</div>  
		  </div>

		  <div style="position: absolute; right: 154px; top: 361px; width:136px; text-align:center;font-weight:bold;">
			<div style="font-size: 20px; ">
			  '.$data["share_bf"].'
			</div>  
		  </div>

		  <div style="position: absolute; left: 289px; top: 416px; width:130px; text-align:center;font-weight:bold; ">
			<div style="font-size: 20px; ">
			  '.number_format($data["request_amt"],2).'
			</div>  
		  </div>

		  <div style="position: absolute; right: 30px; top: 416px; width:220px; text-align:center;font-weight:bold;">
			<div style="font-size: 20px; ">
			'.$lib->baht_text($data["request_amt"]).'
			</div>  
		  </div>

		  <div style="position: absolute; right: 30px; top: 446px; width:280px; text-align:center;font-weight:bold;">
			<div style="font-size: 20px; ">
			เพื่อเหตุฉุกเฉิน
			</div>  
		  </div>
		<div style="position: absolute; left: 220px; top: 476px; width:280px; text-align:center;font-weight:bold;">
			<div style="font-size: 20px; ">
			'.$data["recv_account"].'
			</div>  
		  </div>
		  <div style="position: absolute; left: 420px; top: 476px; width:280px; text-align:center;font-weight:bold;">
			<div style="font-size: 20px; ">
			'.$data["name"].'
			</div>  
		  </div>
		  <div style="position: absolute; left: 250px; bottom: 33px; width:195px; text-align:center;font-weight:bold; ">
			<div style="font-size: 20px; ">
			 '.$data["name"].'
			</div>  
		  </div>
		  
		  <div style="position: absolute; left: 250px; bottom: 7px; width:195px; text-align:center;font-weight:bold; ">
			<div style="font-size: 20px; ">
			 '.$data["full_name"].'
			</div>  
		  </div>
		  </tbody>
		  </table>
		  </div>

	';
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