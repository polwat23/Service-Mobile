<?php
require_once('../autoload.php');

use Dompdf\Dompdf;

$dompdf = new DOMPDF();

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DocBalanceConfirm')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$getBalanceMaster = $conmssql->prepare("SELECT CONVERT(VARCHAR(10),MAX(BALANCE_DATE),23) as BALANCE_DATE FROM YRCONFIRMMASTER WHERE member_no = :member_no");
		$getBalanceMaster->execute([':member_no' => $member_no]);
		$rowBalMaster = $getBalanceMaster->fetch(PDO::FETCH_ASSOC);
		
		$getBalStatus = $conmysql->prepare("SELECT confirm_status FROM confirm_balance WHERE member_no = :member_no and balance_date = :balance_date and confirm_status  in ('1','0') ");
		$getBalStatus->execute([
			':member_no' => $member_no,
			':balance_date' => date('Y-m-d',strtotime($rowBalMaster["BALANCE_DATE"]))
		]);
		$rowBalStatus = $getBalStatus->fetch(PDO::FETCH_ASSOC);
		if(isset($rowBalStatus["confirm_status"])){
			$arrayResult['IS_CONFIRM'] = TRUE;
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}
		
		if(isset($rowBalMaster["BALANCE_DATE"]) && isset($rowBalMaster["BALANCE_DATE"]) != ""){
			$memberInfo = $conmssql->prepare("SELECT mp.PRENAME_SHORT as PRENAME_DESC,mb.MEMB_NAME,mb.MEMB_SURNAME,
													mg.MEMBGROUP_DESC
													FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
													LEFT JOIN MBUCFMEMBGROUP mg ON mb.MEMBGROUP_CODE = mg.MEMBGROUP_CODE
													WHERE mb.member_no = :member_no");
			$memberInfo->execute([':member_no' => $member_no]);
			$rowMember = $memberInfo->fetch(PDO::FETCH_ASSOC);
			$arrHeader = array();
			$arrDetail = array();
			$arrHeader["full_name"] = $rowMember["PRENAME_DESC"].$rowMember["MEMB_NAME"]." ".$rowMember["MEMB_SURNAME"];
			$arrHeader["member_group"] = $rowMember["MEMBGROUP_DESC"];
			$arrHeader["member_no"] = $member_no;
			$arrHeader["date_confirm"] = $lib->convertdate(date('Y-m-d',strtotime($rowBalMaster["BALANCE_DATE"])),'d M Y');
			$getBalanceDetail = $conmssql->prepare("SELECT BALANCE_AMT,BIZZTYPE_CODE,BIZZACCOUNT_NO,FROM_SYSTEM AS CONFIRMTYPE_CODE FROM yrconfirmstatement 
													WHERE member_no = :member_no and CONVERT(VARCHAR(10),balance_date,23) = :balance_date and FROM_SYSTEM NOT IN('GRT')
													ORDER BY SEQ_NO ASC");
			$getBalanceDetail->execute([
				':member_no' => $member_no,
				':balance_date' => $rowBalMaster["BALANCE_DATE"]
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
				$arrDetail[] = $arrBalDetail;
			}
			$arrayPDF = GeneratePdfDoc($arrHeader,$arrDetail);
			if($arrayPDF["RESULT"]){
				if ($forceNewSecurity == true) {
					$arrayResult['REPORT_URL'] = $config["URL_SERVICE"]."/resource/get_resource?id=".hash("sha256", $arrayPDF["PATH"]);
					$arrayResult["REPORT_URL_TOKEN"] = $lib->generate_token_access_resource($arrayPDF["PATH"], $jwt_token, $config["SECRET_KEY_JWT"]);
				} else {
					$arrayResult['REPORT_URL'] = $config["URL_SERVICE"].$arrayPDF["PATH"];
				}
				$arrayResult['BALANCE_DATE'] = date('Y-m-d',strtotime($rowBalMaster["BALANCE_DATE"]));
				$arrayResult['IS_CONFIRM'] = FALSE;
				$arrayResult['DISABLED_CONFIRM'] = FALSE;
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0044";
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
		<div style="positon:absolute;   width:100%;  " class="text-center">ผจก</div>
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
			  <div style="position:absolute width:200px;" class="text-center">ผู้ตรวจ</div>
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
	$dompdf = new Dompdf([
		'fontDir' => realpath('../../resource/fonts'),
		'chroot' => realpath('/'),
		'isRemoteEnabled' => true
	]);
	$dompdf->set_paper('A4');
	$dompdf->load_html($html);
	$dompdf->render();
	$pathfile = __DIR__.'/../../resource/pdf/docbalconfirm';
	if(!file_exists($pathfile)){
		mkdir($pathfile, 0777, true);
	}
	$pathfile = $pathfile.'/'.$arrHeader["member_no"].'.pdf';
	$pathfile_show = '/resource/pdf/docbalconfirm/'.$arrHeader["member_no"].'.pdf?v='.time();
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