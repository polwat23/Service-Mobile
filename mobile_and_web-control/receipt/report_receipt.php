<?php
require_once('../autoload.php');

use Dompdf\Dompdf;

$dompdf = new DOMPDF();

if($lib->checkCompleteArgument(['menu_component','recv_period'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SlipInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$header = array();
		$fetchName = $conmssql->prepare("SELECT MB.MEMB_NAME,MB.MEMB_SURNAME,MP.PRENAME_DESC,MBG.MEMBGROUP_DESC,MBG.MEMBGROUP_CODE
												FROM MBMEMBMASTER MB LEFT JOIN 
												MBUCFPRENAME MP ON MB.PRENAME_CODE = MP.PRENAME_CODE
												LEFT JOIN MBUCFMEMBGROUP MBG ON MB.MEMBGROUP_CODE = MBG.MEMBGROUP_CODE
												WHERE mb.member_no = :member_no");
		$fetchName->execute([
			':member_no' => $member_no
		]);
		$rowName = $fetchName->fetch(PDO::FETCH_ASSOC);
		$header["fullname"] = $rowName["PRENAME_DESC"].$rowName["MEMB_NAME"].' '.$rowName["MEMB_SURNAME"];
		$header["member_group"] = $rowName["MEMBGROUP_CODE"].' '.$rowName["MEMBGROUP_DESC"];
		
		$fetchIntAccum = $conmssql->prepare("SELECT INTEREST_ACCUM FROM KPTEMPRECEIVE WHERE MEMBER_NO = :member_no AND RECV_PERIOD = :recv_period");
		$fetchIntAccum->execute([
			':member_no' => $member_no,
			':recv_period' => $dataComing["recv_period"]
		]);
		$rowIntAccum = $fetchIntAccum->fetch(PDO::FETCH_ASSOC);
		$header["int_accum"] = $rowIntAccum["INTEREST_ACCUM"];
		
		if($lib->checkCompleteArgument(['seq_no'],$dataComing)){
			$getPaymentDetail = $conmssql->prepare("SELECT 
																		CASE kut.system_code 
																		WHEN 'LON' THEN ISNULL(LT.LOANTYPE_DESC,KUT.KEEPITEMTYPE_DESC)
																		WHEN 'DEP' THEN ISNULL(DP.DEPTTYPE_DESC,KUT.KEEPITEMTYPE_DESC)
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
																		kpd.PERIOD,
																		ISNULL(kpd.ITEM_PAYMENT * kut.SIGN_FLAG,0) AS ITEM_PAYMENT,
																		ISNULL(kpd.ITEM_BALANCE,0) AS ITEM_BALANCE,
																		ISNULL(kpd.principal_payment,0) AS PRN_BALANCE,
																		ISNULL(kpd.interest_payment,0) AS INT_BALANCE
																		FROM kpmastreceivedet kpd LEFT JOIN KPUCFKEEPITEMTYPE kut ON 
																		kpd.keepitemtype_code = kut.keepitemtype_code
																		LEFT JOIN lnloantype lt ON kpd.shrlontype_code = lt.loantype_code
																		LEFT JOIN dpdepttype dp ON kpd.shrlontype_code = dp.depttype_code
																		WHERE kpd.member_no = :member_no and kpd.recv_period = :recv_period
																		and kpd.seq_no = :seq_no  and kpd.keepitem_status = '1'
																		ORDER BY kut.SORT_IN_RECEIVE ASC");
			$getPaymentDetail->execute([
				':member_no' => $member_no,
				':recv_period' => $dataComing["recv_period"],
				':seq_no' => $dataComing["seq_no"]
			]);
		}else{
			$getPaymentDetail = $conmssql->prepare("SELECT 
																		CASE kut.system_code 
																		WHEN 'LON' THEN ISNULL(LT.LOANTYPE_DESC,KUT.KEEPITEMTYPE_DESC)
																		WHEN 'DEP' THEN ISNULL(DP.DEPTTYPE_DESC,KUT.KEEPITEMTYPE_DESC)
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
																		kpd.PERIOD,
																		ISNULL(kpd.ITEM_PAYMENT * kut.SIGN_FLAG,0) AS ITEM_PAYMENT,
																		ISNULL(kpd.ITEM_BALANCE,0) AS ITEM_BALANCE,
																		ISNULL(kpd.principal_payment,0) AS PRN_BALANCE,
																		ISNULL(kpd.interest_payment,0) AS INT_BALANCE
																		FROM kpmastreceivedet kpd LEFT JOIN KPUCFKEEPITEMTYPE kut ON 
																		kpd.keepitemtype_code = kut.keepitemtype_code
																		LEFT JOIN lnloantype lt ON kpd.shrlontype_code = lt.loantype_code
																		LEFT JOIN dpdepttype dp ON kpd.shrlontype_code = dp.depttype_code
																		WHERE kpd.member_no = :member_no and kpd.recv_period = :recv_period  and kpd.keepitem_status = '1'
																		ORDER BY kut.SORT_IN_RECEIVE ASC");
			$getPaymentDetail->execute([
				':member_no' => $member_no,
				':recv_period' => $dataComing["recv_period"]
			]);
		}
		$sum_int_period = 0;
		$arrGroupDetail = array();
		while($rowDetail = $getPaymentDetail->fetch(PDO::FETCH_ASSOC)){
			$arrDetail = array();
			$arrDetail["TYPE_DESC"] = $rowDetail["TYPE_DESC"];
			if($rowDetail["TYPE_GROUP"] == 'SHR'){
				$arrDetail["PERIOD"] = $rowDetail["PERIOD"];
			}else if($rowDetail["TYPE_GROUP"] == 'LON'){
				$arrDetail["PAY_ACCOUNT"] = $rowDetail["PAY_ACCOUNT"];
				$arrDetail["PAY_ACCOUNT_LABEL"] = 'เลขสัญญา';
				$arrDetail["PERIOD"] = $rowDetail["PERIOD"];
				if($rowDetail["MONEY_RETURN_STATUS"] == '-99' || $rowDetail["ADJUST_ITEMAMT"] > 0){
					$arrDetail["PRN_BALANCE"] = number_format($rowDetail["ADJUST_PRNAMT"],2);
					$arrDetail["INT_BALANCE"] = number_format($rowDetail["ADJUST_INTAMT"],2);
					$sum_int_period += $rowDetail["INT_BALANCE"];
				}else{
					$arrDetail["PRN_BALANCE"] = number_format($rowDetail["PRN_BALANCE"],2);
					$arrDetail["INT_BALANCE"] = number_format($rowDetail["INT_BALANCE"],2);
					$sum_int_period += $rowDetail["INT_BALANCE"];
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
		$header["int_accum"] += $sum_int_period ;
		$header["int_accum"] = number_format($header["int_accum"],2);
		
		$getDetailKPHeader = $conmssql->prepare("SELECT 
																kpd.RECEIPT_NO,
																kpd.OPERATE_DATE,
																kpd.KEEPING_STATUS
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
		$header["receipt_no"] = TRIM($rowKPHeader["RECEIPT_NO"]);
		$header["operate_date"] = $lib->convertdate($rowKPHeader["OPERATE_DATE"],'D m Y');
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
				.slip-table td {
					padding-left : 10px;
					padding-right : 10px;
				}
			</style>
			<div style="display: flex;text-align: center;position: relative;margin-bottom: 20px;">
				<div style="text-align: left;"><img src="../../resource/logo/logo.png" style="margin: 10px 0 0 5px" alt="" width="80" height="80" /></div>
				<div style="text-align:left;position: absolute;width:100%;margin-left: 140px">';
	if($header["keeping_status"] == '-99' || $header["keeping_status"] == '-9'){
		$html .= '<p style="margin-top: -5px;font-size: 22px;font-weight: bold;color: red;">ยกเลิกใบเสร็จรับเงิน</p>';
	}else{
		$html .= '<p style="margin-top: -5px;font-size: 22px;font-weight: bold">ใบเสร็จรับเงิน</p>';
	}
	$html .= '<p style="margin-top: -30px;font-size: 22px;font-weight: bold">สหกรณ์ออมทรัพย์ครูกรมสามัญศึกษาจังหวัดลำปาง จำกัด</p>
					<p style="margin-top: -27px;font-size: 18px;">เลขที่ 941 ถนนวชิราวุธดำเนิน  ต.พระบาท </p>
					<p style="margin-top: -25px;font-size: 18px;">อ.เมือง จ.ลำปาง 52000</p>
					<p style="margin-top: -25px;font-size: 18px;">โทร.  054-228-407 , 081-961-3481 ,099-272-4130</p>
					<p style="margin-top: -27px;font-size: 19px;font-weight: bold">www.lpgesc.com</p>
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
			<td style="width: 50px;font-size: 18px;">ดอกเบี้ยสะสม :</td>
			<td style="width: 350px;">'.$header["int_accum"].'</td>
			<td style="width: 50px;font-size: 18px;"></td>
			<td style="width: 101px;"></td>
			</tr>
			</tbody>
			</table>
			</div>';
				// Detail
	$html .= '<table class="slip-table" style="
	width: 100%;
	border-collapse: separate;
	border-spacing: -0.5px;">
	<tr style="width: 100%;height: 30px;" class="sub-table">
	<th style="width: 350px;text-align: center;font-size: 18px;font-weight: bold;border : 0.5px solid black;padding-top: 1px;">รายการชำระ</th>
	<th style="width: 100px;text-align: center;font-size: 18px;font-weight: bold;border : 0.5px solid black;margin-left: 355px;padding-top: 1px;">งวดที่</th>
	<th style="width: 110px;text-align: center;font-size: 18px;font-weight: bold;border : 0.5px solid black;margin-left: 465px;padding-top: 1px;">เงินต้น</th>
	<th style="width: 110px;text-align: center;font-size: 18px;font-weight: bold;border : 0.5px solid black;margin-left: 580px;padding-top: 1px;">ดอกเบี้ย</th>
	<th style="width: 120px;text-align: center;font-size: 18px;font-weight: bold;border : 0.5px solid black;margin-left: 700px;padding-top: 1px;">รวมเป็นเงิน</th>
	<th style="text-align: center;font-size: 18px;font-weight: bold;margin-left: 815px;padding-top: 1px;border : 0.5px solid black;">ยอดคงเหลือ</th>
	</tr>
	';
	for($i = 0;$i < sizeof($dataReport); $i++){
		if($i == 0){
			$html .= '<tr style="height: 30px;padding:0px">
			<td style="width: 330px;text-align: left;font-size: 18px;border-right: 0.5px solid black;border-left: 0.5px solid black;">
			<div>'.$dataReport[$i]["TYPE_DESC"].' '.$dataReport[$i]["PAY_ACCOUNT"].'</div>
			</td>
			<td style="width: 80px;text-align: center;font-size: 18px;margin-left: 355px;border-right: 0.5px solid black;">
			<div>'.($dataReport[$i]["PERIOD"] ?? null).'</div>
			</td>
			<td style="width: 90px;text-align: right;font-size: 18px;margin-left: 465px;border-right: 0.5px solid black;">
			<div>'.($dataReport[$i]["PRN_BALANCE"] ?? null).'</div>
			</td>
			<td style="width: 90px;text-align: right;font-size: 18px;margin-left: 580px;border-right: 0.5px solid black;">
			<div>'.($dataReport[$i]["INT_BALANCE"] ?? null).'</div>
			</td>
			<td style="width: 100px;text-align: right;font-size: 18px;margin-left: 700px;border-right: 0.5px solid black;">
			<div>'.($dataReport[$i]["ITEM_PAYMENT"] ?? null).'</div>
			</td>
			<td style="text-align: right;font-size: 18px;margin-left: 814px;border-right: 0.5px solid black;">
			<div>'.($dataReport[$i]["ITEM_BALANCE"] ?? null).'</div>
			</td>
			</tr>';
		}else{
			$html .= '<tr style="border: 0px !important">
			<td style="width: 330px;text-align: left;font-size: 18px;border-right: 0.5px solid black;border-left: 0.5px solid black;">
				<div>'.$dataReport[$i]["TYPE_DESC"].' '.$dataReport[$i]["PAY_ACCOUNT"].'</div>
			</td>
			<td style="width: 80px;text-align: center;font-size: 18px;margin-left: 355px;border-right: 0.5px solid black;">
			<div>'.($dataReport[$i]["PERIOD"] ?? null).'</div>
			</td>
			<td style="width: 90px;text-align: right;font-size: 18px;margin-left: 465px;border-right: 0.5px solid black;">
			<div>'.($dataReport[$i]["PRN_BALANCE"] ?? null).'</div>
			</td>
			<td style="width: 90px;text-align: right;font-size: 18px;margin-left: 580px;border-right: 0.5px solid black;">
			<div>'.($dataReport[$i]["INT_BALANCE"] ?? null).'</div>
			</td>
			<td style="width: 100px;text-align: right;font-size: 18px;margin-left: 700px;border-right: 0.5px solid black;">
			<div>'.($dataReport[$i]["ITEM_PAYMENT"] ?? null).'</div>
			</td>
			<td style="text-align: right;font-size: 18px;margin-left: 814px;border-right: 0.5px solid black;">
			<div>'.($dataReport[$i]["ITEM_BALANCE"] ?? null).'</div>
			</td>
			</tr>';
		}
		$sumBalance += $dataReport[$i]["ITEM_PAYMENT_NOTFORMAT"];
	}
	if(sizeof($dataReport) < 10){
		for($i = 0;$i < (10 - sizeof($dataReport)); $i++){
		$html .= '<tr style="border: 0px !important">
				<td style="width: 330px;text-align: left;font-size: 18px;border-right: 0.5px solid black;border-left: 0.5px solid black;">
					&nbsp;
				</td>
				<td style="width: 80px;text-align: center;font-size: 18px;margin-left: 355px;border-right: 0.5px solid black;">
					&nbsp;
				</td>
				<td style="width: 90px;text-align: right;font-size: 18px;margin-left: 465px;border-right: 0.5px solid black;">
					&nbsp;
				</td>
				<td style="width: 90px;text-align: right;font-size: 18px;margin-left: 580px;border-right: 0.5px solid black;">
					&nbsp;
				</td>
				<td style="width: 100px;text-align: right;font-size: 18px;margin-left: 700px;border-right: 0.5px solid black;">
					&nbsp;
				</td>
				<td style="text-align: right;font-size: 18px;margin-left: 814px;border-right: 0.5px solid black;">
					&nbsp;
				</td>
				</tr>';
		}
	}
			// Footer
			$html .= '<tr style="width: 100%;height: 40px" class="sub-table">
				<td colspan="3" style="width: 600px;text-align:center;height: 30px;font-size: 18px;padding-top: 0px;border : 0.5px solid black;">'.$lib->baht_text($sumBalance).'</td>
				<td style="width: 110px;text-align: center;font-size: 18px;border-right : 0.5px solid black;padding-top: 0px;height:30px;margin-left: 580px;border : 0.5px solid black;">
				รวมเงิน
				</td>
				<td style="width: 120px;text-align: right;border-right: 0.5px solid black;height: 30px;margin-left: 700px;padding-top: 0px;font-size: 18px;border : 0.5px solid black;">'.number_format($sumBalance,2).'</td>
				<td style="text-align: right;font-size: 18px;margin-left: 814px;border: 0.5px solid black;">
					
				</td>
				</tr>
			</table>
			<table>
			<tr>
			<td style="font-size: 18px;vertical-align: top;">หมายเหตุ : ใบรับเงินประจำเดือนจะสมบูรณ์ก็ต่อเมื่อทางสหกรณ์ได้รับเงินที่เรียกเก็บเรียบร้อยแล้ว<br>ติดต่อสหกรณ์ โปรดนำ 1. บัตรประจำตัว 2. ใบเสร็จรับเงิน 3. สลิปเงินเดือนมาด้วยทุกครั้ง
			</td>
			<td style="font-size: 18px;padding-left: 40px;padding-top:10px;">
			<img src="../../resource/utility_icon/signature/mg.png" width="80" height="50" style="margin-top:0px;"/>
			<div style="padding-top: 12px;">ผู้จัดการ</div></td>
			<td style="font-size: 18px;padding-left: 140px;padding-top:10px;">
			<img src="../../resource/utility_icon/signature/fn.png" width="100" height="50" style="margin-top:0px;"/>
			<div style="padding-top: 12px;">เจ้าหน้าที่รับเงิน</div></td>
			</tr>
			</table>
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