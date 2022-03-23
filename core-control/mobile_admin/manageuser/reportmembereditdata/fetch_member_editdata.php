<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','reportmembereditdata')){
		$arrayGroup = array();
		$fetchUserAccount = $conmysql->prepare("SELECT id_editdata, member_no, edit_date, old_data, incoming_data,update_date, inputgroup_type 
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
