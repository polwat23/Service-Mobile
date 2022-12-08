<?php
use Dompdf\Dompdf;
$dompdf = new DOMPDF();

function GenerateReport($dataReport,$header,$lib){
	$sumBalance = 0;
	$html = '<style>
				@font-face {
				  font-family: TH Niramit AS;
				  src: url(../resource/fonts/TH Niramit AS.ttf);
				}
				@font-face {
					font-family: TH Niramit AS;
					src: url(../resource/fonts/TH Niramit AS Bold.ttf);
					font-weight: bold;
				}
				* {
				  font-family: TH Niramit AS;
				}
				body {
				  padding: 0 30px;
				}
				.sub-table div{
					padding : 5px;
				}
			</style>
			<div style="display: flex;text-align: center;position: relative;margin-bottom: 20px;">
				<div style="text-align: left;"><img src="../resource/logo/logo.jpg" style="margin: 10px 0 0 5px" alt="" width="80" height="80" /></div>
				<div style="text-align:left;position: absolute;width:100%;margin-left: 140px">';
	if($header["keeping_status"] == '-99' || $header["keeping_status"] == '-9'){
		$html .= '<p style="margin-top: -5px;font-size: 22px;font-weight: bold;color: red;">ยกเลิกใบเสร็จรับเงิน</p>';
	}else{
		$html .= '<p style="margin-top: -5px;font-size: 22px;font-weight: bold">ใบเสร็จรับเงิน</p>';
	}
	$html .= '<p style="margin-top: -30px;font-size: 22px;font-weight: bold">สหกรณ์ออมทรัพย์สาธารณสุขหนองคาย จำกัด</p>
				<p style="margin-top: -27px;font-size: 18px;">229/1 ถ.ศูนย์ราชการ</p>
				<p style="margin-top: -25px;font-size: 18px;">ต.หนองกอมเกาะ อ.เมือง จ.หนองคาย 43000</p>
				<p style="margin-top: -25px;font-size: 18px;">โทร. 042-420750 มือถือ 087-8604004 , 089-8619198</p>
				<p style="margin-top: -27px;font-size: 19px;font-weight: bold"></p>
				</div>
			</div>
			<div style="margin: 25px 0 10px 0;">
			<table style="width: 100%;">
			<tbody>
			<tr>
			<td style="width: 50px;font-size: 18px;">เลขสมาชิก :</td>
			<td style="width: 350px;">'.$header["member_no"].'</td>
			<td style="width: 50px;font-size: 18px;">เลขที่ใบเสร็จ :</td>
			<td style="width: 101px;">'.$header["receipt_no"].'</td>
			</tr>
			<tr>
			<td style="width: 50px;font-size: 18px;">งวด :</td>
			<td style="width: 350px;">'.$header["recv_period"].'</td>
			<td style="width: 50px;font-size: 18px;">วันที่ :</td>
			<td style="width: 101px;">'.$header["operate_date"].'</td>
			</tr>
			<tr>
			<td style="width: 50px;font-size: 18px;">ชื่อ - สกุล :</td>
			<td style="width: 350px;">'.$header["fullname"].'</td>
			<td style="width: 50px;font-size: 18px;">สังกัด :</td>
			<td style="width: 101px;">'.$header["member_group"].'</td>
			</tr>
			<tr>
			<td style="width: 50px;font-size: 18px;">ดอกเบี้ยสะสม :</td>
			<td style="width: 350px;">'.($header["interest_accum"]??null).'</td>
			<td style="width: 50px;font-size: 18px;"></td>
			<td style="width: 101px;"> </td>
			</tr>
			</tbody>
			</table>
			</div>
			<div style="border: 0.5px solid black;width: 100%; height: 325px;">
			<div style="display:flex;width: 100%;height: 30px;" class="sub-table">
			<div style="border-bottom: 0.5px solid black;">&nbsp;</div>
			<div style="width: 350px;text-align: center;font-size: 18px;font-weight: bold;border-right : 0.5px solid black;padding-top: 1px;">รายการชำระ</div>
			<div style="width: 100px;text-align: center;font-size: 18px;font-weight: bold;border-right : 0.5px solid black;margin-left: 355px;padding-top: 1px;">งวดที่</div>
			<div style="width: 110px;text-align: center;font-size: 18px;font-weight: bold;border-right : 0.5px solid black;margin-left: 465px;padding-top: 1px;">เงินต้น</div>
			<div style="width: 110px;text-align: center;font-size: 18px;font-weight: bold;border-right : 0.5px solid black;margin-left: 580px;padding-top: 1px;">ดอกเบี้ย</div>
			<div style="width: 120px;text-align: center;font-size: 18px;font-weight: bold;border-right : 0.5px solid black;margin-left: 700px;padding-top: 1px;">รวมเป็นเงิน</div>
			<div style="width: 150px;text-align: center;font-size: 18px;font-weight: bold;margin-left: 815px;padding-top: 1px;">ยอดคงเหลือ</div>
			</div>';
				// Detail
	$html .= '<div style="width: 100%;height: 260px" class="sub-table">';
	for($i = 0;$i < sizeof($dataReport); $i++){
		if($i == 0){
			$html .= '<div style="display:flex;height: 30px;padding:0px">
			<div style="width: 350px;border-right: 0.5px solid black;height: 250px;">&nbsp;</div>
			<div style="width: 100px;border-right: 0.5px solid black;height: 250px;margin-left: 355px;">&nbsp;</div>
			<div style="width: 110px;border-right: 0.5px solid black;height: 270px;margin-left: 465px;">&nbsp;</div>
			<div style="width: 110px;border-right: 0.5px solid black;height: 270px;margin-left: 580px;">&nbsp;</div>
			<div style="width: 120px;border-right: 0.5px solid black;height: 270px;margin-left: 700px;">&nbsp;</div>
			<div style="width: 350px;text-align: left;font-size: 18px">
			<div>'.$dataReport[$i]["TYPE_DESC"].' '.$dataReport[$i]["PAY_ACCOUNT"].'</div>
			</div>
			<div style="width: 100px;text-align: center;font-size: 18px;margin-left: 355px;">
			<div>'.($dataReport[$i]["PERIOD"] ?? null).'</div>
			</div>
			<div style="width: 110px;text-align: right;font-size: 18px;margin-left: 465px;">
			<div>'.($dataReport[$i]["PRN_BALANCE"] ?? null).'</div>
			</div>
			<div style="width: 110px;text-align: right;font-size: 18px;margin-left: 580px;">
			<div>'.($dataReport[$i]["INT_BALANCE"] ?? null).'</div>
			</div>
			<div style="width: 120px;text-align: right;font-size: 18px;margin-left: 700px;">
			<div>'.($dataReport[$i]["ITEM_PAYMENT"] ?? null).'</div>
			</div>
			<div style="width: 150px;text-align: right;font-size: 18px;margin-left: 814px;">
			<div>'.($dataReport[$i]["ITEM_BALANCE"] ?? null).'</div>
			</div>
			</div>';
		}else{
			$html .= '<div style="display:flex;height: 30px;padding:0px">
			<div style="width: 350px;text-align: left;font-size: 18px">
				<div>'.$dataReport[$i]["TYPE_DESC"].' '.$dataReport[$i]["PAY_ACCOUNT"].'</div>
			</div>
			<div style="width: 100px;text-align: center;font-size: 18px;margin-left: 355px;">
			<div>'.($dataReport[$i]["PERIOD"] ?? null).'</div>
			</div>
			<div style="width: 110px;text-align: right;font-size: 18px;margin-left: 465px;">
			<div>'.($dataReport[$i]["PRN_BALANCE"] ?? null).'</div>
			</div>
			<div style="width: 110px;text-align: right;font-size: 18px;margin-left: 580px;">
			<div>'.($dataReport[$i]["INT_BALANCE"] ?? null).'</div>
			</div>
			<div style="width: 120px;text-align: right;font-size: 18px;margin-left: 700px;">
			<div>'.($dataReport[$i]["ITEM_PAYMENT"] ?? null).'</div>
			</div>
			<div style="width: 150px;text-align: right;font-size: 18px;margin-left: 814px;">
			<div>'.($dataReport[$i]["ITEM_BALANCE"] ?? null).'</div>
			</div>
			</div>';
		}
		$sumBalance += $dataReport[$i]["ITEM_PAYMENT_NOTFORMAT"];
	}
	$html .= '</div>';
			// Footer
	$html .= '<div style="display:flex;width: 100%;height: 40px" class="sub-table">
			<div style="border-top: 0.5px solid black;">&nbsp;</div>
			<div style="width: 600px;text-align:center;height: 30px;font-size: 18px;padding-top: 0px;">'.$lib->baht_text($sumBalance).'</div>
			<div style="width: 110px;border-right: 0.5px solid black;height: 30px;margin-left: 465px;padding-top: 0px;">&nbsp;</div>
			<div style="width: 110px;text-align: center;font-size: 18px;border-right : 0.5px solid black;padding-top: 0px;height:30px;margin-left: 580px">
			รวมเงิน
			</div>
			<div style="width: 120px;text-align: right;border-right: 0.5px solid black;height: 30px;margin-left: 700px;padding-top: 0px;font-size: 18px;">'.number_format($sumBalance,2).'</div>
			</div>
			</div>
			<div style="display:flex;">
			<div style="width:500px;font-size: 18px;">หมายเหตุ : ใบรับเงินประจำเดือนจะสมบูรณ์ก็ต่อเมื่อทางสหกรณ์ได้รับเงินที่เรียกเก็บเรียบร้อยแล้ว<br>ติดต่อสหกรณ์ โปรดนำ 1. บัตรประจำตัว 2. ใบเสร็จรับเงิน 3. สลิปเงินเดือนมาด้วยทุกครั้ง
			</div>
			<div style="width:200px;margin-left: 700px;display:flex;">
			<img src="../resource/utility_icon/signature/manager.jpg" width="100" height="50" style="margin-top:10px;"/>
			</div>
			</div>
			<div style="font-size: 18px;margin-left: 730px;margin-top:-60px;">ผู้จัดการ</div>
			';

	$dompdf = new Dompdf([
		'fontDir' => realpath('../resource/fonts'),
		'chroot' => realpath('/'),
		'isRemoteEnabled' => true
	]);

	$dompdf->set_paper('A4', 'landscape');
	$dompdf->load_html($html);
	$dompdf->render();
	$pathfile = __DIR__.'/../../../resource/pdf/keeping_monthly';
	if(!file_exists($pathfile)){
		mkdir($pathfile, 0777, true);
	}
	$pathfile = $pathfile.'/'.$header["member_no"].$header["receipt_no"].'.pdf';
	$pathfile_show = '/resource/pdf/keeping_monthly/'.$header["member_no"].$header["receipt_no"].'.pdf?v='.time();
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