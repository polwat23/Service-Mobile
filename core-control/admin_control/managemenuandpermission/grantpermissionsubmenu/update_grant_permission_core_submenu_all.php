<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','username','status_permission'],$dataComing)){
	if($func->check_permission_core($payload,'admincontrol','permissionmenu')){
		$arrayGroupCoreMenu = array();
		$arrayGroupSubMenuId = array();
		$insertSubMenuPermit = $conmysql->prepare("DELETE FROM corepermissionmenu WHERE username = :username");
										if($insertSubMenuPermit->execute([
												':username' => $dataComing["username"]
										])){
												$fetchCoreMenu = $conmysql->prepare("SELECT id_coremenu FROM coremenu WHERE coremenu_status = '1'");
												$fetchCoreMenu->execute();
												while($row_coreMenu = $fetchCoreMenu->fetch(PDO::FETCH_ASSOC)){
													$arr_coreMenu = array();
													$arr_coreMenu = $row_coreMenu["id_coremenu"];
													$arrayGroupCoreMenu[] = $arr_coreMenu;
												}
												$fetchSubMenu = $conmysql->prepare("SELECT id_submenu FROM coresubmenu 
																											WHERE  id_menuparent != '0' AND  menu_status ='1'
																											ORDER BY id_submenu ASC");
												$fetchSubMenu->execute();
												while($row_subMenu = $fetchSubMenu->fetch(PDO::FETCH_ASSOC)){
													$arr_subMenu = array();
													$arr_subMenu = $row_subMenu["id_submenu"];
													$arrayGroupSubMenu[] = $arr_subMenu;
												}
														$sql_insert_permiss_core = array();
														$sql_insert_permiss_subcore = array();
														foreach($arrayGroupCoreMenu as $coreMenu_id){
															$bulkInsert[] = "('".$coreMenu_id."','".$dataComing["username"]."','".$dataComing["status_permission"]."')";
															$sql_insert_permiss_core= $bulkInsert;
														}
														$insertPermitCoreMenu = $conmysql->prepare("INSERT INTO corepermissionmenu(id_coremenu,username,is_use)
																							VALUES".implode(',',$sql_insert_permiss_core));
														
															if($insertPermitCoreMenu->execute()){
																foreach($arrayGroupCoreMenu as $coreMenu_id){
																		$checkSubmenu = $conmysql->prepare("SELECT id_permission_menu 
																																	FROM corepermissionmenu 
																																	WHERE username = :username  AND id_coremenu = :id_coremenu");
																	$checkSubmenu->execute([
																		':username' => $dataComing["username"],
																		':id_coremenu' =>  $coreMenu_id
																	]);	
																		$id = $checkSubmenu->fetch(PDO::FETCH_ASSOC);
																		$fetchSubMenu_id = $conmysql->prepare("SELECT id_submenu FROM coresubmenu 
																											WHERE  id_menuparent != '0' AND  menu_status ='1' AND id_coremenu = :id_coremenu ");
																		$fetchSubMenu_id->execute([':id_coremenu' =>  $coreMenu_id]);
																		while($row_subMenu = $fetchSubMenu_id->fetch(PDO::FETCH_ASSOC)){
																			$arr_subMenu_id = array();
																			//$arr_subMenu_id = $row_subMenu["id_submenu"];
																			$arr_subMenu_id= "('".$row_subMenu["id_submenu"]."','".$id["id_permission_menu"]."','".$dataComing["status_permission"]."')";
																			$arrayGroupSubMenuId[] = $arr_subMenu_id;
																		}
																		
																		foreach($arrayGroupSubMenu as $subMenu_id){
																				$bulkSubInsert[] = "('".$subMenu_id."','".$id["id_permission_menu"].",'".$dataComing["status_permission"]."')";
																				$sql_insert_permiss_subcore=$bulkSubInsert;
																				// id_submenu,id_permission_submenu,is_use
																		}
																	
																}	
																
															}else{
																$arrayResult['RESPONSE'] = "ไม่สามารถให้สิทธิ์ได้";
																$arrayResult['RESULT'] = FALSE;
																echo json_encode($arrayResult);
																exit();
															}	
															
																$insert_permiss_subcore = $conmysql->prepare("INSERT INTO corepermissionsubmenu (id_submenu,id_permission_menu,is_use)
																											VALUES".implode(',',$arrayGroupSubMenuId));
																if($insert_permiss_subcore->execute()){
														
																$arrayResult['SQL_insert_SubMenu'] = $sql_insert_permiss_subcore;
																$arrayResult['CORE_MENU'] = $arrayGroupCoreMenu;
																$arrayResult['SUB_MENU'] = $arrayGroupSubMenu;
																$arrayResult['idSub'] = implode(',',$arrayGroupSubMenuId);
																$arrayResult['SQL_CORE_MENU'] = $sql_insert_permiss_core;
																$arrayResult['RESULT'] = TRUE;
																echo json_encode($arrayResult);
																exit();   
																}else{
																	$arrayResult['idSub'] = implode(',',$arrayGroupSubMenuId);
																	$arrayResult['RESPONSE'] = "ไม่สามารถให้สิทธิ์ได้";
																	$arrayResult['RESULT'] = FALSE;
																	echo json_encode($arrayResult);
																	exit();
																}
																
										}else{
											$arrayResult['RESPONSE'] = "ไม่สามารถให้สิทธิ์ได้";
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