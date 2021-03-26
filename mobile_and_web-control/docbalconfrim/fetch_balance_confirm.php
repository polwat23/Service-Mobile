<?php
require_once('../autoload.php');

use Dompdf\Dompdf;

$dompdf = new DOMPDF();

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'DocBalanceConfirm')){
		//$member_no = $configAS[$payload["ref_memno"]] ?? $payload["ref_memno"];
		$member_no ='100989';
		$getBalanceMaster = $conoracle->prepare("SELECT mp.PRENAME_DESC,mb.MEMB_NAME,mb.MEMB_eNAME ,'' as DEPARTMENT,'' as DEPART_GROUP , mb.ADDR_PHONE,
												(SELECT max(confirmbal_date) FROM cmconfirmbalance WHERE member_no =  :member_no ) as BALANCE_DATE
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
		$arrHeader["date_confirm"] = $lib->convertdate(date('Y-m-d',strtotime($rowBalMaster["BALANCE_DATE"])),'d M Y');
		
		$getBalanceDetail = $conoracle->prepare("SELECT (CASE WHEN cfb.BIZZ_SYSTEM = 'DEP'
												THEN dp.DEPTTYPE_DESC
												WHEN cfb.BIZZ_SYSTEM = 'LON'
												THEN ln.LOANTYPE_DESC
												WHEN cfb.BIZZ_SYSTEM = 'SHR'
												THEN  'ทุนเรือนหุ้น'  
												ELSE '' END) AS DEPTTYPE_DESC,
												(CASE WHEN cfb.BIZZ_SYSTEM = 'DEP' OR cfb.BIZZ_SYSTEM = 'LON'
												THEN cfb.BIZZACCOUNT_NO
												ELSE '' END) as DEPTACCOUNT_NO,
												cfb.BALANCE_VALUE as BALANCE_AMT,cfb.BIZZACCTYPE_CODE as  CONFIRMTYPE_CODE
												FROM cmconfirmbalance cfb LEFT JOIN dpdeptmaster dm ON cfb.BIZZACCOUNT_NO = dm.deptaccount_no
												LEFT JOIN dpdepttype dp ON dm.depttype_code = dp.depttype_code AND dm.deptgroup_code = dp.deptgroup_code   
												LEFT JOIN lccontmaster lc ON cfb.bizzaccount_no = lc.loancontract_no
												LEFT JOIN lccfloantype ln ON  lc.loantype_code = ln.loantype_code 
												WHERE cfb.member_no = :member_no  and cfb.confirmbal_date =  to_date(:balance_date,'YYYY-MM-DD') 
												ORDER BY cfb.BIZZACCTYPE_CODE ASC");
		$getBalanceDetail->execute([
			':member_no' => $member_no,
			':balance_date' => date('Y-m-d',strtotime($rowBalMaster["BALANCE_DATE"]))
		]);
		$formatDept = $func->getConstant('dep_format');
		while($rowBalDetail = $getBalanceDetail->fetch(PDO::FETCH_ASSOC)){
			$arrBalDetail = array();
			$arrBalDetail["TYPE_DESC"] = $rowBalDetail["DEPTTYPE_DESC"];
			if($rowBalDetail["CONFIRMTYPE_CODE"] == "DEP"){
				if(array_search($rowBalDetail["DEPTTYPE_DESC"],array_column($arrDetail,'TYPE_DESC')) === False){
					$arrBalDetail["BALANCE_AMT"] = $rowBalDetail["BALANCE_AMT"];
					$arrDetail[] = $arrBalDetail;
				}else{
					$arrDetail[array_search($rowBalDetail["DEPTTYPE_DESC"],array_column($arrDetail,'TYPE_DESC'))]["BALANCE_AMT"] += $rowBalDetail["BALANCE_AMT"];
				}
			}else{
				$arrBalDetail["BALANCE_AMT"] = $rowBalDetail["BALANCE_AMT"];
				$arrBalDetail["DEPTACCOUNT_NO"] = $rowBalDetail["DEPTACCOUNT_NO"];
				$arrDetail[] = $arrBalDetail;
			}
		}
		foreach($arrDetail as $key => $value){
			$arrDetail[$key]["BALANCE_AMT"] = number_format($value["BALANCE_AMT"],2);
		}
		if(isset($rowBalMaster["MEMB_NAME"]) && sizeof($arrDetail) > 0){
			$arrayPDF = GeneratePdfDoc($arrHeader,$arrDetail);
			if($arrayPDF["RESULT"]){
				$arrayResult['DATA_CONFIRM'] = "ข้าพเจ้า ".$arrHeader["full_name"]." เลขที่สมาชิก ".$member_no." ตามที่ทาง 
				สหกรณ์ออมทรัพย์การไฟฟ้าฝ่ายผลิตแห่งประเทศไทย จำกัด ได้แจ้งรายการบัญชีของข้าพเจ้า สิ้นสุด ณ วันที่ ".$lib->convertdate(date('Y-m-d',strtotime($rowBalMaster["BALANCE_DATE"])),'d m Y').
				' นั้น ข้าพเจ้าได้ตรวจสอบแล้วปรากฏว่า ข้อมูลดังกล่าว';
				$arrayResult['REPORT_URL'] = $config["URL_SERVICE"].$arrayPDF["PATH"];
				$arrayResult['BALANCE_DATE'] = date('Y-m-d',strtotime($rowBalMaster["BALANCE_DATE"]));
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
function GeneratePdfDoc($arrHeader,$arrDetail) {
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
				  border:1px solid;
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
	   <div style="margin-right:80px">
		  <table  style="width:100%;  border-collapse: collapse; margin-top:20px;">
		  <thead>
			<tr>
			  <th>ลำดับ</th>
			  <th>รายการ</th>
			  <th>จำนวนเงิน</th>
			</tr>
		  </thead>
		  <tbody>';
	//ข้อมูลในตาราง
	for($i = 0;$i < sizeof($arrDetail);$i++){
		$html .= '
			<tr>
			  <td class="text-center">'.($i+1).'</td>
			  <td class="text-centr">'.$arrDetail[$i]["TYPE_DESC"].' '.$arrDetail[$i]["DEPTACCOUNT_NO"].'</td>  
			  <td class="text-right">'.$arrDetail[$i]["BALANCE_AMT"].'</td>  
			</tr>
		  ';
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