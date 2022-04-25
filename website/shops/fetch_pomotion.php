<?php
require_once('../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	$arrayGroup = array();
	$arrayGroupFile = array();
	$img = array();
	$fetchPromotion = $conmysql->prepare("SELECT 
												promotion_id,
												title,
												price,
												new_price,
												img_url,
												img_path,
												detail,
												start,
												end,
												create_date,
												update_date,
												create_by,
												discount
											FROM webcooppromotion
											WHERE is_use ='1'
											
											ORDER BY create_date DESC");
	$fetchPromotion->execute();
	while($rowPromotion = $fetchPromotion->fetch(PDO::FETCH_ASSOC)){
		$imgGroup = [];
		$arrPromotion = array();
		$arrGroupList = [];
		$fetchHeadImgNews = $conmysql->prepare("SELECT
													file_url,
													file_path,
													file_name
												FROM
													webcoopfilepromotion
												WHERE promotion_id = :promotion_id
												");
		$fetchHeadImgNews->execute([
			':promotion_id' => $rowPromotion["promotion_id"]
		]);
			$imgfile =[];
		$imgfile["original"] = $rowPromotion["img_url"];
		$imgfile["thumbnail"] = $rowPromotion["img_url"];
		$i =1;
		while($arrNewsHeadImg = $fetchHeadImgNews->fetch(PDO::FETCH_ASSOC)){
			$img = [];
			if(isset($arrNewsHeadImg["file_url"]) && $arrNewsHeadImg["file_path"] != null){
				$img["uid"]=$i;
				$img["url"]=$arrNewsHeadImg["file_url"];
				$img["thumbUrl"]=$arrNewsHeadImg["file_url"];
				$img["path"]=$arrNewsHeadImg["file_path"];
				$img["name"]=$arrNewsHeadImg["file_name"];
				$img["original"]=$arrNewsHeadImg["file_url"];
				$img["thumbnail"]=$arrNewsHeadImg["file_url"];
				$img["status"]="old";	
				$imgGroup[]=$img;
				$arrGroupList [] =$img;
			}else{
				$imgGroup=[];
				$arrGroupList [] =$img;
			}
			$i++;
		}
		
	
		$arrGroupList[]=$imgfile;
		$arrPromotion["PROMOTION_ID"] = $rowPromotion["promotion_id"];
		$arrPromotion["TITLE"] = $rowPromotion["title"];
		$arrPromotion["PRICE"] = $rowPromotion["price"];
		$arrPromotion["NEW_PRICE"] = $rowPromotion["new_price"];
		$arrPromotion["IMG_URL"] = $rowPromotion["img_url"];
		$arrPromotion["IMG_PATH"] = $rowPromotion["img_path"];
		$arrPromotion["FILE"] = $imgGroup;
		$arrPromotion["FILE_SHOW"] = $arrGroupList;
		$arrPromotion["DISCOUNT"] = $rowPromotion["discount"];
		$arrPromotion["DETAIL"] = $rowPromotion["detail"];
		$arrPromotion["START"] = $rowPromotion["start"];
		$arrPromotion["START_FORMATE"] = $lib->convertdate($rowPromotion["start"],'d m Y',false); 
		$arrPromotion["END"] = $rowPromotion["end"]; 
		$arrPromotion["END_FORMAT"] = $lib->convertdate($rowPromotion["end"],'d m Y',false); 
		$arrPromotion["CREATE_BY"] = $rowPromotion["create_by"];
		$arrPromotion["CREATE_DATE"] = $lib->convertdate($rowPromotion["create_date"],'d m Y',true); 
		$arrPromotion["UPDATE_DATE"] = $lib->convertdate($rowPromotion["update_date"],'d m Y',true);  
		$arrayGroup[] = $arrPromotion;
	}
	$arrayResult["PROMOTION_DATA"] = $arrayGroup;
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);
	
}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>