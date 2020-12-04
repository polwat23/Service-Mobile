<?php
require_once('../autoload.php');

use Dompdf\Dompdf;

$dompdf = new DOMPDF();

if($lib->checkCompleteArgument(['menu_component','recv_period'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'PaymentMonthlyDetail')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$date_process = $func->getConstant('date_process_kp');
		$recv_now = (date('Y') + 543).date('m');
		$dateNow = date('d');
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
		$header["fullname"] = $rowName["PRENAME_DESC"].$rowName["MEMB_NAME"].' '.$rowName["MEMB_SURNAME"];
		$header["member_group"] = $rowName["MEMBGROUP_CODE"].' '.$rowName["MEMBGROUP_DESC"];
		$arrGroupDetail = array();
		if($recv_now == trim($dataComing["recv_period"])){
			if($dateNow >= $date_process){
				$qureyKpDetail = "SELECT 
											kut.keepitemtype_desc as TYPE_DESC,
											kut.keepitemtype_grp as TYPE_GROUP,
											case kut.keepitemtype_grp 
												WHEN 'DEP' THEN kpd.description
												WHEN 'LON' THEN kpd.loancontract_no
											ELSE kpd.description END as PAY_ACCOUNT,
											kpd.period,
											NVL(kpd.ITEM_PAYMENT * kut.SIGN_FLAG,0) AS ITEM_PAYMENT,
											NVL(kpd.ITEM_BALANCE,0) AS ITEM_BALANCE,
											NVL(kpd.principal_payment,0) AS PRN_BALANCE,
											NVL(kpd.interest_payment,0) AS INT_BALANCE
											FROM kpmastreceivedet kpd LEFT JOIN KPUCFKEEPITEMTYPE kut ON 
											kpd.keepitemtype_code = kut.keepitemtype_code
											WHERE kpd.member_no = :member_no and kpd.recv_period = :recv_period
											ORDER BY kut.sort_in_receive ASC";
			}else{
				$qureyKpDetail = "SELECT 
											kut.keepitemtype_desc as TYPE_DESC,
											kut.keepitemtype_grp as TYPE_GROUP,
											case kut.keepitemtype_grp 
												WHEN 'DEP' THEN kpd.description
												WHEN 'LON' THEN kpd.loancontract_no
											ELSE kpd.description END as PAY_ACCOUNT,
											kpd.period,
											NVL(kpd.ITEM_PAYMENT * kut.SIGN_FLAG,0) AS ITEM_PAYMENT,
											NVL(kpd.ITEM_BALANCE,0) AS ITEM_BALANCE,
											NVL(kpd.principal_payment,0) AS PRN_BALANCE,
											NVL(kpd.interest_payment,0) AS INT_BALANCE
											FROM kptempreceivedet kpd LEFT JOIN KPUCFKEEPITEMTYPE kut ON 
											kpd.keepitemtype_code = kut.keepitemtype_code
											WHERE kpd.member_no = :member_no and kpd.recv_period = :recv_period
											ORDER BY kut.sort_in_receive ASC";
			}
		}else{
			if(trim($dataComing["recv_period"]) > $recv_now){
				$qureyKpDetail = "SELECT 
											kut.keepitemtype_desc as TYPE_DESC,
											kut.keepitemtype_grp as TYPE_GROUP,
											case kut.keepitemtype_grp 
												WHEN 'DEP' THEN kpd.description
												WHEN 'LON' THEN kpd.loancontract_no
											ELSE kpd.description END as PAY_ACCOUNT,
											kpd.period,
											NVL(kpd.ITEM_PAYMENT * kut.SIGN_FLAG,0) AS ITEM_PAYMENT,
											NVL(kpd.ITEM_BALANCE,0) AS ITEM_BALANCE,
											NVL(kpd.principal_payment,0) AS PRN_BALANCE,
											NVL(kpd.interest_payment,0) AS INT_BALANCE
											FROM kptempreceivedet kpd LEFT JOIN KPUCFKEEPITEMTYPE kut ON 
											kpd.keepitemtype_code = kut.keepitemtype_code
											WHERE kpd.member_no = :member_no and kpd.recv_period = :recv_period
											ORDER BY kut.sort_in_receive ASC";
			}else{
				$qureyKpDetail = "SELECT 
											kut.keepitemtype_desc as TYPE_DESC,
											kut.keepitemtype_grp as TYPE_GROUP,
											case kut.keepitemtype_grp 
												WHEN 'DEP' THEN kpd.description
												WHEN 'LON' THEN kpd.loancontract_no
											ELSE kpd.description END as PAY_ACCOUNT,
											kpd.period,
											NVL(kpd.ITEM_PAYMENT * kut.SIGN_FLAG,0) AS ITEM_PAYMENT,
											NVL(kpd.ITEM_BALANCE,0) AS ITEM_BALANCE,
											NVL(kpd.principal_payment,0) AS PRN_BALANCE,
											NVL(kpd.interest_payment,0) AS INT_BALANCE
											FROM kpmastreceivedet kpd LEFT JOIN KPUCFKEEPITEMTYPE kut ON 
											kpd.keepitemtype_code = kut.keepitemtype_code
											WHERE kpd.member_no = :member_no and kpd.recv_period = :recv_period
											ORDER BY kut.sort_in_receive ASC";
			}
		}
		$getDetailKP = $conoracle->prepare($qureyKpDetail);
		$getDetailKP->execute([
			':member_no' => $member_no,
			':recv_period' => $dataComing["recv_period"]
		]);
		while($rowDetail = $getDetailKP->fetch(PDO::FETCH_ASSOC)){
			$arrDetail = array();
			$arrDetail["TYPE_DESC"] = $rowDetail["TYPE_DESC"];			
			if($rowDetail["TYPE_GROUP"] == 'SHR'){
				$arrDetail["PERIOD"] = $rowDetail["PERIOD"];
				$arrDetail["ITEM_BALANCE"] = number_format($rowDetail["ITEM_BALANCE"],2);
			}else if($rowDetail["TYPE_GROUP"] == 'LON'){
				$arrDetail["PAY_ACCOUNT"] = $rowDetail["PAY_ACCOUNT"];
				$arrDetail["PERIOD"] = $rowDetail["PERIOD"];
				$arrDetail["ITEM_BALANCE"] = number_format($rowDetail["ITEM_BALANCE"],2);
				$arrDetail["ITEM_PAYAMT"] = number_format($rowDetail["PRN_BALANCE"],2);
				$arrDetail["INT_BALANCE"] = number_format($rowDetail["INT_BALANCE"],2);
			}else if($rowDetail["TYPE_GROUP"] == 'DEP'){
				$arrDetail["PAY_ACCOUNT"] = $lib->formataccount($rowDetail["PAY_ACCOUNT"],$func->getConstant('dep_format'));
			}else if($rowDetail["TYPE_GROUP"] == "OTH"){
				$arrDetail["TYPE_DESC"] = $rowDetail["TYPE_DESC"]." ( ".$rowDetail["PAY_ACCOUNT"]." )";
			}
			$arrDetail["ITEM_PAYMENT"] = number_format($rowDetail["ITEM_PAYMENT"],2);
			$arrDetail["ITEM_PAYMENT_NOTFORMAT"] = $rowDetail["ITEM_PAYMENT"];
			$arrGroupDetail[] = $arrDetail;
		}
		if($recv_now == trim($dataComing["recv_period"])){
			if($dateNow > $date_process){
				$qureyKpHeader = "SELECT 
											kpd.RECEIPT_NO,
											kpd.OPERATE_DATE
											FROM kpmastreceive kpd
											WHERE kpd.member_no = :member_no and kpd.recv_period = :recv_period";
			}else{
				$qureyKpHeader = "SELECT 
													kpd.RECEIPT_NO,
													kpd.OPERATE_DATE
													FROM kptempreceive kpd
													WHERE kpd.member_no = :member_no and kpd.recv_period = :recv_period";
			}
		}else{
			if(trim($dataComing["recv_period"]) > $recv_now){
				$qureyKpHeader = "SELECT 
												kpd.RECEIPT_NO,
												kpd.OPERATE_DATE
												FROM kptempreceive kpd
												WHERE kpd.member_no = :member_no and kpd.recv_period = :recv_period";
			}else{
				$qureyKpHeader = "SELECT 
												kpd.RECEIPT_NO,
												kpd.OPERATE_DATE
												FROM kpmastreceive kpd
												WHERE kpd.member_no = :member_no and kpd.recv_period = :recv_period";
			}
		}
		$getDetailKPHeader = $conoracle->prepare($qureyKpHeader);
		$getDetailKPHeader->execute([
			':member_no' => $member_no,
			':recv_period' => $dataComing["recv_period"]
		]);
		$rowKPHeader = $getDetailKPHeader->fetch(PDO::FETCH_ASSOC);
		$header["recv_period"] = $lib->convertperiodkp($dataComing["recv_period"]);
		$header["member_no"] = $payload["member_no"];
		$header["receipt_no"] = $rowKPHeader["RECEIPT_NO"];
		$header["operate_date"] = $lib->convertdate($rowKPHeader["OPERATE_DATE"],'D m Y');
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
	$sumBalance = 0;
	$html = '<style>
				@font-face {
				  font-family: THSarabun;
				  src: url(../../resource/fonts/THSarabun.ttf);
				}
				@font-face {
					font-family: "THSarabun";
					src: url(../../resource/fonts/THSarabun Bold.ttf);
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
			</style>
			<div style="display: flex;text-align: center;position: relative;margin-bottom: 20px;">
				<div style="text-align: left;"><img src="../../resource/logo/logo.jpg" style="margin: 10px 0 0 5px" alt="" width="80" height="80" /></div>
				<div style="text-align:left;position: absolute;width:100%;margin-left: 140px">
				<p style="margin-top: -5px;font-size: 22px;font-weight: bold">ใบเสร็จรับเงิน</p>
				<p style="margin-top: -30px;font-size: 22px;font-weight: bold">สหกรณ์ออมทรัพย์ครูมุกดาหาร จำกัด</p>
				<p style="margin-top: -27px;font-size: 18px;">30/1 ถนนชยางกูร ก. ตำบลมุกดาหาร</p>
				<p style="margin-top: -25px;font-size: 18px;">อำเภอเมืองมุกดาหาร จังหวัดมุกดาหาร</p>
				<p style="margin-top: -25px;font-size: 18px;">โทร : 042-611-454</p>
				<p style="margin-top: -27px;font-size: 19px;font-weight: bold">www.muktc.com</p>
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
			</tbody>
			</table>
			</div>
			<div style="border: 0.5px solid black;width: 100%; height: 255px;">
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
	$html .= '<div style="width: 100%;height: 190px" class="sub-table">';
	for($i = 0;$i < sizeof($dataReport); $i++){
		if($i == 0){
			$html .= '<div style="display:flex;height: 30px;padding:0px">
			<div style="width: 350px;border-right: 0.5px solid black;height: 180px;">&nbsp;</div>
			<div style="width: 100px;border-right: 0.5px solid black;height: 180px;margin-left: 355px;">&nbsp;</div>
			<div style="width: 110px;border-right: 0.5px solid black;height: 200px;margin-left: 465px;">&nbsp;</div>
			<div style="width: 110px;border-right: 0.5px solid black;height: 200px;margin-left: 580px;">&nbsp;</div>
			<div style="width: 120px;border-right: 0.5px solid black;height: 200px;margin-left: 700px;">&nbsp;</div>
			<div style="width: 350px;text-align: left;font-size: 18px">
			<div>'.$dataReport[$i]["TYPE_DESC"].' '.$dataReport[$i]["PAY_ACCOUNT"].'</div>
			</div>
			<div style="width: 100px;text-align: center;font-size: 18px;margin-left: 355px;">
			<div>'.($dataReport[$i]["PERIOD"] ?? null).'</div>
			</div>
			<div style="width: 110px;text-align: right;font-size: 18px;margin-left: 465px;">
			<div>'.($dataReport[$i]["ITEM_PAYAMT"] ?? null).'</div>
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
			<div>'.($dataReport[$i]["ITEM_PAYAMT"] ?? null).'</div>
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
			<img src="../../resource/utility_icon/signature/receive_money.png" width="100" height="50" style="margin-top:10px;"/>
			</div>
			</div>
			<div style="font-size: 18px;margin-left: 730px;margin-top:-60px;">เหรัญญิก</div>
			';

	$dompdf = new DOMPDF();
	$dompdf->set_paper('A4', 'landscape');
	$dompdf->load_html($html);
	$dompdf->render();
	$pathfile = __DIR__.'/../../resource/pdf/keeping_monthly';
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