<?php
require_once('../autoload.php');

use Dompdf\Dompdf;

$dompdf = new DOMPDF();

if($lib->checkCompleteArgument(['menu_component','recv_period'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SlipInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$header = array();
		$fetchName = $conoracle->prepare("SELECT mb.memb_name,mb.memb_surname,mp.prename_desc,mbg.MEMBGROUP_DESC,mbg.MEMBGROUP_CODE,mb.current_coopid,cm.coop_name
												FROM mbmembmaster mb LEFT JOIN 
												mbucfprename mp ON mb.prename_code = mp.prename_code
												LEFT JOIN mbucfmembgroup mbg ON mb.MEMBGROUP_CODE = mbg.MEMBGROUP_CODE
												LEFT JOIN cmcoopmaster cm ON mb.coop_id = cm.coop_id
												WHERE mb.member_no = :member_no");
		$fetchName->execute([
			':member_no' => $member_no
		]);
		$rowName = $fetchName->fetch(PDO::FETCH_ASSOC);
		$header["fullname"] = $rowName["PRENAME_DESC"].$rowName["MEMB_NAME"].' '.$rowName["MEMB_SURNAME"];
		$header["member_group"] = $rowName["MEMBGROUP_CODE"].' '.$rowName["MEMBGROUP_DESC"];
		$header["coop_name"] = $rowName["COOP_NAME"];
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
																		NVL(kpd.interest_payment,0) AS INT_BALANCE,
																		TRIM(lt.loantype_code) as LOANTYPE_CODE
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
																		NVL(kpd.interest_payment,0) AS INT_BALANCE,
																		TRIM(lt.loantype_code) as LOANTYPE_CODE
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
		$share_stk = null;
		while($rowDetail = $getPaymentDetail->fetch(PDO::FETCH_ASSOC)){
			$arrDetail = array();
			$arrDetail["TYPE_DESC"] = $rowDetail["TYPE_DESC"];
			if($rowDetail["TYPE_GROUP"] == 'SHR'){
				$shareinPeriod += $rowDetail["ITEM_PAYMENT"];
				$share_stk = $rowDetail["ITEM_BALANCE"];
				$arrDetail["ITEM_BALANCE"] = number_format($rowDetail["ITEM_BALANCE"],2);
				$arrDetail["PERIOD"] = $rowDetail["PERIOD"];
			}else if($rowDetail["TYPE_GROUP"] == 'LON'){
				$intinPeriod += $rowDetail["INT_BALANCE"];
				$arrDetail["PAY_ACCOUNT"] = $rowDetail["PAY_ACCOUNT"];
				$arrDetail["PAY_ACCOUNT_LABEL"] = 'เลขสัญญา';
				$getLoanPeriod = $conoracle->prepare("SELECT PERIOD_PAYAMT FROM lncontmaster WHERE loancontract_no = :loancontract_no");
				$getLoanPeriod->execute([':loancontract_no' => $rowDetail["PAY_ACCOUNT"]]);
				$rowLoan = $getLoanPeriod->fetch(PDO::FETCH_ASSOC);
				$arrDetail["PERIOD"] = $rowDetail["PERIOD"].' / '.$rowLoan["PERIOD_PAYAMT"];
				
				if($rowDetail["MONEY_RETURN_STATUS"] == '-99' || $rowDetail["ADJUST_ITEMAMT"] > 0){
					$arrDetail["PRN_BALANCE"] = number_format($rowDetail["ADJUST_PRNAMT"],2);
					$arrDetail["INT_BALANCE"] = number_format($rowDetail["ADJUST_INTAMT"],2);
				}else{
					$arrDetail["PRN_BALANCE"] = number_format($rowDetail["PRN_BALANCE"],2);
					$arrDetail["INT_BALANCE"] = number_format($rowDetail["INT_BALANCE"],2);
				}
				$arrDetail["ITEM_BALANCE"] = number_format($rowDetail["ITEM_BALANCE"],2);
			}else if($rowDetail["TYPE_GROUP"] == 'DEP'){
				$getBalance = $conoracle->prepare("SELECT PRNCBAL FROM dpdeptmaster WHERE deptaccount_no = :deptaccount_no");
				$getBalance->execute([':deptaccount_no' => $rowDetail["PAY_ACCOUNT"]]);
				$rowBalance = $getBalance->fetch(PDO::FETCH_ASSOC);
				$arrDetail["ITEM_BALANCE"] = number_format($rowBalance["PRNCBAL"],2);
				$arrDetail["PAY_ACCOUNT"] = $lib->formataccount($rowDetail["PAY_ACCOUNT"],$func->getConstant('dep_format'));
				$arrDetail["PAY_ACCOUNT_LABEL"] = 'เลขบัญชี';
			}else if($rowDetail["TYPE_GROUP"] == "OTH"){
				$arrDetail["PAY_ACCOUNT"] = $rowDetail["PAY_ACCOUNT"];
				$arrDetail["PAY_ACCOUNT_LABEL"] = 'จ่าย';
				$arrDetail["ITEM_BALANCE"] = number_format($rowDetail["ITEM_BALANCE"],2);
			}
			if($rowDetail["MONEY_RETURN_STATUS"] == '-99' || $rowDetail["ADJUST_ITEMAMT"] > 0){
				$arrDetail["ITEM_PAYMENT"] = number_format($rowDetail["ADJUST_ITEMAMT"],2);
				$arrDetail["ITEM_PAYMENT_NOTFORMAT"] = $rowDetail["ADJUST_ITEMAMT"];
			}else{
				$arrDetail["ITEM_PAYMENT"] = number_format($rowDetail["ITEM_PAYMENT"],2);
				$arrDetail["ITEM_PAYMENT_NOTFORMAT"] = $rowDetail["ITEM_PAYMENT"];
			}
			
			$arrGroupDetail[] = $arrDetail;

				
		}
		$getLoanGrp = $conoracle->prepare("SELECT
											PRE.PRENAME_DESC,MEMB.MEMB_NAME,MEMB.MEMB_SURNAME,MEMB.MEMBER_NO
											FROM
											LNCONTCOLL LCC LEFT JOIN LNCONTMASTER LCM ON  LCC.LOANCONTRACT_NO = LCM.LOANCONTRACT_NO
											LEFT JOIN MBMEMBMASTER MEMB ON LCM.MEMBER_NO = MEMB.MEMBER_NO
											LEFT JOIN MBUCFPRENAME PRE ON MEMB.PRENAME_CODE = PRE.PRENAME_CODE
											LEFT JOIN lnloantype LNTYPE  ON LCM.loantype_code = LNTYPE.loantype_code
											WHERE
											LCM.CONTRACT_STATUS > 0 AND LCM.CONTRACT_STATUS <> 8
											AND LCC.LOANCOLLTYPE_CODE = '01'
											AND LCC.REF_COLLNO = :member_no
											AND LNTYPE.LOANPERMGRP_CODE = '20'");
		$getLoanGrp->execute([':member_no' => $member_no]);
		while($rowLoanGrp = $getLoanGrp->fetch(PDO::FETCH_ASSOC)){
			if(isset($rowLoanGrp["PRENAME_DESC"]) && $rowLoanGrp["PRENAME_DESC"] != ""){
				if(!in_array($rowLoanGrp["MEMBER_NO"],$header["member_no_check"])){
					$header["collname"][] = $rowLoanGrp["PRENAME_DESC"].$rowLoanGrp["MEMB_NAME"].' '.$rowLoanGrp["MEMB_SURNAME"];
					$header["member_no_check"][] = $rowLoanGrp["MEMBER_NO"];
				}
			}
		}
		$header["share_stk"]  = number_format($share_stk,2);
		$getDetailKPHeader = $conoracle->prepare("SELECT 
															kpd.RECEIPT_NO,
															kpd.OPERATE_DATE,
															kpd.KEEPING_STATUS,
															kpd.SHARESTK_VALUE,
															kpd.SHARESTKBF_VALUE,
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
		$header["sharestk_value"] = number_format($rowKPHeader["SHARESTK_VALUE"],2);
		$header["sharestkbf_value"] = number_format($rowKPHeader["SHARESTKBF_VALUE"],2);
		$header["interest_accum"] = number_format($rowKPHeader["INTEREST_ACCUM"],2);
		$header["receipt_no"] = TRIM($rowKPHeader["RECEIPT_NO"]);
		$header["operate_date"] = $lib->convertdate($rowKPHeader["OPERATE_DATE"],'D m Y');
		$arrayPDF = GenerateReport($arrGroupDetail,$header,$lib,$rowName["CURRENT_COOPID"]);
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

function GenerateReport($dataReport,$header,$lib,$current_coopid=null){
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
			<div style=" text-align: left; position:absolute; top:-40; left:0"><img src="../../resource/logo/logo.png" style="margin: 10px 0 0 5px; " alt="" width="80" height="80" /></div>
			<div style="height:5px"></div>
			<div style="text-align:left;position: absolute;width:100%;">
				<p style="margin-top: -30px;font-size: 22px;font-weight: bold; text-align:center">สหกรณ์ออมทรัพย์ไทยน้ำทิพย์ จำกัด</p>';
	if($header["keeping_status"] == '-99' || $header["keeping_status"] == '-9'){
		$html .= '<p style="margin-top: -5px;font-size: 22px;font-weight: bold;color: red;">ยกเลิกใบเสร็จรับเงิน</p>';
	}else{
		$html .= '<p style="margin-top: -40px;font-size: 22px;font-weight: bold; text-align:center; " >ใบเสร็จรับเงิน</p>';
	}
	$html .= '
		</div>
			</div>
			<div style="margin: 25px 0 10px 0;">
			<table style="width: 100%;">
				<tbody>
					<tr>
						<td style="width: 50px;font-size: 18px;">แผนก/ฝ่าย :</td>
						<td style="width: 350px;">' . $header["coop_name"] . '</td>
						<td style="width: 50px;font-size: 18px;">เลขที่ใบเสร็จ :</td>
						<td style="width: 101px;">' . $header["receipt_no"] . '</td>
					</tr>
					<tr>
						<td style="width: 50px;font-size: 18px;">เลขสมาชิก :</td>
						<td style="width: 350px;">' . $header["member_no"]  . '</td>
						<td style="width: 50px;font-size: 18px;">วันที่ :</td>
						<td style="width: 101px;">' . $header["operate_date"] . '</td>
					</tr>
					<tr>
						<td style="width: 50px;font-size: 18px;">ได้รับเงินจาก :</td>
						<td style="width: 350px;">' . $header["fullname"] . '</td>
						<td style="width: 50px;font-size: 18px;">ดอกเบี้ยสะสม</td>
						<td style="width: 101px;">'.$header["interest_accum"].'</td>
					</tr>
					<tr>
						<td style="width: 50px;font-size: 18px; ">ทุนเรือนหุ้นยกมา :</td>
						<td style="width: 100px;">'.$header["sharestkbf_value"].'</td>
						<td style="width: 50px;font-size: 18px;">ทุนเรือนหุ้นสะสม :</td>
						<td style="width: 100px; solid;">' . $header["share_stk"] . '</td>
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
		for ($i = 0; $i < sizeof($dataReport); $i++) {
	if ($i == 0) {
		$html .= '<div style="display:flex;height: 30px;padding:0px">
			<div style="width: 350px;border-right: 0.5px solid black;height: 250px;">&nbsp;</div>
			<div style="width: 100px;border-right: 0.5px solid black;height: 250px;margin-left: 355px;">&nbsp;</div>
			<div style="width: 110px;border-right: 0.5px solid black;height: 270px;margin-left: 465px;">&nbsp;</div>
			<div style="width: 110px;border-right: 0.5px solid black;height: 270px;margin-left: 580px;">&nbsp;</div>
			<div style="width: 120px;border-right: 0.5px solid black;height: 270px;margin-left: 700px;">&nbsp;</div>
			<div style="width: 350px;text-align: left;font-size: 18px">
				<div>' . $dataReport[$i]["TYPE_DESC"] . ' ' . $dataReport[$i]["PAY_ACCOUNT"] . '</div>
			</div>
			<div style="width: 100px;text-align: center;font-size: 18px;margin-left: 355px;">
			<div>' . ($dataReport[$i]["PERIOD"] ?? null) . '</div>
			</div>
			<div style="width: 110px;text-align: right;font-size: 18px;margin-left: 465px;">
			<div>' . ($dataReport[$i]["PRN_BALANCE"] ?? null) . '</div>
			</div>
			<div style="width: 110px;text-align: right;font-size: 18px;margin-left: 580px;">
			<div>' . ($dataReport[$i]["INT_BALANCE"] ?? null) . '</div>
			</div>
			<div style="width: 120px;text-align: right;font-size: 18px;margin-left: 700px;">
			<div>' . ($dataReport[$i]["ITEM_PAYMENT"] ?? null) . '</div>
			</div>
			<div style="width: 150px;text-align: right;font-size: 18px;margin-left: 814px;">
			<div>' . ($dataReport[$i]["ITEM_BALANCE"] ?? null) . '</div>
			</div>
		</div>';
	} else {
		$html .= '<div style="display:flex;height: 30px;padding:0px">
				<div style="width: 350px;text-align: left;font-size: 18px">
					<div>' . $dataReport[$i]["TYPE_DESC"] . ' ' . $dataReport[$i]["PAY_ACCOUNT"] . '</div>
				</div>
				<div style="width: 100px;text-align: center;font-size: 18px;margin-left: 355px;">
				<div>' . ($dataReport[$i]["PERIOD"] ?? null) . '</div>
				</div>
				<div style="width: 110px;text-align: right;font-size: 18px;margin-left: 465px;">
				<div>' . ($dataReport[$i]["PRN_BALANCE"] ?? null) . '</div>
				</div>
				<div style="width: 110px;text-align: right;font-size: 18px;margin-left: 580px;">
				<div>' . ($dataReport[$i]["INT_BALANCE"] ?? null) . '</div>
				</div>
				<div style="width: 120px;text-align: right;font-size: 18px;margin-left: 700px;">
				<div>' . ($dataReport[$i]["ITEM_PAYMENT"] ?? null) . '</div>
				</div>
				<div style="width: 150px;text-align: right;font-size: 18px;margin-left: 814px;">
				<div>' . ($dataReport[$i]["ITEM_BALANCE"] ?? null) . '</div>
				</div>
			</div>';
	}
	 $sumBalance += $dataReport[$i]["ITEM_PAYMENT_NOTFORMAT"];
	
}

$html .= '</div>';
//สีพื้นหลังหัวตาราง
$html .= '
	<div style="position:fixed; top:156.5px; left:31px; background-color:#000000; opacity: 0.1; width:970px; height:31px;"></div>
';
//สีพื้นหลัง footer 
$html .= '
	<div style="position:fixed; top:325px; left:33px; text-align:left; color:#000000;   text-decoration: underline; ">ค้ำประกันเงินกู้สามัญ</div>
	<div style="position:fixed; top:350px; left:33px; text-align:left; color:#000000; ">';
		foreach($header["collname"] as $coll_name){
			$html .= "<div>".$coll_name."</div>";
		}
$html .= '</div>
	<div style="position:fixed; top:425px; text-align:center; color:#959292 ">โปรดนำใบเสร็จรับเงินมาด้วยทุกครั้งมาติดต่อกับสหกรณ์</div>
	<div style="position:fixed; top:447px; left:31px; text-align:center;  background-color:#000000; width:584.2px; height: 34.2px; opacity: 0.1"></div>
	<div style="position:fixed; top:447px; left:731px; text-align:center;  background-color:#000000; width:130px; height: 34.2px; opacity: 0.1"></div>
	<div style="position:fixed; top:477px; left:616px; text-align:center;  background-color:#ffffff; width:114.3px; height: 10px; ";></div>
	<div style="position:fixed; top:447px; right:29px; text-align:center;  background-color:#ffffff; width:141.7px; height: 40px; ";></div>
';

// Footer
$html .= '<div style="display:flex;width: 100%;height: 40px" class="sub-table">
			<div style="border-top: 0.5px solid black;">&nbsp;</div>
			<div style="width: 600px;text-align:center;height: 30px;font-size: 18px;padding-top: 0px;">' . $lib->baht_text($sumBalance) . '</div>
			<div style="width: 110px;border-right: 0.5px solid black;height: 30px;margin-left: 465px;padding-top: 0px;">&nbsp;</div>
			<div style="width: 110px;text-align: center;font-size: 18px;border-right : 0.5px solid black;padding-top: 0px;height:30px;margin-left: 580px; font-weight:bold;">
			รวมเงิน
			</div>
			<div style="width: 120px;text-align: right;border-right: 0.5px solid black;height: 30px;margin-left: 700px;padding-top: 0px;font-size: 18px;">' . number_format($sumBalance, 2) . '</div>
			</div>
			</div>
			<div style="display:flex;">
				<div style="font-size: 18px;  padding-top:25px;">
					ผู้จัดการ/รองผู้จัดการ
				</div>
				<div style="width:200px;margin-left: 170px; display:flex; position:absolute; left:0px;">
					<img src="../../resource/utility_icon/signature/manager.png" width="100" height="50" style="margin-top:10px;"/>
				</div>
				<div style="font-size: 18px;  padding-top:30px; position:absolute; right:250px; text-align:right;">
					เจ้าหน้าที่ธุรการ/การเงิน
				</div>
				<div style="width:200px;margin-left: 780px;display:flex;">
					<img src="../../resource/utility_icon/signature/' . $current_coopid . '.png" width="100" height="50" style="margin-top:10px; "/>
				</div>
			</div>
			<div style="text-align:center; margin-top:-50px;">
				(ใบเสร็จรับเงินฉบับนี้จะสมบูรณ์เมื่อได้รับเงินจากสมาชิกเข้าบัญชีเรียบร้อย)
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