<?php
require_once('../autoload.php');

use Dompdf\Dompdf;

$dompdf = new DOMPDF();

if($lib->checkCompleteArgument(['menu_component','slip_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SlipExtraPayment')){
		$member_no = $configAS[$payload["member_no"]] ?? TRIM($payload["member_no"]);
		$arrGroupDetail = array();
		$getHeaderSlip = $conoracle->prepare("SELECT SLIP_DATE,SLIP_AMT FROM CMSHRLONSLIP WHERE slip_no = :slip_no");
		$getHeaderSlip->execute([':slip_no' => $dataComing["slip_no"]]);
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
		$sqlGetMembGrp = $conoracle->prepare("SELECT (B.MEMBGROUP_DESC || ' / ' || A.MEMBGROUP_DESC ) AS MEMBGROUP_CODE_STR 
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
											cld.PERIOD,cld.PRINCIPAL_PAYAMT,cld.INTEREST_PAYAMT,cld.ITEM_PAYAMT
											FROM CMSHRLONSLIPDET cld LEFT JOIN lnloantype lt ON cld.shrlontype_code = lt.loantype_code 
											WHERE TRIM(cld.slip_no) = :slip_no");
		$detailReceipt->execute([':slip_no' => $dataComing["slip_no"]]);
		while($rowDetail = $detailReceipt->fetch(PDO::FETCH_ASSOC)){
			$arrayData = array();
			$arrayData["PAY_ACCOUNT"] = $rowDetail["LOANCONTRACT_NO"];
			$arrayData["TYPE_DESC"] = $rowDetail["TYPE_DESC"];
			$arrayData["PERIOD"] = $rowDetail["PERIOD"];
			$arrayData["PRIN_PAY"] = number_format($rowDetail["PRINCIPAL_PAYAMT"],2);
			$arrayData["INT_PAY"] = number_format($rowDetail["INTEREST_PAYAMT"],2);
			$arrayData["ITEM_PAY"] = number_format($rowDetail["ITEM_PAYAMT"],2);
			$arrGroupDetail[] = $arrayData;
		}
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
	$html = '
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
			  padding: 0 30px;
			  font: size 18px;
			}
			.sub-table div{
				padding : 5px;
			}
			table{
				border-collapse: collapse;
			  }
			th{
				border:1px solid;
				padding:0px 10px;
				background-color:#D3D3D3;
			}
			td{
				border:1px solid;
				padding:0px 10px;
				text-align:center;
				background-color:#D3D3D3;
			}
			.textAlignVer{
		
				-webkit-transform: rotate(-90deg); 
				-moz-transform: rotate(-90deg); 
				transform: rotate(-90deg); 
				white-space:nowrap;
				font-weight:bold;
			}
			.detail-color{
				color:#7A7878;
			}
			</style>';
	//ขนาดของใบเสร็จ
	$html .= '<div style="border:0.5px solid red ; width:13.37cm; height:15.2cm; margin:0 auto;">';

	//ส่วนหัว
	$html .= '
	<div style="margin-top:20px;">
		<div style="position:absolute;">
			<div style=" text-align: left; position:absolute; top:0; left:10"><img src="../../resource/logo/logo.jpg" alt="" width="60" height="0"></div>
		</div>
		<div style="font-size: 22px;font-weight: bold; text-align:right; margin-right:30px; height: 45px;  padding-top:15px;">สหกรณ์ออมทรัพย์ข้าราชการกระทรวงศึกษาธิการ จํากัด</div>
	</div>
	<div style="padding-left:271px;">
		<table>
			<tr>
				<th>เลขที่ใบรับเงิน</th>
				<th>เลขที่ใบเสร็จรับเงิน</th>
			</tr>
			<tr>
				<td>'.$header["slip_no"].'</td>
				<td>'.$header["slip_date"].'</td>
			</tr>
		</table>
	</div>
	<div >
		<div style="padding-left:150px;">
			<span style="font-weight:bold;">หน่วย</span>
			<div style="position:absolute; padding-left:10px;" class="detail-color">'.$header["member_group_code"].'</div>
			<div style="position:absolute; padding-left:50px;" class="detail-color" >'.$header["member_group"].'</div>
		</div>
		<div style="margin-left:10px;">
			<span style="font-weight:bold;">ได้รับเงินจาก</span>
			<div style="position:absolute; padding-left;10px; class="detail-color">'.$header["full_name"].'</div>
			<div style="position:absolute; font-weight:bold; padding-left:250px;">เลขที่สมาชิก</div>
			<div style="position:absolute; padding-left:320px; class="detail-color">'.$header["member_no"].'</div>
		</div>
	</div>

	<div style="margin-top:10px; text-align:center; font-weight:bold; font-size: 22px;">
		ใบรับเงิน/รับชำระหนี้ก่อนกำหนด/ใบรับเงินค่าหุ้น		
	</div>
	';

	//ข้อมูล
	$html .= '<div style="position:absolute; top:248px;  width:465px; margin-left:11px ">';
	for($i = 0;$i < sizeof($dataReport); $i++){
		$html .= '
		<div style="display:flex;width: 100%; height:20px;" class="detail-color">
			<div style="width: 80px;text-align: left;padding-left:3px; white-space: nowrap; overflow: hidden;  ">'.$dataReport[$i]["TYPE_DESC"].'</div>
			<div style="width: 70px;text-align: center;  margin-left: 90px; 1px solid red;">'.$dataReport[$i]["PAY_ACCOUNT"].'</div>
			<div style="width: 40px;text-align: center; margin-left: 128; ">'.$dataReport[$i]["PERIOD"].'</div>
			<div style="width: 70px;text-align: right;  margin-left: 221px;">'.$dataReport[$i]["PRIN_PAY"].'</div>
			<div style="width: 70px;text-align: right; margin-left: 300px;">'.$dataReport[$i]["INT_PAY"].'</div>
			<div style="width: 75px;text-align: right; margin-left: 380px;">'.$dataReport[$i]["ITEM_PAY"].'</div>
		</div>
		';
	}
	$html .= '</div>';


	// ส่วนท้าย
	$html .= '
		<div style="margin-left:11px; width:465px; border:1px solid black; height:268px; margin-top:5px;">
			<div style="display:flex;width: 100%;height: 19px;" class="sub-table">
				<div style="border-bottom: 1px solid black;">&nbsp;</div>
				<div style="width: 80px;text-align: center;font-size: 16px;font-weight: bold;border-right : 1px solid black;padding-top: 1px;">รายการ</div>
				<div style="width: 70px;text-align: center;font-size: 16px;font-weight: bold;border-right : 1px solid black;margin-left: 90px;padding-top: 1px;">หนังสือกู้ที่</div>
				<div style="width: 40px;text-align: center;font-size: 16px;font-weight: bold;border-right : 1px solid black;margin-left: 128;padding-top: 1px;">งวดที่</div>
				<div style="width: 70px;text-align: center;font-size: 16px;font-weight: bold;border-right : 1px solid black;margin-left: 221px;padding-top: 1px;">เงินต้น</div>
				<div style="width: 70px;text-align: center;font-size: 16px;font-weight: bold;border-right : 1px solid black;margin-left: 300px;padding-top: 1px;">ดอกเบี้ย</div>
				<div style="width: 75px;text-align: center;font-size: 16px;font-weight: bold; margin-left: 380px;padding-top: 0px;">จำนวนเงิน</div>
			</div>
			<div style="display:flex;height: 30px;padding:0px; ">
				<div style="width: 90px;border-right: 1px solid black; height: 250px;">&nbsp;</div>
				<div style="width: 80px;border-right: 1px solid black; height: 250px; margin-left: 90px;">&nbsp;</div>
				<div style="width: 50px;border-right: 1px solid black; height: 250px; margin-left: 170.7px;">&nbsp;</div>
				<div style="width: 50px;border-right: 1px solid black; height: 250px; margin-left: 251px;">&nbsp;</div>
				<div style="width: 50px;border-right: 1px solid black; height: 250px; margin-left: 330px;">&nbsp;</div>
			</div>
		</div>

		<div style="display:flex">
			<div style="border-left:1px solid black; border-right:1px solid black; border-bottom:1px solid black; width: 296px; margin-left:11px; height: 30px; padding-left: 5px; background-color:#D3D3D3">
				('.$lib->baht_text($header["slip_amt"]).')
			</div>
			<div style="width: 78px;  height: 30 px; margin-left:235; text-align:center; font-weight:bold;">
				รวมเงิน
			</div>
			<div style=" width: 85px;  height: 30 px; margin-left:294; text-align:center; font-weight:bold; border-bottom:1px solid black; background-color:#D3D3D3;">
				'.number_format($header["slip_amt"],2).'
			</div>
			<div style="margin-left:294; margin-top:60px;  width: 84px;  height: 30 px; border-bottom:1px solid black; border-left:1px solid black; border-right:1px solid black;"></div>
		</div>
		<div style="position:absolute; top:297px; left:418px;  " class="textAlignVer">
			(ใบรับเงินฉบับนี้จะสมบูรณ์เมื่อได้รับเงินจากสมาชิกเข้าบัญชีเรียบร้อยแล้ว)
		</div>
		<div>
			<div style="position:absolute;  left:120px; top:530px">
				<div style="display:flex;">
					<div style="margin-left:250px">ลงซื่อ</div>
					<div style="margin-left:435px">ผู้จัดการ</div>
				</div>
				<div style="position: absolute;  top:-10px; right:-20px">
					<img src="../../resource/utility_icon/signature/manager.png" width="80" height="50" >
				</div>
			</div>
		</div>
	';

	$html .= '</div>';
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
	$pathfile = $pathfile.'/'.$header["member_no"].$header["slip_no"].'.pdf';
	$pathfile_show = '/resource/pdf/cremation/'.urlencode($header["member_no"]).$header["slip_no"].'.pdf?v='.time();
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
