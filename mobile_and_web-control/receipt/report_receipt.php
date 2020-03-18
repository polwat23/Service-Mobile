<?php
require_once('../autoload.php');

use Dompdf\Dompdf;

$dompdf = new DOMPDF();

if($lib->checkCompleteArgument(['menu_component','slip_no'],$dataComing)){
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
		$arrGroupDetail = array();
		$getDetailSlip = $conoracle->prepare("SELECT slt.slipitemtype_desc,slt.slipitemtype_code,sld.loancontract_no,sld.interest_payamt,
											sld.item_payamt,sld.item_balance,sld.period
											FROM slslippayindet sld LEFT JOIN slucfslipitemtype slt ON sld.slipitemtype_code = slt.slipitemtype_code
											WHERE payinslip_no = :slip_no");
		$getDetailSlip->execute([
			':slip_no' => $dataComing["slip_no"]
		]);
		while($rowDetail = $getDetailSlip->fetch(PDO::FETCH_ASSOC)){
			$arrDetail = array();
			$arrDetail["TYPE_DESC"] = $rowDetail["SLIPITEMTYPE_DESC"];			
			if($rowDetail["SLIPITEMTYPE_CODE"] == 'SHR'){
				$arrDetail["PERIOD"] = $rowDetail["PERIOD"];
				$arrDetail["ITEM_BALANCE"] = number_format($rowDetail["ITEM_BALANCE"],2);
			}else if($rowDetail["SLIPITEMTYPE_CODE"] == 'LON'){
				$arrDetail["PAY_ACCOUNT"] = $rowDetail["LOANCONTRACT_NO"];
				$arrDetail["PERIOD"] = $rowDetail["PERIOD"];
				$arrDetail["ITEM_BALANCE"] = number_format($rowDetail["ITEM_BALANCE"],2);
				$arrDetail["ITEM_PAYAMT"] = number_format($rowDetail["ITEM_PAYAMT"],2);
				$arrDetail["INT_BALANCE"] = number_format($rowDetail["INTEREST_PAYAMT"],2);
			}else if($rowDetail["SLIPITEMTYPE_CODE"] == 'DEP'){
				$arrDetail["PAY_ACCOUNT"] = $lib->formataccount($rowDetail["LOANCONTRACT_NO"],$func->getConstant('dep_format'));
			}
			$item_payment = $rowDetail["INTEREST_PAYAMT"] + $rowDetail["ITEM_PAYAMT"];
			$arrDetail["ITEM_PAYMENT"] = number_format($item_payment,2);
			$arrDetail["ITEM_PAYMENT_NOTFORMAT"] = $item_payment;
			$arrGroupDetail[] = $arrDetail;
		}
		if(sizeof($arrGroupDetail) > 0){
			$getDetailHeader = $conoracle->prepare("SELECT SLIP_DATE FROM slslippayin WHERE payinslip_no = :slip_no");
			$getDetailHeader->execute([
				':slip_no' => $dataComing["slip_no"]
			]);
			$rowHeader = $getDetailHeader->fetch(PDO::FETCH_ASSOC);
			$header["member_no"] = $member_no;
			$header["slip_no"] = $dataComing["slip_no"];
			$header["operate_date"] = $lib->convertdate($rowHeader["SLIP_DATE"],'D m Y');
			$arrayPDF = GenerateReport($arrGroupDetail,$header,$lib);
			if($arrayPDF["RESULT"]){
				$arrayResult['REPORT_URL'] = $config["URL_SERVICE"].$arrayPDF["PATH"];
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0044";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				http_response_code(403);
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			http_response_code(204);
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
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
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
			<div style="text-align: left;"><img src="../../resource/logo/logo.png" style="margin: 10px 0 0 5px" alt="" width="80" height="80" /></div>
			<div style="text-align:left;position: absolute;width:100%;margin-left: 140px">
			<p style="margin-top: -5px;font-size: 22px;font-weight: bold">ใบเสร็จรับเงิน</p>
			<p style="margin-top: -30px;font-size: 22px;font-weight: bold">สหกรณ์ออมทรัพย์มหาวิทยาลัยมหิดล จำกัด</p>
			<p style="margin-top: -27px;font-size: 18px;">เลขที่ 2 อาคารศรีสวรินทิรา ชั้น 1 และ ชั้น 6 ถนนวังหลัง</p>
			<p style="margin-top: -25px;font-size: 18px;">แขวงศิริราช เขตบางกอกน้อย กรุงเทพมหานคร 10700</p>
			<p style="margin-top: -25px;font-size: 18px;">โทร. 0-2444-7741-3, 0-2419-7543-5, 0-2419-8363-4</p>
			<p style="margin-top: -27px;font-size: 19px;font-weight: bold">www.si.mahidol.ac.th</p>
			</div>
			</div>
			<div style="margin: 25px 0 10px 0;">
			<table style="width: 100%;">
			<tbody>
			<tr>
			<td style="width: 50px;font-size: 18px;">เลขสมาชิก :</td>
			<td style="width: 350px;">'.$header["member_no"].'</td>
			<td style="width: 50px;font-size: 18px;">เลขที่ใบเสร็จ :</td>
			<td style="width: 101px;">'.$header["slip_no"].'</td>
			</tr>
			<tr>
			<td style="width: 50px;font-size: 18px;"></td>
			<td style="width: 350px;"></td>
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
				<div>'.$dataReport[$i]["TYPE_DESC"].'</div>
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
				<div>'.$dataReport["TYPE_DESC"].'</div>
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
	$pathfile = __DIR__.'/../../resource/pdf/receipt';
	if(!file_exists($pathfile)){
		mkdir($pathfile, 0777, true);
	}
	$pathfile = $pathfile.'/'.$header["slip_no"].'.pdf';
	$pathfile_show = '/resource/pdf/receipt/'.$header["slip_no"].'.pdf';
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