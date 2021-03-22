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
		
		$insertQrMaster = $conmysql->prepare("INSERT INTO gcqrcodegenmaster(qrgenerate, member_no, generate_date, expire_date) 
												VALUES (:qrgenerate,:member_no,:generate_date,:expire_date)");
		if($insertQrMaster->execute([
			':qrgenerate' => $randQrRef,
			':member_no' => $member_no,
			':generate_date' => date_format($currentDate,"Y-m-d H:i:s"),
			':expire_date' =>  date_format($expireDate,"Y-m-d H:i:s")
		])){
			//insert success
			foreach ($dataComing["transList"] as $transValue) {
				
				$insertQrDetail = $conmysql->prepare("INSERT INTO gcqrcodegendetail(qrgenerate, trans_code_qr, ref_account, qrtransferdt_amt, qrtransferdt_fee) 
													VALUES (:qrgenerate, :trans_code_qr, :ref_account, :qrtransferdt_amt, :qrtransferdt_fee)");
				if($insertQrDetail->execute([
					':qrgenerate' => $randQrRef,
					':trans_code_qr' => $transValue["trans_code"],
					':ref_account' => $transValue["account_no"],
					':qrtransferdt_amt' => $transValue["amt_transfer"],
					':qrtransferdt_fee' => 0,
				])){
					$qrTransferAmt += $transValue["amt_transfer"];
					$qrTransferFee += 0;
				}else{
					$conmysql->rollback();
					$arrayResult['RESPONSE_CODE'] = "WS9999";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESPONSE_MESSAGE_SOURCE'] = $arrayResult['RESPONSE_MESSAGE'];
					$arrayResult['RESULT'] = FALSE;
					ob_flush();
					echo json_encode($arrayResult);
					exit();
				}
			}
			
			$stringQRGenerate = "|".$config["CROSSBANK_TAX_SUFFIX"]."\r\n".$member_no."\r\n".$randQrRef."\r\n".$qrTransferAmt."\r\n".$qrTransferFee;
			$qrCode = new QrCode($stringQRGenerate);
			header('Content-Type: '.$qrCode->getContentType());
			$qrCode->writeString();
			$qrCode->writeFile(__DIR__.'/../../resource/qrcode/'.$payload["member_no"].$randQrRef.'.png');
			$fullPath = $config["URL_SERVICE"].'/resource/qrcode/'.$payload["member_no"].$randQrRef.'.png';
			header('Content-Type: application/json;charset=utf-8');
			
			$updateQrMaster = $conmysql->prepare("UPDATE gcqrcodegenmaster 
										SET qrtransfer_amt = :qrtransfer_amt, qrtransfer_fee = :qrtransfer_fee, qr_path = :qr_path 
										WHERE qrgenerate = :qrgenerate");
			if($updateQrMaster->execute([
				':qrtransfer_amt' => $qrTransferAmt,
				':qrtransfer_fee' => $qrTransferFee,
				':qr_path' => $fullPath,
				':qrgenerate' => $randQrRef,
			])){
				$conmysql->commit();
				$arrayResult["QRCODE_PATH"] = $fullPath;
				$arrayResult["REF_NO"] = $randQrRef;
				$arrayResult["EXPIRE_DATE"] = date_format($expireDate,"Y-m-d H:i:s");
				$arrayResult["RESULT"] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE_CODE'] = "WS9999";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESPONSE_MESSAGE_SOURCE'] = $arrayResult['RESPONSE_MESSAGE'];
				$arrayResult['RESULT'] = FALSE;
				ob_flush();
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$conmysql->rollback();
			$arrayResult['RESPONSE_CODE'] = "WS9999";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESPONSE_MESSAGE_SOURCE'] = $arrayResult['RESPONSE_MESSAGE'];
			$arrayResult['RESULT'] = FALSE;
			ob_flush();
			echo json_encode($arrayResult);
			exit();
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