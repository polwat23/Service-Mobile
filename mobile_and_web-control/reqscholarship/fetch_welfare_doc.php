<?php
require_once('../autoload.php');
use Dompdf\Dompdf;

if($lib->checkCompleteArgument(['menu_component','req_doc'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ScholarshipRequest')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
	
		$arrayPDF = GenerateReport($dataComing["req_doc"],$lib,$func);
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

function GenerateReport($dataReport,$lib,$func){
	$account_no = $lib->formataccount($dataReport["DEPTACCOUNT_NO"],$func->getConstant('dep_format'));
	$account_no = $lib->formataccount_hidden($account_no,$func->getConstant('hidden_dep'));
	
	$html = '<style>
				@font-face {
				  font-family: AngsanaUPC;
				  src: url(../../resource/fonts/AngsanaUPC.ttf);
				}
				@font-face {
					font-family: AngsanaUPC;
					src: url(../../resource/fonts/AngsanaUPC Bold.ttf);
					font-weight: bold;
				}
				* {
				  font-family: AngsanaUPC;
				  font-size: 14pt;
				}
				.sub-table div{
					padding : 5px;
				}
			</style>
			<div>
				<div style="display: flex;text-align: center;position: relative;margin-bottom: 20px;">
					<img src="../../resource/logo/logo.jpg" alt="" width="80" height="80" />
				</div>
				<div style="text-align: center; font-weight: bold;">
					สหกรณ์ออมทรัพย์มหาวิทยาลัยมหิดล จำกัด
				</div>
				<div style="text-align: center; font-weight: bold;">
					ใบยืนยันการขอรับทุนการศึกษาบุตรสมาชิก
				</div>
				<div style="text-align: center; font-weight: bold;">
					ปีการศึกษา '.$dataReport["SCHOLARSHIP_YEAR"].'
				</div>
				<div style="margin-top: 12px;line-height: 1.3;">
					เลขที่เอกสาร<span style="position: relative;">..............................<span style="position: absolute; left: 12px;font-weight: bold;top: -0.3em;">'.$dataReport["ASNREQUEST_DOCNO"].'</span></span><span style="margin-left: 8px;">
					วันที่<span style="position: relative;">..............................<span style="position: absolute; left: 12px;font-weight: bold;top: -0.3em;">'.$lib->convertdate($dataReport["ASNREQUEST_DATE"],"D m Y").'</span></span></span>
				</div>
				<div style="line-height: 1.3;">
				ข้าพเจ้าชื่อ<span style="position: relative;">.................................................................<span style="position: absolute; left: 12px;font-weight: bold;top: -2px;top: -0.3em;">'.$dataReport["PRENAME_DESC"].''.$dataReport["MEMB_NAME"].' '.$dataReport["MEMB_SURNAME"].'</span></span><span style="margin-left: 8px;">
					สมาชิกเลขทะเบียนที่<span style="position: relative;">.........................<span style="position: absolute; left: 12px;font-weight: bold;top: -0.3em;">'.$dataReport["MEMBER_NO"].'</span></span></span>
					</span>
				</div>
				<div style="line-height: 1.3;">
					บัญชีเงินฝากออมทรัพย์เลขที่<span style="position: relative;">.......................................<span style="position: absolute; left: 12px;font-weight: bold;top: -0.3em;">'.$account_no.'</span></span>
				</div>
				<div style="line-height: 1.3;padding-top: 12px;">
					ประเภททุนการศึกษา<span style="position: relative;">.........................................................<span style="position: absolute; left: 12px;font-weight: bold;top: -0.3em;">'.$dataReport["TYPE_DESC"].'</span></span>
				</div>
				<div style="line-height: 1.3;">
					ชื่อ - นามสกุล บุตร<span style="position: relative;padding-right: 12px">...................................................................<span style="position: absolute; left: 12px;font-weight: bold;top: -0.3em;">'.$dataReport["CHILD_PRENAME"].''.$dataReport["CHILD_NAME"].' '.$dataReport["CHILD_SURNAME"].'</span></span><span style="margin-left: 8px;">
						อายุ<span style="position: relative;">..................<span style="position: absolute; left: 12px;font-weight: bold;top: -0.3em;">'.$lib->count_duration($dataReport["CHILDBIRTH_DATE"],"y").'</span></span>
					</span>
				</div>
				<div style="line-height: 1.3;">
					เลขบัตรประจำตัวประชาชน<span style="position: relative;">.......................................<span style="position: absolute; left: 12px;font-weight: bold;top: -0.3em;">x-xxxx-xxxxx-'.substr($dataReport["CHILDCARD_ID"], 10, 1).''.substr($dataReport["CHILDCARD_ID"], 11, 1).'-'.substr($dataReport["CHILDCARD_ID"], 12, 1).'</span></span>
				</div>
				<div style="line-height: 1.3;">
					ขอทุนในระดับ<span style="position: relative;">............................................................<span style="position: absolute; left: 12px;font-weight: bold;top: -2px;top: -0.3em;">'.$dataReport["LEVEL_DESC"].'</span></span>
				</div>
				<div>
					โรงเรียน / วิทยาลัย / สถาบัน / มหาวิทยาลัย
				</div>
				<div style="line-height: 1.3;">
					<span style="position: relative;">..............................................................................................................................<span style="left: 0;position: absolute; font-weight: bold;top: -2px;top: -0.3em;line-height: 1.3;width:400px;word-wrap:break-word;">'.$dataReport["SCHOOL_NAME"].'</span></span>
				</div>
				<div style="font-weight: bold;padding-top: 4px;padding-top: 24px;font-size: 12pt;">
					หมายเหตุ : โปรดเก็บเอกสารขอรับทุนการศึกษาฉบับนี้ไว้เป็นหลักฐานจนกว่าจะสิ้นสุดการประกาศผลการ<br />พิจารณากรณีมีปัญหาให้นำเอกสารฉบับนี้มาติดต่อเจ้าหน้าที่
				</div>
			</div>
			';

	$dompdf = new Dompdf([
		'fontDir' => realpath('../../resource/fonts'),
		'chroot' => realpath('/'),
		'isRemoteEnabled' => true
	]);
	$dompdf->set_paper('a5');
	$dompdf->load_html($html);
	$dompdf->render();
	$pathfile = __DIR__.'/../../resource/pdf/scholarshipreq';
	if(!file_exists($pathfile)){
		mkdir($pathfile, 0777, true);
	}
	$pathfile = $pathfile.'/'.$dataReport["MEMBER_NO"].$dataReport["ASNREQUEST_DOCNO"].'.pdf';
	$pathfile_show = '/resource/pdf/scholarshipreq/'.$dataReport["MEMBER_NO"].$dataReport["ASNREQUEST_DOCNO"].'.pdf?v='.time();
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