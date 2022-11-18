<?php
require_once('../autoload.php');

use Dompdf\Dompdf;

$dompdf = new DOMPDF();

if($lib->checkCompleteArgument(['menu_component','recv_period'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SlipInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$header = array();
		
		if ($dataComing["recv_period"] == 'register') {
			$getPaymentDetail = $conoracle->prepare("
				SELECT
					WB.DEPTSLIPBRANCH_NO as DEPTSLIP_NO,
					WQ.WFACCOUNT_NAME,   
					WS.DEPTSLIP_DATE,   
					CC.CS_NAME,   
					CB.COOPBRANCH_DESC,   
					WD.PRNCSLIP_AMT,   
					WQ.MEMBER_NO,   
					WD.SEQ_NO,   
					WT.DEPTITEMTYPE_DESC,
					WR.ROUND_REGIS,
					ftreadtbath((select sum(PRNCSLIP_AMT) from wcdeptslipdet where deptslip_no = WS.DEPTSLIP_NO and  coop_id =   WS.COOP_ID)) as total_desc
				FROM 
					WCREQDEPOSIT WQ
					JOIN WCDEPTSLIP WS ON (WQ.DEPTREQUEST_DOCNO = WS.DEPTACCOUNT_NO AND WQ.COOP_ID = WS.COOP_ID ) 
					JOIN WCDEPTSLIPDET WD ON (WD.DEPTSLIP_NO = WS.DEPTSLIP_NO AND WD.COOP_ID = WS.COOP_ID)
					JOIN WCDEPTSLIPBRANCH WB ON (WB.DEPTSLIP_NO = WS.DEPTSLIP_NO AND WB.COOP_ID = WS.COOP_ID)
					JOIN CMUCFCOOPBRANCH CB ON ( WQ.COOP_ID = CB.COOP_ID ) 
					JOIN CMUCFCREMATION CC ON ( CB.COOP_CONTROL = CC.COOP_CONTROL) 
					JOIN WCUCFDEPTITEMTYPE WT ON (WD.DEPTITEMTYPE_CODE = WT.DEPTITEMTYPE_CODE)
					LEFT JOIN WCUCFROUNDREGISFIXED WR ON (WR.DEPTOPEN_DATE = WQ.DEPTOPEN_DATE)
				WHERE
					WS.DEPTITEMTYPE_CODE  = 'WFF'
					AND TRIM(WQ.DEPTACCOUNT_NO) = :member_no
			");
			$getPaymentDetail->execute([
				':member_no' => $member_no
			]);
		} else {
			$getPaymentDetail = $conoracle->prepare("
				SELECT 
					CM.CS_NAME,   
					CB.COOPBRANCH_DESC,    
					WS.DEPTSLIP_NO,   
					WM.WFACCOUNT_NAME,   
					WM.MEMBER_NO,   
					WM.DEPTACCOUNT_NO,   
					WS.DEPTSLIP_DATE,   
					WD.SEQ_NO,   
					CONCAT(TRIM(WT.DEPTITEMTYPE_DESC),CONCAT('ประจำปี ',trim(to_char(to_number(SUBSTR( WS.RECV_PERIOD,1,4))+1)))) AS DEPTITEMTYPE_DESC,   
					WD.PRNCSLIP_AMT  
				FROM 
					WCDEPTSLIP WS 
					JOIN WCDEPTSLIPDET WD ON (WS.DEPTSLIP_NO = WD.DEPTSLIP_NO AND WS.COOP_ID = WD.COOP_ID)
					JOIN WCDEPTMASTER WM ON (WM.DEPTACCOUNT_NO =  WS.DEPTACCOUNT_NO )
					JOIN CMUCFCOOPBRANCH CB ON (CB.COOP_ID = WS.COOP_ID)
					JOIN CMUCFCREMATION CM ON (CB.COOP_CONTROL = CM.COOP_CONTROL) 
					JOIN WCUCFDEPTITEMTYPE WT ON ( WD.DEPTITEMTYPE_CODE = WT.DEPTITEMTYPE_CODE)
				WHERE 
					TRIM(WS.DEPTSLIP_NO) = :recv_period AND
					TRIM(WS.DEPTACCOUNT_NO) = :member_no AND
					WS.ITEM_STATUS = 1
					ORDER BY WD.SEQ_NO");
			$getPaymentDetail->execute([
				':member_no' => $member_no,
				':recv_period' => $dataComing["recv_period"]
			]);
		}
		
		$arrGroupDetail = array();
		while($rowDetail = $getPaymentDetail->fetch(PDO::FETCH_ASSOC)){
			$arrDetail = array();
			$arrDetail["dept_desc"] = $rowDetail["DEPTITEMTYPE_DESC"];
			$arrDetail["amt"] = $rowDetail["PRNCSLIP_AMT"];
			$arrGroupDetail[] = $arrDetail;
			
			$header["coopbranch_desc"] = $rowDetail["COOPBRANCH_DESC"];
			$header["member_no"] = $rowDetail["MEMBER_NO"];
			$header["deptslip_no"] = TRIM($rowDetail["DEPTSLIP_NO"]);
			$header["deptslip_date"] = $lib->convertdate($rowDetail["DEPTSLIP_DATE"],'d m Y');
			$header["name"] = $rowDetail["WFACCOUNT_NAME"];
			$header["deptaccount_no"] = $rowDetail["DEPTACCOUNT_NO"] ?? "";
			$header["total_desc"] = $rowDetail["TOTAL_DESC"] ?? "";
			$header["round_regis"] = $rowDetail["ROUND_REGIS"] ?? "";
		}

		$arrayPDF = GenerateReport($arrGroupDetail,$header,$lib);
		if($arrayPDF["RESULT"]){
			if ($forceNewSecurity == true) {
				$arrayResult['REPORT_URL'] = $config["URL_SERVICE"]."/resource/get_resource?id=".hash("sha256", $arrayPDF["PATH"]);
				$arrayResult["REPORT_URL_TOKEN"] = $lib->generate_token_access_resource($arrayPDF["PATH"], $jwt_token, $config["SECRET_KEY_JWT"]);
			} else {
				$arrayResult['REPORT_URL'] = $config["URL_SERVICE"].$arrayPDF["PATH"];
			}
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
	$class_td_boder = null;
	$total = 0;
	$html = '
	<!DOCTYPE html>
	<html lang="en">
	<head>
		<meta charset="UTF-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Document</title>
	</head>
	<body>
		<meta charset="UTF-8">
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
				  line-height: 18px;
				}
				div{
					line-height: 18px;
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
				.sub-list{
					padding-left:100px;
				}
				th{
					border:1px solid;
					text-align:center;
					padding-bottom:5px;
				}
				td{
					line-height:15px;
					padding:5px;
				}
				.absolute{
					position:absolute;
				}
				.data{
					font-size:15pt;
					margin-top:-2px;
				}
				.border{
					border: 1px solid;
				}
				.border-left{
					border-left: 1px solid;
				}
				.border-right{
					border-right: 1px solid;
				}
				.border-top{
					border-top: 1px solid;
				}
				.border-bottom
					border-bottom: 1px solid;
				}
				.wrapper-page {
					page-break-after: always;
				  }
				  .spac{
					margin-top:15px;
				  }
				  .tab{
					padding-left:15px;
				  }
				  
				.wrapper-page:last-child {
					page-break-after: avoid;
				}
				.inline{
					display:inline;
				}
				.border-collapse{
					border-collapse: collapse;
				}
				@page { size: 210mm 148mm; }
				width-full{
					width:100%;
				}
				</style>';
	//ขนาด
	$html .= '<div  style="margin:-5px 0px -30px 10px;" >';
	//หน้า 1
	$html .= '<div class="wrapper-page">';
	//ส่วนหัว
	$html .= '
		<div style="position:absolute; top:-20px; "><img src="../../resource/logo/logo.jpg" alt="" width="80" ></div>
		<div class="bold nowrap" style="position:absolute; left:550px" >เลขที่ : ' . ($header["deptslip_no"] ?? null) . ' </div>
		<div class="bold center" style="font-size:11pt; margin-top: -35px;">ต้นฉบับ</div>
		<div class="center bold" style="font-size:18pt;">ใบเสร็จรับเงิน</div>
		<div class="center bold">สมาคมฌาปนกิจสงเคราะห์สมาชิกสหกรณ์ออมทรัพย์สาธารณสุขไทย</div>
		<div class="center bold">ศูนย์ประสานงานสหกรณ์ออมทรัพย์'. $header["coopbranch_desc"] .'</div>
		<hr>
		<div class="center bold">สำนักงานใหญ่ : เลขที่ 199/9 อาคารเพชรสะพานบุญ หมู่ที่ 2 ถนนครอินทร์ ตำบลสีทอง</div>
		<div class="center bold">อำเภอบางกรวย จังหวัดนนทบุรี 11130 โทรศัพท์ : 0 2496 1350-57 โทรสาร:0 2496 1358</div>
		
		<table style="width: 100%;">
			<tbody>
			<tr>
				<td></td>
				<td></td>
				<td style="margin-left:540px">วันที่ : ' . ($header["deptslip_date"] ?? null) . '</td>
			</tr>
			<tr>
				<td style="margin-left:20px;">
					ได้รับเงินจาก : ' . ($header["name"] ?? null) . '
				</td>
				<td style="margin-left:370px;">
					เลขสมาชิกสหกรณ์ : ' . ($header["member_no"] ?? null) . '
				</td>';
		
		if (isset($header["deptaccount_no"]) && $header["deptaccount_no"] != "") {
			$class_td_boder = "border";
			$html .= '
				<td style="margin-left:540px;">เลขฌาปนกิจ ' . $header["deptaccount_no"] . '</td>
			';
		}

	$html .= '
			</tr>	
			</tbody>
		</table>

		<div >
			<table class="border-collapse" style="width:100%;">
				<thead>
					<tr>
						<td class="border center bold" style="width:7%;">ลำดับ</td>
						<td class="border center bold">รายการ</td>
						<td class="border center bold" style="width:20%;">จำนวนเงิน(บาท)</td>
					</tr>
				</thead>
				<tbody>';
	for ($i = 0; $i < sizeof($dataReport); $i++) {
		$no = $i + 1;
		$html .= '	<tr>
						<td class="center border-left border-right ' . $class_td_boder . '">' . ($no ?? '&nbsp;') . '</td>
						<td class="border-right ' . $class_td_boder . '">' . ($dataReport[$i]["dept_desc"] ?? '&nbsp;') . '</td>
						<td class="right border-right ' . $class_td_boder . '">' . (number_format($dataReport[$i]["amt"], 2) ?? '&nbsp;') . '</td>
					</tr>';
		$total +=  ($dataReport[$i]["amt"] ?? 0);
	}

	$html .= '	
					<tr>
						<td colspan="2" class="border-top">
							<div style="clear: both;"></div>
							';
	if (!empty($header["round_regis"])) {
		$html .= '
							<div style="float: left;">ค่าสมัครรอบ ' . ($header["round_regis"]) . '</div>
							<div class="center" style="float: left; width: 60%;">('.$header["total_desc"].')</div>';
	} else {
		$html .= '
							<div style="float: left;">&nbsp;</div>
							<div class="center" style="float: left; width: 50%;">&nbsp;</div>';
	}
	$html .= '							
							<div style="float: right;">รวมเป็นเงิน</div>
						</td>
						<td class="border right">
							' . (number_format($total, 2)) . '
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		
		<div>
			<div style="position:absolute;">
				<div >
					<div style="position:absolute; top:20px; margin-left:310px; "><img src="../../resource/logo/logo.jpg" alt="" width="75" ></div>
				</div>
				<div style="margin-left:480px;">
					<img src="../../resource/utility_icon/signature/c_finance_chief.png" width="100"  height="50" style="margin-top:15px; margin-left:70px;"/>
					<div class="border-top" style="margin-left:30px; width:180px;">&nbsp;</div>
				</div>
				<div class="center" style="margin-top:-20px; margin-left:510px; width:180px;">
					(นางสมจิตร์ โสภา)
				</div>
				<div class="center" style=" margin-left:510px; width:180px;">
					หัวหน้าฝ่ายการเงิน
				</div>
			</div>
			<div>
				<div>
					<img src="../../resource/utility_icon/signature/c_treasurer.png" width="100"  height="50" style="margin-top:10px; margin-left:70px;"/>
					<div class="border-top" style="margin-left:30px; width:180px;">&nbsp;</div>
				</div>
				<div class="center" style="margin-top:-20px; margin-left:30px; width:180px;">
					(นายวิทยา ประเทศ)
				</div>
				<div class="center" style=" margin-left:30px; width:180px;">
					เหรัญญิกสมาคม
				</div>
			</div>
		</div>
		<div style="margin-left:10px; margin-top: 10px;">
			<b>หมายเหตุ</b> : ใบรับเงินของสมาคมฯ จะสมบูรณ์ต่อเมื่อมีลายมือชื่อเหรัญญิกและผู้รับเงิน  
		</div>
		';

	$html .= '</div>';
	//ปิดหน้า

	$html .= '</div>';
	$html .= '
				</body>
				</html>
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
	$pathfile = $pathfile.'/'.(trim($header["deptaccount_no"]) ?? '').'_'.(trim($header["deptslip_no"]) ?? '').'.pdf';
	$pathfile_show = '/resource/pdf/keeping_monthly/'.(trim($header["deptaccount_no"]) ?? '').'_'.(trim($header["deptslip_no"]) ?? '').'.pdf?v='.time();
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