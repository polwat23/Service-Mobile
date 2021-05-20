<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'SettingMemberInfo')){
		$member_no = $configAS[$payload["ref_memno"]] ?? $payload["ref_memno"];
		
		/*$arrConstInfo = array();
		$getConstInfo = $conmysql->prepare("SELECT const_code,save_tablecore FROM gcconstantchangeinfo");
		$getConstInfo->execute();
		while($rowConst = $getConstInfo->fetch(PDO::FETCH_ASSOC)){
			$arrConstInfo[$rowConst["const_code"]] = $rowConst["save_tablecore"];
		}*/
	
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
		
		$shareInfo = $conmysql->prepare("SELECT (Old_share_stk) as SHARE_AMT FROM gcmembereditdata WHERE TRIM(member_no) = :member_no");
		$shareInfo->execute([':member_no' => $member_no]);
		$rowShare = $shareInfo->fetch(PDO::FETCH_ASSOC);
		
		//profile สหกรณฺ์
		$member_info = $conoracle->prepare("SELECT 
											MB.ADDR_EMAIL AS ADDR_EMAIL,
											MB.ADDR_PHONE AS ADDR_PHONE,
											MB.ADDR_FAX AS ADDR_FAX,
											TO_CHAR(MB.COOPREGIS_DATE, 'YYYY-MM-DD') as COOPREGIS_DATE,
											MB.COOPREGIS_NO as COOPREGIS_NO,
											MB.MEMB_REGNO as MEMB_REGNO,
											MB.TAX_ID as TAX_ID,
											TO_CHAR(MB.ACCYEARCLOSE_DATE, 'YYYY-MM-DD') as ACCYEARCLOSE_DATE
											FROM mbmembmaster mb  WHERE mb.member_no = :member_no ");
		$member_info->execute([':member_no' => $member_no]);
		$rowMember_info = $member_info->fetch(PDO::FETCH_ASSOC);
		$arrMember["addr_email"] = $rowMember_info["ADDR_EMAIL"];
		$arrMember["addr_phone"] = $rowMember_info["ADDR_PHONE"];
		$arrMember["addr_fax"] = $rowMember_info["ADDR_FAX"];
		$arrMember["coopregis_date"] = $rowMember_info["COOPREGIS_DATE"];
		$arrMember["coopregis_no"] = $rowMember_info["COOPREGIS_NO"];
		$arrMember["memb_regno"] = $rowMember_info["MEMB_REGNO"];
		$arrMember["accyearclose_date"] = $rowMember_info["ACCYEARCLOSE_DATE"];
		$arrMember["tax_id"] = $rowMember_info["TAX_ID"] ;
			


		$insertChangeData = $conmysql->prepare("INSERT INTO gcmembereditdata(member_no,old_data,incoming_data,old_email, new_email, old_tel, new_tel, old_fax, new_fax, old_website, new_website,
												old_coopregis_date, new_coopregis_date, old_accyearclose_date, new_accyearclose_date, old_coopregis_no, new_coopregis_no, 
												old_memb_regno, new_memb_regno, old_tax_id, new_tax_id, old_share_stk, new_share_stk, inputgroup_type,username)
												VALUES(:member_no,:old_address,:address,:old_email,:new_email,:old_tel,:new_tel,:old_fax,:new_fax,:old_website,:new_website,
											   :old_coopregis_date,:new_coopregis_date, :old_accyearclose_date, :new_accyearclose_date,:old_coopregis_no,:new_coopregis_no,
											   :old_memb_regno,:new_memb_regno,:old_tax_id,:new_tax_id,:old_share_stk,:new_share_stk,:inputgroup_type,:username)");
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
			':old_share_stk' => $rowShare["SHARE_AMT"],
			':new_share_stk' => $dataComing["share"],
			':inputgroup_type' => $inputgroup_type,
			':username'=> $payload["member_no"]
		])){
			$arrayResult["RESULT_EDIT"] = TRUE;
		}else{
			$arrayResult["RESULT_EDIT"] = FALSE;
		}		
			
		if(isset($arrayResult["RESULT_EDIT"]) && !$arrayResult["RESULT_EDIT"]){
			$arrayResult['RESPONSE_CODE'] = "WS1039";
			$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
			$arrayResult['RESULT'] = FALSE;
			$arrayResult['sdsd'] = [
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
					':username'=> $payload["username"]
			];
			require_once('../../include/exit_footer.php');
			
		}
		$arrayResult['RESULT'] = TRUE;
		require_once('../../include/exit_footer.php');
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