<?php
require_once('../autoloadConnection.php');
require_once(__DIR__.'/../include/lib_util.php');
require_once(__DIR__.'/../include/function_util.php');

use Utility\Library;
use Component\functions;

$lib = new library();
$func = new functions();
$listIP = [];
$fh = fopen('ip.txt','r');
while ($line = fgets($fh)) {
	$lineArray = explode('/',$line);
	$ipArray = explode('.',$lineArray[0]);
	if($ipArray[3] == '0'){
		$ipArray[3] = rand(4,255);
	}
	$listIP[] = $ipArray[0].'.'.$ipArray[1].'.'.$ipArray[2].'.'.$ipArray[3];
	
}

$selectIP = $conmysql->prepare("SELECT member_no FROM confirm_balance WHERE DATE_FORMAT(confirm_date,'%Y%m%d') >= '20230101' GROUP BY member_no");
$selectIP->execute();
$i = 0;
while($rowIP = $selectIP->fetch(PDO::FETCH_ASSOC)){	
	$updateIP = $conmysql->prepare("UPDATE confirm_balance SET ip_address = :ip_address 
								WHERE DATE_FORMAT(confirm_date,'%Y%m%d') >= '20230101' and member_no = :member_no");
	$updateIP->execute([
		':ip_address' => $listIP[$i],
		':member_no' => $rowIP["member_no"]
	]);
	$i++;
}
echo json_encode($listIP);
fclose($fh);
?>