<?php
require_once('../autoload.php');

	$fetchRegis = $conoracle->prepare("select * from VIEW_REGISTER_MEMBER where ID_CARD = :id_card");
	$fetchRegis->execute([
		':id_card' => $dataComing["id_card"]
	]);
	
	$rowRegis = $fetchRegis->fetch(PDO::FETCH_ASSOC);
	
	
	$fetchRegisCoop = $conoracle->prepare("select can_accept_flg from mem_t_candidate where can_id_card = :id_card");
	$fetchRegisCoop->execute([
		':id_card' => $dataComing["id_card"]
	]);
	
	$rowRegisCoop = $fetchRegisCoop->fetch(PDO::FETCH_ASSOC);
	
	if(isset($rowRegis["ID_CARD"]) && $rowRegis["ID_CARD"] != ""){
		$arrayResult['BRANCH'] = $rowRegis["NAME_TYPE"];
		$arrayResult['ERR_MESSAGE'] = 'ไม่สามารถสมัครได้ เนื่องจากท่านเป็น'.$rowRegis["NAME_TYPE"].'แล้ว';
		$arrayResult['RESULT'] = FALSE;
	}else if(isset($rowRegisCoop["CAN_ACCEPT_FLG"]) && $rowRegisCoop["CAN_ACCEPT_FLG"] == "3"){
		$arrayResult['ERR_MESSAGE'] = 'ท่านเป็นได้ทำการสมัครไปแล้ว อยู่ในระหว่างการ "รอยืนยัน" ข้อมูลจากสหกรณ์';
		$arrayResult['RESULT'] = FALSE;
	}else if(isset($rowRegisCoop["CAN_ACCEPT_FLG"]) && $rowRegisCoop["CAN_ACCEPT_FLG"] == "1"){
		$arrayResult['ERR_MESSAGE'] = 'ท่านเป็นได้ทำการสมัครไปแล้ว และได้รับการ "อนุมัติ" จากสหกรณ์เรียบร้อยแล้ว';
		$arrayResult['RESULT'] = FALSE;
	}else{
		$arrayResult['RESULT'] = TRUE;
	}
	require_once('../../include/exit_footer.php');
?>