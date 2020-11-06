<?php
require_once('../autoload.php');

use Dompdf\Dompdf;

$dompdf = new DOMPDF();

if($lib->checkCompleteArgument(['menu_component','recv_period'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SlipInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? TRIM($payload["member_no"]);
		$header = array();
		$fetchName = $conoracle->prepare("SELECT mb.memb_name,mb.memb_surname,mp.prename_desc,mbg.MEMBGROUP_CODE,(sh.sharestk_amt * 10) as SHARE_AMT
											,mm.TOTALPAY_AMT
												FROM mbmembmaster mb LEFT JOIN 
												mbucfprename mp ON mb.prename_code = mp.prename_code
												LEFT JOIN mbucfmembgroup mbg ON mb.MEMBGROUP_CODE = mbg.MEMBGROUP_CODE
												LEFT JOIN shsharemaster sh ON mb.member_no = sh.member_no
												LEFT JOIN mumembmaster mm ON mb.member_no = mm.member_no
												WHERE mb.member_no = :member_no and mb.member_status = '1' and mb.branch_id = :branch_id");
		$fetchName->execute([
			':member_no' => $member_no,
			':branch_id' => $payload["branch_id"]
		]);
		$rowName = $fetchName->fetch(PDO::FETCH_ASSOC);
		$header["fullname"] = $rowName["PRENAME_DESC"].$rowName["MEMB_NAME"].' '.$rowName["MEMB_SURNAME"];
		$header["member_group"] = $rowName["MEMBGROUP_CODE"];
		$header["share_amt"] = $rowName["SHARE_AMT"];
		$header["fund_amt"] = $rowName["TOTALPAY_AMT"];
		if($lib->checkCompleteArgument(['seq_no'],$dataComing)){
			$getPaymentDetail = $conoracle->prepare("SELECT 
																		CASE kut.system_code
																		WHEN 'LON' THEN NVL(lt.LOANTYPE_DESC,kut.keepitemtype_desc) 
																		WHEN 'DEP' THEN NVL(dp.DEPTTYPE_DESC,kut.keepitemtype_desc) 
																		ELSE kut.keepitemtype_desc
																		END as TYPE_DESC,
																		kut.keepitemtype_grp as TYPE_GROUP,
																		case kut.keepitemtype_grp 
																			WHEN 'DEP' THEN kpd.description
																			WHEN 'LON' THEN kpd.loancontract_no
																		ELSE kpd.description END as PAY_ACCOUNT,
																		kpd.period,
																		NVL(kpd.ITEM_PAYMENT * kut.SIGN_FLAG,0) AS ITEM_PAYMENT,
																		NVL(kpd.PRINCIPAL_BALANCE,kpd.ITEM_BALANCE) AS ITEM_BALANCE,
																		NVL(kpd.principal_payment,0) AS PRN_BALANCE,
																		NVL(kpd.interest_payment,0) AS INT_BALANCE
																		FROM kpmastreceivedet kpd LEFT JOIN KPUCFKEEPITEMTYPE kut ON 
																		kpd.keepitemtype_code = kut.keepitemtype_code
																		LEFT JOIN lnloantype lt ON kpd.shrlontype_code = lt.loantype_code
																		LEFT JOIN dpdepttype dp ON kpd.shrlontype_code = dp.depttype_code
																		WHERE kpd.member_no = :member_no and kpd.recv_period = :recv_period
																		and kpd.seq_no = :seq_no and kpd.branch_id = :branch_id 
																		and kut.keepitemtype_grp <> 'DEP'
																		ORDER BY kut.SORT_IN_RECEIVE ASC");
			$getPaymentDetail->execute([
				':member_no' => $member_no,
				':recv_period' => $dataComing["recv_period"],
				':seq_no' => $dataComing["seq_no"],
				':branch_id' => $payload["branch_id"]
			]);
		}else{
			$getPaymentDetail = $conoracle->prepare("SELECT 
																		CASE kut.system_code 
																		WHEN 'LON' THEN NVL(lt.LOANTYPE_DESC,kut.keepitemtype_desc) 
																		WHEN 'DEP' THEN NVL(dp.DEPTTYPE_DESC,kut.keepitemtype_desc) 
																		ELSE kut.keepitemtype_desc
																		END as TYPE_DESC,
																		kut.keepitemtype_grp as TYPE_GROUP,
																		case kut.keepitemtype_grp 
																			WHEN 'DEP' THEN kpd.description
																			WHEN 'LON' THEN kpd.loancontract_no
																		ELSE kpd.description END as PAY_ACCOUNT,
																		kpd.period,
																		NVL(kpd.ITEM_PAYMENT * kut.SIGN_FLAG,0) AS ITEM_PAYMENT,
																		NVL(kpd.PRINCIPAL_BALANCE,kpd.ITEM_BALANCE) AS ITEM_BALANCE,
																		NVL(kpd.principal_payment,0) AS PRN_BALANCE,
																		NVL(kpd.interest_payment,0) AS INT_BALANCE
																		FROM kpmastreceivedet kpd LEFT JOIN KPUCFKEEPITEMTYPE kut ON 
																		kpd.keepitemtype_code = kut.keepitemtype_code
																		LEFT JOIN lnloantype lt ON kpd.shrlontype_code = lt.loantype_code
																		LEFT JOIN dpdepttype dp ON kpd.shrlontype_code = dp.depttype_code
																		WHERE kpd.member_no = :member_no and kpd.recv_period = :recv_period
																		and kpd.branch_id = :branch_id and kut.keepitemtype_grp <> 'DEP'
																		ORDER BY kut.SORT_IN_RECEIVE ASC");
			$getPaymentDetail->execute([
				':member_no' => $member_no,
				':recv_period' => $dataComing["recv_period"],
				':branch_id' => $payload["branch_id"]
			]);
		}
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
				$arrDetail["PRN_BALANCE"] = number_format($rowDetail["PRN_BALANCE"],2);
				$arrDetail["INT_BALANCE"] = number_format($rowDetail["INT_BALANCE"],2);
			}else if($rowDetail["TYPE_GROUP"] == 'DEP'){
				$arrDetail["PAY_ACCOUNT"] = $lib->formataccount($rowDetail["PAY_ACCOUNT"],$func->getConstant('dep_format'));
				$arrDetail["PAY_ACCOUNT_LABEL"] = 'เลขบัญชี';
			}else if($rowDetail["TYPE_GROUP"] == "OTH"){
				$arrDetail["PAY_ACCOUNT"] = $rowDetail["PAY_ACCOUNT"];
				$arrDetail["PAY_ACCOUNT_LABEL"] = 'จ่าย';
			}
			$arrDetail["ITEM_PAYMENT"] = number_format($rowDetail["ITEM_PAYMENT"],2);
			$arrDetail["ITEM_PAYMENT_NOTFORMAT"] = $rowDetail["ITEM_PAYMENT"];
			$arrDetail["ITEM_BALANCE"] = number_format($rowDetail["ITEM_BALANCE"],2);
			$arrGroupDetail[] = $arrDetail;
		}
		$getDetailKPHeader = $conoracle->prepare("SELECT 
													kpd.RECEIPT_NO,
													kpd.OPERATE_DATE,
													kpd.KEEPING_STATUS
													FROM kpmastreceive kpd
													WHERE kpd.member_no = :member_no and kpd.recv_period = :recv_period 
													and kpd.branch_id = :branch_id");
		$getDetailKPHeader->execute([
			':member_no' => $member_no,
			':recv_period' => $dataComing["recv_period"],
			':branch_id' => $payload["branch_id"]
		]);
		$rowKPHeader = $getDetailKPHeader->fetch(PDO::FETCH_ASSOC);
		$header["keeping_status"] = $rowKPHeader["KEEPING_STATUS"];
		$header["recv_period"] = $lib->convertperiodkp(TRIM($dataComing["recv_period"]));
		$header["member_no"] = $payload["member_no"];
		$header["receipt_no"] = TRIM($rowKPHeader["RECEIPT_NO"]);
		$header["operate_date"] = $lib->convertdate($rowKPHeader["OPERATE_DATE"],'D m Y');
		$arrayPDF = GenerateReport($arrGroupDetail,$header,$lib);
		if($arrayPDF["RESULT"]){
			$arrayResult['REPORT_URL'] = $config["URL_SERVICE"].$arrayPDF["PATH"];
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
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
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
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
	echo json_encode($arrayResult);
	exit();
}

function GenerateReport($dataReport,$header,$lib){
	$sumBalance = 0;			
	$color_theme = '#024ea2';
	$bg_color = '#afb6d4';
	$logo = '../../resource/logo/logo.jpg';

	$html = '
		<style>

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
		  padding:0px;
		  font-size:20px;
		  color:'.$color_theme.';
		}
		p{
		  margin:0;
		}
		table{
			border:1.5px solid'.$color_theme.';
		}
		th{
		  text-align:center;
		  border:1.5px solid ;
		}
		.text-right{
		  text-align:right;
		}
		.text-center{
		  text-align:center
		}
		td{
		  border-left:1.5px solid '.$color_theme.';
		  paddig:1px;
		}
		.text-head{
		  font-size:22px; 
		  line-height: 20px;
		  font-weight:bold
		}
		.mert-table{
		  border-right:1.5px solid '.$color_theme.';
		  text-align:right;
		}
		.sum-tb{
		  border-right:1.5px  solid '.$color_theme.';
		}
		.bg-color{
		  background-color:'.$bg_color.';
		}
		</style>';

	 $html .='
		  <div style="height:auto;margin: 1cm; padding-bottom:1cm">
			<table style="width:100%;  border-collapse: collapse; border:none ">
				<tr>
					<td style="text-align: center; vertical-align: middle;border-left:none;">
						<img src='.$logo.' style="width:70px"/>
					</td>
					<td style="text-align: center; vertical-align: middle;border-left:none;">
						<div class="text-head">สหกรณ์ออมทรัพย์</div>
						<div class="text-head">มหาวิทยาลัยศรีนครินทรวิโรฒ จำกัด</div>
						<div class="text-head">ใบเสร็จรับเงิน</div>
					</td>
				</tr>
			</table>
			<table style="width:100%;  border-collapse: collapse; border:none;margin-top: 5px,">
				<tr>
					<td style="width: 100%;border-left: none;">
					  <div style="font-size:20px;">
						 เลขที่  '.$header["receipt_no"].'
					  </div>
					</td>
					<td style="border-left: none;padding-right: 20px">
					  <div style="font-size:20px;">
						 วันที่ 
					  </div>
					</td>
					<td style="border-left: none;">
					  <div style="font-size:20px;white-space: nowrap">
						'.$header["operate_date"].'
					  </div>
					</td>
				</tr>	
				<tr>
					<td style="border-left: none;width: 100%">
					  <div style="font-size:20px;">
						 ได้รับเงินจาก  '.$header["fullname"].'
					  </div>
					</td>
					<td style="border-left: none">
					  <div style="font-size:20px;">
						 เงินกองทุน 
					  </div>
					</td>
					<td style="border-left: none;text-align: right;padding-left: 20px;padding-right: 20px;">
					  <div style="font-size:20px;">
						'.number_format($header["fund_amt"],2).'
					  </div>
					</td>
					<td style="border-left: none;text-align: right">
					  <div style="font-size:20px;">
						 บาท
					  </div>
					</td>
				</tr>
				<tr>
					<td style="border-left: none;width: 100%">
					  <div style="font-size:20px;">
						 สมาชิกเลขทะเบียนที่  '.$header["member_group"].' - '.$header["member_no"].'
					  </div>
					</td>
					<td style="border-left: none">
					  <div style="font-size:20px;">
						 ทุนเรือนหุ้น 
					  </div>
					</td>
					<td style="border-left: none;text-align: right;padding-left: 20px;padding-right: 20px;">
					  <div style="font-size:20px;">
						 '.number_format($header["share_amt"],2).'
					  </div>
					</td>
					<td style="border-left: none;text-align: right;">
					  <div style="font-size:20px;">
						 บาท
					  </div>
					</td>
				</tr>
			</table>

	';

	$html .='
	  <div style="width:100%;">
	  <table style="width:100%;  border-collapse: collapse; border:none;margin-top: 10px ">
		  <thead>
			  <tr>
				 <th>รายการชำระ</th>
				 <th>งวดที่</th>
				 <th>เงินต้น</th>
				 <th>ดอกเบี้ย</th>
				 <th>เป็นเงิน</th>
				 <th>เงินต้นคงเหลือ</th>
			  </tr>
		  </thead>
		 <tbody>
	';

	for($i = 0;$i < sizeof($dataReport); $i++){
	  $html .='
		<tr>
			<td style="padding-left: 5px;padding-right: 5px">'.$dataReport[$i]["TYPE_DESC"].' '.$dataReport[$i]["PAY_ACCOUNT"].'</td>
			<td style="padding-left: 5px;padding-right: 5px" class="text-center" >'.($dataReport[$i]["PERIOD"] ?? null).'</td>
			<td style="padding-left: 5px;padding-right: 5px" class="text-right">'.($dataReport[$i]["PRN_BALANCE"] ?? null).'</td>
			<td style="padding-left: 5px;padding-right: 5px" class="text-right">'.($dataReport[$i]["INT_BALANCE"] ?? null).'</td>
			<td style="padding-left: 5px;padding-right: 5px" class="text-right">'.($dataReport[$i]["ITEM_PAYMENT"] ?? null).'</td>
			<td style="padding-left: 5px;padding-right: 5px" class="mert-table">'.($dataReport[$i]["ITEM_BALANCE"] ?? null).'</td>
		</tr> ';
		$sumBalance += $dataReport[$i]["ITEM_PAYMENT_NOTFORMAT"];
	}

	//ยอดรวม
	$html .= '
	  <tr>
		<th colspan=3 class="bg-color">
		  '.$lib->baht_text($sumBalance).'
		</th>
		<th>รวม</th>
		<th  class="text-right sum-tb bg-color" >
		<div style="border-bottom:1.5px solid; width : 100%; margin-bottom:2px;padding-left: 5px;padding-right: 5px;background-color: white">'.number_format($sumBalance,2).'</div>
		</th>
		<th style="border-left:none;border-right:none;border-bottom:none;"></th>
	  </tr>
	';
	$html .='
		</tbody>
	  </table>
	  </div>
	  <div style="margin-top:10px">
		<div style=" float: left; padding-left:2.3cm; width:40%;  ">
			ผู้จัดการ/เหรัญญิก
		</div>
		<div style=" float: left; width:50%; ">
		  ผู้รับเงิน
		</div>
	</div>
	<div style="clear: both;"></div>
	<div style="padding-left:1.2cm;font-weight:bold; ">โปรดทราบ : -</div>
	</div>';

	$dompdf = new DOMPDF();
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