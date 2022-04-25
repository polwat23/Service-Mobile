<?php
require_once('../autoload.php');
if($lib->checkCompleteArgument(['unique_id','id_knowledge'],$dataComing)){

	$groupDataKnowledge = array();	
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
												WHERE id_knowledge = :id_knowledge AND is_use <> '-9'
												ORDER BY create_date
												");
		$fetchKnowledgeData->execute([
			':id_knowledge' => $dataComing["id_knowledge"]
		]);

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
			$groupDataKnowledge = $arrKnowledge;
		}
		
	$arrayResult["KNOWLEDGE_DATA"] = $groupDataKnowledge;	
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>