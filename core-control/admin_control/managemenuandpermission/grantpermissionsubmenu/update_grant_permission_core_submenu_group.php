<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','username','id_coremenu','status_permission'],$dataComing)){
	if($func->check_permission_core($payload,'admincontrol','permissionmenu')){
		$getIdCoreMenu = $conoracle->prepare("SELECT id_coremenu FROM coremenu WHERE id_coremenu =:id_coremenu ");
		$getIdCoreMenu->execute([
			':id_coremenu' => $dataComing["id_coremenu"]
		]);
		$rowidcoremenu = $getIdCoreMenu->fetch(PDO::FETCH_ASSOC);
		if(isset($rowidcoremenu["ID_COREMENU"])){
			$checkPermissionCoremenu = $conoracle->prepare("SELECT id_permission_menu FROM corepermissionmenu 
															WHERE username = :username  and id_coremenu = :id_coremenu");
			$checkPermissionCoremenu->execute([
				':username' => $dataComing["username"],
				':id_coremenu' => $rowidcoremenu["ID_COREMENU"]
			]);
			$row_Permiss_submenu = $checkPermissionCoremenu->fetch(PDO::FETCH_ASSOC);
			if(isset($row_Permiss_submenu["ID_PERMISSION_MENU"])){
				$conoracle->beginTransaction();
				$updatePermitCoreMenu = $conoracle->prepare("UPDATE corepermissionmenu SET
																							is_use = :status_permission
																							WHERE id_coremenu = :id_coremenu AND  username = :username ");
				$updatePermitCoreMenu->execute([
					':id_coremenu' => $rowidcoremenu["ID_COREMENU"],
					':username' => $dataComing["username"],
					':status_permission' => $dataComing["status_permission"],
				]);
				$checkSubmenu = $conoracle->prepare("SELECT id_submenu FROM coresubmenu 
						WHERE id_coremenu = :id_coremenu  AND id_menuparent != '0' AND  menu_status ='1'
						ORDER BY id_submenu ASC");
				$checkSubmenu->execute([
					':id_coremenu' => $dataComing["id_coremenu"]
				]);
				$arrayGroupChkSubMenu = array();
				while($rowCheckSubmenu = $checkSubmenu->fetch(PDO::FETCH_ASSOC)){
					$arraycheckSubmenu = $rowCheckSubmenu["ID_SUBMENU"];
					$arrayGroupChkSubMenu[] = $arraycheckSubmenu;
				}
				$checkPermissSubmenu = $conoracle->prepare("SELECT id_submenu
																					FROM corepermissionsubmenu
																					WHERE id_permission_menu = :id_permission_menu
																					ORDER BY id_submenu ASC");
				$checkPermissSubmenu->execute([
				    ':id_permission_menu' => $row_Permiss_submenu["ID_PERMISSION_MENU"]
				]);
					$arrayGroupChkPermissSubMenu = array();
					while($rowCheckSubmenu = $checkPermissSubmenu->fetch(PDO::FETCH_ASSOC)){
						$arraycheckPermissSubmenu = $rowCheckSubmenu["ID_SUBMENU"];
					    $arrayGroupChkPermissSubMenu[] = $arraycheckPermissSubmenu;
					}
					
					if($arrayGroupChkPermissSubMenu !== $arrayGroupChkSubMenu){
						$bulk_insert = array();
						$not_menu = array_diff($arrayGroupChkSubMenu,$arrayGroupChkPermissSubMenu);
						foreach($not_menu as $value_diff){
							$id_permission_submenu  = $func->getMaxTable('id_permission_submenu' , 'corepermissionsubmenu');
							$bulk_insert[] = "(".$id_permission_submenu.",".$value_diff.",".$row_Permiss_submenu["ID_PERMISSION_MENU"].",'".$dataComing["status_permission"]."')";
						}
						$insertSubMenuPermit = $conoracle->prepare("INSERT INTO corepermissionsubmenu(id_permission_submenu,id_submenu,id_permission_menu,is_use)
																VALUES".implode(',',$bulk_insert));
						if($insertSubMenuPermit->execute()){
							$conoracle->commit();
							$arrayStruc = [
								':menu_name' => "permissionmenu",
								':username' => $payload["username"],
								':use_list' => "change permission menu",
								':details' => 'insert permission group id '.$row_Permiss_submenu["ID_PERMISSION_MENU"].' to status : '.$dataComing["status_permission"].' of username : '.$dataComing["username"]
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
						$UpdateSubmenuPermit = $conoracle->prepare("UPDATE corepermissionsubmenu SET is_use = :status_permission
																	WHERE id_permission_menu = :id_permission_menu");
						if($UpdateSubmenuPermit->execute([
							':status_permission' => $dataComing["status_permission"],
							':id_permission_menu' => $row_Permiss_submenu["ID_PERMISSION_MENU"],
						])){
							$conoracle->commit();
							$arrayStruc = [
								':menu_name' => "permissionmenu",
								':username' => $payload["username"],
								':use_list' => "change permission menu",
								':details' => 'change permission group id '.$row_Permiss_submenu["ID_PERMISSION_MENU"].' to status : '.$dataComing["status_permission"].' of username : '.$dataComing["username"]
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
					}
			}else{
				$conoracle->beginTransaction();
				$id_permission_menu  = $func->getMaxTable('id_permission_menu' , 'corepermissionmenu');
				$insertPermitCoreMenu = $conoracle->prepare("INSERT INTO corepermissionmenu(id_permission_menu,id_coremenu,username,is_use)
																						VALUES(:id_permission_menu,:id_coremenu,:username,:status_permission)");
				if($insertPermitCoreMenu->execute([
					':id_permission_menu' => $id_permission_menu,
					':id_coremenu' => $rowidcoremenu["ID_COREMENU"],
					':username' => $dataComing["username"],
					':status_permission' => $dataComing["status_permission"]
				])){
					$idSubMenu = $conoracle->lastInsertId();
					$checkSubmenu = $conoracle->prepare("SELECT id_submenu FROM coresubmenu 
																			WHERE id_coremenu =:id_coremenu  AND id_menuparent != '0' AND  menu_status ='1'
																			ORDER BY id_submenu ASC");
					$checkSubmenu->execute([
						':id_coremenu' => $dataComing["id_coremenu"]
					]);
					$arrayGroupChkSubMenu = array();
					while($rowCheckSubmenu = $checkSubmenu->fetch(PDO::FETCH_ASSOC)){
						$arrayGroupChkSubMenu[] = $rowCheckSubmenu["ID_SUBMENU"];
					}
					$bulk_insert = array();
					foreach($arrayGroupChkSubMenu as $id_sub){
						$id_permission_submenu  = $func->getMaxTable('id_permission_submenu' , 'corepermissionsubmenu');
						$bulk_insert[] = "(".$id_permission_submenu.",".$id_sub.",".$idSubMenu.",'".$dataComing["status_permission"]."')";
					}
					$insertSubMenuPermit = $conoracle->prepare("INSERT INTO corepermissionsubmenu(id_permission_submenu,id_submenu,id_permission_menu,is_use)
																				VALUES".implode(',',$bulk_insert));
					if($insertSubMenuPermit->execute()){
						$conoracle->commit();
						$arrayStruc = [
							':menu_name' => "permissionmenu",
							':username' => $payload["username"],
							':use_list' => "change permission menu",
							':details' => 'insert permission group id '.$idSubMenu.' to status : '.$dataComing["status_permission"].' of username : '.$dataComing["username"]
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
					$arrayResult['RESPONSE'] = "ไม่สามารถให้สิทธิ์ได้";
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