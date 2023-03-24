<?php
require_once('../autoload.php');
use Dompdf\Dompdf;

$dompdf = new DOMPDF();

if($lib->checkCompleteArgument(['menu_component','confirm_flag'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DocBalanceConfirm')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
	
		$getBalStatus = $conmysql->prepare("SELECT confirm_status FROM confirm_balance WHERE member_no = :member_no and balance_date = :balance_date");
		$getBalStatus->execute([
			':member_no' => $member_no,
			':balance_date' => date('Y-m-d',strtotime($dataComing["balance_date"]))
		]);
		$rowBalStatus = $getBalStatus->fetch(PDO::FETCH_ASSOC);
		if(isset($rowBalStatus["confirm_status"]) && $rowBalStatus["confirm_status"] != ""){
			$arrayResult['RESPONSE_CODE'] = "WS0097";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
		}
		
		$getBalanceDetail = $conmssql->prepare("SELECT BALANCE_AMT,BIZZTYPE_CODE,BIZZACCOUNT_NO,FROM_SYSTEM AS CONFIRMTYPE_CODE FROM yrconfirmstatement 
												WHERE member_no = :member_no and balance_date = :balance_date and FROM_SYSTEM NOT IN('GRT')
												ORDER BY SEQ_NO ASC");
		$getBalanceDetail->execute([
			':member_no' => $member_no,
			':balance_date' =>  date('m/d/y',strtotime($dataComing["balance_date"]))
		]);
		while($rowBalDetail = $getBalanceDetail->fetch(PDO::FETCH_ASSOC)){
			$arrBalDetail = array();
			$getFullname = $conmssql->prepare("SELECT mp.PRENAME_SHORT,mb.MEMB_NAME,mb.MEMB_SURNAME
													FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
													WHERE mb.member_no = :member_no ");
			$getFullname->execute([':member_no' => $member_no]);
			$rowName = $getFullname->fetch(PDO::FETCH_ASSOC);
			$arrBalDetail["FULL_NAME"] = $rowName["PRENAME_SHORT"].''.$rowName["MEMB_NAME"].'	'.$rowName["MEMB_SURNAME"];
			if($rowBalDetail["CONFIRMTYPE_CODE"] == "DEP"){
				$arrBalDetail["TYPE_DESC"] = 'เงินรับฝาก';
				$arrBalDetail["DEPTACCOUNT_NO"] = $rowBalDetail["BIZZACCOUNT_NO"];
				$arrBalDetail["BALANCE_AMT"] = $rowBalDetail["BALANCE_AMT"];
			}else if($rowBalDetail["CONFIRMTYPE_CODE"] == "LON"){
				$arrBalDetail["TYPE_DESC"] = 'เงินกู้';
				$arrBalDetail["LOANCONTRACT_NO"] = $rowBalDetail["BIZZACCOUNT_NO"];
				$arrBalDetail["BALANCE_AMT"] = $rowBalDetail["BALANCE_AMT"];
			}else if($rowBalDetail["CONFIRMTYPE_CODE"] == "SHR"){
				$arrBalDetail["TYPE_DESC"] = 'หุ้นปกติ';
				$arrBalDetail["BALANCE_AMT"] = $rowBalDetail["BALANCE_AMT"];
			}else{
				$arrBalDetail["BALANCE_AMT"] = $rowBalDetail["BALANCE_AMT"];
				$arrBalDetail["LIST_DESC"] = $rowBalDetail["BIZZACCOUNT_NO"];
			}
			$arrDetail[] = $arrBalDetail;
		}
		$conmysql->beginTransaction();
		foreach ($arrDetail as  $dataArr) {
			$FlagComfirm = $conmysql->prepare("INSERT INTO confirm_balance(member_no,full_name,type_desc,balance_amt ,deptaccount_no,	loancontract_no,confirm_remark, confirm_status ,balance_date, ip_address)
											VALUES(:member_no,:full_name,:type_desc,:balance_amt ,:deptaccount_no,:loancontract_no,:remark,:confirm_flag,:balance_date,:ip_address)");
			if($FlagComfirm->execute([
					':member_no' => $member_no,
					':full_name' => $dataArr["FULL_NAME"],
					':type_desc' => $dataArr["TYPE_DESC"],
					':balance_amt' => $dataArr["BALANCE_AMT"],
					':deptaccount_no' => $dataArr["DEPTACCOUNT_NO"],
					':loancontract_no' => $dataArr["LOANCONTRACT_NO"],
					':remark' => 'MobileApp / '.$dataComing["remark"],
					':confirm_flag' => $dataComing["confirm_flag"],
					':balance_date' => date('Y-m-d',strtotime($dataComing["balance_date"])),
					':ip_address' => $dataComing["ip_address"] == 'unknown' ? ($_SERVER['HTTP_X_REAL_IP'] ?? null) : ($dataComing["ip_address"] ?? null)
				])){
			}else{
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS1038",
					":error_desc" => "Update ลงตาราง  cmconfirmmaster ไม่ได้ "."\n".$FlagComfirm->queryString."\n"."data => ".json_encode([
						':member_no' => $member_no,
						':balance_share' => $dataArr["BALANCE_SHARE"],
						':deptaccount_no' => $dataArr["DEPTACCOUNT_NO"],
						':loancontract_no' => $dataArr["LOANCONTRACT_NO"],
						':remark' => 'MobileApp / '.$dataComing["remark"],
						':confirm_flag' => $dataComing["confirm_flag"],
						':balance_date' => date('Y-m-d',strtotime($dataComing["balance_date"])),					
					]),
					":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$message_error = "ไฟล์ ".$filename." Update ลงตาราง  cmconfirmmaster ไม่ได้"."\n".$FlagComfirm->queryString."\n"."data => ".json_encode([
					':member_no' => $member_no,
					':remark' => 'MobileApp / '.$dataComing["remark"],
					':confirm_flag' => $dataComing["confirm_flag"],
					':balance_date' => date('Y-m-d',strtotime($dataComing["balance_date"])),
				]);
				$lib->sendLineNotify($message_error);
				$arrayResult['RESPONSE_CODE'] = "WS1038";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		}
		//put file pdf
		$memberInfo = $conmssql->prepare("SELECT mp.PRENAME_SHORT as PRENAME_DESC,mb.MEMB_NAME,mb.MEMB_SURNAME,
													mg.MEMBGROUP_DESC
												FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
												LEFT JOIN MBUCFMEMBGROUP mg ON mb.MEMBGROUP_CODE = mg.MEMBGROUP_CODE
												WHERE mb.member_no = :member_no");
		$memberInfo->execute([':member_no' => $member_no]);
		$rowMember = $memberInfo->fetch(PDO::FETCH_ASSOC);
		$arrHeader = array();
		$arrDetail_update = array();
		$arrHeader["full_name"] = $rowMember["PRENAME_DESC"].$rowMember["MEMB_NAME"]." ".$rowMember["MEMB_SURNAME"];
		$arrHeader["member_group"] = $rowMember["MEMBGROUP_DESC"];
		$arrHeader["member_no"] = $member_no;
		$arrHeader["date_confirm"] = $lib->convertdate(date('Y-m-d',strtotime($dataComing["balance_date"])),'d M Y');
		$getBalanceDetail = $conmssql->prepare("SELECT BALANCE_AMT,BIZZTYPE_CODE,BIZZACCOUNT_NO,FROM_SYSTEM AS CONFIRMTYPE_CODE FROM yrconfirmstatement 
												WHERE member_no = :member_no and CONVERT(VARCHAR(10),balance_date,23) = :balance_date and FROM_SYSTEM NOT IN('GRT')
												ORDER BY SEQ_NO ASC");
		$getBalanceDetail->execute([
			':member_no' => $member_no,
			':balance_date' => $dataComing["balance_date"]
		]);
		while($rowBalDetail = $getBalanceDetail->fetch(PDO::FETCH_ASSOC)){
			$arrBalDetail = array();
			if($rowBalDetail["CONFIRMTYPE_CODE"] == "DEP"){
				$arrBalDetail["TYPE_DESC"] = 'เงินรับฝาก';
				$getTypeDeposit = $conmssql->prepare("SELECT DEPTTYPE_DESC FROM dpdepttype WHERE depttype_code = :depttype_code");
				$getTypeDeposit->execute([':depttype_code' => $rowBalDetail["BIZZTYPE_CODE"]]);
				$rowTypeDeposit = $getTypeDeposit->fetch(PDO::FETCH_ASSOC);
				$arrBalDetail["BALANCE_AMT"] = number_format($rowBalDetail["BALANCE_AMT"],2);
				$arrBalDetail["LIST_DESC"] = $rowTypeDeposit["DEPTTYPE_DESC"].' '.$rowBalDetail["BIZZACCOUNT_NO"];
			}else if($rowBalDetail["CONFIRMTYPE_CODE"] == "LON"){
				$arrBalDetail["TYPE_DESC"] = 'เงินกู้';
				$getTypeLoan = $conmssql->prepare("SELECT LOANTYPE_DESC FROM lnloantype WHERE loantype_code = :loantype_code");
				$getTypeLoan->execute([':loantype_code' => $rowBalDetail["BIZZTYPE_CODE"]]);
				$rowTypeLoan = $getTypeLoan->fetch(PDO::FETCH_ASSOC);
				$arrBalDetail["BALANCE_AMT"] = number_format($rowBalDetail["BALANCE_AMT"],2);
				$arrBalDetail["LIST_DESC"] = $rowTypeLoan["LOANTYPE_DESC"].' '.$rowBalDetail["BIZZACCOUNT_NO"];
			}else if($rowBalDetail["CONFIRMTYPE_CODE"] == "SHR"){
				$arrBalDetail["TYPE_DESC"] = 'หุ้นปกติ';
				$arrBalDetail["BALANCE_AMT"] = number_format($rowBalDetail["BALANCE_AMT"],2);
				$arrBalDetail["LIST_DESC"] = '';
			}else{
				$arrBalDetail["BALANCE_AMT"] = number_format($rowBalDetail["BALANCE_AMT"],2);
				$arrBalDetail["LIST_DESC"] = $rowBalDetail["BIZZACCOUNT_NO"];
			}
			$arrDetail_update[] = $arrBalDetail;
		}
		$arrayPDF = GeneratePdfDoc($arrHeader,$arrDetail_update,$dataComing["ip_address"]);
		if($arrayPDF){
			$conmysql->commit();
			$arrayResult['RESULT'] = TRUE;
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

function GeneratePdfDoc($arrHeader,$arrDetail,$ip_address) {
	$html = '<style>
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
		  font-family: TH Niramit AS
		}
		body {
		  padding: 0;
		  font-size:14pt;
		  font-weight;
		}
		div{
		  font-size:14pt;
		}
		.text-center{
		  text-align:center;
		}
		.text-right{
		  text-align:right;
		}
		.font-bold{
		  font-weight:bold
		}
		.nowrap{
		  white-space: nowrap;
		}
	</style>
	';



	$html .= '  <div style="margin-top:-20px; margin-left:-10px; margin-right:-10px;">';

	$html .= '
	  <div style="margin-bottom:20px;">
		<div class="text-center">
		  หนังสือยืนยันยอดลูกหนี้เจ้าหนี้ เงินรับฝากและทุนเรือนหุ้น
		<div style="position:absolute; left:560px;">เลขที่ พ.สหกรณ์/1</div>
		</div>
		<div style="margin-left:50% ">
		  วันที่ ..................................
		  <div style="position:fixed; top:4px; left:370px;  width:108px;" class="text-center" >'.$arrHeader["date_confirm"].'</div>
		</div>
		<div style="display:flex; height:30px;">
		  <div>เรียน </div>
		  <div style="margin-left:40px;">'.($arrHeader["full_name"]??null).'</div>
		  <div style="margin-left:230px;">เลขที่ทะเบียนที่ </div>
		  <div style="margin-left:320px;">'.($arrHeader["member_no"]??null).'</div>
		  <div style="margin-left:410px;">หน่วย </div>
		  <div style="margin-left:445px;">'.($arrHeader["member_group"]??null).'</div>
		</div>
		<div style="margin-left: 90px;">
			สหกรณ์ออมทรัพย์มหาวิทยาลัยแม่โจ้ จำกัด
		</div>
		<div style="margin-left:60px;">
			ขอเรียนว่าท่านได้ทำธุรกรรมกับสหกรณ์ และมียอดคงเหลือต่าง ๆ ณ วันที่ '.$arrHeader["date_confirm"].' ดังนี้
		</div>
	  </div>
	';


	foreach ($arrDetail as $key => $dataArr) {
	  $html.='
	  <div>
		 <div style="display:inline; margin-right:30px; margin-left:60px;">'.($dataArr["TYPE_DESC"]??null).'</div>
		 <div style="display:inline; width:200px;">'.($dataArr["LIST_DESC"]??null).'</div>
		 <div style="position:absolute; left:455px;">จำนวนเงิน</div>
		 <div style="position:absolute; right:50px;">'.($dataArr["BALANCE_AMT"]??null).'</div>
		 <div style="position:absolute; right:20px;">บาท</div>
	  </div>
	';
	}


	$html.='<div style="margin-top:30px; margin-left:450px;  margin-right:20px;" class="text-center">ขอแสดงความนับถือ</div>
	  <div>
		<div style="margin-top:30px; margin-left:450px;  margin-right:20px;" class="text-center">
		<div style="positon:absolute;   width:100%;  " class="text-center">นายณัฎฐ์นันธ์  นิกรพันธุ์</div>
		  <div style="margin-top:-24px;"> (..............................................................)</div>
		</div>
	  </div>
	  <div style="border-top:1.5px dotted; margin-top:10px; height:10px;"></div>
	  <div class="text-center" style="padding-top;20px;">
		หนังสือยืนยันยอดลูกหนี้เจ้าหนี้ เงินรับฝากและทุนเรือนหุ้น
		<div style="position:absolute; left:560px;">เลขที่ พ.สหกรณ์/1</div>
	  </div>
	  <div style="width:50%;">
		  <div class="nowrap">
			  <div style="position:absolute width:200px;" class="text-center">ผู้ช่วยศาสตราจารย์ ดร.อรรถพงศ์  พีระเชื้อ</div>
			  <div style="margin-top:-25px;"> เรียน............................................................................................ผู้ตรวจบัญชี</div>
		  </div>
		  <div class="nowrap">
			  .......................................................................................................................
		  </div>
		  <div class="nowrap">
		  .......................................................................................................................
		  </div>
		  <div class="nowrap">
		  .......................................................................................................................
		  </div>
	  </div>
	  <div style="margin-left:60px;">
		  ข้าพเจ้าขอยืนยันจำนวนเงินที่เป็นหนี้ เงินฝากและทุนเรือนหุ้น ระหว่างข้าพเจ้ากับ
	  </div>
	  <div>
	  สหกรณ์ออมทรัพย์มหาวิทยาลัยแม่โจ้ จำกัด ณ วันที่ '.($arrHeader["date_confirm"]??null).' ดังนี้
	  </div>
	  <div style="width:50%; margin-bottom:10px;">
		<div style="margin-left:90px; display:inline;">
			( ) ถูกต้อง
		</div>
		<div style="margin-left:70px; display:inline;">
			( ) ไม่ถูกต้อง ดังนี้
		</div>
		
	  </div>
	';

	foreach ($arrDetail as $key => $dataArr) {
	  $html.='
		  <div>
			 <div style="display:inline; margin-right:30px; margin-left:60px;">'.($dataArr["TYPE_DESC"]??null).'</div>
			 <div style="display:inline; width:200px;">'.($dataArr["LIST_DESC"]??null).'</div>
			 <div style="position:absolute; left:455px;">จำนวนเงิน</div>
			 <div style="position:absolute; right:50px;">'.($dataArr["BALANCE_AMT"]??null).'</div>
			 <div style="position:absolute; right:20px;">บาท</div>
		  </div>
		';
	}

	$html.='<div style="margin-top:30px; margin-left:450px; margin-right:20px;  " class="text-center">('.($arrHeader["full_name"]??null).')</div>
	<div style="margin-left:450px; margin-right:20px;  " class="text-center">เลขที่ทะเบียนที่ '.($arrHeader["member_no"]??null).'</div>';
	$html .= '
		</div>
	';
	
	$html.='<div style="position:absolute ;bottom:0px" class="text-left">'.$ip_address.' '.date("H:i:s").'</div>';
	$html .= '
		</div>
	';
	
	$dompdf = new Dompdf([
		'fontDir' => realpath('../../resource/fonts'),
		'chroot' => realpath('/'),
		'isRemoteEnabled' => true
	]);
	$dompdf->set_paper('A4');
	$dompdf->load_html($html);
	$dompdf->render();
	$pathfile = __DIR__.'/../../resource/pdf/docbalconfirm_update';
	if(!file_exists($pathfile)){
		mkdir($pathfile, 0777, true);
	}
	$pathfile = $pathfile.'/'.$arrHeader["member_no"].'.pdf';
	$pathfile_show = '/resource/pdf/docbalconfirm_update/'.$arrHeader["member_no"].'.pdf?v='.time();
	$arrayPDF = array();
	$output = $dompdf->output();
	if(file_put_contents($pathfile, $output)){
		$arrayPDF["RESULT"] = TRUE;
	}else{
		$arrayPDF["RESULT"] = FALSE;
	}
	return $arrayPDF; 
}
?>