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
										 WHERE 	smscsp_account = :account_no");
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
			foreach($dataComing["insert_list"] as $insert_list){
				$insertlist = $conmysql->prepare('INSERT INTO smsconstantperson (id_smscsperson, smscsp_account, smscsp_mindeposit,smscsp_minwithdraw,is_use,is_mindeposit,is_minwithdraw) 
												VALUES(:constant_id,:account_no,:mindeposit,:minwithdraw,"1",:is_mindeposit,:is_minwithdraw) ON DUPLICATE KEY UPDATE    
												smscsp_mindeposit=:mindeposit, smscsp_minwithdraw=:minwithdraw, is_use = "1"');
				if($insertlist->execute([
					':mindeposit' => $insert_list["MINDEPOSIT"],
					':minwithdraw' => $insert_list["MINWITHDRAW"],
					':account_no' => $insert_list["DEPTACCOUNT_NO"],
					':constant_id' => $insert_list["CONSTANT_ID"] == '' ? null : $insert_list["CONSTANT_ID"],
					':is_mindeposit' => $insert_list["IS_MINDEPOSIT"] ? "1" : "0",
					':is_minwithdraw' => $insert_list["IS_MINWITHDRAW"] ? "1" : "0"
				])){
					continue;
				}else{
					$conmysql->rollback();
					$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มรายการได้ กรุณาติดต่อผู้พัฒนา";
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}
			$conmysql->commit();
		}
		
		//delete list
		if($dataComing["delete_list"]){
			$conmysql->beginTransaction();
			foreach($dataComing["delete_list"] as $delete_list){
				$delete = $conmysql->prepare('INSERT INTO smsconstantperson (id_smscsperson,smscsp_account, smscsp_mindeposit,smscsp_minwithdraw,is_use,is_mindeposit,is_minwithdraw) 
				VALUES(:constant_id,:account_no, :mindeposit,:minwithdraw,"0",:is_mindeposit,:is_minwithdraw) ON DUPLICATE KEY UPDATE    
					is_use = "0"');
				if($delete->execute([
					':mindeposit' => $delete_list["MINDEPOSIT"],
					':minwithdraw' => $delete_list["MINWITHDRAW"],
					':account_no' => $delete_list["DEPTACCOUNT_NO"],
					':constant_id' => $delete_list["CONSTANT_ID"] == '' ? null : $delete_list["CONSTANT_ID"],
					':is_mindeposit' => $delete_list["IS_MINDEPOSIT"] ? "1" : "0",
					':is_minwithdraw' => $delete_list["IS_MINWITHDRAW"] ? "1" : "0"
				])){
					continue;
				}else{
					$conmysql->rollback();
					$arrayResult['RESPONSE'] = "ไม่สามารถลบรายการได้ กรุณาติดต่อผู้พัฒนา";
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}
			$conmysql->commit();
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