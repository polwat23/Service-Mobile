<?php
require_once('../autoload.php');

use Dompdf\Dompdf;

$dompdf = new DOMPDF();

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DepositInterRest')){
		$member_no = $payload["ref_memno"];
		$date_now = $dataComing["date"] ??  $date_now = date('Y-m-d');
		$formatDept = $func->getConstant('dep_format');
		$formatDeptHidden = $func->getConstant('hidden_dep');
		$arrAccount = array();
		$header = array();
					$arrAccount = array();
		$getDepositInt = $conoracle->prepare("SELECT DEPTACCOUNT_NO,
								TRIM(MEMBER_NO) as MEMBER_NO,
								DEPTACCOUNT_NAME,
								BALANCE_AMT,
								SUBSTR(DEPTPASSBOOK_NO ,5) as DEPTPASSBOOK_NO,
								ACCOUNT_NO,
								PRNC_DATE,
								INTEREST_RATE,
								DEPTTYPE_CODE,
								DEPTTYPE_DESC,
								(1 +  TO_DATE(:datenow,'YYYY-MM-DD')  -  DECODE(CNT, -1, PRNC_DATE, 0 , PRNC_DATE, (DECODE(CNT2, 1, START_CALINT_DATE, END_CALINT_DATE)))) AS  COUNT_DATE,           
								DECODE(CNT, -1, PRNC_DATE, 0 , PRNC_DATE, (DECODE(CNT2, 1, START_CALINT_DATE, END_CALINT_DATE))) AS DUE_DATE
								FROM
								(SELECT DPDEPTMASTER.DEPTACCOUNT_NO,   
										DPDEPTMASTER.MEMBER_NO,     
										DPDEPTMASTER.DEPTACCOUNT_NAME,   
										DPDEPTPRNCFIXED.PRNC_AMT   AS BALANCE_AMT,  
										DPDEPTMASTER.DEPTTYPE_CODE,
										DPDEPTTYPE.DEPTTYPE_DESC,
										DPDEPTMASTER.DEPTPASSBOOK_NO,   
										SUBSTR( RTRIM( DPDEPTMASTER.DEPTACCOUNT_NO), -4  ) ||  SUBSTR( '000'|| SUBSTR(DPDEPTMASTER.DEPTACCOUNT_NO , 1,  LENGTH( RTRIM(DPDEPTMASTER.DEPTACCOUNT_NO )) -4 ), -6)  AS ACCOUNT_NO,   
										DPDEPTPRNCFIXED.PRNC_DATE,   
										DPDEPTPRNCFIXED.INTEREST_RATE,   
										DPDEPTPRNCFIXED.LASTCALINT_DATE,   
										1 AS COUNT_DATE,   
										SUM(NVL(DPINTDUEDATE.RECV_INTFLAG, -1)) AS CNT,
										SUM(CASE WHEN DPINTDUEDATE.START_CALINT_DATE <= TO_DATE(:datenow,'YYYY-MM-DD')  AND DPINTDUEDATE.END_CALINT_DATE > TO_DATE(:datenow,'YYYY-MM-DD')  THEN 1 ELSE 0 END) AS CNT2, 
										MAX(DPINTDUEDATE.START_CALINT_DATE) AS START_CALINT_DATE, 
										MAX(DPINTDUEDATE.END_CALINT_DATE) AS END_CALINT_DATE,   
										DPDEPTPRNCFIXED.PRNC_DATE AS DUE_DATE
										FROM DPDEPTMASTER,    DPDEPTPRNCFIXED,   DPINTDUEDATE ,DPDEPTTYPE
										WHERE  	(DPDEPTMASTER.DEPTACCOUNT_NO = DPDEPTPRNCFIXED.DEPTACCOUNT_NO )   
										AND (DPDEPTMASTER.BRANCH_ID = DPDEPTPRNCFIXED.MASTER_BRANCH_ID )   
										AND ( DPDEPTMASTER.DEPTGROUP_CODE = DPDEPTTYPE.DEPTGROUP_CODE ) 
										AND ( DPDEPTMASTER.DEPTTYPE_CODE = DPDEPTTYPE.DEPTTYPE_CODE ) 
										AND (DPDEPTMASTER.DEPTGROUP_CODE = DPDEPTPRNCFIXED.DEPTGROUP_CODE )  
										AND DPDEPTPRNCFIXED.PRINC_ID = DPINTDUEDATE.PRINC_ID (+) 
										AND (DPDEPTMASTER.DEPTOPEN_DATE <= TO_DATE(:datenow,'YYYY-MM-DD')  )   
										AND ((DPDEPTMASTER.DEPTCLOSE_DATE > TO_DATE(:datenow,'YYYY-MM-DD')    
										AND DPDEPTMASTER.DEPTCLOSE_STATUS = 1) OR  DPDEPTMASTER.DEPTCLOSE_STATUS = 0)   
										AND ( DPDEPTMASTER.DEPTGROUP_CODE = '01' )   
										AND (DPDEPTMASTER.CANCEL_DATE < TO_DATE(:datenow,'YYYY-MM-DD')  OR DPDEPTMASTER.CANCEL_DATE IS NULL)   
										AND TRIM(DPDEPTMASTER.MEMBER_NO) = :member_no   
										AND NVL(DPINTDUEDATE.RECV_INTFLAG, -1) IN (-1, 0, 1) 
										AND NVL(DPINTDUEDATE.START_CALINT_DATE, DPDEPTPRNCFIXED.PRNC_DATE) <= TO_DATE(:datenow,'YYYY-MM-DD') 
										GROUP BY DPDEPTMASTER.DEPTACCOUNT_NO,DPDEPTMASTER.MEMBER_NO,DPDEPTMASTER.DEPTACCOUNT_NAME,DPDEPTPRNCFIXED.PRNC_AMT,DPDEPTMASTER.DEPTTYPE_CODE,DPDEPTTYPE.DEPTTYPE_DESC,   
										DPDEPTMASTER.DEPTPASSBOOK_NO,DPDEPTPRNCFIXED.PRNC_DATE,DPDEPTPRNCFIXED.INTEREST_RATE,DPDEPTPRNCFIXED.LASTCALINT_DATE,DPDEPTPRNCFIXED.PRNC_AMT
										ORDER BY  DPDEPTPRNCFIXED.PRNC_DATE ASC )");
		$getDepositInt->execute([
				':member_no' => $member_no,
				':datenow' => $date_now
		]);
		while($rowAccount = $getDepositInt->fetch(PDO::FETCH_ASSOC)){
			$header["DEPTACCOUNT_NAME"] = $rowAccount["DEPTACCOUNT_NAME"];
			$account_no = $lib->formataccount($rowAccount["DEPTACCOUNT_NO"],$formatDept);
			$arrAccount["DEPTACCOUNT_NO"] = $account_no;
			$arrAccount["PRNC_DATE"] = $lib->convertdate($rowAccount["PRNC_DATE"],'d/n/Y');
			$arrAccount["DEPTACCOUNT_NAME"] = $rowAccount["DEPTACCOUNT_NAME"];
			$arrAccount["MEMBER_NO"] = $rowAccount["MEMBER_NO"];
			$arrAccount["DEPTPASSBOOK_NO"] = $rowAccount["DEPTPASSBOOK_NO"];
			$arrAccount["INTEREST_RATE"] =  number_format($rowAccount["INTEREST_RATE"],3);
			$arrAccount["COUNT_DATE"] = $rowAccount["COUNT_DATE"];
			$arrAccount["DUE_DATE"] = $lib->convertdate($rowAccount["DUE_DATE"],'d/n/Y');
			$arrAccount["BALANCE_AMT"] = number_format($rowAccount["BALANCE_AMT"],2);
			$arrAccount["INT_AMT"] = number_format(($arrAccount["COUNT_DATE"] * $rowAccount["BALANCE_AMT"] * $rowAccount["INTEREST_RATE"] /36500),2);
			$arrAccount["SUM_INTEREST"] += ($arrAccount["COUNT_DATE"] * $rowAccount["BALANCE_AMT"] * $rowAccount["INTEREST_RATE"] /36500);
			$arrAccount['SUM_BALANCE'] += $rowAccount["BALANCE_AMT"];
			$arrAccount['TYPE_ACCOUNT'] = $rowAccount["DEPTTYPE_DESC"];
			$arrDetail[] = $arrAccount;				
		}	
		$header["DATE_NOW"] = $lib->convertdate(date("Y-m-d"),'d/n/Y').' '. date("H:i");
		$header["START_DATE"] = $lib->convertdate($date_now,'d/n/Y');
		$sum_balance = number_format($arrAccount['SUM_BALANCE'],2);
		$sum_int_amt = number_format($arrAccount['SUM_INTEREST'],2);
		
		
		if(sizeof($arrDetail) > 0 ){
			$arrayPDF = GeneratePdfDoc($arrDetail,$header,$sum_balance,$sum_int_amt,$member_no);
			if($arrayPDF["RESULT"]){
				$arrayResult['REPORT_URL'] = $config["URL_SERVICE"].$arrayPDF["PATH"];
				$arrayResult['INVOICE'] = $arrDetail;
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$arrayResult['RESPONSE_CODE'] = "WS0044";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
		}else{
			//$arrayResult['INVOICE'] = FALSE;
			$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถออกรายงานได้ เนื่องจากสัญญาถูกปิดไปเเล้ว";
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

function GeneratePdfDoc($arrDetail,$header,$sum_balance,$sum_int_amt,$member_no){
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
	  line-height: 15pt;

	}
	.text-center{
	  text-align:center
	}
	.text-right{
	  text-align:right
	}
	.text-bold{
	  font-weight:bold;
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
	  line-height: 25px
	}
	th,td{
	  text-align:center;
	}
	th{
	  font-size:17pt;
	  border-top:solid 1px;
	  border-bottom:solid 1px;
	  padding-top:10px;
	  padding-bottom:10px;
	}
	td{
	  padding-bottom:5px;
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
	#pageno .page:after { content: counter(page, decimal); }

	</style>
	';

	//@page = ขนาดของกระดาษ 13.97 cm x  13.71 cm
	//ระยะขอบ
	$html .= '<div style=" margin: -10px -10px -30px -10px;">';


	$html.='<div>
		  <div style="position:absolute">
			<div style="display:flex; margin-top:-10px;">
			  <div style="margin-left:830px;">วันที่ : </div>
			  <div style="margin-left:898px;">'.($header["DATE_NOW"]??null).'</div>
			</div>
		  </div>
		  <div style="  line-height:25px;">
			<div class="text-center text-bold">ชุมนุมสหกรณ์ออมทรัพย์แห่งประเทศไทย จำกัด</div>
			<div class="text-center text-bold">รายงานเงินฝากดอกเบี้ยค้างจ่าย</div>
			<div class="text-center text-bold">ณ วันที่ '.($header["START_DATE"]??null).' </div>
		  </div>
		  
		  <div style="margin-top:20px;  ">
			<table>
			  <thead>
				<tr>
				  <th style="width:100px;">P/N Date</th> 
				  <th style="width:100px;">รหัสลูกค้า</th> 
				  <th style="width:100px;">S/R No.</th> 
				  <th style="width:100px;">เลขที่เงินฝาก</th> 
				  <th style="width:150px;">จำนวนเงิน</th> 
				  <th style="width:120px;">อัตรา ด/บ</th>
				  <th style="width:100px;">ระยะเวลา</th> 
				  <th >จ่ายดบ.สุดท้าย</th> 
				  <th style="width:130px;" >ดอกเบี้ยค้างจ่าย</p></th> 
				</tr>
			  </thead>
			  <tbody>
				  <tr>
				   <td colspan="9" style="text-align:left; padding-left:20px" class="text-bold">'.($header["DEPTACCOUNT_NAME"]??null).'</td>  
				  </tr>';
	  foreach($arrDetail AS $arrData)
	  $html.=     '<tr>
					 <td>'.($arrData["PRNC_DATE"]??null).'</td> 
					 <td>'.($arrData["MEMBER_NO"]??null).'</td> 
					 <td>'.($arrData["DEPTPASSBOOK_NO"]??null).'</td>
					 <td>'.($arrData["DEPTACCOUNT_NO"]??null).'</td>
					 <td style="text-align:right;">'.($arrData["BALANCE_AMT"]??null).'</td>  
					 <td>'.($arrData["INTEREST_RATE"]??null).'</td> 
					 <td>'.($arrData["COUNT_DATE"]??null).'</td> 
					 <td>'.($arrData["DUE_DATE"]??null).'</td> 
					 <td style="text-align:right; padding-right:7px;">'.($arrData["INT_AMT"]??null).'</td> 
				  </tr>';


	$html.='    
				<tr>
				<div style="border:1px solid; "></div>
					<td style="border-top:1px solid; "colspan="4">รวมทั้งสิ้น</td>  
					<td style="border-top:1px solid; text-align:right; " ><div style="border-bottom:1px solid;">'.$sum_balance.'</div></td> 
					<td style="border-top:1px solid; "></td>
					<td style="border-top:1px solid; "></td>
					<td style="border-top:1px solid; "</td>
					<td style="border-top:1px solid; text-align:right;" >'.$sum_int_amt.'<div  style="border-bottom:1px solid;"></div></td> 
					<td style="border-top:1px solid; "></td>
					<td style="border-top:1px solid; "></td>
				</tr>
				
			  </tbody>
			</table>

		  </div>
		  <div style="height:30px;"></div>
		  ';
	//ระยะขอบ
	$html .= '
	</div>';
	
	$dompdf = new DOMPDF();
	$dompdf->set_paper('A4','landscape');
	$dompdf->load_html($html);
	$dompdf->render();
	$pathfile = __DIR__.'/../../resource/pdf/payment';
	if(!file_exists($pathfile)){
		mkdir($pathfile, 0777, true);
	}
	$pathfile = $pathfile.'/deptint'.$member_no.'.pdf';
	$pathfile_show = '/resource/pdf/payment/deptint'.$member_no.'.pdf?v='.time();
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