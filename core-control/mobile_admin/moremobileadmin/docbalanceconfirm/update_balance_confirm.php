<?php
require_once('../../../autoload.php');

use Dompdf\Dompdf;

$dompdf = new DOMPDF();
if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','docbalanceconfirm')){
		$formatDept = $func->getConstant('dep_format');
		$member_no = $dataComing["member_no"];
		$id_confirm = $dataComing["id_confirm"];
		$arrIsConfirm  = array();
		$getConfirm = $conmysql->prepare("SELECT confirmdep_list, confirmlon_list, confirmshr_list,confirm_date  FROM gcconfirmbalancelist WHERE member_no = :member_no and id_confirm = :id_confirm ");
		$getConfirm->execute([
			':member_no' => $member_no,
			':id_confirm' => $id_confirm
		]);
		while($rowBalStatus = $getConfirm->fetch(PDO::FETCH_ASSOC)){
			$arrBalConfirm = array();
			$arrBalConfirm["confirm_date"] = $rowBalStatus["confirm_date"];
			$arrBalConfirm["CONFIRMDEP_LIST"] = json_decode($rowBalStatus["confirmdep_list"]);
			$arrBalConfirm["CONFIRMLON_LIST"] = json_decode($rowBalStatus["confirmlon_list"]);
			$arrBalConfirm["CONFIRMSHR_LIST"] = json_decode($rowBalStatus["confirmshr_list"]);

		}
		
		
		if(isset($dataComing["balance_date"]) && isset($dataComing["balance_date"]) != ""){
			$memberInfo = $conoracle->prepare("SELECT mp.PRENAME_SHORT as PRENAME_DESC,mb.MEMB_NAME,mb.MEMB_SURNAME,
													mg.MEMBGROUP_DESC
													FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
													LEFT JOIN MBUCFMEMBGROUP mg ON mb.MEMBGROUP_CODE = mg.MEMBGROUP_CODE
													WHERE mb.member_no = :member_no");
			$memberInfo->execute([':member_no' => $member_no]);
			$rowMember = $memberInfo->fetch(PDO::FETCH_ASSOC);
			$arrHeader = array();
			$arrDetail = array();
			$arrDetailConfirm = array();
			$arrHeader["full_name"] = $rowMember["PRENAME_DESC"].$rowMember["MEMB_NAME"]." ".$rowMember["MEMB_SURNAME"];
			$arrHeader["member_group"] = $rowMember["MEMBGROUP_DESC"];
			$arrHeader["member_no"] = $member_no;
			$arrHeader["date_confirm"] = $lib->convertdate(date('Y-m-d',strtotime($arrBalConfirm["confirm_date"])),'d M Y');
			$getBalanceDetail = $conoracle->prepare("SELECT BALANCE_AMT,BIZZTYPE_CODE,BIZZACCOUNT_NO,FROM_SYSTEM AS CONFIRMTYPE_CODE FROM yrconfirmstatement 
													WHERE member_no = :member_no and TO_CHAR(balance_date, 'YYYY-MM-DD') = :balance_date and FROM_SYSTEM NOT IN('GRT')
													ORDER BY SEQ_NO ASC");
			$getBalanceDetail->execute([
				':member_no' => $member_no,
				':balance_date' =>  $dataComing["balance_date"]
			]);
			while($rowBalDetail = $getBalanceDetail->fetch(PDO::FETCH_ASSOC)){
				$arrBalDetail = array();
				$arrConfirm = array();
				if($rowBalDetail["CONFIRMTYPE_CODE"] == "DEP"){
					$arrBalDetail["TYPE_DESC"] = 'เลขที่บัญชี';
					$arrBalDetail["DEP_DESC"] = 'เงินฝากบัญชีเลขที่';
					$arrBalDetail["BALANCE_AMT"] = number_format($rowBalDetail["BALANCE_AMT"],2);
					$arrBalDetail["LIST_DESC"] = $arrBalDetail["TYPE_DESC"].' '.$rowBalDetail["BIZZACCOUNT_NO"];
					$arrConfirm["BIZZACCOUNT_NO"]  = $rowBalDetail["BIZZACCOUNT_NO"];
					$arrConfirm["BALANCE_AMT"] = number_format($rowBalDetail["BALANCE_AMT"],2);
					$arrDetailConfirm["DEP"][] = $arrConfirm;				
					$account_key = array_search($rowBalDetail["BIZZACCOUNT_NO"], array_column($arrBalConfirm["CONFIRMDEP_LIST"], 'BIZZACCOUNT_NO'));
					if(isset($arrBalConfirm["CONFIRMDEP_LIST"][$account_key])){
						$arrBalDetail["STATUS"] = $arrBalConfirm["CONFIRMDEP_LIST"][$account_key]->STATUS;
					}
					$arrDetail["DEP"][] = $arrBalDetail;
				}else if($rowBalDetail["CONFIRMTYPE_CODE"] == "LON"){
					$arrBalDetail["TYPE_DESC"] = 'เลขที่สัญญา';
					$arrBalDetail["LOAN_DESC"] = 'เงินกู้สัญญาที';
					$arrBalDetail["BALANCE_AMT"] = number_format($rowBalDetail["BALANCE_AMT"],2);
					$arrBalDetail["LIST_DESC"] = $arrBalDetail["TYPE_DESC"].' '.$rowBalDetail["BIZZACCOUNT_NO"];
					$arrConfirm["BIZZACCOUNT_NO"]  = $rowBalDetail["BIZZACCOUNT_NO"];
					$arrConfirm["BALANCE_AMT"] = number_format($rowBalDetail["BALANCE_AMT"],2);
					$account_key = array_search($rowBalDetail["BIZZACCOUNT_NO"], array_column($arrBalConfirm["CONFIRMLON_LIST"], 'BIZZACCOUNT_NO'));
					if(isset($arrBalConfirm["CONFIRMLON_LIST"][$account_key])){
						$arrBalDetail["STATUS"] = $arrBalConfirm["CONFIRMLON_LIST"][$account_key]->STATUS;
					}
					$arrDetail["LON"][] = $arrBalDetail;
					$arrDetailConfirm["LON"][] = $arrConfirm;
				}else if($rowBalDetail["CONFIRMTYPE_CODE"] == "SHR"){
					$arrBalDetail["TYPE_DESC"] = 'ทุนเรือนหุ้น';
					$arrBalDetail["BALANCE_AMT"] = number_format($rowBalDetail["BALANCE_AMT"],2);
					$arrBalDetail["LIST_DESC"] = $arrBalDetail["TYPE_DESC"];
					$arrConfirm["BIZZACCOUNT_NO"]  = $rowBalDetail["BIZZACCOUNT_NO"];
					$arrConfirm["BALANCE_AMT"] = number_format($rowBalDetail["BALANCE_AMT"],2);
					
					if(isset($arrBalConfirm["CONFIRMSHR_LIST"][0])){
						$arrBalDetail["STATUS"] = $arrBalConfirm["CONFIRMSHR_LIST"][0]->STATUS;
					}
					$arrDetail["SHR"] = $arrBalDetail;
					$arrDetailConfirm["SHR"][] = $arrConfirm;
				}else{
					$arrBalDetail["BALANCE_AMT"] = number_format($rowBalDetail["BALANCE_AMT"],2);
					$arrBalDetail["LIST_DESC"] = $rowBalDetail["BIZZACCOUNT_NO"];
					$arrDetail["ETC"][] = $arrBalDetail;
				}
				$arrDetail[] = $arrBalDetail;
			}
			$arrayPDF = GeneratePdfDoc($arrHeader,$arrDetail);
			if($arrayPDF["RESULT"]){
				$FlagComfirm = $conmysql->prepare("UPDATE gcconfirmbalancelist SET url_path  = :url_path where id_confirm =:id_confirm");
				if($FlagComfirm->execute([
					':id_confirm' => $id_confirm,
					':url_path' => $config["URL_SERVICE"].$arrayPDF["PATH"]
				])){
					$arrayResult['PATH'] = $config["URL_SERVICE"].$arrayPDF["PATH"];
					$arrayResult['RESULT'] = TRUE;
					require_once('../../../../include/exit_footer.php');
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
					//$log->writeLog('errorusage',$logStruc);
					$message_error = "ไฟล์ ".$filename." Update ลงตาราง  cmconfirmmaster ไม่ได้"."\n".$FlagComfirm->queryString."\n"."data => ".json_encode([
						':member_no' => $member_no,
						':remark' => 'MobileApp / '.$dataComing["remark"],
						':confirm_flag' => $dataComing["confirm_flag"],
						':balance_date' => date('Y-m-d',strtotime($dataComing["balance_date"])),
					]);
					//$lib->sendLineNotify($message_error);
					$arrayResult['RESPONSE_CODE'] = "WS1038";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../../../include/exit_footer.php');
				}
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0044";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
				
			}
		}else{
			$arrayResult['RESULT'] = FALSE;
			http_response_code(204);
			require_once('../../../../include/exit_footer.php');
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../../include/exit_footer.php');
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
	require_once('../../../../include/exit_footer.php');
}

function GeneratePdfDoc($arrHeader,$arrDetail) {
	$html = '
		<style>
			@font-face {
				font-family: TH Niramit AS;
				src: url(../../../../resource/fonts/TH Niramit AS.ttf);
			}
			@font-face {
				font-family: TH Niramit AS;
				src: url(../../../../resource/fonts/TH Niramit AS Bold.ttf);
				font-weight: bold;
			}
			.bold{
				font-weight:bold;
			}
			* {
				font-family: TH Niramit AS;
			}
			.center{
				text-align:center;
			}
			.left{
				text-align:right;
			}
			.right{
				text-align:right;
			}
			.data{
				font-size:12pt;
				margin-top:-2px;
				white-space: nowrap;
			}
			body {
				padding: 0 ;
				font-size: 12pt;
				line-height: 20px;
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
			.list{
				padding-left:65px;
			}
			.sub-list{
				padding-left:72px;
			}
			.flex{
				display:flex;
				height: 25px;
			}
			.inline{
				display:inline;
			}
			.border{
				border:1px solid ;
			}
			.border-bottom{
				border-bottom:1px solid;
			}
		</style>';
	//ระยะขอบ กระดาษ
	$html .= '<div  style="margin: -20px -10px">';
	//หน้าที่ 1
	$html .='<div class="wrapper-page">';
	$html .= '
			<div style="position:absolute">
					<div style="margin-left:460px;">ชื่อ '.($arrHeader["full_name"]??null).'</div>
					<div style="margin-left:460px;">เลขที่สมาชิก '.($arrHeader["member_no"]??null).'</div>
					<div style="margin-left:460px;">สังกัด '.($arrHeader["member_group"]??null).'</div>
			</div>
			<div  style="font-size:15pt" class="bold">สหกรณ์ออมทรัพย์มหาวิทยาลัยสุโขทัยธรรมาธิราช จํากัด</div>
			<div  style="font-size:15pt" class="bold">หนังสือยืนยันยอดเงินกู้ เงินรับฝาก และทุนเรือนหุ้น </div>
			<div style="margin-top:20px;">
				<div style="position:absolute; margin-left:460px;">'.($arrHeader["date_confirm"]??null).'</div>
				<div class="inline" style="padding-left:10px; padding-right:10px;">เรียน</div>
				<div class="inline" style="padding-right:10px;">'.($arrHeader["full_name"]??null).'</div>
				<div class="inline" style="padding-right:10px;">ทะเบียน </div>
				<div class="inline">'.($arrHeader["member_no"]??null).'</div>
			</div>
			<div style="margin-top:30px; margin-left:65px;">สหกรณ์ออมทรัพย์มหาวิทยาลัยสุโขทัยธรรมาธิราช จํากัด ขอเรียนว่า <span class="bold"><u>ณ วันที่ '.($arrHeader["date_confirm"]??null).'</u></span></div>
			<div>ท่านมีทุนเรือนหุ้น เงินกู้ค้างชําระ เงินรับฝาก ต่อสหกรณ์ตามรายการต่างๆ ดังนี</div>
			<div  style="margin-left:10px; margin-top:10px; width:570px;">
				<div>
					<div class="flex">
						<div class="bold">ทุนเรือนหุ้นทั้งหมด</div>
						<div class="bold right">จำนวนเงิน(บาท)</div>
					</div>';
					if(isset($arrDetail["SHR"])){
						$html .='
						<div class="flex">
							<div  class="list">ทุนเรือนหุ้น </div>
							<div class="right">'.($arrDetail["SHR"]["BALANCE_AMT"]??null).'</div>
						</div>
				</div>
						';
					}
					
		$html.='
				<div>
				';
		
		if(!empty($arrDetail["LON"])){
			$html .='
				<div class="flex">
					<div class="bold">เงินกู้ต่สหกรณ์ ตามรายการดังนี้</div>
				</div>
			';
		}

		foreach($arrDetail["LON"] AS $arrLoan){
			$html.='
				<div class="flex">
					<div  class="list">'.($arrLoan["LIST_DESC"]??null).'</div>
					<div class="right">'.($arrLoan["BALANCE_AMT"]??null).'</div>
				</div>
			';
		}

		if(!empty($arrDetail["DEP"])){
			$html.='
			<div class="flex">
				<div class="bold">เงินฝากไว้กับสสหกรณ์ ตามรายการดังนี้</div>
			</div>';
		}
		foreach($arrDetail["DEP"] AS $arrDept){
			$html.='
				<div class="flex">
					<div  class="list">'.($arrDept["LIST_DESC"]??null).'</div>
					<div class="right">'.($arrDept["BALANCE_AMT"]??null).'</div>
				</div>
			';
		}
		$html.='
			</div>
				<div class="list nowrap" style="letter-spacing:-0.3px">เมื่อท่านได้รับหนังสือแจ้งฉบับนี้ ขอได้โปรดยืนยันยอด หรือทักท้วง ขอได้โปรดยืนยันยอด หรือทักท้วง (ตามแบบข้างล่างนี้) และส่งกลับคืนทั้งฉบับ </div>
				<div class="nowrap" style="">ไปยังผู้สอบบัญชี ภายในกําหนด 7 วัน นับตั้งแต่วันที่ได้รับหนังสือนี้ หากท่านส่งกลับคืนภายในกําหนดจะทําให้การตรวจ สอบบัญชีรวดเร็วขึ้น </div>
				<div class="right" style="margin-left:570px;">ขอแสดงความนับถือ</div>
				<div>&nbsp;</div>
				<div class="center" style="margin-top:10px; margin-left:450px; width:150px;"> <img src="../../../../resource/utility_icon/signature/manager.png" style="height:50px; "></div>
				<div class="center nowrap" style="margin-top:10px; margin-left:450px; width:150px;">นายประสาน ชนาพงษ์จารุ </div>
				<div class="center nowrap" style="margin-left:450px; width:150px;">ผู้จัดการ</div>
			';

		$html.='	
	</div>';
		//ปิดหน้า1
	$html .='</div>';

	//เริ่มหน้า 2 	
	$html .='
	<div class="wrapper-page">
		<div style="margin-left:185px;">
			<div style="padding-right:10px" class="inline">เรียน</div>
			<div class="inline">นายสิรวิชญ์ ไพศาสตร์</div>
		</div>
		<div style="margin-left:223px">ตู้ปณ. 32 ปณศ.คลองจั.น แขวงคลองจั.น เขตบางกะปิ กทม. 10240</div>
		<div style="border-top:1px dotted; padding-top:20px; margin-top:60px; margin-bottom:40px;">
			<div style="font-size:15pt" class="bold center">สหกรณ์ออมทรัพย์มหาวิทยาลัยสุโขทัยธรรมาธิราช จํากัด</div>
			<div style="font-size:15pt" class="bold center">หนังสือตอบยืนยันยอด</div>
		</div>
		<div>
			<div style="padding-right:15px" class="inline">เรียน</div>
			<div style="padding-right:10px" class="inline">นายสิรวิชญ์ ไพศาสตร์</div>
			<div style="padding-right:10px" class="inline">ผู้ตรวจบัญชี</div>
			<div class="inline">สหกรณ์ออมทรัพย์มหาวิทยาลัยสุโขทัยธรรมาธิราช จํากัด ข้าพเจ้าขอยืนยันยอด </div>
		</div>
		<div style="margin-left:42px;">
		ทุนเรือนหุ้น เงินกู้ค้างชําระ เงินรับฝาก ณ วันที่ '.($arrHeader["date_confirm"]??null).' ตามรายการที่ทางสหกรณ์ออมทรัพย์  แจ้งให้
		</div>
		<div style="margin-left:42px;">ข้าพเจ้าทราบ ดังนี้</div>
		<div style="margin-left:42px;">
			<table>
				<tr>
					<th style="width:280px;" class="border-bottom">รายการ</th>
					<th style="width:150px;" class="border-bottom center">จำนวนเงิน(บาท)</th>
					<th style="width:50px;" class="border-bottom center">ถูกต้อง</th>
					<th style="width:50px;" class="border-bottom center">ไม่ถูกต้อง</th>
				</tr>';

				if(isset($arrDetail["SHR"])){
				
		$html .='
				<tr>
					<td>ทุนเรือนหุ้น</td>
					<td class="right">'.($arrDetail["SHR"]["BALANCE_AMT"]??null).'</td>
					<td class="center"><input type="checkbox" style="margin-left:20px;"'.($arrDetail["SHR"]["STATUS"] == true?"checked":null).' ></td>
					<td class="center"><input type="checkbox" style="margin-left:20px;" '.($arrDetail["SHR"]["STATUS"] == false?"checked":null).'></td>
				</tr>';
				}

		foreach($arrDetail["LON"] AS $arrLoan){
			$html .='
			<tr>
				<td>'.($arrLoan["LOAN_DESC"]??null).'</td>
				<td class="right">'.($arrLoan["BALANCE_AMT"]??null).'</td>
				<td class="center"><input type="checkbox" style="margin-left:20px;"'.($arrLoan["STATUS"] == true?"checked":null).' ></td>
				<td class="center"><input type="checkbox" style="margin-left:20px;" '.($arrLoan["STATUS"] == false?"checked":null).'></td>
			</tr>';
			
		}

		foreach($arrDetail["DEP"] AS $arrDept){
			$html .='
			<tr> 
				<td>'.($arrDept["DEP_DESC"]??null).'</td>
				<td class="right">'.($arrDept["BALANCE_AMT"]??null).'</td>
				<td class="center"><input type="checkbox" style="margin-left:20px;"'.($arrDept["STATUS"] == true?"checked":null).' ></td>
				<td class="center"><input type="checkbox" style="margin-left:20px;" '.($arrDept["STATUS"] == false?"checked":null).'></td>
			</tr>';
		}
				

	$html.='			
			</table>
		</div>
		<div>
			คำชี้แจง............................................................................................................................................................................................................................................
		</div>
		<div>.........................................................................................................................................................................................................................................................</div>
		<div class="right" style="margin-top:30px; margin-right:30px;">................................................................</div>
		<div class="center  nowrap" style="margin-left:500px; width:200px">'.($arrHeader["full_name"]??null).'</div>
		<div class="center  nowrap" style="margin-left:500px; width:200px">เลขทะเบียน : '.($arrHeader["member_no"]??null).'</div>

		';
	//ปิดหน้า 2
	$html .='</div>';

	//ระยะขอบ
	$html .= '</div>';
		
	$dompdf = new Dompdf([
		'fontDir' => realpath('../../../../resource/fonts'),
		'chroot' => realpath('/'),
		'isRemoteEnabled' => true
	]);
	$dompdf->set_paper('A4');
	$dompdf->load_html($html);
	$dompdf->render();
	$pathfile = __DIR__.'/../../../../resource/pdf/docbalconfirm';
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