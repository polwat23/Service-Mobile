<?php
require_once('../autoload.php');

use Endroid\QrCode\QrCode;

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'GenerateQR')){
		$conmysql->beginTransaction();
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$currentDate = date_create();
		$tempExpire = new DateTime(date_format($currentDate,"Y-m-d H:i:s"));
		$amt_transferArr = explode('.',$dataComing["amt_transfer"]);

		$randQrRef = date_format($currentDate,"YmdHis").rand(1000,9999);
		$generateDate = date_format($currentDate,"Y-m-d H:i:s").rand(1000,9999);
		$qrTransferAmt = 0;
		$qrTransferFee = 0;
		$expireDate = $tempExpire->add(new DateInterval('PT15M'));
		if(date_format($expireDate,"His") > "235959"){
			$expireDate = date("Y-m-d")." 23:59:59";
		}else{
			$expireDate = date_format($expireDate,"Y-m-d H:i:s");
		}
		$insertQrMaster = $conmysql->prepare("INSERT INTO gcqrcodegenmaster(qrgenerate, member_no, generate_date, expire_date,id_userlogin,app_version) 
												VALUES (:qrgenerate,:member_no,:generate_date,:expire_date,:id_userlogin,:app_version)");
		if($insertQrMaster->execute([
			':qrgenerate' => $randQrRef,
			':member_no' => $member_no,
			':generate_date' => date_format($currentDate,"Y-m-d H:i:s"),
			':expire_date' => $expireDate,
			':id_userlogin' => $payload["id_userlogin"],
			':app_version' => $dataComing["app_version"]
		])){
			//insert success
			$dataLinkTrancode = '';
			foreach ($dataComing["transList"] as $transValue) {
				if($transValue["trans_code"] !== '02'){
					$account_no = preg_replace('/-/','',$transValue["account_no"] ?? $member_no);
				}else{
					$account_no = $transValue["account_no"];
				}
				$insertQrDetail = $conmysql->prepare("INSERT INTO gcqrcodegendetail(qrgenerate, trans_code_qr, ref_account, qrtransferdt_amt, qrtransferdt_fee) 
													VALUES (:qrgenerate, :trans_code_qr, :ref_account, :qrtransferdt_amt, :qrtransferdt_fee)");
				if($insertQrDetail->execute([
					':qrgenerate' => $randQrRef,
					':trans_code_qr' => $transValue["trans_code"],
					':ref_account' => $account_no,
					':qrtransferdt_amt' => $transValue["amt_transfer"],
					':qrtransferdt_fee' => 0,
				])){
					if($dataComing["trans_mode"] === 'coop'){
						if($transValue["trans_code"] === '01'){
							$dataLinkTrancode = "ibafcoop://app/transferdepinsidecoop?destination=".$account_no."&amount=".$transValue["amt_transfer"];
						}else if($transValue["trans_code"] === '02'){
							$dataLinkTrancode = "ibafcoop://app/transferdeppayloan?destination=".$transValue["account_no"]."&amount=".$transValue["amt_transfer"];
						}else if($transValue["trans_code"] === '03'){
							$dataLinkTrancode = "ibafcoop://app/transferdepbuyshare?destination=".$member_no."&amount=".$transValue["amt_transfer"];
						}
					}else{
						$qrTransferAmt += $transValue["amt_transfer"];
						$qrTransferFee += 0;
					}
				}else{
					$conmysql->rollback();
					$arrayResult['RESPONSE_CODE'] = "WS1013";
					$filename = basename(__FILE__, '.php');
					$logStruc = [
						":error_menu" => $filename,
						":error_code" => "WS1013",
						":error_desc" => "ไม่สามารถ Insert ลง gcqrcodegendetail"."\n".json_encode($dataComing),
						":error_device" => $arrPayload["PAYLOAD"]["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
					];
					$log->writeLog('errorusage',$logStruc);
					$message_error = "ไม่สามารถ Insert ลง gcqrcodegendetail"."\n"."Query => ".$insertQrDetail->queryString."\n"."Data => ".json_encode([
						':qrgenerate' => $randQrRef,
						':trans_code_qr' => $transValue["trans_code"],
						':ref_account' => $account_no,
						':qrtransferdt_amt' => $transValue["amt_transfer"],
						':qrtransferdt_fee' => 0,
					]);
					$lib->sendLineNotify($message_error);
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
				}
			}
			$qrTransferAmtFormat = number_format($qrTransferAmt, 2, '', '');
			$qrTransferFeeFormat = number_format($qrTransferFee, 2, '', '');
			if($dataComing["trans_mode"] == 'coop'){
				$stringQRGenerate = $dataLinkTrancode;
			}else{
				$stringQRGenerate = "|".$config["CROSSBANK_TAX_SUFFIX"]."\r\n".$member_no."\r\n".$randQrRef."\r\n".$qrTransferAmtFormat;
			}
			/*$qrCode = new QrCode($stringQRGenerate);
			header('Content-Type: '.$qrCode->getContentType());
			$qrCode->writeString();
			$qrCode->writeFile(__DIR__.'/../../resource/qrcode/'.$payload["member_no"].$randQrRef.'.png');
			$fullPath = $config["URL_SERVICE"].'/resource/qrcode/'.$payload["member_no"].$randQrRef.'.png';
			header('Content-Type: application/json;charset=utf-8');*/
			
			$updateQrMaster = $conmysql->prepare("UPDATE gcqrcodegenmaster 
										SET qrtransfer_amt = :qrtransfer_amt, qrtransfer_fee = :qrtransfer_fee, qr_path = :qr_path 
										WHERE qrgenerate = :qrgenerate");
			if($updateQrMaster->execute([
				':qrtransfer_amt' => $qrTransferAmt,
				':qrtransfer_fee' => $qrTransferFee,
				':qr_path' => $fullPath,
				':qrgenerate' => $randQrRef,
			])){
				$qrData = [];
				$memberInfo = $conoracle->prepare("SELECT mp.PTITLE_NAME as PRENAME_SHORT,MB.FNAME as MEMB_NAME,MB.LNAME as MEMB_SURNAME
												FROM 
												MEM_H_MEMBER MB LEFT JOIN MEM_M_PTITLE MP ON mb.ptitle_id = mp.ptitle_id
												WHERE mb.account_id = :member_no");
				$memberInfo->execute([':member_no' => $member_no]);
				$rowMember = $memberInfo->fetch(PDO::FETCH_ASSOC);
				
				$qrData["ACC_NAME"] = $rowMember["PRENAME_SHORT"].$rowMember["MEMB_NAME"]." ".$rowMember["MEMB_SURNAME"];
				if($dataComing["transList"][0]["trans_code"] == "01"){
					$qrData["QR_TYPE_DESC"] = "QR Code ฝากเงิน";
					$qrData["ACC_NO"] = $dataComing["transList"][0]["account_no"];
				}else if($dataComing["transList"][0]["trans_code"] == "02"){
					$qrData["QR_TYPE_DESC"] = "QR Code ชำระสินเชื่อ";
					$qrData["ACC_NO"] = $dataComing["transList"][0]["account_no"];
				}else if($dataComing["transList"][0]["trans_code"] == "03"){
					$qrData["QR_TYPE_DESC"] = "QR Code ค่าหุ้น";
					$qrData["ACC_NO"] = "";
				}
				$qrData["AMT_TRANSFER"] = number_format($dataComing["transList"][0]["amt_transfer"],2);
				$qrData["FEE"] = number_format(0,2);
				$qrData["datainQR"] = $stringQRGenerate;
				$qrData["OPERATE_DATE"] = $lib->convertdate(date_format($currentDate,"Y-m-d"),"D m Y")." - ".date_format($currentDate,"H : i");
				$qrGen = $lib->generateQrCodeImg($qrData);
				if($qrGen->result){
					$conmysql->commit();
					$arrayResult["QRCODE_PATH"] = $fullPath;
					$arrayResult["REF_NO"] = $randQrRef;
					$arrayResult["EXPIRE_DATE"] = $expireDate;
					$arrayResult["QRCODE_IMG"] = "data:image/png;base64, ".$qrGen->response;
					$arrayResult["RESULT"] = TRUE;
					require_once('../../include/exit_footer.php');
				}else{
					$conmysql->rollback();
					$arrayResult['RESPONSE_CODE'] = "WS1013";
					$filename = basename(__FILE__, '.php');
					$logStruc = [
						":error_menu" => $filename,
						":error_code" => "WS1013",
						":error_desc" => "ไม่สามารถสร้าง Qr Code ได้"."\n".json_encode($dataComing),
						":error_device" => $arrPayload["PAYLOAD"]["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
					];
					$log->writeLog('errorusage',$logStruc);
					$message_error = "ไม่สามารถสร้าง Qr Code ได้"."\n"."Query => ".$insertQrMaster->queryString."\n"."Data => ".json_encode([
						':qrgenerate' => $randQrRef,
						':member_no' => $member_no,
						':generate_date' => date_format($currentDate,"Y-m-d H:i:s"),
						':expire_date' => $expireDate,
						':id_userlogin' => $payload["id_userlogin"],
						':app_version' => $dataComing["app_version"]
					]);
					$lib->sendLineNotify($message_error);
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
				}
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE_CODE'] = "WS1013";
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS1013",
					":error_desc" => "ไม่สามารถ Update ลง gcqrcodegenmaster"."\n".json_encode($dataComing),
					":error_device" => $arrPayload["PAYLOAD"]["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$message_error = "ไม่สามารถ Update ลง gcqrcodegenmaster"."\n"."Query => ".$updateQrMaster->queryString."\n"."Data => ".json_encode([
					':qrtransfer_amt' => $qrTransferAmt,
					':qrtransfer_fee' => $qrTransferFee,
					':qr_path' => $fullPath,
					':qrgenerate' => $randQrRef,
				]);
				$lib->sendLineNotify($message_error);
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		}else{
			$conmysql->rollback();
			$arrayResult['RESPONSE_CODE'] = "WS1013";
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS1013",
				":error_desc" => "ไม่สามารถ Insert ลง gcqrcodegenmaster"."\n".json_encode($dataComing),
				":error_device" => $arrPayload["PAYLOAD"]["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ไม่สามารถ Insert ลง gcqrcodegenmaster"."\n"."Query => ".$insertQrMaster->queryString."\n"."Data => ".json_encode([
				':qrgenerate' => $randQrRef,
				':member_no' => $member_no,
				':generate_date' => date_format($currentDate,"Y-m-d H:i:s"),
				':expire_date' => $expireDate,
				':id_userlogin' => $payload["id_userlogin"],
				':app_version' => $dataComing["app_version"]
			]);
			$lib->sendLineNotify($message_error);
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
?>