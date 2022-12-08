<?php
require_once('../autoload.php');

use Dompdf\Dompdf;

$dompdf = new DOMPDF();

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'FundInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$header = array();
		$getPaymentDetail = $conmssql->prepare("SELECT S.DEPTSLIP_NO,M.MEMBER_NO,M.DEPTACCOUNT_NO,P.PRENAME_DESC,
															M.DEPTACCOUNT_NAME,DEPTACCOUNT_SNAME,S.DEPTSLIP_DATE,S.DEPTITEMTYPE_CODE,
															S.DEPTSLIP_AMT,S.CASH_TYPE,S.ENTRY_ID,
															D.SEQ_NO,D.SLIP_DESC,B.WFTYPE_CODE,
															B.WCMEMBERTYPE_DESC,B.WCMEMBER_DESC,D.PRNCSLIP_AMT,DBO.FT_READTHAIBAHT(S.DEPTSLIP_AMT) AS MONEY_THAIBAHT ,
															C.MONEYTYPE_DESC,MG.MEMBGROUP_CODE,MG.MEMBGROUP_DESC,P.PRENAME_DESC AS MEMBER_PRENAME_DISC,MM.MEMB_NAME,MM.MEMB_SURNAME,MG.MEMBGROUP_DESC ,
															MG.MEMBGROUP_CODE,
												(CASE WHEN S.CASH_TYPE ='CSH' THEN 'เงินสด'
												WHEN S.CASH_TYPE ='CBT' THEN 'โอนธนาคาร'
												WHEN S.CASH_TYPE ='TRN' THEN 'โอนภายใน'
												WHEN S.CASH_TYPE ='LON' THEN ''  ELSE '' END ) as CASH_TYPE
												FROM WCDEPTSLIP S
												LEFT JOIN WCDEPTMASTER M ON S.DEPTACCOUNT_NO = M.DEPTACCOUNT_NO
												LEFT JOIN MBMEMBMASTER MM ON M.MEMBER_NO = MM.MEMBER_NO
												LEFT JOIN WCDEPTSLIPDET D ON S.DEPTSLIP_NO = D.DEPTSLIP_NO
												LEFT JOIN MBUCFPRENAME P ON MM.PRENAME_CODE = P.PRENAME_CODE
												LEFT JOIN WCMEMBERTYPE B ON M.WFTYPE_CODE = B.WFTYPE_CODE
												LEFT JOIN CMUCFMONEYTYPE C ON S.CASH_TYPE = C.MONEYTYPE_CODE
												LEFT JOIN MBUCFMEMBGROUP MG ON MM.MEMBGROUP_CODE = MG.MEMBGROUP_CODE
										WHERE S.DEPTSLIP_NO = :deptslip_no AND ITEM_STATUS=1 
										ORDER BY S.DEPTSLIP_NO");
		$getPaymentDetail->execute([
			':deptslip_no' => $dataComing["deptslip_no"]
		]);
			
		$arrGroupDetail = array();
		while($rowDetail = $getPaymentDetail->fetch(PDO::FETCH_ASSOC)){
			$arrDetail = array();
			$header["deptslip_date"] = $lib->convertdate($rowDetail["DEPTSLIP_DATE"],'D m Y');
			$header["name"] = $rowDetail["PRENAME_DESC"].$rowDetail["DEPTACCOUNT_NAME"]." ".$rowDetail["DEPTACCOUNT_SNAME"];
			$header["wcmember_desc"] = $rowDetail["WCMEMBER_DESC"];
			$header["wftype_code"] = $rowDetail["WFTYPE_CODE"];
			$header["membgroup_desc"] = $rowDetail["MEMBGROUP_CODE"]." ".$rowDetail["MEMBGROUP_DESC"];
			$header["cash_type"] = $rowDetail["CASH_TYPE"];
			$arrDetail["slip_desc"] = $rowDetail["SLIP_DESC"]." - (".$member_no.") ".$rowDetail["PRENAME_DESC"].$rowDetail["DEPTACCOUNT_NAME"]." ".$rowDetail["DEPTACCOUNT_SNAME"];
			$arrDetail["deptslip_amt"] = number_format($rowDetail["DEPTSLIP_AMT"],2);
			$arrDetail["amt"] = $rowDetail["DEPTSLIP_AMT"];
			$arrGroupDetail[] = $arrDetail;
		}	
		$header["member_no"] = $member_no;
		$header["deptslip_no"] = $dataComing["deptslip_no"];
		file_put_contents('response.txt', json_encode($dataComing["deptslip_no"],JSON_UNESCAPED_UNICODE ) . PHP_EOL, FILE_APPEND);
		$arrayPDF = GenerateReport($arrGroupDetail,$header,$lib);
		if($arrayPDF["RESULT"]){

			if ($forceNewSecurity == true) {
				$arrayResult['REPORT_URL'] = $config["URL_SERVICE"]."/resource/get_resource?id=".hash("sha256", $arrayPDF["PATH"]);
				$arrayResult["REPORT_URL_TOKEN"] = $lib->generate_token_access_resource($arrayPDF["PATH"], $jwt_token, $config["SECRET_KEY_JWT"]);
			} else {
				$arrayResult['REPORT_URL'] = $config["URL_SERVICE"].$arrayPDF["PATH"];
			}

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
	$sumBalance = 0;
	if($header["wftype_code"] == '00'){
		$logo = 'logo1';
	}else if($header["wftype_code"] == '01'){
		$logo = 'logo_welfare';
	}else if($header["wftype_code"] == '02'){
		$logo = 'logo_welfare';
	}else if($header["wftype_code"] == '03'){
		$logo = 'logo_consortium';
	}
	
	$html = '
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
	  font-size:13pt;
	  line-height: 15px;

	}
	.text-center{
	  text-align:center
	}
	.text-right{
	  text-align:right
	}
	.nowrap{
	  white-space: nowrap;
	}
	.wrapper-page {
	  page-break-after: always;
	}

	.wrapper-page:last-child {
	  page-break-after: avoid;
	}
	table{
	  border-collapse: collapse;
	  line-height: 15px
	}
	th,td{
	  border:0.25px solid #28b3f4 ;
	  text-align:center;
	  color:#47aaff;
	}
	th{
	  font-size:16pt;
	}

	p{
	  margin:0px;
	}
	.text-color{
	  color:#47aaff;
	}
	.data-text-color{
	  color:#000000;
	}

	@page { size: 13.97cm 13.71cm; }
	</style>
	';

	//@page = ขนาดของกระดาษ 13.97 cm x  13.71 cm
	//ระยะขอบ
	$html .= '<div style=" margin: -30px -30px -50px -30px; ">';


	$html .= '
	  <div style="display:flex; height:60px;"  class="nowrap">
		  <div style="positionabsolute; margin-left:20px; ">
			<img src="../../resource/logo/'.$logo.'.png" style="width:55px" />
		  </div>
		   <div style="margin-left:70px; line-height:12px" class="text-center text-color">
			<div style="font-size:9pt; font-weight:bold;">กองทุนสวัสดิการสมาชิกสหกรณ์ออมทรัพย์สาธารณสุขหนองคาย</div>
			<div style="font-size:9pt;">22/1 ถ.ศูนย์ราชการ ต.หนองกอมเกาะ อ.เมือง จ.หนองคาย 43000 </div>
			<div style="font-size:9pt;"> Tel. 042-420750, 042-42074 Fax. 042420750 </div>
			<div style="font-size:9pt; margin-top:-3px;">Email:support@nkbkcoop.com ตรวจสอบข้อมูลกองทุนของท่านได้ที่: www.nkbk.coop.com</div>
      </div>
	  </div>
	  <div style="margin-left:165px; padding:7px 40px; font-size:14pt; font-weight:bold; width:80px; background-color:#28b3f4; color:#ffffff; margin-bottom:10px; ">ใบเสร็จรับเงิน</div>
	  <div style="display:flex; height:30px; " class="text-color">
		<div style="margin-left:10px;">เลขที่ใบเสร็จ:</div>
		<div style="margin-left:95px;" class="data-text-color">'.($header["deptslip_no"]??null).'</div>
		<div style="margin-left:250px;">วันที่:</div>
		<div style="margin-left:290px;" class="data-text-color">'.($header["deptslip_date"]??null).'</div>
	  </div>
	  <div style="display:flex; height:30px; "class="text-color">
		<div style="margin-left:10px;">ได้รับเงินจาก:</div>
		<div style="margin-left:95px; line-height: 10px;" class="data-text-color">'.($header["name"]??null).'</div>
		    <div style="margin-left:265px;" class="data-text-color">สังกัด :</div>
			<div style="margin-left:305px;" class="data-text-color">'.($header["membgroup_desc"]??null).'</div>
	  </div>
	  <div style="display:flex; height:30px; "class="text-color">
		<div style="margin-left:10px;">เลขที่สมาชิก:</div>
		<div style="margin-left:95px;" class="data-text-color">'.($header["member_no"]??null)." ".($header["wcmember_desc"]??null).'</div>
		  <div style="margin-left:265px;" class="data-text-color">ประเภทใบเสร็จ :</div>
		  <div style="margin-left:360px;" class="data-text-color">'.($header["cash_type"]??null).'</div>
	  </div>
	  <div>
	  <div style="position:absolute; ">
		<div style=" padding:5px;  height:20px; "></div>
		';
	foreach($dataReport AS $arrList){
	  $html .='   
	  <div style=" padding:5px; display:flex; height: 10px; ">
			 <div style="width:365px;" >'.($arrList["slip_desc"]??null).'</div>
			 <div style="width:120px; margin-left:365px; " class="text-right">'.($arrList["deptslip_amt"]??null).'</div>
	  </div>';
	  $sumBalance += ($arrList["amt"]??0);
	}
	$html.='
	  </div>
	  <div>
		<table style="width:100%;">
			<tr>
				<td>รายการ</td>
				<td style="width:25%">จำนวนเงิน</td>
			</tr>
			<tr>
			  <td style="height:180px;"></td>
			  <td></td>
			</tr>
			<tr>
				<td style="font-weight:bold;" class="data-text-color">'.$lib->baht_text($sumBalance).'</td>
				<td style="padding:5px; font-weight:bold;" class="text-right data-text-color">'.number_format($sumBalance,2).'</td>
			</tr>
			
		</table>
	  </div>
	  </div>
	  <div style="position:absolute; bottom:-30px;">
		<div style="position:absolute;"><img src="../../resource/utility_icon/signature/manager.png" "width="40" height="30" style="margin-top:-20px; margin-left:55px; "></div>
		<div style="display:flex">
		  <div style="margin-left:10px;" class="text-color">ลงชื่อ...........................................ผู้จัดการ</div>
		  <div style="margin-left:245px;" class="text-color">ลงชื่อ........................................เจ้าหน้าที่กองทุน</div>
		</div>
	  </div>
	';
	//ระยะขอบ
	//<div style="position:absolute;"><img src="../../resource/utility_icon/signature/welfare_staff.png" "width="90" height="40" style="margin-top:-28px; margin-left:300px;"></div>
	$html .= '
	</div>';

	$dompdf = new Dompdf([
		'fontDir' => realpath('../../resource/fonts'),
		'chroot' => realpath('/'),
		'isRemoteEnabled' => true
	]);

	$dompdf->set_paper('A4', 'landscape');
	$dompdf->load_html($html);
	$dompdf->render();
	$pathfile = __DIR__.'/../../resource/pdf/fund';
	if(!file_exists($pathfile)){
		mkdir($pathfile, 0777, true);
	}
	$pathfile = $pathfile.'/'.$header["member_no"].$header["receipt_no"].'.pdf';
	$pathfile_show = '/resource/pdf/fund/'.$header["member_no"].$header["receipt_no"].'.pdf?v='.time();
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