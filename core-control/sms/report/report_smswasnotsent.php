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
                <p style="margin-top: -25px;font-size: 22px;font-weight: bold">เทมเพลต</p>
                <p style="margin-top: -20px;font-size: 22px;font-weight: bold">ระหว่าง</p>
            </div>
        </div>';
//}




$html .= '<div style="width: 100%; height:auto;">
<table >
  <thead>
    <tr>
      <th style="width:30%">ข้อความ</th>
      <th style="width:10%" >เลขสมาชิก</th>
      <th style="width:8%">ประเภท</th>
      <th style="width:11%">tel_mobile</th>
	  <th style="width:11%">send_date</th>
	  <th>cause_notsent</th>
	  <th>send_by</th>

    </tr>
  </thead>
  <tbody>
';

$fetchSmsWashNotSent = $conmysql->prepare("SELECT message, member_no, send_platform, tel_mobile, send_date, cause_notsent, send_by 
								 FROM smswasnotsent");
										
		$fetchSmsWashNotSent->execute();
		while($rowSmsData = $fetchSmsWashNotSent->fetch(PDO::FETCH_ASSOC)){
		  $html.= '
			<tr>
				<td>'.$rowSmsData["message"].'</td>
				<td style="text-align:center">.'.$rowSmsData["member_no"].'</td>
				<td style="text-align:center">'.$rowSmsData["send_platform"].'</td>
				<td>'.$rowSmsData["tel_mobile"].'</td>
				<td style="text-align:center">'.$rowSmsData["send_date"].'</td>
				<td style="text-align:center">'.$rowSmsData["cause_notsent"].'</td>
				<td>'.$rowSmsData["send_by"].'</td>
			</tr>';
			
			/*$rowSmsData["message"];
			$rowSmsData["member_no"];
			$rowSmsData["send_platform"];
			$rowSmsData["tel_mobile"];
			$rowSmsData["send_date"];
			$rowSmsData["cause_notsent"];
			$rowSmsData["send_by"];
			*/
		}
/*for($i=0;$i<10;$i++){
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

}
*/


$html .='</tbody></table></div>';

	$dompdf->set_paper('A4');
	$dompdf->load_html($html);
	$dompdf->render();
	$dompdf->stream("sample.pdf", array("Attachment"=>0));


?>
