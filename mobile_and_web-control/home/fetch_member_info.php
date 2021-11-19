<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'MemberInfo')){
		
		$member_no = $configAS[$payload["member_no"]] ?? $payload["member_no"];
		$memberInfo = $conmssqlcoop->prepare("select cpt.member_id  as MEMBER_NO, 
										cpt.telephone as TELEPHONE,
										cpt.email as EMAIL,
										cpt.prefixname as PRENAME_SHORT , 
										cpt.firstname as MEMB_NAME , 
										cpt.lastname as MEMB_SURNAME, 
										cpt.birthdate as BIRTH_DATE,  
										cpt.id_number as CARD_PERSON,
										cpt.member_in as MEMBER_DATE,
										cpn.description as COMPANY_DESC,
										cpn.description as MEMBGROUP_DESC,
										'' as MEMBTYPE_DESC,
										cpt.address1 as ADDRESS1,
										cpt.address2 as ADDRESS2
										FROM cocooptation cpt LEFT JOIN cocompany cpn ON cpt.company = cpn.company
										WHERE cpt.member_id = :member_no");
		$memberInfo->execute([':member_no' => $member_no]);
		$rowMember = $memberInfo->fetch(PDO::FETCH_ASSOC);
		$address = $rowMember["ADDRESS1"].' '.$rowMember["ADDRESS2"];		
		$arrayResult["PRENAME"] = $rowMember["PRENAME_SHORT"];
		$arrayResult["NAME"] = $rowMember["MEMB_NAME"];
		$arrayResult["SURNAME"] = $rowMember["MEMB_SURNAME"];
		$arrayResult["PHONE"] = $rowMember["TELEPHONE"];
		$arrayResult["EMAIL"] = $rowMember["EMAIL"];
		//$arrayResult["BIRTH_DATE"] = $lib->convertdate($rowMember["BIRTH_DATE"],"D m Y");
		$arrayResult["BIRTH_DATE_COUNT"] =  $lib->count_duration($rowMember["BIRTH_DATE"],"ym");
		//$arrayResult["CARD_PERSON"] = $rowMember["CARD_PERSON"];
		$arrayResult["MEMBER_DATE"] = $lib->convertdate($rowMember["MEMBER_DATE"],"D m Y");
		$arrayResult["MEMBER_DATE_COUNT"] = $lib->count_duration($rowMember["MEMBER_DATE"],"ym");
		$arrayResult["COMPANY_DESC"] = $rowMember["COMPANY_DESC"];
		///$arrayResult["MEMBER_TYPE"] = $rowMember["MEMBTYPE_DESC"];
		$arrayResult["MEMBGROUP_DESC"] = $rowMember["MEMBGROUP_DESC"];
		$arrayResult["IS_CHANGE_AVATAR"] = false;
		$arrayResult["FULL_ADDRESS_CURR"] = $address;
		$arrayResult["MEMBER_NO"] = $member_no;
		$arrayResult["RESULT"] = TRUE;
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