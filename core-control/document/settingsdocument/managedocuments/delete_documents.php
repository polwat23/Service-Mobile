<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'settingsdocument','managedocuments')){
		$conmysql->beginTransaction();

		//delet folders
		$deleteGrp = $dataComing["checkedFolders"];
		$checkedFolders = $dataComing["checkedFolders"];
		while(count($checkedFolders) > 0){
			$arrDocgrpNo = $checkedFolders;
			$checkedFolders = array();
			$fetchDocumentSystems = $conmysql->prepare("SELECT docgrp_no
														FROM docgroupcontrol 
														WHERE is_use = '1' AND docgrp_ref in ('".implode("','",$arrDocgrpNo)."')");
			$fetchDocumentSystems->execute();
			while($dataSystem = $fetchDocumentSystems->fetch(PDO::FETCH_ASSOC)){
				$systemsArray = array();
				$systemsArray["DOCGRP_NO"] = $dataSystem["docgrp_no"];
				$checkedFolders[] = $dataSystem["docgrp_no"];
				$deleteGrp[] = $systemsArray["DOCGRP_NO"];
			}
		}
		
		if(count($deleteGrp) > 0){
			//delete subfolders
			$deleteDocuments = $conmysql->prepare("UPDATE docgroupcontrol SET is_use = '0' WHERE is_use = '1' AND (docgrp_ref in ('".implode("','",$deleteGrp)."') OR docgrp_no in ('".implode("','",$dataComing["checkedFolders"])."'))");
			if($deleteDocuments->execute()){
				
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE'] = "ไม่สามารถลบเอกสารได้ กรุณาติดต่อผู้พัฒนา 3 ";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
			//delete subfiles
			$deleteFiles = $conmysql->prepare("UPDATE doclistmaster SET doc_status = '-9' WHERE doc_status = '1' AND docgrp_no in ('".implode("','",$deleteGrp)."')");
			if($deleteFiles->execute()){
				
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE'] = "ไม่สามารถลบเอกสารได้ กรุณาติดต่อผู้พัฒนา  2";
				$arrayResult["deleteGrp"] = $deleteFiles->queryString;
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
		}
		
		//delete file
		$deleteCheckedFiles = $dataComing["checkedFiles"];
		if(count($deleteCheckedFiles) > 0){
			$deleteFiles = $conmysql->prepare("UPDATE doclistmaster SET doc_status = '-9' WHERE doc_status = '1' AND doc_no in ('".implode("','",$deleteCheckedFiles)."')");
			if($deleteFiles->execute()){
				
			}else{
				$conmysql->rollback();
				$arrayResult['RESPONSE'] = "ไม่สามารถลบเอกสารได้ กรุณาติดต่อผู้พัฒนา  1";
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
			}
		}
		
		$conmysql->commit();
		$arrayStruc = [
			':menu_name' => "managedocuments",
			':username' => $payload["username"],
			':use_list' =>"delete document",
			':details' => "docno_grp ('".implode("','",$deleteGrp)."') ;doc_no ('".implode("','",$deleteCheckedFiles)."') "
		];
		$log->writeLog('editdocument',$arrayStruc);	
		$arrayResult["RESULT"] = TRUE;
		$arrayResult["deleteGrp"] = $deleteGrp;
		require_once('../../../../include/exit_footer.php');
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

