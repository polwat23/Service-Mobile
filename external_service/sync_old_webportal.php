<?php
require_once(__DIR__.'/../extension/vendor/autoload.php');
require_once(__DIR__.'/../include/lib_util.php');

use Utility\Library;
use WebPConvert\WebPConvert;

$lib = new library();
$webP = new WebPConvert();

$arrGrp = array();

$dbhost = "127.0.0.1";
$dbuser = "root";
$dbpass = "@Egat2020";
$dbname = "mobile_egat_test";
try{
	$conmysql = new PDO("mysql:dbname={$dbname};host={$dbhost}", $dbuser, $dbpass);
	$conmysql->exec("set names utf8mb4");
}catch(Throwable $e){
	echo json_encode($e);
}


$dbuser2 = "SCOBKEGAT";
$dbpass2 = "SCOBKEGAT";
$dbname2 = "(DESCRIPTION =
			(ADDRESS_LIST =
			  (ADDRESS = (PROTOCOL = TCP)(HOST = 10.20.240.100)(PORT = 1521))
			)
			(CONNECT_DATA =
			  (SID=CTDSAVCO)
			)
		  )";
$conoracle = new PDO("oci:dbname=".$dbname2.";charset=utf8", $dbuser2, $dbpass2);
$conoracle->query("ALTER SESSION SET NLS_DATE_FORMAT = 'DD-MM-YYYY HH24:MI:SS'");
$conoracle->query("ALTER SESSION SET NLS_DATE_LANGUAGE = 'AMERICAN'");
	$bulkIns = array();
	$arrayMember = array();

	$member = array();
	$mmn = array();

	$getMe = $conoracle->prepare("SELECT WEB_CODE,MEMBER_NO,MEM_TELMOBILE,EMAIL_ADDRESS FROM mbmembmaster");
	$getMe->execute();
	$insertTOSRN = array();
	$i = 0;
	while($rowMe = $getMe->fetch(PDO::FETCH_ASSOC)){
		$pass = password_hash($rowMe["WEB_CODE"], PASSWORD_DEFAULT);
		$insertTOSRN[] = "('".$rowMe["MEMBER_NO"]."','".$pass."',null,'".
			$rowMe["MEM_TELMOBILE"]."','".$rowMe["EMAIL_ADDRESS"]."','-9','".$pass."',null,'0')";
		if(sizeof($insertTOSRN) == 1000){
			$insert = $conmysql->prepare("INSERT INTO gcmemberaccountweb(member_no,password,pin,phone_number,email,account_status,temppass,path_avatar,temppass_is_md5)
											VALUES".implode(',',$insertTOSRN));
			if($insert->execute()){
				echo 'done insert 1000';
			}else{
				echo json_encode($insert);
			}
			unset($insertTOSRN);
			$insertTOSRN = array();
		}
		$i++;
		echo $i.'/';
	}
		
	if(sizeof($insertTOSRN) > 0){
		$insert = $conmysql->prepare("INSERT INTO gcmemberaccountweb(member_no,password,pin,phone_number,email,account_status,temppass,path_avatar,temppass_is_md5)
												VALUES".implode(',',$insertTOSRN));
		$insert->execute();
	}
	echo 'done !!';

?>