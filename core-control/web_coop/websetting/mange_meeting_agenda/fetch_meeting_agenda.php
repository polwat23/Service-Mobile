<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){

	$arrayGroup = array();
	$arrayGroupFile = array();
	$fetchMeetingAgenda = $conmysql->prepare("SELECT 
												id_meettingagenda,	
												title,	
												detail,
												file_url,
												file_patch,
												date,
												create_by
											FROM webcoopmeetingagenda
											ORDER BY date DESC");
	$fetchMeetingAgenda->execute();
	while($rowFileForm = $fetchMeetingAgenda->fetch(PDO::FETCH_ASSOC)){
		$name=explode('/',$rowFileForm["file_patch"]);
		$arrFile = [];
		$file = array();
		if(isset($rowFileForm["file_url"]) && $rowFileForm["file_url"] != null){
			$arrFile["FILE_URL"] = $rowFileForm["file_url"];
			$arrFile["FILE_PATH"] = $rowFileForm["file_patch"];
			$arrFile["url"] = $rowFileForm["file_url"];
			$arrFile["name"] = $name[3];
			
			$file[]=$arrFile;
		}
		$name = explode('/',$rowFileForm["file_patch"]);
		$arrNewsWebCoop["ID_MEETING"] = $rowFileForm["id_meettingagenda"];
		$arrNewsWebCoop["TITLE"] = $rowFileForm["title"];
		$arrNewsWebCoop["DETAIL"] = $rowFileForm["detail"];
		$arrNewsWebCoop["CREATE_BY"] = $rowFileForm["create_by"];
		$arrNewsWebCoop["DATE"] = $rowFileForm["date"];
		$arrNewsWebCoop["DATE_FORMAT"] = $lib->convertdate($rowFileForm["date"],'d m Y',true); 
		$arrNewsWebCoop["FILE"] = $file;
		$arrayGroup[] = $arrNewsWebCoop;
	}
	$arrayResult["MEETING_DATA"] = $arrayGroup;
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);

}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>