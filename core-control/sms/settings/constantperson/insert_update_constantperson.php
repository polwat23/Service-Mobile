<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'sms','manageconstperson')){
		//edit list
		if($dataComing["edit_list"]){
			$conmysql->beginTransaction();
			foreach($dataComing["edit_list"] as $update_list){
				$updatelist = $conmysql->prepare("UPDATE smsconstantperson SET smscsp_mindeposit = :mindeposit, smscsp_minwithdraw = :minwithdraw,
										is_mindeposit = :is_mindeposit,is_minwithdraw = :is_minwithdraw
										 WHERE smscsp_account = :account_no");
				if($updatelist->execute([
					':mindeposit' => $update_list["MINDEPOSIT"],
					':minwithdraw' => $update_list["MINWITHDRAW"],
					':account_no' => $update_list["DEPTACCOUNT_NO"],
					':is_mindeposit' => $update_list["IS_MINDEPOSIT"] ? '1' : '0',
					':is_minwithdraw' => $update_list["IS_MINWITHDRAW"] ? '1' : '0'
				])){
					continue;
				}else{
					$conmysql->rollback();
					$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขรายการได้ กรุณาติดต่อผู้พัฒนา";
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}
			$conmysql->commit();
		}
		
		//insert list
		if($dataComing["insert_list"]){
			$conmysql->beginTransaction();
			$bulkInsert = array();
			foreach($dataComing["insert_list"] as $insert_list){
				$bulkInsert[] = "('".$insert_list["DEPTACCOUNT_NO"]."','".$insert_list["MEMBER_NO"]."',
										'".$insert_list["MINDEPOSIT"]."','".$insert_list["MINWITHDRAW"]."','1','".($insert_list["IS_MINDEPOSIT"] ? "1" : "0")."','".($insert_list["IS_MINWITHDRAW"] ? "1" : "0")."')";
			}
			$insertlist = $conmysql->prepare("INSERT INTO smsconstantperson (smscsp_account,smscsp_member_no,smscsp_mindeposit,smscsp_minwithdraw,is_use,is_mindeposit,is_minwithdraw) 
												VALUES".implode(',',$bulkInsert)." ON DUPLICATE KEY UPDATE
												smscsp_mindeposit = VALUES(smscsp_mindeposit), smscsp_minwithdraw = VALUES(smscsp_minwithdraw), is_use = '1'");
			if($insertlist->execute()){
				$conmysql->commit();
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มรายการได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}
		
		//delete list
		if($dataComing["delete_list"]){
			$conmysql->beginTransaction();
			$bulkInsertDelete = array();
			foreach($dataComing["delete_list"] as $delete_list){
				$bulkInsertDelete[] = "(".$delete_list["CONSTANT_ID"].",'".$delete_list["DEPTACCOUNT_NO"]."','".$delete_list["MEMBER_NO"]."','".$delete_list["MINDEPOSIT"]."','".$delete_list["MINWITHDRAW"]."','1',
												'".($delete_list["IS_MINDEPOSIT"] ? "1" : "0")."','".($delete_list["IS_MINWITHDRAW"] ? "1" : "0")."')";
			}
			$delete = $conmysql->prepare("INSERT INTO smsconstantperson (id_smscsperson,smscsp_account,smscsp_member_no,smscsp_mindeposit,smscsp_minwithdraw,is_use,is_mindeposit,is_minwithdraw) 
														VALUES".implode(',',$bulkInsertDelete)." ON DUPLICATE KEY UPDATE is_use = '0'");
			if($delete->execute()){	
				$conmysql->commit();
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE'] = "ไม่สามารถลบรายการได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);	
	}else{
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>