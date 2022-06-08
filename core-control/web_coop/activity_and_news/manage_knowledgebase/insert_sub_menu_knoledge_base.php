<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','coremenuboard_id'],$dataComing)){
	
	if(isset($dataComing["icon"]) && $dataComing["icon"] != null){
		$destination = __DIR__.'/../../../../resource/gallery_web_coop/knowledge/'.$forderAcc;
		$file_name = $lib->randomText('all',6);
		if(!file_exists($destination)){
			mkdir($destination, 0777, true);
		}
		$createImage = $lib->base64_to_img($dataComing["icon"],$file_name,$destination,null);
		if($createImage == 'oversize'){
			$arrayResult['RESPONSE'] = "รูปภาพที่ต้องการส่งมีขนาดใหญ่เกินไป";
			$arrayResult['RESULT'] = FALSE;
			echo json_encode($arrayResult);
			exit();
		}else{
			if($createImage){
				$icon = $config["URL_SERVICE"]."resource/gallery_web_coop/knowledge/".$forderAcc."/".$createImage["normal_path"];
			}else{
				$arrayResult['RESPONSE'] = "นามสกุลไฟล์ไม่ถูกต้อง";
				$arrayResult['RESULT'] = FALSE;
				echo json_encode($arrayResult);
				exit();
			}
		}
	}
	
	$insert_sub_menu_knowledge = $conmysql->prepare("INSERT INTO webcoopsubknowledgebase(
										coremenuboard_id,
										icon,
										submenu_name,
										create_by,
										update_by)
									VALUES(
										:coremenuboard_id,
										:icon,
										:submenu_name,
										:create_by,
										:update_by
									)");
	if($insert_sub_menu_knowledge->execute([
		':coremenuboard_id' =>  $dataComing["coremenuboard_id"],
		':icon' =>  $icon,
		':submenu_name' =>  $dataComing["submenu_name"],
		':create_by' =>  $payload["username"],
		':update_by' =>  $payload["username"]
	])){
	    $arrayResult['RESULT'] = TRUE;
		echo json_encode($arrayResult);

	}else{
		$arrayResult['RESPONSE'] = "ไม่สามารถอัพโหลดไฟล์ได้ กรุณาติดต่อผู้พัฒนา ";
		$arrayResult['dataComing'] = [
		':coremenuboard_id' =>  $dataComing["coremenuboard_id"],
		':icon' =>  $icon,
		':submenu_name' =>  $dataComing["submenu_name"],
		':create_by' =>  $payload["username"],
		':update_by' =>  $payload["username"]
	];
		$arrayResult['RESULT'] = FALSE;
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

