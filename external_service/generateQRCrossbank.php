<?php
ini_set('display_errors', false);
ini_set('error_log', __DIR__.'/../log/external_error.log');
header("Access-Control-Allow-Methods: POST");

require_once(__DIR__.'/../extension/vendor/autoload.php');
require_once(__DIR__.'/../include/connection.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/validate_input.php');

if( isset( $_SERVER['HTTP_ACCEPT_ENCODING'] ) && substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') ) {
   ob_start("ob_gzhandler");
}else{
   ob_start();
}

use Utility\library;
use Connection\connection;
use Endroid\QrCode\QrCode;

$con = new connection();
$lib = new library();
$conmysql = $con->connecttomysql();

$lang_locale = "th";

$jsonConfig = file_get_contents(__DIR__.'/../config/config_constructor.json');
$config = json_decode($jsonConfig,true);
$jsonConfigError = file_get_contents(__DIR__.'/../config/config_indicates_error.json');
$configError = json_decode($jsonConfigError,true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if(isset($dataComing) && $lib->checkCompleteArgument(['member_no','transList'],$dataComing)){
		$conmysql->beginTransaction();
		$currentDate = date_create();
		$tempExpire = new DateTime(date_format($currentDate,"Y-m-d H:i:s"));

		$randQrRef = $dataComing["refer_qr"] ?? date_format($currentDate,"YmdHis").rand(1000,9999);
		$generateDate = date_format($currentDate,"Y-m-d H:i:s").rand(1000,9999);
		$qrTransferAmt = 0;
		$qrTransferFee = 0;
		$expireDate = $tempExpire->add(new DateInterval('PT'.($dataComing["expire_minutes"] ?? 15).'M'));

		$insertQrMaster = $conmysql->prepare("INSERT INTO gcqrcodegenmaster(qrgenerate, member_no, generate_date, expire_date) 
												VALUES (:qrgenerate,:member_no,:generate_date,:expire_date)");
		if($insertQrMaster->execute([
			':qrgenerate' => $randQrRef,
			':member_no' => $dataComing["member_no"],
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
					require_once('../include/exit_footer.php');
				}
			}
			$qrTransferAmtFormat = number_format($qrTransferAmt, 2, '', '');
			$qrTransferFeeFormat = number_format($qrTransferFee, 2, '', '');
			$stringQRGenerate = "|".($dataComing["biller_id"] ?? $config["CROSSBANK_TAX_SUFFIX"])."\r\n".$dataComing["member_no"]."\r\n".$randQrRef."\r\n".str_replace('.','',$qrTransferAmtFormat)."\r\n".str_replace('.','',$qrTransferFeeFormat);
			$qrCode = new QrCode($stringQRGenerate);
			header('Content-Type: '.$qrCode->getContentType());
			$qrCode->writeString();
			$qrCode->writeFile(__DIR__.'/../resource/qrcode/'.$dataComing["member_no"].$randQrRef.'.png');
			$fullPath = $config["URL_SERVICE"].'/resource/qrcode/'.$dataComing["member_no"].$randQrRef.'.png';
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
				require_once('../include/exit_footer.php');
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE_CODE'] = "WS9999";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../include/exit_footer.php');
			}
		}else{
			$conmysql->rollback();
			$arrayResult['RESPONSE_CODE'] = "WS9999";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../include/exit_footer.php');
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS4004";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(400);
		require_once('../include/exit_footer.php');
	}
}else{
	http_response_code(500);
}
?>