<?php
require_once('../autoload.php');
use Dompdf\Dompdf;
$dompdf = new DOMPDF();

if($lib->checkCompleteArgument(['form_value_root_','api_token','unique_id'],$dataComing)){
	$arrPayload = $auth->check_apitoken($dataComing["api_token"],$config["SECRET_KEY_JWT"]);
	if(!$arrPayload["VALIDATE"]){
		$filename = basename(__FILE__, '.php');
		$logStruc = [
			":error_menu" => $filename,
			":error_code" => "WS0001",
			":error_desc" => "ไม่สามารถยืนยันข้อมูลได้"."\n".json_encode($dataComing),
			":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
		];
		$log->writeLog('errorusage',$logStruc);
		$arrayResult['RESPONSE_CODE'] = "WS0001";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(401);
		require_once('../../include/exit_footer.php');
		
	}
	
	if(false){
		//$arrayResult['RESPONSE_CODE'] = "WS0020";
		//$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		require_once('../../include/exit_footer.php');
		
	}else{
		$arrDocReq = array();
		$emp_no = $dataComing["form_value_root_"]["EMP_NO"]["VALUE"] ?? "";
		$card_id = str_replace('-', '', $dataComing["form_value_root_"]["MEMBER_CARDID"]["VALUE"] ?? "");
		
		$getReqDocument = $conmysql->prepare("SELECT reqdoc_no, document_url, req_status FROM gcreqdoconline 
											WHERE documenttype_code = 'RRGT' AND member_no = :emp_no AND req_status not IN('-9','9')");
		$getReqDocument->execute([':emp_no' => $emp_no]);
		while($rowPrename = $getReqDocument->fetch(PDO::FETCH_ASSOC)){
			$docArr = array();
			$docArr["REQDOC_NO"] = $rowPrename["reqdoc_no"];
			$docArr["DOCUMENT_URL"] = $rowPrename["document_url"];
			$docArr["REQ_STATUS"] = $rowPrename["req_status"];
			$arrDocReq[] = $docArr;
		}
		
		if(count($arrDocReq) < 1){
			//memmber register list
			$fetchMemberRegis = $conmssql->prepare("SELECT TOP 1 EMP_NO, CARD_PERSON,EMP_NM,EMP_SURNM,SALARY_AMT,EXPENSE_AMT 
												FROM IXPEMPSALARYMASDATA 
												WHERE EMP_NO = :emp_no AND  CARD_PERSON = :card_id
												ORDER BY OPERATE_DATE DESC");
			$fetchMemberRegis->execute([
				':emp_no' => $emp_no,
				':card_id' => $card_id,
			]);
			
			$rowInfoRegister = $fetchMemberRegis->fetch(PDO::FETCH_ASSOC);
			
			$fetchMemberInfo = $conmssql->prepare("SELECT MB.MEMBER_NO, MB.MEMB_NAME, MB.MEMB_SURNAME, MP.PRENAME_SHORT,
											MB.SALARY_AMOUNT, MB.MEMBER_STATUS, MB.CARD_PERSON FROM MBMEMBMASTER MB
											LEFT JOIN MBUCFPRENAME MP ON MB.PRENAME_CODE = MP.PRENAME_CODE
											WHERE MB.SALARY_ID = :emp_no
											AND MB.CARD_PERSON = :card_id AND mb.resign_status != '1'");
			$fetchMemberInfo->execute([
				':emp_no' => $emp_no,
				':card_id' => $card_id,
			]);
			//AND MB.MEMBER_STATUS = '-1' 
			$rowInfoMobile = $fetchMemberInfo->fetch(PDO::FETCH_ASSOC);
			if(isset($rowInfoRegister["EMP_NO"])){
				if(!isset($rowInfoMobile["MEMBER_NO"])){
					$shr_period_payment = $dataComing["form_value_root_"]["SHARE_PERIOD_PAYMENT"]["VALUE"] ?? "";
					
					$mthother_amt = $rowInfoRegister["EXPENSE_AMT"] ?? 0;
					$getSettlement = $conmysql->prepare("SELECT settlement_amt, salary FROM gcmembsettlement WHERE is_use = '1' AND emp_no = :emp_no AND MONTH(month_period) = MONTH(:month_period) AND YEAR(month_period) = YEAR(:month_period)");
					$getSettlement->execute([
						':emp_no' => $emp_no,
						':month_period' => $dataComing["form_value_root_"]["EFFECT_MONTH"]["VALUE"],
					]);
					$rowSettlement = $getSettlement->fetch(PDO::FETCH_ASSOC);
					$other_amt = $rowSettlement["settlement_amt"] ?? $mthother_amt;
					if($shr_period_payment % 10 != 0){
						$arrayResult['RESPONSE_CODE'] = "";
						$arrayResult['RESPONSE_MESSAGE'] = "ค่าหุ้นรายเดือนไม่ถูกต้อง เนื่องจากหุ้นมีมูลค่าหุ้นละ 10 บาท กรุณาตรวจสอบค่าหุ้นรายเดือนและลองใหม่อีกครั้ง";
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
					}else if($shr_period_payment < 200){
						$arrayResult['RESPONSE_CODE'] = "";
						$arrayResult['RESPONSE_MESSAGE'] = "ค่าหุ้นรายเดือนขั้นต่ำ 200 บาท กรุณาตรวจสอบค่าหุ้นรายเดือนและลองใหม่อีกครั้ง";
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
					}else if(($rowInfoRegister["SALARY_AMT"] - $other_amt) < $shr_period_payment){
						$arrayResult['RESPONSE_CODE'] = "";
						$arrayResult['RESPONSE_MESSAGE'] = "ค่าหุ้นรายเดือนเกินเงินเดือนคงเหลือสุทธิ กรุณาตรวจสอบค่าหุ้นรายเดือนและลองใหม่อีกครั้ง";
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
					}
					
					$effect_month_arr = $dataComing["form_value_root_"]["EFFECT_MONTH"]["VALUE"] ? $lib->convertdate($dataComing["form_value_root_"]["EFFECT_MONTH"]["VALUE"],"D m Y") : "";
					$effect_month_arr = explode(" ", $effect_month_arr);
					$effect_month = ($effect_month_arr[1] ?? "")." ".($effect_month_arr[2] ?? "");
					$arrGroupDetail = array();
					$arrGroupDetail["EMP_NO"] = $emp_no;
					$arrGroupDetail["MEMBER_FULLNAME"] =  $rowInfoRegister["EMP_NM"].(isset($rowInfoRegister["EMP_SURNM"]) ? (' '.$rowInfoRegister["EMP_SURNM"]) : '');
					$arrGroupDetail["MEMBER_CARDID"] =  $card_id;
					$arrGroupDetail["MEMBER_MOBILEPHONE"] =  $dataComing["form_value_root_"]["MEMBER_MOBILEPHONE"]["VALUE"] ?? "";
					$arrGroupDetail["MEMBER_BIRTHDATE"] = $dataComing["form_value_root_"]["MEMBER_BIRTHDATE"]["VALUE"] ? $lib->convertdate($dataComing["form_value_root_"]["MEMBER_BIRTHDATE"]["VALUE"],"D m Y") : "";
					$arrGroupDetail["ADDRESS_ADDR_NO"] =  $dataComing["form_value_root_"]["ADDRESS_ADDR_NO"]["VALUE"] ?? "";
					$arrGroupDetail["ADDRESS_VILLAGE_NO"] =  $dataComing["form_value_root_"]["ADDRESS_VILLAGE_NO"]["VALUE"] ?? "";
					$arrGroupDetail["ADDRESS_ROAD"] =  $dataComing["form_value_root_"]["ADDRESS_ROAD"]["VALUE"] ?? "";
					$arrGroupDetail["ADDRESS_SOI"] =  $dataComing["form_value_root_"]["ADDRESS_SOI"]["VALUE"] ?? "";
					$arrGroupDetail["ADDRESS_PROVINE_CODE"] =  $dataComing["form_value_root_"]["ADDRESS_PROVINE_CODE"]["VALUE"]["PROVINCE_DESC"] ?? "";
					$arrGroupDetail["ADDRESS_DISTRICT_CODE"] =  $dataComing["form_value_root_"]["ADDRESS_DISTRICT_CODE"]["VALUE"]["DISTRICT_DESC"] ?? "";
					$arrGroupDetail["ADDRESS_TAMBOL_CODE"] =  $dataComing["form_value_root_"]["ADDRESS_TAMBOL_CODE"]["VALUE"]["TAMBOL_DESC"] ?? "";
					$arrGroupDetail["SHARE_PERIOD_PAYMENT"] =  $dataComing["form_value_root_"]["SHARE_PERIOD_PAYMENT"]["VALUE"] ?? "";
					$arrGroupDetail["BENEF_NAME_1"] =  $dataComing["form_value_root_"]["BENEF_NAME_1"]["VALUE"] ?? "";
					$arrGroupDetail["BENEF_NAME_2"] =  $dataComing["form_value_root_"]["BENEF_NAME_2"]["VALUE"] ?? "";
					$arrGroupDetail["BENEF_NAME_3"] =  $dataComing["form_value_root_"]["BENEF_NAME_3"]["VALUE"] ?? "";
					$arrGroupDetail["BENEF_NAME_4"] =  $dataComing["form_value_root_"]["BENEF_NAME_4"]["VALUE"] ?? "";
					$arrGroupDetail["BENEF_OPTION"] =  $dataComing["form_value_root_"]["BENEF_OPTION"]["VALUE"] ?? "";
					$arrGroupDetail["OPTION_VALUE"] =  $dataComing["form_value_root_"]["BENEF_OPTION"]["OPTION_VALUE"][$dataComing["form_value_root_"]["BENEF_OPTION"]["VALUE"]];
					$arrGroupDetail["EFFECT_MONTH"] =  $effect_month;
								
					if($dataComing["is_confirm"]){
						$getDocSystemPrefix = $conmysql->prepare("SELECT prefix_docno FROM docsystemprefix WHERE menu_component = :menu_component and is_use = '1'");
						$getDocSystemPrefix->execute([':menu_component' => 'RRGT']);
						
						$reqdoc_no = null;
						if($getDocSystemPrefix->rowCount() > 0){
							$rowDocPrefix = $getDocSystemPrefix->fetch(PDO::FETCH_ASSOC);
							$arrPrefixRaw = $func->PrefixGenerate($rowDocPrefix["prefix_docno"]);
							$arrPrefixSort = explode(',',$rowDocPrefix["prefix_docno"]);
							foreach($arrPrefixSort as $prefix){
								$reqdoc_no .= $arrPrefixRaw[$prefix];
							}
						}
						
						if(isset($reqdoc_no) && $reqdoc_no != ""){
							$getControlDoc = $conmysql->prepare("SELECT docgrp_no FROM docgroupcontrol WHERE is_use = '1' and menu_component = :menu_component");
							$getControlDoc->execute([':menu_component' => 'RRGT']);
							$rowConDoc = $getControlDoc->fetch(PDO::FETCH_ASSOC);
							
							$pathFile = $config["URL_SERVICE"].'/resource/pdf/req_document/'.$reqdoc_no.'.pdf?v='.time();
							$conmysql->beginTransaction();
							$InsertFormOnline = $conmysql->prepare("INSERT INTO gcreqdoconline(reqdoc_no, member_no, documenttype_code, form_value, document_url) 
																VALUES (:reqdoc_no, :member_no, :documenttype_code, :form_value,:document_url)");
							if($InsertFormOnline->execute([
								':reqdoc_no' => $reqdoc_no,
								':member_no' => $emp_no,
								':documenttype_code' => 'RRGT',
								':form_value' => json_encode($dataComing["form_value_root_"]),
								':document_url' => $pathFile,
							])){
								$arrGroupDetail["REQDOC_NO"] = $reqdoc_no;
								
								include('form_request_document_RRGT.php');
								$arrayPDF = GenerateReport($arrGroupDetail,$lib);
								if($arrayPDF["RESULT"]){
									$arrayResult['REPORT_URL'] = $config["URL_SERVICE"].$arrayPDF["PATH"];
									
									$insertDocMaster = $conmysql->prepare("INSERT INTO doclistmaster(doc_no,docgrp_no,doc_filename,doc_type,doc_address,member_no)
																			VALUES(:doc_no,:docgrp_no,:doc_filename,'pdf',:doc_address,:member_no)");
									$insertDocMaster->execute([
										':doc_no' => $reqdoc_no,
										':docgrp_no' => $rowConDoc["docgrp_no"],
										':doc_filename' => $reqdoc_no,
										':doc_address' => $pathFile,
										':member_no' => $emp_no,
									]);
									$insertDocList = $conmysql->prepare("INSERT INTO doclistdetail(doc_no,member_no,new_filename,id_userlogin)
																			VALUES(:doc_no,:member_no,:file_name,:id_userlogin)");
									$insertDocList->execute([
										':doc_no' => $reqdoc_no,
										':member_no' => $emp_no,
										':file_name' => $reqdoc_no.'.pdf',
										':id_userlogin' => $payload["id_userlogin"]
									]);
									$conmysql->commit();
									
									$arrayResult['REQDOC_NO'] = $reqdoc_no;
									$arrayResult['RESULT'] = TRUE;
									require_once('../../include/exit_footer.php');
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
									require_once('../../include/exit_footer.php');
									
								}
							}else{
								$conmysql->rollback();
								$filename = basename(__FILE__, '.php');
								$logStruc = [
									":error_menu" => $filename,
									":error_code" => "WS1036",
									":error_desc" => "สมัครสมาชิกสหกรณ์ไม่ได้เพราะ Insert ลงตาราง gcreqdoconline ไม่ได้"."\n"."Query => ".$InsertFormOnline->queryString."\n"."Param => ". json_encode([
										':reqdoc_no' => $reqdoc_no,
										':member_no' => $emp_no,
										':documenttype_code' => 'RRGT',
										':form_value' => json_encode($dataComing["form_value_root_"]),
										':document_url' => $pathFile,
									]),
									":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
								];
								$log->writeLog('errorusage',$logStruc);
								$message_error = "สมัครสมาชิกสหกรณ์ไม่ได้เพราะ Insert ลง gcreqdoconline ไม่ได้"."\n"."Query => ".$InsertFormOnline->queryString."\n"."Param => ". json_encode([
									':reqdoc_no' => $reqdoc_no,
									':member_no' => $emp_no,
									':documenttype_code' => 'RRGT',
									':form_value' => json_encode($dataComing["form_value_root_"]),
									':document_url' => $pathFile,
								]);
								$lib->sendLineNotify($message_error);
								$arrayResult['RESPONSE_CODE'] = "WS1036";
								$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
								$arrayResult['RESULT'] = FALSE;
								require_once('../../include/exit_footer.php');
							}
						}else{
							$filename = basename(__FILE__, '.php');
							$logStruc = [
								":error_menu" => $filename,
								":error_code" => "WS0063",
								":error_desc" => "เลขเอกสารเป็นค่าว่าง ไม่สามารถสร้างเลขเอกสารเป็นค่าว่างได้",
								":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
							];
							$log->writeLog('errorusage',$logStruc);
							$message_error = "เลขเอกสารเป็นค่าว่าง ไม่สามารถสร้างเลขเอกสารเป็นค่าว่างได้";
							$lib->sendLineNotify($message_error);
							$arrayResult['RESPONSE_CODE'] = "WS0063";
							$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
							$arrayResult['RESULT'] = FALSE;
							require_once('../../include/exit_footer.php');
						}
					}else{
						$arrayResult['REGISTER_DATA'] = $arrGroupDetail;
						$arrayResult['RESULT'] = TRUE;
						require_once('../../include/exit_footer.php');
					}
				}else{
					$arrayResult['RESPONSE_CODE'] = "";
					$arrayResult['RESPONSE_MESSAGE'] = "ท่านเป็นสมาชิกสหกรณ์แล้วจึงไม่สามารถสมัครซ้ำได้ กรุณาตรวจสอบรหัสพนักงานและลองใหม่อีกครั้ง";
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
				}
			}else{
				$arrayResult['RESPONSE_CODE'] = "";
				$arrayResult['RESPONSE_MESSAGE'] = "รหัสพนักงานหรือหมายเลขบัตรประชนไม่ถูกต้อง กรุณาตรวจสอบข้อมูลและลองใหม่อีกครั้ง";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		}else{
			$getReqDocumentApv = $conmysql->prepare("SELECT reqdoc_no, document_url, req_status FROM gcreqdoconline 
											WHERE documenttype_code = 'RRGT' AND member_no = :emp_no AND req_status IN('1')");
			$getReqDocumentApv->execute([':emp_no' => $emp_no]);
			$rowAPV = $getReqDocumentApv->fetch(PDO::FETCH_ASSOC);
			if(isset($rowAPV["req_status"]) && $rowAPV["req_status"] == "1"){
				$arrayResult['DOCUMMMENT_REQ'] = $arrDocReq;
				$arrayResult['RESPONSE_CODE'] = "";
				$arrayResult['RESPONSE_MESSAGE'] = "สหกรณ์ได้อนุมัติใบคำขอของท่านแล้ว สามารถตรวจสอบเลขสมาชิกได้จากหน้าจอเข้าสู่ระบบ หากมีคำถามเพิ่มเติมกรุณาติดต่อสหกรณ์";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}else{
				$arrayResult['DOCUMMMENT_REQ'] = $arrDocReq;
				$arrayResult['RESPONSE_CODE'] = "";
				$arrayResult['RESPONSE_MESSAGE'] = "ท่านได้ส่งใบคำขอไปแล้วและอยู่ในระหว่างดำเนินการ หากมีคำถามเพิ่มเติมกรุณาติดต่อสหกรณ์";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		}
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
?>