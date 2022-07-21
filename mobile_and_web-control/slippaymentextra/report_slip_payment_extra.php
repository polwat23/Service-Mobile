<?php
require_once('../autoload.php');

use Dompdf\Dompdf;

$dompdf = new DOMPDF();

if($lib->checkCompleteArgument(['menu_component','slip_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SlipExtraPayment')){
		$member_no = $configAS[$payload["member_no"]] ?? TRIM($payload["member_no"]);
		$arrGroupDetail = array();
		$getHeaderSlip = $conoracle->prepare("SELECT SLIP_DATE,SLIP_AMT,PAYINSLIP_NO
											FROM SLSLIPPAYIN WHERE TRIM(DOCUMENT_NO) = :slip_no and TRIM(member_no) = :member_no");
		$getHeaderSlip->execute([
			':slip_no' => $dataComing["slip_no"],
			':member_no' => $member_no
		]);
		$rowSlip = $getHeaderSlip->fetch(PDO::FETCH_ASSOC);
		$fetchName = $conoracle->prepare("SELECT MB.MEMB_NAME,MB.MEMB_SURNAME,MP.PRENAME_DESC,mbg.MEMBGROUP_CODE
												FROM mbmembmaster mb LEFT JOIN 
												mbucfprename mp ON mb.prename_code = mp.prename_code
												LEFT JOIN mbucfmembgroup mbg ON mb.MEMBGROUP_CODE = mbg.MEMBGROUP_CODE
												WHERE TRIM(mb.member_no) = :member_no");
		$fetchName->execute([
			':member_no' => $member_no
		]);
		$rowName = $fetchName->fetch(PDO::FETCH_ASSOC);
		$header["full_name"] = $rowName["PRENAME_DESC"].$rowName["MEMB_NAME"].' '.$rowName["MEMB_SURNAME"];
		$sqlGetMembGrp = $conoracle->prepare("SELECT (A.MEMBGROUP_CODE || ' ' || A.MEMBGROUP_DESC ) AS MEMBGROUP_CODE_STR 
												FROM MBUCFMEMBGROUP A LEFT JOIN MBUCFMEMBGROUP B ON A.MEMBGROUP_CONTROL = B.MEMBGROUP_CODE 
												WHERE A.MEMBGROUP_CODE = :MEMBGRP");
		$sqlGetMembGrp->execute([':MEMBGRP' => $rowName["MEMBGROUP_CODE"]]);
		$rowMembGrp = $sqlGetMembGrp->fetch(PDO::FETCH_ASSOC);
		$header["member_group_code"] = $rowName["MEMBGROUP_CODE"];
		$header["member_group"] = $rowMembGrp["MEMBGROUP_CODE_STR"];
		$header["member_no"] = $member_no;
		$header["slip_date"] = $lib->convertdate($rowSlip["SLIP_DATE"],'d/n/Y');
		$header["slip_no"] = $dataComing["slip_no"];
		$header["slip_amt"] = $rowSlip["SLIP_AMT"];
		$detailReceipt = $conoracle->prepare("SELECT cld.LOANCONTRACT_NO,
											CASE cld.SLIPITEMTYPE_CODE 
											WHEN 'LON' THEN NVL(lt.LOANTYPE_DESC,cld.slipitem_desc) 
											ELSE cld.slipitem_desc END as TYPE_DESC,
											cld.PERIOD,cld.PRINCIPAL_PAYAMT,cld.INTEREST_PAYAMT,cld.ITEM_PAYAMT,cld.ITEM_BALANCE
											FROM SLSLIPPAYINDET cld LEFT JOIN lnloantype lt ON cld.shrlontype_code = lt.loantype_code 
											WHERE TRIM(cld.PAYINSLIP_NO) = :slip_no");
		$detailReceipt->execute([':slip_no' => $rowSlip["PAYINSLIP_NO"]]);
		while($rowDetail = $detailReceipt->fetch(PDO::FETCH_ASSOC)){
			$arrayData = array();
			$arrayData["PAY_ACCOUNT"] = $rowDetail["LOANCONTRACT_NO"];
			$arrayData["TYPE_DESC"] = $rowDetail["TYPE_DESC"];
			$arrayData["PERIOD"] = $rowDetail["PERIOD"];
			$arrayData["PRN_BALANCE"] = number_format($rowDetail["PRINCIPAL_PAYAMT"],2);
			$arrayData["INT_BALANCE"] = number_format($rowDetail["INTEREST_PAYAMT"],2);
			$arrayData["ITEM_PAYMENT"] = number_format($rowDetail["ITEM_PAYAMT"],2);
			$arrayData["ITEM_PAYMENT_NOTFORMAT"] = $rowDetail["ITEM_PAYAMT"];
			$arrayData["ITEM_BALANCE"] = number_format($rowDetail["ITEM_BALANCE"],2);
			$arrGroupDetail[] = $arrayData;
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
	
	//New
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
				.center{
					text-align:center
				}
				.right{
					text-align:right;
				}
				th{
					border: 1px solid;
					font-size:18px;
					padding:3px 5px 7px 5px;
				}
				td{
					font-size 18px;
				}
				.border{
					border:1px solid;
				}
				.table-data{
					padding:0px 5px 0px 5px;
				}
				.wrapper-page {
					page-break-after: always;
				  }
				  
				.wrapper-page:last-child {
					page-break-after: avoid;
				}
				.border-right{
					border-right:1px solid;
				}
			</style>
	
			<div style="display: flex;text-align: center;position: relative;margin-bottom: 20px;">
			<div style="text-align: left;"><img src="../../resource/logo/logo.jpg" style="margin: 10px 0 0 5px" alt="" width="80" height="80" /></div>
			<div style="text-align:left;position: absolute;width:100%;margin-left: 140px">';

	$html .= '
	<p style="margin-top: -5px;font-size: 22px;font-weight: bold">ใบเสร็จรับเงิน</p>
	<p style="margin-top: -30px;font-size: 22px;font-weight: bold">สหกรณ์ออมทรัพย์กรมป่าไม้ จำกัด</p>
			<p style="margin-top: -27px;font-size: 18px;">ตู้ปณ. 169 ปณศ. จตุจักร กรุงเทพมหานคร 10900</p>
			<p style="margin-top: -25px;font-size: 18px;">โทร. 02-579-7070 โทรสาร 02-579-9774</p>
			<p style="margin-top: -27px;font-size: 19px;font-weight: bold">www.025798899.com</p>
			</div>
			</div>';

			
			$html .='
			<div style="margin: 25px 0 10px 0;">
				<table style="width: 100%;">
					<tbody>
						<tr>
							<td style="width: 50px;font-size: 18px;">เลขสมาชิก :</td>
							<td style="width: 350px;">' . $header["member_no"] . '</td>
							<td style="width: 50px;font-size: 18px;">เลขที่ใบเสร็จ :</td>
							<td style="width: 101px;">' . $header["slip_no"] . '</td>
						</tr>
						<tr>
							<td style="width: 50px;font-size: 18px;"></td>
							<td style="width: 350px;">' . '' . '</td>
							<td style="width: 50px;font-size: 18px;">วันที่ :</td>
							<td style="width: 101px;">' . $header["slip_date"] . '</td>
						</tr>
						<tr>
							<td style="width: 50px;font-size: 18px;">ชื่อ - สกุล :</td>
							<td style="width: 350px;">' . $header["full_name"] . '</td>
							<td style="width: 50px;font-size: 18px;">สังกัด :</td>
							<td style="width: 101px;">' . $header["member_group"] . '</td>
						</tr>
					</tbody>
				</table>
			</div>
			<div>
				<table style=" border-collapse: collapse; width:100%">
					<tr>
						<th class="center" style="width:350px">รายการชำระ</th>
						<th class="center" style="width:80px">งวดที่</th>
						<th class="center">เงินต้น</th>
						<th class="center">ดอกเบี้ย</th>
						<th class="center">รวมเป็นเงิน</th>
						<th class="center">ยอดเงินคงเหลือ</th>
					</tr>
				</table>

				<div style=" ' . (sizeof($dataReport) <= 12 ? 'height: 270px;' : null) . '  border-left:1px solid; margin-left:-0.5px; border-right:1px solid; margin-right:-0.5px;">';
			if (sizeof($dataReport) <= 12) {
				$html .= '
				<div style="border-right:1px solid; position:absolute; margin-left:360px; height:270px;"></div>
				<div style="border-right:1px solid; position:absolute; margin-left:451px; height:270px;"></div>
				<div style="border-right:1px solid; position:absolute; margin-left:559px; height:270px;"></div>
				<div style="border-right:1px solid; position:absolute; margin-left:676.5px; height:270px;"></div>
				<div style="border-right:1px solid; position:absolute; margin-left:811.5px; height:270px;"></div>';
			}

$html .= '<table style=" border-collapse: collapse; width:100%">';

// Detail
$i = 0;
$bordRight = sizeof($dataReport) > 12 ? 'border-right:1px solid;' : '';
$endRow = -1;
$startRow = -1;
if (sizeof($dataReport) == 19 || sizeof($dataReport) == 20) {
	$endRow = 18;
	$startRow = 19;
}  else if (sizeof($dataReport) >= 21) {
	$endRow = 19;
	$startRow = 20;
}

foreach ($dataReport as $data) {
	$html .= '

				<tr>
					<td class="table-data"  style="width:350.1px; ' . $bordRight . ($i == $endRow ? 'border-bottom:1px solid;' : null) . ($i == $startRow ? 'border-top:1px solid;' : null) . '" >' . ($data["TYPE_DESC"] ?? null) . ' ' . $data["PAY_ACCOUNT"] . '</td>
					<td class="center table-data " style="width:80.3px; ' . $bordRight . $bordRight . ($i == $endRow ? 'border-bottom:1px solid;' : null) . ($i == $startRow ? 'border-top:1px solid;' : null) . '">' . ($data["PERIOD"] ?? '&nbsp;') . '</td>
					<td class="right table-data " style="width:97.8px; ' . $bordRight . $bordRight . ($i == $endRow ? 'border-bottom:1px solid;' : null) . ($i == $startRow ? 'border-top:1px solid;' : null) . '">' . ($data["PRN_BALANCE"] ?? '&nbsp;') . '</td>
					<td class="right table-data " style="width:107px; ' . $bordRight . $bordRight . ($i == $endRow ? 'border-bottom:1px solid;' : null) . ($i == $startRow ? 'border-top:1px solid;' : null) . '">' . ($data["INT_BALANCE"] ?? '&nbsp;') . '</td>
					<td class="right table-data " style="width:124.5px; ' . $bordRight . $bordRight . ($i == $endRow ? 'border-bottom:1px solid;' : null) . ($i == $startRow ? 'border-top:1px solid;' : null) . '">' . ($data["ITEM_PAYMENT"] ?? '&nbsp;') . '</td>
					<td class="right table-data " style="' . ($i == $endRow ? 'border-bottom:1px solid;' : null) . ($i == $startRow ? 'border-top:1px solid;' : null) . '">' . ($data["ITEM_BALANCE"] ?? null) . '</td>
				</tr>
		';
	$sumBalance += $data["ITEM_PAYMENT_NOTFORMAT"];

	$i++;
}
$html .= '	
					</table>
				</div>
				<table style=" border-collapse: collapse; width:100%">
					<tr>
						<td class="center border" style="width:557px;">' . $lib->baht_text($sumBalance) . '</td>
						<td class="border center" style="width:114.5px">รวมเงิน</td>
						<td class="border right" style="width:128px; padding-right:5px;">' . number_format($sumBalance,2) . '</td>
						<td class="border right" style="padding-right:5px;"></td>
					</tr>				
				</table>
			</div>';
	if (sizeof($dataReport) > 15 && sizeof($dataReport) < 19) {
		$html .= '<div class="wrapper-page"></div>';
	}
			
			// หมายเหตุ - ลายเซ็น
	$html .= '
			<div style="display:flex;">
			<div style="width:400px;font-size: 18px;">หมายเหตุ : ใบเสร็จฉบับนี้จะสมบูรณ์ก็ต่อเมื่อสหกรณ์ได้รับเงินครบถ้วน</div>
			<div style="width:100px;margin-left: 570px;display:flex;">
			<img src="../../resource/utility_icon/manager.png" width="120" height="60" style="margin-top:0px;"/>
			<div style="font-size: 18px;margin-left: 0px;margin-top:60px; white-space: nowrap;">(นางวรีย์พรรณ โหมดเทศ) </div>
			</div>
			</div>
			<div style="font-size: 18px;margin-left: 610px;margin-top:-25px;">ผู้จัดการ</div>
			';
			
	
			
	
			
	$dompdf = new Dompdf([
		'fontDir' => realpath('../../resource/fonts'),
		'chroot' => realpath('/'),
		'isRemoteEnabled' => true
	]);

	$dompdf->set_paper('A4', 'landscape');
	$dompdf->load_html($html);
	$dompdf->render();
	$pathfile = __DIR__.'/../../resource/pdf/slipextra';
	if(!file_exists($pathfile)){
		mkdir($pathfile, 0777, true);
	}
	$pathfile = $pathfile.'/'.$header["member_no"].$header["slip_no"].'.pdf';
	$pathfile_show = '/resource/pdf/slipextra/'.urlencode($header["member_no"]).$header["slip_no"].'.pdf?v='.time();
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