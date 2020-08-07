<?php
require_once('../autoloadConnection.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/function_util.php');

use Utility\Library;
use Component\functions;

$lib = new library();
$func = new functions();
$bulk = array();
$i = 1;
$fetchDataSTM = $conmysql->prepare("SELECT * FROM gchistory ORDER BY id_history");
$fetchDataSTM->execute();
while($rowSTM = $fetchDataSTM->fetch(PDO::FETCH_ASSOC)){
	$bulk[] = "(".$i.",'".$rowSTM["his_type"]."','".$rowSTM["his_title"]."','".$rowSTM["his_detail"]."','".$rowSTM["his_path_image"]."','".$rowSTM["his_read_status"]."','".$rowSTM["his_del_status"]."','".$rowSTM["member_no"]."','".$rowSTM["receive_date"]."',".(isset($rowSTM["read_date"]) && $rowSTM["read_date"] != "" ? "'".$rowSTM["read_date"]."'" : 'null').")";
	if(sizeof($bulk) == 1000){
		$insert = $conmysql->prepare("INSERT INTO gchistory(id_history, his_type, his_title, his_detail, his_path_image, his_read_status, his_del_status, member_no, receive_date, read_date) VALUES".implode(',',$bulk));
		$insert->execute();
		unset($bulk);
		$bulk = array();
	}
	$i++;
	$delete = $conmysql->prepare("DELETE FROM gchistory WHERE id_history = ".$rowSTM["id_history"]);
	$delete->execute();
}
if(sizeof($bulk) > 0){
	$insert = $conmysql->prepare("INSERT INTO gchistory(id_history, his_type, his_title, his_detail, his_path_image, his_read_status, his_del_status, member_no, receive_date, read_date) VALUES".implode(',',$bulk));
	if($insert->execute()){
		
	}else{
		echo json_encode($insert);
		echo json_encode($bulk);
	}
}

?>