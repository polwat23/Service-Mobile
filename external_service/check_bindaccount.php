<?php


require_once(__DIR__.'/../include/lib_util.php');

$dbuser = 'iscorfsc';
$dbpass = 'iscorfsc';
$dbname = "(DESCRIPTION =
			(ADDRESS_LIST =
			  (ADDRESS = (PROTOCOL = TCP)(HOST = 192.168.10.232)(PORT = 1521))
			)
			(CONNECT_DATA =
			  (SERVICE_NAME = gcoop)
			)
		  )";
$conoracle = new PDO("oci:dbname=".$dbname.";charset=utf8", $dbuser, $dbpass);
$conoracle->query("ALTER SESSION SET NLS_DATE_FORMAT = 'DD-MM-YYYY HH24:MI:SS'");
$conoracle->query("ALTER SESSION SET NLS_DATE_LANGUAGE = 'AMERICAN'");

use Utility\library;

$lib = new library();

$getData = $conoracle->prepare("SELECT MEMBER_NO FROM logwithdrawtransbankerror");
$getData->execute();
while($rowData = $getData->fetch(PDO::FETCH_ASSOC)){
	echo $rowData["MEMBER_NO"];
	$arrData = array();
	$arrData["member_no"] = $rowData["MEMBER_NO"];
	$api = $lib->posting_data("https://coopdirect.thaicoop.co/coopdirect/control/ktb/get_data_bind",$arrData);
	
	$data = json_decode($api);
	if(isset($data[0]->sigma_key)){
		$updateAll = $conoracle->prepare("UPDATE gcbindaccount SET bindaccount_status = '-9' WHERE sigma_key <> :sigma_key and member_no = :member_no");
		$updateAll->execute([
			':sigma_key' => $data[0]->sigma_key,
			':member_no' => $data[0]->member_no
		]);
		$updateStatus = $conoracle->prepare("UPDATE gcbindaccount SET bindaccount_status = '1',deptaccount_no_bank = :deptbank,bind_date = SYSDATE WHERE sigma_key = :sigma_key");
		$updateStatus->execute([
			':sigma_key' => $data[0]->sigma_key,
			':deptbank' => $data[0]->bank_account_no
		]);
	}
}


?>