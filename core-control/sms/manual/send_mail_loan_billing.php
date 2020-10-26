<?php
require_once('../../autoload.php');

use Dompdf\Dompdf;

$dompdf = new DOMPDF();

if($lib->checkCompleteArgument(['unique_id','body_root_','subject'],$dataComing)){
	if($func->check_permission_core($payload,'sms','loanbillingemail')){
		if(isset($dataComing["destination"]) && sizeof($dataComing["destination"]) > 0){
			foreach($dataComing["destination"] as $key => $target){
				if(!in_array($key,$dataComing["destination_revoke"])){
					$destination[] = strtolower($lib->mb_str_pad($target));
				}
			}
			if(sizeof($destination) > 0){
				$bulkInsertNotSent = array();
				$bulkInsertSent = array();
				$getDataForPreview = $conoracle->prepare("SELECT kms.MEMBER_NO,mp.PRENAME_SHORT || mb.MEMB_NAME || ' ' || mb.MEMB_SURNAME as FULL_NAME,to_char(mb.BIRTH_DATE,'DDMMYYYY') as BIRTH_DATE
														FROM kpkepnotenoughmoneytosms kms
														LEFT JOIN mbmembmaster mb ON kms.member_no = mb.member_no
														LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
														WHERE kms.member_no IN('".implode("','",$destination)."') and kms.recv_period = (SELECT MAX(recv_period) FROM kpmastreceive)
														GROUP BY kms.MEMBER_NO,mp.PRENAME_SHORT || mb.MEMB_NAME || ' ' || mb.MEMB_SURNAME,mb.BIRTH_DATE");
				$getDataForPreview->execute();
				while($rowDataPre = $getDataForPreview->fetch(PDO::FETCH_ASSOC)){
					$conoracle->beginTransaction();
					$mailAsset = array();
					$mailAsset = $func->getMailAddress($rowDataPre["MEMBER_NO"]);
					if(isset($mailAsset[0]["EMAIL"]) && $mailAsset[0]["EMAIL"] != ""){
						$arrayAttach = array();
						$arrTarget = array();
						$arrTarget["FULL_NAME"] = $rowDataPre["FULL_NAME"];
						
						$getHeaderForPDF = $conoracle->prepare("SELECT 
														kar.KPSLIP_NO,kms.RECV_PERIOD
														FROM kpkepnotenoughmoneytosms kms LEFT JOIN kparrearreceive kar
														ON kms.recv_period = kar.recv_period and kms.member_no = kar.member_no
														WHERE kms.member_no = :member_no and kms.recv_period = (SELECT MAX(recv_period) FROM kpmastreceive)
														GROUP BY kar.KPSLIP_NO,kms.RECV_PERIOD");
						$getHeaderForPDF->execute([':member_no' => $rowDataPre["MEMBER_NO"]]);
						while($rowHeader = $getHeaderForPDF->fetch(PDO::FETCH_ASSOC)){
							$arrData = array();
							$header = array();
							$header["recv_period"] = $lib->convertperiodkp(TRIM($rowHeader["RECV_PERIOD"]));
							$header["member_no"] = $rowDataPre["MEMBER_NO"];
							$header["receipt_no"] = trim($rowHeader["KPSLIP_NO"]);
							$header["full_name"] = $rowDataPre["FULL_NAME"];
							$header["birth_date"] = $rowDataPre["BIRTH_DATE"];
							$getData = $conoracle->prepare("SELECT kpt.KEEPITEMTYPE_DESC,
																	kar.LOANCONTRACT_NO,kar.PRINARREAR_AMT,kar.INTARREAR_AMT,kar.ITEMARREAR_AMT
																	FROM kpkepnotenoughmoneytosms kms LEFT JOIN kparrearreceive kar
																	ON kms.recv_period = kar.recv_period and kms.member_no = kar.member_no
																	LEFT JOIN kpucfkeepitemtype kpt ON kar.keepitemtype_code = kpt.keepitemtype_code	
																	WHERE kms.member_no = :member_no and kms.recv_period = :recv_period and kar.kpslip_no = :kpslip_no
																	ORDER BY kms.SEQ_NO ASC");
							$getData->execute([
								':member_no' => $rowDataPre["MEMBER_NO"],
								':recv_period' => $rowHeader["RECV_PERIOD"],
								':kpslip_no' => $rowHeader["KPSLIP_NO"]
							]);
							while($rowData = $getData->fetch(PDO::FETCH_ASSOC)){
								$arrDataPre = array();
								$arrDataPre["TYPE_DESC"] = $rowData["KEEPITEMTYPE_DESC"];
								$arrDataPre["DOCPAY_NO"] = $rowData["LOANCONTRACT_NO"];
								$arrDataPre["PRIN_AMT"] = number_format($rowData["PRINARREAR_AMT"],2);
								$arrDataPre["INT_AMT"] = number_format($rowData["INTARREAR_AMT"],2);
								$arrDataPre["ITEM_AMT"] = number_format($rowData["ITEMARREAR_AMT"],2);
								$arrDataPre["ITEM_AMT_NOTFORMAT"] = $rowData["ITEMARREAR_AMT"];
								$arrData[] = $arrDataPre;
							}
							$filename = trim($rowHeader["KPSLIP_NO"]).date('Ymd');
							$arrayPDF = generateBillLoan($arrData,$header,$lib,$filename);
							if($arrayPDF["RESULT"]){
								$arrayAttach[] = $arrayPDF["PATH"];
								$arrTarget["PATH_PDF"] = $config["URL_SERVICE"].$arrayPDF["PATH"];
								$arrTarget["INVOICE_NO"] = trim($rowHeader["KPSLIP_NO"]);
								$arrMessage = $lib->mergeTemplate($dataComing["subject"],$dataComing["body_root_"],$arrTarget);
								$updateFlagMail = $conoracle->prepare("UPDATE kpkepnotenoughmoneytosms SET mailpost_status = '1',mailpost_date = sysdate 
																	WHERE member_no = :member_no and recv_period = :recv_period");
								$updateFlagMail->execute([
									':member_no' => $rowDataPre["MEMBER_NO"],
									':recv_period' => $rowHeader["RECV_PERIOD"]
								]);
							}else{
								$bulkInsertNotSent[] = "('".$arrMessage["SUBJECT"]."','".$arrMessage["BODY"]."','".$mailAsset[0]["EMAIL"]."','0','".$payload["username"]."',null,'0','ไม่สามารถสร้างไฟล์ PDF ได้','".$rowDataPre["MEMBER_NO"]."')";
								if(sizeof($bulkInsertNotSent) == 1000){
									$func->logSendMail($bulkInsertNotSent);
								}
								$conoracle->rollback();
							}
						}
						if(sizeof($arrayAttach) > 0){
							$sendMail = $lib->sendMail($mailAsset[0]["EMAIL"],$arrMessage["SUBJECT"],$arrMessage["BODY"],$mailFunction);
							if($sendMail["RESULT"]){
								$bulkInsertSent[] = "('".$arrMessage["SUBJECT"]."','".$arrMessage["BODY"]."','".$mailAsset[0]["EMAIL"]."','1','".$payload["username"]."',null,'0',null,'".$rowDataPre["MEMBER_NO"]."')";
								if(sizeof($bulkInsertSent) == 1000){
									$func->logSendMail($bulkInsertSent);
								}
								$conoracle->commit();
							}else{
								$bulkInsertNotSent[] = "('".$arrMessage["SUBJECT"]."','".$arrMessage["BODY"]."','".$mailAsset[0]["EMAIL"]."','0','".$payload["username"]."',null,'0','".$sendMail["MESSAGE_ERROR"]."','".$rowDataPre["MEMBER_NO"]."')";
								if(sizeof($bulkInsertNotSent) == 1000){
									$func->logSendMail($bulkInsertNotSent);
								}
								$conoracle->rollback();
							}
						}else{
							$bulkInsertNotSent[] = "('".$arrMessage["SUBJECT"]."','".$arrMessage["BODY"]."','".$mailAsset[0]["EMAIL"]."','0','".$payload["username"]."',null,'0','ไม่สามารถสร้างไฟล์ PDF ได้','".$rowDataPre["MEMBER_NO"]."')";
							if(sizeof($bulkInsertNotSent) == 1000){
								$func->logSendMail($bulkInsertNotSent);
							}
							$conoracle->rollback();
						}
					}else{
						$conoracle->rollback();
					}
				}
				if(sizeof($bulkInsertNotSent) > 0){
					$func->logSendMail($bulkInsertNotSent);
				}
				if(sizeof($bulkInsertSent) > 0){
					$func->logSendMail($bulkInsertSent);
				}
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
				exit();
			}else{
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$destination = array();
			$arrDestTemp = array();
			$getDataForPreview = $conoracle->prepare("SELECT MEMBER_NO
													FROM kpkepnotenoughmoneytosms
													WHERE mailpost_status = '0' and recv_period = (SELECT MAX(recv_period) FROM kpmastreceive) 
													GROUP BY member_no");
			$getDataForPreview->execute();
			while($rowDataPre = $getDataForPreview->fetch(PDO::FETCH_ASSOC)){
				$arrDestTemp[] = $rowDataPre["MEMBER_NO"];
			}
			foreach($arrDestTemp as $key => $target){
				if(!in_array($key,$dataComing["destination_revoke"])){
					$destination[] = strtolower($lib->mb_str_pad($target));
				}
			}
			$getDataForPreview = $conoracle->prepare("SELECT kms.MEMBER_NO,mp.PRENAME_SHORT || mb.MEMB_NAME || ' ' || mb.MEMB_SURNAME as FULL_NAME,to_char(mb.BIRTH_DATE,'DDMMYYYY') as BIRTH_DATE
													FROM kpkepnotenoughmoneytosms kms
													LEFT JOIN mbmembmaster mb ON kms.member_no = mb.member_no
													LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
													WHERE kms.member_no IN('".implode("','",$destination)."') and kms.recv_period = (SELECT MAX(recv_period) FROM kpmastreceive)
													GROUP BY kms.MEMBER_NO,mp.PRENAME_SHORT || mb.MEMB_NAME || ' ' || mb.MEMB_SURNAME,mb.BIRTH_DATE");
			$getDataForPreview->execute();
			while($rowDataPre = $getDataForPreview->fetch(PDO::FETCH_ASSOC)){
				$conoracle->beginTransaction();
				$mailAsset = array();
				$mailAsset = $func->getMailAddress($rowDataPre["MEMBER_NO"]);
				if(isset($mailAsset[0]["EMAIL"]) && $mailAsset[0]["EMAIL"] != ""){
					$arrayAttach = array();
					$arrTarget = array();
					$arrTarget["FULL_NAME"] = $rowDataPre["FULL_NAME"];
					
					$getHeaderForPDF = $conoracle->prepare("SELECT 
													kar.KPSLIP_NO,kms.RECV_PERIOD
													FROM kpkepnotenoughmoneytosms kms LEFT JOIN kparrearreceive kar
													ON kms.recv_period = kar.recv_period and kms.member_no = kar.member_no
													WHERE kms.member_no = :member_no and kms.recv_period = (SELECT MAX(recv_period) FROM kpmastreceive)
													GROUP BY kar.KPSLIP_NO,kms.RECV_PERIOD");
					$getHeaderForPDF->execute([':member_no' => $rowDataPre["MEMBER_NO"]]);
					while($rowHeader = $getHeaderForPDF->fetch(PDO::FETCH_ASSOC)){
						$arrData = array();
						$header = array();
						$header["recv_period"] = $lib->convertperiodkp(TRIM($rowHeader["RECV_PERIOD"]));
						$header["member_no"] = $rowDataPre["MEMBER_NO"];
						$header["receipt_no"] = trim($rowHeader["KPSLIP_NO"]);
						$header["full_name"] = $rowDataPre["FULL_NAME"];
						$header["birth_date"] = $rowDataPre["BIRTH_DATE"];
						$getData = $conoracle->prepare("SELECT kpt.KEEPITEMTYPE_DESC,
																kar.LOANCONTRACT_NO,kar.PRINARREAR_AMT,kar.INTARREAR_AMT,kar.ITEMARREAR_AMT
																FROM kpkepnotenoughmoneytosms kms LEFT JOIN kparrearreceive kar
																ON kms.recv_period = kar.recv_period and kms.member_no = kar.member_no
																LEFT JOIN kpucfkeepitemtype kpt ON kar.keepitemtype_code = kpt.keepitemtype_code	
																WHERE kms.member_no = :member_no and kms.recv_period = :recv_period and kar.kpslip_no = :kpslip_no
																ORDER BY kms.SEQ_NO ASC");
						$getData->execute([
							':member_no' => $rowDataPre["MEMBER_NO"],
							':recv_period' => $rowHeader["RECV_PERIOD"],
							':kpslip_no' => $rowHeader["KPSLIP_NO"]
						]);
						while($rowData = $getData->fetch(PDO::FETCH_ASSOC)){
							$arrDataPre = array();
							$arrDataPre["TYPE_DESC"] = $rowData["KEEPITEMTYPE_DESC"];
							$arrDataPre["DOCPAY_NO"] = $rowData["LOANCONTRACT_NO"];
							$arrDataPre["PRIN_AMT"] = number_format($rowData["PRINARREAR_AMT"],2);
							$arrDataPre["INT_AMT"] = number_format($rowData["INTARREAR_AMT"],2);
							$arrDataPre["ITEM_AMT"] = number_format($rowData["ITEMARREAR_AMT"],2);
							$arrDataPre["ITEM_AMT_NOTFORMAT"] = $rowData["ITEMARREAR_AMT"];
							$arrData[] = $arrDataPre;
						}
						$filename = trim($rowHeader["KPSLIP_NO"]).date('Ymd');
						$arrayPDF = generateBillLoan($arrData,$header,$lib,$filename);
						if($arrayPDF["RESULT"]){
							$arrayAttach[] = $arrayPDF["PATH"];
							$arrTarget["PATH_PDF"] = $config["URL_SERVICE"].$arrayPDF["PATH"];
							$arrTarget["INVOICE_NO"] = trim($rowHeader["KPSLIP_NO"]);
							$arrMessage = $lib->mergeTemplate($dataComing["subject"],$dataComing["body_root_"],$arrTarget);
							$updateFlagMail = $conoracle->prepare("UPDATE kpkepnotenoughmoneytosms SET mailpost_status = '1',mailpost_date = sysdate 
																WHERE member_no = :member_no and recv_period = :recv_period");
							$updateFlagMail->execute([
								':member_no' => $rowDataPre["MEMBER_NO"],
								':recv_period' => $rowHeader["RECV_PERIOD"]
							]);
						}else{
							$bulkInsertNotSent[] = "('".$arrMessage["SUBJECT"]."','".$arrMessage["BODY"]."','".$mailAsset[0]["EMAIL"]."','0','".$payload["username"]."',null,'0','ไม่สามารถสร้างไฟล์ PDF ได้','".$rowDataPre["MEMBER_NO"]."')";
							if(sizeof($bulkInsertNotSent) == 1000){
								$func->logSendMail($bulkInsertNotSent);
							}
							$conoracle->rollback();
						}
					}
					if(sizeof($arrayAttach) > 0){
						$sendMail = $lib->sendMail($mailAsset[0]["EMAIL"],$arrMessage["SUBJECT"],$arrMessage["BODY"],$mailFunction);
						if($sendMail["RESULT"]){
							$bulkInsertSent[] = "('".$arrMessage["SUBJECT"]."','".$arrMessage["BODY"]."','".$mailAsset[0]["EMAIL"]."','1','".$payload["username"]."',null,'0',null,'".$rowDataPre["MEMBER_NO"]."')";
							if(sizeof($bulkInsertSent) == 1000){
								$func->logSendMail($bulkInsertSent);
							}
							$conoracle->commit();
						}else{
							$bulkInsertNotSent[] = "('".$arrMessage["SUBJECT"]."','".$arrMessage["BODY"]."','".$mailAsset[0]["EMAIL"]."','0','".$payload["username"]."',null,'0','".$sendMail["MESSAGE_ERROR"]."','".$rowDataPre["MEMBER_NO"]."')";
							if(sizeof($bulkInsertNotSent) == 1000){
								$func->logSendMail($bulkInsertNotSent);
							}
							$conoracle->rollback();
						}
					}else{
						$bulkInsertNotSent[] = "('".$arrMessage["SUBJECT"]."','".$arrMessage["BODY"]."','".$mailAsset[0]["EMAIL"]."','0','".$payload["username"]."',null,'0','ไม่สามารถสร้างไฟล์ PDF ได้','".$rowDataPre["MEMBER_NO"]."')";
						if(sizeof($bulkInsertNotSent) == 1000){
							$func->logSendMail($bulkInsertNotSent);
						}
						$conoracle->rollback();
					}
				}else{
					$conoracle->rollback();
				}
			}
			if(sizeof($bulkInsertNotSent) > 0){
				$func->logSendMail($bulkInsertNotSent);
			}
			if(sizeof($bulkInsertSent) > 0){
				$func->logSendMail($bulkInsertSent);
			}
			$arrayResult['RESULT'] = TRUE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
function generateBillLoan($dataReport,$header,$lib,$filename){
	$sumBalance = 0;
	$html = '<head>
			<title>ใบแจ้งหนี้ '.$filename.'</title>
			<style>
				@font-face {
					font-family: THSarabun;
					src: url(../../../resource/fonts/THSarabun.ttf);
				}
				@font-face {
					font-family: "THSarabun";
					src: url(../../../resource/fonts/THSarabun Bold.ttf);
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
			</head>
			<div style="display: flex;text-align: center;position: relative;margin-bottom: 20px;">
			<div style="text-align: left;"><img src="../../../resource/logo/logo.jpg" style="margin: 10px 0 0 5px" alt="" width="80" height="80" /></div>
			<div style="text-align:left;position: absolute;width:100%;margin-left: 140px">';
	$html .= '<p style="margin-top: -5px;font-size: 22px;font-weight: bold">ใบแจ้งชำระหนี้</p>
			<p style="margin-top: -30px;font-size: 22px;font-weight: bold">สหกรณ์ออมทรัพย์กรมป่าไม้ จำกัด</p>
			<p style="margin-top: -27px;font-size: 18px;">ตู้ปณ. 169 ปณศ. จตุจักร กรุงเทพมหานคร 10900</p>
			<p style="margin-top: -25px;font-size: 18px;">โทร. 02-579-7070 โทรสาร 02-579-9774</p>
			<p style="margin-top: -27px;font-size: 19px;font-weight: bold">www.025798899.com</p>
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
			<td style="width: 50px;font-size: 18px;">ชื่อ - สกุล :</td>
			<td style="width: 350px;">'.$header["full_name"].'</td>
			<td style="width: 50px;font-size: 18px;">งวด :</td>
			<td style="width: 101px;">'.$header["recv_period"].'</td>
			</tr>
			</tbody>
			</table>
			</div>
			<div style="border: 0.5px solid black;width: 100%; height: 325px;">
			<div style="display:flex;width: 100%;height: 30px;" class="sub-table">
			<div style="border-bottom: 0.5px solid black;">&nbsp;</div>
			<div style="width: 575px;text-align: center;font-size: 18px;font-weight: bold;border-right : 0.5px solid black;padding-top: 1px;">รายการชำระ</div>
			<div style="width: 110px;text-align: center;font-size: 18px;font-weight: bold;border-right : 0.5px solid black;margin-left: 580px;padding-top: 1px;">เงินต้น</div>
			<div style="width: 120px;text-align: center;font-size: 18px;font-weight: bold;border-right : 0.5px solid black;margin-left: 700px;padding-top: 1px;">ดอกเบี้ย</div>
			<div style="width: 150px;text-align: center;font-size: 18px;font-weight: bold;margin-left: 815px;padding-top: 1px;">รวมเป็นเงิน</div>
			</div>';
			// Detail
	$html .= '<div style="width: 100%;height: 260px" class="sub-table">';
	for($i = 0;$i < sizeof($dataReport); $i++){
		if($i == 0){
			$html .= '<div style="display:flex;height: 30px;padding:0px">
			<div style="width: 110px;border-right: 0.5px solid black;height: 270px;margin-left: 465px;">&nbsp;</div>
			<div style="width: 110px;border-right: 0.5px solid black;height: 250px;margin-left: 580px;">&nbsp;</div>
			<div style="width: 120px;border-right: 0.5px solid black;height: 285px;margin-left: 700px;">&nbsp;</div>
			<div style="width: 560px;text-align: left;font-size: 18px">
				<div>'.$dataReport[$i]["TYPE_DESC"].' '.$dataReport[$i]["DOCPAY_NO"].'</div>
			</div>
			<div style="width: 110px;text-align: right;font-size: 18px;margin-left: 580px;">
			<div>'.($dataReport[$i]["PRIN_AMT"] ?? null).'</div>
			</div>
			<div style="width: 120px;text-align: right;font-size: 18px;margin-left: 700px;">
			<div>'.($dataReport[$i]["INT_AMT"] ?? null).'</div>
			</div>
			<div style="width: 150px;text-align: right;font-size: 18px;margin-left: 814px;">
			<div>'.($dataReport[$i]["ITEM_AMT"] ?? null).'</div>
			</div>
			</div>';
		}else{
			$html .= '<div style="display:flex;height: 30px;padding:0px">
			<div style="width: 560px;text-align: left;font-size: 18px">
				<div>'.$dataReport[$i]["TYPE_DESC"].' '.$dataReport[$i]["DOCPAY_NO"].'</div>
			</div>
			<div style="width: 110px;text-align: right;font-size: 18px;margin-left: 580px;">
			<div>'.($dataReport[$i]["PRIN_AMT"] ?? null).'</div>
			</div>
			<div style="width: 120px;text-align: right;font-size: 18px;margin-left: 700px;">
			<div>'.($dataReport[$i]["INT_AMT"] ?? null).'</div>
			</div>
			<div style="width: 150px;text-align: right;font-size: 18px;margin-left: 814px;">
			<div>'.($dataReport[$i]["ITEM_AMT"] ?? null).'</div>
			</div>
			</div>';
		}
		$sumBalance += $dataReport[$i]["ITEM_AMT_NOTFORMAT"];
	}
	$html .= '</div>';
			// Footer
	$html .= '<div style="display:flex;width: 100%;height: 40px" class="sub-table">
			<div style="border-top: 0.5px solid black;">&nbsp;</div>
			<div style="width: 600px;text-align:center;height: 30px;font-size: 18px;padding-top: 0px;">'.$lib->baht_text($sumBalance).'</div>
			<div style="width: 110px;border-right: 0.5px solid black;height: 30px;margin-left: 465px;padding-top: 0px;">&nbsp;</div>
			<div style="width: 110px;text-align: center;font-size: 18px;padding-top: 0px;height:30px;margin-left: 640px">
			รวมเงิน
			</div>
			<div style="width: 150px;text-align: right;height: 30px;margin-left: 809px;padding-top: 0px;font-size: 18px;">'.number_format($sumBalance,2).'</div>
			</div>
			</div>
			<div style="display:flex;">
			<div style="width:400px;font-size: 18px;">หมายเหตุ : ใบเสร็จฉบับนี้จะสมบูรณ์ก็ต่อเมื่อสหกรณ์ได้รับเงินครบถ้วน</div>
			<div style="width:100px;margin-left: 600px;display:flex;">
			<img src="../../../resource/utility_icon/sig.png" width="100" height="50" style="margin-top:20px;"/>
			<div style="font-size: 18px;margin-left: 150px;margin-top:10px;">ผู้จัดการ</div>
			</div>
			</div>
			<div style="font-size: 18px;margin-left: 590px;margin-top:-10px;">(นายไอโซแคร์ ซิสเต็มส์)</div>
			';

	$dompdf = new DOMPDF();
	$dompdf->set_paper('A4', 'landscape');
	$dompdf->load_html($html);
	$dompdf->render();
	$dompdf->getCanvas()->get_cpdf()->setEncryption($header["birth_date"]);
	$pathfile = __DIR__.'/../../../resource/pdf/billing_loan';
	if(!file_exists($pathfile)){
		mkdir($pathfile, 0777, true);
	}
	$pathfile = $pathfile.'/'.$filename.'.pdf';
	$pathfile_show = '/resource/pdf/billing_loan/'.$filename.'.pdf';
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