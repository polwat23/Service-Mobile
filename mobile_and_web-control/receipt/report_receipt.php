<?php
require_once('../autoload.php');

use Dompdf\Dompdf;

if($lib->checkCompleteArgument(['menu_component','recv_period'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SlipInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
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
		if($lib->checkCompleteArgument(['seq_no'],$dataComing)){
			$getPaymentDetail = $conoracle->prepare("SELECT 
																		CASE kut.system_code
																		WHEN 'LON' THEN NVL(lt.LOANTYPE_DESC,kut.keepitemtype_desc) 
																		WHEN 'DEP' THEN NVL(dp.DEPTTYPE_DESC,kut.keepitemtype_desc) 
																		ELSE kut.keepitemtype_desc
																		END as TYPE_DESC,
																		kut.keepitemtype_grp as TYPE_GROUP,
																		kpd.MONEY_RETURN_STATUS,
																		kpd.ADJUST_ITEMAMT,
																		kpd.ADJUST_PRNAMT,
																		kpd.ADJUST_INTAMT,
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
																		LEFT JOIN lnloantype lt ON kpd.shrlontype_code = lt.loantype_code
																		LEFT JOIN dpdepttype dp ON kpd.shrlontype_code = dp.depttype_code
																		WHERE kpd.member_no = :member_no and kpd.recv_period = :recv_period
																		and kpd.seq_no = :seq_no
																		ORDER BY kut.SORT_IN_RECEIVE ASC");
			$getPaymentDetail->execute([
				':member_no' => $member_no,
				':recv_period' => $dataComing["recv_period"],
				':seq_no' => $dataComing["seq_no"]
			]);
		}else{
			$getPaymentDetail = $conoracle->prepare("SELECT 
																		CASE kut.system_code 
																		WHEN 'LON' THEN NVL(lt.LOANTYPE_DESC,kut.keepitemtype_desc) 
																		WHEN 'DEP' THEN NVL(dp.DEPTTYPE_DESC,kut.keepitemtype_desc) 
																		ELSE kut.keepitemtype_desc
																		END as TYPE_DESC,
																		kut.keepitemtype_grp as TYPE_GROUP,
																		kpd.MONEY_RETURN_STATUS,
																		kpd.ADJUST_ITEMAMT,
																		kpd.ADJUST_PRNAMT,
																		kpd.ADJUST_INTAMT,
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
																		LEFT JOIN lnloantype lt ON kpd.shrlontype_code = lt.loantype_code
																		LEFT JOIN dpdepttype dp ON kpd.shrlontype_code = dp.depttype_code
																		WHERE kpd.member_no = :member_no and kpd.recv_period = :recv_period
																		ORDER BY kut.SORT_IN_RECEIVE ASC");
			$getPaymentDetail->execute([
				':member_no' => $member_no,
				':recv_period' => $dataComing["recv_period"]
			]);
		}
		$arrGroupDetail = array();
		$intinPeriod = 0;
		$shareinPeriod = 0;
		while($rowDetail = $getPaymentDetail->fetch(PDO::FETCH_ASSOC)){
			$arrDetail = array();
			$arrDetail["TYPE_DESC"] = $rowDetail["TYPE_DESC"];
			if($rowDetail["TYPE_GROUP"] == 'SHR'){
				$shareinPeriod += $rowDetail["ITEM_PAYMENT"];
				$arrDetail["PERIOD"] = $rowDetail["PERIOD"];
			}else if($rowDetail["TYPE_GROUP"] == 'LON'){
				$intinPeriod += $rowDetail["INT_BALANCE"];
				$arrDetail["PAY_ACCOUNT"] = $rowDetail["PAY_ACCOUNT"];
				$arrDetail["PAY_ACCOUNT_LABEL"] = 'เลขสัญญา';
				$arrDetail["PERIOD"] = $rowDetail["PERIOD"];
				if($rowDetail["MONEY_RETURN_STATUS"] == '-99' || $rowDetail["ADJUST_ITEMAMT"] > 0){
					$arrDetail["PRN_BALANCE"] = number_format($rowDetail["ADJUST_PRNAMT"],2);
					$arrDetail["INT_BALANCE"] = number_format($rowDetail["ADJUST_INTAMT"],2);
				}else{
					$arrDetail["PRN_BALANCE"] = number_format($rowDetail["PRN_BALANCE"],2);
					$arrDetail["INT_BALANCE"] = number_format($rowDetail["INT_BALANCE"],2);
				}
			}else if($rowDetail["TYPE_GROUP"] == 'DEP'){
				$arrDetail["PAY_ACCOUNT"] = $lib->formataccount($rowDetail["PAY_ACCOUNT"],$func->getConstant('dep_format'));
				$arrDetail["PAY_ACCOUNT_LABEL"] = 'เลขบัญชี';
			}else if($rowDetail["TYPE_GROUP"] == "OTH"){
				$arrDetail["PAY_ACCOUNT"] = $rowDetail["PAY_ACCOUNT"];
				$arrDetail["PAY_ACCOUNT_LABEL"] = 'จ่าย';
			}
			if($rowDetail["MONEY_RETURN_STATUS"] == '-99' || $rowDetail["ADJUST_ITEMAMT"] > 0){
				$arrDetail["ITEM_PAYMENT"] = number_format($rowDetail["ADJUST_ITEMAMT"],2);
				$arrDetail["ITEM_PAYMENT_NOTFORMAT"] = $rowDetail["ADJUST_ITEMAMT"];
			}else{
				$arrDetail["ITEM_PAYMENT"] = number_format($rowDetail["ITEM_PAYMENT"],2);
				$arrDetail["ITEM_PAYMENT_NOTFORMAT"] = $rowDetail["ITEM_PAYMENT"];
			}
			$arrDetail["ITEM_BALANCE"] = number_format($rowDetail["ITEM_BALANCE"],2);
			$arrGroupDetail[] = $arrDetail;
		}
		$getDetailKPHeader = $conoracle->prepare("SELECT 
																kpd.RECEIPT_NO,
																kpd.OPERATE_DATE,
																kpd.KEEPING_STATUS,
																kpd.SHARESTK_VALUE,
																kpd.INTEREST_ACCUM
																FROM kpmastreceive kpd
																WHERE kpd.member_no = :member_no and kpd.recv_period = :recv_period");
		$getDetailKPHeader->execute([
			':member_no' => $member_no,
			':recv_period' => $dataComing["recv_period"]
		]);
		$rowKPHeader = $getDetailKPHeader->fetch(PDO::FETCH_ASSOC);
		$header["keeping_status"] = $rowKPHeader["KEEPING_STATUS"];
		$header["recv_period"] = $lib->convertperiodkp(TRIM($dataComing["recv_period"]));
		$header["member_no"] = $payload["member_no"];
		$header["sharestk_value"] = number_format($rowKPHeader["SHARESTK_VALUE"] + $shareinPeriod,2);
		$header["interest_accum"] = number_format($rowKPHeader["INTEREST_ACCUM"] + $intinPeriod,2);
		$header["receipt_no"] = TRIM($rowKPHeader["RECEIPT_NO"]);
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
			<div style="text-align: left;"><img src="../../resource/logo/logo.jpg" style="margin: 10px 0 0 5px" alt="" width="80" height="80" /></div>
			<div style="text-align:left;position: absolute;width:100%;margin-left: 140px">';
	if($header["keeping_status"] == '-99' || $header["keeping_status"] == '-9'){
		$html .= '<p style="margin-top: -5px;font-size: 22px;font-weight: bold;color: red;">ยกเลิกใบเสร็จรับเงิน</p>';
	}else{
		$html .= '<p style="margin-top: -5px;font-size: 22px;font-weight: bold">ใบเสร็จรับเงิน</p>';
	}
	$html .= '<p style="margin-top: -30px;font-size: 22px;font-weight: bold">สหกรณ์ออมทรัพย์สาธารณสุขเชียงราย จำกัด</p>
			<p style="margin-top: -27px;font-size: 18px;">1039/74 ถนนร่วมจิตถวาย ต.เวียง อ.เมือง จ.เชียงราย 57000</p>
			<p style="margin-top: -25px;font-size: 18px;">โทร. ฝ่ายบริหารทั่วไป 086-451-9488, ฝ่ายสินเชื่อ  086-451-9187</p>
			<p style="margin-top: -27px;font-size: 19px;font-weight: bold">www.cricoop.com</p>
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
			<tr>
			<td style="width: 50px;font-size: 18px;">ทุนเรือนหุ้น :</td>
			<td style="width: 350px;">'.$header["sharestk_value"].'</td>
			<td style="width: 50px;font-size: 18px;">ดอกเบี้ยสะสม :</td>
			<td style="width: 101px;">'.$header["interest_accum"].'</td>
			</tr>
			</tbody>
			</table>
			</div>
			<div style="border: 0.5px solid black;width: 100%; height: 325px;">
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
	$html .= '<div style="width: 100%;height: 260px" class="sub-table">';
	for($i = 0;$i < sizeof($dataReport); $i++){
		if($i == 0){
			$html .= '<div style="display:flex;height: 30px;padding:0px">
			<div style="width: 350px;border-right: 0.5px solid black;height: 250px;">&nbsp;</div>
			<div style="width: 100px;border-right: 0.5px solid black;height: 250px;margin-left: 355px;">&nbsp;</div>
			<div style="width: 110px;border-right: 0.5px solid black;height: 270px;margin-left: 465px;">&nbsp;</div>
			<div style="width: 110px;border-right: 0.5px solid black;height: 270px;margin-left: 580px;">&nbsp;</div>
			<div style="width: 120px;border-right: 0.5px solid black;height: 270px;margin-left: 700px;">&nbsp;</div>
			<div style="width: 350px;text-align: left;font-size: 18px">
				<div>'.$dataReport[$i]["TYPE_DESC"].' '.$dataReport[$i]["PAY_ACCOUNT"].'</div>
			</div>
			<div style="width: 100px;text-align: center;font-size: 18px;margin-left: 355px;">
			<div>'.($dataReport[$i]["PERIOD"] ?? null).'</div>
			</div>
			<div style="width: 110px;text-align: right;font-size: 18px;margin-left: 465px;">
			<div>'.($dataReport[$i]["PRN_BALANCE"] ?? null).'</div>
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
			<div>'.($dataReport[$i]["PRN_BALANCE"] ?? null).'</div>
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
			<div style="width:200px;margin-left: 550px;display:flex;">
			<img src="../../resource/utility_icon/signature/manager.jpg" width="100" height="50" style="margin-top:10px;"/>
			</div>
			<div style="width:200px;margin-left: 750px;display:flex;">
			<img src="../../resource/utility_icon/signature/fn_slipmonth.png" width="100" height="50" style="margin-top:10px; margin-left:20px;"/>
			</div>
			</div>
			<div style="font-size: 18px;margin-left: 580px;margin-top:-100px;">ผู้จัดการ</div>
			<div style="font-size: 18px;margin-left: 780px;margin-top:-90px;">เจ้าหน้าที่รับเงิน</div>
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