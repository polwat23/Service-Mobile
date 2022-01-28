<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['menu_component'],$dataComing)){
	if($func->check_permission($payload["user_type"],$dataComing["menu_component"],'CoopInfo')){
		//select ใบคำขอเก่า
		$arrayGroup = null;
		$arrayMgGroup  = null;
		$fetchUserManager= $conmysql->prepare("SELECT id_editdata,member_no, old_data as old_data_manger , incoming_data,update_date,username,document_path,is_updateoncore from gcmanagement 
											where is_updateoncore in('-1') and member_no = :member_no");
		$fetchUserManager->execute([':member_no' => $payload["ref_memno"]]);
		if($rowUserManager = $fetchUserManager->fetch(PDO::FETCH_ASSOC)){
			$arrayMgGroup  = array();
			$manager  = json_decode(($rowUserManager["incoming_data"]), true);
			if($rowUserManager["is_updateoncore"] == '-1'){
				$manager["REQ_STATUS_DESC"] = 'รายการแก้ไขข้อมูลบริหารจัดการถูกปฏิเสธ';
				$manager["REQ_STATUS"] = $rowUserManager["is_updateoncore"];
			}else{
				$manager["REQ_STATUS_DESC"] = 'มีรายการแก้ไขข้อมูลบริหารจัดการรอดำเนินการ';
				$manager["REQ_STATUS"] = $rowUserManager["is_updateoncore"];
			}
			$manager["UPDATE_DATE"] = $lib->convertdate($rowUserManager["update_date"],'d m Y H-i-s',true);
			$manager["ID_EDITDATA"] = $rowUserManager["id_editdata"];
			$arrayMgGroup  = $manager;
		}
		$fetchUserAccount = $conmysql->prepare("SELECT id_editdata, member_no, edit_date, old_data, incoming_data,update_date,inputgroup_type,old_email,new_email,old_tel,new_tel,new_fax,old_website,new_website,old_coopregis_date,new_coopregis_date,
												old_accyearclose_date,new_accyearclose_date,old_coopregis_no,new_coopregis_no,old_memb_regno,new_memb_regno,old_tax_id,new_tax_id,old_share_stk,new_share_stk,username,document_path,is_updateoncore
											FROM gcmembereditdata WHERE is_updateoncore in('-1') and  member_no = :member_no");
		$fetchUserAccount->execute([':member_no' => $payload["ref_memno"]]);
		if($rowUser = $fetchUserAccount->fetch(PDO::FETCH_ASSOC)){
			$arrayGroup = array();
			$address_docno = json_decode(($rowUser["incoming_data"]), true); 
			if($rowUser["is_updateoncore"] == '-1'){
				$arrGroupUserAcount["REQ_STATUS_DESC"] = 'รายการแก้ไขข้อมูลทั่วไปสหกรณ์ถูกปฏิเสธ';
				$arrGroupUserAcount["REQ_STATUS"] = $rowUser["is_updateoncore"];
			}else{
				$arrGroupUserAcount["REQ_STATUS_DESC"] = 'มีรายการแก้ไขข้อมูลทั่วไปสหกรณ์รอดำเนินการ';
				$arrGroupUserAcount["REQ_STATUS"] = $rowUser["is_updateoncore"];
			}
			$arrGroupUserAcount["ID_EDITDATA"] = $rowUser["id_editdata"];
			$arrGroupUserAcount["UPDATE_DATE"] = $lib->convertdate($rowUser["update_date"],'d m Y H-i-s',true);
			$arrGroupUserAcount["DOCUMENT_PATH"] = $rowUser["document_path"];
			$arrayGroup = $arrGroupUserAcount;
		}	
		$arrayResult['MANAGE_DOCUMENT'] = $arrayMgGroup;
		$arrayResult['COOPINFO_DOCUMENT'] = $arrayGroup;
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
