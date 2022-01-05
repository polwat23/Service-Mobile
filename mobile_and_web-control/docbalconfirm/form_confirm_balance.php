<?php
use Dompdf\Dompdf;

$dompdf = new DOMPDF();

function GeneratePdfDoc($arrHeader,$arrDetail,$isConfirm = false) {
	$checkedIcon = '<img src="../../resource/utility_icon/check-icon.png" width="12px" height="12px" style="position: absolute;left: 20px;top: 6px;"/>';
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
				* {
				  font-family: TH Niramit AS;
				}
				body {
				  padding: 0;
				  font-size:18px;
				}
				td {
					padding: 0 4px;
					white-space: nowrap;
				}
				.input-zone{
					position: relative;
				}
				.input-value{
					position: absolute;
					white-space: nowrap;
					left : 5px;
					top: -2px;
					font-weight: bold;
				}
				tr td:nth-child(2),tr td:nth-child(4),tr td:nth-child(5),tr td:nth-child(7),tr td:nth-child(8) {
					padding-left: 16px;
				}
				tr td:nth-child(2),tr td:nth-child(5) {
					font-weight: bold;
					text-align: right;
				}
			</style>';
	$html .= '<div style="height:auto; margin: auto;width: 680px;position: relative;">
			<div style="margin-left:50px; margin-top:5px;"> 
			<img src="../../resource/logo/logo.jpg" style="width:70px;" >
			</div>
			<div style="position: absolute; left: 130px; top: 22px; font-size:22px; font-weight:bold;  ">
			 <b>หนังสือยืนยันยอดทุนเรือนหุ้น และยอดหนี้คงเหลือ</b>
			</div>
			
			<div style="margin:16px 0 16px;" >
				<div>
					เรียน<span style="margin-left: 18px">ผู้สอบบัญชี  สหกรณ์ออมทรัพย์พนักงานสยามคูโบต้า จำกัด</span>
				</div>
				<div>
					ข้าพเจ้า  '.$arrHeader["full_name"].'  เลขสมาชิก '.$arrHeader["member_no"].'
				</div>
				<div>
					ขอยืนยันจำนวนทุนเรือนหุ้น  และยอดหนี้คงเหลือ ที่มีกับสหกรณ์ออมทรัพย์พนักงานสยามคูโบต้า จำกัด ณ วันที่  '.$arrHeader["date_confirm"].'  ดังนี้
				</div>
			</div>
			<div>
			    <table style="border-collapse: collapse;">
					<tr>
					  <td>1. ทุนเรือนหุ้น</td>
					  <td>'.number_format($arrDetail["CONFIRM_LIST"][0]["CONFIRM_SUB_VALUE"], 2).'</td>   
					  <td>หุ้น</td>  
					  <td>จำนวนเงิน</td>  
					  <td>'.number_format($arrDetail["CONFIRM_LIST"][0]["CONFIRM_VALUE"], 2).'</td> 
					  <td>บาท</td>  
					  <td style="position: relative"><span style="border: 1px solid #000000;padding: 5px;height: 16px;width: 20px;font-size: 12px;position: relative;">&nbsp;</span>
						'.($arrDetail["CONFIRM_FLAG"]["SHARE"] == "1" ? $checkedIcon : "").' ถูกต้อง
					  </td>  
					  <td style="position: relative"><span style="border: 1px solid #000000;padding: 5px;height: 16px;width: 20px;font-size: 12px;position: relative;">&nbsp;</span>
						'.($arrDetail["CONFIRM_FLAG"]["SHARE"] == "0" ? $checkedIcon : "").' ไม่ถูกต้อง
					  </td>  
					</tr>  
					<tr>
					  <td>2. เงินกู้  ฉุกเฉิน</td>
					  <td></td>   
					  <td></td>  
					  <td>จำนวนเงิน</td>  
					  <td>'.number_format($arrDetail["CONFIRM_LIST"][1]["CONFIRM_VALUE"], 2).'</td> 
					  <td>บาท</td>  
					  <td style="position: relative"><span style="border: 1px solid #000000;padding: 5px;height: 16px;width: 20px;font-size: 12px;position: relative;">&nbsp;</span>
						'.($arrDetail["CONFIRM_FLAG"]["EMERLOAN"] == "1" ? $checkedIcon : "").' ถูกต้อง
					  </td>  
					  <td style="position: relative"><span style="border: 1px solid #000000;padding: 5px;height: 16px;width: 20px;font-size: 12px;position: relative;">&nbsp;</span>
						'.($arrDetail["CONFIRM_FLAG"]["EMERLOAN"] == "0" ? $checkedIcon : "").' ไม่ถูกต้อง
					  </td>  
					</tr>   
					<tr>
					  <td>3. เงินกู้ สามัญ</td>
					  <td></td>   
					  <td></td>  
					  <td>จำนวนเงิน</td>  
					  <td>'.number_format($arrDetail["CONFIRM_LIST"][2]["CONFIRM_VALUE"], 2).'</td> 
					  <td>บาท</td>  
					  <td style="position: relative;width: 60px;"><span style="border: 1px solid #000000;padding: 5px;height: 16px;width: 20px;font-size: 12px;position: relative;">&nbsp;</span>
						'.($arrDetail["CONFIRM_FLAG"]["LOAN"] == "1" ? $checkedIcon : "").' ถูกต้อง
					  </td>  
					  <td style="position: relative;width: 80px;"><span style="border: 1px solid #000000;padding: 5px;height: 16px;width: 20px;font-size: 12px;position: relative;">&nbsp;</span>
						'.($arrDetail["CONFIRM_FLAG"]["LOAN"] == "0" ? $checkedIcon : "").' ไม่ถูกต้อง
					  </td>  
					</tr>      
				</table>
		    </div>
			<div style="margin-left:80px;padding-top: 16px;">
				 คำชี้แจง (กรณีไม่ถูกต้อง)<span class="input-zone"><span class="input-value">'.$arrDetail["CONFIRM_REASON"].'
				</span>………………………………………………………………………………………………………………………………………</span>
			</div>
			<div style="padding-left:80px;text-align: center;padding-top: 16px;">
				ลงชื่อ…………………………………..สมาชิก
			</div>
			<div style="margin-left:80px;padding-top: 16px;">
				 ขอได้โปรดแจ้งไปยัง    นางดวงกมล  ลิ้มประชาศักดิ์  ผู้สอบบัญชี    ว่ารายการดังกล่าวข้างต้นถูกต้อง   
			</div>
			<div>
				หรือมีข้อทักท้วงประการใด  ตามหนังสือยืนยันยอดฉบับนี้
			</div>
			<div style="text-align: center;padding-top: 16px;">
				 ขอแสดงความนับถือ									
			</div>
			<div style="text-align: center;height: 36px">									
			</div>
			<div style="text-align: center;">
			           (นางวริชยา         ตำนานจิตร)																	
			</div>
			<div style="text-align: center;">
			            ประธานกรรมการ 																								
			</div>
			';
				//<img src="../../resource/utility_icon/president.png" width="36px" height="36px"/>
	$html .= '
	   </div>
	';
	$dompdf = new Dompdf([
		'fontDir' => realpath('../../resource/fonts'),
		'chroot' => realpath('/'),
		'isRemoteEnabled' => true
	]);
	$dompdf->set_paper('A4', 'landscape');
	$dompdf->load_html($html);
	$dompdf->render();
	if(!file_exists($pathfile)){
		mkdir($pathfile, 0777, true);
	}
	if($isConfirm){
		$pathfile = __DIR__.'/../../resource/pdf/docbalconfirm/confirmed';
		$pathfile = $pathfile.'/'.$arrHeader["date_confirm_raw"].$arrHeader["member_no"].'.pdf';
		$pathfile_show = '/resource/pdf/docbalconfirm/confirmed/'.$arrHeader["date_confirm_raw"].$arrHeader["member_no"].'.pdf?v='.time();
	}else{
		$pathfile = __DIR__.'/../../resource/pdf/docbalconfirm';
		$pathfile = $pathfile.'/'.$arrHeader["date_confirm_raw"].$arrHeader["member_no"].'.pdf';
		$pathfile_show = '/resource/pdf/docbalconfirm/'.$arrHeader["date_confirm_raw"].$arrHeader["member_no"].'.pdf?v='.time();
	}
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