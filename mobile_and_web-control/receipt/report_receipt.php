<?php
require_once('../autoload.php');

use Dompdf\Dompdf;

$dompdf = new DOMPDF();

if($lib->checkCompleteArgument(['menu_component','recv_period'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SlipInfo')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$header = array();
		$fetchName = $conoracle->prepare("SELECT mb.memb_name,mb.memb_surname,mp.prename_desc,mbg.MEMBGROUP_DESC,mbg.MEMBGROUP_CODE
												FROM mbmembmaster mb LEFT JOIN 
												mbucfprename mp ON mb.prename_code = mp.prename_code
												LEFT JOIN mbucfmembgroup mbg ON mb.MEMBGROUP_CODE = mbg.MEMBGROUP_CODE
												WHERE mb.member_no = :member_no");
		$fetchName->execute([
			':member_no' => $member_no
		]);
		$rowName = $fetchName->fetch(PDO::FETCH_ASSOC);
		$header["fullname"] = $rowName["PRENAME_DESC"].$rowName["MEMB_NAME"].' '.$rowName["MEMB_SURNAME"];
		$header["member_group"] = $rowName["MEMBGROUP_DESC"];
		$header["sumpay"] = 0;
		if($lib->checkCompleteArgument(['seq_no'],$dataComing)){
			$getPaymentDetail = $conoracle->prepare("SELECT 
																		CASE kut.keepitemtype_grp
																		WHEN 'LON' THEN NVL(lt.LOANTYPE_DESC,kut.keepitemtype_desc) 
																		WHEN 'DEP' THEN NVL(dp.DEPTTYPE_DESC,kut.keepitemtype_desc) 
																		ELSE kut.keepitemtype_desc
																		END as TYPE_DESC,
																		kut.keepitemtype_grp as TYPE_GROUP,
																		kpd.MONEY_RETURN_STATUS,
																		kpd.ADJUST_ITEMAMT,
																		kpd.ADJUST_PRNAMT,
																		kpd.ADJUST_INTAMT,
																		case kut.keepitemtype_grp 
																			WHEN 'DEP' THEN kpd.description
																			WHEN 'LON' THEN kpd.loancontract_no
																		ELSE kpd.description END as PAY_ACCOUNT,
																		kpd.period,
																		NVL(kpd.ITEM_PAYMENT * kut.SIGN_FLAG,0) AS ITEM_PAYMENT,
																		NVL(kpd.ITEM_BALANCE,0) AS ITEM_BALANCE,
																		NVL(kpd.principal_payment,0) AS PRN_BALANCE,
																		NVL(kpd.interest_payment,0) AS INT_BALANCE
																		FROM kpmastreceivedet kpd LEFT JOIN KPUCFKEEPITEMTYPE kut ON 
																		kpd.keepitemtype_code = kut.keepitemtype_code
																		LEFT JOIN lnloantype lt ON kpd.shrlontype_code = lt.loantype_code
																		LEFT JOIN dpdepttype dp ON kpd.shrlontype_code = dp.depttype_code
																		WHERE kpd.member_no = :member_no and kpd.recv_period = :recv_period
																		and kpd.seq_no = :seq_no
																		ORDER BY kut.SORT_IN_RECEIVE ASC");
			$getPaymentDetail->execute([
				':member_no' => $member_no,
				':recv_period' => $dataComing["recv_period"],
				':seq_no' => $dataComing["seq_no"]
			]);
		}else{
			$getPaymentDetail = $conoracle->prepare("SELECT 
																		CASE kut.keepitemtype_grp 
																		WHEN 'LON' THEN NVL(lt.LOANTYPE_DESC,kut.keepitemtype_desc) 
																		WHEN 'DEP' THEN NVL(dp.DEPTTYPE_DESC,kut.keepitemtype_desc) 
																		ELSE kut.keepitemtype_desc
																		END as TYPE_DESC,
																		kut.keepitemtype_grp as TYPE_GROUP,
																		kpd.MONEY_RETURN_STATUS,
																		kpd.ADJUST_ITEMAMT,
																		kpd.ADJUST_PRNAMT,
																		kpd.ADJUST_INTAMT,
																		kpd.SHRLONTYPE_CODE,
																		case kut.keepitemtype_grp 
																			WHEN 'DEP' THEN kpd.description
																			WHEN 'LON' THEN kpd.loancontract_no
																		ELSE kpd.description END as PAY_ACCOUNT,
																		kpd.period,
																		NVL(kpd.ITEM_PAYMENT * kut.SIGN_FLAG,0) AS ITEM_PAYMENT,
																		NVL(kpd.ITEM_BALANCE,0) AS ITEM_BALANCE,
																		NVL(kpd.principal_payment,0) AS PRN_BALANCE,
																		NVL(kpd.interest_payment,0) AS INT_BALANCE
																		FROM kpmastreceivedet kpd LEFT JOIN KPUCFKEEPITEMTYPE kut ON 
																		kpd.keepitemtype_code = kut.keepitemtype_code
																		LEFT JOIN lnloantype lt ON kpd.shrlontype_code = lt.loantype_code
																		LEFT JOIN dpdepttype dp ON kpd.shrlontype_code = dp.depttype_code
																		WHERE kpd.member_no = :member_no and kpd.recv_period = :recv_period
																		ORDER BY kut.SORT_IN_RECEIVE ASC");
			$getPaymentDetail->execute([
				':member_no' => $member_no,
				':recv_period' => $dataComing["recv_period"]
			]);
		}
		$arrGroupDetail = array();
		while($rowDetail = $getPaymentDetail->fetch(PDO::FETCH_ASSOC)){
			$arrDetail = array();
			$arrDetail["TYPE_DESC"] = $rowDetail["TYPE_DESC"];
			if($rowDetail["TYPE_GROUP"] == 'SHR'){
				$header["sharestk_period"] = number_format($rowDetail["ITEM_PAYMENT"],2);
				$arrDetail["PERIOD"] = $rowDetail["PERIOD"];
			}else if($rowDetail["TYPE_GROUP"] == 'LON'){
				$getGrpLoan = $conoracle->prepare("SELECT LOANGROUP_CODE FROM lnloantype WHERE loantype_code = :loantype_code");
				$getGrpLoan->execute([':loantype_code' => $rowDetail["SHRLONTYPE_CODE"]]);
				$rowGrpLoan = $getGrpLoan->fetch(PDO::FETCH_ASSOC);
				$header["loanbalance_".$rowGrpLoan["LOANGROUP_CODE"]] += $rowDetail["ITEM_BALANCE"];
				$arrDetail["PAY_ACCOUNT"] = $rowDetail["PAY_ACCOUNT"];
				$arrDetail["PAY_ACCOUNT_LABEL"] = 'เลขสัญญา';
				$arrDetail["PERIOD"] = $rowDetail["PERIOD"];
				if($rowDetail["MONEY_RETURN_STATUS"] == '-99' || $rowDetail["ADJUST_ITEMAMT"] > 0){
					$arrDetail["PRN_BALANCE"] = number_format($rowDetail["ADJUST_PRNAMT"],2);
					$arrDetail["INT_BALANCE"] = number_format($rowDetail["ADJUST_INTAMT"],2);
				}else{
					$arrDetail["PRN_BALANCE"] = number_format($rowDetail["PRN_BALANCE"],2);
					$arrDetail["INT_BALANCE"] = number_format($rowDetail["INT_BALANCE"],2);
				}
			}else if($rowDetail["TYPE_GROUP"] == 'DEP'){
				$arrDetail["PAY_ACCOUNT"] = $lib->formataccount($rowDetail["PAY_ACCOUNT"],$func->getConstant('dep_format'));
				$arrDetail["PAY_ACCOUNT_LABEL"] = 'เลขบัญชี';
			}else if($rowDetail["TYPE_GROUP"] == "OTH"){
				$arrDetail["PERIOD"] = $rowDetail["PERIOD"];
				$arrDetail["PAY_ACCOUNT"] = $rowDetail["PAY_ACCOUNT"];
				$arrDetail["PAY_ACCOUNT_LABEL"] = 'จ่าย';
			}
			if($rowDetail["MONEY_RETURN_STATUS"] == '-99' || $rowDetail["ADJUST_ITEMAMT"] > 0){
				$arrDetail["ITEM_PAYMENT"] = number_format($rowDetail["ADJUST_ITEMAMT"],2);
				$arrDetail["ITEM_PAYMENT_NOTFORMAT"] = $rowDetail["ADJUST_ITEMAMT"];
			}else{
				$arrDetail["ITEM_PAYMENT"] = number_format($rowDetail["ITEM_PAYMENT"],2);
				$arrDetail["ITEM_PAYMENT_NOTFORMAT"] = $rowDetail["ITEM_PAYMENT"];
			}
			$header["sumpay"] += $rowDetail["ITEM_PAYMENT"];
			$arrDetail["ITEM_BALANCE"] = number_format($rowDetail["ITEM_BALANCE"],2);
			$arrGroupDetail[] = $arrDetail;
		}
		$getDetailKPHeader = $conoracle->prepare("SELECT 
																kpd.RECEIPT_NO,
																kpd.OPERATE_DATE,
																kpd.SHARESTK_VALUE,
																kpd.KEEPING_STATUS,
																KL.LOANCREDIT_AMT,KL.CONCOLL_BLANCE
																FROM kpmastreceive kpd
																LEFT JOIN KPTEMPLOANCREDIT KL ON kpd.MEMBER_NO = KL.MEMBER_NO
																WHERE kpd.member_no = :member_no and kpd.recv_period = :recv_period");
		$getDetailKPHeader->execute([
			':member_no' => $member_no,
			':recv_period' => $dataComing["recv_period"]
		]);
		$rowKPHeader = $getDetailKPHeader->fetch(PDO::FETCH_ASSOC);
		$header["keeping_status"] = $rowKPHeader["KEEPING_STATUS"];
		$header["sharestk_value"] = number_format($rowKPHeader["SHARESTK_VALUE"],2);
		$header["recv_period"] = $lib->convertperiodkp(TRIM($dataComing["recv_period"]));
		$header["member_no"] = $payload["member_no"];
		$header["membgroup_code"] = $rowName["MEMBGROUP_CODE"];
		$header["loancredit_amt"] = number_format($rowKPHeader["LOANCREDIT_AMT"],2);
		$header["loancoll_amt"] = number_format($rowKPHeader["CONCOLL_BLANCE"],2);
		$header["buyshare_more"] = "";
		$header["receipt_no"] = TRIM($rowKPHeader["RECEIPT_NO"]);
		$header["operate_date"] = $lib->convertdate($rowKPHeader["OPERATE_DATE"],'D/n/Y');
		$arrUCollWho = array();
		$getUCollWho = $conoracle->prepare("SELECT
											PRE.PRENAME_DESC,MEMB.MEMB_NAME,MEMB.MEMB_SURNAME
											FROM
											LNCONTCOLL LCC LEFT JOIN LNCONTMASTER LCM ON  LCC.LOANCONTRACT_NO = LCM.LOANCONTRACT_NO
											LEFT JOIN MBMEMBMASTER MEMB ON LCM.MEMBER_NO = MEMB.MEMBER_NO
											LEFT JOIN MBUCFPRENAME PRE ON MEMB.PRENAME_CODE = PRE.PRENAME_CODE
											WHERE
											LCM.CONTRACT_STATUS > 0 AND LCM.CONTRACT_STATUS <> 8
											AND LCC.LOANCOLLTYPE_CODE = '01'
											AND LCC.REF_COLLNO = :member_no");
		$getUCollWho->execute([':member_no' => $member_no]);
		while($rowUCollWho = $getUCollWho->fetch(PDO::FETCH_ASSOC)){
			$arrUCollWho[] = $rowUCollWho["PRENAME_DESC"].$rowUCollWho["MEMB_NAME"].' '.$rowUCollWho["MEMB_SURNAME"];
		}
		
		//$getText = $conoracle->prepare("SELECT TEXT1,TEXT2,TEXT3,TEXT4 FROM KPTEMPTEXT");
		$getText = $conoracle->prepare("SELECT TEXT1,TEXT2,TEXT3,TEXT4,
									nvl((select s. membshort_desc from mbmembmaster m ,mbucfmembgroupshort s 
									where nvl(subgroup_code,'00') = s. membgroup_code and m.member_no = :member_no and rownum = 1),'') as membshort_desc
									FROM KPTEMPTEXT");
		$getText->execute([
			':member_no' => $member_no
		]);
		
		$rowgetText= $getText->fetch(PDO::FETCH_ASSOC);
		
		$text = $rowgetText["TEXT1"];
		$text3 = $rowgetText["TEXT3"];
		$membshort_desc = $rowgetText["MEMBSHORT_DESC"];
		$header["guarantee"] = $arrUCollWho;
		$arrayPDF = GenerateReport($arrGroupDetail,$header,$lib,$text,$text3,$membshort_desc);
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

function GenerateReport($dataReport,$header,$lib,$text,$text3,$membshort_desc){
	$sumBalance = 0;
	
	
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
  font-size:10pt;
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
 
  line-height: 10pt
}
th,td{
  border:0.25px solid #7e9ded ;
  text-align:center;
}
th{
  padding:15px 7px; 
}
td{
  padding:2px 5px;
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
.bg-color{
    background-color:#f2fbff;
}

@page { size: 21.01cm 17.09cm; }
</style>
';


//@page = ขนาดของกระดาษ 13.97 cm x  13.71 cm
//ระยะขอบ
$html .= '<div style=" margin: -30px -30px -50px -30px;   ">';
$html .= '
<div style="width:60%;">
  <div style="display:flex; height:125px; "  class="nowrap">
      <div style="positionabsolute; margin-left:0px; ">
        <img src="../../resource/logo/logo.jpg" style="width:100px" />
      </div>
      <div style="margin-left:110px; padding-top:10px;">
        <div style="font-size:14pt; font-weight:bold; color:#7e9ded;">สหกรณ์ออมทรัพย์กรมชลประทาน จำกัด</div>
        <div style="display:flex">
          <div style="font-size:14pt; font-weight:bold; color:#7e9ded; padding-top:30px;">ใบเสร็จรับเงิน</div>
          <div style="margin-left:87px;  width:260px; padding-top:10px;" >
              <table style="width:260px;">
                  <tr>
                    <th class="bg-color">เลขที่</th>
                    <th class="bg-color">สมาชิกเลขที่</th>
                    <th class="bg-color"><p>วันออกใบเสร็จรับ</p><p>เงิน</p></th>
                  </tr>
                  <tr>
                    <td>'.($header["receipt_no"]??null).'</td>
                    <td>'.($header["membgroup_code"].' - '.$header["member_no"]??null).'</td>
                    <td>'.($header["operate_date"]??null).'</td>
                  </tr>
                  <tr>
                    <td style="border:none;"></td>
                    <td class="bg-color">วงเงินกู้ไม่เกิน</td>
                    <td class="bg-color">สิทธิ์ค้ำคงเหลือ</td>
                  </tr>
                  <tr>
                    <td style="border:none;"></td>
                    <td>'.($header["loancredit_amt"]??null).'</td>
                    <td>'.($header["loancoll_amt"]??null).'</td>
                  </tr>
              </table>
          </div>
        </div>
      </div>
    </div>
    <div style="display:flex; height:10px; margin-top:10px;">
        <div>สังกัด</div>
        <div style="margin-left:110px;">'.($header["member_group"]??null).'</div>
    </div>
    <div style="display:flex; height:10px;">
      <div>ได้รับเงินจาก</div>
      <div style="margin-left:110px;">'.($header["fullname"]??null).'</div>
    </div>
    <div style="margin-top:30px;  height:150px;">
        <table style="width:100%">
            <tr>
               <td style="width:150px;" class="bg-color">รายการ</td>
               <td style="width:40px;" class="bg-color">งวดที่</td>
               <td class="bg-color">เงินต้น</td>
               <td class="bg-color">ดอกเบี้ย</td>
               <td class="bg-color">จำนวนเงิน</td>
            </tr>';

foreach($dataReport AS $arrReport){
  $html.='
    <tr>
        <td style="text-align:left;">'.$arrReport["TYPE_DESC"]." ".$arrReport["PAY_ACCOUNT"] .'</td>
        <td>'.$arrReport["PERIOD"].'</td>
        <td class="text-right">'.$arrReport["PRN_BALANCE"].'</td>
        <td class="text-right">'.$arrReport["INT_BALANCE"].'</td>
        <td class="text-right">'.$arrReport["ITEM_PAYMENT"].'</td>
    </tr>
  ';

}

 $html.='           
        </table>
	  <div style="display:flex; height:20px;">  
        <div style=" font-weight:bold;">'.$lib->baht_text($header["sumpay"]).'</div>
        <div style=" font-weight:bold; margin-left:260px;">รวมเงิน</div>
        <div style=" font-weight:bold; margin-left:310px;">'.number_format($header["sumpay"],2).'</div>
      </div>
    </div>
    <div style="margin-top:10px;">
      <div style="font-size:14pt; margin-top:30px;" class="text-center">
            *ใบเสร็จรับเงินฉบับนี้จะสมบูรณ์ต่อเมื่อหักเงินได้แล้ว*
      </div>
      <div style="margin-top:10px;">
        <table style="width:100%">
          <tr>
            <td class="bg-color">หุ้นรายเดือน</td>
            <td class="bg-color">หุ้นซื้อเพิ่ม</td>
            <td class="bg-color">ทุนเรือนหุ้นรวม</td>
          </tr>
          <tr>
            <td>'.($header["sharestk_period"]??null).'</td>
            <td> </td>
            <td>'.($header["sharestk_value"]??null).'</td>
          </tr>
          <tr>
            <td class="bg-color">หุ้นสามัญคงเหลือ</td>
            <td class="bg-color">หนี้ฉุกเฉินคงเหลือ</td>
            <td class="bg-color">หนี้พิเศษคงเหลือ</td>
          </tr>
          <tr>
            <td>'.(number_format($header["loanbalance_02"],2) ?? null).'</td>
            <td>'.(number_format($header["loanbalance_01"],2) ?? null).'</td>
            <td>'.(number_format($header["loanbalance_03"],2) ?? null).'</td>
          </tr>
        </table>
      </div>
      <div style="margin-top:30px;">
      <div style="position:absolute; top:-10px; margin-left:180px;">
            <div style="display:flex; height:40px;">
                <div>ลงชื่อ</div> 
                <div><img src="../../resource/utility_icon/signature/sign_ผจก.png" width="80" height="30" style=" margin-left:70px;"></div>
                <div style="margin-left:210px;">ผู้จัดการ</div>
            </div>
            <div style="display:flex">
                <div>ลงชื่อ</div> 
                <div><img src="../../resource/utility_icon/signature/sign_รับเงิน.png" width="80" height="30" style="margin-top:-5px; margin-left:70px; "></div>
                <div style="margin-left:200px;">แทนเจ้าหน้าที่รับเงิน</div>
            </div>
      </div>
        <table>
            <tr>
              <td style="width:150px;">เป็นผู้ค้ำประกันให้แก่</td>
            </tr>
            
			';
			foreach($header["guarantee"] as $key => $value){
				$html .= '
				<tr>
				<td style="width:150px;">('.($key+1).')'.$value.'</td>
				</tr>';
			}
	$html .= '
        </table>
      </div>
    </div>

  </div>
  <div style=" position:fixed; top :-30px; left:440px;">
        <div style="border:1px solid #7e9ded; margin-lef:20px; width:280px;  height:430px;  padding:10px 5px;  line-height: 10px; margin-top:30px;" class="text-center">
            <div style="color:#7e9ded; font-weight:bold">สหกรณ์ออมทรัพย์กรมชลประทาน จำกัด</div>
            <div style="color:#066022; font-size:10pt;"> ROYAL IRRIGAION DEPART SANTNG AND CREDIT COOPEATIVE LTD</div>
            <div style="font-size:10pt">811 อาคารสวัสดิการ กรมชลประทานสามแสน ถนนสามแสน</div>
            <div style="font-size:10pt">แขวงถนนนครไซยศรี เขตดุสิต กรุงเทพ 10300</div>
            <div style="font-size:10pt">โทร. 0-2669-6595 โทรสาร 0-2669-6575 http://www.ridsaving.com</div>
            <div style="font-size:10pt; margin-top:10px; text-align:left;">'.($text ?? null).'</div>
            <div  style="font-size:10pt; margin-top:30px; text-align:center; font-weight:bold">'.($text3 ?? '-').'</div>
            <div style="font-size:10pt; margin-top:10px; text-align:center;">'.($membshort_desc ?? '-').'</div>
        </div>
  </div>';

//ระยะขอบ
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
	$pathfile = __DIR__.'/../../resource/pdf/keeping_monthly';
	if(!file_exists($pathfile)){
		mkdir($pathfile, 0777, true);
	}
	$pathfile = $pathfile.'/'.$header["member_no"].$header["receipt_no"].'.pdf';
	$pathfile_show = '/resource/pdf/keeping_monthly/'.$header["member_no"].$header["receipt_no"].'.pdf?v='.time();
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