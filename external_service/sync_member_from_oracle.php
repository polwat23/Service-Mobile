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
$dbpass = "@SPT2021";
$dbname = "mobile_spt";
try{
	$conmysql = new PDO("mysql:dbname={$dbname};host={$dbhost}", $dbuser, $dbpass);
	$conmysql->exec("set names utf8mb4");
}catch(Throwable $e){
	echo json_encode($e);
}
$dbuser = "iscospt";
$dbpass = "iscospt";
$dbname = "(DESCRIPTION =
			(ADDRESS_LIST =
			  (ADDRESS = (PROTOCOL = TCP)(HOST = 192.168.0.230)(PORT = 1521))
			)
			(CONNECT_DATA =
			  (SERVICE_NAME = gcoop)
			)
		  )";
$conoracle = new \PDO("oci:dbname=".$dbname.";charset=utf8", $dbuser, $dbpass);
$conoracle->query("ALTER SESSION SET NLS_DATE_FORMAT = 'DD-MM-YYYY HH24:MI:SS'");
$conoracle->query("ALTER SESSION SET NLS_DATE_LANGUAGE = 'AMERICAN'");

$bulkInsert = array();
$getMemberAll = $conoracle->prepare("SELECT MEMBER_NO,CARD_PERSON FROM mbmembmaster WHERE resign_status <> '1'");
$getMemberAll->execute();
while($rowMember = $getMemberAll->fetch(PDO::FETCH_ASSOC)){
	$password = password_hash($rowMember["CARD_PERSON"], PASSWORD_DEFAULT);
	$bulkInsert[] = "('".$rowMember["MEMBER_NO"]."','".$password."','-9','".$password."')";
	if(sizeof($bulkInsert) == 1000){
		$insertMemb = $conmysql->prepare("INSERT INTO gcmemberaccount(member_no,password,account_status,temppass)
										VALUES".implode(',',$bulkInsert));
		$insertMemb->execute();
		unset($bulkInsert);
		$bulkInsert = array();
	}
}
if(sizeof($bulkInsert) > 0){
	$insertMemb = $conmysql->prepare("INSERT INTO gcmemberaccount(member_no,password,account_status,temppass)
									VALUES".implode(',',$bulkInsert));
	$insertMemb->execute();
}
echo 'done !!';

?>