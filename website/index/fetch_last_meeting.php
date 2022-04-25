<?php
require_once('../autoload.php');
		$arrayGroup = array();
		
		$fetchMenu = $conmysql->prepare("SELECT page_name FROM webcoopmenu WHERE  page_name = 'planmeeting' AND menu_status = '1'");
		$fetchMenu->execute();
		
		if($fetchMenu->rowCount() > 0){
			$fetchMeetingAgenda = $conmysql->prepare("SELECT 
													id_meettingagenda,	
													title,	
													detail,
													file_url,
													file_patch,
													date,
													update_date
												FROM webcoopmeetingagenda
												ORDER BY date DESC
												LIMIT 3
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
			$arrMeeting["ID_MEETING"] = $rowFileForm["id_meettingagenda"];
			$arrMeeting["TITLE"] = $rowFileForm["title"];
			$arrMeeting["DETAIL"] = $rowFileForm["detail"];
			$arrMeeting["update_date"] = $rowFileForm["update_date"];
			$arrMeeting["IMG_URL"] = $rowFileForm["img_url"];
			$arrMeeting["DATE_FORMAT"] = $lib->convertdate($rowFileForm["date"],'d m Y',true); 
			$arrMeeting["FILE"] = $file;
			$arrayGroup[] = $arrMeeting;
		}
		$arrayResult["MEETING_DATA"] = $arrayGroup;
		$arrayResult["RESULT"] = TRUE;
		echo json_encode($arrayResult);

		}else{
			$arrayResult["MEETING_DATA"] = $arrayGroup;
			$arrayResult["RESULT"] = TRUE;
			echo json_encode($arrayResult);
		}
		
		

?>