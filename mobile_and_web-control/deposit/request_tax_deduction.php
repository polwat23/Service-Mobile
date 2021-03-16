<?php
require_once('../autoload.php');

use Dompdf\Dompdf;

$dompdf = new DOMPDF();

if($lib->checkCompleteArgument(['menu_component','account_no'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DepositStatement')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$fetchMail = $conmysql->prepare("SELECT email FROM gcmemberaccount WHERE member_no = :member_no");
		$fetchMail->execute([':member_no' => $payload["member_no"]]);
		$rowMail = $fetchMail->fetch(PDO::FETCH_ASSOC);
		$arrayAttach = array();
		$account_no = preg_replace('/-/','',$dataComing["account_no"]);
		$getMemberData = $conoracle->prepare("SELECT	MBMEMBMASTER.MEMBER_NO,   MBUCFPRENAME.PRENAME_DESC, MBMEMBMASTER.MEMB_NAME, MBMEMBMASTER.MEMB_SURNAME, DPDEPTMASTER.DEPTACCOUNT_NAME,
																	MBMEMBMASTER.CARD_PERSON,
																	MBMEMBMASTER.ADDR_NO AS CURRADDR_NO,   
																	MBMEMBMASTER.ADDR_MOO AS CURRADDR_MOO,   
																	MBMEMBMASTER.ADDR_SOI AS CURRADDR_SOI,   
																	MBMEMBMASTER.ADDR_VILLAGE AS CURRADDR_VILLAGE,   
																	MBMEMBMASTER.ADDR_ROAD AS CURRADDR_ROAD,   
																	MBMEMBMASTER.TAMBOL_CODE AS CURRTAMBOL_CODE,   
																	MBMEMBMASTER.AMPHUR_CODE AS CURRAMPHUR_CODE,   
																	MBMEMBMASTER.PROVINCE_CODE AS CURRPROVINCE_CODE,   
																	MBMEMBMASTER.ADDR_POSTCODE AS CURRADDR_POSTCODE,   
																	MBMEMBMASTER.CURRADDR_PHONE,   
																	MBUCFTAMBOL_B.TAMBOL_DESC as currtambol_desc,   
																	MBUCFDISTRICT_B.DISTRICT_DESC as curramphur_desc,   
																	MBUCFPROVINCE_B.PROVINCE_DESC as currprovince_desc,
																	b.MEMBGROUP_CODE,
																	b.MEMBGROUP_DESC,
																	cmcoopconstant.coop_name as as_payname, 
																	'เลขที่ ' || replace( replace( cmcoopconstant.coop_addr,'/', ' ' ), 'รพ.ศิริราช', '') || 'ชั้น G  และ ชั้น 6  ' || ' ถ.'|| cmcoopconstant.coop_road || ' แขวง' || cmcoopconstant.tambol || ' เขต' || cmcoopconstant.district_desc || ' ' || cmcoopconstant.province_desc || ' ' || cmcoopconstant.postcode as as_payaddr,
																	cmcoopconstant.coop_taxid as as_paytaxid,
																	dpinttax.INT_AMT as adc_payamt,
																	dpinttax.TAX_AMT as adc_paytax
														FROM		MBMEMBMASTER,   
																	MBUCFDISTRICT MBUCFDISTRICT_A,   
																	MBUCFPROVINCE MBUCFPROVINCE_A,   
																	MBUCFPRENAME,   
																	MBUCFTAMBOL MBUCFTAMBOL_A,   
																	MBUCFTAMBOL MBUCFTAMBOL_B,   
																	MBUCFDISTRICT MBUCFDISTRICT_B,   
																	MBUCFPROVINCE MBUCFPROVINCE_B,
																	mbucfmembgroup a,
																	mbucfmembgroup b,
																	DPDEPTMASTER,  
																	cmcoopconstant,(
																		select 
																		sum(INT_AMT) as INT_AMT, sum(TAX_AMT) as TAX_AMT, max(DEPTACCOUNT_NO) as DEPTACCOUNT_NO
																		,MEMBER_NO
																		from
																		(
																		  SELECT 
																					sum(DPINTTAXSTATEMENT.INT_AMT - NVL(DPINTTAXSTATEMENT.INT_RETURN,0)) as INT_AMT,   
																					sum(DPINTTAXSTATEMENT.TAX_AMT -  NVL(DPINTTAXSTATEMENT.TAX_RETURN,0)) as TAX_AMT,   
																				MAX(DPINTTAXSTATEMENT.DEPTACCOUNT_NO) AS DEPTACCOUNT_NO,
																					MBMEMBMASTER.MEMBER_NO
																			 FROM DPINTTAXSTATEMENT,   
																					DPDEPTMASTER,   
																					DPDEPTTYPE,   
																					MBMEMBMASTER,   
																					MBUCFPRENAME,   
																					MBUCFPROVINCE,   
																					MBUCFTAMBOL,   
																					MBUCFDISTRICT,   
																					CMCOOPCONSTANT  
																			WHERE ( mbmembmaster.province_code = mbucfprovince.province_code (+)) and  
																					( mbmembmaster.tambol_code = mbucftambol.tambol_code (+)) and  
																					( mbmembmaster.amphur_code = mbucftambol.district_code (+)) and  
																					( mbmembmaster.amphur_code = mbucfdistrict.district_code (+)) and  
																					( mbmembmaster.province_code = mbucfdistrict.province_code (+)) and  
																					( mbmembmaster.prename_code = mbucfprename.prename_code (+)) and  
																					( DPDEPTMASTER.DEPTACCOUNT_NO = DPINTTAXSTATEMENT.DEPTACCOUNT_NO ) and  
																					( DPDEPTTYPE.DEPTTYPE_CODE = DPDEPTMASTER.DEPTTYPE_CODE ) and  
																					( DPINTTAXSTATEMENT.COOP_ID = DPDEPTMASTER.COOP_ID ) and  
																					( DPDEPTMASTER.COOP_ID = DPDEPTTYPE.COOP_ID ) and  
																					( DPDEPTMASTER.COOP_ID = MBMEMBMASTER.COOP_ID ) and  
																					( DPDEPTMASTER.MEMBER_NO = MBMEMBMASTER.MEMBER_NO ) and  
																					( DPDEPTMASTER.COOP_ID = CMCOOPCONSTANT.COOP_CONTROL ) and  
																					(  to_char(DPINTTAXSTATEMENT.PRNC_DUEDATE,'yyyy') =  (to_char(sysdate, 'YYYY') - 1) AND
																					( DPINTTAXSTATEMENT.tax_amt > 0 OR  
																					DPINTTAXSTATEMENT.tax_return > 0 ) ) and MBMEMBMASTER.MEMBER_NO = :member_no
																		group by 
																					MBMEMBMASTER.MEMBER_NO
																		)
																		group by MEMBER_NO
															) dpinttax
														WHERE	( mbmembmaster.amphur_code = mbucfdistrict_a.district_code (+)) and  
																	( mbmembmaster.province_code = mbucfprovince_a.province_code (+)) and  
																	( mbmembmaster.province_code = mbucfdistrict_a.province_code (+)) and  
																	( mbmembmaster.tambol_code = mbucftambol_a.tambol_code (+)) and  
																	( mbmembmaster.tambol_code = mbucftambol_b.tambol_code (+)) and  
																	( mbmembmaster.amphur_code = mbucfdistrict_b.district_code (+)) and  
																	( mbmembmaster.province_code = mbucfprovince_b.province_code (+)) and  
																	( mbmembmaster.prename_code = MBUCFPRENAME.prename_code (+)) and  
																	( cmcoopconstant.coop_control = mbmembmaster.coop_id ) AND
																	( dpinttax.DEPTACCOUNT_NO = DPDEPTMASTER.DEPTACCOUNT_NO ) and  
																	( DPDEPTMASTER.MEMBER_NO = MBMEMBMASTER.MEMBER_NO ) and  
																	( DPDEPTMASTER.COOP_ID = MBMEMBMASTER.COOP_ID ) and  
																	( MBMEMBMASTER.MEMBGROUP_CODE = a.MEMBGROUP_CODE ) and
																	( MBMEMBMASTER.COOP_ID = a.COOP_ID ) and
																	( a.MEMBGROUP_CONTROL = b.MEMBGROUP_CODE ) and
																	( a.COOP_ID = b.COOP_ID )");
		$getMemberData->execute([':member_no' => $member_no]);
		$rowMemberData = $getMemberData->fetch(PDO::FETCH_ASSOC);
		$passwordPDF = filter_var($rowMemberData["CARD_PERSON"], FILTER_SANITIZE_NUMBER_INT);
		
		
		$address = (isset($rowMemberData["CURRADDR_NO"]) ? $rowMemberData["CURRADDR_NO"] : null);
		if(isset($rowMemberData["CURRPROVINCE_CODE"]) && $rowMemberData["CURRPROVINCE_CODE"] == '10'){
			$address .= (isset($rowMemberData["CURRADDR_MOO"]) ? ' ม.'.$rowMemberData["CURRADDR_MOO"] : null);
			$address .= (isset($rowMemberData["CURRADDR_VILLAGE"]) ? ' หมู่บ้าน'.$rowMemberData["CURRADDR_VILLAGE"] : null);
			$address .= (isset($rowMemberData["CURRADDR_SOI"]) ? ' ซอย'.$rowMemberData["CURRADDR_SOI"] : null);
			$address .= (isset($rowMemberData["CURRADDR_ROAD"]) ? ' ถ'.$rowMemberData["CURRADDR_ROAD"] : null);
			$address .= (isset($rowMemberData["CURRTAMBOL_DESC"]) ? ' แขวง'.$rowMemberData["CURRTAMBOL_DESC"] : null);
			$address .= (isset($rowMemberData["CURRAMPHUR_DESC"]) ? ' เขต'.$rowMemberData["CURRAMPHUR_DESC"] : null);
			$address .= (isset($rowMemberData["CURRPROVINCE_DESC"]) ? ' '.$rowMemberData["CURRPROVINCE_DESC"] : null);
			$address .= (isset($rowMemberData["CURRADDR_POSTCODE"]) ? ' '.$rowMemberData["CURRADDR_POSTCODE"] : null);
		}else{
			$address .= (isset($rowMemberData["CURRADDR_MOO"]) ? ' ม.'.$rowMemberData["CURRADDR_MOO"] : null);
			$address .= (isset($rowMemberData["CURRADDR_VILLAGE"]) ? ' หมู่บ้าน'.$rowMemberData["CURRADDR_VILLAGE"] : null);
			$address .= (isset($rowMemberData["CURRADDR_SOI"]) ? ' ซอย'.$rowMemberData["CURRADDR_SOI"] : null);
			$address .= (isset($rowMemberData["CURRADDR_ROAD"]) ? ' ถ'.$rowMemberData["CURRADDR_ROAD"] : null);
			$address .= (isset($rowMemberData["CURRTAMBOL_DESC"]) ? ' ต.'.$rowMemberData["CURRTAMBOL_DESC"] : null);
			$address .= (isset($rowMemberData["CURRAMPHUR_DESC"]) ? ' อ.'.$rowMemberData["CURRAMPHUR_DESC"] : null);
			$address .= (isset($rowMemberData["CURRPROVINCE_DESC"]) ? ' จ.'.$rowMemberData["CURRPROVINCE_DESC"] : null);
			$address .= (isset($rowMemberData["CURRADDR_POSTCODE"]) ? ' '.$rowMemberData["CURRADDR_POSTCODE"] : null);
		}
		$arrayData["MEMBER_NO"] = $member_no;
		$arrayData["AS_PAYNAME"] = $rowMemberData["AS_PAYNAME"];
		$arrayData["AS_PAYADDR"] = $rowMemberData["AS_PAYADDR"];
		$arrayData["AS_PAYTAXID"] = $rowMemberData["AS_PAYTAXID"];
		$arrayData["ADC_PAYAMT"] = $rowMemberData["ADC_PAYAMT"];
		$arrayData["ADC_PAYTAX"] = $rowMemberData["ADC_PAYTAX"];
		$arrayData["CARD_PERSON"] = $rowMemberData["CARD_PERSON"];
		$arrayData["FULLNAME"] = $rowMemberData["PRENAME_DESC"].$rowMemberData["MEMB_NAME"]." ".$rowMemberData["MEMB_SURNAME"];
		$arrayData["DEPTACCOUNT_NO"] = $lib->formataccount_hidden($account_no,$func->getConstant('hidden_dep'));
		$arrayData["FULL_ADDRESS_CURR"] = $address;
		if(isset($rowMemberData["CARD_PERSON"]) && $rowMemberData["CARD_PERSON"]  != ""){
			$arrayGenPDF = generatePDFSTM($dompdf,$arrayData,$lib,$passwordPDF);
			if($arrayGenPDF["RESULT"]){
				$arrayAttach[] = $arrayGenPDF["PATH"];
			}
			$arrayDataTemplate = array();
			$arrayDataTemplate["ACCOUNT_NO"] = $lib->formataccount_hidden($account_no,$func->getConstant('hidden_dep'));
			$template = $func->getTemplateSystem('DepositTax');
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
					":error_desc" => "ส่งเมลไม่ได้ ".$rowMail["email"]."\n"."Error => ".$arrMailStatus["MESSAGE_ERROR"],
					":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$arrayResult['RESPONSE_CODE'] = "WS0019";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
				
			}
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

function generatePDFSTM($dompdf,$arrayData,$lib,$password){
	$dompdf = new DOMPDF();
	//style table
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
	@font-face {
		font-family: TH Niramit AS;
		src: url(../../resource/fonts/TH Niramit AS Italic.ttf);
		font-weight: italic;
	}
	* {
	  font-family: TH Niramit AS;
	}
	body {
	  padding: 0;
	  font-size:15pt
	  line-height: 13px;
	}
	p{
		margin:0
	}
	.text-detail{
	  margin:-3px 0px 0px 0px;
	}
	.text-head{
	  font-size:15pt;
	  font-weight:bold;
	}
	.text-discipt{
	  font-size:8pt;
	  margin-top:-6px;
	  
	}
	.text-discipt-indent{
	  text-indent:70px;
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

	.checkbox{
	  margin-top:7px;
	}
	table{
	  border-collapse: collapse;
	}

	.border-dotted{
		height: -10px;
		border-right:1px solid;
		
	}
	td{
	  border-right:1px solid;
	  font-size:15pt
	  height:20px;
	  line-height: 13px;
	  white-space: normal;
	  padding: 5px 5px 0px 20px;
	}
	border-right-none{
	  border-right:none;
	}
	th{
	  line-height:13px;
	  border-top:none;
	}
	.border-bottom-solid{
	  border-bottom:1px solid;
	}
	.border-right-solid{
	  border-right:1px solid;
	}
	.text-indent{
	  text-indent:13px;
	}

	.padding-right{
	  padding-right:22px;
	}

	.checkbox-margin-left{
	  margin-left:40px;
	}

	.nowrap{
	  white-space: nowrap;
	}
	</style>
	';



	$html .= '  <div style="height:1020px; margin:-20px; -20px -20px -20px ">';

	$html .= '
		<div class="text-detail">
		  <b> ฉบับที่ 1</b> (สําหรับผู้ถูกหักภาษี ณ ที่จ่าย ใช้แนบพร้อมกับแบบแสดงรายการภาษี) 
		</div>
		<div class="text-detail">
			<b>ฉบับที่ 2</b> (สําหรับผู้ถูกหักภาษี ณ ที่จ่าย เก็บไว้เป็นหลักฐาน)
		</div>
		<div class="text-head" style="text-align:center;">
			หนังสือรับรองการหักภาษี ณ ที่จ่าย
		</div>
		<div class="text-detail" style="text-align:center; margin-top:-5px;">
			ตามมาตรา 50 ทวิ แห่งประมวลรัษฎากร
		</div>
	';

	$html .= '
	 <div  style="position: absolute; right: -18px; top: 30px;" >
		เล่มที่........................... 
	 </div>
	 <div style="position: absolute; right: -18px; top: 52px;">
		เลขที่........................... 
	 </div>
	';


	$html .= '
	<div style="border:1px solid; width:100%;  border-radius:2px; padding:5px; position:absolute; top:97px; ">
	';
	$html .= '
		  <div class="text-detail font-bold">
			ผู้มีหน้าที่หักภาษี ณ ที่จ่าย :-
		  </div>
		  <div class="font-bold" style="position: absolute; right:3px; top:0px;">
			  เลขประจําตัวผู้เสียภาษีอากร (13) หลัก*.................................................................................................
		  </div>
		  <div class="text-detail font-bold" style="margin-top:5px; ">
			  ชื่อ...................................................................................................................เลขประจําตัวผู้เสียภาษีอากร............................................................................
		  </div>
		  <div class="text-discipt text-discipt-indent" style="width:410px;">
			(ให้ระบุว่าเป็น บุคคล นิติบุคคล บริษัท สมาคม หรือคณะบุคคล)
		  </div>

		  <div class="text-detail font-bold" style="margin-top: 2px;">
			ที่อยู่.......................................................................................................................................................................................................................................
		  </div>
		  <div class="text-discipt text-discipt-indent" style="pedding-top:1px;">
			(ให้ระบุ ชื่ออาคาร/หมู่บ้าน ห้องเลขที่ ชั้นที่ เลขที่ ตรอก/ซอย หมู่ที่ ถนน ตําบล/แขวง อําเภอ/เขต จังหวัด)
		  </div>
	  ';

	// ข้อมูล ผู้มีหน้าที่หักภาษี ณ ทีจ่าย -> เลขประจําตัวผู้เสียภาษีอากร | ชื่อ | เลขประจําตัวผู้เสียภาษีอากร | ที่อยู่ 
	$html .= '
		  <div style="position:absolute;   left:33px; top:25px; width:235px;" class="font-bold">
			 '.$arrayData["AS_PAYNAME"].'
		  </div>
		  <div style="position:absolute;   right:5px; top:25px; width:195px; " class="font-bold">
			  '.$arrayData["AS_PAYTAXID"].'
		  </div>
		  <div style="position:absolute;   left:33px; top:56px; width:100%;" class="font-bold">
			  '.$arrayData["AS_PAYADDR"].'
		  </div>
	  ';

	$html .= '
	</div>
	';

	$html .= '
	<div style="border:1px solid; width:100%;  border-radius:2px; padding:5px; position:absolute; top:192px;">';


	$html .= '
		<div class="text-detail font-bold ">
		  ผู้ถูกหักภาษี ณ ที่จ่าย :-
		</div>
		<div style="position: absolute; right:3px; top:0px;" class="font-bold">
		  เลขประจําตัวผู้เสียภาษีอากร (13) หลัก*................................................................................................
		</div>
		<div class="text-detail font-bold" style="margin-top:5px;">
		  ชื่อ...................................................................................................................เลขประจําตัวผู้เสียภาษีอากร............................................................................
		</div>
		<div class="text-discipt text-discipt-indent">
		  (ให้ระบุว่าเป็น บุคคล นิติบุคคล บริษัท สมาคม หรือคณะบุคคล)
		</div>

		<div class="text-detail font-bold font-bold">
		ที่อยู่.......................................................................................................................................................................................................................................
		</div>
		<div class="text-discipt text-discipt-indent">
		(ให้ระบุ ชื่ออาคาร/หมู่บ้าน ห้องเลขที่ ชั้นที่ เลขที่ ตรอก/ซอย หมู่ที่ ถนน ตําบล/แขวง อําเภอ/เขต จังหวัด)
		</div>
		<hr style="border:0.5px solid;">
		<div style="height:25px;">
		  <div style="margin-top:-9px" class="font-bold">
			 ลำดับที่ 
		  </div>
		  <div style="position:absolute; top:90px; left:210px;" class="font-bold">
			  ในแบบ
		  </div>
		</div>
		<div style="margin-top:10px; position:absolute; top:110px; left:5px;" class="text-discipt">
		  (ให้สามารถอ้างอิงหรือสอบยันกันได้ระหว่างลําดับที่ตามหนังสือรับรองฯ กับแบบยื่นรายการหักภาษีหักที่จ่าย)
		</div>
		<div style="border:1px solid black;width:120px;height:16px;position:absolute;top:92px;left: 50px;"></div>
		';

	// ข้อมูล ผู้ถูกหักภาษี ณ ทีจ่าย -> เลขประจําตัวผู้เสียภาษีอากร | ชื่อ | เลขประจําตัวผู้เสียภาษีอากร | ที่อยู่ 
	$html .= '
		  <div style="position:absolute;   right:10px; top:-2px; width:235px;" class="font-bold">
			  '.$arrayData["CARD_PERSON"].'
		  </div>
		  <div style="position:absolute;   left:33px; top:25px; width:235px;" class="font-bold">
			  '.$arrayData["FULLNAME"].' ('.$arrayData["MEMBER_NO"].')
		  </div>
		  <div style="position:absolute;   right:5px; top:25px; width:195px; " class="font-bold">
			  '.$arrayData["CARD_PERSON"].'
		  </div>
		  <div style="position:absolute;   left:33px; top:51px; width:100%;" class="font-bold">
			  '.$arrayData["FULL_ADDRESS_CURR"].'
		  </div>
	  ';


	$html .= '
	</div>
	';
	//ใบแนบ
	$html .= '
	<div style="position:absolute; top:252px; right:0;">
		<input type="checkbox" class="checkbox" >  (1) ภ.ง.ด.1ก  
		<input type="checkbox" class="checkbox">   (2) ภ.ง.ด.1ก พิเศษ  
		<input type="checkbox" class="checkbox">   (3) ภ.ง.ด.2 
		<input type="checkbox" class="checkbox">  (4) ภ.ง.ด.3
	</div>
	<div style="position:absolute; top:270px; right:85px;">
	  <input type="checkbox" class="checkbox" checked > (5) ภ.ง.ด.2ก
	  <input type="checkbox" class="checkbox">   (6) ภ.ง.ด.3ก 
	  <input type="checkbox" class="checkbox">   (7) ภ.ง.ด.53 
	</div>
	';
	//ตารางการจ่าย
	$html .= '
	<div style="position:absolute; top:305px; left:-20px; border-left:1px solid; border-right:1px solid; border-top:1px solid;  solid; border-radius:2px 2px 0px 0px; margin-right:20px; ">
	   <table >
		  <thead>
			<tr>
				<th class="text-center border-bottom-solid border-right-solid" >ประเภทเงินได้พึงประเมินที่จ่าย</th> 
				<th class="text-center border-bottom-solid border-right-solid" style="width:90px;">วัน เดือน  หรือปีภาษี ที่จ่าย</th> 
				<th class="text-center border-bottom-solid border-right-solid" style="width:110px;" >จํานวนเงินที่จ่าย</th> 
				<th class="text-center border-bottom-solid style="width:90px;" ><div>ภาษีที่พัก</div> <div>และนําส่งไว้</div></th>
			</tr>
		  </thead>
		  <tbody>
			<tr>
				<td>
					1. เงินเดือน ค่าจ้าง เบี้ยเลี้ยง โบนัส ฯลฯ ตามมาตรา 40 (1) 
				</td>
				<div style="position:absolute;top:35px;left:380px;">.....................................................................................................................................</div>
				<td class="border-dotted" font-bold></td>
				<td class="border-dotted text-right padding-right font-bold"></td>
				<td class="border-dotted text-right padding-right font-bold" style="border-right:none;"></td>
			</tr>
			<tr>
				<td>
				  2. ค่าธรรมเนียม ค่านายหน้า ฯลฯ ตามมาตรา 40 (2) 
				</td>
				<div style="position:absolute;top:55px;left:380px;">.....................................................................................................................................</div>
				<td class="border-dotted" font-bold></td>
				<td class="border-dotted text-right padding-right font-bold"></td>
				<td class="border-dotted text-right padding-right font-bold" style="border-right:none;"></td>
		  </tr>
		  <tr>
				<td>
				  3. ค่าแห่งลิขสิทธิ์ ฯลฯ ตามมาตรา 40 (3) 
				</td>
				<div style="position:absolute;top:75px;left:380px;">.....................................................................................................................................</div>
				<td class="border-dotted text-center font-bold"></td>
				<td class="border-dotted text-right padding-right font-bold" ></td>
				<td class="border-dotted text-right padding-right font-bold" style="border-right:none;"></td>
		  </tr>
		  <tr>
				<td>
				  4. (ก) ดอกเบี้ย ฯลฯ ตามมาตรา 40 (4) (ก) 
				</td>
				<div style="position:absolute;top:95px;left:380px;">.....................................................................................................................................</div>
				<td class="border-dotted text-center font-bold" style="padding-right: 20px;">'.(date('Y') + 542).'</td>
				<td class="border-dotted text-right padding-right font-bold" style="padding-right: 25px;max-width:100px;min-width:100px;width:100px;">'.number_format($arrayData["ADC_PAYAMT"],2).'</td>
				<td class="border-dotted text-right padding-right font-bold" style="border-right:none;padding-right: 25px;max-width:80px;min-width:80px;width:80px">'.number_format($arrayData["ADC_PAYTAX"],2).'</td>
		  </tr>
				<tr>
				<td class="text-indent">
				  (ข) เงินปันผล เงินส่วนแบ่งกําไร ฯลฯ ตามมาตรา 40 (4) (ข)
				</td>
				<td class="text-center font-bold"></td>
				<td class="text-right padding-right font-bold"></td>
				<td class="text-right padding-right font-bold" style="border-right:none;"></td>
		  </tr>
		   <tr>
				<td style="padding-left:30px; text-indent:15px">
				  (1) กรณีผู้ได้รับเงินปันผลได้รับเครดิตภาษี โดยจ่ายจาก
				<div style="text-indent:29px; " >กําไรสุทธิของกิจการที่ต้องเสียภาษีเงินได้นิติบุคคลในอัตราดังนี้</div> 
				</td>
				<td class="text-center font-bold"></td>
				<td class="text-right padding-right font-bold"></td>
				<td class="text-right padding-right font-bold" style="border-right:none;"></td>
		  </tr>
		  <tr>
				<td style="text-indent:46px">
				(1.1) อัตราร้อยละ 30 ของกําไรสุทธิ 
				</td>
				<div style="position:absolute;top:169px;left:380px;">.....................................................................................................................................</div>
				<td class="border-dotted text-center font-bold"></td>
				<td class="border-dotted text-right padding-right font-bold"></td>
				<td class="border-dotted text-right padding-right font-bold" style="border-right:none;"></td>
		  </tr>
		  <tr>
				<td style="text-indent:46px">
				  (1.2) อัตราร้อยละ 25 ของกําไรสุทธิ 
				</td>
				<div style="position:absolute;top:189px;left:380px;">.....................................................................................................................................</div>
				<td class="border-dotted text-center font-bold"></td>
				<td class="border-dotted text-right padding-right font-bold"></td>
				<td class="border-dotted text-right padding-right font-bold" style="border-right:none;"></td>
		  </tr>
		  <tr>
				<td style="text-indent:46px">
				  (1.3) อัตราร้อยละ 20 ของกําไรสุทธิ
				</td>
				<div style="position:absolute;top:209px;left:380px;">.....................................................................................................................................</div>
				<td class="border-dotted text-center font-bold"></td>
				<td class="border-dotted text-right padding-right font-bold"></td>
				<td class="border-dotted text-right padding-right font-bold" style="border-right:none;"></td>
		  </tr>
		  <tr>
				<td style="text-indent:46px">
				   (1.4) อัตราอื่นๆ (ระบุ) ................... ของกําไรสุทธิ 
				   <div style="position:fixed; left:98px; top:535px;  width:100px; padding-left:15; " class="text-left">9</div>
				</td>
				<td class="border-dotted text-center font-bold"></td>
				<td class="border-dotted text-right padding-right font-bold"></td>
				<td class="border-dotted text-right padding-right font-bold" style="border-right:none;"></td>
		  </tr>
		  <tr>
				<td style="text-indent:30px ">
				  (2) กรณีผู้ได้รับเงินปันผลไม่ได้รับเครดิตภาษี เนื่องจากจ่ายจาก
				</td>
				<td class="text-center font-bold"></td>
				<td class="text-right padding-right font-bold"></td>
				<td class="text-right padding-right font-bold" style="border-right:none;"></td>
		  </tr>
		  <tr>
				<td style="text-indent:46px">
					  (2.1) กําไรสุทธิของกิจการที่ได้รับยกเว้นภาษีเงินได้นิติบุคคล  
				</td>
				<div style="position:absolute;top:267px;left:380px;">.....................................................................................................................................</div>
				<td class="border-dotted text-center font-bold"></td>
				<td class="border-dotted text-right padding-right font-bold"></td>
				<td class="border-dotted text-right padding-right font-bold" style="border-right:none;"></td>
		  </tr>

		  <tr>
				<td style="text-indent:46px">
					  (2.2) เงินปันผลหรือเงินส่วนแบ่งของกําไรที่ได้รับยกเว้นไม่ต้อง 
					  <div style="text-indent:70px">นํามารวมคํานวณเป็นรายได้เพื่อเสียภาษีเงินได้นิติบุคคล</div>
				</td>
				<div style="position:absolute;top:302px;left:380px;">.....................................................................................................................................</div>
				<td class="border-dotted text-center font-bold"></td>
				<td class="border-dotted text-right padding-right font-bold"></td>
				<td class="border-dotted text-right padding-right font-bold" style="border-right:none;"></td>
		  </tr>
		  <tr>
				<td style="text-indent:46px">
				 (2.3) กําไรสุทธิส่วนที่ได้หักผลขาดทุนสุทธิยกมาไม่เกิน 5 ปี 
				<div style="text-indent:70px">
				  ก่อนรอบระยะเวลาบัญชี ปีปัจจุบัน
				</div>
				</td>
				<div style="position:absolute;top:336px;left:380px;">.....................................................................................................................................</div>
				<td class="border-dotted text-center font-bold"></td>
				<td class="border-dotted text-right padding-right font-bold"></td>
				<td class="border-dotted text-right padding-right font-bold" style="border-right:none;"></td>
		  </tr>
		  <tr>
				<td style="text-indent:46px">
				   (2.4) กําไรที่รับรู้ทางบัญชีโดยวิธีส่วนได้เสีย (equity method)
				</td>
				<div style="position:absolute;top:356px;left:380px;">.....................................................................................................................................</div>
				<td class="border-dotted text-center font-bold"></td>
				<td class="border-dotted text-right padding-right font-bold"></td>
				<td class="border-dotted text-right padding-right font-bold" style="border-right:none;"></td>
		  </tr>
		  <tr>
				<td style="text-indent:46px">
				  (2.5) อื่นๆ (ระบุ).......................................................................</td>
				  <div style="position:absolute;top:375px;left:380px;">.....................................................................................................................................</div>
				<td class="border-dotted text-center font-bold"></td>
				<td class="border-dotted textr-ight padding-right font-bold"></td>
				<td class="border-dotted text-right padding-right font-bold" style="border-right:none;"></td>
		  </tr>
		  <tr>
				<td>
				  <div>
					5. การจ่ายเงินได้ที่ต้องหักภาษี ณ ที่จ่าย ตามคําสั่งกรมสรรพากรที่ออก
				  </dvi>
				  <div class="text-indent">
					ตามมาตรา 3 เตรส (ระบุ)..................................................................................
				  </div>
				  <div style="padding-left:13px;  width:100%;  padding-right:16px;  ">
				  (เช่น รางวัล ส่วนลดหรือประโยชน์ใดๆ เนื่องจากการส่งเสริมการขาย รางวัลในการประกวด การแข่งขัน การชิงโชค ค่าแสดงของนัก แสดงสาธารณะ ค่าบริการ ค่าจ้างทําของ ค่าโฆษณา ค่าเช่า ค่าขนส่ง ค่าเบี้ยประกันวินาศภัย ฯลฯ) 
				  </div>
				</td>
				 <div style="position:absolute;top:475px;left:380px;">.....................................................................................................................................</div>
				<td class="border-dotted text-center font-bold"></td>
				<td class="border-dotted text-right padding-right font-bold"></td>
				<td class="border-dotted text-right padding-right font-bold" style="border-right:none;"></td> 
		  </tr>
		  <tr>
				<td class="">
				   6. อื่นๆ (ระบุ)..........................................................................................................        
				</td>
				 <div style="position:absolute;top:494.5px;left:380px;">.....................................................................................................................................</div>
				<td class="border-dotted text-center font-bold"></td>
				<td class="border-dotted text-right padding-right font-bold"></td>
				<td class="border-dotted text-right padding-right font-bold" style="border-right:none;"></td>
		  </tr>
		  <tr>
			  <td class="border-right-solid">&nbsp;</td>
			  <td class="border-right-solid text-center font-bold"></td>
			  <td class="border-right-solid text-right padding-right font-bold"></td>
			  <td class="border-right-solid text-right padding-right font-bold" style="border-right:none;"></td>

		  </tr>
		  <tr>
			  <td colspan="2"  style="text-align:right;margin-top:-10px;">รวมเงินที่จ่ายและภาษีที่หักนําส่ง </td>
			  
			  <td class="text-right padding-right font-bold" style="border-bottom:solid 2px;">'.number_format($arrayData["ADC_PAYAMT"],2).'</td>
			  <td class="text-right padding-right font-bold" style="border-bottom:solid 2px" style="border-right:none;width: 93px;">'.number_format($arrayData["ADC_PAYTAX"],2).'</td>

		  </tr>
		  </tbody>
	   </table>     
	  </div>
	';

	//เส้น สตางค์
	$html .= '
	  <div style="border-left:1px solid;  height:520px;  position:absolute;  left:575px; top:341px;">

	  </div>
	  <div style="border-left:1px solid;  height:520px;  position:absolute;   left:700px; top:341px;">
		
	  </div>
	';

	$html .= '
	<div style="position:absolute; bottom:175px; left:-20px; border:1px solid; border-radius:2px; margin-right:20px; width:100%; height:56px;">
	   <div style="width:500px; position:absolute; top:15px; left:0px; padding-left:30px; padding-top:10px; padding-bottom:5px;"><b>รวมเงินภาษีที่หักนําส่ง</b><i> (ตัวอักษร)</i></div>
	   <div style="border:1px solid; width:500px; position:absolute; top:22px; right:1px; padding-left:30px;padding-top: 3px;height:25px;" class="font-bold">'.$lib->baht_text($arrayData["ADC_PAYTAX"]).'</div>
	</div>
	';

	$html .= '
	<div style="position:absolute; bottom:147px; left:-20px; border:1px solid ; border-radius:2px; margin-right:20px; width:100%;padding-left:7px;height:25px; ">
		<b>เงินที่จ่ายเข้า</b> กบข.กสจ./กองทุนสงเคราะห์ครูโรงเรียนเอกชน.............................บาท กองทุนประกันสังคม...........................บาท กองทุนสำรองเลี้ยงชีพ..............................บาท
	</div>
	';
	//ข้อมูล เงินที่จ่ายเข้า => กบข.กสจ./กองทุนสงเคราะห์ครูโรงเรียนเอกชน | กองทุนประกันสังคม | กองทุนสำรองเลี้ยงชีพ

	$html .= '
	<div style="position:absolute; bottom:119px; left:-20px; border:1px solid;  border-radius:2px; margin-right:20px; width:100%;padding-left:7px;height:25px; ">
		<b>ผู้จ่ายเงิน</b>   
	</div>
	';


	$html .= '
	<div style="position:absolute; bottom:122px; left:30px;  margin-right:20px; width:100%; padding-top:-5px; ">
	  <input type="checkbox" class="checkbox checkbox-margin-left" checked> (1) หัก ณ ที่จ่าย  
	  <input type="checkbox" class="checkbox checkbox-margin-left"> (2) ออกให้ตลอดไป 
	  <input type="checkbox" class="checkbox checkbox-margin-left"> (3) ออกให้ครั้งเดียว
	  <input type="checkbox" class="checkbox checkbox-margin-left"> (4) อื่นๆ (ให้ระบุ)...................................................
	</div>
	';

	$html .= '
	<div  style="position:absolute; bottom:21px; left:-20px;   margin-right:20px; width:240px; height:95px;  padding-left:8px; padding-left:8px;">
	  <b>คำเตือน</b>
	</div>
	<div style="position:absolute; bottom:21px; left:-20px;   margin-right:20px; width:240px; height:95px;  border-radius:2px; border:1px solid  ; padding-left:70px;">
		<div style="letter-spacing:0.2px;">  ผู้มีหน้าที่ออกหนังสือรับรองการหักภาษี ณ ที่จ่าย</div>
		<div style="letter-spacing:0.2px;">ฝ่าฝืนไม่มีปฏิบัติตามมาตรา 50 ทวิ แห่งประมวล</div>
		<div style="letter-spacing:0.6px;">รัษฎากร ต้องรับโทษทางอาญาตามมาตรา 35 แห่งประมวลรัษฎากร</div>
	</div>
	<div style="position:absolute; bottom:21px; right:-40px;   margin-right:20px; width:426px; height:95px;  border-radius:2px; border:1px solid ;">
		<div style="padding-left:30px;">
		ขอรับรองว่าข้อความและตัวเลขดังกล่าวข้างต้นถูกต้องตรงกับความจริงทุกประการ
		</div> 
		<div style="position:absolute; top:24px; left:80px;">
		  ลงชื่อ
		</div> 
		<div style="position:absolute; top:30px; left:115px;">
			<img src="../../resource/utility_icon/signature/manager.jpg" style="width:100px">
	  </div> 
		<div style="padding-left:40px; position:absolute; top:24px; right:150px;">
		  ผู้จ่ายเงิน
		</div> 

		<div style="padding-left:40px; position:absolute; top:24px; right:70px;">
			<img src="../../resource/logo/logo.jpg" style="width:65px">
		</div> 

		<div class="text-center" style="position:absolute; line-height:9px; font-size:14px; top:20px; right:5px; width:60px; height:60px; border:1px solid; border-radius:220%; text-align:center; line-height:12px;border-collapse: separate;">
		
		</div> 
		<div style="position:absolute; line-height:9px; font-size:14px; top:30px; right:5px; width:60px;text-align:center; line-height:12px; ">
			ประทับตรา<br />นิติบุคคล<br />ถ้ามี
		</div>

		<div style="position:absolute; top:53px; left:80px;">
			  .............................................................
		</div> 
		<div style="position:absolute; top:51px; left:80px;  width:155px;" class="text-center font-bold">
			'.date('d/m').'/'.(date('Y') + 543).'
		</div> 

		<div style="position:absolute; top:70px; left:80px;">
			( วัน เดือน ปี ที	ออกหนังสือรับรองฯ )
		</div> 
	</div>
	';

	$html .= '
		</div>
	  ';


	// Footer
	$html .= '</div>';
	$dompdf = new Dompdf([
		'fontDir' => realpath('../../resource/fonts'),
		'chroot' => realpath('/'),
		'isRemoteEnabled' => true
	]);
	$dompdf->set_paper('A4');
	$dompdf->load_html($html);
	$dompdf->render();
	$pathOutput = __DIR__."/../../resource/pdf/statement/tax_".$arrayData['CARD_PERSON'].".pdf";
	$dompdf->getCanvas()->page_text(520,  25, "","", 12, array(0,0,0));
	$dompdf->getCanvas()->get_cpdf()->setEncryption($password);
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