<?php
require_once('../autoload.php');

use Dompdf\Dompdf;

$dompdf = new DOMPDF();

if($lib->checkCompleteArgument(['menu_component','doc_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ImportantDoc')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$header = array();
		
		if (empty($dataComing["doc_type"]) || $dataComing["doc_type"] == '') {
			$getRegisterDetail = $conoracle->prepare("
				SELECT 
					TRIM(MBUCFPRENAME.PRENAME_DESC || WCDEPTMASTER.DEPTACCOUNT_NAME ||' '|| WCDEPTMASTER.DEPTACCOUNT_SNAME) as FULL_NAME,
					TRIM(CMUCFCOOPBRANCH.COOPBRANCH_ID) || '-' || WCDEPTMASTER.DEPTACCOUNT_NO AS DOC_NO,
					to_date('01/'||to_char(ADD_MONTHS(WCDEPTMASTER.APPLY_DATE,1),'mm/yyyy'),'dd/mm/yyyy') as DATE_1,
					WCDEPTMASTER.DEPTOPEN_DATE  as DATE_2,
					WCUCFROUNDREGISFIXED.FULL_DATE as DATE_3
				FROM
					WCDEPTMASTER,  MBUCFPRENAME,  CMUCFCOOPBRANCH,WCUCFROUNDREGISFIXED
				WHERE
					WCDEPTMASTER.PRENAME_CODE = MBUCFPRENAME.PRENAME_CODE  and  
					WCDEPTMASTER.COOP_ID = CMUCFCOOPBRANCH.COOP_ID  and  
					WCUCFROUNDREGISFIXED.DEPTOPEN_DATE = WCDEPTMASTER.DEPTOPEN_DATE  and 
					TRIM(WCDEPTMASTER.DEPTACCOUNT_NO) = :member_no
			");
			$getRegisterDetail->execute([
				':member_no' => $member_no
			]);
		} else {
			$getRegisterDetail = $conoracle->prepare("
				SELECT 
					TRIM(MBUCFPRENAME.PRENAME_DESC || WCDEPTMASTER.DEPTACCOUNT_NAME || ' ' || WCDEPTMASTER.DEPTACCOUNT_SNAME) AS FULL_NAME, 
					TRIM(CMUCFCOOPBRANCH.COOPBRANCH_ID) || '-' || TRIM(WCFUNDMASTER.FUNDACCOUNT_NO) AS DOC_NO,
					(select acci_Date from wcucffundround where fundopen_date = WCFUNDMASTER.FUNDOPEN_DATE and fundtype_code =  WCFUNDMASTER.FUNDTYPE_CODE) as DATE_1,
					(select fund_date from wcucffundround where fundopen_date = WCFUNDMASTER.FUNDOPEN_DATE and fundtype_code =  WCFUNDMASTER.FUNDTYPE_CODE) as DATE_2,
					(select FUNDOPEN2_DATE from wcucffundround where fundopen_date = WCFUNDMASTER.FUNDOPEN_DATE and fundtype_code =  WCFUNDMASTER.FUNDTYPE_CODE) as DATE_3
				FROM 
					WCFUNDMASTER,
					WCDEPTMASTER,   
					MBUCFPRENAME,   
					CMUCFCOOPBRANCH  
				WHERE   
					WCFUNDMASTER.DEPTACCOUNT_NO = WCDEPTMASTER.DEPTACCOUNT_NO  and  
					WCDEPTMASTER.PRENAME_CODE = MBUCFPRENAME.PRENAME_CODE  and  
					WCDEPTMASTER.COOP_ID = CMUCFCOOPBRANCH.COOP_ID  and      
					WCFUNDMASTER.FUNDTYPE_CODE = :fundtype_code  AND  
					TRIM(WCDEPTMASTER.DEPTACCOUNT_NO) = :member_no
					ORDER BY WCFUNDMASTER.FUNDACCOUNT_NO
			");
			$getRegisterDetail->execute([
				':member_no' => $member_no,
				':fundtype_code' => $dataComing["doc_type"]
			]);
		}
		
		$arrGroupDetail = array();
		while($rowDetail = $getRegisterDetail->fetch(PDO::FETCH_ASSOC)){
			// $arrDetail = array();
			$arrGroupDetail["date_1"] = $rowDetail["DATE_1"];
			$arrGroupDetail["date_2"] = $rowDetail["DATE_2"];
			$arrGroupDetail["date_3"] = $rowDetail["DATE_3"];
			// $arrGroupDetail[] = $arrDetail;
			
			$header["full_name"] = $rowDetail["FULL_NAME"];
			$header["doc_no"] = $rowDetail["DOC_NO"];
			$header["doc_type"] = $dataComing["doc_type"] ?? "";
			$header["member_no"] = $member_no;
		}

		$arrayPDF = GenerateReport($arrGroupDetail,$header,$lib);
		if($arrayPDF["RESULT"]){
			if ($forceNewSecurity == true) {
				$arrayResult['REPORT_URL'] = $config["URL_SERVICE"]."/resource/get_resource?id=".hash("sha256", $arrayPDF["PATH"]);
				$arrayResult["REPORT_URL_TOKEN"] = $lib->generate_token_access_resource($arrayPDF["PATH"], $jwt_token, $config["SECRET_KEY_JWT"]);
			} else {
				$arrayResult['REPORT_URL'] = $config["URL_SERVICE"].$arrayPDF["PATH"];
			}
			$arrayResult['RESULT'] = TRUE;
			$arrayResult['ALLOW_SHARE'] = TRUE;
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
	$html = '
	<!DOCTYPE html>
	<html lang="en">
	<head>
		<meta charset="UTF-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Document</title>
	</head>
	<body>
		<meta charset="UTF-8">
			<style>
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
				  padding: 0 ;
				  font-size: 17pt;
				  line-height: 23px;
				}
				div{
					line-height: 23px;
					font-size: 17pt;
				}
				.nowrap{
					white-space: nowrap;
				  }
				.center{
					text-align:center;
				}
				.left{
					text-align:left;
				}
				.right{
					text-align:right;
				}
				.flex{
					display:flex;
				}
				.bold{
					font-weight:bold;
				}
				.list{
					padding-left:50px;
				}
				.sub-list{
					padding-left:100px;
				}
				th{
					border:1px solid;
					text-align:center;
					padding-bottom:5px;
				}
				td{
					line-height:15px;
					padding:5px;
				}
				.absolute{
					position:absolute;
				}
				.data{
					font-size:15pt;
					margin-top:-2px;
				}
				.border{
					border: 1px solid;
				}
				.border-left{
					border-left: 1px solid;
				}
				.border-right{
					border-right: 1px solid;
				}
				.border-top{
					border-top: 1px solid;
				}
				.border-bottom
					border-bottom: 1px solid;
				}
				.wrapper-page {
					page-break-after: always;
				  }
				  .spac{
					margin-top:15px;
				  }
				  .tab{
					padding-left:15px;
				  }
				  
				.wrapper-page:last-child {
					page-break-after: avoid;
				}
				.inline{
					display:inline;
				}
				.border-collapse{
					border-collapse: collapse;
				}
				
				width-full{
					width:100%;
				}
				@page { size: 210mm 148mm; }
				</style>';
	//ขนาด @page { size: 210mm 148mm; }
	$html .= '<div  style="margin:-44px -44px -44px -44px;" >';
	//หน้า 1
	$html .= '<div class="wrapper-page">';
	
	if ($header["doc_type"] == '001') {
	$html .= '
		<img src="../../resource/important_doc/reg_fund001.jpg" style="position: absolute; width:100%; left: 0%; right: 0%; top: 0%; bottom: 0%; z-index: 1;">
		<div  style="margin-top:290px; z-index:100">
			<div class="center bold">' . ($header["full_name"] ?? null) . '</div>
		</div>
		<div style="margin-top:43px; ; z-index:100">
			<div style="margin-left:365px; font-size:16pt" class="bold">' . ($header["doc_no"] ?? null) . '</div>
			<div>
				<div class="bold" style="float:left; margin-top: 10px; margin-left: 300px;">' . ($lib->convertdate($dataReport["date_1"],'d M') ?? null) . '</div> 
				<div class="bold" style="float:left; margin-top: 10px; margin-left: 130px;">' . (substr($lib->convertdate($dataReport["date_1"],'d M Y'), -4) ?? null) . '</div> 
			</div> 
		</div>
		';
	} else if ($header["doc_type"] == '002') {
	$html .= '
		<img src="../../resource/important_doc/reg_fund002.jpg" style="position: absolute; width:100%; left: 0%; right: 0%; top: 0%; bottom: 0%; z-index: 1;">
		<div  style="margin-top:270px; z-index:100">
			<div class="center bold">' . ($header["full_name"] ?? null) . '</div>
		</div>
		<div style="margin-top:30px; ; z-index:100;">
			<div style="margin-left:350px; font-size:16pt" class="bold">' . ($header["doc_no"] ?? null) . '</div>
			
			<div style="clear: both;"></div>
			<div class="bold" style="float:left; height: 20px; width: 38%; text-align: right; font-size:12pt;">' . ($lib->convertdate($dataReport["date_1"],'d M') ?? null) . '</div> 
			<div class="bold" style="float:left; height: 20px; margin-left: 95px; font-size:12pt">' . (substr($lib->convertdate($dataReport["date_1"],'d M Y'), -4) ?? null) . '</div> 
			
			<div style="clear: both;"></div>
			<div class="bold" style="float:left; height: 20px; width: 38%; text-align: right; font-size:12pt;">' . ($lib->convertdate($dataReport["date_2"],'d M') ?? null) . '</div> 
			<div class="bold" style="float:left; height: 20px; margin-left: 95px; font-size:12pt">' . (substr($lib->convertdate($dataReport["date_2"],'d M Y'), -4) ?? null) . '</div> 
			
			<div style="clear: both;"></div>
			<div class="bold" style="float:left; height: 20px; width: 38%; text-align: right; font-size:12pt;">' . ($lib->convertdate($dataReport["date_3"],'d M') ?? null) . '</div> 
			<div class="bold" style="float:left; height: 20px; margin-left: 95px; font-size:12pt">' . (substr($lib->convertdate($dataReport["date_3"],'d M Y'), -4) ?? null) . '</div> 
		</div>
		';
	} else {
	$html .= '
		<img src="../../resource/important_doc/reg_cpct.jpg" style="position: absolute; width:100%; left: 0%; right: 0%; top: 0%; bottom: 0%; z-index: 1;">
		<div style="margin-top:245px; z-index:100;">
			<div class="bold" style="float: right; width: 28%;">' . ($header["doc_no"] ?? null) . '</div>
			<div class="bold center" style="float: right; width: 52%;">' . ($header["full_name"] ?? null) . '</div>
		</div>
		<div style="margin-top:95px; ; z-index:100">
			<div style="clear: both;"></div>
			<div style="float: right; height: 20px;">
				<div class="bold" style="font-size:12pt; display:inline-block; margin-right: 2px;">' . ($lib->convertdate($dataReport["date_1"],'d M') ?? null) . '</div>
				<div class="bold" style="font-size:12pt; display:inline-block; margin-right: 340px;">' . (substr($lib->convertdate($dataReport["date_1"],'d M Y'), -4) ?? null) . '</div>
			</div>
			<div style="clear: both;"></div>
			<div style="float: right; height: 20px;">
				<div class="bold" style="font-size:12pt; display:inline-block; margin-right: 2px;">' . ($lib->convertdate($dataReport["date_2"],'d M') ?? null) . '</div>
				<div class="bold" style="font-size:12pt; display:inline-block; margin-right: 340px;">' . (substr($lib->convertdate($dataReport["date_2"],'d M Y'), -4) ?? null) . '</div>
			</div>
			<div style="clear: both;"></div>
			<div style="float: right; height: 20px;">
				<div class="bold" style="font-size:12pt; display:inline-block; margin-right: 2px;">' . ($dataReport["date_3"] ? $lib->convertdate($dataReport["date_3"],'d M') ?? null : null) . '</div>
				<div class="bold" style="font-size:12pt; display:inline-block; margin-right: 340px;">' . ($dataReport["date_3"] ? substr($lib->convertdate($dataReport["date_3"],'d M Y'), -4) ?? null : null) . '</div>
			</div>
		</div>';
	}

	$html .= '
			</div>
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
	$pathfile = $pathfile.'/'.(trim($header["member_no"]) ?? '').'_'.($header["doc_type"] ?? 'reg').'.pdf';
	$pathfile_show = '/resource/pdf/keeping_monthly/'.(trim($header["member_no"]) ?? '').'_'.($header["doc_type"] ?? 'reg').'.pdf?v='.time();
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