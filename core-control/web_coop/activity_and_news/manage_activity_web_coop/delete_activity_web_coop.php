<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'webcoop','manageactivitywebcoop')){
		function delAllFileInfolder($folder=''){
			if (is_dir($folder)&&$folder!='') {
				//Get a list of all of the file names in the folder.
				$files = glob($folder . '/*');
				//Loop through the file list.
				foreach($files as $file){
					//Make sure that this is a file and not a directory.
					if(is_file($file)){
						//Use the unlink function to delete the file.
						unlink($file);
					}
				}
			}
		}
		$folder = '../../../../resource/gallery_web_coop/activity/'.$dataComing["title"];
			delAllFileInfolder($folder);
			if (is_dir($folder)&&$folder!='') {
			rmdir($folder);
		}
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