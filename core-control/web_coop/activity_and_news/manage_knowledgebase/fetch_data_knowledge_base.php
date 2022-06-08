<?php
require_once('../../../autoload.php');
if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	$arrayGroup = array();
	$submenu = array();
	$arrayGroupMenuAll = array();
	$fetchMenu = $conmysql->prepare(" SELECT
										icon,
										id_coremenu,
										coremenu_name,
										is_use,
										create_by,
										create_date,
										update_date,
										update_by
									FROM
										webcoopcoremenuknowledgebase
									WHERE
										is_use <> '-9'");
	$fetchMenu->execute();
	while($rowParentMemu = $fetchMenu->fetch(PDO::FETCH_ASSOC)){
		$arrCoreMenu["ID_COREMENU"] = $rowParentMemu["id_coremenu"];
		$arrCoreMenu["COREMENU_NAME"] = $rowParentMemu["coremenu_name"];
		$arrCoreMenu["ICON"] = $rowParentMemu["icon"];
        $arrCoreMenu["IS_USE"] = $rowParentMemu["is_use"];
		$arrCoreMenu["UPDATE_BY"] = $rowParentMemu["update_by"];
		$arrCoreMenu["CREATE_BY"] = $rowParentMemu["create_by"];
		$arrCoreMenu["CREATE_DATE"] = $lib->convertdate($rowParentMemu["create_date"],'d m Y',true); 
		$arrCoreMenu["UPDATE_DATE"] = $lib->convertdate($rowParentMemu["update_date"],'d m Y',true); 
			
		$fetchParentMenu = $conmysql->prepare("SELECT
													id_submenu,
													icon,
													submenu_name,
													coremenuboard_id,
													create_by,
													create_date,
													update_date,
													update_by
												FROM webcoopsubknowledgebase
												WHERE coremenuboard_id = :coremenuboard_id AND is_use <> '-9'
												ORDER BY create_date");
		$fetchParentMenu->execute([
			':coremenuboard_id' => $rowParentMemu["id_coremenu"]
		]);
		$parentMenue = array();
		while($rowParentMemu = $fetchParentMenu->fetch(PDO::FETCH_ASSOC)){
			$arrParentMenu["ID_SUBMENU"] = $rowParentMemu["id_submenu"];
			$arrParentMenu["ICON"] = $rowParentMemu["icon"];
			$arrParentMenu["SUBMENU_NAME"] = $rowParentMemu["submenu_name"];
			$arrParentMenu["COREMENUBOARD_ID"] = $rowParentMemu["coremenuboard_id"];
			$arrParentMenu["UPDATE_BY"] = $rowParentMemu["update_by"];
			$arrParentMenu["CREATE_BY"] = $rowParentMemu["create_by"];
			$arrParentMenu["CREATE_DATE"] = $lib->convertdate($rowParentMemu["create_date"],'d m Y',true); 
			$arrParentMenu["UPDATE_DATE"] = $lib->convertdate($rowParentMemu["update_date"],'d m Y',true); 
			
			$fetchKnowledgeData = $conmysql->prepare("SELECT
															id_knowledge,
															submenuknowledge_id,
															title,
															detail,
															img_url,
															file_url,
															create_date,
															update_date,
															create_by,
															update_by
														FROM
															webcoopknowledgebase
														WHERE submenuknowledge_id = :id_submenu AND is_use <> '-9'
														ORDER BY create_date
														");
				$fetchKnowledgeData->execute([
					':id_submenu' => $rowParentMemu["id_submenu"]
				]);
				$groupDataKnowledge = array();
				while($rowKnowledgeData = $fetchKnowledgeData->fetch(PDO::FETCH_ASSOC)){
					$name = explode('/',$rowKnowledgeData["file_url"]);
					$fileName = $name[7];
					$arrKnowledge["ID_KNOWLEDGE"] = $rowKnowledgeData["id_knowledge"];
					$arrKnowledge["SUBMENUKNOWLEDGE_ID"] = $rowKnowledgeData["submenuknowledge_id"];
					$arrKnowledge["TITLE"] = $rowKnowledgeData["title"];
					$arrKnowledge["DETAIL"] = $rowKnowledgeData["detail"];
					$arrKnowledge["IMG_URL"] = $rowKnowledgeData["img_url"];
					$arrKnowledge["FILE_NAME"] = $fileName;
					$arrKnowledge["FILE_URL"] = $rowKnowledgeData["file_url"];
					$arrKnowledge["CREATE_BY"] = $rowKnowledgeData["create_by"];
					$arrKnowledge["UPDATE_BY"] = $rowKnowledgeData["update_by"];
					$arrKnowledge["CREATE_DATE"] = $lib->convertdate($rowKnowledgeData["create_date"],'d m Y',true); 
					$arrKnowledge["UPDATE_DATE"] = $lib->convertdate($rowKnowledgeData["update_date"],'d m Y',true); 
					$groupDataKnowledge[] = $arrKnowledge;
				}
				
			$arrParentMenu["DATA"] = $groupDataKnowledge;	
			$parentMenue[] = $arrParentMenu;
		}	
	
		$arrCoreMenu["TITLE"] = $parentMenue;
		$arrayGroup[] = $arrCoreMenu;
	}
	
	$fetchmenuknowledgebaseGroup = $conmysql->prepare("SELECT
															id_submenu,
															icon,
															submenu_name,
															coremenuboard_id,
															create_by,
															create_date,
															update_date,
															update_by
														FROM webcoopsubknowledgebase
														WHERE is_use <> '-9'
											");
	$fetchmenuknowledgebaseGroup->execute();
	
	while($rowmenuknowledgebaseGroup = $fetchmenuknowledgebaseGroup->fetch(PDO::FETCH_ASSOC)){
		$icon = array();
		$arrNewsFile["url"] = $rowmenuknowledgebaseGroup["icon"];;
		$arrNewsFile["status"] = "old";
		$icon[] = $arrNewsFile;
		
		$arrGroupMenuAll["ID_SUBMENU"] = $rowmenuknowledgebaseGroup["id_submenu"];
		$arrGroupMenuAll["COREMENUBOARD_ID"] = $rowmenuknowledgebaseGroup["coremenuboard_id"];
		$arrGroupMenuAll["ICON"] = $rowmenuknowledgebaseGroup["icon"];
		$arrGroupMenuAll["SUBMENU_NAME"] = $rowmenuknowledgebaseGroup["submenu_name"];
		$arrGroupMenuAll["UPDATE_BY"] = $rowmenuknowledgebaseGroup["update_by"];
		$arrGroupMenuAll["CREATE_BY"] = $rowmenuknowledgebaseGroup["create_by"];
		$arrGroupMenuAll["CREATE_DATE"] = $lib->convertdate($rowmenuknowledgebaseGroup["create_date"],'d m Y',true); 
		$arrGroupMenuAll["UPDATE_DATE"] = $lib->convertdate($rowmenuknowledgebaseGroup["update_date"],'d m Y',true);  
		$arrayGroupMenuAll[] = $arrGroupMenuAll;
	}
	
	$fetchCoreMenu = $conmysql->prepare("SELECT DISTINCT
											c.icon,
											c.id_coremenu,
											c.coremenu_name,
											c.is_use,
											c.create_by,
											c.create_date,
											c.update_date,
											c.update_by
										FROM
											webcoopcoremenuknowledgebase c
										LEFT JOIN webcoopsubknowledgebase s ON
											c.id_coremenu = s.coremenuboard_id
										WHERE
											c.is_use <> '-9' AND s.is_use <> '-9'
												
									");
	$fetchCoreMenu->execute();
	
	while($rowCoreMenu = $fetchCoreMenu->fetch(PDO::FETCH_ASSOC)){
		$arrCoreMenuKnowledge["ID_COREMENU"] = $rowCoreMenu["id_coremenu"];
		$arrCoreMenuKnowledge["ICON"] = $rowCoreMenu["icon"];
		$arrCoreMenuKnowledge["COREMENU_MAME"] = $rowCoreMenu["coremenu_name"];
        $arrCoreMenuKnowledge["IS_USE"] = $rowCoreMenu["is_use"];
		$arrCoreMenuKnowledge["UPDATE_BY"] = $rowCoreMenu["update_by"];
		$arrCoreMenuKnowledge["CREATE_BY"] = $rowCoreMenu["create_by"];
		$arrCoreMenuKnowledge["CREATE_DATE"] = $lib->convertdate($rowCoreMenu["create_date"],'d m Y',true); 
		$arrCoreMenuKnowledge["UPDATE_DATE"] = $lib->convertdate($rowCoreMenu["update_date"],'d m Y',true);  
		$arrayGroupCoreMenu[] = $arrCoreMenuKnowledge;
	}
	
	$arrayResult["KNOWLEDGE_BASE_DATA"] = $arrayGroup;
	$arrayResult["SUB_MENU_DATA"] = $arrayGroupMenuAll;
	$arrayResult["CORE_MENU_DATA"] = $arrayGroupCoreMenu;
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>