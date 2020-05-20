<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','username','id_coremenu','status_permission'],$dataComing)){
	if($func->check_permission_core($payload,'admincontrol','permissionmenu')){
		$getIdCoreMenu = $conmysql->prepare("SELECT id_coremenu FROM coremenu WHERE id_coremenu =:id_coremenu ");
		$getIdCoreMenu->execute([
			':id_coremenu' => $dataComing["id_coremenu"]
		]);
		if($getIdCoreMenu->rowCount() > 0){
			$rowidcoremenu = $getIdCoreMenu->fetch(PDO::FETCH_ASSOC);
			$checkPermissionCoremenu = $conmysql->prepare("SELECT id_permission_menu FROM corepermissionmenu 
															WHERE username = :username  and id_coremenu = :id_coremenu");
			$checkPermissionCoremenu->execute([
				':username' => $dataComing["username"],
				':id_coremenu' => $rowidcoremenu["id_coremenu"]
			]);
			if($checkPermissionCoremenu->rowCount() > 0){
				$updatePermitCoreMenu = $conmysql->prepare("UPDATE corepermissionmenu SET
																							is_use = :status_permission
																							WHERE id_coremenu = :id_coremenu AND  username = :username ");
				$updatePermitCoreMenu->execute([
					':id_coremenu' => $rowidcoremenu["id_coremenu"],
					':username' => $dataComing["username"],
					':status_permission' => $dataComing["status_permission"],
				]);
				$row_Permiss_submenu = $checkPermissionCoremenu->fetch(PDO::FETCH_ASSOC);
				$checkSubmenu = $conmysql->prepare("SELECT id_submenu FROM coresubmenu 
						WHERE id_coremenu = :id_coremenu  AND id_menuparent != '0' AND  menu_status ='1'
						ORDER BY id_submenu ASC");
				$checkSubmenu->execute([
					':id_coremenu' => $dataComing["id_coremenu"]
				]);
				$arrayGroupChkSubMenu = array();
				while($rowCheckSubmenu = $checkSubmenu->fetch(PDO::FETCH_ASSOC)){
					$arraycheckSubmenu = $rowCheckSubmenu["id_submenu"];
					$arrayGroupChkSubMenu[]=$arraycheckSubmenu;
				}
				$checkPermissSubmenu = $conmysql->prepare("SELECT id_submenu
																					FROM corepermissionsubmenu
																					WHERE id_permission_menu = :id_permission_menu
																					ORDER BY id_submenu ASC");
				$checkPermissSubmenu->execute([
				    ':id_permission_menu' => $row_Permiss_submenu["id_permission_menu"]
				]);
					$arrayGroupChkPermissSubMenu = array();
					while($rowCheckSubmenu = $checkPermissSubmenu->fetch(PDO::FETCH_ASSOC)){
						$arraycheckPermissSubmenu = $rowCheckSubmenu["id_submenu"];
					    $arrayGroupChkPermissSubMenu[]=$arraycheckPermissSubmenu;
					}
					
					if($arrayGroupChkPermissSubMenu !== $arrayGroupChkSubMenu){
						$not_menu = array_diff($arrayGroupChkSubMenu,$arrayGroupChkPermissSubMenu);
						foreach($not_menu as $value_diff){
							$insertSubMenuPermit = $conmysql->prepare("INSERT INTO corepermissionsubmenu(id_submenu,id_permission_menu,is_use)
															VALUES(:id_submenu,:id_permission_menu,:status_permission)");
							if($insertSubMenuPermit->execute([
								':id_submenu' => $value_diff,
								':id_permission_menu' => $row_Permiss_submenu["id_permission_menu"],
								':status_permission' => $dataComing["status_permission"]
							])){
							}else{
								$arrayResult['RESPONSE'] = "ไม่สามารถให้สิทธิ์ได้";
								$arrayResult['RESULT'] = FALSE;
								echo json_encode($arrayResult);
								exit();
							}
						}
						$arrayResult['DATA_SUB_MENU'] = $arrayGroupChkSubMenu;
						$arrayResult['PERMISSS_SUB_MENU'] = $arrayGroupChkPermissSubMenu;
						$arrayResult['RESULT'] = TRUE;
						echo json_encode($arrayResult);
						exit();
						
					}else{
							$UpdateSubmenuPermit = $conmysql->prepare("UPDATE corepermissionsubmenu SET is_use = :status_permission
																		WHERE id_permission_menu = :id_permission_menu");
							if($UpdateSubmenuPermit->execute([
								':status_permission' => $dataComing["status_permission"],
								':id_permission_menu' => $row_Permiss_submenu["id_permission_menu"],
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
					$insertPermitCoreMenu = $conmysql->prepare("INSERT INTO corepermissionmenu(id_coremenu,username,is_use)
																							VALUES(:id_coremenu,:username,:status_permission)");
					if($insertPermitCoreMenu->execute([
								':id_coremenu' => $rowidcoremenu["id_coremenu"],
								':username' => $dataComing["username"],
								':status_permission' => $dataComing["status_permission"]
							])){
								$checkSubmenu = $conmysql->prepare("SELECT
																								id_permission_menu
																							FROM
																								corepermissionmenu
																							WHERE username = :username AND id_coremenu = :id_coremenu");
								$checkSubmenu->execute([
									':id_coremenu' => $dataComing["id_coremenu"],
									':username' => $dataComing["username"]
								]);
								$id = $checkSubmenu->fetch(PDO::FETCH_ASSOC);
								
								$checkSubmenu = $conmysql->prepare("SELECT id_submenu FROM coresubmenu 
									WHERE id_coremenu =:id_coremenu  AND id_menuparent != '0' AND  menu_status ='1'
									ORDER BY id_submenu ASC");
									$checkSubmenu->execute([
									':id_coremenu' => $dataComing["id_coremenu"]
									]);
									$arrayGroupChkSubMenu = array();
									while($rowCheckSubmenu = $checkSubmenu->fetch(PDO::FETCH_ASSOC)){
										$arraycheckSubmenu = $rowCheckSubmenu["id_submenu"];
										$arrayGroupChkSubMenu[]=$arraycheckSubmenu;
									}
								 	foreach($arrayGroupChkSubMenu as $id_sub){
										$insertSubMenuPermit = $conmysql->prepare("INSERT INTO corepermissionsubmenu(id_submenu,id_permission_menu,is_use)
																VALUES(:id_submenu,:id_permission_menu,:status_permission)");
										if($insertSubMenuPermit->execute([
											':id_submenu' => $id_sub,
											':id_permission_menu' => $id["id_permission_menu"],
											':status_permission' => $dataComing["status_permission"]
										])){
											//$arrayResult['RESULT'] = TRUE;
											//echo json_encode($arrayResult);
										}else{
											
											$arrayResult['RESPONSE'] = "ไม่สามารถให้สิทธิ์ได้";
											$arrayResult['RESULT'] = FALSE;
											echo json_encode($arrayResult);
											exit();
										}
									}
							}else{
								$arrayResult['RESPONSE'] = "ไม่สามารถให้สิทธิ์ได้";
								$arrayResult['RESULT'] = FALSE;
								echo json_encode($arrayResult);
								exit();
					}
					
					$arrayResult['RESPONSE'] = "ไม่มีสิทธ์ในเมนูหลัก";
					$arrayResult['RESULT'] = TRUE;
					echo json_encode($arrayResult);
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