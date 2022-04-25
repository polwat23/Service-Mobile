<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	$arrayGroupdownload = array();
	$fetchdownloadGroup = $conmysql->prepare("
												SELECT DISTINCT
													g.id_groupdownload,
													g.name,
													g.create_by,
													g.update_date,
													g.update_by,
													g.parent
												FROM
													webcoopgroupdownload g
												LEFT JOIN  webcoopgroupdownload  f
												ON g.id_groupdownload = f.parent
												WHERE
													g.is_use = '1' AND g.parent = '0'");
	$fetchdownloadGroup->execute([':is_use' => $dataComing["is_use"]]);
	
	while($rowdownloadGroup = $fetchdownloadGroup->fetch(PDO::FETCH_ASSOC)){
		$fetchParentGroup = $conmysql->prepare("	
												SELECT DISTINCT
													g.id_groupdownload,
													g.name,
													g.parent,
													g.create_by,
													g.update_date,
													g.update_by

												FROM
													webcoopfiledownload f
												LEFT JOIN webcoopgroupdownload g
												ON f.groupdownload_id = g.id_groupdownload
												WHERE
													g.is_use = '1' AND f.is_use ='1'  AND g.parent = :parent
												");
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
			
			$arrGroupFile["ID_GROUPDOWNLOAD"] = $rowdownloadGroup["id_groupdownload"];
			$arrGroupFile["NAME"] = $rowdownloadGroup["name"];
			$arrGroupFile["PARENT"] = $rowdownloadGroup["parent"];
			$arrGroupFile["CATEGORY"] = $arrayGroupParent;
			$arrayGroupdownload[] = $arrGroupFile;
	}
	$arrayResult["FILE_DATA"] = $arrayGroupdownload;
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>