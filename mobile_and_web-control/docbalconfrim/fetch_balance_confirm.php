<?php
require_once('../autoload.php');

use Dompdf\Dompdf;

$dompdf = new DOMPDF();

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DocBalanceConfirm')){
		$member_no = $configAS[$payload["ref_memno"]] ?? $payload["ref_memno"];
		$date_now = date('Y-m-d');
		$Contractno = null;
		$sum_dept = 0;
		$sum_tiker = 0;
		$sum_share = 0;
		$arrayContractCheckGrp = array();  //เช็คยืนยันยอดเงินกู้
		$fetchContractTypeCheck = $conmysql->prepare("SELECT CONTRACT_NO FROM gcconstantcontractno WHERE IS_CONFIRMBALANCE ='1' AND member_no = :member_no");
		$fetchContractTypeCheck->execute([':member_no' => $member_no]);
		$rowContractnoCheck = $fetchContractTypeCheck->fetch(PDO::FETCH_ASSOC);
		$Contractno  = $rowContractnoCheck["CONTRACT_NO"] ;
		
		$arrayDepttypeCheckGrp = array();//เช็คยืนยันยอดเงินฝาก
		$fetchdeptTypeCheck = $conmysql->prepare("SELECT DEPTACCOUNT_NO FROM gcconstantdeposit WHERE IS_CLOSESTATUS ='1' AND member_no = :member_no");
		$fetchdeptTypeCheck->execute([':member_no' => $member_no]);
		while($rowDepttypeCheck = $fetchdeptTypeCheck->fetch(PDO::FETCH_ASSOC)){
			$arrayDepttypeCheckGrp[] = $rowDepttypeCheck["DEPTACCOUNT_NO"];
		}
		
		$getBalanceMaster = $conoracle->prepare("SELECT mp.PRENAME_DESC,mb.MEMB_NAME,mb.MEMB_ENAME ,'' as DEPARTMENT,'' as DEPART_GROUP , mb.ADDR_PHONE
												FROM mbmembmaster mb LEFT JOIN mbucfprename mp ON mb.PRENAME_CODE = mp.PRENAME_CODE
												WHERE mb.member_no = :member_no");
		$getBalanceMaster->execute([':member_no' => $member_no]);
		$rowBalMaster = $getBalanceMaster->fetch(PDO::FETCH_ASSOC);
		$arrHeader = array();
		$arrDetail = array();
		$arrHeader["full_name"] = $rowBalMaster["PRENAME_DESC"].$rowBalMaster["MEMB_NAME"];
		$arrHeader["department"] = $rowBalMaster["DEPARTMENT"];
		$arrHeader["depart_group"] = TRIM($rowBalMaster["DEPART_GROUP"]);
		$arrHeader["addr_phone"] = $rowBalMaster["ADDR_PHONE"];
		$arrHeader["member_no"] = $member_no;
		$arrHeader["date_confirm"] = $date_now;
		$rowBalMaster["BALANCE_DATE"] = $date_now;
		
		if($Contractno != "1"){
			$getBalanceLoan = $conoracle->prepare("select cmconfirmbalance.member_no,
													'สัญญาเลขที่ '||cmconfirmbalance.bizzaccount_no as bizzaccount_no,
													cmconfirmbalance.balance_value as BALANCE_AMT,
													cmconfirmbalance.intarrear_amt as intarrear_amt,
													cmconfirmbalance.bfintarrear_amt as bfintarrear_amt
													from	cmconfirmbalance,lccfloantype
													where	( lccfloantype.branch_id = cmconfirmbalance.bizzbranch_id )
													and 	( lccfloantype.loantype_code = cmconfirmbalance.bizzacctype_code )
													and		(cmconfirmbalance.confirmbal_date)	= to_date(:balance_date,'YYYY-MM-DD') 
													and		( cmconfirmbalance.bizz_system		= 'LON' )
													and 	( cmconfirmbalance.member_no = :member_no )
													and 	( select count(*) from 	cmconfirmbalance,lccfloantype
													where	( lccfloantype.branch_id = cmconfirmbalance.bizzbranch_id )
													and 	( lccfloantype.loantype_code = cmconfirmbalance.bizzacctype_code )
													and		(cmconfirmbalance.confirmbal_date)	= to_date(:balance_date,'YYYY-MM-DD') 
													and		( cmconfirmbalance.bizz_system		= 'LON' )
													and 	( cmconfirmbalance.member_no =   :member_no  ) ) <= 3
													union all
													select
													cmconfirmbalance.member_no,
													'จำนวน '||to_char(count(cmconfirmbalance.bizzaccount_no))||'  สัญญา ' as bizzaccount_no,
													sum(cmconfirmbalance.balance_value) as BALANCE_AMT,
													sum(cmconfirmbalance.intarrear_amt) as intarrear_amt,
													sum(cmconfirmbalance.bfintarrear_amt) as bfintarrear_amt
													from	cmconfirmbalance, lccfloantype
													where	( lccfloantype.branch_id = cmconfirmbalance.bizzbranch_id )
													and		( lccfloantype.loantype_code = cmconfirmbalance.bizzacctype_code )
													and		(cmconfirmbalance.confirmbal_date)= to_date(:balance_date,'YYYY-MM-DD') 
													and		( cmconfirmbalance.bizz_system	= 'LON' )
													and 	( cmconfirmbalance.member_no = :member_no  )
													and 	( select count(*)  from 	cmconfirmbalance,lccfloantype
													where	( lccfloantype.branch_id = cmconfirmbalance.bizzbranch_id )
													and 	( lccfloantype.loantype_code = cmconfirmbalance.bizzacctype_code )
													and		(cmconfirmbalance.confirmbal_date)	= to_date(:balance_date,'YYYY-MM-DD') 
													and		( cmconfirmbalance.bizz_system	= 'LON' )
													and 	( cmconfirmbalance.member_no =  :member_no ) ) > 3
													group by 	cmconfirmbalance.member_no");
			$getBalanceLoan->execute([':member_no' => '100989',
									  ':balance_date' => '2020-06-30'
			]);
			$sum_balance = 0;
			while($rowBalDetailLoan = $getBalanceLoan->fetch(PDO::FETCH_ASSOC)){
				$arrBalDetail = array();
				$arrBalDetail["INTARREAR_AMT"] = $rowBalDetailLoan["INTARREAR_AMT"];
				$arrBalDetail["BALANCE_AMT"] = $rowBalDetailLoan["BALANCE_AMT"];
				$arrBalDetail["BFINTARREAR_AMT"] = $rowBalDetailLoan["BFINTARREAR_AMT"];
				$arrBalDetail["BIZZACCOUNT_NO"] = $rowBalDetailLoan["BIZZACCOUNT_NO"];
				$sum_balance += $rowBalDetailLoan["BALANCE_AMT"];
				$arrDetail[] = $arrBalDetail;
				
			}
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
		$getBalanceDetail->execute([':member_no' => '101039',
									':balance_date' => '2020-06-30'
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

		foreach($arrDetail as $key => $value){
			$arrDetail[$key]["BALANCE_AMT"] = number_format($value["BALANCE_AMT"],2);
		}
		foreach($arrDetailDept as $key => $value){
			$arrDetailDept[$key]["DEPT_BAL"] = number_format($value["DEPT_BAL"],2);
			$arrDetailDept[$key]["DEPT_INTARR"] = number_format($value["DEPT_INTARR"],2);
			$arrDetailDept[$key]["PRM_BAL"] = number_format($value["PRM_BAL"],2);
			$arrDetailDept[$key]["PRM_INTARR"] = number_format($value["PRM_INTARR"],2); 
			$arrDetailDept[$key]["SHARE_BAL"] = number_format($value["SHARE_BAL"],2);
		}
		
		$sum_balance =  number_format($sum_balance,2);
		$sum_dept = number_format($sum_dept,2);
		$sum_tiker = number_format($sum_tiker,2);
		$sum_share = number_format($sum_share,2);
		if(isset($rowBalMaster["MEMB_NAME"]) && sizeof($arrDetail) > 0 || $arrDetailDept > 0 ){
			$arrayPDF = GeneratePdfDoc($arrHeader,$arrDetail,$sum_balance,$arrDetailDept,$sum_dept,$sum_tiker,$sum_share);
			if($arrayPDF["RESULT"]){
				$arrayResult['DATA_CONFIRM'] = "ข้าพเจ้า ".$arrHeader["full_name"]." เลขที่สหกรณ์ ".$member_no." ตามที่ทาง 
				 ชุมนุมสหกรณ์ออมทรัพย์แห่งประเทศไทย  จํากัด ได้แจ้งรายการบัญชีของข้าพเจ้า สิ้นสุด ณ วันที่ ".$lib->convertdate(date('Y-m-d',strtotime($date_now)),'d m Y').
				' นั้น ข้าพเจ้าได้ตรวจสอบแล้วปรากฏว่า ข้อมูลดังกล่าว';
				$arrayResult['REPORT_URL'] = $config["URL_SERVICE"].$arrayPDF["PATH"];
				$arrayResult['IS_CONsM'] = $arrDetailDept;
				$arrayResult['IS_CONFIRM'] = FALSE;
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

function GeneratePdfDoc($arrHeader,$arrDetail,$sum_balance,$arrDetailDept,$sum_dept,$sum_tiker,$sum_share) {
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
				td{
					border:1px solid;
					padding-left: 5px;
					padding-right: 5px;
				}
				.nonborder td{
					border:0.5px solid;
				}
			</style>';
	$html .= '<div style="border:1px solid #eee; height:auto; margin:-15">
			<div style="margin-left:10px; margin-top:5px;"> 
			<img src="../../resource/logo/logo.png" style="width:100px;" >
			</div>

			<div style="position: absolute; left: 130px; top: 3px; font-size:30px; font-weight:1000;  ">
			 <b>  ชุมนุมสหกรณ์ออมทรัพย์แห่งประเทศไทย  จํากัด </b>
			</div>

			<div style="position: absolute; left: 130px; top:45px; font-size:14pt ">
				199 หมู่ที่ 2 ถนนนครอินทร์ ตำบลบางสีทอง อำเภอบางกรวย จังหวัดนนทบุรี 11130

			</div>
			<div style="margin-left:50px; font-size:19px; margin-bottom:30px;" >
			<div>
				<b>เรื่อง</b>
			</div>
			<div>
			  <b>เรียน</b>
			</div>
			<div style="margin-top:10px">
			  <div><b>เลขประจําตัว</b></div>
			  <div style="position:absolute; left:260px; top:153px;"><b>โทร </b> </div>
			</div>
			<div style="margin-top:20px; text-indent:50px; line-height: 17px; ">
			หนังสือนี้มิใช่คําเตือนให้ชําระเงิน แต่เพื่อประโยชน์ในการสอบบัญชีของสหกรณ์ ขอให้สมาชิกได้โปรดตรวจสอบ รายการด้าน  ล่างนี้ ว่าถูกต้องหรือคลาดเคลื่อนอย่างไร โดยกรอกรายการตามแบบตอบที่ด้านล่าง 
			<b><u>และส่งแบบตอบไปยังกรรมการผู้จัดการใหญ่ ชุมนุมสหกรณ์ออมทรัพย์แห่งประเทศไทย จํากัด ภายในกําหนด 15 วัน นับแต่วันที่ได้ประกาศ</u></b> มิฉะนั้นจะถือว่าถูกต้อง
			</div>
			<div class="text-right"style="padding-right:110px; margin-top:30px;" >ขอแสดงความนับถือ</div>
			<div class="text-right"style="padding-right:100px; margin-top:100px;">(ดร.สมนึก บุญใหญ่) </div>
			<div class="text-right"style="padding-right:103px; margin-top:-5px;">กรรมการผู้จัดการใหญ่</div>
			<div  style="position:absolute; right:80px; top:355px; ">
				<img src="../../resource/utility_icon/signature/signature.png" style="width:130px;">
			</div>
			<div style="position:absolute; left:110px; top:86px;">
				ขอแจ้งรายการยืนยันยอดคงเหลือของท่านเพียงสิ้นวันที่ '.$arrHeader["date_confirm"].'
			</div>
			<div style="position:absolute; left:110px; top:114px;">'.$arrHeader["full_name"].'</div>
			<div style="position:absolute; left:125px; top:153px;">'.$arrHeader["member_no"].'</div>
			<div style="position:absolute; left:330px; top:153px;">'.$arrHeader["addr_phone"].'</div>';
	//ตารางข้อมูล

	
	$html .= '
	   <div style="margin-left: 60px">
		  <table class="nonborder" style="width:90%; border-collapse: collapse; margin-top:20px;">
		  <tbody>';
	//ข้อมูลในตาราง
	
	$n = 0;	
	if($arrDetail[0]["BALANCE_AMT"] > 0){
		$n++;
		$html .= '<tr>
					<td class="text-left"  style="width:30%;">'.$n.' . เงินกู้  เป็นเงิน </td>  
					<td style="width:30%;"></td>  
					<td class="text-right" style="width:30%;" > '.$sum_balance.'</td>
				 </tr>';
	}
	if($arrDetail[0]["BALANCE_AMT"] > 0){
		for($i = 0;$i < sizeof($arrDetail);$i++){
			$html .= '
				<tr>
				  <td class="text-left" style="padding-left:40px;">'.$arrDetail[$i]["BIZZACCOUNT_NO"].'</td>  
				  <td class="text-left" > เงินต้น </td>   
				  <td class="text-right"> '.$arrDetail[$i]["BALANCE_AMT"].'</td>
				</tr>
				<tr>
					<td></td>  
					<td class="text-left"  style="width:40%;"> ดอกเบี้ยค้างชำระ </td>  
					<td class="text-right">'.$arrDetail[$i]["INTARREAR_AMT"].'</td>
				</tr>
			  ';
		}
	}
	if($sum_share > 0){
		$n++;
		for($i = 0;$i < sizeof($arrDetailDept);$i++){	
			$html .= '<tr>
						<td class="text-left">'.$n.' . ทุนเรือนหุ้น  เป็นเงิน </td>
						<td></td>
						<td class="text-right">'.$arrDetailDept[$i]["SHARE_BAL"].'</td>				
					 </tr>';
		}
	}
	if($sum_tiker > 0){  
		$n++;
		for($i = 0;$i < sizeof($arrDetailDept);$i++){	
			$html .= '<tr>
						<td class="text-left">'.$n.' . ตั๋วสัญญาใช้เงิน  เป็นเงิน </td> 
						<td>จำนวนเงิน</td>
						<td class="text-right" >'.$arrDetailDept[$i]["PRM_BAL"].'</td>				
					</tr>';
			$html .= '<tr>
						<td></td>
						<td class="text-left"> ดอกเบี้ยค้างจ่าย </td>  
						<td class="text-right">'.$arrDetailDept[$i]["PRM_INTARR"].'</td>
				 </tr>';
		}
	}

	if($sum_dept > 0){
		$n++;
		
		for($i = 0;$i < sizeof($arrDetailDept);$i++){	
			$html .= '<tr>
						<td class="text-left">'.$n.' . เงินฝากประจำ  เป็นเงิน  </td> 
						<td>จำนวนเงิน</td>
						<td class="text-right" >'.$arrDetailDept[$i]["DEPT_BAL"].'</td>				
					</tr>';
			$html .= '<tr>
						<td></td>
						<td class="text-left"> ดอกเบี้ยค้างจ่าย </td>  
						<td class="text-right">'.$arrDetailDept[$i]["DEPT_INTARR"].'</td>
				 </tr>';
		}
	}
	
	$html .= '       
		  </tbody>
		</table>
	   </div>
	   </div>
	';
	$dompdf = new DOMPDF();
	$dompdf->set_paper('A4', 'landscape');
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