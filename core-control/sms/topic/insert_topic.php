<?php
require_once('../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_template','topic_name','user_control'],$dataComing)){
	if($func->check_permission_core($payload["section_system"],'sms',$conmysql) && is_numeric($dataComing["id_template"])){
		$conmysql->beginTransaction();
		$page_name = $lib->randomText('all',6);
		$insertSmsMenu = $conmysql->prepare("INSERT INTO smsmenu(sms_menu_name,page_name,smsmenu_order,username,id_menuparent)
												VALUES(:topic_name,:page_name,1,:username,8)");
		if($insertSmsMenu->execute([
			':topic_name' => $dataComing["topic_name"],
			':page_name'=> $page_name,
			':username' => $payload["username"]
		])){
			$id_smsmenu = $conmysql->lastInsertId();
			$insertTopicMatch = $conmysql->prepare("INSERT INTO smstopicmatchtemplate(id_smsmenu,id_smstemplate) 
													VALUES(:id_smsmenu,:id_smstemplate)");
			if($insertTopicMatch->execute([
				':id_smsmenu' => $id_smsmenu,
				':id_smstemplate' => $dataComing["id_template"]
			])){
				$id_matching = $conmysql->lastInsertId();
				$arrayInsert = array();
				foreach($dataComing["user_control"] as $username) {
					if(strpos($username,"_system_") === FALSE){
						$getIdPermission = $conmysql->prepare("SELECT id_permission_menu FROM corepermissionmenu WHERE username = :username and id_coremenu = 1 and is_use = '1'");
						$getIdPermission->execute([':username' => $username]);
						if($getIdPermission->rowCount() > 0){
							$rowIdPermission = $getIdPermission->fetch();
							$arrayInsert[] = "(".$id_matching.",".$rowIdPermission["id_permission_menu"].")";
						}
					}else{
						$id_section_system = str_replace("_system_","",$username);
						$selectUserinSystem = $conmysql->prepare("SELECT cpm.id_permission_menu FROM coreuser cu INNER JOIN coresectionsystem cs ON 
																	cu.id_section_system = cs.id_section_system 
																	RIGHT JOIN corepermissionmenu cpm ON cu.username = cpm.username 
																	WHERE cpm.id_coremenu = 1 and cpm.is_use = '1' and cu.id_section_system = :id_section_system");
						$selectUserinSystem->execute([':id_section_system' => $id_section_system]);
						if($selectUserinSystem->rowCount() > 0){
							while($rowUser = $selectUserinSystem->fetch()){
								$arrayInsert[] = "(".$id_matching.",".$rowUser["id_permission_menu"].")";
							}
						}
					}
				}
				$insertMatchPermit = $conmysql->prepare("INSERT INTO smsmatchpermission(id_matching,id_permission_menu) 
														VALUES".implode(',',$arrayInsert));
				if($insertMatchPermit->execute()){
					$conmysql->commit();
					$arrayResult['RESULT'] = TRUE;
					echo json_encode($arrayResult);
				}else{
					$conmysql->rollback();
					$arrayResult['RESPONSE_CODE'] = "5005";
					$arrayResult['RESPONSE_AWARE'] = "insert";
					$arrayResult['RESPONSE'] = "Some user Cannot control topic";
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE_CODE'] = "5005";
				$arrayResult['RESPONSE_AWARE'] = "insert";
				$arrayResult['RESPONSE'] = "Cannot connect template to topic";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}else{
			$conmysql->rollback();
			$arrayResult['RESPONSE_CODE'] = "5005";
			$arrayResult['RESPONSE_AWARE'] = "insert";
			$arrayResult['RESPONSE'] = "Cannot insert SMS Menu";
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