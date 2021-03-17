<?php
require_once('../autoload.php');

		$arrayGroup = array();
		$arrayGroupFile = array();
		$fetchMeetingAgenda = $conmysql->prepare("SELECT 
													id_meettingagenda,	
													title,	
													detail,
													file_url,
													file_patch,
													date,
													update_date,
													img_url
												FROM webcoopmeetingagenda
												ORDER BY date DESC
												LIMIT 2
												");
		$fetchMeetingAgenda->execute();
		while($rowFileForm = $fetchMeetingAgenda->fetch(PDO::FETCH_ASSOC)){
			$name=explode('/',$rowFileForm["file_patch"]);
			$arrFile = [];
			$file = array();
			if(isset($rowFileForm["file_url"]) && $rowFileForm["file_url"] != null){
				$arrFile = $rowFileForm["file_url"];
				$file=$arrFile;
			}
			$name = explode('/',$rowFileForm["file_patch"]);
			$arrNewsWebCoop["ID_MEETING"] = $rowFileForm["id_meettingagenda"];
			$arrNewsWebCoop["TITLE"] = $rowFileForm["title"];
			$arrNewsWebCoop["DETAIL"] = $rowFileForm["detail"];
			$arrNewsWebCoop["update_date"] = $rowFileForm["update_date"];
			$arrNewsWebCoop["IMG_URL"] = $rowFileForm["img_url"];
			$arrNewsWebCoop["DATE_FORMAT"] = $lib->convertdate($rowFileForm["date"],'d m Y',true); 
			$arrNewsWebCoop["FILE"] = $file;
			$arrayGroup[] = $arrNewsWebCoop;
		}
		$arrayResult["MEETING_DATA"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);

?>