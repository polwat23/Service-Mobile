<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){

		$arrayGroup = array();
		$fetchWebboard = $conmysql->prepare("SELECT
												id_webboard,
												title,
												detail,
												img_url,
												img_path,
												create_date,
												update_date,
												create_by
											FROM
												webcoopwebboard
											WHERE 
												id_webboard = :webbord_id
											");
		$fetchWebboard->execute([':webbord_id' => $dataComing["webbord_id"]
		
		]);
		while($rowWebboard = $fetchWebboard->fetch(PDO::FETCH_ASSOC)){
			$img_head_group=[];
			$arrWebboard["ID_WEBBOARD"] = $rowWebboard["id_webboard"];
			$arrWebboard["TITLE"] = $rowWebboard["title"];
			$arrWebboard["DETAIL"] = $rowWebboard["detail"];
			//$rowWebboard["NEWS_DETAIL_SHORT"] = $lib->text_limit($rowWebboard["news_detail"],480);
			
			if(isset($rowWebboard["img_url"]) && $rowWebboard["img_url"] != null){
				$img_head["IMG_URL"]=$rowWebboard["img_url"];
				$img_head["IMG_PATH"]=$rowWebboard["img_path"];
				$img_head["status"]="old";	
				$img_head_group[]=$img_head;
			}else{
				$img_head_group=[];
			}
			$arrWebboard["IMG_HEAD"] = $img_head_group;
			$arrWebboard["CREATE_BY"] = $rowWebboard["create_by"];
			$arrWebboard["CREATE_DATE"] = $lib->convertdate($rowWebboard["create_date"],'d m Y',true); 
			$arrWebboard["UPDATE_DATE"] = $lib->convertdate($rowWebboard["update_date"],'d m Y',true);  
			$arrayGroup = $arrWebboard;
		}
		$arrayResult["WEBBOARD_DATA"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);

}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>