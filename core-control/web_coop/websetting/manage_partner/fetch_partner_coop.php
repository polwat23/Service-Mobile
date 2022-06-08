<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	$arrayGroup = array();
	$arrayGroupFile = array();
	$img_head_web_news = array();
	$fetchImgPartner = $conmysql->prepare("SELECT
												webcooppartner_id,
												name,
												link,
												text_color,
												background_color,
												img_url,
												img_patch
											FROM
												webcooppartner
											WHERE is_use ='1'
											ORDER BY
												create_date
											DESC
	");
	$fetchImgPartner->execute();
	$arrayGroupPartner=[];		
	while($rowData = $fetchImgPartner->fetch(PDO::FETCH_ASSOC)){
			$arrPartner["PARTNERT_ID"] = $rowData["webcooppartner_id"];
			$arrPartner["NAME"]=$rowData["name"];
			$arrPartner["LINK"]=$rowData["link"];
			$arrPartner["TEXT_COLOR"]=$rowData["text_color"];
			$arrPartner["IMG_URL"]=$rowData["img_url"];
			$arrPartner["IMG_PATCH"]=$rowData["img_patch"];
			$arrPartner["BG_COLOR"]=$rowData["background_color"];
			$arrayGroupPartner[]=$arrPartner;
	}
	$arrayResult["PARTNER_DATA"] = $arrayGroupPartner;
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);

}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>