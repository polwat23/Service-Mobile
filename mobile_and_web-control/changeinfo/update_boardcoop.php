<?php
require_once('../autoload.php');

if ($lib->checkCompleteArgument(['unique_id'],$dataComing)) {
	if ($func->check_permission($payload["user_type"], $dataComing["menu_component"], 'SettingMemberInfo')) {
		$member_no = $configAS[$payload["ref_memno"]] ?? $payload["ref_memno"];
		$conmysql->beginTransaction();
		$arrayDataTemplate = array();
		$updateChangeData = $conmysql->prepare("UPDATE gcmanagement SET is_updateoncore = '-9' 
											WHERE is_updateoncore = '0' and  member_no = :member_no");
		if($updateChangeData->execute([
			':member_no' => $member_no,
		])){
			//region uploadfile
			//$member_no = $payload["ref_memno"];
			$getDocSystemPrefix = $conmysql->prepare("SELECT prefix_docno FROM docsystemprefix WHERE menu_component = :menu_component and is_use = '1'");
			$getDocSystemPrefix->execute([':menu_component' => "CoopInfo".$dataComing["docgrp_no"]]);
			if($getDocSystemPrefix->rowCount() > 0){
				$rowDocPrefix = $getDocSystemPrefix->fetch(PDO::FETCH_ASSOC);
				$reqloan_doc = null;
				$arrPrefixRaw = $func->PrefixGenerate($rowDocPrefix["prefix_docno"]);
				$arrPrefixSort = explode(',',$rowDocPrefix["prefix_docno"]);
				foreach($arrPrefixSort as $prefix){
					$reqloan_doc .= $arrPrefixRaw[$prefix];
				}
				if(isset($reqloan_doc) && $reqloan_doc != ""){
					$destination = __DIR__.'/../../resource/coopdocument/'.$member_no;
					$data_Img = explode(',',$dataComing["document_data"]);
					$info_img = explode('/',$data_Img[0]);
					$ext_img = str_replace('base64','',$info_img[1]);
					if(!file_exists($destination)){
						mkdir($destination, 0777, true);
					}
					if($ext_img == 'png' || $ext_img == 'jpg' || $ext_img == 'jpeg'){
						$createImage = $lib->base64_to_img($dataComing["document_data"],$reqloan_doc,$destination,null);
					}else if($ext_img == 'pdf'){
						$createImage = $lib->base64_to_pdf($dataComing["document_data"],$reqloan_doc,$destination);
					}
					if($createImage == 'oversize'){
						$arrayResult['RESPONSE_CODE'] = "WS0008";
						$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
						$arrayResult['RESULT'] = FALSE;
						require_once('../../include/exit_footer.php');
						
					}else{
						if($createImage){
							$directory = __DIR__.'/../../resource/coopdocument/'.$member_no;
							$fullPathSalary = __DIR__.'/../../resource/coopdocument/'.$member_no.'/'.$createImage["normal_path"];
							$document_path = $config["URL_SERVICE"]."resource/coopdocument/".$member_no."/".$createImage["normal_path"];
							$insertDocMaster = $conmysql->prepare("INSERT INTO doclistmaster(doc_no,docgrp_no,doc_filename,doc_aliasname,doc_type,doc_address,member_no,username)
																	VALUES(:doc_no,:docgrp_no,:doc_filename,:doc_aliasname,:doc_type,:doc_address,:member_no,:username)");
							$insertDocMaster->execute([
								':doc_no' => $reqloan_doc,
								':docgrp_no' => $dataComing["docgrp_no"],
								':doc_filename' => $reqloan_doc,
								':doc_aliasname' => $dataComing["doc_aliasname"].(date_format(date_create(),"YmdHi")),
								':doc_type' => $ext_img,
								':doc_address' => $document_path,
								':member_no' => $member_no,
								':username' => $payload["member_no"]
							]);
							$insertDocList = $conmysql->prepare("INSERT INTO doclistdetail(doc_no,member_no,new_filename,id_userlogin,username)
																	VALUES(:doc_no,:member_no,:file_name,:id_userlogin,:username)");
							$insertDocList->execute([
								':doc_no' => $reqloan_doc,
								':member_no' => $member_no,
								':file_name' => $createImage["normal_path"],
								':id_userlogin' => $payload["id_userlogin"],
								':username' => $payload["member_no"]
							]);
						}
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
				$filename = basename(__FILE__, '.php');
				$logStruc = [
					":error_menu" => $filename,
					":error_code" => "WS0063",
					":error_desc" => "ไม่พบเลขเอกสารของระบบ กรุณาสร้างชุด Format เลขเอกสาร",
					":error_device" => $dataComing["channel"].' - '.$dataComing["unique_id"].' on V.'.$dataComing["app_version"]
				];
				$log->writeLog('errorusage',$logStruc);
				$arrayResult['RESPONSE_CODE'] = "WS0063";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
			//endregion uploadfile
			
			$year = date(Y) +543;
			$arrayData = array();
			$arrayBoard = array();
			$arrayBusiness = array();
			//president 
			
			$newArr = array();
			
			$newArr["BOARD"] = $dataComing["board"];
			$newArr["PRESIDENT"] = $dataComing["president"];
			$newArr["BUSINESS"] = $dataComing["business"];
			$newArr["MANAGER"] = $dataComing["manager"];
			$newArr["OFFICER_COUNT"] = $dataComing["officer_count"]["VALUE"];
			$newArr["MEMBER_COUNT"] = $dataComing["member_count"]["VALUE"];
			$incomming = json_encode($newArr);
			
			//getCoopname 
			
			$namecoop = $conoracle->prepare("SELECT MP.PRENAME_EDESC,MB.MEMB_NAME,	MP.SUFFNAME_DESC				
											FROM mbmembmaster mb  LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
											WHERE mb.member_no = :member_no");
			$namecoop->execute([':member_no' => $member_no]);
			$rownamecoop= $namecoop->fetch(PDO::FETCH_ASSOC);
			$coopname = $rownamecoop["PRENAME_DESC"].$rownamecoop["MEMB_NAME"].' '.$rownamecoop["SUFFNAME_DESC"];
			
			$mdInfo = $conoracle->prepare("SELECT  MB.BOARD_NAME as MD_NAME,  MY.MEMBERSHIP_AMT as MD_COUNT, BDRANK_CODE as MD_TYPE,MB.ADD_NO as ADDR_NO,
											MB.ADDR_MOO as ADDR_MOO,MB.ADDR_SOI as ADDR_SOI,MB.ADDR_ROAD as ADDR_ROAD,MB.ADDR_DISTRICT AS DISTRICT_CODE,MB.ADDR_TAMBOL AS TAMBOL_CODE,
											MB.ADDR_PROVINCE AS PROVINCE_CODE,MBT.TAMBOL_DESC AS TAMBOL_REG_DESC,MBD.DISTRICT_DESC AS DISTRICT_REG_DESC,MBP.PROVINCE_DESC AS PROVINCE_REG_DESC,											
											MBT.TAMBOL_DESC AS TAMBOL_DESC,MBD.DISTRICT_DESC AS DISTRICT_DESC,MBP.PROVINCE_DESC AS PROVINCE_DESC,MB.BOARD_TEL,MB.BOARD_AGE,MB.BOARD_EMAIL,MB.PERSON_ID
											FROM MBMEMBDETYEARBOARD MB LEFT JOIN MBMEMBDETYEARBIZ MY ON MB.MEMBER_NO = MY.MEMBER_NO AND MB.BIZ_YEAR  = MY.BIZ_YEAR
											LEFT JOIN MBUCFTAMBOL MBT ON MB.ADDR_TAMBOL = MBT.TAMBOL_CODE
											LEFT JOIN MBUCFDISTRICT MBD ON MB.ADDR_DISTRICT = MBD.DISTRICT_CODE
											LEFT JOIN MBUCFPROVINCE MBP ON MB.ADDR_PROVINCE = MBP.PROVINCE_CODE
											WHERE  MB.MEMBER_NO = :member_no  AND MB.BIZ_YEAR = :year");
			$mdInfo->execute([':member_no' => $payload["ref_memno"] ,':year' =>$year ]);
			while($rowUser = $mdInfo->fetch(PDO::FETCH_ASSOC)){
				$arrayMd = array();
				$arrayMd["BOARD_TEL"] = $rowUser["BOARD_TEL"];
				$arrayMd["BOARD_EMAIL"] = $rowUser["BOARD_EMAIL"];
				$arrayMd["PERSON_ID"] = $rowUser["PERSON_ID"];
				$arrayMd["ADDR_NO"] = $rowUser["ADDR_NO"];
				$arrayMd["ADDR_MOO"] = $rowUser["ADDR_MOO"];
				$arrayMd["ADDR_SOI"] = $rowUser["ADDR_SOI"];
				$arrayMd["ADDR_ROAD"] = $rowUser["ADDR_ROAD"];
				$arrayMd["DISTRICT_CODE"] = $rowUser["DISTRICT_CODE"];
				$arrayMd["TAMBOL_CODE"] = $rowUser["TAMBOL_CODE"];
				$arrayMd["PROVINCE_CODE"] = $rowUser["PROVINCE_CODE"];	
				$arrayMd["TAMBOL_DESC"] = $rowUser["TAMBOL_REG_DESC"];	
				$arrayMd["DISTRICT_DESC"] = $rowUser["DISTRICT_REG_DESC"];	
				$arrayMd["PROVINCE_DESC"] = $rowUser["PROVINCE_REG_DESC"];				
				$arrayMd["MD_NAME"] = $rowUser["MD_NAME"];
			
				
				if($rowUser["MD_TYPE"] == "01"){			//ประธาน
					$arrayChairman = $arrayMd;
				}else if($rowUser["MD_TYPE"] == "09"){	//ผู้จัดการ
					$arrayManager = $arrayMd;
				}else if($rowUser["MD_TYPE"] == "08"){	//คณะกรรมการ
					$arrayBoard[] = $arrayMd;
				}else if($rowUser["MD_TYPE"] == "12"){	//ผู้ตรวจสอบกิจการ
					$arrayBusiness[] = $arrayMd;
				}
					$arrayMember = $rowUser["MD_COUNT"];
					$arrayOfficer  = $rowUser["MD_COUNT"];			

				$arrayData["MEMBER_COUNT"] = $arrayMember;  //จํานวนสมาชิก
				$arrayData["PRESIDENT"] = $arrayChairman;		//ประธานกรรมการ
				$arrayData["BOARD"] =  $arrayBoard;		//รายชื่อคณะกรรมการ
				$arrayData["BUSINESS"] = $arrayBusiness;		//ผู้ตรวจสอบกิจการ
				$arrayData["MANAGER"] = $arrayManager;		//ผู้จัดการ
				$arrayData["OFFICER_COUNT"] = $arrayOfficer;		//เจ้าหน้าที่สหกรณ์			
			}
			$old_data = json_encode($arrayData);
			
			if (isset($incomming) && $incomming != "" ) {
				$insertPresData = $conmysql->prepare("INSERT INTO gcmanagement(member_no, old_data, incoming_data,username,document_path) 
											VALUES (:member_no,:old_data,:incomming,:username,:document_path)");
				if ($insertPresData->execute([':member_no' => $member_no,
					':old_data' => $old_data,
					':incomming' => $incomming,
					':username'=> $payload["member_no"],
					':document_path' => (isset($dataComing["attachfile"]) && $dataComing["attachfile"] != "") ? $dataComing["attachfile"] : $document_path
				])) {
					$message = $coopname."(".$member_no.") "." มีเเก้ไขข้อมูลบริหารจัดการ";
					$lib->sendLineNotify($message ,$config["LINE_NOTIFY_FSCT"]);
					$arrayResult["RESULT_EDIT"] = TRUE;
				} else {
					$arrayResult["RESULT_EDIT"] = FALSE;
				}
			}
			
			if (isset($arrayResult["RESULT_EDIT"]) && !$arrayResult["RESULT_EDIT"]) {
				$conmysql->rollback();
				$arrayResult['RESPONSE_CODE'] = "WS1043";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			} else {
				$conmysql->commit();
				$arrayResult['RESULT'] = TRUE;
				$arrayResult['DATA'] = json_encode($incomming);
				require_once('../../include/exit_footer.php');
			}
		}else{
			$arrayResult["RESULT_EDIT"] = FALSE;
			$conmysql->rollback();
			$arrayResult['RESPONSE_CODE'] = "WS1043";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			require_once('../../include/exit_footer.php');
		}
	} else {
		$arrayResult['RESPONSE_CODE'] = "WS0006";
		$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../include/exit_footer.php');
	}
} else {
	$filename = basename(__FILE__, '.php');
	$logStruc = [
		":error_menu" => $filename,
		":error_code" => "WS4004",
		":error_desc" => "ส่ง Argument มาไม่ครบ " . "\n" . json_encode($dataComing),
		":error_device" => $dataComing["channel"] . ' - ' . $dataComing["unique_id"] . ' on V.' . $dataComing["app_version"]
	];
	$log->writeLog('errorusage', $logStruc);
	$message_error = "ไฟล์ " . $filename . " ส่ง Argument มาไม่ครบมาแค่ " . "\n" . json_encode($dataComing);
	$lib->sendLineNotify($message_error);
	$arrayResult['RESPONSE_CODE'] = "WS4004";
	$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../include/exit_footer.php');
}
