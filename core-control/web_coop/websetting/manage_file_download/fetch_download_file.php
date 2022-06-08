<?php
require_once('../../../autoload.php');
if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	$arrayGroup = array();
	$arrayGroupFile = array();
	$arrayGroupdownload = array();

	$fetchdownloadGroup = $conmysql->prepare("
												SELECT 
													id_groupdownload,
													name,
													create_by,
													update_date,
													update_by,
													parent
												FROM
													webcoopgroupdownload 
												
												WHERE
													is_use = '1' AND parent = '0'");
	$fetchdownloadGroup->execute([':is_use' => $dataComing["is_use"]]);
	
	while($rowdownloadGroup = $fetchdownloadGroup->fetch(PDO::FETCH_ASSOC)){
		$fetchParentGroup = $conmysql->prepare("
													
												SELECT 
													id_groupdownload,
													name,
													parent,
													create_by,
													update_date,
													update_by
												FROM
													webcoopgroupdownload 
													WHERE
														is_use = '1' AND parent = :parent");
		$fetchParentGroup->execute([':parent' => $rowdownloadGroup["id_groupdownload"]]);
		$arrayGroupParent = array();
		while($rowdownloadParent = $fetchParentGroup->fetch(PDO::FETCH_ASSOC)){
			
			$fetchdownload = $conmysql->prepare("SELECT
														id_filedownload,
														groupdownload_id,
														file_name,
														file_path,
														file_url,
														create_date,
														update_date,
														create_by,
														update_by
													FROM
														webcoopfiledownload
													WHERE 
														groupdownload_id = :id_groupdownload AND is_use='1'");
			$fetchdownload ->execute([
				':id_groupdownload'=>$rowdownloadParent["id_groupdownload"]]);
			$groupfile = array();
		 
				while($rowdownload = $fetchdownload->fetch(PDO::FETCH_ASSOC)){
					$arrFileDownload["ID_FILEDOWNLOAD"] = $rowdownload["id_filedownload"];
					$arrFileDownload["GROUPDOWNLOAD_ID"] = $rowdownload["groupdownload_id"];
					$arrFileDownload["FILE_PATH"] = $rowdownload["file_path"];
					$arrFileDownload["FILE_URL"] = $rowdownload["file_url"];
					$arrFileDownload["FILE_NAME"] = $rowdownload["file_name"];
					$arrFileDownload["CREATE_DATE"] = $lib->convertdate($rowdownload["create_date"],'d m Y',true); 
					$arrFileDownload["UPDATE_DATE"] = $lib->convertdate($rowdownload["update_date"],'d m Y',true); 
					$groupfile[]=$arrFileDownload;
				}
				
					$arrGroupParent["ID_GROUPDOWNLOAD"] = $rowdownloadParent["id_groupdownload"];
					$arrGroupParent["NAME"] = $rowdownloadParent["name"];
					$arrGroupParent["PARENT"] = $rowdownloadParent["parent"];
					$arrGroupParent["FILE"] = $groupfile;
					$arrGroupParent["UPDATE_BY"] = $rowdownloadParent["update_by"];
					$arrGroupParent["CREATE_BY"] = $rowdownloadParent["create_by"];
					$arrGroupParent["CREATE_DATE"] = $lib->convertdate($rowdownloadParent["create_date"],'d m Y',true); 
					$arrGroupParent["UPDATE_DATE"] = $lib->convertdate($rowdownloadParent["update_date"],'d m Y',true);  
					$arrayGroupParent[] = $arrGroupParent;
		}
			
			$arrGroupStatement["ID_GROUPDOWNLOAD"] = $rowdownloadGroup["id_groupdownload"];
			$arrGroupStatement["NAME"] = $rowdownloadGroup["name"];
			$arrGroupStatement["PARENT"] = $rowdownloadGroup["parent"];
			$arrGroupStatement["CATEGORY"] = $arrayGroupParent;
			$arrayGroupdownload[] = $arrGroupStatement;
	}
	
	$arrayGroup = array();
	$fetchbalanceSheetGroup = $conmysql->prepare("SELECT DISTINCT
													g.id_groupdownload,
													g.name,
													g.parent,
													g.create_by,
													g.create_date,
													g.update_date,
													g.update_by
												FROM
													webcoopgroupdownload g
												LEFT JOIN webcoopgroupdownload n ON
													g.id_groupdownload = n.parent
												WHERE
													g.is_use <> '-9' AND n.is_use <> '-9' AND g.parent = '0'
											");
	$fetchbalanceSheetGroup->execute();
	while($rowGroupFile = $fetchbalanceSheetGroup->fetch(PDO::FETCH_ASSOC)){
		$arrGroup["ID_GROUPDOWNLOAD"] = $rowGroupFile["id_groupdownload"];
		$arrGroup["PARENT"] = $rowGroupFile["parent"];
		$arrGroup["NAME"] = $rowGroupFile["name"];
		$arrGroup["UPDATE_BY"] = $rowGroupFile["update_by"];
		$arrGroup["CREATE_BY"] = $rowGroupFile["create_by"];
		$arrGroup["CREATE_DATE"] = $lib->convertdate($rowGroupFile["create_date"],'d m Y',true); 
		$arrGroup["UPDATE_DATE"] = $lib->convertdate($rowGroupFile["update_date"],'d m Y',true);  
		$arrayGroupFile[] = $arrGroup;
	}
	
		$arrayTitle = array();
	$fetchTitle = $conmysql->prepare("SELECT
												id_groupdownload,
												name,
												parent,
												create_by,
												create_date,
												update_date,
												update_by
											FROM
												webcoopgroupdownload
											WHERE is_use <> '-9' AND parent <>'0'
											");
	$fetchTitle->execute();
	while($rowTitle = $fetchTitle->fetch(PDO::FETCH_ASSOC)){
		$arrGroupTitle["ID_GROUPDOWNLOAD"] = $rowTitle["id_groupdownload"];
		$arrGroupTitle["NAME"] = $rowTitle["name"];
		$arrGroupTitle["PARENT"] = $rowTitle["parent"];
		$arrGroupTitle["UPDATE_BY"] = $rowTitle["update_by"];
		$arrGroupTitle["CREATE_BY"] = $rowTitle["create_by"];
		$arrGroupTitle["CREATE_DATE"] = $lib->convertdate($rowTitle["create_date"],'d m Y',true); 
		$arrGroupTitle["UPDATE_DATE"] = $lib->convertdate($rowTitle["update_date"],'d m Y',true);  
		$arrayTitle[] = $arrGroupTitle;
	}

	$arrayResult["FILE_DATA"] = $arrayGroupdownload;
	$arrayResult["GROUP_FILE_DATA"] = $arrayGroupFile;
	$arrayResult["DOWNLOAD_TITLE_DATA"] = $arrayTitle;
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);
	

}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}

		
?>