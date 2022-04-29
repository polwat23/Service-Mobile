<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id','id_rule'],$dataComing)){
	if($func->check_permission_core($payload,'mobileadmin','managerule')){
		if(isset($dataComing["rulefile_base64"]) && $dataComing["rulefile_base64"] != ""){
			$destination = __DIR__.'/../../../../resource/pdf/rule';
			$data_Img = explode(',',$dataComing["rulefile_base64"]);
			$info_img = explode('/',$data_Img[0]);
			$ext_img = str_replace('base64','',$info_img[1]);
			if(!file_exists($destination)){
				mkdir($destination, 0777, true);
			}
			$createImage = $lib->base64_to_pdf($dataComing["rulefile_base64"],$dataComing["rule_name"],$destination);
			if($createImage){
				$pathImgShowClient = $config["URL_SERVICE"]."resource/pdf/rule/".$createImage["normal_path"];
				$updatePath = $conmssql->prepare("UPDATE gcrulecooperative SET rule_url = :rule_url WHERE id_rule = :id_rule");
				$updatePath->execute([
					':rule_url' => $pathImgShowClient,
					':id_rule' => $dataComing["id_rule"]
				]);
			}
		}
		if(isset($dataComing["rule_name"]) && $dataComing["rule_name"] != ""){
			$updatePath = $conmssql->prepare("UPDATE gcrulecooperative SET rule_name = :rule_name WHERE id_rule = :id_rule");
			$updatePath->execute([
				':rule_name' => $dataComing["rule_name"],
				':id_rule' => $dataComing["id_rule"]
			]);
		}
		$arrayResult['RESULT'] = TRUE;
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
