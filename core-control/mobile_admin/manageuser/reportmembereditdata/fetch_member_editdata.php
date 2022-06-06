<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','reportmembereditdata')){
		$arrayGroup = array();
		$fetchUserManager= $conmysql->prepare("SELECT id_editdata,member_no, old_data as old_data_manger , incoming_data,update_date,username , document_path from gcmanagement where is_updateoncore = '0' ");
		$fetchUserManager->execute();
		while($rowUserManager = $fetchUserManager->fetch(PDO::FETCH_ASSOC)){
			$arrGroupUserAcount = array();
			$arrGroupUserAcount["ID_EDITDATA"] = $rowUserManager["id_editdata"];
			$arrGroupUserAcount["INPUTGROUP_TYPE"] = "editBoard";
			$arrGroupUserAcount["INCOMING_DATA_JSON"] = json_decode(($rowUserManager["incoming_data"]), true);
			$arrGroupUserAcount["OLD_DATA_JSON"] = json_decode(($rowUserManager["old_data_manger"]), true);
			$arrGroupUserAcount["EDIT_DATE"] = $lib->convertdate($rowUserManager["update_date"],'d m Y H-i-s',true);
			$arrGroupUserAcount["MEMBER_NO"] = $rowUserManager["member_no"];
			$arrGroupUserAcount["DOCUMENT_PATH"] = $rowUserManager["document_path"];
			$arrGroupUserAcount["USERNAME"] = $rowUserManager["username"];

			$fetchName = $conmysql->prepare("SELECT gc.member_no,gc.ref_memno,gc.acc_name,gc.acc_surname,gc.phone_number,gs.prename_desc,gs.memb_name,gs.suffname_desc
											FROM gcmemberaccount gc LEFT JOIN gcmembonlineregis gs ON gc.ref_memno = gs.member_no 
											WHERE  gc.member_no = :member_no AND gc.ref_memno = :ref_memno");
			$fetchName->execute([':member_no' => $rowUserManager["username"],
								 ':ref_memno' => $rowUserManager["member_no"]
							]);
			while($rowContName = $fetchName->fetch(PDO::FETCH_ASSOC)){
				$arrGroupUserAcount["NAME"] = $rowContName["acc_name"].' '.$rowContName["acc_surname"];
				$arrGroupUserAcount["PHONE_NUMBER"] = $rowContName["phone_number"];
				$arrGroupUserAcount["COOP_NAME"] = $rowContName["prename_desc"].$rowContName["memb_name"].' '.$rowContName["suffname_desc"];
			}
			
			
			$arrayGroup[] = $arrGroupUserAcount;
			
		}
		
		$fetchUserAccount = $conmysql->prepare("SELECT id_editdata, member_no, edit_date, old_data, incoming_data,update_date,inputgroup_type,old_email,new_email,old_tel,new_tel,old_website,new_website,old_coopregis_date,new_coopregis_date,
												old_accyearclose_date,new_accyearclose_date,old_coopregis_no,new_coopregis_no,old_memb_regno,new_memb_regno,old_tax_id,new_tax_id,old_share_stk,new_share_stk,username ,document_path,new_fax,old_fax
											FROM gcmembereditdata WHERE is_updateoncore = '0'");
		$fetchUserAccount->execute();
		while($rowUser = $fetchUserAccount->fetch(PDO::FETCH_ASSOC)){
			$arrGroupUserAcount = array();
			$arrGroupUserAcount["ID_EDITDATA"] = $rowUser["id_editdata"];
			$arrGroupUserAcount["MEMBER_NO"] = $rowUser["member_no"];
			$arrGroupUserAcount["USERNAME"] = $rowUser["username"];
			$arrGroupUserAcount["DOCUMENT_PATH"] = $rowUser["document_path"];
			$arrGroupUserAcount["EDIT_DATE"] = $lib->convertdate($rowUser["edit_date"],'d m Y H-i-s',true);
			$arrGroupUserAcount["OLD_DATA_JSON"] = json_decode(($rowUser["old_data"]), true);
			$arrGroupUserAcount["INCOMING_DATA_JSON"] = json_decode(($rowUser["incoming_data"]), true);
			$arrGroupUserAcount["UPDATE_DATE"] = $lib->convertdate($rowUser["update_date"],'d m Y H-i-s',true);
			$arrGroupUserAcount["INPUTGROUP_TYPE"] = "editCoop";
			$arrGroupUserAcount["OLD_DATA_JSON"]["email"] = $rowUser["old_email"];
			$arrGroupUserAcount["INCOMING_DATA_JSON"]["email"] = $rowUser["new_email"];
			$arrGroupUserAcount["OLD_DATA_JSON"]["tel"] = $rowUser["old_tel"];
			$arrGroupUserAcount["INCOMING_DATA_JSON"]["tel"] = $rowUser["new_tel"];
			$arrGroupUserAcount["OLD_DATA_JSON"]["website"] = $rowUser["old_website"];
			$arrGroupUserAcount["INCOMING_DATA_JSON"]["website"] = $rowUser["new_website"];
			$arrGroupUserAcount["OLD_DATA_JSON"]["coopregis_date"] = $rowUser["old_coopregis_date"];
			$arrGroupUserAcount["INCOMING_DATA_JSON"]["coopregis_date"] = $rowUser["new_coopregis_date"];
			$arrGroupUserAcount["OLD_DATA_JSON"]["accyearclose_date"] = $rowUser["old_accyearclose_date"];
			$arrGroupUserAcount["INCOMING_DATA_JSON"]["accyearclose_date"] = $rowUser["new_accyearclose_date"];
			$arrGroupUserAcount["OLD_DATA_JSON"]["coopregis_no"] = $rowUser["old_coopregis_no"];
			$arrGroupUserAcount["INCOMING_DATA_JSON"]["coopregis_no"] = $rowUser["new_coopregis_no"];
			$arrGroupUserAcount["OLD_DATA_JSON"]["memb_regno"] = $rowUser["old_memb_regno"];
			$arrGroupUserAcount["INCOMING_DATA_JSON"]["memb_regno"] = $rowUser["new_memb_regno"];
			$arrGroupUserAcount["OLD_DATA_JSON"]["tax_id"] = $rowUser["old_tax_id"];
			$arrGroupUserAcount["INCOMING_DATA_JSON"]["tax_id"] = $rowUser["new_tax_id"];
			$arrGroupUserAcount["OLD_DATA_JSON"]["addr_fax"] = $rowUser["old_fax"];
			$arrGroupUserAcount["INCOMING_DATA_JSON"]["addr_fax"] = $rowUser["new_fax"];
			$arrGroupUserAcount["OLD_DATA_JSON"]["share_stk"] = number_format($rowUser["old_share_stk"],2);
			$arrGroupUserAcount["INCOMING_DATA_JSON"]["share_stk"] = number_format($rowUser["new_share_stk"],2);
			
			$fetchName = $conmysql->prepare("SELECT gc.member_no,gc.ref_memno,gc.acc_name,gc.acc_surname,gc.phone_number,gs.prename_desc,gs.memb_name,gs.suffname_desc
												FROM gcmemberaccount gc LEFT JOIN gcmembonlineregis gs ON gc.ref_memno = gs.member_no 
												WHERE  gc.member_no = :member_no AND gc.ref_memno = :ref_memno");
			$fetchName->execute([':member_no' => $rowUser["username"],
								 ':ref_memno' => $rowUser["member_no"]
							]);
			while($rowContName = $fetchName->fetch(PDO::FETCH_ASSOC)){
				$arrGroupUserAcount["NAME"] = $rowContName["acc_name"].' '.$rowContName["acc_surname"];
				$arrGroupUserAcount["PHONE_NUMBER"] = $rowContName["phone_number"];
				$arrGroupUserAcount["COOP_NAME"] = $rowContName["prename_desc"].$rowContName["memb_name"].' '.$rowContName["suffname_desc"];
			}
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