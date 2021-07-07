<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','username','id_submenu','status_permission'],$dataComing)){
	if($func->check_permission_core($payload,'admincontrol','permissionmenu')){
		$getIdCoreMenu = $conoracle->prepare("SELECT id_coremenu FROM coresubmenu WHERE id_submenu = :id_submenu");
		$getIdCoreMenu->execute([
			':id_submenu' => $dataComing["id_submenu"]
		]);
		$rowidcoremenu = $getIdCoreMenu->fetch(PDO::FETCH_ASSOC);
		if(isset($rowidcoremenu["ID_COREMENU"])){
			$checkPermissionCoremenu = $conoracle->prepare("SELECT id_permission_menu FROM corepermissionmenu 
															WHERE username = :username and is_use = '1' and id_coremenu = :id_coremenu");
			$checkPermissionCoremenu->execute([
				':username' => $dataComing["username"],
				':id_coremenu' => $rowidcoremenu["ID_COREMENU"]
			]);
			$rowid_permission = $checkPermissionCoremenu->fetch(PDO::FETCH_ASSOC);
			if(isset($rowid_permission["ID_PERMISSION_MENU"])){	
				$checkSubmenuPermit = $conoracle->prepare("SELECT id_permission_submenu FROM corepermissionsubmenu
															WHERE id_permission_menu = :id_permission_menu and id_submenu = :id_submenu and is_use <> '-9'");
				$checkSubmenuPermit->execute([
					':id_permission_menu' => $rowid_permission["ID_PERMISSION_MENU"],
					':id_submenu' => $dataComing["id_submenu"]
				]);
				$rowid_permission_submenu = $checkSubmenuPermit->fetch(PDO::FETCH_ASSOC);
				if(isset($rowid_permission_submenu["ID_PERMISSION_SUBMENU"])){					
					$UpdateSubmenuPermit = $conoracle->prepare("UPDATE corepermissionsubmenu SET is_use = :status_permission
																WHERE id_permission_submenu = :id_permission_submenu");
					if($UpdateSubmenuPermit->execute([
						':status_permission' => $dataComing["status_permission"],
						':id_permission_submenu' => $rowid_permission_submenu["ID_PERMISSION_SUBMENU"]
					])){
						$arrayStruc = [
							':menu_name' => "permissionmenu",
							':username' => $payload["username"],
							':use_list' => "change permission menu",
							':details' => 'change sub permission id '.$rowid_permission_submenu["ID_PERMISSION_SUBMENU"].' to status : '.$dataComing["status_permission"].' of username : '.$dataComing["username"]
						];
						$log->writeLog('editadmincontrol',$arrayStruc);
						$arrayResult['RESULT'] = TRUE;
						require_once('../../../../include/exit_footer.php');
					}else{
						$arrayResult['RESPONSE'] = "ไม่สามารถให้สิทธิ์ได้";
						$arrayResult['RESULT'] = FALSE;
						require_once('../../../../include/exit_footer.php');
					}
				}else{
					$id_permission_submenu  = $func->getMaxTable('id_permission_submenu' , 'corepermissionsubmenu');
					$insertSubMenuPermit = $conoracle->prepare("INSERT INTO corepermissionsubmenu(id_permission_submenu,id_submenu,id_permission_menu,is_use)
																VALUES(:id_permission_submenu,:id_submenu,:id_permission_menu,:status_permission)");
					if($insertSubMenuPermit->execute([
						':id_submenu' => $id_permission_submenu,
						':id_submenu' => $dataComing["id_submenu"],
						':id_permission_menu' => $rowid_permission["ID_PERMISSION_MENU"],
						':status_permission' => $dataComing["status_permission"]
					])){
						$arrayStruc = [
							':menu_name' => "permissionmenu",
							':username' => $payload["username"],
							':use_list' => "change permission menu",
							':details' => 'change permission id '.$rowid_permission["ID_PERMISSION_MENU"].' to status : '.$dataComing["status_permission"].' of username : '.$dataComing["username"]
						];
						$log->writeLog('editadmincontrol',$arrayStruc);
						$arrayResult['RESULT'] = TRUE;
						require_once('../../../../include/exit_footer.php');
					}else{
						$arrayResult['RESPONSE'] = "ไม่สามารถให้สิทธิ์ได้";
						$arrayResult['RESULT'] = FALSE;
						require_once('../../../../include/exit_footer.php');
					}
				}
			}else{
				$conoracle->beginTransaction();
				$id_permission_menu  = $func->getMaxTable('id_permission_menu' , 'corepermissionmenu');
				$insertPermitCoreMenu = $conoracle->prepare("INSERT INTO corepermissionmenu(id_permission_menu,id_coremenu,username)
															VALUES(:id_permission_menu,:id_coremenu,:username)");
				if($insertPermitCoreMenu->execute([
					':id_coremenu' => $id_permission_menu,
					':id_coremenu' => $rowidcoremenu["ID_COREMENU"],
					':username' => $dataComing["username"]
				])){
					$id_permission = $conoracle->lastInsertId();
					$id_permission_submenu  = $func->getMaxTable('id_permission_submenu' , 'corepermissionsubmenu');
					$insertSubMenuPermit = $conoracle->prepare("INSERT INTO corepermissionsubmenu(id_permission_submenu,id_submenu,id_permission_menu,is_use)
																VALUES(:id_permission_submenu,:id_submenu,:id_permission_menu,:status_permission)");
					if($insertSubMenuPermit->execute([
						':id_permission_submenu' => $id_permission_submenu,
						':id_submenu' => $dataComing["id_submenu"],
						':id_permission_menu' => $id_permission,
						':status_permission' => $dataComing["status_permission"]
					])){
						$conoracle->commit();
						$arrayStruc = [
							':menu_name' => "permissionmenu",
							':username' => $payload["username"],
							':use_list' => "change permission menu",
							':details' => 'insert permission id '.$id_permission.' on submenu id : '.$dataComing["id_submenu"].' status menu is '.$dataComing["status_permission"].' of username : '.$dataComing["username"]
						];
						$log->writeLog('editadmincontrol',$arrayStruc);
						$arrayResult['RESULT'] = TRUE;
						require_once('../../../../include/exit_footer.php');
					}else{
						$conoracle->rollback();
						$arrayResult['RESPONSE'] = "ไม่สามารถให้สิทธิ์ได้";
						$arrayResult['RESULT'] = FALSE;
						require_once('../../../../include/exit_footer.php');
					}
				}else{
					$conoracle->rollback();
					$arrayResult['RESPONSE'] = "ไม่พบเมนูหลักของระบบ";
					$arrayResult['RESULT'] = FALSE;
					require_once('../../../../include/exit_footer.php');
				}
			}
		}else{
			$arrayResult['RESPONSE'] = "ไม่พบเมนูหลักของระบบ";
			$arrayResult['RESULT'] = FALSE;
			require_once('../../../../include/exit_footer.php');
		}
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