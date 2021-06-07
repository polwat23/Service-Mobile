<?php
require_once('../autoload.php');

use Dompdf\Dompdf;

$dompdf = new DOMPDF();

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SlipCremation')){
		$member_no = $configAS[$payload["member_no"]] ?? TRIM($payload["member_no"]);
		$getKeepingData = $conoracle->prepare("SELECT STATUS_POST,OPERATE_DATE,MEMBGROUP_CODE,RECEIPT_NO,CARCASS_AMT,INS_AMT,FEE_YEAR,ADDADVANCE_AMT,WFMEMBER_NO 
												FROM WFRECIEVEMONTH 
												WHERE TRIM(MEMBER_NO) = :member_no AND RECV_PERIOD = :recv_period");
		$getKeepingData->execute([
			':member_no' => $member_no,
			':recv_period' => $dataComing["recv_period"]
		]);
		$rowKeeping = $getKeepingData->fetch(PDO::FETCH_ASSOC);
		$header["recv_period"] = $lib->convertperiodkp(TRIM($dataComing["recv_period"]));
		$header["member_no"] = $payload["member_no"];
		$header["wcmember_no"] = $rowKeeping["WFMEMBER_NO"];
		$header["status_post"] = $rowKeeping["STATUS_POST"];
		$header["sumall_pay"] = $rowKeeping["CARCASS_AMT"] + $rowKeeping["INS_AMT"] + $rowKeeping["FEE_YEAR"] + $rowKeeping["ADDADVANCE_AMT"];
		$header["receipt_no"] = TRIM($rowKeeping["RECEIPT_NO"]);
		$fetchName = $conoracle->prepare("SELECT MB.MEMB_NAME,MB.MEMB_SURNAME,MP.PRENAME_DESC
												FROM mbmembmaster mb LEFT JOIN 
												mbucfprename mp ON mb.prename_code = mp.prename_code
												WHERE TRIM(mb.member_no) = :member_no");
		$fetchName->execute([
			':member_no' => $member_no
		]);
		$rowName = $fetchName->fetch(PDO::FETCH_ASSOC);
		$sqlGetMembGrp = $conoracle->prepare("SELECT (B.MEMBGROUP_DESC || ' / ' || A.MEMBGROUP_DESC ) AS MEMBGROUP_CODE_STR 
												FROM MBUCFMEMBGROUP A LEFT JOIN MBUCFMEMBGROUP B ON A.MEMBGROUP_CONTROL = B.MEMBGROUP_CODE 
												WHERE A.MEMBGROUP_CODE = :MEMBGRP");
		$sqlGetMembGrp->execute([':MEMBGRP' => $rowKeeping["MEMBGROUP_CODE"]]);
		$rowMembGrp = $sqlGetMembGrp->fetch(PDO::FETCH_ASSOC);
		$getAmtDiePerson = $conoracle->prepare("SELECT COUNT(wfaccount_name) AS DIE_AMT FROM WFDEPTMASTER 
												WHERE KPRECV_PERIOD = :recv_period ORDER BY DIE_DATE ASC, DEPTACCOUNT_NO ASC");
		$getAmtDiePerson->execute([':recv_period' => $dataComing["recv_period"]]);
		$rowdie = $getAmtDiePerson->fetch(PDO::FETCH_ASSOC);
		$arrDiePerson = array();
		$getDiePerson = $conoracle->prepare("SELECT WFACCOUNT_NAME FROM WFDEPTMASTER 
												WHERE KPRECV_PERIOD = :recv_period ORDER BY DIE_DATE ASC, DEPTACCOUNT_NO ASC");
		$getDiePerson->execute([':recv_period' => $dataComing["recv_period"]]);
		while($rowPersondie = $getDiePerson->fetch(PDO::FETCH_ASSOC)){
			$arrDiePerson[] = $rowPersondie["WFACCOUNT_NAME"];
		}
		$arrGroupDetail = array();
		$arrGroupDetail[0]["LABEL"] = "ค่าสงเคราะห์ศพ จำนวน ".$rowdie["DIE_AMT"]." ราย";
		$arrGroupDetail[0]["AMOUNT"] = number_format($rowKeeping["CARCASS_AMT"],2);
		$arrGroupDetail[1]["LABEL"] = "ค่าสงเคราะห์ศพค้างจ่าย";
		$arrGroupDetail[1]["AMOUNT"] = number_format($rowKeeping["INS_AMT"],2);
		$arrGroupDetail[2]["LABEL"] = "ค่าบำรุงรายปี";
		$arrGroupDetail[2]["AMOUNT"] = number_format($rowKeeping["FEE_YEAR"],2);
		$arrGroupDetail[3]["LABEL"] = "เงินสงเคราะห์ล่วงหน้า";
		$arrGroupDetail[3]["AMOUNT"] = number_format($rowKeeping["ADDADVANCE_AMT"],2);
		$arrGroupDetail[3]["LABEL"] = "รายชื่อผู้เสียชีวิต ประจำเดือน ".$lib->convertperiodkp(TRIM($dataComing["recv_period"]));
		$arrGroupDetail[4]["LABEL"] = $arrDiePerson;
		$header["fullname"] = $rowName["PRENAME_DESC"].$rowName["MEMB_NAME"].' '.$rowName["MEMB_SURNAME"];
		$header["member_group"] = $rowMembGrp["MEMBGROUP_CODE_STR"];
		$header["operate_date"] = $lib->convertdate($rowKeeping["OPERATE_DATE"],'D m Y');
		$arrayPDF = GenerateReport($arrGroupDetail,$header,$lib);
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

function GenerateReport($dataReport,$header,$lib){
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
			  padding: 0 30px;
			}
			.sub-table div{
				padding : 5px;
			}
			</style>

			<div style="display: flex;text-align: center;position: relative;margin-bottom: 20px;">
			<div style="text-align: left;"><img src="../../resource/logo/logo_wc.gif" style="margin: 10px 0 0 5px" alt="" width="80" height="80" /></div>
			<div style="text-align:left;position: absolute;width:100%;margin-left: 140px">';
			if($header["status_post"] == '1'){
				$html .= '<p style="margin-top: -5px;font-size: 22px;font-weight: bold">ใบเสร็จรับเงิน</p>';
			}else{
				$html .= '<p style="margin-top: -5px;font-size: 22px;font-weight: bold">ใบเรียกเก็บเงิน</p>';
			}
	$html .= '<p style="margin-top: -30px;font-size: 22px;font-weight: bold">สมาคมฌาปนกิจสงเคราะห์ สหกรณ์ออมทรัพย์ข้าราชการกระทรวงศึกษาธิการ จำกัด</p>
			<p style="margin-top: -27px;font-size: 18px;">319 อาคารสมานฉันท์ ชั้น 3 กระทรวงศึกษาธิการ</p>
			<p style="margin-top: -25px;font-size: 18px;">ถนนพิษณุโลก แขวงดุสิต เขตดุสิต กทม. 10300</p>
			<p style="margin-top: -25px;font-size: 18px;">โทร : 0-2282-5609, 0-2628-7500-3</p>
			<p style="margin-top: -27px;font-size: 19px;font-weight: bold">www.moecoop.com</p>
			</div>
			</div>
			<div style="margin: 25px 0 10px 0;">
			<table style="width: 100%;">
			<tbody>
			<tr>
			<td style="width: 50px;font-size: 18px;">เลขที่ใบเสร็จ :</td>
			<td style="width: 350px;">'.$header["receipt_no"].'</td>
			<td style="width: 50px;font-size: 18px;">วันที่ :</td>
			<td style="width: 101px;">'.$header["operate_date"].'</td>
			</tr>
			<tr>
			<td style="width: 50px;font-size: 18px;">ได้รับเงินจาก :</td>
			<td style="width: 350px;">'.$header["fullname"].'</td>
			<td style="width: 50px;font-size: 18px;">เลขทะเบียน :</td>
			<td style="width: 101px;">'.$header["member_no"].'</td>
			</tr>
			<tr>
			<td style="width: 50px;font-size: 18px;">หน่วยงาน :</td>
			<td style="width: 350px;">'.$header["member_group"].'</td>
			<td style="width: 50px;font-size: 18px;">เลขสมาชิกสมาคมฯ :</td>
			<td style="width: 101px;">'.$header["wcmember_no"].'</td>
			</tr>
			</tbody>
			</table>
			</div>
			<div>
			<div style="display:flex;width: 100%;height: 30px;" class="sub-table">
			<div style="border-bottom: 0.5px solid #CFCFCF;border-top: 0.5px solid #CFCFCF;">&nbsp;</div>
			<div style="width: 800px;text-align: center;font-size: 18px;font-weight: bold;padding-top: 1px;">รายการ</div>
			<div style="width: 150px;text-align: right;font-size: 18px;font-weight: bold;margin-left: 800px;padding-top: 1px;">จำนวนเงิน</div>
			</div>';
			// Detail
	$html .= '<div style="width: 100%;height: 260px" class="sub-table">';
	foreach($dataReport as $dataArr){
		if(is_array($dataArr["LABEL"])){
			$html .= '
			<div style="display:flex;height: 30px;padding:0px">
			<div style="width:800px;">';
				foreach($dataArr["LABEL"] as $person){
					$html .= '<div style="padding: 10px 20px;display:inline">'.$person.'</div>';
				}
			$html .= '</div></div>';
		}else{
			$html .= '<div style="display:flex;height: 30px;padding:0px">
			<div style="width: 750px;text-align: left;font-size: 18px">
				<div>'.$dataArr["LABEL"].'</div>
			</div>
			<div style="width: 150px;text-align: right;font-size: 18px;margin-left: 800px;">
			<div>'.($dataArr["AMOUNT"] ?? null).'</div>
			</div>
			</div>';
		}
	}

	$html .= '</div>';
			// Footer
	$html .= '<div style="display:flex;width: 100%;height: 40px" class="sub-table">
			<div style="border-top: 0.5px solid #CFCFCF;border-bottom: 0.5px solid #CFCFCF;">&nbsp;</div>
			<div style="width: 600px;text-align:center;height: 30px;font-size: 18px;padding-top: 0px;font-weight:bold">'.$lib->baht_text($header["sumall_pay"]).'</div>
			<div style="width: 110px;height: 30px;margin-left: 465px;padding-top: 0px;">&nbsp;</div>
			<div style="width: 110px;text-align: center;font-size: 18px;padding-top: 0px;height:30px;margin-left: 580px;font-weight:bold">
			รวมเงิน
			</div>
			<div style="width: 150px;text-align: right;height: 30px;margin-left: 800px;padding-top: 0px;font-size: 18px;font-weight:bold">'.number_format($header["sumall_pay"],2).'</div>
			</div>
			</div>
			<div>
			<div style="display:inline;font-weight:bold;padding: 20px;">ลงชื่อ</div>
			<div style="display:inline;font-weight:bold;"><img src="../../resource/utility_icon/signature/fin.jpg" width="100" height="50" style="margin-top:10px;margin-left: 70px"/></div>
			<div style="display:inline;font-weight:bold;padding: 20px;margin-left: 70px;">เหรัญญิก</div>
			<div style="display:inline;font-weight:bold;padding: 20px;margin-left: 150px;">ลงชื่อ</div>
			<div style="display:inline;font-weight:bold;padding: 20px;"><img src="../../resource/utility_icon/signature/staff.jpg" width="100" height="50" style="margin-top:10px;margin-left: 70px"/></div>
			<div style="display:inline;font-weight:bold;padding: 20px;margin-left: 70px;">เจ้าหน้าที่</div>
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
	$pathfile = __DIR__.'/../../resource/pdf/keeping_monthly';
	if(!file_exists($pathfile)){
		mkdir($pathfile, 0777, true);
	}
	$pathfile = $pathfile.'/'.$header["member_no"].$header["receipt_no"].'.pdf';
	$pathfile_show = '/resource/pdf/keeping_monthly/'.urlencode($header["member_no"]).$header["receipt_no"].'.pdf?v='.time();
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
