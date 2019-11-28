<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_template','topic_name','user_control','id_smsmenu'],$dataComing)){
	if($func->check_permission_core($payload,'sms','managetopic',$conmysql)) && is_numeric($dataComing["id_template"])){
		$conmysql->beginTransaction();
		$UpdateMenuSMS = $conmysql->prepare("UPDATE smsmenu SET sms_menu_name = :topic_name WHERE id_smsmenu = :id_smsmenu");
		if($UpdateMenuSMS->execute([
			':topic_name' => $dataComing["topic_name"],
			':id_smsmenu'=> $dataComing["id_smsmenu"]
		])){
			$updateMatching = $conmysql->prepare("UPDATE smstopicmatchtemplate SET id_smstemplate = :id_template WHERE id_smsmenu = :id_smsmenu");
			if($updateMatching->execute([
				':id_template' => $dataComing["id_template"],
				':id_smsmenu'=> $dataComing["id_smsmenu"]
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
				$findMatching = $conmysql->prepare("SELECT id_matching FROM smstopicmatchtemplate WHERE id_smsmenu = :id_smsmenu");
				$findMatching->execute([':id_smsmenu' => $dataComing["id_smsmenu"]]);
				if($findMatching->rowCount() > 0){
					$rowMatch = $findMatching->fetch();
					$id_matching = $rowMatch["id_matching"];
					$updateNotPermit = $conmysql->prepare("UPDATE smsmatchpermission SET is_use = '-9' WHERE id_permission_menu 
															NOT IN(".implode(',',$arrIdPermission).") and id_matching = :id_matching");
					if($updateNotPermit->execute([':id_matching' => $id_matching])){
					}else{
						$conmysql->rollback();
						$arrayResult['RESPONSE_CODE'] = "5005";
						$arrayResult['RESPONSE_AWARE'] = "update";
						$arrayResult['RESPONSE'] = "Some user Cannot control topic";
						$arrayResult['RESULT'] = FALSE;
						echo json_encode($arrayResult);
						exit();
					}
					foreach($arrIdPermission as $id_permission){
						$findHaving = $conmysql->prepare("SELECT id_match_permit,is_use FROM smsmatchpermission WHERE id_permission_menu = :id_permission and id_matching = :id_matching");
						$findHaving->execute([
							':id_permission' => $id_permission,
							':id_matching' => $id_matching
						]);
						if($findHaving->rowCount() > 0){
							while($rowHaving = $findHaving->fetch()){
								if($rowHaving["is_use"] != '1'){
									$arrWaitForUpdate[] = $id_permission;
								}
							}
						}else{
							$arrWaitForInsert[] = "(".$id_matching.",".$id_permission.")";
						}
					}
					if(sizeof($arrWaitForUpdate) > 0){
						$updateTopicMatch = $conmysql->prepare("UPDATE smsmatchpermission SET is_use = '1' 
																WHERE id_permission_menu IN(".implode(',',$arrWaitForUpdate).") and id_matching = :id_matching");
						if($updateTopicMatch->execute([':id_matching' => $id_matching])){
						}else{
							$conmysql->rollback();
							$arrayResult['RESPONSE_CODE'] = "5005";
							$arrayResult['RESPONSE_AWARE'] = "update";
							$arrayResult['RESPONSE'] = "Some user Cannot control topic";
							$arrayResult['RESULT'] = FALSE;
							echo json_encode($arrayResult);
							exit();
						}
					}
					if(sizeof($arrWaitForInsert) > 0){
						$insertMatchPermit = $conmysql->prepare("INSERT INTO smsmatchpermission(id_matching,id_permission_menu) 
														VALUES".implode(',',$arrWaitForInsert));
						if($insertMatchPermit->execute()){
						}else{
							$conmysql->rollback();
							$arrayResult['RESPONSE_CODE'] = "5005";
							$arrayResult['RESPONSE_AWARE'] = "insert";
							$arrayResult['RESPONSE'] = "Some user Cannot control topic";
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
					$arrayResult['RESPONSE_CODE'] = "5005";
					$arrayResult['RESPONSE_AWARE'] = "select";
					$arrayResult['RESPONSE'] = "Cannot find match template";
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE_CODE'] = "5005";
				$arrayResult['RESPONSE_AWARE'] = "update";
				$arrayResult['RESPONSE'] = "Cannot update SMS Template";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$conmysql->rollback();
			$arrayResult['RESPONSE_CODE'] = "5005";
			$arrayResult['RESPONSE_AWARE'] = "update";
			$arrayResult['RESPONSE'] = "Cannot update SMS Menu";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}
	}else{
		$arrayResult['RESPONSE_CODE'] = "4003";
		$arrayResult['RESPONSE_AWARE'] = "permission";
		$arrayResult['RESPONSE'] = "Not permission this menu";
		$arrayResult['RESULT'] = FALSE;
		http_response_code(403);
		echo json_encode($arrayResult);
		exit();
	}
}else{
	$arrayResult['RESPONSE_CODE'] = "4004";
	$arrayResult['RESPONSE_AWARE'] = "argument";
	$arrayResult['RESPONSE'] = "Not complete argument";
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>