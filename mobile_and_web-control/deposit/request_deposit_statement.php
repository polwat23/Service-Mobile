<?php
require_once('../autoload.php');

use Dompdf\Dompdf;

$dompdf = new DOMPDF();

if($lib->checkCompleteArgument(['menu_component','account_no','request_date'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DepositStatement')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$fetchMail = $conmysql->prepare("SELECT email FROM gcmemberaccount WHERE member_no = :member_no");
		$fetchMail->execute([':member_no' => $payload["member_no"]]);
		$rowMail = $fetchMail->fetch(PDO::FETCH_ASSOC);
		$arrayAttach = array();
		$account_no = preg_replace('/-/','',$dataComing["account_no"]);
		$getCardPerson = $conoracle->prepare("SELECT card_person FROM mbmembmaster WHERE member_no = :member_no");
		$getCardPerson->execute([':member_no' => $member_no]);
		$rowCardPerson = $getCardPerson->fetch(PDO::FETCH_ASSOC);
		$passwordPDF = filter_var($rowCardPerson["CARD_PERSON"], FILTER_SANITIZE_NUMBER_INT);
		foreach($dataComing["request_date"] as $date_between){
			$arraySTMGrp = array();
			$arrHeaderAPISTM[] = 'Req-trans : '.date('YmdHis');
			$arrDataAPISTM["MemberID"] = substr($member_no,-6);
			$arrDataAPISTM["CoopAccountNo"] = $account_no;
			$arrDataAPISTM["FromDate"] = date('c',strtotime($date_between[0]));
			$arrDataAPISTM["ToDate"] = date('c',strtotime($date_between[1]));
			$arrResponseAPISTM = $lib->posting_dataAPI($config["URL_SERVICE_EGAT"]."Account/InquiryBalance",$arrDataAPISTM,$arrHeaderAPISTM);
			$arrResponseAPISTM = json_decode($arrResponseAPISTM);
			if($arrResponseAPISTM->responseCode == "200"){
				foreach($arrResponseAPISTM->inquieryBalanceDetail as $accData){
					$arraySTM = array();
					$arraySTM["TYPE_TRAN"] = $accData->trxDesc;
					$arraySTM["SIGN_FLAG"] = $accData->trxOperate == '+' ? "1" : "-1";
					$arraySTM["OPERATE_DATE"] = $lib->convertdate($accData->trxDate,'D m Y');
					$arraySTM["TRAN_AMOUNT"] = str_replace('-','',$accData->totalAmount);
					$arraySTM["PRNCBAL"] = $rowDataSTM["PRNCBAL"];
					$arraySTMGrp[] = $arraySTM;
				}
			}
			$arrayData["STATEMENT"] = $arraySTMGrp;
			$arrayData["MEMBER_NO"] = $payload["member_no"];
			$arrayData["DEPTACCOUNT_NO"] = $lib->formataccount_hidden($account_no,$func->getConstant('hidden_dep'));
			$arrayData["DATE_BETWEEN_FORMAT"] = $lib->convertdate($date_between[0],'d m Y').' - '.$lib->convertdate($date_between[1],'d m Y');
			$arrayData["DATE_BETWEEN"] = $date_between[0].'-'.$date_between[1];
			$arrayGenPDF = generatePDFSTM($dompdf,$arrayData,$lib,$passwordPDF);
			if($arrayGenPDF["RESULT"]){
				$arrayAttach[] = $arrayGenPDF["PATH"];
			}
		}
		$arrayDataTemplate = array();
		$arrayDataTemplate["ACCOUNT_NO"] = $lib->formataccount_hidden($account_no,$func->getConstant('hidden_dep'));
		$template = $func->getTemplateSystem('DepositStatement');
		$arrResponse = $lib->mergeTemplate($template["SUBJECT"],$template["BODY"],$arrayDataTemplate);
		$arrMailStatus = $lib->sendMail($rowMail["email"],$arrResponse["SUBJECT"],$arrResponse["BODY"],$mailFunction,$arrayAttach);
		if($arrMailStatus["RESULT"]){
			foreach($arrayAttach as $path){
				unlink($path);
			}
			$arrayResult['RESULT'] = TRUE;
			require_once('../../include/exit_footer.php');
		}else{
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS0019",
				":error_desc" => "���������� ".$rowMail["email"]."\n"."Error => ".$arrMailStatus["MESSAGE_ERROR"],
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$arrayResult['RESPONSE_CODE'] = "WS0019";
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
		":error_desc" => "�� Argument �����ú "."\n".json_encode($dataComing),
		":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
	];
	$log->writeLog('errorusage',$logStruc);
	$message_error = "��� ".$filename." �� Argument �����ú���� "."\n".json_encode($dataComing);
	$lib->sendLineNotify($message_error);
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
	
}

function generatePDFSTM($dompdf,$arrayData,$lib,$password){
	$dompdf = new DOMPDF();
	//style table
	  $html = '<style>

		  @font-face {
			  font-family: THSarabun;
				 src: url(../../resource/fonts/THSarabun.ttf);
			}
			@font-face {
				font-family: "THSarabun";
				src: url(../../resource/fonts/THSarabun Bold.ttf);
				font-weight: bold;
			}
		  * {
			font-family: THSarabun;
		  }


		  body {
			margin-top: 3.6cm;
			margin-bottom:0.5cm;
			padding: 10px 0 0 0;
		  }
		  tr {
			border-right: 0.5px #DDDDDD solid;	
			border-left:0.5px #DDDDDD solid;	
		  }
		  td {
			border-bottom: 0.5px #DDDDDD solid;
		  }
		  table {
			border-spacing: -1px;
			width: 100%;
			border: 0.5px #DDDDDD solid;
		  }
		  th {
			text-align:center;
			color:white;
			padding: 5px;
			font-size: 20px;
			font-weight:bold;
			background-color:#0C6DBF;
			border: 0.5px #DDDDDD solid;	
		  }
		  td{
			padding:5px;
			font-size: 18px;
		  }
		  p{
			margin:0px;
		  }
		  header {
			position: fixed;
			top: 0cm;
			left: 0cm;
			right: 0cm;
			color: #000; 
		}
		.label-type {
			font-weight: bold;
			font-size: 20px;
			padding-top: 80px;
		}
		.frame-info-user {
			padding: 10px -10px 10px 10px;
			position: fixed;
			left: 440px;
			top: 30px;
			width: 260px;
			height: 90px;
			border: 0.5px #DDDDDD solid;
			border-radius: 5px;
		}
		.label {
			width: 30%;
			padding: 0 5px;
			font-size: 18px;
		}
		  </style>
		';

	//head table
	$html .='
	 <div style="text-align: center;margin-bottom: 0px;" padding:0px; margin-bottom:20px; width:100%;></div>
	<header>
	<div style="position:fixed;">
			   <div style="padding:0px;"><img src="../../resource/logo/logo.jpg" style="width:50px "></div>
			   <div style=" position: fixed;top:2px; left: 60px; font-size:20px; font-weight:bold;">
					�ˡó������Ѿ������Է�������Դ� �ӡѴ
			   </div>
			   <div style=" position: fixed;top:25px; left: 60px;font-size:20px">
					Mahidol University Savings and Credit Co-Operative, Limited
			   </div>
			   </div>
				<div class="frame-info-user">
					<div style="display:flex;width: 100%;padding-top: -20px;">
					<div class="label">�Ţ��Ҫԡ</div>
					<div style="padding-left: 90px;font-weight: bold;font-size: 17px;">'.$arrayData["MEMBER_NO"].'</div>
					</div>
					<div style="display:flex;width: 100%;padding-top: -20px;">
					<div class="label">�Ţ�ѭ���Թ�ҡ</div>
					<div style="padding-left: 90px;font-weight: bold;font-size: 17px;">'.$arrayData["DEPTACCOUNT_NO"].'</div>
					</div>
					<div style="display:flex;width: 100%">
					<div class="label">�����ҧ�ѹ���</div>
					<div style="padding-left: 90px;font-weight: bold;font-size: 17px;">'.$arrayData["DATE_BETWEEN_FORMAT"].'</div>
					</div>
				</div>
			   <div class="label-type">
			   <p style="font-size: 25px;">��¡���Թ�ѭ���Թ�ҡ</p>
			   </div>
			</header>';
	$html .='<main>';
	$html .= '  <div style="width: 100%;">
	<table >
	  <thead>
		<tr>
		  <th style="text-align:center;width:70px;">�ѹ ��͹ ��</th>
		  <th>��¡��</th>
		  <th>�ҡ</th>
		  <th>�͹</th>
		  <th>�ʹ�������</th>
		  <th>�Ţ��ҧ�ԧ</th>
		</tr>
	  </thead>

	  <tbody>

	';
	// table value
	$count_deposit = 0;
	$count_withdraw = 0;
	$count_sumall = 0;
	$sum_deposit = 0;
	$sum_withdraw = 0;
	$sum_all = 0;
	foreach($arrayData["STATEMENT"] as $stm){
		$count_sumall++;
		$sum_all += $stm["TRAN_AMOUNT"];
	  $html .= '
		<tr>
		  <td style="text-align:center;width:70px;">'.$stm["OPERATE_DATE"].'</td>
		  <td style="text-align:left">'.$stm["TYPE_TRAN"].'</td>';
			if($stm["SIGN_FLAG"] == '1'){
				$count_deposit++;
				$sum_deposit += $stm["TRAN_AMOUNT"];
				$html .= '<td style="text-align:right">'.number_format($stm["TRAN_AMOUNT"],2).'</td>
				 <td style="text-align:right"></td>';
			}else{
				$count_withdraw++;
				$sum_withdraw += $stm["TRAN_AMOUNT"];
				$html .= ' <td style="text-align:right"></td>
				<td style="text-align:right">'.number_format($stm["TRAN_AMOUNT"],2).'</td>';
			}
		  $html .= '<td style="text-align:right">'.number_format($stm["PRNCBAL"],2).'</td>
		  <td style="text-align:center">'.$stm["DEPTSLIP_NO"].'</td>
		</tr>
	';
	}
	//list sum
	$html .='
	  <tr>
		<td ></td>
		<td ><b>��¡�ö͹ '.$count_withdraw.' ��¡��</b></td>
		<td ></td>
		<td style="text-align:right"><b>'.number_format($sum_withdraw,2).'</b></td>
		<td ></td>
		<td ></td>
	  </tr>
	  <tr>
		<td ></td>
		<td ><b>��¡�ýҡ '.$count_deposit.' ��¡��</b></td>
		<td ></td>
		<td style="text-align:right"><b>'.number_format($sum_deposit,2).'</b></td>
		<td ></td>
		<td ></td>
	  </tr>
	  <tr>
		<td style="border-bottom:1px solid #000;" ></td>
		<td style="border-bottom:1px solid #000;" ><b>�ʹ����ء��¡�� '.$count_sumall.' ��¡��</b></td>
		<td style="border-bottom:1px solid #000;" ></td>
		<td style="border-bottom:1px solid #000;" ></td>
		<td style="border-bottom:1px solid #000; text-align:right;" ><b>'.number_format($sum_all,2).'</b></td>
		<td style="border-bottom:1px solid #000;" ></td>
	  </tr>
	';

	$html .='</tbody></table>';
	$html .= '</div>';
	$html .='</main>';

	$dompdf->set_paper('A4');
	$dompdf->load_html($html);
	$dompdf->render();
	$pathOutput = __DIR__."/../../resource/pdf/statement/".$arrayData['DEPTACCOUNT_NO']."_".$arrayData["DATE_BETWEEN"].".pdf";
	$font = $dompdf->getFontMetrics()->get_font("THSarabun", "");
	$dompdf->getCanvas()->page_text(520,  25, "˹�� {PAGE_NUM} / {PAGE_COUNT}", $font, 12, array(0,0,0));
	//$dompdf->getCanvas()->get_cpdf()->setEncryption($password);
	$output = $dompdf->output();
	if(file_put_contents($pathOutput, $output)){
		$arrayPDF["RESULT"] = TRUE;
	}else{
		$arrayPDF["RESULT"] = FALSE;
	}
	$arrayPDF["PATH"] = $pathOutput;
	return $arrayPDF;
}
?>
