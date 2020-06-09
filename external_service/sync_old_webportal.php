<?php
require_once('../autoloadConnection.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/function_util.php');

use Utility\Library;
use Component\functions;

$lib = new library();
$func = new functions();
$arrGrp = array();

if(is_array($conmysqlold) && $conmysqlold["RESULT"] == FALSE){
	echo $conmysqlold["ERROR"];
}else{
	$bulkIns = array();
	$arrayMember = array();
	$getData_New = $conmysql->prepare("SELECT member_no FROM gcmemberaccount");
	$getData_New->execute();
	while($row = $getData_New->fetch(PDO::FETCH_ASSOC)){
		$arrayMember[] = $row["member_no"];
	}
	$getData_Old = $conmysqlold->prepare("SELECT * FROM mbmembmaster GROUP BY member_no");
	$getData_Old->execute();
	while($rowData = $getData_Old->fetch(PDO::FETCH_ASSOC)){
		if(!in_array($rowData["member_no"], $arrayMember)){
			$tel = preg_replace('/-/', '', $rowData["mobile"]);
			$pass = password_hash($rowData["password"],PASSWORD_DEFAULT);
			$bulkIns[] = "('".$rowData["member_no"]."','".$pass."','".substr($tel,0,10)."','".$rowData["email"]."','-9','".$pass."','".$rowData["date_reg"]."','web','1')";
			/*if(sizeof($bulkIns) == 1000){
				$ins = $conmysql->prepare("INSERT INTO gcmemberaccount(member_no,password,phone_number,email,account_status,temppass,register_date,register_channel,temppass_is_md5) VALUES".implode(',',$bulkIns));
				/*if($ins->execute()){
					
					echo 'Inserted';
				}else{
					echo json_encode($ins);
				}
				unset($bulkIns);
				$bulkIns = array();
			}*/
		}
	}
	/*if(sizeof($bulkIns) > 0){
		$ins = $conmysql->prepare("INSERT INTO gcmemberaccount(member_no,password,phone_number,email,account_status,temppass,register_date,register_channel,temppass_is_md5) VALUES".implode(',',$bulkIns));
		if($ins->execute()){
					
					echo 'Inserted';
				}else{
					echo json_encode($ins);
				}
		unset($bulkIns);
		$bulkIns = array();
		
	}*/
	echo "INSERT INTO gcmemberaccount(member_no,password,phone_number,email,account_status,temppass,register_date,register_channel,temppass_is_md5) VALUES".implode(',',$bulkIns);
	
}

?>