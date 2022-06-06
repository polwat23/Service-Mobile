<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SettingMemberInfo')){
		$member_no = $configAS[$payload["ref_memno"]] ?? $payload["ref_memno"];
		$arrayDataTemplate = array();
		//region uploadfile
		$approve_editcoop = $func->getConstant('approve_edit_coop');
		$member_no = $payload["ref_memno"];
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
	
		$inputgroup_type = $dataComing["inputgroup_type"]; //type  ว่าเเก้ไขอะไรไป
		
		//ที่อยู่เก่า
		$memberInfo = $conoracle->prepare("SELECT 
											mb.ADDR_NO as ADDR_NO,
											mb.ADDR_MOO as ADDR_MOO,
											mb.ADDR_SOI as ADDR_SOI,
											mb.ADDR_VILLAGE as ADDR_VILLAGE,
											mb.ADDR_ROAD as ADDR_ROAD,
											MB.DISTRICT_CODE AS DISTRICT_CODE,
											MB.PROVINCE_CODE AS PROVINCE_CODE,
											MB.ADDR_POSTCODE AS ADDR_POSTCODE,
											MB.TAMBOL_CODE AS TAMBOL_CODE
											FROM mbmembmaster mb
											LEFT JOIN MBUCFTAMBOL MBT ON mb.TAMBOL_CODE = MBT.TAMBOL_CODE
											LEFT JOIN MBUCFDISTRICT MBD ON mb.DISTRICT_CODE = MBD.DISTRICT_CODE
											LEFT JOIN MBUCFPROVINCE MBP ON mb.PROVINCE_CODE = MBP.PROVINCE_CODE
											WHERE mb.member_no = :member_no ");
		$memberInfo->execute([':member_no' => $member_no]);
		$rowMember = $memberInfo->fetch(PDO::FETCH_ASSOC);
		$arrOldAddress["addr_no"] = $rowMember["ADDR_NO"] == null ? "" : $rowMember["ADDR_NO"];
		$arrOldAddress["addr_moo"] = $rowMember["ADDR_MOO"] == null ? "" : $rowMember["ADDR_MOO"];
		$arrOldAddress["addr_soi"] = $rowMember["ADDR_SOI"] == null ? "" : $rowMember["ADDR_SOI"];
		$arrOldAddress["addr_village"] = $rowMember["ADDR_VILLAGE"]  ==  null ? "" : $rowMember["ADDR_VILLAGE"];
		$arrOldAddress["addr_road"] = $rowMember["ADDR_ROAD"] == null ? "" : $rowMember["ADDR_ROAD"];
		$arrOldAddress["district_code"] = $rowMember["DISTRICT_CODE"] == null ? "" : $rowMember["DISTRICT_CODE"];
		$arrOldAddress["addr_postcode"] = $rowMember["ADDR_POSTCODE"] == null ? "" : $rowMember["ADDR_POSTCODE"];
		$arrOldAddress["tambol_code"] = $rowMember["TAMBOL_CODE"] == null ? "" : $rowMember["TAMBOL_CODE"];
		$arrOldAddress["province_code"] = $rowMember["PROVINCE_CODE"] == null ? "" : $rowMember["PROVINCE_CODE"];
	
		//profile สหกรณฺ์
		$member_info = $conoracle->prepare("SELECT 
											MB.ADDR_EMAIL AS ADDR_EMAIL,
											MB.ADDR_PHONE AS ADDR_PHONE,
											MP.PRENAME_DESC,
											MB.MEMB_NAME,
											MP.SUFFNAME_DESC,
											MB.ADDR_FAX AS ADDR_FAX,
											TO_CHAR(MB.COOPREGIS_DATE, 'YYYY-MM-DD') as COOPREGIS_DATE,
											MB.COOPREGIS_NO as COOPREGIS_NO,
											MB.MEMB_REGNO as MEMB_REGNO,
											MB.TAX_ID as TAX_ID,
											TO_CHAR(MB.ACCYEARCLOSE_DATE, 'YYYY-MM-DD') as ACCYEARCLOSE_DATE
											FROM mbmembmaster mb  LEFT JOIN mbucfprename mp ON mb.prename_code = mp.prename_code
											WHERE mb.member_no = :member_no ");
		$member_info->execute([':member_no' => $member_no]);
		$rowMember_info = $member_info->fetch(PDO::FETCH_ASSOC);
		$arrMember["addr_email"] = $rowMember_info["ADDR_EMAIL"];
		$arrMember["addr_phone"] = $rowMember_info["ADDR_PHONE"];
		$arrMember["addr_fax"] = $rowMember_info["ADDR_FAX"];
		$arrMember["coopregis_date"] = $rowMember_info["COOPREGIS_DATE"];
		$arrMember["coopregis_no"] = $rowMember_info["COOPREGIS_NO"];
		$arrMember["memb_regno"] = $rowMember_info["MEMB_REGNO"];
		$arrMember["accyearclose_date"] = $rowMember_info["ACCYEARCLOSE_DATE"];
		$arrMember["tax_id"] = $rowMember_info["TAX_ID"];
		$arrMember["full_name"] = $rowMember_info["PRENAME_DESC"].$rowMember_info["MEMB_NAME"].' '.$rowMember_info["SUFFNAME_DESC"];
		
		$conmysql->beginTransaction();
		
		
		$updateChangeData = $conmysql->prepare("UPDATE gcmembereditdata SET is_updateoncore = '-9' 
											WHERE is_updateoncore = '0' and  member_no = :member_no and inputgroup_type = 'editdata'");
		if($updateChangeData->execute([
			':member_no' => $member_no,
		])){
			$insertChangeData = $conmysql->prepare("INSERT INTO gcmembereditdata(member_no,old_data,incoming_data,old_email, new_email, old_tel, new_tel, old_fax, new_fax, old_website, new_website,
													old_coopregis_date, new_coopregis_date, old_accyearclose_date, new_accyearclose_date, old_coopregis_no, new_coopregis_no, 
													old_memb_regno, new_memb_regno, old_tax_id, new_tax_id,inputgroup_type,username,document_path)
													VALUES(:member_no,:old_address,:address,:old_email,:new_email,:old_tel,:new_tel,:old_fax,:new_fax,:old_website,:new_website,
												   :old_coopregis_date,:new_coopregis_date, :old_accyearclose_date, :new_accyearclose_date,:old_coopregis_no,:new_coopregis_no,
												   :old_memb_regno,:new_memb_regno,:old_tax_id,:new_tax_id,:inputgroup_type,:username,:document_path)");
			if($insertChangeData->execute([
				':member_no' => $member_no,
				':old_address' => json_encode($arrOldAddress),
				':address' => json_encode($dataComing["address"]),
				':old_email' => $rowMember_info["ADDR_EMAIL"],
				':new_email' => $dataComing["addr_reg_email"],
				':old_tel' => $arrMember["addr_phone"],
				':new_tel' => $dataComing["addr_phone"],
				':old_fax' => $arrMember["addr_fax"],
				':new_fax' => $dataComing["addr_fax"],
				':old_website' => $rowMember_info["WEBSITE"],
				':new_website' => $dataComing["website"],
				':old_coopregis_date' => $rowMember_info["COOPREGIS_DATE"],
				':new_coopregis_date' => $dataComing["coopregis_date"] == "" ? null : $dataComing["coopregis_date"],
				':old_accyearclose_date' => $arrMember["accyearclose_date"],
				':new_accyearclose_date' => $dataComing["accyearclose_date"] == "" ? null : $dataComing["accyearclose_date"],
				':old_coopregis_no' => $rowMember_info["COOPREGIS_NO"],
				':new_coopregis_no' => $dataComing["coopregis_no"],
				':old_memb_regno' => $rowMember_info["MEMB_REGNO"],
				':new_memb_regno' => $dataComing["memb_regno"],
				':old_tax_id' => $rowMember_info["TAX_ID"],
				':new_tax_id' => $dataComing["tax_id"],
				':inputgroup_type' => $inputgroup_type,
				':username'=> $payload["member_no"],
				':document_path' => (isset($dataComing["attachfile"]) && $dataComing["attachfile"] != "") ? $dataComing["attachfile"] : $document_path
			])){
				$conmysql->commit();
				
				//เช็คเงื่อนไข ไม่ต้องมีการอนุมัติ
				if($approve_editcoop == "0"){
					$updateChangeData = $conmysql->prepare("UPDATE gcmembereditdata SET is_updateoncore = '1' 
											WHERE is_updateoncore = '0' and  member_no = :member_no and inputgroup_type = 'editdata'");
					if($updateChangeData->execute([
						':member_no' => $member_no,
					])){
						$update_coop = $conoracle->prepare("UPDATE mbmembmaster SET addr_no =:addr_no ,addr_moo =:addr_moo ,addr_village =:addr_village ,addr_soi =:addr_soi , addr_road =:addr_road ,
								tambol_code =:tambol_code,district_code =:district_code ,province_code =:province_code, addr_postcode=:addr_postcode ,coopregis_no =:coopregis_no,
								coopregis_date =  TRUNC(TO_DATE(:coopregis_date,'yyyy-mm-dd hh24:mi:ss')),accyearclose_date = TRUNC(TO_DATE(:accyearclose_date ,'yyyy-mm-dd hh24:mi:ss')) , 
								memb_regno =:memb_regno ,addr_email =:email ,tax_id =:tax_id ,addr_phone =:tel ,addr_fax =:addr_fax
								WHERE   member_no = :member_no");
							if($update_coop->execute([
								':addr_no' => $dataComing["address"]["addr_no"],
								':addr_moo' => $dataComing["address"]["addr_moo"],
								':addr_village' => $dataComing["address"]["addr_village"],
								':addr_soi' => $dataComing["address"]["addr_soi"],
								':addr_road' => $dataComing["address"]["addr_road"],
								':tambol_code' => $dataComing["address"]["tambol_code"],
								':district_code' => $dataComing["address"]["district_code"],
								':province_code' => $dataComing["address"]["province_code"],
								':addr_postcode' => $dataComing["address"]["addr_postcode"],
								':coopregis_no' => $dataComing["coopregis_no"],
								':coopregis_date' => $dataComing["coopregis_date"] == "" ? null : $dataComing["coopregis_date"],
								':accyearclose_date' => $dataComing["accyearclose_date"] == "" ? null : $dataComing["accyearclose_date"],
								':memb_regno' => $dataComing["memb_regno"],
								':email' => $dataComing["addr_reg_email"],
								':tax_id' => $dataComing["tax_id"],
								':tel' => $dataComing["addr_phone"],
								':addr_fax' => $dataComing["addr_fax"],
								':member_no' => $member_no
							])){	
						}else{
							$conoracle->rollback();
							$arrayResult['RESPONSE'] = "ไม่สามารถบันทึกได้ กรุณาติดต่อผู้พัฒนา";
							$arrayResult['RESULT'] = FALSE;
							require_once('../../../../include/exit_footer.php');
						}
					}				
				}else{
					$message = $arrMember["full_name"]."(".$member_no.") "." มีเเก้ไขข้อมูลทั่วไปสหกรณ์";
					$lib->sendLineNotify($message ,$config["LINE_NOTIFY_FSCT"]);
				}

				$arrayResult["RESULT_EDIT"] = TRUE;
				$arrayResult['RESULT'] = TRUE;
				require_once('../../include/exit_footer.php');
			}else{
				$arrayResult["RESULT_EDIT"] = FALSE;
				$conmysql->rollback();
				$arrayResult['RESPONSE_CODE'] = "WS1039";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../include/exit_footer.php');
			}
		}else{
			$arrayResult["RESULT_EDIT"] = FALSE;
			$conmysql->rollback();
			$arrayResult['RESPONSE_CODE'] = "WS1039";
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
?>