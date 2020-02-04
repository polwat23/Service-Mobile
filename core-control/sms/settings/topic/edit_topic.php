<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_template','topic_name','user_control','id_submenu'],$dataComing)){
	if($func->check_permission_core($payload,'sms','managetopic') && is_numeric($dataComing["id_template"])){
		$conmysql->beginTransaction();
		$UpdateMenuSMS = $conmysql->prepare("UPDATE coresubmenu SET menu_name = :topic_name WHERE id_submenu = :id_submenu");
		if($UpdateMenuSMS->execute([
			':topic_name' => $dataComing["topic_name"],
			':id_submenu'=> $dataComing["id_submenu"]
		])){
			$updateMatching = $conmysql->prepare("UPDATE smstopicmatchtemplate SET id_smstemplate = :id_template WHERE id_submenu = :id_submenu");
			if($updateMatching->execute([
				':id_template' => $dataComing["id_template"],
				':id_submenu'=> $dataComing["id_submenu"]
			])){
				$arrIdPermission = array();
				foreach($dataComing["user_control"] as $username) {
					if(strpos($username,"_system_") === FALSE){
						$getIdPermission = $conmysql->prepare("SELECT id_permission_menu FROM corepermissionmenu WHERE username = :username and id_coremenu = 1");
						$getIdPermission->execute([
							':username' => $username
						]);
						while($rowidPermission = $getIdPermission->fetch()){
							$arrIdPermission[] = $rowidPermission["id_permission_menu"];
						}
					}else{
						$id_section_system = str_replace("_system_","",$username);
						$selectUserinSystem = $conmysql->prepare("SELECT cpm.id_permission_menu FROM coreuser cu INNER JOIN coresectionsystem cs ON 
																	cu.id_section_system = cs.id_section_system 
																	RIGHT JOIN corepermissionmenu cpm ON cu.username = cpm.username 
																	WHERE cpm.id_coremenu = 1 and cpm.is_use = '1' and cu.id_section_system = :id_section_system");
						$selectUserinSystem->execute([':id_section_system' => $id_section_system]);
						while($rowUser = $selectUserinSystem->fetch()){
							$arrIdPermission[] = $rowUser["id_permission_menu"];
						}
					}
				}
				$arrWaitForUpdate = array();
				$arrWaitForInsert = array();
				$updateNotPermit = $conmysql->prepare("UPDATE corepermissionsubmenu SET is_use = '-9' WHERE id_permission_menu 
														NOT IN(".implode(',',$arrIdPermission).") and id_submenu = :id_submenu");
				if($updateNotPermit->execute([':id_submenu' => $dataComing["id_submenu"]])){
				}else{
					$conmysql->rollback();
					$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มผู้ใช้งานระบบได้ กรุณาติดต่อผู้พัฒนา";
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
				foreach($arrIdPermission as $id_permission){
					$findHaving = $conmysql->prepare("SELECT is_use FROM corepermissionsubmenu WHERE id_permission_menu = :id_permission and id_submenu = :id_submenu");
					$findHaving->execute([
						':id_permission' => $id_permission,
						':id_submenu' => $dataComing["id_submenu"]
					]);
					if($findHaving->rowCount() > 0){
						while($rowHaving = $findHaving->fetch()){
							if($rowHaving["is_use"] != '1'){
								$arrWaitForUpdate[] = $id_permission;
							}
						}
					}else{
						$arrWaitForInsert[] = "(".$dataComing["id_submenu"].",".$id_permission.")";
					}
				}
				if(sizeof($arrWaitForUpdate) > 0){
					$updateTopicMatch = $conmysql->prepare("UPDATE corepermissionsubmenu SET is_use = '1' 
															WHERE id_permission_menu IN(".implode(',',$arrWaitForUpdate).") and id_submenu = :id_submenu");
					if($updateTopicMatch->execute([':id_submenu' => $dataComing["id_submenu"]])){
					}else{
						$conmysql->rollback();
						$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มผู้ใช้งานระบบได้ กรุณาติดต่อผู้พัฒนา";
						$arrayResult['RESULT'] = FALSE;
						echo json_encode($arrayResult);
						exit();
					}
				}
				if(sizeof($arrWaitForInsert) > 0){
					$insertMatchPermit = $conmysql->prepare("INSERT INTO corepermissionsubmenu(id_submenu,id_permission_menu) 
													VALUES".implode(',',$arrWaitForInsert));
					if($insertMatchPermit->execute()){
					}else{
						$conmysql->rollback();
						$arrayResult['RESPONSE'] = "ไม่สามารถเพิ่มผู้ใช้งานระบบได้ กรุณาติดต่อผู้พัฒนา";
						$arrayResult['RESULT'] = FALSE;
						echo json_encode($arrayResult);
						exit();
					}
				}
				$conmysql->commit();
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขหัวข้องานได้ กรุณาติดต่อผู้พัฒนา";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$conmysql->rollback();
			$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขหัวข้องานได้ กรุณาติดต่อผู้พัฒนา";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
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