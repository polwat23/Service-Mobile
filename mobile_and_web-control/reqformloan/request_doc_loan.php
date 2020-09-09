<?php
require_once('../autoload.php');

use Dompdf\Dompdf;

$dompdf = new DOMPDF();

if($lib->checkCompleteArgument(['menu_component','loantype_code','request_amt','period_payment','period','loanpermit_amt'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'LoanRequestForm')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$slipSalary = null;
		$citizenCopy = null;
		$fullPathSalary = null;
		$fullPathCitizen = null;
		$directory = null;
		$getLastDocno = $conmysql->prepare("SELECT MAX(reqloan_doc) as REQLOAN_DOC FROM gcreqloan");
		$getLastDocno->execute();
		$rowLastDocno = $getLastDocno->fetch(PDO::FETCH_ASSOC);
		$getLastDoc = isset($rowLastDocno["REQLOAN_DOC"]) && $rowLastDocno["REQLOAN_DOC"] != "" ? substr($rowLastDocno["REQLOAN_DOC"],11) : 0;
		$reqloan_doc = 'D'.$dataComing["loantype_code"].date("Ymd").str_pad(intval($getLastDoc) + 1,4,0,STR_PAD_LEFT);
		if(isset($dataComing["upload_slip_salary"]) && $dataComing["upload_slip_salary"] != ""){
			$subpath = 'salary';
			$destination = __DIR__.'/../../resource/reqloan_doc/'.$reqloan_doc;
			$data_Img = explode(',',$dataComing["upload_slip_salary"]);
			$info_img = explode('/',$data_Img[0]);
			$ext_img = str_replace('base64','',$info_img[1]);
			if(!file_exists($destination)){
				mkdir($destination, 0777, true);
			}
			if($ext_img == 'png' || $ext_img == 'jpg' || $ext_img == 'jpeg'){
				$createImage = $lib->base64_to_img($dataComing["upload_slip_salary"],$subpath,$destination,null);
			}else if($ext_img == 'pdf'){
				$createImage = $lib->base64_to_pdf($dataComing["upload_slip_salary"],$subpath,$destination);
			}
			if($createImage == 'oversize'){
				$arrayResult['RESPONSE_CODE'] = "WS0008";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}else{
				if($createImage){
					$directory = __DIR__.'/../../resource/reqloan_doc/'.$reqloan_doc;
					$fullPathSalary = __DIR__.'/../../resource/reqloan_doc/'.$reqloan_doc.'/'.$createImage["normal_path"];
					$slipSalary = $config["URL_SERVICE"]."resource/reqloan_doc/".$reqloan_doc."/".$createImage["normal_path"];
				}
			}
		}
		if(isset($dataComing["upload_citizen_copy"]) && $dataComing["upload_citizen_copy"] != ""){
			$subpath = 'citizen';
			$destination = __DIR__.'/../../resource/reqloan_doc/'.$reqloan_doc;
			$data_Img = explode(',',$dataComing["upload_citizen_copy"]);
			$info_img = explode('/',$data_Img[0]);
			$ext_img = str_replace('base64','',$info_img[1]);
			if(!file_exists($destination)){
				mkdir($destination, 0777, true);
			}
			if($ext_img == 'png' || $ext_img == 'jpg' || $ext_img == 'jpeg'){
				$createImage = $lib->base64_to_img($dataComing["upload_citizen_copy"],$subpath,$destination,null);
			}else if($ext_img == 'pdf'){
				$createImage = $lib->base64_to_pdf($dataComing["upload_citizen_copy"],$subpath,$destination);
			}
			if($createImage == 'oversize'){
				unlink($fullPathSalary);
				rmdir($directory);
				$arrayResult['RESPONSE_CODE'] = "WS0008";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}else{
				if($createImage){
					$directory = __DIR__.'/../../resource/reqloan_doc/'.$reqloan_doc;
					$fullPathCitizen = __DIR__.'/../../resource/reqloan_doc/'.$reqloan_doc.'/'.$createImage["normal_path"];
					$citizenCopy = $config["URL_SERVICE"]."resource/reqloan_doc/".$reqloan_doc."/".$createImage["normal_path"];
				}
			}
		}
		$fetchData = $conoracle->prepare("SELECT mb.memb_name,mb.memb_surname,mp.prename_desc,mb.position_desc,mg.membgroup_desc,mb.salary_amount,
												md.district_desc,(sh.SHAREBEGIN_AMT * 10) AS SHAREBEGIN_AMT
												FROM mbmembmaster mb LEFT JOIN 
												mbucfprename mp ON mb.prename_code = mp.prename_code
												LEFT JOIN mbucfmembgroup mg ON mb.membgroup_code = mg.membgroup_code
												LEFT JOIN mbucfdistrict md ON mg.ADDR_AMPHUR = md.DISTRICT_CODE
												LEFT JOIN shsharemaster sh ON mb.member_no = sh.member_no
												WHERE mb.member_no = :member_no");
		$fetchData->execute([
			':member_no' => $member_no
		]);
		$rowData = $fetchData->fetch(PDO::FETCH_ASSOC);
		$pathFile = $config["URL_SERVICE"].'/resource/pdf/request_loan/'.$reqloan_doc.'.pdf?v='.time();
		$conmysql->beginTransaction();
		$InsertFormOnline = $conmysql->prepare("INSERT INTO gcreqloan(reqloan_doc,member_no,loantype_code,request_amt,period_payment,period,loanpermit_amt,receive_net,
																int_rate_at_req,salary_at_req,salary_img,citizen_img,id_userlogin,contractdoc_url)
																VALUES(:reqloan_doc,:member_no,:loantype_code,:request_amt,:period_payment,:period,:loanpermit_amt,:request_amt,:int_rate
																,:salary,:salary_img,:citizen_img,:id_userlogin,:contractdoc_url)");
		if($InsertFormOnline->execute([
			':reqloan_doc' => $reqloan_doc,
			':member_no' => $payload["member_no"],
			':loantype_code' => $dataComing["loantype_code"],
			':request_amt' => $dataComing["request_amt"],
			':period_payment' => $dataComing["period_payment"],
			':period' => $dataComing["period"],
			':loanpermit_amt' => $dataComing["loanpermit_amt"],
			':int_rate' => $dataComing["int_rate"] / 100,
			':salary' => $rowData["SALARY_AMOUNT"],
			':salary_img' => $slipSalary,
			':citizen_img' => $citizenCopy,
			':id_userlogin' => $payload["id_userlogin"],
			':contractdoc_url' => $pathFile
		])){
			$arrData = array();
			$arrData["requestdoc_no"] = $reqloan_doc;
			$arrData["full_name"] = $rowData["PRENAME_DESC"].$rowData["MEMB_NAME"].' '.$rowData["MEMB_SURNAME"];
			$arrData["name"] = $rowData["MEMB_NAME"].' '.$rowData["MEMB_SURNAME"];
			$arrData["member_no"] = $payload["member_no"];
			$arrData["position"] = $rowData["POSITION_DESC"];
			$arrData["pos_group"] = $rowData["MEMBGROUP_DESC"];
			$arrData["district_desc"] = $rowData["DISTRICT_DESC"];
			$arrData["salary_amount"] = number_format($rowData["SALARY_AMOUNT"],2);
			$arrData["share_bf"] = number_format($rowData["SHAREBEGIN_AMT"],2);
			$arrData["request_amt"] = $dataComing["request_amt"];
			$arrayPDF = GeneratePDFContract($arrData,$lib);
			if($arrayPDF["RESULT"]){
				$conmysql->commit();
				$arrayResult['REPORT_URL'] = $pathFile;
				$arrayResult['APV_DOCNO'] = $reqloan_doc;
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
				exit();
			}else{
				$conmysql->rollback();
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
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$conmysql->rollback();
			unlink($fullPathSalary);
			unlink($fullPathCitizen);
			rmdir($directory);
			$filename = basename(__FILE__, '.php');
			$logStruc = [
				":error_menu" => $filename,
				":error_code" => "WS1036",
				":error_desc" => "ขอกู้ไม่ได้เพราะ Insert ลงตาราง gcreqloan ไม่ได้"."\n"."Query => ".$InsertFormOnline->queryString."\n"."Param => ". json_encode([
					':reqloan_doc' => $reqloan_doc,
					':member_no' => $payload["member_no"],
					':loantype_code' => $dataComing["loantype_code"],
					':request_amt' => $dataComing["request_amt"],
					':period_payment' => $dataComing["period_payment"],
					':period' => $dataComing["period"],
					':loanpermit_amt' => $dataComing["loanpermit_amt"],
					':int_rate' => $dataComing["int_rate"] / 100,
					':salary' => $rowData["SALARY_AMOUNT"],
					':salary_img' => $slipSalary,
					':citizen_img' => $citizenCopy,
					':id_userlogin' => $payload["id_userlogin"],
					':contractdoc_url' => $pathFile
				]),
				":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
			];
			$log->writeLog('errorusage',$logStruc);
			$message_error = "ขอกู้ไม่ได้เพราะ Insert ลง gcreqloan ไม่ได้"."\n"."Query => ".$InsertFormOnline->queryString."\n"."Param => ". json_encode([
				':reqloan_doc' => $reqloan_doc,
				':member_no' => $payload["member_no"],
				':loantype_code' => $dataComing["loantype_code"],
				':request_amt' => $dataComing["request_amt"],
				':period_payment' => $dataComing["period_payment"],
				':period' => $dataComing["period"],
				':loanpermit_amt' => $dataComing["loanpermit_amt"],
				':int_rate' => $dataComing["int_rate"] / 100,
				':salary' => $rowData["SALARY_AMOUNT"],
				':salary_img' => $slipSalary,
				':citizen_img' => $citizenCopy,
				':id_userlogin' => $payload["id_userlogin"],
				':contractdoc_url' => $pathFile
			]);
			$lib->sendLineNotify($message_error);
			$arrayResult['RESPONSE_CODE'] = "WS1036";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
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
	echo json_encode($arrayResult);
	exit();
}
function GeneratePDFContract($data,$lib) {
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
			  padding: 0 30px;
			}
			.sub-table div{
			  padding : 5px;
			}
			</style>';
	$html .= '<div style="display: flex;text-align: center;">
			<div>
				  <img src="../../resource/logo/logo.jpg" style="width:100px"/>
				</div>
				<div style="text-align:center;width:100%;margin-left: 0px; ">
					<p style="margin-top: 110px;font-size: 20px;font-weight: bold;;">
					   ใบคําขอกู้เพื่อเหตุฉุกเฉินออนไลน์ 
					</p>
			   </div>
			   <div style="position: absolute; right: 15px; top: 140px; width:100%">
					<p style="font-size: 20px; text-align:right; ">
					   เขียนที่ Surin Saving (Mobile Application)
					</p>
			   </div>
			   <div style="position: absolute; right: 15px; top: 176px; width:100%;">
				<p style="font-size: 20px;  text-align:right; ">
				  วันที่............เดือน..........................พ.ศ..............
				 </p>
			  </div>
			  <div style="position: absolute; right: 190px; top: 173px; width:40px; ">
				<p style="font-size: 20px; ">
				  '.date('d').'
				</p>
			  </div>
			  <div style="position: absolute; right: 80px; top: 173px; width:85px;">
				<p style="font-size: 20px; ">
				'.(explode(' ',$lib->convertdate(date("Y-m-d"),"d M Y")))[1].'
				</p>
			  </div>
			  <div style="position: absolute;right: 15px; top: 173px; width:45px;  ">
				<p style="font-size: 20px; ">
				'.(date('Y') + 543).'
				</p>
			  </div>
			  </div>
			  <div style="position: absolute; left: 20px; top: 210px; width:100%">
			<p style="font-size: 20px; text-align:left">
			เรียน 
			</p>
		  </div>
		  <div style="position: absolute; left: 61px; top: 210px; width:100%">
			<p style="font-size: 20px; text-align:left">
			   คณะกรรมการดําเนินการสหกรณ์ออมทรัพย์ครูสุรินทร์ จํากัด
			</p>
		  </div>
		  <div style="position: absolute; left: 20px; top: 258px; right:0px; width:660px; font-size: 20px; ">
			  <p style="text-indent:50px;  text-align:left;">
			  ข้าพเจ้า.......................................................................... สมาชิกเลขทะเบียนที่...................................... รับราชการหรือ
			  ทํางานประจําในตําแหน่ง........................................................โรงเรียนหรือที่ทําการ.......................................................................
			  อำเภอ.........................................จังหวัดสุรินทร์ ได้รับเงินได้รายเดือน ๆ ละ...................................................บาท  มีหุ้นอยู่ใน สหกรณ์ออมทรัพย์ครูสุรินทร์ จํากัด
			  ณ วันที่ 31 ธันวาคม พ.ศ.................เป็นเงิน.........................................บาท  ขอเสนอ คําขอกู้เงินเพื่อเหตุฉุกเฉินดังต่อไปนี้
			  </p>
			  <p style="text-indent:50px; margin:0px; margin-top:-20px;  text-align:left;">
				ข้อ 1. ข้าพเจ้าขอกู้เงินของสหกรณ์จํานวน........................................บาท (....................................................................)
				โดยจะนําไปใช้เพื่อการดังต่อไปนี้ (ชี้แจงเหตุฉุกเฉินที่จําเป็นต้องขอกู้เงิน)......................................................................................
			  </p>
			  <p style="text-indent:50px; margin:0px; text-align:left;">
				ข้อ 2. ถ้าข้าพเจ้าได้รับเงินกู้ ข้าพเจ้าขอส่งชําระเงินดอกเบี้ยเป็นรายเดือน และส่งคืนเงินกู้เพื่อเหตุฉุกเฉินเต็มจํานวน พร้อมดอกเบี้ยเดือนสุดท้ายให้เสร็จสิ้นภายใน 12 เดือน
			  </p>
			  <p style="text-indent:55px; margin:0px; text-align:left;">
			  ข้อ 3. เมื่อข้าพเจ้าได้รับเงินแล้ว ข้าพเจ้ายอมรับผูกพันตามข้อบังคับของสหกรณ์ ดังนี้
			  </p>
			  <p style="text-indent:93px; margin:0px;">
				 3.1 ยินยอมให้ผู้บังคับบัญชา หรือเจ้าหน้าที่ผู้จ่ายเงินได้รายเดือนของข้าพเจ้า หักเงินได้รายเดือนของ ข้าพเจ้าตามจํานวนงวดชําระหนี้ ข้อ 2 เพื่อส่งต่อสหกรณ์
			  </p>
			  <div style="width:670px ">
				  <p style="text-indent:93px; margin:0px;">
				  3.2 ยอมให้ถือว่าในกรณีใด ๆ ดังกล่าวในข้อบังคับข้อ 17 ให้เงินกู้ที่ขอกู้ไปจากสหกรณ์เป็นอันถึงกําหนดส่งคืน โดยสิ้นเชิงพร้อมด้วยดอกเบี้ยในทันที โดยมพักคํานึงถึงกําหนดเวลาที่ตกลงไว้
				</p>
			  </div>
			 
			  <p style="text-indent:97px; margin:0px;">
				3.3. ถ้าประสงค์จะลาออกหรือย้ายจากราชการ หรืองานประจําตามข้อบังคับข้อ 41 และข้อ 43 จะแจ้งเป็น 
			  </p>
			  <p style=" margin:0px; text-align:justify">
				 หนังสือให้สหกรณ์ทราบ และจัดการชําระหนี้ซึ่งมีอยู่ตามสหกรณ์ให้เสร็จสิ้นเสียก่อน ถ้าข้าพเจ้าไม่จัดการหนี้ให้เสร็จสิ้น ตามที่กล่าวข้างต้น เมื่อข้าพเจ้าได้ลงชื่อรับเงินเดือน ค่าจ้าง เงินสะสม เงินบําเหน็จบํานาญ เงินทุนเลี้ยงชีพ หรือเงินอื่นใด ในหลักฐานที่ทางราชการหน่วยงานเจ้าของสังกัดจะจ่ายเงินให้แก่ข้าพเจ้า ข้าพเจ้ายินยอมให้เจ้าหน้าที่ผู้จ่ายเงินดังกล่าวหักเงิน ชําระหนี้ พร้อมด้วยดอกเบี้ยส่งชําระหนี้ต่อสหกรณ์ให้เสร็จสิ้นเสียก่อนได้
			  </p>
			  <p style="text-indent:50px; margin:0px;  text-align:left;">
				ข้อ 4. หากมีการบังคับใดๆ ก็ตาม ข้าพเจ้ายินยอมให้สหกรณ์โอนหุ้นของข้าพเจ้าชําระหนี้สหกรณ์ก่อน และหากมีการ ฟ้องร้องคดีต่อศาลยุติธรรม ข้าพเจ้ายินยอมให้มีการฟ้องร้อง ณ ศาลจังหวัดสุรินทร์
			  </p>
			  <p style="text-indent:50px; margin:0px;  text-align:left;">
				 หนังสือนี้ ข้าพเจ้าอ่านและเข้าใจทั้งหมดแล้ว
			  </p>
			  <p style="text-indent:50px; margin-top:40px;  text-align:center;">
				 ลงชื่อ...........................................................ผู้กู้ / ผู้รับเงิน
			  </p>
			  <p style="text-indent:232px;  margin-top:-20px; text-align:left;">
			  (...........................................................)
		   </p>
		  </div>
		  <div style="position: absolute; left: 103px; top: 271px; width:243px; text-align:center;font-weight:bold; ">
			<div style="font-size: 20px; ">
			  '.$data["full_name"].'
			</div>
		  </div>

		  <div style="position: absolute; right: 105px; top: 271px; width:127px; text-align:center;font-weight:bold; ">
			<div style="font-size: 20px; ">
			  '.$data["member_no"].'
			</div>
		  </div>

		  <div style="position: absolute; left: 137px; top: 301px; width:140px; text-align:center;font-weight:bold;  ">
			<div style="font-size: 20px; ">
			  '.$data["position"].'
			</div>  
		  </div>

		  
		  <div style="position: absolute; left: 440px; top: 301px; width:140px; text-align:center;font-weight:bold; ">
			<div style="font-size: 20px; ">
			  '.$data["pos_group"].'
			</div>  
		  </div>

		  <div style="position: absolute; left: 25px; top: 331px; width:120px; text-align:center;font-weight:bold;">
			<div style="font-size: 20px; ">
			  '.$data["district_desc"].'
			</div>  
		  </div>

		  <div style="position: absolute; right: 135px; top: 331px; width:165px; text-align:center;font-weight:bold; ">
			<div style="font-size: 20px; ">
			 '.$data["salary_amount"].'
			</div>  
		  </div>

		  <div style="position: absolute; left: 335px; top: 361px; width:56px; text-align:center;font-weight:bold;  ">
			<div style="font-size: 20px; ">
			  '.(date('Y') + 542).'
			</div>  
		  </div>

		  <div style="position: absolute; right: 154px; top: 361px; width:136px; text-align:center;font-weight:bold;">
			<div style="font-size: 20px; ">
			  '.$data["share_bf"].'
			</div>  
		  </div>

		  <div style="position: absolute; left: 289px; top: 416px; width:130px; text-align:center;font-weight:bold; ">
			<div style="font-size: 20px; ">
			  '.number_format($data["request_amt"],2).'
			</div>  
		  </div>

		  <div style="position: absolute; right: 30px; top: 416px; width:220px; text-align:center;font-weight:bold;">
			<div style="font-size: 20px; ">
			'.$lib->baht_text($data["request_amt"]).'
			</div>  
		  </div>

		  <div style="position: absolute; right: 30px; top: 446px; width:280px; text-align:center;font-weight:bold;">
			<div style="font-size: 20px; ">
			เพื่อเหตุฉุกเฉิน
			</div>  
		  </div>

		  <div style="position: absolute; left: 250px; bottom: 38px; width:195px; text-align:center;font-weight:bold; ">
			<div style="font-size: 20px; ">
			 '.$data["name"].'
			</div>  
		  </div>
		  
		  <div style="position: absolute; left: 250px; bottom: 13px; width:195px; text-align:center;font-weight:bold; ">
			<div style="font-size: 20px; ">
			 '.$data["full_name"].'
			</div>  
		  </div>
		  </tbody>
		  </table>
		  </div>
	';
	$dompdf = new DOMPDF();
	$dompdf->set_paper('A4');
	$dompdf->load_html($html);
	$dompdf->render();
	$pathfile = __DIR__.'/../../resource/pdf/request_loan';
	if(!file_exists($pathfile)){
		mkdir($pathfile, 0777, true);
	}
	$pathfile = $pathfile.'/'.$data["requestdoc_no22"].'.pdf';
	$pathfile_show = '/resource/pdf/request_loan/'.$data["requestdoc_no22"].'.pdf';
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