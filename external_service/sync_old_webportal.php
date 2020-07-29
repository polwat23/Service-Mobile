<?php
require_once('../autoloadConnection.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/function_util.php');

use Utility\Library;
use Component\functions;

$lib = new library();
$func = new functions();
$arrGrp = array();

		$dbhost = "mobilecloud.coopsiam.com";
		$dbuser = "root";
		$dbpass = "@Gensoft2018";
		$dbname = "mobile_mhd_test";
		try{
			$conmysql2 = new PDO("mysql:dbname={$dbname};host={$dbhost}", $dbuser, $dbpass);
			$conmysql2->exec("set names utf8mb4");
		}catch(Throwable $e){
			echo json_encode($e);
		}

	$bulkIns = array();
	$arrayMember = array();
	$getData_New = $conmysql->prepare("SELECT * FROM gcmemberaccount ");
	$getData_New->execute();
	$member = array();
	$mmn = array();
	while($row = $getData_New->fetch(PDO::FETCH_ASSOC)){
		$member[] = $row["member_no"];
	}
		$getMe = $conmysql2->prepare("SELECT * FROM gcmemberaccount");
		$getMe->execute();
		while($rowMe = $getMe->fetch()){
			if(in_array($rowMe["member_no"],$member)){
				
			}else{
				$mmn[] = $rowMe["member_no"];
			}
		}
		$insertTOMHD = array();
		foreach($mmn as $rowMe2){
			$getM = $conmysql2->prepare("SELECT * FROM gcmemberaccount WHERE member_no = :member_no");
			$getM->execute([':member_no' => $rowMe2]);
			if($getM->rowCount() > 0){
				$rowMe = $getM->fetch();
				$insertTOMHD[] = "('".$rowMe["member_no"]."','".$rowMe["password"]."','".$rowMe["pin"]."','".$rowMe["phone_number"]."','".$rowMe["email"]."','".$rowMe["account_status"]."','".$rowMe["temppass"]."','".$rowMe["path_avatar"]."','".$rowMe["fcm_token"]."')";
			}
		}
	if(sizeof($insertTOMHD) > 0){
		$insert = $conmysql->prepare("INSERT INTO gcmemberaccount(member_no,password,pin,phone_number,email,account_status,temppass,path_avatar,fcm_token)
												VALUES".implode(',',$insertTOMHD));
		//$insert->execute();
	}
	echo json_encode($insertTOMHD);

?>