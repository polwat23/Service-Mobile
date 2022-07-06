<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'sms','manageconstperson',$conoracle)){
		//edit list
		if($dataComing["edit_list"]){
			$conoracle->beginTransaction();
			foreach($dataComing["edit_list"] as $update_list){
				if($update_list["PAY_TYPE"] == '1'){
					$updatelist = $conoracle->prepare("UPDATE smsconstantperson SET smscsp_mindeposit = :mindeposit, smscsp_minwithdraw = :minwithdraw,
											is_mindeposit = :is_mindeposit,is_minwithdraw = :is_minwithdraw,smscsp_pay_type = :pay_type,request_flat_date = :effect_date
											 WHERE smscsp_account = :account_no");
					if($updatelist->execute([
						':mindeposit' => $update_list["MINDEPOSIT"],
						':minwithdraw' => $update_list["MINWITHDRAW"],
						':account_no' => $update_list["DEPTACCOUNT_NO"],
						':is_mindeposit' => $update_list["IS_MINDEPOSIT"] ? '1' : '0',
						':is_minwithdraw' => $update_list["IS_MINWITHDRAW"] ? '1' : '0',
						':pay_type' => $update_list["PAY_TYPE"],
						':effect_date' => date("Ym")
					])){
						continue;
					}else{
						$conoracle->rollback();
						$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขรายการได้ กรุณาติดต่อผู้พัฒนา";
						$arrayResult['RESULT'] = FALSE;
						require_once('../../../../include/exit_footer.php');
						
					}
				}else{
					$updatelist = $conoracle->prepare("UPDATE smsconstantperson SET smscsp_mindeposit = :mindeposit, smscsp_minwithdraw = :minwithdraw,
											is_mindeposit = :is_mindeposit,is_minwithdraw = :is_minwithdraw,smscsp_pay_type = :pay_type,request_flat_date = null
											 WHERE smscsp_account = :account_no");
					if($updatelist->execute([
						':mindeposit' => $update_list["MINDEPOSIT"],
						':minwithdraw' => $update_list["MINWITHDRAW"],
						':account_no' => $update_list["DEPTACCOUNT_NO"],
						':is_mindeposit' => $update_list["IS_MINDEPOSIT"] ? '1' : '0',
						':is_minwithdraw' => $update_list["IS_MINWITHDRAW"] ? '1' : '0',
						':pay_type' => $update_list["PAY_TYPE"],
					])){
						continue;
					}else{
						$conoracle->rollback();
						$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขรายการได้ กรุณาติดต่อผู้พัฒนา";
						$arrayResult['RESULT'] = FALSE;
						require_once('../../../../include/exit_footer.php');
						
					}
				}
				
			}
			$conoracle->commit();
		}
		
		//insert list
		if($dataComing["insert_list"]){
			$conoracle->beginTransaction();
			$arrayGroup = array();
			foreach($dataComing["insert_list"] as $delete_list){
				
				$arrayGroup[] = $delete_list["DEPTACCOUNT_NO"];
			}
		
			$id_smscsperson  = $func->getMaxTable('id_smscsperson' , 'smsconstantperson',$conoracle);				
			
			foreach($dataComing["insert_list"] as $insert_list){
				
				$fetchConst = $conoracle->prepare("SELECT COUNT(smscsp_account) as COUNT FROM  smsconstantperson 
												   WHERE smscsp_member_no = '".$insert_list["MEMBER_NO"]."' and  smscsp_account = '".$insert_list["DEPTACCOUNT_NO"]."' ");
				$fetchConst->execute();
				$row_const = $fetchConst->fetch(PDO::FETCH_ASSOC);
			
				if($row_const["COUNT"] > 0){
					$updatelist = $conoracle->prepare("UPDATE smsconstantperson SET is_use = 1 WHERE smscsp_account = :account_no and smscsp_member_no = :smscsp_member_no ");
					if($updatelist->execute([
						':account_no' => $insert_list["DEPTACCOUNT_NO"],
						':smscsp_member_no' => $insert_list["MEMBER_NO"]
					])){
						continue;
					}else{
						$conoracle->rollback();
						$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขรายการได้ กรุณาติดต่อผู้พัฒนา";
						$arrayResult['RESULT'] = FALSE;
						require_once('../../../../include/exit_footer.php');
						
					}
				}else{
					if($insert_list["PAY_TYPE"] == '1'){	
					$bulkInsert = "(".$id_smscsperson.",'".$insert_list["DEPTACCOUNT_NO"]."','".$insert_list["MEMBER_NO"]."',
											'".$insert_list["MINDEPOSIT"]."','".$insert_list["MINWITHDRAW"]."','".($insert_list["IS_MINDEPOSIT"] ? "1" : "0")."',
											'".($insert_list["IS_MINWITHDRAW"] ? "1" : "0")."',
											'".$insert_list["PAY_TYPE"]."','".date("Ym")."')";
					}else{
						$bulkInsert = "(".$id_smscsperson.",'".$insert_list["DEPTACCOUNT_NO"]."','".$insert_list["MEMBER_NO"]."',
												'".$insert_list["MINDEPOSIT"]."','".$insert_list["MINWITHDRAW"]."','".($insert_list["IS_MINDEPOSIT"] ? "1" : "0")."',
												'".($insert_list["IS_MINWITHDRAW"] ? "1" : "0")."',
												'".$insert_list["PAY_TYPE"]."',null)";
					}
					$insertlist = $conoracle->prepare("INSERT INTO smsconstantperson(id_smscsperson,smscsp_account,smscsp_member_no,smscsp_mindeposit,smscsp_minwithdraw,
													is_mindeposit,is_minwithdraw,smscsp_pay_type,request_flat_date) 
													VALUES".$bulkInsert);
					if($insertlist->execute()){
						$id_smscsperson++;
					}else{
						$conoracle->rollback();
						$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มรายการได้ กรุณาติดต่อผู้พัฒนา";	
						$arrayResult['RESULT_RT'] = $id_smscsperson;
						$arrayResult['RESULT'] = FALSE;
						require_once('../../../../include/exit_footer.php');
						
					}
				}	
			}
			$conoracle->commit();
				
		}
		
		//delete list
		if($dataComing["delete_list"]){
			$conoracle->beginTransaction();
			foreach($dataComing["delete_list"] as $delete_list){
				$deletelist = $conoracle->prepare("UPDATE smsconstantperson SET smscsp_mindeposit = :mindeposit, smscsp_minwithdraw = :minwithdraw,
										is_mindeposit = :is_mindeposit,is_minwithdraw = :is_minwithdraw,smscsp_pay_type = :pay_type,is_use = '0',request_flat_date = null
										 WHERE smscsp_account = :account_no");
				if($deletelist->execute([
					':mindeposit' => $delete_list["MINDEPOSIT"],
					':minwithdraw' => $delete_list["MINWITHDRAW"],
					':account_no' => $delete_list["DEPTACCOUNT_NO"],
					':is_mindeposit' => $delete_list["IS_MINDEPOSIT"] ? '1' : '0',
					':is_minwithdraw' => $delete_list["IS_MINWITHDRAW"] ? '1' : '0',
					':pay_type' => $delete_list["PAY_TYPE"],
				])){
					continue;
				}else{
					$conoracle->rollback();
					$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขรายการได้ กรุณาติดต่อผู้พัฒนา";
					$arrayResult['RESULT'] = FALSE;
					require_once('../../../../include/exit_footer.php');
					
				}
			}
			$conoracle->commit();
		}
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