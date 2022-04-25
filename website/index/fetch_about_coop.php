<?php
require_once('../autoload.php');

		$arrayGroup = array();
		$arrayGroupFile = array();
		$img_head_web_news = array();
		
			
		$fetchImgBanner = $conmysql->prepare("SELECT	
												address,
												location,
												tel,
												fax,
												facebook_name,
												facebook_url,
												line_name,
												line_name2,
												line_url,
												line_url2,
												email,
												playstore,
												appstore,
												huawei,
												vision,
												mission,
												policy,
												objective,
												history,
												web_url,
												youtube_name,
												youtube_url
											FROM
												webcoopprofile
											");
		$fetchImgBanner->execute();
		$arrayGroupFile=[];		
		while($rowCoopProfile = $fetchImgBanner->fetch(PDO::FETCH_ASSOC)){
				$arrNewsFile["ADDRESS"] = $rowCoopProfile["address"];
				$arrNewsFile["LOCATION"] = $rowCoopProfile["location"];
				$arrNewsFile["TEL"] = $rowCoopProfile["tel"];
				$arrNewsFile["FAX"]=$rowCoopProfile["fax"];
				$arrNewsFile["FACEBOOK_NAME"]=$rowCoopProfile["facebook_name"];
				$arrNewsFile["FACEBOOK_URL"]=$rowCoopProfile["facebook_url"];
				$arrNewsFile["LINE_NAME"]=$rowCoopProfile["line_name"];
				$arrNewsFile["LINE_NAME2"]=$rowCoopProfile["line_name2"];
				$arrNewsFile["LINE_URL"]=$rowCoopProfile["line_url"];
				$arrNewsFile["LINE_URL2"]=$rowCoopProfile["line_url2"];
				$arrNewsFile["EMAIL"]=$rowCoopProfile["email"];
				$arrNewsFile["VISION"]=$rowCoopProfile["vision"];
				$arrNewsFile["PLAYSTORE"]=$rowCoopProfile["playstore"];
				$arrNewsFile["APPSTORE"]=$rowCoopProfile["appstore"];
				$arrNewsFile["HUAWEI"]=$rowCoopProfile["huawei"];
				$arrNewsFile["WEB_URL"]=$rowCoopProfile["web_url"];
				$arrNewsFile["YOUTUBE_NAME"]=$rowCoopProfile["youtube_name"];
				$arrNewsFile["YOUTUBE_URL"]=$rowCoopProfile["youtube_url"];
				
				
				$arrNewsFile["HISTORY"]=$rowCoopProfile["history"];
				
				$mission = explode(',',$rowCoopProfile["mission"]);
				$arrNewsFile["MISSION"] = $mission;
				$policy =[];
				if(isset($rowCoopProfile["policy"]) && $rowCoopProfile["policy"] != null){
					$policy = explode(',',$rowCoopProfile["policy"]);
				}
				
				
				$arrNewsFile["POLICY"] = $policy;
				$groupTel = explode(',',$rowCoopProfile["tel"]);
				$arrNewsFile["GROUP_TEL"]=$groupTel;
				$objective = explode(',',$rowCoopProfile["objective"]);
				$arrNewsFile["OBJECTIVE"]= $objective;
			
				$arrayGroupFile[]=$arrNewsFile;
		}
		$arrayResult["POFILE_DATA"] = $arrayGroupFile;
		
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);
?>