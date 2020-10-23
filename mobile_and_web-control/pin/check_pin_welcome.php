<?php
require_once('../autoload.php');

$checkPin = $conmysql->prepare("SELECT pin FROM gcmemberaccount WHERE trim(member_no) = :member_no");
$checkPin->execute([
	':member_no' => $payload["member_no"]
]);
$rowPin = $checkPin->fetch(PDO::FETCH_ASSOC);
// Pin Status : 9 => DEV, 1 => TRUE, 0 => FALSE
if(isset($rowPin["pin"])){
	if($payload["user_type"] == '9'){
		$arrayResult['RESULT'] = 9;
	}else{
		$arrayResult['RESULT'] = 1;
	}
	echo json_encode($arrayResult);
}else{
	$arrayResult['RESULT'] = 0;
	echo json_encode($arrayResult);
}
?>