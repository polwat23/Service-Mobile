<?php
require_once('../../../autoload.php');
if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	$arrayGroup = array();
	if(isset($dataComing["search"]) && $dataComing["search"] != null){
		$test = "not null";
		$fetchWebboard = $conmysql->prepare("SELECT
												question_id,
												title,
												detail,
												is_use,
												is_edit,
												view,
												reply,
												create_date,
												update_date,
												img,
												create_by,
												member_no,
												avatar,
												is_use
											FROM
												question
											WHERE 
												is_use = '1' AND title LIKE'%".$dataComing[search]."%' OR create_by LIKE'%".$dataComing[search]."%'
											ORDER BY
												create_date
											DESC");
	}else{
		$fetchWebboard = $conmysql->prepare("SELECT
												question_id,
												title,
												detail,
												is_use,
												is_edit,
												view,
												reply,
												create_date,
												update_date,
												img,
												create_by,
												member_no,
												avatar,
												is_use
											FROM
												question
											WHERE 
												is_use = '1'
											ORDER BY
												create_date
											DESC");
	}

	

	$fetchWebboard->execute();
	$total_topic = 0;
	while($rowWebboard = $fetchWebboard->fetch(PDO::FETCH_ASSOC)){
		$img_head_group="";
		$arrWebboard["QUESTION_ID"] = $rowWebboard["question_id"];
		$arrWebboard["TITLE"] = $rowWebboard["title"];
		$arrWebboard["DETAIL"] = $rowWebboard["detail"];
		$arrWebboard["IS_EDIT"] = $rowWebboard["is_edit"];
		$arrWebboard["IS_USE"] = $rowWebboard["is_use"];
		$arrWebboard["VIEW"] = $rowWebboard["view"];
		$arrWebboard["MEMBER_NO"] = $rowWebboard["member_no"];
		$arrWebboard["REPLY"] = $rowWebboard["reply"];
		$arrWebboard["CREATE_BY"] = $rowWebboard["create_by"];
		$arrWebboard["AVATAR"] = $rowWebboard["avatar"];
		$arrWebboard["IMG_HEAD"] = $rowWebboard["img"];
		$arrWebboard["CREATE_DATE"] = $rowWebboard["create_date"];
		$arrWebboard["CREATE_DATE_FORMAT"] = $lib->convertdate($rowWebboard["create_date"],'d m Y',true); 
		$arrWebboard["UPDATE_DATE"] = $rowWebboard["update_date"];  
		$arrWebboard["UPDATE_DATE_FORMAT"] = $lib->convertdate($rowWebboard["update_date"],'d m Y',true);  
		$fetchTotalComment = $conmysql->prepare("
								SELECT
									reply_id
								FROM
									reply
								WHERE
									question_id = :question_id 
		");
		$fetchTotalComment->execute([
			':question_id' => $rowWebboard["question_id"]
		]);
		$arrWebboard["TOTAL_COMMENT"] = $fetchTotalComment ->rowCount();
		$arrayGroup[] = $arrWebboard;
		$total_topic++;
	}
	
	$fetchTopTopic = $conmysql->prepare("SELECT
												question_id,
												title,
												detail,
												is_use,
												is_edit,
												view,
												reply,
												create_date,
												update_date,
												img,
												create_by,
												member_no
											FROM
												question
											WHERE 
												is_use = '1'
											ORDER BY
												view
											DESC
											LIMIT 10");
	$fetchTopTopic->execute();
	$no =1;
	while($rowTopTopic = $fetchTopTopic->fetch(PDO::FETCH_ASSOC)){
		$img_head_group="";
		$arrWebboard["NO"] = $no;
		$arrWebboard["QUESTION_ID"] = $rowTopTopic["question_id"];
		$arrWebboard["TITLE"] = $rowTopTopic["title"];
		$arrWebboard["DETAIL"] = $rowTopTopic["detail"];
		$arrWebboard["IS_EDIT"] = $rowTopTopic["is_edit"];
		$arrWebboard["IS_USE"] = $rowTopTopic["is_use"];
		$arrWebboard["VIEW"] = $rowTopTopic["view"];
		$arrWebboard["MEMBER_NO"] = $rowTopTopic["member_no"];
		$arrWebboard["REPLY"] = $rowTopTopic["reply"];
		$arrWebboard["CREATE_BY"] = $rowTopTopic["create_by"];
		$arrWebboard["IMG_HEAD"] = $rowTopTopic["img"];
		$arrWebboard["CREATE_DATE"] = $rowTopTopic["create_date"];
		$arrWebboard["CREATE_DATE_FORMAT"] = $lib->convertdate($rowTopTopic["create_date"],'d m Y',true); 
		$arrWebboard["UPDATE_DATE"] = $rowTopTopic["update_date"];  
		$arrWebboard["UPDATE_DATE_FORMAT"] = $lib->convertdate($rowTopTopic["update_date"],'d m Y',true);  
		$fetchTotalComment = $conmysql->prepare("
								SELECT
									reply_id
								FROM
									reply
								WHERE
									is_use = '1' AND question_id = :question_id 
		");
		$fetchTotalComment->execute([
			':question_id' => $rowTopTopic["question_id"]
		]);
		$arrWebboard["TOTAL_COMMENT"] = $fetchTotalComment ->rowCount();
		$arrayGroupTopTopic[] = $arrWebboard;
		$no++;
	}
	
	
	
	$fetchHiddenTopic = $conmysql->prepare("SELECT
												question_id,
												title,
												detail,
												is_use,
												is_edit,
												view,
												reply,
												create_date,
												update_date,
												img,
												create_by,
												member_no
											FROM
												question
											WHERE 
												is_use != '1'
											ORDER BY
												update_date
											DESC");
	$fetchHiddenTopic->execute();
	$total_hiddenTopic = 0;
	while($rowHiddenTopic = $fetchHiddenTopic->fetch(PDO::FETCH_ASSOC)){
		$arrWebboard["QUESTION_ID"] = $rowHiddenTopic["question_id"];
		$arrWebboard["TITLE"] = $rowHiddenTopic["title"];
		$arrWebboard["DETAIL"] = $rowHiddenTopic["detail"];
		$arrWebboard["IS_EDIT"] = $rowHiddenTopic["is_edit"];
		$arrWebboard["IS_USE"] = $rowHiddenTopic["is_use"];
		$arrWebboard["VIEW"] = $rowHiddenTopic["view"];
		$arrWebboard["MEMBER_NO"] = $rowHiddenTopic["member_no"];
		$arrWebboard["REPLY"] = $rowHiddenTopic["reply"];
		$arrWebboard["CREATE_BY"] = $rowHiddenTopic["create_by"];
		$arrWebboard["IMG_HEAD"] = $rowHiddenTopic["img"];
		$arrWebboard["CREATE_DATE"] = $rowHiddenTopic["create_date"];
		$arrWebboard["CREATE_DATE_FORMAT"] = $lib->convertdate($rowHiddenTopic["create_date"],'d m Y',true); 
		$arrWebboard["UPDATE_DATE"] = $rowHiddenTopic["update_date"];  
		$arrWebboard["UPDATE_DATE_FORMAT"] = $lib->convertdate($rowHiddenTopic["update_date"],'d m Y',true);  
		$fetchTotalComment = $conmysql->prepare("
								SELECT
									reply_id
								FROM
									reply
								WHERE
									is_use = '1' AND question_id = :question_id 
		");
		$fetchTotalComment->execute([
			':question_id' => $rowHiddenTopic["question_id"]
		]);
		$arrWebboard["TOTAL_COMMENT"] = $fetchTotalComment ->rowCount();
		$arrayGroupHiddenTopic[] = $arrWebboard;
		$total_hiddenTopic++;
	}
	
	$arrayResult["WEBBOARD_DATA"] = $arrayGroup;
	$arrayResult["TOP_TOPIC"] = $arrayGroupTopTopic;
	$arrayResult["HIDDEN_TOPIC"] = $arrayGroupHiddenTopic;
	$arrayResult["TOTAL_HIDDEN_TOPIC"] = $total_hiddenTopic;
	$arrayResult["TOTAL_TOPIC_ALL"] = $total_topic;
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);


}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>