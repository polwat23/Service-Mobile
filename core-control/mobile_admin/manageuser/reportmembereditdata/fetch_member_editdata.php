<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','reportmembereditdata')){
		$arrayGroup = array();
		$fetchUserAccount = $conmysql->prepare("SELECT id_editdata, member_no, edit_date, old_data, incoming_data,update_date,inputgroup_type,old_email,new_email,old_tel,new_tel,old_website,new_website,old_coopregis_date,new_coopregis_date,old_coopregis_no,new_coopregis_no,old_memb_regno,new_memb_regno,old_tax_id,new_tax_id
											FROM gcmembereditdata WHERE is_updateoncore = '0'");
		$fetchUserAccount->execute();
		while($rowUser = $fetchUserAccount->fetch(PDO::FETCH_ASSOC)){
			$arrGroupUserAcount = array();
			$arrGroupUserAcount["ID_EDITDATA"] = $rowUser["id_editdata"];
			$arrGroupUserAcount["MEMBER_NO"] = $rowUser["member_no"];
			$arrGroupUserAcount["EDIT_DATE"] = $lib->convertdate($rowUser["edit_date"],'d m Y H-i-s',true);
			$arrGroupUserAcount["OLD_DATA_JSON"] = json_decode(($rowUser["old_data"]), true);
			$arrGroupUserAcount["INCOMING_DATA_JSON"] = json_decode(($rowUser["incoming_data"]), true);
			$arrGroupUserAcount["UPDATE_DATE"] = $lib->convertdate($rowUser["update_date"],'d m Y H-i-s',true);
			$arrGroupUserAcount["INPUTGROUP_TYPE"] = $rowUser["inputgroup_type"];
			$arrGroupUserAcount["OLD_DATA_JSON"]["email"] = $rowUser["old_email"];
			$arrGroupUserAcount["INCOMING_DATA_JSON"]["email"] = $rowUser["new_email"];
			$arrGroupUserAcount["OLD_DATA_JSON"]["tel"] = $rowUser["old_tel"];
			$arrGroupUserAcount["INCOMING_DATA_JSON"]["tel"] = $rowUser["new_tel"];
			$arrGroupUserAcount["OLD_DATA_JSON"]["website"] = $rowUser["old_website"];
			$arrGroupUserAcount["INCOMING_DATA_JSON"]["website"] = $rowUser["new_website"];
			$arrGroupUserAcount["OLD_DATA_JSON"]["coopregis_date"] = $lib->convertdate($rowUser["old_coopregis_date"],'d m Y');
			$arrGroupUserAcount["INCOMING_DATA_JSON"]["coopregis_date"] = $lib->convertdate($rowUser["new_coopregis_date"],'d m Y');
			$arrGroupUserAcount["OLD_DATA_JSON"]["coopregis_no"] = $rowUser["old_coopregis_no"];
			$arrGroupUserAcount["INCOMING_DATA_JSON"]["coopregis_no"] = $rowUser["new_coopregis_no"];
			$arrGroupUserAcount["OLD_DATA_JSON"]["memb_regno"] = $rowUser["old_memb_regno"];
			$arrGroupUserAcount["INCOMING_DATA_JSON"]["memb_regno"] = $rowUser["new_memb_regno"];
			$arrGroupUserAcount["OLD_DATA_JSON"]["tax_id"] = $rowUser["old_tax_id"];
			$arrGroupUserAcount["INCOMING_DATA_JSON"]["tax_id"] = $rowUser["new_tax_id"];
			$arrayGroup[] = $arrGroupUserAcount;
		}
		$arrayResult["MEMBER_EDIT_DATA"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		require_once('../../../../include/exit_footer.php');
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		require_once('../../../../include/exit_footer.php');
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	require_once('../../../../include/exit_footer.php');
}
?>