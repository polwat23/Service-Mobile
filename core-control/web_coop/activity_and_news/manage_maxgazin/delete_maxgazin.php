<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'webcoop','managemagazin')){
			$del_file = null;
			$del_headfile = null;
			
			$fetchNewsFileCoop = $conmysql->prepare("SELECT
													file_patch
												FROM
													webcoopfiles
												WHERE
													id_gallery  = :id_gallery ");
			$fetchNewsFileCoop->execute([
				':id_gallery' => $dataComing["id_gallery"]
			]);
				
		
			$arrayGroupFile=[];
			while($rowFile = $fetchNewsFileCoop->fetch(PDO::FETCH_ASSOC)){
				$arrNewsFile = array();	
				$arrNewsFile = $rowFile["file_patch"];
				$arrayGroupFile[] = $arrNewsFile;
			}
			foreach($arrayGroupFile as $file){
				$del_file="../../../../".$file;
				unlink($del_file);
			}
			
			$deleteGallery = $conmysql->prepare("DELETE FROM webcoopgallary WHERE id_gallery = :id_gallery");
			if($deleteGallery->execute([
				':id_gallery' =>  $dataComing["id_gallery"]
			])){
				
				$del_headfile="../../../../".$dataComing["imgPath"];
				$del=unlink($del_headfile);
				
				$arrayResult['del'] = $del;
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามาลบข่าวสารได้ กรุณาติดต่อผู้พัฒนา  ";
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