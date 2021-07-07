<?php
$anonymous = '';
$skip_autoload = true;
require_once('../autoload.php');

if(!$anonymous){
	$flag_granted = 'anonymous';
}else{
	$flag_granted = 'member';
}
$arrGroupAnn = array();

if(isset($dataComing["firsttime"]) && $dataComing["firsttime"] == '1'){
	$firstapp = '1';
}else{
	$firstapp = '-1';
}
$fetchAnn = $conoracle->prepare("SELECT priority,announce_cover,announce_title,announce_detail,announce_html,effect_date,id_announce,flag_granted,due_date,is_check,check_text,accept_text,cancel_text
												FROM gcannounce 
												WHERE effect_date IS NOT NULL and 
												((CASE WHEN priority = 'high' OR priority = 'ask'
												THEN 
													DATE_FORMAT(effect_date,'%Y-%m-%d %H:%i:%s') <= DATE_FORMAT(NOW(),'%Y-%m-%d %H:%i:%s')
												ELSE   
													DATE_FORMAT(NOW(),'%Y-%m-%d %H:%i:%s') BETWEEN DATE_FORMAT(effect_date,'%Y-%m-%d %H:%i:%s') AND DATE_FORMAT(due_date,'%Y-%m-%d %H:%i:%s')
												END ) OR first_time = :first_time) and flag_granted <> :flag_granted");
$fetchAnn->execute([
	':first_time' => $firstapp,
	':flag_granted' => $flag_granted
]);
while($rowAnn = $fetchAnn->fetch(PDO::FETCH_ASSOC)){
	$checkAcceptAnn = $conoracle->prepare("SELECT id_accept_ann FROM logacceptannounce WHERE member_no = :member_no and id_announce = :id_announce");
	$checkAcceptAnn->execute([
		':member_no' => $payload["member_no"],
		':id_announce' => $rowAnn["ID_ANNOUNCE"]
	]);
	if($checkAcceptAnn->rowCount() == 0){
			$arrAnn = array();
			$arrAnn["FLAG_GRANTED"] = $rowAnn["FLAG_GRANTED"];
			$arrAnn["PRIORITY"] = $rowAnn["PRIORITY"];
			$arrAnn["ID_ANNOUNCE"] = $rowAnn["ID_ANNOUNCE"];
			$arrAnn["EFFECT_DATE"] = $rowAnn["EFFECT_DATE"];
			$arrAnn["END_DATE"] = $rowAnn["DUE_DATE"];
			$arrAnn["ANNOUNCE_COVER"] = $rowAnn["ANNOUNCE_COVER"];
			$arrAnn["ANNOUNCE_TITLE"] = $rowAnn["ANNOUNCE_TITLE"];
			$arrAnn["ANNOUNCE_DETAIL"] = $rowAnn["ANNOUNCE_DETAIL"];
			$arrAnn["IS_CHECK"] = $rowAnn["IS_CHECK"];
			$arrAnn["CHECK_TEXT"] = $rowAnn["CHECK_TEXT"];
			$arrAnn["ACCEPT_TEXT"] = $rowAnn["ACCEPT_TEXT"];
			$arrAnn["CANCEL_TEXT"] = $rowAnn["CANCEL_TEXT"];
			$arrAnn["ANNOUNCE_HTML"] = $rowAnn["ANNOUNCE_HTML"];
			$arrGroupAnn[] = $arrAnn;
	}
}
$arrayResult['ANNOUNCE'] = $arrGroupAnn;
$arrayResult['RESULT'] = TRUE;
require_once('../../include/exit_footer.php');
?>