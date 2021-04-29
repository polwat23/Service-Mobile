<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','membgroup_code','checked'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','extramonthlypaymentmembers')){
		$arrayGroup = array();
		
		$fetchUserGroup = $conmysql->prepare("SELECT id_extrapayment, membgroup_code, is_use FROM gcextrapaymentmembergroup WHERE membgroup_code = :membgroup_code");
		$fetchUserGroup->execute([
			':membgroup_code' => $dataComing['membgroup_code']
		]);
		while($rowUserGroup = $fetchUserGroup->fetch(PDO::FETCH_ASSOC)){
			$arrGroupUserAcount = array();
			$arrGroupUserAcount["MEMBGROUP_CODE"] = $rowUserGroup["membgroup_code"];
			$arrayGroup[] = $arrGroupUserAcount;
		}
		
		if(count($arrayGroup) > 0){
			$updateMembType = $conmysql->prepare("UPDATE gcextrapaymentmembergroup SET is_use = :checked, update_by = :update_by WHERE membgroup_code = :membgroup_code");
			if($updateMembType->execute([
				':checked' => $dataComing["checked"],
				':update_by' => $payload["username"],
				':membgroup_code' => $dataComing["membgroup_code"]
			])){
				$arrayStruc = [
					':menu_name' => "manageuser",
					':username' => $payload["username"],
					':use_list' => "edit extra payment membertype",
					':details' => $dataComing["membgroup_code"].' => '.$dataComing["checked"]
				];
				
				$log->writeLog('manageuser',$arrayStruc);	
				$arrayResult["RESULT"] = TRUE;
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขข้อมูลได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
		}else{
			$updateMembType = $conmysql->prepare("INSERT INTO gcextrapaymentmembergroup (is_use, update_by,membgroup_code) VALUES (:checked,:update_by,:membgroup_code)");
			if($updateMembType->execute([
				':checked' => $dataComing["checked"],
				':update_by' => $payload["username"],
				':membgroup_code' => $dataComing["membgroup_code"]
			])){
				$arrayStruc = [
					':menu_name' => "manageuser",
					':username' => $payload["username"],
					':use_list' => "edit extra payment membertype",
					':details' => $dataComing["membgroup_code"].' => '.$dataComing["checked"]
				];
				
				$log->writeLog('manageuser',$arrayStruc);	
				$arrayResult["RESULT"] = TRUE;
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขข้อมูลได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
		}
		
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