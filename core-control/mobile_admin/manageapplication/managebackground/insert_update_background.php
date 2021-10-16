<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managebackground',$conoracle)){
		
		$fetchBG = $conoracle->prepare("SELECT id_background,image,update_date,is_use,update_by FROM gcconstantbackground");
								
		$fetchBG->execute();
		$arrayGroup = array();
		while($rowbg = $fetchBG->fetch(PDO::FETCH_ASSOC)){
			$arrGroupBg = array();
			$arrGroupBg["ID_BACKGROUND"] = $rowbg["ID_BACKGROUND"];
			$arrGroupBg["IMAGE"] = $rowbg["IMAGE"];
			$arrGroupBg["UPDATE_DATE"] = $rowbg["UPDATE_DATE"];
			$arrGroupBg["IS_USE"] = $rowbg["IS_USE"];
			$arrGroupBg["UPDATE_BY"] = $rowbg["UPDATE_BY"];
			$arrayGroup[] = $arrGroupBg;
		}
		
			$encode_image = $dataComing["image"];
			$destination = __DIR__.'/../../../../resource/background';
			$random_text = $lib->randomText('all',6);
			$file_name = 'appbg';
			if(!file_exists($destination)){
				mkdir($destination, 0777, true);
			}
			$createBg = $lib->base64_to_img($encode_image,$file_name,$destination,$webP);
			if($createBg == 'oversize'){
				$arrayResult['RESPONSE_CODE'] = "WS0008";
				$arrayResult['RESPONSE_MESSAGE'] = $configError[$arrayResult['RESPONSE_CODE']][0][$lang_locale];
				$arrayResult['RESULT'] = FALSE;
				require_once('../../../../include/exit_footer.php');
				
			}else{
				if($createBg){
					if(count($arrayGroup) > 0){
						$path_bg = '/resource/background/'.$createBg["normal_path"];
						$insertIntoInfo = $conoracle->prepare("UPDATE gcconstantbackground SET image=:path_bg,update_by=:username,is_use = '1' WHERE id_background = :id_background");
						if($insertIntoInfo->execute([
							':path_bg' => $path_bg.'?'.$random_text,
							'username' => $payload["username"],
							'id_background' => $arrayGroup[0]["ID_BACKGROUND"]
						])){
							$arrayResult['RESULT'] = TRUE;
							require_once('../../../../include/exit_footer.php');
						}else{
							$arrayResult['RESPONSE_MESSAGE'] = "อัพโหลดรุปภาพไม่สำเร็จ";
							$arrayResult['RESULT'] = FALSE;
							require_once('../../../../include/exit_footer.php');
							
						}
					}else{
						$path_bg = '/resource/background/'.$createBg["normal_path"];
						$id_background = $func->getMaxTable('id_background' , 'gcconstantbackground',$conoracle);
						$insert_news = $conoracle->prepare("INSERT INTO gcconstantbackground(id_background,image,update_by) VALUES (:id_background, :path_bg,:username)");
						if($insert_news->execute([
								':id_background' => $id_background,
								':path_bg' => $path_bg,
								':username' => $payload["username"]
						])){
							$arrayResult['RESULT'] = TRUE;
							require_once('../../../../include/exit_footer.php');
						}else{
							$arrayResult['RESPONSE_MESSAGE'] = "อัพโหลดรุปภาพไม่สำเร็จ";
							$arrayResult['RESULT'] = FALSE;
							require_once('../../../../include/exit_footer.php');
							
						}
					}
				}else{
					$arrayResult['RESPONSE_MESSAGE'] = "อัพโหลดรุปภาพไม่สำเร็จ";
					$arrayResult['RESULT'] = FALSE;
					require_once('../../../../include/exit_footer.php');
					
				}
			}
		
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