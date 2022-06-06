<?php
require_once('../autoload.php');

use Dompdf\Dompdf;

$dompdf = new DOMPDF();

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DocBalanceConfirm')){
		$member_no = $configAS[$payload["ref_memno"]] ?? $payload["ref_memno"];
		$Contractno = null;
		$sum_dept = 0;
		$sum_tiker = 0;
		$sum_share = 0;
		$start_date = $dataComing["date"] ?? $default_date;
		$getBalancedate = $conoracle->prepare("SELECT cf.CONFIRMBAL_DATE  FROM (
											select MAX(confirmbal_date) as CONFIRMBAL_DATE from cmconfirmbalance where  member_no = :member_no
											and TO_CHAR(confirmbal_date,'YYYYMM') = TO_CHAR(TO_DATE(:start_date,'YYYY-MM-DD') ,'YYYYMM')
											order by confirmbal_date desc ) t1, cmconfirmbalance cf
											WHERE cf.confirmbal_date = t1.CONFIRMBAL_DATE and member_no = :member_no AND rownum  = 1");
		$getBalancedate->execute([':member_no' => $member_no,
								  ':start_date' => $start_date
		]);
		$rowBalDate = $getBalancedate->fetch(PDO::FETCH_ASSOC);
		$date_balance = date('Y-m-d',strtotime($rowBalDate["CONFIRMBAL_DATE"]));
		$date_now = date('Ymd',strtotime($rowBalDate["CONFIRMBAL_DATE"]. ' + 5 days'));

		if(date(Ymd) >= $date_now){
			$arrayContractCheckGrp = array();  //เช็คยืนยันยอดเงินกู้
			$fetchContractTypeCheck = $conmysql->prepare("SELECT balance_status FROM gcconstantbalanceconfirm WHERE member_no = :member_no");
			$fetchContractTypeCheck->execute([':member_no' => $member_no]);
			$rowContractnoCheck = $fetchContractTypeCheck->fetch(PDO::FETCH_ASSOC);
			$Contractno  = $rowContractnoCheck["balance_status"] ||"0";
			
			$arrayDepttypeCheckGrp = array();//เช็คยืนยันยอดเงินฝาก
			$fetchdeptTypeCheck = $conmysql->prepare("SELECT DEPTACCOUNT_NO FROM gcconstantdeposit WHERE IS_CLOSESTATUS ='1' AND member_no = :member_no");
			$fetchdeptTypeCheck->execute([':member_no' => $member_no]);
			while($rowDepttypeCheck = $fetchdeptTypeCheck->fetch(PDO::FETCH_ASSOC)){
				$arrayDepttypeCheckGrp[] = $rowDepttypeCheck["DEPTACCOUNT_NO"];
			}
			
			$getBalanceMaster = $conoracle->prepare("SELECT mp.PRENAME_DESC,mb.MEMB_NAME,mb.MEMB_ENAME ,'' as DEPARTMENT,'' as DEPART_GROUP , mb.ADDR_PHONE, mb.ADDR_FAX,
													mp.SUFFNAME_DESC,
													mb.ADDR_POSTCODE AS ADDR_POSTCODE,
													mb.ADDR_NO as ADDR_NO,
													mb.ADDR_MOO as ADDR_MOO,
													mb.ADDR_SOI as ADDR_SOI,
													mb.ADDR_VILLAGE as ADDR_VILLAGE,
													mb.ADDR_ROAD as ADDR_ROAD,
													mb.DISTRICT_CODE AS DISTRICT_CODE,
													mb.TAMBOL_CODE AS TAMBOL_CODE,
													MB.PROVINCE_CODE AS PROVINCE_CODE,
													MB.ADDR_POSTCODE AS ADDR_POSTCODE,
													MBT.TAMBOL_DESC AS TAMBOL_REG_DESC,
													MBD.DISTRICT_DESC AS DISTRICT_REG_DESC,
													MBP.PROVINCE_DESC AS PROVINCE_REG_DESC,											
													MBT.TAMBOL_DESC AS TAMBOL_DESC,
													MBD.DISTRICT_DESC AS DISTRICT_DESC,										
													MBP.PROVINCE_DESC AS PROVINCE_DESC
													FROM mbmembmaster mb 
													LEFT JOIN mbucfprename mp ON mb.PRENAME_CODE = mp.PRENAME_CODE
													LEFT JOIN MBUCFTAMBOL MBT ON mb.TAMBOL_CODE = MBT.TAMBOL_CODE
													LEFT JOIN MBUCFDISTRICT MBD ON mb.DISTRICT_CODE = MBD.DISTRICT_CODE
													LEFT JOIN MBUCFPROVINCE MBP ON mb.PROVINCE_CODE = MBP.PROVINCE_CODE
													WHERE mb.member_no = :member_no");
			$getBalanceMaster->execute([':member_no' => $member_no]);
			$rowBalMaster = $getBalanceMaster->fetch(PDO::FETCH_ASSOC);
			$address = (isset($rowBalMaster["ADDR_NO"]) ? $rowBalMaster["ADDR_NO"] : null);
			if(isset($rowBalMaster["PROVINCE_CODE"]) && $rowBalMaster["PROVINCE_CODE"] == '10'){
				$address .= (isset($rowBalMaster["ADDR_MOO"]) ? ' หมู่'.$rowBalMaster["ADDR_MOO"] : null);
				$address .= (isset($rowBalMaster["ADDR_SOI"]) ? ' ซอย'.$rowBalMaster["ADDR_SOI"] : null);
				$address .= (isset($rowBalMaster["ADDR_VILLAGE"]) ? ' หมู่บ้าน'.$rowBalMaster["ADDR_VILLAGE"] : null);
				$address .= (isset($rowBalMaster["ADDR_ROAD"]) ? ' ถนน'.$rowBalMaster["ADDR_ROAD"] : null);
				$address .= (isset($rowBalMaster["TAMBOL_REG_DESC"]) ? ' แขวง'.$rowBalMaster["TAMBOL_REG_DESC"] : null);
				$address .= (isset($rowBalMaster["DISTRICT_REG_DESC"]) ? ' เขต'.$rowBalMaster["DISTRICT_REG_DESC"] : null);
				$address .= (isset($rowBalMaster["PROVINCE_REG_DESC"]) ? ' '.$rowBalMaster["PROVINCE_REG_DESC"] : null);
				$address .= (isset($rowBalMaster["ADDR_POSTCODE"]) ? ' '.$rowBalMaster["ADDR_POSTCODE"] : null);
			}else{
				$address .= (isset($rowBalMaster["ADDR_MOO"]) ? ' หมู่'.$rowBalMaster["ADDR_MOO"] : null);
				$address .= (isset($rowBalMaster["ADDR_SOI"]) ? ' ซอย'.$rowBalMaster["ADDR_SOI"] : null);
				$address .= (isset($rowBalMaster["ADDR_VILLAGE"]) ? ' หมู่บ้าน'.$rowBalMaster["ADDR_VILLAGE"] : null);
				$address .= (isset($rowBalMaster["ADDR_ROAD"]) ? ' ถนน'.$rowBalMaster["ADDR_ROAD"] : null);
				$address .= (isset($rowBalMaster["TAMBOL_REG_DESC"]) ? ' ตำบล'.$rowBalMaster["TAMBOL_REG_DESC"] : null);
				$address .= (isset($rowBalMaster["DISTRICT_REG_DESC"]) ? ' อำเภอ'.$rowBalMaster["DISTRICT_REG_DESC"] : null);
				$address .= (isset($rowBalMaster["PROVINCE_REG_DESC"]) ? ' จังหวัด'.$rowBalMaster["PROVINCE_REG_DESC"] : null);
				$address .= (isset($rowBalMaster["ADDR_POSTCODE"]) ? ' '.$rowBalMaster["ADDR_POSTCODE"] : null);
			}
			$arrHeader = array();
			$arrDetail = array();
			$arrHeader["full_name"] = $rowBalMaster["PRENAME_DESC"].$rowBalMaster["MEMB_NAME"].' '.$rowBalMaster["SUFFNAME_DESC"];
			$arrHeader["department"] = $rowBalMaster["DEPARTMENT"];
			$arrHeader["depart_group"] = TRIM($rowBalMaster["DEPART_GROUP"]);
			$arrHeader["addr_phone"] = $rowBalMaster["ADDR_PHONE"];
			$arrHeader["addr_fax"] = $rowBalMaster["ADDR_FAX"];
			$arrHeader["full_address"] = $address;
			$arrHeader["member_no"] = $member_no;
			$arrHeader["date_confirm"] = $rowBalDate["CONFIRMBAL_DATE"];
			
			$getBalanceLoan = $conoracle->prepare(" select cmconfirmbalance.member_no,
												'สัญญาเลขที่' ||'	 '|| TRIM(cmconfirmbalance.bizzaccount_no) as bizzaccount_no,
												cmconfirmbalance.balance_value as BALANCE_AMT,
												cmconfirmbalance.intarrear_amt as intarrear_amt,
												cmconfirmbalance.bfintarrear_amt as bfintarrear_amt
												from cmconfirmbalance,lccfloantype
												where ( lccfloantype.branch_id = cmconfirmbalance.bizzbranch_id )
												and ( lccfloantype.loantype_code = cmconfirmbalance.bizzacctype_code )
												and	(cmconfirmbalance.confirmbal_date) = to_date(:balance_date,'YYYY-MM-DD') 
												and	( cmconfirmbalance.bizz_system = 'LON' )
												and ( cmconfirmbalance.member_no = :member_no)
												order by lccfloantype.loangroup_code");
			$getBalanceLoan->execute([':member_no' => $member_no,
									  ':balance_date' => $date_balance
			]);
			$sum_balance = 0;
			$sum_int = 0;
			while($rowBalDetailLoan = $getBalanceLoan->fetch(PDO::FETCH_ASSOC)){
				$arrBalDetail = array();
				$arrBalDetail["INTARREAR_AMT"] = $rowBalDetailLoan["INTARREAR_AMT"];
				$arrBalDetail["BALANCE_AMT"] = $rowBalDetailLoan["BALANCE_AMT"];
				$arrBalDetail["BFINTARREAR_AMT"] = $rowBalDetailLoan["BFINTARREAR_AMT"];
				$arrBalDetail["BIZZACCOUNT_NO"] = $rowBalDetailLoan["BIZZACCOUNT_NO"];
				$sum_balance += $rowBalDetailLoan["BALANCE_AMT"];
				$sum_int += $rowBalDetailLoan["INTARREAR_AMT"];
				$arrDetail[] = $arrBalDetail;
				
			}
			
			$getBalanceDetail = $conoracle->prepare("select	 a.member_no ,a.confirmbal_date,
												sum( case when a.bizz_system = 'DEP' then a.balance_value else 0 end ) as DEPT_BAL,
												sum( case when a.bizz_system = 'DEP' then a.intarrear_amt else 0 end ) as DEPT_INTARR,
												sum( case when a.bizz_system = 'PRM' then a.balance_value else 0 end ) as PRM_BAL,
												sum( case when a.bizz_system = 'PRM' then a.intarrear_amt else 0 end ) as PRM_INTARR,
												sum( case when a.bizz_system = 'SHR' then a.balance_value else 0 end ) as SHARE_BAL,
												sum( a.balance_value ) as sum_allbal
												from cmconfirmbalance a
												where a.member_no= :member_no
												and a.confirmbal_date	= to_date(:balance_date,'YYYY-MM-DD') 
												".(count($arrayDepttypeCheckGrp) > 0 ? ("and a.BIZZACCOUNT_NO NOT IN('".implode("','",$arrayDepttypeCheckGrp)."')") : null)."
												group by	a.member_no, a.confirmbal_date
												order by a.member_no");											
			$getBalanceDetail->execute([':member_no' => $member_no,
										':balance_date' => $date_balance
			]);
			while($rowBalDept = $getBalanceDetail->fetch(PDO::FETCH_ASSOC)){
				$arrDetailDept = array();
				$arrBalDept["DEPT_BAL"] = $rowBalDept["DEPT_BAL"];
				$arrBalDept["DEPT_INTARR"] = $rowBalDept["DEPT_INTARR"];
				$arrBalDept["PRM_BAL"] = $rowBalDept["PRM_BAL"];
				$arrBalDept["PRM_INTARR"] = $rowBalDept["PRM_INTARR"];
				$arrBalDept["SHARE_BAL"] = $rowBalDept["SHARE_BAL"];
				$sum_dept += $rowBalDept["DEPT_BAL"];
				$sum_tiker += $rowBalDept["PRM_BAL"];
				$sum_share += $rowBalDept["SHARE_BAL"];
				$arrDetailDept[] = $arrBalDept;
			}

			/*foreach($arrDetail as $key => $value){
				$arrDetail[$key]["BALANCE_AMT"] = number_format($value["BALANCE_AMT"],2);
				$arrDetail[$key]["INTARREAR_AMT"] = number_format($value["INTARREAR_AMT"],2);
			}*/
			foreach($arrDetailDept as $key => $value){
				$arrDetailDept[$key]["DEPT_BAL"] = number_format($value["DEPT_BAL"],2);
				$arrDetailDept[$key]["DEPT_INTARR"] = number_format($value["DEPT_INTARR"],2);
				$arrDetailDept[$key]["PRM_BAL"] = number_format($value["PRM_BAL"],2);
				$arrDetailDept[$key]["PRM_INTARR"] = number_format($value["PRM_INTARR"],2); 
				$arrDetailDept[$key]["SHARE_BAL"] = number_format($value["SHARE_BAL"],2);
			}
			
			$sum_balance =  number_format($sum_balance,2);
			$sum_int =  number_format($sum_int,2);
			$sum_dept = number_format($sum_dept,2);
			$sum_tiker = number_format($sum_tiker,2);
			$sum_share = number_format($sum_share,2);
			if(isset($rowBalMaster["MEMB_NAME"]) && sizeof($arrDetail) > 0 || $arrDetailDept > 0 ){
				$arrayPDF = GeneratePdfDoc($arrHeader,$arrDetail,$sum_balance,$arrDetailDept,$sum_dept,$sum_tiker,$sum_share,$Contractno,$sum_int,$date_now,$lib);
				if($arrayPDF["RESULT"]){
					$arrayResult['DATA_CONFIRM'] = "ข้าพเจ้า ".$arrHeader["full_name"]." เลขที่สหกรณ์ ".$member_no." ตามที่ทาง 
					 ชุมนุมสหกรณ์ออมทรัพย์แห่งประเทศไทย  จํากัด ได้แจ้งรายการบัญชีของข้าพเจ้า สิ้นสุด ณ วันที่ ".$lib->convertdate(date('Y-m-d',strtotime($date_now)),'d m Y').
					' นั้น ข้าพเจ้าได้ตรวจสอบแล้วปรากฏว่า ข้อมูลดังกล่าว';
					$arrayResult['REPORT_URL'] = $config["URL_SERVICE"].$arrayPDF["PATH"];
					$arrayResult['ADVICE'] = "1.ข้อมูลยืนยันยอดคงเหลือนี้ ใช้สำหรับตรวจสอบข้อมูลเบื้องต้นเท่านั้น หากต้องการเอกสารที่ใช้ประกอบการลงบัญชีของสหกรณ์ กรุณาติดต่อเจ้าหน้าที่ ชสอ.\n2.ข้อมูลยืนยันยอดคงเหลือ สามารถดูข้อมูลได้เฉพาะวันสิ้นเดือนเท่านั้น โดยดูข้อมูลได้ถัดจากวันสิ้นเดือน 5 วันทำการ หรือติดต่อเจ้าหน้าที่ ชสอ.";
					$arrayResult['IS_CONFIRM'] = FALSE;
					$arrayResult['DISABLED_CONFIRM'] = TRUE;
					$arrayResult['RESULT'] = TRUE;
					require_once('../../include/exit_footer.php');
				}else{
					$arrayResult['RESPONSE_CODE'] = "WS0044";
					$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');	
				}
			}else{
				$arrayResult['IS_CONFIRM'] = FALSE;
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}
						
		}else{
			$arrayResult['WAITING_CONFIRM '] = "ข้อมูลยืนยันยอดคงเหลือ สามารถดูข้อมูลได้เฉพาะวันสิ้นเดือนเท่านั้น โดยดูข้อมูลได้ถัดจากวันสิ้นเดือน 3 วันทำการ หรือติดต่อเจ้าหน้าที่ ชสอ.";
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

function GeneratePdfDoc($arrHeader,$arrDetail,$sum_balance,$arrDetailDept,$sum_dept,$sum_tiker,$sum_share,$Contractno,$sum_int,$date_now,$lib) {
	$cur_date = date();
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
				.div-table{ 
					display: table; 
				}
				.div-tr{ 
					display: table-row; 
				}
				.div-td{ 
					display: inline-block; 
				}
				* {
				  font-family: THSarabun;
				  font-size: 16pt;
				  line-height: 16pt;
				}
				body {
				  padding: 0;
				}

				table{
				  border: none;
				}
				th{
					text-align:center;
					border:1px solid ;
					font-weight:bold;
				}
				.text-right{
					text-align:right;
				}
				.text-center{
					text-align:center
				}
				.text-left{
					text-align:left;
				}
				td{
					border:1px solid;
					padding-left: 5px;
					padding-right: 5px;
				}
				.nonborder td{
					border:0.5px solid;
				}
				.none-border {
					border-collapse: collapse;border: 0px;
				}
				.none-border td{
					border: 0px;
				}
				.nowrap{
					white-space: nowrap;
				}
			</style>';
	$html .= '<div style="height:auto; margin:-15;padding: 0 44px;">
			<div style="text-align: center;padding-top: 80px;">
				<div>
					<div style="padding: 12px 20px;border: 3px solid #000000;font-weight: bold;font-size: 24px;clear: both; width: 30% ; margin-left:210px">ยืนยันยอดคงเหลือ</div>
				</div>
			</div>
			<div style="text-align:right;display: block">วันที่พิมพ์ '.$lib->convertdate(date('Y-m-d'),'d m Y').'</div>
			<br>
			<div>
				<div class="div-td">
					เลขที่สมาชิก/รหัสลูกค้า 
				</div>
				<div class="div-td" style="padding-left: 20px;">
					'.$arrHeader["member_no"].'
				</div>
			</div>
			<div>
				<div class="div-td">
					ชื่อสมาชิก/ชื่อลูกค้า
				</div>
				<div class="div-td" style="padding-left: 20px;">
					'.$arrHeader["full_name"].'
				</div>
			</div>
			<table class="none-border" style="width: 55%;">
			  <tr>
				<td style="vertical-align: top;">ที่อยู่</td>
				<td>'.$arrHeader["full_address"].'</td>
			  </tr>
			  <tr>
				<td style="vertical-align: top">โทรศัพท์</td>
				<td>'.$arrHeader["addr_phone"].' '.(isset($arrHeader["addr_fax"]) ? ("โทรสาร ".$arrHeader["addr_fax"]."") : "").'</td>
			  </tr>
			</table>
			<div style="margin-top: 10px;">
			</div>
			<div style="border-top: 1px solid #000000;text-indent: 40px; padding-top: 5px;">
				ชุมนุมสหกรณ์ออมทรัพย์แห่งประเทศไทย จำกัด (ชสอ.) ขอแจ้งยืนยันยอดคงเหลือ ณ วันที่ '.$lib->convertdate(date('Y-m-d',strtotime($arrHeader["date_confirm"])),'d M Y').' ประกอบด้วยรายละเอียดดังนี้
			</div>
			';
	//ตารางข้อมูล

	
	$html .= '
	   <div">
		  <table class="none-border" style="width:100%; border-collapse: collapse;">
		  <tbody>';
	//ข้อมูลในตาราง
	
	$n = 0;	
	
	if($Contractno == "1"){
		$n++;
		$html .= '<tr>
					<td class="text-left"  style="width:5%;">'.$n.'</td> 
					<td class="text-left">เงินกู้  เป็นเงิน </td>  						
					<td></td>
					<td class="text-right">**ให้ติดต่อเจ้าหน้าที่ ชสอ**</td>	
					
				 </tr>
			';
	}else{
		$n++;
		$html .= '<tr>
					<td style="width: 5%;white-space: nowrap;">'.$n.'</td>  
					<td class="text-left">เงินกู้  เป็นเงิน </td>  
					<td class="text-right">'.($arrDetail[0]["BALANCE_AMT"] > 0 ? ((sizeof($arrDetail) > 3) ? '' : 'รวมทั้งหมด') : null).'</td>  
					<td class="text-right" style="" > '.((sizeof($arrDetail) > 3) ? '' : $sum_balance).'</td>
					<td class="text-left" style="width: 5%;white-space: nowrap;" >'.((sizeof($arrDetail) > 3) ? '' : 'บาท').'</td>
				 </tr>';
		if(sizeof($arrDetail) > 3){
				$html .= '
					<tr>
						<td style="width: 5%;white-space: nowrap;"></td>  
						<td class="text-left" style="padding-left:40px;white-space: nowrap;">จำนวน '.sizeof($arrDetail).' สัญญา</td>  
						<td class="text-right" > เงินต้น </td>   
						<td class="text-right"> '.$sum_balance.'</td>
						<td class="text-left" style="width: 5%;white-space: nowrap;">บาท</td>
					</tr>
					<tr>
						<td style="width: 5%; white-space: nowrap;"></td>  
						<td></td>  
						<td class="text-right"> ดอกเบี้ยค้างชำระ </td>  
						<td class="text-right">'.$sum_int.'</td>
						<td class="text-left" style="width: 5%;white-space: nowrap;">บาท</td>
					</tr>
				';
		}else{
			for($i = 0;$i < sizeof($arrDetail);$i++){
				$html .= '
					<tr>
						<td style="width: 5%;white-space: nowrap;"></td>  
						<td class="text-left" style="padding-left:40px;white-space: nowrap;">'.$arrDetail[$i]["BIZZACCOUNT_NO"].'</td>  
						<td class="text-right" > เงินต้น </td>   
						<td class="text-right"> '.number_format($arrDetail[$i]["BALANCE_AMT"],2).'</td>
						<td class="text-left" style="width: 5%;white-space: nowrap;">บาท</td>
					</tr>
					<tr>
						<td style="width: 5%;white-space: nowrap;"></td>  
						<td></td>  
						<td class="text-right"> ดอกเบี้ยค้างชำระ </td>  
						<td class="text-right">'.number_format($arrDetail[$i]["INTARREAR_AMT"],2).'</td>
						<td class="text-left" style="width: 5%;white-space: nowrap;">บาท</td>
					</tr>
				';
			}
		}
	}	
	
	
	if($sum_share > 0){
		$n++;
		for($i = 0;$i < sizeof($arrDetailDept);$i++){	
			$html .= '<tr>
						<td style="width: 5%;white-space: nowrap;">'.$n.'</td> 
						<td class="text-left">ทุนเรือนหุ้น  เป็นเงิน </td>
						<td></td>
						<td class="text-right">'.$arrDetailDept[$i]["SHARE_BAL"].'</td>	
						<td class="text-left" style="width: 5%;white-space: nowrap;">บาท</td>			
					 </tr>';
		}
	}
	//if($sum_tiker > 0){  
		$n++;
		for($i = 0;$i < sizeof($arrDetailDept);$i++){	
			$html .= '<tr>
						<td style="width: 5%;white-space: nowrap;">'.$n.'</td> 
						<td class="text-left">ตั๋วสัญญาใช้เงิน  เป็นเงิน </td> 
						<td></td>
						<td class="text-right" >'.$arrDetailDept[$i]["PRM_BAL"].'</td>	
						<td class="text-left" style="width: 5%;white-space: nowrap;">บาท</td>					
					</tr>';
			$html .= '<tr>
						<td></td>
						<td class="text-left" style="padding-left:80px;white-space: nowrap;"> ดอกเบี้ยค้างจ่าย </td>  
						<td class="text-left" style=""></td>		
						<td class="text-right">'.$arrDetailDept[$i]["PRM_INTARR"].'</td>
						<td class="text-left" style="width: 5%;white-space: nowrap;">บาท</td>
				 </tr>';
		}
	//}

	//if($sum_dept > 0){
		$n++;
		
		for($i = 0;$i < sizeof($arrDetailDept);$i++){	
			$html .= '<tr>
						<td style="width: 5%;white-space: nowrap;">'.$n.'</td> 
						<td class="text-left">เงินฝากประจำ  เป็นเงิน  </td> 
						<td></td>
						<td class="text-right" >'.$arrDetailDept[$i]["DEPT_BAL"].'</td>	
						<td class="text-left" style="width: 5%;white-space: nowrap;">บาท</td>			
					</tr>';
			$html .= '<tr>
						<td></td>
						<td class="text-left" style="padding-left:80px;white-space: nowrap;"> ดอกเบี้ยค้างจ่าย </td>  
						<td class="text-left" style=""></td>
						<td class="text-right">'.$arrDetailDept[$i]["DEPT_INTARR"].'</td>
						<td class="text-left" style="width: 5%;white-space: nowrap;">บาท</td>
				 </tr>';
		}
		
		$n++;
			
			
	//}
	
	$html .= '       
		  </tbody>
		</table>
		<div style="text-indent: 40px;word-wrap: break-word; margin-top:7px">
			อนึ่ง รายการข้างต้นเป็นยอดคงเหลือ ณ วันที่ '.$lib->convertdate(date('Y-m-d',strtotime($arrHeader["date_confirm"])),'d M Y').' รายการที่เกิดขึ้นหลังจากนั้นถือว่า
			ไม่เกี่ยวข้อง หากยอดคงเหลือที่ ชสอ. แจ้งไม่ตรงกับยอดในบัญชีของท่าน กรุณาติดต่อกลับไปยัง ชสอ. เพื่อการ
			ตรวจสอบต่อไป
		</div>
		<div style="text-align: center;padding-left: 100px;padding-top: 80px; color:red;">
			<div></div>
		</div>
		<div style="position: absolute;bottom: 0;"></div>
	   </div>
	   </div>
	';
	
	if(sizeof($arrDetail) > 3){
		$html .= '<div style="page-break-before: always;padding: 0 24px;">
			<div style="position: absolute;bottom: 0;">ฝ่ายสินเชื่อ ต่อ 212-216 , 223, 226 และ 227</div>
			<div style="padding-left: 10px;padding-top: 80px;">
				<div>
					<div style="display: inline-block; padding-top: 5px;">
						เอกสารแนบรายละเอียดเงินกู้ ดังนี้ 
					</div>
					<div style="display: inline-block;text-align: right;float: right;">
						วันที่พิมพ์ '.$lib->convertdate(date('Y-m-d'),'d m Y').'
					</div>
				</div>
				<div style="border-top: 1px solid #000000;text-indent: 30px">
					'.$arrHeader["full_name"].' ('.$arrHeader["member_no"].') ณ วันที่ '.$lib->convertdate(date('Y-m-d',strtotime($arrHeader["date_confirm"])),'d M Y').'
				</div>
				<br>
			</div>
		   <div">
			  <table class="none-border" style="width:100%; border-collapse: collapse;">
			  <tbody>';
			  
		for($i = 0;$i < sizeof($arrDetail);$i++){
			$html .= '
				<tr>
					<td style="width: 5%;white-space: nowrap;"></td>  
					<td class="text-left" style="padding-left:20px;white-space: nowrap;"><span>'.explode(" ",$arrDetail[$i]["BIZZACCOUNT_NO"])[0].'</span><span style="padding-left: 20px;">'.explode(" ",$arrDetail[$i]["BIZZACCOUNT_NO"])[1].'</span></td>  
					<td class="text-center" > เงินต้น </td>   
					<td class="text-right"> '.number_format($arrDetail[$i]["BALANCE_AMT"],2).'</td>
					<td class="text-left" style="width: 5%;white-space: nowrap;">บาท</td>
				</tr>
				<tr>
					<td style="width: 5%;white-space: nowrap;"></td>  
					<td class="text-left" style="padding-left:80px;white-space: nowrap;">ดอกเบี้ยค้างชำระ </td> 
					<td class="text-right"></td>  
					<td class="text-right">'.number_format($arrDetail[$i]["INTARREAR_AMT"],2).'</td>
					<td class="text-left" style="width: 5%;white-space: nowrap;">บาท</td>
				</tr>
			';
		}	
		$html .= '
				<tr>
					<td style="width: 5%;white-space: nowrap;" colspan="2">รวม เงินกู้</td>  
					<td class="text-left"></td>  
					<td class="text-left" > </td>   
					<td class="text-right"></td>
					<td class="text-left" style="width: 5%;white-space: nowrap;"></td>
				</tr>
				<tr>
					<td style="width: 5%;white-space: nowrap;"></td>  
					<td class="text-left" style="padding-left:40px;white-space: nowrap;">จำนวน '.sizeof($arrDetail).' สัญญา</td>  
					<td class="text-right" > เงินต้น </td>   
					<td class="text-right"> '.$sum_balance.'</td>
					<td class="text-left" style="width: 5%;white-space: nowrap;">บาท</td>
				</tr>
				<tr>
					<td style="width: 5%;white-space: nowrap;"></td>  
					<td></td>  
					<td class="text-right"> ดอกเบี้ยค้างชำระ </td>  
					<td class="text-right">'.$sum_int.'</td>
					<td class="text-left" style="width: 5%;white-space: nowrap;">บาท</td>
				</tr>
		</tbody>
		 </table>
		</div>';
	}
	//<div style="position: absolute;bottom: 0;">ฝ่ายสินเชื่อ ต่อ 212-216 , 223, 226 และ 227</div>
	$dompdf = new DOMPDF();
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