<?php
require_once('../autoload.php');

use Dompdf\Dompdf;

$dompdf = new DOMPDF();

if($lib->checkCompleteArgument(['menu_component','recv_period'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SlipInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$recv_year = substr(trim($dataComing["recv_period"]),0,4) - 543;
		$revc_period_raw = $recv_year.substr(trim($dataComing["recv_period"]),-2,2);
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
		
		
		$getSharemasterinfo = $conoracle->prepare("SELECT (sharestk_amt * 10) as SHARE_AMT,(periodshare_amt * 10) as PERIOD_SHARE_AMT,sharebegin_amt
													FROM shsharemaster WHERE member_no = :member_no");
		$getSharemasterinfo->execute([':member_no' => $member_no]);
		$rowMastershare = $getSharemasterinfo->fetch(PDO::FETCH_ASSOC);
		if($rowMastershare){
			$header['BRING_FORWARD'] = number_format($rowMastershare["SHAREBEGIN_AMT"] * 10,2);	
		}
		
		//sum(kptempreceivedet.item_balance) as item_balance , 
		if($lib->checkCompleteArgument(['seq_no'],$dataComing)){
			$getPaymentDetail = $conoracle->prepare("SELECT 
																		CASE kut.system_code 
																		WHEN 'LON' THEN NVL(lt.LOANTYPE_DESC,kut.keepitemtype_desc) 
																		WHEN 'DEP' THEN NVL(dp.DEPTTYPE_DESC,kut.keepitemtype_desc) 
																		ELSE kut.keepitemtype_desc
																		END as TYPE_DESC,
																		kpd.SEQ_NO,
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
																		NVL(kpd.real_payment * kut.SIGN_FLAG,0) AS ITEM_PAYMENT,
																		NVL(kpd.item_balance,0) AS ITEM_BALANCE,
																		NVL(kpd.real_prinpayment,0) AS PRN_BALANCE,
																		NVL(kpd.real_intpayment,0) AS INT_BALANCE,
																		kpd.KEEPITEMTYPE_CODE,
																		kpd.SHRLONTYPE_CODE
																		FROM kpmastreceivedet kpd LEFT JOIN KPUCFKEEPITEMTYPE kut ON 
																		kpd.keepitemtype_code = kut.keepitemtype_code
																		LEFT JOIN lnloantype lt ON kpd.shrlontype_code = lt.loantype_code
																		LEFT JOIN dpdepttype dp ON kpd.shrlontype_code = dp.depttype_code
																		WHERE kpd.member_no = :member_no and kpd.recv_period = :recv_period
																		and kpd.keepitem_status != -9  and kpd.real_payment > 0
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
																		kpd.SEQ_NO,
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
																		NVL(kpd.real_payment * kut.SIGN_FLAG,0) AS ITEM_PAYMENT,
																		NVL(kpd.item_balance,0) AS ITEM_BALANCE,
																		NVL(kpd.real_prinpayment,0) AS PRN_BALANCE,
																		NVL(kpd.real_intpayment,0) AS INT_BALANCE,
																		kpd.KEEPITEMTYPE_CODE,
																		kpd.SHRLONTYPE_CODE
																		FROM kpmastreceivedet kpd LEFT JOIN KPUCFKEEPITEMTYPE kut ON 
																		kpd.keepitemtype_code = kut.keepitemtype_code
																		LEFT JOIN lnloantype lt ON kpd.shrlontype_code = lt.loantype_code
																		LEFT JOIN dpdepttype dp ON kpd.shrlontype_code = dp.depttype_code
																		WHERE kpd.member_no = :member_no and kpd.recv_period = :recv_period
																		and kpd.keepitem_status != -9  and kpd.real_payment > 0
																		ORDER BY kut.SORT_IN_RECEIVE ASC");
			$getPaymentDetail->execute([
				':member_no' => $member_no,
				':recv_period' => $dataComing["recv_period"]
			]);
		}
		$arrGroupDetail = array();
		$sumAmount = 0;
		while($rowDetail = $getPaymentDetail->fetch(PDO::FETCH_ASSOC)){
			$arrDetail = array();
			$arrDetail["TYPE_DESC"] = $rowDetail["TYPE_DESC"];
			$arrDetail["KEY"] = $rowDetail["SHRLONTYPE_CODE"].$rowDetail["KEEPITEMTYPE_CODE"].$rowDetail["PAY_ACCOUNT"];
			if($rowDetail["MONEY_RETURN_STATUS"] == '-99' || $rowDetail["ADJUST_ITEMAMT"] > 0){
				$itemPayment = $rowDetail["ADJUST_ITEMAMT"];
			}else{
				$itemPayment = $rowDetail["ITEM_PAYMENT"];
			}
			if($rowDetail["TYPE_GROUP"] == 'SHR'){
				$arrDetail["PERIOD"] = $rowDetail["PERIOD"];
				$rowDetail["ITEM_BALANCE"] = $rowDetail["ITEM_BALANCE"] ;
				$header['SHARE_AMT'] = number_format($rowDetail["ITEM_BALANCE"],2);
			}else if($rowDetail["TYPE_GROUP"] == 'LON'){
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
				$rowDetail["ITEM_BALANCE"] = $rowDetail["ITEM_BALANCE"];
			}else if($rowDetail["TYPE_GROUP"] == 'DEP'){
				if(rowDetail["ITEM_BALANCE"] == 0){
					$getAccDetail = $conoracle->prepare("SELECT * FROM (SELECT PRNCBAL,SEQ_NO FROM dpdeptstatement 
													WHERE deptaccount_no = :deptaccount_no and 
													deptitemtype_code = 'DTR' and TO_CHAR(operate_date,'YYYYMM') = :operate_date 
													ORDER BY SEQ_NO DESC) WHERE rownum <= 1");
					$getAccDetail->execute([
						':deptaccount_no' => preg_replace("/[^0-9]/", "", $rowDetail["PAY_ACCOUNT"]),
						':operate_date' => $revc_period_raw
					]);
					$rowAccDetail = $getAccDetail->fetch(PDO::FETCH_ASSOC);
					//$rowDetail["ITEM_BALANCE"] = $rowDetail["ITEM_BALANCE"];
				}
				$arrDetail["PAY_ACCOUNT"] = $lib->formataccount(preg_replace("/[^0-9]/", "", $rowDetail["PAY_ACCOUNT"]),$func->getConstant('dep_format'));
				$arrDetail["PAY_ACCOUNT_LABEL"] = 'เลขบัญชี';
			}else if($rowDetail["TYPE_GROUP"] == "OTH"){
				$arrDetail["PAY_ACCOUNT"] = $rowDetail["PAY_ACCOUNT"];
				$arrDetail["TYPE_DESC"] = '';
				if(isset($rowDetail["SEQ_NO"]) && $rowDetail["SEQ_NO"] != "" && $rowDetail["SEQ_NO"] > 0){
					$arrDetail["PERIOD"] = $rowDetail["SEQ_NO"];
				}
				$arrDetail["PAY_ACCOUNT"] = $rowDetail["PAY_ACCOUNT"];
				$arrDetail["PAY_ACCOUNT_LABEL"] = 'จ่าย';
			}
			if($rowDetail["ITEM_BALANCE"] > 0 && $rowDetail["TYPE_GROUP"] != 'DEP'){
				$arrDetail["ITEM_BALANCE"] = number_format($rowDetail["ITEM_BALANCE"],2);
			}
			$arrDetail["ITEM_PAYMENT"] = number_format($itemPayment,2);
			$sumAmount += $itemPayment;
			if(array_search($rowDetail["SHRLONTYPE_CODE"].$rowDetail["KEEPITEMTYPE_CODE"].$rowDetail["PAY_ACCOUNT"],array_column($arrGroupDetail,'KEY')) === False){
				$arrGroupDetail[] = $arrDetail;
			}else{
				$arrGroupDetail[array_search($rowDetail["SHRLONTYPE_CODE"].$rowDetail["KEEPITEMTYPE_CODE"],array_column($arrGroupDetail,'KEY'))]["ITEM_PAYMENT"] = number_format(preg_replace('/,/','',$arrGroupDetail[array_search($rowDetail["SHRLONTYPE_CODE"].$rowDetail["KEEPITEMTYPE_CODE"],array_column($arrGroupDetail,'KEY'))]["ITEM_PAYMENT"]) + $itemPayment,2);
			}
		}
		
		$getReturnAmt = $conoracle->prepare("SELECT SUM(RETOVERPAY_AMT + REMAINSHRRET_AMT) AS REALRETURN_AMT FROM KPMASTRECEIVEDET
											WHERE KEEPITEM_STATUS != -9 AND MEMBER_NO = :member_no AND RECV_PERIOD = :recv_period GROUP BY COOP_ID, KPSLIP_NO, RECV_PERIOD, MEMBER_NO");
		$getReturnAmt->execute([
			':member_no' => $member_no,
			':recv_period' => $dataComing["recv_period"]
		]);
		while($rowReturn = $getReturnAmt->fetch(PDO::FETCH_ASSOC)){
			$arrDetail = array();
			if($rowReturn["REALRETURN_AMT"] > 0){
				$arrDetail["TYPE_DESC"] = "เงินคืน ".number_format($rowReturn["REALRETURN_AMT"],2)." บาท";
				$arrGroupDetail[] = $arrDetail;
			}
			
		}
		$getDetailKPHeader = $conoracle->prepare("SELECT 
																kpd.RECEIPT_NO,
																kpd.RECEIPT_DATE as OPERATE_DATE,
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
		$header["sum_amount"] = $sumAmount;
		$header["receipt_no"] = trim($rowKPHeader["RECEIPT_NO"]);
		$header["operate_date"] = $lib->convertdate($rowKPHeader["OPERATE_DATE"],'D/n/Y');
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
			
			</style>

			<div style="display: flex;text-align: center;position: relative;margin-bottom: 20px;">
			<div style="text-align: left;"><img src="../../resource/logo/logo.jpg" style="margin: 10px 0 0 5px" alt="" width="80" height="80" /></div>
			<div style="text-align:left;position: absolute;width:100%;margin-left: 140px">';
	if($header["keeping_status"] == '-99' || $header["keeping_status"] == '-9'){
		$html .= '<p style="margin-top: -5px;font-size: 22px;font-weight: bold;color: red;">ยกเลิกใบเสร็จรับเงิน</p>';
	}else{
		$html .= '<p style="margin-top: -5px;font-size: 22px;font-weight: bold">ใบเสร็จรับเงิน</p>';
	}
	$html .= '<p style="margin-top: -30px;font-size: 22px;font-weight: bold">สหกรณ์ออมทรัพย์กรมป่าไม้ จำกัด</p>
			<p style="margin-top: -27px;font-size: 18px;">ตู้ปณ. 169 ปณศ. จตุจักร กรุงเทพมหานคร 10900</p>
			<p style="margin-top: -25px;font-size: 18px;">โทร. 02-579-7070 โทรสาร 02-579-9774</p>
			<p style="margin-top: -27px;font-size: 19px;font-weight: bold">www.025798899.com</p>
			</div>
			</div>
			';
			
			
		
	
			
	$html .='
		<div style="margin: 25px 0 10px 0;">
				<table style="width: 100%;">
					<tbody>
						<tr>
							<td style="width: 50px;font-size: 18px;">สังกัด/งบงาน :</td>
							<td style="width: 101px;">'.$header["member_group"].'</td>
							<td style="width: 50px;font-size: 18px;">เลขที่ใบเสร็จ :</td>
							<td style="width: 101px;">'.$header["receipt_no"].'</td>
						</tr>
						<tr>
							<td style="width: 50px;font-size: 18px;">เลขสมาชิก :</td>
							<td style="width: 350px;">'.$header["member_no"].'</td>
							<td style="width: 50px;font-size: 18px;">วันที่ :</td>
							<td style="width: 101px;">'.$header["operate_date"].'</td>
						</tr>
						<tr>
							<td style="width: 50px;font-size: 18px;">ชื่อ - สกุล :</td>
							<td style="width: 350px;">'.$header["fullname"].'</td>
						</tr>
					</tbody>
				</table>
			</div>
			<div>
				<table style=" border-collapse: collapse; width:100%">
					<tr>
						<th class="center" style="width:350px; text-align:left">หุนเรือนหุ้นยกมาต้นปี  &nbsp;&nbsp; '.$header["BRING_FORWARD"].'</th>
						<th class="center" style="width:80px" colspan="3"></th>
						<th class="center" colspan="2">ทุนเรือนหุ้น &nbsp;&nbsp;'.$header["SHARE_AMT"].'</th>
					</tr>
					<tr>
						<th class="center" style="width:350px">รายละเอียดรายการ</th>
						<th class="center" style="width:80px">งวดที่</th>
						<th class="center">เงินต้น</th>
						<th class="center">ดอกเบี้ย</th>
						<th class="center">รวมเป็นเงิน</th>
						<th class="center" style="width:148.5px">ยอดคงเหลือ</th>
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

	foreach($dataReport as $data){
		$html .='

				<tr>
					<td class="table-data"  style="width:350.1px; ' . $bordRight . ($i == $endRow ? 'border-bottom:1px solid;' : null) . ($i == $startRow ? 'border-top:1px solid;' : null) . '" >' . ($data["TYPE_DESC"] ?? '&nbsp;') . ' ' .$data["PAY_ACCOUNT"] . '</td>
					<td class="center table-data " style="width:80.3px; ' . $bordRight . $bordRight . ($i == $endRow ? 'border-bottom:1px solid;' : null) . ($i == $startRow ? 'border-top:1px solid;' : null) . '">' . ($data["PERIOD"] ?? '&nbsp;') . '</td>
					<td class="right table-data " style="width:97.8px; ' . $bordRight . $bordRight . ($i == $endRow ? 'border-bottom:1px solid;' : null) . ($i == $startRow ? 'border-top:1px solid;' : null) . '">' . ($data["PRN_BALANCE"] ?? '&nbsp;') . '</td>
					<td class="right table-data " style="width:107px; ' . $bordRight . $bordRight . ($i == $endRow ? 'border-bottom:1px solid;' : null) . ($i == $startRow ? 'border-top:1px solid;' : null) . '">' . ($data["INT_BALANCE"] ?? '&nbsp;') . '</td>
					<td class="right table-data " style="width:124.5px; ' . $bordRight . $bordRight . ($i == $endRow ? 'border-bottom:1px solid;' : null) . ($i == $startRow ? 'border-top:1px solid;' : null) . '">' . ($data["ITEM_PAYMENT"] ?? '&nbsp;') . '</td>
					<td class="right table-data " style="' . ($i == $endRow ? 'border-bottom:1px solid;' : null) . ($i == $startRow ? 'border-top:1px solid;' : null) . '">' . ($data["ITEM_BALANCE"] ?? null) . '</td>
				</tr>
		';
		$i++;
	}
	
	// Footer
	$html.='	
					</table>
				</div>
				<table style=" border-collapse: collapse; width:100%">
					<tr>
						<td class="center border" style="width:557px;">('.$lib->baht_text($header["sum_amount"]).')</td>
						<td class="border center" style="width:114.5px">รวมเป็นเงิน</td>
						<td class="border right" style="width:128px; padding-right:5px;">'.number_format($header["sum_amount"],2).'</td>
						<td class="border right" style="padding-right:5px;"></td>
					</tr>				
				</table>
			</div>';
		if (sizeof($dataReport) > 15 && sizeof($dataReport) < 19) {
			$html .= '<div class="wrapper-page"></div>';
		}
			// หมายเหตุ - ลายเซ็น
		$html .= '
	<div style="display:flex; position:relative">
		<div style="width:400px;font-size: 18px;">หมายเหตุ : ใบเสร็จฉบับนี้จะสมบูรณ์ก็ต่อเมื่อสหกรณ์ได้รับเงินครบถ้วน</div>
	</div>
	<div>
		<div style="margin-left: 650px; width: 150px;  text-align:center; position:absolute ">
			<img src="../../resource/utility_icon/finance.png" width="120" height="60"/>
			<div style="font-size: 18px;margin-top: 0px; white-space: nowrap; margin-top: -10px;">(นางวิไลวรรณ นิวาสเวช) </div>
				<div>เจ้าหน้าที่ผู้รับเงิน</dvi>
			</div>
		</div>
		<div style="margin-left: 835px; width: 150px;  text-align:center; position:absolute">
			<img src="../../resource/utility_icon/manager.png" width="120" height="60"/>
			<div style="font-size: 18px;margin-left: 0px; white-space: nowrap; margin-top: -10px;">(นางวรีย์พรรณ โหมดเทศ)</div>
				<div>ผู้จัดการ</dvi>
			</div>
		</div>
	</div>';

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
	$pathfile = $pathfile.'/'.$header["receipt_no"].'.pdf';
	$pathfile_show = '/resource/pdf/keeping_monthly/'.$header["receipt_no"].'.pdf?v='.time();
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