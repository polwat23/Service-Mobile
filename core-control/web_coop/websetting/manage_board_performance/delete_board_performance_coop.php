<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'webcoop','manageboardperformance')){
			$del_file = null;
			$del_headfile = null;
			$fetchNewsFileHeadCoop = $conmysql->prepare("SELECT
													img_gallery_path
												FROM
													webcoopgallary
												WHERE
													id_gallery  = :id_gallery ");
			$fetchNewsFileHeadCoop->execute([
				':id_gallery' => $dataComing["id_gallery"]
			]);
			$HeadFile = $fetchNewsFileHeadCoop->fetch(PDO::FETCH_ASSOC);
			$del_headfile="../../../../".$HeadFile["img_gallery_path"];
			unlink($del_headfile);
			
			
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
	
		
		$deletNews = $conmysql->prepare("DELETE FROM webcoopnews WHERE id_webcoopnews = :id_webcoopnews");
		if($deletNews->execute([
			':id_webcoopnews' =>  $dataComing["id_webcoopnews"]
		])){
			$deleteGallery = $conmysql->prepare("DELETE FROM webcoopgallary WHERE id_gallery = :id_gallery");
			if($deleteGallery->execute([
				':id_gallery' =>  $dataComing["id_gallery"]
			])){
				$arrayResult['RESULT'] = TRUE;
				echo json_encode($arrayResult);
			}else{
				$arrayResult['RESPONSE'] = "ไม่สามาลบข่าวสารได้ กรุณาติดต่อผู้พัฒนา  ";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
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