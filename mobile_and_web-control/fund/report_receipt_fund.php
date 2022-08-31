<?php
require_once('../autoload.php');

use Dompdf\Dompdf;

if ($lib->checkCompleteArgument(['menu_component'], $dataComing)) {
    if ($func->check_permission($payload["user_type"], $dataComing["menu_component"], 'FundInfo')) {
        $member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
        $header = array();
		if ($dataComing["deptslip_no"] == 'register') {
			$getPaymentDetail = $conoracle->prepare("
			SELECT 
				concat (SUBSTR(WB.FUNDSLIPBRANCH_NO,1,4),concat('/',SUBSTR(WB.FUNDSLIPBRANCH_NO,5,8))) as FUNDSLIPBRANCH_NO ,
				WD.SEQ_NO,   
				WT.DEPTITEMTYPE_DESC ,
				WD.PRNCSLIP_AMT,   
				WS.DEPTSLIP_AMT,
				ftreadtbath( WS.DEPTSLIP_AMT) as total_desc,
				WS.FUNDSLIP_NO,   
				WS.FUNDSLIP_DATE,   
				WM.WFACCOUNT_NAME as NAME,      
				CC.COOPBRANCH_DESC,    
				WM.MEMBER_NO,       
				WQ.FUNDACCOUNT_NO,
				WR.ROUND_REGIS
			FROM 
				WCFUNDSLIP WS
				JOIN WCREQFUND WQ ON (WQ.FUNDREQUEST_DOCNO = WS.FUNDACCOUNT_NO AND WQ.FUNDTYPE_CODE = WS.FUNDTYPE_CODE)
				JOIN WCFUNDSLIPDET WD ON (WD.FUNDSLIP_NO = WS.FUNDSLIP_NO AND WD.FUNDTYPE_CODE = WS.FUNDTYPE_CODE AND WD.COOP_ID = WS.COOP_ID )
				JOIN WCFUNDSLIPBRANCH WB ON ( WB.FUNDSLIP_NO =  WS.FUNDSLIP_NO AND WB.FUNDTYPE_CODE = WS.FUNDTYPE_CODE AND WB.COOP_ID = WS.COOP_ID)
				JOIN WCDEPTMASTER WM ON (WM.DEPTACCOUNT_NO = WQ.DEPTACCOUNT_NO)
				JOIN CMUCFCOOPBRANCH CC ON (WS.COOP_ID = CC.COOP_ID)
				JOIN WCUCFFUNDITEMTYPE WT ON (WD.DEPTITEMTYPE_CODE = WT.DEPTITEMTYPE_CODE)
				LEFT JOIN WCUCFFUNDROUND WR ON (WR.FUNDOPEN_DATE = WQ.FUNDOPEN_DATE AND WR.FUNDTYPE_CODE = WS.FUNDTYPE_CODE)		 
			WHERE 
				WS.FUNDTYPE_CODE = :fundtype_code 
				AND WQ.APPROVE_STATUS = 1
				AND TRIM(WQ.DEPTACCOUNT_NO) = :member_no
				ORDER BY WD.SEQ_NO");
			$getPaymentDetail->execute([
				':member_no' => $member_no,
				':fundtype_code' => $dataComing["fundtype_code"]
			]);

		}else {
			$getPaymentDetail = $conoracle->prepare("
			SELECT 
				WT.DEPTITEMTYPE_DESC,
				WD.PRNCSLIP_AMT,
				WS.DEPTSLIP_AMT,
				WS.FUNDSLIP_NO,   
				WS.FUNDSLIP_DATE, 
				WM.WFACCOUNT_NAME as NAME,     
				CB.COOPBRANCH_DESC,    
				WM.MEMBER_NO,   
				WS.FUNDACCOUNT_NO

			FROM WCFUNDSLIP WS 
				JOIN WCFUNDSLIPDET WD ON (WD.FUNDSLIP_NO = WS.FUNDSLIP_NO AND WD.COOP_ID = WS.COOP_ID AND WD.FUNDTYPE_CODE = WS.FUNDTYPE_CODE)
				JOIN WCDEPTMASTER WM ON (WM.DEPTACCOUNT_NO = WS.DEPTACCOUNT_NO)
				JOIN CMUCFCOOPBRANCH CB ON (WS.COOP_ID = CB.COOP_ID)
				JOIN WCUCFFUNDITEMTYPE WT ON (WD.DEPTITEMTYPE_CODE = WT.DEPTITEMTYPE_CODE)

			WHERE
				WS.FUNDSLIP_NO = :deptslip_no
				AND TRIM(WS.DEPTACCOUNT_NO) = :member_no
				AND WS.FUNDTYPE_CODE = :fundtype_code");
			$getPaymentDetail->execute([
				':member_no' => $member_no,
				':deptslip_no' => $dataComing["deptslip_no"],
				':fundtype_code' => $dataComing["fundtype_code"]
			]);
		}
        
        $arrGroupDetail = array();
        while ($rowDetail = $getPaymentDetail->fetch(PDO::FETCH_ASSOC)) {
            $arrDetail = array();
            $header["fundslip_date"] = $lib->convertdate($rowDetail["FUNDSLIP_DATE"], 'D m Y');
            $header["name"] = $rowDetail["NAME"];
            $header["fundslip_no"] = $rowDetail["FUNDSLIP_NO"];
            $header["fundaccount_no"] = $rowDetail["FUNDACCOUNT_NO"];
            $header["member_no"] = $rowDetail["MEMBER_NO"];
			$header["coopbranch_desc"] = $rowDetail["COOPBRANCH_DESC"];
			$header["total_desc"] = $rowDetail["TOTAL_DESC"] ?? "";
			$header["round_regis"] = $rowDetail["ROUND_REGIS"] ?? "";
			
			if ($dataComing["fundtype_code"] == '001') {
				$arrDetail["slip_desc"] = $rowDetail["DEPTITEMTYPE_DESC"]." (ล้านที่ 2)";
			} else {
				$arrDetail["slip_desc"] = $rowDetail["DEPTITEMTYPE_DESC"]." (ล้านที่ 3)";
			}
            $arrDetail["deptslip_amt"] = number_format($rowDetail["PRNCSLIP_AMT"], 2);
            $arrDetail["amt"] = $rowDetail["PRNCSLIP_AMT"];
            $arrGroupDetail[] = $arrDetail;
        }
        $header["deptslip_no"] = $dataComing["deptslip_no"];
        $arrayPDF = GenerateReport($arrGroupDetail, $header, $lib);
        if ($arrayPDF["RESULT"]) {

            if ($forceNewSecurity == true) {
                $arrayResult['REPORT_URL'] = $config["URL_SERVICE"] . "/resource/get_resource?id=" . hash("sha256", $arrayPDF["PATH"]);
                $arrayResult["REPORT_URL_TOKEN"] = $lib->generate_token_access_resource($arrayPDF["PATH"], $jwt_token, $config["SECRET_KEY_JWT"]);
            } else {
                $arrayResult['REPORT_URL'] = $config["URL_SERVICE"] . $arrayPDF["PATH"];
            }
			$arrayResult['test'] = TRUE;
            $arrayResult['RESULT'] = $arrGroupDetail;
            require_once('../../include/exit_footer.php');
        } else {
            $filename = basename(__FILE__, '.php');
            $logStruc = [
                ":error_menu" => $filename,
                ":error_code" => "WS0044",
                ":error_desc" => "สร้าง PDF ไม่ได้ " . "\n" . json_encode($dataComing),
                ":error_device" => $dataComing["channel"] . ' - ' . $dataComing["unique_id"] . ' on V.' . $dataComing["app_version"]
            ];
            $log->writeLog('errorusage', $logStruc);
            $message_error = "สร้างไฟล์ PDF ไม่ได้ " . $filename . "\n" . "DATA => " . json_encode($dataComing);
            $lib->sendLineNotify($message_error);
            $arrayResult['RESPONSE_CODE'] = "WS0044";
            $arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
            $arrayResult['RESULT'] = FALSE;
            require_once('../../include/exit_footer.php');
        }
    } else {
        $arrayResult['RESPONSE_CODE'] = "WS0006";
        $arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
        $arrayResult['RESULT'] = FALSE;
        http_response_code(403);
        require_once('../../include/exit_footer.php');
    }
} else {
    $filename = basename(__FILE__, '.php');
    $logStruc = [
        ":error_menu" => $filename,
        ":error_code" => "WS4004",
        ":error_desc" => "ส่ง Argument มาไม่ครบ " . "\n" . json_encode($dataComing),
        ":error_device" => $dataComing["channel"] . ' - ' . $dataComing["unique_id"] . ' on V.' . $dataComing["app_version"]
    ];
    $log->writeLog('errorusage', $logStruc);
    $message_error = "ไฟล์ " . $filename . " ส่ง Argument มาไม่ครบมาแค่ " . "\n" . json_encode($dataComing);
    $lib->sendLineNotify($message_error);
    $arrayResult['RESPONSE_CODE'] = "WS4004";
    $arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
    $arrayResult['RESULT'] = FALSE;
    http_response_code(400);
    require_once('../../include/exit_footer.php');
}

function GenerateReport($dataReport, $header, $lib){
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
		<div style="position:absolute; top:-25px; "><img src="../../resource/logo/fund_logo.jpg" alt="" width="85" ></div>
		<div class="bold nowrap" style="position:absolute; left:550px" >เลขที่ : ' . ($header["fundslip_no"] ?? null) . ' </div>
		<div class="bold center" style="font-size:11pt; margin-top: -35px;">ต้นฉบับ</div>
		<div class="center bold" style="font-size:18pt;">ใบเสร็จรับเงิน</div>
		<div class="center bold">กองทุนสวัสดิการสมาชิกของสหกรณ์ออมทรัพย์สาธารณสุขไทย(กสธท.)</div>
		';

    if (empty($header["round_regis"])) {
		$html .= '
			<div class="center bold">เลขที่ 199/9 อาคารเพชรสะพานบุญ ชั้น4 หมู่ที่ 2 ถนนครอินทร์ ตำบลสีทอง </div>
			<div class="center bold">อำเภอบางกรวย จังหวัดนนทบุรี 11130</div>
			<div class="center bold">โทรศัพท์  0 2496 1350-57 โทรสาร 0 2496 1358</div>
			';
	} else {
		$html .= '
			<div class="center bold">ศูนย์ประสานงานสหกรณ์ออมทรัพย์'.($header["coopbranch_desc"] ?? null).'</div>
			<hr>
			<div class="center bold">สำนักงานใหญ่ : 199/9 อาคารเพชรสะพานบุญ ชั้น4 หมู่ที่ 2 ถนนครอินทร์ ตำบลสีทอง อำเภอบางกรวย จังหวัดนนทบุรี 11130</div>
			<div class="center bold">โทรศัพท์  0 2496 1350-57 โทรสาร 0 2496 1358</div>
			';
	}

    $html .= '
		<table style="width: 100%;">
			<tbody>
			<tr>
				<td></td>
				<td></td>
				<td style="margin-left:540px">วันที่ : ' . ($header["fundslip_date"] ?? null) . '</td>
			</tr>
			<tr>
				<td style="margin-left:20px;">
					ได้รับเงินจาก : ' . ($header["name"] ?? null) . '
				</td>
				<td style="margin-left:280px;">
					เลขสมาชิกสหกรณ์ : ' . ($header["member_no"] ?? null) . '
				</td>';
		
	if (empty($header["round_regis"])) {
		$class_td_boder = "border";
	}
	
    $html .= '
				<td style="margin-left:461px;">กองทุนเลขทะเบียนที่ : ' . $header["fundaccount_no"] . '</td>
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
						<td class="border-right ' . $class_td_boder . '">' . ($dataReport[$i]["slip_desc"] ?? '&nbsp;') . '</td>
						<td class="right border-right ' . $class_td_boder . '">' . (number_format($dataReport[$i]["amt"], 2) ?? '&nbsp;') . '</td>
					</tr>';
        $total += ($dataReport[$i]["amt"] ?? 0);
    }

    $html .= '	
					<tr>
						<td colspan="2" class="border-top">
							<div style="clear: both;"></div>';
	if (isset($header["round_regis"]) && $header["round_regis"] == !"") {
	$html .= '
								<div style="float: left;">ค่าสมัครรอบ ' . ($header["round_regis"] ?? null) . '</div>
								<div class="center" style="float: left; width: 60%;">( '.($header["total_desc"] ?? null).' )</div>
								';
	} else {
		$html .= '				<div style="float: left;">&nbsp;</div>
								<div class="center" style="float: left; width: 50%;">&nbsp;</div>';
	}
	
    $html .= '					<div style="float: right;">รวมเป็นเงิน</div>
						</td>
						<td class="border right">
							' . number_format($total, 2) . '
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		
		<div>
			<div style="position:absolute;">
				<div >
					<div style="position:absolute; top:20px; margin-left:310px; "><img src="../../resource/logo/fund_logo.jpg" alt="" width="75" ></div>
				</div>
				<div style="margin-left:480px;">
					<img src="../../resource/utility_icon/signature/fund_finance.png" width="100"  height="50" style="margin-top:10px; margin-left:70px;"/>
					<div class="border-top" style="margin-left:30px; width:180px;">&nbsp;</div>
				</div>
				<div class="center" style="margin-top:-20px; margin-left:510px; width:180px;">
					(นางสาวกัลยรัตน์ กล้าเวช)
				</div>
				<div class="center" style=" margin-left:510px; width:180px;">
					เจ้าหน้าที่การเงิน
				</div>
			</div>
			<div>
				<div>
					<img src="../../resource/utility_icon/signature/fund_treasurer.png" width="100"  height="50" style="margin-top:10px; margin-left:70px;"/>
					<div class="border-top" style="margin-left:30px; width:180px;">&nbsp;</div>
				</div>
				<div class="center" style="margin-top:-20px; margin-left:30px; width:180px;">
					(นายพรเพท ล้อมพรม)
				</div>
				<div class="center" style=" margin-left:30px; width:180px;">
					เหรัญญิกกองทุนฯ
				</div>
			</div>
		</div>
		<div style="margin-left:10px;margin-top:10px;">';
    if (empty($header["round_regis"]) && $header["round_regis"] == "") {
		$html .= '<b>หมายเหตุ</b> : ใบรับเงินของกองทุนฯ จะสมบูรณ์ต่อเมื่อมีลายมือชื่อเหรัญญิกกองทุนและผู้รับเงิน  ';
	} else {
		$html .= '<b>หมายเหตุ</b> : ใบรับเงินของกองทุนฯ จะสมบูรณ์ต่อเมื่อมีลายมือชื่อเหรัญญิกกองทุนฯ/ผู้มีอำนาจลงนามและผู้รับเงิน  โดยประทับตรากองทุนฯ ';
	}

    $html .= '
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
    $pathfile = __DIR__ . '/../../resource/pdf/fund';
    if (!file_exists($pathfile)) {
        mkdir($pathfile, 0777, true);
    }
    $pathfile = $pathfile . '/' . (trim($header["fundaccount_no"]) ?? '') .'_'. (trim($header["fundslip_no"]) ?? '') . '.pdf';
    $pathfile_show = '/resource/pdf/fund/' . (trim($header["fundaccount_no"]) ?? '') .'_'. (trim($header["fundslip_no"]) ?? '') . '.pdf?v=' . time();
    $arrayPDF = array();
    $output = $dompdf->output();
    if (file_put_contents($pathfile, $output)) {
        $arrayPDF["RESULT"] = TRUE;
    } else {
        $arrayPDF["RESULT"] = FALSE;
    }
    $arrayPDF["PATH"] = $pathfile_show;
    return $arrayPDF;
}