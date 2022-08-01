<?php
require_once('../autoload.php');

use Dompdf\Dompdf;

$dompdf = new DOMPDF();
if($lib->checkCompleteArgument(['menu_component','confirm_flag'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DocBalanceConfirm')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$formatDept = $func->getConstant('dep_format');
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
		
		if(isset($dataComing["balance_date"]) && isset($dataComing["balance_date"]) != ""){
			$memberInfo = $conoracle->prepare("SELECT mp.PRENAME_DESC as PRENAME_DESC,mb.MEMB_NAME,mb.MEMB_SURNAME,mg.MEMBGROUP_CODE,cm.COOP_NAME,
													mg.MEMBGROUP_DESC
													FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
													LEFT JOIN MBUCFMEMBGROUP mg ON mb.MEMBGROUP_CODE = mg.MEMBGROUP_CODE
													LEFT JOIN cmcoopmaster cm ON mb.current_coopid = cm.coop_id
													WHERE mb.member_no = :member_no");
			$memberInfo->execute([':member_no' => $member_no]);
			$rowMember = $memberInfo->fetch(PDO::FETCH_ASSOC);
			$arrHeader = array();
			$arrDetail = array();
			$arrHeader["remark"] = $dataComing["remark"];
			$arrHeader["confirm_flag"] = $dataComing["confirm_flag"];
			$arrHeader["full_name"] = $rowMember["PRENAME_DESC"].$rowMember["MEMB_NAME"]." ".$rowMember["MEMB_SURNAME"];
			$arrHeader["member_group"] = $rowMember["MEMBGROUP_CODE"].' '.$rowMember["MEMBGROUP_DESC"];
			$arrHeader["coop_name"] = $rowMember["COOP_NAME"];
			$arrHeader["member_no"] = $member_no;
			$arrHeader["date_confirm"] = $lib->convertdate(date('Y-m-d',strtotime($dataComing["balance_date"])),'d M Y');
			$getBalanceDetail = $conoracle->prepare("SELECT BALANCE_AMT,BIZZTYPE_CODE,BIZZACCOUNT_NO,FROM_SYSTEM AS CONFIRMTYPE_CODE FROM yrconfirmstatement 
													WHERE member_no = :member_no and TO_CHAR(balance_date, 'YYYY-MM-DD') = :balance_date and FROM_SYSTEM NOT IN('GRT')
													ORDER BY BIZZACCOUNT_NO DESC");
			$getBalanceDetail->execute([
				':member_no' => $member_no,
				':balance_date' => $dataComing["balance_date"]
			]);
			while($rowBalDetail = $getBalanceDetail->fetch(PDO::FETCH_ASSOC)){
				$arrBalDetail = array();
				if($rowBalDetail["CONFIRMTYPE_CODE"] == "DEP"){
					$arrBalDetail["TYPE_DESC"] = 'เงินรับฝาก';
					$getTypeDeposit = $conoracle->prepare("SELECT DEPTTYPE_DESC FROM dpdepttype WHERE depttype_code = :depttype_code");
					$getTypeDeposit->execute([':depttype_code' => $rowBalDetail["BIZZTYPE_CODE"]]);
					$rowTypeDeposit = $getTypeDeposit->fetch(PDO::FETCH_ASSOC);
					$arrBalDetail["BALANCE_AMT"] = number_format($rowBalDetail["BALANCE_AMT"],2);
					$arrBalDetail["LIST_DESC"] = $lib->formataccount($rowBalDetail["BIZZACCOUNT_NO"],$formatDept); 
					$arrDetail["DEP"][] = $arrBalDetail;
				}else if($rowBalDetail["CONFIRMTYPE_CODE"] == "LON"){
					$arrBalDetail["TYPE_DESC"] = 'เงินกู้';
					$getTypeLoan = $conoracle->prepare("SELECT LOANTYPE_DESC FROM lnloantype WHERE loantype_code = :loantype_code");
					$getTypeLoan->execute([':loantype_code' => $rowBalDetail["BIZZTYPE_CODE"]]);
					$rowTypeLoan = $getTypeLoan->fetch(PDO::FETCH_ASSOC);
					$arrBalDetail["BALANCE_AMT"] = number_format($rowBalDetail["BALANCE_AMT"],2);
					$arrBalDetail["LIST_DESC"] = $rowBalDetail["BIZZACCOUNT_NO"];
					$arrDetail["LON"][] = $arrBalDetail;
				}	
				else if($rowBalDetail["CONFIRMTYPE_CODE"] == "SHR"){
					$arrBalDetail["TYPE_DESC"] = 'หุ้นปกติ';
					$arrBalDetail["BALANCE_AMT"] = number_format($rowBalDetail["BALANCE_AMT"],2);
					$arrBalDetail["LIST_DESC"] = '';
					$arrDetail["SHR"] = $arrBalDetail;
				}else{
					$arrBalDetail["BALANCE_AMT"] = number_format($rowBalDetail["BALANCE_AMT"],2);
					$arrBalDetail["LIST_DESC"] = $rowBalDetail["BIZZACCOUNT_NO"];
					$arrDetail["ETC"][] = $arrBalDetail;
				}
			}
			
			$arrayPDF = GeneratePdfDoc($arrHeader,$arrDetail);
			if($arrayPDF["RESULT"]){
				$FlagComfirm = $conmysql->prepare("INSERT INTO confirm_balance(member_no, confirm_remark, confirm_status ,balance_date ,url_path)
											VALUES(:member_no,:remark,:confirm_flag,:balance_date,:url_path)");
				if($FlagComfirm->execute([
					':member_no' => $member_no,
					':remark' => 'MobileApp / '.$dataComing["remark"],
					':confirm_flag' => $dataComing["confirm_flag"],
					':balance_date' => date('Y-m-d',strtotime($dataComing["balance_date"])),
					':url_path' => $config["URL_SERVICE"].$arrayPDF["PATH"]
				])){
					$arrayResult['RESULT'] = TRUE;
					require_once('../../include/exit_footer.php');
				}else{
					$filename = basename(__FILE__, '.php');
					$logStruc = [
						":error_menu" => $filename,
						":error_code" => "WS1038",
						":error_desc" => "Update ลงตาราง  cmconfirmmaster ไม่ได้ "."\n".$FlagComfirm->queryString."\n"."data => ".json_encode([
							':member_no' => $member_no,
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
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS1038";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');	
			}
		}else{
			$arrayResult['RESULT'] = FALSE;
			http_response_code(204);
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

function GeneratePdfDoc($arrHeader,$arrDetail) {
	if($arrHeader["confirm_flag"]==1){
		$accept="checked";
	}else if($arrHeader["confirm_flag"]== 0){ 		
		$notAccept="checked";
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
	  font-size:15pt;
	  line-height: 25px;

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
	  line-height: 20px
	}
	th,td{
	  border:0.5px solid;
	  text-align:center;
	}
	th{
	  font-size:16px;
	}
	td{
	 
	  padding:2px;
	  height:19px;
	}
	p{
	  margin:0px;
	}

	</style>
	';

//
$html .= '<div style=" margin:  0 0 -40px 0; ">';
$html .='
	<div style="font-size:16pt; font-weight:bold" class="text-center">'.($arrHeader["coop_name"]??null).'</div>
	<div style="font-size:16pt; font-weight:bold" class="text-center">หนังสือยืนยันยอดทุนเรือนหุ้น เงินกู้ และเงินรับฝาก</div>
	<div class="text-center">'.($arrHeader["date_confirm"]??null).'</div>
	<div>
		<div style="display:inline; padding-right:20px;">เรียน</div>
		<div style="display:inline">'.($arrHeader["full_name"]??null).'</div>
		<div style="display:inline">ทะเบียน : </div>
		<div style="display:inline">'.($arrHeader["member_no"]??null).'</div>
		<div style="display:inline">หน่วยงาน : </div>
		<div style="display:inline">'.($arrHeader["member_group"]??null).'</div>
	</div>
	<div>
		<div style="display:inline; padding-left:70px;">สหกรณ์ออมทรัพย์ ไทยน้ำทิพย์ จำกัด ขอเรียนว่า ณ วันที่ </div>
		<div style="display:inline; padding-left:10px; padding-right:10px; font-weight:bold; ">'.($arrHeader["date_confirm"]??null).'</div>
		<div style="display:inline; ">ท่านมีทุนเรือนหุ้น </div>
	</div>
	<div>เงินกู้ค้างชำระและเงินรับฝาก ต่อสหกรณ์ตามรายการต่างๆ ดังนี้</div>
	<div style="">
		<div style="display:flex; height:23px ">
			<div style="margin-left:55px; font-weight:bold;">ทุนเรือนหุ้นทั้งหมด</div>
			<div style="margin-left:55px; text-align:right; margin-right:30px;">จำหนวนเงิน(บาท)</div>
		</div>
		<div style="display:flex; height:23px ">
			<div style="margin-left:70px;">ทุนเรือนหุ้น</div>
			<div style="margin-left:70px; text-align:right; margin-right:30px;">'.($arrDetail["SHR"]["BALANCE_AMT"]??null).'</div>
		</div>
		<div style="margin-left:55px; font-weight:bold;">เงินกู้ต่อสหกรณ์ ตามรายการดังนี้</div>

';

foreach($arrDetail["LON"] AS $arrLoan){
	$html.='
	<div style="display:flex; height:23px ">
	<div style="margin-left:70px;"> เลขที่สัญญา '.($arrLoan["LIST_DESC"]??null).'</div>
	<div style="margin-left:70px; text-align:right; margin-right:30px;">'.($arrLoan["BALANCE_AMT"]??null).'</div>
	</div>
	';
}

$html.='
	<div style="margin-left:55px; font-weight:bold;">เงินฝากไว้กับสหกรณ์ ตามรายการดังนี้</div>
';

foreach($arrDetail["DEP"] AS $arrDept){
	$html.='
	<div style="display:flex; height:23px ">
	<div style="margin-left:70px;">เลขที่บัญชี '.($arrDept["LIST_DESC"]??null).'</div>
	<div style="margin-left:70px; text-align:right; margin-right:30px;">'.($arrDept["BALANCE_AMT"]??null).'</div>
	</div>
	';
	
}

$html.='
	</div>
	<div style="margin-left:70px; margin-top:50px; letter-spacing:-0.15px" class="nowrap">เมื่อท่านได้รับหนังสือฉบับนี้ ขอได้โปรดยืนยันยอดว่าถูกต้องหรือไม่ถูกต้อง พร้อมลงลายมือชื่อ และส่งกลับคืนทั้งฉบับ </div>
	<div class="nowrap">ไปยังผู้สอบบัญชี ภายในกำหนด 7 วัน นับตั้งแต่วันที่ได้รับหนังสือนี้ และขอขอบคุณที่ให้ความร่วมมือในโอกาสนี้ และเรียนท่านว่า</div>
	<div class="nowrap">หนังสือนี้มิใช่ใบทวงหนี้ หากแต่ใช้ประโยชน์ในการตรวจสอบบัญชี สำหรับปีสิ้นสุดวันที่ 30 กันยายน '.(date('Y')+543).'</div>
	<div style="margin-left:465px;">ขอแสดงความนับถือ</div>
	<div style="width:50%;  margin-left:350px;">
		<div class="text-center"><img src="../../resource/utility_icon/signature/manager.png" width="100" height="50" style="margin-top:10px; "/></div>
		<div class="text-center">นางดวงพร  นุชศิริ</div>
		<div class="text-center">ผู้จัดการ</div>
	</div>
	<div style="border-bottom:1px solid dashed; font-weight; margin-top:20px;"></div>
	<div style="font-weight:bold;" class="text-center">'.($arrHeader["coop_name"]??null).'</div>
	<div style="font-weight:bold;" class="text-center">หนังสือตอบตอบยืนยันยอด</div>
	<div>
		<div style="margin-left:30px; display:inline;">เรียน</div>
		<div style="margin-left:30px; display:inline;">นางสาวสุภางค์   เวสารัชอารีย์กุล</div>
		<div style="margin-left:30px; display:inline;">ผู้ตรวจสอบบัญชี สหกรณ์ออมทรัพย์ไทยน้ำทิพย์ จำกัด</div>
	</div>
	<div>ข้าพเจ้าขอยืนยันยอดทุนเรือนหุ้น เงินกู้ค้างชำระ และเงินฝาก ตามรายการที่ทางสหกรณ์ออมทรัพย์ แจ้งให้ข้าพเจ้าทราบนั้น</div>
	<div style="height:25px;">
		<div style="position:absolute;" class="nowrap">
			<input type="checkbox" style="margin-top:7px;  margin-right:8px;"'.($accept??null).'>ถูกต้อง
		</div> 
	</div>
	<div style="height:25px;">
		<div style="position:absolute;  text-indent:130px; line-height:21px; ">
			<div style="margin-top:2px;">'.($arrHeader["confirm_flag"]== 0 ? $arrHeader["remark"] ?? null : null).' </div>
		</div>
		<div style="position:absolute;" >
			<input type="checkbox" style="margin-top:7px;  margin-right:8px;"'.($notAccept??null).' >
			<div style="position:absolute;" class="nowrap">ไม่ถูกต้อง เพราะ................................................................................................................................................................</div>
 		</div> 
	</div>
	<div class="nowrap">..................................................................................................................................................................................................</div>
	<div class="text-right" style="margin-top:30px;"></div>
	<div class="text-center" style=" width:240px; margin-left:460px;">'.($arrHeader["full_name"]??null).'</div>
	<div class="text-center" style=" width:240px; margin-left:460px;"> เลขทะเบียน : '.($arrHeader["member_no"]??null).'</div>
	<div style="font-weight:bold">หมายเหตุ : ส่งคืนทั้งฉบับ</div>
	';

//
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
	$pathfile = __DIR__.'/../../resource/pdf/membalconfirm';
	if(!file_exists($pathfile)){
		mkdir($pathfile, 0777, true);
	}
	$pathfile = $pathfile.'/'.$arrHeader["member_no"].'.pdf';
	$pathfile_show = '/resource/pdf/membalconfirm/'.$arrHeader["member_no"].'.pdf?v='.time();
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