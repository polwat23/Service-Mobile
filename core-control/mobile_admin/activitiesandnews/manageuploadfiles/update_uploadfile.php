<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_uploadfile'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','manageuploadfiles')){
		$conmysql->beginTransaction();
		
		$update_news= $conmysql->prepare("UPDATE gcuploadfile 
									SET file_name=:file_name,path_file=:path_file,type_upload=:type_upload,update_by=:update_by
									WHERE id_uploadfile = :id_uploadfile");
			if($update_news->execute([
				':file_name' =>  $dataComing["file_name"],
				':path_file' => $dataComing["type_upload"]  == "upload" ? null : $dataComing["path_file"],
				':type_upload' => $dataComing["type_upload"]  == "upload" ? "0" : "1",
				':update_by' => $payload["username"],
				':id_uploadfile' =>  $dataComing["id_uploadfile"]
			])){
				$last_id = $dataComing["id_uploadfile"];
				
				// start เพิ่มไฟล์เเนบ
				if($dataComing["type_upload"] == "upload"){
					
					if(isset($dataComing["file_upload"]) && $dataComing["file_upload"] != null){
						$destination = __DIR__.'/../../../../resource/uploadfile';
						$random_text = $lib->randomText('all',6);
						$file_name = 'uploadfile_'.$last_id;
						if(!file_exists($destination)){
							mkdir($destination, 0777, true);
						}
						$createImage = $lib->base64_to_pdf($dataComing["file_upload"],$file_name,$destination,null);
						if($createImage){
							$pathFile = $config["URL_SERVICE"]."resource/uploadfile/".$createImage["normal_path"];
							
							if(isset($pathFile) && $pathFile != null){
								$pathFile = $pathFile."?".$random_text;
							}
							//update file sql
							$update_news= $conmysql->prepare("UPDATE gcuploadfile SET 
																path_file = :path_file
														  WHERE id_uploadfile = :id_uploadfile");
							if($update_news->execute([
								':id_uploadfile' =>  $last_id,
								':path_file' => $pathFile ?? null
							])){
								$conmysql->commit();
								$arrayResult["RESULT"] = TRUE;
								echo json_encode($arrayResult);
								exit();
							}else{
								$conmysql->rollback();
								$arrayResult['RESPONSE'] = "ไม่สามารถอัพโหลดไฟล์แนบได้ กรุณาติดต่อผู้พัฒนา";
								$arrayResult['RESULT'] = FALSE;
								echo json_encode($arrayResult);
								exit();
							}
				
							$conmysql->rollback();
							$arrayResult['DATA'] = [
								':id_uploadfile' =>  $last_id,
								':path_file' => $pathFile ?? null
							];
							$arrayResult['RESULT'] = FALSE;
							echo json_encode($arrayResult);
							exit();
						}else{
							$conmysql->rollback();
							$arrayResult['RESPONSE_MESSAGE'] = "ไม่สามารถอัพโหลดไฟล์แนบได้ กรุณาติดต่อผู้พัฒนา";
							$arrayResult['RESULT'] = FALSE;
							echo json_encode($arrayResult);
							exit();
						}
					}else{
						$conmysql->commit();
						$arrayResult["RESULT"] = TRUE;
						echo json_encode($arrayResult);
						exit();
					}
				}else{
					$conmysql->commit();
					$arrayResult["RESULT"] = TRUE;
					echo json_encode($arrayResult);
					exit();
				}
				//end เพิ่มไฟล์เเนบ

			}else{
				$arrayResult['RESPONSE'] = "ไม่สามารถแก้ไขไฟล์ได้ กรุณาติดต่อผู้พัฒนา ";
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