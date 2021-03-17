<?php
require_once('../../../autoload.php');

if($lib->checkCompleteArgument(['unique_id'],$dataComing)){
	$arrayGroup = array();
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
											avatar
										FROM
											question
										WHERE
											question_id = :question_id
										");
	$fetchWebboard->execute(['question_id' => $dataComing["question_id"]]);
	while($rowWebboard = $fetchWebboard->fetch(PDO::FETCH_ASSOC)){
		$groupComment = [];
		$fetchComment = $conmysql->prepare("
											SELECT
												reply_id,
												id_momment_parent,
												detail,
												name,
												creat_date,
												update_date,
												is_edit,
												avatar
											FROM
												reply
											WHERE
												is_use = '1' AND question_id = :question_id AND id_momment_parent ='0'
											ORDER BY 
												creat_date DESC
		");
		$fetchComment->execute(['question_id' => $rowWebboard["question_id"]]);
		while($rowComment = $fetchComment->fetch(PDO::FETCH_ASSOC)){
			
			$groupReply = [];
			$fetchReply = $conmysql->prepare("
												SELECT
													reply_id,
													id_momment_parent,
													detail,
													name,
													creat_date,
													update_date,
													is_edit,
													avatar
												FROM
													reply
												WHERE
													is_use = '1' AND  id_momment_parent = :reply_id
												ORDER BY creat_date DESC
			");
			$fetchReply->execute([
				'reply_id' => $rowComment["reply_id"]
			]);
			$arrReply =[];
			while($rowReply = $fetchReply->fetch(PDO::FETCH_ASSOC)){
				$arrReply["PARENT_COMMENT_ID"] = $rowReply["id_momment_parent"];
				$arrReply["REPLY_ID"] = $rowReply["reply_id"];
				$arrReply["DETAIL"] = $rowReply["detail"];
				$arrReply["NAME"] = $rowReply["name"];
				$arrReply["CREATE_DATE"] = $rowReply["creat_date"];
				$arrReply["CREATE_DATE_FORMAT"] = $lib->convertdate($rowReply["creat_date"],'d m Y',true);  
				$arrReply["UPDATE_DATE"] = $rowReply["update_date"];
				$arrReply["UPDATE_DATE_FORMAT"] = $lib->convertdate($rowReply["update_date"],'d m Y',true);  
				$arrReply["IS_EDIT"] = $rowReply["is_edit"];
				$arrReply["AVATAR"] = $rowReply["avatar"];
				$groupReply[]=$arrReply;
			}
	
			
			$arrComment["PARENT_COMMENT_ID"] = $rowComment["id_momment_parent"];
			$arrComment["REPLY_ID"] = $rowComment["reply_id"];
			$arrComment["DETAIL"] = $rowComment["detail"];
			$arrComment["NAME"] = $rowComment["name"];
			$arrComment["AVATAR"] = $rowComment["avatar"];
			$arrComment["CREATE_DATE"] = $rowComment["creat_date"];
			$arrComment["CREATE_DATE_FORMAT"] = $lib->convertdate($rowComment["creat_date"],'d m Y',true);
			$arrComment["UPDATE_DATE"] = $rowComment["update_date"];
			$arrComment["UPDATE_DATE_FORMAT"] = $lib->convertdate($rowComment["update_date"],'d m Y',true);
			$arrComment["REPLY"] = $groupReply;
			$groupComment[] = $arrComment;
		}
		
		
		$fetchTotalComment = $conmysql->prepare("
											SELECT
												reply_id
											FROM
												reply
											WHERE
												is_use = '1' AND question_id = :question_id 
		");
		$fetchTotalComment->execute([
			':question_id' => $rowWebboard["question_id"]
		]);
		$totalComment = $fetchTotalComment ->rowCount();
		
		//rowCount()
		$arrWebboard["QUESTION_ID"] = $rowWebboard["question_id"];
		$arrWebboard["COMMENT"] = $groupComment;
		$arrWebboard["TOTAL_COMMENT"] = $totalComment;
		$arrWebboard["TITLE"] = $rowWebboard["title"];
		$arrWebboard["DETAIL"] = $rowWebboard["detail"];
		$arrWebboard["IS_EDIT"] = $rowWebboard["is_edit"];
		$arrWebboard["VIEW"] = $rowWebboard["view"];
		$arrWebboard["REPLY"] = $rowWebboard["reply"];
		$arrWebboard["CREATE_BY"] = $rowWebboard["create_by"];
		$arrWebboard["AVATAR"] = $rowWebboard["avatar"];
		$arrWebboard["IMG_HEAD"] = $rowWebboard["img"];
		$arrWebboard["CREATE_DATE"] = $rowWebboard["create_date"];
		$arrWebboard["CREATE_DATE_FORMAT"] = $lib->convertdate($rowWebboard["create_date"],'d m Y',true); 
		$arrWebboard["UPDATE_DATE"] = $rowWebboard["update_date"];  
		$arrWebboard["UPDATE_DATE_FORMAT"] = $lib->convertdate($rowWebboard["update_date"],'d m Y',true);  
		$arrayGroup = $arrWebboard;
	}
	$arrayResult["WEBBOARD_DETAIL_DATA"] = $arrayGroup;
	$arrayResult["RESULT"] = TRUE;
	echo json_encode($arrayResult);


}else{
	$arrayResult['RESULT'] = FALSE;
	http_response_code(400);
	echo json_encode($arrayResult);
	exit();
}
?>