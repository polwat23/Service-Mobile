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
		$fetchName = $conmssql->prepare("SELECT MB.MEMB_NAME,MB.MEMB_SURNAME,MP.PRENAME_DESC,MBG.MEMBGROUP_DESC,MBG.MEMBGROUP_CODE
												FROM MBMEMBMASTER MB LEFT JOIN 
												MBUCFPRENAME MP ON MB.PRENAME_CODE = MP.PRENAME_CODE
												LEFT JOIN MBUCFMEMBGROUP MBG ON MB.MEMBGROUP_CODE = MBG.MEMBGROUP_CODE
												WHERE mb.member_no = :member_no");
		$fetchName->execute([
			':member_no' => $member_no
		]);
		$rowName = $fetchName->fetch(PDO::FETCH_ASSOC);
		
		
		$fetchIntAccum = $conmssql->prepare("SELECT INTEREST_ACCUM FROM KPMASTRECEIVE WHERE MEMBER_NO = :member_no AND RECV_PERIOD = :recv_period");
		$fetchIntAccum->execute([
			':member_no' => $member_no,
			':recv_period' => $dataComing["recv_period"]
		]);
		$rowIntAccum = $fetchIntAccum->fetch(PDO::FETCH_ASSOC);
		
		$header["fullname"] = $rowName["PRENAME_DESC"].' '.$rowName["MEMB_NAME"].' '.$rowName["MEMB_SURNAME"];
		$header["member_group"] = $rowName["MEMBGROUP_DESC"];
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
																		kpd.SEQ_NO,
																		ISNULL(kpd.ITEM_PAYMENT * kut.SIGN_FLAG,0) AS ITEM_PAYMENT,
																		ISNULL(kpd.ITEM_BALANCE,0) AS ITEM_BALANCE,
																		ISNULL(kpd.principal_payment,0) AS PRN_BALANCE,
																		ISNULL(kpd.interest_payment,0) AS INT_BALANCE
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
																		kpd.SEQ_NO,
																		ISNULL(kpd.ITEM_PAYMENT * kut.SIGN_FLAG,0) AS ITEM_PAYMENT,
																		ISNULL(kpd.ITEM_BALANCE,0) AS ITEM_BALANCE,
																		ISNULL(kpd.principal_payment,0) AS PRN_BALANCE,
																		ISNULL(kpd.interest_payment,0) AS INT_BALANCE
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
		$sum_int = 0;
		while($rowDetail = $getPaymentDetail->fetch(PDO::FETCH_ASSOC)){
			$arrDetail = array();
			$arrDetail["TYPE_DESC"] = $rowDetail["TYPE_DESC"];
			if($rowDetail["TYPE_GROUP"] == 'SHR'){
				$arrDetail["PERIOD"] = $rowDetail["PERIOD"];
			}else if($rowDetail["TYPE_GROUP"] == 'LON'){
				$arrDetail["CONTRACT_NO"] = $rowDetail["PAY_ACCOUNT"];
				$arrDetail["PAY_ACCOUNT_LABEL"] = 'เลขสัญญา';
				$arrDetail["PERIOD"] = $rowDetail["PERIOD"];
				if($rowDetail["MONEY_RETURN_STATUS"] == '-99' || $rowDetail["ADJUST_ITEMAMT"] > 0){
					$arrDetail["PRN_BALANCE"] = number_format($rowDetail["ADJUST_PRNAMT"],2);
					$arrDetail["INT_BALANCE"] = number_format($rowDetail["ADJUST_INTAMT"],2);
					$sum_int += ($rowDetail["ADJUST_INTAMT"] ?? 0);
				}else{
					$arrDetail["PRN_BALANCE"] = number_format($rowDetail["PRN_BALANCE"],2);
					$arrDetail["INT_BALANCE"] = number_format($rowDetail["INT_BALANCE"],2);
					$sum_int += ($rowDetail["INT_BALANCE"] ?? 0);
				}
			}else if($rowDetail["TYPE_GROUP"] == 'DEP'){
			
				$getAccDetail = $conmssql->prepare("select PRNCBAL from dpdeptstatement 
												where  deptaccount_no = :deptaccount_no and 
												deptitemtype_code = 'DTM' and CONVERT(varchar(6),operate_date,112) = :operate_date");
				$getAccDetail->execute([
					':deptaccount_no' => $rowDetail["PAY_ACCOUNT"],
					':operate_date' => $revc_period_raw
				]);
				$rowAccDetail = $getAccDetail->fetch(PDO::FETCH_ASSOC);
			
				$arrDetail["PAY_ACCOUNT"] = $lib->formataccount($rowDetail["PAY_ACCOUNT"],$func->getConstant('dep_format'));
				$arrDetail["PAY_ACCOUNT_LABEL"] = 'เลขบัญชี';
				$rowDetail["ITEM_BALANCE"] = $rowAccDetail["PRNCBAL"];
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
			if($rowDetail["ITEM_BALANCE"] > 0){
				$arrDetail["ITEM_BALANCE"] = number_format($rowDetail["ITEM_BALANCE"],2);
			}
			$arrGroupDetail[] = $arrDetail;
		}
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
		
		$header["interest_accum"] = number_format((($rowIntAccum["INTEREST_ACCUM"] ?? 0) + $sum_int),2);
		$header["keeping_status"] = $rowKPHeader["KEEPING_STATUS"];
		$header["recv_period"] = $lib->convertperiodkp(TRIM($dataComing["recv_period"]));
		$header["member_no"] = $payload["member_no"];
		$header["receipt_no"] = TRIM($rowKPHeader["RECEIPT_NO"]);
		$header["operate_date"] = $lib->convertdate($rowKPHeader["OPERATE_DATE"],'D/n/Y');
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
				.theme-color {
					color: #70ad47;
				}

				.theme-table {
					border-spacing: 0;
					border: 2px solid #70ad47;
					border-radius: 10px;
					width: 100%
				}

				.theme-table-bordered,
				.theme-table-bordered th,
				.theme-table-bordered td {
					border: 2px solid #70ad47;
					border-collapse: collapse;
				}
				.theme-table-bordered th {
					border: 2px solid #70ad47;
					background-color: #c5e0b3;
					font-weight: bold;
					border-collapse: collapse;
					height: 40px;
					color: #70ad47;
				}
			</style>
			<div>
				 <div style="text-align: center;">
					<div style="display: inline-block;vertical-align: top;position: absolute;left: 40px;">
						<img src="../../resource/logo/logo_slip.jpg" style="width:80px" />
					</div>
					<div style="display: inline-block;">
						<div style="text-align:center;">
							<div class="theme-color" style="font-weight: bold;font-size: 14pt;padding-top: 12px;">
								สหกรณ์ออมทรัพย์มหาวิทยาลัยแม่โจ้ จำกัด
							</div>
						</div>
						<div style="text-align:center;">
							<div class="theme-color" style="font-weight: bold;font-size: 14pt;padding-top: 12px;">
								ใบรับเงิน
							</div>
						</div>
					</div>
				</div>
			</div>
			<div style="padding-top: 30px;">
				<div style="display: inline-block;width: 55%;padding-right: 10px;">
					<div>
						<table class="theme-table">
							<tr>
								<td class="theme-color" style="white-space: nowrap;padding-right: 10px;padding-left: 10px;border-right: 2px solid #70ad47;border_bottom: 2px solid #70ad47;">
									เลขที่</td>
								<td style="width: 100%;padding-right: 10px;padding-left: 10px;">'.$header["receipt_no"].'</td>
							</tr>
							<tr>
								<td class="theme-color" style="white-space: nowrap;padding-right: 10px;padding-left: 10px;border-right: 2px solid #70ad47;border_bottom: 2px solid #70ad47;">
									ได้รับเงินจาก</td>
								<td style="width: 100%;padding-right: 10px;padding-left: 10px;">'.$header["fullname"].'</td>
							</tr>
							<tr>
								<td class="theme-color" style="white-space: nowrap;padding-right: 10px;padding-left: 10px;border-right: 2px solid #70ad47;">
									หน่วยงาน</td>
								<td style="width: 100%;padding-right: 10px;padding-left: 10px;">'.$header["member_group"].'
								</td>
							</tr>
						</table>
					</div>
				</div>
				<div style="display: inline-block;width: 42%;padding-left: 4px">
					<div>
						<table class="theme-table">
							<tr>
								<td class="theme-color" style="white-space: nowrap;padding-right: 10px;padding-left: 10px;border-right: 2px solid #70ad47;border_bottom: 2px solid #70ad47;">
									วันที่</td>
								<td style="width: 100%;padding-right: 10px;padding-left: 10px;">'.$header["operate_date"].'</td>
							</tr>
							<tr>
								<td class="theme-color" style="white-space: nowrap;padding-right: 10px;padding-left: 10px;border-right: 2px solid #70ad47;border_bottom: 2px solid #70ad47;">
									เลขทะเบียน</td>
								<td style="width: 100%;padding-right: 10px;padding-left: 10px;">'.$header["member_no"].'</td>
							</tr>
							<tr>
								<td class="theme-color" style="white-space: nowrap;padding-right: 10px;padding-left: 10px;border-right: 2px solid #70ad47;">
									ดอกเบี้ยสะสม</td>
								<td style="width: 100%;text-align: right;padding-right: 10px;padding-left: 10px;">'.$header["interest_accum"].'
								</td>
							</tr>
						</table>
					</div>
				</div>
            </div>';
				// Detail
	$html .= ' <div style="margin-top: 16px;">
				<div style="position: relative;">
					<table class="theme-table-bordered" style="width: 100%">
                    <tr>
                        <th>รายการ/สัญญา</th>
                        <th>งวดที่</th>
                        <th>เงินต้น</th>
                        <th>ดอกเบี้ย</th>
                        <th>จำนวนเงิน</th>
                        <th>ยอดคงเหลือ</th>
                    </tr>';
	for($i = 0;$i < sizeof($dataReport); $i++){
	
		$html .= '
				<tr>
					<td style="white-space: nowrap;padding-right: 10px;font-size: small;">'.($dataReport[$i]["CONTRACT_NO"] ? ($dataReport[$i]["CONTRACT_NO"].' '): '').''.$dataReport[$i]["TYPE_DESC"].'</td>
					<td style="padding-right: 10px;padding-left: 10px;text-align: center;">'.($dataReport[$i]["PERIOD"] ?? null).'</td>
					<td style="padding-right: 10px;padding-left: 10px;text-align: right;">'.$dataReport[$i]["PRN_BALANCE"].'</td>
					<td style="padding-right: 10px;padding-left: 10px;text-align: right;">'.$dataReport[$i]["INT_BALANCE"].'</td>
					<td style="padding-right: 10px;padding-left: 10px;text-align: right;">'.$dataReport[$i]["ITEM_PAYMENT"].'</td>
					<td style="padding-right: 10px;padding-left: 10px;text-align: right;">'.$dataReport[$i]["ITEM_BALANCE"].'</td>
				</tr>'
				;
					
		$sumBalance += $dataReport[$i]["ITEM_PAYMENT_NOTFORMAT"];
	}
	$html .= '
				<tr>
					<td style="white-space: nowrap;padding-right: 10px;font-size: small;">&nbsp;</td>
					<td style="padding-right: 10px;padding-left: 10px;text-align: center;">&nbsp;</td>
					<td style="padding-right: 10px;padding-left: 10px;text-align: right;">&nbsp;</td>
					<td style="padding-right: 10px;padding-left: 10px;text-align: right;">&nbsp;</td>
					<td style="padding-right: 10px;padding-left: 10px;text-align: right;">&nbsp;</td>
					<td style="padding-right: 10px;padding-left: 10px;text-align: right;">&nbsp;</td>
				</tr>
				<tr>
					<td colspan="4" style="white-space: nowrap;padding-right: 10px;padding-left: 10px;">'.$lib->baht_text($sumBalance).'</td>
					<td style="padding-right: 10px;padding-left: 10px;text-align: right;">'.number_format($sumBalance, 2).'</td>
					<td style="padding-right: 10px;padding-left: 10px;"></td>
				</tr>
				</table>
				<img src="../../resource/logo/logo_slip.jpg" style="width:90px;position: absolute;opacity: 0.1;left: 50%;top: '.((sizeof($dataReport)/2)*32+50).'px; transform: translate(-50%, -50%);" />
			</div>';
			// Footer
	$html .= '<div class="theme-color" style="margin-top: 16px;position: relative;white-space: nowrap;">
						ผู้จัดการ / เหรัญญิก ................................................................................ เจ้าหน้าที่ผู้รับเงิน ...............................................................................
					<img src="../../resource/utility_icon/signature/mg.jpg" height="28" style="margin-top:10px;position: absolute;left: 150px;top: -22px"/>
                </div>
                <div style="margin-top: 12px; border: 2px solid #70ad47; background-color: #c5e0b3;border-radius: 10px;height: 70px;">

                </div>
                <div class="theme-color" style="margin-top: 24px;font-weight: bold;text-align: center;">
                    ใบรับเงินประจำเดือนจะสมบูรณ์ต่อเมื่อสหกรณ์ได้รับเงินที่เรียกเก็บครบถ้วนแล้ว
                </div>
            </div>';

	$dompdf = new Dompdf([
		'fontDir' => realpath('../../resource/fonts'),
		'chroot' => realpath('/'),
		'isRemoteEnabled' => true
	]);

	$dompdf->set_paper('A4');
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