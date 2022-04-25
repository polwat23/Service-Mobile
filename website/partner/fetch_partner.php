<?php
require_once('../autoload.php');
		$arrayGroup = array();
		$arrayGroupFile = array();
		$img_head_web_news = array();
		$fetchImgPartner = $conmysql->prepare("SELECT
													p.webcooppartner_id,
													p.name,
													p.link,
													p.id_gallery,
													p.text_color,
													p.background_color,
													g.img_gallery_url,
													g.img_gallery_path
												FROM
													webcooppartner p
												LEFT JOIN webcoopgallary g ON
													p.id_gallery = g.id_gallery
												");
		$fetchImgPartner->execute();
		$arrayGroupPartner=[];		
		while($rowData = $fetchImgPartner->fetch(PDO::FETCH_ASSOC)){
				$arrPartner["PARTNERT_ID"] = $rowData["webcooppartner_id"];
				$arrPartner["NAME"]=$rowData["name"];
				$arrPartner["LINK"]=$rowData["link"];
				$arrPartner["TEXT_COLOR"]=$rowData["text_color"];
				$arrPartner["IMG_GALLERY_URL"]=$rowData["img_gallery_url"];
				$arrPartner["IMG_GALLERY_PATH"]=$rowData["img_gallery_path"];
				$arrPartner["BG_COLOR"]=$rowData["background_color"];
				$arrPartner["ID_GALLERY"]=$rowData["id_gallery"];
				$arrayGroupPartner[]=$arrPartner;
		}
		$arrayResult["PARTNER_DATA"] = $arrayGroupPartner;
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);
?>