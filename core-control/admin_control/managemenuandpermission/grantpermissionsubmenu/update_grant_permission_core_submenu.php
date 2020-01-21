<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','username','id_submenu','status_permission'],$dataComing)){
	if($func->check_permission_core($payload,'admincontrol','permissionmenu')){
		$getIdCoreMenu = $conmysql->prepare("SELECT id_coremenu FROM coresubmenu WHERE id_submenu = :id_submenu");
		$getIdCoreMenu->execute([
			':id_submenu' => $dataComing["id_submenu"]
		]);
		if($getIdCoreMenu->rowCount() > 0){
			$rowidcoremenu = $getIdCoreMenu->fetch();
			$checkPermissionCoremenu = $conmysql->prepare("SELECT id_permission_menu FROM corepermissionmenu 
															WHERE username = :username and is_use = '1' and id_coremenu = :id_coremenu");
			$checkPermissionCoremenu->execute([
				':username' => $dataComing["username"],
				':id_coremenu' => $rowidcoremenu["id_coremenu"]
			]);
			if($checkPermissionCoremenu->rowCount() > 0){
				$rowid_permission = $checkPermissionCoremenu->fetch();
				$checkSubmenuPermit = $conmysql->prepare("SELECT id_permission_submenu FROM corepermissionsubmenu
															WHERE id_permission_menu = :id_permission_menu and id_submenu = :id_submenu and is_use <> '-9'");
				$checkSubmenuPermit->execute([
					':id_permission_menu' => $rowid_permission["id_permission_menu"],
					':id_submenu' => $dataComing["id_submenu"]
				]);
				if($checkSubmenuPermit->rowCount() > 0){
					$rowid_permission_submenu = $checkSubmenuPermit->fetch();
					$UpdateSubmenuPermit = $conmysql->prepare("UPDATE corepermissionsubmenu SET is_use = :status_permission
																WHERE id_permission_submenu = :id_permission_submenu");
					if($UpdateSubmenuPermit->execute([
						':status_permission' => $dataComing["status_permission"],
						':id_permission_submenu' => $rowid_permission_submenu["id_permission_submenu"]
					])){
						$arrayResult['RESULT'] = TRUE;
						echo json_encode($arrayResult);
					}else{
						$arrayResult['RESPONSE'] = "ไม่สามารถให้สิทธิ์ได้";
						$arrayResult['RESULT'] = FALSE;
						echo json_encode($arrayResult);
						exit();
					}
				}else{
					$insertSubMenuPermit = $conmysql->prepare("INSERT INTO corepermissionsubmenu(id_submenu,id_permission_menu,is_use)
																VALUES(:id_submenu,:id_permission_menu,:status_permission)");
					if($insertSubMenuPermit->execute([
						':id_submenu' => $dataComing["id_submenu"],
						':id_permission_menu' => $rowid_permission["id_permission_menu"],
						':status_permission' => $dataComing["status_permission"]
					])){
						$arrayResult['RESULT'] = TRUE;
						echo json_encode($arrayResult);
					}else{
						$arrayResult['RESPONSE'] = "ไม่สามารถให้สิทธิ์ได้";
						$arrayResult['RESULT'] = FALSE;
						echo json_encode($arrayResult);
						exit();
					}
				}
			}else{
				$conmysql->beginTransaction();
				$insertPermitCoreMenu = $conmysql->prepare("INSERT INTO corepermissionmenu(id_coremenu,username)
															VALUES(:id_coremenu,:username)");
				if($insertPermitCoreMenu->execute([
					':id_coremenu' => $rowidcoremenu["id_coremenu"],
					':username' => $dataComing["username"]
				])){
					$id_permission = $conmysql->lastInsertId();
					$insertSubMenuPermit = $conmysql->prepare("INSERT INTO corepermissionsubmenu(id_submenu,id_permission_menu,is_use)
																VALUES(:id_submenu,:id_permission_menu,:status_permission)");
					if($insertSubMenuPermit->execute([
						':id_submenu' => $dataComing["id_submenu"],
						':id_permission_menu' => $id_permission,
						':status_permission' => $dataComing["status_permission"]
					])){
						$conmysql->commit();
						$arrayResult['RESULT'] = TRUE;
						echo json_encode($arrayResult);
					}else{
						$conmysql->rollback();
						$arrayResult['RESPONSE'] = "ไม่สามารถให้สิทธิ์ได้";
						$arrayResult['RESULT'] = FALSE;
						echo json_encode($arrayResult);
						exit();
					}
				}else{
					$conmysql->rollback();
					$arrayResult['RESPONSE'] = "ไม่พบเมนูหลักของระบบ";
					$arrayResult['RESULT'] = FALSE;
					echo json_encode($arrayResult);
					exit();
				}
			}
		}else{
			$arrayResult['RESPONSE'] = "ไม่พบเมนูหลักของระบบ";
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