<?php
require_once('../autoload.php');

use Dompdf\Dompdf;

$dompdf = new DOMPDF();

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SlipCremation')){
		$member_no = $configAS[$payload["member_no"]] ?? TRIM($payload["member_no"]);
		$arrGroupDetail = array();
		$header = array();
		$fetchName = $conoracle->prepare("SELECT mb.memb_name,mb.memb_surname,mp.prename_desc,mbg.MEMBGROUP_DESC,mbg.MEMBGROUP_CODE
												FROM mbmembmaster mb LEFT JOIN 
												mbucfprename mp ON mb.prename_code = mp.prename_code
												LEFT JOIN mbucfmembgroup mbg ON mb.MEMBGROUP_CODE = mbg.MEMBGROUP_CODE
												WHERE mb.member_no = :member_no");
		$fetchName->execute([
			':member_no' => $member_no
		]);
		$rowName = $fetchName->fetch(PDO::FETCH_ASSOC);
		
		$getSlipNo = $conoracle->prepare("SELECT   kptempreceive.KPSLIP_NO ,  TO_CHAR(kptempreceive.receipt_date,'DD/MM/YYYY','NLS_CALENDAR=''THAI BUDDHA') as RECEIPT_DATE  
									FROM wcrecievemonthdetail LEFT JOIN  kptempreceive ON wcrecievemonthdetail.member_no  = kptempreceive.member_no AND  wcrecievemonthdetail.recv_period = kptempreceive.recv_period 
									where TRIM(wcrecievemonthdetail.recv_period) = :recv_period  and wcrecievemonthdetail.cash_type = 'WFA' 
									and TRIM(wcrecievemonthdetail.member_no) =  :member_no
									and  wcrecievemonthdetail.status_post <> -9
									GROUP BY   kptempreceive.kpslip_no ,  kptempreceive.receipt_date");
		$getSlipNo->execute([
					':recv_period' => $dataComing["recv_period"],
					':member_no' => $member_no
		]);
		$rowSlipNo = $getSlipNo->fetch(PDO::FETCH_ASSOC);
		$header["fullname"] = $rowName["PRENAME_DESC"].$rowName["MEMB_NAME"].' '.$rowName["MEMB_SURNAME"];
		$header["member_group"] = $rowName["MEMBGROUP_CODE"].' '.$rowName["MEMBGROUP_DESC"];
		$header["member_no"] = $member_no;
		$header["receipt_no"] = $rowSlipNo["KPSLIP_NO"];
		$header["receipt_date"] = $rowSlipNo["RECEIPT_DATE"];
		$getReceipt = $conoracle->prepare("select  ('ชำระศพที่ ' ||wcrecievemonthdetail.die_no || ' '|| mbucfprename.prename_desc ||wcdeptmaster.deptaccount_name ||'  ' ||wcdeptmaster.deptaccount_sname ) as KEEPOTHITEMTYPE_DESC ,  
								sum(wcrecievemonthdetail.carcass_amt)  as ITEM_PAYMENT, 
								kptempreceive.kpslip_no as KPSLIP_NO, 
								TO_CHAR(kptempreceive.receipt_date,'DD/MM/YYYY','NLS_CALENDAR=''THAI BUDDHA') as RECEIPT_DATE  
								from  wcrecievemonthdetail left join  wcrecievemonth on wcrecievemonthdetail.recv_period = wcrecievemonth.recv_period
								and wcrecievemonthdetail.member_no = wcrecievemonth.member_no and  wcrecievemonthdetail.wfmember_no = wcrecievemonth.wfmember_no
								left join wcdeptmaster on wcrecievemonthdetail.die_accno = wcdeptmaster.deptaccount_no 
								left join mbucfprename on mbucfprename.prename_code  = wcdeptmaster.prename_code  
								left join kptempreceive on  kptempreceive.recv_period = wcrecievemonth.recv_period and kptempreceive.member_no = wcrecievemonthdetail.member_no
								where TRIM(wcrecievemonthdetail.recv_period) = :recv_period and
								TRIM(wcrecievemonthdetail.member_no) =  :member_no and 
								wcrecievemonthdetail.cash_type = 'WFA'
								and wcrecievemonthdetail.status_post <> -9
								group by wcrecievemonthdetail.die_no , mbucfprename.prename_desc, wcdeptmaster.deptaccount_name ,
								wcdeptmaster.deptaccount_sname ,  kptempreceive.kpslip_no ,  kptempreceive.receipt_date
								UNION  
								select 
								'ค่าบำรุงรายปี' as  KEEPOTHITEMTYPE_DESC , 
								sum(ins_amt)   as ITEM_PAYMENT ,kptempreceive.KPSLIP_NO as KPSLIP_NO, TO_CHAR(kptempreceive.receipt_date, 'DD/MM/YYYY','NLS_CALENDAR=''THAI BUDDHA') as RECEIPT_DATE 
								FROM 
								wcrecievemonth left join  kptempreceive on    kptempreceive.recv_period = wcrecievemonth.recv_period and kptempreceive.member_no = wcrecievemonth.member_no
								where 
								wcrecievemonth.recv_period =  :recv_period 
								and wcrecievemonth.cash_type = 'WFA'
								and wcrecievemonth.status_post <> -9
								and TRIM(wcrecievemonth.member_no) =  :member_no
								group by kptempreceive.kpslip_no ,  kptempreceive.receipt_date
								having sum( wcrecievemonth.ins_amt) > 0");
		$getReceipt->execute([':member_no' => $member_no,
							  ':recv_period' => $dataComing["recv_period"]]);
		while($rowReceipt = $getReceipt->fetch(PDO::FETCH_ASSOC)){
			$arrDetail = array();
			$arrDetail["keepothitemtype_desc"] = $rowReceipt["KEEPOTHITEMTYPE_DESC"];
			$arrDetail["item_payment"] = number_format($rowReceipt["ITEM_PAYMENT"],2);
			$sum_payment += $rowReceipt["ITEM_PAYMENT"];				
			$arrGroupDetail[] = $arrDetail;
		}
		
		$arrayPDF = GenerateReport($arrGroupDetail,$header,$lib,$sum_payment);
		if($arrayPDF["RESULT"]){
			$arrayResult['REPORT_URL'] = $config["URL_SERVICE"].$arrayPDF["PATH"];
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS0044",
				":error_desc" => "สร้าง PDF ไม่ได้ "."\n".json_encode($dataComing),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "สร้างไฟล์ PDF ไม่ได้ ".$filename."\n"."DATA => ".json_encode($dataComing);
			$lib->sendLineNotify($message_error);
			$arrayResult['RESPONSE_CODE'] = "WS0044";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
			
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../include/exit_footer.php');
		
	}
}else{
	$filename = basename(__FILE__, '.php');
	$logStruc = [
		":error_menu" => $filename,
		":error_code" => "WS4004",
		":error_desc" => "ส่ง Argument มาไม่ครบ "."\n".json_encode($dataComing),
		":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
	];
	$log->writeLog('errorusage',$logStruc);
	$message_error = "ไฟล์ ".$filename." ส่ง Argument มาไม่ครบมาแค่ "."\n".json_encode($dataComing);
	$lib->sendLineNotify($message_error);
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
	
}
function GenerateReport($dataReport,$header,$lib,$sum_payment){
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
		  padding: 0 px;
		}
		.sub-table div{
			padding : 5px;
		}
		</style>

		<div style="display: flex;text-align: center;position: relative;margin-bottom: 0px;">
		<div style="text-align: left;"><img src="../../resource/logo/logo_etc.png" style="margin: -10px 0 0 5px" alt="" width="80" height="80" /></div>
		<div style="text-align:left;position: absolute;width:100%;margin-left: 120px">';
		$html .= '<p style="margin-top: -10px;font-size: 22px;font-weight: bold">สมาคม ฌาปนกิจสงเคราะห์สหกรณ์ออมทรัพย์สาธารณสุขเชียงราย - พะเยา</p>
		<p style="margin-top: -27px;font-size: 18px;">1039/74 ถนนร่วมจิตถวาย ต.เวียง อ.เมือง จ.เชียงราย 57000</p>
		<p style="margin-top: -25px;font-size: 18px;">โทร. ฝ่ายบริหารทั่วไป 086-451-9488, ฝ่ายสินเชื่อ  086-451-9187 www.cricoop.com</p>
		</div>
		</div>
		<div style="margin: -30px 0 -30px 0;">';
		$html .= '<p style="font-size: 25px;font-weight: bold;margin-left: 320px;">ใบเสร็จรับเงิน</p>';
		$html .= '<table style="width: 100%;margin-top:-30px;margin-bottom: 27px;">
		<tbody>
		<tr>
		<td style="width: 80px; font-size: 18px; ">วันที่ใบเสร็จ :</td>
		<td style="width: 310px; ">' . $header["receipt_date"] .'</td>
		<td style="width: 50px;font-size: 18px;">เลขที่ใบเสร็จ :</td>
		<td style="width: 101px;">' . $header["receipt_no"] . '</td>
		</tr>
		<tr>
		<td style="width: 50px;font-size: 18px;">ได้รับเงินจาก :</td>
		<td style="width: 310px;">' . $header["fullname"] . '</td>
		<td style="width: 50px;font-size: 18px;">เลขสมาชิก :</td>
		<td style="width: 101px;">' . $header["member_no"] . '</td>
		</tr>
		<tr>
		<td style="width: 50px;font-size: 18px;">หน่วยงาน :</td>
		<td style="width: 310px;">'. $header["member_group"] . '</td>
		<td style="width: 50px;font-size: 18px;"></td>
		<td style="width: 101px;"></td>
		</tr>
		</tbody>
		</table>
		</div>
		<div>
		<div style="display:flex;width: 100%;height: 20px;" class="sub-table">
		<div style="border-bottom: 0.5px solid #CFCFCF;border-top: 0.5px solid #CFCFCF;">&nbsp;</div>
		<div style="width: 500px;text-align: center;font-size: 18px;font-weight: bold;padding-top: 1px;">รายการ</div>
		<div style="width: 150px;text-align: right;font-size: 18px;font-weight: bold;margin-left: 550px;padding-top: 1px;">จำนวนเงิน</div>
		</div>';


		// Detail
		$html .= '<div style="width: 100%;" class="sub-table">';
		$html .= '<table style="height: 30px;padding:10px;">';
		foreach ($dataReport as $dataArr) {		
			$html .= '<tr>
			<td style="width: 500px">'. $dataArr["keepothitemtype_desc"] . '</td>
			<td style="width: 150px;text-align: right;height: 30px;padding-left: 35px;padding-top: 0px;">'. $dataArr["item_payment"] . '</td>
			</tr>';
		}
		$html .= '</div>';
	

		$html .= ' <div style=" margin-top:50px">
			<div style="display:flex; height:30px">
			<div style="position:absolute;font-weight:bold;"><img src="../../resource/utility_icon/signature/mg_etc.jpg" width="100" height="50" style="margin-top:-45px;margin-left: 90px"/></div>
			<div style="position:absolute;font-weight:bold;"><img src="../../resource/utility_icon/signature/fn_etc.png" width="100" height="50" style="margin-top:-45px;margin-left: 70%"/></div>
			<div style="display:inline;font-weight:bold;padding: 20px;">ลงชื่อ......................................................นายกสมาคมฯ</div>
			<div style="display:inline;font-weight:bold;padding: 20px;margin-left:55%">ลงชื่อ......................................................จนท.ผู้รับเงิน</div>
			</div>
			<div style="display:flex; margin-top:-15px ">
			<div style="display:inline;font-weight:bold;padding: 20px;margin-left: 60px;">นายอุทิศ ทับทิมฉาย</div>
			<div style="display:inline;font-weight:bold;padding: 20px;margin-left: 65%;">นายธวัชชัย เยายานัง</div>
		</div>';
		$html .= '
		
		</div>';
		
		$html .= '<div style="display:flex;width: 100%;height: 40px" class="sub-table">
		<div style="border-top: 0.5px solid #CFCFCF;border-bottom: 0.5px solid #CFCFCF;">&nbsp;</div>
		<div style="width: 300px;text-align:center;height: 30px;font-size: 18px;padding-top: 0px;font-weight:bold;  ">(-'.$lib->baht_text($sum_payment).'-)</div>
		<div style="width: 110px;height: 30px;margin-left: 313px;padding-top: 0px;">&nbsp;</div>
		<div style="width: 110px;text-align: center;font-size: 18px;padding-top: 0px;height:30px;margin-left: 430px;font-weight:bold; ">
		รวม
		</div>
		<div style="width: 150px;text-align: right;height: 30px;margin-left: 544px;padding-top: 0px;font-size: 18px;font-weight:bold;">' . number_format($sum_payment, 2) . '</div>
		</div>
		</div>';

	$dompdf = new Dompdf([
		'fontDir' => realpath('../../resource/fonts'),
		'chroot' => realpath('/'),
		'isRemoteEnabled' => true
	]);

	//$dompdf->set_paper('A4', 'landscape');
	$dompdf->load_html($html);
	$dompdf->render();
	$pathfile = __DIR__.'/../../resource/pdf/cremation';
	if(!file_exists($pathfile)){
		mkdir($pathfile, 0777, true);
	}
	$pathfile = $pathfile.'/'.$header["member_no"].$header["receipt_no"].'.pdf';
	$pathfile_show = '/resource/pdf/cremation/'.urlencode($header["member_no"]).$header["receipt_no"].'.pdf?v='.time();
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
