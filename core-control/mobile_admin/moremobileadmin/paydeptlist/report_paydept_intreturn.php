<?php
require_once('../../../autoload.php');

use Dompdf\Dompdf;

$dompdf = new DOMPDF();

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','paydeptlist')){
		$arrGroupDetail = array();
		$header = array();
		
		$member_no = strtolower($lib->mb_str_pad($dataComing["member_no"]));
		
		$memberInfo = $conoracle->prepare("SELECT mp.prename_short,mb.memb_name,mb.memb_surname,mb.birth_date,mb.card_person,
												mb.member_date,mb.position_desc,mg.membgroup_desc,mb.MEMBGROUP_CODE
												FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
												LEFT JOIN MBUCFMEMBGROUP mg ON mb.MEMBGROUP_CODE = mg.MEMBGROUP_CODE
												WHERE mb.member_no = :member_no");
		$memberInfo->execute([':member_no' => $member_no]);
		$rowMember = $memberInfo->fetch(PDO::FETCH_ASSOC);
		
		$header["member_no"] = $member_no;
		$header["ref_no"] =  $dataComing["ref_no"];
		$header["fullname"] = $rowMember["PRENAME_SHORT"].$rowMember["MEMB_NAME"]." ".$rowMember["MEMB_SURNAME"];
		$header["membgroup_desc"] = $rowMember["MEMBGROUP_DESC"];
		$header["membgroup_code"] = $rowMember["MEMBGROUP_CODE"];
		$operateDate = $lib->convertdate($dataComing["operate_date"],'d M Y');
		$arrOperateDate = explode(" ", $operateDate);
		
		$header["operate_datetime"] = $operateDate." ".(date("H:i", strtotime($dataComing["operate_date"])));
		$header["operate_datetimeraw"] = date("d/m/", strtotime($dataComing["operate_date"])).(date("Y", strtotime($dataComing["operate_date"]))+543);
		$header["operate_month"] =  $arrOperateDate[1];
		$header["operate_year"] =  (date("Y", strtotime($dataComing["operate_date"]))+543);
		$header["loancontract_no"] =  $dataComing["loancontract_no"];
		$header["amount"] = $dataComing["amount"];
		$header["principal"] =  $dataComing["principal"];
		$header["interest"] =  $dataComing["interest"];
		$arrGroupDetail["member_no"] = $member_no;
		$arrayPDF = GenerateReport($arrGroupDetail,$header,$lib);
		if($arrayPDF["RESULT"]){
			$arrayResult['RESULT'] = TRUE;
			$arrayResult['PATH'] = $arrayPDF["PATH"];
			echo json_encode($arrayResult);
		}
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}

function GenerateReport($dataReport,$header,$lib){
	$sumBalance = 0;
	$html = '<style>
			@font-face {
				font-family: THSarabun;
				src: url(../../../../resource/fonts/THSarabun.ttf);
			}
			@font-face {
				font-family: "THSarabun";
				src: url(../../../../resource/fonts/THSarabun Bold.ttf);
				font-weight: bold;
			}
			* {
			  font-family: THSarabun
			}
			body {
			  padding: 0;
			  font-size:15pt;
			  font-weight;
			}
			div{
			  font-size:15pt;
			}
			.text-center{
			  text-align:center;
			}
			.text-right{
			  text-align:right;
			}
			.font-bold{
			  font-weight:bold
			}
			.clear{clear:both; line-height:0; height:0; font-size: 1px}
			table{
			  border-collapse: collapse;
			  line-height: 20px
			}
			th,td{
			  border:1px dashed;
			  text-align:center;
			}
			td{
			  font-weight:bold;
			  height:40px;
			  padding: 0 6px;
			}
			</style>';
			

	$html .= '  <div  style=" margin-left:25px;">';

	$html .= '
	  <div>
		<div class="text-center" style="font-weight:bold; font-size:20pt;" >
		  บันทึกข้อความ
		</div>
		<div class="text-right" style="font-weight:bold; font-size:12pt;line-height: 0px;transform: translateY(12pt);" >
		  '.$header["operate_datetime"].'
		</div>
		<div style="line-height: 1.5em;" >
			เรื่อง ขออนุมัติคืนเงินต้น,ดอกเบี้ย เงินกู้ส่งชําระหลังรายการส่งหัก
		</div>
		<div style="line-height: 1.5em;" >
			เรียน ผู้จัดการสหกรณ์ออมทรัพย์สาธารณสุขเชียงราย จํากัด
		</div>
	  </div>

	';
	$html .= '
	  <div>
		   <div style="position:absolut;  float :left; padding-left:75px;line-height: 1.5em;">
			  ตามที่
		   </div>
		   <div class="text-center font-bold" style="position:absolut;  float :left;  width:295px;line-height: 1.5em;">
			'.$header["fullname"].'
		  </div>
		  <div style="position:absolut;  float :left;  width:180px;line-height: 1.5em;">
		  สมาชิกเลขที่ 
		  </div>
		  <div class="font-bold" style="position:absolut;  float :left;line-height: 1.5em; ">
		  '.$header["member_no"].' 
		  </div>
		  <div class="clear"></div>
	  </div>
	  <div>
		<div style="position:absolut;    float :left;line-height: 1.5em;">
			สังกัดหน่วยงาน 
		</div>
		<div class="text-center font-bold" style="position:absolut;    float :left; width:130px;line-height: 1.5em; ">
		   '.$header["membgroup_code"].'  
		</div>
		<div class="font-bold"  style="position:absolut;    float :left;  width:190px;line-height: 1.5em;  ">
			'.$header["membgroup_desc"].' 
		</div>
		<div style="position:absolut;    float :left;line-height: 1.5em;">
		  ได้ส่งชําระหนี้ภายหลังจากสหกรณ์
		</div>
		<div class="clear"></div>
	  </div>
	  <div>
		<div style="position:absolut;    float :left;line-height: 1.5em;">
		   จัดทำ/ส่ง รายการส่งหักงวดเดือน
		</div>
		<div class="text-center font-bold" style="position:absolut;    float :left; width:140px;line-height: 1.5em; ">
		'.$header["operate_month"].' 
		</div>
		<div class="text-center font-bold" style="position:absolut;    float :left; width:90px;line-height: 1.5em;">
		   '.$header["operate_year"].'  
		</div>
		<div  style="position:absolut;    float :left;line-height: 1.5em;">
			เมื่อวันที่
		</div>
		<div class="text-center font-bold" style="position:absolut;    float :left; width:150px;line-height: 1.5em;">
			'.$header["operate_datetimeraw"].'
		</div>
		<div class="text-center" style="position:absolut;    float :left;line-height: 1.5em;">
		  มีรายการ
		</div>
		<div class="clear"></div>
	</div>
	<div style="line-height: 1.5em;">
	  เงินต้น,ดอกเบี้ยต้องคืนให้แก่สมาชิก ดังนี้
	</div>
	';

	$html .='
	  <div style="margin-right:40px; margin-top:30px; ">
		  <table style="width:100%">
			 <thead>
				<tr>
					<th>สัญญาที่</th>
					<th>ส่ง/ชำระเงินต้น</th>
					<th colspan="5" style="">คืนเงินต้น,ดอกเบี้ย</th>
				</tr>
				<tr>
					<th></th>
					<th></th>
					<th>จากวันที่</th>
					<th>ถึงวันที่</th>
					<th>วัน</th>
					<th>เงินต้น</th>
					<th>ดอกเบี้ย</th>
				</tr>
			 </thead>
			 <tbody>
			  <tr>
				<td>'.$header["loancontract_no"].'</td>
				<td class="text-right" ></td>
				<td></td>
				<td></td>
				<td></td>
				<td class="text-right">'.number_format($header["principal"],2).'</td>
				<td class="text-right">'.number_format($header["interest"],2).'</td>
			  </tr>
			 </tbody>
		  </table>
		  <div class="text-center" style="position:absolut; float :left;  width:100px;">
			  เป็นเงิน
		  </div>
		  <div class="text-center font-bold" style="position:absolut; float :left;  width:390px;">
			'.$lib->baht_text($header["amount"]).'
		  </div>
		  <div class="text-center" style="position:absolut; float :left;">
			  รวม
		  </div>
		  <div class="text-right font-bold" style="position:absolut; float :left;  width:130px;  border-bottom:3px double ;   ">
			  '.number_format($header["amount"],2).'
		  </div>
	  </div>
	';
	$html .='
	  <div>
		  จึงเรียนมาเพื่อโปรดพิจารณาอนุมัติ
	  </div>
	  <div style="margin-left:380px;">
		 (...................................................)
	  </div>
	  <div style="margin-left:340px;">
		  ตำแหน่ง...................................................
	  </div>
	';


	$html .='
	<div style="line-height: 20px; margin-left:20px; ">
	  <div class="font-bold">
	  ตรวจสอบแล้วเห็นควรอนุมัติ
	  </div>
	  <div>
		ลงชื่อ...................................................
	  </div>
	  <div class="text-center" style="width:185px; ">
	   (หัวหน้าสินเชื่อ)
	  </div>
	  <div>
		  วันที่.......เดือน..................พ.ศ.............
	  </div>
	</div>
	<div style="margin-left:20px;">
	  <div>
		ลงชื่อ...................................................
	  </div>
	  <div class="text-center" style="line-height: 20px; width:185px; ">
	   (รองผู้จัดการ)
	  </div>
	  <div>
		วันที่.......เดือน..................พ.ศ.............
	  </div>
	</div>
	';

	$html.='
	  <div style="margin-left:340px; line-height: 20px;">
		  <div style="font-weight:bold; width:190px;  margin-left:28px; ">
		  [ ] -อนุมัติ [ ] -ไม่อนุมัติ
		  </div>
		  <div>
			  ลงชื่อ...................................................
		  </div>
		  <div class="text-center" style="width:185px; ">
		  (ผู้จัดการ)
		 </div>
		 <div>
		 วันที่.......เดือน..................พ.ศ.............
		 </div>
	  </div>
	';

	$html.='
	<div style="line-height: 20px; margin-left:20px; ">
	  <div>
		[ ] -เงินสด
	  </div>
	  <div>
		[ ] -เงินฝากสหกรณ์ เลขที่...........................
	  </div>
	  <div>
	  [ ] -อาคาร..........................สาขา..................................เลขที่..........................
	  </div>
	</div>
	';




	$html .= '
		</div>
	  ';


	// Footer
	$html .= '</div>';

	$dompdf = new DOMPDF();
	$dompdf->set_paper('A4');
	$dompdf->load_html($html);
	$dompdf->render();
	$pathfile = __DIR__.'/../../../../resource/pdf/repayloan';
	if(!file_exists($pathfile)){
		mkdir($pathfile, 0777, true);
	}
	$pathfile = $pathfile.'/'.$header["ref_no"].'.pdf';
	$pathfile_show = '/resource/pdf/repayloan/'.$header["ref_no"].'.pdf?v='.time();
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