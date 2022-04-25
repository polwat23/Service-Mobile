<?php
require_once('../autoload.php');

		$arrayGroup = array();
		$fetchWebboard = $conmysql->prepare("SELECT
												id_webboard,
												title,
												detail,
												img_url,
												img_path,
												create_date,
												update_date,
												create_by,
												short_detail
											FROM
												create_by
											WHERE is_use='1'
											ORDER BY
												update_date
											DESC
											LIMIT 1
											");
		$fetchWebboard->execute();
		while($rowWebboard = $fetchWebboard->fetch(PDO::FETCH_ASSOC)){
			$img_head_group=[];
			$arrWebboard["ID_WEBBOARD"] = $rowWebboard["id_webboard"];
			$arrWebboard["TITLE"] = $rowWebboard["title"];
			
			$arrWebboard["DETAIL"] = $rowWebboard["detail"];
			
			$arrWebboard["DETAIL_SHORT"] = $lib->text_limit($rowWebboard["short_detail"],380);
			
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
			$arrayGroup[] = $arrWebboard;
		}
		$arrayResult["WEBBOARD_DATA"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);

?>