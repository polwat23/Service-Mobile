<?php
require '../../../extension/vendor/autoload.php';
require('../../autoload.php');



use Dompdf\Dompdf;
$dompdf = new DOMPDF();


$html ='

<style>
			@font-face {
				font-family: THSarabun;
				src: url(../../../resource/fonts/THSarabun.ttf);
			}
			@font-face {
				font-family: "THSarabun";
				src: url(../../../resource/fonts/THSarabun Bold.ttf);
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
			td, th {
				border: 1px solid black;
			}

			table {
				border-collapse: collapse;
				width: 100%;
			}

			th {
				height: 30px;
				text-align:center;
			}
			td{
				word-wrap: break-word;
				padding-left:5px;
				padding-right:5px;
			}
      </style>

 

        
        <div style="display: flex;text-align: center;position: relative;margin-bottom: 20px;">
            <div style="text-align:center;width:100%;margin-left: 0px">
                <p style="margin-top: -5px;font-size: 22px;font-weight: bold">รายงานข้อความ SMS ที่ส่งไม่ได้</p>
               
            </div>
        </div>';
//}   <p style="margin-top: -25px;font-size: 22px;font-weight: bold">เทมเพลต</p>
//<p style="margin-top: -20px;font-size: 22px;font-weight: bold">ระหว่าง</p>

$html .= '<div style="width: 100%; height:auto;">
<table >
  <thead>
    <tr>
      <th style="width:30%">ข้อความ</th>
      <th style="width:10%" >เลขสมาชิก</th>
      <th style="width:8%">ประเภท</th>
      <th style="width:11%">เบอร์โทร</th>
	  <th>สาเหตุที่ส่งไม่ได้</th>
	  <th>ผู้ส่ง</th>
	   <th style="width:11%">วันที่ส่ง</th>

    </tr>
  </thead>
  <tbody>
';

$fetchSmsWashNotSent = $conmysql->prepare("SELECT message, member_no, send_platform, tel_mobile, send_date, cause_notsent, send_by 
								 FROM smswasnotsent LIMIT 10 ");
										
		$fetchSmsWashNotSent->execute();
		while($rowSmsData = $fetchSmsWashNotSent->fetch(PDO::FETCH_ASSOC)){
		  $html .= '
			<tr>
				<td>'.($rowSmsData["message"] ?? null).'</td>
				<td style="text-align:center">.'.($rowSmsData["member_no"] ?? null).'</td>
				<td style="text-align:center">'.($rowSmsData["send_platform"] ?? null).'</td>
				<td>'.($rowSmsData["tel_mobile"] ?? null).'</td>
				<td style="text-align:center">'.($rowSmsData["cause_notsent"] ?? null).'</td>
				<td>'.($rowSmsData["send_by"] ?? null).'</td>
				<td style="text-align:center">'.($rowSmsData["send_date"] ?? null).'</td>
			</tr>';
		}
	
		
/*for($i=0;$i<390;$i++){
  $html .= '
    <tr>
      <td>ท่านไม่สามารถกู้ฉุกเฉินได้เนื่องจากเงินเดือนหักภาระหนี้ทุกสัญญารวมกันแล้วคงเหลือต่ำกว่า 3,500 บาท</td>
      <td style="text-align:center">00517607</td>
      <td style="text-align:center">sms</td>
	  <td>0992409191</td>
	  <td style="text-align:center">2020-02-12 14:55:59</td>
	  <td style="text-align:center">ไม่พบเบอร์โทรศัพท์</td>
	  <td>dev@mode</td>
    </tr>
';
} */



$html .='</tbody></table></div>';

	$dompdf->set_paper('A4');
	$dompdf->load_html($html);
	$dompdf->render();
	$dompdf->stream("sample.pdf", array("Attachment"=>0));


?>
