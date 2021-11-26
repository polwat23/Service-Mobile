<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'ScholarshipRequest')){
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$conoracle->beginTransaction();
		$arrChildCheck = array();
		//new
		$checkChildAdd = $conoracle->prepare("SELECT REQUEST_STATUS, CANCEL_REMARK, CHILD_NAME, CHILD_SURNAME, CHILDCARD_ID FROM asnreqschshiponline 
															WHERE SCHOLARSHIP_YEAR = (EXTRACT(year from sysdate) +543) AND MEMBER_NO = :member_no");
		$checkChildAdd->execute([':member_no' => $member_no]);
		while($rowChildAdd = $checkChildAdd->fetch(PDO::FETCH_ASSOC)){
			$arrChildCheck[$rowChildAdd["CHILDCARD_ID"]] = $rowChildAdd["CHILDCARD_ID"];
		}
		// old
		$checkChildHave = $conoracle->prepare("SELECT asch.childcard_id as CHILDCARD_ID, mp.prename_desc||asch.child_name||'   '||asch.child_surname as CHILD_NAME
															FROM ASNREQSCHOLARSHIP asch LEFT JOIN mbucfprename mp ON  asch.childprename_code = mp.prename_code
															WHERE asch.approve_status = 1 and asch.scholarship_year = (EXTRACT(year from sysdate) +542) and asch.member_no = :member_no");
		$checkChildHave->execute([':member_no' => $member_no]);
		while($rowChild = $checkChildHave->fetch(PDO::FETCH_ASSOC)){
			$arrChildCheck[$rowChild["CHILDCARD_ID"]] = $rowChild["CHILDCARD_ID"];
		}
		
		if(isset($arrChildCheck[$dataComing["childcard_id"]])){
					$arrayResult['RESPONSE_CODE'] = "WS0121";
										$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
					$arrayResult['RESULT'] = FALSE;
					require_once('../../include/exit_footer.php');
		}else{
			$insertSchShipOnline = $conoracle->prepare("INSERT INTO asnreqschshiponline(scholarship_year, member_no, childcard_id, request_status, child_name, child_surname, lastupload_date)
																						VALUES((EXTRACT(year from sysdate) +543),:member_no,:child_id,1,:child_name,:child_surname,sysdate)");
			if($insertSchShipOnline->execute([
				':member_no' => $member_no,
				':child_id' => $dataComing["childcard_id"],
				':child_name' => $dataComing["child_name"],
				':child_surname' => $dataComing["child_surname"]
			])){
						foreach($dataComing["upload_list"] as $list){
							if(isset($list["upload_base64"]) && $list["upload_base64"] != ""){
								$subpath = $dataComing["childcard_id"].date('Ym');
								$destination = __DIR__.'/../../resource/reqwelfare/'.$subpath;
								$data_Img = explode(',',$list["upload_base64"]);
								$info_img = explode('/',$data_Img[0]);
								$ext_img = str_replace('base64','',$info_img[1]);
								if(!file_exists($destination)){
									mkdir($destination, 0777, true);
								}
								if($ext_img == 'png' || $ext_img == 'jpg' || $ext_img == 'jpeg'){
									$createImage = $lib->base64_to_img($list["upload_base64"],$list["upload_name"],$destination,null);
									if($createImage == 'oversize'){
										$deleteDocSch = $conoracle->prepare("DELETE FROM asnreqschshiponlinedet WHERE scholarship_year = (EXTRACT(year from sysdate) +543) and member_no = :member_no and childcard_id = :child_id and seq_no = :seq_no");
										$deleteDocSch->execute([
											':member_no' => $member_no,
											':child_id' => $dataComing["childcard_id"],
											':seq_no' => $list["upload_seq"]
										]);
										$insertSchShipOnlineDoc = $conoracle->prepare("INSERT INTO asnreqschshiponlinedet(scholarship_year, member_no, childcard_id, seq_no, document_desc, upload_status,filename)
																										VALUES((EXTRACT(year from sysdate) +543),:member_no,:child_id,:seq_no,:document_desc,8,:filename)");
										if($insertSchShipOnlineDoc->execute([
											':member_no' => $member_no,
											':child_id' => $dataComing["childcard_id"],
											':seq_no' => $list["upload_seq"],
											':document_desc' => $list["upload_label"],
											':filename' => null
										])){
										}else{
											$filename = basename(__FILE__, '.php');
											$logStruc = [
												":error_menu" => $filename,
												":error_code" => "WS1032",
												":error_desc" => "ไม่สามารถ Insert ลง insertSchShipOnlineDoc ได้ "."\n".$insertSchShipOnlineDoc->queryString."\n".json_encode([
													':member_no' => $member_no,
													':child_id' => $dataComing["childcard_id"],
													':seq_no' => $list["upload_seq"],
													':document_desc' => $list["upload_label"],
													':filename' => null
												]),
												":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
											];
											$log->writeLog('errorusage',$logStruc);
											$message_error = "ไม่สามารถ Insert ลง insertSchShipOnlineDoc ได้ "."\n".$insertSchShipOnlineDoc->queryString."\n".json_encode([
												':member_no' => $member_no,
												':child_id' => $dataComing["childcard_id"],
												':seq_no' => $list["upload_seq"],
												':document_desc' => $list["upload_label"],
												':filename' => null
											]);
											$lib->sendLineNotify($message_error);
											$arrayResult['RESPONSE_CODE'] = "WS1032";
											$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
											$arrayResult['RESULT'] = FALSE;
											require_once('../../include/exit_footer.php');
											
										}
									}else{
										if($createImage){
											$pathImgShowClient = $config["URL_SERVICE"]."resource/reqwelfare/".$subpath."/".$createImage["normal_path"];
											$deleteDocSch = $conoracle->prepare("DELETE FROM asnreqschshiponlinedet WHERE scholarship_year = (EXTRACT(year from sysdate) +543) and member_no = :member_no and childcard_id = :child_id and seq_no = :seq_no");
											$deleteDocSch->execute([
												':member_no' => $member_no,
												':child_id' => $dataComing["childcard_id"],
												':seq_no' => $list["upload_seq"]
											]);
											$insertSchShipOnlineDoc = $conoracle->prepare("INSERT INTO asnreqschshiponlinedet(scholarship_year, member_no, childcard_id, seq_no, document_desc, upload_status,filename)
																											VALUES((EXTRACT(year from sysdate) +543),:member_no,:child_id,:seq_no,:document_desc,1,:filename)");
											if($insertSchShipOnlineDoc->execute([
												':member_no' => $member_no,
												':child_id' => $dataComing["childcard_id"],
												':seq_no' => $list["upload_seq"],
												':document_desc' => $list["upload_label"],
												':filename' => $pathImgShowClient
											])){
												
											}else{
												$filename = basename(__FILE__, '.php');
												$logStruc = [
													":error_menu" => $filename,
													":error_code" => "WS1032",
													":error_desc" => "ไม่สามารถ Insert ลง insertSchShipOnlineDoc ได้ "."\n".$insertSchShipOnlineDoc->queryString."\n".json_encode([
														':member_no' => $member_no,
														':child_id' => $dataComing["childcard_id"],
														':seq_no' => $list["upload_seq"],
														':document_desc' => $list["upload_label"],
														':filename' => $pathImgShowClient
													]),
													":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
												];
												$log->writeLog('errorusage',$logStruc);
												$message_error = "ไม่สามารถ Insert ลง insertSchShipOnlineDoc ได้ "."\n".$insertSchShipOnlineDoc->queryString."\n".json_encode([
													':member_no' => $member_no,
													':child_id' => $dataComing["childcard_id"],
													':seq_no' => $list["upload_seq"],
													':document_desc' => $list["upload_label"],
													':filename' => $pathImgShowClient
												]);
												$lib->sendLineNotify($message_error);
												$arrayResult['RESPONSE_CODE'] = "WS1032";
												$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
												$arrayResult['RESULT'] = FALSE;
												require_once('../../include/exit_footer.php');
												
											}
										}else{
											$deleteDocSch = $conoracle->prepare("DELETE FROM asnreqschshiponlinedet WHERE scholarship_year = (EXTRACT(year from sysdate) +543) and member_no = :member_no and childcard_id = :child_id and seq_no = :seq_no");
											$deleteDocSch->execute([
												':member_no' => $member_no,
												':child_id' => $dataComing["childcard_id"],
												':seq_no' => $list["upload_seq"]
											]);
											$insertSchShipOnlineDoc = $conoracle->prepare("INSERT INTO asnreqschshiponlinedet(scholarship_year, member_no, childcard_id, seq_no, document_desc, upload_status,filename)
																											VALUES((EXTRACT(year from sysdate) +543),:member_no,:child_id,:seq_no,:document_desc,8,:filename)");
											if($insertSchShipOnlineDoc->execute([
												':member_no' => $member_no,
												':child_id' => $dataComing["childcard_id"],
												':seq_no' => $list["upload_seq"],
												':document_desc' => $list["upload_label"],
												':filename' => null
											])){
											}else{
												$filename = basename(__FILE__, '.php');
												$logStruc = [
													":error_menu" => $filename,
													":error_code" => "WS1032",
													":error_desc" => "ไม่สามารถ Insert ลง insertSchShipOnlineDoc ได้ "."\n".$insertSchShipOnlineDoc->queryString."\n".json_encode([
														':member_no' => $member_no,
														':child_id' => $dataComing["childcard_id"],
														':seq_no' => $list["upload_seq"],
														':document_desc' => $list["upload_label"],
														':filename' => null
													]),
													":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
												];
												$log->writeLog('errorusage',$logStruc);
												$message_error = "ไม่สามารถ Insert ลง insertSchShipOnlineDoc ได้ "."\n".$insertSchShipOnlineDoc->queryString."\n".json_encode([
													':member_no' => $member_no,
													':child_id' => $dataComing["childcard_id"],
													':seq_no' => $list["upload_seq"],
													':document_desc' => $list["upload_label"],
													':filename' => null
												]);
												$lib->sendLineNotify($message_error);
												$arrayResult['RESPONSE_CODE'] = "WS1032";
												$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
												$arrayResult['RESULT'] = FALSE;
												require_once('../../include/exit_footer.php');
												
											}
										}
									}
								}else if($ext_img == 'pdf'){
									$createImage = $lib->base64_to_pdf($list["upload_base64"],$list["upload_name"],$destination);
									if($createImage){
										$pathImgShowClient = $config["URL_SERVICE"]."resource/reqwelfare/".$subpath."/".$createImage["normal_path"];
										$deleteDocSch = $conoracle->prepare("DELETE FROM asnreqschshiponlinedet WHERE scholarship_year = (EXTRACT(year from sysdate) +543) and member_no = :member_no and childcard_id = :child_id and seq_no = :seq_no");
										$deleteDocSch->execute([
											':member_no' => $member_no,
											':child_id' => $dataComing["childcard_id"],
											':seq_no' => $list["upload_seq"]
										]);
										$insertSchShipOnlineDoc = $conoracle->prepare("INSERT INTO asnreqschshiponlinedet(scholarship_year, member_no, childcard_id, seq_no, document_desc, upload_status,filename)
																										VALUES((EXTRACT(year from sysdate) +543),:member_no,:child_id,:seq_no,:document_desc,1,:filename)");
										if($insertSchShipOnlineDoc->execute([
											':member_no' => $member_no,
											':child_id' => $dataComing["childcard_id"],
											':seq_no' => $list["upload_seq"],
											':document_desc' => $list["upload_label"],
											':filename' => $pathImgShowClient
										])){
										}else{
											$filename = basename(__FILE__, '.php');
											$logStruc = [
												":error_menu" => $filename,
												":error_code" => "WS1032",
												":error_desc" => "ไม่สามารถ Insert ลง insertSchShipOnlineDoc ได้ "."\n".$insertSchShipOnlineDoc->queryString."\n".json_encode([
													':member_no' => $member_no,
													':child_id' => $dataComing["childcard_id"],
													':seq_no' => $list["upload_seq"],
													':document_desc' => $list["upload_label"],
													':filename' => $pathImgShowClient
												]),
												":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
											];
											$log->writeLog('errorusage',$logStruc);
											$message_error = "ไม่สามารถ Insert ลง insertSchShipOnlineDoc ได้ "."\n".$insertSchShipOnlineDoc->queryString."\n".json_encode([
												':member_no' => $member_no,
												':child_id' => $dataComing["childcard_id"],
												':seq_no' => $list["upload_seq"],
												':document_desc' => $list["upload_label"],
												':filename' => $pathImgShowClient
											]);
											$lib->sendLineNotify($message_error);
											$arrayResult['RESPONSE_CODE'] = "WS1032";
											$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
											$arrayResult['RESULT'] = FALSE;
											require_once('../../include/exit_footer.php');
											
										}
									}else{
										$deleteDocSch = $conoracle->prepare("DELETE FROM asnreqschshiponlinedet WHERE scholarship_year = (EXTRACT(year from sysdate) +543) and member_no = :member_no and childcard_id = :child_id and seq_no = :seq_no");
										$deleteDocSch->execute([
											':member_no' => $member_no,
											':child_id' => $dataComing["childcard_id"],
											':seq_no' => $list["upload_seq"]
										]);
										$insertSchShipOnlineDoc = $conoracle->prepare("INSERT INTO asnreqschshiponlinedet(scholarship_year, member_no, childcard_id, seq_no, document_desc, upload_status,filename)
																										VALUES((EXTRACT(year from sysdate) +543),:member_no,:child_id,:seq_no,:document_desc,8,:filename)");
										if($insertSchShipOnlineDoc->execute([
											':member_no' => $member_no,
											':child_id' => $dataComing["childcard_id"],
											':seq_no' => $list["upload_seq"],
											':document_desc' => $list["upload_label"],
											':filename' => null
										])){
										}else{
											$filename = basename(__FILE__, '.php');
											$logStruc = [
												":error_menu" => $filename,
												":error_code" => "WS1032",
												":error_desc" => "ไม่สามารถ Insert ลง insertSchShipOnlineDoc ได้ "."\n".$insertSchShipOnlineDoc->queryString."\n".json_encode([
													':member_no' => $member_no,
													':child_id' => $dataComing["childcard_id"],
													':seq_no' => $list["upload_seq"],
													':document_desc' => $list["upload_label"],
													':filename' => null
												]),
												":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
											];
											$log->writeLog('errorusage',$logStruc);
											$message_error = "ไม่สามารถ Insert ลง insertSchShipOnlineDoc ได้ "."\n".$insertSchShipOnlineDoc->queryString."\n".json_encode([
												':member_no' => $member_no,
												':child_id' => $dataComing["childcard_id"],
												':seq_no' => $list["upload_seq"],
												':document_desc' => $list["upload_label"],
												':filename' => null
											]);
											$lib->sendLineNotify($message_error);
											$arrayResult['RESPONSE_CODE'] = "WS1032";
											$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
											$arrayResult['RESULT'] = FALSE;
											require_once('../../include/exit_footer.php');
											
										}
									}
								}
							}else{
								$checkHavingDet = $conoracle->prepare("SELECT filename FROM asnreqschshiponlinedet WHERE scholarship_year = (EXTRACT(year from sysdate) +543) and member_no = :member_no and childcard_id = :child_id and seq_no = :seq_no");
								$checkHavingDet->execute([
									':member_no' => $member_no,
									':child_id' => $dataComing["childcard_id"],
									':seq_no' => $list["upload_seq"]
								]);
								$rowHavingDet = $checkHavingDet->fetch(PDO::FETCH_ASSOC);
								if(isset($rowHavingDet["FILENAME"]) && $rowHavingDet["FILENAME"] != ""){
								}else{
									$deleteDocSch = $conoracle->prepare("DELETE FROM asnreqschshiponlinedet WHERE scholarship_year = (EXTRACT(year from sysdate) +543) and member_no = :member_no and childcard_id = :child_id and seq_no = :seq_no");
									$deleteDocSch->execute([
										':member_no' => $member_no,
										':child_id' => $dataComing["childcard_id"],
										':seq_no' => $list["upload_seq"]
									]);
									$insertSchShipOnlineDoc = $conoracle->prepare("INSERT INTO asnreqschshiponlinedet(scholarship_year, member_no, childcard_id, seq_no, document_desc, upload_status,filename)
																									VALUES((EXTRACT(year from sysdate) +543),:member_no,:child_id,:seq_no,:document_desc,8,:filename)");
									if($insertSchShipOnlineDoc->execute([
										':member_no' => $member_no,
										':child_id' => $dataComing["childcard_id"],
										':seq_no' => $list["upload_seq"],
										':document_desc' => $list["upload_label"],
										':filename' => null
									])){
									}else{
										$filename = basename(__FILE__, '.php');
										$logStruc = [
											":error_menu" => $filename,
											":error_code" => "WS1032",
											":error_desc" => "ไม่สามารถ Insert ลง insertSchShipOnlineDoc ได้ "."\n".$insertSchShipOnlineDoc->queryString."\n".json_encode([
												':member_no' => $member_no,
												':child_id' => $dataComing["childcard_id"],
												':seq_no' => $list["upload_seq"],
												':document_desc' => $list["upload_label"],
												':filename' => null
											]),
											":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
										];
										$log->writeLog('errorusage',$logStruc);
										$message_error = "ไม่สามารถ Insert ลง insertSchShipOnlineDoc ได้ "."\n".$insertSchShipOnlineDoc->queryString."\n".json_encode([
											':member_no' => $member_no,
											':child_id' => $dataComing["childcard_id"],
											':seq_no' => $list["upload_seq"],
											':document_desc' => $list["upload_label"],
											':filename' => null
										]);
										$lib->sendLineNotify($message_error);
										$arrayResult['RESPONSE_CODE'] = "WS1032";
										$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
										$arrayResult['RESULT'] = FALSE;
										require_once('../../include/exit_footer.php');
										
									}
								}
							}
						}
				
						$conoracle->commit();
						$arrayResult['RESULT'] = TRUE;
						require_once('../../include/exit_footer.php');
			}else{
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS1032",
					":error_desc" => "ไม่สามารถ Insert ลง asnreqschshiponline ได้ "."\n".$insertSchShipOnline->queryString."\n".json_encode([
						':member_no' => $member_no,
						':child_id' => $dataComing["childcard_id"]
					]),
					":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$message_error = "ไม่สามารถ Insert ลง asnreqschshiponline ได้ "."\n".$insertSchShipOnline->queryString."\n".json_encode([
					':member_no' => $member_no,
					':child_id' => $dataComing["childcard_id"],
					':child_name' => $dataComing["child_name"],
					':child_surname' => $dataComing["child_surname"]
				]);
				$lib->sendLineNotify($message_error);
				$arrayResult['RESPONSE_CODE'] = "WS1032";
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
?>