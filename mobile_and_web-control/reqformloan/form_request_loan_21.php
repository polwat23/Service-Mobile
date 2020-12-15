<?php
use Dompdf\Dompdf;

$dompdf = new DOMPDF();
function GeneratePDFContract($data,$lib) {
	/*$html = '<style>
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
			   <div style="position: absolute; right: 15px; top: 140px; width:100%">
					<p style="font-size: 20px; text-align:right; ">
					   เขียนที่ CRH Saving (Mobile Application)
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
			   คณะกรรมการดําเนินการสหกรณ์ออมทรัพย์สาธารณสุขเชียงราย จำกัด
			</p>
		  </div>
		  <div style="position: absolute; left: 20px; top: 258px; right:0px; width:660px; font-size: 20px; ">
			  <p style="text-indent:50px;  text-align:left;">
			  ข้าพเจ้า.......................................................................... สมาชิกเลขทะเบียนที่...................................... รับราชการหรือ
			  ทํางานประจําในตําแหน่ง........................................................ที่ทําการ.................................................................................
			  อำเภอ.........................................จังหวัดเชียงราย ได้รับเงินได้รายเดือน ๆ ละ...................................................บาท  มีหุ้นอยู่ใน สหกรณ์ออมทรัพย์สาธารณสุขเชียงราย จำกัด เป็นเงิน.........................................บาท  
			  ขอเสนอ คําขอกู้เงินฉุกเฉินดังต่อไปนี้
			  </p>
			  <p style="text-indent:50px; margin:0px; margin-top:-20px;  text-align:left;">
				ข้อ 1. ข้าพเจ้าขอกู้เงินของสหกรณ์จํานวน........................................บาท (....................................................................)
				โดยจะนําไปใช้เพื่อการดังต่อไปนี้ (ชี้แจงเหตุฉุกเฉินที่จําเป็นต้องขอกู้เงิน)......................................................................................
			  </p>
			  <p style="text-indent:50px; margin:0px; text-align:left;">
				ข้อ 2. ถ้าข้าพเจ้าได้รับเงินกู้ ข้าพเจ้าขอส่งชําระเงินดอกเบี้ยเป็นรายเดือน
			  </p>
			  <p style="text-indent:55px; margin:0px; text-align:left;">
			  ข้อ 3. เมื่อข้าพเจ้าได้รับเงินแล้ว ข้าพเจ้ายอมรับผูกพันตามข้อบังคับของสหกรณ์ ดังนี้
			  </p>
			  <p style="text-indent:93px; margin:0px;">
				 3.1 ยินยอมให้ผู้บังคับบัญชา หรือเจ้าหน้าที่ผู้จ่ายเงินได้รายเดือนของข้าพเจ้า หักเงินได้รายเดือนของ ข้าพเจ้าตามจํานวนงวดชําระหนี้ ข้อ 2 เพื่อส่งต่อสหกรณ์
			  </p>
			  <div style="width:670px ">
				  <p style="text-indent:93px; margin:0px;">
				  3.2 ยอมให้ถือว่าในกรณีใด ๆ ดังกล่าวในข้อบังคับข้อ ? ให้เงินกู้ที่ขอกู้ไปจากสหกรณ์เป็นอันถึงกําหนดส่งคืน โดยสิ้นเชิงพร้อมด้วยดอกเบี้ยในทันที โดยมิพักคํานึงถึงกําหนดเวลาที่ตกลงไว้
				</p>
			  </div>
			 
			  <p style="text-indent:97px; margin:0px;">
				3.3. ถ้าประสงค์จะลาออกหรือย้ายจากราชการ หรืองานประจําตามข้อบังคับข้อ ? และข้อ ? จะแจ้งเป็น 
			  </p>
			  <p style=" margin:0px; text-align:justify">
				 หนังสือให้สหกรณ์ทราบ และจัดการชําระหนี้ซึ่งมีอยู่ตามสหกรณ์ให้เสร็จสิ้นเสียก่อน ถ้าข้าพเจ้าไม่จัดการหนี้ให้เสร็จสิ้น ตามที่กล่าวข้างต้น เมื่อข้าพเจ้าได้ลงชื่อรับเงินเดือน ค่าจ้าง เงินสะสม เงินบําเหน็จบํานาญ เงินทุนเลี้ยงชีพ หรือเงินอื่นใด ในหลักฐานที่ทางราชการหน่วยงานเจ้าของสังกัดจะจ่ายเงินให้แก่ข้าพเจ้า ข้าพเจ้ายินยอมให้เจ้าหน้าที่ผู้จ่ายเงินดังกล่าวหักเงิน ชําระหนี้ พร้อมด้วยดอกเบี้ยส่งชําระหนี้ต่อสหกรณ์ให้เสร็จสิ้นเสียก่อนได้
			  </p>
			  <p style="text-indent:50px; margin:0px;  text-align:left;">
				ข้อ 4. หากมีการบังคับใดๆ ก็ตาม ข้าพเจ้ายินยอมให้สหกรณ์โอนหุ้นของข้าพเจ้าชําระหนี้สหกรณ์ก่อน และหากมีการ ฟ้องร้องคดีต่อศาลยุติธรรม ข้าพเจ้ายินยอมให้มีการฟ้องร้อง ณ ศาลจังหวัดเชียงราย
			  </p>
			  <p style="text-indent:50px; margin:0px;  text-align:left;">
				 หนังสือนี้ ข้าพเจ้าอ่านและเข้าใจทั้งหมดแล้ว
			  </p>
			  <p style="text-indent:50px; margin-top:40px;  text-align:center;">
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

		  
		  <div style="position: absolute; left: 380px; top: 301px; width:210px; text-align:center;font-weight:bold; ">
			<div style="font-size: 20px; ">
			  '.$data["pos_group"].'
			</div>  
		  </div>

		  <div style="position: absolute; left: 40px; top: 331px; width:120px; text-align:center;font-weight:bold;">
			<div style="font-size: 20px; ">
			  '.$data["district_desc"].'
			</div>  
		  </div>

		  <div style="position: absolute; right: 135px; top: 331px; width:165px; text-align:center;font-weight:bold; ">
			<div style="font-size: 20px; ">
			 '.$data["salary_amount"].'
			</div>  
		  </div>

		  <div style="position: absolute; right: 270px; top: 361px; width:136px; text-align:center;font-weight:bold;">
			<div style="font-size: 20px; ">
			  '.$data["share_bf"].'
			</div>  
		  </div>

		  <div style="position: absolute; left: 289px; top: 386px; width:130px; text-align:center;font-weight:bold; ">
			<div style="font-size: 20px; ">
			  '.number_format($data["request_amt"],2).'
			</div>  
		  </div>

		  <div style="position: absolute; right: 30px; top: 386px; width:220px; text-align:center;font-weight:bold;">
			<div style="font-size: 20px; ">
			'.$lib->baht_text($data["request_amt"]).'
			</div>  
		  </div>

		  <div style="position: absolute; right: 30px; top: 416px; width:280px; text-align:center;font-weight:bold;">
			<div style="font-size: 20px; ">
			เพื่อเหตุฉุกเฉิน
			</div>  
		  </div>

		  <div style="position: absolute; left: 250px; bottom: 98px; width:195px; text-align:center;font-weight:bold; ">
			<div style="font-size: 20px; ">
			 '.$data["name"].'
			</div>  
		  </div>
		  
		  <div style="position: absolute; left: 250px; bottom: 72px; width:195px; text-align:center;font-weight:bold; ">
			<div style="font-size: 20px; ">
			 '.$data["full_name"].'
			</div>  
		  </div>
		  </tbody>
		  </table>
		  </div>
	';*/
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
		  padding-left:45px;
		}
		.sub-indent{
		  text-indent:80px;
		}

		table{
		  border:1px solid;
		}
		th{
		text-align:center;
		border:1px solid ;
		}
		.text-right{
		text-align:right;
		}
		.text-center{
		text-align:center
		}
		td{
		border:1px solid ;
		paddig:1px;
		text-align:center;

		}
	</style>
	';
	//--------------------------page 1-------------------------------------------------------------------//
	$html .= '
		  <div style=" height:auto; margin-left:1.1cm; margin-top:-15px; margin-right:0.4cm; ">
	 ';
	//--------------------  head  ----------------------------------------------------------------------

	//head  (logo) 
	$html .= '
	  <div class="text-center">
		<img src="../../resource/logo/logo.jpg" style="width:90px;"/>
	  </div>
	  <div style="text-align:center; font-size:18pt;  font-weight:bold">
		คําขอและหนังสือกู้เพื่อเหตุฉุกเฉิน
	  </div>
	';

	$html .= '
		<div style="border: 1px solid black;width: 200px;position:absolute;height: 95px;margin-left: 440px;line-height:0.5em;padding: 0 5px;">
			<p>หนังสือกู้ที่ ...............................................</p>
			<p>วันที่ .........................................................</p>
			<p>บัญชีเงินกู้ที่ .............................................</p>
		</div>
	';
	$html .= '
	  <div class="text-right">
		  เขียนที่........CRH Saving Application..........
	  </div>
	  <div class="text-right">
		  วันที่....................เดือน.......................................พ.ศ................
	  </div>
	 
	';



	$html .= '
		<div style="position: absolute; left:-25px; top: -10px;">
			 <div style=" width:247px;  height:220px; border:1px solid; padding:5px; font-size:17px; "> 
				  <div style="text-align:center; text-decoration: underline;">หลักฐานประกอบ</div>
				  <div>
				  -สลิปเงินเดือนของผู้กู้ รับรองโดยเจ้าหน้าที่การเงิน 
				  </div>
				  <div style="text-indent:5px; margin-top:-5px">
					  ประจําหน่วยงาน(เดือนล่าสุด)
				  </div>
				  <div style="margin-top:-5px">
				  -บัตรประจําตัวประชาชนหรือบัตรข้าราชการผู้กู้
				  </div>
				  <div style="text-indent:5px; margin-top:-5px">
					(กรณีรับเงินด้วยตนเองที่สหกรณ์)
				  </div>
				  <div style="margin-top:-5px;">
					-สําเนาสมุดคู่ฝากธนาคารหรือสําเนาสมุศคู่ฝากสหกรณ์
				  </div>
				  <div style="text-indent:5px; margin-top:-5px;">
					หน้าที่มีหมายเลขบัญชี(กรณีโอนเข้าบัญชีผู้กู้)
				  </div>
				  <div style="margin-top:-5px;" >
				  -บัตรประจําตัวประชาชนหรือบัตรข้าราชการผู้กู้และ
				  </div>
				  <div style="text-indent:5px; margin-top:-5px;">
					บัตรประตัวประชาชนหรือบัตรข้าราชการ 
				  </div>
				  <div style="text-indent:5px; margin-top:-5px;">
					ผู้รับมอบอํานาจ(กรณีมอบอํานาจรับเงินกู้
				  </div>
			 </div>
		</div>
	';



	$html .= '
	  <div style="margin-top:60px">
		เรียน คณะกรรมการดําเนินการสหกรณ์ออมทรัพย์สาธารณสุขเชียงราย จํากัด
	  </div>
	  <div style="margin-top:25px;" class="indent" >
		ข้าพเจ้า........................................................................................................สมาชิกเลขทะเบียนที่................................................
	  </div>
	  <div>
		รับราชการหรือทํางานประจําในตําแหน่ง................................................................สังกัด............................................................................
	  </div>
	  <div>
	  โทรศัพท์...................................ได้รับเงินได้รายเดือน..................................บาท  ขอเสนอคําขอกู้เงินเพื่อเหตุฉุกเฉิน ดังต่อไปนี้
	  </div>
	  <div class="indent" style="margin-top:10px" >
		ข้อ ๑. ข้าพเจ้าขอกู้เงินของสหกรณ์ จํานวน......................................บาท(................................................................................)
	  </div>
	  <div>
		 โดยจะนําไปใช้เพื่อ........................................................................................................................................................................................
	  </div>

	  <div  class="indent" style="margin-top:10px">
		ข้อ ๒ ข้าพเจ้ามีหนี้สินอยู่ต่อสหกรณ์ในฐานะผู้กู้ ดังต่อไปนี้ 
	  </div>
	  <div class="indent">
		หนังสือกู้เงินเพื่อเหตุฉุกเฉิน ที่.....................................................วันที่..............................................
	  </div>
	  <div style="padding-left:80px; ">
		เพื่อ.................................................................................เงินต้นคงเหลือ...................................ดอกเบี้ย...........................
	  </div>

	  <div  class="indent" style="margin-top:10px">
		ข้อ ๓. ถ้าข้าพเจ้าได้รับเงินกู้ ข้าพเจ้าจะขอส่งเงินกู้คืน ดังนี้
	  </div>
	  <div style="padding-left:82px;">
	  ภายในวันสิ้นเดือน ระยะเวลา.......................เดือน ต้นเงิน.......................................บาท พร้อมด้วยดอกเบี้ย
	  </div>

	  <div  class="indent" style="margin-top:10px">
	  ข้อ ๔. เมื่อข้าพเจ้าได้รับเงินกู้แล้ว ข้าพเจ้ายอมรับผูกพันตามข้อบังคับของสหกรณ์ ดังนี้
	  </div>
	  <div class="sub-indent">
		 ๔.๑ ยินยอมให้ผู้บังคับบัญชา หรือเจ้าหน้าที่ผู้จ่ายเงินได้รายเดือนของข้าพเจ้า ที่ได้รับมอบหมายจากสหกรณ์หักเงิน ได้รายเดือนของข้าพเจ้า ตามจํานวนงวดชําระหนี้ ข้อ ๒ เพื่อส่งต่อสหกรณ์
	  </div>
	  <div  class="sub-indent">
		 ๔.๒ ยอมให้ถือว่าในกรณีใด ๆ ดังกล่าวในข้อบังคับของสหกรณ์ ให้เงินกู้ที่ขอกู้ไปจากสหกรณ์เป็นอันถึงกําหนด ส่งคืนโดยสิ้นเชิงพร้อมดอกเบี้ยในทันที โดยมีพักคํานึงถึงกําหนดเวลาที่ตกลงไว้
	  </div>
	  <div  style="text-align:justify; text-indent:80px;">
		๔.๓ ถ้าประสงค์จะลาออกหรือย้ายจากราชการ หรืองานประจําของข้อบังคับของสหกรณ์จะแจ้งเป็นหนังสือให้ 
	  </div>
	  <div style="text-align:justify;">
	  สหกรณ์ทราบ และจัดการชําระหนี้ซึ่งมีอยู่ต่อสหกรณ์ให้เสร็จสิ้นเสียก่อน ถ้าข้าพเจ้าไม่จัดการชําระหนี้ให้เสร็จสิ้นตามที่กล่าวข้างต้น เมื่อข้าพเจ้าได้ลงชื่อได้รับเงินเดือน ค่าจ้าง เงินสะสม บําเหน็จ บํานาญ เงินกองทุนเลี้ยงชีพหรือเงินอื่นใดในหลักฐานที่ทางราชการหรือ หน่วยงานเจ้าของสังกัดหรือนายจ้าง จะจ่ายเงินให้แก่ข้าพเจ้า ข้าพเจ้ายินยอมให้เจ้าหน้าที่ผู้จ่ายเงินดังกล่าว หักเงินชําระหนี้พร้อมด้วย ดอกเบี้ยส่งชําระต่อสหกรณ์ให้เสร็จสิ้นเสียก่อนได้
	  </div>

	';


	$html .= '
	  <div style="text-align:center; margin-top:30px">
			ลงชื่อ......................................................................ผู้กู้
	  </div>
	  <div style="text-align:left; margin-left:5.7cm">
			(.......................................................................)
	  </div>
	  <div style="text-align:center; position: absolute; left:6.3 cm; margin-top:10px" >
		ลงชื่อ......................................................................ผยาน
		</div>

		<div style="text-align:center; position: absolute; left:6.7 cm; margin-top:30px">
		(.........................................................................)
		</div>
	<div style="height:30px"></div>
	';

	$html .= '
		<div style="position:absolute; text-align:center; left:560px; top:-13px; width:100px;font-weight:bold; ">
		  '.$data["requestdoc_no"].'
		</div>
		<div style="position:absolute; text-align:center; left:530px; top:15px; width:100px;font-weight:bold; ">
		  '.$lib->convertdate(date("Y-m-d"),"d m Y").'
		</div>';
	// ข้อมูล เขียนที่ /วันที่ / เดือน/ พ.ศ.
	$html .= '
	  <div style="position:absolute; text-align:center; left:415px; top:136px; width:60px;font-weight:bold; ">
		  '.$arrRequestDate[0].'
	  </div>

	  <div style="position:absolute; text-align:center; left:505px; top:136px; width:115px;font-weight:bold; ">
		 '.$arrRequestDate[1].'
	  </div>

	  <div style="position:absolute; text-align:left; left:650px; top:136px;font-weight:bold; ">
		 '.$arrRequestDate[2].'
	  </div>
	';

	// ข้อมูล หน้าแรก/

	//  ชื่อ นามสกุล
	$html .= '
	<div style="position:absolute; text-align:center; left:105px; top:276px; width:315px;font-weight:bold ">
	   '.$data["full_name"].'
	</div>';

	//เลขสมาชิก
	$html .= '
	<div style="position:absolute; text-align:center; left:540px; top:276px; width:146px;font-weight:bold ">
		'.$data["member_no"].'
	</div>';

	// ตำแหน่ง
	$html .= '
	<div style="position:absolute; text-align:center; left:203px; top:304px; width:195px;font-weight:bold">
	   '.($data["position"] ?? "-").'
	</div>
	';

	// สังกัด
	$html .= '
	<div style="position:absolute; text-align:center; left:425px; top:304px; width:230px;font-weight:bold ">
		'.($data["pos_group"] ?? "-").'
	</div>
	';

	// โทรศัพท์
	$html .= '
	<div style="position:absolute; text-align:center; left:83px; top:332px; width:100px;font-weight:bold">
		'.($lib->formatphone($data["tel"],' ') ?? "-").'
	</div>
	';

	// รายเดือน
	$html .= '
	<div style="position:absolute; text-align:right; right:345px; top:332px;font-weight:bold ">
		'.($data["salary_amount"] ?? "-").'
	</div>
	';

	// จำนวนเงินกู้ตัวเลข
	$html .= '
	<div style="position:absolute; text-align:right; right:335px; top:370px;font-weight:bold">
		'.(number_format($data["request_amt"],2) ?? "-").'
	</div>
	';

	// จำนวนเงินกู้ ตัวอักษร
	$html .= '
	<div style="position:absolute; text-align:center; left:420px; top:370px; width:230px;font-weight:bold">
	  '.($lib->baht_text($data["request_amt"]) ?? "-").'
	</div>
	';

	// นำไปใช้เพื่อ
	$html .= '
	<div style="position:absolute; text-align:left; left:165px; top:397px;font-weight:bold">
	  '.($data["objective"] ?? "-").'
	</div>
	';

	// ที่
	$html .= '
	<div style="position:absolute; text-align:center; left:240px; top:463px; width:80px;font-weight:bold ">
	 
	</div>
	';

	// วันที่
	$html .= '
	<div style="position:absolute; text-align:left; left:430px; top:463px;font-weight:bold">

	</div>
	';

	// เพื่อ
	$html .= '
	<div style="position:absolute; text-align:center; left:143px; top:491px; width:240px;font-weight:bold ">

	</div>
	';

	// เงินต้นคงเหลือ
	$html .= '
	<div style="position:absolute; text-align:center; left:455px; top:491px; width:110px;font-weight:bold">

	</div>
	';

	// ดอกเบี้ย
	$html .= '
	<div style="position:absolute; text-align:center; left:605px; top:491px; width:80px;font-weight:bold">

	</div>
	';

	// ระยะเวลา
	$html .= '
	<div style="position:absolute; text-align:center; left:265px; top:558px; width:70px;font-weight:bold">
	  '.($data["period"] ?? "-").'
	</div>
	';

	// เงินต้น
	$html .= '
	<div style="position:absolute; text-align:center; left:389px; top:558px; width:115px;font-weight:bold;">
	  '.(number_format($data["request_amt"],2) ?? "-").'
	</div>
	';

	// (ชื่อ นามสกุล) ผู้กู้
	$html .= '
	<div style="position:absolute; text-align:center; left:260px; top:932px; width:217px;font-weight:bold;">
	 '.($data["name"] ?? "-").'
	</div>
	';

	// (ชื่อ นามสกุล) ผยาน
	$html .= '
	<div style="position:absolute; text-align:center; left:260px; top:1008px; width:217px;font-weight:bold;">

	</div>
	';

	$html .= '</div>';

	//----------------------------------page 2 ---------------------------------------
	//เคยผิดนัดการส่งเงินกู้   0 =ไม่เคย ,1= เคย
	$miss = -1;

	//ช่องทางการโอน 0 บช. ธนาคาร, 1 = บช. สหกรณ์ 
	$channel = -1;

	//บัญชีธนาคาร 0 = กรุงไทย, 1= กสิกรไทย
	 $bank = -1;

	//head page 2
	$html .= '
	<div style=" height:auto; margin-left:1.1cm; margin-top:-15px; margin-right:0.4cm; ">
		<div style="text-align:center">2</div>
		<div style="text-align:center; font-size:20pt;  font-weight:bold">
		 (สำหรับเจ้าหน้าที่ของสหกณ์)
		</div>
		<div class="text-center" style="margin-top:20px">
		  จำนวนเงินกู้.......................................................................บาท
		</div>
	';
	//table
	$html .= '
	  <table style="width:100%;  border-collapse: collapse; border:none;margin-top:15px; margin-left:-10px; ">
		  <thead>
			  <tr>
				  <th colspan=5>จำกัดวงเงินกู้เพื่อฉุกเฉิน</th>
			  </tr>
		  </thead>
		  <tbody>
		   <tr>
			<td>เงินได้รายเดือน</td>
			<td>ต้นเงินกู้สามัญคงเหลือ</td>
			<td>ต้นเงินกู้เพื่อฉุกเฉินคงเหลือ</td>
			<td>จำกัดวงเงินกู้</td>
			<td>จำกัดวงเงินกู้คงเหลือ</td>
		   </tr>
	';
	// ขอ้มูลในตาราง
	$html .= '
		<tr>
			<td> &nbsp; </td>
			<td> &nbsp; </td>
			<td> &nbsp; </td>
			<td> &nbsp; </td>
			<td> &nbsp; </td>
		</tr>
	';
	$html .= '
		</tbody>
	  </table>
	';
	$html .= '
	<div style="margin-top:15px; ">
	  <b>หมายเหตุ</b> ๑.  ผู้ขอกู้ เคยผิดนัดการส่งเงินงวดชำระหนี้หรือขาดส่งเงินค่าหุ้นรายเดือนหรือไม่
	</div>
	<div style="padding-left:120px;margin-top:10px; ">';
	$html .= '
		<input type="checkbox" 
		';
	$miss == 1 ? $html .= 'checked ' : $html .= '';
	$html .= '>';
	$html .= '
		<div style="position: absolute; left:185px; top: 270px;">
			เคย
		</div>
		';
	$html .= '
	  <input type="checkbox" style="margin-left:80px;"';
	$miss == 0 ? $html .= 'checked ' : $html .= '';
	$html .= '>';

	$html .= '
	  <div style="position: absolute; left:290px; top: 270px;">
	  ไม่เคย
	  </div>
	</div>
	<div style="margin-left:53px;">  
	  ๒. ข้อชี้แจงอื่น ๆ
	</div>
	<div class="sub-indent" >
	  เห็นควร อนุมัติ / ไม่อนุมัติ
	</div>
	<div style="margin-left:123px; margin-top:15px">
	  .......................................................................เจ้าหน้าที่
	</div>
	<div style="margin-left:123px;">
	  วันที่................../....................../.....................
	</div>

	<div class="sub-indent" >
	  อนุมัติ / ไม่อนุมัติ
	</div>

	<div style="margin-left:123px; margin-top:15px">
	  .......................................................................ประธาน / รองประธาน/ ผู้จัดการ/ ผู้ที่ได้รับมอบหมาย
	</div>

	<div style="margin-left:53px;"> 
	  ข้าพเจ้าผู้กู้มอบอํานาจให้.....................................................................ตําแหน่ง.......................................................................
	</div>

	<div>
		สังกัด...........................................................เป็นผู้รับเงินกู้ตามหนังสือกู้แทนข้าพเจ้า
	</div>

	<div style="position: absolute; left:40px; top: 565px; width:300px; height:123px; border:1px solid; ">
		  <div class="text-center">คำเตือน</div>
		  <div class="text-center"> ถ้าท่านมอบอํานาจให้ผู้อื่นรับเงินแทน</div>
		  <div class="text-center">โปรดฝากบัตรประจําตัวของท่านไปกับผู้รับเงิน</div>
		  <div class="text-center">แทนท่านด้วยเพื่อแสดงต่อเจ้าหน้าที่</div>
	</div>
	<div style="position: absolute; left:40px; top: 705px; width:280px; height:60px; border:1px solid; padding-left:10px; padding-right:10px ">
		  <div >บัตรประจำตัว....................................................................</div>
		  <div >เลขที่..................................................................................</div>

	</div>



	<div style="margin-left:340px; margin-top:28px">
	   ................................................................ผู้กู้
	</div>
	<div style="margin-left:340px;">
	   ................................................................ผู้รับมอบอำนาจ
	</div>


	<div style="margin-left:340px; margin-top:30px">
	   ................................................................ผยาน
	</div>
	<div style="margin-left:340px;">
	   (..............................................................)
	</div>


	<div style="margin-left:53px; margin-top:50px;"> 
		ข้าพเจ้า...............................................................................................ได้รับเงินกู้ จำนวน.................................................บาท
	</div>
	<div>
	  (..................................................................................................) ไปเป็นการถูกต้องแล้ว ณ วันที่............../.................../.........................
	</div>

	<div style="margin-left:300px; margin-top:20px;">
	  ......................................................................................ผู้รับ
	</div>
	<div  style="margin-left:300px;">
	  ต้องลงลายมือชื่อในการรับเงินต่อเจ้าหน้าที่ของสหกรณ์
	</div>

	<div style="margin-left:210px; margin-top:20px;">
	  จ่ายเงินถูกตอ้งแล้ว.......................................................................................เจ้าหน้าที่การเงิน
	</div>
	<div style="margin-left:300px;">
	  วันที่......................../........................./............................
	</div>
	';


	$html .= '
	<div>
	  <div style="position: absolute; left:42px; top:989px;">
	  ';
	$html .= '
	  <input type="checkbox"';
	$channel == 0 ? $html .= "checked" : $html .= "";
	$html .= '>';

	$html .= '
	  </div> 
	  <span style="margin-left:20px;">โอนเข้าบัญชีธนาคาร</span>
	  <div style="position: absolute; left:174px; top:989px;">';
	  $html .= '
	  <input type="checkbox"';
	  $bank == 0 ? $html .= "checked" : $html .= '';
	  $html .= '>';


	$html .='
	  </div> 
	  <span style="margin-left:20px;">กรุงไทย</span> 
	  <div style="position: absolute; left:240px; top:989px;">';
	  
	  $html .= '
	  <input type="checkbox"';
	  $bank == 1 ? $html .= "checked" : $html .= '';
	  $html .= '>';

	$html .='
	  </div>  
	  <span style="margin-left:20px;">กสิกรไทย</span> 
	  สาขา...........................................................เลขบัญชี..........................................
	</div>
	';

	$html .= '
	<div style="position:absolute; bottom:0px; left 30px;">
	  <div style="position: absolute; left:0px;  bottom:21px;"> ';
	  $html .= '
	  <input type="checkbox"';
		  $channel == 1 ? $html .= "checked" : $html .= "";
		  $html .= '>';

	$html.='
		</div> 
			<span style="margin-left:20px;">โอนเข้าบัญชีเงินฝากสหกรณ์ๆ เลขที่บัญชี..............................................................</span>
		</div>';

	//ข้อมูลหน้า 2 

	//จำนวนเงินกู้
	$html .= '
	  <div style="position:absolute; text-align:right; left:275px; top:85px; width:213px; ">
		  
	  </div>';

	//วันที่อนุมัติ
	//วันที่
	$html .= '
	  <div style="position:absolute; text-align:center; left:187px; top:400px; width:51px; ">
		
	  </div>  
	';

	//เดือน
	$html .= '
	  <div style="position:absolute; text-align:center; left:248px; top:400px; width:60px; ">
		
	  </div>  
	';

	//ปี
	$html .= '
	  <div style="position:absolute; text-align:center; left:318px; top:400px; width:60px;">
	   
	  </div>';

	//ชื่อพยาน
	$html .= '
	  <div style="position:absolute; text-align:center; left:385px; top:695px; width:187px;">
	  
	  </div>';

	//ชื่อผู้กู้
	$html .= '
	  <div style="position:absolute; text-align:center; left:132px; top:774px; width:287px;">
	  
	  </div>';

	//ได้รับเงินกู้ตัวเลข
	$html .= '
	  <div style="position:absolute; text-align:right; right:47px; top:774px;">
	  
	  </div>';
	//ได้รับเงินกู้ตัวอักษร
	$html .= '
	  <div style="position:absolute; text-align:center; left:47px; top:801px; width:290px;  ">
	  
	  </div>';
	//วันที่ตรวจสอบ
	//วัน
	$html .= '
	  <div style="position:absolute; text-align:center; right:165px; top:801px; width:40px;  ">
		
	  </div>';
	//เดือน
	$html .= '
	  <div style="position:absolute; text-align:center; right:100px; top:801px; width:60px; ">
		
	  </div>';
	//ปี
	$html .= '
	  <div style="position:absolute; text-align:center; right:20px; top:801px; width:70px;">
		
	  </div>';
	//บช สาขา ธนาคาร 
	$html .= '
	  <div style="position:absolute; text-align:center; left:333px; bottom:36px; width:178px;">
		 
	  </div>';

	//เลข บช ธนาคาร 
	$html .= '
	  <div style="position:absolute; text-align:center; right:21px; bottom:36px; width:128px;">
		 
	  </div>';

	//เลข บช สหกรณ์ 
	$html .= '
	  <div style="position:absolute; text-align:center; left:260px; bottom:1px; width:188px;">
		
	  </div>
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