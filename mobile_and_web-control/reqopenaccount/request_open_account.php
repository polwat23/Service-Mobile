<?php
require_once('../autoload.php');

use Dompdf\Dompdf;

if($lib->checkCompleteArgument(['menu_component','amount','slip'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'OpenAccountRequest')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$fullPathSlip = null;
		$conmysql->beginTransaction();
		$reqopendoc_no = "RO".date('YmdHis').$member_no;
		if(isset($dataComing["slip"]) && $dataComing["slip"] != ""){
			$subpath = 'slip';
			$destination = __DIR__.'/../../resource/openaccount/'.$reqopendoc_no;
			$data_Img = explode(',',$dataComing["slip"]);
			$info_img = explode('/',$data_Img[0]);
			$ext_img = str_replace('base64','',$info_img[1]);
			if(!file_exists($destination)){
				mkdir($destination, 0777, true);
			}
			if($ext_img == 'png' || $ext_img == 'jpg' || $ext_img == 'jpeg'){
				$createImage = $lib->base64_to_img($dataComing["slip"],$subpath,$destination,null);
			}else if($ext_img == 'pdf'){
				$createImage = $lib->base64_to_pdf($dataComing["slip"],$subpath,$destination);
			}
			if($createImage == 'oversize'){
				$conmysql->rollback();
				$arrayResult['RESPONSE_CODE'] = "WS0008";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}else{
				if($createImage){
					$directory = __DIR__.'/../../resource/openaccount/'.$reqopendoc_no;
					$fullPathSlip = __DIR__.'/../../resource/openaccount/'.$reqopendoc_no.'/'.$createImage["normal_path"];
					$fullPathSlip = $config["URL_SERVICE"]."resource/openaccount/".$reqopendoc_no."/".$createImage["normal_path"];
				}
			}
		}
		$docUrl = $config["URL_SERVICE"].'/resource/openaccount/'.$reqopendoc_no.'/reqdoc.pdf';
		$insertReq = $conmysql->prepare("INSERT INTO gcreqopenaccount(reqopendoc_no,member_no,depttype_code,amount_open,slip_url,doc_url,id_userlogin)
										VALUES(:reqdoc_no,:member_no,'10',:amount_open,:slip_url,:doc_url,:id_userlogin)");
		if($insertReq->execute([
			':reqdoc_no' => $reqopendoc_no,
			':member_no' => $member_no,
			':amount_open' => $dataComing["amount"],
			':slip_url' => $fullPathSlip,
			':doc_url' => $docUrl,
			':id_userlogin' => $payload["id_userlogin"]
		])){
			$memberInfoMobile = $conmysql->prepare("SELECT phone_number,email,path_avatar,member_no FROM gcmemberaccount WHERE member_no = :member_no");
			$memberInfoMobile->execute([':member_no' => $payload["member_no"]]);
			if($memberInfoMobile->rowCount() > 0){
				$rowInfoMobile = $memberInfoMobile->fetch(PDO::FETCH_ASSOC);
				$dataReq["phone"] = $lib->formatphone($rowInfoMobile["phone_number"]);
			}
		
		
			$memberInfo = $conmssql->prepare("SELECT mp.PRENAME_SHORT,mb.MEMB_NAME,mb.MEMB_SURNAME,mb.BIRTH_DATE,mb.CARD_PERSON,
													mb.MEMBER_DATE,mb.POSITION_DESC,mg.MEMBGROUP_DESC,mt.MEMBTYPE_DESC,
													mb.CURRADDR_NO as ADDR_NO,
													mb.CURRADDR_MOO as ADDR_MOO,
													mb.CURRADDR_SOI as ADDR_SOI,
													mb.CURRADDR_VILLAGE as ADDR_VILLAGE,
													mb.CURRADDR_ROAD as ADDR_ROAD,
													MBT.TAMBOL_DESC AS TAMBOL_DESC,
													MBD.DISTRICT_DESC AS DISTRICT_DESC,
													MB.CURRPROVINCE_CODE AS PROVINCE_CODE,
													MBP.PROVINCE_DESC AS PROVINCE_DESC,
													MB.CURRADDR_POSTCODE AS ADDR_POSTCODE
													FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
													LEFT JOIN MBUCFMEMBGROUP mg ON mb.MEMBGROUP_CODE = mg.MEMBGROUP_CODE
													LEFT JOIN MBUCFMEMBTYPE mt ON mb.MEMBTYPE_CODE = mt.MEMBTYPE_CODE
													LEFT JOIN MBUCFTAMBOL MBT ON mb.tambol_code = MBT.TAMBOL_CODE
													LEFT JOIN MBUCFDISTRICT MBD ON mb.amphur_code = MBD.DISTRICT_CODE
													LEFT JOIN MBUCFPROVINCE MBP ON mb.province_code = MBP.PROVINCE_CODE
													WHERE mb.member_no = :member_no");
			$memberInfo->execute([':member_no' => $member_no]);
			$rowMember = $memberInfo->fetch(PDO::FETCH_ASSOC);
			$address = (isset($rowMember["ADDR_NO"]) ? $rowMember["ADDR_NO"] : null);
			if(isset($rowMember["PROVINCE_CODE"]) && $rowMember["PROVINCE_CODE"] == '10'){
				$address .= (isset($rowMember["ADDR_MOO"]) ? ' ม.'.$rowMember["ADDR_MOO"] : null);
				$address .= (isset($rowMember["ADDR_SOI"]) ? ' ซอย'.$rowMember["ADDR_SOI"] : null);
				$address .= (isset($rowMember["ADDR_VILLAGE"]) ? ' หมู่บ้าน'.$rowMember["ADDR_VILLAGE"] : null);
				$address .= (isset($rowMember["ADDR_ROAD"]) ? ' ถนน'.$rowMember["ADDR_ROAD"] : null);
				$address .= (isset($rowMember["TAMBOL_DESC"]) ? ' แขวง'.$rowMember["TAMBOL_DESC"] : null);
				$address .= (isset($rowMember["DISTRICT_DESC"]) ? ' เขต'.$rowMember["DISTRICT_DESC"] : null);
				$address .= (isset($rowMember["PROVINCE_DESC"]) ? ' '.$rowMember["PROVINCE_DESC"] : null);
				$address .= (isset($rowMember["ADDR_POSTCODE"]) ? ' '.$rowMember["ADDR_POSTCODE"] : null);
			}else{
				$address .= (isset($rowMember["ADDR_MOO"]) ? ''.$rowMember["ADDR_MOO"] : null);
				$address .= (isset($rowMember["ADDR_SOI"]) ? ' ซอย'.$rowMember["ADDR_SOI"] : null);
				$address .= (isset($rowMember["ADDR_VILLAGE"]) ? ' หมู่บ้าน'.$rowMember["ADDR_VILLAGE"] : null);
				$address .= (isset($rowMember["ADDR_ROAD"]) ? ' ถนน'.$rowMember["ADDR_ROAD"] : null);
				$address .= (isset($rowMember["TAMBOL_DESC"]) ? ' ต.'.$rowMember["TAMBOL_DESC"] : null);
				$address .= (isset($rowMember["DISTRICT_DESC"]) ? ' อ.'.$rowMember["DISTRICT_DESC"] : null);
				$address .= (isset($rowMember["PROVINCE_DESC"]) ? ' จ.'.$rowMember["PROVINCE_DESC"] : null);
				$address .= (isset($rowMember["ADDR_POSTCODE"]) ? ' '.$rowMember["ADDR_POSTCODE"] : null);
			}
			$dataReq["fname"] = $rowMember["PRENAME_SHORT"].$rowMember["MEMB_NAME"];
			$dataReq["lname"] = $rowMember["MEMB_SURNAME"];
			$dataReq["birth_date"] = $lib->convertdate($rowMember["BIRTH_DATE"],"D m Y");
			$dataReq["birth_date_count"] =  $lib->count_duration($rowMember["BIRTH_DATE"],"ym");
			$dataReq["card_person"] = $lib->formatcitizen($rowMember["CARD_PERSON"]);
			$dataReq["member_date"] = $lib->convertdate($rowMember["MEMBER_DATE"],"D m Y");
			$dataReq["member_date_count"] = $lib->count_duration($rowMember["MEMBER_DATE"],"ym");
			$dataReq["position_desc"] = $rowMember["POSITION_DESC"];
			$dataReq["membergroup_desc"] = $rowMember["MEMBGROUP_DESC"];
			$dataReq["full_address_curr"] = $address;
			$dataReq["amount_open"] = $dataComing["amount"];
			$dataReq["member_no"] = $member_no;

			$docReq = GenerateReqDoc($dataReq,$lib,'reqdoc',$reqopendoc_no);
			if($docReq["RESULT"]){
				$conmysql->commit();
				$arrayResult['DOC_URL'] = $config["URL_SERVICE"].$docReq["PATH"];
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE_CODE'] = "WS9999";
				$arrayResult['RESPONSE_MESSAGE'] = $configError["OPEN_ACCOUNT"][0]["CANNOT_CREATEDOC"][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		}else{
			$conmysql->rollback();
			$arrayResult['RESPONSE_CODE'] = "WS9999";
			$arrayResult['RESPONSE_MESSAGE'] = $configError["OPEN_ACCOUNT"][0]["CANNOT_INSERT"][0][$lang_locale];
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
function GenerateReqDoc($data,$lib,$filename,$subpath){
	
	$dompdf = new DOMPDF();

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
	  font-size:16pt;
	  line-height: 25px;

	}
	.head{
		font-size:16pt;
		font-weight:bold;
	}
	.center{
	  text-align:center
	}
	.right{
	  text-align:right
	}
	.nowrap{
	  white-space: nowrap;
	}
	.wrapper-page {
	  page-break-after: always;
	}
	.flex{
		display:flex;
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
	.data{
		font-size:14pt;
		margin-top:-3px;
	 
	}
	.absolute{
	  position:absolute;
	}
	.font-bold{
		font-weight:bold;
	}
	</style>
	';

	//ระยะขอบ
	$html .= '<div style="margin: -10px 40px -10px 40px; " >';
	$html .= '
	<div style=" text-align: center; margin-top:5px;"><img src="../../resource/logo/logo.jpg" alt="" width="60" height="0"></div>
	<div class="center head" style="margin-top:10px;">คำขอเปิดบัญชีเงินฝากออมทรัพย์</div>
	<div class="center head">สหกรณ์ออมทรัพย์มหาวิทยาลัยแม่โจ้ จำกัด</div>
	<div class="flex" style="height:120px;">
		<div>
			<div>สําหรับเจ้าหน้าที่สหกรณ์</div>
			<div>
				<div class="absolute" style="margin-left:70px"><div class="data"></div></div>
				บัญชีเลขที่................................
			</div>
			<div>
			   
				ลงนาม................................................
			</div>
			<div>
				<div class="absolute" style="margin-left:40px"><div class="data">'.($lib->convertdate(date("Y-m-d"),"d m Y") ?? null).'</div></div>
				วันที่................................................
			</div>

		</div>
		<div class="" style="margin-left:47%;">
			<div>
				<div class="absolute" style="margin-left:130px"><div class="data">'.($data["member_no"]??null).'</div></div>
				สมาชิกเลขทะเบียนที่...................
			</div>
			<div>
				<div class="absolute" style="text-indent:100px; margin-top:-4px">'.($data["full_address_curr"]??null).'</div>
				ที่อยู่ของผู้ฝาก............................................................
			</div>
			<div>..................................................................................</div>
		</div>
	</div>
	<div>เรียน &nbsp;ประธานกรรมการสหกรณ์ออมทรัพย์มหาวิทยาลัยแม่โจ้ จํากัด </div>
	<div style="margin-left:80px;">
		<div class="absolute" style="margin-left:130px; width:180px; "><div class="data nowrap center">'.($data["fname"]??null).'</div></div>
		<div class="absolute" style="margin-left:365px; width:175px;"><div class="data nowrap center">'.($data["lname"]??null).'</div></div>
		ข้าพเจ้านาย/นาง/น.ส................................................นามสกุล..............................................
	</div>
	<div>
		<div class="absolute" style="margin-left:420px; "><div class="data">'.($data["member_no"]??null).'</div></div>
		สมาชิกแห่งสหกรณ์ออมทรัพย์มหาวิทยาลัยแม่โจ้ จํากัด เลขทะเบียนที่..........................
	</div>
	<div class="nowrap"> 
		<div class="absolute" style="margin-left:140px;  width:240px;"><div class="data nowrap center">'.($data["membergroup_desc"]??null).'</div></div>
		<div class="absolute" style="margin-left:410px; margin-top:-3px"><div class="data"></div>'.($data["phone"]??null).'</div>
		สังกัด กอง,คณะหรือสํานัก.........................................................โทร.............................มหาวิทยาลัยแม่โจ้
	</div>
	<div class="nowrap">
		<div class="absolute" style="margin-left:230px;  width:305px;"><div class="data nowrap center">'.($data["fname"].' '.$data["lname"] ?? null).'</div></div>
		ขอเปิดบัญชีเงินฝากออมทรัพย์ ในชื่อ....................................................................................ไว้กับสหกรณ์
	</div>
	<div class="nowrap">
		<div class="absolute" style="margin-left:280px;  width:100px;"><div class="data nowrap center">'.(number_format($data["amount_open"],2)?? null).'</div></div>
		<div class="absolute" style="margin-left:410px;  width:200px; "><div class="data nowrap center">'.($lib->baht_text($data["amount_open"]) ?? null).'</div></div>
		ออมทรัพย์มหาวิทยาลัยแม่โจ้ จํากัด จํานวนเงิน.........................บาท(.......................................................)
	</div>
	<div style="margin-left:40px;">ผู้มีอํานาจถอนเงิน ตลอดจนให้คําสั่งเกี่ยวกับเงินฝากออมทรัพย์ ที่เปิดขึ้นนี้คือ</div>
	<div style="margin-left:40px;" class="nowrap">
		<div class="absolute" style="margin-left:15px;"><div class="data nowrap">'.($data["fname"].' '.$data["lname"] ?? null).'</div></div>
		<div class="absolute" style="margin-left:310px;"><div class="data nowrap"></div></div>
		1. .......................................................................  2. .......................................................................
	</div>
	<div style="margin-left:40px;" class="nowrap">
		<div class="absolute" style="margin-left:15px;"><div class="data nowrap"></div></div>
		<div class="absolute" style="margin-left:310px;"><div class="data nowrap"></div></div>
		3. ......................................................................  4. .......................................................................
	</div>
	<div style="margin-left:40px;" class="nowrap">
		<div class="absolute" style="margin-left:15px;"><div class="data nowrap"></div></div>
		<div class="absolute" style="margin-left:310px;"><div class="data nowrap"></div></div>
		5. ......................................................................  6. .......................................................................
	</div>
	<div style="margin-left:40px;">
		<div class="absolute" style="margin-left:115px;"><div class="data nowrap">ได้คนเดียว</div></div>
		เงื่อนไขในการถอน.......................................................................
	</div>
	<div style="padding-left:80px;">ทั้งนี้ ข้าพเจ้าได้ส่งตัวอย่างลายมือชื่อของผู้มีอํานาจถอนเงินในบัตร มาพร้อมกับคําขอเปิด</div>
	<div>บัญชีเงินฝากออมทรัพย์ฉบับนี้</div>
	<div style="padding-left:80px;">ข้าพเจ้ายินยอมผูกพันและปฏิบัติตามระเบียบของสหกรณ์ฯ นี้ในส่วนที่ว่าด้วยเงินฝาก</div>
	<div>ออมทรัพย์ ซึ่งใช้อยู่ในเวลานั้น ๆ ทุกประการ</div>
	<div class="center " style="margint-top:5px;">ขอแสดงความนับถือ</div>
	<div class="center" style="margin-top:50px;">
		<div class="absolute center"><div class="data nowrap">'.($data["fname"].' '.$data["lname"] ?? null).'</div></div>
		(........................................................)
	</div>
	<div class="nowrap">.................................................................................................................................................................</div>
	<div class="center">สำหรับเจ้าหน้าที่</div>
	<div class="nowrap">
		<div class="absolute" style="margin-left:270px;"><div class="data nowrap"></div></div>
		<div class="absolute" style="margin-left:400px;"><div class="data nowrap center">'.(number_format($data["amount_open"],2)?? null).'</div></div>

		ได้รับเงินตามใบฝากเงินออมทรัพย์ บัญชีเลขที่...........................จำนวนเงิน.........................................บาท
	</div>
	<div>
		<div class="absolute" style="margin-left:5px;  width:210px;"><div class="data nowrap center">'.($lib->baht_text($data["amount_open"]) ?? null).'</div></div>
		<div class="absolute" style="margin-left:260px;  width:310px; "><div class="data nowrap"></div></div>
		(.......................................................) วันที่...............................
	</div>
	<div>
		ลายมือชื่อผู้รับเงินฝาก.............................................
	</div>
	<div>
		<div class="absolute" style="margin-left:250px;"><div class="data nowrap"></div></div>
		<div class="absolute" style="margin-left:400px;"><div class="data nowrap"></div></div>
		เข้าทะเบียนเงินฝากออมทรัพย์ บัญชีเลขที่...............................วันที่.......................
	</div>
	<div>พนักงานบัญชี........................................................</div>

	';

	//ระยะขอบ
	$html .= '
	</div>';
	
	$dompdf = new Dompdf([
		'fontDir' => realpath('../../resource/fonts'),
		'chroot' => realpath('/'),
		'isRemoteEnabled' => true
	]);

	$dompdf->set_paper('A4');
	$dompdf->load_html($html);
	$dompdf->render();
	$pathfile = __DIR__.'/../../resource/openaccount/'.$subpath;
	if(!file_exists($pathfile)){
		mkdir($pathfile, 0777, true);
	}
	$pathfile = $pathfile.'/'.$filename.'.pdf';
	$pathfile_show = '/resource/openaccount/'.$subpath.'/'.$filename.'.pdf?v='.time();
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