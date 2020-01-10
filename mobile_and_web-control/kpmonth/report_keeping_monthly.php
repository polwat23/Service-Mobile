<?php
require_once('../autoload.php');

use Dompdf\Dompdf;

$dompdf = new DOMPDF();

if($lib->checkCompleteArgument(['menu_component','recv_period'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'PaymentMonthlyDetail')){
		if($payload["member_no"] == 'dev@mode'){
			$member_no = $config["MEMBER_NO_DEV_KEEPINGMONTH"];
		}else if($payload["member_no"] == 'salemode'){
			$member_no = $config["MEMBER_NO_SALE_KEEPINGMONTH"];
		}else{
			$member_no = $payload["member_no"];
		}
		$arrGroupDetail = array();
		$getDetailKP = $conoracle->prepare("SELECT * FROM (
												SELECT 
													kut.keepitemtype_desc as TYPE_DESC,
													kut.keepitemtype_grp as TYPE_GROUP,
													case kut.keepitemtype_grp 
														WHEN 'DEP' THEN kpd.description
														WHEN 'LON' THEN kpd.loancontract_no
													ELSE null END as PAY_ACCOUNT,
													kpd.period,
													NVL(kpd.ITEM_PAYMENT * kut.SIGN_FLAG,0) AS ITEM_PAYMENT,
													NVL(kpd.ITEM_BALANCE,0) AS ITEM_BALANCE,
													NVL(kpd.principal_payment,0) AS PRN_BALANCE,
													NVL(kpd.interest_payment,0) AS INT_BALANCE
													FROM kpmastreceivedet kpd LEFT JOIN KPUCFKEEPITEMTYPE kut ON 
													kpd.keepitemtype_code = kut.keepitemtype_code
													WHERE kpd.member_no = :member_no and kpd.recv_period = :recv_period)
												UNION 
												(	SELECT 
													kut.keepitemtype_desc as TYPE_DESC,
													kut.keepitemtype_grp as TYPE_GROUP,
													case kut.keepitemtype_grp 
														WHEN 'DEP' THEN kpd.description
														WHEN 'LON' THEN kpd.loancontract_no
													ELSE null END as PAY_ACCOUNT,
													kpd.period,
													NVL(kpd.ITEM_PAYMENT * kut.SIGN_FLAG,0) AS ITEM_PAYMENT,
													NVL(kpd.ITEM_BALANCE,0) AS ITEM_BALANCE,
													NVL(kpd.principal_payment,0) AS PRN_BALANCE,
													NVL(kpd.interest_payment,0) AS INT_BALANCE
													FROM kptempreceivedet kpd LEFT JOIN KPUCFKEEPITEMTYPE kut ON 
													kpd.keepitemtype_code = kut.keepitemtype_code
													WHERE kpd.member_no = :member_no and kpd.recv_period = :recv_period)");
		$getDetailKP->execute([
			':member_no' => $member_no,
			':recv_period' => $dataComing["recv_period"]
		]);
		while($rowDetail = $getDetailKP->fetch()){
			$arrDetail = array();
			$arrDetail["TYPE_DESC"] = $rowDetail["TYPE_DESC"];			
			if($rowDetail["TYPE_GROUP"] == 'SHR'){
				$arrDetail["PERIOD"] = $rowDetail["PERIOD"];
			}else if($rowDetail["TYPE_GROUP"] == 'LON'){
				$arrDetail["PAY_ACCOUNT"] = $lib->formatcontract($rowDetail["PAY_ACCOUNT"],$func->getConstant('loan_format'));
				$arrDetail["PERIOD"] = $rowDetail["PERIOD"];
				$arrDetail["ITEM_BALANCE"] = number_format($rowDetail["ITEM_BALANCE"],2);
				$arrDetail["PRN_BALANCE"] = number_format($rowDetail["PRN_BALANCE"],2);
				$arrDetail["INT_BALANCE"] = number_format($rowDetail["INT_BALANCE"],2);
			}else if($rowDetail["TYPE_GROUP"] == 'DEP'){
				$arrDetail["PAY_ACCOUNT"] = $lib->formataccount($rowDetail["PAY_ACCOUNT"],$func->getConstant('dep_format'));
			}
			$arrDetail["ITEM_PAYMENT"] = number_format($rowDetail["ITEM_PAYMENT"],2);
			$arrGroupDetail[] = $arrDetail;
		}
		if(sizeof($arrGroupDetail) > 0 || isset($new_token)){
			$arrayPDF = GenerateReport($arrGroupDetail,$payload["member_no"],$dompdf);
			if($arrayPDF["RESULT"]){
				$arrayResult['REPORT_URL'] = $arrayPDF["PATH"];
				if(isset($new_token)){
					$arrayResult['NEW_TOKEN'] = $new_token;
				}
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

function GenerateReport($dataReport,$member_no,$dompdf){
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
				.sub-table div{
					padding : 5px;
				}
			</style>

			<div style="display: flex;text-align: center;position: relative;margin-bottom: 20px;">
				<div style="text-align: left;"><img src="../../resource/logo/logo.png" style="margin: 10px 0 0 5px" alt="" width="80" height="80" /></div>
					<div style="text-align:center;position: absolute;width:100%">
						<p style="margin-top: 10px;font-size: 22px;font-weight: bold">สหกรณ์ออมทรัพย์กรมป่าไม้ จำกัด</p>
						<p style="margin-top: -15px;font-size: 18px;font-weight: bold">รายการเรียกเก็บประจำเดือน</p>
						<p style="margin-top: -28px;font-size: 18px;font-weight: bold">มีนาคม 2562</p>
					</div>
				</div>
				<div style="margin: 30px 0 20px 0;">
					<table style="width: 100%;">
						<tbody>
							<tr>
								<td style="width: 150px;font-size: 18px;font-weight: bold">เลขที่</td>
								<td style="width: 300px;">&nbsp;</td>
								<td style="width: 50px;font-size: 18px;font-weight: bold">วันที่</td>
								<td style="width: 101px;">&nbsp;</td>
							</tr>
							<tr>
								<td style="width: 150px;font-size: 18px;font-weight: bold">เรียกเก็บเงินจาก</td>
								<td style="width: 300px;"></td>
								<td style="width: 50px;font-size: 18px;font-weight: bold">เลขสมาชิก</td>
								<td style="width: 101px;">'.$member_no.'</td>
							</tr>
						</tbody>
					</table>
				</div>
				<div style="border: 1px solid black;width: 100%; height: 750px;">
					<div style="display:flex;width: 100%;height: 30px;" class="sub-table">
						<div style="border-bottom: 1px solid black;">&nbsp;</div>
						<div style="width: 280px;text-align: center;font-size: 18px;font-weight: bold;border-right : 1px solid black;padding-top: 0px;">รายการชำระ</div>
						<div style="width: 70px;text-align: center;font-size: 18px;font-weight: bold;border-right : 1px solid black;margin-left: 290px;padding-top: 0px;">งวดที่</div>
						<div style="width: 110px;text-align: center;font-size: 18px;font-weight: bold;border-right : 1px solid black;margin-left: 370px;padding-top: 0px;">เงินต้น</div>
						<div style="width: 100px;text-align: center;font-size: 18px;font-weight: bold;border-right : 1px solid black;margin-left: 490px;padding-top: 0px;">ดอกเบี้ย</div>
						<div style="width: 100px;text-align: center;font-size: 18px;font-weight: bold;margin-left: 610px;padding-top: 0px;">เป็นเงิน</div>
					</div>';
				// Detail
	$html .= '<div style="width: 100%;height: 683px" class="sub-table">
					<div style="display:flex;height: 30px;padding:0px">
						<div style="width: 280px;text-align: left;font-size: 18px;">
							<div>ทดสอบ</div>
						</div>
						<div style="width: 280px;border-right: 1px solid black;height: 673px;">&nbsp;</div>
						<div style="width: 70px;border-right: 1px solid black;height: 673px;;margin-left: 290px;">&nbsp;</div>
						<div style="width: 100px;border-right: 1px solid black;height: 673px;margin-left: 380px;">&nbsp;</div>
						<div style="width: 100px;border-right: 1px solid black;height: 673px;margin-left: 490px;">&nbsp;</div>
						<div style="width: 70px;text-align: center;font-size: 18px;border-right : 1px solid black;margin-left: 290px;">
						<div>ทดสอบ</div>
					</div>
					<div style="width: 110px;text-align: right;font-size: 18px;border-right : 1px solid black;margin-left: 370px;">
						<div>ทดสอบ</div>
					</div>
					<div style="width: 100px;text-align: right;font-size: 18px;border-right : 1px solid black;margin-left: 490px;">
						<div>ทดสอบ</div>
					</div>
					<div style="width: 100px;text-align: right;font-size: 18px;margin-left: 610px;">
						<div>ทดสอบ</div>
					</div>
				</div>

				<div style="display:flex;height: 30px;padding:0px">
					<div style="width: 280px;text-align: left;font-size: 18px">
						<div>ทดสอบ</div>
					</div>
					<div style="width: 70px;text-align: center;font-size: 18px;border-right : 1px solid black;margin-left: 290px;">
						<div>ทดสอบ</div>
					</div>
					<div style="width: 110px;text-align: right;font-size: 18px;border-right : 1px solid black;margin-left: 370px;">
						<div>ทดสอบ</div>
					</div>
					<div style="width: 100px;text-align: right;font-size: 18px;border-right : 1px solid black;margin-left: 490px;">
						<div>ทดสอบ</div>
					</div>
					<div style="width: 100px;text-align: right;font-size: 18px;margin-left: 610px;">
						<div>ทดสอบ</div>
					</div>
				</div>

				<div style="display:flex;height: 30px;padding:0px">
					<div style="width: 280px;text-align: left;font-size: 18px">
						<div>ทดสอบ</div>
					</div>
					<div style="width: 70px;text-align: center;font-size: 18px;border-right : 1px solid black;margin-left: 290px;">
						<div>ทดสอบ</div>
					</div>
					<div style="width: 110px;text-align: right;font-size: 18px;border-right : 1px solid black;margin-left: 370px;">
						<div>ทดสอบ</div>
					</div>
					<div style="width: 100px;text-align: right;font-size: 18px;border-right : 1px solid black;margin-left: 490px;">
						<div>ทดสอบ</div>
					</div>
					<div style="width: 100px;text-align: right;font-size: 18px;margin-left: 610px;">
						<div>ทดสอบ</div>
					</div>
				</div>
			</div>';
				// Footer
				$html .= '<div style="display:flex;width: 100%;height: 40px" class="sub-table">
								<div style="border-top: 1px solid black;">&nbsp;</div>
								<div style="width: 590px;text-align: right;font-size: 18px;border-right : 1px solid black;padding-top: 0px;height:32px;">
									รวมเงิน
								</div>
								<div style="width: 90px;text-align: right;font-size: 18px;font-weight: bold"></div>
							</div>';

	$dompdf = new DOMPDF();
	$dompdf->load_html($html);
	$dompdf->render();
	$pathfile = __DIR__.'/../../resource/pdf/keeping_monthly';
	if(!file_exists($pathfile)){
		mkdir($pathfile, 0777, true);
	}
	$pathfile = $pathfile.'/'.$member_no.'.pdf';
	$pathfile_show = '/resource/pdf/keeping_monthly/'.$member_no.'.pdf';
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